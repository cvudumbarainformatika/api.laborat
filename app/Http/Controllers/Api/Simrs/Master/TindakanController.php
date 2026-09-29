<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Api\Simrs\Master\Tarif\PemeriksaanLaboratControllr;
use App\Http\Controllers\Api\Simrs\Master\Tarif\TarifMasterAmbulanController;
use App\Http\Controllers\Api\Simrs\Master\Tarif\TarifRadiologiController;
use App\Http\Controllers\Api\Simrs\Master\Tarif\TindakanOperasiController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Mtindakan;
use App\Models\Simrs\Master\MtindakanSementara;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class TindakanController extends Controller
{
    public function getPoli()
    {
        $data = Mpoli::listpoli()->orderBy('rs2', 'ASC')->get();
        return new JsonResponse($data);
    }
    public function listtindakan(Request $request)
    {
        $today = today()->toDateString();
        $status = $request->input('status', 'aktif');

        $query = MtindakanSementara::select(
            'rs30_sementara.idx',
            'rs30_sementara.rs1 as kdtindakan',
            'rs30_sementara.rs2 as nmtindakan',
            'rs30_sementara.rs4 as ruangan',
            'rs30_sementara.rs8 as js3',
            'rs30_sementara.rs9 as jp3',
            'rs30_sementara.rs10 as habispake3',
            'rs30_sementara.anastesi as anastesi',
            DB::raw('rs30_sementara.rs8+rs30_sementara.rs9+rs30_sementara.anastesi as tarif3'),
            'rs30_sementara.rs11 as js2',
            'rs30_sementara.rs12 as jp2',
            'rs30_sementara.rs13 as habispake2',
            DB::raw('rs30_sementara.rs11+rs30_sementara.rs12 as tarif2'),
            'rs30_sementara.rs14 as js1',
            'rs30_sementara.rs15 as jp1',
            'rs30_sementara.rs16 as habispake1',
            DB::raw('rs30_sementara.rs14+rs30_sementara.rs15 as tarif1'),
            'rs30_sementara.rs17 as jsutama',
            'rs30_sementara.rs18 as jputama',
            'rs30_sementara.rs19 as habispakeutama',
            DB::raw('rs30_sementara.rs17+rs30_sementara.rs18 as tarifutama'),
            'rs30_sementara.rs20 as jsvip',
            'rs30_sementara.rs21 as jpvip',
            'rs30_sementara.rs22 as habispakevip',
            DB::raw('rs30_sementara.rs20+rs30_sementara.rs21 as tarifvip'),
            'rs30_sementara.rs23 as jsvvip',
            'rs30_sementara.rs24 as jpvvip',
            'rs30_sementara.rs25 as habispakevvip',
            DB::raw('rs30_sementara.rs23+rs30_sementara.rs24 as tarifvvip'),
            'rs30_sementara.pss as js_presidential',
            'rs30_sementara.psp as jp_presidential',
            'rs30_sementara.habispake_presidential',
            DB::raw('rs30_sementara.pss+rs30_sementara.psp+rs30_sementara.habispake_presidential as tarif_presidential'),
            'rs30_sementara.js_hcu',
            'rs30_sementara.jp_hcu',
            'rs30_sementara.habispake_hcu',
            DB::raw('rs30_sementara.js_hcu+rs30_sementara.jp_hcu+rs30_sementara.habispake_hcu as tarif_hcu'),
            'rs30_sementara.js_hc',
            'rs30_sementara.jp_hc',
            'rs30_sementara.habispake_hc',
            DB::raw('rs30_sementara.js_hc+rs30_sementara.jp_hc+rs30_sementara.habispake_hc as tarif_hc'),
            'rs30_sementara.tgl_hapus',
            'rs30_sementara.tgl_mulai_berlaku',
            'rs30_sementara.dasar_perubahan'
        );

        // Filter Status
        if ($status === 'aktif') {
            $sub = DB::table('rs.rs30_sementara')
                ->select('rs1', DB::raw('MAX(idx) as max_idx'))
                ->whereNull('tgl_hapus')
                ->where(function ($q) use ($today) {
                    $q->whereNull('tgl_mulai_berlaku')->orWhere('tgl_mulai_berlaku', '<=', $today);
                })
                ->groupBy('rs1');

            $query->joinSub($sub, 'latest_aktif', function ($join) {
                $join->on('rs30_sementara.idx', '=', 'latest_aktif.max_idx');
            });
        } elseif ($status === 'aktif_draft') {
            // Gabungan tarif aktif saat ini + draft usulan tarif masa depan
            $sub = DB::table('rs.rs30_sementara')
                ->select('rs1', DB::raw('MAX(idx) as max_idx'))
                ->whereNull('tgl_hapus')
                ->where(function ($q) use ($today) {
                    $q->whereNull('tgl_mulai_berlaku')->orWhere('tgl_mulai_berlaku', '<=', $today);
                })
                ->groupBy('rs1');

            $query->leftJoinSub($sub, 'latest_aktif', function ($join) {
                $join->on('rs30_sementara.idx', '=', 'latest_aktif.max_idx');
            })
            ->whereNull('rs30_sementara.tgl_hapus')
            ->where(function ($q) use ($today) {
                $q->where('rs30_sementara.tgl_mulai_berlaku', '>', $today)
                    ->orWhereNotNull('latest_aktif.max_idx');
            });
        } elseif ($status === 'draft') {
            $query->whereNull('rs30_sementara.tgl_hapus')
                ->where('rs30_sementara.tgl_mulai_berlaku', '>', $today);
        } elseif ($status === 'history') {
            $sub = DB::table('rs.rs30_sementara')
                ->select('rs1', DB::raw('MAX(idx) as max_idx'))
                ->whereNull('tgl_hapus')
                ->where(function ($q) use ($today) {
                    $q->whereNull('tgl_mulai_berlaku')->orWhere('tgl_mulai_berlaku', '<=', $today);
                })
                ->groupBy('rs1');

            $query->leftJoinSub($sub, 'latest_aktif', function ($join) {
                $join->on('rs30_sementara.idx', '=', 'latest_aktif.max_idx');
            })
            ->whereNull('rs30_sementara.tgl_hapus')
            ->where(function ($q) use ($today) {
                $q->whereNull('rs30_sementara.tgl_mulai_berlaku')->orWhere('rs30_sementara.tgl_mulai_berlaku', '<=', $today);
            })
            ->whereNull('latest_aktif.max_idx');
        } elseif ($status === 'dihapus') {
            $query->whereNotNull('rs30_sementara.tgl_hapus');
        }
        // $status === 'semua' -> tidak difilter statusnya

        // Filter Ruangan
        if ($request->filled('ruangan')) {
            $query->where('rs30_sementara.rs4', 'like', '%' . $request->input('ruangan') . '%');
        }

        // Filter Pencarian Nama / Kode Tindakan
        if ($request->filled('nmtindakan')) {
            $keyword = $request->input('nmtindakan');
            $query->where(function ($q) use ($keyword) {
                $q->where('rs30_sementara.rs2', 'like', '%' . $keyword . '%')
                    ->orWhere('rs30_sementara.rs1', 'like', '%' . $keyword . '%');
            });
        }

        $listtindakan = $query->orderBy('rs30_sementara.rs2', 'ASC')
            ->paginate($request->input('per_page', 10));

        return new JsonResponse($listtindakan);
    }

    public function simpanmastertindakan(Request $request)
    {

        if ($request->kdtindakan == '' || $request->kdtindakan == null) {
            $ceknama = Mtindakan::where('rs2', $request->nmtindakan)->count();
            if ($ceknama > 0) {
                return new JsonResponse(['message' => 'Maaf Tindakan Sudah Ada...!!!'], 500);
            }

            $cektotal = Mtindakan::count();
            $akhir = (int) $cektotal + (int) 1;

            $has = null;
            $lbr = strlen($akhir);
            for ($i = 1; $i <= 4 - $lbr; $i++) {
                $has = $has . "0";
            }

            $kdtindakan = 'TB' . $has . $akhir;
        } else {
            $kdtindakan = $request->kdtindakan;
        }
        $simpantindakan = Mtindakan::updateOrCreate(
            [
                'rs1' => $kdtindakan
            ],
            [
                'rs2' => $request->nmtindakan,
                'rs3' => 'T1#',
                'rs8' => $request->js3,
                'rs9' => $request->jp3,
                'rs10' => $request->habispake3,
                'rs11' => $request->js2,
                'rs12' => $request->jp2,
                'rs13' => $request->habispake2,
                'rs14' => $request->js1,
                'rs15' => $request->jp1,
                'rs16' => $request->habispake1,
                'rs17' => $request->jsutama,
                'rs18' => $request->jputama,
                'rs19' => $request->habispakeutama,
                'rs20' => $request->jsvip,
                'rs21' => $request->jpvip,
                'rs22' => $request->habispakevip,
                'rs23' => $request->jsvvip,
                'rs24' => $request->jpvvip,
                'rs25' => $request->habispakevvip
            ]
        );
        if (!$simpantindakan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 410);
        }
        return new JsonResponse($simpantindakan);
    }

    public function hidden(Request $request)
    {
        $caritindakan = MtindakanSementara::where('idx', $request->idx)->first();
        if (!$caritindakan) {
            return new JsonResponse(['message' => 'Data tidak ditemukan...!!!'], 404);
        }

        $kdtindakan = $caritindakan->rs1;

        if ($request->action == 'delete') {
            // Opsi: Hapus fisik draft (HANYA boleh untuk usulan baru di masa depan yang belum berlaku)
            if ($caritindakan->tgl_mulai_berlaku == null || $caritindakan->tgl_mulai_berlaku <= today()->toDateString()) {
                return new JsonResponse([
                    'message' => 'Data master awal atau data yang sudah berlaku tidak boleh dihapus permanen. Gunakan opsi "Set Hapus" untuk menonaktifkan tindakan ini.'
                ], 422);
            }

            // Hapus baris draft ini
            $caritindakan->delete();
            return new JsonResponse(['message' => 'Draft usulan tarif berhasil dihapus'], 200);
        } else {
            // Opsi: Set Hapus / Non-Aktifkan tindakan
            $tglHapus = $request->tgl_mulai_berlaku ?? today()->addDay()->toDateString();

            // Seragamkan tgl_hapus pada SEMUA baris riwayat tindakan tersebut agar riwayat lama tidak aktif kembali
            MtindakanSementara::where('rs1', $kdtindakan)->update([
                'tgl_hapus' => $tglHapus,
                'hidden' => '1',
            ]);

            return new JsonResponse(['message' => 'Tindakan berhasil dinonaktifkan mulai tanggal ' . $tglHapus], 200);
        }
    }

    public function showAgain(Request $request)
    {
        $caritindakan = MtindakanSementara::where('idx', $request->idx)->first();
        if (!$caritindakan) {
            return new JsonResponse(['message' => 'Data tidak ditemukan...!!!'], 404);
        }

        $kdtindakan = $caritindakan->rs1;

        // Pulihkan SEMUA baris riwayat tindakan tersebut
        MtindakanSementara::where('rs1', $kdtindakan)->update([
            'tgl_hapus' => null,
            'hidden' => null,
        ]);

        // Pulihkan juga di tabel master rs30 jika ada yang ter-soft delete
        $dataSudahHapus = Mtindakan::onlyTrashed()->where('rs1', $kdtindakan)->first();
        if ($dataSudahHapus) {
            $dataSudahHapus->restore();
        }

        return new JsonResponse(['message' => 'Tindakan berhasil dipulihkan / ditampilkan kembali'], 200);
    }

    public function simpanTindakanKeTabelSementara(Request $request)
    {
        if ($request->kdtindakan == '' || $request->kdtindakan == null) {
            $ceknama = MtindakanSementara::where('rs2', $request->nmtindakan)
                ->whereNull('tgl_hapus')
                ->count();
            if ($ceknama > 0) {
                return new JsonResponse(['message' => 'Maaf Tindakan dengan nama tersebut sudah ada dan masih aktif...!!!'], 500);
            }

            // Generate nomor urut TB berikutnya berbasis MAX agar anti-tabrakan
            $maxTb = MtindakanSementara::where('rs1', 'like', 'TB%')
                ->selectRaw('MAX(CAST(SUBSTRING(rs1, 3) AS UNSIGNED)) as max_num')
                ->value('max_num');
            $akhir = (int) ($maxTb ?? 0) + 1;
            $kdtindakan = 'TB' . str_pad($akhir, 4, '0', STR_PAD_LEFT);
        } else {
            $kdtindakan = $request->kdtindakan;
        }
        $cekData = MtindakanSementara::where('rs1', $kdtindakan)->whereDate('tgl_mulai_berlaku', '>', today())->first();
        if (!$cekData) {
            $simpantindakan = MtindakanSementara::create(
                [
                    'rs1' => $kdtindakan,
                    'tgl_mulai_berlaku' => $request->tgl_mulai_berlaku,
                    'rs2' => $request->nmtindakan,
                    'rs3' => 'T1#',
                    'rs8' => $request->js3,
                    'rs9' => $request->jp3,
                    'rs10' => $request->habispake3,
                    'anastesi' => $request->anastesi ?? 0,
                    'rs11' => $request->js2,
                    'rs12' => $request->jp2,
                    'rs13' => $request->habispake2,
                    'rs14' => $request->js1,
                    'rs15' => $request->jp1,
                    'rs16' => $request->habispake1,
                    'rs17' => $request->jsutama,
                    'rs18' => $request->jputama,
                    'rs19' => $request->habispakeutama,
                    'rs20' => $request->jsvip,
                    'rs21' => $request->jpvip,
                    'rs22' => $request->habispakevip,
                    'rs23' => $request->jsvvip,
                    'rs24' => $request->jpvvip,
                    'rs25' => $request->habispakevvip,
                    'dasar_perubahan' => $request->dasar_perubahan,
                    'pss' => $request->js_presidential,
                    'psp' => $request->jp_presidential,
                    'habispake_presidential' => $request->habispake_presidential,
                    'js_hcu' => $request->js_hcu,
                    'jp_hcu' => $request->jp_hcu,
                    'habispake_hcu' => $request->habispake_hcu,
                    'js_hc' => $request->js_hc,
                    'jp_hc' => $request->jp_hc,
                    'habispake_hc' => $request->habispake_hc,
                    'tgl_hapus' => null,
                    'hidden' => null,
                    'rs4' => $request->ruangan ?? '',
                ]
            );
        } else {
            $simpantindakan = $cekData->update(
                [
                    // 'rs1' => $kdtindakan,
                    'tgl_mulai_berlaku' => $request->tgl_mulai_berlaku,
                    'rs2' => $request->nmtindakan,
                    'rs3' => 'T1#',
                    'rs8' => $request->js3,
                    'rs9' => $request->jp3,
                    'rs10' => $request->habispake3,
                    'anastesi' => $request->anastesi ?? 0,
                    'rs11' => $request->js2,
                    'rs12' => $request->jp2,
                    'rs13' => $request->habispake2,
                    'rs14' => $request->js1,
                    'rs15' => $request->jp1,
                    'rs16' => $request->habispake1,
                    'rs17' => $request->jsutama,
                    'rs18' => $request->jputama,
                    'rs19' => $request->habispakeutama,
                    'rs20' => $request->jsvip,
                    'rs21' => $request->jpvip,
                    'rs22' => $request->habispakevip,
                    'rs23' => $request->jsvvip,
                    'rs24' => $request->jpvvip,
                    'rs25' => $request->habispakevvip,
                    'dasar_perubahan' => $request->dasar_perubahan,
                    'pss' => $request->js_presidential,
                    'psp' => $request->jp_presidential,
                    'habispake_presidential' => $request->habispake_presidential,
                    'js_hcu' => $request->js_hcu,
                    'jp_hcu' => $request->jp_hcu,
                    'habispake_hcu' => $request->habispake_hcu,
                    'js_hc' => $request->js_hc,
                    'jp_hc' => $request->jp_hc,
                    'habispake_hc' => $request->habispake_hc,
                    'tgl_hapus' => null,
                    'hidden' => null,
                    'rs4' => $request->ruangan,
                ]
            );
        }
        if (!$simpantindakan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 410);
        }
        return new JsonResponse($simpantindakan);
    }
    public static function pindahKeTabelMaster($tanggal = null)
    {
        try {
            $msg = [];
            DB::beginTransaction();
            $tglTarget = $tanggal ?? date('Y-m-d');
            $adaTarifBerubah = MtindakanSementara::where(function ($q) use ($tglTarget) {
                $q->whereDate('tgl_mulai_berlaku', $tglTarget)
                    ->orWhereDate('tgl_hapus', $tglTarget);
            })->get();

            if ($adaTarifBerubah) {
                foreach ($adaTarifBerubah as $baru) {
                    $data = Mtindakan::where('rs1', $baru['rs1'])->lockForUpdate()->first();
                    $dataSudahHapus = Mtindakan::onlyTrashed()->where('rs1', $baru['rs1'])->lockForUpdate()->first();
                    $simpantindakan = Mtindakan::withTrashed()->updateOrCreate(
                        [
                            'rs1' => $baru['rs1']
                        ],
                        [
                            'rs2' => $baru['rs2'],
                            'rs3' => $baru['rs3'],
                            'rs8' => $baru['rs8'],
                            'rs9' => $baru['rs9'],
                            'rs10' => $baru['rs10'],
                            'rs11' => $baru['rs11'],
                            'rs12' => $baru['rs12'],
                            'rs13' => $baru['rs13'],
                            'rs14' => $baru['rs14'],
                            'rs15' => $baru['rs15'],
                            'rs16' => $baru['rs16'],
                            'rs17' => $baru['rs17'],
                            'rs18' => $baru['rs18'],
                            'rs19' => $baru['rs19'],
                            'rs20' => $baru['rs20'],
                            'rs21' => $baru['rs21'],
                            'rs22' => $baru['rs22'],
                            'rs23' => $baru['rs23'],
                            'rs24' => $baru['rs24'],
                            'rs25' => $baru['rs25'],
                            'psp' => $baru['psp'],
                            'pss' => $baru['pss'],
                            'anastesi' => $baru['anastesi'] ?? 0,
                            'habispake_presidential' => $baru['habispake_presidential'],
                            'js_hcu' => $baru['js_hcu'],
                            'jp_hcu' => $baru['jp_hcu'],
                            'habispake_hcu' => $baru['habispake_hcu'],
                            'js_hc' => $baru['js_hc'],
                            'jp_hc' => $baru['jp_hc'],
                            'habispake_hc' => $baru['habispake_hc'],
                            'rs4' => $baru['rs4'],
                            'hidden' => $baru['hidden'],
                        ]
                    );

                    // Sinkronkan soft delete di rs30 jika tindakan berstatus dihapus
                    if ($baru['tgl_hapus'] != null && $baru['tgl_hapus'] <= $tglTarget) {
                        if ($data && !$dataSudahHapus) {
                            $data->delete();
                        }
                    } elseif ($baru['tgl_hapus'] == null && $dataSudahHapus) {
                        $dataSudahHapus->restore();
                    }
                }
            }
            DB::commit();
            return ['jumlah' => count($adaTarifBerubah)];
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),

            ], 410);
        }
    }
    public function aksesPindahTable(Request $request)
    {
        $tanggal = $request->input('tanggal', date('Y-m-d'));
        $data['tindakan'] = self::pindahKeTabelMaster($tanggal);
        $data['tarifLab'] = PemeriksaanLaboratControllr::pindahKeTabelMaster();
        $data['tarifTindOk'] = TindakanOperasiController::pindahKeTabelMaster();
        $data['tarifRadiologi'] = TarifRadiologiController::pindahKeTabelMaster();
        $data['ambulan'] = TarifMasterAmbulanController::pindahKeTabelMaster();
        $data['rs 30taris'] = RsTigaPuluhTarifController::pindahKeTabelMaster();
        return $data;
    }
}
