<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Bast;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BastController extends Controller
{

    public function perusahaan()
    {
        // ambil perusahaan yang sedang belum bast
        $raw = PenerimaanHeder::select('kdpbf')
            ->where(function ($q) {
                $q->whereNull('tgl_bast')
                    ->orWhere('jumlah_bast', '<=', 0);
            })
            ->where('jenis_penerimaan', 'Pesanan')
            ->get();
        // map kode perusahaan ke bentuk array, biar aman jika nanti ada append
        $temp = collect($raw)->map(function ($y) {
            return $y->kdpbf;
        });
        $data = KontrakPengerjaan::select('kodeperusahaan', 'namaperusahaan')->whereIn('kodeperusahaan', $temp)->distinct()->get();

        return new JsonResponse($data);
    }
    public function pemesanan()
    {
        $data = PemesananHeder::where('kdpbf', request('kdpbf'))->get();
        return new JsonResponse($data);
    }
    public function penerimaan()
    {
        // return new JsonResponse(request()->all());
        $data = PenerimaanHeder::where('kdpbf', request('kdpbf'))
            ->where('nopemesanan', request('nopemesanan'))
            ->where(function ($q) {
                $q->whereNull('tgl_bast')
                    ->orWhere('jumlah_bast', '<=', 0);
            })
            ->where('jenis_penerimaan', 'Pesanan')
            ->with([
                'penerimaanrinci' => function ($tr) {
                    $tr->selectRaw('penerimaan_r.*, sum(retur_penyedia_r.subtotal) as nilai_retur, retur_penyedia_r.jumlah_retur')
                        ->leftJoin('retur_penyedia_h', 'retur_penyedia_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
                        ->leftJoin('retur_penyedia_r', function ($join) {
                            $join->on('retur_penyedia_r.no_retur', '=', 'retur_penyedia_h.no_retur')
                                ->on('retur_penyedia_r.kd_obat', '=', 'penerimaan_r.kdobat');
                        })
                        ->with('masterobat:kd_obat,nama_obat,satuan_b')
                        ->groupBy('penerimaan_r.kdobat');
                },
                'faktur'
            ])
            ->get();
        return new JsonResponse($data);
    }
    public function simpan(Request $request)
    {
        return new JsonResponse($request->all());

        if ($request->nobast === '' || $request->nobast === null) {
            DB::connection('farmasi')->select('call nobast(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('bast')->get();
            $wew = $x[0]->bast;
            $nobast = FormatingHelper::penerimaanobat($wew, 'BAST-FAR');
        } else {
            $nobast = $request->nobast;
        }
    }
}
