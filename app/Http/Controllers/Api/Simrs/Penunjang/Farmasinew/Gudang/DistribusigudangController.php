<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistribusigudangController extends Controller
{
    public function listpermintaandepo()
    {
        $gudang = request('kdgudang');
        $nopermintaan = request('no_permintaan');
        if ($gudang === '' || $gudang === null) {
            $listpermintaandepo = Permintaandepoheder::with(
                [
                    'permintaanrinci.masterobat',
                    'user:id,nip,nama',
                    'permintaanrinci.stokreal' => function ($stokdendiri) {
                        $stokdendiri->select('kdobat', 'kdobat', DB::raw('sum(stokreal.jumlah) as stokdendiri'))
                            ->groupBy('kdruang');
                    }
                ]
            )
                ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
                ->where('flag', '1')
                ->orderBY('tgl_permintaan', 'desc')
                ->get();
            return new JsonResponse($listpermintaandepo);
        } else {

            $listpermintaandepo = Permintaandepoheder::with([
                'permintaanrinci.masterobat', 'user:id,nip,nama',
                'permintaanrinci.stokreal' => function ($stokdendiri) {
                    $stokdendiri->select('kdobat', 'kdobat', DB::raw('sum(stokreal.jumlah) as stokdendiri'))
                        ->groupBy('kdruang');
                }
            ])
                ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
                ->where('tujuan', $gudang)
                ->where('flag', '1')
                ->orderBY('tgl_permintaan', 'desc')
                ->get();
            return new JsonResponse($listpermintaandepo);
        }
    }

    public function verifpermintaanobat(Request $request)
    {
        if ($request->jumlah_diverif > $request->jumlah_minta) {
            return new JsonResponse(['message' => 'Maaf Jumlah Yang Diminta Tidak Sebanyak Itu....']);
        }
        $verifobat = Permintaandeporinci::where('id', $request->id)->update(
            [
                'jumlah_diverif' => $request->jumlah_diverif,
                'tgl_verif' => date('Y-m-d H:i:s')
            ]
        );
        if (!$verifobat) {
            return new JsonResponse(['message' => 'Maaf Anda Gagal Memverif,Moho Periksa Kembali Data Anda...!!!'], 500);
        }
        return new JsonResponse(['message' => 'Permintaan Obat Behasil Diverif...!!!'], 200);
    }
}
