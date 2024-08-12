<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Masalrujukan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AsalrujukanContoller extends Controller
{
    public function listasalrujukan()
    {
        // $asalrujukan = Masalrujukan::asalrujukan()->where('rs1', '!=', '')->get();
        $asalrujukan = Cache::rememberForever('asalrujukan', function () {
            return Masalrujukan::asalrujukan()->where('rs1', '!=', '')->get();
        });
        return new JsonResponse($asalrujukan, 200);
    }
}
