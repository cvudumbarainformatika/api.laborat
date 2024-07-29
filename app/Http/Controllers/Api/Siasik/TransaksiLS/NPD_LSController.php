<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use Illuminate\Http\Request;
use App\Helpers\FormatingHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Siasik\Master\Mapping_Bidang_Ptk_Kegiatan;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastrinciM;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;

class NPD_LSController extends Controller
{
    public function perusahaan()
    {
        $phk = Mpihakketiga::select('kode','nama','alamat','npwp','norek','bank','kodemapingrs','namasuplier')
        ->when(request('q'),function ($query) {
            $query->where('nama', 'LIKE', '%' . request('q') . '%');
        })
        ->get();

        return new JsonResponse($phk);
    }
    public function ptk()
    {
        $tahun = date('Y');
        // cari ptk kegiatan
        $cari = Mapping_Bidang_Ptk_Kegiatan::where('tahun', $tahun)->get();

        return new JsonResponse($cari);
    }

    public function bast(){
        $penerimaan=PenerimaanHeder::where('nobast', '!=', '')
        ->whereNotNull('tgl_bast')
        ->with('penerimaanrinci')
        ->orderBy('tgl_bast', 'DESC')
        ->orderBy('nobast', 'DESC')
        ->get();
        return new JsonResponse($penerimaan);
    }
    public function simpan(Request $request)
    {
        $save = NpdLS_heder::create([
            'nonpdls' => self::buatnomor(),
            'kodekegiatanblud' => $request -> kodekegiatanblud
        ]);
        return response()->json(['message' =>'Berhasil Disimpan', 'succes' => $save], 200);
    }
    public static function buatnomor(){
        $bidang = Mapping_Bidang_Ptk_Kegiatan::select('alias')->where('kodekegiatan', request('kodekegiatan'))->get();
        $huruf = ('NPD-LS');
        // $no = ('4.02.0.00.0.00.01.0000');
        date_default_timezone_set('Asia/Jakarta');
        // $tgl = date('Y/m/d');
        $rom = array('','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');
        $thn = date('Y');
        // $time = date('mis');
        // $nomer=Transaksi::latest();
        $cek = NpdLS_heder::count();
        if ($cek == null){
            $urut = "00001";
            $sambung = $urut.'/'.$rom[date('n')].'/'.strtoupper($bidang).'/'.strtoupper($huruf).'/'.$thn;
        }
        else{
            $ambil=NpdLS_heder::all()->last();
            $urut = (int)substr($ambil->notrans, 1, 4) + 1;
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
            $sambung = $urut.'/'.$rom[date('n')].'/'.strtoupper($bidang).'/'.strtoupper($huruf).'/'.$thn;
        }

        return $sambung;
    }
}
