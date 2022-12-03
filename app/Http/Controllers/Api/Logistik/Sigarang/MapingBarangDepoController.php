<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\MapingBarangDepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapingBarangDepoController extends Controller
{
    public function allMapingDepo()
    {
        $data = MapingBarangDepo::with('barangrs', 'gudang')->get();
        return new JsonResponse($data);
    }
}
