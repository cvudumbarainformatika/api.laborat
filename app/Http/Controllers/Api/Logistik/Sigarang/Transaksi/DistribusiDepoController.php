<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DistribusiDepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistribusiDepoController extends Controller
{
    public function index()
    {
        $data = DistribusiDepo::latest('id')
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
}
