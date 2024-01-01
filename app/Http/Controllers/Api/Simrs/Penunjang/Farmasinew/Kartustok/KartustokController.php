<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Kartustok;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mapingkelasterapi;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KartustokController extends Controller
{

    public function index()
    {
        $list = Mobatnew::with('mkelasterapi')
            ->where(function ($q) {
                $q->where('nama_obat', 'Like', '%' . request('q') . '%')
                    ->orWhere('merk', 'Like', '%' . request('q') . '%')
                    ->orWhere('kandungan', 'Like', '%' . request('q') . '%');
            })->orderBy('id', 'desc')
            ->where('flag', '')
            ->paginate(request('per_page'));

        return new JsonResponse($list);
    }

    public function cariobat()
    {

        $query = Mobatnew::select(
            'kd_obat as kodeobat',
            'nama_obat as namaobat',
            'satuan_k',
            'satuan_b',
        )->where('flag', '')
            ->where(function ($list) {
                $list->where('nama_obat', 'Like', '%' . request('q') . '%');
            })->orderBy('nama_obat')
            ->get();
        return new JsonResponse($query);
    }
}
