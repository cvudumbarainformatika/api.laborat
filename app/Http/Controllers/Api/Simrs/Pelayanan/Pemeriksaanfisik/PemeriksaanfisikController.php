<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Pemeriksaanfisik;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PemeriksaanfisikController extends Controller
{
    public function simpan(Request $request)
    {
        $simpanperiksaan = Pemeriksaanfisik::create(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->denyutjantung,
                'pernapasan' => $request->pernapasan,
                'sistole' => $request->sistole,
                'diastole' => $request->diastole,
                'suhutubuh' => $request->suhutubuh,
                'statuspsikologis' => $request->statuspsikologis,
                'sosialekonomi' => $request->sosialekonomi,
                'spiritual' => $request->spiritual,
                'user'  => auth()->user()->pegawai_id,
            ]
        );



        return new JsonResponse($request->all());
    }

    public function simpangambar(Request $request)
    {
        $image = $request->image;

        $name = date('YmdHis');
        $noreg = str_replace('/', '-', $request->noreg);
        $folderPath = "pemeriksaan_fisik/" . $noreg . '/';

        $image_parts = explode(";base64,", $image);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $file = $folderPath . $name . '.' . $image_type;

        $imageName = $name . '.' . $image_type;
        // Storage::delete('public/pemeriksaan_fisik/' . $noreg . '/' . $imageName);
        $wew = Storage::disk('public')->put('pemeriksaan_fisik/' . $noreg . '/' . $imageName, $image_base64);

        return $file;
    }
}
