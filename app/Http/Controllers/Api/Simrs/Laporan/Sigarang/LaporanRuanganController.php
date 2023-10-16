<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\Ruang;
use App\Models\Sigarang\Transaksi\DistribusiLangsung\DetailDistribusiLangsung;
use App\Models\Sigarang\Transaksi\Pemakaianruangan\DetailsPemakaianruangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\DetailPermintaanruangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanRuanganController extends Controller
{
    public function getBarang()
    {
        $minta = DetailPermintaanruangan::distinct()->get('kode_rs');
        $dist = DetailDistribusiLangsung::distinct()->get('kode_rs');
        // return new JsonResponse(['minta' => $minta, 'dist' => $dist]);
        $data = BarangRS::select(
            'kode',
            'nama',
        )
            ->whithTrased()
            ->get();
        return new JsonResponse($data);
    }
    public function lapPengeluaranDepo()
    {
        $minta = DetailPermintaanruangan::select('kode_rs')->distinct()
            ->leftJoin('permintaanruangans', function ($p) {
                $p->on('permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id');
            })
            ->whereBetween('permintaanruangans.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->whereIn('permintaanruangans.status', [7, 8])
            ->where('detail_permintaanruangans.jumlah_distribusi', '>', 0)
            ->when(request('kode_ruang'), function ($q) {
                if (request('kode_ruang') !== 'Gd-02010102') {
                    $q->where('permintaanruangans.dari', request('kode_ruang'));
                }
            })
            ->when(request('q'), function ($q) {
                $anu = BarangRS::select('kode')->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')->get();
                $q->whereIn('detail_permintaanruangans.kode_rs', $anu);
            })
            ->get();
        $dist = DetailDistribusiLangsung::select('kode_rs')->distinct()
            ->leftJoin('distribusi_langsungs', function ($p) {
                $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
            })
            ->when(request('kode_ruang') === 'Gd-02010102', function ($q) {
                $q->where('distribusi_langsungs.ruang_tujuan', request('kode_ruang'));
            })
            ->when(request('q'), function ($q) {
                $anu = BarangRS::select('kode')->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')->get();
                $q->whereIn('detail_distribusi_langsungs.kode_rs', $anu);
            })
            ->whereBetween('distribusi_langsungs.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->get();
        $result = BarangRS::select([
            'permintaanruangans.tanggal',
            'distribusi_langsungs.tanggal as tanggal_l',
            'detail_permintaanruangans.no_penerimaan',
            'detail_permintaanruangans.kode_rs',
            'detail_distribusi_langsungs.kode_rs as kode_rs_l',
            'barang_r_s.nama',
            'satuans.nama as satuan',
            'ruangs.uraian as tujuan',
            DB::raw('ROUND(sum(detail_distribusi_langsungs.jumlah),2) as jumlah_distribusi_l'),
            DB::raw('ROUND(sum(detail_permintaanruangans.jumlah),2) as jumlah'),
            DB::raw('ROUND(sum(detail_permintaanruangans.jumlah_disetujui),2) as jumlah_disetujui'),
            DB::raw('ROUND(sum(detail_permintaanruangans.jumlah_distribusi),2) as jumlah_distribusi'),
        ])
            ->leftJoin('detail_permintaanruangans', function ($b) {
                $b->on('detail_permintaanruangans.kode_rs', '=', 'barang_r_s.kode')
                    ->leftJoin('permintaanruangans', function ($p) {
                        $p->on('permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id');
                    });
            })
            ->leftJoin('detail_distribusi_langsungs', function ($b) {
                $b->on('detail_distribusi_langsungs.kode_rs', '=', 'barang_r_s.kode')
                    ->leftJoin('distribusi_langsungs', function ($p) {
                        $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
                    });
            })
            ->leftJoin('ruangs', function ($p) {
                $p->on('ruangs.kode', '=', 'detail_permintaanruangans.tujuan')
                    ->orOn('ruangs.kode', '=', 'distribusi_langsungs.ruang_tujuan');
            })
            ->leftJoin('satuans', function ($s) {
                $s->on('satuans.kode', '=', 'barang_r_s.kode_satuan');
            })
            ->where(function ($q) use ($minta) {
                $q->whereBetween('permintaanruangans.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                    ->whereIn('barang_r_s.kode', $minta);
            })
            ->orWhere(function ($q) use ($dist) {
                $q->whereBetween('distribusi_langsungs.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                    ->whereIn('barang_r_s.kode', $dist);
            })
            ->groupBy(
                'barang_r_s.kode',
                'permintaanruangans.tanggal',
                'distribusi_langsungs.tanggal',
                'detail_permintaanruangans.tujuan',
                'distribusi_langsungs.ruang_tujuan'
            )
            ->orderBy('barang_r_s.nama', 'ASC')
            // ->orderBy('permintaanruangans.tanggal', 'ASC')
            // ->orderBy('distribusi_langsungs.tanggal', 'ASC')
            ->withTrashed()
            // ->get();
            ->paginate(request('per_page'));
        $data = $result;

        return new JsonResponse($data);
    }
    public function lapPemakaianRuangan()
    {
        // $minta = DetailsPemakaianruangan::select('kode_rs')->distinct()
        //     ->leftJoin('pemakaianruangans', function ($p) {
        //         $p->on('pemakaianruangans.id', '=', 'detaisl_pemakaianruangans.pemakaianruangan_id');
        //     })
        //     ->whereBetween('pemakaianruangans.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
        //     ->when(request('kode_ruang'), function ($q) {
        //         if (request('kode_ruang') !== 'Gd-02010102') {
        //             $q->where('pemakaianruangans.dari', request('kode_ruang'));
        //         }
        //     })
        //     ->when(request('q'), function ($q) {
        //         $anu = BarangRS::select('kode')->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
        //             ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')->get();
        //         $q->whereIn('details_pemakaianruangans.kode_rs', $anu);
        //     })
        //     ->get();
        $result = BarangRS::select([
            'pemakaianruangans.tanggal',
            'details_pemakaianruangans.no_penerimaan',
            'details_pemakaianruangans.kode_rs',
            'barang_r_s.nama',
            'satuans.nama as satuan',
            'ruangs.uraian as ruang',
            DB::raw('ROUND(sum(details_pemakaianruangans.jumlah),2) as jumlah'),
        ])
            ->leftJoin('details_pemakaianruangans', function ($b) {
                $b->on('details_pemakaianruangans.kode_rs', '=', 'barang_r_s.kode')
                    ->leftJoin('pemakaianruangans', function ($p) {
                        $p->on('pemakaianruangans.id', '=', 'details_pemakaianruangans.pemakaianruangan_id');
                    });
            })
            ->leftJoin('ruangs', function ($p) {
                $p->on('ruangs.kode', '=', 'pemakaianruangans.kode_ruang');
            })
            ->leftJoin('satuans', function ($s) {
                $s->on('satuans.kode', '=', 'barang_r_s.kode_satuan');
            })
            ->whereBetween('pemakaianruangans.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->when(request('kode_ruang'), function ($q) {
                $anu = Ruang::select('kode')->where('kode', 'LIKE', '%' . request('kode_ruang') . '%')
                    ->orWhere('uraian', 'LIKE', '%' . request('kode_ruang') . '%')->get();
                $q->whereIn('pemakaianruangans.kode_ruang', $anu);
            })
            ->when(request('q'), function ($q) {
                $q->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')->get();
            })
            ->groupBy(
                'barang_r_s.kode',
                'pemakaianruangans.tanggal',
                'pemakaianruangans.kode_ruang',
            )
            ->orderBy('ruangs.uraian', 'ASC')
            // ->orderBy('pemakaianruangans.tanggal', 'ASC')
            // ->orderBy('distribusi_langsungs.tanggal', 'ASC')
            ->withTrashed()
            // ->get();
            ->paginate(request('per_page'));
        $data = $result;

        return new JsonResponse($data);
    }
}