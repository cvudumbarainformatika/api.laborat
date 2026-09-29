<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Rstigapuluhtarif as MasterRstigapuluhtarif;
use App\Models\Simrs\Master\TarifVisiteDanKamarSementara;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RsTigaPuluhTarifController extends Controller
{
    public function gettigapuluhtarif()
    {
        $data = MasterRstigapuluhtarif::get();
        return new JsonResponse($data);
    }

    public function list(Request $request)
    {
        $today = today()->toDateString();
        $status = $request->input('status', 'aktif');

        $query = TarifVisiteDanKamarSementara::select(
            'rs30tarif_sementara.*',
            DB::raw('rs30tarif_sementara.rs6 + rs30tarif_sementara.rs7 as tarif_kelas3'),
            DB::raw('rs30tarif_sementara.rs8 + rs30tarif_sementara.rs9 as tarif_kelas2'),
            DB::raw('rs30tarif_sementara.rs10 + rs30tarif_sementara.rs11 as tarif_kelas1'),
            DB::raw('rs30tarif_sementara.rs12 + rs30tarif_sementara.rs13 as tarif_utama'),
            DB::raw('rs30tarif_sementara.rs14 + rs30tarif_sementara.rs15 as tarif_vip'),
            DB::raw('rs30tarif_sementara.rs16 + rs30tarif_sementara.rs17 as tarif_vvip')
        );

        // Filter Status
        if ($status === 'aktif') {
            $sub = DB::table('rs30tarif_sementara')
                ->select('rs1', DB::raw('MAX(id) as max_id'))
                ->whereNull('tgl_hapus')
                ->where(function ($q) use ($today) {
                    $q->whereNull('tgl_mulai_berlaku')->orWhere('tgl_mulai_berlaku', '<=', $today);
                })
                ->groupBy('rs1');

            $query->joinSub($sub, 'latest_aktif', function ($join) {
                $join->on('rs30tarif_sementara.id', '=', 'latest_aktif.max_id');
            });
        } elseif ($status === 'aktif_draft') {
            $sub = DB::table('rs30tarif_sementara')
                ->select('rs1', DB::raw('MAX(id) as max_id'))
                ->whereNull('tgl_hapus')
                ->where(function ($q) use ($today) {
                    $q->whereNull('tgl_mulai_berlaku')->orWhere('tgl_mulai_berlaku', '<=', $today);
                })
                ->groupBy('rs1');

            $query->leftJoinSub($sub, 'latest_aktif', function ($join) {
                $join->on('rs30tarif_sementara.id', '=', 'latest_aktif.max_id');
            })
            ->whereNull('rs30tarif_sementara.tgl_hapus')
            ->where(function ($q) use ($today) {
                $q->where('rs30tarif_sementara.tgl_mulai_berlaku', '>', $today)
                    ->orWhereNotNull('latest_aktif.max_id');
            });
        } elseif ($status === 'draft') {
            $query->whereNull('rs30tarif_sementara.tgl_hapus')
                ->where('rs30tarif_sementara.tgl_mulai_berlaku', '>', $today);
        } elseif ($status === 'history') {
            $sub = DB::table('rs30tarif_sementara')
                ->select('rs1', DB::raw('MAX(id) as max_id'))
                ->whereNull('tgl_hapus')
                ->where(function ($q) use ($today) {
                    $q->whereNull('tgl_mulai_berlaku')->orWhere('tgl_mulai_berlaku', '<=', $today);
                })
                ->groupBy('rs1');

            $query->leftJoinSub($sub, 'latest_aktif', function ($join) {
                $join->on('rs30tarif_sementara.id', '=', 'latest_aktif.max_id');
            })
            ->whereNull('rs30tarif_sementara.tgl_hapus')
            ->where(function ($q) use ($today) {
                $q->whereNull('rs30tarif_sementara.tgl_mulai_berlaku')->orWhere('rs30tarif_sementara.tgl_mulai_berlaku', '<=', $today);
            })
            ->whereNull('latest_aktif.max_id');
        } elseif ($status === 'dihapus') {
            $query->whereNotNull('rs30tarif_sementara.tgl_hapus');
        }

        // Pencarian
        if ($request->filled('q')) {
            $keyword = $request->input('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('rs30tarif_sementara.rs2', 'like', '%' . $keyword . '%')
                    ->orWhere('rs30tarif_sementara.rs1', 'like', '%' . $keyword . '%');
            });
        }

        $data = $query->orderBy('rs30tarif_sementara.rs2', 'ASC')
            ->paginate($request->input('per_page', 10));

        $rawRes = collect($data);
        $result['data'] = $rawRes['data'];
        $result['meta'] = $rawRes->except('data');
        return new JsonResponse($result);
    }
    public function simpan(Request $request)
    {

        try {
            DB::beginTransaction();
            if ($request->rs1 == '' || $request->rs1 == null) {
                throw new Exception('Tarif Visite dan Kamar Belum bisa ditambahkan dari sistem');
                $cekNama = TarifVisiteDanKamarSementara::where('rs2', $request->nama)->first();
                if ($cekNama) throw new Exception('Nama Tujuan sudah ada');
                // ambil id terakhir
                $lastData = TarifVisiteDanKamarSementara::orderBy('id', 'DESC')->first();
                $cektotal = $lastData->id;
                $akhir = (int) $cektotal + (int) 1;
                $kode = 'TV' . str_pad($akhir, 3, '0', STR_PAD_LEFT);
            } else {
                $kode = $request->rs1;
            }
            $cekData = TarifVisiteDanKamarSementara::where('rs1', $kode)->whereDate('tgl_mulai_berlaku', '>', today())->first();
            if (!$cekData) {
                $result['simpan'] = TarifVisiteDanKamarSementara::create(
                    [
                        'rs1' => $kode,
                        'tgl_mulai_berlaku' => $request->tgl_mulai_berlaku,
                        'rs2' => $request->rs2,
                        // 'rs4' => $request->rs4,
                        // 'rs5' => $request->rs5,
                        'rs6' => $request->rs6 ?? 0,
                        'rs7' => $request->rs7 ?? 0,
                        'rs8' => $request->rs8 ?? 0,
                        'rs9' => $request->rs9 ?? 0,
                        'rs10' => $request->rs10 ?? 0,
                        'rs11' => $request->rs11 ?? 0,
                        'rs12' => $request->rs12 ?? 0,
                        'rs13' => $request->rs13 ?? 0,
                        'rs14' => $request->rs14 ?? 0,
                        'rs15' => $request->rs15 ?? 0,
                        'rs16' => $request->rs16 ?? 0,
                        'rs17' => $request->rs17 ?? 0,
                        'hcus' => $request->hcus ?? 0,
                        'hcup' => $request->hcup ?? 0,
                        'icus' => $request->icus ?? 0,
                        'icup' => $request->icup ?? 0,
                        'iccus' => $request->iccus ?? 0,
                        'iccup' => $request->iccup ?? 0,
                        'nicus' => $request->nicus ?? 0,
                        'nicup' => $request->nicup ?? 0,
                        'ins' => $request->ins ?? 0,
                        'inp' => $request->inp ?? 0,
                        'isos' => $request->isos ?? 0,
                        'isop' => $request->isop ?? 0,
                        'pss' => $request->pss ?? 0,
                        'psp' => $request->psp ?? 0,
                        'dasar_perubahan' => $request->dasar_perubahan,
                    ]
                );
            } else {
                $result['simpan'] = $cekData->update(
                    [
                        'rs1' => $kode,
                        'tgl_mulai_berlaku' => $request->tgl_mulai_berlaku,
                        'rs2' => $request->rs2,
                        // 'rs4' => $request->rs4,
                        // 'rs5' => $request->rs5,
                        'rs6' => $request->rs6 ?? 0,
                        'rs7' => $request->rs7 ?? 0,
                        'rs8' => $request->rs8 ?? 0,
                        'rs9' => $request->rs9 ?? 0,
                        'rs10' => $request->rs10 ?? 0,
                        'rs11' => $request->rs11 ?? 0,
                        'rs12' => $request->rs12 ?? 0,
                        'rs13' => $request->rs13 ?? 0,
                        'rs14' => $request->rs14 ?? 0,
                        'rs15' => $request->rs15 ?? 0,
                        'rs16' => $request->rs16 ?? 0,
                        'rs17' => $request->rs17 ?? 0,
                        'hcus' => $request->hcus ?? 0,
                        'hcup' => $request->hcup ?? 0,
                        'icus' => $request->icus ?? 0,
                        'icup' => $request->icup ?? 0,
                        'iccus' => $request->iccus ?? 0,
                        'iccup' => $request->iccup ?? 0,
                        'nicus' => $request->nicus ?? 0,
                        'nicup' => $request->nicup ?? 0,
                        'ins' => $request->ins ?? 0,
                        'inp' => $request->inp ?? 0,
                        'isos' => $request->isos ?? 0,
                        'isop' => $request->isop ?? 0,
                        'pss' => $request->pss ?? 0,
                        'psp' => $request->psp ?? 0,
                        'dasar_perubahan' => $request->dasar_perubahan,
                        'tgl_hapus' => null,
                    ]
                );
            }
            $result['message'] = 'Data Berhasil Disimpan';
            DB::commit();
            return new JsonResponse($result);
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
    public function hidden(Request $request)
    {
        $cekNull = TarifVisiteDanKamarSementara::where('id', $request->id)->first();
        if ($request->action == 'delete') {
            if ($cekNull->tgl_mulai_berlaku == null) return new JsonResponse(['message' => 'Data Ini tidak boleh dihapus'], 410);
            $data = TarifVisiteDanKamarSementara::where('id', $request->id)->whereNotNull('tgl_mulai_berlaku')->first();
            if (!$data) return new JsonResponse(['message' => 'Data Tidak ditemukan'], 410);
            $data->delete();
            return new JsonResponse(['message' => 'Data Dihapus'], 200);
        } else {
            if ($cekNull->tgl_mulai_berlaku == null) return new JsonResponse(['message' => 'Data Ini tidak boleh dijadikan sebagai dasar penghapusan. silahkan edit data terlebih dahulu, kemudian jadikan data tersebut sebagai dasar penghapusan'], 410);
            $data = TarifVisiteDanKamarSementara::where('id', $request->id)->whereNotNull('tgl_mulai_berlaku')->first();
            if (!$data) return new JsonResponse(['message' => 'Data Tidak ditemukan'], 410);
            $data->tgl_hapus = $request->tgl_mulai_berlaku;
            // $data->flag = '1';
            $data->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
            $data->save();
            return new JsonResponse(['message' => 'Data menjadi Dasar penghapusan tarif'], 200);
        }
        // $data = TarifVisiteDanKamarSementara::where('rs1', $request->kode)->first();
        // $data->tgl_hapus = $request->tgl_mulai_berlaku;
        // // $data->flag = '1';
        // $data->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
        // $data->save();
        // return new JsonResponse(['message' => 'ok'], 200);
    }
    public function showAgain(Request $request)
    {
        $data = TarifVisiteDanKamarSementara::where('id', $request->id)->first();
        $data->tgl_hapus = null;
        // $data->flag = '';
        $data->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
        $data->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }
    public static function pindahKeTabelMaster()
    {
        try {
            $msg = [];
            DB::beginTransaction();
            $adaTarifBerubah = TarifVisiteDanKamarSementara::whereDate('tgl_mulai_berlaku', date('Y-m-d'))->get();
            if ($adaTarifBerubah) {
                foreach ($adaTarifBerubah as $baru) {
                    $simpantindakan = MasterRstigapuluhtarif::withTrashed()->updateOrCreate(
                        [
                            'rs1' => $baru['rs1']
                        ],
                        [
                            'rs2' => $baru['rs2'],
                            'rs3' => $baru['rs3'],
                            'rs4' => $baru['rs4'],
                            'rs5' => $baru['rs5'],
                            'rs6' => $baru['rs6'],
                            'rs7' => $baru['rs7'],
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
                            'hcus' => $baru['hcus'],
                            'hcup' => $baru['hcup'],
                            'icus' => $baru['icus'],
                            'icup' => $baru['icup'],
                            'iccus' => $baru['iccus'],
                            'iccup' => $baru['iccup'],
                            'nicus' => $baru['nicus'],
                            'nicup' => $baru['nicup'],
                            'ins' => $baru['ins'],
                            'inp' => $baru['inp'],
                            'isos' => $baru['isos'],
                            'isop' => $baru['isop'],
                            'pss' => $baru['pss'],
                            'psp' => $baru['psp'],
                        ]
                    );
                    $data = MasterRstigapuluhtarif::where('rs1', $baru['rs1'])->first();
                    $dataSudahHapus = MasterRstigapuluhtarif::onlyTrashed()->where('rs1', $baru['rs1'])->first();
                    if ($data && $baru['tgl_hapus'] != null && !$dataSudahHapus) {
                        $data->delete();
                    }
                    if ($baru['tgl_hapus'] == null && $dataSudahHapus) {
                        $dataSudahHapus->restore();
                    }
                }
            }
            DB::commit();
            return ['jumlah Tarif Ambulance' => count($adaTarifBerubah)];
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
}
