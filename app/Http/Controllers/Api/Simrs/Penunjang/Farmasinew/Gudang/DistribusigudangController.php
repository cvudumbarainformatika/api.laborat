<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistribusigudangController extends Controller
{
    public function listpermintaandepo()
    {
        $gudang = request('kdgudang');
        $nopermintaan = request('no_permintaan');
        $flag = request('flag');
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
                ->where('flag', '!=', '')
                ->when($flag, function ($wew) use ($flag) {
                    $wew->where('flag', $flag);
                })
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
                'tgl_verif' => date('Y-m-d H:i:s'),
                'user_verif' => auth()->user()->pegawai_id
            ]
        );
        if (!$verifobat) {
            return new JsonResponse(['message' => 'Maaf Anda Gagal Memverif,Mohon Periksa Kembali Data Anda...!!!'], 500);
        }
        return new JsonResponse(['message' => 'Permintaan Obat Behasil Diverif...!!!'], 200);
    }

    public function rencanadistribusikedepo()
    {
        $jenisdistribusi = request('jenisdistribusi');
        $gudang = request('kdgudang');
        $listrencanadistribusi = Permintaandeporinci::with(
            [
                'permintaanobatheder' => function ($permintaanobatheder) use ($gudang) {
                    $permintaanobatheder->when($gudang, function ($xxx) use ($gudang) {
                        $xxx->where('tujuan', $gudang)->where('flag', '1');
                    });
                },
                'masterobat'
            ]
        )->where('flag_distribusi', '')
            ->where('user_verif', '!=', '')
            ->when($jenisdistribusi, function ($wew) use ($jenisdistribusi) {
                $wew->where('status_obat', $jenisdistribusi);
            })
            ->get();
        return new JsonResponse($listrencanadistribusi);
    }

    public function simpandistribusidepo(Request $request)
    {
        $simpandistribusidepo = Permintaandeporinci::where('id', $request->id)->update(
            [
                'flag_distribusi' => '1',
                'tgl_distribusi' => date('Y-m-d H:i:s'),
                'user_distribusi' => auth()->user()->pegawai_id
            ]
        );
        if (!$simpandistribusidepo) {
            return new JsonResponse(['message' => 'Maaf Anda Gagal Mendistribusikan Obat,Mohon Periksa Kembali Data Anda...!!!'], 500);
        }
        //  $stok = Stokrel::where()
        return new JsonResponse(['message' => ' Obat Behasil Didistribusikan...!!!'], 200);
    }
}
