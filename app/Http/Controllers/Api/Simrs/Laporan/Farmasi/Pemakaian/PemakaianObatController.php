<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Pemakaian;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\SistemBayar;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemakaianObatController extends Controller
{
    function getAllPemakaianObat(){
        
        $dateAwal = Carbon::parse(request('from'));
        $dateAkhir = Carbon::parse(request('to'));
        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m-d');
        $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-d');
        $gudangdepo=['Gd-03010100', 'Gd-03010101', 'Gd-05010100', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']; //  
        $obat=Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'bentuk_sediaan',
            'status_forkid',
            'status_fornas',
            'status_generik',
            'status_prb',
            'status_konsinyasi',
            'status_kronis',
            'kelompok_psikotropika',
            'kode108',
            )
        ->with([
            'kodebelanja:kode,uraian,uraianB',
            'saldoawal'=>function($sal) use($blnLaluAkhir,$gudangdepo){
                $sal->select(
                    'kdobat',
                    DB::raw('sum(jumlah) as jumlah')
                )
                ->where('tglopname','LIKE','%'.$blnLaluAkhir.'%')
                ->whereIn('kdruang',$gudangdepo)
                ->groupBy('tglopname','kdobat');
            },
            'penerimaanrinci'=>function($ter){
                $ter->select(
                    'penerimaan_r.kdobat',
                    DB::raw('sum(penerimaan_r.jml_terima_k) as jumlah')
                )
                ->leftJoin('penerimaan_h','penerimaan_h.nopenerimaan','=','penerimaan_r.nopenerimaan')
                ->whereBetween('tglpenerimaan',[request('from').' 00:00:00',request('to').' 23:59:59'])
                ->groupBy('penerimaan_r.kdobat');
            },
            'mutasikeluar'=>function($mut){
                $mut->select(
                    'mutasi_gudangdepo.kd_obat',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah'),
                    DB::raw('sum(mutasi_gudangdepo.jml * mutasi_gudangdepo.harga) as subtotal'),
                    )
                ->leftJoin('permintaan_h','permintaan_h.no_permintaan','=','mutasi_gudangdepo.no_permintaan')
                ->whereBetween('permintaan_h.tgl_kirim_depo',[request('from').' 00:00:00',request('to').' 23:59:59'])
                ->where('permintaan_h.dari','LIKE','%R-%');
            },
            
            'resepkeluar'=>function($kel){
                $kel->select(
                    'resep_keluar_r.kdobat',
                    'resep_keluar_h.sistembayar',
                    DB::raw('sum(resep_keluar_r.jumlah) as jumlah'),
                    DB::raw('sum(resep_keluar_r.jumlah * resep_keluar_r.harga_jual) as subtotal'),
                )
                ->leftJoin('resep_keluar_h','resep_keluar_h.noresep','=','resep_keluar_r.noresep')
                ->whereBetween('resep_keluar_h.tgl_selesai',[request('from').' 00:00:00',request('to').' 23:59:59'])
                ->groupBy('resep_keluar_h.sistembayar','resep_keluar_r.kdobat');
                
            },
            
            'resepkeluarracikan'=>function($kel){
                $kel->select(
                    'resep_keluar_racikan_r.kdobat',
                    'resep_keluar_h.sistembayar',
                    DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah'),
                    DB::raw('sum(resep_keluar_racikan_r.jumlah * resep_keluar_racikan_r.harga_jual) as subtotal'),
                )
                ->leftJoin('resep_keluar_h','resep_keluar_h.noresep','=','resep_keluar_racikan_r.noresep')
                ->whereBetween('resep_keluar_h.tgl_selesai',[request('from').' 00:00:00',request('to').' 23:59:59'])
                ->groupBy('resep_keluar_h.sistembayar','resep_keluar_racikan_r.kdobat');                
            }, 
            'returpenjualan'=>function($kel){
                $kel->select(
                    'retur_penjualan_r.kdobat',
                    'resep_keluar_h.sistembayar',
                    DB::raw('sum(retur_penjualan_r.jumlah_keluar) as jumlah'),
                    DB::raw('sum(retur_penjualan_r.jumlah_keluar * retur_penjualan_r.harga_jual) as subtotal'),
                )
                ->leftJoin('retur_penjualan_h','retur_penjualan_h.noretur','=','retur_penjualan_r.noretur')
                ->leftJoin('resep_keluar_h','resep_keluar_h.noresep','=','retur_penjualan_h.noresep')
                ->whereBetween('retur_penjualan_h.tgl_retur',[request('from').' 00:00:00',request('to').' 23:59:59'])
                ->groupBy('resep_keluar_h.sistembayar','retur_penjualan_r.kdobat');                
            }
        ])
        ->get();
        $obat->append('harga');
        return new JsonResponse([
            'data'=>$obat,
            'req'=>request()->all(),
            // 'blnLaluAkhir'=>$blnLaluAkhir
        ]);
    }
    function getPemakaianObat(){
        
        $dateAwal = Carbon::parse(request('from'));
        $dateAkhir = Carbon::parse(request('to'));
        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m-d');
        $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-d');
        $gudangdepo=['Gd-03010100', 'Gd-03010101', 'Gd-05010100', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']; //  
        $obat=Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'bentuk_sediaan',
            'status_forkid',
            'status_fornas',
            'status_generik',
            'status_prb',
            'status_konsinyasi',
            'status_kronis',
            'kelompok_psikotropika',
            'kode108',
            )
        ->with([
            'kodebelanja:kode,uraian,uraianB',
            'saldoawal'=>function($sal) use($blnLaluAkhir,$gudangdepo){
                $sal->select(
                    'kdobat',
                    DB::raw('sum(jumlah) as jumlah')
                )
                ->where('tglopname','LIKE','%'.$blnLaluAkhir.'%')
                ->whereIn('kdruang',$gudangdepo)
                ->groupBy('kdobat');
            },
            'penerimaanrinci'=>function($ter){
                $ter->select(
                    'penerimaan_r.kdobat',
                    DB::raw('sum(penerimaan_r.jml_terima_k) as jumlah')
                )
                ->leftJoin('penerimaan_h','penerimaan_h.nopenerimaan','=','penerimaan_r.nopenerimaan')
                ->whereBetween('tglpenerimaan',[request('from').' 00:00:00',request('to').' 23:59:59'])
                ->groupBy('penerimaan_r.kdobat');
            },
            'mutasikeluar'=>function($mut){
                $mut->select(
                    'mutasi_gudangdepo.kd_obat',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah'),
                    DB::raw('sum(mutasi_gudangdepo.jml * mutasi_gudangdepo.harga) as subtotal'),
                    )
                ->leftJoin('permintaan_h','permintaan_h.no_permintaan','=','mutasi_gudangdepo.no_permintaan')
                ->whereBetween('permintaan_h.tgl_kirim_depo',[request('from').' 00:00:00',request('to').' 23:59:59'])
                ->where('permintaan_h.dari','LIKE','%R-%');
            },
            
            'resepkeluar'=>function($kel){
                $kel->select(
                    'resep_keluar_r.kdobat',
                    'resep_keluar_h.sistembayar',
                    DB::raw('sum(resep_keluar_r.jumlah) as jumlah'),
                    DB::raw('sum(resep_keluar_r.jumlah * resep_keluar_r.harga_jual) as subtotal'),
                )
                ->leftJoin('resep_keluar_h','resep_keluar_h.noresep','=','resep_keluar_r.noresep')
                ->whereBetween('resep_keluar_h.tgl_selesai',[request('from').' 00:00:00',request('to').' 23:59:59'])
                ->groupBy('resep_keluar_h.sistembayar','resep_keluar_r.kdobat');
                
            },
            
            'resepkeluarracikan'=>function($kel){
                $kel->select(
                    'resep_keluar_racikan_r.kdobat',
                    'resep_keluar_h.sistembayar',
                    DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah'),
                    DB::raw('sum(resep_keluar_racikan_r.jumlah * resep_keluar_racikan_r.harga_jual) as subtotal'),
                )
                ->leftJoin('resep_keluar_h','resep_keluar_h.noresep','=','resep_keluar_racikan_r.noresep')
                ->whereBetween('resep_keluar_h.tgl_selesai',[request('from').' 00:00:00',request('to').' 23:59:59'])
                ->groupBy('resep_keluar_h.sistembayar','resep_keluar_racikan_r.kdobat');                
            }, 
            'returpenjualan'=>function($kel){
                $kel->select(
                    'retur_penjualan_r.kdobat',
                    'resep_keluar_h.sistembayar',
                    DB::raw('sum(retur_penjualan_r.jumlah_keluar) as jumlah'),
                    DB::raw('sum(retur_penjualan_r.jumlah_keluar * retur_penjualan_r.harga_jual) as subtotal'),
                )
                ->leftJoin('retur_penjualan_h','retur_penjualan_h.noretur','=','retur_penjualan_r.noretur')
                ->leftJoin('resep_keluar_h','resep_keluar_h.noresep','=','retur_penjualan_h.noresep')
                ->whereBetween('retur_penjualan_h.tgl_retur',[request('from').' 00:00:00',request('to').' 23:59:59'])
                ->groupBy('resep_keluar_h.sistembayar','retur_penjualan_r.kdobat');                
            }
        ])
        ->where('nama_obat','LIKE','%'.request('q').'%')
        ->orWhere('kd_obat','LIKE','%'.request('q').'%')
        ->paginate(request('per_page'));
        $obat->append('harga');
        $data=collect($obat)['data'];
        $meta=collect($obat)->except('data');
        return new JsonResponse([
            'data'=>$data,
            'meta'=>$meta,
            'req'=>request()->all(),
            'blnLaluAkhir'=>$blnLaluAkhir
        ]);
    }
    public function getSistemBayar(){
        $data=SistemBayar::get();
        return new JsonResponse($data);
    }
}
