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

class KartustokController extends Controller
{

    public function index()
    {
        $koderuangan = request('koderuangan');
        $bulan = request('bulan');
        $tahun = request('tahun');
        $x = $tahun . '-' . $bulan;
        $tglAwal = $x . '-01';
        $tglAkhir = $x . '-31';
        $dateAwal = Carbon::parse($tglAwal);
        $dateAkhir = Carbon::parse($tglAkhir);
        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m-d');
        $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-d');
        // $date->format('Y-m-d')
        // return new JsonResponse($dateAwal);

        // $ruangan = Ruang::select('uraian')->where('kode', $koderuangan)->first()->uraian ?? null ;
        // $gudang=Gudang::select('nama')->where('kode', $koderuangan)->first()->nama ?? null;

        // $ruang= $ruangan?? $gudang ?? null;

            $list = Mobatnew::query()
            ->select('kd_obat', 'nama_obat', 'satuan_k', 'satuan_b', 'id', 'flag', 'merk', 'kandungan')
            ->with([
                'saldoawal' => function ($saldo) use ($blnLaluAwal, $blnLaluAkhir) {
                    $saldo->whereBetween('tglopname', [$blnLaluAwal . ' 00:00:00', $blnLaluAkhir . ' 23:59:59'])
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', DB::raw('sum(jumlah) as jumlah'))
                        ->groupBy('kdobat','tglopname');
                },
                'fisik' => function ($saldo) use ($tglAwal, $tglAkhir) {
                    $saldo->whereBetween('tglopname', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', 'jumlah');
                },
                'saldoakhir' => function ($saldo) use ($tglAwal, $tglAkhir) {
                    $saldo->whereBetween('tglopname', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', DB::raw('sum(jumlah) as jumlah'))
                        ->groupBy('kdobat','tglopname');
                },
                // untuk ambil penyesuaian stok awal
                'stok' => function ($stok) use ($koderuangan,$tglAwal, $tglAkhir) {
                    $stok->select('id', 'kdobat', 'nopenerimaan', 'nobatch', 'jumlah')
                        ->with([
                            'ssw'=> function ($q) use ($tglAwal, $tglAkhir){
                                $q->whereBetween('tgl_penyesuaian', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                            }
                        ])
                        ->where('kdruang', $koderuangan);
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
                    $q->select(
                        'resep_keluar_r.kdobat',
                        DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                    )
                    ->join('resep_keluar_h', 'resep_keluar_r.noresep', '=', 'resep_keluar_h.noresep')
                    ->when($koderuangan==='Gd-04010103', function($kd){
                        $kd->leftJoin('persiapan_operasi_rincis', function($q){
                            $q->on('persiapan_operasi_rincis.noresep','=','resep_keluar_r.noresep')
                            ->on('persiapan_operasi_rincis.kd_obat','=','resep_keluar_r.kdobat');
                        })
                        ->whereNull('persiapan_operasi_rincis.noresep');
                    })
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_r.jumlah', '>',0)
                        ->whereIn('resep_keluar_h.flag', ['3', '4'])
                        ->groupBy('resep_keluar_r.kdobat');
                        // ->with('retur.rinci');
                },

                'resepkeluarracikan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'resep_keluar_racikan_r.kdobat',
                        DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                    )
                    ->join('resep_keluar_h', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_racikan_r.jumlah', '>',0)
                        ->whereIn('resep_keluar_h.flag', ['3', '4'])
                        ->groupBy('resep_keluar_racikan_r.kdobat');
                        
                        // ->with('retur.rinci');
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
        $tglAwal = $x . '-01';
        $tglAkhir = $x . '-31';
        $dateAwal = Carbon::parse($tglAwal);
        $dateAkhir = Carbon::parse($tglAkhir);
        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m-d');
        $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-d');
        // $date->format('Y-m-d')
        // return new JsonResponse($dateAwal);

        // $ruangan = Ruang::select('uraian')->where('kode', $koderuangan)->first()->uraian ?? null ;
        // $gudang=Gudang::select('nama')->where('kode', $koderuangan)->first()->nama ?? null;

        // $ruang= $ruangan?? $gudang ?? null;

            $list = Mobatnew::query()
            ->select('kd_obat', 'nama_obat', 'satuan_k', 'satuan_b', 'id', 'flag', 'merk', 'kandungan')
            ->with([
                'saldoawal' => function ($saldo) use ($blnLaluAwal, $blnLaluAkhir) {
                    $saldo->whereBetween('tglopname', [$blnLaluAwal . ' 00:00:00', $blnLaluAkhir . ' 23:59:59'])
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', DB::raw('sum(jumlah) as jumlah'))
                        ->groupBy('kdobat','tglopname');
                },
                'fisik' => function ($saldo) use ($tglAwal, $tglAkhir) {
                    $saldo->whereBetween('tglopname', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', 'jumlah');
                },
                'saldoakhir' => function ($saldo) use ($tglAwal, $tglAkhir) {
                    $saldo->whereBetween('tglopname', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', DB::raw('sum(jumlah) as jumlah'))
                        ->groupBy('kdobat','tglopname');
                },
                // untuk ambil penyesuaian stok awal
                'stok' => function ($stok) use ($koderuangan,$tglAwal, $tglAkhir) {
                    $stok->select('id', 'kdobat', 'nopenerimaan', 'nobatch', 'jumlah')
                        ->with([
                            'ssw'=> function ($q) use ($tglAwal, $tglAkhir){
                                $q->whereBetween('tgl_penyesuaian', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                            }
                        ])
                        ->where('kdruang', $koderuangan);
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
                        'permintaan_h.tgl_permintaan as tgl_permintaan',
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
                        'permintaan_h.tgl_permintaan as tgl_permintaan',
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
                    $q->join('resep_keluar_h', 'resep_keluar_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_r.jumlah', '>',0)
                        ->whereIn('resep_keluar_h.flag', ['3', '4'])
                        ->with('retur.rinci');
                        // $q->whereHas('header', function ($x) use ($tglAwal, $tglAkhir, $koderuangan) {
                        //     $x->whereBetween('tgl_selesai', [$tglAwal, $tglAkhir])
                        //     ->where('depo', $koderuangan);
                        // })
                        // ->with('retur.rinci');
                },

                'resepkeluarracikan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->join('resep_keluar_h', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_racikan_r.jumlah', '>',0)
                        ->whereIn('resep_keluar_h.flag', ['3', '4'])
                        // $q->whereHas('header', function ($x) use ($tglAwal, $tglAkhir, $koderuangan) {
                        //     $x->whereBetween('tgl_selesai', [$tglAwal, $tglAkhir])
                        //     ->where('depo', $koderuangan);
                        // })
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
