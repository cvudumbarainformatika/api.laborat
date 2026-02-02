<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Laporan\Operasi\LaporanOperasi;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Kamaroperasi\Masteroperasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TindakanDanLaporanController extends Controller
{
    public function getTindakanOp()
    {
        $data = Masteroperasi::select(
            'idx',
            'rs1 as kode',
            'rs2 as nama',
            'rs4 as jenis',
            'rs5 as smf',
            'rs6 as js3', // igd masuk tarif kelas 3
            'rs7 as jp3',
            'rs8 as an3',
            DB::raw('(rs6+rs7+rs8) as tarif3'),
            'rs9 as js1',
            'rs10 as jp1',
            'rs11 as an1',
            DB::raw('(rs9+rs10+rs11) as tarif1'),
            'rs12 as js_poli',
            'rs13 as jp_poli',
            DB::raw('(rs12+rs13) as tarif_poli'),
            'ssp',
            'psp',
            'asp',
            DB::raw('(ssp+psp+asp) as tarif_presiden'),
        )
            ->when(request('q'), function ($q) {
                $q->where('rs2', 'like', '%' . request('q') . '%');
            })
            ->get();

        return new JsonResponse([
            'data' => $data
        ]);
    }

    public function simpanTindakanOp(Request $request)
    {
        $cekLaporanOperasi = LaporanOperasi::where('rs1', $request->noreg)->where('rs2', $request->nota)->first();
        if ($cekLaporanOperasi)  return new JsonResponse(['message' => 'Laporan Operasi sudah dibuatkan, tidak boleh update tindakan operasi'], 410);
        $request->validate(
            [
                'noreg' => 'required|string',
                'nota' => 'required|string',
                'kode' => 'required|string',
                'tanggal' => 'required|date',
                'subtotal' => 'required|numeric|gt:0',
            ],
            [
                'noreg.required' => 'Nomor Registrasi Pasien kosong. silahkan pindah ke menu lain terlebih dahulu kemudian coba lagi',
                'nota.required' => 'Nomor Nota Pasien kosong. silahkan pindah ke menu lain terlebih dahulu kemudian coba lagi',
                'kode.required' => 'Tindakan kosong. silahkan Pilih tindakan',
                'tanggal.required' => 'Tanggal tidak boleh kosong. silahkan pilih tanggal',
                'subtotal.required' => 'Subtotal kosong',
                'subtotal.numeric' => 'Jasa sarana, pelayanan dan/atau anastesi harus angka',
                'subtotal.gt'      => 'Jasa sarana, pelayanan dan/atau anastesi harus lebih besar dari 0',
            ]
        );
        $flag = substr($request->noreg, -1);
        $cito = $request->cito ? 'cito' : '';
        $user = FormatingHelper::session_user();
        $tanggal = $request->tanggal ?? date('Y-m-d');
        try {
            DB::beginTransaction();
            $data = Kamaroperasi::updateOrCreate(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->nota,
                ],
                [
                    'rs3' => $tanggal . date(' H:m:s'),
                    'rs4' => $request->kode,
                    'rs5' => $request->js,
                    'rs6' => $request->jp,
                    'rs7' => $request->an,
                    'rs8' => 1,
                    'rs9' => $request->rs9,
                    'rs10' => $user['kodesimrs'],
                    'rs14' => $request->rs14,
                    'rs15' => $request->rs15,
                    'rs16' => $request->rs16,

                    'rs17' => $flag,
                    'rs18' => $cito,
                    'rs19' => $request->sisbaysplit,
                    'rs20' => $request->total_split,
                ]
            );
            if (!$data) throw ('Data gagal disimpan');
            DB::commit();
            $data->load('mastertindakanoperasi', 'laporanoperasi');
            return new JsonResponse([
                'message' => 'Sudah Disimpan',
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 410);
        }
    }
    public function hapusTindakanOp(Request $request)
    {
        // return new JsonResponse($request->all());
        $cekLaporanOperasi = LaporanOperasi::where('rs1', $request->noreg)->where('rs2', $request->nota)->first();
        if ($cekLaporanOperasi)  return new JsonResponse(['message' => 'Laporan Operasi sudah dibuatkan, tidak boleh hapus tindakan operasi'], 410);
        try {
            DB::beginTransaction();
            $data = Kamaroperasi::find($request->id);
            if (!$data) throw ('Data tidak ditmukan');
            $data->delete();
            DB::commit();
            return new JsonResponse([
                'message' => 'Sudah Disimpan',
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 410);
        }
    }
    public function simpanLaporan(Request $request)
    {
        // return new JsonResponse($request->all());

        $cekTindakanOperasi = Kamaroperasi::where('rs1', $request->noreg)->where('rs2', $request->nota)->first();
        if (!$cekTindakanOperasi)  return new JsonResponse(['message' => 'Tindakan Operasi belum dibuatkan, tidak bisa membuat laporan operasi'], 410);
        try {
            DB::beginTransaction();

            $data = LaporanOperasi::updateOrCreate(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->nota,
                ],
                [
                    'rs3' => $request->tanggal,
                    'rs4' => $request->rs4,
                    'rs5' => $request->rs5,
                    'rs6' => $request->rs6,
                    'rs7' => $request->rs7,
                    'rs8' => $request->rs8,
                    'rs9' => $request->rs9,
                    'rs10' => $request->rs10,
                    'rs11' => $request->rs11,
                    'rs12' => $request->rs12,
                    'rs13' => $request->rs13,
                    'rs14' => $request->rs14,
                    'rs15' => $request->rs15,
                    'asa' => $request->asa,
                    'jenis_darah_masuk' => $request->jenis_darah_masuk,
                    'jd_keluar' => $request->jd_keluar,
                    'jd_masuk' => $request->jd_masuk,
                    'tindakan' => $request->tindakan,
                    'ttime' => !!$request->tTime ? '1' : '',

                ]
            );
            if (!$data) throw ('Data gagal disimpan');
            DB::commit();
            // $data->load('mastertindakanoperasi', 'laporanoperasi');
            return new JsonResponse([
                'message' => 'Sudah Disimpan',
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 410);
        }
    }
    public function hapusLaporannOp(Request $request)
    {
        // return new JsonResponse($request->all());
        try {
            DB::beginTransaction();
            $data = LaporanOperasi::find($request->id);
            if (!$data) throw ('Data tidak ditmukan');
            $data->delete();
            DB::commit();
            return new JsonResponse([
                'message' => 'Sudah Disimpan',
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 410);
        }
    }
}
