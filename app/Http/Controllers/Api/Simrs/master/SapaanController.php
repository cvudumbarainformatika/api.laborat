<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Msapaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SapaanController extends Controller
{
    public function index()
    {
       $query = Msapaan::query()
       ->selectRaw('id1 as kode,rs2 as sapaan,rs1 as kodex')
       ->get();

        return new JsonResponse($query);
    }
}
