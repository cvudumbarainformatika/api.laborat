<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
use App\Models\Siasik\Anggaran\Tampung_pendapatan;
use App\Models\Siasik\Akuntansi\Jurnal\JurnalUmum_Header;
use App\Models\Siasik\Master\Akun50_2024;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LpeController extends Controller
{
    public function get_lpe() {
        $thn=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        // $awal=request('tgl', 'Y'.'-'.'01-01');
        $awal=request('tgl', 'Y-m-d');
        $akhir=request('tglx', 'Y-m-d');
        $sebelum = date( 'Y-m-d', strtotime( $awal . ' -1 day' ) );
        $thnakhir=Carbon::createFromFormat('Y-m-d', request('tglx'))->format('Y');
        if($thn !== $thnakhir){
         return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        }

        $ekuitas = Akun50_2024::select(
            'akun50_2024.kodeall3',
            'akun50_2024.uraian',
        )
        ->with(['saldoawal'=>function($sa) use ($awal,$akhir){
            $sa->select(
                'saldoawal.tglentry as tanggal',
                'saldoawal.kodepsap13',
                DB::raw('ifnull(sum(saldoawal.kredit-saldoawal.debit),0) as saldo')
            ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
            ->groupBy( 'saldoawal.kodepsap13');
        },'jurnalotom' => function($x) use ($awal,$akhir){
            $x->select(
                'jurnal_postingotom.kode',
                DB::raw('ifnull(sum(jurnal_postingotom.kredit-jurnal_postingotom.debit),0) as totaljurnal'),
            )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.verif', '=', '1')
            ->where('jurnal_postingotom.uraian', 'LIKE', 'Ekuitas' . '%')
            ->groupBy( 'jurnal_postingotom.kode');
        },
        'penyesuaianx' =>  function($sel) use ($awal,$akhir){
            $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_rinci.kodepsap13',
                    'jurnalumum_heder.tanggal',
                    DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian')
                    )
            ->where('jurnalumum_heder.verif', '=', '1')
            ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
            ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
            ->where('jurnalumum_rinci.uraianpsap13', 'LIKE', 'Ekuitas' . '%')
            ->groupBy( 'jurnalumum_rinci.kodepsap13');
        }])
        ->where('akun50_2024.kodeall3', 'LIKE', '3.1.01' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '3.1.03' . '%')
        ->orderBy('akun50_2024.kodeall3', 'asc')
        ->get();

        $pagupendapatan = Tampung_pendapatan::where('tahun', $thn)
        ->select(
                'akun50_2024.kodeall3 as kode6',
                'akun50_2024.uraian',
                DB::raw('sum(t_tampung_pendapatan.pagu) as pagupendapatan'),
                )
        ->join('akun50_2024', 'akun50_2024.kodeall3', '=', DB::raw("CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2))"))
        ->groupBy('t_tampung_pendapatan.koderekeningblud')
        ->get();
        
        $pendapatan = Create_JurnalPosting::select(
            'jurnal_postingotom.tanggal',
             'jurnal_postingotom.kode as kode6',
            DB::raw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as realisasi')
        )
        ->with('penyesuaian',  function($sel) use ($awal,$akhir){
            $sel->leftJoin('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_rinci.kodepsap13',
                    'jurnalumum_heder.tanggal',
                    'jurnalumum_heder.nobukti',
                    DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian'))
            ->where('jurnalumum_heder.verif', '=', '1')
            ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
            ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '7.' . '%')
            ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
            ->groupBy( 'jurnalumum_heder.nobukti');
        })
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where('jurnal_postingotom.verif', '=', '1')
        ->where('jurnal_postingotom.kode', 'LIKE', '7.' . '%')
        // ->groupBy( 'kode6')
        ->get();

        $penyesuaianpendapatan = JurnalUmum_Header::where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        ->join('jurnalumum_rinci', 'jurnalumum_rinci.nobukti', 'jurnalumum_heder.nobukti')
        ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '7.' . '%')
        ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
        ->select('jurnalumum_heder.tanggal',
                'jurnalumum_heder.keterangan',
                'jurnalumum_heder.nobukti',
                'jurnalumum_rinci.nobukti',
                'jurnalumum_rinci.kodepsap13 as kode6',
                'jurnalumum_rinci.uraianpsap13 as uraian',
                DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as subtotal'),
                )
        
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnalumum_rinci.kodepsap13')
        ->groupBy( 'jurnalumum_rinci.kodepsap13')
        ->get();

        $beban = Create_JurnalPosting::select(
            'jurnal_postingotom.tanggal',
             'jurnal_postingotom.kode as kode6',
            DB::raw('sum(jurnal_postingotom.debit-jurnal_postingotom.kredit) as realisasi')
        )
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where('jurnal_postingotom.verif', '=', '1')
        ->where('jurnal_postingotom.kode', 'LIKE', '8.' . '%')
        ->with('penyesuaian',  function($sel) use ($awal,$akhir){
            $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_rinci.kodepsap13',
                    'jurnalumum_heder.tanggal',
                    DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian'))
            ->where('jurnalumum_heder.verif', '=', '1')
            ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
            ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '8.' . '%')
            ->where('jurnalumum_heder.keterangan', 'NOT LIKE','Reklas Pendapatan' . '%')
            ->groupBy( 'kodepsap13');
        })
        ->groupBy( 'kode6')
        ->get();

        $koreksi = Akun50_2024::select(
            'akun50_2024.kodeall3',
            'akun50_2024.uraian',
        )
        ->with(['saldoawal'=>function($sa) use ($awal,$akhir){
            $sa->select(
                'saldoawal.kodepsap13',
                DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo')
            ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
            ->groupBy( 'saldoawal.kodepsap13');
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
        ->where('akun50_2024.kodeall3', 'LIKE', '3.1.01.01.02.' . '%')
        ->orderBy('akun50_2024.kodeall3', 'asc')
        ->get();

        $data = [
            'ekuitas' => $ekuitas,
            'pagupendapatan' => $pagupendapatan,
            'pendapatan' => $pendapatan,
            'penyesuaianpendapatan' => $penyesuaianpendapatan,
            'beban' => $beban,
            'koreksi' => $koreksi
        ];
        return new JsonResponse ($data);
    }
}
