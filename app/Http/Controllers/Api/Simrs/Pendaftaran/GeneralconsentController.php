<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Generalconsent\Generalconsent;
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
            $ttdpetugas = $this->createTtdPetugas($request->ttdpetugas, $request->norm, $request->nikpetugas);
        }

        // simpan ke transaksi general consent pasien

        // return $ttdpetugas;

        $gencon = Generalconsent::updateOrCreate(
            ['norm' => $request->norm],
            [
                'nama' => $request->nama,
                'alamat' => $request->alamat,
                'nohp' => $request->nohp,
                'hubunganpasien' => $request->hubunganpasien,
                'nikpetugas' => $request->nikpetugas,
                'ttdpasien' => $ttdpasien,
                'ttdpetugas' => $ttdpetugas,
            ]
        );

        if (!$gencon) {
            $message = [
                'message' => 'Ada yang Error ... Silahkan Ulangi !'
            ];
            return response()->json($message, 500);
        }

        $res = Generalconsent::where('norm', $request->norm)->first();

        return response()->json($res);
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


        $folderPath = "ttdpasien/";

        $cek = Generalconsent::where('norm', '=', $norm)->first();

        $image_parts = explode(";base64,", $img);
        // return $image_parts;
        if (count($image_parts) < 2) {
            return $cek->ttdpasien;
        }
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $file = $folderPath . $norm . '-' . date('YmdHis') . '.' . $image_type;

        $imageName = $norm . '.' . $image_type;
        if (!$cek) {
            $imageName = $file;
        } else {
            $imageName = $cek->ttdpasien;
            Storage::delete('public/' . $imageName);
        }


        Storage::disk('public')->put($file, $image_base64);

        // $data = file_get_contents(Storage::disk('public')->get($file));
        // $base64 = 'data:image/' . $image_type . ';base64,' . base64_encode($data);
        return $file;
    }
    public function createTtdPetugas($img, $norm, $nik)
    {
        $folderPath = "ttdpetugas/";

        $cek = Generalconsent::where('norm', '=', $norm)->first();
        $image_parts = explode(";base64,", $img);
        if (count($image_parts) < 2) {
            return $cek->ttdpetugas;
        }
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $file = $folderPath . $norm . '-' . date('YmdHis') . '.' . $image_type;

        $imageName = $norm . '.' . $image_type;
        if (!$cek) {
            $imageName = $file;
        } else {
            $imageName = $cek->ttdpetugas;
            Storage::delete('public/' . $imageName);
        }

        Storage::disk('public')->put($file, $image_base64);

        $pegawai = Pegawai::where('nik', $nik)->first();
        $pegawai->ttdpegawai = $file;
        $pegawai->save();

        return $file;
    }

    public function simpanpdf(Request $request)
    {
        $validatedData = $request->validate([
            'pdf' => 'required|mimes:pdf',
            'norm' => 'required'
        ]);
        $file = $request->name('pdf');
        $name = $file->getClientOriginalName();
        // $uniqueName = bin2hex(random_bytes(3)) . '.' . 'pdf';
        $file->move(public_path('generalconsent/'), $name);



        $data = Generalconsent::where('norm', '=', $request->norm)->first();
        $data->pdf = $file;
        $data->save();
        return response()->json(['success' => $data]);
    }
}
