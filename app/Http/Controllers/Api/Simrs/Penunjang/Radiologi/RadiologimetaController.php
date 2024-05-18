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
        if (!empty($request->nota)) {
            return new JsonResponse(['message' => 'Maaf buat nota baru untuk permintaan ini...!!!'], 500);
        }

        DB::select('call nota_permintaanradio(@nomor)');
        $x = DB::table('rs1')->select('rs41')->get();
        $wew = $x[0]->rs41;
        if($request->kodepoli === 'POL014')
        {
            $notapermintaanradio = FormatingHelper::formatallpermintaan($wew, 'G-RAD');
        }else{
            $notapermintaanradio = FormatingHelper::formatallpermintaan($wew, 'J-RAD');
        }

        $userid = FormatingHelper::session_user();
        $simpanpermintaanradiologi = Transpermintaanradiologi::create(
            // [
            //     'rs1' => $request->noreg,
            //     'rs2' => $request->nota ?? $notapermintaanradio,
            // ],
            [
                'rs1' => $request->noreg,
                'rs2' => $notapermintaanradio,
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->permintaan,
                'rs7' => $request->keterangan,
                'rs8' => $request->kodedokter, //$request->kodedokter
                'rs9' => '1',
                'rs10' => $request->kodepoli,
                'rs11' => $userid['kodesimrs'],
                'rs13' => $request->kodepoli,
                'rs14' => $request->kodesistembayar, //$request->kd_akun
                'rs15' => $request->tpemeriksaan,
                'cito' => $request->cito === 'Iya' ? 'Cito' : '',
                'jenis_pemeriksaan' => '',
                'kddokterpengirim' => '',
                'faskespengirim' => '',
                'unitpengirim' => '',
                'diagnosakerja' => $request->diagnosakerja ?? '',
                'catatanpermintaan' => $request->catatanpermintaan ?? '',
                'metodepenyampaianhasil' => $request->metodepenyampaianhasil ?? '',
                'statusalergipasien' => $request->statusalergipasien ?? '',
                'statuskehamilan' => $request->statuskehamilan ?? '',
            ]
        );

        if (!$simpanpermintaanradiologi) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }
        // return ($simpanpermintaanradiologi);
        $nota = Transpermintaanradiologi::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();

        return new JsonResponse(
            [
                'message' => 'Berhasil Order Ke Radiologi',
                'result' => $simpanpermintaanradiologi,
                'nota' => $nota
            ],
            200
        );
    }

    public function getnota()
    {
        $nota = Transpermintaanradiologi::select('rs2 as nota')->where('rs1', request('noreg'))
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }

    public function hapusradiologi(Request $request)
    {
        $cari = Transpermintaanradiologi::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        $nota = Transpermintaanradiologi::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }
}
