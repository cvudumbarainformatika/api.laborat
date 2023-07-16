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

    public function ambilnoantrian($request,$input)
    {
    //    $idUnitAntrian = '';
    //    $noreg = $input->noreg;
    //    $user_id = auth()->user()->pegawai_id;
    //    $created_at = date('Y-m-d H:i:s');
    //    $updated_at = date('Y-m-d H:i:s');
    //    $tglBooking = date('Y-m-d');
    //    $norm = $request->norm;
    //    $pelayanan_id_tujuan = $request->kodepoli;

    //     $url = (new Client())->post('http://192.168.160.100:2000/api/api' . '/daftar_lokal_layanan',
    //     [
    //         "layanan"=>$pelayanan_id,
    //         "booking_type"=>"w",
    //         "id_customer"=>$norm,
    //         "user_id"=>"a1",
    //         "tgl_booking"=>$tglBooking
    //     ]);
    //     $query = json_decode($url->getBody()->getContents(), false);
    //     return $query;
    }
}
