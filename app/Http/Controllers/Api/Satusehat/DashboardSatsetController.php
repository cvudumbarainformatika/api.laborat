<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Http\Controllers\Controller;
use App\Helpers\Satsets\PostKunjunganRajalHelper;
use App\Helpers\Satsets\PostKunjunganRanapHelper;
use App\Helpers\Satsets\PostKunjunganIgdHelper;
use App\Models\Satset\Satset;
use App\Models\Satset\SatsetErrorRespon;
use App\Models\Satset\SatsetAuditDataLog;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardSatsetController extends Controller
{
    private static function cacheRemember(string $key, int $ttlSeconds, \Closure $callback)
    {
        try {
            return Cache::remember($key, $ttlSeconds, $callback);
        } catch (\Throwable $e) {
            return $callback();
        }
    }

    /**
     * Ringkasan / Summary statistik pengiriman SatuSehat per modul
     */
    public function summary(Request $request): JsonResponse
    {
        $tglAwal = $request->input('tgl_awal', Carbon::today()->toDateString());
        $tglAkhir = $request->input('tgl_akhir', Carbon::today()->toDateString());

        $cacheKey = "satset_dash_summary_{$tglAwal}_{$tglAkhir}";
        $data = self::cacheRemember($cacheKey, 30, function () use ($tglAwal, $tglAkhir) {
            $bukanPoli = ['POL014', 'PEN005', 'PEN004'];

            // Rajal
            $totalRajal = DB::table('rs17')->whereNotIn('rs8', $bukanPoli)
                ->where('rs19', '1')
                ->whereBetween('rs3', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->count();

            // Ranap
            $totalRanap = DB::table('rs23')
                ->whereBetween('rs3', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->count();

            // IGD
            $totalIgd = DB::table('rs17')->where('rs8', 'POL014')
                ->where('rs19', '1')
                ->whereBetween('rs3', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->count();

            // HD (Hemodialisa)
            $totalHd = DB::table('rs17')->where('rs8', 'PEN005')
                ->where('rs19', '1')
                ->whereBetween('rs3', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->count();

            // Terkirim Sukses (Tabel satsets)
            $terkirimRajal = DB::table('satsets')->where('jenis', 'rajal')
                ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->count();

            $terkirimRanap = DB::table('satsets')->where('jenis', 'ranap')
                ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->count();

            $terkirimIgd = DB::table('satsets')->where('jenis', 'igd')
                ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->count();

            $terkirimHd = DB::table('satsets')->where('jenis', 'hd')
                ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->count();

            // Error Respon (Tabel satset_error_respon)
            $errorRajal = DB::table('satset_error_respon')->where('jenis', 'rajal')
                ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->count();

            $errorRanap = DB::table('satset_error_respon')->where('jenis', 'ranap')
                ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->count();

            $errorIgd = DB::table('satset_error_respon')->where('jenis', 'igd')
                ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->count();

            $errorHd = DB::table('satset_error_respon')->where('jenis', 'hd')
                ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->count();

            $totalKunjungan = $totalRajal + $totalRanap + $totalIgd + $totalHd;
            $totalTerkirim = $terkirimRajal + $terkirimRanap + $terkirimIgd + $terkirimHd;
            $totalError = $errorRajal + $errorRanap + $errorIgd + $errorHd;
            $complianceRate = $totalKunjungan > 0 ? round(($totalTerkirim / $totalKunjungan) * 100, 2) : 0;

            return [
                'periode' => [
                    'tgl_awal' => $tglAwal,
                    'tgl_akhir' => $tglAkhir
                ],
                'summary' => [
                    'total_kunjungan' => $totalKunjungan,
                    'total_terkirim' => $totalTerkirim,
                    'total_error' => $totalError,
                    'compliance_rate' => $complianceRate . '%',
                ],
                'detail_modul' => [
                    'rajal' => [
                        'total_kunjungan' => $totalRajal,
                        'terkirim' => $terkirimRajal,
                        'error' => $errorRajal,
                        'rate' => $totalRajal > 0 ? round(($terkirimRajal / $totalRajal) * 100, 2) . '%' : '0%'
                    ],
                    'ranap' => [
                        'total_kunjungan' => $totalRanap,
                        'terkirim' => $terkirimRanap,
                        'error' => $errorRanap,
                        'rate' => $totalRanap > 0 ? round(($terkirimRanap / $totalRanap) * 100, 2) . '%' : '0%'
                    ],
                    'igd' => [
                        'total_kunjungan' => $totalIgd,
                        'terkirim' => $terkirimIgd,
                        'error' => $errorIgd,
                        'rate' => $totalIgd > 0 ? round(($terkirimIgd / $totalIgd) * 100, 2) . '%' : '0%'
                    ],
                    'hd' => [
                        'total_kunjungan' => $totalHd,
                        'terkirim' => $terkirimHd,
                        'error' => $errorHd,
                        'rate' => $totalHd > 0 ? round(($terkirimHd / $totalHd) * 100, 2) . '%' : '0%'
                    ],
                ]
            ];
        });

        return response()->json(array_merge(['status' => 'success'], $data));
    }

    /**
     * Analisis Kategori & Pesan Error Terbanyak
     */
    public function errorStats(Request $request): JsonResponse
    {
        $tglAwal = $request->input('tgl_awal', Carbon::today()->toDateString());
        $tglAkhir = $request->input('tgl_akhir', Carbon::today()->toDateString());
        $jenis = $request->input('jenis', 'all');

        $cacheKey = "satset_dash_error_stats_{$tglAwal}_{$tglAkhir}_{$jenis}";
        $data = self::cacheRemember($cacheKey, 30, function () use ($tglAwal, $tglAkhir, $jenis) {
            $query = DB::table('satset_error_respon')->select(
                DB::raw("TRIM(SUBSTRING_INDEX(COALESCE(NULLIF(error_summary, ''), 'Respon Error Umum / Validasi Payload'), ';', 1)) as pesan_error"),
                DB::raw('count(*) as total')
            )
                ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);

            if ($jenis !== 'all') {
                $query->where('jenis', $jenis);
            }

            $topErrors = $query->groupBy('pesan_error')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    $human = self::humanizeErrorMessage($item->pesan_error);
                    return [
                        'pesan_error' => $human['title'],
                        'keterangan' => $human['description'],
                        'raw_error' => $item->pesan_error,
                        'total' => (int) $item->total,
                    ];
                });

            $totalErrorPeriod = DB::table('satset_error_respon')
                ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->when($jenis !== 'all', function ($q) use ($jenis) {
                    return $q->where('jenis', $jenis);
                })
                ->count();

            return [
                'periode' => [
                    'tgl_awal' => $tglAwal,
                    'tgl_akhir' => $tglAkhir,
                    'jenis' => $jenis
                ],
                'total_error_periode' => $totalErrorPeriod,
                'top_errors' => $topErrors
            ];
        });

        return response()->json(array_merge(['status' => 'success'], $data));
    }

    /**
     * Menerjemahkan pesan error teknis SatuSehat / FHIR ke Bahasa Indonesia yang mudah dipahami
     */
    public static function humanizeErrorMessage(?string $raw): array
    {
        $raw = trim($raw ?? '');
        if (empty($raw) || $raw === 'Respon Error Umum / Validasi Payload') {
            return [
                'title' => 'Format Payload / Struktur Data FHIR Tidak Sesuai',
                'description' => 'Ada ketidaksesuaian struktur JSON/elemen wajib FHIR dengan spesifikasi Kemenkes.',
                'raw' => $raw ?: 'Respon Error Umum'
            ];
        }

        if (stripos($raw, 'Encounter.diagnosis') !== false) {
            return [
                'title' => 'Diagnosa Utama / ICD-10 Belum Diisi',
                'description' => 'Kunjungan pasien belum memiliki entri diagnosa primer (ICD-10) di SIMRS.',
                'raw' => $raw
            ];
        }

        if (stripos($raw, 'icd-10') !== false) {
            return [
                'title' => 'Kode Diagnosa (ICD-10) Tidak Ditemukan / Tidak Valid',
                'description' => 'Kode ICD-10 yang dikirim tidak terdaftar atau belum sesuai standar terminologi Kemenkes.',
                'raw' => $raw
            ];
        }

        if (stripos($raw, 'Patient/') !== false || stripos($raw, 'citizen') !== false || stripos($raw, 'NIK') !== false) {
            return [
                'title' => 'NIK / IHS Pasien Tidak Ditemukan di SatuSehat',
                'description' => 'NIK pasien belum terdaftar atau tidak sinkron dengan Dukcapil / SatuSehat.',
                'raw' => $raw
            ];
        }

        if (stripos($raw, 'Practitioner') !== false || stripos($raw, 'Tenaga Kesehatan') !== false || stripos($raw, 'SDMK') !== false) {
            return [
                'title' => 'IHS Dokter / Nakes Belum Terdaftar di SatuSehat',
                'description' => 'IHS Number dokter penanggung jawab belum terdaftar atau belum sesuai data SISDMK.',
                'raw' => $raw
            ];
        }

        if (stripos($raw, 'Location') !== false || stripos($raw, 'locationServiceClass') !== false || stripos($raw, 'locationUpgradeClass') !== false) {
            return [
                'title' => 'ID Lokasi / Kelas Kamar Belum Sesuai Master Ruangan',
                'description' => 'Mapping Location UUID atau kelas rawat SatuSehat untuk unit/ruangan belum lengkap.',
                'raw' => $raw
            ];
        }

        if (stripos($raw, 'dicom') !== false || stripos($raw, 'DCM') !== false) {
            return [
                'title' => 'Kode Modalitas Radiologi (DICOM) Tidak Sesuai',
                'description' => 'Modalitas pemeriksaan penunjang (CR, CT, USG, dll.) belum terisi sesuai standar DCM.',
                'raw' => $raw
            ];
        }

        if (stripos($raw, 'snomed') !== false) {
            return [
                'title' => 'Kode Tindakan (SNOMED-CT) Tidak Valid',
                'description' => 'Kode tindakan medis SNOMED-CT tidak ditemukan dalam dictionary Kemenkes.',
                'raw' => $raw
            ];
        }

        if (stripos($raw, 'duplicate') !== false) {
            return [
                'title' => 'Data Resource Sudah Pernah Terkirim (Duplikasi)',
                'description' => 'Resource ini sudah tercatat sebelumnya di server SatuSehat Kemenkes.',
                'raw' => $raw
            ];
        }

        if (stripos($raw, 'Access Token') !== false || stripos($raw, 'Unauthorized') !== false) {
            return [
                'title' => 'Token Otentikasi SatuSehat Expired / Gagal',
                'description' => 'Kredensial API Client ID / Secret SatuSehat perlu diperbarui.',
                'raw' => $raw
            ];
        }

        if (stripos($raw, 'reference_not_found') !== false) {
            return [
                'title' => 'Referensi ID Resource Induk Belum Ada',
                'description' => 'Resource utama (misal Encounter ID) belum berhasil terbuat sebelum data ini dikirim.',
                'raw' => $raw
            ];
        }

        if (stripos($raw, 'failed') !== false || stripos($raw, 'Invalid query') !== false) {
            return [
                'title' => 'Permintaan / Koneksi API SatuSehat Gagal',
                'description' => 'Server SatuSehat Kemenkes mengalami timeout atau query tidak valid.',
                'raw' => $raw
            ];
        }

        return [
            'title' => $raw,
            'description' => 'Kendala respon validasi SatuSehat Kemenkes.',
            'raw' => $raw
        ];
    }

    /**
     * Daftar Riwayat Kunjungan & Status Pengiriman SatuSehat
     */
    public function listKunjungan(Request $request): JsonResponse
    {
        $tglAwal = $request->input('tgl_awal', Carbon::today()->toDateString());
        $tglAkhir = $request->input('tgl_akhir', Carbon::today()->toDateString());
        $jenis = $request->input('jenis', 'all'); // all, rajal, ranap, igd
        $q = $request->input('q', '');
        $perPage = (int) $request->input('per_page', 20);

        if ($jenis === 'ranap') {
            $ranapQuery = Kunjunganranap::select(
                'rs23.rs1 as noreg',
                'rs23.rs2 as norm',
                'rs23.rs3 as tgl_kunjungan',
                'rs23.rs4 as tgl_pulang',
                'rs15.rs2 as nama_pasien',
                'rs15.rs49 as nik',
                'rs24.rs2 as unit_layanan',
                'rs21.rs2 as dokter_dpjp',
                DB::raw("'ranap' as jenis")
            )
                ->leftJoin('rs15', 'rs15.rs1', '=', 'rs23.rs2')
                ->leftJoin('rs24', 'rs24.rs1', '=', 'rs23.rs5')
                ->leftJoin('rs21', 'rs21.rs1', '=', 'rs23.rs10')
                ->whereBetween('rs23.rs3', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);

            if (!empty($q)) {
                $ranapQuery->where(function ($sub) use ($q) {
                    $sub->where('rs23.rs1', 'like', "%$q%")
                        ->orWhere('rs23.rs2', 'like', "%$q%")
                        ->orWhere('rs15.rs2', 'like', "%$q%")
                        ->orWhere('rs15.rs49', 'like', "%$q%");
                });
            }

            $list = $ranapQuery->with([
                'satset' => function ($q) {
                    $q->select('id', 'uuid', 'resource', 'response', 'created_at');
                },
                'satset_error' => function ($q) {
                    $q->select('id', 'uuid', 'error_summary', 'created_at');
                }
            ])
                ->orderBy('rs23.rs3', 'desc')
                ->paginate($perPage);

            $list->getCollection()->transform(function ($item) {
                return $this->formatKunjunganItem($item);
            });
        } else {
            $rajalQuery = KunjunganPoli::select(
                'rs17.rs1 as noreg',
                'rs17.rs2 as norm',
                'rs17.rs3 as tgl_kunjungan',
                'rs15.rs2 as nama_pasien',
                'rs15.rs49 as nik',
                'rs19.rs2 as unit_layanan',
                'rs21.rs2 as dokter_dpjp',
                DB::raw("IF(rs17.rs8 = 'POL014', 'igd', IF(rs17.rs8 = 'PEN005', 'hd', 'rajal')) as jenis")
            )
                ->leftJoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
                ->leftJoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
                ->leftJoin('rs21', 'rs21.rs1', '=', 'rs17.rs9')
                ->where('rs17.rs19', '1')
                ->whereBetween('rs17.rs3', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);

            if ($jenis === 'igd') {
                $rajalQuery->where('rs17.rs8', '=', 'POL014');
            } elseif ($jenis === 'hd') {
                $rajalQuery->where('rs17.rs8', '=', 'PEN005');
            } elseif ($jenis === 'rajal') {
                $rajalQuery->whereNotIn('rs17.rs8', ['POL014', 'PEN005', 'PEN004']);
            } else {
                $rajalQuery->whereNotIn('rs17.rs8', ['PEN004']);
            }

            if (!empty($q)) {
                $rajalQuery->where(function ($sub) use ($q) {
                    $sub->where('rs17.rs1', 'like', "%$q%")
                        ->orWhere('rs17.rs2', 'like', "%$q%")
                        ->orWhere('rs15.rs2', 'like', "%$q%")
                        ->orWhere('rs15.rs49', 'like', "%$q%");
                });
            }

            $list = $rajalQuery->with([
                'satset' => function ($q) {
                    $q->select('id', 'uuid', 'resource', 'response', 'created_at');
                },
                'satset_error' => function ($q) {
                    $q->select('id', 'uuid', 'error_summary', 'created_at');
                }
            ])
                ->orderBy('rs17.rs3', 'desc')
                ->paginate($perPage);

            $list->getCollection()->transform(function ($item) {
                return $this->formatKunjunganItem($item);
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $list
        ]);
    }

    /**
     * Daftar Laporan Pengiriman Error / Gagal SatuSehat Lengkap
     */
    public function listError(Request $request): JsonResponse
    {
        $tglAwal = $request->input('tgl_awal', Carbon::today()->toDateString());
        $tglAkhir = $request->input('tgl_akhir', Carbon::today()->toDateString());
        $jenis = $request->input('jenis', 'all'); // all, rajal, ranap, igd
        $q = $request->input('q', '');
        $perPage = (int) $request->input('per_page', 20);

        $query = SatsetErrorRespon::whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);

        if ($jenis !== 'all') {
            $query->where('jenis', $jenis);
        }

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('uuid', 'like', "%$q%")
                    ->orWhere('error_summary', 'like', "%$q%");
            });
        }

        $list = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Ambil noreg-noreg untuk lookup data pasien di SIMRS dan status satsets sukses
        $noregList = collect($list->items())->pluck('uuid')->filter()->unique()->toArray();

        // Cek apakah ada yang sudah sukses terkirim (Resolved)
        $suksesList = Satset::whereIn('uuid', $noregList)->pluck('uuid')->toArray();
        $suksesSet = array_flip($suksesList);

        // Lookup data pasien Rajal / IGD (rs17)
        $rajalData = KunjunganPoli::select(
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs15.rs2 as nama_pasien',
            'rs15.rs49 as nik',
            'rs19.rs2 as unit_layanan',
            'rs21.rs2 as dokter_dpjp'
        )
            ->leftJoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
            ->leftJoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
            ->leftJoin('rs21', 'rs21.rs1', '=', 'rs17.rs9')
            ->whereIn('rs17.rs1', $noregList)
            ->get()
            ->keyBy('noreg');

        // Lookup data pasien Ranap (rs23)
        $ranapData = Kunjunganranap::select(
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs3 as tgl_kunjungan',
            'rs15.rs2 as nama_pasien',
            'rs15.rs49 as nik',
            'rs24.rs2 as unit_layanan',
            'rs21.rs2 as dokter_dpjp'
        )
            ->leftJoin('rs15', 'rs15.rs1', '=', 'rs23.rs2')
            ->leftJoin('rs24', 'rs24.rs1', '=', 'rs23.rs5')
            ->leftJoin('rs21', 'rs21.rs1', '=', 'rs23.rs10')
            ->whereIn('rs23.rs1', $noregList)
            ->get()
            ->keyBy('noreg');

        $list->getCollection()->transform(function ($item) use ($suksesSet, $rajalData, $ranapData) {
            $pasien = $rajalData[$item->uuid] ?? ($ranapData[$item->uuid] ?? null);
            $isResolved = isset($suksesSet[$item->uuid]);

            // Ekstrak detail issues jika ada
            $issues = [];
            $resp = is_array($item->response) ? $item->response : json_decode($item->response, true);
            if (isset($resp['issue']) && is_array($resp['issue'])) {
                foreach ($resp['issue'] as $iss) {
                    $issues[] = [
                        'severity' => $iss['severity'] ?? 'error',
                        'code' => $iss['code'] ?? null,
                        'message' => $iss['details']['text'] ?? ($iss['diagnostics'] ?? 'Error tidak terdefinisi'),
                        'expression' => $iss['expression'] ?? []
                    ];
                }
            }

            return [
                'id' => $item->id,
                'noreg' => $item->uuid,
                'jenis' => $item->jenis,
                'waktu_error' => $item->created_at,
                'status_terkini' => $isResolved ? 'RESOLVED (Terkirim Ulang Sukses)' : 'UNRESOLVED (Perlu Tindakan)',
                'is_resolved' => $isResolved,
                'error_summary' => $item->error_summary ?: 'Respon Error Umum / Validasi Payload',
                'issues' => $issues,
                'pasien' => $pasien ? [
                    'norm' => $pasien->norm,
                    'nama_pasien' => $pasien->nama_pasien,
                    'nik' => $pasien->nik,
                    'unit_layanan' => $pasien->unit_layanan,
                    'dokter_dpjp' => $pasien->dokter_dpjp,
                    'tgl_kunjungan' => $pasien->tgl_kunjungan
                ] : null
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $list
        ]);
    }

    /**
     * Statistik Detail Jumlah Resource FHIR yang Berhasil Terkirim
     */
    public function resourceStats(Request $request): JsonResponse
    {
        $tglAwal = $request->input('tgl_awal', Carbon::today()->toDateString());
        $tglAkhir = $request->input('tgl_akhir', Carbon::today()->toDateString());
        $jenis = $request->input('jenis', 'all');

        $cacheKey = "satset_dash_resource_stats_{$tglAwal}_{$tglAkhir}_{$jenis}";
        $data = self::cacheRemember($cacheKey, 30, function () use ($tglAwal, $tglAkhir, $jenis) {
            $query = DB::table('satsets')
                ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->whereNotNull('response');

            if ($jenis !== 'all') {
                $query->where('jenis', $jenis);
            }

            $records = $query->select('response')->get();

            $standardGrid = [
                'Encounter' => 0,
                'Condition' => 0,
                'Observation' => 0,
                'Procedure' => 0,
                'Composition' => 0,
                'Medication' => 0,
                'MedicationRequest' => 0,
                'MedicationDispense' => 0,
                'AllergyIntolerance' => 0,
                'ImagingStudy' => 0,
                'ServiceRequest' => 0,
                'ClinicalImpression' => 0,
                'Immunization' => 0,
                'QuestionnaireResponse' => 0,
                'MedicationStatement' => 0,
                'CarePlan' => 0,
                'Specimen' => 0,
                'DiagnosticReport' => 0,
                'EpisodeOfCare' => 0,
            ];

            $resourceCounts = [];
            $totalResourceCount = 0;

            foreach ($records as $rec) {
                if (empty($rec->response)) continue;
                $parsed = $this->parseResourceDetails($rec->response);
                foreach ($parsed['breakdown'] as $resType => $count) {
                    if (!isset($resourceCounts[$resType])) {
                        $resourceCounts[$resType] = 0;
                    }
                    $resourceCounts[$resType] += $count;
                    $totalResourceCount += $count;

                    if (isset($standardGrid[$resType])) {
                        $standardGrid[$resType] += $count;
                    }
                }
            }

            arsort($resourceCounts);

            $breakdownList = [];
            foreach ($resourceCounts as $resType => $count) {
                $breakdownList[] = [
                    'resource_type' => $resType,
                    'total_terkirim' => $count,
                    'persentase' => $totalResourceCount > 0 ? round(($count / $totalResourceCount) * 100, 2) . '%' : '0%'
                ];
            }

            return [
                'periode' => [
                    'tgl_awal' => $tglAwal,
                    'tgl_akhir' => $tglAkhir,
                    'jenis' => $jenis
                ],
                'last_updated' => Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB',
                'total_transaksi_bundle' => $records->count(),
                'total_resource_terkirim' => $totalResourceCount,
                'card_grid' => $standardGrid,
                'detail_resource' => $breakdownList
            ];
        });

        return response()->json(array_merge(['status' => 'success'], $data));
    }

    /**
     * Detail Satu Kunjungan (Payload, Respon SatuSehat, dan List Resource)
     */
    public function detailKunjungan(Request $request): JsonResponse
    {
        $noreg = $request->input('noreg');
        if (empty($noreg)) {
            return response()->json(['status' => 'failed', 'message' => 'Parameter noreg wajib diisi'], 400);
        }

        $satset = Satset::where('uuid', $noreg)->orderBy('id', 'desc')->first();
        $satsetError = SatsetErrorRespon::where('uuid', $noreg)->orderBy('id', 'desc')->first();

        $resourceDetails = null;
        if ($satset && !empty($satset->response)) {
            $resourceDetails = $this->parseResourceDetails($satset->response);
        }

        return response()->json([
            'status' => 'success',
            'noreg' => $noreg,
            'is_terkirim' => $satset ? true : false,
            'is_error' => $satsetError ? true : false,
            'satset' => $satset ? [
                'id' => $satset->id,
                'jenis' => $satset->jenis,
                'created_at' => $satset->created_at,
                'total_resource' => $resourceDetails['total'] ?? 0,
                'summary_resource' => $resourceDetails['summary'] ?? [],
                'list_resource' => $resourceDetails['items'] ?? [],
                'response_raw' => is_array($satset->response) ? $satset->response : json_decode($satset->response, true)
            ] : null,
            'satset_error' => $satsetError ? [
                'id' => $satsetError->id,
                'error_summary' => $satsetError->error_summary,
                'created_at' => $satsetError->created_at,
                'response_raw' => is_array($satsetError->response) ? $satsetError->response : json_decode($satsetError->response, true)
            ] : null
        ]);
    }

    /**
     * Helper Parser Response SatuSehat FHIR
     */
    private function parseResourceDetails($response): array
    {
        if (empty($response)) {
            return ['total' => 0, 'breakdown' => [], 'summary' => [], 'items' => []];
        }

        $data = is_array($response) ? $response : json_decode($response, true);
        if (!$data || !isset($data['entry']) || !is_array($data['entry'])) {
            return ['total' => 0, 'breakdown' => [], 'summary' => [], 'items' => []];
        }

        $breakdown = [];
        $items = [];
        $total = 0;

        foreach ($data['entry'] as $entry) {
            $res = $entry['response'] ?? [];
            $resType = $res['resourceType'] ?? null;
            $resId = $res['resourceID'] ?? null;
            $status = $res['status'] ?? null;

            if ($resType) {
                $total++;
                if (!isset($breakdown[$resType])) {
                    $breakdown[$resType] = 0;
                }
                $breakdown[$resType]++;

                $items[] = [
                    'resource_type' => $resType,
                    'resource_id' => $resId,
                    'status' => $status,
                    'location' => $res['location'] ?? null
                ];
            }
        }

        $summary = [];
        foreach ($breakdown as $type => $count) {
            $summary[] = $count > 1 ? "$type ($count)" : $type;
        }

        return [
            'total' => $total,
            'breakdown' => $breakdown,
            'summary' => $summary,
            'items' => $items
        ];
    }

    /**
     * Format item pada list kunjungan
     */
    private function formatKunjunganItem($item)
    {
        $resParsed = null;
        if ($item->satset && !empty($item->satset->response)) {
            $parsed = $this->parseResourceDetails($item->satset->response);
            $resParsed = [
                'total_resources' => $parsed['total'],
                'resources_summary' => $parsed['summary'],
                'created_at' => $item->satset->created_at
            ];
        }

        $item->satset_terkirim = $resParsed;
        unset($item->satset); // hilangkan payload raw yang berat dari list pagination
        return $item;
    }

    /**
     * Kirim Ulang (Retry) SatuSehat per No. Registrasi
     */
    public function retry(Request $request): JsonResponse
    {
        $noreg = $request->input('noreg');
        if (empty($noreg)) {
            return response()->json(['status' => 'failed', 'message' => 'Parameter noreg wajib diisi'], 400);
        }

        // Tentukan jenis dari parameter atau akhiran noreg
        $jenis = $request->input('jenis');
        if (!$jenis) {
            if (Str::endsWith($noreg, ['/I', '/i'])) {
                $jenis = 'ranap';
            } elseif (Str::endsWith($noreg, ['/X', '/x'])) {
                $jenis = 'igd';
            } else {
                $jenis = 'rajal';
            }
        }

        if ($jenis === 'ranap') {
            $res = PostKunjunganRanapHelper::cobaRanap($noreg);
        } elseif ($jenis === 'igd') {
            $res = PostKunjunganIgdHelper::cobaIgd($noreg);
        } elseif ($jenis === 'hd') {
            $res = \App\Helpers\Satsets\PostKunjunganHDHerlper::cobarajal($noreg);
        } else {
            $res = PostKunjunganRajalHelper::cobaRajal($noreg);
        }

        return response()->json([
            'status' => 'success',
            'noreg' => $noreg,
            'jenis' => $jenis,
            'result' => $res
        ]);
    }

    /**
     * Statistik Ringkasan Audit Log
     */
    public function auditStats(Request $request): JsonResponse
    {
        $tglAwal = $request->input('tgl_awal', Carbon::today()->subDays(30)->toDateString());
        $tglAkhir = $request->input('tgl_akhir', Carbon::today()->toDateString());

        $query = SatsetAuditDataLog::whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);

        $totalTemuan = (clone $query)->count();
        $totalPending = (clone $query)->where('status_perbaikan', 'PENDING')->count();
        $totalDiperbaiki = (clone $query)->where('status_perbaikan', 'DIPERBAIKI')->count();
        $totalDiabaikan = (clone $query)->where('status_perbaikan', 'DIABAIKAN')->count();

        $pasienMismatch = (clone $query)->where('kategori', 'PASIEN_MISMATCH_BPJS')->count();
        $pegawaiNikKosong = (clone $query)->where('kategori', 'PEGAWAI_NIK_KOSONG')->count();
        $pegawaiUnregistered = (clone $query)->where('kategori', 'PEGAWAI_UNREGISTERED_SATSET')->count();

        return response()->json([
            'status' => 'success',
            'stats' => [
                'total_temuan' => $totalTemuan,
                'total_pending' => $totalPending,
                'total_diperbaiki' => $totalDiperbaiki,
                'total_diabaikan' => $totalDiabaikan,
                'pasien_mismatch' => $pasienMismatch,
                'pegawai_nik_kosong' => $pegawaiNikKosong,
                'pegawai_unregistered' => $pegawaiUnregistered,
            ]
        ]);
    }

    /**
     * Daftar Audit Data Log dengan Filter & Pagination
     */
    public function auditList(Request $request): JsonResponse
    {
        $tglAwal = $request->input('tgl_awal', Carbon::today()->subDays(30)->toDateString());
        $tglAkhir = $request->input('tgl_akhir', Carbon::today()->toDateString());
        $kategori = $request->input('kategori');
        $unit = $request->input('unit');
        $status = $request->input('status');
        $q = $request->input('q');
        $perPage = (int) $request->input('per_page', 20);

        $query = SatsetAuditDataLog::query();

        if (!empty($tglAwal) && !empty($tglAkhir)) {
            $query->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
        }

        if (!empty($kategori) && $kategori !== 'all') {
            $query->where('kategori', $kategori);
        }

        if (!empty($unit) && $unit !== 'all') {
            $query->where('unit', $unit);
        }

        if (!empty($status) && $status !== 'all') {
            $query->where('status_perbaikan', $status);
        }

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('ref_id', 'like', "%{$q}%")
                    ->orWhere('noreg', 'like', "%{$q}%")
                    ->orWhere('nik_simrs', 'like', "%{$q}%")
                    ->orWhere('nik_valid', 'like', "%{$q}%")
                    ->orWhere('keterangan', 'like', "%{$q}%");
            });
        }

        $list = $query->orderBy('created_at', 'DESC')->paginate($perPage);

        // Attach Status Pengiriman SatuSehat & IHS UUID
        $noregs = collect($list->items())->pluck('noreg')->filter()->unique()->values()->all();
        $satsets = !empty($noregs) ? Satset::whereIn('uuid', $noregs)->get()->keyBy('uuid') : collect();
        $satsetErrors = !empty($noregs) ? SatsetErrorRespon::whereIn('uuid', $noregs)->get()->keyBy('uuid') : collect();

        $norms = collect($list->items())->where('kategori', 'PASIEN_MISMATCH_BPJS')->pluck('ref_id')->filter()->unique()->values()->all();
        $pasiens = !empty($norms) ? DB::table('rs15')->whereIn('rs1', $norms)->get(['rs1', 'satset_uuid'])->keyBy('rs1') : collect();

        foreach ($list->items() as $item) {
            $noreg = $item->noreg;
            $refId = $item->ref_id;
            $isSent = !empty($noreg) && isset($satsets[$noreg]);
            $isError = !empty($noreg) && isset($satsetErrors[$noreg]);

            $satsetUuid = isset($pasiens[$refId]) ? $pasiens[$refId]->satset_uuid : null;

            $item->satset_terkirim = $isSent;
            $item->satset_error = $isError && !$isSent;
            $item->satset_id = $isSent ? $satsets[$noreg]->id : null;
            $item->satset_ihs_uuid = $satsetUuid;
            $item->satset_waktu_kirim = $isSent ? $satsets[$noreg]->created_at : null;
        }

        return response()->json([
            'status' => 'success',
            'data' => $list
        ]);
    }

    /**
     * Update Status Perbaikan Audit Log
     */
    public function updateAuditStatus(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $status = $request->input('status'); // 'PENDING', 'DIPERBAIKI', 'DIABAIKAN'
        $user = $request->input('user', 'Administrator');

        if (!$id || !$status || !in_array($status, ['PENDING', 'DIPERBAIKI', 'DIABAIKAN'])) {
            return response()->json(['status' => 'failed', 'message' => 'Data tidak valid'], 400);
        }

        $audit = SatsetAuditDataLog::find($id);
        if (!$audit) {
            return response()->json(['status' => 'failed', 'message' => 'Data log audit tidak ditemukan'], 404);
        }

        $audit->update([
            'status_perbaikan' => $status,
            'user_perbaikan' => $user,
            'tgl_perbaikan' => ($status !== 'PENDING') ? now() : null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status berhasil diperbarui',
            'data' => $audit
        ]);
    }
}
