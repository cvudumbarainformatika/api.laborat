<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiSisaAnggaran;

use App\Http\Controllers\Controller;
use App\Models\Siasik\TransaksiSilpa\SisaAnggaran;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SilpaController extends Controller
{
    public function getSilpa(){

        $thn=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        $awal = $thn.'-01-01';
        $akhir=request('tglx', 'Y-m-d');
        // $thnakhir =Carbon::createFromFormat('Y-m-d', request('tglx'))->format('Y');
        // if($thn !== $thnakhir){
        //  return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        // }
        $silpa = SisaAnggaran::where('tahun', $thn)
        ->select('silpa.notrans',
                'silpa.tanggal',
                'silpa.koderek50',
                'silpa.uraian50',
                'silpa.nominal')
        ->whereBetween('tanggal', [$awal, $akhir])
        ->when(request('q'),function ($query) {
            $query->where('notrans', 'LIKE', '%' . request('q') . '%')
                ->orWhere('tanggal', 'LIKE', '%' . request('q') . '%')
                ->orWhere('nominal', 'LIKE', '%' . request('q') . '%');
        })
        ->paginate(request('per_page'));
        $collect = collect($silpa);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');
        return new JsonResponse ($balik);
    }
    public function transSilpa(Request $request){

        try{
            DB::beginTransaction();
            if (!$request->has('id')){
                SisaAnggaran::firstOrCreate([
                    'notrans'=> self::buatnomor(),
                    'tanggal' => $request->tanggal,
                    'tahun' => $request->tahun,
                    'nominal'=> $request->nominal,
                    'koderek50'=> '6.1.01.08.01.0001',
                    'uraian50' => 'Sisa Lebih Perhitungan Anggaran BLUD',
                    'kode79' => '6.1.1',
                    'uraian79' => 'Sisa Lebih Perhitungan Anggaran Tahun Anggaran Sebelumnya'
                ]);
            }
            else {
                $data = SisaAnggaran::find($request->id);
                $data->update([
                    'notrans'=> self::buatnomor(),
                    'tanggal' => $request->tanggal,
                    'tahun' => $request->tahun,
                    'nominal'=> $request->nominal,
                    'koderek50'=> '6.1.01.08.01.0001',
                    'uraian50' => 'Sisa Lebih Perhitungan Anggaran BLUD',
                    'kode79' => '6.1.1',
                    'uraian79' => 'Sisa Lebih Perhitungan Anggaran Tahun Anggaran Sebelumnya'
                ]);
            }
            DB::commit();
            return response()->json(['message' => 'Berhasil, Data Tersimpan'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
        // $data = SisaAnggaran::create([
        // 'notrans'=> self::buatnomor(),
        // 'tanggal' => $request->tanggal,
        // 'tahun' => $request->tahun,
        // 'nominal'=> $request->nominal,
        // 'koderek50'=> '6.1.01.08.01.0001',
        // 'uraian50' => 'Sisa Lebih Perhitungan Anggaran BLUD',
        // 'kode79' => '6.1.1',
        // 'uraian79' => 'Sisa Lebih Perhitungan Anggaran Tahun Anggaran Sebelumnya'
        // ]);
        // ($request->only('bulan','tahun','rekening','nilaisaldo'));

        // return new JsonResponse(['message' => 'berhasil disimpan', 'data' => $data], 200);
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
            $urut = (int)substr($ambil->notrans, 1, 3) + 1;
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
    public function hapusSilpa(Request $request){
        $id=$request->id;
        $data=SisaAnggaran::find($id);
        $del=$data->delete();
        if(!$del){
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }

}
