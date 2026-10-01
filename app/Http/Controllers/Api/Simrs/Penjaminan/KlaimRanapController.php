<?php

namespace App\Http\Controllers\Api\Simrs\Penjaminan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penjaminan\ListCasmixRanap;
use App\Models\Simrs\Ranap\RuanganRanap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KlaimRanapController extends Controller
{
    /**
     * Mengambil daftar pasien rawat inap pada antrean casemix / penjaminan ranap.
     */
    public function getdataklaimranap(Request $request): JsonResponse
    {
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));
        $kdruangan = $request->input('kdruangan');
        $q = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', 10);

        $query = ListCasmixRanap::select([
            'listkirimcasmixranap.id',
            'listkirimcasmixranap.noreg as noreg',
            DB::raw('COALESCE(listkirimcasmixranap.norm, rs23.rs2) as norm'),
            DB::raw('COALESCE(NULLIF(listkirimcasmixranap.nosep, ""), rs227.rs8, rs222.rs8, "") as nosep'),
            'rs227.rs8 as sep_ranap',
            'rs222.rs8 as sep_igd',
            DB::raw("CASE WHEN rs227.rs8 IS NOT NULL AND rs227.rs8 != '' THEN 'RANAP' WHEN rs222.rs8 IS NOT NULL AND rs222.rs8 != '' THEN 'IGD' ELSE '' END as jenis_sep"),
            DB::raw('COALESCE(listkirimcasmixranap.noka, rs15.rs46) as noka'),
            DB::raw('COALESCE(listkirimcasmixranap.kdruangan, rs23.rs5) as kdruangan'),
            'listkirimcasmixranap.flag_verif_rm as flag_verif_rm',
            'listkirimcasmixranap.tgl_verif_rm as tgl_verif_rm',
            'listkirimcasmixranap.petugas_verif_rm as petugas_verif_rm',
            'listkirimcasmixranap.catatan_verif_rm as catatan_verif_rm',
            DB::raw('COALESCE(kepegx.pegawai.nama, listkirimcasmixranap.kddpjp, "-") as dokter'),
            DB::raw('COALESCE(listkirimcasmixranap.tgl_masuk, rs23.rs3) as tgl_kunjungan'),
            DB::raw('COALESCE(listkirimcasmixranap.tgl_masuk, rs23.rs3) as tglmasuk'),
            DB::raw('COALESCE(listkirimcasmixranap.tgl_pulang, rs23.rs4) as tglpulang'),
            'rs15.rs2 as pasien',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            DB::raw("COALESCE(NULLIF(listkirimcasmixranap.kdsistembayar, ''), rs23.rs19, 'BPJS') as sistembayar"),
            'rs24.rs2 as ruangan',
            'rs24.rs2 as poli',
            'klaim_trans_ranap.status_klaim as ket',
            DB::raw('\'ranap\' as layanan')
        ])
            ->leftJoin('rs23', 'rs23.rs1', '=', 'listkirimcasmixranap.noreg')
            ->leftJoin('rs15', 'rs15.rs1', '=', DB::raw('COALESCE(listkirimcasmixranap.norm, rs23.rs2)'))
            ->leftJoin('rs227', 'rs227.rs1', '=', 'listkirimcasmixranap.noreg')
            ->leftJoin('rs222', 'rs222.rs1', '=', 'listkirimcasmixranap.noreg')
            ->leftJoin('rs24', function ($join) {
                $join->on('rs24.rs1', '=', DB::raw('COALESCE(listkirimcasmixranap.kdruangan, rs23.rs5)'));
            })
            ->leftJoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', DB::raw('COALESCE(listkirimcasmixranap.kddpjp, rs23.rs10)'))
            ->leftJoin('klaim_trans_ranap', 'klaim_trans_ranap.noreg', '=', 'listkirimcasmixranap.noreg')
            ->whereYear(DB::raw("COALESCE(listkirimcasmixranap.tgl_masuk, rs23.rs3, listkirimcasmixranap.created_at)"), $tahun)
            ->whereMonth(DB::raw("COALESCE(listkirimcasmixranap.tgl_masuk, rs23.rs3, listkirimcasmixranap.created_at)"), $bulan);

        if (!empty($kdruangan) && $kdruangan !== 'SEMUA' && $kdruangan !== 'SEMUA RUANGAN' && $kdruangan !== 'all') {
            $query->where(function ($qRuang) use ($kdruangan) {
                $qRuang->where('listkirimcasmixranap.kdruangan', $kdruangan)
                    ->orWhere('rs23.rs5', $kdruangan);
            });
        }

        // =========================================================================================
        // CATATAN PENTING ALUR VERIFIKASI REKAM MEDIK:
        // Filter `flag_verif_rm` di bawah ini sengaja DI-COMMENT terlebih dahulu karena modul dan
        // alur proses verifikasi oleh tim Rekam Medik saat ini masih belum matang/dalam pengembangan.
        // Untuk saat ini, seluruh data kunjungan ranap yang masuk ke tabel `listkirimcasmixranap`
        // akan langsung ditampilkan ke menu Penjaminan Klaim Ranap agar berkas/dokumennya dapat ditinjau.
        // Nanti jika alur verifikasi Rekam Medik sudah siap/final, baris filter ini dapat diaktifkan kembali.
        // =========================================================================================
        // ->where('listkirimcasmixranap.flag_verif_rm', '1')

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('rs15.rs2', 'LIKE', '%' . $q . '%')
                    ->orWhere('listkirimcasmixranap.noreg', 'LIKE', '%' . $q . '%')
                    ->orWhere('listkirimcasmixranap.norm', 'LIKE', '%' . $q . '%')
                    ->orWhere('listkirimcasmixranap.nosep', 'LIKE', '%' . $q . '%')
                    ->orWhere('listkirimcasmixranap.noka', 'LIKE', '%' . $q . '%')
                    ->orWhere('rs15.rs49', 'LIKE', '%' . $q . '%')
                    ->orWhere('rs24.rs2', 'LIKE', '%' . $q . '%');
            });
        }

        $data = $query->orderBy('listkirimcasmixranap.id', 'DESC')->paginate($perPage);

        return new JsonResponse($data);
    }

    /**
     * Endpoint untuk mengirim data kunjungan rawat inap ke antrean penjaminan/casemix ranap.
     * Hanya pasien dengan penjamin group BPJS yang dapat dikirim.
     * Satu noreg dipastikan hanya memiliki 1 baris data di antrean casemix ranap.
     */
    public function kirimpenjaminan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg' => ['required', 'string'],
            'norm' => ['nullable', 'string'],
            'noka' => ['nullable', 'string'],
            'nosep' => ['nullable', 'string'],
            'kdruangan' => ['nullable', 'string'],
            'kdsistembayar' => ['nullable', 'string'],
            'kddpjp' => ['nullable', 'string'],
            'tgl_masuk' => ['nullable', 'string'],
            'tgl_pulang' => ['nullable', 'string'],
            'flaging' => ['nullable', 'string'],
        ]);

        $kunjungan = DB::table('rs23')->where('rs1', $validated['noreg'])->first();
        if (!$kunjungan) {
            return new JsonResponse(['message' => 'Data kunjungan pasien ranap tidak ditemukan'], 404);
        }

        // Ambil kode sistem bayar dari request atau rs23.rs19 atau rs23.rs14
        $kdSistemBayar = $validated['kdsistembayar'] ?: ($kunjungan->rs19 ?: $kunjungan->rs14);
        $sistemBayar = DB::table('rs9')->where('rs1', $kdSistemBayar)->first();

        // Validasi: hanya pasien BPJS (groups 1, kode BPJS, nama BPJS, atau AR49) yang dapat dikirim
        $isBpjs = false;
        if ($sistemBayar) {
            $isBpjs = $sistemBayar->groups === '1'
                || stripos((string)$sistemBayar->rs1, 'BPJS') !== false
                || stripos((string)$sistemBayar->rs2, 'BPJS') !== false
                || $sistemBayar->rs1 === 'AR49';
        } else {
            $isBpjs = stripos((string)$kdSistemBayar, 'BPJS') !== false || $kdSistemBayar === 'AR49';
        }

        if (!$isBpjs) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Hanya pasien BPJS yang dapat dikirim ke Casemix. Pasien umum dan tagihan tidak perlu dikirim.'
            ], 422);
        }

        $pasien = DB::table('rs15')->where('rs1', $kunjungan->rs2)->first();

        $sepRanap = DB::table('rs227')->where('rs1', $kunjungan->rs1)->first();
        $sepRajal = DB::table('rs222')->where('rs1', $kunjungan->rs1)->first();
        $norm = $validated['norm'] ?: $kunjungan->rs2;
        $noka = $validated['noka'] ?: ($pasien?->rs46 ?? null);
        $nosep = $validated['nosep'] ?: ($sepRanap?->rs8 ?: ($sepRajal?->rs8 ?: null));
        $kdruangan = $validated['kdruangan'] ?: ($kunjungan->rs5 ?? null);
        $kddpjp = $validated['kddpjp'] ?: ($kunjungan->rs10 ?? null);
        $tgl_masuk = $validated['tgl_masuk'] ?: ($kunjungan->rs3 ?? null);
        $tgl_pulang = $validated['tgl_pulang'] ?: ($kunjungan->rs4 !== '0000-00-00 00:00:00' ? $kunjungan->rs4 : null);

        // updateOrCreate menjamin satu noreg hanya memiliki 1 baris di tabel listkirimcasmixranap
        $simpan = ListCasmixRanap::updateOrCreate(
            ['noreg' => $validated['noreg']],
            [
                'norm' => $norm,
                'noka' => $noka,
                'nosep' => $nosep,
                'kdruangan' => $kdruangan,
                'kdsistembayar' => $kdSistemBayar,
                'kddpjp' => $kddpjp,
                'tgl_masuk' => $tgl_masuk,
                'tgl_pulang' => $tgl_pulang,
                'flaging' => $validated['flaging'] ?? '1',
            ]
        );

        return new JsonResponse([
            'success' => true,
            'message' => 'Data Kunjungan Rawat Inap berhasil dikirim ke Penjaminan',
            'data' => $simpan
        ], 200);
    }

    /**
     * Endpoint untuk verifikasi berkas oleh Rekam Medik (disiapkan untuk tahap berikutnya).
     */
    public function verifikasiRekamMedik(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg' => ['required', 'string'],
            'status' => ['required', 'in:0,1'], // 1 = diverifikasi, 0 = ditolak/batal
            'catatan' => ['nullable', 'string'],
        ]);

        $data = ListCasmixRanap::where('noreg', $validated['noreg'])->first();
        if (!$data) {
            return new JsonResponse(['message' => 'Data kunjungan ranap di casemix tidak ditemukan'], 404);
        }

        $user = auth()->user();
        $petugas = $user?->pegawai?->nama ?? $user?->nama ?? $user?->username ?? 'Petugas RM';

        $data->update([
            'flag_verif_rm' => $validated['status'],
            'tgl_verif_rm' => now(),
            'petugas_verif_rm' => $petugas,
            'catatan_verif_rm' => $validated['catatan'] ?? null,
        ]);

        return new JsonResponse([
            'success' => true,
            'message' => $validated['status'] === '1'
                ? 'Data Pasien Rawat Inap Berhasil Diverifikasi oleh Rekam Medik'
                : 'Verifikasi Rekam Medik Dibatalkan',
            'data' => $data
        ]);
    }

    /**
     * Mengambil master ruangan rawat inap untuk dropdown filter.
     */
    public function getRuanganRanap(): JsonResponse
    {
        $data = DB::table('rs24')
            ->select('rs1 as kode', 'rs2 as nama', 'groups', 'groups_nama')
            ->where('status', '<>', '1')
            ->orderBy('rs2')
            ->get();

        return new JsonResponse($data);
    }
}
