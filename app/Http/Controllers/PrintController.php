<?php

namespace App\Http\Controllers;

use App\Models\LaboratLuar;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class PrintController extends Controller
{

    public function index()
    {
        $page = request('data');
        $params = request('q');
        if ($page === 'permintaan-laborat-luar') {
            return $this->print_permintaan_luar($params);
        }

    }

    public function print_permintaan_luar($q)
    {
        $header = (object) array(
            'title'=> 'UOBK RSUD dr. MOHAMAD SALEH',
            'sub'=> 'Jl. Mayjend Panjaitan No. 65 Probolinggo Jawa Timur',
            'sub2'=> 'Telp. (0335) 433478,433119,421118 Fax. (0335) 432702',
        );
        $details = LaboratLuar::query()
        ->selectRaw('
            nama, kelamin, alamat,
            nota,tgl,pengirim,hasil,hl,kd_lab,jml,hasil,tarif_sarana,tarif_pelayanan,
            (tarif_sarana + tarif_pelayanan) as biaya, ((tarif_sarana + tarif_pelayanan)* jml) as subtotal')
        ->where('nota', $q)
        ->with(['perusahaan', 'pemeriksaan_laborat'])
        ->get();

        // return response()->json($details);
       $data = array(
        'header'=> $header,
        'details'=> $details
       );

    //    return response()->json($data);
       return view('print.permintaan_laborat_luar',$data);
    }


}
