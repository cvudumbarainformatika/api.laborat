<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BridgingbpjsController extends Controller
{
    public function cekpsertabpjsbynoka(Request $request)
    {
        $cekpsereta = BridgingbpjsHelper::get_url('vclaim','Peserta/nokartu/'. $request->noka.'/tglSEP/'.$request->tglsep);
       // $wew = $cekpsereta['result']->peserta->provUmum;
        return ($cekpsereta);
    }

    public function cekpsertabpjsbynik(Request $request)
    {
        $cekpseretax = BridgingbpjsHelper::get_url('vclaim','Peserta/nik/'. $request->nik.'/tglSEP/'.$request->tglsep);
       // $wew = $cekpsereta['result']->peserta->provUmum;
        return ($cekpseretax);
    }

    public function listrujukanpcare(Request $request)
    {
        $listrujukanpcare = BridgingbpjsHelper::get_url('vclaim','Rujukan/List/Peserta/'. $request->noka);
        return($listrujukanpcare);
    }

    public function listrujukanrs(Request $request)
    {
        $listrujukanrs = BridgingbpjsHelper::get_url('vclaim', '/Rujukan/RS/List/Peserta/'. $request->noka);
        return($listrujukanrs);
    }

    public function diagnosabybpjs(Request $request)
    {
        if($request->kodediagnosa != '')
        {
            $diagnosa = BridgingbpjsHelper::get_url('vclaim','referensi/diagnosa/'. $request->kodediagnosa);
            return($diagnosa);
        }
            $diagnosa = BridgingbpjsHelper::get_url('vclaim','referensi/diagnosa/'. $request->diagnosa);
            return($diagnosa);
    }

    public function faskesasalbpjs(Request $request)
    {
        $faskesbpjs = BridgingbpjsHelper::get_url('vclaim','referensi/faskes/'.$request->faskesasal.'/1');

    }
}
