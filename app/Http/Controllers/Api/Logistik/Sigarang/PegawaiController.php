<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\PegawaiResource;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
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
        $data = Pegawai::latest('id')->filter(request(['q']))->limit(request('limit'))->get();

        return PegawaiResource::collection($data);
        // return response()->json([
        //     'data' => $data
        // ]);
    }
    public function cari()
    {
        $data = Pegawai::latest('id')->filter(request(['q']))->limit(request('limit'))->get();

        return new JsonResponse($data);
        // return response()->json([
        //     'data' => $data
        // ]);
    }
}
