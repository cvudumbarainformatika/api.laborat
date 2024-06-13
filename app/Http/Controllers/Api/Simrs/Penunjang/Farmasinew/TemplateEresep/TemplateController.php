<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\TemplateEresep;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function cariobat()
    {
      $listobat = Mobatnew::query()
      ->select(
        'new_masterobat.kd_obat',
        'new_masterobat.nama_obat as namaobat',
        'new_masterobat.kandungan as kandungan',
        'new_masterobat.bentuk_sediaan as bentuk_sediaan',
        'new_masterobat.satuan_k as satuankecil',
        'new_masterobat.status_fornas as fornas',
        'new_masterobat.status_forkid as forkit',
        'new_masterobat.status_generik as generik',
        'new_masterobat.status_kronis as kronis',
        'new_masterobat.status_prb as prb',
        'new_masterobat.kode108',
        'new_masterobat.uraian108',
        'new_masterobat.kode50',
        'new_masterobat.uraian50',
        'new_masterobat.kekuatan_dosis as kekuatandosis',
        'new_masterobat.volumesediaan as volumesediaan',
        'new_masterobat.kelompok_psikotropika as psikotropika'
    )
      ->where(function ($query) {
      $query->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%')
          ->orWhere('new_masterobat.kandungan', 'LIKE', '%' . request('q') . '%');
      })->limit(30)->get();
        return new JsonResponse(
          ['dataobat' => $listobat]
        );
    }
}
