<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Magama;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AlasanMutasiController extends Controller
{
    public function index()
    {
        

        $data = Cache::remember('alasanmutasi', now()->addDays(7), function () {
            return DB::table('rs45')
            // ->selectRaw('rs1 kode,rs2 keterangan,kodemap kodemapping,ketmap keteranganmapping')
            // ->where('flag','<>','1')
            ->orderBy('rs1', 'ASC')
            ->get();
        });

        return new JsonResponse($data);
    }

    
}
