<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Ruangan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemakaianRuanganController extends Controller
{
    //
    public function getStokRuangan()
    {
        // $obat = Mobatnew::has('stok')
        //     ->with([
        //         'stok' => function ($q) {
        //             $q->where('jumlah', '>', 0)
        //                 ->where('kdruang', request('kdruang'));
        //         }
        //     ])

        //     ->paginate(request('per_page'));
        // $obat = Mobatnew::select(
        //     'new_masterobat.kd_obat',
        //     'new_masterobat.nama_obat',
        //     'stokreal.kdobat',
        //     'stokreal.nopenerimaan',
        //     'stokreal.jumlah',
        //     'stokreal.harga',
        //     'stokreal.nobatch',
        //     'stokreal.tglexp',
        // )
        //     ->leftJoin('stokreal', 'stokreal.kdobat', '=', 'new_masterobat.kd_obat')
        //     ->where('stokreal.jumlah', '>', 0)
        //     ->where('stokreal.kdruang', request('kdruang'))
        //     ->groupBy('stokreal.kdobat', 'stokreal.kdruang', 'stokreal.nopenerimaan')
        //     ->paginate(request('per_page'));

        $bKode = Stokreal::select('kdobat')
            ->distinct()
            ->where('jumlah', '>', 0)
            ->where('kdruang', request('kdruang'))
            ->paginate(request('per_page'));
        $col = collect($bKode);
        $kode = $col['data'];
        $meta = $col->except('data');
        $obat = Stokreal::selectRaw('*, sum(jumlah) as stok')
            ->with('obat:kd_obat,nama_obat')
            ->where('jumlah', '>', 0)
            ->where('kdruang', request('kdruang'))
            ->groupBy('kdobat', 'kdruang')
            ->paginate(request('per_page'));
        // ->get();

        return new JsonResponse([
            'kode' => $kode,
            'meta' => $meta,
            'data' => $obat,
        ]);
    }
}
