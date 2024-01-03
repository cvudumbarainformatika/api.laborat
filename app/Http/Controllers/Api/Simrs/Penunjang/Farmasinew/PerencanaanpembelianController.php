<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mminmaxobat;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliH;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliR;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerencanaanpembelianController extends Controller
{
    public function perencanaanpembelian()
    {
        $perencanaapembelianobat = Mminmaxobat::select(
            'min_max_ruang.kd_obat as kd_obat',
            'new_masterobat.nama_obat as namaobat',
            DB::raw('sum(min_max_ruang.min) as summin'),
            DB::raw('sum(min_max_ruang.max) as summax'),
            DB::raw('round(sum(stokreal.jumlah)) as stok'),
        )
            ->leftjoin('stokreal', 'stokreal.kdobat', 'min_max_ruang.kd_obat')
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'min_max_ruang.kd_obat')
            ->havingRaw('stok <= summin')
            ->where('min_max_ruang.kd_ruang', 'like', '%GD%')
            ->where('new_masterobat.nama_obat', 'like', '%' . request('namaobat') . '%')
            ->groupby('min_max_ruang.kd_obat')
            ->get();
        return new JsonResponse($perencanaapembelianobat);
        // $xxx = FormatingHelper::session_user();
        // if ($xxx['kdruang'] === '') {
        //     $ruangan = ['', 'Gd-05010100', 'Gd-03010100'];
        // } else {
        //     $ruangan = ['', $xxx['kdruang']];
        // }

        // $perencanaapembelianobat = Mobatnew::select(
        //     'kd_obat',
        //     'nama_obat',
        //     'status_generik',
        //     'status_fornas',
        //     'status_forkid',
        //     'satuan_k',
        //     'sistembayar',
        //     'gudang'
        // )->with(
        //     [
        //         'stokrealgudang' => function ($stokrealgudang) {
        //             $stokrealgudang->select(
        //                 'stokreal.kdobat',
        //                 DB::raw(
        //                     'sum(stokreal.jumlah) as jumlah'
        //                 )
        //             )
        //                 ->whereIn(
        //                     'stokreal.kdruang',
        //                     ['Gd-03010100', 'Gd-05010100']
        //                 )
        //                 ->groupBy('stokreal.kdobat');
        //         },
        //         'stokrealgudangfs' => function ($stokrealgudangfs) {
        //             $stokrealgudangfs->select(
        //                 'stokreal.kdobat',
        //                 DB::raw(
        //                     'sum(stokreal.jumlah) as jumlah'
        //                 )
        //             )
        //                 ->where(
        //                     'stokreal.kdruang',
        //                     'Gd-03010100'
        //                 )
        //                 ->groupBy('stokreal.kdobat');
        //         },
        //         'stokrealgudangko' => function ($stokrealgudangko) {
        //             $stokrealgudangko->select(
        //                 'stokreal.kdobat',
        //                 DB::raw(
        //                     'sum(stokreal.jumlah) as jumlah'
        //                 )
        //             )
        //                 ->where(
        //                     'stokreal.kdruang',
        //                     'Gd-05010100'
        //                 )
        //                 ->groupBy('stokreal.kdobat');
        //         },
        //         'stokrealallrs' => function ($stokrealallrs) {
        //             $stokrealallrs->select(
        //                 'stokreal.kdobat',
        //                 DB::raw(
        //                     'sum(stokreal.jumlah) as jumlah'
        //                 )
        //             )->groupBy('stokreal.kdobat');
        //         },
        //         'stokmaxrs' => function ($stokmaxrs) {
        //             $stokmaxrs->select(
        //                 'min_max_ruang.kd_obat',
        //                 DB::raw(
        //                     'sum(min_max_ruang.max) as jumlah'
        //                 )
        //             )->groupBy('min_max_ruang.kd_obat');
        //         },
        //         'perencanaanrinci' => function ($perencanaanrinci) {
        //             $perencanaanrinci->select(
        //                 'kdobat',
        //                 DB::raw(
        //                     'sum(jumlahdirencanakan) as jumlah'
        //                 )
        //             )->where('flag', '')
        //                 ->groupBy('kdobat');
        //         },
        //         'stokmaxpergudang' => function ($stokmaxpergudang) use ($ruangan) {
        //             $stokmaxpergudang->select(
        //                 'kd_ruang',
        //                 'min_max_ruang.kd_obat',
        //                 'min_max_ruang.max as jumlah'
        //             )->whereIn('kd_ruang', $ruangan);
        //         },
        //     ]
        // )
        //     ->where(function ($obat) {
        //         $obat->where('nama_obat', 'Like', '%' . request('q') . '%')
        //             ->orWhere('kd_obat', 'Like', '%' . request('q') . '%');
        //     })
        //     ->where('flag', '')
        //     ->whereIn('gudang', $ruangan)
        //     ->orderBy('kd_obat')
        //     ->paginate(request('per_page'));

        // return new JsonResponse($perencanaapembelianobat);
    }

    public function viewrinci()
    {
        $viewrinci = Stokreal::select(
            'stokreal.kdobat as kdobat',
            'stokreal.kdruang as kdruang',
            'new_masterobat.nama_obat as namaobat',
            DB::raw('sum(stokreal.jumlah) as jumlah'),

        )->with(
            [
                'gudangdepo:kode,nama',
                // 'gudanggudangdepo.minmax'
            ]
        )
            ->leftjoin('new_masterobat', 'stokreal.kdobat', 'new_masterobat.kd_obat')
            ->groupby('stokreal.kdobat', 'stokreal.kdruang')
            ->where('kdobat', request('kdobat'))->get();

        $viewrinciminmax = Mminmaxobat::where('kd_obat', request('kdobat'))
            ->where('kd_ruang', 'like', '%GD%')
            ->get();
        return new JsonResponse([
            'viewrincistok' => $viewrinci,
            'viewrinciminmax' => $viewrinciminmax,
        ]);
    }

    public function simpanrencanabeliobat(Request $request)
    {
        //$cekflag = RencanabeliR::where('kdobat', $request->kdobat)->where('flag', '')->count();
        $xxx = FormatingHelper::session_user();
        $cekflag = RencanabeliH::select(
            'perencana_pebelian_h.no_rencbeliobat as notrans',
            'perencana_pebelian_h.kd_ruang as gudang',
            'perencana_pebelian_r.no_rencbeliobat'
        )
            ->leftjoin('perencana_pebelian_r', 'perencana_pebelian_h.no_rencbeliobat', 'perencana_pebelian_r.no_rencbeliobat')
            ->where('perencana_pebelian_h.kd_ruang', $request->kd_ruang)
            ->where('perencana_pebelian_r.kdobat', $request->kdobat)
            ->where('perencana_pebelian_r.flag', '')
            ->count();

        if ($cekflag > 0) {
            return new JsonResponse(['message' => 'maaf obat ini masih dalam proses pemesanan...!!!'], 500);
        }

        $cekminmax = Mminmaxobat::where('kd_obat', $request->kdobat)->where('kd_ruang', $request->kd_ruang)->first();
        $maxobat = (int) $cekminmax['max'] ?? 0;
        $cekstok = Stokrel::where('kdobat', $request->kdobat)->where('kdruang', $request->kd_ruang)->first();
        $stok = $cekstok['jumlah'] ?? 0;
        $max = $maxobat - $stok;

        if ($request->jumlahdpesan > $max) {
            return new JsonResponse(['message' => 'Maaf Jumlah yang Dipesan melebihi jumlah yang boleh dipesan...!!!'], 500);
        }

        if ($request->norencanabeliobat === '' || $request->norencanabeliobat === null) {

            DB::connection('farmasi')->select('call rencana_beliobat(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('rencblobat')->get();
            $wew = $x[0]->rencblobat;
            $norencanabeliobat = FormatingHelper::norencanabeliobat($wew, 'REN-BOBAT');
        } else {
            $norencanabeliobat = $request->norencanabeliobat;
        }
        $simpanheder = RencanabeliH::updateorcreate(
            [
                'no_rencbeliobat' => $norencanabeliobat
            ],
            [
                'tgl' => date('Y-m-d'),
                'user' => $xxx['kodesimrs'],
                'kd_ruang' => $request->kd_ruang
            ]
        );

        if (!$simpanheder) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }

        $simpanrinci = RencanabeliR::updateorcreate(
            [
                'no_rencbeliobat' => $norencanabeliobat,
                'kdobat' => $request->kdobat,
                'jumlahdirencanakan'  => $request->jumlahdpesan
            ],
            [
                'stok_real_gudang' => $request->stok_real_gudang,
                'stok_real_rs'  => $request->stok_real_rs,
                'stok_max_rs'  => $request->stok_max_rs,
                'jumlah_bisa_dibeli'  => $request->jumlah_bisa_dibeli,
                'tgl_stok'  => $request->tgl_stok,
                'pabrikan'  => $request->pabrikan,
                'pbf'  => $request->pbf,
                'user'  => $xxx['kodesimrs']
            ]
        );

        if (!$simpanrinci) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }

        return new JsonResponse(
            [
                'message' => 'ok',
                'notrans' => $norencanabeliobat,
                'heder' => $simpanheder,
                'rinci' => $simpanrinci
            ],
            200
        );
    }

    public function listrencanabeli()
    {
        $rencanabeli = RencanabeliH::with('rincian')->where('no_rencbeliobat', 'LIKE', '%' . request('no_rencbeliobat') . '%')
            ->orderBy('tgl', 'desc')->paginate(request('per_page'));
        return new JsonResponse($rencanabeli);
    }

    public function kuncirencana(Request $request)
    {
        $kunci = RencanabeliH::where('no_rencbeliobat', $request->no_rencbeliobat)
            ->update(['flag' => 1]);
        if (!$kunci) {
            return new JsonResponse(['message' => 'gagal mengupdate data'], 500);
        }
        return new JsonResponse(['message' => 'ok'], 200);
    }
}
