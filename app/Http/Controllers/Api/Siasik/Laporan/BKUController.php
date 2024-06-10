<?php

namespace App\Http\Controllers\Api\Siasik\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Siasik\Master\Akun_Kepmendg50;
use App\Models\Siasik\Master\PejabatTeknis;
use App\Models\Siasik\TransaksiLS\Contrapost;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Siasik\TransaksiLS\NpdLS_rinci;
use App\Models\Siasik\TransaksiLS\NpkLS_heder;
use App\Models\Siasik\TransaksiLS\NpkLS_rinci;
use App\Models\Siasik\TransaksiPendapatan\DataSTS;
use App\Models\Siasik\TransaksiPendapatan\PendapatanLain;
use App\Models\Siasik\TransaksiPendapatan\PendapatanLainRinci;
use App\Models\Siasik\TransaksiPendapatan\TranskePPK;
use App\Models\Siasik\TransaksiPjr\CpPanjar_Header;
use App\Models\Siasik\TransaksiPjr\CpSisaPanjar_Header;
use App\Models\Siasik\TransaksiPjr\GeserKas_Header;
use App\Models\Siasik\TransaksiPjr\Nihil;
use App\Models\Siasik\TransaksiPjr\NpkPanjar_Header;
use App\Models\Siasik\TransaksiPjr\SpjPanjar_Header;
use App\Models\Siasik\TransaksiPjr\SPM_GU;
use App\Models\Siasik\TransaksiPjr\SpmUP;
use App\Models\Siasik\TransaksiSaldo\SaldoAwal_PPK;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;

