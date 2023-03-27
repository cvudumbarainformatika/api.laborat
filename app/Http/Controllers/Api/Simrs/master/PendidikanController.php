<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpendidikan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendidikanController extends Controller
{
    public function index()
    {
        $data = Mpendidikan::query()
        ->selectRaw('rs1 as kode,rs2 as pendidikan')
        ->get();

        return new JsonResponse($data);
    }

}
