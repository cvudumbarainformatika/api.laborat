<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Ruang;
use App\Models\Sigarang\Transaksi\DistribusiLangsung\DistribusiLangsung;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DistribusiLangsungController extends Controller
{
    // ambil data barang dan transaksi sekarang
    public function getDataBarangWithTransaksi()
    {
        /*
        * ambil data barang, join, ambil yang ada stoknya saja di recent stok update.
        * barang yang diambil yang punya depo gizi saja
        * beserta data transaksi berdasarkan no reff (jika ada)
        */
        $paginate = request('per_page') ? request('per_page') : 10;
        $ruang = 'Gd-02010102';
        $data = BarangRS::with([
            'detailDistribusiLangsung' => function ($detail) {
                $detail->select(
                    'detail_distribusi_langsungs.*',
                    'distribusi_langsungs.*',
                )
                    ->join('distribusi_langsungs', function ($langsung) {
                        $langsung->on('detail_distribusi_langsungs.distribusi_langsung_id', '=', 'distribusi_langsungs.id')
                            ->where('status', '=', 1)
                            ->where('reff', request('reff'));
                    });
            }
        ])
            // join where has recent stok > 0
            ->select(
                'barang_r_s.*',
                // 'sum(recent_stok_updates.sisa_stok) as totalStok',
                'recent_stok_updates.sisa_stok',
                'recent_stok_updates.kode_ruang',
                'recent_stok_updates.kode_rs',
            )
            ->join('recent_stok_updates', function ($wew) {
                $wew->on('recent_stok_updates.kode_rs', '=', 'barang_r_s.kode')
                    ->where('sisa_stok', '>', 0);
            })
            ->where('kode_depo', $ruang)
            ->paginate($paginate);

        return new JsonResponse($data);
    }
    //
    public function index()
    {
        $data = DistribusiLangsung::latest('id')
            ->paginate(request('per_page'));
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function getStokDepo()
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        //  kusus depo gizi
        $ruang = 'Gd-02010102';
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as totalStok')
            ->where('sisa_stok', '>', 0)
            ->where('kode_ruang', $ruang)
            ->groupBy('kode_rs', 'kode_ruang')
            ->with('barang', 'depo', 'satuan')
            ->get();

        return new JsonResponse($data, 200);
    }

    public function getRuang()
    {
        $data = Ruang::oldest('id')
            ->filter(request(['q']))
            ->limit(15)
            ->get();
        // return RuangResource::collection($data);
        // $collect = collect($data);
        // $balik = $collect->only('data');
        // $balik['meta'] = $collect->except('data');

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        // ini belum termasuk fifo
        try {
            DB::beginTransaction();

            $valid = Validator::make($request->all(), ['reff' => 'required']);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }
            $data = DistribusiLangsung::updateOrCreate(['reff' => $request->reff], $request->all());
            if ($request->has('kode_rs') && $request->kode_rs !== null) {
                $data->details()->updateOrCreate(['kode_rs' => $request->kode_rs], $request->all());
            }

            DB::commit();

            return new JsonResponse([
                'message' => 'success',
                'data' => $data,
                // 'gudang' => $gudang,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
        return new JsonResponse($request->all());
    }
}
