<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Rajal\Igd\TriageA;
use App\Models\Simrs\Rajal\Igd\TriageB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TriageController extends Controller
{
    public function simpantriage(Request $request)
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;
        try {
            $simpan = TriageA::create(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => date('Y-m-d H:i:s'),
                    'rs4' => 'IRD',
                    'rs5' => 'POL014',
                    'rs6' => date('Y-m-d H:i:s'),
                    // 'rs7' => $request->norm,
                    'rs8' => $request->suhu,
                    'rs9' => '-',
                    'rs10' => $request->pernapasanx,
                    'rs11' => $request->nadi,
                    //'rs12' => $request->tinggibadan,
                    'rs13' => $request->bb,
                    //'rs14' => $request->,
                    //'rs15' => $request->norm,
                    'rs16' => $request->kategoritriage,
                    'rs17' => $kdpegsimrs,
                    // 'rs18' => $request->norm,
                    // 'rs19' => $request->norm,
                    'rs20' => $kdpegsimrs,
                    'rs21' => $request->tinggibadan,
                    //'rs22' => $request->norm,
                    'sistole' => $request->sistole,
                    'diastole' => $request->diastole,
                    'kesadarans' => $request->kesadaran,
                    'spo2' => $request->spo2,
                    'doa' => $request->doa,
                    'scorediastole' => $request->scorediastole,
                    'scoresistole' => $request->scoresistole,
                    'scorekesadaran' => $request->scorekesadaran,
                    'scorelochea' => $request->scorelochea,
                    'scorenadi' => $request->scorenadi,
                    'scorenyeri' => $request->scorenyeri,
                    'scorepernapasanx' => $request->scorepernapasanx,
                    'scoreproteinurin' => $request->scoreproteinurin,
                    'scorespo2' => $request->scorespo2,
                    'scoresuhu' => $request->scoresuhu,
                    'totalscore' => $request->totalscore,
                    'hasilprimarusurve' => $request->hasilprimarysurve,
                    'hasilsecondsurve' => $request->hasilsecondsurve
                ]
            );

            $simpanx = TriageB::create(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => date('Y-m-d'),
                    'rs4' => 'IRD',
                    'rs5' => 'POL014',
                    'rs7' => $request->jalannafas,
                    'rs9' => $request->pernapasan,
                    'rs14' => $request->eye,
                    'rs15' => $request->verbal,
                    'rs16' => $request->motorik,
                    'rs18' => $kdpegsimrs,
                    'rs19' => $request->sirkulasi,
                    'flaghamil' => $request->pasienhamil,
                    'haidterakir' => $request->haid,
                    'gravida' => $request->gravida,
                    'partus' => $request->partus,
                    'abortus' => $request->abortus,
                    'nyeri' => $request->nyeri,
                    'lochea' => $request->lochea,
                    'proteinurin' => $request->proteinurin,
                    'rs20' => $request->disability,
                ]
            );

            $result = [
                'id' => $simpan['id'],
                'noreg' => $simpan['rs1'],
                'rs1' => $simpan['rs1'],
                'suhu' => $simpan['rs8'],
                'pernapasanx' => $simpan['rs10'],
                'nadi' => $simpan['rs11'],
                'bb' => $simpan['rs13'],
                'tinggibadan' => $simpan['rs21'],
                'sistole' => $simpan['sistole'],
                'diastole' => $simpan['diastole'],
                'kesadaran' => $simpan['kesadarans'],
                'spo2' => $simpan['spo2'],
                'doa' => $simpan['doa'],
                'jalannafas' => $simpanx['rs7'],
                'pernapasan' => $simpanx['rs9'],
                'scoresistole' => $simpan['scoresistole'],
                'scorediastole' => $simpan['scorediastole'],
                'scorekesadaran' => $simpan['scorekesadaran'],
                'scorelochea' => $simpan['scorelochea'],
                'scorenadi' => $simpan['scorenadi'],
                'scorenyeri' => $simpan['scorenyeri'],
                'scorepernapasanx' => $simpan['scorepernapasanx'],
                'scoreproteinurin' => $simpan['scoreproteinurin'],
                'scorespo2' => $simpan['scorespo2'],
                'scoresuhu' => $simpan['scoresuhu'],
                'totalscore' => $simpan['totalscore'],
                'kategoritriage' => $simpan['rs16'],
                'hasilprimarusurve' => $simpan['hasilprimarusurve'],
                'hasilsecondsurve' => $simpan['hasilsecondsurve'],

                'eye' => $simpanx['rs14'],
                'verbal' => $simpanx['rs15'],
                'motorik' => $simpanx['rs16'],
                'sirkulasi' => $simpanx['rs19'],
                'flaghamil' => $simpanx['flaghamil'],
                'haid' => $simpanx['haidterakir'],
                'gravida' => $simpanx['gravida'],
                'partus' => $simpanx['partus'],
                'abortus' => $simpanx['abortus'],
                'nyeri' => $simpanx['nyeri'],
                'lochea' => $simpanx['lochea'],
                'proteinurin' => $simpanx['proteinurin'],
                'disability' => $simpanx['rs20'],
                'disability' => $simpanx['rs20'],
                'disability' => $simpanx['rs20'],
            ];
            return new JsonResponse([
                'message' => 'BERHASIL DISIMPAN',
                'result' => $result
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse([
                'message' => 'GAGAL DISIMPAN...!!!'
            ], 500);
        }
    }

    public function hapustriage(Request $request)
    {
          try {
            $carinoreg = TriageA::select('rs1')->where('id', $request->id)->get();
            $noreg = $carinoreg[0]['rs1'];

            $cariid = TriageB::select('id')->where('rs1', $noreg)->orderBy('id','DESC')->limit(1)->get();
            $id = $cariid[0]['id'];

            $triageA = TriageA::find($request->id);
            $triageB = TriageB::find($id);

            $hapusB = $triageB->delete();
            $hapusA = $triageA->delete();

            return new JsonResponse([
                'message' => 'BERHASIL DIHAPUS...!!!'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse([
                'message' => 'GAGAL DIHAPUS...!!!'
            ], 500);
        }
    }
}
