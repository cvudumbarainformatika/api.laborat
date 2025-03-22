<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Pembayarannontunai;
use App\Models\Simrs\Kasir\Tagihannontunai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlagingManualVaController extends Controller
{
    public function listva()
    {
        $list = Tagihannontunai::with(
            [
                'mpasien',
                'flagbayar' =>function($flagbayar){
                    $flagbayar->orderby('id', 'Desc');
                }
            ]
        )->where('rs10','!=','RAJAL')->orWhere('rs10','!=','PASIEN LUAR')
        ->where('rs12','!=','1')
        ->orderby('id', 'Desc')
        ->paginate(request('per_page'));

        return new JsonResponse( $list);
    }

    public function flagingmanual(Request $request)
    {
        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        $simpan = Pembayarannontunai::create(
            [
                'rs1' => $request->nova,
                'rs2' => date('Y-m-d H:i:s'),
                'rs3' => $request->total,
                'rs5' => $kdpegsimrs,
                'rs6' => "RSUD"
            ]
        );
        $hasil = self::getbynova($request->nova);
        if(!$simpan){
            return new JsonResponse(['message' => 'Data Gagal Disimpan'],500);
        }
        return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $hasil],200);
    }

    public static function getbynova($nova)
    {
        $list = Tagihannontunai::with(
            [
                'mpasien',
                'flagbayar' =>function($flagbayar){
                    $flagbayar->orderby('id', 'Desc');
                }
            ]
        )
        ->where('rs4', $nova)
        ->get();

        return $list;
    }
}
