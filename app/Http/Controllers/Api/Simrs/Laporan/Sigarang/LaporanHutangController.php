<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Sigarang;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanHutangController extends Controller
{
    public function lapHutang()
    {
        return new JsonResponse(request()->all());
    }
}
