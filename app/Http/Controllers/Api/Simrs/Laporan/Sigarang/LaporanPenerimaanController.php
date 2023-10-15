<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\MonthlyStokUpdate;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Rekening50;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DistribusiDepo;
use App\Models\Sigarang\Transaksi\Penerimaan\DetailPenerimaan;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPenerimaanController extends Controller
{
    public function lappenerimaan()
    {
        $tgl = request('tgl');
        $tglx = request('tglx');
        $rek50 = Rekening50::select(
            'rekening50s.kode as kode',
            'rekening50s.uraian as uraian50',
        )->with([
            'barangrs' => function ($rincianpenerimaan) use ($tgl, $tglx) {
                $rincianpenerimaan->select(
                    'barang_r_s.kode_50',
                    // 'detail_penerimaans.kode_108 as kode_108',
                    // 'detail_penerimaans.uraian_108 as uraian_108',
                    // 'detail_penerimaans.nama_barang as nama_barang'
                    // 'detail_penerimaans.penerimaan_id',
                    'penerimaans.tanggal',
                    'detail_penerimaans.kode_rs',
                    'barang_r_s.kode_108 as kode_108',
                    'barang_r_s.uraian_108 as uraian_108',
                    'barang_r_s.kode',
                    'barang_r_s.nama as nama_barang',
                    DB::raw('sum(detail_penerimaans.qty*detail_penerimaans.harga) as subtotal'),
                )
                    ->join('detail_penerimaans', function ($detail) {
                        $detail->on('barang_r_s.kode', '=', 'detail_penerimaans.kode_rs')
                            ->join('penerimaans', 'penerimaans.id', '=', 'detail_penerimaans.penerimaan_id');
                        // ->join('penerimaans', function ($trm) {
                        //     $trm->on('penerimaans.id', '=', 'detail_penerimaans.penerimaan_id')
                        //         ->whereBetween('penerimaans.tanggal', [request('tgl'), request('tglx')]);
                        //     });
                        // ->whereBetween('penerimaans.tanggal', [request('tgl'), request('tglx')]);
                    })
                    ->whereBetween('penerimaans.tanggal', [request('tgl') . ' 00:00:00', request('tglx') . ' 23:59:59'])
                    ->groupBy('detail_penerimaans.kode_rs');
            }
        ])

            ->Where('rekening50s.jenis', '02')->where('rekening50s.objek', '01')
            ->get();
        //$wew[] = $rek50[0]->kode50cari;
        return $rek50;
        // $rek50x = Rekening50::select(
        //     'rekening50s.kode as kode50',
        //     'rekening50s.uraian as uraian50'

        // )
        //     ->whereIn('rekening50s.kode50', $wew)
        //     ->get();

        // return $rek50x;

        // $judulsatu = Penerimaan::select(
        //     DB::raw('SUBSTRING_INDEX(detail_penerimaans.kode_50,".",4) as kode50'),
        //     'detail_penerimaans.uraian_50 as uraian50',
        //     DB::raw('sum(detail_penerimaans.qty*detail_penerimaans.harga) as total')
        // )
        //     ->join('detail_penerimaans', 'penerimaans.id', '=', 'detail_penerimaans.penerimaan_id')
        //     ->with('details.penerimaan')
        //     ->whereBetween('penerimaans.tanggal', [$tgl, $tglx])
        //     ->groupBy(DB::raw('SUBSTRING_INDEX(detail_penerimaans.kode_50,".",4)'))
        //     ->get();

        // return new JsonResponse($judulsatu);
    }

    public function lappersediaan()
    {
        $date = date_create(request('tahun') . '-' . request('bulan'));
        $date2 = date_create(request('tahun') . '-' . request('bulan'));
        $anu = date_format($date2, 'Y-m');
        $comp = $anu === date('Y-m');
        $temp = date_modify($date, '-1 months');
        $prev = date_format($temp, 'Y-m');
        $from = request('tahun') . '-' . request('bulan') . '-01 00:00:00';
        $to = request('tahun') . '-' . request('bulan') . '-31 23:59:59';
        $fromA = $prev . '-01 00:00:00';
        $toA = $prev . '-31 23:59:59';
        $depo = ['Gd-02010101', 'Gd-02010102', 'Gd-02010103'];
        if ($comp) {
            $recent = RecentStokUpdate::select('kode_rs')
                ->when(!request('kode_ruang'), function ($anu) use ($depo) {
                    $anu->whereIn('kode_ruang', $depo);
                })
                ->when(request('kode_ruang'), function ($anu) {
                    $anu->whereKodeRuang(request('kode_ruang'));
                })
                ->where('sisa_stok', '>', 0)->distinct()->orderBy('kode_rs', 'ASC')->get();
        } else {
            $recent = MonthlyStokUpdate::select('kode_rs')->distinct()
                ->when(!request('kode_ruang'), function ($anu) use ($depo) {
                    $anu->whereIn('kode_ruang', $depo);
                })
                ->when(request('kode_ruang'), function ($anu) {
                    $anu->whereKodeRuang(request('kode_ruang'));
                })
                ->where('sisa_stok', '>', 0)->whereBetween('tanggal', [$from, $to])->orderBy('kode_rs', 'ASC')->get();
        }
        $col = collect($recent);

        $barang = BarangRS::select('kode', 'nama', 'kode_satuan')
            ->whereIn('kode', $col)
            ->filter(request(['q']))
            ->with([
                'satuan:kode,nama',
                'monthly' => function ($m) use ($from, $to, $depo) {
                    $m->select('tanggal', 'harga', 'kode_rs', 'kode_ruang', 'sisa_stok')
                        ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->with('depo')
                        ->whereBetween('tanggal', [$from, $to])
                        ->when(!request('kode_ruang'), function ($anu) use ($depo) {
                            $anu->whereIn('kode_ruang', $depo);
                        })
                        ->when(request('kode_ruang'), function ($anu) {
                            $anu->whereKodeRuang(request('kode_ruang'));
                        })
                        ->groupBy('kode_rs', 'kode_ruang', 'harga');
                },
                'recent' => function ($m) use ($depo) {
                    $m->select('harga', 'kode_rs', 'kode_ruang', 'sisa_stok', 'no_penerimaan')
                        ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->with('depo')
                        ->where('sisa_stok', '>', 0)
                        ->when(!request('kode_ruang'), function ($anu) use ($depo) {
                            $anu->whereIn('kode_ruang', $depo);
                        })
                        ->when(request('kode_ruang'), function ($anu) {
                            $anu->whereKodeRuang(request('kode_ruang'));
                        })
                        ->groupBy('kode_rs', 'kode_ruang', 'harga');
                },
            ]);


        $data = $barang->get();

        return new JsonResponse($data);
    }

    public function lapPenerimaanGudang()
    {
        $data = Penerimaan::select(
            'penerimaans.tanggal',
            'penerimaans.no_penerimaan',
            'penerimaans.status',
            'penerimaans.surat_jalan',
            'penerimaans.faktur',
            'penerimaans.kode_perusahaan',
            'detail_penerimaans.kode_rs',
            'detail_penerimaans.harga_jadi as harga',
            'detail_penerimaans.ppn',
            'detail_penerimaans.sub_total',
            'detail_penerimaans.qty',
            'barang_r_s.nama',
            'satuans.nama as satuan',
            'recent_stok_updates.sisa_stok',
        )
            ->leftJoin('detail_penerimaans', function ($p) {
                $p->on('detail_penerimaans.penerimaan_id', '=', 'penerimaans.id')
                    ->leftJoin('barang_r_s', function ($b) {
                        $b->on('detail_penerimaans.kode_rs', '=', 'barang_r_s.kode')
                            ->leftJoin('satuans', function ($s) {
                                $s->on('satuans.kode', '=', 'barang_r_s.kode_satuan');
                            });
                    });
                // ->leftJoin('recent_stok_updates', function ($s) {
                //     $s->on('recent_stok_updates.kode_rs', '=', 'detail_penerimaans.kode_rs');
                // });
            })
            ->leftJoin('recent_stok_updates', function ($s) {
                $s->on('penerimaans.no_penerimaan', '=', 'recent_stok_updates.no_penerimaan')
                    ->on('detail_penerimaans.kode_rs', '=', 'recent_stok_updates.kode_rs')
                    ->where('recent_stok_updates.kode_ruang', 'Gd-02010100');
            })
            ->when(request('q'), function ($q) {
                $q->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%');
            })
            ->whereBetween('penerimaans.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->with('perusahaan')
            ->orderBy('penerimaans.tanggal', 'ASC')
            ->orderBy('penerimaans.no_penerimaan', 'ASC')
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function lapPenerimaanDepo()
    {
        $data = DistribusiDepo::select(
            'distribusi_depos.tanggal',
            'distribusi_depos.no_distribusi',
            'distribusi_depos.status',
            'detail_distribusi_depos.no_penerimaan',
            'detail_distribusi_depos.kode_rs',
            'detail_distribusi_depos.jumlah as qty',
            'barang_r_s.nama',
            'satuans.nama as satuan',
            'recent_stok_updates.harga',
            'recent_stok_updates.sisa_stok',
            DB::raw('detail_distribusi_depos.jumlah * recent_stok_updates.harga as sub_total')
        )
            ->leftJoin('detail_distribusi_depos', function ($p) {
                $p->on('detail_distribusi_depos.distribusi_depo_id', '=', 'distribusi_depos.id')
                    ->leftJoin('barang_r_s', function ($b) {
                        $b->on('detail_distribusi_depos.kode_rs', '=', 'barang_r_s.kode')
                            ->leftJoin('satuans', function ($s) {
                                $s->on('satuans.kode', '=', 'barang_r_s.kode_satuan');
                            });
                    })
                    // ->leftJoin('recent_stok_updates', function ($s) {
                    //     $s->on('recent_stok_updates.kode_rs', '=', 'detail_distribusi_depos.kode_rs');
                    // });
                    ->leftJoin('recent_stok_updates', function ($s) {
                        $s->on('recent_stok_updates.no_penerimaan', '=', 'detail_distribusi_depos.no_penerimaan')
                            ->on('recent_stok_updates.kode_rs', '=', 'detail_distribusi_depos.kode_rs')
                            ->when(request('kode_ruang'), function ($q) {
                                $depo = ['Gd-02010101', 'Gd-02010102', 'Gd-02010103'];
                                if (request('kode_ruang') === 'all') {
                                    $q->whereIn('recent_stok_updates.kode_ruang', $depo);
                                } else {
                                    $q->whereIn('recent_stok_updates.kode_ruang', request('kode_ruang'));
                                }
                            });
                    });
            })
            ->when(request('q'), function ($q) {
                $q->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%');
            })
            ->when(request('kode_ruang'), function ($q) {
                $depo = ['Gd-02010101', 'Gd-02010102', 'Gd-02010103'];
                if (request('kode_ruang') === 'all') {
                    $q->whereIn('distribusi_depos.kode_depo', $depo);
                } else {
                    $q->whereIn('distribusi_depos.kode_depo', request('kode_ruang'));
                }
            })
            ->whereBetween('distribusi_depos.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->where('status', 2)
            ->with('depo:nama')
            ->orderBy('distribusi_depos.tanggal', 'ASC')
            ->orderBy('distribusi_depos.no_distribusi', 'ASC')
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
}
