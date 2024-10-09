<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Tindakan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Bridgingeklaim\EwseklaimController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mtindakan;
use App\Models\Simrs\Penunjang\Kamaroperasi\Masteroperasi;
use App\Models\Simrs\Tindakan\Gbrdokumentindakan;
use App\Models\Simrs\Tindakan\Tindakan;
use App\Models\Simrs\Tindakan\TindakanSambung;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            'rs30.rs51 as flaghari',
            DB::raw('rs30.rs8 + rs30.rs9 as tarif'),
            'prosedur_mapping.icd9',
            'rs30.rs4 as kdpoli'
        )
            ->leftjoin('prosedur_mapping', 'rs30.rs1', '=', 'prosedur_mapping.kdMaster')
            ->where(function ($query) {
                $query->where('rs30.rs2', 'Like', '%' . request('tindakan') . '%')
                    ->orWhere('prosedur_mapping.icd9', 'Like', '%' . request('tindakan') . '%');
            })
            // ->where('rs30.rs2', 'Like', '%' . request('kdpoli') . '%')
            // ->where('rs30.rs4')
            ->get();
        return new JsonResponse($dialogtindakanpoli);
    }

    public function simpantindakanpoli(Request $request)
    {
        DB::select('call nota_tindakan(@nomor)');
        $x = DB::table('rs1')->select('rs14')->get();
        $wew = $x[0]->rs14;
        if ($request->kdpoli === 'POL014') {
            $notatindakan = FormatingHelper::notatindakan($wew, 'T-IG');
        } else {
            $notatindakan = FormatingHelper::notatindakan($wew, 'T-RJ');
        }


        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        // $simpantindakan = Tindakan::firstOrNew(
        //     [
        //         // 'rs8' => $request->kodedokter,
        //         'rs2' => $request->nota ?? $notatindakan,
        //         'rs1' => $request->noreg,
        //         'rs4' => $request->kdtindakan
        //     ],
        //     [
        //         // 'rs1' => $request->noreg,
        //         // 'rs2' => $request->nota ?? $notatindakan,
        //         'rs3' => date('Y-m-d H:i:s'),
        //         'rs4' => $request->kdtindakan,
        //         // 'rs5' => $request->jmltindakan,
        //         'rs6' => $request->hargasarana,
        //         'rs7' => $request->hargasarana,
        //         'rs8' => $request->kodedokter,
        //         'rs9' => $kdpegsimrs, //auth()->user()->pegawai_id,
        //         'rs13' => $request->hargapelayanan,
        //         'rs14' => $request->hargapelayanan,
        //         // 'rs15' => $request->noreg,
        //         'rs20' => $request->keterangan ?? '',
        //         'rs22' => $request->kdpoli,
        //         'rs24' => $request->kdsistembayar,
        //     ]
        // );

        $nota = $request->nota ?? $notatindakan;

        $simpantindakan = Tindakan::where(['rs1' => $request->noreg, 'rs4' => $request->kdtindakan, 'rs2' => $nota])->first();
        if (!$simpantindakan) {
            $simpantindakan = new Tindakan();
            $simpantindakan->rs5 = $request->jmltindakan ?? '';
        } else {
            $simpantindakan->rs5 = (int)$simpantindakan->rs5 + (int)$request->jmltindakan;
        }

        $simpantindakan->rs2 = $nota;
        $simpantindakan->rs1 = $request->noreg ?? '';
        $simpantindakan->rs3 = date('Y-m-d H:i:s');
        $simpantindakan->rs4 = $request->kdtindakan ?? '';
        $simpantindakan->rs6 = $request->hargasarana ?? '';
        $simpantindakan->rs7 = $request->hargasarana ?? '';
        $simpantindakan->rs8 = $request->kodedokter ?? '';
        $simpantindakan->rs9 = $kdpegsimrs ?? '';
        $simpantindakan->rs13 = $request->hargapelayanan ?? '';
        $simpantindakan->rs14 = $request->hargapelayanan ?? '';
        $simpantindakan->rs20 = $request->keterangan ?? '';
        $simpantindakan->rs22 = $request->kdpoli  ?? '';
        // $simpantindakan->rs23 = $request->pelaksanaDua ?? '';
        $simpantindakan->rs24 = $request->kdsistembayar ?? '';
        $simpantindakan->save();

        if (!$simpantindakan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        TindakanSambung::updateOrCreate(
            ['nota' => $request->nota ?? $notatindakan, 'noreg' => $request->noreg, 'kd_tindakan' => $request->kdtindakan],
            ['ket' => $request->keterangan, 'rs73_id' => $simpantindakan->id]
        );

        // $simpantindakan->rs5 = (int)$simpantindakan->rs5 + (int)$request->jmltindakan;
        // $simpantindakan->save();

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
        TindakanSambung::where('rs73_id', $request->id)->delete();
        EwseklaimController::ewseklaimrajal_newclaim($request->noreg);
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }

    public function notatindakan()
    {
        $nota = Tindakan::select('rs2 as nota')->where('rs1', request('noreg'))
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }


    public function simpandokumentindakanpoli(Request $request)
    {
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (!empty($files)) {

                for ($i = 0; $i < count($files); $i++) {
                    $file = $files[$i];
                    $originalname = $file->getClientOriginalName();
                    $penamaan = date('YmdHis') . '-' . $i . '-' . $request->rs73_id . '.' . $file->getClientOriginalExtension();
                    $data = Gbrdokumentindakan::where('original', $originalname)->first();
                    Storage::delete('public/dokumentindakan/' . $originalname);

                    $gallery = null;
                    if ($data) {
                        $gallery = $data;
                    } else {
                        $gallery = new Gbrdokumentindakan();
                    }
                    $path = $file->storeAs('public/dokumentindakan', $penamaan);
                    // $target = storage_path() . "/app/public/dokumentindakan/" . $penamaan;
                    // $type = pathinfo($target, PATHINFO_EXTENSION);
                    // $data = file_get_contents($target);
                    // $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    // $base64 = 'data:' . mime_content_type($target) . ';base64,' . base64_encode($target); //ini baru



                    $gallery->nama = $path;
                    $gallery->url = 'dokumentindakan/' . $penamaan;
                    $gallery->original = $originalname;
                    $gallery->rs73_id = $request->rs73_id;
                    $gallery->save();
                }
                $res = Tindakan::find($request->rs73_id);
                return new JsonResponse(['message' => 'success', 'result' => $res->load(['gambardokumens'])], 200);
            }
        }
    }

    public function hapusdokumentindakan(Request $request)
    {
        $template = Gbrdokumentindakan::find($request->id);
        // return $template;
        Storage::delete($template->nama);
        $template->delete();

        $res = Tindakan::find($template->rs73_id);

        return new JsonResponse(['message' => 'success', 'result' => $res->load(['gambardokumens'])], 200);
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


    public static function dataTindakanByNoreg($noreg)
    {
        $data = Tindakan::select(
            'id','rs1','rs4',
            'rs1 as noreg',
            'rs2 as nota',
            'rs3 as tglinput',
 
            'rs4 as kdtindakan',
            'rs5',
            'rs6 as hargasarana',
            'rs7 as hargasarana',
            'rs8 as pelaksanaSatu',
            'rs9 as kddpjp',
            'rs13 as hargapelayanan',
            'rs14 as hargapelayanan',
            'rs20 as keterangan',
            'rs22 as kdruangan',
            'rs23 as pelaksanaDua',
            'rs24 as kdsistembayar',
        )
        ->with(['mastertindakan:rs1,rs2','sambungan:rs73_id,ket'])
        ->where('rs1', $noreg)->get();

        return $data;
    }

    public function getTindakanRanap()
    {
       
        $data = self::dataTindakanByNoreg(request('noreg'));
        return new JsonResponse($data);
    }

    public function simpantindakanranap(Request $request)
    {
        DB::select('call nota_tindakan(@nomor)');
        $x = DB::table('rs1')->select('rs14')->get();
        $wew = $x[0]->rs14;
        if ($request->kdpoli === 'POL014') {
            $notatindakan = FormatingHelper::notatindakan($wew, 'T-IG');
        } else {
            $notatindakan = FormatingHelper::notatindakan($wew, 'T-RI');
        }


        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];

        $nota = $request->nota ?? $notatindakan;

        $tindakan = Tindakan::where(['rs1' => $request->noreg, 'rs4' => $request->kdtindakan, 'rs2' => $nota])->first();
        if (!$tindakan) {
            $tindakan = new Tindakan();
            $tindakan->rs5 = $request->jmltindakan ?? '';
        } else {
            $tindakan->rs5 = (int)$tindakan->rs5 + (int)$request->jmltindakan;
        }

        $tindakan->rs2 = $nota;
        $tindakan->rs1 = $request->noreg ?? '';
        $tindakan->rs3 = date('Y-m-d H:i:s');
        $tindakan->rs4 = $request->kdtindakan ?? '';
        $tindakan->rs6 = $request->hargasarana ?? '';
        $tindakan->rs7 = $request->hargasarana ?? '';
        $tindakan->rs8 = $request->pelaksanaSatu ?? '';
        $tindakan->rs9 = $request->kddpjp ?? '';
        $tindakan->rs13 = $request->hargapelayanan ?? '';
        $tindakan->rs14 = $request->hargapelayanan ?? '';
        $tindakan->rs20 = $request->keterangan ?? '';
        $tindakan->rs22 = $request->kdpoli  ?? '';
        $tindakan->rs23 = $request->pelaksanaDua ?? '';
        $tindakan->rs24 = $request->kdsistembayar ?? '';
        $tindakan->save();

        if (!$tindakan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $idTindakan = $tindakan->id;

        $tindakan->sambungan()->updateOrCreate(
            ['rs73_id' => $idTindakan],
            [
                'nota' => $tindakan->rs2, 'noreg' => $request->noreg, 
                'kd_tindakan' => $request->kdtindakan,
                'ket' => $request->keterangan,
                'rs73_id' => $idTindakan
            ],  
            // ['ket' => $request->keterangan]
        );

        
        // $tindakan->save();

        $nota = Tindakan::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();

        // EwseklaimController::ewseklaimrajal_newclaim($request->noreg);

        $tindakan->load('mastertindakan:rs1,rs2,rs4');
        return new JsonResponse(
            [
                'message' => 'Tindakan Berhasil Disimpan.',
                'result' => $tindakan,
                'nota' => $nota
            ],
            200
        );
    }

    //public static function
}
