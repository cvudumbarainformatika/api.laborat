<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use Illuminate\Http\Request;
use App\Helpers\FormatingHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use DateTime;

class KontrakController extends Controller
{
    public function listkontrak()
    {
        $tahunawal=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $tahun=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $data = KontrakPengerjaan::when(request('q'),function ($query) {
            $query->where('nokontrak', 'LIKE', '%' . request('q') . '%')
            ->orWhere('namaperusahaan', 'LIKE', '%' . request('q') . '%')
            ->orWhere('nilaikontrak', 'LIKE', '%' . request('q') . '%')
            ->orWhere('nokontrakx', 'LIKE', '%' . request('q') . '%')
            ->orWhere('kegiatanblud', 'LIKE', '%' . request('q') . '%');
        })
        // ->whereYear('tgltrans', date('Y'))
        ->whereBetween('tgltrans', [$tahunawal.'-01-01', $tahun.'-12-31'])
        ->orderBy('tglentry', 'desc')
        ->get();
        // ->paginate(request('per_page'));

        return new JsonResponse($data);
    }
    public function simpankontrak(Request $request)
    {
        $time = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;
        $nomor = $request->nokontrak ?? self::buatnomor();
        try {
            DB::beginTransaction();
             $simpan = KontrakPengerjaan::updateOrCreate(
            [
                'nokontrak'=> $nomor,
            ],
            [
                'kodeperusahaan' => $request->kodeperusahaan ?? '',
                'namaperusahaan' => $request->namaperusahaan ?? '',
                'tglmulaikontrak' => $request->tglmulaikontrak ?? '',
                'tglakhirkontrak' => $request->tglakhirkontrak,
                'tgltrans' => $request->tgltrans ?? '',
                'tglentry' => $time ?? '',
                'kodepptk' => $request->kodepptk ?? '',
                'namapptk' => $request->namapptk ?? '',
                'program' => 'PROGRAM PENUNJANG URUSAN PEMERINTAH DAERAH KABUPATEN/KOTA',
                'kegiatan' => 'PELAYANAN DAN PENUNJANG PELAYANAN BLUD',
                'kodekegiatanblud' => $request->kodekegiatanblud ?? '',
                'kegiatanblud' => $request->kegiatanblud ?? '',
                'kodemapingrs' => $request->kodemapingrs ?? '',
                'namasuplier' => $request->namasuplier ?? '',
                'nilaikontrak' => $request->nilaikontrak ?? '',
                'kodeBagian' => $request->kodeBagian ?? '',
                'nokontrakx' => $request->nokontrakx ?? '',
                'termin' => $request->termin ?? '',
                'userentry'=>$pegawai ?? '',
                'kunci'=> '1'
            ]
        );
        return new JsonResponse(
                [
                    'message' => 'Data Berhasil disimpan...!!!',
                    'result' => $simpan,
                ], 200);
        } catch (\Exception $er) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Ada Kesalahan',
                'error' => $er
            ], 500);
        }

    }
    public function deletedata(Request $request){
        $data = KontrakPengerjaan::where('nokontrak', $request->nokontrak)->first();
        if(!$data){
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 404);
        } else {
            $data->delete();
        }
        return new JsonResponse([
            'message' => 'Data Berhasil dihapus',
             'data' => $data
        ]);
    }

    public static function buatnomor(){
        $huruf = ('KP');
        // $no = ('4.02.0.00.0.00.01.0000');
        date_default_timezone_set('Asia/Jakarta');
        // $tgl = date('Y/m/d');
        $rom = array('','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');
        $thn = date('Y');
        // $time = date('mis');
        // $nomer=Transaksi::latest();
        $cek = KontrakPengerjaan::count();
        if ($cek == null){
            $urut = "00001";
            $sambung = $urut.'/'.$rom[date('n')].'/'.strtoupper($huruf).'/'.$thn;
        }
        else{
            $ambil=KontrakPengerjaan::all()->last();
            $urut = (int)substr($ambil->nokontrak, 0, 5) + 1;
            //cara menyambungkan antara tgl dn kata dihubungkan tnda .
            // $urut = "000" . $urut;
            if(strlen($urut) == 1){
                $urut = "0000" . $urut;
            }
            else if(strlen($urut) == 2){
                $urut = "000" . $urut;
            }
            else if(strlen($urut) == 3){
                $urut = "00" . $urut;
            }
            else if(strlen($urut) == 4){
                $urut = "0" . $urut;
            }
            else {
                $urut = (int)$urut;
            }
            $sambung = $urut.'/'.$rom[date('n')].'/'.strtoupper($huruf).'/'.$thn;
        }

        return $sambung;
    }
}
