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
        $obat = Stokreal::selectRaw('*, sum(jumlah) as stok')
            ->with('obat:kd_obat,nama_obat,satuan_k', 'ruang:kode,uraian')
            ->where('jumlah', '>', 0)
            ->where('kdruang', request('kdruang'))
            ->when(request('q'), function ($query) {
                $kode = Mobatnew::select('kd_obat')
                    ->where('kd_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->get();
                $query->whereIn('kdobat', $kode);
            })
            ->groupBy('kdobat', 'kdruang')
            ->paginate(request('per_page'));

        return new JsonResponse($obat);
    }
}
