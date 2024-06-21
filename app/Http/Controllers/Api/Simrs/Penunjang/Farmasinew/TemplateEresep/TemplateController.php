<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\TemplateEresep;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Template\Templateresep;
use App\Models\Simrs\Penunjang\Farmasinew\Template\TemplateResepRacikan;
use App\Models\Simrs\Penunjang\Farmasinew\Template\TemplateResepRinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

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

    public function simpantemplate(Request $request)
    {
        $user = auth()->user()->pegawai_id;
        $request->request->add(['pegawai_id' => $user]);

        $cek = Templateresep::where('pegawai_id', $user)->where('nama', $request->nama)->where('kodedepo', $request->kodedepo)->first();
        if ($cek) {
          // return new JsonResponse(['message' => 'Maaf ... Template sudah ada, ganti nama'], 406);
          return self::updatetemplate($request, $cek);
        }

        // iF save not update
        return self::store($request);
    }

    public static function updatetemplate($request, $template)
    {
       try {
        //code...
        DB::beginTransaction();
        TemplateResepRinci::where('template_id', $template->id)->delete();

        $rinci = $request->items;
        foreach ($rinci as $key => $value) {
            $simpanRincian = [
                'kodeobat'  => $value['kodeobat'],
                'namaobat'  => $value['namaobat'],
                'forkit'  => $value['forkit'],
                'fornas'  => $value['fornas'],
                'generik'  => $value['generik'],
                'kandungan'  => $value['kandungan'],
                'kekuatandosis'  => $value['kekuatandosis'],
                'keterangan'  => $value['keterangan'],
                'kode50'  => $value['kode50'],
                'kode108'  => $value['kode108'],
                'konsumsi'  => $value['konsumsi'],
                'racikan'  => $value['racikan'] === true ? 1 : 0,
                'satuan_kcl'  => $value['satuan_kcl'],
                'signa'  => $value['signa'],
                'jumlah_diminta'  => $value['jumlah_diminta'],
                'tiperacikan'  => $value['tiperacikan'],
                'tiperesep'  => $value['tiperesep'],
                'template_id'  => $template->id
            ];

            $rincian = TemplateResepRinci::create($simpanRincian);
            if ($value['racikan'] === true && $value['kodeobat'] === $rincian->kodeobat) {
              // hapus dulu rincian racikan
              TemplateResepRacikan::where('obat_id', $rincian->id)->delete();
              foreach ($value['rincian'] as $k => $val) {
                $racikan = [
                  'obat_id'  => $rincian->id,
                  'kodeobat'  => $val['kodeobat'],
                  'namaobat'  => $val['namaobat'],
                  'forkit'  => $val['forkit'],
                  'fornas'  => $val['fornas'],
                  'generik'  => $val['generik'],
                  // 'kandungan'  => $val['kandungan'],
                  'kekuatandosis'  => $val['kekuatandosis'],
                  'keterangan'  => $val['keterangan'],
                  'kode50'  => $val['kode50'],
                  'kode108'  => $val['kode108'],
                  // 'konsumsi'  => $val['konsumsi'],
                  'satuan_kcl'  => $val['satuan_kcl'],
                  // 'signa'  => $val['signa'],
                  'jumlah_diminta'  => $val['jumlah_diminta'],
                  'dosis'  => $val['dosis'],
                  // 'tiperacikan'  => $val['tiperacikan'],
                  // 'tiperesep'  => $val['tiperesep'],
                ];

                TemplateResepRacikan::create($racikan);
              }
            }
        };
        DB::commit();
        return new JsonResponse($template->load(['rincian.rincian']), 200);

       } catch (\Throwable $th) {
          DB::rollback();
          return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $th], 500);
       }
    }

    public static function store($request)
    {
        try {
          DB::beginTransaction();
          $saved = Templateresep::create($request->all());
          
          $rinci = $request->items;
        foreach ($rinci as $key => $value) {
            $simpanRincian = [
                'kodeobat'  => $value['kodeobat'],
                'namaobat'  => $value['namaobat'],
                'forkit'  => $value['forkit'],
                'fornas'  => $value['fornas'],
                'generik'  => $value['generik'],
                'kandungan'  => $value['kandungan'],
                'kekuatandosis'  => $value['kekuatandosis'],
                'keterangan'  => $value['keterangan'],
                'kode50'  => $value['kode50'],
                'kode108'  => $value['kode108'],
                'konsumsi'  => $value['konsumsi'],
                'racikan'  => $value['racikan'] === true ? 1 : 0,
                'satuan_kcl'  => $value['satuan_kcl'],
                'signa'  => $value['signa'],
                'jumlah_diminta'  => $value['jumlah_diminta'],
                'tiperacikan'  => $value['tiperacikan'],
                'tiperesep'  => $value['tiperesep'],
                'template_id'  => $saved->id
            ];

            $rincian = TemplateResepRinci::create($simpanRincian);
            if ($value['racikan'] === true && $value['kodeobat'] === $rincian->kodeobat) {
              foreach ($value['rincian'] as $k => $val) {
                $racikan = [
                  'obat_id'  => $rincian->id,
                  'kodeobat'  => $val['kodeobat'],
                  'namaobat'  => $val['namaobat'],
                  'forkit'  => $val['forkit'],
                  'fornas'  => $val['fornas'],
                  'generik'  => $val['generik'],
                  // 'kandungan'  => $val['kandungan'],
                  'kekuatandosis'  => $val['kekuatandosis'],
                  'keterangan'  => $val['keterangan'],
                  'kode50'  => $val['kode50'],
                  'kode108'  => $val['kode108'],
                  // 'konsumsi'  => $val['konsumsi'],
                  'satuan_kcl'  => $val['satuan_kcl'],
                  // 'signa'  => $val['signa'],
                  'jumlah_diminta'  => $val['jumlah_diminta'],
                  'dosis'  => $val['dosis'],
                  // 'tiperacikan'  => $val['tiperacikan'],
                  // 'tiperesep'  => $val['tiperesep'],
                ];

                TemplateResepRacikan::create($racikan);
              }
            }
            
        };
          DB::commit();
          return new JsonResponse($saved->load(['rincian.rincian']), 200);

        } catch (\Throwable $th) {
          DB::rollback();
          return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $th], 500);
        }
    }

    public function gettemplate()
    {
        $data = Templateresep::where('pegawai_id', auth()->user()->pegawai_id)
        ->where('kodedepo', request('kodedepo'))
        ->with('rincian.rincian')
        ->get();

        return new JsonResponse($data, 200);
    }
}
