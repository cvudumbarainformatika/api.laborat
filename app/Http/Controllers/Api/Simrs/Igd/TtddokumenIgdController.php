<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\Igd\TtdDokumenIgd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class TtddokumenIgdController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'noreg' => [
                'required',
                'string',
                'max:50',
            ],

            'norm' => [
                'required',
                'string',
                'max:50',
            ],

            'saksiPasien' => [
                'required',
                'string',
                'max:255',
            ],

            'hubunganPasien' => [
                'required',
                'string',
                'max:100',
            ],

            'resumekeluargapasien' => [
                'required',
                'string',
            ],
        ], [
            'noreg.required' => 'Nomor registrasi wajib diisi.',
            'norm.required' => 'Nomor rekam medis wajib diisi.',
            'saksiPasien.required' => 'Nama saksi pasien wajib diisi.',
            'hubunganPasien.required' => 'Hubungan dengan pasien wajib diisi.',
            'resumekeluargapasien.required' => 'Tanda tangan wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = TtdDokumenIgd::updateOrCreate(
                [
                    'noreg' => $request->noreg,
                    'kodedokumen' => $request->kodedokumen,
                ],
                [
                    'norm' => $request->norm,
                    'nama' => $request->saksiPasien,
                    'statusdenganpasien' => $request->hubunganPasien,
                ]
            );

            // Simpan file gambar ke remote storage; DB hanya menyimpan path-nya.
            $ttd = self::saveImage($request, $request->resumekeluargapasien, $data->id);
            if ($ttd !== null) {
                $data->ttd = $ttd;
                $data->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Tanda tangan berhasil disimpan.',
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Gagal menyimpan tanda tangan.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    private static function saveImage(Request $request, ?string $image, $id): ?string
    {
        if (!$image || !$id || strpos($image, ';base64,') === false) {
            return $image ?: null;
        }

        $parts = explode(';base64,', $image, 2);
        $type = explode('image/', $parts[0])[1] ?? 'png';
        $decoded = base64_decode($parts[1], true);
        if($request->kodedokumen == 'DK-RE'){
            $polder = 'Resume';
        }else if($request->kodedokumen == 'DK-PAM'){
             $polder = 'Awal_Medis';
        }else if($request->kodedokumen == 'DK-PAK'){
             $polder = 'Awal_Keperawatan';
        }
        if ($decoded === false) {
            return null;
        }

        $noreg = str_replace('/', '-', $request->noreg);
        $filename = $id . '-' .$request->noreg. '.' . $type;
        $path = 'dokumen_igd/' . $polder . '/' . $filename;
        Storage::disk('remote')->put('public/' . $path, $decoded);

        return $path;
    }
}
