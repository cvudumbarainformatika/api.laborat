<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\RBAPerubahan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Penetapan_Pagu_pak;
use App\Models\Siasik\Anggaran\Tampung_Pagu;
use App\Models\Siasik\Anggaran\Tampung_Pagu_Copy;
use App\Models\Siasik\Anggaran\Tampung_Pagu_pak;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerubahanPaguController extends Controller
{
    public function index()
    {
        $tahun = request('tahun', date('Y'));
        $q     = request('q');

        $query = Penetapan_Pagu_pak::where('penetapan_pagu_pak.tahun', $tahun)
            ->join('kegiatan_blud', 'kegiatan_blud.no', '=', 'penetapan_pagu_pak.kodekegiatan')
            ->select(
                'penetapan_pagu_pak.*',
                'kegiatan_blud.no',
                'kegiatan_blud.nomenklatur',
                'kegiatan_blud.kode'
            );

        // 🔍 FILTER PENCARIAN
        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('kegiatan_blud.nomenklatur', 'like', "%{$q}%")
                ->orWhere('penetapan_pagu.namaorganisasi', 'like', "%{$q}%")
                ->orWhere('penetapan_pagu.total', 'like', "%{$q}%");
            });
        }

        $data = $query->get();

        return response()->json($data);
    }

    private function getRealisasiBelanja($tahun, $kodekegiatanblud)
    {
        $tgl = $tahun . '-01-01';
        $tglx = $tahun . '-12-31';

        $hasil = DB::connection('siasik')->selectOne("
            SELECT 
                COALESCE(SUM(realisasi) - SUM(kurangi), 0) AS realisasix
            FROM (

                -- REALISASI NPKLS
                SELECT 
                    0 AS kurangi,
                    SUM(npkls_rinci.total) AS realisasi
                FROM npkls_rinci
                JOIN npkls_heder 
                    ON npkls_heder.nopencairan = npkls_rinci.nopencairan
                WHERE npkls_heder.tglpencairan >= '" . $tgl . "'
                AND npkls_heder.tglpencairan <= '" . $tglx . "'
                AND npkls_rinci.kodekegiatanblud = '".$kodekegiatanblud."'

                UNION ALL

                -- REALISASI SPJ PANJAR
                SELECT 
                    0 AS kurangi,
                    SUM(spjpanjar_rinci.jumlahbelanjapanjar) AS realisasi
                FROM spjpanjar_heder
                JOIN spjpanjar_rinci 
                    ON spjpanjar_heder.nospjpanjar = spjpanjar_rinci.nospjpanjar
                WHERE spjpanjar_heder.verif = 1
                AND spjpanjar_heder.tglspjpanjar >= '" . $tgl . "'
                AND spjpanjar_heder.tglspjpanjar <= '" . $tglx . "'
                AND spjpanjar_heder.kodekegiatanblud = '".$kodekegiatanblud."'

                UNION ALL

                -- CONTRAPOST (PENGURANG)
                SELECT 
                    SUM(contrapost.nominalcontrapost) AS kurangi,
                    0 AS realisasi
                FROM contrapost
                WHERE contrapost.tglcontrapost >= '" . $tgl . " 00:00:00'
                AND contrapost.tglcontrapost <= '" . $tglx . " 23:59:59'
                AND contrapost.kodekegiatanblud = '".$kodekegiatanblud."'

            ) AS total
        ");

        return (float) ($hasil->realisasix ?? 0);
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
            $notrans = FormatingHelper::nonotadinas($nomer, 'PG_PAK');
        } else {
            $notrans = $request->notrans;
        }

        try {
            $realisasi = $this->getRealisasiBelanja(
                $validated['tahun'],
                $validated['kodekegiatan']
            );

            if ($realisasi > (float) $validated['total']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Total pagu tidak boleh lebih kecil dari realisasi belanja.'
                ], 422);
            }

            DB::beginTransaction();

            $data = Penetapan_Pagu_pak::updateOrCreate(
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
            if ($data) {
                Tampung_Pagu_pak::updateOrCreate([
                    'kodekegiatanblud' => $data->kodekegiatan,
                    'tahun' => $data->tahun,
                ],
                [
                    'pagu' => $data->total,
                ]);
            }

            DB::commit();
            return new JsonResponse(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $data]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['status' => 'error', 'message' => 'Data gagal disimpan: ' . $e->getMessage()], 500);
        }
    }

    // public function penetapan(Request $request) {

    //     $tahun = request('tahun', date('Y'));
    //     try {
    //         DB::beginTransaction();
    //         $dataPak = Penetapan_Pagu_pak::where('tahun', $tahun)->get();

    //         foreach ($dataPak as $item) {

    //             Tampung_Pagu_pak::where('kodekegiatanblud', $item->kodekegiatan)
    //                 ->where('tahun', $item->tahun)
    //                 ->update([
    //                     'pagu'    => $item->pagu
    //                 ]);
                
    //         }
    //         DB::commit();
    //         return new JsonResponse(['status' => 'success', 'message' => 'Berhasil Penetapan Data Pendapatan']);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return new JsonResponse(['status' => 'error', 'message' => 'Data gagal penetapan: ' . $e->getMessage()], 500);
    //     }
    // }
    public function delete(Request $request)
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'id' => 'required'
            ]);

            DB::beginTransaction();

            $data = Penetapan_Pagu_pak::find($validated['id']);

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
            Tampung_Pagu_Copy::where('kodekegiatanblud', $data->kodekegiatan)->delete();

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

            $data = Penetapan_Pagu_pak::find($validated['id']);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $data->kunci = $data->kunci === '1' ? '' : '1';
            $data->save(); 
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
                'message' => 'Gagal Kunci Data: ' . $e->getMessage()
            ], 500);
        }
    }
}
