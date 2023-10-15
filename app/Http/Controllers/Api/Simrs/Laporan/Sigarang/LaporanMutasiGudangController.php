<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\MonthlyStokUpdate;
use App\Models\Sigarang\RecentStokUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanMutasiGudangController extends Controller
{
    //
    public function lapMutasi()
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
        if ($comp) {
            $recent = RecentStokUpdate::select('kode_rs')
                ->where('sisa_stok', '>', 0)->distinct()->orderBy('kode_rs', 'ASC')->get();
        } else {
            $recent = MonthlyStokUpdate::select('kode_rs')->distinct()
                ->where('sisa_stok', '>', 0)->whereBetween('tanggal', [$from, $to])->orderBy('kode_rs', 'ASC')->get();
        }
        $col = collect($recent);

        $barang = BarangRS::select('kode', 'nama', 'kode_satuan', 'kode_108', 'uraian_108')
            ->whereIn('kode', $col)
            ->filter(request(['q']))
            ->with([
                'satuan:kode,nama',
                'monthly' => function ($m) use ($from, $to) {
                    $m->select('tanggal', 'harga', 'kode_rs', 'kode_ruang', 'sisa_stok')
                        ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->selectRaw('round(sum(sisa_stok*harga),2) as totalRp')
                        ->whereBetween('tanggal', [$from, $to])
                        ->groupBy('kode_rs', 'harga');
                },
                'recent' => function ($m) {
                    $m->select('harga', 'kode_rs', 'kode_ruang', 'sisa_stok', 'no_penerimaan')
                        ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->selectRaw('round(sum(sisa_stok*harga),2) as totalRp')
                        ->where('sisa_stok', '>', 0)
                        ->groupBy('kode_rs', 'harga');
                },
                'stok_awal' => function ($m) use ($fromA, $toA) {
                    $m->select('tanggal', 'harga', 'kode_rs', 'kode_ruang', 'sisa_stok')
                        ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->selectRaw('round(sum(sisa_stok*harga),2) as totalRp')
                        ->whereBetween('tanggal', [$fromA, $toA])
                        ->groupBy('kode_rs', 'harga');
                },
                'detailPenerimaan' => function ($m) use ($from, $to) {
                    $m->select(
                        'kode_rs',
                    )
                        ->selectRaw('round(sum(qty),2) as total')
                        ->selectRaw('round(sum(qty*harga_jadi),2) as totalRp')
                        ->leftJoin('penerimaans', function ($p) {
                            $p->on('penerimaans.id', '=', 'detail_penerimaans.penerimaan_id');
                        })
                        ->whereBetween('penerimaans.tanggal', [$from, $to])
                        ->where('status', 2)
                        ->groupBy('kode_rs');
                },
                'detailDistribusiLangsung' => function ($m) use ($from, $to) {
                    $m->select(
                        'detail_distribusi_langsungs.kode_rs',
                        DB::raw('detail_distribusi_langsungs.jumlah * recent_stok_updates.harga as totalRp')
                    )
                        ->selectRaw('round(sum(jumlah),2) as total')
                        ->leftJoin('distribusi_langsungs', function ($p) {
                            $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
                        })
                        ->leftJoin('recent_stok_updates', function ($p) {
                            $p->on('recent_stok_updates.no_penerimaan', '=', 'detail_distribusi_langsungs.no_penerimaan');
                        })
                        ->whereBetween('distribusi_langsungs.tanggal', [$from, $to])
                        ->where('status', 2)
                        ->groupBy('kode_rs');
                },
                'detailPemakaianruangan' => function ($m) use ($from, $to) {
                    $m->select(
                        'details_pemakaianruangans.kode_rs',
                        DB::raw('details_pemakaianruangans.jumlah * recent_stok_updates.harga as totalRp')
                    )
                        ->selectRaw('round(sum(jumlah),2) as total')
                        ->leftJoin('pemakaianruangans', function ($p) {
                            $p->on('pemakaianruangans.id', '=', 'details_pemakaianruangans.pemakaianruangan_id');
                        })
                        ->leftJoin('recent_stok_updates', function ($p) {
                            $p->on('recent_stok_updates.no_penerimaan', '=', 'details_pemakaianruangans.no_penerimaan');
                        })
                        ->whereBetween('pemakaianruangans.tanggal', [$from, $to])
                        ->where('status', 2)
                        ->groupBy('kode_rs');
                },

            ]);


        $data = $barang->withTrashed()->get();

        return new JsonResponse($data);
    }
}
