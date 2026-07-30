<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Ranap\RekonsiliasiObat;
use App\Models\Simrs\Ranap\RekonsiliasiObatPersetujuan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RekonsiliasiObatController extends Controller
{
    public function list()
    {
        $noreg = request('noreg');
        $data = RekonsiliasiObat::where('noreg', $noreg)
            ->with('user_petugas:kdpegsimrs,nama')
            ->orderBy('id', 'desc')
            ->get();
        return new JsonResponse($data);
    }

    public function simpandata(Request $request)
    {
        $request->validate([
            'noreg' => 'required',
            'norm' => 'required',
            'tipe' => 'required', // 'mrs', 'pindah', 'krs'
            'nama_obat' => 'required'
        ]);

        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user ? $user->kdpegsimrs : null;

        $data = RekonsiliasiObat::create([
            'noreg' => $request->noreg,
            'norm' => $request->norm,
            'tgl' => $request->tgl ?? date('Y-m-d'),
            'petugas' => $kdpegsimrs,
            'tipe' => $request->tipe,
            'nama_obat' => $request->nama_obat,
            'dosis' => $request->dosis,
            
            // tipe 'mrs'
            'lama_pakai' => $request->lama_pakai,
            'dibawa_saat_mrs' => $request->dibawa_saat_mrs,
            'berlanjut_ke_ranap' => $request->berlanjut_ke_ranap,
            'berlanjut_saat_krs' => $request->berlanjut_saat_krs,
            
            // tipe 'pindah'
            'frekuensi' => $request->frekuensi,
            'cara_pemberian' => $request->cara_pemberian,
            'waktu_pemberian_terakhir' => $request->waktu_pemberian_terakhir,
            'tindak_lanjut' => $request->tindak_lanjut,
            'perubahan_aturan_pakai' => $request->perubahan_aturan_pakai,
            
            // tipe 'krs'
            'aturan_pakai' => $request->aturan_pakai,
            'rekonsiliasi' => $request->rekonsiliasi,
            'aturan_pakai_saat_pulang' => $request->aturan_pakai_saat_pulang,
            
            'kdruang' => $request->kdruang
        ]);

        if (!$data) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menyimpan data'
            ], 500);
        }

        // Load relation
        $data->load('user_petugas:kdpegsimrs,nama');

        return new JsonResponse([
            'success' => true,
            'message' => 'Data berhasil disimpan',
            'result' => $data
        ]);
    }

    public function hapusdata(Request $request)
    {
        $cari = RekonsiliasiObat::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 404);
        }
        $cari->delete();
        return new JsonResponse(['message' => 'Data berhasil dihapus'], 200);
    }

    public function simpanpersetujuan(Request $request)
    {
        $request->validate([
            'noreg' => 'required',
            'norm' => 'required',
        ]);

        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user ? $user->kdpegsimrs : null;

        $data = RekonsiliasiObatPersetujuan::updateOrCreate(
            ['noreg' => $request->noreg],
            [
                'norm' => $request->norm,
                'tgl' => $request->tgl ?? date('Y-m-d'),
                'petugas' => $kdpegsimrs,
                'pernyataan_nama' => $request->pernyataan_nama,
                'pernyataan_tgl_lahir' => $request->pernyataan_tgl_lahir,
                'pernyataan_alamat' => $request->pernyataan_alamat,
                'pernyataan_hubungan' => $request->pernyataan_hubungan,
                'pasien_nama' => $request->pasien_nama,
                'pasien_tgl_lahir' => $request->pasien_tgl_lahir,
                'pasien_norm' => $request->pasien_norm,
                'pasien_alamat' => $request->pasien_alamat,
                'ttd_yang_menyatakan' => $request->ttd_yang_menyatakan,
                'ttd_saksi' => $request->ttd_saksi,
                'ttd_saksi_2' => $request->ttd_saksi_2,
                'kdruang' => $request->kdruang
            ]
        );

        if (!$data) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menyimpan data persetujuan'
            ], 500);
        }

        $data->load('user_petugas:kdpegsimrs,nama');

        return new JsonResponse([
            'success' => true,
            'message' => 'Data persetujuan berhasil disimpan',
            'result' => $data
        ]);
    }

    public function hapuspersetujuan(Request $request)
    {
        $cari = RekonsiliasiObatPersetujuan::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 404);
        }
        $cari->delete();
        return new JsonResponse(['message' => 'Data persetujuan berhasil dihapus'], 200);
    }
}
