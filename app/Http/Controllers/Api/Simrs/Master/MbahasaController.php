<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mbahasa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MbahasaController extends Controller
{
    public function listbahasa()
    {
        // $listbahasa = Mbahasa::where('flag', '')->get();

        $listbahasa = Cache::rememberForever('bahasa', function () {
            return Mbahasa::where('flag', '')->get();
        });
        return new JsonResponse($listbahasa);
    }
}
