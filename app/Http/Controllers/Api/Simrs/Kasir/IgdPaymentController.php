<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Helpers\FormatingHelper;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IgdPaymentController extends Controller
{
    private const TYPE = 'IG#';

    private function details(string $noreg): array
    {
        $map = [
            ['administrasi', 'Administrasi IGD', 'adminigd'], ['laboratorium', 'Laboratorium', 'laborat'],
            ['radiologi', 'Radiologi', 'radiologi'], ['hemodialisa', 'Hemodialisa', 'hd'],
            ['anestesi', 'Anestesi Di Luar OK & ICU', 'penunjanglain'], ['cardio', 'Cardio', 'cardio'],
            ['eeg', 'EEG', 'eeg'], ['endoscope', 'Endoscope', 'endoscopy'],
            ['darah', 'Biaya Penggunaan Darah', 'bdrs'], ['ok_igd', 'OK IGD', 'okigd'],
            ['tindakan_ok_igd', 'Tindakan Operasi IGD', 'tindakanokigd'], ['ok_ibs', 'OK IBS', 'okranap'],
            ['tindakan_ok_ibs', 'Tindakan Operasi IBS', 'tindakanokranap'], ['jenasah', 'Perawatan Jenasah', 'perawatanjenasah'],
            ['ambulan', 'Ambulan', 'ambulan'],
        ];
        $details = collect($map)->map(fn ($item) => ['key' => $item[0], 'nama' => $item[1], 'nominal' => (float) DetailbillingbynoregIgdController::{$item[2]}($noreg)]);
        $details->push(['key' => 'tindakan', 'nama' => 'Tindakan', 'nominal' => (float) DetailbillingbynoregIgdController::tindakan($noreg)->sum('subtotal')]);
        $details->push(['key' => 'materai', 'nama' => 'Biaya Pembuatan Dokumen dan Materai', 'nominal' => (float) DetailbillingbynoregIgdController::biayamatrei($noreg)->sum('subtotal')]);
        $details->push(['key' => 'farmasi', 'nama' => 'Farmasi', 'nominal' => (float) DetailbillingbynoregIgdController::farmasi($noreg) + (float) DetailbillingbynoregIgdController::eresep($noreg)]);
        return $details->all();
    }

    private function paidKeys(string $noreg, bool $activeReceiptOnly = true): array
    {
        $query = DB::table('rs35 as payment')
            ->where('payment.rs1', $noreg)
            ->where('payment.rs3', self::TYPE);
        if ($activeReceiptOnly) {
            $query->join('kwitansilog as kwitansi', function ($join) {
                $join->on('kwitansi.noreg', '=', 'payment.rs1')
                    ->on('kwitansi.no_pembayaran', '=', 'payment.rs2');
            })->whereRaw("COALESCE(NULLIF(TRIM(kwitansi.batal), ''), '0') <> '1'");
        }
        return $query->pluck('payment.kwitansi_d')
            ->flatMap(function ($details) {
                return collect(explode(';', (string) $details))->map(function ($detail) {
                    $parts = explode('|', $detail);
                    $legacyKey = $parts[2] ?? '';
                    if (in_array($legacyKey, ['administrasi', 'laboratorium', 'tindakan', 'farmasi', 'radiologi'], true)) return $legacyKey;
                    return match ($parts[5] ?? '') {
                        'Administrasi IGD', 'Administrasi' => 'administrasi',
                        'Laboratorium', 'Laborat' => 'laboratorium',
                        'Tindakan' => 'tindakan', 'Farmasi' => 'farmasi', 'Radiologi' => 'radiologi',
                        default => null,
                    };
                });
            })->filter()->unique()->values()->all();
    }

    public function rincianPembayaran(Request $request): JsonResponse
    {
        $request->validate(['noreg' => 'required|string']);
        $paidKeys = $this->paidKeys($request->noreg);
        $paymentKeys = $this->paidKeys($request->noreg, false);
        $details = collect($this->details($request->noreg))->map(function ($item) use ($paidKeys, $paymentKeys) {
            $item['terbayar'] = in_array($item['key'], $paidKeys, true) ? $item['nominal'] : 0;
            $item['sisa'] = $item['nominal'] - $item['terbayar'];
            $item['sudah_dibayar'] = $item['terbayar'] > 0;
            $item['sudah_dibayar_rs35'] = in_array($item['key'], $paymentKeys, true);
            return $item;
        });
        $history = DB::table('rs35')->where('rs1', $request->noreg)->where('rs3', self::TYPE)
            ->select('id', 'rs2 as no_pembayaran', 'rs4 as tanggal', 'rs7 as nominal', 'jenis_pembayaran', 'kwitansi_d')
            ->selectRaw("EXISTS(SELECT 1 FROM kwitansilog k WHERE k.noreg = rs35.rs1 AND k.no_pembayaran = rs35.rs2 AND COALESCE(NULLIF(TRIM(k.batal), ''), '0') <> '1') as sudah_kwitansi")
            ->orderByDesc('rs4')->get();
        return new JsonResponse(['data' => $details, 'total_tagihan' => $details->sum('nominal'), 'total_terbayar' => $details->sum('terbayar'), 'total_sisa' => $details->sum('sisa'), 'riwayat_pembayaran' => $history]);
    }

    public function hapusPembayaran(Request $request): JsonResponse
    {
        $request->validate(['noreg' => 'required|string', 'no_pembayaran' => 'required|string']);
        return DB::transaction(function () use ($request) {
            $payment = DB::table('rs35')->where('rs1', $request->noreg)->where('rs2', $request->no_pembayaran)->where('rs3', self::TYPE)->lockForUpdate()->first();
            if (!$payment) return new JsonResponse(['message' => 'Pembayaran IGD tidak ditemukan.'], 404);
            $kwitansiAktif = DB::table('kwitansilog')->where('noreg', $request->noreg)->where('no_pembayaran', $payment->rs2)->whereRaw("COALESCE(NULLIF(TRIM(batal), ''), '0') <> '1'")->first();
            if ($kwitansiAktif) return new JsonResponse(['message' => 'Pembayaran tidak dapat dihapus karena kwitansi aktif ' . $kwitansiAktif->nokwitansi . ' masih tersedia.'], 422);
            DB::table('rs35')->where('id', $payment->id)->delete();
            return new JsonResponse(['message' => 'Pembayaran IGD berhasil dihapus.']);
        });
    }
    public function batalKwitansi(Request $request): JsonResponse
    {
        $request->validate(['noreg' => 'required|string', 'nokwitansi' => 'required|string']);
        return DB::transaction(function () use ($request) {
            $kwitansi = DB::table('kwitansilog')->where('noreg', $request->noreg)->where('nokwitansi', $request->nokwitansi)->where('flag', 'Kasir IGD')->lockForUpdate()->first();
            if (!$kwitansi) return new JsonResponse(['message' => 'Kwitansi IGD tidak ditemukan.'], 404);
            if ((string) $kwitansi->batal === '1') return new JsonResponse(['message' => 'Kwitansi sudah dibatalkan.'], 422);
            if (isset($kwitansi->no_tbp) && !empty($kwitansi->no_tbp)) return new JsonResponse(['message' => 'Kwitansi sudah dibuat TBP dan tidak dapat dibatalkan.'], 422);
            $user = auth()->user();
            $userid = $user->username ?? $user->name ?? $user->email ?? (string) $user->id;
            DB::table('kwitansilog')->where('id', $kwitansi->id)->update(['batal' => '1', 'tgl_batal' => now(), 'user_batal' => $userid]);
            $rincianDihapus = DB::table('kwitansi_d')->where('no_kwitansi', $kwitansi->nokwitansi)->delete();
            return new JsonResponse(['message' => 'Kwitansi IGD berhasil dibatalkan.', 'rincian_dihapus' => $rincianDihapus]);
        });
    }
    public function cekKwitansiPembayaran(Request $request): JsonResponse
    {
        $request->validate(['noreg' => 'required|string', 'no_pembayaran' => 'required|string']);
        $kwitansi = DB::table('kwitansilog')->where('noreg', $request->noreg)->where('no_pembayaran', $request->no_pembayaran)->whereRaw("COALESCE(NULLIF(TRIM(batal), ''), '0') <> '1'")->select('nokwitansi', 'tglx', 'total')->first();
        return new JsonResponse(['data' => ['ada' => (bool) $kwitansi, 'kwitansi' => $kwitansi]]);
    }
    public function cetakKwitansi(Request $request): JsonResponse
    {
        $request->validate(['noreg' => 'required|string', 'no_pembayaran' => 'required|string']);
        return DB::transaction(function () use ($request) {
            $payment = DB::table('rs35')->where('rs1', $request->noreg)->where('rs2', $request->no_pembayaran)->where('rs3', self::TYPE)->lockForUpdate()->first();
            if (!$payment) return new JsonResponse(['message' => 'Pembayaran IGD tidak ditemukan.'], 404);
            $existing = DB::table('kwitansilog')->where('noreg', $request->noreg)->where('no_pembayaran', $payment->rs2)->whereRaw("COALESCE(NULLIF(TRIM(batal), ''), '0') <> '1'")->first();
            if ($existing) {
                $sudahAdaRincian = DB::table('kwitansi_d')->where('no_kwitansi', $existing->nokwitansi)->exists();
                if (!$sudahAdaRincian) {
                    foreach (array_filter(explode(';', (string) $payment->kwitansi_d)) as $detail) {
                        $parts = explode('|', $detail);
                        if (count($parts) < 6) continue;
                        $jenis = $parts[5] === 'Administrasi IGD' ? 'Administrasi' : $parts[5];
                        $unit = $jenis === 'Laboratorium' ? 'PEN002' : $parts[4];
                        DB::table('kwitansi_d')->insert(['no_pembayaran' => $payment->rs2, 'no_kwitansi' => $existing->nokwitansi, 'id_trans' => $parts[2], 'noreg' => $request->noreg, 'pelayanan' => 'POL014', 'jenis' => $jenis, 'unit' => $unit, 'jml' => (float) $parts[1], 'nova' => $payment->nova ?? '']);
                    }
                }
                $adminId = DB::table('rs35x')->where('rs1', $request->noreg)->where('rs3', 'A2#')->pluck('id')->implode(',');
                $laboratNota = DB::table('rs51')->where('rs1', $request->noreg)->where('rs23', 'POL014')->where('rs18', '!=', '')->where('lunas', '!=', '1')->pluck('rs2')->filter()->unique()->implode(',');
                DB::table('kwitansi_d')->where('no_kwitansi', $existing->nokwitansi)->whereIn('jenis', ['Administrasi', 'Administrasi IGD'])->update(['id_trans' => $adminId, 'pelayanan' => 'POL014', 'unit' => 'POL014', 'jenis' => 'Administrasi']);
                DB::table('kwitansi_d')->where('no_kwitansi', $existing->nokwitansi)->whereIn('jenis', ['Laboratorium', 'Laborat'])->update(['id_trans' => $laboratNota, 'pelayanan' => 'POL014', 'unit' => 'PEN002', 'jenis' => 'Laboratorium']);
                $tindakanNota = DB::table('rs73')->where('rs1', $request->noreg)->where('rs22', 'POL014')->pluck('rs2')->filter()->unique()->implode(',');
                $radiologiId = DB::table('rs48')->where('rs1', $request->noreg)->pluck('id')->filter()->unique()->implode(',');
                $farmasiResep = collect([DB::table('rs38')->where('rs1', $request->noreg)->where('rs20', 'POL014')->pluck('rs2'), DB::table('rs39')->where('rs1', $request->noreg)->where('rs18', 'IRD')->pluck('rs2'), DB::table('rs63')->where('rs1', $request->noreg)->where('rs18', 'IRD')->pluck('rs2')])->flatten()->filter()->unique()->implode(',');
                DB::table('kwitansi_d')->where('no_kwitansi', $existing->nokwitansi)->where('jenis', 'Tindakan')->update(['id_trans' => $tindakanNota, 'pelayanan' => 'POL014']);
                DB::table('kwitansi_d')->where('no_kwitansi', $existing->nokwitansi)->where('jenis', 'Farmasi')->update(['id_trans' => $farmasiResep, 'pelayanan' => 'POL014']);
                DB::table('kwitansi_d')->where('no_kwitansi', $existing->nokwitansi)->where('jenis', 'Radiologi')->update(['id_trans' => $radiologiId, 'pelayanan' => 'POL014']);
                return new JsonResponse(['message' => 'Kwitansi pembayaran sudah tersedia.', 'data' => ['nokwitansi' => $existing->nokwitansi, 'tanggal' => $existing->tglx, 'nominal' => $existing->total]]);
            }
            $pasien = DB::table('rs17')->leftJoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')->leftJoin('rs9', 'rs9.rs1', '=', 'rs17.rs14')->where('rs17.rs1', $request->noreg)->select('rs17.rs2 as norm', 'rs17.rs3 as tgl', 'rs15.rs2 as nama', 'rs15.rs3 as sapaan', 'rs15.rs17 as kelamin', 'rs9.rs2 as sistembayar')->first();
            if (!$pasien) return new JsonResponse(['message' => 'Data kunjungan IGD tidak ditemukan.'], 404);
            $counter = DB::table('rs1')->lockForUpdate()->first();
            if (!$counter) return new JsonResponse(['message' => 'Counter kwitansi IGD tidak ditemukan.'], 422);
            $nomorBerikutnya = (int) ($counter->rs53 ?? 0) + 1;
            DB::table('rs1')->update(['rs53' => $nomorBerikutnya]);
            $nokwitansi = FormatingHelper::nokwitansi($nomorBerikutnya, 'IGD');
            $user = auth()->user();
            $userid = $user->username ?? $user->name ?? $user->email ?? (string) $user->id;
            $now = now();
            DB::table('kwitansilog')->insert(['noreg' => $request->noreg, 'norm' => $pasien->norm, 'tgl' => $pasien->tgl, 'nokwitansi' => $nokwitansi, 'nama' => $pasien->nama, 'sapaan' => $pasien->sapaan, 'kelamin' => $pasien->kelamin, 'ruangan' => 'IGD', 'sistembayar' => $pasien->sistembayar, 'total' => $payment->rs7, 'flag' => 'Kasir IGD', 'tglx' => $now, 'userid' => $userid, 'no_pembayaran' => $payment->rs2, 'nova' => $payment->nova ?? '']);
            foreach (array_filter(explode(';', (string) $payment->kwitansi_d)) as $detail) {
                $parts = explode('|', $detail);
                if (count($parts) < 6) continue;
                $jenis = $parts[5] === 'Administrasi IGD' ? 'Administrasi' : $parts[5];
                $unit = $jenis === 'Laboratorium' ? 'PEN002' : $parts[4];
                DB::table('kwitansi_d')->insert(['no_pembayaran' => $payment->rs2, 'no_kwitansi' => $nokwitansi, 'id_trans' => $parts[2], 'noreg' => $request->noreg, 'pelayanan' => 'POL014', 'jenis' => $jenis, 'unit' => $unit, 'jml' => (float) $parts[1], 'nova' => $payment->nova ?? '']);
            }
            return new JsonResponse(['message' => 'Kwitansi IGD berhasil dibuat.', 'data' => ['nokwitansi' => $nokwitansi, 'tanggal' => $now->format('Y-m-d H:i:s'), 'nominal' => $payment->rs7]]);
        });
    }
    public function riwayatKwitansi(Request $request): JsonResponse
    {
        $request->validate(['noreg' => 'required|string']);
        $data = DB::table('kwitansilog as k')
            ->join('rs35 as p', 'p.rs2', '=', 'k.no_pembayaran')
            ->where('k.noreg', $request->noreg)->where('p.rs3', self::TYPE)
            ->select('k.id', 'k.nokwitansi as nomor', 'k.tglx as tanggal', 'k.total as nominal', 'k.batal', 'k.tgl_batal', 'k.no_pembayaran')
            ->orderByDesc('k.tglx')->get();
        $totalTerbayar = $data->filter(fn ($item) => !($item->batal === '1' || $item->batal === 1))->sum('nominal');
        return new JsonResponse(['data' => $data, 'total_terbayar' => $totalTerbayar]);
    }
    public function simpanPembayaran(Request $request): JsonResponse
    {
        $request->validate(['noreg' => 'required|string', 'jenis_pembayaran' => 'required|string|max:50', 'rincian' => 'required|array|min:1', 'rincian.*' => 'required|string']);
        return DB::transaction(function () use ($request) {
            $requested = collect($request->rincian)->unique()->values();
            $paidKeys = $this->paidKeys($request->noreg);
            $details = collect($this->details($request->noreg))->keyBy('key');
            $invalid = $requested->filter(fn ($key) => !isset($details[$key]) || (float) $details[$key]['nominal'] <= 0 || in_array($key, $paidKeys, true));
            if ($invalid->isNotEmpty()) return new JsonResponse(['message' => 'Ada rincian yang sudah terbayar atau tidak valid.'], 422);
            $selected = $requested->map(fn ($key) => $details[$key]);
            $user = auth()->user();
            $login = $user->username ?? $user->name ?? $user->email ?? (string) $user->id;
            $noPembayaran = 'IGD' . now()->format('ymdHis') . random_int(10, 99);
            $adminId = DB::table('rs35x')->where('rs1', $request->noreg)->where('rs3', 'A2#')->pluck('id')->implode(',');
            $laboratNota = DB::table('rs51')->where('rs1', $request->noreg)->where('rs23', 'POL014')->where('rs18', '!=', '')->where('lunas', '!=', '1')->pluck('rs2')->filter()->unique()->implode(',');
            $tindakanNota = DB::table('rs73')->where('rs1', $request->noreg)->where('rs22', 'POL014')->pluck('rs2')->filter()->unique()->implode(',');
            $radiologiId = DB::table('rs48')->where('rs1', $request->noreg)->pluck('id')->filter()->unique()->implode(',');
            $farmasiResep = collect([
                DB::table('rs38')->where('rs1', $request->noreg)->where('rs20', 'POL014')->pluck('rs2'),
                DB::table('rs39')->where('rs1', $request->noreg)->where('rs18', 'IRD')->pluck('rs2'),
                DB::table('rs63')->where('rs1', $request->noreg)->where('rs18', 'IRD')->pluck('rs2'),
            ])->flatten()->filter()->unique()->implode(',');
            $idTransMap = ['administrasi' => $adminId, 'laboratorium' => $laboratNota, 'tindakan' => $tindakanNota, 'farmasi' => $farmasiResep, 'radiologi' => $radiologiId];
            $rincian = $selected->map(function ($item) use ($idTransMap) {
                $idTrans = $idTransMap[$item['key']] ?? $item['key'];
                return implode('|', ['igd', round($item['nominal']), $idTrans, 'IGD', 'POL014', $item['nama']]);
            })->implode(';');
            DB::table('rs35')->insert(['rs1' => $request->noreg, 'rs2' => $noPembayaran, 'rs3' => self::TYPE, 'rs4' => now(), 'rs5' => 'C', 'rs6' => 'Pembayaran IGD', 'rs7' => $selected->sum('nominal'), 'rs10' => $login, 'rs13' => '1', 'kwitansi_d' => $rincian, 'jenis_pembayaran' => $request->jenis_pembayaran]);
            return new JsonResponse(['message' => 'Pembayaran IGD berhasil disimpan.', 'no_pembayaran' => $noPembayaran, 'total' => $selected->sum('nominal')]);
        });
    }
}
