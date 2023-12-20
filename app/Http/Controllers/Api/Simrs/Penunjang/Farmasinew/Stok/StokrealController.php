<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokrealController extends Controller
{
    public static function stokreal($nopenerimaan, $request)
    {
        //return ($request->kdobat);
        $simpanstokreal = Stokrel::updateOrCreate(
            [
                'nopenerimaan' => $nopenerimaan,
                'kdobat' => $request->kdobat,
                'kdruang' => $request->kdruang,
                'nobatch' => $request->no_batch,
                'tglexp' => $request->tgl_exp,
                'harga' => $request->harga_kcl
            ],
            [
                'tglpenerimaan' => $request->tglpenerimaan,
                'jumlah' => $request->jml_terima_k,
                'flag' => 1

            ]
        );
        if (!$simpanstokreal) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }
        return 200;
    }

    public static function updatestokgudangdandepo($request)
    {
        $jml_dikeluarkan = (int) $request->jumlah_diverif;
        $totalstok = Stokrel::select(DB::raw('sum(stokreal.jumlah) as totalstok'))
            ->where('kdobat', $request->kdobat)
            ->where('kdruang', $request->kdruang)
            ->where('jumlah', '!=', 0)
            ->groupBy('kdobat', 'kdruang')
            ->first();

        $totalstokx = (int) $totalstok->totalstok;
        if ($jml_dikeluarkan > $totalstokx) {
            return new JsonResponse(['message' => 'Maaf Stok Anda Tidak Mencukupi...!!!'], 500);
        }

        $caristokgudang = Stokrel::where('kdobat', $request->kdobat)
            ->where('kdruang', $request->kdruang)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();

        foreach ($caristokgudang as $val) {
            $jmlstoksatuan = $val['jumlah'];
            $nopenerimaan = $val['nopenerimaan'];
        }

        return [$nopenerimaan, $jmlstoksatuan];
    }

    public function insertsementara(Request $request)
    {
        $sementara = date('ymdhis');
        $ruang = $request->kdruang;
        if ($ruang === 'Gd-05010100') {
            $kdruang = 'GO';
        } elseif ($ruang === 'Gd-03010100') {
            $kdruang = 'FS';
        } elseif ($ruang === 'Gd-04010102') {
            $kdruang = 'DRI';
        } elseif ($ruang === 'Gd-05010101') {
            $kdruang = 'DRJ';
        } else {
            $kdruang = 'DKO';
        }
        $notrans = $sementara . '-' . $kdruang;

        $simpanstok = Stokrel::create(
            [
                'nopenerimaan' => $request->notrans ?? $notrans,
                'tglpenerimaan' => $request->tglpenerimaan ?? date('Y-m-d H:i:s'),
                'kdobat' => $request->kdobat,
                'jumlah' => $request->jumlah,
                'kdruang' => $request->kdruang,
                'harga' => $request->harga ?? '',
                'tglexp' => $request->tglexp ?? '',
                'nobatch' => $request->nobatch ?? '',
            ]
        );
        return new JsonResponse(
            [
                'datastok' => $simpanstok,
                'message' => 'Stok Berhasil Disimpan...!!!'
            ],
            200
        );
    }
    public function updatestoksementara(Request $request)
    {
        $cari = Stokrel::where('id', $request->id)->first();
        $cari->jumlah = $request->jumlah ?? '';
        $cari->harga = $request->harga ?? '';
        $cari->tglexp = $request->tglexp ?? '';
        $cari->nobatch = $request->nobatch ?? '';
        $cari->save();

        return new JsonResponse(['message' => 'Stok Berhasil Disimpan...!!!'], 200);
    }

    public function liststokreal()
    {
        $kdruang = request('kdruang');
        $stokreal = Stokreal::where('stokreal.flag', '')
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->where('stokreal.kdruang', $kdruang)
            ->where('stokreal.nopenerimaan', 'like', '%' . request('q') . '%')
            ->orwhere('stokreal.kdobat', 'like', '%' . request('q') . '%')
            ->orwhere('new_masterobat.nama_obat', 'like', '%' . request('q') . '%')
            ->paginate(request('per_page'));
        return new JsonResponse($stokreal);
    }
}
