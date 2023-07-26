<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Pemesanan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananRinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemesananController extends Controller
{
    public function simpan(Request $request)
    {
        if($request->nopemesanan === '' || $request->nopemesanan === null)
        {
            DB::connection('farmasi')->select('call pemesanan_obat(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('pemesanan')->get();
            $wew = $x[0]->rencblobat;
            $nopemesanan = FormatingHelper::pemesananobat($wew, 'PES-BOBAT');

            $simpanheder = PemesananHeder::create([
                'nopemesanan' => $nopemesanan,
                'noperencanaan' => $request->noperencanaan,
                'tgl_pemesanan' => $request->tgl_pemesanan,
                'kdpbf' => $request->kdpbf,
                'user' => auth()->user()->pegawai_id
            ]);

            if(!$simpanheder)
            {
                return new JsonResponse(['message' => 'not ok'], 500);
            }

            $simpanrinci = PemesananRinci::create([
                'nopemesanan' => $nopemesanan,
                'kdobat'  => $request->kdobat,
                'stok_real_gudang'  => $request->stok_real_gudang,
                'stok_real_rs'  => $request->stok_real_rs,
                'stok_max_rs'  => $request->stok_max_rs,
                'jumlah_bisa_dibeli'  => $request->jumlah_bisa_dibeli,
                'tgl_stok'  => $request->tgl_stok,
                'jumlahdpesan'  => $request->jumlahdpesan,
                'user'  => auth()->user()->pegawai_id,
            ]);

            if(!$simpanrinci)
            {
                return new JsonResponse(['message' => 'not ok'], 500);
            }

            return new JsonResponse(
                [
                    'message' => 'ok',
                    'notrans' => $nopemesanan,
                    'heder' => $simpanheder,
                    'rinci' => $simpanrinci
                ], 200);
        }
    }

    public function listpemesanan()
    {
        $listpemesanan = PemesananHeder::with('pihakketiga')
        ->where('')
        ->get();
        return new JsonResponse($listpemesanan);
    }
}
