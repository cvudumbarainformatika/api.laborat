<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
use App\Models\Siasik\Akuntansi\Jurnal\JurnalUmum_Header;
use App\Models\Siasik\Anggaran\Tampung_pendapatan;
use App\Models\Siasik\Master\Akun50_2024;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LapOperasionalController extends Controller
{

    public function get_lo(){
        $thn=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        // $awal=request('tgl', 'Y'.'-'.'01-01');
        $awal=request('tgl', 'Y-m-d');
        $akhir=request('tglx', 'Y-m-d');
        $sebelum = date( 'Y-m-d', strtotime( $awal . ' -1 day' ) );
        $thnakhir=Carbon::createFromFormat('Y-m-d', request('tglx'))->format('Y');
        if($thn !== $thnakhir){
         return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        }
        $pagupendapatan = Tampung_pendapatan::where('tahun', $thn)
        ->select(
                DB::raw("CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2))"),
                'akun50_2024.kodeall3 as kode6',
                'akun50_2024.uraian',
                DB::raw('sum(t_tampung_pendapatan.pagu) as pagupendapatan'),
                DB::raw("SUBSTRING_INDEX(CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2)), '.', 1) as kode1"),
                DB::raw("SUBSTRING_INDEX(CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2)), '.', 2) as kode2"),
                DB::raw("SUBSTRING_INDEX(CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2)), '.', 3) as kode3"),
                DB::raw("SUBSTRING_INDEX(CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2)), '.', 4) as kode4"),
                DB::raw("SUBSTRING_INDEX(CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2)), '.', 5) as kode5"),
                DB::raw("(SELECT uraian FROM akun50_2024 
                        WHERE kodeall3 = SUBSTRING_INDEX(CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2)), '.', 1) 
                        LIMIT 1) as uraian1"),
                DB::raw("(SELECT uraian FROM akun50_2024 
                        WHERE kodeall3 = SUBSTRING_INDEX(CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2)), '.', 2) 
                        LIMIT 1) as uraian2"),
                DB::raw("(SELECT uraian FROM akun50_2024 
                        WHERE kodeall3 = SUBSTRING_INDEX(CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2)), '.', 3) 
                        LIMIT 1) as uraian3"),
                DB::raw("(SELECT uraian FROM akun50_2024 
                        WHERE kodeall3 = SUBSTRING_INDEX(CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2)), '.', 4) 
                        LIMIT 1) as uraian4"),
                DB::raw("(SELECT uraian FROM akun50_2024 
                        WHERE kodeall3 = SUBSTRING_INDEX(CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2)), '.', 5) 
                        LIMIT 1) as uraian5")
                )
        ->join('akun50_2024', 'akun50_2024.kodeall3', '=', DB::raw("CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2))"))
        ->groupBy('t_tampung_pendapatan.koderekeningblud')
        ->get();

        $pendapatan = Create_JurnalPosting::join('akun50_2024', 'akun50_2024.kodeall3', 'jurnal_postingotom.kode')
         ->select(
            'jurnal_postingotom.tanggal',
            'akun50_2024.kodeall3 as kode6',
            'akun50_2024.uraian',
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) as kode5'),
            DB::raw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as subtotal'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) LIMIT 1) as uraian1'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) LIMIT 1) as uraian2'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) LIMIT 1) as uraian3'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) LIMIT 1) as uraian4'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) LIMIT 1) as uraian5'))
      
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where('jurnal_postingotom.kode', 'LIKE', '7.' . '%')
        ->where('jurnal_postingotom.verif', '=', '1')
        ->groupBy( 'kode6')
        ->orderBy('kode6', 'asc')
        ->get();

        $penyesuaianpendapatan = JurnalUmum_Header::where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        ->join('jurnalumum_rinci', 'jurnalumum_rinci.nobukti', 'jurnalumum_heder.nobukti')
        ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '7.' . '%')
        ->select('jurnalumum_heder.tanggal',
                'jurnalumum_heder.nobukti',
                'jurnalumum_rinci.nobukti',
                'akun50_2024.kodeall3 as kode6',
                'akun50_2024.uraian',
                DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as subtotal'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 1) as kode1'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 2) as kode2'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) as kode3'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 4) as kode4'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 5) as kode5'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 1) LIMIT 1) as uraian1'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 2) LIMIT 1) as uraian2'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) LIMIT 1) as uraian3'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 4) LIMIT 1) as uraian4'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 5) LIMIT 1) as uraian5'))
       
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnalumum_rinci.kodepsap13')
        ->groupBy( 'jurnalumum_rinci.kodepsap13')
        ->get();


        $bebanotom = Create_JurnalPosting::
        join('akun50_2024', 'akun50_2024.kodeall3', 'jurnal_postingotom.kode')
        ->select(
            'akun50_2024.kodeall3 as kode6',
            'akun50_2024.uraian',
            'jurnal_postingotom.tanggal',
            // 'jurnal_postingotom.kode as kode6',
            // 'jurnal_postingotom.uraian',
            DB::raw('sum(jurnal_postingotom.debit-jurnal_postingotom.kredit) as subtotal'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) as kode5'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) LIMIT 1) as uraian1'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) LIMIT 1) as uraian2'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) LIMIT 1) as uraian3'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) LIMIT 1) as uraian4'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) LIMIT 1) as uraian5')
            )
    
        ->where('jurnal_postingotom.verif', '=', '1')
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where('jurnal_postingotom.kode', 'LIKE', '8.' . '%')
        ->groupBy( 'kode6')
        ->orderBy('kode6', 'asc')
        ->get();


        $penyesuaianbeban = JurnalUmum_Header::where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        ->join('jurnalumum_rinci', 'jurnalumum_rinci.nobukti', 'jurnalumum_heder.nobukti')
        ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '8.' . '%')
        ->select('jurnalumum_heder.tanggal',
                'jurnalumum_heder.nobukti',
                'jurnalumum_rinci.nobukti',
                'akun50_2024.kodeall3 as kode6',
                'akun50_2024.uraian',
                DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as subtotal'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 1) as kode1'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 2) as kode2'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) as kode3'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 4) as kode4'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 5) as kode5'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 1) LIMIT 1) as uraian1'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 2) LIMIT 1) as uraian2'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) LIMIT 1) as uraian3'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 4) LIMIT 1) as uraian4'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 5) LIMIT 1) as uraian5'))
      
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnalumum_rinci.kodepsap13')
        ->groupBy( 'jurnalumum_rinci.kodepsap13')
        ->get();

        $data = [
            'pagupendapatan' => $pagupendapatan,
            'pendapatan' => $pendapatan,
            'penyesuaianpendapatan' => $penyesuaianpendapatan,
            'beban' => $bebanotom,
            'penyesuaianbeban' => $penyesuaianbeban,
            
        ];
        return new JsonResponse ($data);
    }
}
