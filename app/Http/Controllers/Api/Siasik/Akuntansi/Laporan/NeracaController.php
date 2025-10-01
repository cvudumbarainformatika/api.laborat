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


        $aset = SaldoAwal::where('tahun', $thn)
        ->select(
            'saldoawal.kodepsap13 as kode',
            DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo'),
            'akun50_2024.kodeall3 as kode6',
            'akun50_2024.uraian',
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 5) as kode5'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 1) LIMIT 1) as uraian1'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 2) LIMIT 1) as uraian2'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 3) LIMIT 1) as uraian3'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 4) LIMIT 1) as uraian4'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 5) LIMIT 1) as uraian5'),
        )
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'saldoawal.kodepsap13')
        ->groupBy('saldoawal.kodepsap13')
        ->with(['jurnalotom' => function($x) use ($awal, $akhir)
            {
                $x->select(
                    'jurnal_postingotom.kode',
                    DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
                )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
                ->where('jurnal_postingotom.verif', '=', '1')
                ->groupBy( 'jurnal_postingotom.kode');
            }, 'penyesuaian' =>  function($sel) use ($awal, $akhir)
            {
                $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
                ->select('jurnalumum_rinci.kodepsap13',
                        'jurnalumum_heder.tanggal',
                        DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
                        )
                ->where('jurnalumum_heder.verif', '=', '1')
                ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
                ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
                ->groupBy( 'jurnalumum_rinci.kodepsap13');
            }
        ])
        ->where('saldoawal.kodepsap13', 'LIKE', '1.' . '%')
        ->get();

        $kewajiban = SaldoAwal::where('tahun', $thn)
        ->select(
            'saldoawal.kodepsap13 as kode',
            DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo'),
            'akun50_2024.kodeall3 as kode6',
            'akun50_2024.uraian',
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 5) as kode5'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 1) LIMIT 1) as uraian1'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 2) LIMIT 1) as uraian2'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 3) LIMIT 1) as uraian3'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 4) LIMIT 1) as uraian4'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 5) LIMIT 1) as uraian5'),
        )
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'saldoawal.kodepsap13')
        ->groupBy('saldoawal.kodepsap13')
        ->with(['jurnalotom' => function($x) use ($awal, $akhir)
            {
                $x->select(
                    'jurnal_postingotom.kode',
                    DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
                )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
                ->where('jurnal_postingotom.verif', '=', '1')
                ->groupBy( 'jurnal_postingotom.kode');
            }, 'penyesuaian' =>  function($sel) use ($awal, $akhir)
            {
                $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
                ->select('jurnalumum_rinci.kodepsap13',
                        'jurnalumum_heder.tanggal',
                        DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
                        )
                ->where('jurnalumum_heder.verif', '=', '1')
                ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
                ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
                ->groupBy( 'jurnalumum_rinci.kodepsap13');
            }
        ])
        ->where('saldoawal.kodepsap13', 'LIKE', '2.' . '%')
        ->get();

        $ekuitas = SaldoAwal::where('tahun', $thn)
        ->select(
            'saldoawal.kodepsap13 as kode',
            DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo'),
            'akun50_2024.kodeall3 as kode6',
            'akun50_2024.uraian',
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 5) as kode5'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 1) LIMIT 1) as uraian1'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 2) LIMIT 1) as uraian2'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 3) LIMIT 1) as uraian3'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 4) LIMIT 1) as uraian4'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 5) LIMIT 1) as uraian5'),
        )
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'saldoawal.kodepsap13')
        ->groupBy('saldoawal.kodepsap13')
        ->with(['jurnalotom' => function($x) use ($awal, $akhir)
            {
                $x->select(
                    'jurnal_postingotom.kode',
                    DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
                )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
                ->where('jurnal_postingotom.verif', '=', '1')
                ->groupBy( 'jurnal_postingotom.kode');
            }, 'penyesuaian' =>  function($sel) use ($awal, $akhir)
            {
                $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
                ->select('jurnalumum_rinci.kodepsap13',
                        'jurnalumum_heder.tanggal',
                        DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
                        )
                ->where('jurnalumum_heder.verif', '=', '1')
                ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
                ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
                ->groupBy( 'jurnalumum_rinci.kodepsap13');
            }
        ])
        ->where('saldoawal.kodepsap13', 'LIKE', '3.' . '%')
        ->get();
        $data = [
            'aset' => $aset,
            'kewajiban' => $kewajiban,
            'ekuitas' => $ekuitas,
        ];
        return new JsonResponse ($data);


    }
   

}
