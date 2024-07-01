<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokrealController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistribusigudangController extends Controller
{
    public function listpermintaandepo()
    {
        $gudang = request('kdgudang');
        $nopermintaan = request('no_permintaan');
        $flag = request('flag');
        $depo = request('kddepo');

        // return new JsonResponse(['fl' => $flag, 'type' => $type]);
        $listpermintaandepo = Permintaandepoheder::with([
            'permintaanrinci.masterobat',
            'user:id,nip,nama',
            'permintaanrinci' => function ($rinci) {
                $rinci->with([
                    'stokreal' => function ($stokdendiri) {
                        $stokdendiri
                            ->select(
                                'kdobat',
                                'kdruang',
                                'jumlah',
                            );
                    }
                ]);
            },
            'mutasigudangkedepo',
            'asal:kode,nama',
            'menuju:kode,nama',
        ])
            ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
            ->where('flag', '!=', '')
            ->when($gudang, function ($wew) use ($gudang) {
                $wew->where('tujuan', $gudang);
            })
            ->when($flag, function ($wew) use ($flag) {
                $type = gettype($flag);
                if ($type === 'array') {
                    $wew->whereIn('flag', $flag);
                } else {
                    $wew->where('flag', $flag);
                }
            })
            ->when($depo, function ($wew) use ($depo) {
                $wew->where('dari', $depo);
            })
            ->orderBY('tgl_permintaan', 'desc')
            ->paginate(request('per_page'));
        return new JsonResponse($listpermintaandepo);
    }
    public function listPermintaanRuangan()
    {
        $gudang = request('kdgudang');
        $nopermintaan = request('no_permintaan');
        $flag = request('flag');
        $depo = request('kddepo');
        $listpermintaandepo = Permintaandepoheder::with([
            'permintaanrinci.masterobat',
            'user:id,nip,nama',
            'permintaanrinci' => function ($rinci) {
                $rinci->with([
                    'stokreal' => function ($stokdendiri) {
                        $stokdendiri
                            ->select(
                                'kdobat',
                                'kdruang',
                                'jumlah',
                            );
                    }
                ]);
            },
            'mutasigudangkedepo',
            'ruangan:kode,uraian',
        ])
            ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
            ->where('flag', '!=', '')
            ->when($gudang, function ($wew) use ($gudang) {
                $wew->where('tujuan', $gudang);
            })
            ->when($flag, function ($wew) use ($flag) {
                $wew->where('flag', $flag);
            })
            ->when($depo, function ($wew) use ($depo) {
                $wew->where('dari', $depo);
            })
            ->when(!$depo, function ($wew) {
                $wew->where('dari', 'LIKE', '%' . 'R-' . '%');
            })
            ->orderBY('tgl_permintaan', 'desc')
            ->paginate(request('per_page'));
        return new JsonResponse($listpermintaandepo);
    }

    // public function verifpermintaanobat(Request $request)
    // {
    //     if ($request->jumlah_diverif > $request->jumlah_minta) {
    //         return new JsonResponse(['message' => 'Maaf Jumlah Yang Diminta Tidak Sebanyak Itu....']);
    //     }
    //     $verifobat = Permintaandeporinci::where('id', $request->id)->update(
    //         [
    //             'jumlah_diverif' => $request->jumlah_diverif,
    //             'tgl_verif' => date('Y-m-d H:i:s'),
    //             'user_verif' => auth()->user()->pegawai_id
    //         ]
    //     );
    //     if (!$verifobat) {
    //         return new JsonResponse(['message' => 'Maaf Anda Gagal Memverif,Mohon Periksa Kembali Data Anda...!!!'], 500);
    //     }
    //     return new JsonResponse(['message' => 'Permintaan Obat Behasil Diverif...!!!'], 200);
    // }

    // public function rencanadistribusikedepo()
    // {
    //     $jenisdistribusi = request('jenisdistribusi');
    //     $gudang = request('kdgudang');
    //     $listrencanadistribusi = Permintaandeporinci::with(
    //         [
    //             'permintaanobatheder' => function ($permintaanobatheder) use ($gudang) {
    //                 $permintaanobatheder->when($gudang, function ($xxx) use ($gudang) {
    //                     $xxx->where('tujuan', $gudang)->where('flag', '1');
    //                 });
    //             },
    //             'masterobat'
    //         ]
    //     )->where('flag_distribusi', '')
    //         ->where('user_verif', '!=', '')
    //         ->when($jenisdistribusi, function ($wew) use ($jenisdistribusi) {
    //             $wew->where('status_obat', $jenisdistribusi);
    //         })
    //         ->paginate(request('per_page'));
    //     return new JsonResponse($listrencanadistribusi);
    // }



    public function simpandistribusidepo(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $jmldiminta = $request->jumlah_minta;
            $caristok = Stokreal::select('*', DB::raw('sum(jumlah) as total'))
                ->where('kdobat', $request->kodeobat)->where('kdruang', $request->kdgudang)
                ->where('jumlah', '>', 0)
                ->groupBy('nopenerimaan', 'kdobat', 'kdruang', 'nobatch')
                ->orderBy('tglexp', 'ASC')
                ->get();
            // $caristok = Stokreal::where('kdobat', $request->kodeobat)->where('kdruang', $request->kdgudang)
            //     ->where('jumlah', '>', 0)
            //     ->orderBy('tglexp', 'ASC')
            //     ->get();
            // $sisaStok = collect($caristok)->sum('jumlah');
            // $stok = $caristok[0]->jumlah;
            // $sisa = $stok - $jmldiminta;
            if (count($caristok) <= 0) {
                return new JsonResponse(['message' => 'Stok Tidak ditemukan, apakah stok sudah habis?'], 410);
            }
            $index = 0;
            $masuk = $jmldiminta;
            while ($masuk > 0) {
                $sisa = $caristok[$index]->total;
                if ($sisa < $masuk) {
                    $sisax = $masuk - $sisa;
                    $hargaBeli = PenerimaanRinci::select('harga_netto_kecil')
                        ->where('nopenerimaan', $caristok[$index]->nopenerimaan)
                        ->where('kdobat', $caristok[$index]->kdobat)
                        ->where('no_batch', $caristok[$index]->nobatch)
                        ->first();
                    $mutasi = Mutasigudangkedepo::updateOrCreate(
                        [
                            'no_permintaan' => $request->nopermintaan,
                            'nopenerimaan' => $caristok[$index]->nopenerimaan,
                            'kd_obat' => $caristok[$index]->kdobat,
                            'nobatch' => $caristok[$index]->nobatch,
                        ],
                        [
                            'jml' => $sisa,
                            'tglpenerimaan' => $caristok[$index]->tglpenerimaan,
                            'harga' => $hargaBeli->harga_netto_kecil ?? 0,
                            'tglexp' => $caristok[$index]->tglexp,
                        ]
                    );
                    // $dataStok = Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                    //     ->where('kdobat', $caristok[$index]->kdobat)
                    //     ->where('kdruang', $request->kdgudang)
                    //     ->get();
                    // foreach ($dataStok as $key) {
                    //     // $key->jumlah = 0;
                    //     // $key->save();
                    //     $key->update(['jumlah' => 0]);
                    // }
                    $dataStok =  Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                        ->where('kdobat', $caristok[$index]->kdobat)
                        ->where('kdruang', $request->kdgudang)
                        ->where('nobatch', $caristok[$index]->nobatch)
                        ->get();
                    foreach ($dataStok as $st) {
                        $st->update(['jumlah' => 0]);
                    }


                    $masuk = $sisax;
                    $index = $index + 1;
                    //return $jmldiminta;
                } else {
                    $sisax = $sisa - $masuk;
                    $hargaBeli = PenerimaanRinci::select('harga_netto_kecil')
                        ->where('nopenerimaan', $caristok[$index]->nopenerimaan)
                        ->where('kdobat', $caristok[$index]->kdobat)
                        ->where('no_batch', $caristok[$index]->nobatch)
                        ->first();
                    $mutasi = Mutasigudangkedepo::updateOrCreate(
                        [
                            'no_permintaan' => $request->nopermintaan,
                            'nopenerimaan' => $caristok[$index]->nopenerimaan,
                            'kd_obat' => $caristok[$index]->kdobat,
                            'nobatch' => $caristok[$index]->nobatch,
                        ],
                        [
                            'jml' => $masuk,
                            'tglpenerimaan' => $caristok[$index]->tglpenerimaan,
                            'harga' => $hargaBeli->harga_netto_kecil ?? 0,
                            'tglexp' => $caristok[$index]->tglexp,
                        ]
                    );
                    // // nol kan semua
                    // $getStok = Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                    //     ->where('kdobat', $caristok[$index]->kdobat)
                    //     // ->where('nodistribusi', $caristok[$index]->nodistribusi)
                    //     ->where('kdruang', $request->kdgudang)
                    //     ->get();
                    // foreach ($getStok as $key) {
                    //     // $key->jumlah = 0;
                    //     // $key->save();
                    //     $key->update(['jumlah' => 0]);
                    // }
                    // // ambil data dengan nopenerimaan yang sama di yang terakhir
                    // $data = Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                    //     ->where('kdobat', $caristok[$index]->kdobat)
                    //     // ->where('nodistribusi', $caristok[$index]->nodistribusi)
                    //     ->where('kdruang', $request->kdgudang)
                    //     ->latest()
                    //     ->first();
                    // // tumpuk di situ stok nya
                    // // $data->jumlah = $sisax;
                    // // $data->save();
                    // $data->update(['jumlah' => $sisax]);

                    // ambil data dengan nopenerimaan yang sama di yang terakhir
                    // $data = Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                    //     ->where('kdobat', $caristok[$index]->kdobat)
                    //     ->where('kdruang', $request->kdgudang)
                    //     ->where('nobatch', $caristok[$index]->nobatch)
                    //     ->when($caristok[$index]->nodistribusi === null || $caristok[$index]->nodistribusi === '', function ($q) use ($caristok, $index) {
                    //         $q->where('nodistribusi', '');
                    //     })
                    //     ->when($caristok[$index]->nodistribusi, function ($q) use ($caristok, $index) {
                    //         $q->where('nodistribusi', $caristok[$index]->nodistribusi);
                    //     })
                    //     ->latest()
                    //     ->first();
                    // ambil data dengan nopenerimaan yang sama di yang terakhir
                    $data = Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                        ->where('kdobat', $caristok[$index]->kdobat)
                        ->where('kdruang', $request->kdgudang)
                        ->where('nobatch', $caristok[$index]->nobatch)
                        ->oldest()
                        ->get();
                    //jumlah datanya ada berapa
                    $jumlah = count($data);
                    // nolkan nomor distribusi yang lain
                    foreach ($data as $st) {
                        $st->update(['jumlah' => 0]);
                    }
                    // tumpuk di index terakhir stok nya
                    $data[$jumlah - 1]->update(['jumlah' => $sisax]);

                    $masuk = 0;
                }
            }
            $user = FormatingHelper::session_user();
            $rinciPer = Permintaandeporinci::where('no_permintaan', $request->nopermintaan)
                ->where('kdobat', $request->kodeobat)
                ->first();
            if ($rinciPer) {
                $rinciPer->update(
                    [
                        'jumlah_diverif' => $request->jumlah_minta,
                        'user_verif' => $user['kodesimrs'],
                        'tgl_verif' => date('Y-m-d H:i:s'),
                    ]
                );
            }
            $msg = [
                'data' => [
                    'aksi' => 'distribusi',
                    'dari' =>  $request->dari,
                    'no_permintaan' => $request->nopermintaan,
                    'kdobat' => $request->kodeobat,
                    'depo' =>  $request->dari,
                    'jml' => $jmldiminta,
                    // 'flag' => $simpanpermintaandepo->flag
                ]
            ];
            event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));
            DB::connection('farmasi')->commit();
            $nyamuta = Mutasigudangkedepo::select('kd_obat', DB::raw('sum(jml) as jml'))->where('no_permintaan', $request->nopermintaan)
                ->where('kd_obat', $request->kodeobat)
                ->first();
            // [
            //     'no_permintaan' => $request->nopermintaan,
            //     'nopenerimaan' => $caristok[$index]->nopenerimaan,
            //     'kd_obat' => $caristok[$index]->kdobat,
            //     'nobatch' => $caristok[$index]->nobatch,

            //     'jml' => $masuk,
            //     'tglpenerimaan' => $caristok[$index]->tglpenerimaan,
            //     'harga' => $hargaBeli->harga_netto_kecil ?? 0,
            //     'tglexp' => $caristok[$index]->tglexp,
            // ]);
            return new JsonResponse([
                'message' => 'Data Berhasil Disimpan',
                'data' => $nyamuta,
                'jumlah' => $jumlah ?? 'none'
            ], 200);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan ',
                'result' => '' . $e,
                'err' =>  $e,
                'caristok' => $caristok ?? '',
                'mutasi' => $mutasi ?? '',
                'stoknya' => $data ?? '',
                'rinciPer' => $rinciPer ?? '',
            ], 410);
        }
    }

    public function kuncipermintaandaridepo(Request $request)
    {
        $user = FormatingHelper::session_user();
        $kuncipermintaan = Permintaandepoheder::where('no_permintaan', $request->no_permintaan)->first();
        $kuncipermintaan->flag = '2';
        $kuncipermintaan->tgl_terima = date('Y-m-d H:i:s');
        $kuncipermintaan->user_terima = $user['kodesimrs'];
        $kuncipermintaan->save();

        $kdobat = Permintaandeporinci::select('kdobat')->where('no_permintaan', $request->no_permintaan)->get();
        $msg = [
            'data' => [
                'aksi' => 'kunci',
                'dari' => $kuncipermintaan->dari,
                'no_permintaan' => $kuncipermintaan->no_permintaan,
                'depo' => $kuncipermintaan->dari,
                'flag' => $kuncipermintaan->flag,
                'kodeobats' => $kdobat,

            ]
        ];
        event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));

        return new JsonResponse(['message' => 'Permintaan Berhasil Diterima...!!!'], 200);
    }
    public function tolakpermintaandaridepo(Request $request)
    {
        $user = FormatingHelper::session_user();
        $kuncipermintaan = Permintaandepoheder::where('no_permintaan', $request->no_permintaan)->first();
        $kuncipermintaan->flag = '5';
        // $kuncipermintaan->tgl_terima = date('Y-m-d H:i:s');
        // $kuncipermintaan->user_terima = $user['kodesimrs'];
        $kuncipermintaan->save();

        $kdobat = Permintaandeporinci::select('kdobat')->where('no_permintaan', $request->no_permintaan)->get();
        $msg = [
            'data' => [
                'aksi' => 'kunci',
                'dari' => $kuncipermintaan->dari,
                'no_permintaan' => $kuncipermintaan->no_permintaan,
                'depo' => $kuncipermintaan->dari,
                'flag' => $kuncipermintaan->flag,
                'kodeobats' => $kdobat,

            ]
        ];
        event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));
        return new JsonResponse(['message' => 'Permintaan Ditolak '], 200);
    }

    public function distribusikan(Request $request)
    {
        $user = FormatingHelper::session_user();
        $kuncipermintaan = Permintaandepoheder::where('no_permintaan', $request->no_permintaan)->first();
        $kuncipermintaan->flag = '3';
        $kuncipermintaan->tgl_kirim_depo = date('Y-m-d H:i:s');
        $kuncipermintaan->user_kirim_depo = $user['kodesimrs'];
        $kuncipermintaan->save();

        $kdobat = Permintaandeporinci::select('kdobat')->where('no_permintaan', $request->no_permintaan)->get();
        $msg = [
            'data' => [
                'aksi' => 'kunci',
                'dari' => $kuncipermintaan->dari,
                'no_permintaan' => $kuncipermintaan->no_permintaan,
                'depo' => $kuncipermintaan->dari,
                'flag' => $kuncipermintaan->flag,
                'kodeobats' => $kdobat,

            ]
        ];
        event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));

        return new JsonResponse(['message' => 'Permintaan Berhasil Didistribusikan...!!!'], 200);
    }
}
