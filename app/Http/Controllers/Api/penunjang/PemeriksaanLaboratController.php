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
        // ->where('rs2', 'LIKE', '%' . request('q') . '%')
        //             ->orWhere('rs21', 'LIKE', '%' . request('q') . '%')
        //     ->when(request()->q, function ($search){
        //         // if (request('p') == 'non') {
        //         //     return $search->orWhere('rs21','=','')
        //         //     ->orWhere('rs2', 'LIKE', '%' . $q . '%');
        //         // }
        //             return $search->where('rs2', 'LIKE', '%' . 'DARA' . '%')
        //             ->orWhere('rs21', 'LIKE', '%' . 'DARA' . '%');

        // })
        ->where('hidden','=','')->get();
        return new JsonResponse($data);
    }
}
