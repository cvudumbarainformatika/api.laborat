<?php

namespace App\Http\Controllers\Api\Siasik\Master;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Penetapan_Pagu;
use App\Models\Siasik\Master\Mapping_Bidang_Ptk_Kegiatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Mapping_KegiatanPtkController extends Controller
{
    public function index()
    {
        $tahun = request('tahun','Y');
        $data = Mapping_Bidang_Ptk_Kegiatan::where('tahun',$tahun)
        ->when(request('q'),function ($query) {
            $query->where('kodepptk', 'LIKE', '%' . request('q') . '%')
            ->orWhere('namapptk', 'LIKE', '%' . request('q') . '%')
            ->orWhere('kegiatan', 'LIKE', '%' . request('q') . '%')
            ->orWhere('bidang', 'LIKE', '%' . request('q') . '%')
            ->orWhere('alias', 'LIKE', '%' . request('q') . '%')
            ;
        })->get();
        return new JsonResponse($data);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'kodepptk' => 'required',
            'namapptk' => 'required',
            'kodekegiatan' => 'required',
            'kegiatan' => 'required',
            'kodebidang' => 'required',
            'bidang' => 'required',
            'tahun' => 'required',
            'alias' => 'required',
        ], [
            'kodepptk.required' => 'Kode PTK Harus Di isi.',
            'namapptk.required' => 'Nama PTK Harus Di isi.',
            'kodekegiatan.required' => 'Kode Kegiatan Harus Di isi.',
            'kegiatan.required' => 'Kegiatan Harus Di isi.',
            'kodebidang.required' => 'Kode Bidang Harus Di isi.',
            'bidang.required' => 'Bidang Harus Di isi.',
            'tahun.required' => 'Tahun Harus Di isi.',
            'alias.required' => 'Alias Harus Di isi.',
        ]);

        // $time = date('Y-m-d H:i:s');
        // $user = auth()->user()->pegawai_id;
        // $pg= Pegawai::find($user);
        // $pegawai= $pg->kdpegsimrs;

        // if (empty($request->notrans)) {
        //     DB::connection('siasik')->select('call anggaranpendapatan(@nomor)');
        //     $x = DB::connection('siasik')->table('conter')->select('anggaran_pendapatan')->first();

        //     if (!$x) {
        //         throw new \Exception('Gagal mendapatkan nomor dari prosedur notadinas');
        //     }
        //     $nomer = (int)$x->anggaran_pendapatan;
        //     $notrans = FormatingHelper::nonotadinas($nomer, 'AP');
        // } else {
        //     $notrans = $request->notrans;
        // }

        try {
            DB::beginTransaction();

            $data = Mapping_Bidang_Ptk_Kegiatan::updateOrCreate(
                [
                    'id' => $request->id
                ],
                [
                    'kodepptk' => $validated['kodepptk'],
                    'namapptk' => $validated['namapptk'],
                    'kodekegiatan' => $validated['kodekegiatan'],
                    'kegiatan' => $validated['kegiatan'],
                    'kodebidang' => $validated['kodebidang'],
                    'bidang' => $validated['bidang'],
                    'tahun' => $validated['tahun'],
                    'alias' => $validated['alias']
                ]
            );
            // if ($anggaran) {
            //     Tampung_pendapatan::create([
            //         'notrans' => $anggaran->notrans,
            //         'pagu' => $anggaran->nilai,
            //         'koderekeningblud' => $anggaran->koderekeningblud,
            //         'tahun' => $anggaran->tahun,
            //     ]);
            // }

            DB::commit();
            return new JsonResponse(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $data]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['status' => 'error', 'message' => 'Data gagal disimpan: ' . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'id' => 'required'
            ]);

            DB::beginTransaction();

            $data = Mapping_Bidang_Ptk_Kegiatan::find($validated['id']);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }
            $PenetapanPagu = Penetapan_Pagu::where('kodekegiatan', $data->kodekegiatan)->exists();
            if ($PenetapanPagu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak dapat dihapus karena sudah dilakukan Penetapan Pagu'
                ], 403);
            }
            $data->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus',
                'data' => $data
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
