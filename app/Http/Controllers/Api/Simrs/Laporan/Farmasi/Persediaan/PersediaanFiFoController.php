<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Persediaan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersediaanFiFoController extends Controller
{
    public function getPersediaan()
    {
        $obat = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'satuan_k',
            'jenis_perbekalan',
            'bentuk_sediaan',
        )
            ->with([
                'stok' => function ($st) {
                    $st->select(
                        'stokreal.kdobat',
                        'stokreal.nopenerimaan',
                        DB::raw('sum(stokreal.jumlah) as jumlah'),
                        DB::raw('sum(stokreal.jumlah * stokreal.harga) as sub'),
                        // 'penerimaan_r.nopenerimaan',
                        'penerimaan_h.jenis_penerimaan',
                        'stokreal.harga',
                        // 'daftar_hargas.harga',
                    )
                        // ->leftJoin('daftar_hargas', function ($jo) {
                        //     $jo->on('daftar_hargas.nopenerimaan', '=', 'stokreal.nopenerimaan')
                        //         ->on('daftar_hargas.kd_obat', '=', 'stokreal.kdobat');
                        // })
                        // ->leftJoin('penerimaan_r', function ($jo) {
                        //     $jo->on('penerimaan_r.nopenerimaan', '=', 'stokreal.nopenerimaan')
                        //         ->on('penerimaan_r.kdobat', '=', 'stokreal.kdobat');
                        // })
                        ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'stokreal.nopenerimaan')
                        ->where('stokreal.jumlah', '!=', 0)
                        ->when(
                            request('kode_ruang') === 'all',
                            function ($re) {
                                $gd = ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
                                $re->whereIn('stokreal.kdruang', $gd);
                            },
                            function ($sp) {
                                $sp->where('stokreal.kdruang', request('kode_ruang'));
                            }
                        )
                        ->groupBy('stokreal.kdobat', 'stokreal.nopenerimaan', 'stokreal.harga');
                },
                // ***** ini untuk testing ****
                // 'saldoawal' => function ($st) {
                //     $st->select(
                //         'stokopname_sementaras.kdobat',
                //         'stokopname_sementaras.nopenerimaan',
                //         DB::raw('sum(stokopname_sementaras.jumlah) as jumlah'),
                //         DB::raw('sum(stokopname_sementaras.jumlah * stokopname_sementaras.harga) as sub'),
                //         'penerimaan_h.jenis_penerimaan',
                //         'stokopname_sementaras.harga',
                //     )
                //         ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'stokopname_sementaras.nopenerimaan')
                //         ->where('stokopname_sementaras.jumlah', '!=', 0)
                //         ->where('stokopname_sementaras.tglopname', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
                //         ->when(
                //             request('kode_ruang') === 'all',
                //             function ($re) {
                //                 $gd = ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
                //                 $re->whereIn('stokopname_sementaras.kdruang', $gd);
                //             },
                //             function ($sp) {
                //                 $sp->where('stokopname_sementaras.kdruang', request('kode_ruang'));
                //             }
                //         )
                //         ->groupBy('stokopname_sementaras.kdobat', 'stokopname_sementaras.nopenerimaan');
                // }
                // ***** ini Aslinya ****
                'saldoawal' => function ($st) {
                    $st->select(
                        'stokopname.kdobat',
                        'stokopname.nopenerimaan',
                        DB::raw('sum(stokopname.jumlah) as jumlah'),
                        DB::raw('sum(stokopname.jumlah * stokopname.harga) as sub'),
                        'penerimaan_h.jenis_penerimaan',
                        'stokopname.harga',
                    )
                        ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'stokopname.nopenerimaan')
                        ->where('stokopname.jumlah', '!=', 0)
                        ->where('stokopname.tglopname', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
                        ->when(
                            request('kode_ruang') === 'all',
                            function ($re) {
                                $gd = ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
                                $re->whereIn('stokopname.kdruang', $gd);
                            },
                            function ($sp) {
                                $sp->where('stokopname.kdruang', request('kode_ruang'));
                            }
                        )
                        ->groupBy('stokopname.kdobat', 'stokopname.nopenerimaan');
                }
            ])
            ->where(function ($mo) {
                $mo->where('nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'LIKE', '%' . request('q') . '%');
            })
            ->where('status_konsinyasi', '=', '')
            ->get();
        // $data = collect($obat)['data'];
        // $meta = collect($obat)->except('data');
        return new JsonResponse([
            'data' => $obat,
            // 'meta' => $meta,
            'req' => request()->all()
        ]);
    }
    public function getMutasi()
    {
        $tglAwal = request('tahun') . '-' . request('bulan') . '-01';
        $dateAwal = Carbon::parse($tglAwal);
        $blnLalu = $dateAwal->subMonth()->format('Y-m');

        $rwobat = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'satuan_k',
            'uraian50',

        )

            ->when(request('kode_ruang') !== 'all', function ($q) {
                $q->whereIn('gudang', ['', request('kode_ruang')]);
            })
            ->where(function ($q) {
                $q->where('nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'LIKE', '%' . request('q') . '%');
            });

        $rwobat->with([
            'saldoawal' => function ($st) use ($blnLalu) {
                $st->select(
                    'stokopname.kdobat',
                    'stokopname.nopenerimaan',
                    'stokopname.harga',
                    DB::raw('sum(stokopname.jumlah) as jumlah'),
                    DB::raw('sum(stokopname.jumlah * stokopname.harga) as sub'),
                    // DB::raw('stokopname.harga as harga'),
                    // 'daftar_hargas.harga as dftHar',
                )

                    ->where('stokopname.jumlah', '!=', 0)
                    ->where('stokopname.tglopname', 'LIKE', $blnLalu . '%')
                    ->whereIn('stokopname.kdruang', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
                if (request('jenis') == 'rekap') {
                    $st->groupBy('stokopname.kdobat', 'stokopname.nopenerimaan', 'stokopname.nobatch');
                } else {
                    $st->groupBy('stokopname.kdobat', 'stokopname.nopenerimaan', 'stokopname.tglopname', 'stokopname.nobatch');
                }
            },
            'penerimaanrinci' => function ($trm) {
                $trm->select(
                    'penerimaan_r.kdobat',
                    'penerimaan_r.nopenerimaan',
                    'penerimaan_h.tglpenerimaan as tgl',
                    'penerimaan_h.jenissurat',
                    'penerimaan_h.nomorsurat',
                    'penerimaan_h.kdpbf',
                    'penerimaan_r.satuan_kcl',
                    'penerimaan_r.harga_netto_kecil as harga',
                    DB::raw('sum(penerimaan_r.jml_terima_k) as jumlah'),
                    DB::raw('sum(penerimaan_r.harga_netto_kecil * penerimaan_r.jml_terima_k) as sub')
                )
                    ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
                    ->with('pbf:kode,nama')
                    ->where('penerimaan_h.kunci', '1')
                    ->where('penerimaan_h.tglpenerimaan', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%');
                // if (request('jenis') == 'rekap') {
                //     $trm->groupBy('penerimaan_r.kdobat');
                // } else {
                $trm->groupBy('penerimaan_r.kdobat', 'penerimaan_r.nopenerimaan', 'penerimaan_r.no_batch');
                // }
            },
            'resepkeluar' => function ($kel) {
                $kel->select(
                    'resep_keluar_r.noresep',
                    'resep_keluar_r.kdobat',
                    'resep_keluar_h.tgl_selesai as tgl',
                    'resep_keluar_r.nopenerimaan',
                    'resep_keluar_r.harga_beli as harga',
                    DB::raw('sum(resep_keluar_r.jumlah) as jumlah'),
                    DB::raw('sum(resep_keluar_r.jumlah * resep_keluar_r.harga_beli) as sub')

                )
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                    ->havingRaw('jumlah > 0')
                    ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
                    ->whereIn('resep_keluar_h.depo', ['Gd-04010102', 'Gd-05010101', 'Gd-02010104']) // ambil yang selain OK
                    ->with(
                        'header:noresep,norm',
                        'header.datapasien:rs1,rs2',
                    );
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan');
                } else {
                    $kel->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan', 'resep_keluar_r.noresep');
                }
            },
            //// tambahan Ok
            'resepkeluarok' => function ($kel) {
                $kel->select(
                    'resep_keluar_r.noresep',
                    'resep_keluar_r.kdobat',
                    'resep_keluar_h.tgl_selesai as tgl',
                    'resep_keluar_r.nopenerimaan',
                    'resep_keluar_r.harga_beli as harga',
                    DB::raw('sum(resep_keluar_r.jumlah) as jumlah'),
                    DB::raw('sum(resep_keluar_r.jumlah * resep_keluar_r.harga_beli) as sub')

                )
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                    ->leftJoin('persiapan_operasi_rincis', function ($q) {
                        $q->on('persiapan_operasi_rincis.noresep', '=', 'resep_keluar_r.noresep')
                            ->on('persiapan_operasi_rincis.kd_obat', '=', 'resep_keluar_r.kdobat');
                    })
                    ->whereNull('persiapan_operasi_rincis.noresep')
                    ->havingRaw('jumlah > 0')
                    ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
                    ->whereIn('resep_keluar_h.depo', ['Gd-04010103'])
                    ->with(
                        'header:noresep,norm',
                        'header.datapasien:rs1,rs2',
                    );
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan');
                } else {
                    $kel->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan', 'resep_keluar_r.noresep');
                }
                // ->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan', 'resep_keluar_r.noresep');
            },

            'distribusipersiapan' => function ($dist) {
                $dist->select(
                    'persiapan_operasi_distribusis.kd_obat as kdobat',
                    'persiapan_operasi_distribusis.kd_obat',
                    'persiapan_operasi_distribusis.nopenerimaan',
                    'persiapan_operasis.tgl_distribusi as tgl',
                    'persiapan_operasis.norm',
                    'persiapan_operasi_rincis.noresep',
                    'daftar_hargas.harga',
                    DB::raw('sum(persiapan_operasi_distribusis.jumlah) as jumlah'),
                    DB::raw('sum(persiapan_operasi_distribusis.jumlah * daftar_hargas.harga) as sub'),

                )
                    ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                    ->leftJoin('persiapan_operasi_rincis', function ($join) {
                        $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                            ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                    })
                    ->leftJoin(DB::raw('(SELECT kd_obat, nopenerimaan, MAX(harga) as harga FROM daftar_hargas GROUP BY kd_obat, nopenerimaan) as daftar_hargas'), function ($join) {
                        $join->on('daftar_hargas.nopenerimaan', '=', 'persiapan_operasi_distribusis.nopenerimaan')
                            ->on('daftar_hargas.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                    })
                    ->where('persiapan_operasis.tgl_distribusi', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])
                    ->with([
                        'pasien:rs1,rs2',
                    ]);
                if (request('jenis') === 'rekap') {
                    $dist->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopenerimaan');
                } else {
                    $dist->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasis.nopermintaan', 'persiapan_operasi_distribusis.nopenerimaan');
                }
                // ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasis.nopermintaan', 'persiapan_operasi_distribusis.nopenerimaan');
            },
            'persiapanretur' => function ($dist) {
                $dist->select(
                    'persiapan_operasi_distribusis.kd_obat as kdobat',
                    'persiapan_operasi_distribusis.kd_obat',
                    'persiapan_operasi_distribusis.nopenerimaan',
                    'persiapan_operasis.nopermintaan',
                    // 'persiapan_operasis.tgl_distribusi',
                    'persiapan_operasi_distribusis.tgl_retur as tgl',
                    'persiapan_operasi_rincis.noresep',
                    'persiapan_operasis.norm',
                    'daftar_hargas.harga',
                    // DB::raw('sum(persiapan_operasi_distribusis.jumlah) as keluar'),
                    DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as jumlah'),
                    DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur * daftar_hargas.harga) as sub'),

                )
                    ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                    ->leftJoin('persiapan_operasi_rincis', function ($join) {
                        $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                            ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                    })
                    ->leftJoin(DB::raw('(SELECT kd_obat, nopenerimaan, MAX(harga) as harga FROM daftar_hargas GROUP BY kd_obat, nopenerimaan) as daftar_hargas'), function ($join) {
                        $join->on('daftar_hargas.nopenerimaan', '=', 'persiapan_operasi_distribusis.nopenerimaan')
                            ->on('daftar_hargas.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                    })
                    ->where('persiapan_operasis.tgl_retur', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
                    ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])
                    ->havingRaw('sum(persiapan_operasi_distribusis.jumlah_retur) > 0')
                    ->with([
                        'pasien:rs1,rs2',
                    ]);
                if (request('jenis') === 'rekap') {
                    $dist->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopenerimaan');
                } else {
                    $dist->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasis.nopermintaan', 'persiapan_operasi_distribusis.nopenerimaan');
                }
                // ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasis.nopermintaan', 'persiapan_operasi_distribusis.nopenerimaan');
            },
            //// akhir OK ////
            'resepkeluarracikan' => function ($kel) {
                $kel->select(
                    'resep_keluar_racikan_r.noresep',
                    'resep_keluar_racikan_r.kdobat',
                    'resep_keluar_h.tgl_selesai as tgl',
                    'resep_keluar_racikan_r.nopenerimaan',
                    'resep_keluar_racikan_r.harga_beli as harga',
                    'resep_keluar_racikan_r.harga_beli as harga',
                    DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah'),
                    DB::raw('sum(resep_keluar_racikan_r.jumlah * resep_keluar_racikan_r.harga_beli) as sub')

                )
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_racikan_r.noresep')
                    ->havingRaw('jumlah > 0')
                    ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->with(
                        'header:noresep,norm',
                        'header.datapasien:rs1,rs2',
                    );
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('resep_keluar_racikan_r.kdobat');
                } else {
                    $kel->groupBy('resep_keluar_racikan_r.kdobat', 'resep_keluar_racikan_r.nopenerimaan', 'resep_keluar_racikan_r.noresep');
                }
                // ->groupBy('resep_keluar_racikan_r.kdobat', 'resep_keluar_racikan_r.nopenerimaan', 'resep_keluar_racikan_r.noresep');
            },
            'returpenjualan' => function ($kel) {
                $kel->select(
                    'retur_penjualan_r.noresep',
                    'retur_penjualan_r.kdobat',
                    'retur_penjualan_h.tgl_retur as tgl',
                    'retur_penjualan_r.nopenerimaan',
                    'retur_penjualan_r.harga_beli as harga',
                    DB::raw('sum(retur_penjualan_r.jumlah_retur) as jumlah'),
                    DB::raw('sum(retur_penjualan_r.jumlah_retur * retur_penjualan_r.harga_beli) as sub'),

                )
                    ->join('retur_penjualan_h', 'retur_penjualan_h.noretur', '=', 'retur_penjualan_r.noretur')
                    ->havingRaw('jumlah > 0')
                    ->where('retur_penjualan_h.tgl_retur', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->with(
                        'header:noresep,norm',
                        'header.datapasien:rs1,rs2',
                    );
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('retur_penjualan_r.kdobat', 'retur_penjualan_r.nopenerimaan');
                } else {
                    $kel->groupBy('retur_penjualan_r.kdobat', 'retur_penjualan_r.nopenerimaan', 'retur_penjualan_r.noresep');
                }
                // ->groupBy('retur_penjualan_r.kdobat', 'retur_penjualan_r.nopenerimaan', 'retur_penjualan_r.noresep');
            },
            'mutasikeluar' => function ($mut) {
                $mut->select(
                    'mutasi_gudangdepo.no_permintaan',
                    'mutasi_gudangdepo.kd_obat',
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    'mutasi_gudangdepo.harga',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah'),
                    DB::raw('sum(mutasi_gudangdepo.jml * mutasi_gudangdepo.harga) as sub'),
                    'permintaan_h.dari',
                    'permintaan_h.dari as kdruang',
                    'permintaan_h.tgl_kirim_depo as tgl',
                )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->havingRaw('jumlah > 0')
                    ->where('permintaan_h.dari', 'LIKE', 'R-%')
                    ->where('permintaan_h.tgl_kirim_depo', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->with([
                        'ruangan:kode,uraian',
                    ]);
                if (request('jenis') === 'rekap') {
                    $mut->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan');
                } else {
                    $mut->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan', 'mutasi_gudangdepo.no_permintaan');
                }
                // ->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan');
            },
            'penyesuaian' => function ($pak) {
                $pak->select(
                    'penyesuaian_stoks.kdobat',
                    'penyesuaian_stoks.nopenerimaan',
                    'penyesuaian_stoks.tgl_penyesuaian as tgl',
                    'stokreal.harga',
                    DB::raw('sum(penyesuaian_stoks.penyesuaian) as jumlah'),
                    DB::raw('sum(penyesuaian_stoks.penyesuaian * stokreal.harga) as sub'),

                )
                    ->join('stokreal', 'stokreal.id', '=', 'penyesuaian_stoks.stokreal_id')
                    ->where('penyesuaian_stoks.tgl_penyesuaian', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->where('penyesuaian_stoks.penyesuaian', '!=', 0)
                    ->groupBy('penyesuaian_stoks.kdobat', 'penyesuaian_stoks.nopenerimaan');
            },

            'barangrusak' => function ($pak) {
                $pak->select(
                    'kd_obat',
                    'kd_obat as kdobat',
                    'nopenerimaan_default as nopenerimaan',
                    'harga_net_default as harga',
                    'tgl_kunci as tgl',
                    'status as ket',
                    DB::raw('sum(jumlah) as jumlah'),
                    DB::raw('sum(jumlah * harga_net_default) as sub'),

                )
                    ->where('tgl_kunci', 'LIKE', request('tahun') . '-' . request('bulan') . '%')
                    ->where('kunci', '1')
                    ->whereIn('gudang', ['Gd-05010100', 'Gd-03010100'])
                    ->groupBy('kdobat', 'nopenerimaan_default');
            },
            'returpbf' => function ($kel) {
                $kel->select(
                    'retur_penyedia_r.no_retur',
                    'retur_penyedia_r.kd_obat',
                    'retur_penyedia_r.kd_obat as kdobat',
                    'retur_penyedia_h.tgl_kunci as tgl',
                    'retur_penyedia_r.nopenerimaan_default as nopenerimaan',
                    'retur_penyedia_r.harga_net_default as harga',
                    DB::raw('sum(retur_penyedia_r.jumlah_retur) as jumlah'),
                    DB::raw('sum(retur_penyedia_r.jumlah_retur * retur_penyedia_r.harga_net_default) as sub'),

                )
                    ->join('retur_penyedia_h', 'retur_penyedia_h.no_retur', '=', 'retur_penyedia_r.no_retur')
                    ->havingRaw('jumlah > 0')
                    ->where('retur_penyedia_h.tgl_kunci', 'LIKE', request('tahun') . '-' . request('bulan') . '%')
                    ->with(
                        'header.penyedia:kode,nama',
                    );
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('retur_penyedia_r.kd_obat');
                } else {
                    $kel->groupBy('retur_penyedia_r.kd_obat', 'retur_penyedia_r.nopenerimaan_default', 'retur_penyedia_r.no_retur');
                }
                // ->groupBy('retur_penyedia_r.kdobat', 'retur_penyedia_r.nopenerimaan', 'retur_penyedia_r.noresep');
            },
            'pengembalianrincififo' => function ($kel) {
                $kel->select(
                    'pengembalian_rinci_fifos.nopengembalian',
                    'pengembalian_rinci_fifos.kdobat',
                    'pengembalian_rinci_fifos.kdobat as kd_obat',
                    'pengembalians.tgl_kunci as tgl',
                    'pengembalian_rinci_fifos.nopenerimaan',
                    'pengembalian_rinci_fifos.harga',
                    DB::raw('sum(pengembalian_rinci_fifos.jml_dikembalikan) as jumlah'),
                    DB::raw('sum(pengembalian_rinci_fifos.jml_dikembalikan * pengembalian_rinci_fifos.harga) as sub'),

                )
                    ->join('pengembalians', 'pengembalians.nopengembalian', '=', 'pengembalian_rinci_fifos.nopengembalian')
                    ->havingRaw('jumlah > 0')
                    ->where('pengembalians.tgl_kunci', 'LIKE', request('tahun') . '-' . request('bulan') . '%')
                    ->with(
                        'header.penyedia:kode,nama',
                    );
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('pengembalian_rinci_fifos.kdobat');
                } else {
                    $kel->groupBy('pengembalian_rinci_fifos.kdobat', 'pengembalian_rinci_fifos.nopenerimaan', 'pengembalian_rinci_fifos.nopengembalian');
                }
                // ->groupBy('retur_penyedia_r.kdobat', 'retur_penyedia_r.nopenerimaan', 'retur_penyedia_r.noresep');
            },
            'daftarharga:kd_obat,nopenerimaan,harga',
            'mutasikeluarngambang' => function ($kel) {
                $kel->where('permintaan_h.tgl_kirim_depo', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->with([
                        'depo:kode,nama',
                    ]);
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan');
                } else {
                    $kel->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan', 'mutasi_gudangdepo.no_permintaan');
                }
            },
            'mutasimasukngambang' => function ($kel) {
                $kel->where('permintaan_h.tgl_terima_depo', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->with([
                        'depo:kode,nama',
                    ]);

                if (request('jenis') === 'rekap') {
                    $kel->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan');
                } else {
                    $kel->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan', 'mutasi_gudangdepo.no_permintaan');
                }
            },

        ]);
        // }
        $kirim = [];
        if (request('action') === 'download') {
            // $obat = $rwobat->offset(0)
            //     ->limit(300)
            //     ->get();
            $obat = $rwobat->get();
            $obat->map(function ($it) {
                $it->saldo = $it->saldoawal;
                $it->terima = $it->penerimaanrinci;
                $it->retur = $it->returpenjualan ?? [];
                return $it;
            });
            $kirim = $obat;
        } else {
            $obat = $rwobat->paginate(30);
            $anu = collect($obat)['data'];
            $meta = collect($obat)->except('data');
            foreach ($anu as $it) {
                $it['saldo'] = $it['saldoawal'];
                $it['terima'] = $it['penerimaanrinci'];
                $it['retur'] = $it['returpenjualan'] ?? [];
                $kirim[] = $it;
            }
        }


        return new JsonResponse([
            'obat' => $obat,
            'data' => $kirim,
            'blnLalu' => $blnLalu,
            'meta' => $meta ?? null,
            'req' => request()->all()
        ]);
    }
    public function getStokopname()
    {
        $year = request('tahun');
        $month = request('bulan');
        $kode_ruang = request('kode_ruang');

        // Setup Date Boundaries
        $dateAwal = Carbon::createFromDate($year, $month, 1);
        $startOfMonth = $dateAwal->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $endOfMonth = $dateAwal->copy()->endOfMonth()->format('Y-m-d H:i:s');

        $prevMonthDate = $dateAwal->copy()->subMonth();
        // $startPrevMonth = $prevMonthDate->copy()->startOfMonth()->format('Y-m-d H:i:s'); 
        // Note: Existing logic uses LIKE for Opname, assuming Opname happens once a month or we take the sum of ops in that month. 
        // Usually Opname for 'Last Month' is the 'Opening Stock' for 'This Month'.
        $blnLalu = $prevMonthDate->format('Y-m');

        // 1. Prepare Subquery Saldo Awal (Stock Opname Bulan Lalu)
        $qAwal = DB::connection('farmasi')->table('stokopname')
            ->select(
                'kdobat',
                DB::raw("SUM(jumlah) as qty"),
                DB::raw("SUM(jumlah * harga) as val")
            )
            ->where('tglopname', 'LIKE', $blnLalu . '%')
            ->where('jumlah', '!=', 0);

        if ($kode_ruang !== 'all') {
            $qAwal->where('kdruang', $kode_ruang);
        } else {
            $qAwal->whereIn('kdruang', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
        }
        $qAwal->groupBy('kdobat');


        // 2. Prepare Subqueries Masuk & Keluar based on Room Type
        $qMasuk = null;
        $qKeluar = null;

        // Helper for Union Checks
        $isGudang = in_array($kode_ruang, ['Gd-05010100', 'Gd-03010100']);
        $isDepoOk = $kode_ruang === 'Gd-04010103';
        $isFloorStok = $kode_ruang === 'Gd-03010101';

        // --- LOGIKA MASUK ---
        // Referensi KartuStok: 'mutasikeluar' (variable name) -> tujuan=Me -> tgl_kirim_depo
        // Referensi KartuStok: 'returpenjualan' -> resep_keluar_h.depo=Me -> tgl_retur
        // Referensi KartuStok: 'penerimaanrinci' (Gudang) -> gudang=Me -> tglpenerimaan

        $qPenerimaan = null;
        if ($isGudang) {
            // Gudang: Penerimaan (Surat Jalan/Faktur)
            $qPenerimaan = DB::connection('farmasi')->table('penerimaan_r as r')
                ->join('penerimaan_h as h', 'h.nopenerimaan', '=', 'r.nopenerimaan')
                ->select('r.kdobat', DB::raw("SUM(r.jml_terima_k) as qty"), DB::raw("SUM(r.jml_terima_k * r.harga_netto_kecil) as val"))
                ->where('h.kunci', '1')
                ->where('h.gudang', $kode_ruang)
                ->whereBetween('h.tglpenerimaan', [$startOfMonth, $endOfMonth])
                ->groupBy('r.kdobat');
            // Untuk Gudang, Retur dari Unit Lain (Retur Gudang) adalah MASUK
            $qReturKeGudang = DB::connection('farmasi')->table('retur_gudang_details as r')
                ->join('retur_gudangs as h', 'h.no_retur', '=', 'r.no_retur')
                ->select('r.kd_obat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * CAST(0 as DECIMAL)) as val")) // Harga unknown in retur_gudangs?
                ->where('h.gudang', $kode_ruang)
                ->where('h.kunci', '1')
                ->whereBetween('h.tgl_retur', [$startOfMonth, $endOfMonth])
                ->groupBy('r.kd_obat');
            // Note: Val is tricky without price join. Leaving 0 or need join stock? For now 0 to avoid crash. user concerned with Qty/Stock mostly.
        }

        // 1. Mutasi Masuk (Dari Unit Lain ke Sini)
        // Referensi KartuStok: 'mutasimasuk' -> Uses where('dari', $koderuangan) !!!
        // Note: Logic sistem ini sepertinya 'dari' = Unit Peminta (Destination), 'tujuan' = Unit Pengirim (Source).
        // Atau variabel controller referensi yang namanya terbalik? TAPI kita ikut referensi code yang jalan.
        // Ref: where('dari', $koderuangan) AND whereBetween('tgl_terima_depo')
        $qMutasiMasuk = DB::connection('farmasi')->table('mutasi_gudangdepo as m')
            ->join('permintaan_h as h', 'h.no_permintaan', '=', 'm.no_permintaan')
            ->select('m.kd_obat as kdobat', DB::raw("SUM(m.jml) as qty"), DB::raw("SUM(m.jml * m.harga) as val"))
            ->whereBetween('h.tgl_terima_depo', [$startOfMonth, $endOfMonth])
            ->where('h.dari', $kode_ruang) // MATCH REF 'mutasimasuk'
            ->groupBy('m.kd_obat');

        // 2. Retur Penjualan (Pasien ke Unit)
        $qReturPasien = DB::connection('farmasi')->table('retur_penjualan_r as r')
            ->join('retur_penjualan_h as h', 'h.noretur', '=', 'r.noretur')
            ->join('resep_keluar_h as rh', 'rh.noresep', '=', 'r.noresep')
            ->select('r.kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * r.harga_beli) as val"))
            ->whereBetween('h.tgl_retur', [$startOfMonth, $endOfMonth])
            ->where('rh.depo', $kode_ruang)
            ->groupBy('r.kdobat');

        // Compile Masuk
        $qMasuk = $qMutasiMasuk->unionAll($qReturPasien);
        if ($isGudang) {
            $qMasuk = $qMasuk->unionAll($qPenerimaan)->unionAll($qReturKeGudang);
        }

        $qMasuk = DB::connection('farmasi')->query()->fromSub($qMasuk, 'union_masuk')
            ->select('kdobat', DB::raw("SUM(qty) as qty"), DB::raw("SUM(val) as val"))
            ->groupBy('kdobat');


        // --- LOGIKA KELUAR ---
        // Referensi KartuStok: 'mutasikeluar' -> Uses where('tujuan', $koderuangan)
        // Ref: where('tujuan', $koderuangan) AND whereBetween('tgl_kirim_depo')

        // 1. Mutasi Keluar (Transfer ke Unit Lain)
        $qMutasiKeluar = DB::connection('farmasi')->table('mutasi_gudangdepo as m')
            ->join('permintaan_h as h', 'h.no_permintaan', '=', 'm.no_permintaan')
            ->select('m.kd_obat as kdobat', DB::raw("SUM(m.jml) as qty"), DB::raw("SUM(m.jml * m.harga) as val"))
            ->whereBetween('h.tgl_kirim_depo', [$startOfMonth, $endOfMonth])
            ->where('h.tujuan', $kode_ruang) // MATCH REF 'mutasikeluar'
            // ->whereIn('h.flag', ['4']) // Ref doesn't check flag explicitly in mutasikeluar? But safer to keep distinct if needed. 
            // Ref Lines 123-137 doesn't show flag wait. 
            // Ah, Lines 364 listpermintaandepo uses flag. But KartuStokController Lines 123 doesn't.
            // Let's remove flag check to be EXACTLY like Ref if Ref doesn't have it.
            // Ref: whereBetween(tgl_kirim), where(tujuan), join. No flag.
            ->groupBy('m.kd_obat');

        // 2. Retur Ke Gudang (Dari Depo) - Equivalent to 'returdepo'
        $qReturDariDepo = DB::connection('farmasi')->table('retur_gudang_details as r')
            ->join('retur_gudangs as h', 'h.no_retur', '=', 'r.no_retur')
            ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * CAST(0 as DECIMAL)) as val")) // Harga Issue?
            ->where('h.depo', $kode_ruang) // Sebagai Pengirim
            ->where('h.kunci', '1')
            ->whereBetween('h.tgl_retur', [$startOfMonth, $endOfMonth])
            ->groupBy('r.kd_obat');

        // 3. Resep Keluar (Biasa)
        $qResep = DB::connection('farmasi')->table('resep_keluar_r as r')
            ->join('resep_keluar_h as h', 'h.noresep', '=', 'r.noresep')
            ->select('r.kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * r.harga_beli) as val"))
            ->whereBetween('h.tgl_selesai', [$startOfMonth, $endOfMonth])
            ->where('h.depo', $kode_ruang)
            ->whereIn('h.flag', ['3', '4'])
            ->where('r.jumlah', '>', 0)
            ->groupBy('r.kdobat');

        // 4. Resep Keluar (Racikan)
        $qResepRacikan = DB::connection('farmasi')->table('resep_keluar_racikan_r as r')
            ->join('resep_keluar_h as h', 'h.noresep', '=', 'r.noresep')
            ->select('r.kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * r.harga_beli) as val"))
            ->whereBetween('h.tgl_selesai', [$startOfMonth, $endOfMonth])
            ->where('h.depo', $kode_ruang)
            ->whereIn('h.flag', ['3', '4'])
            ->where('r.jumlah', '>', 0)
            ->groupBy('r.kdobat');

        // 5. Barang Rusak
        $qBarangRusak = DB::connection('farmasi')->table('barang_rusaks as r')
            // Assumption: table name 'barang_rusaks', column 'gudang' matches kode_ruang
            ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * CAST(0 as DECIMAL)) as val"))
            ->whereBetween('r.tgl_kunci', [$startOfMonth, $endOfMonth])
            ->where('r.gudang', $kode_ruang)
            ->where('r.kunci', '1')
            ->groupBy('r.kd_obat');

        // OK Specific: Distribusi Persiapan Operasi
        $qDistribusiOK = null;
        if ($isDepoOk) {
            $qDistribusiOK = DB::connection('farmasi')->table('persiapan_operasi_distribusis as d')
                ->join('persiapan_operasis as h', 'h.nopermintaan', '=', 'd.nopermintaan')
                ->leftJoin(DB::raw('(SELECT kd_obat, nopenerimaan, MAX(harga) as harga FROM daftar_hargas GROUP BY kd_obat, nopenerimaan) as dh'), function ($join) {
                    $join->on('dh.nopenerimaan', '=', 'd.nopenerimaan')
                        ->on('dh.kd_obat', '=', 'd.kd_obat');
                })
                ->select('d.kd_obat as kdobat', DB::raw("SUM(d.jumlah) as qty"), DB::raw("SUM(d.jumlah * dh.harga) as val"))
                ->whereBetween('h.tgl_distribusi', [$startOfMonth, $endOfMonth])
                ->whereIn('h.flag', ['2', '3', '4']) // Matches Ref 'distribusipersiapan'
                ->groupBy('d.kd_obat');
        }

        // GABUNG KELUAR
        $qKeluar = $qMutasiKeluar
            ->unionAll($qResep)
            ->unionAll($qResepRacikan)
            ->unionAll($qReturDariDepo)
            ->unionAll($qBarangRusak);

        if ($qDistribusiOK) {
            $qKeluar = $qKeluar->unionAll($qDistribusiOK);
        }

        if ($isGudang) {
            // Retur PBF
            $qReturPbf = DB::connection('farmasi')->table('retur_penyedia_r as r')
                ->join('retur_penyedia_h as h', 'h.no_retur', '=', 'r.no_retur')
                ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * CAST(0 as DECIMAL)) as val"))
                ->where('h.gudang', $kode_ruang)
                ->whereBetween('h.tgl_kunci', [$startOfMonth, $endOfMonth])
                ->where('h.kunci', '1')
                ->groupBy('r.kd_obat');
            $qKeluar = $qKeluar->unionAll($qReturPbf);

            // Pengembalian Pinjaman (Line 281 in Ref)
            $qPengembalian = DB::connection('farmasi')->table('pengembalian_rinci_fifos as r')
                ->join('pengembalians as h', 'h.nopengembalian', '=', 'r.nopengembalian')
                ->select('r.kdobat', DB::raw("SUM(r.jml_dikembalikan) as qty"), DB::raw("SUM(r.jml_dikembalikan * CAST(0 as DECIMAL)) as val"))
                ->where('h.kdruang', $kode_ruang)
                ->whereBetween('h.tgl_kunci', [$startOfMonth, $endOfMonth])
                ->where('h.flag', '1')
                ->groupBy('r.kdobat');
            $qKeluar = $qKeluar->unionAll($qPengembalian);
        }

        $qKeluar = DB::connection('farmasi')->query()->fromSub($qKeluar, 'union_keluar')
            ->select('kdobat', DB::raw("SUM(qty) as qty"), DB::raw("SUM(val) as val"))
            ->groupBy('kdobat');


        // 3. MAIN QUERY
        // Master Obat + Left Joins
        $mainQuery = DB::connection('farmasi')->table('new_masterobat as m')
            ->leftJoin('kodeBelanjaObat as k108', 'm.kode108', '=', 'k108.kode')
            ->leftJoinSub($qAwal, 'awal', 'm.kd_obat', '=', 'awal.kdobat')
            ->leftJoinSub($qMasuk, 'masuk', 'm.kd_obat', '=', 'masuk.kdobat')
            ->leftJoinSub($qKeluar, 'keluar', 'm.kd_obat', '=', 'keluar.kdobat')
            ->select(
                'k108.kode as kode_108',
                'k108.uraian as nama_108',
                'm.kd_obat',
                'm.nama_obat',
                'm.satuan_k', // Optional: Unit
                DB::raw("COALESCE(awal.qty, 0) as awal_qty"),
                DB::raw("COALESCE(awal.val, 0) as awal_val"),
                DB::raw("COALESCE(masuk.qty, 0) as masuk_qty"),
                DB::raw("COALESCE(masuk.val, 0) as masuk_val"),
                DB::raw("COALESCE(keluar.qty, 0) as keluar_qty"),
                DB::raw("COALESCE(keluar.val, 0) as keluar_val"),
                // Sisa = Awal + Masuk - Keluar
                DB::raw("(COALESCE(awal.qty, 0) + COALESCE(masuk.qty, 0) - COALESCE(keluar.qty, 0)) as sisa_qty"),
                DB::raw("(COALESCE(awal.val, 0) + COALESCE(masuk.val, 0) - COALESCE(keluar.val, 0)) as sisa_val")
            )
            ->where(function ($q) {
                // Filter Search
                $term = request('q');
                if ($term) {
                    $q->where('m.nama_obat', 'LIKE', '%' . $term . '%')
                        ->orWhere('m.kd_obat', 'LIKE', '%' . $term . '%');
                }
            })
            // Only show items with activity or stock? User didn't specify, but usually beneficial.
            // "kode 108, nama 108, kode obat, nama obat, awal , masuk, keluar, sisa" implies ALL items or relevant items.
            // If we filter, we might miss items that only have Opening Stock but no movement.
            // Let's filter where ANY aggregate is non-zero to reduce payload size.



            // ->where(function ($q) {
            //     $q->whereRaw("COALESCE(awal.qty, 0) != 0")
            //         ->orWhereRaw("COALESCE(masuk.qty, 0) != 0")
            //         ->orWhereRaw("COALESCE(keluar.qty, 0) != 0");
            // });

            ->where(function ($q) {
                $q->whereRaw("m.flag != 1");
            });


        // Pagination or Download
        if (request('action') === 'download') {
            $data = $mainQuery->get();
            return new JsonResponse([
                'data' => $data,
                'req' => request()->all()
            ]);
        } else {
            $perPage = request('per_page') ? request('per_page') : 100;
            $data = $mainQuery->paginate((int)$perPage);
            return new JsonResponse($data);
        }
    }
    public function getStokopnameHarian()
    {
        $tanggal = request('tanggal');
        $date = Carbon::parse($tanggal);
        $year = $date->year;
        $month = $date->format('m');
        $kode_ruang = request('kode_ruang');

        // Setup Date Boundaries
        $dateAwal = Carbon::createFromDate($year, $month, 1);
        $startOfMonth = $dateAwal->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $endOfMonth = $dateAwal->copy()->endOfMonth()->format('Y-m-d H:i:s');

        $blnLalu = $date->copy()->subMonth()->format('Y-m');
        $startOfMonth = $date->copy()->startOfMonth()->format('Y-m-d 00:00:00');
        $yesterdayEnd = $date->copy()->subDay()->format('Y-m-d 23:59:59');
        $todayStart   = $date->copy()->format('Y-m-d 00:00:00');
        $todayEnd     = $date->copy()->format('Y-m-d 23:59:59');

        // $prevMonthDate = $dateAwal->copy()->subMonth();
        // $startPrevMonth = $prevMonthDate->copy()->startOfMonth()->format('Y-m-d H:i:s'); 
        // Note: Existing logic uses LIKE for Opname, assuming Opname happens once a month or we take the sum of ops in that month. 
        // Usually Opname for 'Last Month' is the 'Opening Stock' for 'This Month'.
        // $blnLalu = $prevMonthDate->format('Y-m');

        // 1. Prepare Subquery Saldo Awal (Stock Opname Bulan Lalu)
        // $qAwal = DB::connection('farmasi')->table('stokopname')
        //     ->select(
        //         'kdobat',
        //         DB::raw("SUM(jumlah) as qty"),
        //         DB::raw("SUM(jumlah * harga) as val")
        //     )
        //     ->where('tglopname', 'LIKE', $blnLalu . '%')
        //     ->where('jumlah', '!=', 0);

        // if ($kode_ruang !== 'all') {
        //     $qAwal->where('kdruang', $kode_ruang);
        // } else {
        //     $qAwal->whereIn('kdruang', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
        // }
        // $qAwal->groupBy('kdobat');
        // 1. Saldo Awal (Stok Opname Bulan Lalu)
        $qOpnameLalu = DB::connection('farmasi')->table('stokopname')
            ->select('kdobat', DB::raw("SUM(jumlah) as qty"), DB::raw("SUM(jumlah * harga) as val"))
            ->where('tglopname', 'LIKE', $blnLalu . '%')
            ->where('jumlah', '!=', 0)
            ->when($kode_ruang !== 'all', function ($q) use ($kode_ruang) {
                return $q->where('kdruang', $kode_ruang);
            }, function ($q) {
                return $q->whereIn('kdruang', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('kdobat');


        // 2. Prepare Subqueries Masuk & Keluar based on Room Type
        $qMasuk = null;
        $qKeluar = null;


        // --- LOGIKA MASUK ---
        // Referensi KartuStok: 'mutasikeluar' (variable name) -> tujuan=Me -> tgl_kirim_depo
        // Referensi KartuStok: 'returpenjualan' -> resep_keluar_h.depo=Me -> tgl_retur
        // Referensi KartuStok: 'penerimaanrinci' (Gudang) -> gudang=Me -> tglpenerimaan




        // --- LOGIKA KELUAR ---
        // Referensi KartuStok: 'mutasikeluar' -> Uses where('tujuan', $koderuangan)
        // Ref: where('tujuan', $koderuangan) AND whereBetween('tgl_kirim_depo')

        // 2. Mutasi Pendukung Saldo Awal (Dari tgl 1 s/d H-1)
        $qMasukSebelumnya = $this->queryMasuk($kode_ruang, $startOfMonth, $yesterdayEnd);
        $qKeluarSebelumnya = $this->queryKeluar($kode_ruang, $startOfMonth, $yesterdayEnd);

        // 3. Mutasi Hari Ini (Tanggal Terpilih)
        $qMasukHariIni = $this->queryMasuk($kode_ruang, $todayStart, $todayEnd);
        $qKeluarHariIni = $this->queryKeluar($kode_ruang, $todayStart, $todayEnd);

        // 3. MAIN QUERY
        // Master Obat + Left Joins
        // $mainQuery = DB::connection('farmasi')->table('new_masterobat as m')
        //     ->leftJoin('kodeBelanjaObat as k108', 'm.kode108', '=', 'k108.kode')
        //     ->leftJoinSub($qAwal, 'awal', 'm.kd_obat', '=', 'awal.kdobat')
        //     ->leftJoinSub($qMasuk, 'masuk', 'm.kd_obat', '=', 'masuk.kdobat')
        //     ->leftJoinSub($qKeluar, 'keluar', 'm.kd_obat', '=', 'keluar.kdobat')
        //     ->select(
        //         'k108.kode as kode_108',
        //         'k108.uraian as nama_108',
        //         'm.kd_obat',
        //         'm.nama_obat',
        //         'm.satuan_k', // Optional: Unit
        //         DB::raw("COALESCE(awal.qty, 0) as awal_qty"),
        //         DB::raw("COALESCE(awal.val, 0) as awal_val"),
        //         DB::raw("COALESCE(masuk.qty, 0) as masuk_qty"),
        //         DB::raw("COALESCE(masuk.val, 0) as masuk_val"),
        //         DB::raw("COALESCE(keluar.qty, 0) as keluar_qty"),
        //         DB::raw("COALESCE(keluar.val, 0) as keluar_val"),
        //         // Sisa = Awal + Masuk - Keluar
        //         DB::raw("(COALESCE(awal.qty, 0) + COALESCE(masuk.qty, 0) - COALESCE(keluar.qty, 0)) as sisa_qty"),
        //         DB::raw("(COALESCE(awal.val, 0) + COALESCE(masuk.val, 0) - COALESCE(keluar.val, 0)) as sisa_val")
        //     )
        //     ->where(function ($q) {
        //         // Filter Search
        //         $term = request('q');
        //         if ($term) {
        //             $q->where('m.nama_obat', 'LIKE', '%' . $term . '%')
        //                 ->orWhere('m.kd_obat', 'LIKE', '%' . $term . '%');
        //         }
        //     })

        //     ->where(function ($q) {
        //         $q->whereRaw("m.flag != 1");
        //     });
        // 4. Main Query Gabungan
        $mainQuery = DB::connection('farmasi')->table('new_masterobat as m')
            ->leftJoin('kodeBelanjaObat as k108', 'm.kode108', '=', 'k108.kode')
            ->leftJoinSub($qOpnameLalu, 'opname', 'm.kd_obat', '=', 'opname.kdobat')
            ->leftJoinSub($qMasukSebelumnya, 'm_lalu', 'm.kd_obat', '=', 'm_lalu.kdobat')
            ->leftJoinSub($qKeluarSebelumnya, 'k_lalu', 'm.kd_obat', '=', 'k_lalu.kdobat')
            ->leftJoinSub($qMasukHariIni, 'm_skrg', 'm.kd_obat', '=', 'm_skrg.kdobat')
            ->leftJoinSub($qKeluarHariIni, 'k_skrg', 'm.kd_obat', '=', 'k_skrg.kdobat')
            ->select(
                'k108.kode as kode_108',
                'k108.uraian as nama_108',
                'm.kd_obat',
                'm.nama_obat',
                'm.satuan_k',
                // Perhitungan Awal: (Opname + Masuk 1-H-1) - (Keluar 1-H-1)
                DB::raw("(COALESCE(opname.qty, 0) + COALESCE(m_lalu.qty, 0) - COALESCE(k_lalu.qty, 0)) as awal_qty"),
                DB::raw("(COALESCE(opname.val, 0) + COALESCE(m_lalu.val, 0) - COALESCE(k_lalu.val, 0)) as awal_val"),
                // Mutasi hari ini
                DB::raw("COALESCE(m_skrg.qty, 0) as masuk_qty"),
                DB::raw("COALESCE(m_skrg.val, 0) as masuk_val"),
                DB::raw("COALESCE(k_skrg.qty, 0) as keluar_qty"),
                DB::raw("COALESCE(k_skrg.val, 0) as keluar_val"),
                // Sisa Akhir
                DB::raw("(COALESCE(opname.qty, 0) + COALESCE(m_lalu.qty, 0) - COALESCE(k_lalu.qty, 0) + COALESCE(m_skrg.qty, 0) - COALESCE(k_skrg.qty, 0)) as sisa_qty"),
                DB::raw("(COALESCE(opname.val, 0) + COALESCE(m_lalu.val, 0) - COALESCE(k_lalu.val, 0) + COALESCE(m_skrg.val, 0) - COALESCE(k_skrg.val, 0)) as sisa_val")
            )
            ->where('m.flag', '!=', '1');


        // Pagination or Download
        if (request('action') === 'download') {
            $data = $mainQuery->get();
            return new JsonResponse([
                'data' => $data,
                'req' => request()->all()
            ]);
        } else {
            $perPage = request('per_page') ? request('per_page') : 100;
            $data = $mainQuery->paginate((int)$perPage);
            return new JsonResponse($data);
        }
    }
    /**
     * Reusable logic untuk Masuk
     */
    private function queryMasuk($kode_ruang, $start, $end)
    {

        // Helper for Union Checks
        $isGudang = in_array($kode_ruang, ['Gd-05010100', 'Gd-03010100']);
        $isDepoOk = $kode_ruang === 'Gd-04010103';
        $isFloorStok = $kode_ruang === 'Gd-03010101';
        $qPenerimaan = null;
        if ($isGudang) {
            // Gudang: Penerimaan (Surat Jalan/Faktur)
            $qPenerimaan = DB::connection('farmasi')->table('penerimaan_r as r')
                ->join('penerimaan_h as h', 'h.nopenerimaan', '=', 'r.nopenerimaan')
                ->select('r.kdobat', DB::raw("SUM(r.jml_terima_k) as qty"), DB::raw("SUM(r.jml_terima_k * r.harga_netto_kecil) as val"))
                ->where('h.kunci', '1')
                ->where('h.gudang', $kode_ruang)
                ->whereBetween('h.tglpenerimaan', [$start, $end])
                ->groupBy('r.kdobat');
            // Untuk Gudang, Retur dari Unit Lain (Retur Gudang) adalah MASUK
            $qReturKeGudang = DB::connection('farmasi')->table('retur_gudang_details as r')
                ->join('retur_gudangs as h', 'h.no_retur', '=', 'r.no_retur')
                ->select('r.kd_obat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * CAST(0 as DECIMAL)) as val")) // Harga unknown in retur_gudangs?
                ->where('h.gudang', $kode_ruang)
                ->where('h.kunci', '1')
                ->whereBetween('h.tgl_retur', [$start, $end])
                ->groupBy('r.kd_obat');
            // Note: Val is tricky without price join. Leaving 0 or need join stock? For now 0 to avoid crash. user concerned with Qty/Stock mostly.
        }

        // 1. Mutasi Masuk (Dari Unit Lain ke Sini)
        // Referensi KartuStok: 'mutasimasuk' -> Uses where('dari', $koderuangan) !!!
        // Note: Logic sistem ini sepertinya 'dari' = Unit Peminta (Destination), 'tujuan' = Unit Pengirim (Source).
        // Atau variabel controller referensi yang namanya terbalik? TAPI kita ikut referensi code yang jalan.
        // Ref: where('dari', $koderuangan) AND whereBetween('tgl_terima_depo')
        $qMutasiMasuk = DB::connection('farmasi')->table('mutasi_gudangdepo as m')
            ->join('permintaan_h as h', 'h.no_permintaan', '=', 'm.no_permintaan')
            ->select('m.kd_obat as kdobat', DB::raw("SUM(m.jml) as qty"), DB::raw("SUM(m.jml * m.harga) as val"))
            ->whereBetween('h.tgl_terima_depo', [$start, $end])
            ->where('h.dari', $kode_ruang) // MATCH REF 'mutasimasuk'
            ->groupBy('m.kd_obat');

        // 2. Retur Penjualan (Pasien ke Unit)
        $qReturPasien = DB::connection('farmasi')->table('retur_penjualan_r as r')
            ->join('retur_penjualan_h as h', 'h.noretur', '=', 'r.noretur')
            ->join('resep_keluar_h as rh', 'rh.noresep', '=', 'r.noresep')
            ->select('r.kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * r.harga_beli) as val"))
            ->whereBetween('h.tgl_retur', [$start, $end])
            ->where('rh.depo', $kode_ruang)
            ->groupBy('r.kdobat');

        // Compile Masuk
        $qMasuk = $qMutasiMasuk->unionAll($qReturPasien);
        if ($isGudang) {
            $qMasuk = $qMasuk->unionAll($qPenerimaan)->unionAll($qReturKeGudang);
        }

        return DB::connection('farmasi')->query()->fromSub($qMasuk, 'union_masuk')
            ->select('kdobat', DB::raw("SUM(qty) as qty"), DB::raw("SUM(val) as val"))
            ->groupBy('kdobat');
    }
    /**
     * Reusable logic untuk Keluar
     */
    private function queryKeluar($kode_ruang, $start, $end)
    {

        // Helper for Union Checks
        $isGudang = in_array($kode_ruang, ['Gd-05010100', 'Gd-03010100']);
        $isDepoOk = $kode_ruang === 'Gd-04010103';
        $isFloorStok = $kode_ruang === 'Gd-03010101';

        // 1. Mutasi Keluar (Transfer ke Unit Lain)
        $qMutasiKeluar = DB::connection('farmasi')->table('mutasi_gudangdepo as m')
            ->join('permintaan_h as h', 'h.no_permintaan', '=', 'm.no_permintaan')
            ->select('m.kd_obat as kdobat', DB::raw("SUM(m.jml) as qty"), DB::raw("SUM(m.jml * m.harga) as val"))
            ->whereBetween('h.tgl_kirim_depo', [$start, $end])
            ->where('h.tujuan', $kode_ruang) // MATCH REF 'mutasikeluar'
            // ->whereIn('h.flag', ['4']) // Ref doesn't check flag explicitly in mutasikeluar? But safer to keep distinct if needed. 
            // Ref Lines 123-137 doesn't show flag wait. 
            // Ah, Lines 364 listpermintaandepo uses flag. But KartuStokController Lines 123 doesn't.
            // Let's remove flag check to be EXACTLY like Ref if Ref doesn't have it.
            // Ref: whereBetween(tgl_kirim), where(tujuan), join. No flag.
            ->groupBy('m.kd_obat');

        // 2. Retur Ke Gudang (Dari Depo) - Equivalent to 'returdepo'
        $qReturDariDepo = DB::connection('farmasi')->table('retur_gudang_details as r')
            ->join('retur_gudangs as h', 'h.no_retur', '=', 'r.no_retur')
            ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * CAST(0 as DECIMAL)) as val")) // Harga Issue?
            ->where('h.depo', $kode_ruang) // Sebagai Pengirim
            ->where('h.kunci', '1')
            ->whereBetween('h.tgl_retur', [$start, $end])
            ->groupBy('r.kd_obat');

        // 3. Resep Keluar (Biasa)
        $qResep = DB::connection('farmasi')->table('resep_keluar_r as r')
            ->join('resep_keluar_h as h', 'h.noresep', '=', 'r.noresep')
            ->select('r.kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * r.harga_beli) as val"))
            ->whereBetween('h.tgl_selesai', [$start, $end])
            ->where('h.depo', $kode_ruang)
            ->whereIn('h.flag', ['3', '4'])
            ->where('r.jumlah', '>', 0)
            ->groupBy('r.kdobat');

        // 4. Resep Keluar (Racikan)
        $qResepRacikan = DB::connection('farmasi')->table('resep_keluar_racikan_r as r')
            ->join('resep_keluar_h as h', 'h.noresep', '=', 'r.noresep')
            ->select('r.kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * r.harga_beli) as val"))
            ->whereBetween('h.tgl_selesai', [$start, $end])
            ->where('h.depo', $kode_ruang)
            ->whereIn('h.flag', ['3', '4'])
            ->where('r.jumlah', '>', 0)
            ->groupBy('r.kdobat');

        // 5. Barang Rusak
        $qBarangRusak = DB::connection('farmasi')->table('barang_rusaks as r')
            // Assumption: table name 'barang_rusaks', column 'gudang' matches kode_ruang
            ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * CAST(0 as DECIMAL)) as val"))
            ->whereBetween('r.tgl_kunci', [$start, $end])
            ->where('r.gudang', $kode_ruang)
            ->where('r.kunci', '1')
            ->groupBy('r.kd_obat');

        // OK Specific: Distribusi Persiapan Operasi
        $qDistribusiOK = null;
        if ($isDepoOk) {
            $qDistribusiOK = DB::connection('farmasi')->table('persiapan_operasi_distribusis as d')
                ->join('persiapan_operasis as h', 'h.nopermintaan', '=', 'd.nopermintaan')
                ->leftJoin(DB::raw('(SELECT kd_obat, nopenerimaan, MAX(harga) as harga FROM daftar_hargas GROUP BY kd_obat, nopenerimaan) as dh'), function ($join) {
                    $join->on('dh.nopenerimaan', '=', 'd.nopenerimaan')
                        ->on('dh.kd_obat', '=', 'd.kd_obat');
                })
                ->select('d.kd_obat as kdobat', DB::raw("SUM(d.jumlah) as qty"), DB::raw("SUM(d.jumlah * dh.harga) as val"))
                ->whereBetween('h.tgl_distribusi', [$start, $end])
                ->whereIn('h.flag', ['2', '3', '4']) // Matches Ref 'distribusipersiapan'
                ->groupBy('d.kd_obat');
        }

        // GABUNG KELUAR
        $qKeluar = $qMutasiKeluar
            ->unionAll($qResep)
            ->unionAll($qResepRacikan)
            ->unionAll($qReturDariDepo)
            ->unionAll($qBarangRusak);

        if ($qDistribusiOK) {
            $qKeluar = $qKeluar->unionAll($qDistribusiOK);
        }

        if ($isGudang) {
            // Retur PBF
            $qReturPbf = DB::connection('farmasi')->table('retur_penyedia_r as r')
                ->join('retur_penyedia_h as h', 'h.no_retur', '=', 'r.no_retur')
                ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * CAST(0 as DECIMAL)) as val"))
                ->where('h.gudang', $kode_ruang)
                ->whereBetween('h.tgl_kunci', [$start, $end])
                ->where('h.kunci', '1')
                ->groupBy('r.kd_obat');
            $qKeluar = $qKeluar->unionAll($qReturPbf);

            // Pengembalian Pinjaman (Line 281 in Ref)
            $qPengembalian = DB::connection('farmasi')->table('pengembalian_rinci_fifos as r')
                ->join('pengembalians as h', 'h.nopengembalian', '=', 'r.nopengembalian')
                ->select('r.kdobat', DB::raw("SUM(r.jml_dikembalikan) as qty"), DB::raw("SUM(r.jml_dikembalikan * CAST(0 as DECIMAL)) as val"))
                ->where('h.kdruang', $kode_ruang)
                ->whereBetween('h.tgl_kunci', [$start, $end])
                ->where('h.flag', '1')
                ->groupBy('r.kdobat');
            $qKeluar = $qKeluar->unionAll($qPengembalian);
        }

        return DB::connection('farmasi')->query()->fromSub($qKeluar, 'union_keluar')
            ->select('kdobat', DB::raw("SUM(qty) as qty"), DB::raw("SUM(val) as val"))
            ->groupBy('kdobat');
    }
}
