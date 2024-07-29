<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use Illuminate\Http\Request;
use App\Helpers\FormatingHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;

class KontrakController extends Controller
{
    public function listkontrak()
    {
        $data = KontrakPengerjaan::where('kunci', '=', 1)
        ->when(request('q'),function ($query) {
            $query->where('nokontrak', 'LIKE', '%' . request('q') . '%')
            ->orWhere('namaperusahaan', 'LIKE', '%' . request('q') . '%')
            ->orWhere('namaperusahaan', 'LIKE', '%' . request('q') . '%')
            ->orWhere('kegiatanblud', 'LIKE', '%' . request('q') . '%');
        })->paginate(request('per_page'));

        return new JsonResponse($data);
    }
    public function simpankontrak(Request $request)
    {
        $nomor = $request->notrans ?? self::buatnomor();
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
                'kodepptk' => $request->kodepptk ?? '',
                'namapptk' => $request->namapptk ?? '',
                'program' => 'PROGRAM PENUNJANG URUSAN PEMERINTAH DAERAH KABUPATEN/KOTA',
                'kegiatan' => 'PELAYANAN DAN PENUNJANG PELAYANAN BLUD',
                'kodekegiatanblud' => $request->kodekegiatanblud ?? '',
                'kegiatanblud' => $request->kegiatanblud ?? '',
                'kodemapingrs' => $request->kodemapingrs ?? '',
                'namasuplier' => $request->namasuplier ?? '',
                'nilaikontrak' => $request->nilaikontrak ?? '',
                'kodeBagian' => $request->kodebagian ?? '',
                'nokontrakx' => $request->nokontrakx ?? '',
                'termin' => $request->termin ?? ''
                // 'userentry'=>$user['kodesimrs']
            ]
        );
        if (!$simpan){
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }
        // else {
        //     return new JsonResponse(['message' => 'Berhasil di Simpan'], 200);
        // }
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
