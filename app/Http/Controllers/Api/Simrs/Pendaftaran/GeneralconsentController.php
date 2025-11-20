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
        $data = Mgeneralconsent::select('kelompok', 'pernyataan')
            // ->where('flag','=',null)
            ->when(request('kelompok'), function ($query, $param) {
                $query->where('kelompok', $param);
            })->get();
        return new JsonResponse($data);
    }

    public function simpangeneralcontent(Request $request)
    {
        //decode string base64 image to image
        $ttdpasien = "";
        $ttdpetugas = "";

        $str = $request->noreg;
        $noreg = str_replace('/', '', $str);

        $normNoreg = $request->isRajal? $request->norm : $noreg;


        if ($request->ttdpasien !== null || $request->ttdpasien !== "") {
            $ttdpasien = $this->createImage($request->ttdpasien, $normNoreg);
        }
        if ($request->ttdpetugas !== null || $request->ttdpetugas !== "") {
            $ttdpetugas = $this->createTtdPetugas($request->ttdpetugas, $normNoreg, $request->nikpetugas);
        }

        // simpan ke transaksi general consent pasien

        // return $ttdpetugas;
        $user = auth()->user()->pegawai_id;

        $key = $request->isRajal
            ? ['norm' => $request->norm]
            : ['noreg' => $request->noreg];

        $gencon = Generalconsent::updateOrCreate(
            $key,
            [
                'nama' => $request->nama,
                'alamat' => $request->alamat,
                'nohp' => $request->nohp,
                'hubunganpasien' => $request->hubunganpasien,
                'nikpetugas' => $request->nikpetugas,
                'ttdpasien' => $ttdpasien,
                'ttdpetugas' => $ttdpetugas,
                'wali1' => $request->wali1,
                'wali2' => $request->wali2,
                'hubunganWali1' => $request->hubunganWali1,
                'hubunganWali2' => $request->hubunganWali2,
                'user_input' => $user,

                'norm' => $request->norm,
                'noreg' => $request->noreg,
            ]
        );

        if (!$gencon) {
            $message = [
                'message' => 'Ada yang Error ... Silahkan Ulangi !'
            ];
            return response()->json($message, 500);
        }

        if ($request->isRajal) {
            $res = Generalconsent::where('norm', $request->norm)->first();
        } else {
            $res = Generalconsent::where('noreg', $request->noreg)->first();
        }

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

    // public function saveTtdPasien(Request $request){

    // }

    public function createImage($img, $norm)
    {
        $folderPath = "ttdpasien/";

        $cek = Generalconsent::where('norm', '=', $norm)->first();

        $image_parts = explode(";base64,", $img);
        // return $image_parts;
        if (count($image_parts) < 2) {
            return $img;
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
            // Storage::delete('public/' . $imageName);
            // Storage::disk('remote')->delete('public/' . $imageName);
        }


        // Storage::disk('public')->put($file, $image_base64);
        Storage::disk('remote')->put('public/' . $file, $image_base64);

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
            return $img;
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
            // Storage::delete('public/' . $imageName);
            // Storage::disk('remote')->delete('public/' . $imageName);
        }

        // Storage::disk('public')->put($file, $image_base64);
        Storage::disk('remote')->put('public/' . $file, $image_base64);

        $pegawai = Pegawai::where('nik', $nik)->first();
        $pegawai->ttdpegawai = $file;
        $pegawai->save();

        return $file;
    }

    public function simpanpdf(Request $request)
    {
        if (!$request->hasFile('pdf')) {
            return response()->json(['message' => 'Tidak ada file'], 422);
        }

        // if ($request->hasFile('pdf')) {
        $files = $request->file('pdf');

        // $noregNorm = $request->kelompok == "irja" ? $request->norm : $request->noreg;



        if (!empty($files)) {
            $file = $files;
            $originalname = $file->getClientOriginalName();
            if ($request->kelompok === 'irja') {
                $data = Generalconsent::where('norm', '=', $request->norm)->first();
            } else {
                $data = Generalconsent::where('noreg', '=', $request->noreg)->first();
            }
            // Storage::delete('public/generalconsent/' . $originalname);
            // Storage::disk('remote')->delete('public/generalconsent/' . $originalname);
            $pdf = null;
            if ($data) {
                $pdf = $data;
            } else {
                $pdf = new Generalconsent();
            }
            $file->storeAs('public/generalconsent/', $originalname, 'remote');

            $url = "generalconsent/$originalname";
            $pdf->pdf = $url;
            $pdf->save();
            return response()->json(['success' => $data]);
        }
    }

    public function createImagePdf($img, $norm)
    {


        $folderPath = "generalconsent/";

        $cek = Generalconsent::where('norm', '=', $norm)->first();

        $image_parts = explode(";base64,", $img);
        // return $image_parts;
        if (count($image_parts) < 2) {
            return $img;
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
            // Storage::delete('public/' . $imageName);
            // Storage::disk('remote')->delete('public/' . $imageName);
        }


        // Storage::disk('public')->put($file, $image_base64);
        Storage::disk('remote')->put('public/' . $file, $image_base64);

        // $data = file_get_contents(Storage::disk('public')->get($file));
        // $base64 = 'data:image/' . $image_type . ';base64,' . base64_encode($data);
        return $file;
    }
}