class BKUController extends Controller
{
    public function ptk()
    {
        $thn= date('Y');
        $ptk = PejabatTeknis::where('tahun', $thn)->get();
        return new JsonResponse($ptk);
    }
    public function bkuppk()
    {

        $awal=request('tahun').'-'. request('bulan').'-01';
        $akhir=request('tahun').'-'. request('bulan').'-31';
        $saldo = SaldoAwal_PPK::where('rekening', '=', '0121161061')
        ->whereBetween('tanggal', [$awal, $akhir])
        ->get();
        $setor=TranskePPK::orderBy('tgltrans', 'asc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        // $sts = DataSTS::with(['tbp', 'pendpatanlain'=>function($rinci){
        //     $rinci -> with('plainlain',function($tgl){
        //         $tgl->orderBy('rs2','desc');
        //     });
        //  }])
        // ->whereBetween('tgl', [[$awal. ' 00:00:00', $akhir. ' 23:59:59']])
        // ->get();
        // $pendapatan = PendapatanLainRinci::select('rs260.*')
        // // ->join('rs258', 'rs258.rs1', '=', 'rs260.rs1', function($null) use ($awal, $akhir){
        // //     $null->whereNull('rs258.noSetor')
        // //     ->orderBy('rs258.rs2', 'desc')
        // //     ->whereBetween('rs258.rs2', [$awal. ' 00:00:00', $akhir. ' 23:59:59']);
        // // })
        // ->whereHas('pendapatanlain',function($null) use ($awal, $akhir){
        //     $null->whereNull('noSetor')
        //         ->orderBy('rs2','desc')
        //         ->whereBetween('rs2', [$awal. ' 00:00:00', $akhir. ' 23:59:59']);
        // })
        // ->with('pendapatanlain')
        // ->get();

        $npkls = NpkLS_heder::with(['npklsrinci'=> function($npk)
            {
                $npk->with(['npdlshead'=> function ($npdrinci){
                    $npdrinci->with(['npdlsrinci']);
                }]);
            }])
        ->whereBetween('tglpindahbuku', [$awal, $akhir])
        ->get();

        // $npklsa = NpkLS_heder::whereBetween('tglnpk', [$awal, $akhir])->pluck('nonpk');
        // $npkls = [];
        // if(count($npklsa)>0){
        //     $npkls = NpkLS_rinci::with(['npdlshead'=> function ($npdrinci){
        //         $npdrinci->with(['npdlsrinci']);
        //     },
        //     'header:nonpk,tglnpk'
        //     ])
        //     ->whereIn('nonpk', $npklsa)->get();
        // }


        $nihil = Nihil::select(
            'nopengembalian',
            'tgltrans',
            'jmlup',
            'jmlspj',
            'jmlcp',
            'jmlpengembalianup',
            'jmlsisaup',
            'jmlpengembalianreal',)
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();


        $spm = SpmUP::orderBy('tglSpm','desc')
        ->whereBetween('tglSpm', [$awal, $akhir])
        ->get();
        $spmgu = SPM_GU::orderBy('tglSpm','desc')
        ->whereBetween('tglSpm', [$awal, $akhir])
        ->get();

        $ppk = [
            'saldo' => $saldo,
            'setor' => $setor,
            // 'sts' => $sts,
            // 'pendapatan' => $pendapatan,
            'spm' => $spm,
            'spmgu' => $spmgu,
            'nihil' => $nihil,
            'npkls' => $npkls,
        ];

        return new JsonResponse($ppk);
    }
    public function bkupengeluaran()
    {
        $awal=request('tahun').'-'. request('bulan').'-01';
        $akhir=request('tahun').'-'. request('bulan').'-31';
        $npkls = NpkLS_heder::with(['npklsrinci'=> function($npk)
            {
                $npk->with(['npdlshead'=> function ($npdrinci){
                    $npdrinci->with(['npdlsrinci']);
                }]);
            }])
        ->whereBetween('tglnpk', [$awal, $akhir])
        ->get();
        $pencairanls = NpkLS_heder::with(['npklsrinci'=> function($npk)
            {
                $npk->with(['npdlshead'=> function ($npdrinci){
                    $npdrinci->with(['npdlsrinci']);
                }]);
            }])
        ->whereBetween('tglpencairan', [$awal, $akhir])
        ->get();

        $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        ->get();

        $spm = SpmUP::orderBy('tglSpm','desc')
        ->whereBetween('tglSpm', [$awal, $akhir])
        ->get();

        $spmgu = SPM_GU::orderBy('tglSpm','desc')
        ->whereBetween('tglSpm', [$awal, $akhir])
        ->get();

        $npkpanjar=NpkPanjar_Header::with(['npkrinci'=> function($npd){
            $npd->with(['npdpjr_rinci']);
        }])
        ->whereBetween('tglnpk', [$awal, $akhir])
        ->get();
        // $npkpanjar=NpkPanjar_Header::with(['npkrinci'=> function($npd){
        //     $npd->with(['npdpjr_head'=>function($npdrinci){
        //         $npdrinci->with(['npdpjr_rinci']);
        //     }]);
        // }])
        // ->whereBetween('tglnpk', [$awal, $akhir])
        // ->get();

        $spjpanjar=SpjPanjar_Header::with(['spj_rinci'])
        ->whereBetween('tglspjpanjar', [$awal, $akhir])
        ->get();

        $pengembalianpjr=CpPanjar_Header::with(['cppjr_rinci'])
        ->whereBetween('tglpengembalianpanjar', [$awal, $akhir])
        ->get();

        $cpsisapjr=CpSisaPanjar_Header::with(['sisarinci'])
        ->whereBetween('tglpengembaliansisapanjar', [$awal, $akhir])
        ->get();


        $pergeserankas = GeserKas_Header::with(['kasrinci'])
        // $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        $nihil = Nihil::select(
            'nopengembalian',
            'tgltrans',
            'jmlup',
            'jmlspj',
            'jmlcp',
            'jmlpengembalianup',
            'jmlsisaup',
            'jmlpengembalianreal',)
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        $bkupengeluaran = [
            'npkls' => $npkls,
            'pencairanls' => $pencairanls,
            'cp' => $cp,
            'spm' => $spm,
            'spmgu' => $spmgu,
            'npkpanjar' => $npkpanjar,
            'spjpanjar' => $spjpanjar,
            'pengembalianpjr'=> $pengembalianpjr,
            'cpsisapjr' => $cpsisapjr,
            'pergeserankas' => $pergeserankas,
            'nihil' => $nihil,
        ];

        return new JsonResponse($bkupengeluaran);
    }

