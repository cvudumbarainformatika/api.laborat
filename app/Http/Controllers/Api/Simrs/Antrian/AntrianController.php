<?php

namespace App\Http\Controllers\Api\Simrs\Antrian;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Rajalumum\Antrianambil;
use App\Models\Simrs\Pendaftaran\Rajalumum\Antrianbatal;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjsrespontime;
use App\Models\Simrs\Pendaftaran\Rajalumum\Unitantrianbpjs;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
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

    public static function ambilnoantrian($request,$input)
    {
       $idUnitAntrian = '';
       $noreg = $input->noreg;
       $user_id = auth()->user()->pegawai_id;
       $tglBooking = date('Y-m-d');
       $norm = $request->norm;
       $pelayanan_id_tujuan = $request->kodepoli;
       $unitantrian = Unitantrianbpjs::select('tersedia')->where('pelayanan_id', $pelayanan_id_tujuan)->first();
       $tersedia = $unitantrian->tersedia;
       $unitgroup = '';
       if($idUnitAntrian === '')
       {
        $pelayanan_id= $pelayanan_id_tujuan;
       }else{
        $sqlUnitAntrian = Unitantrianbpjs::select('pelayanan_id')->where('id', $idUnitAntrian)->first();
        $pelayanan_id = $sqlUnitAntrian->pelayanan_id;
        $unitgroup = $sqlUnitAntrian->unit_group;
       }

       $tgl = date('Y-m-d');
       $sqlCekAntrian = Antrianambil::where('noreg', $noreg)->where('pelayanan_id', $pelayanan_id)->wheredate('tgl_booking', $tgl)->get();
       if(count($sqlCekAntrian) > 0)
       {
            $sqlCekBatal = Antrianbatal::where('id', $sqlCekAntrian[0]->id)->count();
            if($sqlCekBatal === 0)
            {
                return new JsonResponse(['message' => 'Maaf, pasien tersebut telah mengambil antrian'],500);
            }
       }

       if($unitgroup === 'Farmasi')
       {
            $bpjsrespon = Bpjsrespontime::where('noreg', $noreg)->where('taskid', '=', 5);
            if($bpjsrespon === 0 && $tersedia)
            {
                return new JsonResponse(['message' => 'Maaf, akhir layanan poli tujuan pasien tersebut belum diinput, silahkan hubungi poli bersangkutan.'],500);
            }
       }

        $myReq["layanan"] = $pelayanan_id;
        $myReq["booking_type"] = 'w';
        $myReq["id_customer"] = $norm;
        $myReq["user_id"] = "a1";
        $myReq["tgl_booking"] = $tglBooking;

        $url = (new Client())->post('http://192.168.160.100:2000/api/api' . '/daftar_lokal_layanan',
        [
            'form_params' => $myReq,
            'http_errors' => false
        ]);
        $query = json_decode($url->getBody()->getContents(), false);
        return $query;
    }
}
