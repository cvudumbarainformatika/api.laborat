<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Tindakan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mtindakan;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TindakanController extends Controller
{
    public function dialogtindakanpoli()
    {
        $dialogtindakanpoli = Mtindakan::select(
            'rs1 as kdtindakan',
            'rs2 as tindakan',
            'rs8 as sarana',
            'rs9 as pelayanan',
            DB::raw('rs8 +rs9 as tarif')
        )
            ->where('rs2', 'Like', '%' . request('tindakan') . '%')
            ->get();
        return new JsonResponse($dialogtindakanpoli);
    }

    public function simpantindakanpoli(Request $request)
    {
        DB::select('call nota_tindakan(@nomor)');
        $x = DB::table('rs1')->select('rs14')->get();
        $wew = $x[0]->rs14;
        $notatindakan = FormatingHelper::notatindakan($wew, 'T-RJ');

        $simpantindakan = Tindakan::firstOrNew(
            ['rs8' => auth()->user()->pegawai_id, 'rs2' => $request->nota ?? $notatindakan, 'rs1' => $request->noreg, 'rs4' => $request->kdtindakan],
            [
                // 'rs1' => $request->noreg,
                // 'rs2' => $request->nota ?? $notatindakan,
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->kdtindakan,
                // 'rs5' => $request->jmltindakan,
                'rs6' => $request->hargasarana,
                'rs7' => $request->hargasarana,
                // 'rs8' => auth()->user()->pegawai_id,
                'rs9' => FormatingHelper::session_user(), //auth()->user()->pegawai_id,
                'rs13' => $request->hargapelayanan,
                'rs14' => $request->hargapelayanan,
                // 'rs15' => $request->noreg,
                'rs22' => $request->kdpoli,
                'rs24' => $request->kdsistembayar,
            ]
        );
        if (!$simpantindakan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $simpantindakan->rs5 = (int)$simpantindakan->rs5 + (int)$request->jmltindakan;
        $simpantindakan->save();

        $nota = Tindakan::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse(
            [
                'message' => 'Tindakan Berhasil Disimpan.',
                'result' => $simpantindakan,
                'nota' => $nota
            ],
            200
        );
    }

    public function hapustindakanpoli(Request $request)
    {
        $cari = Tindakan::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }
        $hapus = $cari->delete();
        $nota = Tindakan::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }

    public function notatindakan()
    {
        $nota = Tindakan::select('rs2 as nota')->where('rs1', request('noreg'))
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }

    //public static function
}
