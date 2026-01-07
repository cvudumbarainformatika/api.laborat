<?php

namespace App\Http\Controllers\Api\Simrs\Master\Tarif;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\MpemeriksaaanLabSementara;
use App\Models\Simrs\Master\Mpemeriksaanlab;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Event\Code\Throwable;

class PemeriksaanLaboratControllr extends Controller
{
    //
    public function list()
    {
        $data = MpemeriksaaanLabSementara::select(
            'rs1',
            'rs1 as kode',
            'rs2 as nama',
            'rs3 as hs1', // harga sarana 1
            'rs4 as hp1', // harga pelayanan 1
            DB::raw('rs3+rs4 as tf1'),
            'rs5 as hs2', // harga sarana 2
            'rs6 as hp2', // harga pelayanan 2
            DB::raw('rs5+rs6 as tf2'),
            'pss', // presiden suit sarana
            'psp', // presiden suit pelayanan
            DB::raw('pss+psp as tfps'),
            'hcus', // HCU sarana
            'hcup', // HCU pelayanan
            DB::raw('hcus+hcup as tfhcu'),
            'hcs', // Home Care sarana
            'hcp', // Home Care pelayanan
            DB::raw('hcs+hcp as tfhc'),
            'rs21 as kelompok', // nama paket 
            'rs22 as satuan',
            'rs23 as flag',
            'rs24',
            'rs25 as cito',
            'hidden',
            'nilainormal',
            'satuan',
            'tampilanurut',
            'jenislab',
            'loinc',
            'display_loinc',
            'loinc_paket',
            'display_loinc_paket',
            'tgl_mulai_berlaku',
            'tgl_hapus',
            'dasar_perubahan',
        )
            ->where(function ($q) {
                $q->where('rs2', 'like', '%' . request('q') . '%')
                    ->orWhere('rs21', 'like', '%' . request('q') . '%');
            })
            ->paginate(request('per_page'));
        $rawRes = collect($data);
        $result['data'] = $rawRes['data'];
        $result['meta'] = $rawRes->except('data');
        return new JsonResponse($result);
    }
    public function listKelompok()
    {
        $data = MpemeriksaaanLabSementara::select('rs21 as kelompok')->distinct()->pluck('kelompok');

        return new JsonResponse($data);
    }
    public function listJenis()
    {
        $data = MpemeriksaaanLabSementara::select('jenislab as jenislab')->distinct()->pluck('jenislab');

        return new JsonResponse($data);
    }
    public function simpan(Request $request)
    {
        try {
            DB::beginTransaction();
            if ($request->kode == '' || $request->kode == null) {
                $cekNama = MpemeriksaaanLabSementara::where('rs2', $request->nama)->first();
                if ($cekNama) throw new Exception('Nama Pemeriksaan sudah ada');
                $cektotal = MpemeriksaaanLabSementara::count();
                $akhir = (int) $cektotal + (int) 1;
                if ($request->jenislab == 'PK' || $request->jenislab == '') $kode = 'LAB' . str_pad($akhir, 4, '0', STR_PAD_LEFT);
                else $kode = $request->jenislab . str_pad($akhir, 4, '0', STR_PAD_LEFT);
            } else {
                $kode = $request->kode;
            }
            $result['simpan'] = MpemeriksaaanLabSementara::updateOrCreate(
                [
                    'rs1' => $kode
                ],
                [
                    'rs2' => $request->nama,
                    'rs3' => $request->hs1 ?? 0,
                    'rs4' => $request->hp1 ?? 0,
                    'rs5' => $request->hs2 ?? 0,
                    'rs6' => $request->hp2 ?? 0,
                    'pss' => $request->pss ?? 0,
                    'psp' => $request->psp ?? 0,
                    'hcus' => $request->hcus ?? 0,
                    'hcup' => $request->hcup ?? 0,
                    'hcs' => $request->hcs ?? 0,
                    'hcp' => $request->hcp ?? 0,
                    'rs21' => $request->kelompok ?? '',
                    'jenislab' => $request->jenislab ?? '',
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
        $caritindakan = MpemeriksaaanLabSementara::where('rs1', $request->kode)->first();
        $caritindakan->tgl_hapus = $request->tgl_mulai_berlaku;
        $caritindakan->hidden = '1';
        $caritindakan->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
        $caritindakan->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }
    public function showAgain(Request $request)
    {
        $caritindakan = MpemeriksaaanLabSementara::where('rs1', $request->kode)->first();
        $caritindakan->tgl_hapus = null;
        $caritindakan->hidden = '';
        $caritindakan->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
        $caritindakan->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }
    public static function pindahKeTabelMaster()
    {
        try {
            $msg = [];
            DB::beginTransaction();
            $adaTarifBerubah = MpemeriksaaanLabSementara::whereDate('tgl_mulai_berlaku', date('Y-m-d'))->get();
            if ($adaTarifBerubah) {
                foreach ($adaTarifBerubah as $baru) {
                    $data = Mpemeriksaanlab::where('rs1', $baru['rs1'])->first();
                    $dataSudahHapus = Mpemeriksaanlab::onlyTrashed()->where('rs1', $baru['rs1'])->first();
                    $simpantindakan = Mpemeriksaanlab::withTrashed()->updateOrCreate(
                        [
                            'rs1' => $baru['rs1']
                        ],
                        [
                            'rs2' => $baru['rs2'],
                            'rs3' => $baru['rs3'],
                            'rs4' => $baru['rs4'],
                            'rs5' => $baru['rs5'],
                            'rs6' => $baru['rs6'],
                            'rs21' => $baru['rs21'],
                            'rs22' => $baru['rs22'],
                            'rs23' => $baru['rs23'],
                            'rs24' => $baru['rs24'],
                            'rs25' => $baru['rs25'],
                            'pss' => $baru['pss'],
                            'psp' => $baru['psp'],
                            'hcus' => $baru['hcus'],
                            'hcup' => $baru['hcup'],
                            'hcs' => $baru['hcs'],
                            'hcp' => $baru['hcp'],
                            'hidden' => $baru['hidden'],
                            'jenislab' => $baru['jenislab'],
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
