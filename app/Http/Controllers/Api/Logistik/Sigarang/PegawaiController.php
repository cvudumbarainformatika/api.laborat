<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\PegawaiResource;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    public function index()
    {
        $data = Pegawai::latest('id')->filter(request(['q']))->paginate(request('per_page'));

        return PegawaiResource::collection($data);
        // return response()->json([
        //     'data' => $data
        // ]);
    }
    public function find()
    {
        $data = Pegawai::latest('id')->filter(request(['q']))->get();

        return PegawaiResource::collection($data);
        // return response()->json([
        //     'data' => $data
        // ]);
    }
}
