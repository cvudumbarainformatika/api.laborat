<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\IT;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanAntianRsDanBpjsController extends Controller
{
    //
    public function getListBpjs()
    {
        // $data = request()->all();
        $data = self::getListFromBpjs(request('tgl'));
        return new JsonResponse(['data' => $data]);
    }
    public function getOneBpjs()
    {

        // $data = request()->all();
        // $data = self::getOneFromBpjs("");
        $data = self::getOneFromBpjs(request('kode'));
        return new JsonResponse(['data' => $data]);
    }
    public static function getListFromBpjs($request)
    {
        $data = BridgingbpjsHelper::get_url(
            'antrean',
            'antrean/pendaftaran/tanggal/' . $request
        );
        return $data;
    }
    public static function getOneFromBpjs($request)
    {
        // return $request;
        $encoded = urlencode($request);
        $data = BridgingbpjsHelper::get_url(
            'antrean',
            'antrean/pendaftaran/kodebooking/' . $encoded
        );
        return $data;
    }
}
