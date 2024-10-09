<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\JurnalUmum;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\JurnalUmum_Header;
use App\Models\Siasik\Akuntansi\Jurnal\JurnalUmum_Rinci;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use Faker\Provider\cs_CZ\DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JurnalManualController extends Controller
{
    public function permen50()
    {
        $akun=Akun50_2024::where('subrincian_objek', '!=', '')
        ->select('uraian','kodeall3')
        ->when(request('q'), function($q){
            $q->where('uraian', 'LIKE', '%'.request('q').'%');
        })
        ->get();
        return new JsonResponse($akun);
    }

    public function jurnalumumotot()
    {
        $jurnal = JurnalUmum_Header::with(
            [
                'rincianjurnalumum'
            ]
        )
        ->whereYear('tanggal', request('tahuncari'))
        ->get();
        return new JsonResponse($jurnal);
    }

    public function simpanjurnalmanual(Request $request)
    {
        if($request->nobukti === null)
        {
            $nobukti = time()."-JU";
        }else{
            $nobukti = $request->nobukti;
        }
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;
        $simpan = JurnalUmum_Header::updateOrCreate(
            [
                'nobukti' => $nobukti
            ],
            [
                'tanggal' => $request->tanggal,
                'keterangan' => $request->keterangan ?? '',
                'tgl_entry' => Carbon::now(),
                'user_entry' => $kdpegsimrs
            ]
        );
        if(!$simpan)
        {
            return new JsonResponse(['message' => 'MAAF GAGAL DISIMPAN...!!!'], 500);
        }

        $simpanrinci = JurnalUmum_Rinci::create(
            [
                'nobukti' => $nobukti,
                'kodepsap13' => $request->koderekening,
                'uraianpsap13' => $request->uraian,
                'tgl_entry' => Carbon::now(),
                'debet' => $request->jenis === 'Debet' ? $request->nominal : 0,
                'kredit' => $request->jenis === 'Kredit' ? $request->nominal : 0,
                'user_entry' => $kdpegsimrs
            ]
        );
        return new JsonResponse(
            ['message' => 'Data Berhasil Disimpan...!!',
            'nobukti' => $nobukti
        ], 200);
    }

    public function getrincian()
    {
        $data = JurnalUmum_Rinci::where('nobukti', request('nobukti'))
        ->get();

        return new JsonResponse($data);
    }
}
