<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Http\Controllers\Controller;
use App\Helpers\Satsets\PostKunjunganRajalHelper;
use App\Helpers\Satsets\PostKunjunganRanapHelper;
use App\Helpers\Satsets\PostKunjunganIgdHelper;
use App\Models\Satset\Satset;
use App\Models\Satset\SatsetErrorRespon;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardSatsetController extends Controller
{
    /**
     * Ringkasan / Summary statistik pengiriman SatuSehat per modul
     */
    public function summary(Request $request): JsonResponse
    {
        $tglAwal = $request->input('tgl_awal', Carbon::today()->toDateString());
        $tglAkhir = $request->input('tgl_akhir', Carbon::today()->toDateString());

        // 1. Total Kunjungan Selesai di SIMRS pada periode
        $bukanPoli = ['POL014', 'PEN005', 'PEN004'];

        // Rajal
        $totalRajal = KunjunganPoli::whereNotIn('rs8', $bukanPoli)
            ->where('rs19', '1')
            ->whereBetween('rs3', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
            ->count();

        // Ranap (semua pasien ranap masuk pada periode)
        $totalRanap = Kunjunganranap::whereBetween('rs3', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
            ->count();

        // IGD
        $totalIgd = KunjunganPoli::where('rs8', 'POL014')
            ->where('rs19', '1')
            ->whereBetween('rs3', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
            ->count();

        // 2. Terkirim Sukses (Tabel satsets)
        $terkirimRajal = Satset::where('jenis', 'rajal')
            ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
            ->count();

        $terkirimRanap = Satset::where('jenis', 'ranap')
            ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
            ->count();

        $terkirimIgd = Satset::where('jenis', 'igd')
            ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
            ->count();

        // 3. Error Respon (Tabel satset_error_respon)
        $errorRajal = SatsetErrorRespon::where('jenis', 'rajal')
            ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
            ->count();

        $errorRanap = SatsetErrorRespon::where('jenis', 'ranap')
            ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
            ->count();

        $errorIgd = SatsetErrorRespon::where('jenis', 'igd')
            ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
            ->count();

        $totalKunjungan = $totalRajal + $totalRanap + $totalIgd;
        $totalTerkirim = $terkirimRajal + $terkirimRanap + $terkirimIgd;
        $totalError = $errorRajal + $errorRanap + $errorIgd;
        $complianceRate = $totalKunjungan > 0 ? round(($totalTerkirim / $totalKunjungan) * 100, 2) : 0;

        return response()->json([
            'status' => 'success',
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
            ]
        ]);
    }

    /**
     * Analisis Kategori & Pesan Error Terbanyak
     */
    public function errorStats(Request $request): JsonResponse
    {
        $tglAwal = $request->input('tgl_awal', Carbon::today()->toDateString());
        $tglAkhir = $request->input('tgl_akhir', Carbon::today()->toDateString());
        $jenis = $request->input('jenis', 'all');

        $query = SatsetErrorRespon::select(
            DB::raw("COALESCE(NULLIF(error_summary, ''), 'Respon Error Umum / Validasi Payload') as pesan_error"),
            DB::raw('count(*) as total')
        )
            ->whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);

        if ($jenis !== 'all') {
            $query->where('jenis', $jenis);
        }

        $topErrors = $query->groupBy('pesan_error')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'periode' => [
                'tgl_awal' => $tglAwal,
                'tgl_akhir' => $tglAkhir,
                'jenis' => $jenis
            ],
            'top_errors' => $topErrors
        ]);
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
            $bukanPoli = ['PEN005', 'PEN004'];
            $rajalQuery = KunjunganPoli::select(
                'rs17.rs1 as noreg',
                'rs17.rs2 as norm',
                'rs17.rs3 as tgl_kunjungan',
                'rs15.rs2 as nama_pasien',
                'rs15.rs49 as nik',
                'rs19.rs2 as unit_layanan',
                'rs21.rs2 as dokter_dpjp',
                DB::raw("IF(rs17.rs8 = 'POL014', 'igd', 'rajal') as jenis")
            )
                ->leftJoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
                ->leftJoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
                ->leftJoin('rs21', 'rs21.rs1', '=', 'rs17.rs9')
                ->where('rs17.rs19', '1')
                ->whereNotIn('rs17.rs8', $bukanPoli)
                ->whereBetween('rs17.rs3', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);

            if ($jenis === 'igd') {
                $rajalQuery->where('rs17.rs8', '=', 'POL014');
            } elseif ($jenis === 'rajal') {
                $rajalQuery->where('rs17.rs8', '!=', 'POL014');
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

        $query = Satset::whereBetween('created_at', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
            ->whereNotNull('response');

        if ($jenis !== 'all') {
            $query->where('jenis', $jenis);
        }

        $records = $query->select('response', 'jenis')->get();

        $resourceCounts = [];
        $totalResourceCount = 0;

        foreach ($records as $rec) {
            $parsed = $this->parseResourceDetails($rec->response);
            foreach ($parsed['breakdown'] as $resType => $count) {
                if (!isset($resourceCounts[$resType])) {
                    $resourceCounts[$resType] = 0;
                }
                $resourceCounts[$resType] += $count;
                $totalResourceCount += $count;
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

        return response()->json([
            'status' => 'success',
            'periode' => [
                'tgl_awal' => $tglAwal,
                'tgl_akhir' => $tglAkhir,
                'jenis' => $jenis
            ],
            'total_transaksi_bundle' => $records->count(),
            'total_resource_terkirim' => $totalResourceCount,
            'detail_resource' => $breakdownList
        ]);
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
}
