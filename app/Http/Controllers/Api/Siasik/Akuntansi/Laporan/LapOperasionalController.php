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
            ->where('jurnal_postingotom.verif', '=', '1')
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
            ->where('jurnal_postingotom.verif', '=', '1')
            ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])

            ->with('penyesuaian',  function($sel) use ($awal,$akhir){
                $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
                ->where('jurnalumum_heder.verif', '=', '1')
                ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
                ->select('jurnalumum_rinci.kodepsap13',
                        'jurnalumum_heder.tanggal',
                        DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian'))
                ->groupBy( 'kodepsap13');
            })

            ->groupBy( 'kode6')
            ->orderBy('kode6', 'asc')
            ->get();

        $psaprealisasipendapatan = Create_JurnalPosting::select(
            'jurnal_postingotom.tanggal', 'jurnal_postingotom.kode',
            DB::raw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as realisasi')
        )
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where('jurnal_postingotom.verif', '=', '1')
        ->where('jurnal_postingotom.kode', 'LIKE', '4.1.04.16' . '%')
        ->get();

        $psaprealisasipendapatanx = JurnalUmum_Header::select(
            'jurnalumum_heder.nobukti',
            'jurnalumum_heder.tanggal',
            'jurnalumum_heder.keterangan',
            'jurnalumum_rinci.kodepsap13 as kode',
            'jurnalumum_rinci.uraianpsap13 as uraian',
            'jurnalumum_rinci.kredit as realisasix',
        )
        ->where('jurnalumum_heder.keterangan', 'LIKE', 'Reklas Pendapatan' . '%')
        ->where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        ->leftJoin('jurnalumum_rinci', function($join)  {
            $join->on('jurnalumum_rinci.nobukti', '=', 'jurnalumum_heder.nobukti')
            ->where('jurnalumum_rinci.kodepsap13', '!=', '4.1.04.16.02.0001')
            ;
          })
        ->get();

        $psappenyesuaianpendp = JurnalUmum_Header::select(
            'jurnalumum_rinci.uraianpsap13 as uraian',
            'jurnalumum_rinci.debet as nilaix',
        )
        ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
        ->where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        ->leftJoin('jurnalumum_rinci', function($join)  {
            $join->on('jurnalumum_rinci.nobukti', '=', 'jurnalumum_heder.nobukti')
            ->where('jurnalumum_rinci.kodepsap13', '=', '4.1.04.16.02.0001')
            ;
          })
        ->get();

        $psapbebanpegawai = Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // DB::raw('sum(t_tampung.pagu) as pagu'),
        DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 6) as kode'),
                    )
                    ->with('kode1',function($gg){
                        $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                        })
                        ->with('kode2',function($gg){
                            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                            })
                            ->with('kode3',function($gg){
                                $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                                })
        ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
            $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
            ->where('jurnal_postingotom.verif', '=', '1')
            ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.kode', 'LIKE', '8.1.01' . '%')
            ;
            })
        ->where('akun50_2024.kodeall3', 'LIKE', '8.1.01' . '%')
        ->groupBy('kode3')
        ->orderBy('kode', 'asc')
        ->get();

        $psapbebanlain = Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // DB::raw('sum(t_tampung.pagu) as pagu'),
        DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 6) as kode'),
                    )
                    ->with('kode1',function($gg){
                        $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                        })
                        ->with('kode2',function($gg){
                            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                            })
                            ->with('kode3',function($gg){
                                $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                                })
        ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
            $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
            ->where('jurnal_postingotom.verif', '=', '1')
            ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.kode', 'LIKE', '8.1' . '%')
            ;
            })
        ->where('akun50_2024.kodeall3', 'LIKE', '8.1.02' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '8.1.07' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '8.1.08' . '%')
        ->where('akun50_2024.kodeall3', 'NOT LIKE', '8.1.01')
        ->groupBy('kode4')
        ->orderBy('kode', 'asc')
        ->get();

        $psappenjualanaset = Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // DB::raw('sum(t_tampung.pagu) as pagu'),
        DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 6) as kode'),
                    )
                    ->with('kode1',function($gg){
                        $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                        })
                        ->with('kode2',function($gg){
                            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                            })
                            ->with('kode3',function($gg){
                                $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                                })
        ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
            $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
            ->where('jurnal_postingotom.verif', '=', '1')
            ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.kode', 'LIKE', '7.4' . '%');
            })
        ->where('akun50_2024.kodeall3', 'LIKE', '7.4.01.01' . '%')
        ->groupBy('kode4')
        ->orderBy('kode', 'asc')
        ->get();


        $psapkerugian = Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // DB::raw('sum(t_tampung.pagu) as pagu'),
        DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 6) as kode'),
                    )
                    ->with('kode1',function($gg){
                        $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                        })
                        ->with('kode2',function($gg){
                            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                            })
                            ->with('kode3',function($gg){
                                $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                                })
        ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
            $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
            ->where('jurnal_postingotom.verif', '=', '1')
            ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.kode', 'LIKE', '8.3' . '%');
            })
        ->where('akun50_2024.kodeall3', 'LIKE', '8.3.01.01' . '%')
        ->groupBy('kode4')
        ->orderBy('kode', 'asc')
        ->get();


        $psapnonoperasional= Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // DB::raw('sum(t_tampung.pagu) as pagu'),
        DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 6) as kode'),
                    )
                    ->with('kode1',function($gg){
                        $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                        })
                        ->with('kode2',function($gg){
                            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                            })
                            ->with('kode3',function($gg){
                                $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                                })
        ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
            $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
            ->where('jurnal_postingotom.verif', '=', '1')
            ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.kode', 'LIKE', '7.5' . '%');
            })
        ->where('akun50_2024.kodeall3', 'LIKE', '7.4.03.01' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '8.3.03.01' . '%')
        // // ->orWhere('akun50_2024.kodeall3', 'LIKE', '8.3' . '%')
        // ->where('akun50_2024.kodeall3', '!=', '8.3.03.02')
        // ->orWhere('akun50_2024.kodeall3', '!=', '7.4.01.01')
        ->groupBy('kode4')
        ->orderBy('kode', 'asc')
        ->get();

        $psappendapatanluarbiasa= Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // DB::raw('sum(t_tampung.pagu) as pagu'),
        DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 6) as kode'),
                    )
                    ->with('kode1',function($gg){
                        $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                        })
                        ->with('kode2',function($gg){
                            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                            })
                            ->with('kode3',function($gg){
                                $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                                })
        ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
            $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
            ->where('jurnal_postingotom.verif', '=', '1')
            ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.kode', 'LIKE', '7.5' . '%');
            })
        ->where('akun50_2024.kodeall3', 'LIKE', '7.5.01.01' . '%')
        ->groupBy('kode4')
        ->orderBy('kode', 'asc')
        ->get();

        $psapbebanluarbiasa= Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // DB::raw('sum(t_tampung.pagu) as pagu'),
        DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 6) as kode'),
                    )
                    ->with('kode1',function($gg){
                        $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                        })
                        ->with('kode2',function($gg){
                            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                            })
                            ->with('kode3',function($gg){
                                $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                                })
        ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
            $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
            ->where('jurnal_postingotom.verif', '=', '1')
            ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.kode', 'LIKE', '8.4' . '%');
            })
        ->where('akun50_2024.kodeall3', 'LIKE', '8.4.01.01' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '8.4.01.02' . '%')
        ->groupBy('kode4')
        ->orderBy('kode', 'asc')
        ->get();

        $data = [
            'beban' => $bebanotom,
            'pendapatan' => $pendapatan,
            'psaprealisasipendapatan' => $psaprealisasipendapatan,
            'psaprealisasipendapatanx' => $psaprealisasipendapatanx,
            'psappenyesuaianpendp' => $psappenyesuaianpendp,
            'psapbebanpegawai' => $psapbebanpegawai,
            'psapbebanlain' => $psapbebanlain,
            'psappenjualanaset' => $psappenjualanaset,
            'psapkerugian' => $psapkerugian,
            'psapnonoperasional' => $psapnonoperasional,
            'psappendapatanluarbiasa' => $psappendapatanluarbiasa,
            'psapbebanluarbiasa' => $psapbebanluarbiasa
        ];
        return new JsonResponse ($data);
    }
}
