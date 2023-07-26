<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Mgeneralconsent;
use App\Models\Simrs\Pendaftaran\Rajalumum\Generalconsenttrans_h;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        //decode string base64 image to image
        $ttdpasien = "";
        $ttdpetugas = "";
        if ($request->ttdpasien !== null || $request->ttdpasien !== "") {
            $ttdpasien = $this->createImage($request->ttdpasien, $request->norm);
        }
        if ($request->ttdpetugas !== null || $request->ttdpetugas !== "") {
            $ttdpetugas = $this->createImage($request->ttdpasien, $request->norm);
        }

        // simpan ke transaksi general consent pasien
        return response()->json($ttdpasien);
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

    public function createImage($img, $norm)
    {

        $folderPath = "images/";

        $image_parts = explode(";base64,", $img);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $file = $folderPath . $norm . '.' . $image_type;

        $imageName = $norm . '.' . $image_type;
        // file_put_contents($file, $image_base64);
        Storage::delete('public/images/' . $imageName);
        Storage::disk('public')->put('images/' . $imageName, $image_base64);

        // $data = file_get_contents(Storage::disk('public')->get($file));
        // $base64 = 'data:image/' . $image_type . ';base64,' . base64_encode($data);
        return $file;
    }
}
