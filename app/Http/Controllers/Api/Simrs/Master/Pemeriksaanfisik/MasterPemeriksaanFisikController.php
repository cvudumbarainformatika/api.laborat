<?php

namespace App\Http\Controllers\Api\Simrs\Master\Pemeriksaanfisik;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpemeriksaanfisik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterPemeriksaanFisikController extends Controller
{
    public function index()
    {

        $data = Mpemeriksaanfisik::all();
        return new JsonResponse($data, 200);
    }

    public function simpanmasterpemeriksaan(Request $request)
    {
        $data = null;
        if ($request->has('id')) {
            $data = Mpemeriksaanfisik::find($request->id);
            $data->nama = $request->nama;
            $data->icon = $request->icon;
            $data->lokalis = $request->lokalis;
            $data->save();
        } else {
            $data = Mpemeriksaanfisik::create(
                [
                    'nama' => $request->nama,
                    'icon' => $request->icon,
                    'lokalis' => $request->lokalis
                ]
            );
        }



        return new JsonResponse(['message' => 'Berhasil disimpan', 'result' => $data], 200);
    }
}
