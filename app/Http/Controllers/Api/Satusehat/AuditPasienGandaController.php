<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Satset\SatsetAuditDataLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditPasienGandaController extends Controller
{
    /**
     * Ringkasan Statistik Temuan Pasien Terindikasi Ganda
     */
    public function stats(): JsonResponse
    {
        // 1. NIK Ganda
        $resNik = DB::select("
            SELECT COUNT(*) as total FROM (
                SELECT rs49 FROM rs15 
                WHERE rs49 IS NOT NULL AND LENGTH(TRIM(rs49)) = 16 
                  AND rs49 NOT IN ('0000000000000000', '1111111111111111', '1234567890123456')
                GROUP BY rs49 
                HAVING COUNT(*) > 1
            ) as sub
        ");
        $nikGanda = $resNik[0]->total ?? 0;

        // 2. Catatan No. RM di Nama Pasien (misal: UNTUNG 123456, MOENTAMAH / 667196, RUSNATI 135595)
        $resNama = DB::select("
            SELECT COUNT(*) as total FROM rs15 
            WHERE rs2 REGEXP '[0-9]{4,8}'
        ");
        $namaRmLama = $resNama[0]->total ?? 0;

        // 3. No. Kartu BPJS Ganda
        $resBpjs = DB::select("
            SELECT COUNT(*) as total FROM (
                SELECT rs46 FROM rs15 
                WHERE rs46 IS NOT NULL AND LENGTH(TRIM(rs46)) >= 11 
                  AND rs46 NOT IN ('00000000000', '000000000000', '0000000000000', '00000000000000', '0000000000000000')
                GROUP BY rs46 
                HAVING COUNT(*) > 1
            ) as sub
        ");
        $bpjsGanda = $resBpjs[0]->total ?? 0;

        // 4. Nama Lengkap + Tgl Lahir + Jenis Kelamin Sama
        $resNamaTgl = DB::select("
            SELECT COUNT(*) as total FROM (
                SELECT TRIM(LOWER(rs2)) as n, rs16, rs17 FROM rs15 
                WHERE rs2 IS NOT NULL AND rs2 != '' AND rs16 IS NOT NULL AND rs16 != '0000-00-00'
                GROUP BY TRIM(LOWER(rs2)), rs16, rs17 
                HAVING COUNT(*) > 1
            ) as sub
        ");
        $namaTglLahir = $resNamaTgl[0]->total ?? 0;

        // 5. Nama Lengkap + Tgl Lahir + Nama Ibu Kandung Sama
        $resIbu = DB::select("
            SELECT COUNT(*) as total FROM (
                SELECT TRIM(LOWER(rs2)) as n, rs16, TRIM(LOWER(namaibu)) as ibu FROM rs15 
                WHERE rs2 IS NOT NULL AND rs2 != '' AND rs16 IS NOT NULL AND rs16 != '0000-00-00'
                  AND namaibu IS NOT NULL AND namaibu != '' 
                  AND TRIM(LOWER(namaibu)) NOT IN ('-', '--', 'tidak tahu', 'tidak ada', 'unknown', 'ibu', 'alm', 'almarhumah')
                GROUP BY TRIM(LOWER(rs2)), rs16, TRIM(LOWER(namaibu)) 
                HAVING COUNT(*) > 1
            ) as sub
        ");
        $namaTglLahirIbu = $resIbu[0]->total ?? 0;

        $stats = [
            'nik_ganda' => (int) $nikGanda,
            'nama_rm_lama' => (int) $namaRmLama,
            'bpjs_ganda' => (int) $bpjsGanda,
            'nama_tgllahir_ganda' => (int) $namaTglLahir,
            'nama_tgllahir_ibu_ganda' => (int) $namaTglLahirIbu,
            'total_grup_terdeteksi' => (int) ($nikGanda + $namaRmLama + $bpjsGanda + $namaTglLahir + $namaTglLahirIbu)
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Mengambil Daftar Grup Pasien Terindikasi Ganda dengan Pagination & Detail
     */
    public function list(Request $request): JsonResponse
    {
        $kategori = $request->input('kategori', 'nik_ganda');
        $q = trim($request->input('q', ''));
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);
        $offset = ($page - 1) * $perPage;

        $groupsData = [];
        $totalGroups = 0;

        if ($kategori === 'nik_ganda') {
            $sqlCount = "
                SELECT COUNT(*) as total FROM (
                    SELECT rs49 as key_identifier,
                           GROUP_CONCAT(rs1 SEPARATOR ',') as norm_list,
                           GROUP_CONCAT(rs2 SEPARATOR ' | ') as nama_list
                    FROM rs15 
                    WHERE rs49 IS NOT NULL AND LENGTH(TRIM(rs49)) = 16 
                      AND rs49 NOT IN ('0000000000000000', '1111111111111111', '1234567890123456')
                    GROUP BY rs49 
                    HAVING COUNT(*) > 1 " . (!empty($q) ? "AND (rs49 LIKE '%$q%' OR nama_list LIKE '%$q%' OR norm_list LIKE '%$q%')" : "") . "
                ) as sub
            ";
            $resCount = DB::select($sqlCount);
            $totalGroups = $resCount[0]->total ?? 0;

            $sqlData = "
                SELECT rs49 as key_identifier,
                       'nik' as key_type,
                       COUNT(*) as total_member,
                       GROUP_CONCAT(rs1 ORDER BY rs1 ASC SEPARATOR ',') as norm_list,
                       GROUP_CONCAT(rs2 ORDER BY rs1 ASC SEPARATOR ' | ') as nama_list,
                       MAX(updated_at) as latest_update
                FROM rs15 
                WHERE rs49 IS NOT NULL AND LENGTH(TRIM(rs49)) = 16 
                  AND rs49 NOT IN ('0000000000000000', '1111111111111111', '1234567890123456')
                GROUP BY rs49 
                HAVING COUNT(*) > 1 " . (!empty($q) ? "AND (rs49 LIKE '%$q%' OR nama_list LIKE '%$q%' OR norm_list LIKE '%$q%')" : "") . "
                ORDER BY total_member DESC, rs49 ASC
                LIMIT $perPage OFFSET $offset
            ";
            $groups = DB::select($sqlData);
        } elseif ($kategori === 'nama_rm_lama') {
            $filterQ = !empty($q) ? "AND (rs1 LIKE '%$q%' OR rs2 LIKE '%$q%' OR rs49 LIKE '%$q%')" : "";
            $sqlCount = "
                SELECT COUNT(*) as total FROM rs15 
                WHERE rs2 REGEXP '[0-9]{4,8}'
                   $filterQ
            ";
            $resCount = DB::select($sqlCount);
            $totalGroups = $resCount[0]->total ?? 0;

            $sqlData = "
                SELECT rs1 as key_identifier,
                       'norm' as key_type,
                       1 as total_member,
                       rs1 as norm_list,
                       rs2 as nama_list,
                       updated_at as latest_update
                FROM rs15 
                WHERE rs2 REGEXP '[0-9]{4,8}'
                   $filterQ
                ORDER BY rs1 DESC
                LIMIT $perPage OFFSET $offset
            ";
            $rawGroups = DB::select($sqlData);
            
            // Auto-pairing: ekstrak angka RM dari nama dan cari No. RM pasangannya
            $groups = [];
            foreach ($rawGroups as $rg) {
                preg_match('/[0-9]{4,8}/', $rg->nama_list, $matches);
                $extractedNorm = $matches[0] ?? null;
                $normList = $rg->norm_list;

                if ($extractedNorm && $extractedNorm !== $rg->key_identifier) {
                    $normList .= ",$extractedNorm";
                }

                $rg->norm_list = $normList;
                $groups[] = $rg;
            }
        } elseif ($kategori === 'bpjs_ganda') {
            $sqlCount = "
                SELECT COUNT(*) as total FROM (
                    SELECT rs46 as key_identifier,
                           GROUP_CONCAT(rs1 SEPARATOR ',') as norm_list,
                           GROUP_CONCAT(rs2 SEPARATOR ' | ') as nama_list
                    FROM rs15 
                    WHERE rs46 IS NOT NULL AND LENGTH(TRIM(rs46)) >= 11 
                      AND rs46 NOT IN ('00000000000', '000000000000', '0000000000000', '00000000000000', '0000000000000000')
                    GROUP BY rs46 
                    HAVING COUNT(*) > 1 " . (!empty($q) ? "AND (rs46 LIKE '%$q%' OR nama_list LIKE '%$q%' OR norm_list LIKE '%$q%')" : "") . "
                ) as sub
            ";
            $resCount = DB::select($sqlCount);
            $totalGroups = $resCount[0]->total ?? 0;

            $sqlData = "
                SELECT rs46 as key_identifier,
                       'noka' as key_type,
                       COUNT(*) as total_member,
                       GROUP_CONCAT(rs1 ORDER BY rs1 ASC SEPARATOR ',') as norm_list,
                       GROUP_CONCAT(rs2 ORDER BY rs1 ASC SEPARATOR ' | ') as nama_list,
                       MAX(updated_at) as latest_update
                FROM rs15 
                WHERE rs46 IS NOT NULL AND LENGTH(TRIM(rs46)) >= 11 
                  AND rs46 NOT IN ('00000000000', '000000000000', '0000000000000', '00000000000000', '0000000000000000')
                GROUP BY rs46 
                HAVING COUNT(*) > 1 " . (!empty($q) ? "AND (rs46 LIKE '%$q%' OR nama_list LIKE '%$q%' OR norm_list LIKE '%$q%')" : "") . "
                ORDER BY total_member DESC, rs46 ASC
                LIMIT $perPage OFFSET $offset
            ";
            $groups = DB::select($sqlData);
        } elseif ($kategori === 'nama_tgllahir_ganda') {
            $sqlCount = "
                SELECT COUNT(*) as total FROM (
                    SELECT TRIM(LOWER(rs2)) as n, rs16, rs17,
                           GROUP_CONCAT(rs1 SEPARATOR ',') as norm_list,
                           GROUP_CONCAT(rs2 SEPARATOR ' | ') as nama_list
                    FROM rs15 
                    WHERE rs2 IS NOT NULL AND rs2 != '' AND rs16 IS NOT NULL AND rs16 != '0000-00-00'
                    GROUP BY TRIM(LOWER(rs2)), rs16, rs17 
                    HAVING COUNT(*) > 1 " . (!empty($q) ? "AND (nama_list LIKE '%$q%' OR norm_list LIKE '%$q%')" : "") . "
                ) as sub
            ";
            $resCount = DB::select($sqlCount);
            $totalGroups = $resCount[0]->total ?? 0;

            $sqlData = "
                SELECT CONCAT(TRIM(LOWER(rs2)), ' # ', IFNULL(rs16, '')) as key_identifier,
                       'nama_tgl' as key_type,
                       COUNT(*) as total_member,
                       GROUP_CONCAT(rs1 ORDER BY rs1 ASC SEPARATOR ',') as norm_list,
                       GROUP_CONCAT(rs2 ORDER BY rs1 ASC SEPARATOR ' | ') as nama_list,
                       MAX(updated_at) as latest_update
                FROM rs15 
                WHERE rs2 IS NOT NULL AND rs2 != '' AND rs16 IS NOT NULL AND rs16 != '0000-00-00'
                GROUP BY TRIM(LOWER(rs2)), rs16, rs17 
                HAVING COUNT(*) > 1 " . (!empty($q) ? "AND (nama_list LIKE '%$q%' OR norm_list LIKE '%$q%')" : "") . "
                ORDER BY total_member DESC
                LIMIT $perPage OFFSET $offset
            ";
            $groups = DB::select($sqlData);
        } else { // nama_tgllahir_ibu_ganda
            $sqlCount = "
                SELECT COUNT(*) as total FROM (
                    SELECT TRIM(LOWER(rs2)) as n, rs16, TRIM(LOWER(namaibu)) as ibu,
                           GROUP_CONCAT(rs1 SEPARATOR ',') as norm_list,
                           GROUP_CONCAT(rs2 SEPARATOR ' | ') as nama_list
                    FROM rs15 
                    WHERE rs2 IS NOT NULL AND rs2 != '' AND rs16 IS NOT NULL AND rs16 != '0000-00-00'
                      AND namaibu IS NOT NULL AND namaibu != '' 
                      AND TRIM(LOWER(namaibu)) NOT IN ('-', '--', 'tidak tahu', 'tidak ada', 'unknown', 'ibu', 'alm', 'almarhumah')
                    GROUP BY TRIM(LOWER(rs2)), rs16, TRIM(LOWER(namaibu)) 
                    HAVING COUNT(*) > 1 " . (!empty($q) ? "AND (nama_list LIKE '%$q%' OR norm_list LIKE '%$q%')" : "") . "
                ) as sub
            ";
            $resCount = DB::select($sqlCount);
            $totalGroups = $resCount[0]->total ?? 0;

            $sqlData = "
                SELECT CONCAT(TRIM(LOWER(rs2)), ' # Ibu: ', TRIM(LOWER(namaibu))) as key_identifier,
                       'nama_ibu' as key_type,
                       COUNT(*) as total_member,
                       GROUP_CONCAT(rs1 ORDER BY rs1 ASC SEPARATOR ',') as norm_list,
                       GROUP_CONCAT(rs2 ORDER BY rs1 ASC SEPARATOR ' | ') as nama_list,
                       MAX(updated_at) as latest_update
                FROM rs15 
                WHERE rs2 IS NOT NULL AND rs2 != '' AND rs16 IS NOT NULL AND rs16 != '0000-00-00'
                  AND namaibu IS NOT NULL AND namaibu != '' 
                  AND TRIM(LOWER(namaibu)) NOT IN ('-', '--', 'tidak tahu', 'tidak ada', 'unknown', 'ibu', 'alm', 'almarhumah')
                GROUP BY TRIM(LOWER(rs2)), rs16, TRIM(LOWER(namaibu)) 
                HAVING COUNT(*) > 1 " . (!empty($q) ? "AND (nama_list LIKE '%$q%' OR norm_list LIKE '%$q%')" : "") . "
                ORDER BY total_member DESC
                LIMIT $perPage OFFSET $offset
            ";
            $groups = DB::select($sqlData);
        }

        // Kumpulkan semua No. RM dari grup yang diambil
        $allNorms = [];
        foreach ($groups as $g) {
            $norms = explode(',', $g->norm_list);
            foreach ($norms as $n) {
                $n = trim($n);
                if (!empty($n)) {
                    $allNorms[] = $n;
                }
            }
        }
        $allNorms = array_unique($allNorms);

        // Ambil Data Master Pasien Lengkap
        $pasiens = DB::table('rs15')
            ->select(
                'rs1 as norm',
                'rs2 as nama',
                'rs3 as sapaan',
                'rs4 as alamat',
                'rs5 as kelurahan',
                'rs6 as kecamatan',
                'rs11 as kabupaten',
                'rs16 as tgl_lahir',
                'rs17 as kelamin',
                'rs46 as noka_bpjs',
                'rs49 as nik',
                'namaibu',
                'satset_uuid',
                'updated_at'
            )
            ->whereIn('rs1', $allNorms)
            ->get()
            ->keyBy('norm');

        // Ambil Riwayat Keaktifan Kunjungan (Rajal / IGD / HD)
        $kunjunganRajal = DB::table('rs17')
            ->select(
                'rs2 as norm',
                DB::raw('count(*) as total_kunjungan'),
                DB::raw('MAX(rs3) as kunjungan_terakhir')
            )
            ->whereIn('rs2', $allNorms)
            ->groupBy('rs2')
            ->get()
            ->keyBy('norm');

        // Ambil Riwayat Keaktifan Kunjungan Ranap
        $kunjunganRanap = DB::table('rs23')
            ->select(
                'rs2 as norm',
                DB::raw('count(*) as total_ranap'),
                DB::raw('MAX(rs3) as ranap_terakhir')
            )
            ->whereIn('rs2', $allNorms)
            ->groupBy('rs2')
            ->get()
            ->keyBy('norm');

        // Susun struktur data grup beserta item pasiennya
        $formattedGroups = [];
        foreach ($groups as $g) {
            $norms = explode(',', $g->norm_list);
            $members = [];

            foreach ($norms as $n) {
                $n = trim($n);
                if (isset($pasiens[$n])) {
                    $p = (array) $pasiens[$n];
                    $rajal = $kunjunganRajal->get($n);
                    $ranap = $kunjunganRanap->get($n);

                    $totRajal = $rajal ? (int) $rajal->total_kunjungan : 0;
                    $totRanap = $ranap ? (int) $ranap->total_ranap : 0;
                    $lastRajal = $rajal ? $rajal->kunjungan_terakhir : null;
                    $lastRanap = $ranap ? $ranap->ranap_terakhir : null;

                    $lastVisit = $lastRajal;
                    if ($lastRanap && (!$lastVisit || $lastRanap > $lastVisit)) {
                        $lastVisit = $lastRanap;
                    }

                    $p['total_kunjungan'] = $totRajal + $totRanap;
                    $p['kunjungan_terakhir'] = $lastVisit;
                    $p['has_satset'] = !empty($p['satset_uuid']);

                    $members[] = $p;
                }
            }

            // Urutkan member: yang paling banyak kunjungan / paling baru berobat berada di paling atas
            usort($members, function ($a, $b) {
                if ($a['total_kunjungan'] === $b['total_kunjungan']) {
                    return strcmp($b['kunjungan_terakhir'] ?? '', $a['kunjungan_terakhir'] ?? '');
                }
                return $b['total_kunjungan'] <=> $a['total_kunjungan'];
            });

            // Deteksi saran No. RM Utama
            $suggestedMaster = count($members) > 0 ? $members[0]['norm'] : null;

            $formattedGroups[] = [
                'group_id' => md5($g->key_identifier . '_' . $kategori),
                'kategori' => $kategori,
                'key_identifier' => $g->key_identifier,
                'total_members' => count($members),
                'suggested_master_norm' => $suggestedMaster,
                'members' => $members
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => (int) $totalGroups,
                'last_page' => ceil($totalGroups / max(1, $perPage)),
                'kategori' => $kategori,
                'groups' => $formattedGroups
            ]
        ]);
    }

    /**
     * Update Status Verifikasi / Catatan Audit Pasien Ganda
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $groupId = $request->input('group_id');
        $status = $request->input('status', 'DIPERIKSA');
        $catatan = $request->input('catatan');
        $user = $request->input('user', 'Administrator');

        // Simpan log aksi audit di SatsetAuditDataLog
        $auditLog = SatsetAuditDataLog::create([
            'kategori' => 'AUDIT_PASIEN_GANDA',
            'unit' => 'rekam_medis',
            'ref_id' => $groupId,
            'nama' => $request->input('nama', '-'),
            'keterangan' => $catatan ?: "Verifikasi status pasien ganda ($status)",
            'status_perbaikan' => $status,
            'user_perbaikan' => $user,
            'tgl_perbaikan' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status review berhasil dicatat',
            'data' => $auditLog
        ]);
    }
}
