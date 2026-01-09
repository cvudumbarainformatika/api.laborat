<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Api\Simrs\Master\Tarif\PemeriksaanLaboratControllr;
use App\Http\Controllers\Api\Simrs\Master\Tarif\TarifMasterAmbulanController;
use App\Http\Controllers\Api\Simrs\Master\Tarif\TarifRadiologiController;
use App\Http\Controllers\Api\Simrs\Master\Tarif\TindakanOperasiController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mtindakan;
use App\Models\Simrs\Master\MtindakanSementara;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class TindakanController extends Controller
{
    public function listtindakan()
    {
        $listtindakan = MtindakanSementara::select(
            'rs1 as kdtindakan',
            'rs2 as nmtindakan',
            'rs4 as ruangan',
            'rs8 as js3',
            'rs9 as jp3',
            'rs10 as habispake3',
            DB::raw('rs8+rs9 as tarif3'),
            'rs11 as js2',
            'rs12 as jp2',
            'rs13 as habispake2',
            DB::raw('rs11+rs12 as tarif2'),
            'rs14 as js1',
            'rs15 as jp1',
            'rs16 as habispake1',
            DB::raw('rs14+rs15 as tarif1'),
            'rs17 as jsutama',
            'rs18 as jputama',
            'rs19 as habispakeutama',
            DB::raw('rs17+rs18 as tarifutama'),
            'rs20 as jsvip',
            'rs21 as jpvip',
            'rs22 as habispakevip',
            DB::raw('rs20+rs21 as tarifvip'),
            'rs23 as jsvvip',
            'rs24 as jpvvip',
            'rs25 as habispakevvip',
            DB::raw('rs23+rs24 as tarifvvip'),
            'pss as js_presidential',
            'psp as jp_presidential',
            'habispake_presidential',
            DB::raw('pss+psp+habispake_presidential as tarif_presidential'),
            'js_hcu',
            'jp_hcu',
            'habispake_hcu',
            DB::raw('js_hcu+jp_hcu+habispake_hcu as tarif_hcu'),
            'js_hc',
            'jp_hc',
            'habispake_hc',
            DB::raw('js_hc+jp_hc+habispake_hc as tarif_hc'),
            'tgl_hapus',
            'tgl_mulai_berlaku',
            'dasar_perubahan',
        )->where('rs2', 'like', '%' . request('nmtindakan') . '%')
            ->paginate(request('per_page'));
        return new JsonResponse($listtindakan);
    }

    public function simpanmastertindakan(Request $request)
    {

        if ($request->kdtindakan == '' || $request->kdtindakan == null) {
            $ceknama = Mtindakan::where('rs2', $request->nmtindakan)->count();
            if ($ceknama > 0) {
                return new JsonResponse(['message' => 'Maaf Tindakan Sudah Ada...!!!'], 500);
            }

            $cektotal = Mtindakan::count();
            $akhir = (int) $cektotal + (int) 1;

            $has = null;
            $lbr = strlen($akhir);
            for ($i = 1; $i <= 4 - $lbr; $i++) {
                $has = $has . "0";
            }

            $kdtindakan = 'TB' . $has . $akhir;
        } else {
            $kdtindakan = $request->kdtindakan;
        }
        $simpantindakan = Mtindakan::updateOrCreate(
            [
                'rs1' => $kdtindakan
            ],
            [
                'rs2' => $request->nmtindakan,
                'rs3' => 'T1#',
                'rs8' => $request->js3,
                'rs9' => $request->jp3,
                'rs10' => $request->habispake3,
                'rs11' => $request->js2,
                'rs12' => $request->jp2,
                'rs13' => $request->habispake2,
                'rs14' => $request->js1,
                'rs15' => $request->jp1,
                'rs16' => $request->habispake1,
                'rs17' => $request->jsutama,
                'rs18' => $request->jputama,
                'rs19' => $request->habispakeutama,
                'rs20' => $request->jsvip,
                'rs21' => $request->jpvip,
                'rs22' => $request->habispakevip,
                'rs23' => $request->jsvvip,
                'rs24' => $request->jpvvip,
                'rs25' => $request->habispakevvip
            ]
        );
        if (!$simpantindakan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 410);
        }
        return new JsonResponse($simpantindakan);
    }

    public function hidden(Request $request)
    {
        $caritindakan = MtindakanSementara::where('rs1', $request->kdtindakan)->first();
        $caritindakan->tgl_hapus = $request->tgl_mulai_berlaku;
        $caritindakan->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
        $caritindakan->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }
    public function showAgain(Request $request)
    {
        $caritindakan = MtindakanSementara::where('rs1', $request->kdtindakan)->first();
        $caritindakan->tgl_hapus = null;
        $caritindakan->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
        $caritindakan->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }
    public function simpanTindakanKeTabelSementara(Request $request)
    {

        if ($request->kdtindakan == '' || $request->kdtindakan == null) {
            $ceknama = MtindakanSementara::where('rs2', $request->nmtindakan)->count();
            if ($ceknama > 0) {
                return new JsonResponse(['message' => 'Maaf Tindakan Sudah Ada...!!!'], 500);
            }

            $cektotal = MtindakanSementara::count();
            $akhir = (int) $cektotal + (int) 1;

            $has = null;
            $lbr = strlen($akhir);
            for ($i = 1; $i <= 4 - $lbr; $i++) {
                $has = $has . "0";
            }

            $kdtindakan = 'TB' . $has . $akhir;
        } else {
            $kdtindakan = $request->kdtindakan;
        }
        $simpantindakan = MtindakanSementara::updateOrCreate(
            [
                'rs1' => $kdtindakan
            ],
            [
                'rs2' => $request->nmtindakan,
                'rs3' => 'T1#',
                'rs8' => $request->js3,
                'rs9' => $request->jp3,
                'rs10' => $request->habispake3,
                'rs11' => $request->js2,
                'rs12' => $request->jp2,
                'rs13' => $request->habispake2,
                'rs14' => $request->js1,
                'rs15' => $request->jp1,
                'rs16' => $request->habispake1,
                'rs17' => $request->jsutama,
                'rs18' => $request->jputama,
                'rs19' => $request->habispakeutama,
                'rs20' => $request->jsvip,
                'rs21' => $request->jpvip,
                'rs22' => $request->habispakevip,
                'rs23' => $request->jsvvip,
                'rs24' => $request->jpvvip,
                'rs25' => $request->habispakevvip,
                'tgl_mulai_berlaku' => $request->tgl_mulai_berlaku,
                'dasar_perubahan' => $request->dasar_perubahan,
                'pss' => $request->js_presidential,
                'psp' => $request->jp_presidential,
                'habispake_presidential' => $request->habispake_presidential,
                'js_hcu' => $request->js_hcu,
                'jp_hcu' => $request->jp_hcu,
                'habispake_hcu' => $request->habispake_hcu,
                'js_hc' => $request->js_hc,
                'jp_hc' => $request->jp_hc,
                'habispake_hc' => $request->habispake_hc,
                'rs4' => $request->ruangan,
            ]
        );
        if (!$simpantindakan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 410);
        }
        return new JsonResponse($simpantindakan);
    }
    public static function pindahKeTabelMaster()
    {
        try {
            $msg = [];
            DB::beginTransaction();
            $adaTarifBerubah = MtindakanSementara::whereDate('tgl_mulai_berlaku', date('Y-m-d'))->get();
            if ($adaTarifBerubah) {
                foreach ($adaTarifBerubah as $baru) {
                    $data = Mtindakan::where('rs1', $baru['rs1'])->first();
                    $dataSudahHapus = Mtindakan::onlyTrashed()->where('rs1', $baru['rs1'])->first();
                    $simpantindakan = Mtindakan::withTrashed()->updateOrCreate(
                        [
                            'rs1' => $baru['rs1']
                        ],
                        [
                            'rs2' => $baru['rs2'],
                            'rs3' => $baru['rs3'],
                            'rs8' => $baru['rs8'],
                            'rs9' => $baru['rs9'],
                            'rs10' => $baru['rs10'],
                            'rs11' => $baru['rs11'],
                            'rs12' => $baru['rs12'],
                            'rs13' => $baru['rs13'],
                            'rs14' => $baru['rs14'],
                            'rs15' => $baru['rs15'],
                            'rs16' => $baru['rs16'],
                            'rs17' => $baru['rs17'],
                            'rs18' => $baru['rs18'],
                            'rs19' => $baru['rs19'],
                            'rs20' => $baru['rs20'],
                            'rs21' => $baru['rs21'],
                            'rs22' => $baru['rs22'],
                            'rs23' => $baru['rs23'],
                            'rs24' => $baru['rs24'],
                            'rs25' => $baru['rs25'],
                            'psp' => $baru['psp'],
                            'pss' => $baru['pss'],
                            'js_presidential' => $baru['js_presidential'],
                            'jp_presidential' => $baru['jp_presidential'],
                            'habispake_presidential' => $baru['habispake_presidential'],
                            'js_hcu' => $baru['js_hcu'],
                            'jp_hcu' => $baru['jp_hcu'],
                            'habispake_hcu' => $baru['habispake_hcu'],
                            'js_hc' => $baru['js_hc'],
                            'jp_hc' => $baru['jp_hc'],
                            'habispake_hc' => $baru['habispake_hc'],
                            'rs4' => $baru['rs4'],
                        ]
                    );
                    if ($data && $baru['tgl_hapus'] != null && !$dataSudahHapus) {

                        $data->delete();
                    }
                    if ($baru['tgl_hapus'] == null && $dataSudahHapus) {
                        $dataSudahHapus->restore();
                    }
                }
            }
            DB::commit();
            return ['jumlah' => count($adaTarifBerubah)];
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),

            ], 410);
        }
    }
    public function aksesPindahTable()
    {
        $data['tindakan'] = self::pindahKeTabelMaster();
        $data['tarifLab'] = PemeriksaanLaboratControllr::pindahKeTabelMaster();
        $data['tarifTindOk'] = TindakanOperasiController::pindahKeTabelMaster();
        $data['tarifRadiologi'] = TarifRadiologiController::pindahKeTabelMaster();
        $data['ambulan'] = TarifMasterAmbulanController::pindahKeTabelMaster();
        return $data;
    }
}
