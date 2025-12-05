<?php

namespace App\Http\Controllers\Api\Siasik\Master;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Master\Kegiatan_Blud;
use App\Models\Siasik\Master\Organisasi_siasik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class KegiatanBludController extends Controller
{
    public function getBidang(){
        $perPage = request('per_page', 100); // Default ke 100 per halaman, 0 untuk semua data

        $query = Organisasi_siasik::select('*')
            ->where('kode4', '')
            ->whereNotNull('kode3')
            ->whereNotNull('panggilan');
        // Pencarian
        if (request('q')) {
            $cari = request('q');
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', '%' . $cari . '%')
                  ->orWhere('panggilan', 'like', '%' . $cari . '%');
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
        $data = Kegiatan_Blud::where('tahun',$tahun)
        ->when(request('q'),function ($query) {
            $query->where('nomenklatur', 'LIKE', '%' . request('q') . '%')
            ->orWhere('organisasi_nama', 'LIKE', '%' . request('q') . '%')
            ;
        })->get();
        return new JsonResponse($data);
    }



    public function save(Request $request)
    {
        $validated = $request->validate([
            'nomenklatur' => 'required',
            'organisasi_kode1' => 'required',
            'organisasi_kode2' => 'required',
            'organisasi_kode3' => 'required',
            'organisasi_nama' => 'required',
            'kode' => 'required',
            'tahun' => 'required',
        ], [
            'nomenklatur.required' => 'NIP Harus Di isi.',
            'organisasi_kode1.required' => 'Bagian Harus Di isi.',
            'organisasi_kode2.required' => 'Bagian Harus Di isi.',
            'organisasi_kode3.required' => 'Bagian Harus Di isi.',
            'organisasi_nama.required' => 'Bagian Harus Di isi.',
            'kode.required' => 'Kode Bagian Harus Di isi.',
            'tahun.required' => 'Bagian Harus Di isi.',
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

            $data = Kegiatan_Blud::updateOrCreate(
                [
                    'no' => $request->no
                ],
                [
                    'nomenklatur' => $validated['nomenklatur'],
                    'organisasi_kode1' => $validated['organisasi_kode1'],
                    'organisasi_kode2' => $validated['organisasi_kode2'],
                    'organisasi_kode3' => $validated['organisasi_kode3'],
                    'organisasi_nama' => $validated['organisasi_nama'],
                    'kode' => $validated['kode'],
                    'tahun' => $validated['tahun'],
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
                'no' => 'required'
            ]);

            DB::beginTransaction();

            // $data = Kegiatan_Blud::find($validated['no']);
            $data = Kegiatan_Blud::where('no', $validated['no'])->first();

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            // Hapus data utama
            $data->delete();

            // Hapus detail / relasi
            // Tampung_pendapatan::where('notrans', $data->notrans)->delete();

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
