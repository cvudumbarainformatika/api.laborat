<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Rstigapuluhtarif as MasterRstigapuluhtarif;
use App\Models\Simrs\Master\TarifVisiteDanKamarSementara;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RsTigaPuluhTarifController extends Controller
{
    public function gettigapuluhtarif()
    {
        $data = MasterRstigapuluhtarif::get();
        return new JsonResponse($data);
    }

    public function list()
    {
        $data = TarifVisiteDanKamarSementara::where(function ($q) {
            $q->where('rs2', 'like', '%' . request('q') . '%')
                ->orWhere('rs1', 'like', '%' . request('q') . '%');
        })
            ->paginate(request('per_page'));
        $rawRes = collect($data);
        $result['data'] = $rawRes['data'];
        $result['meta'] = $rawRes->except('data');
        return new JsonResponse($result);
    }
    public function simpan(Request $request)
    {

        try {
            DB::beginTransaction();
            if ($request->rs1 == '' || $request->rs1 == null) {
                throw new Exception('Tarif Visite dan Kamar Belum bisa ditambahkan dari sistem');
                $cekNama = TarifVisiteDanKamarSementara::where('rs2', $request->nama)->first();
                if ($cekNama) throw new Exception('Nama Tujuan sudah ada');
                // ambil id terakhir
                $lastData = TarifVisiteDanKamarSementara::orderBy('id', 'DESC')->first();
                $cektotal = $lastData->id;
                $akhir = (int) $cektotal + (int) 1;
                $kode = 'TV' . str_pad($akhir, 3, '0', STR_PAD_LEFT);
            } else {
                $kode = $request->rs1;
            }
            $result['simpan'] = TarifVisiteDanKamarSementara::updateOrCreate(
                [
                    'rs1' => $kode
                ],
                [
                    'rs2' => $request->rs2,
                    // 'rs4' => $request->rs4,
                    // 'rs5' => $request->rs5,
                    'rs6' => $request->rs6 ?? 0,
                    'rs7' => $request->rs7 ?? 0,
                    'rs8' => $request->rs8 ?? 0,
                    'rs9' => $request->rs9 ?? 0,
                    'rs10' => $request->rs10 ?? 0,
                    'rs11' => $request->rs11 ?? 0,
                    'rs12' => $request->rs12 ?? 0,
                    'rs13' => $request->rs13 ?? 0,
                    'rs14' => $request->rs14 ?? 0,
                    'rs15' => $request->rs15 ?? 0,
                    'rs16' => $request->rs16 ?? 0,
                    'rs17' => $request->rs17 ?? 0,
                    'hcus' => $request->hcus ?? 0,
                    'hcup' => $request->hcup ?? 0,
                    'icus' => $request->icus ?? 0,
                    'icup' => $request->icup ?? 0,
                    'iccus' => $request->iccus ?? 0,
                    'iccup' => $request->iccup ?? 0,
                    'nicus' => $request->nicus ?? 0,
                    'nicup' => $request->nicup ?? 0,
                    'ins' => $request->ins ?? 0,
                    'inp' => $request->inp ?? 0,
                    'isos' => $request->isos ?? 0,
                    'isop' => $request->isop ?? 0,
                    'pss' => $request->pss ?? 0,
                    'psp' => $request->psp ?? 0,
                    'tgl_mulai_berlaku' => $request->tgl_mulai_berlaku,
                    'dasar_perubahan' => $request->dasar_perubahan,
                ]
            );
            $result['message'] = 'Data Berhasil Disimpan';
            DB::commit();
            return new JsonResponse($result);
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
    public function hidden(Request $request)
    {
        $data = TarifVisiteDanKamarSementara::where('rs1', $request->kode)->first();
        $data->tgl_hapus = $request->tgl_mulai_berlaku;
        // $data->flag = '1';
        $data->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
        $data->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }
    public function showAgain(Request $request)
    {
        $data = TarifVisiteDanKamarSementara::where('rs1', $request->kode)->first();
        $data->tgl_hapus = null;
        // $data->flag = '';
        $data->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
        $data->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }
    public static function pindahKeTabelMaster()
    {
        try {
            $msg = [];
            DB::beginTransaction();
            $adaTarifBerubah = TarifVisiteDanKamarSementara::whereDate('tgl_mulai_berlaku', date('Y-m-d'))->get();
            if ($adaTarifBerubah) {
                foreach ($adaTarifBerubah as $baru) {
                    $simpantindakan = MasterRstigapuluhtarif::withTrashed()->updateOrCreate(
                        [
                            'rs1' => $baru['rs1']
                        ],
                        [
                            'rs2' => $baru['rs2'],
                            'rs3' => $baru['rs3'],
                            'rs4' => $baru['rs4'],
                            'rs5' => $baru['rs5'],
                            'rs6' => $baru['rs6'],
                            'rs7' => $baru['rs7'],
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
                            'hcus' => $baru['hcus'],
                            'hcup' => $baru['hcup'],
                            'icus' => $baru['icus'],
                            'icup' => $baru['icup'],
                            'iccus' => $baru['iccus'],
                            'iccup' => $baru['iccup'],
                            'nicus' => $baru['nicus'],
                            'nicup' => $baru['nicup'],
                            'ins' => $baru['ins'],
                            'inp' => $baru['inp'],
                            'isos' => $baru['isos'],
                            'isop' => $baru['isop'],
                            'pss' => $baru['pss'],
                            'psp' => $baru['psp'],
                        ]
                    );
                    $data = MasterRstigapuluhtarif::where('rs1', $baru['rs1'])->first();
                    $dataSudahHapus = MasterRstigapuluhtarif::onlyTrashed()->where('rs1', $baru['rs1'])->first();
                    if ($data && $baru['tgl_hapus'] != null && !$dataSudahHapus) {
                        $data->delete();
                    }
                    if ($baru['tgl_hapus'] == null && $dataSudahHapus) {
                        $dataSudahHapus->restore();
                    }
                }
            }
            DB::commit();
            return ['jumlah Tarif Ambulance' => count($adaTarifBerubah)];
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
}
