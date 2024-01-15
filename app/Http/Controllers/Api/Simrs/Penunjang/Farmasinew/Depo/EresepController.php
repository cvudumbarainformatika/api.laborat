<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EresepController extends Controller
{

    public function conterracikan()
    {
        $conter = Permintaanresepracikan::where('noresep', request('noresep'))
            ->groupby('noresep', 'namaracikan')
            ->count();
        $conterx =  (int) $conter + 1;
        $contery = 'Racikan ' . $conterx;
        return new JsonResponse($contery);
    }

    public function lihatstokobateresepBydokter()
    {
        $groupsistembayar = request('groups');
        if ($groupsistembayar == '1') {
            $sistembayar = ['SEMUA', 'BPJS'];
        } else {
            $sistembayar = ['SEMUA', 'UMUM'];
        }
        $cariobat = Stokreal::select(
            'stokreal.kdobat as kdobat',
            'stokreal.kdruang as kdruang',
            'new_masterobat.nama_obat as namaobat',
            'new_masterobat.kandungan as kandungan',
            'new_masterobat.bentuk_sediaan as bentuk_sediaan',
            'new_masterobat.satuan_k as satuankecil',
            'new_masterobat.status_fornas as fornas',
            'new_masterobat.status_forkid as forkit',
            'new_masterobat.status_generik as generik',
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
                            'resep_keluar_r.kdobat as kdobat',
                            'resep_keluar_h.depo as kdruang',
                            DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                        )
                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_keluar_r.noresep')
                            ->where('resep_keluar_h.depo', request('kdruang'))
                            ->where('flag', '!=', '3')
                            ->groupBy('resep_keluar_r.kdobat');
                    },
                    'transracikan' => function ($transracikan) {
                        $transracikan->select(
                            'resep_keluar_racikan_r.kdobat as kdobat',
                            'resep_keluar_h.depo as kdruang',
                            DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                        )
                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_keluar_racikan_r.noresep')
                            ->where('resep_keluar_h.depo', request('kdruang'))
                            ->where('flag', '!=', '3')
                            ->groupBy('resep_keluar_racikan_r.kdobat');
                    },
                ]
            )
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->where('stokreal.kdruang', request('kdruang'))
            ->whereIn('new_masterobat.sistembayar', $sistembayar)
            ->where(function ($query) {
                $query->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('new_masterobat.kandungan', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('stokreal.kdobat', 'LIKE', '%' . request('q') . '%');
            })
            ->groupBy('stokreal.kdobat')
            ->get();
        $wew = collect($cariobat)->map(function ($x, $y) {
            $total = $x->total ?? 0;
            $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
            $x->alokasi = $total - $jumlahtrans + $jumlahtransx;
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
                'depo' => $request->kodedepo,
                'ruangan' => $request->kdruangan,
                'dokter' =>  $user['kodesimrs'],
                'sistembayar' => $request->sistembayar,
                'diagnosa' => $request->diagnosa,
                'kodeincbg' => $request->kodeincbg,
                'uraianinacbg' => $request->uraianinacbg,
                'tarifina' => $request->tarifina,
                'tagihanrs' => $request->tagihanrs ?? 0,
            ]
        );

        if (!$simpan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $gudang = ['Gd-05010100', 'Gd-03010100'];
        $cariharga = Stokreal::select(DB::raw('max(harga) as harga'))
            ->whereIn('kdruang', $gudang)
            ->where('kdobat', $request->kodeobat)
            ->orderBy('tglpenerimaan', 'desc')
            ->limit(5)
            ->get();
        $harga = $cariharga[0]->harga;

        if ($request->groupsistembayar == 1) {
            if ($harga <= 50000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 28 / (int) 100;
            } elseif ($harga > 50000 && $harga <= 250000) {
                $hargajualx = (int) $harga + ((int) $harga * (int) 26 / (int) 100);
            } elseif ($harga > 250000 && $harga <= 500000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 21 / (int) 100;
            } elseif ($harga > 500000 && $harga <= 1000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 16 / (int)100;
            } elseif ($harga > 1000000 && $harga <= 5000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 11 /  (int)100;
            } elseif ($harga > 5000000 && $harga <= 10000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 9 / (int) 100;
            } elseif ($harga > 10000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 7 / (int) 100;
            }
        } else {
            $hargajualx = (int) $harga + (int) $harga * (int) 25 / (int)100;
        }

        if ($request->jenisresep == 'Racikan') {
            if ($request->jenisracikan == 'DTD') {
                $simpandtd = Permintaanresepracikan::create(
                    [
                        'noreg' => $request->noreg,
                        'noresep' => $noresep,
                        'namaracikan' => $request->namaracikan,
                        'tiperacikan' => $request->jenisracikan,
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
                        'keteranganx' => $request->keteranganx, // keterangan obat
                        'user' => $user['kodesimrs']
                    ]
                );
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
                        'keteranganx' => $request->keteranganx,
                        'user' => $user['kodesimrs']
                    ]
                );
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
        }
        return new JsonResponse([
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
        // return $rm;
        $listresep = Resepkeluarheder::select(
            'farmasi.resep_keluar_h.*',
            'kepegx.pegawai.nama as dokter',
        )
            ->leftjoin('kepegx.pegawai', 'farmasi.resep_keluar_h.dokter', 'kepegx.pegawai.kdpegsimrs')
            ->with(
                [
                    'rincian.mobat:kd_obat,nama_obat',
                    'permintaanresep.mobat:kd_obat,nama_obat',
                    'permintaanracikan.mobat:kd_obat,nama_obat',
                    'poli',
                    'sistembayar',
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
                    $wew->whereIn('farmasi.resep_keluar_h.norm', $rm);
                })
                    ->orWhere('farmasi.resep_keluar_h.noresep', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('farmasi.resep_keluar_h.norm', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('farmasi.resep_keluar_h.noreg', 'LIKE', '%' . request('q') . '%');
            })
            ->where('farmasi.resep_keluar_h.depo', request('kddepo'))
            ->where('farmasi.resep_keluar_h.flag', '!=', '')
            ->orderBy('farmasi.resep_keluar_h.tgl_permintaan', 'DESC')
            ->paginate(request('per_page'));
        return new JsonResponse($listresep);
    }

    public function kirimresep(Request $request)
    {
        $kirimresep = Resepkeluarheder::where('noresep', $request->noresep)->first();
        $kirimresep->flag = '1';
        $kirimresep->tgl_kirim = date('Y-m-d H:i:s');
        $kirimresep->save();

        return new JsonResponse(['message' => 'Resep Berhasil Dikirim Kedepo Farmasi...!!!'], 200);
    }

    public function eresepobatkeluar(Request $request)
    {
        $cekjumlahstok = Stokreal::select(DB::raw('sum(jumlah) as jumlahstok'))
            ->where('kdobat', $request->kodeobat)->where('kdruang', $request->kodedepo)
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
            ->where('kdobat', $request->kodeobat)
            ->orderBy('tglpenerimaan', 'desc')
            ->limit(5)
            ->get();
        $harga = $cariharga[0]->harga;

        $jmldiminta = $request->jumlah;
        $caristok = Stokreal::where('kdobat', $request->kodeobat)->where('kdruang', $request->kodedepo)
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
                    $simpanrinci = Resepkeluarrinci::create(
                        [
                            'noreg' => $request->noreg,
                            'noresep' => $request->noresep,
                            'tiperacikan' => $request->tiperacikan,
                            'namaracikan' => $request->namaracikan,
                            'kdobat' => $request->kodeobat,
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
                            'kdobat' => $request->kodeobat,
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
                    $simpanrinci = Resepkeluarrinci::create(
                        [
                            'noreg' => $request->noreg,
                            'noresep' => $request->noresep,
                            'namaracikan' => $request->namaracikan,
                            'kdobat' => $request->kodeobat,
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
                            'kdobat' => $request->kodeobat,
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
}
