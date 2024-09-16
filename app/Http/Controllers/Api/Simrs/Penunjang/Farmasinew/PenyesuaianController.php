<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PenyesuaianController extends Controller
{
    public function getObat()
    {
        $data = Mobatnew::select('kd_obat', 'nama_obat')
            ->where('nama_obat', 'LIKE', '%' . request('q') . '%')
            ->limit(10)
            ->get();
        return new JsonResponse($data);
    }
    public function getTransaksi()
    {
        $data = request()->all();
        return new JsonResponse($data);
    }
}
