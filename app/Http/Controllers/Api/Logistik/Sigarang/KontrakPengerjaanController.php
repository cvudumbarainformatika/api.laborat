<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\KontrakPengerjaanResource;
use App\Models\Sigarang\KontrakPengerjaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KontrakPengerjaanController extends Controller
{
    public function index()
    {
        $data = KontrakPengerjaan::orderBy(request('order_by'), request('sort'))
            ->where('kunci', '=', 1)
            ->filter(request(['q']))->paginate(request('per_page'));
        // return KontrakPengerjaanResource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function kontrakAktif()
    {
        $data = KontrakPengerjaan::orderBy(request('order_by'), request('sort'))
            ->where('kunci', '=', 1)
            ->filter(request(['q']))->get();
        // return KontrakPengerjaanResource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
}
