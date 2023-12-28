<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokrealController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
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
                        $stokdendiri->select('kdobat', 'kdruang', DB::raw('sum(stokreal.jumlah) as stokdendiri'))
                            ->groupBy('kdruang');
                    },
                    'mutasigudangkedepo'
                ]
            )
                ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
                ->where('flag', '!=', '')
                ->when($flag, function ($wew) use ($flag) {
                    $wew->where('flag', $flag);
                })
                ->orderBY('tgl_permintaan', 'desc')
                ->paginate(request('per_page'));
            return new JsonResponse($listpermintaandepo);
        } else {

            $listpermintaandepo = Permintaandepoheder::with([
                'permintaanrinci.masterobat', 'user:id,nip,nama',
                'permintaanrinci.stokreal' => function ($stokdendiri) {
                    $stokdendiri->select('kdobat', 'kdruang', DB::raw('sum(stokreal.jumlah) as stokdendiri'))
                        ->groupBy('kdruang');
                },
                'mutasigudangkedepo'
            ])
                ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
                ->where('tujuan', $gudang)
                ->where('flag', '!=', '')
                ->when($flag, function ($wew) use ($flag) {
                    $wew->where('flag', $flag);
                })
                ->orderBY('tgl_permintaan', 'desc')
                ->paginate(request('per_page'));
            return new JsonResponse($listpermintaandepo);
        }
    }

    // public function verifpermintaanobat(Request $request)
    // {
    //     if ($request->jumlah_diverif > $request->jumlah_minta) {
    //         return new JsonResponse(['message' => 'Maaf Jumlah Yang Diminta Tidak Sebanyak Itu....']);
    //     }
    //     $verifobat = Permintaandeporinci::where('id', $request->id)->update(
    //         [
    //             'jumlah_diverif' => $request->jumlah_diverif,
    //             'tgl_verif' => date('Y-m-d H:i:s'),
    //             'user_verif' => auth()->user()->pegawai_id
    //         ]
    //     );
    //     if (!$verifobat) {
    //         return new JsonResponse(['message' => 'Maaf Anda Gagal Memverif,Mohon Periksa Kembali Data Anda...!!!'], 500);
    //     }
    //     return new JsonResponse(['message' => 'Permintaan Obat Behasil Diverif...!!!'], 200);
    // }

    // public function rencanadistribusikedepo()
    // {
    //     $jenisdistribusi = request('jenisdistribusi');
    //     $gudang = request('kdgudang');
    //     $listrencanadistribusi = Permintaandeporinci::with(
    //         [
    //             'permintaanobatheder' => function ($permintaanobatheder) use ($gudang) {
    //                 $permintaanobatheder->when($gudang, function ($xxx) use ($gudang) {
    //                     $xxx->where('tujuan', $gudang)->where('flag', '1');
    //                 });
    //             },
    //             'masterobat'
    //         ]
    //     )->where('flag_distribusi', '')
    //         ->where('user_verif', '!=', '')
    //         ->when($jenisdistribusi, function ($wew) use ($jenisdistribusi) {
    //             $wew->where('status_obat', $jenisdistribusi);
    //         })
    //         ->paginate(request('per_page'));
    //     return new JsonResponse($listrencanadistribusi);
    // }



    public function simpandistribusidepo(Request $request)
    {
        $jmldiminta = $request->jumlah_minta;
        $caristok = Stokreal::where('kdobat', $request->kodeobat)->where('kdruang', $request->kdgudang)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();
        // $sisaStok = collect($caristok)->sum('jumlah');
        // $stok = $caristok[0]->jumlah;
        // $sisa = $stok - $jmldiminta;
        $index = 0;
        $masuk = $jmldiminta;
        while ($masuk > 0) {

            $sisa = $caristok[$index]->jumlah;
            if ($sisa < $masuk) {
                $sisax = $masuk - $sisa;
                Mutasigudangkedepo::create(
                    [
                        'no_permintaan' => $request->nopermintaan,
                        'nopenerimaan' => $caristok[$index]->nopenerimaan,
                        'kd_obat' => $caristok[$index]->kdobat,
                        'jml' => $sisa
                    ]
                );
                Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                    ->where('kdobat', $caristok[$index]->kdobat)
                    ->where('kdruang', $request->kdgudang)
                    ->update(['jumlah' => 0]);

                $masuk = $sisax;
                $index = $index + 1;
                //return $jmldiminta;
            } else {
                $sisax = $sisa - $masuk;
                Mutasigudangkedepo::create(
                    [
                        'no_permintaan' => $request->nopermintaan,
                        'nopenerimaan' => $caristok[$index]->nopenerimaan,
                        'kd_obat' => $caristok[$index]->kdobat,
                        'jml' => $masuk
                    ]
                );
                Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                    ->where('kdobat', $caristok[$index]->kdobat)
                    ->where('kdruang', $request->kdgudang)
                    ->update(['jumlah' => $sisax]);
                $masuk = 0;
            }
        }
        return new JsonResponse(['message' => 'Data Berhasil Disimpan'], 200);
    }

    public function kuncipermintaandaridepo(Request $request)
    {
        $user = FormatingHelper::session_user();
        $kuncipermintaan = Permintaandepoheder::where('no_permintaan', $request->no_permintaan)->first();
        $kuncipermintaan->flag = '2';
        $kuncipermintaan->tgl_terima = date('Y-m-d H:i:s');
        $kuncipermintaan->user_terima = $user['kodesimrs'];
        $kuncipermintaan->save();

        return new JsonResponse(['message' => 'Permintaan Berhasil Diterima...!!!'], 200);
    }

    public function distribusikan(Request $request)
    {
        $user = FormatingHelper::session_user();
        $kuncipermintaan = Permintaandepoheder::where('no_permintaan', $request->no_permintaan)->first();
        $kuncipermintaan->flag = '3';
        $kuncipermintaan->tgl_kirim_depo = date('Y-m-d H:i:s');
        $kuncipermintaan->user_kirim_depo = $user['kodesimrs'];
        $kuncipermintaan->save();

        return new JsonResponse(['message' => 'Permintaan Berhasil Didistribusikan...!!!'], 200);
    }
}
