<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\Igd\TtdDokumenIgd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                    'ttd' => $request->resumekeluargapasien,
                ]
            );

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
}
