<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\PenggunaRuang;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Ruang;
use App\Models\Sigarang\Transaksi\DistribusiLangsung\DetailDistribusiLangsung;
use App\Models\Sigarang\Transaksi\DistribusiLangsung\DistribusiLangsung;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DistribusiLangsungController extends Controller
{
    // ambil barang berdasarkan transaksi
    public function getDataTransaksiWithBarang()
    {
        $paginate = request('per_page') ? request('per_page') : 10;
        $ruang = 'Gd-02010102';
        $distribute = DistribusiLangsung::where('reff', request('reff'))
            ->where('status', 1)
            ->first();
        if (!$distribute) {
            return new JsonResponse(['data' => []]);
        }
        $data = RecentStokUpdate::leftJoin(
            'penerimaans',
            'recent_stok_updates.no_penerimaan',
            '=',
            'penerimaans.no_penerimaan'
        )
            ->where('recent_stok_updates.kode_ruang', $ruang)
            ->where('recent_stok_updates.sisa_stok', '>', 0)
            ->join('barang_r_s', 'recent_stok_updates.kode_rs', '=', 'barang_r_s.kode')
            ->join('satuans', 'satuans.kode', '=', 'barang_r_s.kode_satuan')
            ->when(request('q'), function ($search) {
                $search->where(function ($anu) {
                    $anu->where('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('barang_r_s.kode', 'LIKE', '%' . request('q') . '%');
                })
                    ->where('barang_r_s.tipe', request('tipe'));
            })
            ->where('barang_r_s.tipe', request('tipe'))
            ->orderBy('penerimaans.tanggal', 'ASC')
            ->select(
                'barang_r_s.nama',
                'barang_r_s.kode',
                'barang_r_s.kode_satuan',
                'recent_stok_updates.id',
                'recent_stok_updates.kode_rs',
                'recent_stok_updates.kode_ruang',
                'recent_stok_updates.sisa_stok',
                'recent_stok_updates.no_penerimaan as no_penerimaan_stok',
                'penerimaans.no_penerimaan',
                'penerimaans.tanggal',
                'satuans.nama as satuan',
            )
            ->with([
                'detailDistribusiLangsung' => function ($detail) {
                    $detail->select(
                        'detail_distribusi_langsungs.*',
                        'distribusi_langsungs.*',
                    )
                        ->join('distribusi_langsungs', function ($langsung) {
                            $langsung->on('detail_distribusi_langsungs.distribusi_langsung_id', '=', 'distribusi_langsungs.id')
                                ->where('status', '=', 1)
                                ->where('reff', request('reff'));
                        });
                }
            ])
            ->paginate($paginate);

        $anu = collect($data);
        $balik['data'] = $anu->only('data');
        $balik['meta'] = $anu->except('data');
        $balik['transaksi'] = $distribute;

        return new JsonResponse($balik);
    }
    // ambil data barang dan transaksi sekarang
    public function getDataBarangWithTransaksi()
    {
        /*
        * ambil data barang, join, ambil yang ada stoknya saja di recent stok update.
        * barang yang diambil yang punya depo gizi saja
        * beserta data transaksi berdasarkan no reff (jika ada)
        */
        $paginate = request('per_page') ? request('per_page') : 10;
        $ruang = 'Gd-02010102';
        $distribute = DistribusiLangsung::where('reff', request('reff'))
            ->where('status', 1)
            ->first();
        $data = BarangRS::with([
            'detailDistribusiLangsung' => function ($detail) {
                $detail->select(
                    'detail_distribusi_langsungs.*',
                    'distribusi_langsungs.*',
                )
                    ->join('distribusi_langsungs', function ($langsung) {
                        $langsung->on('detail_distribusi_langsungs.distribusi_langsung_id', '=', 'distribusi_langsungs.id')
                            ->where('status', '=', 1)
                            ->where('reff', request('reff'));
                    });
            }
        ])
            // join where has recent stok > 0
            ->select(
                'barang_r_s.*',
                'recent_stok_updates.sisa_stok',
                'recent_stok_updates.kode_ruang',
                'satuans.nama as satuan',
            )
            ->join('recent_stok_updates', function ($wew) use ($ruang) {
                $wew->on('recent_stok_updates.kode_rs', '=', 'barang_r_s.kode')
                    ->where('kode_ruang', $ruang)
                    ->where('sisa_stok', '>', 0);
            })
            ->join('satuans', 'satuans.kode', '=', 'barang_r_s.kode_satuan')
            ->where('kode_depo', $ruang)
            ->where('tipe', request('tipe'))
            ->when(request('q'), function ($search) {
                $search->where('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.kode', 'LIKE', '%' . request('q') . '%');
            })
            ->paginate($paginate);

        $anu = collect($data);
        $balik['data'] = $anu->only('data');
        $balik['meta'] = $anu->except('data');
        $balik['transaksi'] = $distribute;

        return new JsonResponse($balik);
    }
    //
    public function index()
    {
        $data = DistribusiLangsung::latest('id')
            ->paginate(request('per_page'));
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function getStokDepo()
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        //  kusus depo gizi
        $ruang = 'Gd-02010102';
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as totalStok')
            ->where('sisa_stok', '>', 0)
            ->where('kode_ruang', $ruang)
            ->groupBy('kode_rs', 'kode_ruang')
            ->with('barang', 'depo', 'satuan')
            ->get();

        return new JsonResponse($data, 200);
    }

    public function getRuang()
    {
        $ruang = 'R-0101071';
        $pengguna = PenggunaRuang::where('kode_ruang', $ruang)->first();
        $ruang = PenggunaRuang::where('kode_pengguna', $pengguna->kode_pengguna)->get();
        $raw = collect($ruang);
        $only = $raw->map(function ($y) {
            return $y->kode_ruang;
        });
        $data = Ruang::oldest('id')
            ->whereIn('kode', $only)
            ->filter(request(['q']))
            ->limit(15)
            ->get();
        // return RuangResource::collection($data);
        // $collect = collect($data);
        // $balik = $collect->only('data');
        // $balik['meta'] = $collect->except('data');

        return new JsonResponse($data);
    }


    public function store(Request $request)
    {
        // ini belum termasuk fifo
        $pesan = '';
        try {
            DB::beginTransaction();

            $valid = Validator::make($request->all(), ['reff' => 'required']);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }
            $distribusi = DistribusiLangsung::updateOrCreate(
                [
                    'reff' => $request->reff
                ],
                $request->all()
            );
            if ($request->has('kode_rs') && $request->kode_rs !== null) {
                $distribusi->details()->updateOrCreate(
                    [
                        'kode_rs' => $request->kode_rs
                    ],
                    $request->all()
                );
            }


            DB::commit();

            return new JsonResponse([
                'message' => 'distribusi telah ditambahkan',
                'distribusi' => $distribusi,
                // 'gudang' => $gudang,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e,
                'distribusi' => $distribusi['id'],
                'pesan' => $pesan,
            ], 500);
        }
        return new JsonResponse($request->all());
    }

    public function selesai(Request $request)
    {
        $distribusi = DistribusiLangsung::with('details')->find($request->id);
        $ruang = 'Gd-02010102';
        foreach ($distribusi->details as $detail) {
            $recent = RecentStokUpdate::where('kode_rs', $detail->kode_rs)
                ->where('kode_ruang', $ruang)
                ->where('sisa_stok', '>', 0)
                ->get();

            $sisaStok = collect($recent)->sum('sisa_stok');
            $jumlah = $detail->jumlah;
            $index = 0;


            // masukkan detail sesuai order FIFO
            $masuk = $jumlah;
            do {
                $ada = $recent[$index]->sisa_stok;
                if ($ada < $masuk) {
                    $sisa = $masuk - $ada;

                    // RecentStokUpdate::updateOrCreate(
                    //     [
                    //         'kode_rs' => $detail->kode_rs,
                    //         'kode_ruang' => $detail->ruang_tujuan,
                    //         'no_penerimaan' => $recent[$index]->no_penerimaan,
                    //     ],
                    //     [
                    //         'sisa_stok' => $ada,
                    //         'harga' => $recent[$index]->harga,

                    //     ]
                    // );
                    $recent[$index]->update([
                        'sisa_stok' => 0
                    ]);
                    $detailDist = DetailDistribusiLangsung::find($detail->id);
                    $detailDist->update([
                        'no_penerimaan' => $recent[$index]->no_penerimaan,
                    ]);
                    $index = $index + 1;
                    $masuk = $sisa;
                    $loop = true;
                } else {
                    $sisa = $ada - $masuk;

                    // RecentStokUpdate::create([
                    //     'kode_rs' => $detail['kode_rs'],
                    //     'kode_ruang' => $detail['tujuan'],
                    //     'sisa_stok' => $masuk,
                    //     'harga' => $recent[$index]->harga,
                    //     'no_penerimaan' => $recent[$index]->no_penerimaan,
                    // ]);

                    $recent[$index]->update([
                        'sisa_stok' => $sisa
                    ]);

                    $detailPermintaan = DetailDistribusiLangsung::find($detail->id);
                    $detailPermintaan->update([
                        'no_penerimaan' => $recent[$index]->no_penerimaan,
                    ]);
                    $loop = false;
                }
            } while ($loop);
        }

        $distribusi->update([
            'status' => 2
        ]);
        return new JsonResponse([
            'distribusi' => $distribusi,
            'message' => 'Distribusi telah dikalukan, data stok sudah berkurang'
        ]);
    }

    public function habiskanBahanBasah(Request $request)
    {
        try {
            DB::beginTransaction();

            $valid = Validator::make($request->all(), ['reff' => 'required']);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }
            $distribusi = DistribusiLangsung::updateOrCreate(
                [
                    'reff' => $request->reff
                ],
                $request->all()
            );
            $ruang = 'Gd-02010102';
            $data = BarangRS::
                // join where has recent stok > 0
                select(
                    'barang_r_s.*',
                    'recent_stok_updates.sisa_stok',
                    'recent_stok_updates.kode_ruang',
                    'satuans.nama as satuan',
                )
                ->join('recent_stok_updates', function ($wew) use ($ruang) {
                    $wew->on('recent_stok_updates.kode_rs', '=', 'barang_r_s.kode')
                        ->where('kode_ruang', $ruang)
                        ->where('sisa_stok', '>', 0);
                })
                ->join('satuans', 'satuans.kode', '=', 'barang_r_s.kode_satuan')
                ->where('kode_depo', $ruang)
                ->where('tipe', 'basah')
                ->when(request('q'), function ($search) {
                    $search->where('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('barang_r_s.kode', 'LIKE', '%' . request('q') . '%');
                })
                ->get();
            $in = collect($data);
            $to = $in->map(function ($wew) {
                return $wew->kode;
            });
            $stok = RecentStokUpdate::where('sisa_stok', '>', 0)
                ->where('kode_ruang', $ruang)
                ->whereIn('kode_rs', $to)
                ->get();

            foreach ($stok as $dipakai) {
                $distribusi->details()->updateOrCreate(
                    [
                        'kode_rs' => $dipakai->kode_rs
                    ],
                    [
                        'no_permintaan' => $dipakai->no_permintaan,
                        'kode_satuan' => $dipakai->kode_satuan,
                        'jumlah' => $dipakai->sisa_stok,
                    ]
                );
                $dipakai->update([
                    'sisa_stok' => 0
                ]);
            }
            $distribusi->update([
                'status' => 2
            ]);
            DB::commit();
            return new JsonResponse([
                'data' => $data,
                'stok' => $stok,
                'to' => $to,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e,
                'distribusi' => $distribusi['id'],
            ], 500);
        }
    }
}
