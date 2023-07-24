<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Mgeneralconsent;
use App\Models\Simrs\Pendaftaran\Rajalumum\Generalconsenttrans_h;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeneralconsentController extends Controller
{
    public function mastergeneralconsent()
    {
        $data = Mgeneralconsent::when(request('kelompok'), function ($query, $param) {
            $query->where('kelompok', $param);
        })->get();
        return new JsonResponse($data);
    }

    public function simpangeneralcontent(Request $request)
    {
        return 'ok';
    }

    public function simpanmaster(Request $request)
    {
        // return response()->json($request->all());
        $data = Mgeneralconsent::updateOrCreate(
            ['kelompok' => $request->kelompok],
            ['pernyataan' => $request->pernyataan]
        );

        return response()->json($data);
    }
}
