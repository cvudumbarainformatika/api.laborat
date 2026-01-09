<?php

namespace App\Http\Controllers\Api\Aset\Master;

use App\Http\Controllers\Controller;
use App\Models\Aset\Master\KodeAspak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KodeAspakController extends Controller
{
    public function index()
    {
        $data = KodeAspak::all();
        return new JsonResponse($data);
    }
}