    public function bkuptk()
    {
        $awal=request('tahun').'-'. request('bulan').'-01';
        $akhir=request('tahun').'-'. request('bulan').'-31';

        // cari where ... relasi pertma kedua tidak tampil jika kosong
        $pencairanls = NpkLS_heder::select('npkls_heder.*')
            ->when(request('ptk'),function($anu)
        {
            // $anu->whereHas('npklsrinci.npdlshead',function($hed){
            //     $hed->where('kodepptk', request('ptk'));
            // });

            $anu->join('npkls_rinci', 'npkls_rinci.nonpk','=','npkls_heder.nonpk')
                ->join('npdls_heder', 'npkls_rinci.nonpdls','=','npdls_heder.nonpdls')
                ->where('npdls_heder.kodepptk', request('ptk'))
                ->groupBy('npkls_rinci.nonpk');
        })->with(['npklsrinci'=> function($npk)
                {
                    $npk->when(request('ptk'),function($anu){
                        // $anu->whereHas('npdlshead',function($hed){
                        //     $hed->where('kodepptk', request('ptk'));
                        // });
                        $anu->join('npdls_heder', 'npdls_heder.nonpdls', '=','npkls_rinci.nonpdls')
                            ->where('npdls_heder.kodepptk', request('ptk'))
                            ->groupBy('npdls_heder.nonpdls');
                    })->with(['npdlshead'=> function ($npdrinci){
                        $npdrinci->with(['npdlsrinci']);
                    }]);
                }])
            ->whereBetween('npkls_heder.tglnpk', [$awal, $akhir])
            ->get();

        // $pencairanls = NpkLS_heder::when(request('ptk'),function($ada){
        //     $ada->with('npklsrinci.npdlshead.npdlsrinci')->whereHas('npklsrinci',function($npk)
        //     {
        //         $npk
        //         ->whereHas('npdlshead',function($hed){
        //             $hed->where('kodepptk', request('ptk'));
        //         })
        //         ->with('npdlshead', function ($npdrinci){
        //             $npdrinci
        //             ->with(['npdlsrinci']);
        //         });
        //     });
        // })
        // ->when(!request('ptk'), function($xx){
        //     $xx->with('npklsrinci.npdlshead');
        // })
        // ->whereBetween('tglnpk', [$awal, $akhir])
        // ->get();


        $npkpanjar = NpkPanjar_Header::select('npkpanjar_heder.*')
        ->when(request('ptk'),function($anu){
            // $anu->whereHas('npkrinci.npdpjr_head',function($hed){
            //     $hed->where('kodepptk', request('ptk'));
            // });
            $anu->join('npkpanjar_rinci', 'npkpanjar_rinci.nonpk', '=', 'npkpanjar_heder.nonpk')
                ->join('npdpanjar_heder', 'npdpanjar_heder.nonpdpanjar', '=', 'npkpanjar_rinci.nonpd')
                ->where('npdpanjar_heder.kodepptk', request('ptk'))
                ->groupBy('npkpanjar_rinci.nonpk');
        })->with(['npkrinci'=> function($npk)
                {
                    $npk->when(request('ptk'),function($anu){
                        // $anu->whereHas('npdpjr_head',function($hed){
                        //     $hed->where('kodepptk', request('ptk'));
                        // });
                        $anu->join('npdpanjar_heder', 'npdpanjar_heder.nonpdpanjar', '=', 'npkpanjar_rinci.nonpd')
                            ->where('npdpanjar_heder.kodepptk', request('ptk'))
                            ->groupBy('npdpanjar_heder.nonpdpanjar');
                    })->with(['npdpjr_head'=> function ($npdrinci){
                        $npdrinci->with(['npdpjr_rinci']);
                    }]);
                }])
            ->whereBetween('npkpanjar_heder.tglnpk', [$awal, $akhir])
            ->get();

        $spjpanjar=SpjPanjar_Header::with(['spj_rinci'])
        ->whereBetween('tglspjpanjar', [$awal, $akhir])
        ->where('kodepptk', request('ptk'))
        ->get();

        $pengembalianpjr=CpPanjar_Header::with(['cppjr_rinci'])
        ->whereBetween('tglpengembalianpanjar', [$awal, $akhir])
        ->where('kodepptk', request('ptk'))
        ->get();

        $cpsisapjr=CpSisaPanjar_Header::with(['sisarinci'])
        ->whereBetween('tglpengembaliansisapanjar', [$awal, $akhir])
        ->where('kodepptk', request('ptk'))
        ->get();

        $bkuptk = [
            'pencairanls' => $pencairanls,
            'npkpanjar' => $npkpanjar,
            'spjpanjar' => $spjpanjar,
            'pengembalianpjr' => $pengembalianpjr,
            'cpsisapjr' => $cpsisapjr,
        ];
        return new JsonResponse($bkuptk);

    }

