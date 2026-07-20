<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Kamaroperasi\PembatalanPelayanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PembatalanPelayananController extends Controller
{
    public function getPembatalan(Request $request)
    {
        $request->validate([
            'noreg' => 'required'
        ]);

        $data = PembatalanPelayanan::with('dpjp')
            ->where('noreg', $request->noreg)
            ->first();

        return new JsonResponse($data);
    }

    public static function is_base64_image($image)
    {
        return str_starts_with($image, 'data:image');
    }

    public static function saveImage($request, $image, $id)
    {
        $file = null;
        if ($image && $id) {
            $name = $id;
            $noreg = str_replace('/', '-', $request->noreg);
            $folderPath = "pembatalan_operasi/" . $noreg . '/';

            $image_parts = explode(";base64,", $image);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1], true);
            $file = $folderPath . $name . '.' . $image_type;

            $imageName = $name . '.' . $image_type;
            Storage::disk('remote')->put('public/' . $folderPath . $imageName, $image_base64);
        }
        return $file;
    }

    public function store(Request $request)
    {
        $request->validate([
            'noreg' => 'required',
            'norm' => 'required',
        ]);

        $user = FormatingHelper::session_user();

        $ttd = $request->ttd_penanggung_jawab;
        $ttdPath = null;
        if (filled($ttd)) {
            if (self::is_base64_image($ttd)) {
                $filename = 'ttd_' . time();
                $ttdPath = self::saveImage($request, $ttd, $filename);
            } else {
                $ttdPath = $ttd;
            }
        }

        $data = PembatalanPelayanan::updateOrCreate(
            [
                'noreg' => $request->noreg,
            ],
            [
                'norm' => $request->norm,
                'nota' => $request->nota,
                'nama_penerima_informasi' => $request->nama_penerima_informasi,
                'tgllahir_penerima_informasi' => $request->tgllahir_penerima_informasi,
                'umur_penerima_informasi' => $request->umur_penerima_informasi,
                'hubungan_penerima_informasi' => $request->hubungan_penerima_informasi,
                'alasan_pembatalan' => $request->alasan_pembatalan,
                'alternatif_pilihan' => $request->alternatif_pilihan,
                'alternatif_rujuk_ke' => $request->alternatif_rujuk_ke,
                'alternatif_kembali_rencana' => $request->alternatif_kembali_rencana,
                'dpjp_kodesimrs' => $request->dpjp_kodesimrs,
                'ttd_penanggung_jawab' => $ttdPath,
                'updated_by' => $user['kodesimrs'] ?? null,
            ]
        );

        if ($data->wasRecentlyCreated) {
            $data->update(['created_by' => $user['kodesimrs'] ?? null]);
        }

        $data->load('dpjp');

        return new JsonResponse([
            'message' => 'Data pembatalan pelayanan berhasil disimpan',
            'data' => $data
        ]);
    }
}
