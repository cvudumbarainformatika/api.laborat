<?php

namespace App\Http\Controllers\Api\Siasik\Master;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Akses\User;
use App\Models\Siasik\Master\PejabatTeknis;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Organisasi\Organisasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class PTKController extends Controller
{
    public function getPegawai(){
        $perPage = request('per_page', 100); // Default ke 100 per halaman, 0 untuk semua data

        $query = Pegawai::select('*')
            ->where('aktif', 'AKTIF')
            ->with('bagiansiasik')
            ;
        // Pencarian
        if (request('q')) {
            $cari = request('q');
            $query->where(function ($q) use ($cari) {
                $q->where('nik', 'like', '%' . $cari . '%')
                  ->orWhere('nama', 'like', '%' . $cari . '%')
                  ->orWhere('alamat', 'like', '%' . $cari . '%');
            });
        }

        if ($perPage <= 0) {
            $akun = $query->get();
            return new JsonResponse(['data' => $akun]);
        }

        $akun = $query->simplePaginate($perPage);

        return new JsonResponse($akun);
    }


    // public function getBidang(){
    //     $perPage = request('per_page', 100); // Default ke 100 per halaman, 0 untuk semua data

    //     $query = Organisasi::select('*')
    //         ->where('kode4', '')
    //         ->whereNotNull('panggilan');
    //     // Pencarian
    //     if (request('q')) {
    //         $cari = request('q');
    //         $query->where(function ($q) use ($cari) {
    //             $q->where('nik', 'like', '%' . $cari . '%')
    //               ->orWhere('panggilan', 'like', '%' . $cari . '%');
    //         });
    //     }

    //     if ($perPage <= 0) {
    //         $akun = $query->get();
    //         return new JsonResponse(['data' => $akun]);
    //     }

    //     $akun = $query->simplePaginate($perPage);

    //     return new JsonResponse($akun);
    // }

    public function index()
    {
        $tahun = request('tahun','Y');
        $data = PejabatTeknis::where('tahun',$tahun)
        ->when(request('q'),function ($query) {
            $query->where('nip', 'LIKE', '%' . request('q') . '%')
            ->orWhere('nama', 'LIKE', '%' . request('q') . '%')
            ->orWhere('bagian', 'LIKE', '%' . request('q') . '%')
            ->orWhere('alias', 'LIKE', '%' . request('q') . '%')
            ;
        })->get();
        return new JsonResponse($data);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'required',
            'nama' => 'required',
            'bagian' => 'required',
            'kodeBagian' => 'required',
            'tahun' => 'required',
            'alias' => 'required',
        ], [
            'nip.required' => 'NIP Harus Di isi.',
            'nama.required' => 'Nama Harus Di isi.',
            'bagian.required' => 'Bagian Harus Di isi.',
            'kodeBagian.required' => 'Kode Bagian Harus Di isi.',
            'tahun.required' => 'Tahun Harus Di isi.',
            'alias.required' => 'Alias Harus Di isi.'
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

            $data = PejabatTeknis::updateOrCreate(
                [
                    'nip' => $validated['nip'],
                ],
                [
                    'nama' => $validated['nama'],
                    'bagian' => $validated['bagian'],
                    'kodeBagian' => $validated['kodeBagian'],
                    'tahun' => $validated['tahun'],
                    'alias' => $validated['alias'],
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

            $data = PejabatTeknis::find($validated['id']);

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
