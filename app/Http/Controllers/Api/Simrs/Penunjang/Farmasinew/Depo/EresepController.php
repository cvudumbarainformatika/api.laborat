<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Helpers\HargaHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\PelayananInformasiObat;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use App\Models\SistemBayar;
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
                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
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
                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                            ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                    },
                    'permintaanobatrinci' => function ($permintaanobatrinci) {
                        $permintaanobatrinci->select(
                            'permintaan_r.no_permintaan',
                            'permintaan_r.kdobat',
                            DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                        )
                            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                            // biar yang ada di tabel mutasi ga ke hitung
                            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                            })
                            ->whereNull('mutasi_gudangdepo.kd_obat')

                            ->where('permintaan_h.tujuan', request('kdruang'))
                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                            ->groupBy('permintaan_r.kdobat');
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
            $mutasiantardepo = $x['permintaanobatrinci'][0]->allpermintaan ?? 0;
            $x->alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$mutasiantardepo;
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
        try {
            DB::connection('farmasi')->beginTransaction();
            $user = FormatingHelper::session_user();
            if ($user['kdgroupnakes'] != '1') {
                return new JsonResponse(['message' => 'Maaf Anda Bukan Dokter...!!!'], 500);
            }

            if ($request->kodedepo === 'Gd-05010101') {
                $tiperesep = $request->tiperesep ?? 'normal';
                $iter_expired = $request->iter_expired ?? null;
                $iter_jml = $request->iter_jml ?? null;
            } else {
                $tiperesep =  'normal';
                $iter_expired =  null;
                $iter_jml =  null;
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
                    'tgl_permintaan' => date('Y-m-d H:i:s'),
                    'tgl' => date('Y-m-d'),
                    'depo' => $request->kodedepo,
                    'ruangan' => $request->kdruangan,
                    'dokter' =>  $user['kodesimrs'],
                    'sistembayar' => $request->sistembayar,
                    'diagnosa' => $request->diagnosa,
                    'kodeincbg' => $request->kodeincbg,
                    'uraianinacbg' => $request->uraianinacbg,
                    'tarifina' => $request->tarifina,
                    'tiperesep' => $tiperesep,
                    'iter_expired' => $iter_expired,
                    'iter_jml' => $iter_jml,
                    // 'iter_expired' => $request->iter_expired ?? '',
                    'tagihanrs' => $request->tagihanrs ?? 0,
                ]
            );

            if (!$simpan) {
                return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
            }


            $har = HargaHelper::getHarga($request->kodeobat, $request->groupsistembayar);
            $res = $har['res'];
            if ($res) {
                return new JsonResponse(['message' => $har['message'], 'data' => $har], 410);
            }
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
                    // if ($simpandtd) {
                    //     $simpandtd->load('mobat:kd_obat,nama_obat');
                    // }
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
                    // if ($simpannondtd) {
                    //     $simpannondtd->load('mobat:kd_obat,nama_obat');
                    // }
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
                // if ($simpanrinci) {
                //     $simpanrinci->load('mobat:kd_obat,nama_obat');
                // }
            }

            // $simpan->load(
            //     'permintaanresep.mobat:kd_obat,nama_obat',
            //     'permintaanracikan.mobat:kd_obat,nama_obat'
            // );
            $endas = Resepkeluarheder::where('noreg', $request->noreg)->with(
                'permintaanresep.mobat:kd_obat,nama_obat',
                'permintaanracikan.mobat:kd_obat,nama_obat'
            )->get();
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'newapotekrajal' => $endas,
                'heder' => $simpan,
                'rinci' => $simpanrinci ?? 0,
                'rincidtd' => $simpandtd ?? 0,
                'rincinondtd' => $simpannondtd ?? 0,
                'nota' => $noresep,
                'message' => 'Data Berhasil Disimpan...!!!'
            ], 200);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'newapotekrajal' => $endas ?? false,
                'har' => $har ?? false,
                'heder' => $simpan ?? false,
                'rinci' => $simpanrinci ?? 0,
                'rincidtd' => $simpandtd ?? 0,
                'rincinondtd' => $simpannondtd ?? 0,
                'cekjumlahstok' => $cekjumlahstok ?? 0,
                'noresep' => $noresep ?? 0,
                'user' => $user['kodesimrs'] ?? 0,
                'tiperesep' => $tiperesep ?? 0,
                'iter_expired' => $iter_expired ?? 0,
                'iter_jml' => $iter_jml ?? 0,
                'error' => $e,
                'message' => 'ada kesalahan'
            ], 410);
        }
    }

    public function listresepbydokter()
    {
        // $data['c'] = date('m');
        // $data['c-3'] = (int)date('m') - 3;
        // $m = (int)date('m') - 3;
        // $data['all'] = [date('Y') . '-0' . ((int)date('m') - 3) . '-01 00:00:00', date('Y-m-31 23:59:59')];
        // return new JsonResponse($data);
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
                'info',
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
            ->when(!request('tipe') || request('tipe') === null, function ($x) use ($tgl, $tglx) {
                $x->whereBetween('tgl_permintaan', [$tgl, $tglx]);
            })
            ->when(request('tipe'), function ($x) use ($tgl, $tglx) {
                if (request('tipe') === 'iter' && request('kddepo') === 'Gd-05010101') {
                    $x->where('tiperesep', request('tipe'))
                        ->where('noresep_asal', '=', '')
                        ->whereBetween('iter_expired', [date('Y-m-d 00:00:00'), date('Y') . '-0' . ((int)date('m') + 3) . '-31 23:59:59']);
                } else {
                    $x->where('tiperesep', request('tipe'))
                        ->whereBetween('tgl_permintaan', [$tgl, $tglx]);
                }
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

        $flag = $kirimresep->flag;
        if ((int)$flag >= 1) {
            return new JsonResponse([
                'message' => 'Resep sudah dikirimkan',

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
        try {
            DB::connection('farmasi')->beginTransaction();



            // $gudang = ['Gd-05010100', 'Gd-03010100'];
            // $cariharga = Stokreal::select(DB::raw('max(harga) as harga'))
            //     ->whereIn('kdruang', $gudang)
            //     ->where('kdobat', $request->kdobat)
            //     ->orderBy('tglpenerimaan', 'desc')
            //     ->limit(5)
            //     ->get();
            // $harga = $cariharga[0]->harga;

            $jmldiminta = $request->jumlah;
            $caristok = Stokreal::where('kdobat', $request->kdobat)->where('kdruang', $request->kodedepo)
                ->where('jumlah', '!=', 0)
                ->orderBy('tglexp')
                ->get();

            $index = 0;
            $masuk = $jmldiminta;

            while ($masuk > 0) {
                $sisa = $caristok[$index]->jumlah;

                $har = HargaHelper::getHarga($request->kdobat, $request->groupsistembayar);
                $res = $har['res'];
                if ($res) {
                    return new JsonResponse(['message' => $har['message'], 'data' => $har], 410);
                }
                $hargajual = $har['hargaJual'];
                $harga = $har['harga'];

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
                DB::connection('farmasi')->commit();
                return new JsonResponse([
                    'rinci' => $simpanrinci,
                    'message' => 'Data Berhasil Disimpan...!!!'
                ], 200);
            }
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 410);
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

    public function ambilIter(Request $request)
    {
        // $noresep = $request->noresep_asal ?? $request->noresep;

        $head = Resepkeluarheder::where('noresep', $request->noresep)
            ->when(
                $request->noresep_asal === null || $request->noresep_asal === '',
                function ($h) use ($request) {
                    $h->with([
                        'permintaanresep.mobat',
                        'permintaanresep.stok' => function ($stok) use ($request) {
                            $stok->selectRaw('kdobat, sum(jumlah) as total')
                                ->where('kdruang', $request->depo)
                                ->where('jumlah', '>', 0)
                                ->with([
                                    'transnonracikan' => function ($transnonracikan) use ($request) {
                                        $transnonracikan->select(
                                            // 'resep_keluar_r.kdobat as kdobat',
                                            'resep_permintaan_keluar.kdobat as kdobat',
                                            'resep_keluar_h.depo as kdruang',
                                            DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                                        )
                                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                                            ->where('resep_keluar_h.depo', $request->depo)
                                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                            ->groupBy('resep_permintaan_keluar.kdobat');
                                    },
                                    'transracikan' => function ($transracikan) use ($request) {
                                        $transracikan->select(
                                            // 'resep_keluar_racikan_r.kdobat as kdobat',
                                            'resep_permintaan_keluar_racikan.kdobat as kdobat',
                                            'resep_keluar_h.depo as kdruang',
                                            DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                                        )
                                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                                            ->where('resep_keluar_h.depo', $request->depo)
                                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                            ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                                    },
                                    'permintaanobatrinci' => function ($permintaanobatrinci) use ($request) {
                                        $permintaanobatrinci->select(
                                            'permintaan_r.no_permintaan',
                                            'permintaan_r.kdobat',
                                            DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                                        )
                                            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                                            // biar yang ada di tabel mutasi ga ke hitung
                                            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                                            })
                                            ->whereNull('mutasi_gudangdepo.kd_obat')

                                            ->where('permintaan_h.tujuan', $request->depo)
                                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                                            ->groupBy('permintaan_r.kdobat');
                                    },

                                ])
                                ->groupBy('kdobat');
                        },
                        'permintaanracikan.mobat',
                        'permintaanracikan.stok' => function ($stok) use ($request) {
                            $stok->selectRaw('kdobat, sum(jumlah) as total')
                                ->where('kdruang', $request->depo)
                                ->where('jumlah', '>', 0)->with([
                                    'transnonracikan' => function ($transnonracikan) use ($request) {
                                        $transnonracikan->select(
                                            // 'resep_keluar_r.kdobat as kdobat',
                                            'resep_permintaan_keluar.kdobat as kdobat',
                                            'resep_keluar_h.depo as kdruang',
                                            DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                                        )
                                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                                            ->where('resep_keluar_h.depo', $request->depo)
                                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                            ->groupBy('resep_permintaan_keluar.kdobat');
                                    },
                                    'transracikan' => function ($transracikan) use ($request) {
                                        $transracikan->select(
                                            // 'resep_keluar_racikan_r.kdobat as kdobat',
                                            'resep_permintaan_keluar_racikan.kdobat as kdobat',
                                            'resep_keluar_h.depo as kdruang',
                                            DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                                        )
                                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                                            ->where('resep_keluar_h.depo', $request->depo)
                                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                            ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                                    },
                                    'permintaanobatrinci' => function ($permintaanobatrinci) use ($request) {
                                        $permintaanobatrinci->select(
                                            'permintaan_r.no_permintaan',
                                            'permintaan_r.kdobat',
                                            DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                                        )
                                            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                                            // biar yang ada di tabel mutasi ga ke hitung
                                            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                                            })
                                            ->whereNull('mutasi_gudangdepo.kd_obat')

                                            ->where('permintaan_h.tujuan', $request->depo)
                                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                                            ->groupBy('permintaan_r.kdobat');
                                    },

                                ])
                                ->groupBy('kdobat');
                        }
                    ]);
                }
            )
            ->when($request->noresep_asal !== null, function ($h) use ($request) {
                $h->where('noresep_asal', $request->noresep_asal)
                    ->with([
                        'asalpermintaanresep' => function ($per) {
                            $per->select('resep_permintaan_keluar.*')
                                ->leftJoin('resep_keluar_r', function ($join) {
                                    $join->on('resep_keluar_r.noresep', '=', 'resep_permintaan_keluar.noresep')
                                        ->on('resep_keluar_r.kdobat', '=', 'resep_permintaan_keluar.kdobat');
                                })
                                ->whereNotNull('resep_keluar_r.kdobat');
                        },
                        'asalpermintaanracikan' => function ($per) {
                            $per->select('resep_permintaan_keluar_racikan.*')
                                ->leftJoin('resep_keluar_racikan_r', function ($join) {
                                    $join->on('resep_keluar_racikan_r.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                                        ->on('resep_keluar_racikan_r.kdobat', '=', 'resep_permintaan_keluar_racikan.kdobat');
                                })
                                ->whereNotNull('resep_keluar_racikan_r.kdobat');
                        },
                        'asalpermintaanresep.mobat',
                        'asalpermintaanresep.stok' => function ($stok) use ($request) {
                            $stok->selectRaw('kdobat, sum(jumlah) as total')
                                ->where('kdruang', $request->depo)
                                ->where('jumlah', '>', 0)
                                ->with([
                                    'transnonracikan' => function ($transnonracikan) use ($request) {
                                        $transnonracikan->select(
                                            // 'resep_keluar_r.kdobat as kdobat',
                                            'resep_permintaan_keluar.kdobat as kdobat',
                                            'resep_keluar_h.depo as kdruang',
                                            DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                                        )
                                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                                            ->where('resep_keluar_h.depo', $request->depo)
                                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                            ->groupBy('resep_permintaan_keluar.kdobat');
                                    },
                                    'transracikan' => function ($transracikan) use ($request) {
                                        $transracikan->select(
                                            // 'resep_keluar_racikan_r.kdobat as kdobat',
                                            'resep_permintaan_keluar_racikan.kdobat as kdobat',
                                            'resep_keluar_h.depo as kdruang',
                                            DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                                        )
                                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                                            ->where('resep_keluar_h.depo', $request->depo)
                                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                            ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                                    },
                                    'permintaanobatrinci' => function ($permintaanobatrinci) use ($request) {
                                        $permintaanobatrinci->select(
                                            'permintaan_r.no_permintaan',
                                            'permintaan_r.kdobat',
                                            DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                                        )
                                            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                                            // biar yang ada di tabel mutasi ga ke hitung
                                            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                                            })
                                            ->whereNull('mutasi_gudangdepo.kd_obat')

                                            ->where('permintaan_h.tujuan', $request->depo)
                                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                                            ->groupBy('permintaan_r.kdobat');
                                    },

                                ])
                                ->groupBy('kdobat');
                        },
                        'asalpermintaanracikan.mobat',
                        'asalpermintaanracikan.stok' => function ($stok) use ($request) {
                            $stok->selectRaw('kdobat, sum(jumlah) as total')
                                ->where('kdruang', $request->depo)
                                ->where('jumlah', '>', 0)->with([
                                    'transnonracikan' => function ($transnonracikan) use ($request) {
                                        $transnonracikan->select(
                                            // 'resep_keluar_r.kdobat as kdobat',
                                            'resep_permintaan_keluar.kdobat as kdobat',
                                            'resep_keluar_h.depo as kdruang',
                                            DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                                        )
                                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                                            ->where('resep_keluar_h.depo', $request->depo)
                                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                            ->groupBy('resep_permintaan_keluar.kdobat');
                                    },
                                    'transracikan' => function ($transracikan) use ($request) {
                                        $transracikan->select(
                                            // 'resep_keluar_racikan_r.kdobat as kdobat',
                                            'resep_permintaan_keluar_racikan.kdobat as kdobat',
                                            'resep_keluar_h.depo as kdruang',
                                            DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                                        )
                                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                                            ->where('resep_keluar_h.depo', $request->depo)
                                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                            ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                                    },
                                    'permintaanobatrinci' => function ($permintaanobatrinci) use ($request) {
                                        $permintaanobatrinci->select(
                                            'permintaan_r.no_permintaan',
                                            'permintaan_r.kdobat',
                                            DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                                        )
                                            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                                            // biar yang ada di tabel mutasi ga ke hitung
                                            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                                            })
                                            ->whereNull('mutasi_gudangdepo.kd_obat')

                                            ->where('permintaan_h.tujuan', $request->depo)
                                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                                            ->groupBy('permintaan_r.kdobat');
                                    },

                                ])
                                ->groupBy('kdobat');
                        }
                    ]);
            })
            ->first();
        if ($request->noresep_asal !== null) {
            $head->permintaanresep = $head->asalpermintaanresep;
            $head->permintaanracikan = $head->asalpermintaanracikan;
            // $resp['head'] = $head;
            // // $resp['noresep'] = $noresep;
            // // $resp['sistembayar'] = $sistembayar->groups;
            // $resp['req'] = $request->all();
            // return new JsonResponse($resp);
        }
        $sistembayar = SistemBayar::where('rs1', $head->sistembayar)->first();
        if (count($head->permintaanresep) > 0) {
            foreach ($head->permintaanresep as $key) {
                $har = HargaHelper::getHarga($key['kdobat'], $sistembayar->groups);
                $key['res'] = $har;
                $key['hargapokok'] = $har['harga'] ?? 0;
                $key['hargajual'] = $har['hargaJual'] ?? 0;
                $key['groupsistembayar'] = $sistembayar->groups;
            }
        }
        if (count($head->permintaanracikan) > 0) {
            foreach ($head->permintaanracikan as $key) {
                $har = HargaHelper::getHarga($key['kdobat'], $sistembayar->groups);
                $key['res'] = $har;
                $key['hargapokok'] = $har['harga'] ?? 0;
                $key['hargajual'] = $har['hargaJual'] ?? 0;
                $key['groupsistembayar'] = $sistembayar->groups;
            }
        }
        $resp['head'] = $head;
        // $resp['noresep'] = $noresep;
        $resp['sistembayar'] = $sistembayar->groups;
        $resp['req'] = $request->all();
        return new JsonResponse($resp);
    }

    public function copyResep(Request $request)
    {


        $head = $request->head;
        $ada = Resepkeluarheder::where('tiperesep', $head['tiperesep'])
            ->whereDate('tgl', $head['tgl'])
            ->where('noresep_asal', $head['noresep_asal'])
            ->first();
        if ($ada) {
            $ada->load('rincian.mobat:kd_obat,nama_obat', 'rincianracik.mobat:kd_obat,nama_obat');
            return new JsonResponse(
                [
                    'message' => 'Resep Iter Sudah dibuat Hari ini',
                    'data' => $ada
                ],
                410
            );
        }

        $hasilCek = [];
        $lanjut = $request->lanjut ?? '';
        if (count($request->kirimResep) > 0) {
            foreach ($request->kirimResep as $key) {
                $cek = self::cekpemberianobatCopy($key['kdobat'], $head['norm'], $key['kandungan']);
                if ($cek['status'] == 1 && $lanjut !== '1') {
                    return new JsonResponse(['message' => '', 'cek' => $cek], 202);
                }
                $hasilCek[] = $cek;
            }
        }
        $procedure = 'resepkeluardeporajal(@nomor)';
        $colom = 'deporajal';
        $lebel = 'iter-D-RJ';
        DB::connection('farmasi')->select('call ' . $procedure);
        $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
        $wew = $x[0]->$colom;
        $noresep = FormatingHelper::resep($wew, $lebel);

        // $noresep = 'noresepbaru';
        $resep = [];
        $racik = [];

        try {
            DB::connection('farmasi')->beginTransaction();
            $head['noresep'] = $noresep;

            if (count($request->kirimResep) > 0) {
                foreach ($request->kirimResep as $key) {
                    // $cek = self::cekpemberianobatCopy($key['kdobat'], $head['norm'], $key['kandungan']);
                    // if ($cek['status'] == 1 && $lanjut !== '1') {
                    //     return new JsonResponse(['message' => '', 'cek' => $cek], 202);
                    // }
                    // $hasilCek[] = $cek;
                    $jmldiminta = $key['jumlahMinta'];
                    unset($key['jumlahMinta']);
                    $caristok = Stokreal::where('kdobat', $key['kdobat'])->where('kdruang', $request->kddepo)
                        ->where('jumlah', '!=', 0)
                        ->orderBy('tglexp')
                        ->get();

                    $index = 0;
                    $masuk = $jmldiminta;
                    while ($masuk > 0) {
                        $sisa = $caristok[$index]->jumlah;

                        $har = HargaHelper::getHarga($key['kdobat'], $request->groupsistembayar);
                        $res = $har['res'];
                        if ($res) {
                            return new JsonResponse(['message' => $har['message'], 'data' => $har], 410);
                        }
                        $hargajual = $har['hargaJual'];
                        $harga = $har['harga'];
                        if ($sisa < $masuk) {
                            $sisax = $masuk - $sisa;

                            $key['nopenerimaan'] = $caristok[$index]->nopenerimaan;
                            $key['jumlah'] = $caristok[$index]->jumlah;
                            $key['harga_beli'] = $caristok[$index]->harga;
                            $key['hpp'] = $harga;
                            $key['harga_jual'] = $hargajual;


                            $key['noresep'] = $noresep;
                            $key['created_at'] = date('Y-m-d H:i:s');
                            $key['updated_at'] = date('Y-m-d H:i:s');

                            Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                                ->where('kdobat', $caristok[$index]->kdobat)
                                ->where('kdruang', $request->kodedepo)
                                ->update(['jumlah' => 0]);

                            $masuk = $sisax;
                            $index = $index + 1;
                        } else {
                            $sisax = $sisa - $masuk;

                            $key['nopenerimaan'] = $caristok[$index]->nopenerimaan;
                            $key['jumlah'] = $masuk;
                            $key['harga_beli'] = $caristok[$index]->harga;
                            $key['hpp'] = $harga;
                            $key['harga_jual'] = $hargajual;


                            $key['noresep'] = $noresep;
                            $key['created_at'] = date('Y-m-d H:i:s');
                            $key['updated_at'] = date('Y-m-d H:i:s');

                            Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                                ->where('kdobat', $caristok[$index]->kdobat)
                                ->where('kdruang', $request->kodedepo)
                                ->update(['jumlah' => $sisax]);

                            $masuk = 0;
                        }

                        $resep[] = $key;
                    }
                }
            }

            if (count($request->kirimRacik) > 0) {
                foreach ($request->kirimRacik as $key) {
                    $jmldiminta = $key['jumlahMinta'];
                    unset($key['jumlahMinta']);
                    $caristok = Stokreal::where('kdobat', $key['kdobat'])->where('kdruang', $request->kddepo)
                        ->where('jumlah', '!=', 0)
                        ->orderBy('tglexp')
                        ->get();

                    $index = 0;
                    $masuk = $jmldiminta;
                    while ($masuk > 0) {
                        $sisa = $caristok[$index]->jumlah;

                        $har = HargaHelper::getHarga($key['kdobat'], $request->groupsistembayar);
                        $res = $har['res'];
                        if ($res) {
                            return new JsonResponse(['message' => $har['message'], 'data' => $har], 410);
                        }
                        $hargajual = $har['hargaJual'];
                        $harga = $har['harga'];
                        if ($sisa < $masuk) {
                            $sisax = $masuk - $sisa;
                            $key['nopenerimaan'] = $caristok[$index]->nopenerimaan;
                            $key['jumlah'] = $caristok[$index]->jumlah;
                            $key['harga_beli'] = $caristok[$index]->harga;
                            $key['hpp'] = $harga;
                            $key['harga_jual'] = $hargajual;


                            $key['noresep'] = $noresep;
                            $key['satuan_racik'] = $key['satuan_racik'] ?? '';
                            $key['created_at'] = date('Y-m-d H:i:s');
                            $key['updated_at'] = date('Y-m-d H:i:s');


                            // Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                            // ->where('kdobat', $caristok[$index]->kdobat)
                            // ->where('kdruang', $request->kodedepo)
                            // ->update(['jumlah' => 0]);

                            $masuk = $sisax;
                            $index = $index + 1;
                        } else {
                            $sisax = $sisa - $masuk;

                            $key['nopenerimaan'] = $caristok[$index]->nopenerimaan;
                            $key['jumlah'] = $masuk;
                            $key['harga_beli'] = $caristok[$index]->harga;
                            $key['hpp'] = $harga;
                            $key['harga_jual'] = $hargajual;


                            $key['noresep'] = $noresep;
                            $key['satuan_racik'] = $key['satuan_racik'] ?? '';
                            $key['created_at'] = date('Y-m-d H:i:s');
                            $key['updated_at'] = date('Y-m-d H:i:s');

                            // Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                            // ->where('kdobat', $caristok[$index]->kdobat)
                            // ->where('kdruang', $request->kodedepo)
                            // ->update(['jumlah' => $sisax]);

                            $masuk = 0;
                        }
                    }




                    $racik[] = $key;
                }
            }
            if (count($request->kirimResep) <= 0 && count($request->kirimRacik)) {
                return new JsonResponse(['message' => 'Tidak ada obat untuk di input'], 410);
            }
            /**
             * start of create section
             */

            $createHead = Resepkeluarheder::create($head);
            if (count($resep) > 0) {
                $createResep = Resepkeluarrinci::insert($resep);
            }
            if (count($racik) > 0) {
                $createRacik = Resepkeluarrinciracikan::insert($racik);
            }

            /**
             * end of create section
             */

            $data['req'] = $request->all();
            $data['head'] = $head;
            $data['hasilCek'] = $hasilCek;
            $data['resep'] = $resep;
            $data['racik'] = $racik;
            $data['createHead'] = $createHead;
            $data['createResep'] = $createResep ?? false;
            $data['createRacik'] = $createRacik ?? false;
            $data['message'] = 'Copy Resep selesai dan Obat sudah berkurang';
            DB::connection('farmasi')->commit();
            return new JsonResponse($data);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 410);
        }
    }

    public static function cekpemberianobatCopy($obat, $norm, $kandungan)
    {
        // ini tujuannya mencari sisa obat pasien dengan dihitung jumlah konsumsi obat per hari bersasarkan signa
        // harus ada data jumlah hari (obat dikonsumsi dalam ... hari) di tabel

        $cekmaster = Mobatnew::select('kandungan')->where('kd_obat', $obat)->first();

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
                ->where('resep_keluar_h.norm', $norm)
                ->where('resep_keluar_r.kdobat', $obat)
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
                ->where('resep_keluar_h.norm', $norm)
                ->where('resep_keluar_r.kdobat', $obat)
                ->where('new_masterobat.kandungan', $kandungan)
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

    public function ambilHistory(Request $request)
    {
        $data['req'] = $request->all();
        $data['data'] = Resepkeluarheder::where('noresep_asal', $request->noresep)
            ->with([
                'rincian',
                'rincianracik',
                'asalpermintaanresep.mobat:kd_obat,nama_obat,satuan_k,kandungan',
                'asalpermintaanracikan.mobat:kd_obat,nama_obat,satuan_k,kandungan',
            ])
            ->get();
        return new JsonResponse($data);
    }

    public function getPegawaiFarmasi()
    {
        $data = Pegawai::select('nama', 'id', 'kdpegsimrs')
            ->where('aktif', '=', 'AKTIF')

            ->where('ruang', '=', 'R00025')


            ->get();

        return new JsonResponse($data);
    }

    public function simPelIOnfOb(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $data = PelayananInformasiObat::updateOrCreate(
                [
                    'norm' => $request->norm,
                    'noreg' => $request->noreg,
                ],
                [
                    'tanggal' => $request->tanggal,
                    'metode' => $request->metode,
                    'nama_penanya' => $request->nama_penanya,
                    'status_penanya' => $request->status_penanya,
                    'tlp_penanya' => $request->tlp_penanya,
                    'umur_pasien' => $request->umur_pasien,
                    'kehamilan' => $request->kehamilan,
                    'kasus_khusus' => $request->kasus_khusus,
                    'jenis_kelamin' => $request->jenis_kelamin,
                    'menyusui' => $request->menyusui,
                    'uraian_pertanyaan' => $request->uraian_pertanyaan,
                    'jenis_pertanyaan' => $request->jenis_pertanyaan,
                    'jawaban' => $request->jawaban,
                    'referensi' => $request->referensi,
                    'apoteker' => $request->apoteker,
                    'user_input' => $request->user_input,

                ]
            );
            if (!$data) {
                return new JsonResponse(['message' => 'Pelayanan Infromasi Obat gagal disimpan'], 410);
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Pelayanan Infromasi Obat sudah disimpan',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 410);
        }
    }
}
