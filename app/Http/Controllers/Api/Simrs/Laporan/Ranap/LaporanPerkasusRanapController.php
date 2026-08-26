<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Ranap;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPerkasusRanapController extends Controller
{
    public function index(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $bulan = $request->input('bulan', 'ALL');
        $kdDiagnosaRaw = $request->input('kd_diagnosa');
        $limitParam = $request->input('limit', 500); // Default 500 rows untuk respon kilat web

        // 1. Parsing kode ICD-10
        $kdDiagnosaArr = [];
        if (!empty($kdDiagnosaRaw)) {
            if (is_array($kdDiagnosaRaw)) {
                $kdDiagnosaArr = $kdDiagnosaRaw;
            } else {
                $kdDiagnosaArr = array_filter(array_map('trim', explode(',', $kdDiagnosaRaw)));
            }
        }

        // Jika filter kode diagnosa belum dipilih, langsung return array kosong (Fast Return)
        if (empty($kdDiagnosaArr)) {
            return new JsonResponse(['total' => 0, 'data' => []]);
        }

        // 2. Rentang tanggal presisi memanfaatkan B-Tree Index rs3 di rs23
        if ($bulan !== 'ALL' && !empty($bulan) && is_numeric($bulan)) {
            $monthFormatted = sprintf('%02d', (int)$bulan);
            $startDate = "{$tahun}-{$monthFormatted}-01 00:00:00";
            $endDate = date("Y-m-t 23:59:59", strtotime($startDate));
        } else {
            $startDate = "{$tahun}-01-01 00:00:00";
            $endDate = "{$tahun}-12-31 23:59:59";
        }

        // Quote & format ICD codes untuk SQL query murni STRAIGHT_JOIN
        $escapedCodes = array_map(function ($code) {
            return "'" . addslashes($code) . "'";
        }, $kdDiagnosaArr);
        $icdCodesSql = implode(',', $escapedCodes);

        // Hitung total seluruh data pasien yang sesuai filter
        $totalCount = DB::table('rs23 as ranap')
            ->join('rs101 as diag', function ($join) use ($kdDiagnosaArr) {
                $join->on('diag.rs1', '=', 'ranap.rs1')
                    ->whereIn('diag.rs3', $kdDiagnosaArr);
            })
            ->where('ranap.rs3', '>=', $startDate)
            ->where('ranap.rs3', '<=', $endDate)
            ->count();

        // Limit Clause
        $limitSql = '';
        if ($limitParam !== 'all' && is_numeric($limitParam) && (int)$limitParam > 0) {
            $limitVal = (int)$limitParam;
            $limitSql = "LIMIT {$limitVal}";
        }

        // 3. ULTRA FAST SQL DENGAN SUBQUERY TERKONTROL (Mencegah Perkalian Kartesian Data / Duplicate Rows)
        $sql = "
            SELECT 
                ranap.rs1 as noreg,
                ranap.rs2 as norm,
                ranap.rs3 as mrs,
                ranap.rs4 as krs,
                ranap.rs5 as kdruang,
                ranap.rs10 as kddokter,
                ranap.rs19 as kdsistembayar,
                ranap.rs23 as carakeluar_code,
                ranap.rs26 as diagakhir,
                pasien.rs2 as nama_pasien,
                pasien.rs4 as alamat,
                pasien.rs17 as kelamin,
                pasien.rs16 as tgllahir,
                pasien.rs49 as nik,
                ruang.rs2 as ruang,
                dokter.rs2 as dpjp,
                sistembayar.rs2 as sistem_bayar,
                carakeluar.rs2 as cara_keluar,
                diag.kode_diagnosa,
                diag.kode_diagnosa,
                diag_master.rs4 as nama_diagnosa
            FROM rs23 as ranap
            JOIN (
                SELECT rs1, MIN(rs3) as kode_diagnosa 
                FROM rs101 
                WHERE rs3 IN ({$icdCodesSql})
                GROUP BY rs1
            ) as diag ON diag.rs1 = ranap.rs1
            LEFT JOIN rs99x as diag_master ON diag_master.rs1 = diag.kode_diagnosa
            LEFT JOIN rs15 as pasien ON pasien.rs1 = ranap.rs2
            LEFT JOIN rs24 as ruang ON ruang.rs1 = ranap.rs5
            LEFT JOIN rs21 as dokter ON dokter.rs1 = ranap.rs10
            LEFT JOIN rs9 as sistembayar ON sistembayar.rs1 = ranap.rs19
            LEFT JOIN rs26 as carakeluar ON carakeluar.rs1 = ranap.rs23
            WHERE ranap.rs3 >= '{$startDate}' AND ranap.rs3 <= '{$endDate}'
            ORDER BY ranap.rs3 DESC
            {$limitSql}
        ";

        $results = collect(DB::select($sql));

        if ($results->isEmpty()) {
            return new JsonResponse(['total' => 0, 'data' => []]);
        }

        $noregs = $results->pluck('noreg')->unique()->filter()->toArray();
        $norms = $results->pluck('norm')->unique()->filter()->toArray();

        // 4. Batch Query Tgl Masuk IGD
        $igdMap = [];
        if (!empty($norms)) {
            $igdVisits = DB::table('rs17')
                ->select('rs2 as norm', DB::raw('MAX(rs3) as tgl_igd'))
                ->whereIn('rs2', $norms)
                ->where('rs8', 'POL014')
                ->groupBy('rs2')
                ->get();

            foreach ($igdVisits as $v) {
                $igdMap[$v->norm] = $v->tgl_igd;
            }
        }

        // 5. Batch Query Anamnesis Awal (rs209)
        $anamnesisMap = [];
        if (!empty($noregs)) {
            $anamnesisRows = DB::table('rs209')
                ->select('rs1 as noreg', 'rs4 as keluhan_utama', 'riwayatpenyakitsekarang as rps')
                ->whereIn('rs1', $noregs)
                ->orderBy('id', 'asc')
                ->get();

            foreach ($anamnesisRows as $a) {
                $anamnesisMap[$a->noreg] = [
                    'keluhan_utama' => $a->keluhan_utama,
                    'rps' => $a->rps
                ];
            }
        }

        // 6. Batch Query Memo Diagnosa Dokter (memodiagnosadokter)
        $memoMap = [];
        if (!empty($noregs)) {
            $memoRows = DB::table('memodiagnosadokter')
                ->select('noreg', 'diagnosa')
                ->whereIn('noreg', $noregs)
                ->orderBy('id', 'asc')
                ->get();

            foreach ($memoRows as $m) {
                $memoMap[$m->noreg] = $m->diagnosa;
            }
        }

        // 7. Batch Query Diagnosa Sekunder Pasien
        $diagnosaSekunderMap = [];
        if (!empty($noregs)) {
            $sekunders = DB::table('rs101 as diag')
                ->select([
                    'diag.rs1 as noreg',
                    'diag.rs3 as kode_icd',
                    'master.rs4 as nama_icd'
                ])
                ->leftJoin('rs99x as master', 'master.rs1', '=', 'diag.rs3')
                ->whereIn('diag.rs1', $noregs)
                ->whereNotIn('diag.rs3', $kdDiagnosaArr)
                ->get();

            foreach ($sekunders as $s) {
                $diagnosaSekunderMap[$s->noreg][] = $s->kode_icd . ' - ' . ($s->nama_icd ?: '');
            }
        }

        // 8. Format Response JSON Kilat
        $data = $results->map(function ($row) use ($igdMap, $anamnesisMap, $memoMap, $diagnosaSekunderMap) {
            $noreg = $row->noreg;
            $norm = $row->norm;

            // Hitung LOS (Length of Stay dalam Hari)
            $mrsCarbon = $row->mrs ? Carbon::parse($row->mrs) : null;
            $krsCarbon = ($row->krs && $row->krs !== '0000-00-00 00:00:00') ? Carbon::parse($row->krs) : Carbon::now();
            $los = ($mrsCarbon && $krsCarbon) ? max(1, $mrsCarbon->diffInDays($krsCarbon)) : 1;

            // Hitung Usia Pasien
            $usia = '-';
            if (!empty($row->tgllahir) && $row->tgllahir !== '0000-00-00') {
                $usia = Carbon::parse($row->tgllahir)->age . ' Thn';
            }

            $diagSekunderList = $diagnosaSekunderMap[$noreg] ?? [];
            $anamnesisData = $anamnesisMap[$noreg] ?? [];

            return [
                'noreg' => $row->noreg,
                'norm' => $row->norm,
                'nik' => $row->nik ?: '-',
                'nama' => $row->nama_pasien ?: '-',
                'alamat' => $row->alamat ?: '-',
                'kelamin' => $row->kelamin === 'L' ? 'Laki-Laki' : ($row->kelamin === 'P' ? 'Perempuan' : ($row->kelamin ?: '-')),
                'usia' => $usia,
                'tgl_masuk_igd' => $igdMap[$norm] ?? '-',
                'mrs' => $row->mrs ?: '-',
                'krs' => ($row->krs && $row->krs !== '0000-00-00 00:00:00') ? $row->krs : '-',
                'los' => (string)$los,
                'ruang' => $row->ruang ?: '-',
                'dpjp' => $row->dpjp ?: '-',
                'sistem_bayar' => $row->sistem_bayar ?: '-',
                'cara_keluar' => $row->cara_keluar ?: '-',
                'kode_diagnosa' => $row->kode_diagnosa ?: '-',
                'diagnosa' => $row->nama_diagnosa ?: ($row->diagakhir ?: '-'),
                'anamnese_awal' => $anamnesisData['keluhan_utama'] ?? '-',
                'riwayat_penyakit_sekarang' => $anamnesisData['rps'] ?? '-',
                'diagnosa_tambahan' => !empty($diagSekunderList) ? implode('; ', $diagSekunderList) : '-',
                'memodiagnosa' => $memoMap[$noreg] ?? '-'
            ];
        });

        return new JsonResponse([
            'total' => $totalCount,
            'data' => $data
        ]);
    }
}
