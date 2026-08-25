<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\RBAPerubahan_pergeseran;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Anggaran_Pendapatan_pak;
use App\Models\Siasik\Anggaran\Tampung_pendapatan;
use App\Models\Siasik\Master\Akun50_2024;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class PergeseranPerubahanPendapatanController extends Controller
{
    public function getRekening(){
        $perPage = request('per_page', 100); // Default ke 100 per halaman, 0 untuk semua data

        $query = Akun50_2024::select('uraian', 'kodeall3', 'kodeall2')
            ->where('subrincian_objek', '!=', '')
            ->where('akun', '4');
        // Pencarian
        if (request('q')) {
            $cari = request('q');
            $query->where(function ($q) use ($cari) {
                $q->where('uraian', 'like', '%' . $cari . '%')
                  ->orWhere('kodeall3', 'like', '%' . $cari . '%');
            });
        }

        if ($perPage <= 0) {
            $akun = $query->get();
            return new JsonResponse(['data' => $akun]);
        }

        $akun = $query->simplePaginate($perPage);

        return new JsonResponse($akun);
    }    

    public function index()
    {
        $tahun = request('tahun','Y');
        $data = Anggaran_Pendapatan_pak::where('anggaran_pendapatan_pak.tahun',$tahun)
        ->leftJoin('t_tampung_pendapatan', 't_tampung_pendapatan.notrans', 'anggaran_pendapatan_pak.notrans')
         ->select('anggaran_pendapatan_pak.*',
                 't_tampung_pendapatan.pagu as nilai_pergeseran',
                 )
        ->get();
        return new JsonResponse($data);
    }
    public function save(Request $request)
    {
         try {
            $notrans = $request->notrans;
            $data = Tampung_pendapatan::where('notrans', $notrans)->first();

            if (!$data) {
                return response()->json([
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $data->update([
                'pagu' => $request->nilai_pergeseran
            ]);

            return response()->json([
                'message' => 'Nilai pagu berhasil diupdate',
                'data' => $data
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'message' => 'Terjadi kesalahan saat update data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function kunci(Request $request)
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'id' => 'required'
            ]);

            DB::beginTransaction();

            $data = Anggaran_Pendapatan_pak::find($validated['id']);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $data->kunci = $data->kunci === '1' ? '' : '1';
            $data->save(); 
            Tampung_pendapatan::where('notrans', $data->notrans)
            ->update([
                'flag' => $data->kunci === '1' ? '1' : ''
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $data->kunci === '1'
                ? 'Data berhasil dikunci'
                : 'Kunci berhasil dibuka',
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
                'message' => 'Gagal proses kunci: ' . $e->getMessage()
            ], 500);
        }
    }
}
