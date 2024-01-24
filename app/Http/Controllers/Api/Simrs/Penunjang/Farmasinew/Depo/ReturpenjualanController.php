<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokrealController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_h;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_r;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturpenjualanController extends Controller
{
    public function caribynoresep()
    {

        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('from') . ' 00:00:00';
            $tglx = request('to') . ' 23:59:59';
        }
        $carinoresep = Resepkeluarheder::with(
            [
                'rincian.mobat:kd_obat,nama_obat,satuan_k',
                'rincianracik.mobat:kd_obat,nama_obat,satuan_k',
                'datapasien:rs1,rs2,rs3,rs17,rs16,rs46,rs49',
                'dokter:kdpegsimrs,nama',
                'ruanganranap:rs1,rs2',
                'poli:rs1,rs2',
                'sistembayar:rs1,rs2'
            ]
        )
            ->where(function ($query) {
                $query->where('noresep', 'like', '%' . request('q') . '%')
                    ->orWhere('norm', 'LIKE', '%' . request('q') . '%');
            })
            ->where('depo', request('kddepo'))
            ->whereBetween('tgl_permintaan', [$tgl, $tglx])
            ->whereIn('flag', request('flag'))
            ->orderBy('tgl', 'ASC')
            ->paginate(request('per_page'));
        return new JsonResponse(
            [
                'result' => $carinoresep
            ]
        );
    }

    public function returpenjualan(Request $request)
    {
        $data = $request->all();
        return new JsonResponse($data);
        if ($request->noretur == '' || $request->noretur == null) {
            DB::connection('farmasi')->select('call returpenjualan');
            $x = DB::connection('farmasi')->table('conter')->select('returpenjualan')->get();
            $wew = $x[0]->returpenjualan;
            $noretur = FormatingHelper::penerimaanobat($wew, '-RET-PEN');
        } else {
            $noretur = $request->noretur;
        }

        $user = FormatingHelper::session_user();
        $simpanheder = Returpenjualan_h::firstorcreate(
            [
                'noretur' => $noretur
            ],
            [
                'tgl_retur' => $noretur,
                'noresep' => $request->noresep,
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'kddokter' => $request->kddokter,
                'kdruangan' => $request->kdruangan,
                'user' => $user['kodesimrs']
            ]
        );
        if (!$simpanheder) {
            return new JsonResponse(['message' => 'Maaf Data Gagal Disimpan'], 500);
        }

        $simpanrinci = Returpenjualan_r::create(
            [
                'noretur' => $noretur,
                'noreg' => $request->noreg,
                'kdobat' => $request->kdobat,
                'kandungan' => $request->kandungan,
                'fornas' => $request->fornas,
                'forkit' => $request->forkit,
                'generik' => $request->generik,
                'kode108' => $request->kode108,
                'uraian108' => $request->uraian108,
                'kode50' => $request->kode50,
                'uraian50' => $request->uraian50,
                'nopenerimaan' => $request->nopenerimaan,
                'jumlah_keluar' => $request->jumlah_keluar,
                'jumlah_retur' => $request->jumlah_retur,
                'user' => $user['kodesimrs']
            ]
        );
        $updatestok = StokrealController::updatestokdepo($request);
        return new JsonResponse(
            [
                'message' => 'Data Berhasil Disimpan...!!!',
                'heder' => $simpanheder,
                'rinci' => $simpanrinci->load('mobatnew'),
            ],
            200
        );
    }
}
