<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;

class bridgingbankjatimHelper
{
    public static function createqris($request)
    {

        $billNumberxx =$request->jenislayanan === 'KARCIS' ? $request->noreg :  $request->nota;
        $url = "https://jatimva.bankjatim.co.id/MC/Qris/Dynamic";
        $total = (int) $request->total;
        $bj = (int) 0.4;

        $totalall = (int) $total + (int) ($total * $bj / 100);
        $tglsekarang = date('Y-m-d 23:59:59');
        $billNumber_tampung = $billNumberxx;
        $terminalUser_tampung = 'U012001';
        $merchanthashkey = '8M9R0BZE21';


        $merchantPan = '9360011400000396828';
        $hashcodeKey = hash("sha256", $merchantPan . '' . $billNumber_tampung . '' . $terminalUser_tampung . '' . $merchanthashkey);
        $billNumber = $billNumber_tampung;
        $purposetrx = $request->noreg;
        $storelabel = 'RSUD DR M SALEH';
        $customerlabel = 'PUBLIC';
        $terminalUser = $terminalUser_tampung;
        $expiredDate = $tglsekarang;
        $amount = $totalall;

        $data = [
            'merchantPan' => $merchantPan,
            'hashcodeKey' => $hashcodeKey,
            'billNumber' => $billNumber,
            'purposetrx' => $purposetrx,
            'storelabel' => $storelabel,
            'customerlabel' => $customerlabel,
            'terminalUser' => $terminalUser,
            'expiredDate' => $expiredDate,
            'amount' => $amount
        ];
        $myvars = json_encode($data);

        $reqjatim = self::reqqris('POST', $url, $myvars);
        return $reqjatim;
    }

    public static function createva($request)
    {
        $url="https://jatimva.bankjatim.co.id/Va/RegPen/";
        $vajatim= 18012000;
        $tgl = substr(date('Ymd'),2,-2);
        $kasir=2;

        DB::select('call conterva(@nomor)');
        $x = DB::table('rs1')->select('rs297')->get();
        $counter = $x[0]->rs297;

        $tglsekarang= date('Y-m-d');
		$tglkadaluarsax = date('Y-m-d', strtotime('+6 days', strtotime($tglsekarang)));
		$tglkadaluarsa=str_replace("-","",$tglkadaluarsax);

        $idpembayaran=$vajatim.''.$kasir.''.$tgl.''.$counter;
        $data = [
            "VirtualAccount"=>$idpembayaran,
			"Nama"=>$request->nama,
			"TotalTagihan"=>$request->total,
			"TanggalExp"=>$tglkadaluarsa,
			"Berita1"=>"RSUD MOHAMAD SALEH",
			"Berita2"=>$request->kodepoli,
			"Berita3"=>'PELUNASAN',
			"Berita4"=>"",
			"Berita5"=>"",
			"FlagProses"=> 1,
			"Flag"=> "F",
			"FlagLunas"=> "N",
			"sign"=> "+"
        ];
        $myvars = json_encode($data);

        $reqjatim = self::reqqris('POST', $url, $myvars);
         return [
            'response' => $reqjatim,
            'tglkadaluarsax' => $tglkadaluarsax
        ];
    }

    public static function reqqris($method, $url, $myvars)
    {
        // $url = 'https://jatimva.bankjatim.co.id/MC/Qris/Dynamic';

        $session = curl_init($url);
        $arrheader =  array(
            //'Accept: application/json',
            'Content-Type: application/json',
        );

        curl_setopt($session, CURLOPT_URL, $url);
        curl_setopt($session, CURLOPT_HTTPHEADER, $arrheader);
        curl_setopt($session, CURLOPT_VERBOSE, true);

        if ($method == 'POST') {
            curl_setopt($session, CURLOPT_POST, true);
            curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
        }

        if ($method == 'PUT') {
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
        }

        if ($method == 'GET') {
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
        }
        if ($method == 'DELETE') {
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
        }

        curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);

        $response = curl_exec($session);
        return json_decode($response);
    }
}
