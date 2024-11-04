<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
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
        $bebanotom = Create_JurnalPosting::select(
                'jurnal_postingotom.tanggal',
                'jurnal_postingotom.kode as kode6',
                'jurnal_postingotom.uraian',
                )->addSelect(
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
                    DB::raw('sum(jurnal_postingotom.debit-jurnal_postingotom.kredit) as subtotalx')
                )
            ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnal_postingotom.kode')
            ->with(['lvl1' => function($sel){
                    $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
                }, 'lvl2' => function($sel){
                    $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
                },'lvl3' => function($sel){
                    $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
                },'lvl4' => function($sel){
                    $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
                },'lvl5' => function($sel){
                    $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
            }])
            // ->where('jurnal_postingotom.verif', '=', '1')
            ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.kode', 'LIKE', '8.' . '%')
            // ->where('jurnal_postingotom.tanggal', '>=', $awal)
            // ->where('jurnal_postingotom.tanggal', '<', $akhir)
            ->groupBy( 'kode6')
            ->orderBy('kode6', 'asc')
            ->get();
        $pendapatan = Create_JurnalPosting::select(
                'jurnal_postingotom.tanggal',
                'jurnal_postingotom.kode as kode6',
                'jurnal_postingotom.uraian',
                // 'jurnalumum_heder.nobukti'
                // DB::raw('sum(jurnal_postingotom.debit) as subtotalx')
                )
                ->selectRaw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as subtotal')
                ->addSelect(
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5')
                )
            ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnal_postingotom.kode')
            ->with(['lvl1' => function($sel){
                    $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
                }, 'lvl2' => function($sel){
                    $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
                },'lvl3' => function($sel){
                    $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
                },'lvl4' => function($sel){
                    $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
                },'lvl5' => function($sel){
                    $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
            }])
            ->where('jurnal_postingotom.kode', 'LIKE', '7.' . '%')
            // ->where('jurnal_postingotom.verif', '=', '1')
            ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])

            ->with('penyesuaian',  function($sel) use ($awal,$akhir){
                $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
                ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
                ->select('jurnalumum_rinci.kodepsap13',
                        'jurnalumum_heder.tanggal',
                        DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian'))
                ->groupBy( 'kodepsap13');
            })

            ->groupBy( 'kode6')
            ->orderBy('kode6', 'asc')
            ->get();
        $data = [
            'beban' => $bebanotom,
            'pendapatan' => $pendapatan
        ];
        return new JsonResponse ($data);
    }
}
