<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LihatStokController extends Controller
{

    public function lihatstokobateresep()
    {
        $groupsistembayar = request('groups');
        if ($groupsistembayar == '1') {
            $sistembayar = ['SEMUA', 'BPJS'];
        } else {
            $sistembayar = ['SEMUA', 'UMUM'];
        }
        $cariobat = Stokreal::select(
            'stokreal.kdobat as kdobat',
            'new_masterobat.nama_obat as namaobat',
            'new_masterobat.kandungan as kanduangan',
            'new_masterobat.satuan_k as satuankecil',
            'new_masterobat.kekuatan_dosis as kekuatandosis',
            'new_masterobat.volumesediaan as volumesediaan',
            DB::raw('sum(stokreal.jumlah) as total')
        )
            ->with(
                [
                    'minmax'
                ]
            )
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->where('kdruang', request('kdruang'))
            ->whereIn('new_masterobat.sistembayar', $sistembayar)
            ->groupBy('stokreal.kdobat')
            ->get();

        return new JsonResponse(
            [
                'dataobat' => $cariobat
            ]
        );
    }
}
