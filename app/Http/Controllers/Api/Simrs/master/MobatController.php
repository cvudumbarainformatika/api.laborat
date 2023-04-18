<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mobat;
use Illuminate\Http\JsonResponse;

class MobatController extends Controller
{
    public function index()
    {
        $query = Mobat::mobat()->get();
        return new JsonResponse($query);
    }
}
