<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokrealController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_h;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_r;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturpenjualanController extends Controller
{
    public function caribynoresep()
    {

        // if (request('to') === '' || request('from') === null) {
        //     $tgl = Carbon::now()->format('Y-m-d 00:00:00');
        //     $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        // } else {
        //     $tgl = request('from') . ' 00:00:00';
        //     $tglx = request('to') . ' 23:59:59';
        // }
        $rm = [];
        if (request('q') !== null) {
            if (preg_match('~[0-9]+~', request('q'))) {
                $rm = [];
            } else {
                if (strlen(request('q')) >= 3) {
                    $data = Mpasien::select('rs1 as norm')->where('rs2', 'LIKE', '%' . request('q') . '%')->get();
                    $rm = collect($data)->map(function ($x) {
                        return $x->norm;
                    });
                } else $rm = [];
            }
        }

        // cari nama obat->cari noresep yang ada obat itu di rincian dan rincian racik
        $nama = [];
        $noresep = [];
        if (request('nama') !== null) {
            if (strlen(request('nama')) >= 3) {
                $raw = Mobatnew::select('kd_obat')->where('nama_obat', 'LIKE', '%' . request('nama') . '%')->get('kd_obat');
                if (count($raw) > 0) {
                    $col = collect($raw);
                    $nama = $col->map(function ($it) {
                        return $it->kd_obat;
                    });
                }
                $resRinc = Resepkeluarrinci::select('noresep')->whereIn('kdobat', $nama)->distinct()->get();
                // $resRac = Resepkeluarrinciracikan::select('noresep')->whereIn('kdobat', $nama)->distinct()->get();
                foreach ($resRinc as $key) {
                    $noresep[] = $key['noresep'];
                }
                // foreach ($resRac as $key) {
                //     $noresep[] = $key['noresep'];
                // }
                array_unique($noresep);
            }
        }
        $carinoresep = Resepkeluarheder::select('resep_keluar_h.*', 'resep_keluar_h.dokter as kddokter')
            ->with(
                [
                    'rincian.mobat:kd_obat,nama_obat,satuan_k,kandungan,status_generik,status_forkid,status_fornas,kode108,uraian108,kode50,uraian50',
                    'rincianracik.mobat:kd_obat,nama_obat,satuan_k,kandungan,status_generik,status_forkid,status_fornas,kode108,uraian108,kode50,uraian50',
                    'datapasien:rs1,rs2,rs3,rs17,rs16,rs46,rs49',
                    'dokter:kdpegsimrs,nama',
                    'ruanganranap:rs1,rs2',
                    'poli:rs1,rs2',
                    'sistembayar:rs1,rs2',
                    'rincianwret' => function ($ri) {
                        $ri->select(
                            'resep_keluar_r.noresep',
                            'resep_keluar_r.kdobat',
                            'retur_penjualan_r.jumlah_retur',
                        )
                            ->leftjoin('retur_penjualan_r', function ($j) {
                                $j->on('retur_penjualan_r.noresep', '=', 'resep_keluar_r.noresep')
                                    ->on('retur_penjualan_r.kdobat', '=', 'resep_keluar_r.kdobat');
                            });
                    },
                    'rincianracikwret' => function ($ri) {
                        $ri->select(
                            'resep_keluar_racikan_r.noresep',
                            'resep_keluar_racikan_r.kdobat',
                            'retur_penjualan_r.jumlah_retur',
                        )
                            ->leftjoin('retur_penjualan_r', function ($j) {
                                $j->on('retur_penjualan_r.noresep', '=', 'resep_keluar_racikan_r.noresep')
                                    ->on('retur_penjualan_r.kdobat', '=', 'resep_keluar_racikan_r.kdobat');
                            });
                    }
                ]
            )
            // ->where(function ($query) {
            //     $query->where('noresep', 'like', '%' . request('q') . '%')
            //         ->orWhere('norm', 'LIKE', '%' . request('q') . '%');
            // })
            ->where(function ($query) use ($rm) {
                $query->when(count($rm) > 0, function ($wew) use ($rm) {
                    $wew->whereIn('norm', $rm);
                })
                    ->orWhere('noresep', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('norm', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('noreg', 'LIKE', '%' . request('q') . '%');
            })
            ->where('depo', request('kddepo'))
            ->when(request('from'), function ($q) {
                $tgl = request('from') . ' 00:00:00';
                $tglx = Carbon::now()->format('Y-m-d 23:59:59');
                $q->whereBetween('tgl_permintaan', [$tgl, $tglx]);
            })
            ->when(request('flag'), function ($x) {
                $x->whereIn('flag', request('flag'));
            })
            ->when(count($noresep) > 0, function ($q) use ($noresep) {
                $q->whereIn('noresep', $noresep);
            })


            ->orderBy('tgl_permintaan', 'ASC')
            ->paginate(request('per_page'));
        return new JsonResponse(
            [
                'result' => $carinoresep,
                'nama' => $nama,
                'rm' => $rm,
                'noresep' => $noresep,
            ]
        );
    }

    public function returpenjualan(Request $request)
    {
        $data = $request->all();
        return new JsonResponse($data);

        if ($request->noretur == '' || $request->noretur == null) {
            DB::connection('farmasi')->select('call returpenjualan');
            $x = DB::connection('farmasi')->table('conter')->select('returpenjualan')->get();
            $wew = $x[0]->returpenjualan;
            $noretur = FormatingHelper::penerimaanobat($wew, '-RET-PEN');
        } else {
            $noretur = $request->noretur;
        }

        $user = FormatingHelper::session_user();
        $simpanheder = Returpenjualan_h::firstorcreate(
            [
                'noretur' => $noretur
            ],
            [
                'tgl_retur' => $noretur,
                'noresep' => $request->noresep,
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'kddokter' => $request->kddokter,
                'kdruangan' => $request->kdruangan,
                'user' => $user['kodesimrs']
            ]
        );
        if (!$simpanheder) {
            return new JsonResponse(['message' => 'Maaf Data Gagal Disimpan'], 500);
        }

        $simpanrinci = Returpenjualan_r::create(
            [
                'noretur' => $noretur,
                'noreg' => $request->noreg,
                'kdobat' => $request->kdobat,
                'kandungan' => $request->kandungan,
                'fornas' => $request->fornas,
                'forkit' => $request->forkit,
                'generik' => $request->generik,
                'kode108' => $request->kode108,
                'uraian108' => $request->uraian108,
                'kode50' => $request->kode50,
                'uraian50' => $request->uraian50,
                'nopenerimaan' => $request->nopenerimaan,
                'jumlah_keluar' => $request->jumlah_keluar,
                'jumlah_retur' => $request->jumlah_retur,
                'user' => $user['kodesimrs']
            ]
        );
        $updatestok = StokrealController::updatestokdepo($request);
        return new JsonResponse(
            [
                'message' => 'Data Berhasil Disimpan...!!!',
                'heder' => $simpanheder,
                'rinci' => $simpanrinci->load('mobatnew'),
            ],
            200
        );
    }
    public function newreturpenjualan(Request $request)
    {

        $user = FormatingHelper::session_user();
        $req = $request->all();
        $tmpRin = collect($req['listObat'])->sum('jumlah_retur');
        // $tmpRac = collect($req['rincianracik'])->sum('jumlah_retur');
        // $tmpRinMap = $tmpRin->sum('jumlah_retur');
        // cek bahwa ada yang diretur
        if ($tmpRin <= 0) {
            return new JsonResponse(['message' => 'Tidak ada Jumlah Obat yang akan diretur'], 410);
        }

        try {

            DB::connection('farmasi')->beginTransaction();
            if ($request->noretur == '' || $request->noretur == null) {
                DB::connection('farmasi')->select('call returpenjualan(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('returpenjualan')->first();
                $wew = $x->returpenjualan;
                $noretur = FormatingHelper::penerimaanobat($wew, 'RET-PEN');
            } else {
                $noretur = $request->noretur;
            }
            $noret = ['noretur' => $noretur];

            $isiHead = [
                'tgl_retur' => date('Y-m-d H:i:s'),
                'noresep' => $request->noresep,
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'kddokter' => $request->kddokter,
                'kdruangan' => $request->ruangan,
                'user' => $user['kodesimrs']
            ];

            $rinci = [];
            $racik = [];

            if (count($request->listObat)) {
                foreach ($request->listObat as $key) {
                    if ($key['jumlah_retur'] > 0) {
                        $obats = Resepkeluarrinci::where('noreg', $request->noreg)
                            ->where('noresep', $request->noresep)
                            ->where('kdobat', $key['kdobat'])
                            ->orderBy('id', 'DESC')
                            ->get();

                        $jum = $key['jumlah_retur'];
                        $index = 0;


                        while ($jum > 0) {
                            $ada = (int)$obats[$index]->jumlah;
                            if ($ada < $jum) {
                                $temp = [
                                    'noretur' => $noretur,
                                    'noreg' => $request->noreg,
                                    'noresep' => $request->noresep,
                                    'kdobat' => $key['kdobat'],
                                    'kandungan' => $key['mobat']['kandungan'] ?? '',
                                    'fornas' => $key['mobat']['status_generik'] ?? '',
                                    'forkit' => $key['mobat']['status_forkid'] ?? '',
                                    'generik' => $key['mobat']['status_fornas'] ?? '',
                                    'kode108' => $key['mobat']['kode108'],
                                    'uraian108' => $key['mobat']['uraian108'],
                                    'kode50' => $key['mobat']['kode50'],
                                    'uraian50' => $key['mobat']['uraian50'],
                                    'nopenerimaan' => $obats[$index]->nopenerimaan,
                                    'jumlah_keluar' => $ada,
                                    'jumlah_retur' => $ada,
                                    'harga_beli' => $key['harga_beli'],
                                    'hpp' => $key['hpp'],
                                    'harga_jual' => $key['harga_jual'],
                                    'nilai_r' => $key['nilai_r'],
                                    'user' => $user['kodesimrs'],
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];

                                $sisa = $jum - $ada;
                                $index += 1;
                                $jum = $sisa;
                                array_push($rinci, $temp);
                            } else {
                                $temp = [
                                    'noretur' => $noretur,
                                    'noreg' => $request->noreg,
                                    'noresep' => $request->noresep,
                                    'kdobat' => $key['kdobat'],
                                    'kandungan' => $key['mobat']['kandungan'] ?? '',
                                    'fornas' => $key['mobat']['status_generik'] ?? '',
                                    'forkit' => $key['mobat']['status_forkid'] ?? '',
                                    'generik' => $key['mobat']['status_fornas'] ?? '',
                                    'kode108' => $key['mobat']['kode108'],
                                    'uraian108' => $key['mobat']['uraian108'],
                                    'kode50' => $key['mobat']['kode50'],
                                    'uraian50' => $key['mobat']['uraian50'],
                                    'nopenerimaan' => $obats[$index]->nopenerimaan,
                                    'jumlah_keluar' => $ada,
                                    'jumlah_retur' => $jum,
                                    'harga_beli' => $key['harga_beli'],
                                    'hpp' => $key['hpp'],
                                    'harga_jual' => $key['harga_jual'],
                                    'nilai_r' => $key['nilai_r'],
                                    'user' => $user['kodesimrs'],
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                                $jum = 0;
                                array_push($rinci, $temp);
                            }
                        }
                    }
                }
            }
            // if (count($request->rincianracik)) {
            //     foreach ($request->rincianracik as $key) {
            //         if ($key['jumlah_retur'] > 0) {
            //             $jum = $key['jumlah_retur'];
            //             $index = 0;

            //             $obats = Resepkeluarrinciracikan::where('noreg', $request->noreg)
            //                 ->where('noresep', $request->noresep)
            //                 ->where('kdobat', $key['kdobat'])
            //                 ->orderBy('id', 'DESC')
            //                 ->get();

            //             while ($jum > 0) {
            //                 $ada = (int)$obats[$index]->jumlah;
            //                 if ($ada < $jum) {
            //                     $temp = [
            //                         'noretur' => $noretur,
            //                         'noreg' => $request->noreg,
            //                         'noresep' => $request->noresep,
            //                         'kdobat' => $key['kdobat'],
            //                         'kandungan' => $key['mobat']['kandungan'],
            //                         'fornas' => $key['mobat']['status_generik'],
            //                         'forkit' => $key['mobat']['status_forkid'],
            //                         'generik' => $key['mobat']['status_fornas'],
            //                         'kode108' => $key['mobat']['kode108'],
            //                         'uraian108' => $key['mobat']['uraian108'],
            //                         'kode50' => $key['mobat']['kode50'],
            //                         'uraian50' => $key['mobat']['uraian50'],
            //                         'nopenerimaan' => $obats[$index]->nopenerimaan,
            //                         'jumlah_keluar' => $ada,
            //                         'jumlah_retur' => $ada,
            //                         'harga_beli' => $key['harga_beli'],
            //                         'hpp' => $key['hpp'],
            //                         'harga_jual' => $key['harga_jual'],
            //                         'nilai_r' => $key['nilai_r'],
            //                         'user' => $user['kodesimrs'],
            //                         'created_at' => date('Y-m-d H:i:s'),
            //                         'updated_at' => date('Y-m-d H:i:s'),
            //                     ];

            //                     $sisa = $jum - $ada;
            //                     $index += 1;
            //                     $jum = $sisa;
            //                     array_push($racik, $temp);
            //                 } else {
            //                     $temp = [
            //                         'noretur' => $noretur,
            //                         'noreg' => $request->noreg,
            //                         'noresep' => $request->noresep,
            //                         'kdobat' => $key['kdobat'],
            //                         'kandungan' => $key['mobat']['kandungan'],
            //                         'fornas' => $key['mobat']['status_generik'],
            //                         'forkit' => $key['mobat']['status_forkid'],
            //                         'generik' => $key['mobat']['status_fornas'],
            //                         'kode108' => $key['mobat']['kode108'],
            //                         'uraian108' => $key['mobat']['uraian108'],
            //                         'kode50' => $key['mobat']['kode50'],
            //                         'uraian50' => $key['mobat']['uraian50'],
            //                         'nopenerimaan' => $obats[$index]->nopenerimaan,
            //                         'jumlah_keluar' => $ada,
            //                         'jumlah_retur' => $jum,
            //                         'harga_beli' => $key['harga_beli'],
            //                         'hpp' => $key['hpp'],
            //                         'harga_jual' => $key['harga_jual'],
            //                         'nilai_r' => $key['nilai_r'],
            //                         'user' => $user['kodesimrs'],
            //                         'created_at' => date('Y-m-d H:i:s'),
            //                         'updated_at' => date('Y-m-d H:i:s'),
            //                     ];
            //                     $jum = 0;
            //                     array_push($racik, $temp);
            //                 }
            //             }
            //         }
            //     }
            // }

            $simpanHeader = Returpenjualan_h::firstOrCreate($noret, $isiHead);
            if (!$simpanHeader) {
                return new JsonResponse(['message' => 'Header Data Gagal Disimpan'], 410);
            }
            if (count($rinci)) {
                Returpenjualan_r::insert($rinci);
            }
            // if (count($racik)) {
            //     Returpenjualan_r::insert($racik);
            // }
            $permintaanHead = Resepkeluarheder::where('noresep', $request->noresep)->where('noreg', $request->noreg)->first();
            if ($permintaanHead) {
                // $permintaanHead->flag = '4';
                // $permintaanHead->save();
                $permintaanHead->update(['flag' => '4']);
            }

            $simpanHeader->load('rinci');
            $returRinci = Returpenjualan_r::where('noretur', $simpanHeader->noretur)->get();
            // foreach ($returRinci as $key) {
            //     $data = (object) [];
            //     $data->koderuang = $request->depo;
            //     $data->kdobat = $key['kdobat'];
            //     $data->noresep = $simpanHeader->noresep;

            //     $updatestok = StokrealController::newupdatestokdepo($data);
            // }
            foreach ($returRinci as $key) {
                $stok = Stokreal::where('nopenerimaan', $key['nopenerimaan'])
                    ->where('kdobat', $key['kdobat'])
                    ->where('kdruang', $request->depo)
                    // ->where('jumlah', '>', 0)
                    // ->latest()
                    ->orderBy('tglexp', 'DESC')
                    ->first();
                if (!$stok) {
                    return new JsonResponse([
                        'message' => 'Stok tidak ditemukan',
                        'data' => $stok,
                        'key' => $key,


                    ], 410);
                }
                $jumlah = (int) $stok->jumlah + (int)$key['jumlah_retur'];
                $stok->update(['jumlah' => $jumlah]);
            }

            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'retur disimpan',
                'data' => $simpanHeader,
                'retur rinci' => $returRinci
            ]);
        } catch (\Exception $e) {
            // May day,  rollback!!! rollback!!!
            DB::connection('farmasi')->rollback();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan...!!!',
                'result' => ' ' . $e,
                'err' => $e
            ], 410);
        }
    }
}
