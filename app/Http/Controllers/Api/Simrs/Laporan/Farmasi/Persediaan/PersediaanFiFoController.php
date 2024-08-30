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
                    $gd = ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
                    $st->select(
                        'stokreal.kdobat',
                        'stokreal.nopenerimaan as stpen',
                        DB::raw('sum(stokreal.jumlah) as jumlah'),
                        DB::raw('sum(stokreal.jumlah * penerimaan_r.harga_netto_kecil) as sub'),
                        'penerimaan_r.nopenerimaan',
                        'penerimaan_h.jenis_penerimaan',
                        'penerimaan_r.harga_netto_kecil as harga',
                    )
                        ->leftJoin('penerimaan_r', function ($jo) {
                            $jo->on('penerimaan_r.nopenerimaan', '=', 'stokreal.nopenerimaan')
                                ->on('penerimaan_r.kdobat', '=', 'stokreal.kdobat');
                        })
                        ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
                        ->where('stokreal.jumlah', '!=', 0)
                        ->groupBy('stokreal.kdobat', 'penerimaan_r.nopenerimaan', 'penerimaan_r.harga_netto_kecil');
                }
            ])
            ->where('nama_obat', 'LIKE', '%' . request('q') . '%')
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
