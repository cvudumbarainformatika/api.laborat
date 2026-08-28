<?php

namespace App\Http\Controllers\Api\Simrs\Radiologi;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanRadiologiController extends Controller
{
    public function masterdokter(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        $query = Petugas::query()
            ->select(
                'kdpegsimrs as kode',
                'nama'
            )
            ->where('kdgroupnakes', '1')
            ->where('aktif', 'AKTIF')
            ->whereNotNull('kdpegsimrs')
            ->where(DB::raw('TRIM(COALESCE(kdpegsimrs,""))'), '!=', '');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('kdpegsimrs', 'LIKE', '%' . $q . '%')
                    ->orWhere('nama', 'LIKE', '%' . $q . '%')
                    ->orWhere('nip', 'LIKE', '%' . $q . '%')
                    ->orWhere('nik', 'LIKE', '%' . $q . '%');
            });
        }

        $data = $query->orderBy('nama', 'ASC')->limit(200)->get();

        return response()->json($data);
    }

    public function laporanradiologi(Request $request): JsonResponse
    {
        $from         = $request->get('from');
        $to           = $request->get('to');
        $dokter       = $request->get('dokter', 'ALL');
        $jenis_pasien = $request->get('jenis_pasien', 'ALL');
        $search       = trim($request->get('q', ''));

        if (empty($from) || empty($to)) {
            $from = Carbon::now()->format('Y-m-d');
            $to   = Carbon::now()->format('Y-m-d');
        }

        $tglAwal  = $from . ' 00:00:00';
        $tglAkhir = $to   . ' 23:59:59';

        $base = Transpermintaanradiologi::query();

        $select = $base->select(
            'rs106.rs1 as noreg',
            'rs106.rs2 as nota_permintaan',
            DB::raw('(CASE WHEN rs17.rs2 IS NOT NULL THEN rs17.rs2 ELSE rs23.rs2 END) as norm'),
            DB::raw('COALESCE(
                CONCAT(pasien17.rs3," ",pasien17.gelardepan," ",pasien17.rs2," ",pasien17.gelarbelakang),
                CONCAT(pasien23.rs3," ",pasien23.gelardepan," ",pasien23.rs2," ",pasien23.gelarbelakang)
            ) as namaPasien'),
            DB::raw('(CASE
                WHEN rs17.rs1 IS NOT NULL THEN "Rajal"
                WHEN rs23.rs1 IS NOT NULL THEN "Ranap"
                ELSE "IGD"
            END) as jenisPasien'),
            'rs106.rs8 as kodedokter',
            'petugas.nama as namaDokter',
            DB::raw('(CASE
                WHEN rs19.rs4 IS NOT NULL THEN rs19.rs2 ELSE rs24.rs2
            END) as ruangan'),
            'rs106.rs3 as tgl',
            DB::raw('(CASE
                WHEN rs106.rs9 = "1" THEN "Selesai"
                WHEN rs106.rs9 = "2" THEN "Proses"
                WHEN rs106.rs9 = "3" THEN "Batal"
                ELSE "Belum Terlayani"
            END) as status')
        )
            ->leftjoin('rs17', 'rs106.rs1', '=', 'rs17.rs1')
            ->leftjoin('rs23', 'rs106.rs1', '=', 'rs23.rs1')
            ->leftjoin('rs24', 'rs24.rs1', '=', 'rs106.rs10')
            ->leftjoin('rs15 as pasien17', 'pasien17.rs1', '=', 'rs17.rs2')
            ->leftjoin('rs15 as pasien23', 'pasien23.rs1', '=', 'rs23.rs2')
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs106.rs10')
            ->leftJoin('kepegx.pegawai as petugas', 'petugas.kdpegsimrs', '=', 'rs106.rs8');

        $select->whereBetween('rs106.rs3', [$tglAwal, $tglAkhir]);
        $select->whereNotNull('rs106.rs2');
        $select->where(DB::raw('TRIM(COALESCE(rs106.rs2,""))'), '!=', '');

        if ($dokter !== 'ALL' && !empty($dokter)) {
            $select->where('rs106.rs8', $dokter);
        }

        if ($jenis_pasien !== 'ALL' && !empty($jenis_pasien)) {
            if ($jenis_pasien === 'Rajal') {
                $select->whereNotNull('rs17.rs1');
            } elseif ($jenis_pasien === 'Ranap') {
                $select->whereNotNull('rs23.rs1');
            } elseif ($jenis_pasien === 'IGD') {
                $select->whereNull('rs17.rs1')->whereNull('rs23.rs1');
            }
        }

        if ($search !== '') {
            $select->where(function ($w) use ($search) {
                $w->where('rs106.rs1', 'LIKE', '%' . $search . '%')
                    ->orWhere('rs106.rs2', 'LIKE', '%' . $search . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . $search . '%')
                    ->orWhere('rs23.rs2', 'LIKE', '%' . $search . '%')
                    ->orWhere('pasien17.rs2', 'LIKE', '%' . $search . '%')
                    ->orWhere('pasien23.rs2', 'LIKE', '%' . $search . '%')
                    ->orWhere('petugas.nama', 'LIKE', '%' . $search . '%');
            });
        }

        $select->with([
            'rinciansementara.relmasterpemeriksaan' => function ($q) {
                $q->withTrashed()->select('id1', 'rs2', 'rs3');
            }
        ]);

        $rows = $select->orderBy('rs106.rs3', 'DESC')->limit(5000)->get();

        $final = [];
        foreach ($rows as $row) {
            $rincianList = $row->rinciansementara ?? collect();

            if ($rincianList->count() === 0) {
                $final[] = $this->buildItem($row, null);
                continue;
            }

            foreach ($rincianList as $rincian) {
                $final[] = $this->buildItem($row, $rincian);
            }
        }

        return response()->json([
            'status' => 200,
            'total'  => count($final),
            'data'   => $final,
        ]);
    }

    private function buildItem($row, $rincian): array
    {
        $master = $rincian->relmasterpemeriksaan ?? null;
        $kodeTindakan = $master->id1 ?? ($rincian->rs3 ?? '-');
        $namaTindakan = $master->rs2 ?? ($master->rs3 ?? 'Tindakan Radiologi');

        $nota = $row->nota_permintaan ?? $row->noreg;
        $tgl  = $row->tgl ? Carbon::parse($row->tgl)->format('Y-m-d H:i') : null;

        return [
            'id'           => $nota . '-' . ($kodeTindakan ?? '0'),
            'noreg'        => $row->noreg,
            'norm'         => $row->norm,
            'namaPasien'   => $row->namaPasien,
            'jenisPasien'  => $row->jenisPasien,
            'kodedokter'   => $row->kodedokter,
            'namaDokter'   => $row->namaDokter,
            'ruangan'      => $row->ruangan,
            'tgl'          => $tgl,
            'status'       => $row->status,
            'kodeTindakan' => $kodeTindakan,
            'namaTindakan' => $namaTindakan,
        ];
    }



    public function reportRadiologi(Request $request)
    {
        $groupBy = $request->get('group_by', 'detail');

        $from = $request->get('from');
        $to   = $request->get('to');

        if (!$from || !$to) {
            $from = now()->format('Y-m-d');
            $to   = now()->format('Y-m-d');
        }

        $tglAwal  = $from . ' 00:00:00';
        $tglAkhir = $to . ' 23:59:59';

        switch ($groupBy) {

            /*
        |--------------------------------------------------------------------------
        | DETAIL
        |--------------------------------------------------------------------------
        */
            case 'detail':

                $query = Transpermintaanradiologi::query()

                    ->leftJoin('rs17', 'rs106.rs1', '=', 'rs17.rs1')
                    ->leftJoin('rs23', 'rs106.rs1', '=', 'rs23.rs1')
                    ->leftJoin('rs24', 'rs24.rs1', '=', 'rs106.rs10')
                    ->leftJoin('rs19', 'rs19.rs1', '=', 'rs106.rs10')

                    ->leftJoin('rs15 as pasien17', 'pasien17.rs1', '=', 'rs17.rs2')
                    ->leftJoin('rs15 as pasien23', 'pasien23.rs1', '=', 'rs23.rs2')

                    ->leftJoin(
                        'kepegx.pegawai as pengirim',
                        'pengirim.kdpegsimrs',
                        '=',
                        'rs106.rs8'
                    )

                    ->select(
                        'rs106.rs1',
                        'rs106.rs2',
                        'rs106.rs3',

                        DB::raw('COALESCE(rs17.rs2,rs23.rs2) norm'),

                        DB::raw('COALESCE(
                        CONCAT(pasien17.rs3," ",pasien17.rs2),
                        CONCAT(pasien23.rs3," ",pasien23.rs2)
                    ) pasien'),

                        'pengirim.nama as dokter_pengirim'
                    );

                break;

            /*
        |--------------------------------------------------------------------------
        | PER PEMERIKSAAN
        |--------------------------------------------------------------------------
        */

            case 'pemeriksaan':
                $query = DB::table('rs48')
                    ->join('rs106', 'rs106.rs2', '=', 'rs48.rs2')
                    ->join('rs47', 'rs47.rs1', '=', 'rs48_sem.rs4')
                    ->select(
                        'rs47.rs1 as kode',
                        'rs47.rs2 as kode_bpjs',
                        'rs47.rs3 as pemeriksaan',
                        DB::raw('COUNT(*) jumlah')

                    )

                    ->groupBy(
                        'rs47.rs1',
                        'rs47.rs2',
                        'rs47.rs3'
                    );

                break;

            /*
        |--------------------------------------------------------------------------
        | DOKTER PENGIRIM
        |--------------------------------------------------------------------------
        */

            case 'dokter_pengirim':
                $query = DB::table('rs106')
                    ->leftJoin(
                        'kepegx.pegawai as p',
                        'p.kdpegsimrs',
                        '=',
                        'rs106.rs8'
                    )

                    ->select(
                        'rs106.rs8',
                        'p.nama',
                        DB::raw('COUNT(*) jumlah')

                    )

                    ->groupBy(
                        'rs106.rs8',
                        'p.nama'
                    );

                break;

            /*
        |--------------------------------------------------------------------------
        | DOKTER PELAKSANA
        |--------------------------------------------------------------------------
        */

            case 'dokter_pelaksana':
                $query = DB::table('rs151')
                    ->join(
                        'rs106',
                        'rs106.rs2',
                        '=',
                        'rs151.rs5'
                    )

                    ->select(
                        'rs151.rs4 as dokter',
                        DB::raw('COUNT(*) jumlah')

                    )

                    ->groupBy(
                        'rs151.rs4'
                    );

                break;

            /*
        |--------------------------------------------------------------------------
        | RUANGAN
        |--------------------------------------------------------------------------
        */

            case 'ruangan':

                $query = DB::table('rs106')

                    ->leftJoin('rs19', 'rs19.rs1', '=', 'rs106.rs10')

                    ->leftJoin('rs24', 'rs24.rs1', '=', 'rs106.rs10')

                    ->select(

                        DB::raw("
                        CASE
                            WHEN rs19.rs4 IS NOT NULL
                            THEN rs19.rs2
                            ELSE rs24.rs2
                        END as ruangan
                    "),

                        DB::raw("COUNT(*) jumlah")

                    )

                    ->groupBy('ruangan');

                break;
        }

        /*
    |--------------------------------------------------------------------------
    | FILTER BERSAMA
    |--------------------------------------------------------------------------
    */

        if (Schema::hasColumn($query->from, 'rs3')) {

            $query->whereBetween('rs3', [
                $tglAwal,
                $tglAkhir
            ]);
        } else {

            $query->whereBetween('rs106.rs3', [
                $tglAwal,
                $tglAkhir
            ]);
        }

        return response()->json([
            'data' => $query->get()
        ]);
    }



    public function laporanradiologiSummary(Request $request): JsonResponse
    {
        $from         = $request->get('from');
        $to           = $request->get('to');
        $dokter       = $request->get('dokter', 'ALL');
        $jenis_pasien = $request->get('jenis_pasien', 'ALL');
        $status       = $request->get('status', 'Selesai');
        $group_by     = $request->get('group_by', 'pemeriksaan');
        $search       = trim($request->get('q', ''));

        if (empty($from) || empty($to)) {
            $from = Carbon::now()->format('Y-m-d');
            $to   = Carbon::now()->format('Y-m-d');
        }

        $tglAwal  = $from . ' 00:00:00';
        $tglAkhir = $to   . ' 23:59:59';

        // ===================== BASE (minimal) =====================
        $base = DB::table('rs106')
            ->where('rs106.rs9', '1')
            ->whereBetween('rs106.rs3', [$tglAwal, $tglAkhir])
            ->whereNotNull('rs106.rs2')
            ->where(DB::raw('TRIM(rs106.rs2)'), '!=', '');

        // Filter dokter peminta
        if ($dokter !== 'ALL' && $dokter !== '') {
            $base->where('rs106.rs8', $dokter);
        }

        // Filter status pelayanan pemeriksaan (rs106.rs9)
        if ($status !== 'ALL' && $status !== '') {
            if ($status === 'Selesai' || $status === 'Terlayani') {
                $base->where('rs106.rs9', '1');
            } elseif ($status === 'Belum') {
                $base->where(function ($q) {
                    $q->whereNull('rs106.rs9')->orWhere('rs106.rs9', '!=', '1');
                });
            }
        }

        // Filter jenis pasien
        if ($jenis_pasien !== 'ALL' && $jenis_pasien !== '') {
            if ($jenis_pasien === 'Rajal') {
                $base->whereExists(function ($q) {
                    $q->select(DB::raw(1))->from('rs17')->whereColumn('rs17.rs1', 'rs106.rs1');
                });
            } elseif ($jenis_pasien === 'Ranap') {
                $base->whereExists(function ($q) {
                    $q->select(DB::raw(1))->from('rs23')->whereColumn('rs23.rs1', 'rs106.rs1');
                });
            } elseif ($jenis_pasien === 'IGD') {
                $base->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('rs17')->whereColumn('rs17.rs1', 'rs106.rs1');
                })->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('rs23')->whereColumn('rs23.rs1', 'rs106.rs1');
                });
            }
        }

        // Search (opsional, hanya kalau diisi)
        if ($search !== '') {
            $base->where(function ($w) use ($search) {
                $w->where('rs106.rs1', 'like', "%{$search}%")
                    ->orWhere('rs106.rs2', 'like', "%{$search}%");
            });
        }

        // Jika jenis_pasien adalah 'Luar', panggil handler khusus untuk tabel rs270 & rs271
        if ($jenis_pasien === 'Luar') {
            return $this->laporanRadiologiLuarSummary($tglAwal, $tglAkhir, $group_by, $search, $from, $to, $status);
        }

        // Hitung total nota unik keseluruhan (untuk prosentase)
        $totalNotaUnik = (clone $base)
            ->distinct('rs106.rs2')
            ->count('rs106.rs2');

        // ===================== GROUP BY =====================
        switch ($group_by) {

            // 1. Per Pemeriksaan
            case 'pemeriksaan':
                $data = (clone $base)
                    ->join('rs48', 'rs48.rs2', '=', 'rs106.rs2')
                    ->leftJoin('rs47 as master', 'master.rs1', '=', 'rs48.rs4')
                    ->select(
                        'rs48.rs4 as kode',
                        DB::raw('COALESCE(master.rs2, "Tidak diketahui") as nama'),
                        DB::raw('COUNT(DISTINCT rs106.rs2) as total_nota'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy('rs48.rs4', 'master.rs2')
                    ->orderByDesc('total')
                    ->get();

                if ($jenis_pasien === 'ALL') {
                    $dataLuar = DB::table('rs270')
                        ->join('rs271', 'rs271.rs1', '=', 'rs270.rs1')
                        ->leftJoin('rs47 as master', 'master.rs1', '=', 'rs271.rs3')
                        ->whereBetween('rs270.rs8', [$tglAwal, $tglAkhir])
                        ->select(
                            'rs271.rs3 as kode',
                            DB::raw('COALESCE(master.rs2, "Tidak diketahui") as nama'),
                            DB::raw('COUNT(DISTINCT rs270.rs1) as total_nota'),
                            DB::raw('COUNT(*) as total')
                        )
                        ->groupBy('rs271.rs3', 'master.rs2')
                        ->get();

                    $totalNotaUnik += DB::table('rs270')->whereBetween('rs270.rs8', [$tglAwal, $tglAkhir])->distinct('rs270.rs1')->count('rs270.rs1');

                    $dataMap = $data->keyBy('kode');
                    foreach ($dataLuar as $l) {
                        if ($dataMap->has($l->kode)) {
                            $item = $dataMap->get($l->kode);
                            $item->total_nota += $l->total_nota;
                            $item->total += $l->total;
                        } else {
                            $dataMap->put($l->kode, $l);
                        }
                    }
                    $data = $dataMap->values()->sortByDesc('total')->values();
                }
                break;

            // 2. Per Dokter yang meminta
            case 'dokter_minta':
                $data = (clone $base)
                    ->leftJoin('kepegx.pegawai as petugas', 'petugas.kdpegsimrs', '=', 'rs106.rs8')
                    ->select(
                        'rs106.rs8 as kode',
                        DB::raw('COALESCE(petugas.nama, "Tidak diketahui") as nama'),
                        DB::raw('COUNT(DISTINCT rs106.rs2) as total_nota'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy('rs106.rs8', 'petugas.nama')
                    ->orderByDesc('total')
                    ->get();
                break;

            // 3. Per Dokter yang melaksanakan
            case 'dokter_laksana':
                $data = (clone $base)
                    ->join('rs151', 'rs151.rs5', '=', 'rs106.rs2')
                    ->select(
                        DB::raw('COALESCE(rs151.rs4, "Tidak diketahui") as nama'),
                        DB::raw('COUNT(DISTINCT rs106.rs2) as total_nota'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy('rs151.rs4')
                    ->orderByDesc('total')
                    ->get();
                break;

            // 4. Per Ruangan
            case 'ruangan':
                $data = (clone $base)
                    ->leftJoin('rs19', 'rs19.rs1', '=', 'rs106.rs10')
                    ->leftJoin('rs24', 'rs24.rs1', '=', 'rs106.rs10')
                    ->select(
                        DB::raw('COALESCE(rs19.rs2, rs24.rs2, "Tidak diketahui") as nama'),
                        DB::raw('COUNT(DISTINCT rs106.rs2) as total_nota'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy(DB::raw('COALESCE(rs19.rs2, rs24.rs2)'))
                    ->orderByDesc('total')
                    ->get();

                if ($jenis_pasien === 'ALL') {
                    $dataLuar = DB::table('rs270')
                        ->join('rs271', 'rs271.rs1', '=', 'rs270.rs1')
                        ->whereBetween('rs270.rs8', [$tglAwal, $tglAkhir])
                        ->select(
                            DB::raw('"Permintaan Luar" as nama'),
                            DB::raw('COUNT(DISTINCT rs270.rs1) as total_nota'),
                            DB::raw('COUNT(*) as total')
                        )
                        ->groupBy(DB::raw('"Permintaan Luar"'))
                        ->get();

                    $data = $data->concat($dataLuar)->sortByDesc('total')->values();
                }
                break;


            // =====================================================
            // 5. Per Dokter Peminta + detail pemeriksaan (nested)
            // =====================================================
            case 'dokter_minta_detail':
                $rows = (clone $base)
                    ->leftJoin('kepegx.pegawai as petugas', 'petugas.kdpegsimrs', '=', 'rs106.rs8')
                    ->join('rs48', 'rs48.rs2', '=', 'rs106.rs2')
                    ->leftJoin('rs47 as master', 'master.rs1', '=', 'rs48.rs4')
                    ->select(
                        'rs106.rs8 as kode_dokter',
                        DB::raw('COALESCE(petugas.nama, "Tidak diketahui") as nama_dokter'),
                        'rs48.rs4 as kode_pemeriksaan',
                        DB::raw('COALESCE(master.rs2, "Tidak diketahui") as nama_pemeriksaan'),
                        DB::raw('COUNT(DISTINCT rs106.rs2) as total_nota'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy('rs106.rs8', 'petugas.nama', 'rs48.rs4', 'master.rs2')
                    ->orderBy('nama_dokter')
                    ->orderByDesc('total')
                    ->get();

                if ($jenis_pasien === 'ALL') {
                    $rowsLuar = DB::table('rs270')
                        ->join('rs271', 'rs271.rs1', '=', 'rs270.rs1')
                        ->leftJoin('perusahaan', 'perusahaan.id', '=', 'rs270.perusahaan')
                        ->leftJoin('rs47 as master', 'master.rs1', '=', 'rs271.rs3')
                        ->whereBetween('rs270.rs8', [$tglAwal, $tglAkhir])
                        ->select(
                            DB::raw('"Luar" as kode_dokter'),
                            DB::raw('COALESCE(NULLIF(perusahaan.perusahaan, ""), NULLIF(rs270.rs6, ""), "PASIEN LUAR MANDIRI") as nama_dokter'),
                            'rs271.rs3 as kode_pemeriksaan',
                            DB::raw('COALESCE(master.rs2, "Tidak diketahui") as nama_pemeriksaan'),
                            DB::raw('COUNT(DISTINCT rs270.rs1) as total_nota'),
                            DB::raw('COUNT(*) as total')
                        )
                        ->groupBy(DB::raw('COALESCE(NULLIF(perusahaan.perusahaan, ""), NULLIF(rs270.rs6, ""), "PASIEN LUAR MANDIRI")'), 'rs271.rs3', 'master.rs2')
                        ->get();

                    $rows = $rows->concat($rowsLuar);
                }

                $data = $rows->groupBy('nama_dokter')->map(function ($items, $nama) {
                    $first = $items->first();
                    return [
                        'kode'       => $first->kode_dokter ?? '-',
                        'nama'       => $nama,
                        'total_nota' => $items->sum('total_nota'),
                        'total'      => $items->sum('total'),
                        'pemeriksaan' => $items->groupBy('kode_pemeriksaan')->map(function ($subItems) {
                            $f = $subItems->first();
                            return [
                                'kode'  => $f->kode_pemeriksaan,
                                'nama'  => $f->nama_pemeriksaan,
                                'total' => $subItems->sum('total'),
                            ];
                        })->values(),
                    ];
                })->values();
                break;


            // =====================================================
            // 6. Per Dokter Pelaksana + detail pemeriksaan (nested)
            // =====================================================
            case 'dokter_laksana_detail':
                $rows = (clone $base)
                    ->join('rs151', 'rs151.rs5', '=', 'rs106.rs2')
                    ->join('rs48', 'rs48.rs2', '=', 'rs106.rs2')
                    ->leftJoin('rs47 as master', 'master.rs1', '=', 'rs48.rs4')
                    ->select(
                        DB::raw('COALESCE(rs151.rs4, "Tidak diketahui") as nama_dokter'),
                        'rs48.rs4 as kode_pemeriksaan',
                        DB::raw('COALESCE(master.rs2, "Tidak diketahui") as nama_pemeriksaan'),
                        DB::raw('COUNT(DISTINCT rs106.rs2) as total_nota'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy('rs151.rs4', 'rs48.rs4', 'master.rs2')
                    ->orderBy('nama_dokter')
                    ->orderByDesc('total')
                    ->get();

                if ($jenis_pasien === 'ALL') {
                    $rowsLuar = DB::table('rs270')
                        ->join('rs271', 'rs271.rs1', '=', 'rs270.rs1')
                        ->leftJoin('rs272', function ($j) {
                            $j->on('rs272.rs1', '=', 'rs271.rs1')->on('rs272.kode', '=', 'rs271.rs3');
                        })
                        ->leftJoin('rs47 as master', 'master.rs1', '=', 'rs271.rs3')
                        ->whereBetween('rs270.rs8', [$tglAwal, $tglAkhir])
                        ->select(
                            DB::raw('COALESCE(rs272.rs9, "Tidak diketahui") as nama_dokter'),
                            'rs271.rs3 as kode_pemeriksaan',
                            DB::raw('COALESCE(master.rs2, "Tidak diketahui") as nama_pemeriksaan'),
                            DB::raw('COUNT(DISTINCT rs270.rs1) as total_nota'),
                            DB::raw('COUNT(*) as total')
                        )
                        ->groupBy('rs272.rs9', 'rs271.rs3', 'master.rs2')
                        ->get();

                    $rows = $rows->concat($rowsLuar);
                }

                $data = $rows->groupBy('nama_dokter')->map(function ($items, $nama) {
                    return [
                        'nama'       => $nama,
                        'total_nota' => $items->sum('total_nota'),
                        'total'      => $items->sum('total'),
                        'pemeriksaan' => $items->groupBy('kode_pemeriksaan')->map(function ($subItems) {
                            $f = $subItems->first();
                            return [
                                'kode'  => $f->kode_pemeriksaan,
                                'nama'  => $f->nama_pemeriksaan,
                                'total' => $subItems->sum('total'),
                            ];
                        })->values(),
                    ];
                })->values();
                break;

            default:
                return response()->json([
                    'status'  => 400,
                    'message' => 'group_by harus: pemeriksaan | dokter_minta | dokter_laksana | ruangan'
                ], 400);
        }

        return response()->json([
            'status'   => 200,
            'group_by' => $group_by,
            'from'     => $from,
            'to'       => $to,
            'total'    => $data->sum('total'),
            'total_nota_unik'   => $totalNotaUnik,
            'data'     => $data,
        ]);
    }

    private function laporanRadiologiLuarSummary($tglAwal, $tglAkhir, $groupBy, $search, $from, $to, $status = 'ALL'): JsonResponse
    {
        $base = DB::table('rs270')
            ->whereBetween('rs270.rs8', [$tglAwal, $tglAkhir]);

        if ($status !== 'ALL' && $status !== '') {
            if ($status === 'Selesai' || $status === 'Terlayani') {
                $base->where('rs270.rs10', '1');
            } elseif ($status === 'Belum') {
                $base->where(function ($q) {
                    $q->whereNull('rs270.rs10')->orWhere('rs270.rs10', '!=', '1');
                });
            }
        }

        if ($search !== '') {
            $base->where(function ($w) use ($search) {
                $w->where('rs270.rs1', 'like', "%{$search}%")
                    ->orWhere('rs270.rs2', 'like', "%{$search}%");
            });
        }

        $totalNotaUnik = (clone $base)->distinct('rs270.rs1')->count('rs270.rs1');

        switch ($groupBy) {
            case 'pemeriksaan':
                $data = (clone $base)
                    ->join('rs271', 'rs271.rs1', '=', 'rs270.rs1')
                    ->leftJoin('rs47 as master', 'master.rs1', '=', 'rs271.rs3')
                    ->select(
                        'rs271.rs3 as kode',
                        DB::raw('COALESCE(master.rs2, "Tidak diketahui") as nama'),
                        DB::raw('COUNT(DISTINCT rs270.rs1) as total_nota'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy('rs271.rs3', 'master.rs2')
                    ->orderByDesc('total')
                    ->get();
                break;

            case 'dokter_minta_detail':
                $rows = (clone $base)
                    ->join('rs271', 'rs271.rs1', '=', 'rs270.rs1')
                    ->leftJoin('perusahaan', 'perusahaan.id', '=', 'rs270.perusahaan')
                    ->leftJoin('rs47 as master', 'master.rs1', '=', 'rs271.rs3')
                    ->select(
                        DB::raw('COALESCE(NULLIF(perusahaan.perusahaan, ""), NULLIF(rs270.rs6, ""), "PASIEN LUAR MANDIRI") as nama_dokter'),
                        'rs271.rs3 as kode_pemeriksaan',
                        DB::raw('COALESCE(master.rs2, "Tidak diketahui") as nama_pemeriksaan'),
                        DB::raw('COUNT(DISTINCT rs270.rs1) as total_nota'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy(DB::raw('COALESCE(NULLIF(perusahaan.perusahaan, ""), NULLIF(rs270.rs6, ""), "PASIEN LUAR MANDIRI")'), 'rs271.rs3', 'master.rs2')
                    ->orderBy('nama_dokter')
                    ->orderByDesc('total')
                    ->get();

                $data = $rows->groupBy('nama_dokter')->map(function ($items, $nama) {
                    return [
                        'nama'       => $nama,
                        'total_nota' => $items->sum('total_nota'),
                        'total'      => $items->sum('total'),
                        'pemeriksaan' => $items->map(fn($i) => [
                            'kode'  => $i->kode_pemeriksaan,
                            'nama'  => $i->nama_pemeriksaan,
                            'total' => $i->total,
                        ])->values(),
                    ];
                })->values();
                break;

            case 'dokter_laksana_detail':
                $rows = (clone $base)
                    ->join('rs271', 'rs271.rs1', '=', 'rs270.rs1')
                    ->leftJoin('rs272', function ($j) {
                        $j->on('rs272.rs1', '=', 'rs271.rs1')->on('rs272.kode', '=', 'rs271.rs3');
                    })
                    ->leftJoin('rs47 as master', 'master.rs1', '=', 'rs271.rs3')
                    ->select(
                        DB::raw('COALESCE(rs272.rs9, "Tidak diketahui") as nama_dokter'),
                        'rs271.rs3 as kode_pemeriksaan',
                        DB::raw('COALESCE(master.rs2, "Tidak diketahui") as nama_pemeriksaan'),
                        DB::raw('COUNT(DISTINCT rs270.rs1) as total_nota'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy('rs272.rs9', 'rs271.rs3', 'master.rs2')
                    ->orderBy('nama_dokter')
                    ->orderByDesc('total')
                    ->get();

                $data = $rows->groupBy('nama_dokter')->map(function ($items, $nama) {
                    return [
                        'nama'       => $nama,
                        'total_nota' => $items->sum('total_nota'),
                        'total'      => $items->sum('total'),
                        'pemeriksaan' => $items->map(fn($i) => [
                            'kode'  => $i->kode_pemeriksaan,
                            'nama'  => $i->nama_pemeriksaan,
                            'total' => $i->total,
                        ])->values(),
                    ];
                })->values();
                break;

            case 'ruangan':
                $data = (clone $base)
                    ->join('rs271', 'rs271.rs1', '=', 'rs270.rs1')
                    ->select(
                        DB::raw('"Permintaan Luar" as nama'),
                        DB::raw('COUNT(DISTINCT rs270.rs1) as total_nota'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy(DB::raw('"Permintaan Luar"'))
                    ->get();
                break;

            default:
                $data = collect([]);
                break;
        }

        return response()->json([
            'status'   => 200,
            'group_by' => $groupBy,
            'from'     => $from,
            'to'       => $to,
            'total'    => $data->sum('total'),
            'total_nota_unik' => $totalNotaUnik,
            'data'     => $data,
        ]);
    }
}
