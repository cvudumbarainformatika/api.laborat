<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\PenyesuaianStok;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use App\Models\Simrs\Ranap\Mruangranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokrealController extends Controller
{
    public static function stokreal($nopenerimaan, $request)
    {
        //return ($request->kdobat);
        $simpanstokreal = Stokrel::updateOrCreate(
            [
                'nopenerimaan' => $nopenerimaan,
                'kdobat' => $request->kdobat,
                'kdruang' => $request->kdruang,
                'nobatch' => $request->no_batch,
                'tglexp' => $request->tgl_exp,
                'harga' => $request->harga_netto_kecil
            ],
            [
                'tglpenerimaan' => $request->tglpenerimaan,
                'jumlah' => $request->jml_terima_k,
                'flag' => 1

            ]
        );
        if (!$simpanstokreal) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }
        return 200;
    }

    public static function updatestokgudangdandepo($request)
    {
        $jml_dikeluarkan = (int) $request->jumlah_diverif;
        $totalstok = Stokrel::select(DB::raw('sum(stokreal.jumlah) as totalstok'))
            ->where('kdobat', $request->kdobat)
            ->where('kdruang', $request->kdruang)
            ->where('jumlah', '!=', 0)
            ->groupBy('kdobat', 'kdruang')
            ->first();

        $totalstokx = (int) $totalstok->totalstok;
        if ($jml_dikeluarkan > $totalstokx) {
            return new JsonResponse(['message' => 'Maaf Stok Anda Tidak Mencukupi...!!!'], 500);
        }

        $caristokgudang = Stokrel::where('kdobat', $request->kdobat)
            ->where('kdruang', $request->kdruang)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();

        foreach ($caristokgudang as $val) {
            $jmlstoksatuan = $val['jumlah'];
            $nopenerimaan = $val['nopenerimaan'];
        }

        return [$nopenerimaan, $jmlstoksatuan];
    }

    public function insertsementara(Request $request)
    {
        $sementara = date('ymdhis');
        $ruang = $request->kdruang;
        if ($ruang === 'Gd-05010100') {
            $kdruang = 'GO';
        } elseif ($ruang === 'Gd-03010100') {
            $kdruang = 'FS';
        } elseif ($ruang === 'Gd-04010102') {
            $kdruang = 'DRI';
        } elseif ($ruang === 'Gd-05010101') {
            $kdruang = 'DRJ';
        } else {
            $kdruang = 'DKO';
        }
        $notrans = $sementara . '-' . $kdruang;

        // $simpanstok = Stokrel::create(
        //     [
        //         'nopenerimaan' => $request->notrans ?? $notrans,
        //         'tglpenerimaan' => $request->tglpenerimaan ?? date('Y-m-d H:i:s'),
        //         'kdobat' => $request->kdobat,
        //         'jumlah' => $request->jumlah,
        //         'kdruang' => $request->kdruang,
        //         'harga' => $request->harga ?? '',
        //         'tglexp' => $request->tglexp ?? '',
        //         'nobatch' => $request->nobatch ?? '',
        //     ]
        // );

        $simpanstokopname = Stokopname::create(
            [
                'nopenerimaan' => $request->notrans ?? $notrans,
                'tglpenerimaan' => $request->tglpenerimaan ?? date('Y-m-d H:i:s'),
                'kdobat' => $request->kdobat,
                'jumlah' => $request->jumlah,
                'kdruang' => $request->kdruang,
                'harga' => $request->harga ?? '',
                'tglexp' => $request->tglexp ?? '',
                'nobatch' => $request->nobatch ?? '',
                'tglopname' => '2023-12-31 23:59:59'
            ]
        );
        return new JsonResponse(
            [
                'datastok' => $simpanstokopname,
                'message' => 'Stok Berhasil Disimpan...!!!'
            ],
            200
        );
    }
    public function updatestoksementara(Request $request)
    {
        $cari = Stokopname::where('id', $request->id)->first();
        $cari->jumlah = $request->jumlah ?? '';
        $cari->harga = $request->harga ?? '';
        $cari->tglexp = $request->tglexp ?? '';
        $cari->nobatch = $request->nobatch ?? '';
        $cari->save();

        return new JsonResponse(['message' => 'Stok Berhasil Disimpan...!!!'], 200);
    }
    public function updatehargastok(Request $request)
    {
        $cari = Stokreal::where('id', $request->id)->first();
        $penyesuaian = PenyesuaianStok::create([
            'tgl_penyesuaian' => date('Y-m-d H:i:s'),
            'stokreal_id' => $request->id,
            'nopenerimaan' => $request->nopenerimaan,
            'kdobat' => $cari->kdobat,
            'awal' => $request->awal,
            'penyesuaian' => $request->penyesuaian,
            'akhir' => $request->akhir,

        ]);

        $cari->jumlah = $request->akhir ?? 0;
        $cari->harga = $request->harga ?? 0;
        $cari->tglexp = $request->tglexp ?? '';
        $cari->nobatch = $request->nobatch ?? '';
        $cari->save();

        return new JsonResponse(['message' => 'Stok Berhasil Disimpan...!!!'], 200);
    }

    public function liststokreal()
    {
        $kdruang = request('kdruang');

        // $today = date('Y-m-d');
        // $dToday = date_create($today);
        // $dFrom = date_create(request('from'));
        // $diff = date_diff($dToday, $dFrom);
        // if ($diff->m === 0) {
        //     $stokreal = Stokreal::select(
        //         'stokreal.*',
        //         'new_masterobat.kd_obat',
        //         'new_masterobat.nama_obat',
        //         'new_masterobat.satuan_k',
        //         'new_masterobat.status_fornas',
        //         'new_masterobat.status_forkid',
        //         'new_masterobat.status_generik',
        //         'new_masterobat.gudang',
        //         DB::raw('sum(stokreal.jumlah) as total')
        //     )->where('stokreal.flag', '')
        //         ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
        //         ->where('stokreal.kdruang', $kdruang)
        //         ->where(function ($x) {
        //             $x->where('stokreal.nopenerimaan', 'like', '%' . request('q') . '%')
        //                 ->orwhere('stokreal.kdobat', 'like', '%' . request('q') . '%')
        //                 ->orwhere('new_masterobat.nama_obat', 'like', '%' . request('q') . '%');
        //         })

        //         ->where('stokreal.jumlah', '>', 0)
        //         ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
        //         ->orderBy('new_masterobat.nama_obat', 'ASC')
        //         ->paginate(request('per_page'));
        // } else {

        $stokreal = Stokopname::select(
            'stokopname.*',
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
            'new_masterobat.status_fornas',
            'new_masterobat.status_forkid',
            'new_masterobat.status_generik',
            'new_masterobat.gudang',
            'stokopname.id as idx',
            DB::raw('sum(stokopname.jumlah) as total')
        )->where('stokopname.flag', '')
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokopname.kdobat')
            ->where('stokopname.kdruang', $kdruang)
            ->where(function ($x) {
                $x->where('stokopname.nopenerimaan', 'like', '%' . request('q') . '%')
                    ->orwhere('stokopname.kdobat', 'like', '%' . request('q') . '%')
                    ->orwhere('new_masterobat.nama_obat', 'like', '%' . request('q') . '%');
            })
            ->when(request('from'), function ($q) {
                $q->whereBetween('tglopname', [request('from') . ' 23:00:00', request('to') . ' 23:59:59']);
            })
            ->groupBy('stokopname.kdobat', 'stokopname.kdruang')
            ->orderBy('new_masterobat.nama_obat', 'ASC')
            ->paginate(request('per_page'));
        // }
        $raw = collect($stokreal);
        $data['data'] = $raw['data'];
        $data['meta'] = $raw->except('data');
        // $data['diff'] = $diff;
        // $data['stokreal'] = $stokreal;

        return new JsonResponse($data);
    }
    public function listStokSekarang()
    {
        $kdruang = request('kdruang');
        $stokreal = Stokreal::select(
            'stokreal.id as idx',
            'stokreal.kdruang',
            'stokreal.jumlah',
            'stokreal.tglexp',
            'stokreal.kdobat',
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
            'new_masterobat.status_fornas',
            'new_masterobat.status_forkid',
            'new_masterobat.status_generik',
            'new_masterobat.gudang',
            DB::raw('sum(stokreal.jumlah) as total')
        )->where('stokreal.flag', '')
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->where('stokreal.kdruang', $kdruang)
            // ->where('stokreal.jumlah', '>', 0)
            ->where(function ($x) {
                $x->orwhere('stokreal.kdobat', 'like', '%' . request('q') . '%')
                    ->orwhere('new_masterobat.nama_obat', 'like', '%' . request('q') . '%');
            })
            ->with([
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
                'permintaanobatrinci' => function ($permintaanobatrinci) use ($kdruang) {
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

                        ->where('permintaan_h.tujuan', $kdruang)
                        ->whereIn('permintaan_h.flag', ['', '1', '2'])
                        ->groupBy('permintaan_r.kdobat');
                },
            ])
            ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
            ->orderBy('new_masterobat.nama_obat', 'ASC')
            ->orderBy('stokreal.tglexp', 'ASC')
            ->paginate(request('per_page'));
        $stokreal->append('harga');
        $datastok = $stokreal->map(function ($xxx) {
            $stolreal = $xxx->total;
            $jumlahtrans = $xxx['transnonracikan'][0]->jumlah ?? 0;
            $jumlahtransx = $xxx['transracikan'][0]->jumlah ?? 0;
            $permintaantotal = count($xxx->permintaanobatrinci) > 0 ? $xxx->permintaanobatrinci[0]->allpermintaan : 0;
            $stokalokasi = (float) $stolreal - (float) $permintaantotal - (float) $jumlahtrans - (float) $jumlahtransx;
            $xxx['stokalokasi'] = $stokalokasi;
            $xxx['permintaantotal'] = $permintaantotal;
            $xxx['lain'] = [];
            return $xxx;
        });
        return new JsonResponse([
            'data' => $datastok,
            'meta' => collect($stokreal)->except('data'),
        ]);
    }
    public function listStokMinDepo()
    {
        $kdruang = request('kdruang');
        $stokreal = Stokreal::select(
            'stokreal.id as idx',
            'stokreal.kdruang',
            'stokreal.jumlah',
            'stokreal.tglexp',
            'stokreal.kdobat',
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
            'new_masterobat.status_fornas',
            'new_masterobat.status_forkid',
            'new_masterobat.status_generik',
            'new_masterobat.gudang',
            'min_max_ruang.min as minvalue',
            DB::raw('sum(stokreal.jumlah) as total'),
            DB::raw('((min_max_ruang.min - sum(stokreal.jumlah)) / min_max_ruang.min * 100) as persen')

        )
            ->where('stokreal.flag', '')
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->leftjoin('min_max_ruang', function ($anu) {
                $anu->on('min_max_ruang.kd_obat', 'stokreal.kdobat')
                    ->on('min_max_ruang.kd_ruang', 'stokreal.kdruang');
            })
            ->where('stokreal.kdruang', $kdruang)
            // ->where('stokreal.jumlah', '>', 0)
            ->where(function ($x) {
                $x->orwhere('stokreal.kdobat', 'like', '%' . request('q') . '%')
                    ->orwhere('new_masterobat.nama_obat', 'like', '%' . request('q') . '%');
            })
            ->havingRaw('minvalue >= total')
            ->with([
                'permintaanobatrinci' => function ($pr) use ($kdruang) {
                    $pr->select(
                        'permintaan_r.kdobat',
                        'permintaan_r.jumlah_minta',
                        'permintaan_h.dari',
                        'permintaan_h.flag',
                        'permintaan_h.no_permintaan',
                        'mutasi_gudangdepo.jml',
                    )
                        ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', 'permintaan_r.no_permintaan')
                        ->leftJoin('mutasi_gudangdepo', function ($anu) {
                            $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                        })
                        ->where('permintaan_h.dari', $kdruang)
                        ->whereIn('permintaan_h.flag', ['', '1', '2', '3']);
                }
            ])
            ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
            ->orderBy(DB::raw('(min_max_ruang.min - sum(stokreal.jumlah)) / min_max_ruang.min * 100'), 'DESC')
            ->orderBy(DB::raw('min_max_ruang.min'), 'DESC')
            ->paginate(request('per_page'));
        $stokreal->append('harga');

        $nu = collect($stokreal);
        return new JsonResponse([
            'data' => $nu['data'],
            'meta' => collect($stokreal)->except('data'),
        ]);
    }

    public static function updatestokdepo($request)
    {
        $kembalikan = Stokreal::select(
            'stokreal.nopenerimaan as nopenerimaan',
            'stokreal.kdobat as kdobat',
            'stokreal.harga as harga',
            DB::raw('stokreal.jumlah + retur_penjualan_r.jumlah_retur as masuk')
        )
            ->leftjoin('retur_penjualan_r', function ($e) {
                $e->on('retur_penjualan_r.nopenerimaan', 'stokreal.nopenerimaan')
                    ->on('retur_penjualan_r.kdobat', 'stokreal.kdobat')
                    ->on('retur_penjualan_r.harga_beli', 'stokreal.harga');
            })
            ->where('retur_penjualan_r.kdobat', $request->kdobat)
            ->where('stokreal.kdruang', $request->koderuang)
            ->where('retur_penjualan_r.noresep', $request->noresep)
            ->get();
        foreach ($kembalikan as $e) {
            $updatestok = Stokreal::where('nopenerimaan', $e->nopenerimaan)
                ->where('kdobat', $e->kdobat)
                ->where('stokreal.kdruang', $request->koderuang)
                ->where('harga', $e->harga)->first();
            $updatestok->jumlah = $e->masuk;
            $updatestok->save();
        }
        return 200;
    }
    public function dataAlokasi()
    {
        $transNonRacikan = Permintaanresep::select(
            'resep_permintaan_keluar.noreg',
            'resep_permintaan_keluar.noresep',
            'resep_permintaan_keluar.kdobat as kdobat',
            'resep_keluar_h.depo as kdruang',
            'resep_keluar_h.ruangan as dari',
            'resep_keluar_h.tgl',
            'resep_keluar_h.tgl_permintaan',
            'resep_keluar_h.flag',
            'resep_permintaan_keluar.jumlah'
            // DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
        )
            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
            ->where('resep_keluar_h.depo', request('kdruang'))
            ->where('resep_permintaan_keluar.kdobat', request('kdobat'))
            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
            // ->with(['head'])
            // ->with(['head' => function ($he) {
            //     $he->select('noresep', 'noreg');
            // }])
            // ->groupBy('resep_permintaan_keluar.kdobat')
            ->get();
        $transRacikan = Permintaanresepracikan::select(
            'resep_permintaan_keluar_racikan.noreg',
            'resep_permintaan_keluar_racikan.noresep',
            'resep_permintaan_keluar_racikan.kdobat as kdobat',
            'resep_keluar_h.depo as kdruang',
            'resep_keluar_h.ruangan as dari',
            'resep_keluar_h.tgl',
            'resep_keluar_h.tgl_permintaan',
            'resep_keluar_h.flag',
            'resep_permintaan_keluar_racikan.jumlah'
            // DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
        )
            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
            ->where('resep_keluar_h.depo', request('kdruang'))
            ->where('resep_permintaan_keluar_racikan.kdobat', request('kdobat'))
            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
            // ->groupBy('resep_permintaan_keluar_racikan.kdobat')
            ->get();

        $permintaan = Permintaandeporinci::select(
            'permintaan_h.tgl_permintaan as tgl',
            'permintaan_h.flag',
            'permintaan_h.dari',
            'permintaan_r.kdobat',
            'permintaan_r.jumlah_minta as jumlah'
            // DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
        )
            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
            // biar yang ada di tabel mutasi ga ke hitung
            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
            })
            ->whereNull('mutasi_gudangdepo.kd_obat')

            ->where('permintaan_h.tujuan', request('kdruang'))
            ->where('permintaan_r.kdobat', request('kdobat'))
            ->whereIn('permintaan_h.flag', ['', '1', '2'])
            // ->groupBy('permintaan_r.kdobat')
            ->get();

        $data = [
            // 'req' => request()->all(),
            'transNonRacikan' => $transNonRacikan,
            'transRacikan' => $transRacikan,
            'permintaan' => $permintaan,
        ];
        return new JsonResponse($data);
    }
    public function getRuangRanap()
    {
        $data = Mruangranap::select(
            'rs1 as kdruang',
            'rs2 as nama',
        )
            ->get();
        return new JsonResponse($data);
    }
    public function obatMauDisesuaikan()
    {
        $data = Stokrel::where('kdobat', request('kdobat'))
            ->where('kdruang', request('kdruang'))
            ->where('nopenerimaan', 'LIKE', '%awal%')
            ->get();
        // $data->append('harga');
        return new JsonResponse([
            'data' => $data,
            'req' => request()->all(),
        ]);
    }
}
