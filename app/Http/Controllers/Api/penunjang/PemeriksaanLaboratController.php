<?php

namespace App\Http\Controllers\Api\penunjang;

use App\Http\Controllers\Controller;
use App\Models\PemeriksaanLaborat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemeriksaanLaboratController extends Controller
{
    public function groupped()
    {
        // $query = collect(PemeriksaanLaborat::all());
        // $data= $query->groupBy('rs21');

        $data = PemeriksaanLaborat::query()
        ->where('hidden','=','')
            ->when(request('q'), function($search, $q){
                if (request('p') === 'non') {
                    $search->where('rs21','=','')
                    ->where('rs2', 'LIKE', '%' . $q . '%');
                } else {
                    $search->where('rs21','<>','')
                    ->where('rs2', 'LIKE', '%' . $q . '%');
                }

        })->limit(20)->get();
        return new JsonResponse($data);
    }
}
