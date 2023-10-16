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
        $fromN = request('tahun') . '-' . request('bulan') . '-01';
        $toN = request('tahun') . '-' . request('bulan') . '-31';
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
                    $m->select('tanggal', 'harga', 'no_penerimaan', 'kode_rs', 'kode_ruang', 'sisa_stok as totalStok')
                        // ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->selectRaw('round(sisa_stok*harga,2) as totalRp')
                        ->whereBetween('tanggal', [$from, $to]);
                    // ->groupBy('kode_rs', 'harga');
                },
                'recent' => function ($m) {
                    $m->select('harga', 'kode_rs', 'kode_ruang', 'sisa_stok as totalStok', 'no_penerimaan')
                        // ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->selectRaw('round(sisa_stok*harga,2) as totalRp')
                        ->where('sisa_stok', '>', 0);
                    // ->groupBy('kode_rs', 'harga', 'kode_ruang');
                },
                'stok_awal' => function ($m) use ($fromA, $toA) {
                    $m->select('tanggal', 'harga', 'no_penerimaan', 'kode_rs', 'kode_ruang', 'sisa_stok as totalStok')
                        // ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->selectRaw('round(sisa_stok*harga,2) as totalRp')
                        ->whereBetween('tanggal', [$fromA, $toA]);
                    // ->groupBy('kode_rs', 'harga', 'kode_ruang');
                },
                'detailPenerimaan' => function ($m) use ($fromN, $toN) {
                    $m->select(
                        'detail_penerimaans.kode_rs',
                        'detail_penerimaans.qty as total',
                    )
                        // ->selectRaw('round(sum(qty),2) as total')
                        ->selectRaw('round(qty*harga_jadi,2) as totalRp')
                        ->leftJoin('penerimaans', function ($p) {
                            $p->on('penerimaans.id', '=', 'detail_penerimaans.penerimaan_id');
                        })
                        ->whereBetween('penerimaans.tanggal', [$fromN, $toN])
                        ->where('penerimaans.status', '>', 1);
                },
                'detailDistribusiLangsung' => function ($m) use ($from, $to) {
                    $m->select(
                        'distribusi_langsungs.ruang_tujuan',
                        'detail_distribusi_langsungs.kode_rs',
                        'detail_distribusi_langsungs.no_penerimaan',
                        'detail_distribusi_langsungs.jumlah as total',
                    )
                        ->leftJoin('distribusi_langsungs', function ($p) {
                            $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
                        })
                        ->whereBetween('distribusi_langsungs.tanggal', [$from, $to])
                        // ->with('recentstok')
                        ->where('distribusi_langsungs.status', '>', 1);
                },
                'detailPemakaianruangan' => function ($m) use ($from, $to) {
                    $m->select(
                        'details_pemakaianruangans.kode_rs',
                        'details_pemakaianruangans.no_penerimaan',
                        'details_pemakaianruangans.jumlah as total',
                        'pemakaianruangans.tanggal',
                        'pemakaianruangans.kode_ruang',
                    )
                        ->leftJoin('pemakaianruangans', function ($p) {
                            $p->on('pemakaianruangans.id', '=', 'details_pemakaianruangans.pemakaianruangan_id');
                        })
                        ->whereBetween('pemakaianruangans.tanggal', [$from, $to])
                        ->where('pemakaianruangans.status', '>', 1);
                },

            ]);


        $data = $barang->orderBy('kode_108', 'ASC')->withTrashed()->get();

        return new JsonResponse($data);
    }
}
