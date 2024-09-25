<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\SaldoAwal;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaldoawalController extends Controller
{
    public function akunsaldo(){
        $akun=Akun50_2024::where('subrincian_objek', '!=', '')
        ->select('uraian','kodeall3')
        ->when(request('q'), function($q){
            $q->where('uraian', 'LIKE', '%'.request('q').'%');
        })
        ->get();
        return new JsonResponse($akun);
    }

    public function index(){
        $saldo=SaldoAwal::all();
        return new JsonResponse($saldo);
    }
    public function save(Request $request){
        // $user = auth()->user()->pegawai_id;
        // $pg= Pegawai::find($user);
        // $pegawai= $pg->kdpegsimrs;
        $year=date('Y');
        $time = date('Y-m-d H:i:s');
        $saldo=SaldoAwal::create([
                'kodepsap13' => $request['kodepsap13'],
                'uraianpsap13' => $request['uraianpsap13'],
                'debit' => $request['debit'],
                'kredit' => $request['kredit'],
                'tahun' => $year,
                'tglentry' => $time,
                // 'userentry'=> $pegawai

        ]);
        return new JsonResponse(['message' => 'Data Berhasil disimpan...!!!', 'result'=> $saldo]);
    }
}
