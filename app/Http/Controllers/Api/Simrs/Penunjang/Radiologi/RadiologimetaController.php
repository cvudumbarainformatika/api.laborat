<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Radiologi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Radiologi\Mjenispemeriksaanradiologimeta;
use App\Models\Simrs\Penunjang\Radiologi\Mpemeriksaanradiologi;
use App\Models\Simrs\Penunjang\Radiologi\Mpemeriksaanradiologimeta;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RadiologimetaController extends Controller
{
    public function listmasterpemeriksaanradiologi()
    {
        $listmasterpemeriksaanradiologi = Mpemeriksaanradiologimeta::get();
        return new JsonResponse($listmasterpemeriksaanradiologi);
    }

    public function jenispermintaanradiologi()
    {
        $jenispermintaanradiologi = Mjenispemeriksaanradiologimeta::all();
        return new JsonResponse($jenispermintaanradiologi);
    }

    public function listpermintaanradiologirinci()
    {
        $rincianpermintaan = Mpemeriksaanradiologi::all();
        return new JsonResponse($rincianpermintaan);
    }

    public function simpanpermintaanradiologi(Request $request)
    {
        DB::select('call nota_permintaanradio(@nomor)');
        $x = DB::table('rs1')->select('rs41')->get();
        $wew = $x[0]->rs41;
        $notapermintaanradio = FormatingHelper::formatallpermintaan($wew, 'J-RAD');

        $simpanpermintaanradiologi = Transpermintaanradiologi::create(
            // [
            //     'rs1' => $request->noreg,
            //     'rs2' => $request->nota ?? $notapermintaanradio,
            // ],
            [
                'rs1' => $request->noreg,
                'rs2' => $request->nota ?? $notapermintaanradio,
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->permintaan,
                'rs7' => $request->keterangan,
                'rs8' => auth()->user()->pegawai_id, //$request->kodedokter
                'rs9' => '1',
                'rs10' => $request->kodepoli,
                'rs11' => auth()->user()->pegawai_id,
                'rs13' => $request->kd_ruang,
                'rs14' => auth()->user()->pegawai_id, //$request->kd_akun
                'rs15' => $request->tpemeriksaan,
                'cito' => $request->cito === 'Iya' ? 'Cito' : '',
                'jenis_pemeriksaan' => '',
                'kddokterpengirim' => '',
                'faskespengirim' => '',
                'unitpengirim' => '',
                'diagnosakerja' => $request->diagnosakerja,
                'catatanpermintaan' => $request->catatanpermintaan,
                'metodepenyampaianhasil' => $request->metodepenyampaianhasil,
                'statusalergipasien' => $request->statusalergipasien,
                'statuskehamilan' => $request->statuskehamilan,
            ]
        );

        if (!$simpanpermintaanradiologi) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }
        // return ($simpanpermintaanradiologi);
        // $nota = LaboratMeta::select('nota')->where('noreg', $request->noreg)
        //     ->groupBy('nota')->orderBy('id', 'DESC')->get();

        return new JsonResponse(
            [
                'message' => 'Berhasil Order Ke Radiologi',
                'result' => $simpanpermintaanradiologi,
                'nota' => '$nota'
            ],
            200
        );
    }
}
