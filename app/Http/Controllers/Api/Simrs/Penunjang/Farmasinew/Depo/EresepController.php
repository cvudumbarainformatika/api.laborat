<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Helpers\HargaHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EresepController extends Controller
{

    public function conterracikan()
    {
        $conter = Permintaanresepracikan::where('noresep', request('noresep'))
            ->groupby('noresep', 'namaracikan')
            // ->count(); kalo count ada 2 nama racikan terhitung 1
            ->get();

        /*  mencari nilai max racikan jika lebih dari satu, dan agar jika ada yang di hapus racikannya tapi tidak
            dihapus semua, maka nomornya bisa lanjut
        */
        $col = collect($conter)->map(function ($c) {
            $temp = explode(' ', $c->namaracikan);
            return  (int)$temp[1];
        })->max();
        $num = 0;
        if (count($conter)) {
            $num = $col;
        }
        // return new JsonResponse($num);
        $conterx =  $num + 1;
        $contery = 'Racikan ' . $conterx;
        return new JsonResponse($contery);
    }

    public function lihatstokobateresepBydokter()
    {
        // penccarian termasuk tiperesep
        $groupsistembayar = request('groups');
        if ($groupsistembayar == '1') {
            $sistembayar = ['SEMUA', 'BPJS'];
        } else {
            $sistembayar = ['SEMUA', 'UMUM'];
        }
        $cariobat = Stokreal::select(
            'stokreal.kdobat as kdobat',
            'stokreal.kdruang as kdruang',
            'stokreal.tglexp',
            'new_masterobat.nama_obat as namaobat',
            'new_masterobat.kandungan as kandungan',
            'new_masterobat.bentuk_sediaan as bentuk_sediaan',
            'new_masterobat.satuan_k as satuankecil',
            'new_masterobat.status_fornas as fornas',
            'new_masterobat.status_forkid as forkit',
            'new_masterobat.status_generik as generik',
            'new_masterobat.status_kronis as kronis',
            'new_masterobat.status_prb as prb',
            'new_masterobat.kode108',
            'new_masterobat.uraian108',
            'new_masterobat.kode50',
            'new_masterobat.uraian50',
            'new_masterobat.kekuatan_dosis as kekuatandosis',
            'new_masterobat.volumesediaan as volumesediaan',
            DB::raw('sum(stokreal.jumlah) as total')
        )
            ->with(
                [
                    'minmax',
                    'transnonracikan' => function ($transnonracikan) {
                        $transnonracikan->select(
                            // 'resep_keluar_r.kdobat as kdobat',
                            'resep_permintaan_keluar.kdobat as kdobat',
                            'resep_keluar_h.depo as kdruang',
                            DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                        )
                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                            ->where('resep_keluar_h.depo', request('kdruang'))
                            ->whereIn('flag', ['', '1', '2'])
                            ->groupBy('resep_permintaan_keluar.kdobat');
                    },
                    'transracikan' => function ($transracikan) {
                        $transracikan->select(
                            // 'resep_keluar_racikan_r.kdobat as kdobat',
                            'resep_permintaan_keluar_racikan.kdobat as kdobat',
                            'resep_keluar_h.depo as kdruang',
                            DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                        )
                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                            ->where('resep_keluar_h.depo', request('kdruang'))
                            ->whereIn('flag', ['', '1', '2'])
                            ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                    },
                ]
            )
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->where('stokreal.kdruang', request('kdruang'))
            ->where('stokreal.jumlah', '>', 0)
            ->whereIn('new_masterobat.sistembayar', $sistembayar)
            ->where('new_masterobat.status_konsinyasi', '')
            ->when(request('tiperesep') === 'prb', function ($q) {
                $q->where('new_masterobat.status_prb', '!=', '');
            })
            ->when(request('tiperesep') === 'iter', function ($q) {
                $q->where('new_masterobat.status_kronis', '!=', '');
            })
            ->where(function ($query) {
                $query->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('new_masterobat.kandungan', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('stokreal.kdobat', 'LIKE', '%' . request('q') . '%');
            })
            ->groupBy('stokreal.kdobat')
            ->limit(30)
            ->get();
        $wew = collect($cariobat)->map(function ($x, $y) {
            $total = $x->total ?? 0;
            $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
            $x->alokasi = $total - $jumlahtrans - $jumlahtransx;
            return $x;
        });
        return new JsonResponse(
            [
                'dataobat' => $wew
            ]
        );
    }

    public function pembuatanresep(Request $request)
    {
        $user = FormatingHelper::session_user();
        if ($user['kdgroupnakes'] != '1') {
            return new JsonResponse(['message' => 'Maaf Anda Bukan Dokter...!!!'], 500);
        }

        $cekjumlahstok = Stokreal::select(DB::raw('sum(jumlah) as jumlahstok'))
            ->where('kdobat', $request->kodeobat)->where('kdruang', $request->kodedepo)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();
        $jumlahstok = $cekjumlahstok[0]->jumlahstok;
        if ($request->jumlah > $jumlahstok) {
            return new JsonResponse(['message' => 'Maaf Stok Tidak Mencukupi...!!!'], 500);
        }

        if ($request->kodedepo === 'Gd-04010102') {
            $procedure = 'resepkeluardeporanap(@nomor)';
            $colom = 'deporanap';
            $lebel = 'D-RI';
        } elseif ($request->kodedepo === 'Gd-04010103') {
            $procedure = 'resepkeluardepook(@nomor)';
            $colom = 'depook';
            $lebel = 'D-KO';
        } elseif ($request->kodedepo === 'Gd-05010101') {
            $lanjut = $request->lanjuTr ?? '';
            $cekpemberian = self::cekpemberianobat($request, $jumlahstok);
            if ($cekpemberian['status'] == 1 && $lanjut !== '1') {
                return new JsonResponse(['message' => '', 'cek' => $cekpemberian], 202);
            }

            $procedure = 'resepkeluardeporajal(@nomor)';
            $colom = 'deporajal';
            $lebel = 'D-RJ';
        } else {
            $procedure = 'resepkeluardepoigd(@nomor)';
            $colom = 'depoigd';
            $lebel = 'D-IR';
        }

        if ($request->noresep === '' || $request->noresep === null) {
            DB::connection('farmasi')->select('call ' . $procedure);
            $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
            $wew = $x[0]->$colom;
            $noresep = FormatingHelper::resep($wew, $lebel);
        } else {
            $noresep = $request->noresep;
        }

        $simpan = Resepkeluarheder::updateOrCreate(
            [
                'noresep' => $noresep,
                'noreg' => $request->noreg,
            ],
            [
                'norm' => $request->norm,
                'iter_expired' => $request->iter_expired ?? null,
                'tgl_permintaan' => date('Y-m-d H:i:s'),
                'depo' => $request->kodedepo,
                'ruangan' => $request->kdruangan,
                'dokter' =>  $user['kodesimrs'],
                'sistembayar' => $request->sistembayar,
                'diagnosa' => $request->diagnosa,
                'kodeincbg' => $request->kodeincbg,
                'uraianinacbg' => $request->uraianinacbg,
                'tarifina' => $request->tarifina,
                'tiperesep' => $request->tiperesep ?? 'normal',
                // 'iter_expired' => $request->iter_expired ?? '',
                'tagihanrs' => $request->tagihanrs ?? 0,
            ]
        );

        if (!$simpan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        // $gudang = ['Gd-05010100', 'Gd-03010100'];
        // $cariharga = Stokreal::select(DB::raw('max(harga) as harga'))
        //     ->whereIn('kdruang', $gudang)
        //     ->where('kdobat', $request->kodeobat)
        //     ->orderBy('tglpenerimaan', 'desc')
        //     ->limit(5)
        //     ->get();
        // $harga = $cariharga[0]->harga;
        // if (!$harga) {
        //     return new JsonResponse(['message' => 'Tidak ada harga di gudang untuk obat ini'], 410);
        // }

        // if ($request->groupsistembayar == 1) {
        //     if ($harga <= 50000) {
        //         $hargajualx = (int) $harga + (int) $harga * (int) 28 / (int) 100;
        //     } elseif ($harga > 50000 && $harga <= 250000) {
        //         $hargajualx = (int) $harga + ((int) $harga * (int) 26 / (int) 100);
        //     } elseif ($harga > 250000 && $harga <= 500000) {
        //         $hargajualx = (int) $harga + (int) $harga * (int) 21 / (int) 100;
        //     } elseif ($harga > 500000 && $harga <= 1000000) {
        //         $hargajualx = (int) $harga + (int) $harga * (int) 16 / (int)100;
        //     } elseif ($harga > 1000000 && $harga <= 5000000) {
        //         $hargajualx = (int) $harga + (int) $harga * (int) 11 /  (int)100;
        //     } elseif ($harga > 5000000 && $harga <= 10000000) {
        //         $hargajualx = (int) $harga + (int) $harga * (int) 9 / (int) 100;
        //     } elseif ($harga > 10000000) {
        //         $hargajualx = (int) $harga + (int) $harga * (int) 7 / (int) 100;
        //     }
        // } else {
        //     $hargajualx = (int) $harga + (int) $harga * (int) 25 / (int)100;
        // }
        $har = HargaHelper::getHarga($request->kodeobat, $request->groupsistembayar);
        $hargajualx = $har['hargaJual'];
        $harga = $har['harga'];

        if ($request->jenisresep == 'Racikan') {
            if ($request->tiperacikan == 'DTD') {
                $simpandtd = Permintaanresepracikan::create(
                    [
                        'noreg' => $request->noreg,
                        'noresep' => $noresep,
                        'namaracikan' => $request->namaracikan,
                        'tiperacikan' => $request->tiperacikan,
                        'jumlahdibutuhkan' => $request->jumlahdibutuhkan, // jumlah racikan
                        'aturan' => $request->aturan,
                        'konsumsi' => $request->konsumsi,
                        'keterangan' => $request->keterangan,
                        'kdobat' => $request->kodeobat,
                        'kandungan' => $request->kandungan,
                        'fornas' => $request->fornas,
                        'forkit' => $request->forkit,
                        'generik' => $request->generik,
                        'r' => 500,
                        'hpp' => $harga,
                        'harga_jual' => $hargajualx,
                        'kode108' => $request->kode108,
                        'uraian108' => $request->uraian108,
                        'kode50' => $request->kode50,
                        'uraian50' => $request->uraian50,
                        'stokalokasi' => $request->stokalokasi,
                        'dosisobat' => $request->dosisobat,
                        'dosismaksimum' => $request->dosismaksimum, // dosis resep
                        'jumlah' => $request->jumlah, // jumlah obat
                        'satuan_racik' => $request->satuan_racik, // jumlah obat
                        'keteranganx' => $request->keteranganx, // keterangan obat
                        'user' => $user['kodesimrs']
                    ]
                );
                $simpandtd->load('mobat:kd_obat,nama_obat');
            } else {
                $simpannondtd = Permintaanresepracikan::create(
                    [
                        'noreg' => $request->noreg,
                        'noresep' => $noresep,
                        'namaracikan' => $request->namaracikan,
                        'tiperacikan' => $request->tiperacikan,
                        'jumlahdibutuhkan' => $request->jumlahdibutuhkan,
                        'aturan' => $request->aturan,
                        'konsumsi' => $request->konsumsi,
                        'keterangan' => $request->keterangan,
                        'kdobat' => $request->kodeobat,
                        'kandungan' => $request->kandungan,
                        'fornas' => $request->fornas,
                        'forkit' => $request->forkit,
                        'generik' => $request->generik,
                        'r' => 500,
                        'hpp' => $harga,
                        'harga_jual' => $hargajualx,
                        'kode108' => $request->kode108,
                        'uraian108' => $request->uraian108,
                        'kode50' => $request->kode50,
                        'uraian50' => $request->uraian50,
                        'stokalokasi' => $request->stokalokasi,
                        // 'dosisobat' => $request->dosisobat,
                        // 'dosismaksimum' => $request->dosismaksimum,
                        'jumlah' => $request->jumlah,
                        'satuan_racik' => $request->satuan_racik,
                        'keteranganx' => $request->keteranganx,
                        'user' => $user['kodesimrs']
                    ]
                );
                $simpannondtd->load('mobat:kd_obat,nama_obat');
            }
        } else {
            $simpanrinci = Permintaanresep::create(
                [
                    'noreg' => $request->noreg,
                    'noresep' => $noresep,
                    'kdobat' => $request->kodeobat,
                    'kandungan' => $request->kandungan,
                    'fornas' => $request->fornas,
                    'forkit' => $request->forkit,
                    'generik' => $request->generik,
                    'kode108' => $request->kode108,
                    'uraian108' => $request->uraian108,
                    'kode50' => $request->kode50,
                    'uraian50' => $request->uraian50,
                    'stokalokasi' => $request->stokalokasi,
                    'r' => 300,
                    'jumlah' => $request->jumlah_diminta,
                    'hpp' => $harga,
                    'hargajual' => $hargajualx,
                    'aturan' => $request->aturan,
                    'konsumsi' => $request->konsumsi,
                    'keterangan' => $request->keterangan ?? '',
                    'user' => $user['kodesimrs']
                ]
            );
            $simpanrinci->load('mobat:kd_obat,nama_obat');
        }

        // $simpan->load(
        //     'permintaanresep.mobat:kd_obat,nama_obat',
        //     'permintaanracikan.mobat:kd_obat,nama_obat'
        // );
        $endas = Resepkeluarheder::where('noreg', $request->noreg)->with(
            'permintaanresep.mobat:kd_obat,nama_obat',
            'permintaanracikan.mobat:kd_obat,nama_obat'
        )->get();
        return new JsonResponse([
            'newapotekrajal' => $endas,
            'heder' => $simpan,
            'rinci' => $simpanrinci ?? 0,
            'rincidtd' => $simpandtd ?? 0,
            'rincinondtd' => $simpannondtd ?? 0,
            'nota' => $noresep,
            'message' => 'Data Berhasil Disimpan...!!!'
        ], 200);
    }

    public function listresepbydokter()
    {
        $rm = [];
        if (request('q') !== null) {
            if (preg_match('~[0-9]+~', request('q'))) {
                $rm = [];
            } else {
                $data = Mpasien::select('rs1 as norm')->where('rs2', 'LIKE', '%' . request('q') . '%')->get();
                $rm = collect($data)->map(function ($x) {
                    return $x->norm;
                });
            }
        }
        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('from') . ' 00:00:00';
            $tglx = request('to') . ' 23:59:59';
        }
        // return $rm;
        $listresep = Resepkeluarheder::with(
            [
                'rincian.mobat:kd_obat,nama_obat,satuan_k',
                'rincianracik.mobat:kd_obat,nama_obat,satuan_k',
                'permintaanresep.mobat:kd_obat,nama_obat,satuan_k',
                'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k',
                'poli',
                'ruanganranap',
                'sistembayar',
                'dokter:kdpegsimrs,nama',
                'datapasien' => function ($quer) {
                    $quer->select(
                        'rs1',
                        'rs2 as nama'
                    );
                }
            ]
        )
            ->where(function ($query) use ($rm) {
                $query->when(count($rm) > 0, function ($wew) use ($rm) {
                    $wew->whereIn('norm', $rm);
                })
                    ->orWhere('noresep', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('norm', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('noreg', 'LIKE', '%' . request('q') . '%');
            })
            ->where('depo', request('kddepo'))
            ->whereBetween('tgl_permintaan', [$tgl, $tglx])
            ->when(request('tipe'), function ($x) {
                $x->where('tiperesep', request('tipe'));
            })
            ->when(request('flag'), function ($x) {
                $x->whereIn('flag', request('flag'));
            })
            ->when(!request('flag'), function ($x) {
                $x->where('flag', '2');
            })
            ->orderBy('flag', 'ASC')
            ->orderBy('tgl_permintaan', 'ASC')
            ->paginate(request('per_page'));
        // return new JsonResponse(request()->all());
        return new JsonResponse($listresep);
    }
    public function getSingleResep()
    {

        $listresep = Resepkeluarheder::with(
            [
                'rincian.mobat:kd_obat,nama_obat,satuan_k',
                'rincianracik.mobat:kd_obat,nama_obat,satuan_k',
                'permintaanresep.mobat:kd_obat,nama_obat,satuan_k',
                'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k',
                'poli',
                'ruanganranap',
                'sistembayar',
                'dokter:kdpegsimrs,nama',
                'datapasien' => function ($quer) {
                    $quer->select(
                        'rs1',
                        'rs2 as nama'
                    );
                }
            ]
        )
            ->where('farmasi.resep_keluar_h.id', request('id'))
            ->first();
        return new JsonResponse($listresep);
    }

    public function kirimresep(Request $request)
    {
        $kirimresep = Resepkeluarheder::where('noresep', $request->noresep)->first();
        if (!$kirimresep) {
            return new JsonResponse([
                'message' => 'Resep tidak ditemukan',

            ], 410);
        }
        $kirimresep->flag = '1';
        $kirimresep->tgl_kirim = date('Y-m-d H:i:s');
        $kirimresep->save();
        $kirimresep->load([
            'permintaanresep.mobat:kd_obat,nama_obat,satuan_k',
            'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k',
        ]);

        $msg = [
            'data' => [
                'id' => $kirimresep->id,
                'noreg' => $kirimresep->noreg,
                'depo' => $kirimresep->depo,
                'noresep' => $kirimresep->noresep,
                'status' => '1',
            ]
        ];
        event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));
        return new JsonResponse([
            'message' => 'Resep Berhasil Dikirim Kedepo Farmasi...!!!',
            'data' => $kirimresep
        ], 200);
    }

    public function terimaResep(Request $request)
    {
        $data = Resepkeluarheder::find($request->id);
        if ($data) {
            $data->flag = '2';
            $data->save();
            // $msg = [
            //     'data' => [
            //         'id' => $data->id,
            //         'noreg' => $data->noreg,
            //         'depo' => $data->depo,
            //         'noresep' => $data->noresep,
            //         'status' => '2',
            //     ]
            // ];
            // event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));
            return new JsonResponse(['message' => 'Resep Diterima', 'data' => $data], 200);
        }
        return new JsonResponse(['message' => 'data tidak ditemukan'], 410);
    }
    public function resepSelesai(Request $request)
    {
        $data = Resepkeluarheder::find($request->id);
        if ($data) {
            $data->update(['flag' => '3', 'tgl' => date('Y-m-d')]);
            // $msg = [
            //     'data' => [
            //         'id' => $data->id,
            //         'noreg' => $data->noreg,
            //         'depo' => $data->depo,
            //         'noresep' => $data->noresep,
            //         'status' => '3',
            //     ]
            // ];
            // event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));
            return new JsonResponse(['message' => 'Resep Selesai', 'data' => $data], 200);
        }
        return new JsonResponse(['message' => 'data tidak ditemukan'], 410);
    }

    public function eresepobatkeluar(Request $request)
    {
        // return new JsonResponse($request->all());
        $cekjumlahstok = Stokreal::select(DB::raw('sum(jumlah) as jumlahstok'))
            ->where('kdobat', $request->kdobat)->where('kdruang', $request->kodedepo)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();
        $jumlahstok = $cekjumlahstok[0]->jumlahstok;
        if ($request->jumlah > $cekjumlahstok[0]->jumlahstok) {
            return new JsonResponse(['message' => 'Maaf Stok Tidak Mencukupi...!!!'], 500);
        }

        $user = FormatingHelper::session_user();

        $gudang = ['Gd-05010100', 'Gd-03010100'];
        $cariharga = Stokreal::select(DB::raw('max(harga) as harga'))
            ->whereIn('kdruang', $gudang)
            ->where('kdobat', $request->kdobat)
            ->orderBy('tglpenerimaan', 'desc')
            ->limit(5)
            ->get();
        $harga = $cariharga[0]->harga;

        $jmldiminta = $request->jumlah;
        $caristok = Stokreal::where('kdobat', $request->kdobat)->where('kdruang', $request->kodedepo)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();

        $index = 0;
        $masuk = $jmldiminta;

        while ($masuk > 0) {
            $sisa = $caristok[$index]->jumlah;
            if ($request->groupsistembayar == 1) {
                if ($caristok[$index]->harga <= 50000) {
                    $hargajual = (int) $harga + (int) $harga * (int) 28 / (int) 100;
                } elseif ($caristok[$index]->harga > 50000 && $caristok[$index]->harga <= 250000) {
                    $hargajual = (int) $harga + ((int) $harga * (int) 26 / (int) 100);
                } elseif ($caristok[$index]->harga > 250000 && $caristok[$index]->harga <= 500000) {
                    $hargajual = (int) $harga + (int) $harga * (int) 21 / (int) 100;
                } elseif ($caristok[$index]->harga > 500000 && $caristok[$index]->harga <= 1000000) {
                    $hargajual = (int) $harga + (int) $harga * (int) 16 / (int)100;
                } elseif ($caristok[$index]->harga > 1000000 && $caristok[$index]->harga <= 5000000) {
                    $hargajual = (int) $harga + (int) $harga * (int) 11 /  (int)100;
                } elseif ($caristok[$index]->harga > 5000000 && $caristok[$index]->harga <= 10000000) {
                    $hargajual = (int) $harga + (int) $harga * (int) 9 / (int) 100;
                } elseif ($caristok[$index]->harga > 10000000) {
                    $hargajual = (int) $harga + (int) $harga * (int) 7 / (int) 100;
                }
            } else {
                $hargajual = (int) $harga + (int) $harga * (int) 25 / (int)100;
            }

            if ($sisa < $masuk) {
                $sisax = $masuk - $sisa;

                if ($request->jenisresep == 'Racikan') {
                    $simpanrinci = Resepkeluarrinciracikan::create(
                        [
                            'noreg' => $request->noreg,
                            'noresep' => $request->noresep,
                            'tiperacikan' => $request->tiperacikan,
                            'namaracikan' => $request->namaracikan,
                            'kdobat' => $request->kdobat,
                            'nopenerimaan' => $caristok[$index]->nopenerimaan,
                            'jumlah' => $caristok[$index]->jumlah,
                            'harga_beli' => $caristok[$index]->harga,
                            'hpp' => $harga,
                            'harga_jual' => $hargajual,
                            'nilai_r' => $request->nilai_r,
                            'user' => $user['kodesimrs']
                        ]
                    );
                } else {
                    $simpanrinci = Resepkeluarrinci::create(
                        [
                            'noreg' => $request->noreg,
                            'noresep' => $request->noresep,
                            'kdobat' => $request->kdobat,
                            'kandungan' => $request->kandungan,
                            'fornas' => $request->fornas,
                            'forkit' => $request->forkit,
                            'generik' => $request->generik,
                            'kode108' => $request->kode108,
                            'uraian108' => $request->uraian108,
                            'kode50' => $request->kode50,
                            'uraian50' => $request->uraian50,
                            'nopenerimaan' => $caristok[$index]->nopenerimaan,
                            'jumlah' => $caristok[$index]->jumlah,
                            'harga_beli' => $caristok[$index]->harga,
                            'hpp' => $harga,
                            'harga_jual' => $hargajual,
                            'nilai_r' => $request->nilai_r,
                            'aturan' => $request->aturan,
                            'konsumsi' => $request->konsumsi,
                            'keterangan' => $request->keterangan ?? '',
                            'user' => $user['kodesimrs']
                        ]
                    );
                }

                Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                    ->where('kdobat', $caristok[$index]->kdobat)
                    ->where('kdruang', $request->kodedepo)
                    ->update(['jumlah' => 0]);

                $masuk = $sisax;
                $index = $index + 1;
                $simpanrinci->load('mobat:kd_obat,nama_obat');
            } else {
                $sisax = $sisa - $masuk;

                if ($request->jenisresep == 'Racikan') {
                    $simpanrinci = Resepkeluarrinciracikan::create(
                        [
                            'noreg' => $request->noreg,
                            'noresep' => $request->noresep,
                            'namaracikan' => $request->namaracikan,
                            'tiperacikan' => $request->tiperacikan,
                            'kdobat' => $request->kdobat,
                            'nopenerimaan' => $caristok[$index]->nopenerimaan,
                            'jumlah' => $masuk,
                            'harga_beli' => $caristok[$index]->harga,
                            'hpp' => $harga,
                            'harga_jual' => $hargajual,
                            'nilai_r' => $request->nilai_r,
                            'user' => $user['kodesimrs']
                        ]
                    );
                } else {
                    $simpanrinci = Resepkeluarrinci::create(
                        [
                            'noreg' => $request->noreg,
                            'noresep' => $request->noresep,
                            'kdobat' => $request->kdobat,
                            'kandungan' => $request->kandungan,
                            'fornas' => $request->fornas,
                            'forkit' => $request->forkit,
                            'generik' => $request->generik,
                            'kode108' => $request->kode108,
                            'uraian108' => $request->uraian108,
                            'kode50' => $request->kode50,
                            'uraian50' => $request->uraian50,
                            'nopenerimaan' => $caristok[$index]->nopenerimaan,
                            'jumlah' => $masuk,
                            'harga_beli' => $caristok[$index]->harga,
                            'hpp' => $harga,
                            'harga_jual' => $hargajual,
                            'nilai_r' => $request->nilai_r,
                            'aturan' => $request->aturan,
                            'konsumsi' => $request->konsumsi,
                            'keterangan' => $request->keterangan ?? '',
                            'user' => $user['kodesimrs']
                        ]
                    );
                }

                Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                    ->where('kdobat', $caristok[$index]->kdobat)
                    ->where('kdruang', $request->kodedepo)
                    ->update(['jumlah' => $sisax]);
                $masuk = 0;
                $simpanrinci->load('mobat:kd_obat,nama_obat');
            }
            return new JsonResponse([
                'rinci' => $simpanrinci,
                'message' => 'Data Berhasil Disimpan...!!!'
            ], 200);
        }
    }

    public function hapusPermintaanObat(Request $request)
    {
        if ($request->has('namaracikan')) {
            $obat = Permintaanresepracikan::find($request->id);
            if ($obat) {
                $obat->delete();
                self::hapusHeader($request);
                $endas = Resepkeluarheder::where('noreg', $request->noreg)->with(
                    'permintaanresep.mobat:kd_obat,nama_obat',
                    'permintaanracikan.mobat:kd_obat,nama_obat'
                )->get();
                return new JsonResponse([
                    'message' => 'Permintaan resep Obat Racikan telah dihapus',
                    'obat' => $obat,
                    'newapotekrajal' => $endas,
                ]);
            }
            return new JsonResponse([
                'message' => 'Permintaan resep Obat Racikan Gagal dihapus',
                'obat' => $obat,
            ], 410);
        }
        $obat = Permintaanresep::find($request->id);
        if ($obat) {
            $obat->delete();
            self::hapusHeader($request);
            $endas = Resepkeluarheder::where('noreg', $request->noreg)->with(
                'permintaanresep.mobat:kd_obat,nama_obat',
                'permintaanracikan.mobat:kd_obat,nama_obat'
            )->get();
            return new JsonResponse([
                'message' => 'Permintaan resep Obat telah dihapus',
                'obat' => $obat,
                'newapotekrajal' => $endas,
            ]);
        }
        return new JsonResponse([
            'message' => 'Permintaan resep Obat Gagal dihapus',
            'obat' => $obat,
        ], 410);
    }

    public static function hapusHeader($request)
    {
        $racik = Permintaanresepracikan::where('noresep', $request->noresep)->get();
        $nonracik = Permintaanresep::where('noresep', $request->noresep)->get();
        if (count($racik) === 0 && count($nonracik) === 0) {
            $head = Resepkeluarheder::where('noresep', $request->noresep)->first();
            if ($head) {
                $head->delete();
            }
        }
    }
    public static function cekpemberianobat($request, $jumlahstok)
    {
        // ini tujuannya mencari sisa obat pasien dengan dihitung jumlah konsumsi obat per hari bersasarkan signa
        // harus ada data jumlah hari (obat dikonsumsi dalam ... hari) di tabel

        $cekmaster = Mobatnew::select('kandungan')->where('kd_obat', $request->kodeobat)->first();

        // $jumlahdosis = $request->jumlahdosis;
        // $jumlah = $request->jumlah;
        // $jmlhari = (int) $jumlah / $jumlahdosis;
        // $total = (int) $jmlhari + (int) $jumlahstok;
        if ($cekmaster->kandungan === '') {
            $hasil = Resepkeluarheder::select(
                'resep_keluar_h.noresep as noresep',
                'resep_keluar_h.tgl as tgl',
                'resep_keluar_r.konsumsi',
                DB::raw('(DATEDIFF(CURRENT_DATE(), resep_keluar_h.tgl)+1) as selisih')
            )
                ->leftjoin('resep_keluar_r', 'resep_keluar_h.noresep', 'resep_keluar_r.noresep')
                ->where('resep_keluar_h.norm', $request->norm)
                ->where('resep_keluar_r.kdobat', $request->kodeobat)
                ->orderBy('resep_keluar_h.tgl', 'desc')
                ->limit(1)
                ->get();
        } else {
            $hasil = Resepkeluarheder::select(
                'resep_keluar_h.noresep as noresep',
                'resep_keluar_h.tgl as tgl',
                'resep_keluar_r.konsumsi',
                DB::raw('(DATEDIFF(CURRENT_DATE(), resep_keluar_h.tgl)+1) as selisih')
            )
                ->leftjoin('resep_keluar_r', 'resep_keluar_h.noresep', 'resep_keluar_r.noresep')
                ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'resep_keluar_r.kdobat')
                ->where('resep_keluar_h.norm', $request->norm)
                ->where('resep_keluar_r.kdobat', $request->kodeobat)
                ->where('new_masterobat.kandungan', $request->kandungan)
                ->orderBy('resep_keluar_h.tgl', 'desc')
                ->limit(1)
                ->get();
        }
        $selisih = 0;
        $total = 0;
        if (count($hasil)) {
            $selisih = $hasil[0]->selisih;
            $total = (float)$hasil[0]->konsumsi;
            if ($selisih <= $total) {
                return [
                    'status' => 1,
                    'hasil' => $hasil,
                    'selisih' => $selisih,
                    'total' => $total,
                ];
            } else {
                return [
                    'status' => 2,
                    'hasil' => $hasil,
                    'selisih' => $selisih,
                    'total' => $total,
                ];
                // return 2;
            }
        }
        return [
            'status' => 2,
            'hasil' => $hasil,
            'selisih' => $selisih,
            'total' => $total,
        ];
    }
}
