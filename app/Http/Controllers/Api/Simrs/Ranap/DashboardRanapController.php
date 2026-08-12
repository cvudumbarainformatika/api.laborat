<?php

namespace App\Http\Controllers\Api\Simrs\Ranap;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardRanapController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-t'));
        $koderuangan = $request->input('koderuangan');

        $fromFull = $from . ' 00:00:00';
        $toFull = $to . ' 23:59:59';

        // Resolve Kode Ruangan (Matching rs1 or groups)
        $kdRuanganList = [];
        if (!empty($koderuangan)) {
            $kdRuanganList = DB::table('rs24')
                ->where('rs1', '=', $koderuangan)
                ->orWhere('groups', '=', $koderuangan)
                ->pluck('rs1')
                ->toArray();
            if (empty($kdRuanganList)) {
                $kdRuanganList = [$koderuangan];
            }
        }

        // --- 1. Pasien Aktif (Belum Pulang - Sensus Real-Time Presisi 100% Cocok dengan List Pengunjung) ---
        $queryAktifBase = function () use ($kdRuanganList) {
            $q = DB::table('rs23')
                ->where(function ($query) {
                    $query->where('rs23.rs22', '=', '')
                        ->orWhere('rs23.rs22', '=', '1');
                })
                ->where('rs23.rs1', '!=', '');

            if (!empty($kdRuanganList)) {
                $q->whereIn('rs23.rs5', $kdRuanganList);
            }
            return $q;
        };

        $pasienAktifList = $queryAktifBase()->select(
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs5 as kdruangan',
            'rs23.rs10 as kddokter',
            'rs23.rs19 as kdsistembayar'
        )->get();

        $totalPasienAktif = $pasienAktifList->count();

        // --- 2. Pasien Masuk & Pulang (Rentang Tanggal) ---
        $queryMasuk = DB::table('rs23')
            ->where('rs23.rs1', '!=', '')
            ->whereBetween('rs23.rs3', [$fromFull, $toFull]);
        if (!empty($kdRuanganList)) {
            $queryMasuk->whereIn('rs23.rs5', $kdRuanganList);
        }
        $totalPasienMasuk = $queryMasuk->count();

        $queryPulang = DB::table('rs23')
            ->where('rs23.rs1', '!=', '')
            ->whereIn('rs23.rs22', ['2', '3'])
            ->whereBetween('rs23.rs4', [$fromFull, $toFull]);
        if (!empty($kdRuanganList)) {
            $queryPulang->whereIn('rs23.rs5', $kdRuanganList);
        }
        $totalPasienPulang = $queryPulang->count();

        // --- 3. Tempat Tidur (Bed Capacity & BOR) ---
        $whereBedRuang = "";
        if (!empty($kdRuanganList)) {
            $quotedCodes = array_map(function ($c) {
                return DB::getPdo()->quote($c);
            }, $kdRuanganList);
            $whereBedRuang = "AND rs24.rs1 IN (" . implode(',', $quotedCodes) . ") ";
        }

        $tempatTidurRaw = DB::select(
            "SELECT UPPER(rs24.rs2) AS ruang, rs24.rs1 AS kdruangan, COUNT(vBed.rs5) AS total, SUM(vBed.terisi) AS terisi, (COUNT(vBed.rs5) - SUM(vBed.terisi)) AS sisa
             FROM (
                SELECT rs5, IF(rs3='S',1,0) AS terisi FROM rs25 WHERE rs7<>'1' AND extra<>'1' AND rs5<>'-' AND rs8<>'1'
             ) AS vBed
             JOIN rs24 ON rs24.rs1 = vBed.rs5
             WHERE rs24.status <> '1' AND rs24.rs4 <> 'BR' " .
             $whereBedRuang .
             "GROUP BY vBed.rs5, rs24.rs2, rs24.rs1
             ORDER BY ruang ASC"
        );

        $totalBedSystem = 0;
        $totalBedTerisi = 0;
        $totalBedSisa = 0;
        $keterisianRuanganFormatted = [];

        foreach ($tempatTidurRaw as $row) {
            $t = (int) $row->total;
            $isi = (int) $row->terisi;
            $sisa = (int) $row->sisa;
            $pct = $t > 0 ? round(($isi / $t) * 100, 1) : 0;

            $totalBedSystem += $t;
            $totalBedTerisi += $isi;
            $totalBedSisa += $sisa;

            $keterisianRuanganFormatted[] = [
                'kode' => $row->kdruangan,
                'nama' => $row->ruang,
                'total_bed' => $t,
                'terisi' => $isi,
                'sisa' => $sisa,
                'persen' => $pct
            ];
        }

        $borSystem = $totalBedSystem > 0 ? round(($totalBedTerisi / $totalBedSystem) * 100, 1) : 0;

        // --- 4. Distribusi Sistem Bayar ---
        $sistemBayarRaw = $queryAktifBase()
            ->join('rs9', 'rs9.rs1', '=', 'rs23.rs19')
            ->select('rs9.rs1 as kode', 'rs9.rs2 as nama', DB::raw('COUNT(rs23.rs1) as total'))
            ->groupBy('rs9.rs1', 'rs9.rs2')
            ->orderBy('total', 'desc')
            ->get();

        $sistemBayarFormatted = [];
        foreach ($sistemBayarRaw as $sb) {
            $cnt = (int) $sb->total;
            $pct = $totalPasienAktif > 0 ? round(($cnt / $totalPasienAktif) * 100, 1) : 0;
            $sistemBayarFormatted[] = [
                'kode' => $sb->kode,
                'nama' => $sb->nama,
                'total' => $cnt,
                'persen' => $pct
            ];
        }

        // --- 5. Distribusi DPJP ---
        $dpjpRaw = $queryAktifBase()
            ->leftJoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs23.rs10')
            ->whereNotNull('rs23.rs10')
            ->where('rs23.rs10', '<>', '')
            ->select(
                'rs23.rs10 as kode',
                DB::raw('COALESCE(kepegx.pegawai.nama, "Belum Ditentukan") as nama'),
                DB::raw('COUNT(rs23.rs1) as total')
            )
            ->groupBy('rs23.rs10', 'kepegx.pegawai.nama')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        $dpjpFormatted = [];
        foreach ($dpjpRaw as $dp) {
            $cnt = (int) $dp->total;
            $pct = $totalPasienAktif > 0 ? round(($cnt / $totalPasienAktif) * 100, 1) : 0;
            $dpjpFormatted[] = [
                'kode' => $dp->kode,
                'nama' => $dp->nama,
                'total' => $cnt,
                'persen' => $pct
            ];
        }

        // --- 6. Distribusi Cara Pulang ---
        $queryCaraPulang = DB::table('rs23')
            ->leftJoin('rs26', 'rs26.rs1', '=', 'rs23.rs23')
            ->where('rs23.rs1', '!=', '')
            ->whereIn('rs23.rs22', ['2', '3'])
            ->whereBetween('rs23.rs4', [$fromFull, $toFull]);
        if (!empty($kdRuanganList)) {
            $queryCaraPulang->whereIn('rs23.rs5', $kdRuanganList);
        }
        $caraPulangRaw = $queryCaraPulang
            ->select(
                'rs26.rs1 as kode',
                DB::raw('COALESCE(rs26.rs2, "Lain-lain") as nama'),
                DB::raw('COUNT(rs23.rs1) as total')
            )
            ->groupBy('rs26.rs1', 'rs26.rs2')
            ->orderBy('total', 'desc')
            ->get();

        $caraPulangFormatted = [];
        foreach ($caraPulangRaw as $cp) {
            $cnt = (int) $cp->total;
            $pct = $totalPasienPulang > 0 ? round(($cnt / $totalPasienPulang) * 100, 1) : 0;
            $caraPulangFormatted[] = [
                'kode' => $cp->kode ?? '-',
                'nama' => $cp->nama,
                'total' => $cnt,
                'persen' => $pct
            ];
        }

        // --- 7. Indikator Risiko Pasien (Flagging Summary) ---
        $activeNoregs = $pasienAktifList->pluck('noreg')->filter()->unique()->toArray();
        $activeNorms = $pasienAktifList->pluck('norm')->filter()->unique()->toArray();

        $cntResikoJatuh = 0;
        $cntDnr = 0;
        $cntMpp = 0;
        $cntAlergi = 0;
        $cntBerisikoKekerasan = 0;

        if (!empty($activeNoregs)) {
            // Resiko Jatuh
            $penilaians = DB::table('penilaian')
                ->select('humpty_dumpty', 'morse_fall', 'ontario')
                ->whereIn('rs1', $activeNoregs)
                ->get();
            foreach ($penilaians as $pen) {
                if (
                    ($pen->humpty_dumpty && (strpos($pen->humpty_dumpty, '"kuning":true') !== false || strpos($pen->humpty_dumpty, '"kuning": true') !== false)) ||
                    ($pen->morse_fall && (strpos($pen->morse_fall, '"kuning":true') !== false || strpos($pen->morse_fall, '"kuning": true') !== false)) ||
                    ($pen->ontario && (strpos($pen->ontario, '"kuning":true') !== false || strpos($pen->ontario, '"kuning": true') !== false))
                ) {
                    $cntResikoJatuh++;
                }
            }

            // DNR / Penolakan Resusitasi
            $cntDnr = DB::table('inform_concern')
                ->whereIn('noreg', $activeNoregs)
                ->where('jenis', '=', 'Resusitasi')
                ->where('setuju', '=', 'Tidak')
                ->count();

            // Pasien MPP
            $mppList = DB::table('mpp_skrinings')
                ->select('skrining')
                ->whereIn('noreg', $activeNoregs)
                ->get();
            $mppKeys = ['usia', 'kognitif_rendah', 'resiko_tinggi', 'potensi_komplain', 'kasus_penyakit', 'keterbatasan_adl', 'pakai_alat_medis', 'riwayat_psikologis', 'readmisi', 'biaya_tinggi', 'pembiayaan_komplek', 'melebihi_los', 'transfer_rujukan', 'kerjasama_sektor', 'kontinuitas_pelayanan'];
            foreach ($mppList as $mppItem) {
                if ($mppItem->skrining) {
                    $skr = json_decode($mppItem->skrining, true);
                    if (is_array($skr)) {
                        $score = 0;
                        foreach ($mppKeys as $k) {
                            if (!empty($skr[$k])) {
                                $score++;
                            }
                        }
                        if ($score >= 3) {
                            $cntMpp++;
                        }
                    }
                }
            }

            // Alergi Pasien
            if (!empty($activeNorms)) {
                $cntAlergi = DB::table('rs209')
                    ->whereIn('rs2', $activeNorms)
                    ->whereNotNull('riwayatalergi')
                    ->where('riwayatalergi', '!=', '')
                    ->where('riwayatalergi', '!=', '[]')
                    ->where('riwayatalergi', 'not like', '%tidak ada%')
                    ->where('riwayatalergi', 'not like', '%tdk ada%')
                    ->distinct('rs2')
                    ->count('rs2');
            }

            // Berisiko Kekerasan (Pasien Aktif)
            $pasienDetailList = DB::table('rs23')
                ->join('rs15', 'rs15.rs1', '=', 'rs23.rs2')
                ->leftJoin('mhambatan', 'mhambatan.id', '=', 'rs15.kdhambatan')
                ->select(
                    'rs23.rs1 as noreg',
                    'rs15.rs16 as tgllahir',
                    'rs15.rs17 as kelamin',
                    'rs15.kdhambatan',
                    'mhambatan.hambatan'
                )
                ->whereIn('rs23.rs1', $activeNoregs)
                ->get();

            $kesadarans = DB::table('rs253')
                ->join('rs253_sambung', 'rs253_sambung.rs253_id', '=', 'rs253.id')
                ->select('rs253.rs1 as noreg', 'rs253_sambung.tkKesadaran', 'rs253_sambung.tkKesadaranKet')
                ->whereIn('rs253.rs1', $activeNoregs)
                ->get()
                ->groupBy('noreg');

            $mppMap = DB::table('mpp_skrinings')
                ->select('noreg', 'skrining')
                ->whereIn('noreg', $activeNoregs)
                ->get()
                ->groupBy('noreg');

            foreach ($pasienDetailList as $px) {
                $res = RanapController::getBerisikoKekerasan($px, $mppMap, $kesadarans);
                if ($res['status'] === true) {
                    $cntBerisikoKekerasan++;
                }
            }
        }

        // --- 8. Tren Kunjungan Harian (Masuk vs Pulang) ---
        $trenMasukRaw = DB::table('rs23')
            ->select(DB::raw('DATE(rs23.rs3) as tgl'), DB::raw('COUNT(*) as total'))
            ->where('rs23.rs1', '!=', '')
            ->whereBetween('rs23.rs3', [$fromFull, $toFull])
            ->when(!empty($kdRuanganList), function ($q) use ($kdRuanganList) {
                $q->whereIn('rs23.rs5', $kdRuanganList);
            })
            ->groupBy(DB::raw('DATE(rs23.rs3)'))
            ->pluck('total', 'tgl')
            ->toArray();

        $trenPulangRaw = DB::table('rs23')
            ->select(DB::raw('DATE(rs23.rs4) as tgl'), DB::raw('COUNT(*) as total'))
            ->where('rs23.rs1', '!=', '')
            ->whereIn('rs23.rs22', ['2', '3'])
            ->whereBetween('rs23.rs4', [$fromFull, $toFull])
            ->when(!empty($kdRuanganList), function ($q) use ($kdRuanganList) {
                $q->whereIn('rs23.rs5', $kdRuanganList);
            })
            ->groupBy(DB::raw('DATE(rs23.rs4)'))
            ->pluck('total', 'tgl')
            ->toArray();

        $trenKunjungan = [];
        $startDate = Carbon::parse($from);
        $endDate = Carbon::parse($to);

        while ($startDate->lte($endDate)) {
            $tglKey = $startDate->format('Y-m-d');
            $jmlMasuk = $trenMasukRaw[$tglKey] ?? 0;
            $jmlPulang = $trenPulangRaw[$tglKey] ?? 0;

            $trenKunjungan[] = [
                'tanggal' => $tglKey,
                'masuk' => $jmlMasuk,
                'pulang' => $jmlPulang
            ];

            $startDate->addDay();
        }

        // --- Construct Final Response ---
        return response()->json([
            'status' => 200,
            'message' => 'Data Dashboard Rawat Inap Berhasil Dimuat',
            'from' => $from,
            'to' => $to,
            'summary' => [
                'pasien_aktif' => $totalPasienAktif,
                'pasien_masuk' => $totalPasienMasuk,
                'pasien_pulang' => $totalPasienPulang,
                'total_bed' => $totalBedSystem,
                'bed_terisi' => $totalBedTerisi,
                'bed_sisa' => $totalBedSisa,
                'bor_percent' => $borSystem
            ],
            'keterisian_ruangan' => $keterisianRuanganFormatted,
            'sistem_bayar' => $sistemBayarFormatted,
            'dpjp' => $dpjpFormatted,
            'cara_pulang' => $caraPulangFormatted,
            'indikator_risiko' => [
                'berisiko_kekerasan' => $cntBerisikoKekerasan,
                'resiko_jatuh' => $cntResikoJatuh,
                'penolakan_resusitasi' => $cntDnr,
                'pasien_mpp' => $cntMpp,
                'alergis' => $cntAlergi
            ],
            'tren_kunjungan' => $trenKunjungan
        ]);
    }
}
