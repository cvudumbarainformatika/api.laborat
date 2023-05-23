<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SepranapController extends Controller
{
    public function sepranap()
    {
        $carisepranap = Sepranap::sepranap()->filter(request('noka'))->get();
        return new JsonResponse(['message' => 'OK', $carisepranap], 200);
    }
}
