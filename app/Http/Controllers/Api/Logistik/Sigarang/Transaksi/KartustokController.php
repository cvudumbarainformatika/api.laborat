<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\StokOpname;
use App\Models\Sigarang\Transaksi\Pemesanan\Pemesanan;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KartustokController extends Controller
{
    public function kartustokgudang()
    {
        $kd_tempat = 'Gd-02010100';
        $kd_obat= request('kd_obat');
        $bln    = request('bln');
        $thn    = request('thn');

        if($bln==1){
            $blnx=12;
            $thnx=$thn-1;
        }else{
            $blnx=$bln-1;
            $thnx=$thn;
        }

        $query = BarangRS::select('kode as kode','nama as nama')->with(
            [
                'stok_awal'=>function($stokawal) use ($bln,$blnx,$thn,$thnx,$kd_tempat)
                {
                    $stokawal->select('monthly_stok_updates.id','monthly_stok_updates.tanggal','monthly_stok_updates.no_penerimaan',
                    'monthly_stok_updates.harga','monthly_stok_updates.sisa_stok','monthly_stok_updates.kode_rs')
                               ->whereMonth('monthly_stok_updates.tanggal', $blnx)
                               ->whereYear('monthly_stok_updates.tanggal', $thnx)
                               ->where('monthly_stok_updates.kode_ruang','=', $kd_tempat);

                },
                'masukgudang'=> function($penerimaan) use ($bln,$blnx,$thn,$thnx,$kd_tempat)
                {
                    $penerimaan->select('penerimaans.id as id','penerimaans.tanggal as tanggal','penerimaans.no_penerimaan',
                    'detail_penerimaans.qty as masuk','detail_penerimaans.harga as harga',
                    'detail_penerimaans.satuan_besar as satuan_besar','penerimaans.nomor as nomor')
                               ->whereMonth('penerimaans.tanggal', $bln)
                               ->whereYear('penerimaans.tanggal', $thn);

                },
                'masukgudang.pemesanan:id,nomor,kode_perusahaan','masukgudang.pemesanan.perusahaan:kode,nama',
                'keluargudang'=> function($keluar) use ($bln,$blnx,$thn,$thnx,$kd_tempat)
                {
                    $keluar->select('distribusi_depos.id as id','distribusi_depos.tanggal as tanggal','distribusi_depos.no_distribusi as no_distrobusi',
                    'detail_distribusi_depos.jumlah as keluar','distribusi_depos.kode_depo as kode_depo')
                           ->where('status','=','2')
                           ->whereMonth('distribusi_depos.tanggal', $bln)
                           ->whereYear('distribusi_depos.tanggal', $thn)->with(['depo:kode,nama']);
                },
                'stok_akhir'=>function($stokahir) use ($bln,$blnx,$thn,$thnx,$kd_tempat)
                {
                    $stokahir->select('monthly_stok_updates.id','monthly_stok_updates.tanggal','monthly_stok_updates.no_penerimaan',
                    'monthly_stok_updates.harga','monthly_stok_updates.sisa_stok','monthly_stok_updates.kode_rs')
                               ->whereMonth('monthly_stok_updates.tanggal', $bln)
                               ->whereYear('monthly_stok_updates.tanggal', $thn)
                               ->where('monthly_stok_updates.kode_ruang','=', $kd_tempat);

                }
            ])
        ->where('kode','=', $kd_obat)
        ->get();

        return new JsonResponse($query);
    }
}
