<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\MonthlyStokUpdate;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Rekening50;
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
        $temp = date_modify($date, '-1 months');
        $anu = date_format($date, 'Y-m');
        $prev = date_format($temp, 'Y-m');;
        // return new JsonResponse($prev);
        $from = request('tahun') . '-' . request('bulan') . '-01 00:00:00';
        $to = request('tahun') . '-' . request('bulan') . '-31 23:59:59';
        $fromA = $prev . '-01 00:00:00';
        $toA = $prev . '-31 23:59:59';

        $recent = RecentStokUpdate::select('kode_rs')->distinct()->orderBy('kode_rs', 'ASC')->get();
        $trx = DetailPenerimaan::select('kode_rs')->distinct()
            ->leftJoin('penerimaans', function ($p) {
                $p->on('penerimaans.id', '=', 'detail_penerimaans.penerimaan_id');
            })
            ->whereBetween('penerimaans.tanggal', [$from, $to])
            ->orderBy('kode_rs', 'ASC')
            ->get();
        $col = collect($recent);
        foreach ($trx as $key) {
            $temp = $col->where('kode_rs', $key->kode_rs)->all();
            if (count($temp) <= 0) {
                $col->push($key);
                // return new JsonResponse($key);
            }
            // return new JsonResponse(count($temp));
        }
        $depo = ['Gd-02010101', 'Gd-02010102', 'Gd-02010103'];
        $barang = BarangRS::select('kode', 'nama', 'kode_satuan')
            ->whereIn('kode', $col)
            ->filter(request(['q']))
            ->with([
                'satuan:kode,nama',
                'monthly' => function ($m) use ($from, $to, $depo) {
                    $m->select('tanggal', 'harga', 'kode_rs', 'kode_ruang', 'sisa_stok')
                        ->whereBetween('tanggal', [$from, $to])
                        ->whereIn('kode_ruang', $depo)
                        ->when(request('kode_ruang'), function ($anu) use ($depo) {
                            $anu->whereKodeRuang(request('kode_ruang'));
                        });
                },
                'stok_awal' => function ($m) use ($fromA, $toA, $depo) {
                    $m->select('tanggal', 'harga', 'kode_rs', 'kode_ruang', 'sisa_stok')
                        ->whereBetween('tanggal', [$fromA, $toA])
                        ->whereIn('kode_ruang', $depo)
                        ->when(request('kode_ruang'), function ($anu) use ($depo) {
                            $anu->whereKodeRuang(request('kode_ruang'));
                        });
                },
                'recent' => function ($m) use ($depo) {
                    $m->select('harga', 'kode_rs', 'kode_ruang', 'sisa_stok')
                        ->where('sisa_stok', '>', 0)
                        ->whereIn('kode_ruang', $depo)
                        ->when(request('kode_ruang'), function ($anu) use ($depo) {
                            $anu->whereKodeRuang(request('kode_ruang'));
                        });
                },
                'detailPenerimaan' => function ($m) use ($from, $to) {
                    $m->select(
                        'harga',
                        'harga_jadi',
                        'harga_kontrak',
                        'isi',
                        'qty',
                        'kode_rs',
                        'diskon',
                        'penerimaan_id',
                        'ppn'
                    )
                        ->leftJoin('penerimaans', function ($p) {
                            $p->on('penerimaans.id', '=', 'detail_penerimaans.penerimaan_id');
                        })
                        ->whereBetween('penerimaans.tanggal', [$from, $to])
                        ->where('status', 2);
                },
                'detailDistribusiDepo' => function ($m) use ($from, $to) {
                    $m->select(
                        'jumlah',
                        'kode_rs',
                        'isi',
                        'distribusi_depo_id',
                    )
                        ->leftJoin('distribusi_depos', function ($p) {
                            $p->on('distribusi_depos.id', '=', 'detail_distribusi_depos.distribusi_depo_id');
                        })
                        ->whereBetween('distribusi_depos.tanggal', [$from, $to])
                        ->where('status', 2);
                },
                'detailDistribusiLangsung' => function ($m) use ($from, $to) {
                    $m->select(
                        'jumlah',
                        'kode_rs',
                        'isi',
                        'distribusi_langsung_id',
                    )
                        ->leftJoin('distribusi_langsungs', function ($p) {
                            $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
                        })
                        ->whereBetween('distribusi_langsungs.tanggal', [$from, $to])
                        ->where('status', 2);
                },
                'detailPermintaanruangan' => function ($m) use ($from, $to) {
                    $m->select(
                        'jumlah_distribusi',
                        'kode_rs',
                        'isi',
                        'permintaanruangan_id',
                    )
                        ->leftJoin('permintaanruangans', function ($p) {
                            $p->on('permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id');
                        })
                        ->whereBetween('permintaanruangans.tanggal', [$from, $to])
                        ->where('status', 7);
                },
            ]);


        $data = $barang->paginate(request('per_page'));

        return new JsonResponse($data);
        // $comp = $anu === date('Y-m');
        // if ($comp) {
        //     $result = RecentStokUpdate::selectRaw('*, (sisa_stok * harga) as subtotal, sum(sisa_stok * harga) as total, sum(sisa_stok) as totalStok')
        //         ->where('sisa_stok', '>', 0)
        //         ->where('kode_ruang', 'LIKE', '%Gd-%')
        //         ->when(request('kode_ruang'), function ($anu) {
        //             $anu->whereKodeRuang(request('kode_ruang'));
        //         })
        //         ->when(request('kode_rs'), function ($anu) {
        //             $anu->whereKodeRs(request('kode_rs'));
        //         })
        //         ->with(
        //             'barang:kode,nama',
        //             'penerimaan:id,no_penerimaan',
        //             'penerimaan.details:kode_rs,penerimaan_id,harga,harga_kontrak,diskon,ppn,harga_jadi'
        //         );
        //     // ->with('penerimaan.details')
        //     if (request('kode_ruang')) {
        //         $result->groupBy('kode_rs', 'kode_ruang', 'no_penerimaan');
        //     } else {
        //         $result->groupBy('kode_rs', 'no_penerimaan');
        //     }
        //     $data = $result->paginate(request('per_page'));

        //     return new JsonResponse($data);
        // }


        // $result = MonthlyStokUpdate::selectRaw('*, (sisa_stok * harga) as subtotal, sum(sisa_stok * harga) as total, sum(sisa_stok) as totalStok')
        //     ->where('sisa_stok', '>', 0)
        //     ->where('kode_ruang', 'LIKE', '%Gd-%')
        //     ->whereBetween('tanggal', [$from, $to])
        //     ->when(request('kode_ruang'), function ($anu) {
        //         $anu->whereKodeRuang(request('kode_ruang'));
        //     })
        //     ->when(request('kode_rs'), function ($anu) {
        //         $anu->whereKodeRs(request('kode_rs'));
        //     })
        //     ->with(
        //         'barang:kode,nama',
        //         'penerimaan:id,no_penerimaan',
        //         'penerimaan.details:kode_rs,penerimaan_id,harga,harga_kontrak,diskon,ppn,harga_jadi'
        //     );
        // // ->with('penerimaan.details')
        // if (request('kode_ruang')) {
        //     $result->groupBy('kode_rs', 'kode_ruang', 'no_penerimaan');
        // } else {
        //     $result->groupBy('kode_rs', 'no_penerimaan');
        // }
        // $data = $result->paginate(request('per_page'));

        // return new JsonResponse($data);
    }
}
