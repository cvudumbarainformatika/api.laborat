<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Diagnosa_m;
use App\Models\Simrs\Master\Mkamar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KamarController extends Controller
{
    public function listkamar()
    {
      $listkamar = Mkamar::query()
      ->selectRaw('rs1,rs2,rs3,rs4,rs6')
      ->where(function ($q) {
        $q->where('rs6', '<>', '1')
        ->where('status', '<>', '1');
      })->distinct('rs1')
      ->orderBy('rs2', 'DESC')->get();
      return new JsonResponse($listkamar);
    }
    
}
