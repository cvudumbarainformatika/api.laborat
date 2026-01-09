<?php

namespace App\Http\Controllers\Api\Simrs\Master\Tarif;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Radiologi\MasterPemeriksaanRadiologiSementara;
use App\Models\Simrs\Penunjang\Radiologi\Mpemeriksaanradiologi;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TarifRadiologiController extends Controller
{
    public function tipe()
    {
        $data = MasterPemeriksaanRadiologiSementara::select('rs3 as tipe')->distinct()->pluck('tipe');

        return new JsonResponse($data);
    }
    public function list()
    {
        $data = MasterPemeriksaanRadiologiSementara::where(function ($q) {
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
                $cekNama = MasterPemeriksaanRadiologiSementara::where('rs2', $request->nama)->first();
                if ($cekNama) throw new Exception('Nama Pemeriksaan sudah ada');
                // ambil id terakhir
                $lastData = MasterPemeriksaanRadiologiSementara::orderBy('idx', 'DESC')->first();
                $cektotal = (int) substr($lastData->rs1, 2);
                $akhir = (int) $cektotal + (int) 1;
                $kode = 'RD' . str_pad($akhir, 5, '0', STR_PAD_LEFT);
            } else {;
                $cektotal = (int) substr($request->rs1, 2);
                $kode = $request->rs1;
            }
            $result['simpan'] = MasterPemeriksaanRadiologiSementara::updateOrCreate(
                [
                    'rs1' => $kode
                ],
                [
                    'rs2' => $request->rs2,
                    'rs3' => $request->rs3 ?? '',
                    'rs4' => $request->rs4 ?? 0,
                    'rs5' => $request->rs5 ?? 0,
                    'rs6' => $request->rs6 ?? 0,
                    'rs7' => $request->rs7 ?? 0,
                    'pss' => $request->psp ?? 0,
                    'psp' => $request->asp ?? 0,
                    'tgl_mulai_berlaku' => $request->tgl_mulai_berlaku,
                    'dasar_perubahan' => $request->dasar_perubahan,
                    'idx' => $cektotal
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
        $data = MasterPemeriksaanRadiologiSementara::where('rs1', $request->kode)->first();
        $data->tgl_hapus = $request->tgl_mulai_berlaku;
        $data->hidden = '1';
        $data->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
        $data->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }
    public function showAgain(Request $request)
    {
        $data = MasterPemeriksaanRadiologiSementara::where('rs1', $request->kode)->first();
        $data->tgl_hapus = null;
        $data->hidden = '';
        $data->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
        $data->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }
    public static function pindahKeTabelMaster()
    {
        try {
            $msg = [];
            DB::beginTransaction();
            $adaTarifBerubah = MasterPemeriksaanRadiologiSementara::whereDate('tgl_mulai_berlaku', date('Y-m-d'))->get();
            if ($adaTarifBerubah) {
                foreach ($adaTarifBerubah as $baru) {
                    $simpantindakan = Mpemeriksaanradiologi::withTrashed()->updateOrCreate(
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
                            'pss' => $baru['psp'],
                            'psp' => $baru['asp'],
                        ]
                    );
                    $data = Mpemeriksaanradiologi::where('rs1', $baru['rs1'])->first();
                    $dataSudahHapus = Mpemeriksaanradiologi::onlyTrashed()->where('rs1', $baru['rs1'])->first();
                    if ($data && $baru['tgl_hapus'] != null && !$dataSudahHapus) {
                        $data->delete();
                    }
                    if ($baru['tgl_hapus'] == null && $dataSudahHapus) {
                        $dataSudahHapus->restore();
                    }
                }
            }
            DB::commit();
            return ['jumlah Pemeriksaan lab' => count($adaTarifBerubah)];
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
