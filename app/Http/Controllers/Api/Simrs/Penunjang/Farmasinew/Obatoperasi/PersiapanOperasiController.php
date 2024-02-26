<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Obatoperasi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use App\Models\SistemBayar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersiapanOperasiController extends Controller
{
    // get area
    public function getPermintaan()
    {
        $flag = request('flag') ?? [];
        $data = PersiapanOperasi::with('rinci.obat:kd_obat,nama_obat,satuan_k', 'pasien:rs1,rs2')
            ->whereIn('flag', $flag)
            ->whereBetween('tgl_permintaan', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->orderBy('tgl_permintaan', "desc")
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function getPermintaanForDokter()
    {
        $belum = PersiapanOperasi::with([
            'rinci' => function ($ri) {
                $ri->with('obat:kd_obat,nama_obat,satuan_k');
            },
            'pasien:rs1,rs2',
            'userminta:kdpegsimrs,nama',
            'userdist:kdpegsimrs,nama',
            'dokter:kdpegsimrs,nama',
        ])

            ->where('flag', '=', '2')
            ->where('noreg', '=', request('noreg'))
            ->whereBetween('tgl_permintaan', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->orderBy('tgl_permintaan', "desc")
            ->get();
        $sudah = PersiapanOperasi::select(
            'persiapan_operasi_rincis.*',
            'persiapan_operasis.id as headid',
            'persiapan_operasis.noreg',
            'persiapan_operasis.norm',
            'persiapan_operasis.tgl_resep',
            'persiapan_operasis.dokter',
            'persiapan_operasis.user_minta',
            'persiapan_operasis.flag',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
        )
            ->with([
                'pasien:rs1,rs2',
                'userminta:kdpegsimrs,nama',
                'dokter:kdpegsimrs,nama',
            ])
            // ->leftJoin('persiapan_operasi_rincis', function ($q) {
            //     $q->on('persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
            //         ->leftJoin('new_masterobat', 'persiapan_operasi_rincis.kd_obat', '=', 'new_masterobat.kd_obat');
            // })
            ->leftJoin('persiapan_operasi_rincis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
            ->leftJoin('new_masterobat', 'persiapan_operasi_rincis.kd_obat', '=', 'new_masterobat.kd_obat')
            ->whereIn('persiapan_operasis.flag', ['2', '3'])
            ->where('persiapan_operasis.noreg', '=', request('noreg'))
            // ->when(request('noresep'), function ($q) {
            //     $q->where('persiapan_operasi_rincis.noresep', request('noresep'));
            // })
            ->where('persiapan_operasi_rincis.noresep', '!=', '')
            ->whereBetween('persiapan_operasis.tgl_permintaan', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->orderBy('persiapan_operasis.tgl_permintaan', "desc")
            ->get();
        return new JsonResponse([
            'belum' => $belum,
            'sudah' => $sudah,
        ]);
    }

    // post placed down below
    public function simpanPermintaan(Request $request)
    {
        return new JsonResponse($request->all());
    }
    public function simpanDistribusi(Request $request)
    {
        // cek stok
        $cek = $request->rinci;
        $st = [];
        if (count($cek) > 0) {
            foreach ($cek as $key) {
                $stok = Stokreal::selectRaw('*, sum(jumlah) as total')
                    ->where('kdobat', $key['kd_obat'])
                    ->where('kdruang', 'Gd-04010103')
                    ->where('jumlah', '>', 0)
                    ->groupBy('kdobat')
                    ->first();
                if ($stok->total < $key['jumlah_distribusi']) {
                    $obat = Mobatnew::where('kd_obat', $key['kd_obat'])->first();
                    return new JsonResponse([
                        'message' => 'stok ' . $obat->nama_obat . ' tidak mencukupi, stok tersisa' . $stok->total . ' silahkan kurangi jumlah distribusi'
                    ], 410);
                }
            }
        }
        try {
            DB::beginTransaction();
            $rinci = $request->rinci;
            $user = FormatingHelper::session_user();
            $kode = $user['kodesimrs'];

            // pastikan ada data
            if (count($rinci) > 0) {
                $data = [];
                foreach ($rinci as $key) {

                    // update rinci
                    $dataRinci = PersiapanOperasiRinci::find($key['id']);
                    if (!$dataRinci) {
                        return new JsonResponse(['message' => 'Data Rinci tidak ditemukan']);
                    }
                    $dataRinci->jumlah_distribusi = $key['jumlah_distribusi'];
                    $dataRinci->save();

                    // lanjut ngisi data by fifo
                    $distribusi = (float)$key['jumlah_distribusi'];

                    // pastikan jumlah distribusi lebih dari 0
                    if ($distribusi > 0) {
                        $stok = Stokreal::where('kdobat', $key['kd_obat'])
                            ->where('kdruang', 'Gd-04010103')
                            ->where('jumlah', '>', 0)
                            ->orderBy('tglExp', 'ASC')
                            ->get();
                        $index = 0;

                        while ($distribusi > 0) {
                            $ada = (float)$stok[$index]->jumlah;
                            if ($ada < $distribusi) {
                                $temp = [
                                    'nopermintaan' => $key['nopermintaan'],
                                    'kd_obat' => $key['kd_obat'],
                                    'nopenerimaan' => $stok[$index]->nopenerimaan,
                                    'jumlah' => $ada,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                                $data[] = $temp;
                                $sisa = $distribusi - $ada;
                                $index += 1;
                                $distribusi = $sisa;
                            } else {
                                $temp = [
                                    'nopermintaan' => $key['nopermintaan'],
                                    'kd_obat' => $key['kd_obat'],
                                    'nopenerimaan' => $stok[$index]->nopenerimaan,
                                    'jumlah' => $distribusi,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                                $data[] = $temp;
                                $distribusi = 0;
                            }
                        }
                    }
                }
            }

            // update header
            $head = PersiapanOperasi::where('nopermintaan', $request->nopermintaan)->first();
            if (!$head) {
                return new JsonResponse(['message' => 'Data Header tidak ditemukan'], 410);
            }
            $head->flag = '2';
            $head->user_distribusi = $kode;
            $head->tgl_distribusi = date('Y-m-d H:i:s');
            $head->save();

            //simpan ditribusi
            $dist = PersiapanOperasiDistribusi::insert($data); // ini hasilnya kalo berhasil itu true
            if (!$dist) {
                return new JsonResponse(['message' => 'Data gagal disimpan!'], 410);
            }
            // update stok
            $dataDist = PersiapanOperasiDistribusi::where('nopermintaan', $request->nopermintaan)->get();
            foreach ($dataDist as $rin) {
                $stok = Stokreal::where('kdobat', $rin['kd_obat'])
                    ->where('kdruang', 'Gd-04010103')
                    ->where('nopenerimaan', $rin['nopenerimaan'])
                    ->first();

                if ($stok->jumlah <= 0) {
                    return new JsonResponse(['message' => 'Data stok kurang dari 0'], 410);
                }
                $sisa = $stok->jumlah - $rin['jumlah'];
                $stok->jumlah = $sisa;
                $stok->save();
            }

            DB::commit();

            return new JsonResponse([
                'rinci' => $rinci,
                'data' => $dist,
                'head' => $head,
                'message' => 'Data berhasil di simpan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan...!!!',
                'result' => $e,
            ], 410);
        }
    }
    public function simpanEresep(Request $request)
    {
        // cek user
        $user = FormatingHelper::session_user();
        if ($user['kdgroupnakes'] != '1') {
            return new JsonResponse(['message' => 'Maaf Anda Bukan Dokter...!!!'], 500);
        }

        // buat no resep
        if ($request->noresep === '' || $request->noresep === null) {
            DB::connection('farmasi')->select('call resepkeluardepook(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('depook')->get();
            $wew = $x[0]->depook;
            $noresep = FormatingHelper::resep($wew, 'D-KO');
        } else {
            $noresep = $request->noresep;
        }
        $head =            [
            // 'noresep' => $noresep,
            'noreg' => $request->noreg,
            'norm' => $request->norm,
            'tgl_permintaan' => date('Y-m-d H:i:s'),
            'tgl_kirim' => date('Y-m-d H:i:s'),
            'depo' => 'Gd-04010103',
            'ruangan' => 'R-0101025',
            'dokter' =>  $user['kodesimrs'],
            'sistembayar' => $request->sistembayar,
            'diagnosa' => $request->diagnosa ?? '',
            'kodeincbg' => $request->kodeincbg ?? '',
            'uraianinacbg' => $request->uraianinacbg ?? '',
            'tarifina' => $request->tarifina ?? '',
            'tiperesep' => $request->tiperesep ?? 'normal',
            'tagihanrs' => $request->tagihanrs ?? 0,
            'flag' => '10',
        ];
        $obat = $request->obats;
        $rinci = [];
        $noper = [];
        if (count($obat) > 0) {
            foreach ($obat as $key) {
                // cari harga
                $gudang = ['Gd-05010100', 'Gd-03010100'];
                $cariharga = Stokreal::select(DB::raw('max(harga) as harga'))
                    ->whereIn('kdruang', $gudang)
                    ->where('kdobat', $key['kd_obat'])
                    ->orderBy('tglpenerimaan', 'desc')
                    ->limit(5)
                    ->get();
                $harga = $cariharga[0]->harga;
                $sistemBayar = SistemBayar::select('groups')->where('rs1', $request->kodesistembayar)->first();
                $gr = $sistemBayar->groups ?? '';
                if ($gr == 1) {
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
                $masterObat = Mobatnew::where('kd_obat', $key['kd_obat'])->first();
                $rin = [
                    'noreg' => $request->noreg,
                    'noresep' => $noresep,
                    'kdobat' => $masterObat->kd_obat,
                    'kandungan' => $masterObat->kandungan,
                    'fornas' => $masterObat->status_fornas,
                    'forkit' => $masterObat->status_forkid,
                    'generik' => $masterObat->status_generik,
                    'kode108' => $masterObat->kode108,
                    'uraian108' => $masterObat->uraian108,
                    'kode50' => $masterObat->kode50,
                    'uraian50' => $masterObat->uraian50,
                    'stokalokasi' => $request->stokalokasi ?? 0,
                    'r' => 300,
                    'jumlah' => $key['jumlah_resep'],
                    'hpp' => $harga ?? 0,
                    'hargajual' => $hargajualx,
                    'aturan' => '-',
                    'konsumsi' => 1,
                    'keterangan' => 'Di pakai untuk operasi' ?? '',
                    'created_at' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d'),
                ];
                $rinci[] = $rin;
                $noper[] = $key['nopermintaan'];
                // update rinci no resep
                $adaRinci = PersiapanOperasiRinci::find($key['id']);
                if (!$adaRinci) {
                    return new JsonResponse(['message' => 'update no resep gagal']);
                }
                $adaRinci->noresep = $noresep;
                $adaRinci->jumlah_resep = $key['jumlah_resep'];
                $adaRinci->save();
            }
        } else {

            return new JsonResponse(['message' => 'Tidak ada Obat untuk disimpan'], 410);
        }
        $header = Resepkeluarheder::updateOrCreate(['noresep' => $noresep], $head);
        if (!$header) {
            return new JsonResponse(['message' => 'Resep gagal di buat'], 410);
        }
        // insert permintaan resep
        $insRinci = Permintaanresep::insert($rinci);

        $unoper = array_unique($noper);
        // cek untuk update header
        foreach ($unoper as $key) {
            $temp = PersiapanOperasiRinci::where('noresep', '')->where('nopermintaan', $key)->get();
            if (count($temp) === 0) {
                $he = PersiapanOperasi::where('nopermintaan', $key)->first();
                $he->flag = '3';
                $he->save();
            }
        }
        return new JsonResponse([
            'message' => 'resep sudah di dimpan',
            'header' => $header,
            'rinci' => $insRinci,
            'noresep' => $noresep,
            // 'header' => $head,
            // 'rinci' => $rinci,
            // 'noper' => $noper,
            // 'unoper' => $unoper,

        ]);
    }
    public function selesaiEresep(Request $request)
    {
        $data = PersiapanOperasi::find($request->id);
        if ($data) {
            $data->flag = '3';
            $data->save();
            return new JsonResponse([
                'message' => 'Resep untuk nomor permintaan ' . $request->nopermintaan . ' sudah selesai'
            ]);
        }
        return new JsonResponse([
            'message' => 'Nomor permintaan ' . $request->nopermintaan . ' gagal diselesaikan, tidak adakan diterima oleh depo'
        ], 410);
    }
    public function batalObatResep(Request $request)
    {
        $head = PersiapanOperasi::find($request->headid);
        $data = PersiapanOperasiRinci::find($request->id);
        $flag = (int) $head->flag;
        if ($flag <= 3) {
            $data->noresep = '';
            $data->jumlah_resep = 0;
            $data->save();
        } else {
            return new JsonResponse(['message' => 'Tidak boleh di hapus dari resep karena sudah di proses di apotek'], 410);
        }
        return new JsonResponse([
            'message' => 'Obat sudah di hapus dari resep',
            'head' => $head,
            'data' => $data,
            // 'req' => $request->all(),
        ]);
    }
    public static function resepKeluar($key)
    {
        $rinci = [];
        // cari harga
        $gudang = ['Gd-05010100', 'Gd-03010100'];
        $cariharga = Stokreal::select(DB::raw('max(harga) as harga'))
            ->whereIn('kdruang', $gudang)
            ->where('kdobat', $key['kd_obat'])
            ->orderBy('tglpenerimaan', 'desc')
            ->limit(5)
            ->get();
        $harga = $cariharga[0]->harga;
        $sistemBayar = SistemBayar::select('groups')->where('rs1', $request->kodesistembayar)->first();
        $gr = $sistemBayar->groups ?? '';
        if ($gr == 1) {
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
        $masterObat = Mobatnew::where('kd_obat', $key['kd_obat'])->first();
        $dist = PersiapanOperasiDistribusi::where('kd_obat', $key['kd_obat'])
            ->where('nopermintaan', $key['nopermintaan'])
            ->orderBy('id', 'ASC')
            ->get();
        $index = 0;
        $masuk = (float) $key['jumlah_resep'];
        while ($masuk > 0) {
            $ada = (float)$dist[$index]->jumlah;
            if ($ada < $masuk) {
                $rin = [
                    'noreg' => $request->noreg,
                    // 'noresep' => $noresep,
                    'kdobat' => $masterObat->kd_obat,
                    'kandungan' => $masterObat->kandungan,
                    'fornas' => $masterObat->status_fornas,
                    'forkit' => $masterObat->status_forkid,
                    'generik' => $masterObat->status_generik,
                    'kode108' => $masterObat->kode108,
                    'uraian108' => $masterObat->uraian108,
                    'kode50' => $masterObat->kode50,
                    'uraian50' => $masterObat->uraian50,
                    'stokalokasi' =>  0,
                    'r' => 300,
                    'jumlah' => $ada,
                    'hpp' => $harga ?? 0,
                    'hargajual' => $hargajualx,
                    'aturan' => 'dipakai untuk operasi',
                    'konsumsi' => 1,
                    'keterangan' => 'Di pakai untuk operasi' ?? '',
                    'user' => $user['kodesimrs']
                ];
                $rinci[] = $rin;
                $sisa = $masuk - $ada;
                $index += 1;
                $masuk = $sisa;
            } else {
                $rin = [
                    'noreg' => $request->noreg,
                    // 'noresep' => $noresep,
                    'kdobat' => $masterObat->kd_obat,
                    'kandungan' => $masterObat->kandungan,
                    'fornas' => $masterObat->status_fornas,
                    'forkit' => $masterObat->status_forkid,
                    'generik' => $masterObat->status_generik,
                    'kode108' => $masterObat->kode108,
                    'uraian108' => $masterObat->uraian108,
                    'kode50' => $masterObat->kode50,
                    'uraian50' => $masterObat->uraian50,
                    'stokalokasi' =>  0,
                    'r' => 300,
                    'jumlah' => $masuk,
                    'hpp' => $harga ?? 0,
                    'hargajual' => $hargajualx,
                    'aturan' => 'dipakai untuk operasi',
                    'konsumsi' => 1,
                    'keterangan' => 'Di pakai untuk operasi' ?? '',
                    'user' => $user['kodesimrs']
                ];
                $rinci[] = $rin;

                $masuk = 0;
            }
        }
        return $rinci;
    }
    public function terimaPengembalian(Request $request)
    {
        try {
            DB::beginTransaction();
            $rinci = $request->rinci;
            $user = FormatingHelper::session_user();
            $kode = $user['kodesimrs'];
            $resepKeluar = [];
            if (count($rinci) > 0) {
                foreach ($rinci as $key) {
                    // update data rinci
                    $kembali = (float)$key['jumlah_kembali'];
                    $dataDistribusi = PersiapanOperasiDistribusi::where('kd_obat', $key['kd_obat'])
                        ->where('nopermintaan', $key['nopermintaan'])
                        ->orderBy('id', 'DESC')
                        ->get();

                    if ($kembali > 0) {
                        $dataRinci = PersiapanOperasiRinci::find($key['id']);
                        if (!$dataRinci) {
                            return new JsonResponse(['message' => 'Data Rinci tidak ditemukan']);
                        }
                        $dataRinci->jumlah_kembali = $key['jumlah_kembali'];
                        $dataRinci->save();
                        // update data distribusi

                        $index = 0;
                        while ($kembali > 0) {
                            $ada = (float)$dataDistribusi[$index]->jumlah;
                            if ($ada < $kembali) {
                                $dataDistribusi[$index]->jumlah_retur = $ada;
                                $dataDistribusi[$index]->tgl_retur = date('Y-m_d H:i:s');
                                $dataDistribusi[$index]->save();

                                // update stok
                                $stok = Stokreal::where('kdobat', $dataDistribusi[$index]->kd_obat)
                                    ->where('nopenerimaan', $dataDistribusi[$index]->nopenerimaan)
                                    ->where('kdruang', 'Gd-04010103')
                                    ->first();

                                $totalStok = (float)$stok->jumlah + $ada;
                                $stok->jumlah = $totalStok;
                                $stok->save();

                                $sisa = $kembali - $ada;
                                $index += 1;
                                $kembali = $sisa;
                            } else {

                                $dataDistribusi[$index]->jumlah_retur = $kembali;
                                $dataDistribusi[$index]->tgl_retur = date('Y-m_d H:i:s');
                                $dataDistribusi[$index]->save();

                                // update stok
                                $stok = Stokreal::where('kdobat', $dataDistribusi[$index]->kd_obat)
                                    ->where('nopenerimaan', $dataDistribusi[$index]->nopenerimaan)
                                    ->where('kdruang', 'Gd-04010103')
                                    ->first();
                                $totalStok = (float)$stok->jumlah + $kembali;
                                $stok->jumlah = $totalStok;
                                $stok->save();

                                $kembali = 0;
                            }
                        }
                    } else if ($kembali == 0) {

                        foreach ($dataDistribusi as $key) {
                            $key['tgl_retur'] = date('Y-m_d H:i:s');
                            $key->save();
                        }
                    }
                    $resepKeluar = self::resepKeluar($key);
                }
            }

            // update header
            $head = PersiapanOperasi::where('nopermintaan', $request->nopermintaan)->first();
            if (!$head) {
                return new JsonResponse(['message' => 'Data Header tidak ditemukan'], 410);
            }
            $head->flag = '4';
            $head->save();

            return new JsonResponse([
                'rinci' => $rinci,
                'head' => $head,
                'resepKeluar' => $resepKeluar,
                'dataDistribusi' => $dataDistribusi ?? [],
                'message' => 'Data berhasil di simpan'
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan...!!!',
                'result' => $e,
            ], 410);
        }
    }
}
