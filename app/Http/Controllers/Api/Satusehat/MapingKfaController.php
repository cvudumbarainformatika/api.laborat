<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapingKfaController extends Controller
{
    public function getMasterObat(){
        return new JsonResponse([
            'req'=>request()->all(),
        ],410);
    }
}
