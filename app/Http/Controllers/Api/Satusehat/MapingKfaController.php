<?php

namespace App\Http\Controllers\Api\Satusehat;

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
}
