<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\Pergeseran;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Penetapan_Pagu;
use App\Models\Siasik\Anggaran\Tampung_Pagu;
use Illuminate\Http\Request;

class PergeseranPaguController extends Controller
{
    public function index()
    {
        $tahun = request('tahun', date('Y'));
        $q     = request('q');

        $query = Penetapan_Pagu::where('penetapan_pagu.tahun', $tahun)
            ->join('kegiatan_blud', 'kegiatan_blud.no', '=', 'penetapan_pagu.kodekegiatan')
            ->join('t_tampung_pagu', 't_tampung_pagu.kodekegiatanblud', '=', 'penetapan_pagu.kodekegiatan')
            ->select(
                'penetapan_pagu.*',
                'kegiatan_blud.no',
                'kegiatan_blud.nomenklatur',
                'kegiatan_blud.kode',
                't_tampung_pagu.pagu as pagu_pergeseran'
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

    public function save(Request $request)
    {
         try {
            $notrans = $request->kodekegiatan;
            $data = Tampung_Pagu::where('kodekegiatanblud', $notrans)->first();

            if (!$data) {
                return response()->json([
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $data->update([
                'pagu' => $request->pagu_pergeseran
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
}
