<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepoController extends Controller
{
    public function lihatstokgudang()
    {

        $gudang = request('kdgudang');
        $depo = request('kddepo');
        $stokgudang = Stokrel::select(
            'stokreal.*',
            'new_masterobat.*',
            DB::raw('sum(stokreal.jumlah) as  jumlah'),
            'new_masterobat.nama_obat as nama_obat'
        )->with([
            'permintaanobatrinci' => function ($permintaanobatrinci) {
                $permintaanobatrinci->select(
                    'permintaan_r.kdobat',
                    DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                )
                    ->leftjoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                    ->where('permintaan_h.flag', '');
            },
            'minmax' => function ($mimnmax) use ($depo) {
                $mimnmax->select('kd_obat', 'kd_ruang', 'max')->when($depo, function ($xxx) use ($depo) {
                    $xxx->where('kd_ruang', $depo);
                });
            }
        ])
            ->join('new_masterobat', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->when($gudang, function ($wew) use ($gudang) {
                $wew->where('stokreal.kdruang', $gudang);
            })
            ->where('new_masterobat.nama_obat', 'Like', '%' . request('nama_obat') . '%')
            ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
            ->get();
        $datastok = $stokgudang->map(function ($xxx) {
            $stolreal = $xxx->jumlah;
            $permintaantotal = count($xxx->permintaanobatrinci) > 0 ? $xxx->permintaanobatrinci[0]->allpermintaan : 0;
            $stokalokasi = (int) $stolreal - (int) $permintaantotal;
            $xxx['stokalokasi'] = $stokalokasi;
            return $xxx;
        });

        $stokdewe = Stokrel::select('kdobat', DB::raw('sum(stokreal.jumlah) as  jumlah'), 'kdruang')
            ->when($depo, function ($wew) use ($depo) {
                $wew->where('stokreal.kdruang', $depo);
            })->groupBy('stokreal.kdobat', 'stokreal.kdruang')
            ->get();

        return new JsonResponse(
            [
                'obat' => $datastok,
                'stokdewe' => $stokdewe
            ]
        );
    }

    public function simpanpermintaandepo(Request $request)
    {
        $stokreal = Stokrel::select('jumlah as stok')->where('kdobat', $request->kdobat)->where('kdruang', $request->tujuan)->first();
        $stokrealx = (int) $stokreal->stok;
        $allpermintaan = Permintaandeporinci::select(DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan'))
            ->leftjoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
            ->where('permintaan_h.flag', '')->where('kdobat', $request->kdobat)->where('tujuan', $request->tujuan)
            ->groupby('kdobat')->get();
        $allpermintaanx =  $allpermintaan[0]->allpermintaan ?? '';
        $stokalokasi = $stokrealx - (int) $allpermintaanx;

        if ($request->jumlah_minta > $stokalokasi) {
            return new JsonResponse(['message' => 'Maaf Stok Alokasi Tidak mencukupi...!!!'], 500);
        }

        if ($request->no_permintaan === '' || $request->no_permintaan === null) {
            DB::connection('farmasi')->select('call permintaandepo(@nomor) ');
            $x = DB::connection('farmasi')->table('conter')->select('permintaandepo')->get();
            $wew = $x[0]->permintaandepo;
            $nopermintaandepo = FormatingHelper::permintaandepo($wew, 'REQ-DEPO');
        } else {
            $nopermintaandepo = $request->no_permintaan;
        }

        $simpanpermintaandepo = Permintaandepoheder::updateorcreate(
            [
                'no_permintaan' => $nopermintaandepo,
            ],
            [
                'tgl_permintaan' => $request->tgl_permintaan ?? date('Y-m-d H:i:s'),
                'dari' => $request->dari,
                'tujuan' => $request->tujuan,
                'user' => auth()->user()->pegawai_id
            ]
        );
        if (!$simpanpermintaandepo) {
            return new JsonResponse(['message' => 'Permintaan Gagal Disimpan...!!!'], 500);
        }

        $simpanrincipermintaandepo = Permintaandeporinci::updateorcreate(
            [
                'no_permintaan' => $nopermintaandepo,
                'kdobat' => $request->kdobat
            ],
            [
                'stok_alokasi' => $request->stok_alokasi,
                'mak_stok' => $request->mak_stok,
                'jumlah_minta' => $request->jumlah_minta,
                'status_obat' => $request->status_obat
            ]
        );

        if (!$simpanrincipermintaandepo) {
            return new JsonResponse(['message' => 'Permintaan Gagal Disimpan...!!!'], 500);
        }
        return new JsonResponse(
            [
                'message' => 'Data Berhasil Disimpan...!!!',
                'notrans' => $nopermintaandepo,
                'heder' => $simpanpermintaandepo,
                'rinci' => $simpanrincipermintaandepo,
                'stokalokasi' => $stokalokasi
            ]
        );
    }

    public function kuncipermintaan(Request $request)
    {
        $kuncipermintaan = Permintaandepoheder::where('no_permintaan', $request->no_permintaan)->update(['flag' => '1']);
        if (!$kuncipermintaan) {
            return new JsonResponse(['message' => 'Maaf Permintaan Gagal Dikirim Ke Gudang,Moho Periksa Kembali Data Anda...!!!'], 500);
        }
        return new JsonResponse(['message' => 'Permintaan Berhasil Dikirim Kegudang...!!!'], 200);
    }

    public function listpermintaandepo()
    {
        $depo = request('kddepo');
        $nopermintaan = request('no_permintaan');
        if ($depo === '' || $depo === null) {
            $listpermintaandepo = Permintaandepoheder::with('permintaanrinci.masterobat')
                ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
                ->orderBY('tgl_permintaan', 'desc')
                ->get();
            return new JsonResponse($listpermintaandepo);
        } else {

            $listpermintaandepo = Permintaandepoheder::with('permintaanrinci.masterobat')
                ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
                ->where('dari', $depo)
                ->orderBY('tgl_permintaan', 'desc')
                ->get();
            return new JsonResponse($listpermintaandepo);
        }
    }

    // public function lihatstokgudang()
    // {

    //     $gudang = request('kdgudang');
    //     $depo = request('kddepo');
    //     $stokgudang = Stokrel::select(
    //         'stokreal.*',
    //         'new_masterobat.*',
    //         DB::raw('sum(stokreal.jumlah) as  jumlah'),
    //         'new_masterobat.nama_obat as nama_obat'
    //     )->with([
    //         'permintaanobatrinci' => function ($permintaanobatrinci) {
    //             $permintaanobatrinci->select(
    //                 'permintaan_r.kdobat',
    //                 DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
    //             )
    //                 ->leftjoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
    //                 ->where('permintaan_h.flag', '');
    //         },
    //         'minmax' => function ($mimnmax) use ($depo) {
    //             $mimnmax->select('kd_obat', 'kd_ruang', 'max')->when($depo, function ($xxx) use ($depo) {
    //                 $xxx->where('kd_ruang', $depo);
    //             });
    //         }
    //     ])
    //         ->join('new_masterobat', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
    //         ->when($gudang, function ($wew) use ($gudang) {
    //             $wew->where('stokreal.kdruang', $gudang);
    //         })
    //         ->where('new_masterobat.nama_obat', 'Like', '%' . request('nama_obat') . '%')
    //         //    ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
    //         ->get();
    //     $datastok = $stokgudang->groupBy()->map(function ($xxx) {
    //         $stolreal = $xxx->jumlah;
    //         $permintaantotal = count($xxx->permintaanobatrinci) > 0 ? $xxx->permintaanobatrinci[0]->allpermintaan : 0;
    //         $stokalokasi = (int) $stolreal - (int) $permintaantotal;
    //         $xxx['stokalokasi'] = $stokalokasi;
    //         return $xxx;
    //     });
    //     return new JsonResponse(
    //         [
    //             'obat' => $stokgudang
    //         ]
    //     );
    // }
}
