<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\JurnalUmum;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\JurnalUmum_Header;
use App\Models\Siasik\Master\Akun50_2024;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JurnalManualController extends Controller
{
    public function permen50()
    {
        $akun=Akun50_2024::where('subrincian_objek', '!=', '')
        ->select('uraian','kodeall3')
        ->when(request('q'), function($q){
            $q->where('uraian', 'LIKE', '%'.request('q').'%');
        })
        ->get();
        return new JsonResponse($akun);
    }

    public function jurnalumumotot()
    {
        $jurnal = JurnalUmum_Header::with(
            [
                'rincianjurnalumum'
            ]
        )
        ->whereYear('tanggal', request('tahuncari'))
        ->get();
        return new JsonResponse($jurnal);
    }
}
