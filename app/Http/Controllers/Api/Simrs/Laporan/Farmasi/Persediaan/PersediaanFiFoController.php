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
                        'stokreal.nopenerimaan as stpen',
                        DB::raw('sum(stokreal.jumlah) as jumlah'),
                        DB::raw('sum(stokreal.jumlah * daftar_hargas.harga) as sub'),
                        'penerimaan_r.nopenerimaan',
                        'penerimaan_h.jenis_penerimaan',
                        'daftar_hargas.harga',
                    )
                        ->leftJoin('daftar_hargas', function ($jo) {
                            $jo->on('daftar_hargas.nopenerimaan', '=', 'stokreal.nopenerimaan')
                                ->on('daftar_hargas.kd_obat', '=', 'stokreal.kdobat');
                        })
                        ->leftJoin('penerimaan_r', function ($jo) {
                            $jo->on('penerimaan_r.nopenerimaan', '=', 'stokreal.nopenerimaan')
                                ->on('penerimaan_r.kdobat', '=', 'stokreal.kdobat');
                        })
                        ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
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
                        ->groupBy('stokreal.kdobat', 'penerimaan_r.nopenerimaan', 'daftar_hargas.harga');
                },
                'saldoawal' => function ($st) {
                    $st->select(
                        'stokopname.kdobat',
                        'stokopname.nopenerimaan as stpen',
                        DB::raw('sum(stokopname.jumlah) as jumlah'),
                        DB::raw('sum(stokopname.jumlah * daftar_hargas.harga) as sub'),
                        'penerimaan_r.nopenerimaan',
                        'penerimaan_h.jenis_penerimaan',
                        'daftar_hargas.harga',
                    )
                        ->leftJoin('daftar_hargas', function ($jo) {
                            $jo->on('daftar_hargas.nopenerimaan', '=', 'stokopname.nopenerimaan')
                                ->on('daftar_hargas.kd_obat', '=', 'stokopname.kdobat');
                        })
                        ->leftJoin('penerimaan_r', function ($jo) {
                            $jo->on('penerimaan_r.nopenerimaan', '=', 'stokopname.nopenerimaan')
                                ->on('penerimaan_r.kdobat', '=', 'stokopname.kdobat');
                        })
                        ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
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
                        ->groupBy('stokopname.kdobat', 'penerimaan_r.nopenerimaan', 'daftar_hargas.harga');
                }
            ])
            ->where('nama_obat', 'LIKE', '%' . request('q') . '%')
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

        $obat = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'satuan_k',
            'uraian50',

        )
            ->with([
                'saldoawal' => function ($st) use ($blnLalu) {
                    $st->select(
                        'stokopname.kdobat',
                        'stokopname.nopenerimaan',
                        DB::raw('sum(stokopname.jumlah) as jumlah'),
                        DB::raw('sum(stokopname.jumlah * stokopname.harga) as sub'),
                        DB::raw('stokopname.harga as harga '),
                        // 'daftar_hargas.harga as dftHar',
                    )

                        ->where('stokopname.jumlah', '!=', 0)
                        ->where('stokopname.tglopname', 'LIKE', $blnLalu . '%')
                        // ->where('stokopname.kdruang', request('kode_ruang'))
                        ->when(
                            request('jenis') === 'rekap',
                            function ($re) {
                                $re->groupBy('stokopname.kdobat', 'stokopname.tglopname');
                            },
                            function ($re) {
                                $re->groupBy('stokopname.kdobat', 'stokopname.nopenerimaan', 'stokopname.tglopname');
                            }
                        );
                    // ->groupBy('stokopname.kdobat', 'stokopname.nopenerimaan', 'stokopname.tglopname');
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
                        ->where('penerimaan_h.tglpenerimaan', 'LIKE', request('tahun') . '-' . request('bulan') . '%')
                        ->when(
                            request('jenis') === 'rekap',
                            function ($re) {
                                $re->groupBy('penerimaan_r.kdobat');
                            },
                            function ($re) {
                                $re->groupBy('penerimaan_r.kdobat', 'penerimaan_r.nopenerimaan');
                            }
                        );
                    // ->groupBy('penerimaan_r.kdobat', 'penerimaan_r.nopenerimaan');
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
                        ->where('resep_keluar_h.tgl_selesai', 'LIKE', request('tahun') . '-' . request('bulan') . '%')
                        ->with(
                            'header:noresep,norm',
                            'header.datapasien:rs1,rs2'
                        )
                        ->when(
                            request('jenis') === 'rekap',
                            function ($re) {
                                $re->groupBy('resep_keluar_r.kdobat');
                            },
                            function ($re) {
                                $re->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan', 'resep_keluar_r.noresep');
                            }
                        );
                },
                'resepkeluarracikan' => function ($kel) {
                    $kel->select(
                        'resep_keluar_racikan_r.noresep',
                        'resep_keluar_racikan_r.kdobat',
                        'resep_keluar_h.tgl_selesai as tgl',
                        'resep_keluar_racikan_r.nopenerimaan',
                        'resep_keluar_racikan_r.harga_beli as header_register_callback',
                        DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah'),
                        DB::raw('sum(resep_keluar_racikan_r.jumlah * resep_keluar_racikan_r.harga_beli) as sub')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_racikan_r.noresep')
                        ->havingRaw('jumlah > 0')
                        ->where('resep_keluar_h.tgl_selesai', 'LIKE', request('tahun') . '-' . request('bulan') . '%')
                        ->with(
                            'header:noresep,norm',
                            'header.datapasien:rs1,rs2'
                        )
                        ->when(
                            request('jenis') === 'rekap',
                            function ($re) {
                                $re->groupBy('resep_keluar_racikan_r.kdobat');
                            },
                            function ($re) {
                                $re->groupBy('resep_keluar_racikan_r.kdobat', 'resep_keluar_racikan_r.nopenerimaan', 'resep_keluar_racikan_r.noresep');
                            }
                        );
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
                        ->where('retur_penjualan_h.tgl_retur', 'LIKE', request('tahun') . '-' . request('bulan') . '%')
                        ->with(
                            'header:noresep,norm',
                            'header.datapasien:rs1,rs2'
                        )
                        ->when(
                            request('jenis') === 'rekap',
                            function ($re) {
                                $re->groupBy('retur_penjualan_r.kdobat');
                            },
                            function ($re) {
                                $re->groupBy('retur_penjualan_r.kdobat', 'retur_penjualan_r.nopenerimaan', 'retur_penjualan_r.noresep');
                            }
                        );
                },
                'pemakaian' => function ($pak) {
                    $pak->select(
                        'pemakaian_r.kd_obat as kdobat',
                        'pemakaian_r.kd_obat',
                        'pemakaian_r.nopenerimaan',
                        'pemakaian_h.tgl as tgl',
                        'pemakaian_h.kdruang',
                        'stokopname.harga as harga',
                        DB::raw('sum(pemakaian_r.jumlah) as jumlah'),
                        DB::raw('sum(pemakaian_r.jumlah * stokopname.harga) as sub'),

                    )
                        ->join('pemakaian_h', 'pemakaian_h.nopemakaian', '=', 'pemakaian_r.nopemakaian')
                        ->join('stokopname', function ($jo) {
                            $jo->on('stokopname.kdobat', '=', 'pemakaian_r.kd_obat')
                                ->on('stokopname.nopenerimaan', '=', 'pemakaian_r.nopenerimaan');
                        })
                        ->havingRaw('jumlah > 0')
                        ->where('pemakaian_h.tgl', 'LIKE', request('tahun') . '-' . request('bulan') . '%')
                        ->with('ruangan:kode,uraian')
                        ->when(
                            request('jenis') === 'rekap',
                            function ($re) {
                                $re->groupBy('pemakaian_r.kd_obat');
                            },
                            function ($re) {
                                $re->groupBy('pemakaian_r.kd_obat', 'pemakaian_r.nopenerimaan', 'pemakaian_r.nopemakaian');
                            }
                        );
                },
                'daftarharga' => function ($q) {
                    $q->select(
                        'kd_obat',
                        'nopenerimaan',
                        'harga',
                    )
                        ->groupBy(
                            'kd_obat',
                            'nopenerimaan'
                        )
                        ->orderby('tgl_mulai_berlaku', 'DESC');
                }

            ])

            ->when(request('kode_ruang') !== 'all', function ($q) {
                $q->whereIn('gudang', ['', request('kode_ruang')]);
            })
            ->where(function ($q) {
                $q->where('nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'LIKE', '%' . request('q') . '%');
            })
            ->paginate(request('per_page'));
        // ->limit(10)
        // ->get();

        $anu = collect($obat)['data'];
        $meta = collect($obat)->except('data');
        $kirim = [];
        foreach ($anu as $it) {
            $it['saldo'] = $it['saldoawal'];
            $it['terima'] = $it['penerimaanrinci'];
            $it['retur'] = $it['returpenjualan'];
            $kirim[] = $it;
        }
        // $anu->map(function ($it) {
        //     $it->saldo = $it->saldoawal;
        //     $it->terima = $it->penerimaanrinci;
        //     $it->retur = $it->returpenjualan;
        //     return $it;
        // });
        return new JsonResponse([
            'obat' => $obat,
            'data' => $kirim,
            'blnLalu' => $blnLalu,
            'meta' => $meta,
            'req' => request()->all()
        ]);
    }
}
