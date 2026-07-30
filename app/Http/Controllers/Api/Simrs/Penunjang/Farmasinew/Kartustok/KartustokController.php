<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Kartustok;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Penunjang\Farmasinew\Mapingkelasterapi;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class KartustokController extends Controller
{

    public function index()
    {
        $koderuangan = request('koderuangan');
        $bulan = request('bulan');
        $tahun = request('tahun');
        $x = $tahun . '-' . $bulan;
        $tglAwal = $x . '-01';
        $tglAkhir = $x . date('-t', strtotime($x . '-01'));
        $dateAwal = Carbon::parse($tglAwal);
        $dateAkhir = Carbon::parse($tglAkhir);
        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m');
        $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-t');
        // $date->format('Y-m-d')
        // return new JsonResponse($dateAwal);
        // return new JsonResponse([
        //     'lalu awal' => $blnLaluAwal,
        //     'lalu Akhir' => $blnLaluAkhir,
        //     'Akhir' => $tglAkhir,
        // ]);

        // $ruangan = Ruang::select('uraian')->where('kode', $koderuangan)->first()->uraian ?? null ;
        // $gudang=Gudang::select('nama')->where('kode', $koderuangan)->first()->nama ?? null;

        // $ruang= $ruangan?? $gudang ?? null;

        $list = Mobatnew::query()
            ->select('kd_obat', 'nama_obat', 'satuan_k', 'satuan_b', 'id', 'flag', 'merk', 'kandungan')
            ->with([
                'saldoawal' => function ($saldo) use ($blnLaluAwal, $blnLaluAkhir, $x) {
                    $saldo
                        // ->whereBetween('tglopname', [$blnLaluAwal . ' 00:00:00', $blnLaluAkhir . ' 23:59:59'])
                        ->where('tglopname', 'LIKE', $blnLaluAwal . '%')
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', DB::raw('sum(jumlah) as jumlah'))
                        ->groupBy('kdobat', 'tglopname');
                },
                'fisik' => function ($saldo) use ($tglAwal, $tglAkhir) {
                    $saldo->whereBetween('tglopname', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', 'jumlah');
                },
                'saldoakhir' => function ($saldo) use ($tglAwal, $tglAkhir, $x) {
                    $saldo
                        // ->whereBetween('tglopname', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('tglopname', 'LIKE', $x . '%')
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', DB::raw('sum(jumlah) as jumlah'))
                        ->groupBy('kdobat', 'tglopname');
                },
                // untuk ambil penyesuaian stok awal
                'stok' => function ($stok) use ($koderuangan, $tglAwal, $tglAkhir, $x) {
                    $stok->select('id', 'kdobat', 'nopenerimaan', 'nobatch', 'jumlah')
                        ->with([
                            'ssw' => function ($q) use ($tglAwal, $tglAkhir, $x) {
                                $q->where('tgl_penyesuaian', 'LIKE', $x . '%');
                                // $q->whereBetween('tgl_penyesuaian', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                            }
                        ])
                        ->where('jumlah', '!=', 0)
                        ->where('kdruang', $koderuangan);
                },
                'penyesuaian' => function ($q) use ($koderuangan, $x) {
                    $q->join('stokreal', 'stokreal.id', '=', 'penyesuaian_stoks.stokreal_id')
                        ->where('kdruang', $koderuangan)
                        ->where('tgl_penyesuaian', 'LIKE', $x . '%');
                },
                // hanya ada jika koderuang itu adalah gudang
                'penerimaanrinci' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'penerimaan_r.kdobat as kdobat',
                        'penerimaan_r.jml_all_penerimaan as jml_all_penerimaan',
                        'penerimaan_r.jml_terima_b as jml_terima_b',
                        'penerimaan_r.jml_terima_k as jml_terima_k',
                        'penerimaan_h.nopenerimaan as nopenerimaan',
                        'penerimaan_h.tglpenerimaan as tglpenerimaan',
                        'penerimaan_h.gudang as gudang',
                        'penerimaan_h.jenissurat as jenissurat',
                        'penerimaan_h.jenis_penerimaan as jenis_penerimaan',
                        'penerimaan_h.kunci as kunci',
                    )
                        ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                        ->whereBetween('penerimaan_h.tglpenerimaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('penerimaan_h.gudang', $koderuangan);
                },


                // mutasi masuk baik dari gudang, ataupun depo termasuk didalamnya mutasi antar depo dan antar gudang÷
                'mutasimasuk' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'mutasi_gudangdepo.kd_obat as kd_obat',
                        // 'mutasi_gudangdepo.jml as jml',
                        DB::raw('sum(mutasi_gudangdepo.jml) as jml'),
                        'permintaan_h.tgl_permintaan as tgl_permintaan',
                        'permintaan_h.tujuan as tujuan',
                        'permintaan_h.dari as dari',
                        'permintaan_h.no_permintaan as no_permintaan'
                    )
                        ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_terima_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('dari', $koderuangan)
                        ->groupBy('mutasi_gudangdepo.kd_obat');
                },


                // mutasi keluar baik ke gudang(mutasi antar gudang), ataupun ke depo dan juga ke ruangan
                'mutasikeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'mutasi_gudangdepo.kd_obat as kd_obat',
                        // 'mutasi_gudangdepo.jml as jml',
                        DB::raw('sum(mutasi_gudangdepo.jml) as jml'),
                        'permintaan_h.tgl_permintaan as tgl_permintaan',
                        'permintaan_h.tujuan as tujuan',
                        'permintaan_h.dari as dari',
                        'permintaan_h.no_permintaan as no_permintaan'
                    )
                        ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_kirim_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('tujuan', $koderuangan)
                        ->groupBy('mutasi_gudangdepo.kd_obat');
                },

                // retur
                'returpenjualan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'retur_penjualan_r.kdobat',
                        DB::raw('sum(retur_penjualan_r.jumlah_retur) as jumlah_retur'),
                    )
                        ->join('retur_penjualan_h', 'retur_penjualan_r.noretur', '=', 'retur_penjualan_h.noretur')
                        ->join('resep_keluar_h', 'retur_penjualan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('retur_penjualan_h.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->groupBy('retur_penjualan_r.kdobat');
                },

                'resepkeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->from('resep_keluar_h')
                        ->select(DB::raw('STRAIGHT_JOIN resep_keluar_r.kdobat, sum(resep_keluar_r.jumlah) as jumlah'))
                        ->join('resep_keluar_r', 'resep_keluar_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->when($koderuangan === 'Gd-04010103', function ($kd) {
                            $kd->leftJoin('persiapan_operasi_rincis', function ($q) {
                                $q->on('persiapan_operasi_rincis.noresep', '=', 'resep_keluar_r.noresep')
                                    ->on('persiapan_operasi_rincis.kd_obat', '=', 'resep_keluar_r.kdobat');
                            })
                                ->whereNull('persiapan_operasi_rincis.noresep');
                        })
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_r.jumlah', '>', 0)
                        ->whereIn('resep_keluar_h.flag', ['3', '4'])
                        ->groupBy('resep_keluar_r.kdobat');
                },

                'resepkeluarracikan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->from('resep_keluar_h')
                        ->select(DB::raw('STRAIGHT_JOIN resep_keluar_racikan_r.kdobat, sum(resep_keluar_racikan_r.jumlah) as jumlah'))
                        ->join('resep_keluar_racikan_r', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_racikan_r.jumlah', '>', 0)
                        ->whereIn('resep_keluar_h.flag', ['3', '4'])
                        ->groupBy('resep_keluar_racikan_r.kdobat');
                },

                'distribusipersiapan' => function ($dist) use ($tglAwal, $tglAkhir) {
                    $dist->select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasis.nopermintaan',
                        'persiapan_operasis.tgl_distribusi',
                        'persiapan_operasi_distribusis.tgl_retur',
                        'persiapan_operasi_rincis.noresep',
                        'persiapan_operasi_rincis.created_at',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah) as keluar'),
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as retur'),

                    )
                        ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                        ->leftJoin('persiapan_operasi_rincis', function ($join) {
                            $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                                ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                        })
                        ->whereBetween('persiapan_operasis.tgl_distribusi', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])

                        ->groupBy('persiapan_operasi_distribusis.kd_obat');
                },
                'persiapanretur' => function ($dist) use ($tglAwal, $tglAkhir) {
                    $dist->select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasis.nopermintaan',
                        'persiapan_operasis.tgl_distribusi',
                        'persiapan_operasi_distribusis.tgl_retur',
                        'persiapan_operasi_rincis.noresep',
                        'persiapan_operasi_rincis.created_at',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah) as keluar'),
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as retur'),

                    )
                        ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                        ->leftJoin('persiapan_operasi_rincis', function ($join) {
                            $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                                ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                        })
                        ->whereBetween('persiapan_operasis.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])

                        ->groupBy('persiapan_operasi_distribusis.kd_obat');
                },
                'barangrusak' => function ($ru) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $ru->select(
                        'kd_obat',
                        DB::raw('sum(jumlah) as jumlah')
                    )->whereBetween('tgl_kunci', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('kunci', '1')
                        ->where('gudang', $koderuangan)
                        ->groupBy('kd_obat');
                },
                // retur gudang (masuk gudang)
                'returgudang' => function ($ru) use ($tglAwal, $tglAkhir) {
                    $ru->select(
                        'retur_gudang_details.kd_obat',
                        'retur_gudangs.tgl_retur',
                        DB::raw('sum(retur_gudang_details.jumlah_retur) as jumlah')
                    )
                        ->leftJoin('retur_gudangs', 'retur_gudangs.no_retur', '=', 'retur_gudang_details.no_retur')
                        ->where('retur_gudangs.gudang', request('koderuangan'))
                        ->whereBetween('retur_gudangs.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('retur_gudangs.kunci', '1')
                        ->groupBy('retur_gudang_details.kd_obat', 'retur_gudangs.gudang');
                },
                // retur depo (keluar depo)
                'returdepo' => function ($ru) use ($tglAwal, $tglAkhir) {
                    $ru->select(
                        'retur_gudang_details.kd_obat',
                        'retur_gudangs.tgl_retur',
                        DB::raw('sum(retur_gudang_details.jumlah_retur) as jumlah')
                    )
                        ->leftJoin('retur_gudangs', 'retur_gudangs.no_retur', '=', 'retur_gudang_details.no_retur')
                        ->where('retur_gudangs.depo', request('koderuangan'))
                        ->whereBetween('retur_gudangs.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('retur_gudangs.kunci', '1')
                        ->groupBy('retur_gudang_details.kd_obat', 'retur_gudangs.depo');
                },
                // retur ke PBF
                'returpbf' => function ($ru) use ($tglAwal, $tglAkhir) {
                    $ru->select(
                        'retur_penyedia_r.kd_obat',
                        'retur_penyedia_h.tgl_kunci as tgl_retur',
                        DB::raw('sum(retur_penyedia_r.jumlah_retur) as jumlah_retur')
                    )
                        ->leftJoin('retur_penyedia_h', 'retur_penyedia_h.no_retur', '=', 'retur_penyedia_r.no_retur')
                        ->where('retur_penyedia_h.gudang', request('koderuangan'))
                        ->whereBetween('retur_penyedia_h.tgl_kunci', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('retur_penyedia_h.kunci', '1')
                        ->groupBy('retur_penyedia_r.kd_obat', 'retur_penyedia_h.gudang');
                },
                // pengembalian pinjaman
                'pengembalianrincififo' => function ($ru) use ($tglAwal, $tglAkhir) {
                    $ru->select(
                        'pengembalian_rinci_fifos.kdobat',
                        'pengembalians.tgl_kunci',
                        DB::raw('sum(pengembalian_rinci_fifos.jml_dikembalikan) as jumlah')
                    )
                        ->leftJoin('pengembalians', 'pengembalians.nopengembalian', '=', 'pengembalian_rinci_fifos.nopengembalian')
                        ->where('pengembalians.kdruang', request('koderuangan'))
                        ->whereBetween('pengembalians.tgl_kunci', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('pengembalians.flag', '1')
                        ->groupBy('pengembalian_rinci_fifos.kdobat', 'pengembalians.kdruang');
                }

            ])

            // ->withCount('penerimaanrinci')
            // ->addSelect([
            //     'ruangan' => $ruang
            // ])
            ->where(function ($q) {
                $q->where('nama_obat', 'Like', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'Like', '%' . request('q') . '%')
                    ->orWhere('merk', 'Like', '%' . request('q') . '%')
                    ->orWhere('kandungan', 'Like', '%' . request('q') . '%');
            })->orderBy('id', 'asc')
            ->where('flag', '')
            ->paginate(request('rowsPerPage'));



        return new JsonResponse($list);
        // return new JsonResponse([
        //     'lalu awal'=>$blnLaluAwal,
        //     'lalu Akhir'=>$blnLaluAkhir,
        // ]);
    }
    public function rinci()
    {
        $koderuangan = request('koderuangan');
        $bulan = request('bulan');
        $tahun = request('tahun');
        $x = $tahun . '-' . $bulan;
        // $tglAwal = $x . '-01';
        // $tglAkhir = $x . date('-t', strtotime($x . '-01'));
        // $dateAwal = Carbon::parse($tglAwal);
        // $dateAkhir = Carbon::parse($tglAkhir);
        $bulan = request('bulan');
        $tahun = request('tahun');
        $dateAwal = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
        $dateAkhir = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->endOfDay();

        $tglAwal = $dateAwal->toDateTimeString(); // "2025-04-01 00:00:00"
        $tglAkhir = $dateAkhir->toDateTimeString(); // "2025-04-30 23:59:59"

        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m');
        $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-t');
        // $date->format('Y-m-d')
        // return new JsonResponse($dateAwal);

        // $ruangan = Ruang::select('uraian')->where('kode', $koderuangan)->first()->uraian ?? null ;
        // $gudang=Gudang::select('nama')->where('kode', $koderuangan)->first()->nama ?? null;

        // $ruang= $ruangan?? $gudang ?? null;

        $list = Mobatnew::query()
            ->select('kd_obat', 'nama_obat', 'satuan_k', 'satuan_b', 'id', 'flag', 'merk', 'kandungan')
            ->with([
                'saldoawal' => function ($saldo) use ($blnLaluAwal, $blnLaluAkhir) {
                    $saldo
                        // ->whereBetween('tglopname', [$blnLaluAwal . ' 00:00:00', $blnLaluAkhir . ' 23:59:59'])
                        ->where('tglopname', 'LIKE', $blnLaluAwal . '%')
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', DB::raw('sum(jumlah) as jumlah'))
                        ->groupBy('kdobat', 'tglopname');
                },
                'fisik' => function ($saldo) use ($tglAwal, $tglAkhir) {
                    $saldo->whereBetween('tglopname', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', 'jumlah');
                },
                'saldoakhir' => function ($saldo) use ($tglAwal, $tglAkhir, $x) {
                    $saldo
                        // ->whereBetween('tglopname', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('tglopname', 'LIKE', $x . '%')
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', DB::raw('sum(jumlah) as jumlah'))
                        ->groupBy('kdobat', 'tglopname');
                },
                // untuk ambil penyesuaian stok awal
                'stok' => function ($stok) use ($koderuangan, $tglAwal, $tglAkhir, $x) {
                    $stok->select('id', 'kdobat', 'nopenerimaan', 'nobatch', 'jumlah')
                        ->with([
                            'ssw' => function ($q) use ($tglAwal, $tglAkhir, $x) {
                                $q->where('tgl_penyesuaian', 'LIKE', $x . '%');
                                // $q->whereBetween('tgl_penyesuaian', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                            }
                        ])
                        ->where('jumlah', '!=', 0)
                        ->where('kdruang', $koderuangan);
                },

                'penyesuaian' => function ($q) use ($koderuangan, $x) {
                    $q->join('stokreal', 'stokreal.id', '=', 'penyesuaian_stoks.stokreal_id')
                        ->where('kdruang', $koderuangan)
                        ->where('tgl_penyesuaian', 'LIKE', $x . '%');
                },
                // hanya ada jika koderuang itu adalah gudang
                'penerimaanrinci' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'penerimaan_r.kdobat as kdobat',
                        'penerimaan_r.jml_all_penerimaan as jml_all_penerimaan',
                        'penerimaan_r.jml_terima_b as jml_terima_b',
                        'penerimaan_r.jml_terima_k as jml_terima_k',
                        'penerimaan_h.nopenerimaan as nopenerimaan',
                        'penerimaan_h.tglpenerimaan as tglpenerimaan',
                        'penerimaan_h.gudang as gudang',
                        'penerimaan_h.jenissurat as jenissurat',
                        'penerimaan_h.jenis_penerimaan as jenis_penerimaan',
                        'penerimaan_h.kunci as kunci',
                        // DB::connection('sigarang')->raw(
                        //     '(CASE WHEN EXISTS (
                        //         SELECT 1
                        //         FROM gudangs
                        //         WHERE gudangs.kode = penerimaan_h.gudang
                        //     ) THEN "yes" ELSE "no" END) AS ruangan'
                        // )
                    )
                        ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                        // ->join('sigarang.gudangs as gudangs', 'penerimaan_h.gudang', '=', 'gudangs.kode')
                        ->whereBetween('penerimaan_h.tglpenerimaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('penerimaan_h.gudang', $koderuangan);
                },


                // mutasi masuk baik dari gudang, ataupun depo termasuk didalamnya mutasi antar depo dan antar gudang÷
                'mutasimasuk' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {

                    $q->select(
                        'mutasi_gudangdepo.kd_obat as kd_obat',
                        'mutasi_gudangdepo.jml as jml',
                        'permintaan_h.tgl_terima_depo as tgl_permintaan',
                        'permintaan_h.tujuan as tujuan',
                        'permintaan_h.dari as dari',
                        'permintaan_h.no_permintaan as no_permintaan'
                    )
                        ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_terima_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('dari', $koderuangan);
                },


                // mutasi keluar baik ke gudang(mutasi antar gudang), ataupun ke depo dan juga ke ruangan
                'mutasikeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {

                    $q->select(
                        'mutasi_gudangdepo.kd_obat as kd_obat',
                        'mutasi_gudangdepo.jml as jml',
                        'permintaan_h.tgl_kirim_depo as tgl_permintaan',
                        'permintaan_h.tujuan as tujuan',
                        'permintaan_h.dari as dari',
                        'permintaan_h.no_permintaan as no_permintaan'
                    )
                        ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_kirim_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('tujuan', $koderuangan);
                },

                // retur
                'returpenjualan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->join('retur_penjualan_h', 'retur_penjualan_r.noretur', '=', 'retur_penjualan_h.noretur')
                        ->join('resep_keluar_h', 'retur_penjualan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('retur_penjualan_h.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan);
                },

                'resepkeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->from('resep_keluar_h')
                        ->select(DB::raw('STRAIGHT_JOIN resep_keluar_r.*, resep_keluar_h.tgl_selesai'))
                        ->join('resep_keluar_r', 'resep_keluar_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->when($koderuangan === 'Gd-04010103', function ($kd) {
                            $kd->leftJoin('persiapan_operasi_rincis', function ($q) {
                                $q->on('persiapan_operasi_rincis.noresep', '=', 'resep_keluar_r.noresep')
                                    ->on('persiapan_operasi_rincis.kd_obat', '=', 'resep_keluar_r.kdobat');
                            })
                                ->whereNull('persiapan_operasi_rincis.noresep');
                        })
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_r.jumlah', '>', 0)
                        ->whereIn('resep_keluar_h.flag', ['3', '4'])
                        ->with('retur.rinci');
                },

                'resepkeluarracikan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->from('resep_keluar_h')
                        ->select(DB::raw('STRAIGHT_JOIN resep_keluar_racikan_r.*, resep_keluar_h.tgl_selesai'))
                        ->join('resep_keluar_racikan_r', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_racikan_r.jumlah', '>', 0)
                        ->whereIn('resep_keluar_h.flag', ['3', '4'])
                        ->with('retur.rinci');
                },

                // // ini jika $koderuangan = Gd-04010103 (Depo OK) ini nanti di front end
                // 'persiapanoperasiretur' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                //     $q->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                //         ->whereBetween('persiapan_operasis.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                // },
                // // ini jika $koderuangan = Gd-04010103 (Depo OK)
                // // ini keluarnya nanti jumlah_distribusi harus dikurangi jumlah_resep karena resep nanti akan di ambil juga
                // 'persiapanoperasikeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                //     $q->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                //         ->whereBetween('persiapan_operasis.tgl_distribusi', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                //     // $q->whereHas('header', function ($x) use ($tglAwal, $tglAkhir, $koderuangan) {
                //     //     $x->whereBetween('tgl_distribusi', [$tglAwal, $tglAkhir]);
                //     //     // ->where('tujuan', $koderuangan);
                //     // });
                // },
                'distribusipersiapan' => function ($dist) use ($tglAwal, $tglAkhir) {
                    $dist->select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasis.nopermintaan',
                        'persiapan_operasis.tgl_distribusi',
                        'persiapan_operasi_distribusis.tgl_retur',
                        'persiapan_operasi_rincis.noresep',
                        'persiapan_operasi_rincis.created_at',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah) as keluar'),
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as retur'),

                    )
                        ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                        ->leftJoin('persiapan_operasi_rincis', function ($join) {
                            $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                                ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                        })
                        ->whereBetween('persiapan_operasis.tgl_distribusi', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])

                        ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasis.nopermintaan');
                },
                'persiapanretur' => function ($dist) use ($tglAwal, $tglAkhir) {
                    $dist->select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasis.nopermintaan',
                        'persiapan_operasis.tgl_distribusi',
                        'persiapan_operasi_distribusis.tgl_retur',
                        'persiapan_operasi_rincis.noresep',
                        'persiapan_operasi_rincis.created_at',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah) as keluar'),
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as retur'),

                    )
                        ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                        ->leftJoin('persiapan_operasi_rincis', function ($join) {
                            $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                                ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                        })
                        ->whereBetween('persiapan_operasis.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])

                        ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasis.nopermintaan');
                },
                'barangrusak' => function ($ru) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $ru->select(
                        'kd_obat',
                        'jumlah',
                        'status',
                        'tgl_kunci as tgl_rusak',
                        'created_at',
                    )
                        ->whereBetween('tgl_kunci', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('gudang', $koderuangan)
                        ->where('kunci', '1');
                },
                // retur gudang (masuk gudang)
                'returgudang' => function ($ru) use ($tglAwal, $tglAkhir) {
                    $ru->select(
                        'retur_gudang_details.kd_obat',
                        'retur_gudangs.tgl_retur',
                        'retur_gudangs.no_retur',
                        DB::raw('sum(retur_gudang_details.jumlah_retur) as jumlah')
                    )
                        ->leftJoin('retur_gudangs', 'retur_gudangs.no_retur', '=', 'retur_gudang_details.no_retur')
                        ->where('retur_gudangs.gudang', request('koderuangan'))
                        ->whereBetween('retur_gudangs.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('retur_gudangs.kunci', '1')
                        ->groupBy('retur_gudang_details.kd_obat', 'retur_gudangs.gudang', 'retur_gudangs.no_retur');
                },
                // retur depo (keluar depo)
                'returdepo' => function ($ru) use ($tglAwal, $tglAkhir) {
                    $ru->select(
                        'retur_gudang_details.kd_obat',
                        'retur_gudangs.tgl_retur',
                        'retur_gudangs.no_retur',
                        DB::raw('sum(retur_gudang_details.jumlah_retur) as jumlah')
                    )
                        ->leftJoin('retur_gudangs', 'retur_gudangs.no_retur', '=', 'retur_gudang_details.no_retur')
                        ->where('retur_gudangs.depo', request('koderuangan'))
                        ->whereBetween('retur_gudangs.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('retur_gudangs.kunci', '1')
                        ->groupBy('retur_gudang_details.kd_obat', 'retur_gudangs.depo', 'retur_gudangs.no_retur');
                },
                // retur ke PBF
                'returpbf' => function ($ru) use ($tglAwal, $tglAkhir) {
                    $ru->select(
                        'retur_penyedia_r.kd_obat',
                        'retur_penyedia_h.tgl_kunci as tgl_retur',
                        'retur_penyedia_h.no_retur',
                        DB::raw('sum(retur_penyedia_r.jumlah_retur) as jumlah_retur')
                    )
                        ->leftJoin('retur_penyedia_h', 'retur_penyedia_h.no_retur', '=', 'retur_penyedia_r.no_retur')
                        ->where('retur_penyedia_h.gudang', request('koderuangan'))
                        ->whereBetween('retur_penyedia_h.tgl_kunci', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('retur_penyedia_h.kunci', '1')
                        ->groupBy('retur_penyedia_r.kd_obat', 'retur_penyedia_h.gudang', 'retur_penyedia_h.no_retur');
                },

                // pengembalian pinjaman
                'pengembalianrincififo' => function ($ru) use ($tglAwal, $tglAkhir) {
                    $ru->select(
                        'pengembalian_rinci_fifos.kdobat',
                        'pengembalians.tgl_kunci',
                        'pengembalians.nopengembalian',
                        DB::raw('sum(pengembalian_rinci_fifos.jml_dikembalikan) as jumlah')
                    )
                        ->leftJoin('pengembalians', 'pengembalians.nopengembalian', '=', 'pengembalian_rinci_fifos.nopengembalian')
                        ->where('pengembalians.kdruang', request('koderuangan'))
                        ->whereBetween('pengembalians.tgl_kunci', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('pengembalians.flag', '1')
                        ->groupBy('pengembalian_rinci_fifos.kdobat', 'pengembalians.kdruang', 'pengembalians.nopengembalian');
                }


            ])

            // ->withCount('penerimaanrinci')
            // ->addSelect([
            //     'ruangan' => $ruang
            // ])
            // ->where(function ($q) {
            //     $q->where('nama_obat', 'Like', '%' . request('q') . '%')
            //         ->orWhere('kd_obat', 'Like', '%' . request('q') . '%')
            //         ->orWhere('merk', 'Like', '%' . request('q') . '%')
            //         ->orWhere('kandungan', 'Like', '%' . request('q') . '%');
            // })
            ->orderBy('id', 'asc')
            ->where('flag', '')
            ->where('kd_obat', request('kd_obat'))
            ->first();



        return new JsonResponse($list);
        // return new JsonResponse([
        //     'lalu awal'=>$blnLaluAwal,
        //     'lalu Akhir'=>$blnLaluAkhir,
        // ]);
    }

    public function exportExcel()
    {
        $koderuangan = request('koderuangan');
        $bulan = request('bulan');
        $tahun = request('tahun');
        
        $x = $tahun . '-' . $bulan;
        $tglAwal = $x . '-01';
        $tglAkhir = $x . date('-t', strtotime($x . '-01'));
        
        $dateAwal = Carbon::parse($tglAwal);
        $dateAkhir = Carbon::parse($tglAkhir);
        
        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m');
        $blnLalu = $blnLaluAwal . '%';
        
        $tglAwalFull = $tglAwal . ' 00:00:00';
        $tglAkhirFull = $tglAkhir . ' 23:59:59';
        $bulanTahunPattern = $x . '%';
        
        $bulanIni = Carbon::now()->format('m');
        
        $stokTable = 'stokopname';
        $stokQueryPart = "SELECT kdobat, SUM(jumlah) as jumlah FROM stokopname WHERE tglopname LIKE :stokBulanTahun AND kdruang = :kdruang15 GROUP BY kdobat";
        
        if ($bulan === $bulanIni && $tahun == Carbon::now()->format('Y')) {
            $stokTable = 'stokreal';
            $stokQueryPart = "SELECT kdobat, SUM(jumlah) as jumlah FROM stokreal WHERE kdruang = :kdruang15 GROUP BY kdobat";
        }
        
        $sql = "
            SELECT 
                m.kd_obat, 
                m.nama_obat, 
                m.satuan_k,
                m.satuan_b,
                m.merk,
                m.kandungan,
                COALESCE(sa.jumlah, 0) as saldo_awal,
                COALESCE(p.jumlah, 0) as penerimaan,
                COALESCE(mm.jumlah, 0) as mutasi_masuk,
                COALESCE(rp.jumlah, 0) as retur_penjualan,
                COALESCE(pm.jumlah, 0) as penyesuaian_masuk,
                COALESCE(pr.jumlah, 0) as persiapan_retur,
                COALESCE(rg.jumlah, 0) as retur_gudang,
                COALESCE(mk.jumlah, 0) as mutasi_keluar,
                COALESCE(rk.jumlah, 0) as resep_keluar,
                COALESCE(rkr.jumlah, 0) as resep_keluar_racikan,
                COALESCE(pk.jumlah, 0) as penyesuaian_keluar,
                COALESCE(dp.jumlah, 0) as distribusi_persiapan,
                COALESCE(br.jumlah, 0) as barang_rusak,
                COALESCE(rd.jumlah, 0) as retur_depo,
                COALESCE(rpbf.jumlah, 0) as retur_pbf,
                COALESCE(pb.jumlah, 0) as pengembalian,
                COALESCE(ss.jumlah, 0) as stok_sekarang,
                COALESCE(sf.jumlah, 0) as stok_fisik
            FROM new_masterobat m
            LEFT JOIN (
                SELECT kdobat, SUM(jumlah) as jumlah 
                FROM stokopname 
                WHERE tglopname LIKE :blnLalu 
                  AND kdruang = :kdruang1
                GROUP BY kdobat
            ) sa ON sa.kdobat = m.kd_obat
            LEFT JOIN (
                SELECT pr.kdobat, 
                       SUM(CASE WHEN ph.jenis_penerimaan != 'Pesanan' THEN pr.jml_terima_k
                                WHEN ph.jenissurat = 'Faktur' THEN pr.jml_terima_k 
                                ELSE 0 END) as jumlah
                FROM penerimaan_r pr
                INNER JOIN penerimaan_h ph ON pr.nopenerimaan = ph.nopenerimaan
                WHERE ph.tglpenerimaan BETWEEN :tglAwal1 AND :tglAkhir1
                  AND ph.gudang = :kdruang2
                  AND ph.kunci = '1'
                GROUP BY pr.kdobat
            ) p ON p.kdobat = m.kd_obat
            LEFT JOIN (
                SELECT mgd.kd_obat, SUM(mgd.jml) as jumlah
                FROM mutasi_gudangdepo mgd
                INNER JOIN permintaan_h ph ON ph.no_permintaan = mgd.no_permintaan
                WHERE ph.tgl_terima_depo BETWEEN :tglAwal2 AND :tglAkhir2
                  AND ph.dari = :kdruang3
                GROUP BY mgd.kd_obat
            ) mm ON mm.kd_obat = m.kd_obat
            LEFT JOIN (
                SELECT rpr.kdobat, SUM(rpr.jumlah_retur) as jumlah
                FROM retur_penjualan_r rpr
                INNER JOIN retur_penjualan_h rph ON rpr.noretur = rph.noretur
                INNER JOIN resep_keluar_h rkh ON rpr.noresep = rkh.noresep
                WHERE rph.tgl_retur BETWEEN :tglAwal3 AND :tglAkhir3
                  AND rkh.depo = :kdruang4
                GROUP BY rpr.kdobat
            ) rp ON rp.kdobat = m.kd_obat
            LEFT JOIN (
                SELECT sr.kdobat, SUM(ps.penyesuaian) as jumlah
                FROM penyesuaian_stoks ps
                INNER JOIN stokreal sr ON sr.id = ps.stokreal_id
                WHERE ps.tgl_penyesuaian LIKE :bulanTahun1
                  AND sr.kdruang = :kdruang5
                  AND ps.penyesuaian > 0
                GROUP BY sr.kdobat
            ) pm ON pm.kdobat = m.kd_obat
            LEFT JOIN (
                SELECT pod.kd_obat, SUM(pod.jumlah_retur) as jumlah
                FROM persiapan_operasi_distribusis pod
                INNER JOIN persiapan_operasis po ON po.nopermintaan = pod.nopermintaan
                WHERE po.tgl_retur BETWEEN :tglAwal4 AND :tglAkhir4
                  AND po.flag IN ('2', '3', '4')
                GROUP BY pod.kd_obat
            ) pr ON pr.kd_obat = m.kd_obat
            LEFT JOIN (
                SELECT rgd.kd_obat, SUM(rgd.jumlah_retur) as jumlah
                FROM retur_gudang_details rgd
                INNER JOIN retur_gudangs rg ON rg.no_retur = rgd.no_retur
                WHERE rg.tgl_retur BETWEEN :tglAwal5 AND :tglAkhir5
                  AND rg.gudang = :kdruang6
                  AND rg.kunci = '1'
                GROUP BY rgd.kd_obat
            ) rg ON rg.kd_obat = m.kd_obat
            LEFT JOIN (
                SELECT mgd.kd_obat, SUM(mgd.jml) as jumlah
                FROM mutasi_gudangdepo mgd
                INNER JOIN permintaan_h ph ON ph.no_permintaan = mgd.no_permintaan
                WHERE ph.tgl_kirim_depo BETWEEN :tglAwal6 AND :tglAkhir6
                  AND ph.tujuan = :kdruang7
                GROUP BY mgd.kd_obat
            ) mk ON mk.kd_obat = m.kd_obat
            LEFT JOIN (
                SELECT r.kdobat, SUM(r.jumlah) as jumlah
                FROM resep_keluar_h h STRAIGHT_JOIN resep_keluar_r r ON r.noresep = h.noresep
                WHERE h.tgl_selesai BETWEEN :tglAwal7 AND :tglAkhir7
                  AND h.depo = :kdruang8
                  AND h.flag IN ('3', '4')
                  AND r.jumlah > 0
                GROUP BY r.kdobat
            ) rk ON rk.kdobat = m.kd_obat
            LEFT JOIN (
                SELECT r.kdobat, SUM(r.jumlah) as jumlah
                FROM resep_keluar_h h STRAIGHT_JOIN resep_keluar_racikan_r r ON r.noresep = h.noresep
                WHERE h.tgl_selesai BETWEEN :tglAwal8 AND :tglAkhir8
                  AND h.depo = :kdruang9
                  AND h.flag IN ('3', '4')
                  AND r.jumlah > 0
                GROUP BY r.kdobat
            ) rkr ON rkr.kdobat = m.kd_obat
            LEFT JOIN (
                SELECT sr.kdobat, SUM(-ps.penyesuaian) as jumlah
                FROM penyesuaian_stoks ps
                INNER JOIN stokreal sr ON sr.id = ps.stokreal_id
                WHERE ps.tgl_penyesuaian LIKE :bulanTahun2
                  AND sr.kdruang = :kdruang10
                  AND ps.penyesuaian < 0
                GROUP BY sr.kdobat
            ) pk ON pk.kdobat = m.kd_obat
            LEFT JOIN (
                SELECT pod.kd_obat, SUM(pod.jumlah) as jumlah
                FROM persiapan_operasi_distribusis pod
                INNER JOIN persiapan_operasis po ON po.nopermintaan = pod.nopermintaan
                WHERE po.tgl_distribusi BETWEEN :tglAwal9 AND :tglAkhir9
                  AND po.flag IN ('2', '3', '4')
                GROUP BY pod.kd_obat
            ) dp ON dp.kd_obat = m.kd_obat
            LEFT JOIN (
                SELECT kd_obat, SUM(jumlah) as jumlah
                FROM barang_rusaks
                WHERE tgl_kunci BETWEEN :tglAwal10 AND :tglAkhir10
                  AND gudang = :kdruang11
                  AND kunci = '1'
                GROUP BY kd_obat
            ) br ON br.kd_obat = m.kd_obat
            LEFT JOIN (
                SELECT rgd.kd_obat, SUM(rgd.jumlah_retur) as jumlah
                FROM retur_gudang_details rgd
                INNER JOIN retur_gudangs rg ON rg.no_retur = rgd.no_retur
                WHERE rg.tgl_retur BETWEEN :tglAwal11 AND :tglAkhir11
                  AND rg.depo = :kdruang12
                  AND rg.kunci = '1'
                GROUP BY rgd.kd_obat
            ) rd ON rd.kd_obat = m.kd_obat
            LEFT JOIN (
                SELECT rpr.kd_obat, SUM(rpr.jumlah_retur) as jumlah
                FROM retur_penyedia_r rpr
                INNER JOIN retur_penyedia_h rph ON rpr.no_retur = rph.no_retur
                WHERE rph.tgl_kunci BETWEEN :tglAwal12 AND :tglAkhir12
                  AND rph.gudang = :kdruang13
                  AND rph.kunci = '1'
                GROUP BY rpr.kd_obat
            ) rpbf ON rpbf.kd_obat = m.kd_obat
            LEFT JOIN (
                SELECT r.kdobat, SUM(r.jml_dikembalikan) as jumlah
                FROM pengembalian_rinci_fifos r
                INNER JOIN pengembalians p ON p.nopengembalian = r.nopengembalian
                WHERE p.tgl_kunci BETWEEN :tglAwal13 AND :tglAkhir13
                  AND p.kdruang = :kdruang14
                  AND p.flag = '1'
                GROUP BY r.kdobat
            ) pb ON pb.kdobat = m.kd_obat
            LEFT JOIN (
                {$stokQueryPart}
            ) ss ON ss.kdobat = m.kd_obat
            LEFT JOIN (
                SELECT kdobat, SUM(jumlah) as jumlah
                FROM stok_opname_fisiks
                WHERE tglopname BETWEEN :tglAwal14 AND :tglAkhir14
                  AND kdruang = :kdruang16
                GROUP BY kdobat
            ) sf ON sf.kdobat = m.kd_obat
            WHERE m.flag = ''
            ORDER BY m.nama_obat ASC
        ";
        
        $bindings = [
            'blnLalu' => $blnLalu,
            'kdruang1' => $koderuangan,
            'tglAwal1' => $tglAwalFull,
            'tglAkhir1' => $tglAkhirFull,
            'kdruang2' => $koderuangan,
            'tglAwal2' => $tglAwalFull,
            'tglAkhir2' => $tglAkhirFull,
            'kdruang3' => $koderuangan,
            'tglAwal3' => $tglAwalFull,
            'tglAkhir3' => $tglAkhirFull,
            'kdruang4' => $koderuangan,
            'bulanTahun1' => $bulanTahunPattern,
            'kdruang5' => $koderuangan,
            'tglAwal4' => $tglAwalFull,
            'tglAkhir4' => $tglAkhirFull,
            'tglAwal5' => $tglAwalFull,
            'tglAkhir5' => $tglAkhirFull,
            'kdruang6' => $koderuangan,
            'tglAwal6' => $tglAwalFull,
            'tglAkhir6' => $tglAkhirFull,
            'kdruang7' => $koderuangan,
            'tglAwal7' => $tglAwalFull,
            'tglAkhir7' => $tglAkhirFull,
            'kdruang8' => $koderuangan,
            'tglAwal8' => $tglAwalFull,
            'tglAkhir8' => $tglAkhirFull,
            'kdruang9' => $koderuangan,
            'bulanTahun2' => $bulanTahunPattern,
            'kdruang10' => $koderuangan,
            'tglAwal9' => $tglAwalFull,
            'tglAkhir9' => $tglAkhirFull,
            'tglAwal10' => $tglAwalFull,
            'tglAkhir10' => $tglAkhirFull,
            'kdruang11' => $koderuangan,
            'tglAwal11' => $tglAwalFull,
            'tglAkhir11' => $tglAkhirFull,
            'kdruang12' => $koderuangan,
            'tglAwal12' => $tglAwalFull,
            'tglAkhir12' => $tglAkhirFull,
            'kdruang13' => $koderuangan,
            'tglAwal13' => $tglAwalFull,
            'tglAkhir13' => $tglAkhirFull,
            'kdruang14' => $koderuangan,
            'kdruang15' => $koderuangan,
            'tglAwal14' => $tglAwalFull,
            'tglAkhir14' => $tglAkhirFull,
            'kdruang16' => $koderuangan,
        ];
        
        if ($stokTable === 'stokopname') {
            $bindings['stokBulanTahun'] = $bulanTahunPattern;
        }
        
        $results = DB::connection('farmasi')->select($sql, $bindings);
        
        return Excel::download(new KartuStokExport($results, $koderuangan), "kartu-stok-{$bulan}-{$tahun}.xlsx");
    }

    public function cariobat()
    {

        $query = Mobatnew::select(
            'kd_obat as kodeobat',
            'nama_obat as namaobat',
            'satuan_k',
            'satuan_b',
        )->where('flag', '')
            ->where(function ($list) {
                $list->where('nama_obat', 'Like', '%' . request('q') . '%');
            })->orderBy('nama_obat')
            ->get();
        return new JsonResponse($query);
    }
}

