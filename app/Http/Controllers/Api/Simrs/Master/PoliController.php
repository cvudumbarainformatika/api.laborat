<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PoliController extends Controller
{
    public function listpoli()
    {
        $data = Cache::remember('list_poliklinik', now()->addDays(1), function () {
            return Mpoli::listpoli()
                ->where('rs5', '=', '1') // Tetap aktifkan status keaktifan 1
                ->where(function ($query) {
                    $query->where('rs4', '=', 'Poliklinik') // Ambil semua Poliklinik
                        ->orWhereIn('rs1', ['PEN004', 'PEN005']); // ATAU ambil Penunjang khusus ini
                })
                ->get()
                ->toArray();
        });
        // $listpoli = Mpoli::listpoli()->where('rs4', '=', 'Poliklinik')->where('rs5', '=', '1')
        //     ->get();
        return new JsonResponse($data, 200);
    }
}
