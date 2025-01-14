<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Planing\Planningdokter;
use App\Models\Simrs\Ranap\Pelayanan\NurseNote;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NursenoteController extends Controller
{

    public function list()
    {
       $data = NurseNote::where('noreg', request('noreg'))->get();
       return new JsonResponse($data);
    }
    
    public function simpan(Request $request)
    {
        $pegawai = Petugas::find(auth()->user()->pegawai_id);

        $data = null;
        if ($request->id === null) {
          $data = new NurseNote();
        } else {
          $data = NurseNote::find($request->id);
        }

        $data->noreg = $request->noreg;
        $data->norm = $request->norm;
        $data->kdruang = $request->kdruang;
        $data->albumin = $request->albumin;
        $data->bb = $request->bb;
        $data->cvp = $request->cvp;
        $data->dia = $request->dia;
        $data->drain = $request->drain;
        $data->dx = $request->dx;
        $data->feces = $request->feces;
        $data->fraksio2 = $request->fraksio2;
        $data->frek = $request->frek;
        $data->gcs = $request->gcs;
        $data->icp = $request->icp;
        $data->implementasi = $request->implementasi;
        $data->infus = $request->infus;
        $data->iwl = $request->iwl;
        $data->kejang = $request->kejang;
        $data->ket = $request->ket;
        $data->mamin = $request->mamin;
        $data->mode = $request->mode;
        $data->muntah = $request->muntah;
        $data->nadi = $request->nadi;
        $data->nyeri = $request->nyeri;
        $data->obat = $request->obat;
        $data->peep = $request->peep;
        $data->pendarahan = $request->pendarahan;
        $data->pins = $request->pins;
        $data->produksigc = $request->produksigc;
        $data->pump = $request->pump;
        $data->ratio = $request->ratio;
        $data->rr = $request->rr;
        $data->sis = $request->sis;
        $data->skor = $request->skor;
        $data->spo2 = $request->spo2;
        $data->suhu = $request->suhu;
        $data->tb = $request->tb;
        $data->tindakan = $request->tindakan;
        $data->ufg = $request->ufg;
        $data->urine = $request->urine;
        $data->water = $request->water;
        $data->zonde = $request->zonde;
        $data->save();


        if (!$data) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Gagal menyimpan data'
          ]);
         }

         return new JsonResponse([
          'success' => true,
          'message' => 'success',
          'result' => $data->load('petugas')
         ]);
        
        return new JsonResponse($data);
    }


    
}
