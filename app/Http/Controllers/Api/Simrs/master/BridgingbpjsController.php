<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BridgingbpjsController extends Controller
{
    public function cekpsertabpjs(Request $request)
    {
        $cekpsereta = BridgingbpjsHelper::get_url('vclaim','Peserta/nokartu/'. $request->noka.'/tglSEP/'.$request->tglsep);
        $wew = $cekpsereta['result']->peserta->provUmum;
        return ($wew);
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
        $diagnosa = BridgingbpjsHelper::get_url('vclaim','referensi/diagnosa/'. $request->kodediagnosa);
        return($diagnosa);
    }
}
