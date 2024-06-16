<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Eresep;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Helpers\FormatingHelper;
use App\Helpers\HargaHelper;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;

;

class EresepController extends Controller
{
    public function listresepbynorm()
    {
        $history = Resepkeluarheder::with(
            [
                'rincian.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'rincianracik.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'permintaanresep.aturansigna:signa,jumlah',
                'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,kekuatan_dosis,status_kronis,kelompok_psikotropika',
                'poli',
                'info',
                'ruanganranap',
                'sistembayar',
                'sep:rs1,rs8',
                'dokter:kdpegsimrs,nama',
                'datapasien' => function ($quer) {
                    $quer->select(
                        'rs1',
                        'rs2 as nama',
                        'rs46 as noka',
                        'rs16 as tgllahir',
                        DB::raw('concat(rs4," KEL ",rs5," RT ",rs7," RW ",rs8," ",rs6," ",rs11," ",rs10) as alamat'),
                    );
                }
            ]
        )
            ->where('norm', request('norm'))
            ->orderBy('tgl_permintaan', 'DESC')
            ->get()
            ->chunk(10);
        // return new JsonResponse(request()->all());
        $collapsed = $history->collapse();


        return new JsonResponse($collapsed->all());
    }

    public function copiresep(Request $request)
    {
        $request->validate([
            'kodeobat' => 'required',
            // 'jumlah' => 'required',
            'kdruangan' => 'required',
        ]);
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
                if ($request->tiperesep === 'normal') {
                    $iter_expired =  null;
                    $iter_jml =  null;
                }
            } else {
                $tiperesep =  'normal';
                $iter_expired =  null;
                $iter_jml =  null;
            }
            $cekjumlahstok = Stokreal::select('kdobat', DB::raw('sum(jumlah) as jumlahstok'))
                ->where('kdobat', $request->kodeobat)
                ->where('kdruang', $request->kodedepo)
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
                            ->where('resep_keluar_h.depo', $request->kodedepo)
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
                            ->where('resep_keluar_h.depo', $request->kodedepo)
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

                            ->where('permintaan_h.tujuan', $request->kodedepo)
                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                            ->groupBy('permintaan_r.kdobat');
                    },
                    'persiapanrinci' => function ($res) use ($request) {
                        $res->select(
                            'persiapan_operasi_rincis.kd_obat',

                            DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as jumlah'),
                        )
                            ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                            ->whereIn('persiapan_operasis.flag', ['', '1'])
                            ->groupBy('persiapan_operasi_rincis.kd_obat');
                    },
                ])
                ->orderBy('tglexp')
                ->groupBy('kdobat')
                ->get();
            $wew = collect($cekjumlahstok)->map(function ($x, $y) use ($request) {
                $total = $x->jumlahstok ?? 0;
                $jumlahper = $request->kodedepo === 'Gd-04010103' ? $x['persiapanrinci'][0]->jumlah ?? 0 : 0;
                $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
                $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
                $permintaanobatrinci = $x['permintaanobatrinci'][0]->allpermintaan ?? 0; // mutasi antar depo
                $x->alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci - (float)$jumlahper;
                return $x;
            });
            // $jumlahstok = $cekjumlahstok[0]->jumlahstok;
            $alokasi = $wew[0]->alokasi ?? 0;
            // return new JsonResponse([
            //     'alokasi' => $alokasi,
            //     'wew' => $wew,
            // ]);
            if ($request->jenisresep == 'Racikan') {
                if ($request->jumlah > $alokasi) {
                    return new JsonResponse(['message' => 'Maaf Stok Alokasi Tidak Mencukupi...!!!', 'cek' => $cekjumlahstok], 500);
                }
            } else {

                if ($request->jumlah_diminta > $alokasi) {
                    return new JsonResponse(['message' => 'Maaf Stok Alokasi Tidak Mencukupi...!!!', 'cek' => $cekjumlahstok], 500);
                }
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
                            'kandungan' => $request->kandungan ?? '',
                            'fornas' => $request->fornas ?? '',
                            'forkit' => $request->forkit ?? '',
                            'generik' => $request->generik ?? '',
                            'r' => 500,
                            'hpp' => $harga,
                            'harga_jual' => $hargajualx,
                            'kode108' => $request->kode108,
                            'uraian108' => $request->uraian108,
                            'kode50' => $request->kode50,
                            'uraian50' => $request->uraian50,
                            'stokalokasi' => $request->stokalokasi,
                            'dosisobat' => $request->dosisobat ?? 0,
                            'dosismaksimum' => $request->dosismaksimum ?? 0, // dosis resep
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
                            'kandungan' => $request->kandungan ?? '',
                            'fornas' => $request->fornas ?? '',
                            'forkit' => $request->forkit ?? '',
                            'generik' => $request->generik ?? '',
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
                        'kandungan' => $request->kandungan ?? '',
                        'fornas' => $request->fornas ?? '',
                        'forkit' => $request->forkit ?? '',
                        'generik' => $request->generik ?? '',
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
                'message' => 'rolled back ada kesalahan'
            ], 410);
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
