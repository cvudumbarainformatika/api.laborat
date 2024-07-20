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
      $listkamar = Mkamar::where(function ($q) {
        $q->where('rs7', '<>', '1')
        ->where('rs3', 'A')
        ->where('rs4', '=', 'V');
      })->distinct('rs1')->get();
      return new JsonResponse($listkamar);
    }
    public function listdiagnosa()
    {
        $listdiagnosa = Diagnosa_m::where('disable_status', '')->orderBy('rs3')->limit(25)->get();
        return new JsonResponse($listdiagnosa);
    }

    public function diagnosa_autocomplete()
    {
       $data = Diagnosa_m::query()
        ->select('rs1 as icd', 'rs2 as dtd', 'rs3 as ketindo', 'rs4 as keterangan')
        ->where('disable_status', '')
        ->where(function ($q) {
            $q->where('rs1', 'LIKE', '%' . request('q') . '%')
                ->orWhere('rs2', 'LIKE', '%' . request('q') . '%')
                ->orWhere('rs3', 'LIKE', '%' . request('q') . '%')
                ->orWhere('rs4', 'LIKE', '%' . request('q') . '%');
        })
        ->limit(15)->get();

        return new JsonResponse($data);
    }
}
