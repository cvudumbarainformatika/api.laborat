<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DetailDistribusiDepo;
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

    public function getDistribusi()
    {
        // $data = DistribusiDepo::where('status', '=', 1)
        //     ->with('details')
        //     ->get();
        $data = DetailDistribusiDepo::selectRaw('kode_rs,sum(jumlah) as jml')
            ->whereHas('distribusi', function ($a) {
                $a->where('status', '=', 1);
            })->groupBy('kode_rs')->get();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'details' => 'required',
            'no_penerimaan' => 'required'
        ]);
        $details = $request->details;
        $data = DistribusiDepo::create($request->only('reff', 'no_distribusi', 'no_penerimaan', 'kode_depo'));
        if ($data) {
            foreach ($details as $key) {
                $data->details()->create($key);
            }
        }

        return new JsonResponse(['message' => 'data telah dibuat'], 201);
    }
}
