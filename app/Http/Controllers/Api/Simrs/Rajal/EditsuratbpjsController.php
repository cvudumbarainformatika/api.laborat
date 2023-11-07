<?php

namespace App\Http\Controllers\Api\Simrs\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Planing\Simpansuratkontrol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditsuratbpjsController extends Controller
{
    public function listsuratkontrol()
    {
        $tglawal = request('tglawal');
        $tglakhir = request('tglakhir');
        $filter = request('filter');
        $listsuratkontrol = BridgingbpjsHelper::get_url('vclaim', '/RencanaKontrol/ListRencanaKontrol/tglAwal/' . $tglawal . '/tglAkhir/' . $tglakhir . '/filter/' . $filter);
        return new JsonResponse($listsuratkontrol);
    }

    public function editsuratkontrol(Request $request)
    {
        $data = [
            "request" => [
                "noSuratKontrol" => $request->noSuratKontrol,
                "noSEP" => $request->noSepAsalKontrol,
                "kodeDokter" => $request->kodeDokter,
                "poliKontrol" => $request->poliTujuan,
                "tglRencanaKontrol" => $request->tglrencanakontrol,
                "user" => FormatingHelper::session_user()
            ]
        ];
        $editsuratkontrol = BridgingbpjsHelper::put_url(
            'vclaim',
            'RencanaKontrol/Update',
            $data
        );
        $cari = Simpansuratkontrol::where('noSuratKontrol', $request->noSuratKontrol)->first();

        $noreg = $cari->noreg;
        Bpjs_http_respon::create(
            [
                'noreg' => $noreg,
                'method' => 'PUT',
                'request' => $data,
                'respon' => $editsuratkontrol,
                'url' => 'RencanaKontrol/Update',
                'tgl' => date('Y-m-d H:i:s')
            ]
        );
        $xxx = $editsuratkontrol['metadata']['code'];
        if ($xxx === 200 || $xxx === '200') {
            $cari = Simpansuratkontrol::where('noSuratKontrol', $request->noSuratKontrol)->first();
            $cari->rs19 = $request->tglrencanakontrol;
            $cari->save();
            return new JsonResponse(
                [
                    'result' => $editsuratkontrol
                ]
            );
        } else {
            return new JsonResponse(
                [
                    'result' => $editsuratkontrol
                ]
            );
        }
    }
}