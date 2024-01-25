<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Tindakan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Bridgingeklaim\EwseklaimController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mtindakan;
use App\Models\Simrs\Penunjang\Kamaroperasi\Masteroperasi;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TindakanController extends Controller
{
    public function dialogtindakanpoli()
    {
        $dialogtindakanpoli = Mtindakan::select(
            'rs30.rs1',
            'rs30.rs1 as kdtindakan',
            'rs30.rs2 as tindakan',
            'rs30.rs8 as sarana',
            'rs30.rs9 as pelayanan',
            DB::raw('rs30.rs8 + rs30.rs9 as tarif'),
            'prosedur_mapping.icd9'
        )
            ->leftjoin('prosedur_mapping', 'rs30.rs1', '=', 'prosedur_mapping.kdMaster')
            ->with('maapingprocedure.prosedur')
            ->where('rs30.rs2', 'Like', '%' . request('tindakan') . '%')
            ->orWhere('prosedur_mapping.icd9', 'Like', '%' . request('tindakan') . '%')
            ->get();
        return new JsonResponse($dialogtindakanpoli);
    }

    public function simpantindakanpoli(Request $request)
    {
        DB::select('call nota_tindakan(@nomor)');
        $x = DB::table('rs1')->select('rs14')->get();
        $wew = $x[0]->rs14;
        $notatindakan = FormatingHelper::notatindakan($wew, 'T-RJ');

        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        $simpantindakan = Tindakan::firstOrNew(
            [
                // 'rs8' => $request->kodedokter,
                'rs2' => $request->nota ?? $notatindakan,
                'rs1' => $request->noreg,
                'rs4' => $request->kdtindakan
            ],
            [
                // 'rs1' => $request->noreg,
                // 'rs2' => $request->nota ?? $notatindakan,
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->kdtindakan,
                // 'rs5' => $request->jmltindakan,
                'rs6' => $request->hargasarana,
                'rs7' => $request->hargasarana,
                'rs8' => $request->kodedokter,
                'rs9' => $kdpegsimrs, //auth()->user()->pegawai_id,
                'rs13' => $request->hargapelayanan,
                'rs14' => $request->hargapelayanan,
                // 'rs15' => $request->noreg,
                'rs20' => $request->keterangan ?? '',
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

        EwseklaimController::ewseklaimrajal_newclaim($request->noreg);

        $simpantindakan->load('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs');
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
        EwseklaimController::ewseklaimrajal_newclaim($request->noreg);
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }

    public function notatindakan()
    {
        $nota = Tindakan::select('rs2 as nota')->where('rs1', request('noreg'))
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }

    public function dialogoperasi()
    {
        $dialogoperasi = Masteroperasi::select(
            'rs1 as kdtindakan',
            'rs2 as tindakan',
        )
            ->where('rs2', 'Like', '%' . request('tindakan') . '%')
            ->orWhere('rs1', 'Like', '%' . request('tindakan') . '%')
            ->get();
        return new JsonResponse($dialogoperasi);
    }

    //public static function
}
