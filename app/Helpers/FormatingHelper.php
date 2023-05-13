<?php

namespace App\Helpers;

use App\Models\Simrs\Master\Mpoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FormatingHelper
{
    public static function gennoreg($n,$kode)
    {
        $lbr=strlen($n);
        for($i=1;$i<=5-$lbr;$i++){
            $has=@$has."0";
        }
        return @$has.$n."/".date("m")."/".date("Y")."/".$kode;
    }

    public static function getKarcisPoli($poli,$kartu)
    {
        $data = Mpoli::select('rs3')->where('rs1', '=' , $poli)->orderBY('rs1')->first();
          if($data == '')
          {
            return new JsonResponse('data tidak ada');
         }
        $flag = $data->rs3;
        $data2 = DB::table('rs30z')->select('rs2','rs8','rs9')->where('rs3', '=', 'RM#')->first();
        $nama_biaya_rm = $data2?$data2->rs2:'';
        $kode_biaya_rm="RM";
        $biaya_rm1 = $data2?$data2->rs8:'0';
        $biaya_rm2 = $data2?$data2->rs9:'0';

        $data3 = DB::table('rs30z')->select('rs2','rs8','rs9')->where('rs3', '=', 'K2#')
        ->where('rs4', 'LIKE', '%' . $flag . '%')->get();
        $nama_biaya_lama=@$nama_biaya."#".$data3[0]->rs2;
        $kode_biaya_lama=@$kode_biaya."#"."K2";
        $biaya_karcis1=$data3[0]->rs8;
        $biaya_karcis2=$data3[0]->rs9;

        $data4 = DB::table('rs30z')->select('rs2','rs8','rs9')->where('rs3', '=', 'K1#')
        ->where('rs4', '=', 'RJ')->get();
        $nama_biaya_tidak_lama=@$nama_biaya_tidak_lama."#".$data4[0]->rs2;
        $kode_biaya_tidak_lama=@$kode_biaya."#"."K1";
        $biaya_kartu1=$data4[0]->rs8;
        $biaya_kartu2=$data4[0]->rs9;

        if($kartu != 'Lama'){
            $nama_biaya=$nama_biaya_rm.''.$nama_biaya_lama.''.$nama_biaya_tidak_lama;
            $kode_biaya=$kode_biaya_rm.''.$kode_biaya_lama.''.$kode_biaya_tidak_lama;
            $biaya_kartu1;
            $biaya_kartu2;
        }else{
            $nama_biaya=$nama_biaya_rm.''.$nama_biaya_lama;
            $kode_biaya=$kode_biaya_rm.''.$kode_biaya_lama;
            $biaya_kartu1=0;
            $biaya_kartu2=0;
        }

        if($biaya_rm1 > 0)
        {
            $sarana=@$sarana.$biaya_rm1;
            $pelayanan=@$pelayanan.$biaya_rm2;
        }

        if($biaya_karcis1 > 0){
            $sarana=$sarana."#".$biaya_karcis2;
            $pelayanan=$pelayanan."#".$biaya_karcis1;
        }

        if($biaya_kartu1 > 0){
            $sarana=$sarana."#".$biaya_kartu1;
            $pelayanan=$pelayanan."#".$biaya_kartu2;
        }


        $tarif=$biaya_rm1+$biaya_rm2+$biaya_karcis1+$biaya_karcis2+$biaya_kartu1+$biaya_kartu2;

        return [
                'nama_biaya' => $nama_biaya,
                'kode_biaya'=>$kode_biaya,
                'sarana'=>$sarana,
                'pelayanan'=>$pelayanan,
                'tarif'=>$tarif,
            ];

    }
}
