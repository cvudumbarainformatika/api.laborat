<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\PelayananInformasiObat;
use App\Models\Simrs\Penunjang\Farmasinew\EdukasiFarmasi;
use App\Models\Simrs\Penunjang\Farmasinew\Meso;
use App\Models\Simrs\Penunjang\Farmasinew\PenilaianObatLuar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KunjunganController extends Controller
{
    // Pelayanan Informasi Obat
    public function simPelIOnfOb(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $data = PelayananInformasiObat::updateOrCreate(
                [
                    'norm' => $request->norm,
                    'noreg' => $request->noreg,
                ],
                [
                    'tanggal' => $request->tanggal,
                    'metode' => $request->metode,
                    'nama_penanya' => $request->nama_penanya,
                    'status_penanya' => $request->status_penanya,
                    'tlp_penanya' => $request->tlp_penanya,
                    'umur_pasien' => $request->umur_pasien,
                    'kehamilan' => $request->kehamilan,
                    'kasus_khusus' => $request->kasus_khusus,
                    'jenis_kelamin' => $request->jenis_kelamin,
                    'menyusui' => $request->menyusui,
                    'uraian_pertanyaan' => $request->uraian_pertanyaan,
                    'obat_non_eresep' => $request->obat_non_eresep,
                    'jenis_pertanyaan' => $request->jenis_pertanyaan,
                    'kode' => $request->kode,
                    'jawaban' => $request->jawaban,
                    'referensi' => $request->referensi,
                    'apoteker' => $request->apoteker,
                    'user_input' => $request->user_input,
                ]
            );
            if (!$data) {
                return new JsonResponse(['message' => 'Pelayanan Informasi Obat gagal disimpan'], 410);
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Pelayanan Informasi Obat sudah disimpan',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e->getMessage()], 410);
        }
    }

    // Edukasi Farmasi
    public function simpanEdukasiFarmasi(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $data = EdukasiFarmasi::updateOrCreate(
                [
                    'norm' => $request->norm,
                    'noreg' => $request->noreg,
                ],
                [
                    'tanggal' => $request->tanggal ?? date('Y-m-d H:i:s'),
                    'indikasi_chk' => $request->indikasi_chk ?? 0,
                    'indikasi_keterangan' => $request->indikasi_keterangan,
                    'aturan_chk' => $request->aturan_chk ?? 0,
                    'aturan_keterangan' => $request->aturan_keterangan,
                    'antibiotik_chk' => $request->antibiotik_chk ?? 0,
                    'antibiotik_keterangan' => $request->antibiotik_keterangan,
                    'penyimpanan_chk' => $request->penyimpanan_chk ?? 0,
                    'penyimpanan_keterangan' => $request->penyimpanan_keterangan,
                    'jangka_chk' => $request->jangka_chk ?? 0,
                    'jangka_keterangan' => $request->jangka_keterangan,
                    'interaksi_chk' => $request->interaksi_chk ?? 0,
                    'interaksi_keterangan' => $request->interaksi_keterangan,
                    'efek_samping_chk' => $request->efek_samping_chk ?? 0,
                    'efek_samping_keterangan' => $request->efek_samping_keterangan,
                    'pemahaman' => $request->pemahaman,
                    'penerima' => $request->penerima,
                    'tanda_tangan' => $request->tanda_tangan,
                    'petugas' => $request->petugas,
                    'user_input' => auth()->user()->pegawai_id ?? $request->user_input,
                ]
            );
            if (!$data) {
                return new JsonResponse(['message' => 'Edukasi Farmasi gagal disimpan'], 410);
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Edukasi Farmasi sudah disimpan',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e->getMessage()], 410);
        }
    }

    public function getEdukasiFarmasi(Request $request)
    {
        $data = EdukasiFarmasi::where('norm', $request->norm)
            ->where('noreg', $request->noreg)
            ->first();

        return new JsonResponse($data);
    }

    // MESO
    public function simpanMeso(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $data = Meso::updateOrCreate(
                [
                    'norm' => $request->norm,
                    'noreg' => $request->noreg,
                ],
                [
                    'tanggal' => $request->tanggal ?? date('Y-m-d H:i:s'),
                    'keluhan' => $request->keluhan,
                    'obat_dicurigai' => $request->obat_dicurigai,
                    'tindakan_diambil' => $request->tindakan_diambil,
                    'outcome' => $request->outcome,
                    'petugas' => $request->petugas,
                    'detail' => $request->detail,
                    'user_input' => auth()->user()->pegawai_id ?? $request->user_input,
                ]
            );
            if (!$data) {
                return new JsonResponse(['message' => 'Meso gagal disimpan'], 410);
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Meso sudah disimpan',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e->getMessage()], 410);
        }
    }

    public function getMeso(Request $request)
    {
        $data = Meso::where('norm', $request->norm)
            ->where('noreg', $request->noreg)
            ->first();

        return new JsonResponse($data);
    }

    // Penilaian Obat Luar
    public function simpanPenilaianObatLuar(Request $request)
    {
        try {
            $request->validate([
                'norm' => 'required',
                'noreg' => 'required',
                'tanggal' => 'required|date',
                'lembar_resep' => 'nullable|string',
                'detail' => 'nullable|array',
                'check_1' => 'nullable|string',
                'double_check_2' => 'nullable|string',
            ]);

            DB::connection('farmasi')->beginTransaction();
            $data = PenilaianObatLuar::updateOrCreate(
                [
                    'norm' => $request->norm,
                    'noreg' => $request->noreg,
                ],
                [
                    'tanggal' => $request->tanggal ?? date('Y-m-d H:i:s'),
                    'lembar_resep' => $request->lembar_resep,
                    'detail' => $request->detail,
                    'check_1' => $request->check_1 ?? ($request->detail['check_1'] ?? null),
                    'double_check_2' => $request->double_check_2 ?? ($request->detail['double_check_2'] ?? null),
                    'user_input' => auth()->user()->pegawai_id ?? $request->user_input,
                ]
            );
            if (!$data) {
                return new JsonResponse(['message' => 'Penilaian Obat Luar gagal disimpan'], 410);
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Penilaian Obat Luar sudah disimpan',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e->getMessage()], 410);
        }
    }

    public function getPenilaianObatLuar(Request $request)
    {
        $data = PenilaianObatLuar::where('norm', $request->norm)
            ->where('noreg', $request->noreg)
            ->first();

        return new JsonResponse($data);
    }
}
