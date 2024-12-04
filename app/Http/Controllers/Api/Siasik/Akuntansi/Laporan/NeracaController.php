<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
use App\Models\Siasik\Akuntansi\SaldoAwal;
use App\Models\Siasik\Master\Akun50_2024;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NeracaController extends Controller
{
    public function getNeraca (){
        $thn=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        $awal=request('tgl', 'Y-m-d');
        $akhir=request('tglx', 'Y-m-d');
        $sebelum = date( 'Y-m-d', strtotime( $awal . ' -1 day' ) );
        $thnakhir=Carbon::createFromFormat('Y-m-d', request('tglx'))->format('Y');
        if($thn !== $thnakhir){
         return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        }

        $kaspengeluaran = Akun50_2024::select(
            'akun50_2024.kodeall3',
            'akun50_2024.uraian'
        )
        // ->join('saldoawal', 'saldoawal.kodepsap13', 'jurnal_postingotom.kode')
        // ->where('jurnal_postingotom.verif', '=', '1')
        ->with(['saldoawal'=>function($sa) use ($awal,$akhir){
            $sa->select(
                'saldoawal.tglentry as tanggal',
                'saldoawal.kodepsap13',
                DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo')
            ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59']);
        },'jurnalotom' => function($x) use ($awal,$akhir){
            $x->select(
                'jurnal_postingotom.kode',
                DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
            )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.verif', '=', '1')
            ->groupBy( 'jurnal_postingotom.kode');
        },
        'penyesuaianx' =>  function($sel) use ($awal,$akhir){
            $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_rinci.kodepsap13',
                    'jurnalumum_heder.tanggal',
                    DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
                    )
            ->where('jurnalumum_heder.verif', '=', '1')
            ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
            ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
            ->groupBy( 'jurnalumum_rinci.kodepsap13');
        }])

        ->where('akun50_2024.kodeall3', '=', '1.1.01.03.01.0001')
        // ->groupBy('kode6', 'kodepsap13')
        ->get();

        $kasblud = SaldoAwal::select(
            'saldoawal.tglentry as tanggal',
            'saldoawal.kodepsap13 as kode',
            // DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi'),
            DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo')
        )
        // ->join('saldoawal', 'saldoawal.kodepsap13', 'jurnal_postingotom.kode')
        // ->where('jurnal_postingotom.verif', '=', '1')
        ->with(['jurnalotom' => function($x) use ($awal,$akhir){
            $x->select(
                'jurnal_postingotom.kode',
                DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
            )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.verif', '=', '1')
            ->groupBy( 'jurnal_postingotom.kode');
        },
        'penyesuaian' =>  function($sel) use ($awal,$akhir){
            $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_rinci.kodepsap13',
                    'jurnalumum_heder.tanggal',
                    DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
                    )
            ->where('jurnalumum_heder.verif', '=', '1')
            ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
            ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
            ->groupBy( 'jurnalumum_rinci.kodepsap13');
        }])
        ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        ->where('saldoawal.kodepsap13', 'LIKE', '1.1.01.04' . '%')
        // ->groupBy('kode6', 'kodepsap13')
        ->get();

        $data = [
            'kaspengeluaran' => $kaspengeluaran,
            'kasblud' => $kasblud,
            // 'asetx' => $asetx,
        ];
        return new JsonResponse ($data);
    }
}
