<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Helpers\AuthSatsetHelper;
use App\Helpers\BridgingSatsetHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapingKfaController extends Controller
{
    public function getMasterObat(){
        $obat=Mobatnew::select('kd_obat','nama_obat','satset_uuid')->where('nama_obat','LIKE','%'.request('q').'%')->paginate(request('per_page'));
        $data=collect($obat)['data'];
        $meta=collect($obat)->except('data');
        
        return new JsonResponse([
            'data'=>$data,
            'meta'=>$meta,
            'obat'=>$obat,
            'req'=>request()->all(),
        ]);
    }
    public function getKfa(){
        $extend='/kfa-v2/products/all';
        $token = AuthSatsetHelper::accessToken();
        $param='?page='.request('page').'&size='.request('per_page').'&product_type=farmasi'.'keyword='.request('q');
        
        $obat=BridgingSatsetHelper::get_data_kfa($extend,$token,$param) ;
        $data=$obat['items']['data'];
        $adaur=(int)$obat['page']<(int)$obat['total']?'ada':null;
        $meta=[
            'current_page'=>$obat['page'],
            'last_page'=>$obat['total'],
            'total'=>$obat['total'],
            'total'=>$obat['total'],
            'next_page_url'=>$adaur,
        ];
        
        return new JsonResponse([
            'data'=>$data,
            'meta'=>$meta,
            'obat'=>$obat,
            'token'=>$token,
            'req'=>request()->all(),
        ]);
    }
}
