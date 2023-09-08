<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepoController extends Controller
{
    public function lihatstokgudang()
    {

        $gudang = request('kdgudang');
        $stokgudang = Stokrel::select(
            'stokreal.*',
            'new_masterobat.*',
            DB::raw('sum(stokreal.jumlah) as  jumlah'),
            DB::raw('sum(permintaan_r.jumlah_minta) as stokalokasi'),
            'new_masterobat.nama_obat as nama_obat'
        )->join('new_masterobat', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->leftjoin('permintaan_r', 'new_masterobat.kd_obat', '=', 'permintaan_r.kdobat')
            ->where('stokreal.kdruang', $gudang)
            ->where('new_masterobat.nama_obat', 'Like', '%' . request('nama_obat') . '%')
            ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
            ->get();
        return $stokgudang;
    }
}
