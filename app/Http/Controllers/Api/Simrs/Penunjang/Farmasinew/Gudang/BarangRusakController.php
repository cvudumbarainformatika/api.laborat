<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangRusakController extends Controller
{
    //
    public function cariObat()
    {
        $gudang = ['Gd-05010100', 'Gd-03010100'];
        $data = Mobatnew::select(
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
            'new_masterobat.satuan_b',
            DB::raw('sum(stokreal.jumlah) as jumlah'),
        )
            ->leftJoin('stokreal', 'stokreal.kdobat', '=', 'new_masterobat.kd_obat')
            ->when(request('q'), function ($q) {
                $q->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%');
            })
            ->whereIn('stokreal.kdruang', $gudang)
            ->where('stokreal.jumlah', '>', 0)
            ->groupBy('new_masterobat.kd_obat')
            ->limit(50)
            ->get();
        return new JsonResponse($data);
    }
    public function cariBatch()
    {
        $gudang = ['Gd-05010100', 'Gd-03010100'];
        $data = Stokrel::selectRaw('nobatch')
            ->whereIn('stokreal.kdruang', $gudang)
            ->when(request('batch'), function ($q) {
                $q->where('nobatch', 'LIKE', '%' . request('batch') . '%');
            })
            ->where('kdobat', request('kdobat'))
            ->where('jumlah', '>', 0)
            // ->with(
            //     'penerimaan:nopenerimaan,kdpbf',
            //     'penerimaan.pihakketiga:kode,nama'
            // )
            ->groupBy('nobatch')
            ->limit(50)
            ->get();
        return new JsonResponse($data);
    }
    public function cariPenerimaan()
    {
        $gudang = ['Gd-05010100', 'Gd-03010100'];
        $data = Stokrel::selectRaw('*, sum(jumlah) as total')
            ->whereIn('stokreal.kdruang', $gudang)
            ->when(request('noper'), function ($q) {
                $q->where('nopenerimaan', 'LIKE', '%' . request('noper') . '%');
            })
            ->where('kdobat', request('kdobat'))
            ->where('nobatch', request('nobatch'))
            ->where('jumlah', '>', 0)
            ->with(
                'penerimaan:nopenerimaan,kdpbf',
                'penerimaan.pihakketiga:kode,nama',
                'penerimaan.penerimaanrinci:nopenerimaan,isi'
            )
            ->groupBy('kdobat', 'nobatch', 'nopenerimaan')
            ->limit(50)
            ->get();
        return new JsonResponse($data);
    }
}
