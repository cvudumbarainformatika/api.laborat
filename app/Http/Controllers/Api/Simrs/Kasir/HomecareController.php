<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Homecare\HomeCareKunjungan;
use App\Models\Simrs\Master\Mpasien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomecareController extends Controller
{
    public function rincianPembayaran(Request $request)
    {
        $request->validate(['noreg' => 'required|string']);
        $noreg = $request->noreg;
        $kunjungan = HomeCareKunjungan::where('noreg', $noreg)->firstOrFail();

        $admin = (float) $kunjungan->administrasi + (float) $kunjungan->js + (float) $kunjungan->jp;
        $labPerNota = DB::table('rs51 as lab')
            ->join('rs51_meta as meta', 'meta.nota', '=', 'lab.rs2')
            ->where('lab.rs1', $noreg)
            ->selectRaw('lab.rs2 as nota, SUM((COALESCE(lab.rs6, 0) + COALESCE(lab.rs13, 0)) * COALESCE(lab.rs5, 1)) as nominal')
            ->groupBy('lab.rs2')
            ->get();
        $laborat = (float) $labPerNota->sum('nominal');
        $rehab = (float) DB::table('rs73')->where('rs1', $noreg)
            ->selectRaw('COALESCE(SUM((COALESCE(rs7, 0) + COALESCE(rs13, 0)) * COALESCE(rs5, 1)), 0) as nominal')->value('nominal');

        $farmasi = DB::connection('farmasi');
        $reguler = (float) $farmasi->table('resep_keluar_h as h')->join('resep_keluar_r as r', 'r.noresep', '=', 'h.noresep')
            ->where('h.noreg', $noreg)->selectRaw('COALESCE(SUM((r.jumlah * r.harga_jual) + COALESCE(r.nilai_r, 0)), 0) as nominal')->value('nominal');
        $racikan = (float) $farmasi->table('resep_keluar_h as h')->join('resep_keluar_racikan_r as r', 'r.noresep', '=', 'h.noresep')
            ->where('h.noreg', $noreg)->selectRaw('COALESCE(SUM((r.jumlah * r.harga_jual) + COALESCE(r.nilai_r, 0)), 0) as nominal')->value('nominal');
        $retur = (float) $farmasi->table('retur_penjualan_h as h')->join('retur_penjualan_r as r', 'r.noretur', '=', 'h.noretur')
            ->where('h.noreg', $noreg)->selectRaw('COALESCE(SUM((r.jumlah_retur * r.harga_jual) + COALESCE(r.nilai_r, 0)), 0) as nominal')->value('nominal');
        $farmasiTotal = $reguler + $racikan - $retur;
        $rincian = [
            ['nama' => 'Admin', 'nominal' => $admin],
            ['nama' => 'Laborat', 'nominal' => $laborat, 'nota' => $labPerNota],
            ['nama' => 'Rehab Medik', 'nominal' => $rehab],
            ['nama' => 'Farmasi', 'nominal' => $farmasiTotal, 'reguler' => $reguler, 'racikan' => $racikan, 'retur' => $retur],
        ];
        return new JsonResponse(['data' => $rincian, 'total' => array_sum(array_column($rincian, 'nominal'))]);
    }
    public function riwayatPembayaran(Request $request)
    {
        $request->validate(['noreg' => 'required|string']);
        $data = DB::table('rs35')
            ->where('rs1', $request->noreg)
            ->where('rs3', 'LU#')
            ->select('id', 'rs1 as noreg', 'rs2 as no_pembayaran', 'rs4 as tanggal', 'rs7 as nominal', 'rs10 as user_simpan', 'jenis_pembayaran', 'kwitansi_d')
            ->orderByDesc('rs4')
            ->get();
        return new JsonResponse(['data' => $data]);
    }
    public function simpanPembayaran(Request $request)
    {
        $request->validate([
            'noreg' => 'required|string',
            'jenis_pembayaran' => 'required|string|max:50',
        ]);

        $rincianResponse = $this->rincianPembayaran($request)->getData(true);
        $rincian = collect($rincianResponse['data'] ?? []);
        $total = (float) ($rincianResponse['total'] ?? 0);
        if ($total <= 0) {
            return new JsonResponse(['message' => 'Tidak ada tagihan yang dapat dibayar.'], 422);
        }

        return DB::transaction(function () use ($request, $rincian, $total) {
            $kunjungan = HomeCareKunjungan::where('noreg', $request->noreg)->lockForUpdate()->firstOrFail();
            if ($kunjungan->tgl_lunas) {
                return new JsonResponse(['message' => 'Kunjungan Homecare sudah lunas.'], 422);
            }

            $unit = ['Admin' => 'PEN014', 'Laborat' => 'PEN002', 'Rehab Medik' => 'REHABMEDIK', 'Farmasi' => 'FARMASI'];
            $jenisKwitansi = ['Admin' => 'Administrasi', 'Laborat' => 'Laboratorium'];
            $kategori = ['Admin' => 'admin', 'Laborat' => 'laborat', 'Rehab Medik' => 'rehabmedik', 'Farmasi' => 'farmasi'];
            $kwitansiD = $rincian->filter(fn ($item) => (float) ($item['nominal'] ?? 0) > 0)
                ->map(function ($item) use ($unit, $kategori, $jenisKwitansi, $kunjungan) {
                    $idTrans = $item['nama'] === 'Admin' ? (string) $kunjungan->id : collect($item['nota'] ?? [])->pluck('nota')->implode(',');
                    return implode('|', [
                        $kategori[$item['nama']] ?? strtolower(str_replace(' ', '', $item['nama'])),
                        round($item['nominal']),
                        $idTrans,
                        'HOMECARE',
                        $unit[$item['nama']] ?? 'HOMECARE',
                        $jenisKwitansi[$item['nama']] ?? $item['nama'],
                    ]);
                })->implode(';');

            $user = auth()->user();
            $userLogin = $user->username ?? $user->name ?? $user->email ?? (string) $user->id;
            DB::table('rs35')->insert([
                'rs1' => $request->noreg,
                'rs2' => $request->noreg,
                'rs3' => 'LU#',
                'rs4' => now(),
                'rs5' => 'C',
                'rs6' => 'Pembayaran Pelunasan',
                'rs7' => $total,
                'rs10' => $userLogin,
                'rs13' => '1',
                'kwitansi_d' => $kwitansiD,
                'jenis_pembayaran' => $request->jenis_pembayaran,
            ]);
            $kunjungan->update(['tgl_lunas' => now()]);

            return new JsonResponse(['message' => 'Pembayaran Homecare berhasil disimpan.', 'total' => $total]);
        });
    }
    public function hapusPembayaran(Request $request)
    {
        $request->validate([
            'noreg' => 'required|string',
            'no_pembayaran' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $payment = DB::table('rs35')
                ->where('rs1', $request->noreg)
                ->where('rs2', $request->no_pembayaran)
                ->where('rs3', 'LU#')
                ->lockForUpdate()
                ->first();
            if (!$payment) return new JsonResponse(['message' => 'Data pembayaran Homecare tidak ditemukan.'], 404);

            $kwitansiAktif = DB::table('kwitansilog')
                ->where('noreg', $request->noreg)
                ->where(function ($query) use ($payment) {
                    $query->where('no_pembayaran', (string) $payment->rs2)
                        ->orWhere('no_pembayaran', (string) $payment->id);
                })
                ->whereRaw("COALESCE(NULLIF(TRIM(batal), ''), '0') <> '1'")
                ->first();
            if ($kwitansiAktif) {
                return new JsonResponse(['message' => 'Pembayaran tidak dapat dihapus karena kwitansi aktif ' . $kwitansiAktif->nokwitansi . ' masih tersedia.'], 422);
            }

            DB::table('rs35')->where('id', $payment->id)->delete();
            $masihAdaPembayaran = DB::table('rs35')->where('rs1', $request->noreg)->where('rs3', 'LU#')->exists();
            if (!$masihAdaPembayaran) HomeCareKunjungan::where('noreg', $request->noreg)->update(['tgl_lunas' => null]);

            return new JsonResponse(['message' => 'Pembayaran Homecare berhasil dihapus.']);
        });
    }
    public function cetakKwitansi(Request $request)
    {
        $request->validate([
            'noreg' => 'required|string',
            'no_pembayaran' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $payment = DB::table('rs35')
                ->where('rs2', $request->no_pembayaran)
                ->where('rs1', $request->noreg)
                ->where('rs3', 'LU#')
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return new JsonResponse(['message' => 'Data pembayaran Homecare tidak ditemukan.'], 404);
            }

            $existing = DB::table('kwitansilog')
                ->where('noreg', $request->noreg)
                ->where(function ($query) use ($payment) {
                    $query->where('no_pembayaran', (string) $payment->rs2)
                        ->orWhere('no_pembayaran', (string) $payment->id);
                })
                ->whereRaw("COALESCE(NULLIF(TRIM(batal), ''), '0') <> '1'")
                ->first();
            if ($existing) {
                return new JsonResponse([
                    'message' => 'Kwitansi pembayaran sudah tersedia.',
                    'data' => ['nokwitansi' => $existing->nokwitansi, 'tanggal' => $existing->tglx, 'nominal' => $existing->total],
                ]);
            }

            $kunjungan = HomeCareKunjungan::where('noreg', $request->noreg)->firstOrFail();
            $pasien = Mpasien::where('rs1', $kunjungan->norm)->firstOrFail();

            DB::select('call homecarekwitansi(@nomor)');
            $counter = DB::table('rs1')->value('homecarekwitansi');
            $nokwitansi = FormatingHelper::nokwitansi($counter, 'HC');
            $user = auth()->user();
            $petugas = $user?->pegawai_id ? Petugas::find($user->pegawai_id) : null;
            $userid = $petugas?->kdpegsimrs ?? $user?->username ?? $user?->name ?? (string) $user?->id;
            $now = now();

            DB::table('kwitansilog')->insert([
                'noreg' => $request->noreg,
                'norm' => $kunjungan->norm,
                'tgl' => $kunjungan->tgl_kunjungan,
                'nokwitansi' => $nokwitansi,
                'nama' => $pasien->rs2,
                'sapaan' => $pasien->rs3,
                'kelamin' => $pasien->rs17,
                'ruangan' => 'HOMECARE',
                'sistembayar' => $kunjungan->sistem_bayar,
                'total' => $payment->rs7,
                'flag' => 'Kasir Homecare',
                'tglx' => $now,
                'userid' => $userid,
                'no_pembayaran' => $payment->rs2,
                'nova' => $payment->nova ?? '',
            ]);

            $rincianKwitansi = collect(array_filter(explode(';', (string) $payment->kwitansi_d)))
                ->map(function ($detail) use ($kunjungan) {
                    $parts = explode('|', $detail);
                    if (count($parts) < 6) return null;
                    $jenis = ['Admin' => 'Administrasi', 'Laborat' => 'Laboratorium'][$parts[5]] ?? $parts[5];
                    $unit = $jenis === 'Laboratorium' ? 'PEN002' : ($jenis === 'Administrasi' ? 'PEN014' : $parts[4]);
                    $idTrans = $jenis === 'Administrasi' ? (string) $kunjungan->id : $parts[2];
                    return [
                        'id_trans' => $idTrans, 'pelayanan' => $parts[3], 'unit' => $unit,
                        'jenis' => $jenis, 'jml' => (float) $parts[1],
                    ];
                })->filter()
                ->groupBy('jenis')
                ->map(function ($items) {
                    return [
                        'id_trans' => $items->flatMap(fn ($item) => explode(',', $item['id_trans']))->filter()->unique()->implode(','),
                        'pelayanan' => $items->first()['pelayanan'],
                        'unit' => $items->first()['unit'],
                        'jenis' => $items->first()['jenis'],
                        'jml' => $items->sum('jml'),
                    ];
                });

            foreach ($rincianKwitansi as $rincian) {
                $sudahAda = DB::table('kwitansi_d')->where('no_kwitansi', $nokwitansi)->where('jenis', $rincian['jenis'])->exists();
                if ($sudahAda) continue;
                DB::table('kwitansi_d')->insert([
                    'no_pembayaran' => $payment->rs2, 'no_kwitansi' => $nokwitansi,
                    'id_trans' => $rincian['id_trans'], 'noreg' => $request->noreg,
                    'pelayanan' => $rincian['pelayanan'], 'jenis' => $rincian['jenis'],
                    'unit' => $rincian['unit'], 'jml' => $rincian['jml'], 'nova' => $payment->nova ?? '',
                ]);
            }

            return new JsonResponse([
                'message' => 'Kwitansi Homecare berhasil dibuat.',
                'data' => ['nokwitansi' => $nokwitansi, 'tanggal' => $now->format('Y-m-d H:i:s'), 'nominal' => $payment->rs7],
            ]);
        });
    }
    public function cekKwitansiPembayaran(Request $request)
    {
        $request->validate([
            'noreg' => 'required|string',
            'no_pembayaran' => 'required|string',
        ]);

        $legacyPaymentId = DB::table('rs35')
            ->where('rs1', $request->noreg)
            ->where('rs2', $request->no_pembayaran)
            ->where('rs3', 'LU#')
            ->value('id');
        $kwitansi = DB::table('kwitansilog')
            ->where('noreg', $request->noreg)
            ->where(function ($query) use ($request, $legacyPaymentId) {
                $query->where('no_pembayaran', (string) $request->no_pembayaran);
                if ($legacyPaymentId) $query->orWhere('no_pembayaran', (string) $legacyPaymentId);
            })
            ->whereRaw("COALESCE(NULLIF(TRIM(batal), ''), '0') <> '1'")
            ->select('nokwitansi', 'tglx', 'total')
            ->first();

        return new JsonResponse(['data' => ['ada' => (bool) $kwitansi, 'kwitansi' => $kwitansi]]);
    }
    public function riwayatKwitansi(Request $request)
    {
        $request->validate(['noreg' => 'required|string']);
        $data = DB::table('kwitansilog')
            ->where('noreg', $request->noreg)
            ->where('flag', 'Kasir Homecare')
            ->select('id', 'nokwitansi as nomor', 'tglx as tanggal', 'total as nominal', 'batal', 'tgl_batal', 'user_batal', 'no_pembayaran')
            ->orderByDesc('tglx')
            ->get();
        return new JsonResponse(['data' => $data]);
    }

    public function batalKwitansi(Request $request)
    {
        $request->validate([
            'noreg' => 'required|string',
            'nokwitansi' => 'required|string',
        ]);

        $kwitansi = DB::table('kwitansilog')
            ->where('noreg', $request->noreg)
            ->where('nokwitansi', $request->nokwitansi)
            ->lockForUpdate()
            ->first();
        if (!$kwitansi) return new JsonResponse(['message' => 'Kwitansi tidak ditemukan.'], 404);
        if ((string) $kwitansi->batal === '1') return new JsonResponse(['message' => 'Kwitansi sudah dibatalkan.'], 422);
        if (!empty($kwitansi->no_tbp)) return new JsonResponse(['message' => 'Kwitansi sudah dibuat TBP dan tidak dapat dibatalkan.'], 422);

        $user = auth()->user();
        $petugas = $user?->pegawai_id ? Petugas::find($user->pegawai_id) : null;
        $userid = $petugas?->kdpegsimrs ?? $user?->username ?? $user?->name ?? (string) $user?->id;
        $jumlahRincianDihapus = DB::transaction(function () use ($kwitansi, $userid) {
            DB::table('kwitansilog')->where('id', $kwitansi->id)->update([
                'batal' => '1',
                'tgl_batal' => now(),
                'user_batal' => $userid,
            ]);
            return DB::table('kwitansi_d')->where('no_kwitansi', $kwitansi->nokwitansi)->delete();
        });

        return new JsonResponse([
            'message' => 'Kwitansi berhasil dibatalkan.',
            'rincian_dihapus' => $jumlahRincianDihapus,
        ]);
    }
}
