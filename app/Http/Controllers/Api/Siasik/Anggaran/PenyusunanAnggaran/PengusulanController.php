<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Penetapan_Pagu;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class PengusulanController extends Controller
{
    public function selectKegiatan()
    {
        $perPage = request('per_page', 50);
        $tahun = request('tahun','Y');
        $query = Penetapan_Pagu::where('penetapan_pagu.tahun',$tahun)
        ->join('kegiatan_blud', 'kegiatan_blud.no', 'penetapan_pagu.kodekegiatan')
        ->select('penetapan_pagu.*', 'kegiatan_blud.no', 'kegiatan_blud.nomenklatur', 'kegiatan_blud.kode');
         if (request('q')) {
            $cari = request('q');
            $query->where(function ($q) use ($cari) {
                $q->where('nomenklatur', 'like', '%' . $cari . '%')
                  ->orWhere('kegiatanblud', 'like', '%' . $cari . '%');
            });
        }
         if ($perPage <= 0) {
            $data = $query->get();
            return new JsonResponse(['data' => $data]);
        }
        $data = $query->simplePaginate($perPage);
        return new JsonResponse($data);
    }

   

}
