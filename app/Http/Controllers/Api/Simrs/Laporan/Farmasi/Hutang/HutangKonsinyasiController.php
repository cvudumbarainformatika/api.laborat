<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Hutang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastKonsinyasi;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HutangKonsinyasiController extends Controller
{
    //
    public function getHutangKonsinyasi(){
        // hutang konsinyasi adalah barang konsinyasi yang sudah dipakai
        // ambil pihak 3 yang konsinyasi
        $pbf=PenerimaanHeder::select('kdpbf')->where('jenis_penerimaan','Konsinyasi')->distinct()->pluck('kdpbf');
        // ambil pihak 3
        $data=Mpihakketiga::whereIn('kode',$pbf)
        ->paginate(request('per_page'));
        // ambil master barang konsinyasi
        $master=Mobatnew::select('kd_obat')->where('status_konsinyasi','1')->pluck('kd_obat');
        // ambil jumlah distribusi dan kembali
        $dist=PersiapanOperasiDistribusi::select(
            'persiapan_operasi_distribusis.nopermintaan',
            'persiapan_operasi_distribusis.nopenerimaan',
            'persiapan_operasi_distribusis.kd_obat',
            'penerimaan_r.harga_netto_kecil',
            DB::raw('sum(persiapan_operasi_distribusis.jumlah) as jumlah'),
            DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as jumlah_retur'),
            DB::raw('sum(persiapan_operasi_distribusis.jumlah - persiapan_operasi_distribusis.jumlah_retur) as dipakai'),
            DB::raw('sum((persiapan_operasi_distribusis.jumlah - persiapan_operasi_distribusis.jumlah_retur) * penerimaan_r.harga_netto_kecil) as sub'),
        )
        ->leftJoin('penerimaan_r',function($jo){
            $jo->on('penerimaan_r.nopenerimaan','=','persiapan_operasi_distribusis.nopenerimaan')
            ->on('penerimaan_r.kdobat','=','persiapan_operasi_distribusis.kd_obat');
        })
        ->whereIn('persiapan_operasi_distribusis.kd_obat',$master)
        ->havingRaw('dipakai > 0')
        ->groupBy('nopenerimaan', 'kd_obat',)
        ->get();
        // cari bast untuk cari jumlah dimintakan faktur dan bast
        $kdp = collect($data->items())->pluck('kode');
        $bastkonsi=BastKonsinyasi::with('rinci')->whereIn('kdpbf',$kdp)->get();

        // get jumlah penerimaan
        $nopen=$dist->pluck('nopenerimaan');
        $trm=PenerimaanRinci::select(
            'penerimaan_h.kdpbf',
            'penerimaan_r.nopenerimaan',
            DB::raw('sum(penerimaan_r.subtotal) as total')
        )
        ->leftJoin('penerimaan_h','penerimaan_h.nopenerimaan','=','penerimaan_r.nopenerimaan')
        ->whereIn('penerimaan_r.nopenerimaan',$nopen)
        ->groupBy('penerimaan_r.nopenerimaan')
        ->get();

        $datanya=collect($data)['data'];
        $meta=collect($data)->except('data');
        return new JsonResponse([
            'raw'=>$data,
            'data'=>$datanya,
            'meta'=>$meta,
            'bastkonsi'=>$bastkonsi,
            'dist'=>$dist,
            'trm'=>$trm,
            'req'=>request()->all(),
        ]);
    }
}
