<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TriageController extends Controller
{
    public function simpantriage()
    {
        $wew = ['sasasas'];
        return new JsonResponse([
            'message' => 'BERHASIL DISIMPAN',
            'result' => $wew
        ], 200);
    }
}