class KartuStokExport implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\ShouldAutoSize
{
    protected $results;
    protected $koderuangan;
    protected $rowNumber = 0;

    public function __construct($results, $koderuangan)
    {
        $this->results = $results;
        $this->koderuangan = $koderuangan;
    }

    public function collection()
    {
        return collect($this->results);
    }

    public function headings(): array
    {
        return [
            'No', 
            'Kode Obat', 
            'Nama Obat', 
            'Satuan',
            'Merk',
            'Kandungan',
            'Saldo Awal', 
            'Stok Masuk', 
            'Stok Keluar', 
            'Stok Akhir', 
            'Stok Sekarang', 
            'Stok Fisik'
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;
        
        $gudangList = ['Gd-05010100', 'Gd-03010100'];
        $isGudang = in_array($this->koderuangan, $gudangList);
        
        // Calculate Masuk
        $masuk = floatval($row->penerimaan) + 
                 floatval($row->mutasi_masuk) + 
                 floatval($row->retur_penjualan) + 
                 floatval($row->penyesuaian_masuk) + 
                 floatval($row->persiapan_retur) + 
                 floatval($row->retur_gudang);
                 
        // Calculate Keluar
        $isDepoOk = ($this->koderuangan === 'Gd-04010103');
        
        $resepKeluarJml = floatval($row->resep_keluar);
        $resepRacikanJml = floatval($row->resep_keluar_racikan);
        $distPersiapanJml = $isDepoOk ? floatval($row->distribusi_persiapan) : 0;
        $barangRusakJml = $isGudang ? floatval($row->barang_rusak) : 0;
        
        $keluar = floatval($row->mutasi_keluar) + 
                  $resepKeluarJml + 
                  $resepRacikanJml + 
                  floatval($row->penyesuaian_keluar) + 
                  $distPersiapanJml + 
                  $barangRusakJml + 
                  floatval($row->retur_depo) + 
                  floatval($row->retur_pbf) + 
                  floatval($row->pengembalian);
                  
        $stokAkhir = floatval($row->saldo_awal) + $masuk - $keluar;
        
        return [
            $this->rowNumber,
            $row->kd_obat,
            $row->nama_obat,
            $row->satuan_k,
            $row->merk ?? '-',
            $row->kandungan ?? '-',
            floatval($row->saldo_awal),
            $masuk,
            $keluar,
            $stokAkhir,
            floatval($row->stok_sekarang),
            floatval($row->stok_fisik)
        ];
    }
}
