<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Penetapan_Pagu;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class PenetapanPaguController extends Controller
{
    public function index()
    {
        $tahun = request('tahun','Y');
        $data = Penetapan_Pagu::where('penetapan_pagu.tahun',$tahun)
        ->join('kegiatan_blud', 'kegiatan_blud.no', 'penetapan_pagu.kodekegiatan')
        ->select('penetapan_pagu.*', 'kegiatan_blud.no', 'kegiatan_blud.nomenklatur', 'kegiatan_blud.kode')
        ->get();
        return new JsonResponse($data);
    }
    public function save(Request $request)
    {
        $validated = $request->validate([
            'kodekegiatan' => 'required',
            'kegiatanblud' => 'required',
            'kodeorganisasi1' => 'required',
            'kodeorganisasi2' => 'required',
            'kodeorganisasi3' => 'required',
            'namaorganisasi' => 'required',
            'total' => 'required',
            'tahun' => 'required',
        ], [
            'kodekegiatan.required' => 'Kode Kegiatan Harus Di isi.',
            'kegiatanblud.required' => 'Kegiatan BLUD Harus Di isi.',
            'kodeorganisasi1.required' => 'Kode Organisasi 1 Harus Di isi.',
            'kodeorganisasi2.required' => 'Kode Organisasi 2 Harus Di isi.',
            'kodeorganisasi3.required' => 'Kode Organisasi 3 Harus Di isi.',
            'namaorganisasi.required' => 'Nama Organisasi Harus Di isi.',
            'total.required' => 'Total Harus Di isi.',
            'tahun.required' => 'Tahun Harus Di isi.',
        ]);

        $time = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;

        if (empty($request->notrans)) {
            DB::connection('siasik')->select('call penetapan_pagu(@nomor)');
            $x = DB::connection('siasik')->table('conter')->select('penetapan_pagu')->first();

            if (!$x) {
                throw new \Exception('Gagal mendapatkan nomor dari prosedur notadinas');
            }
            $nomer = (int)$x->penetapan_pagu;
            $notrans = FormatingHelper::nonotadinas($nomer, 'PG');
        } else {
            $notrans = $request->notrans;
        }

        try {
            DB::beginTransaction();

            $data = Penetapan_Pagu::updateOrCreate(
                [
                    'notrans' => $notrans
                ],
                [
                    'kodekegiatan' => $validated['kodekegiatan'],
                    'kegiatanblud' => $validated['kegiatanblud'],
                    'kodeorganisasi1' => $validated['kodeorganisasi1'],
                    'kodeorganisasi2' => $validated['kodeorganisasi2'],
                    'kodeorganisasi3' => $validated['kodeorganisasi3'],
                    'namaorganisasi' => $validated['namaorganisasi'],
                    'total' => $validated['total'],
                    'tahun' => $validated['tahun'],
                    'tgl_entry' => $time,
                    'user_entry' => $pegawai,
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

            $data = Penetapan_Pagu::find($validated['id']);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            // Tambahan validasi kunci
            if ($data->kunci == '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak dapat dihapus karena sedang terkunci'
                ], 403);
            }
            // Hapus data utama
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

    public function kunci(Request $request)
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'id' => 'required'
            ]);

            DB::beginTransaction();

            $data = Penetapan_Pagu::find($validated['id']);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $data->kunci = '1';
            $data->save(); 
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dikunci',
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
