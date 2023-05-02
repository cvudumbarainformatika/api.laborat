<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mstatuspernikahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusPernikahanController extends Controller
{
    public function index()
    {
        $data = Mstatuspernikahan::all();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $simpan = Mstatuspernikahan::updateOrCreate(['kode' =>$request->kode],
            [
                'statuspernikahan' => $request->statuspernikahan
            ]
        );

        if(!$simpan)
        {
            return new JsonResponse(['message' => 'DATA GAGAL DISIMPAN'], 500);
        }
            return new JsonResponse(['message' => 'DATA BERHASIL DISIMPAN' , $simpan] ,200);
    }
}
