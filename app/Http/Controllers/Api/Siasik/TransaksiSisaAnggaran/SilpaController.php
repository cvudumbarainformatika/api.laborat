<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiSisaAnggaran;

use App\Http\Controllers\Controller;
use App\Models\Siasik\TransaksiSilpa\SisaAnggaran;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SilpaController extends Controller
{
    public function getSilpa(){
        // $thn=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        // // $awal=request('tgl', 'Y'.'-'.'01-01');
        // $awal = $thn.'-01-01';
        // $akhir=request('tglx', 'Y-m-d');
        // $thnakhir =Carbon::createFromFormat('Y-m-d', request('tglx'))->format('Y');
        // if($thn !== $thnakhir){
        //  return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        // }
        $silpa = SisaAnggaran::select('silpa.koderek50',
                                    'silpa.kode79',
                                    'silpa.tanggal',
                                    'silpa.nominal')->get();
        return new JsonResponse ($silpa);
    }
    public function transSilpa(Request $request){

        $data = SisaAnggaran::create([
        'notrans'=> self::buatnomor(),
        'tanggal' => $request->tanggal,
        'tahun' => $request->tahun,
        'nominal'=> $request->nominal,
        'koderek50'=> '6.1.01.08.01.0001',
        'uraian50' => 'Sisa Lebih Perhitungan Anggaran BLUD',
        'kode79' => '6.1.1',
        'uraian79' => 'Sisa Lebih Perhitungan Anggaran Tahun Anggaran Sebelumnya'
        ]);
        // ($request->only('bulan','tahun','rekening','nilaisaldo'));

        return new JsonResponse(['message' => 'berhasil disimpan', 'data' => $data], 200);
    }
    public static function buatnomor(){
        $huruf = ('SILPA-BLUD');
        // $no = ('4.02.0.00.0.00.01.0000');
        date_default_timezone_set('Asia/Jakarta');
        // $tgl = date('Y/m/d');
        $thn = date('Y');
        $rom = array('','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');
        // $time = date('mis');
        // $nomer=Transaksi::latest();
        $cek = SisaAnggaran::count();
        if ($cek == null){
            $urut = "0001";
            $sambung = $urut.'/'.strtoupper($huruf).'/'.$rom[date('n')].'/'.$thn;
        }
        else{
            $ambil=SisaAnggaran::all()->last();
            $urut = (int)substr($ambil->no_transaksi, 0, 4) + 1;
            //cara menyambungkan antara tgl dn kata dihubungkan tnda .
            // $urut = "000" . $urut;
            if(strlen($urut) == 1){
                $urut = "000" . $urut;
            }
            else if(strlen($urut) == 2){
                $urut = "00" . $urut;
            }
            else if(strlen($urut) == 3){
                $urut = "0" . $urut;
            }
            else {
                $urut = (int)$urut;
            }
            $sambung = $urut.'/'.strtoupper($huruf).'/'.$rom[date('n')].'/'.$thn;
        }

        return $sambung;
    }
}
