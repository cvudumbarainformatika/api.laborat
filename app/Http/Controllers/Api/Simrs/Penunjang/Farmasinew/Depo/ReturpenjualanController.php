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
        $carinoresep = Resepkeluarheder::select('resep_keluar_h.*', 'resep_keluar_h.dokter as kddokter')
            ->with(
                [
                    'rincian.mobat:kd_obat,nama_obat,satuan_k,kandungan,status_generik,status_forkid,status_fornas,kode108,uraian108,kode50,uraian50',
                    'rincianracik.mobat:kd_obat,nama_obat,satuan_k,kandungan,status_generik,status_forkid,status_fornas,kode108,uraian108,kode50,uraian50',
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
            ->when(request('flag'), function ($x) {
                $x->whereIn('flag', request('flag'));
            })
            ->when(!request('flag'), function ($x) {
                $x->where('flag', '3');
            })
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
    public function newreturpenjualan(Request $request)
    {

        $user = FormatingHelper::session_user();
        $req = $request->all();
        $tmpRin = collect($req['listObat'])->sum('jumlah_retur');
        $tmpRac = collect($req['rincianracik'])->sum('jumlah_retur');
        // $tmpRinMap = $tmpRin->sum('jumlah_retur');
        // cek bahwa ada yang diretur
        if ($tmpRin <= 0 && $tmpRac <= 0) {
            return new JsonResponse(['message' => 'Tidak ada Jumlah Obat yang akan diretur'], 410);
        }

        if ($request->noretur == '' || $request->noretur == null) {
            DB::connection('farmasi')->select('call returpenjualan(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('returpenjualan')->first();
            $wew = $x->returpenjualan;
            $noretur = FormatingHelper::penerimaanobat($wew, '-RET-PEN');
        } else {
            $noretur = $request->noretur;
        }
        $noret = ['noretur' => $noretur];

        $isiHead = [
            'tgl_retur' => date('Y-m-d H:i:s'),
            'noresep' => $request->noresep,
            'noreg' => $request->noreg,
            'norm' => $request->norm,
            'kddokter' => $request->kddokter,
            'kdruangan' => $request->ruangan,
            'user' => $user['kodesimrs']
        ];

        $rinci = [];
        $racik = [];

        if (count($request->listObat)) {
            foreach ($request->listObat as $key) {
                if ($key['jumlah_retur'] > 0) {
                    $temp = [
                        'noretur' => $noretur,
                        'noreg' => $request->noreg,
                        'kdobat' => $key['kdobat'],
                        'kandungan' => $key['mobat']['kandungan'],
                        'fornas' => $key['mobat']['status_generik'],
                        'forkit' => $key['mobat']['status_forkid'],
                        'generik' => $key['mobat']['status_fornas'],
                        'kode108' => $key['mobat']['kode108'],
                        'uraian108' => $key['mobat']['uraian108'],
                        'kode50' => $key['mobat']['kode50'],
                        'uraian50' => $key['mobat']['uraian50'],
                        'nopenerimaan' => $key['nopenerimaan'],
                        'jumlah_keluar' => $key['jumlah_keluar'],
                        'jumlah_retur' => $key['jumlah_retur'],
                        'user' => $user['kodesimrs']
                    ];
                    array_push($rinci, $temp);
                }
            }
        }
        if (count($request->rincianracik)) {
            foreach ($request->rincianracik as $key) {
                if ($key['jumlah_retur'] > 0) {
                    $temp = [
                        'noretur' => $noretur,
                        'noreg' => $request->noreg,
                        'kdobat' => $key['kdobat'],
                        'kandungan' => $key['mobat']['kandungan'],
                        'fornas' => $key['mobat']['status_generik'],
                        'forkit' => $key['mobat']['status_forkid'],
                        'generik' => $key['mobat']['status_fornas'],
                        'kode108' => $key['mobat']['kode108'],
                        'uraian108' => $key['mobat']['uraian108'],
                        'kode50' => $key['mobat']['kode50'],
                        'uraian50' => $key['mobat']['uraian50'],
                        'nopenerimaan' => $key['nopenerimaan'],
                        'jumlah_keluar' => $key['jumlah_keluar'],
                        'jumlah_retur' => $key['jumlah_retur'],
                        'user' => $user['kodesimrs']
                    ];
                    array_push($racik, $temp);
                }
            }
        }
        // stok kembali ke gudang atau ke depo?
        try {
            DB::beginTransaction();
            $simpanHeader = Returpenjualan_h::firstOrCreate($noret, $isiHead);
            if (!$simpanHeader) {
                return new JsonResponse(['message' => 'Header Data Gagal Disimpan'], 410);
            }
            if (count($rinci)) {
                Returpenjualan_r::insert($rinci);
            }
            if (count($racik)) {
                Returpenjualan_r::insert($racik);
            }
            $permintaanHead = Resepkeluarheder::where('noresep', $request->noresep)->where('noreg', $request->noreg)->first();
            if ($permintaanHead) {
                $permintaanHead->flag = '4';
                $permintaanHead->save();
            }
            DB::commit();
            $simpanHeader->load('rinci');
            return new JsonResponse([
                'message' => 'retur disimpan',
                'data' => $simpanHeader
            ]);
        } catch (\Exception $e) {
            // May day,  rollback!!! rollback!!!
            DB::rollback();
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $e], 410);
        }
    }
}
