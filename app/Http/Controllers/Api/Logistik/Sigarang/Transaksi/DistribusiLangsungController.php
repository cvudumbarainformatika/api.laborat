<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\RecentStokUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistribusiLangsungController extends Controller
{
    //
    public function getStokDepo()
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);

        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as totalStok')
            ->where('sisa_stok', '>', 0)
            ->where('kode_ruang', $pegawai->kode_ruang)
            ->groupBy('kode_rs', 'kode_ruang')
            ->with('barang', 'depo', 'satuan')
            ->get();

        return new JsonResponse($data, 200);
    }
}
