<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Persediaan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
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
}
