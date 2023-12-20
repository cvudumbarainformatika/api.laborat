<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
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

    public function insertsementara()
    {
    }
}
