<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Bukutamu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BukutamuController extends Controller
{
    public function store(Request $request)
    {
        $clientIP = request()->ip();
        $agent = request()->header('User-Agent');

        $saved = Bukutamu::updateOrCreate(
            ['ip'=> $clientIP],
            [
                'agent'=> $agent,
                'nama'=> $request->nama,
                'email'=> $request->email,
                'pesan'=> $request->pesan,
                'ratings'=> $request->ratings,
            ],
        );
        if (!$saved) {
            return new JsonResponse(['message'=>'Ada Kesalahan'], 500);
        }
        return new JsonResponse(['message'=>'Terimakasih, Saran dan Penilaian Anda sudah kami tampung'], 201);
    }
}
