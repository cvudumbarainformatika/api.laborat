<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Anggaran_Pendapatan;
use App\Models\Siasik\Anggaran\Tampung_pendapatan;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class AnggaranPendapatanController extends Controller
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
        $data = Anggaran_Pendapatan::where('tahun',$tahun)->get();
        return new JsonResponse($data);
    }
    
    public function save(Request $request)
    {
        $validated = $request->validate([
            'notrnas' => 'nullable',
            'bidang' => 'nullable',
            'koderekeningblud' => 'required',
            'uraian_rekening' => 'nullable',
            'nilai' => 'required',
            'tahun' => 'nullable',
            'tgl_entry' => 'nullable',
            'user_entry' => 'nullable',
        ], [
            'koderekeningblud.required' => 'Rekening Harus Di isi.',
            'nilai.required' => 'Nilai Harus Di isi.'
        ]);

        $time = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;

        if (empty($request->notrans)) {
            DB::connection('siasik')->select('call anggaranpendapatan(@nomor)');
            $x = DB::connection('siasik')->table('conter')->select('anggaran_pendapatan')->first();

            if (!$x) {
                throw new \Exception('Gagal mendapatkan nomor dari prosedur notadinas');
            }
            $nomer = (int)$x->anggaran_pendapatan;
            $notrans = FormatingHelper::nonotadinas($nomer, 'AP');
        } else {
            $notrans = $request->notrans;
        }

        try {
            DB::beginTransaction();

            $anggaran = Anggaran_Pendapatan::updateOrCreate(
                [
                    'notrans' => $notrans
                ],
                [
                    'bidang' => $validated['bidang'],
                    'koderekeningblud' => $validated['koderekeningblud'],
                    'uraian_rekening' => $validated['uraian_rekening'],
                    'nilai' => $validated['nilai'],
                    'tahun' => $validated['tahun'],
                    'tgl_entry' => $time,
                    'user_entry' => $pegawai,
                ]
            );
            if ($anggaran) {
                Tampung_pendapatan::create([
                    'notrans' => $anggaran->notrans,
                    'pagu' => $anggaran->nilai,
                    'koderekeningblud' => $anggaran->koderekeningblud,
                    'tahun' => $anggaran->tahun,
                ]);
            }

            DB::commit();
            return new JsonResponse(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $anggaran]);
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

            $data = Anggaran_Pendapatan::find($validated['id']);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            // Hapus data utama
            $data->delete();

            // Hapus detail / relasi
            Tampung_pendapatan::where('notrans', $data->notrans)->delete();

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
