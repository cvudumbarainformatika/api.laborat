<?php

namespace App\Http\Controllers\Api\Simrs\Antrian;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class AntrianController extends Controller
{
    public function call_layanan_ruang()
    {

        $myReq["layanan"] = '1';
        $myReq["loket"] = '1';
        $myReq["id_ruang"] = '1';
        $myReq["user_id"] = "a1";
        $myReq["nomor"] = 'A069';

        //$myVars=json_encode($myReq);
        $url = (new Client())->post('http://192.168.160.100:2000/api/api' . '/tombolrecall_layanan_ruang', [
            'form_params' => $myReq,
            'http_errors' => false]);
        $query = json_decode($url->getBody()->getContents(), false);
        return $query;
    }

    public function ambilnoantrian($request)
    {
        $myReq["layanan"] = '1';
        $myReq["booking_type"] = 'w';
        $myReq["id_customer"] = $request->norm;
        $myReq["user_id"] = "a1";
        $myReq["tgl_booking"] = date('Y-m-d');

        $url = (new Client())->post('http://192.168.160.100:2000/api/api' . '/daftar_lokal_layanan', [
            'form_params' => $myReq,
            'http_errors' => false]);
            $query = json_decode($url->getBody()->getContents(), false);
            return $query;
    }
}
