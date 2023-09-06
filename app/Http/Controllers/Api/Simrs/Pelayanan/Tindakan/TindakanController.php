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
            ->where('rs2', 'Like', request('tindakan'))
            ->get();
        return new JsonResponse($dialogtindakanpoli);
    }

    public function simpantindakanpoli(Request $request)
    {
        DB::select('call nota_tindakan(@nomor)');
        $x = DB::table('rs1')->select('rs14')->get();
        $wew = $x[0]->rs14;
        $notatindakan = FormatingHelper::notatindakan($wew, 'T-RJ');

        $simpantindakan = Tindakan::create(
            [
                'rs1' => $request->noreg,
                'rs2' => $notatindakan,
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->kdtindakan,
                'rs5' => $request->jmltindakan,
                'rs6' => $request->hargasarana,
                'rs7' => $request->hargasarana,
                'rs8' => auth()->user()->pegawai_id,
                'rs9' => auth()->user()->pegawai_id,
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
        return new JsonResponse(['message' => 'Data Berhasil Disimpan...!!!'], 200);
    }

    public function hapustindakanpoli(Request $request)
    {
        $cari = Tindakan::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }
}