    public function bukubank()
    {
        $awal=request('tahun').'-'. request('bulan').'-01';
        $akhir=request('tahun').'-'. request('bulan').'-31';
        $pencairanls = NpkLS_heder::with(['npklsrinci'=> function($npk)
            {
                $npk->with(['npdlshead'=> function ($npdrinci){
                    $npdrinci->with(['npdlsrinci']);
                }]);
            }])
        ->whereBetween('tglnpk', [$awal, $akhir])
        ->get();

        $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        ->get();

        $spm = SpmUP::orderBy('tglSpm','desc')
        ->whereBetween('tglSpm', [$awal, $akhir])
        ->get();

        $bankkas='Bank Ke Kas';
        $bankkekas = GeserKas_Header::where('jenis', $bankkas)->with(['kasrinci'])
        // $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();


        $kasbank='Kas Ke Bank';
        $kaskebank = GeserKas_Header::where('jenis', $kasbank)->with(['kasrinci'])
        // $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        $spjpanjar=SpjPanjar_Header::with(['spj_rinci'])
        ->whereBetween('tglspjpanjar', [$awal, $akhir])
        ->get();

        $nihil = Nihil::select(
            'nopengembalian',
            'tgltrans',
            'jmlup',
            'jmlspj',
            'jmlcp',
            'jmlpengembalianup',
            'jmlsisaup',
            'jmlpengembalianreal',)
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        $spmgu = SPM_GU::orderBy('tglSpm','desc')
        ->whereBetween('tglSpm', [$awal, $akhir])
        ->get();

        $bukubank = [
            'pencairanls' => $pencairanls,
            'cp' => $cp,
            'spm' => $spm,
            'bankkekas' => $bankkekas,
            'kaskebank'=> $kaskebank,
            'spjpanjar' => $spjpanjar,
            'nihil' => $nihil,
            'spmgu' => $spmgu,
        ];
        return new JsonResponse($bukubank);

    }
    public function bukutunai()
    {
        $awal=request('tahun').'-'. request('bulan').'-01';
        $akhir=request('tahun').'-'. request('bulan').'-31';
        // $pergeserankas = GeserKas_Header::with(['kasrinci'])
        // // $cp = Contrapost::orderBy('tglcontrapost','desc')
        // ->whereBetween('tgltrans', [$awal, $akhir])
        // ->get();

        $bankkas='Bank Ke Kas';
        $bankkekas = GeserKas_Header::where('jenis', $bankkas)->with(['kasrinci'])
        // $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();


        $kasbank='Kas Ke Bank';
        $kaskebank = GeserKas_Header::where('jenis', $kasbank)->with(['kasrinci'])
        // $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        $npkpanjar=NpkPanjar_Header::with(['npkrinci'=> function($npd){
            $npd->with(['npdpjr_rinci']);
        }])
        ->whereBetween('tglnpk', [$awal, $akhir])
        ->get();

        $pengembalianpjr=CpPanjar_Header::with(['cppjr_rinci'])
        ->whereBetween('tglpengembalianpanjar', [$awal, $akhir])
        ->get();

        $cpsisapjr=CpSisaPanjar_Header::with(['sisarinci'])
        ->whereBetween('tglpengembaliansisapanjar', [$awal, $akhir])
        ->get();

        $pjr = 'PANJAR';
        $cp = Contrapost::where('jenisbelanja', $pjr)
        ->orderBy('tglcontrapost','desc')
        ->whereBetween('tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        ->get();

        $bukutunai = [
            // 'pergeserankas' => $pergeserankas,
            'bankkekas' => $bankkekas,
            'kaskebank' => $kaskebank,
            'npkpanjar' => $npkpanjar,
            'pengembalianpjr' => $pengembalianpjr,
            'cpsisapjr' => $cpsisapjr,
            'cp' => $cp,
        ];
        return new JsonResponse($bukutunai);
    }

    // coba appends
    public function kode(){
        $awal=request('tglmulai', '2024-03-01');
        $akhir=request('tglakhir', '2024-03-31');

        // $pencairanls = NpkLS_heder::whereHas('npklsrinci.npdlshead',function($hed)
        //     {
        //         $hed->where('kodepptk', request('ptk'));

        //     })->with('npklsrinci', function($npk)
        //         {
        //             $npk->whereHas('npdlshead',function($hed)
        //                 {
        //                     $hed->where('kodepptk', request('ptk'));

        //                 })->with('npdlshead', function ($npdrinci)
        //             {
        //             $npdrinci->with('npdlsrinci', function($rek)
        //                 {
        //                     $rek->orderBy('koderek50', 'asc');
        //                 });
        //             });
        //         })
        //     ->whereBetween('tglnpk', [$awal, $akhir])
        //     ->get();


        // $kodecoba = Akun50_2024::
        // whereHas('npdls_rinci.headerls',function ($cair){
        //     $cair->where('nopencairan', '!=', '');
        // })
        // ->with(['npdls_rinci' => function ($head){
        //     $head->whereHas('headerls',function ($cair){
        //         $cair->where('nopencairan', '!=', '');
        //     })->with('headerls',function($npk) {
        //         $npk->with('npkrinci',function($header) {
        //             $header->with('header',function($tgl){
        //                 $tgl->whereBetween('tglpencairan')->get();
        //             });
        //         });
        //     });
        // },
        // 'spjpanjar','cp'])
        // ->get();


        // $ls = Akun_Kepmendg50::with(['npdls_rinci'=> function($head)
        // {
        //     $head->whereHas('headerls',function($cair)
        //     {
        //         $cair->where('nopencairan', '!=', '');
        //     })->with(['headerls'=> function($where)
        //     {
        //         $where->where('kodebidang', request('kode')
        //     );
        //         // ->whereBetween('tglpencairan', [$awal, $akhir]);
        //     }]);
        // }])
        // ->where('kode1', '5')
        // // ->where('kode3', '02')
        // // ->limit(100)
        // ->get();

        // return ($kode);
        // $npd=NpdLS_rinci::with('cp')
        // ->where('nonpdls','00004/I/UMUM/NPD-LS/2022')
        // ->limit(50)
        // ->get();
        // return ($npd);
        $pencairanls = NpkLS_heder::when(request('kode'),function($anu)
        {
            $anu->whereHas('npklsrinci.npdlshead',function($hed){
                $hed->where('kodebidang', request('kode'));
            });
        })->when(request('keg'),function($anu){
            $anu->whereHas('npklsrinci.npdlshead',function($hed){
                $hed->where('kodebidang', request('kode'))
                ->where('kegiatanblud', request('keg'));
            });
        })->with(['npklsrinci'=> function($npk)
                {
                    $npk->when(request('kode'),function($anu){
                        $anu->whereHas('npdlshead',function($hed){
                            $hed->where('kodebidang', request('kode'));
                        });
                    })->when(request('keg'),function($anu){
                        $anu->whereHas('npdlshead',function($hed){
                            $hed->where('kodebidang', request('kode'))
                            ->where('kegiatanblud', request('keg'));
                        });
                    })
                    ->with(['npdlshead'=> function ($npdrinci){
                        $npdrinci->with('npdlsrinci',function($kode){
                            $kode->with('akun50');
                        });
                    }]);
                }])
            ->whereBetween('tglpencairan', [$awal, $akhir])
            ->get();

        return new JsonResponse($pencairanls);
    }

    public function coba(){
        // $coba = NpdLS_rinci::with(['akun'=> function($kode){
        //     $kode->where('kode1', '5')->get();
        // }]);
        // $x = NpdLS_rinci::where('koderek50');
        // $kode = Akun50_2024::with(['npdrinci'=>function($hed){
        //     $hed->where('koderek50');
        // }])
        // ->where('akun', '5')
        // // // ->where('kode4', '!=', '')
        // // // ->where('kode5', '!=', '')
        // // ->where('kode6', '!=', '')
        // ->limit(100)
        // ->get();

        $awal=request('tglmulai', '2024-03-01');
        $akhir=request('tglakhir', '2024-03-31');
        $setor=TranskePPK::orderBy('tgltrans', 'asc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        // $npd = NpdLS_rinci::with('akun50')
        // ->get();

        // $akun=Akun_Kepmendg50::all();
        // $filter = $akun->filter(function($kode){
        //     return $kode->kodeall == true;
        // });
        // $npd = NpdLS_rinci::where('koderek50')->with('akun', function($x) use ($filter){
        //     $x->where('kodeall', $filter);
        // })
        // ->get();

        return($setor);
    }

}
