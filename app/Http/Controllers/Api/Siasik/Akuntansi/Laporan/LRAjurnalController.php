<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Anggaran\Tampung_pendapatan;
use App\Models\Siasik\TransaksiSilpa\SisaAnggaran;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LRAjurnalController extends Controller
{
    public function get_lra () {
        $thnpagu=date('Y');
        $thn=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        $awal=request('tgl', 'Y-m-d');
        $akhir=request('tglx', 'Y-m-d');
        $sebelum = date( 'Y-m-d', strtotime( $awal . ' -1 day' ) );
        $thnakhir=Carbon::createFromFormat('Y-m-d', request('tglx'))->format('Y');
        if($thn !== $thnakhir){
         return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        }

        $pagu = PergeseranPaguRinci::select(
            't_tampung.koderek50',
            't_tampung.uraian50 as uraian',
            't_tampung.tgl as tanggal',
            'akun50_2024.kodeall3 as kode6',
            DB::raw('sum(t_tampung.pagu) as pagu')
            // 't_tampung_pendapatan.pagu as pagupendapatan'
            )
            // ->selectRaw('sum(t_tampung.pagu) as pagu')
            ->addSelect(
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5')
            )
        ->join('akun50_2024', 'akun50_2024.kodeall2', 't_tampung.koderek50')
        // ->join('t_tampung_pendapatan', 't_tampung_pendapatan.tahun', 't_tampung.tgl')
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
        ->where('t_tampung.pagu', '!=', 0)
        ->where('t_tampung.tgl',  $thn)
        ->groupBy('t_tampung.koderek50')

        ->get();
        $pagupendapatan = Tampung_pendapatan::where('tahun', $thn)
        ->select('t_tampung_pendapatan.pagu as pagupendapatan')
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
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where('jurnal_postingotom.kode', 'LIKE', '4.' . '%')
        // ->where('jurnal_postingotom.verif', '=', '1')
        // ->whereDate('tanggal', '>=', $awal)
        // ->whereDate('tanggal', '<=', $akhir)
        ->with('penyesuaian',  function($sel) use ($awal,$akhir){
            $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_rinci.kodepsap13',
                    'jurnalumum_heder.tanggal',
                    DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian'))
            // ->where('jurnalumum_heder.verif', '=', '1')
            ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
            ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '4.' . '%')
            ->groupBy( 'kodepsap13');
        })
        ->groupBy( 'kode6')
        ->orderBy('kode6', 'asc')
        ->get();

        $pendapatansblm = Create_JurnalPosting::select(
            'jurnal_postingotom.tanggal',
            'jurnal_postingotom.kode as kode6',
            // 'jurnalumum_heder.nobukti'
            DB::raw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as pendpsebelumnya')
            )
        ->where('jurnal_postingotom.kode', 'LIKE', '4.' . '%')
        // ->where('jurnal_postingotom.verif', '=', '1')
        ->whereDate('tanggal', '<', $awal)
        ->with('penyesuaian',  function($sel) use ($awal,$akhir){
            $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_heder.tanggal',
                    DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian'))
            // ->where('jurnalumum_heder.verif', '=', '1')
            ->whereDate('jurnalumum_heder.tanggal', '<', $awal)
            ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '4.' . '%')
            ->groupBy( 'kodepsap13');
        })
        ->groupBy( 'kode6')
        ->orderBy('kode6', 'asc')
        ->get();

        $belanja = Create_JurnalPosting::select(
            'jurnal_postingotom.tanggal',
            'jurnal_postingotom.kode as kode6',
            'jurnal_postingotom.uraian',
            DB::raw('sum(jurnal_postingotom.debit-jurnal_postingotom.kredit) as subtotalx')
            )->addSelect(
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
        // ->where('jurnal_postingotom.verif', '=', '1')
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where('jurnal_postingotom.kode', 'LIKE', '5.' . '%')
        // ->where('jurnal_postingotom.tanggal', '>=', $awal)
        // ->where('jurnal_postingotom.tanggal', '<', $akhir)
        ->groupBy( 'kode6')
        ->orderBy('kode6', 'asc')
        ->get();
        $belanjasblm = Create_JurnalPosting::select(
            'jurnal_postingotom.tanggal',
            'jurnal_postingotom.kode as kode6',
            'jurnal_postingotom.uraian',
            DB::raw('sum(jurnal_postingotom.debit-jurnal_postingotom.kredit) as nilaisebelumnya')
            )->addSelect(
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
        // ->where('jurnal_postingotom.verif', '=', '1')
        ->whereDate('jurnal_postingotom.tanggal', '<', $awal)
        ->where('jurnal_postingotom.kode', 'LIKE', '5.' . '%')
        // ->where('jurnal_postingotom.tanggal', '>=', $awal)
        // ->where('jurnal_postingotom.tanggal', '<', $akhir)
        ->groupBy( 'kode6')
        ->orderBy('kode6', 'asc')
        ->get();

        $silpapagu = SisaAnggaran::where('tahun', $thn)
        ->select('silpa.koderek50 as kode6',
                'silpa.uraian50 as uraian',
                'silpa.tanggal',
                DB::raw('sum(silpa.nominal) as pagu'))
            ->addSelect(
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5')
            )
            ->join('akun50_2024', 'akun50_2024.kodeall3', 'silpa.koderek50')
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
        ->groupBy('silpa.koderek50')
        ->get();

        $silpasblm = SisaAnggaran::where('tahun', $thn)
        ->select('silpa.koderek50 as kode6',
                'silpa.uraian50 as uraian',
                'silpa.tanggal',
                DB::raw('sum(silpa.nominal) as nilaisblm'))
        ->whereDate('tanggal', '<', $awal)
        ->groupBy('silpa.koderek50')
        ->get();

        $silpaskg = SisaAnggaran::where('tahun', $thn)
        ->select('silpa.koderek50 as kode6',
                'silpa.uraian50 as uraian',
                'silpa.tanggal',
                DB::raw('sum(silpa.nominal) as nilaiskg'))
        ->whereBetween('silpa.tanggal', [$awal, $akhir])
        ->groupBy('silpa.koderek50')
        ->get();

    $data = [
        'pagu' => $pagu,
        'pagupendapatan' => $pagupendapatan,
        'pendapatan' => $pendapatan,
        'pendapatansblm' => $pendapatansblm,
        'belanja' => $belanja,
        'belanjasblm' => $belanjasblm,
        'pagusilpa' => $silpapagu,
        'silpasblm' => $silpasblm,
        'silpaskg' => $silpaskg
    ];
    return new JsonResponse ($data);
    }
}
