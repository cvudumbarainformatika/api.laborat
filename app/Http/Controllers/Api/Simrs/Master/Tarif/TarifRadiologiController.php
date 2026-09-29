<?php

namespace App\Http\Controllers\Api\Simrs\Master\Tarif;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Radiologi\MasterPemeriksaanRadiologiSementara;
use App\Models\Simrs\Penunjang\Radiologi\Mpemeriksaanradiologi;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TarifRadiologiController extends Controller
{
    public function tipe()
    {
        $data = MasterPemeriksaanRadiologiSementara::select('rs3 as tipe')->distinct()->pluck('tipe');

        return new JsonResponse($data);
    }
    public function list(Request $request)
    {
        $today = today()->toDateString();
        $status = $request->input('status', 'aktif');

        $query = MasterPemeriksaanRadiologiSementara::select(
            'rs47_sementara.*',
            DB::raw('rs47_sementara.rs4 + rs47_sementara.rs5 as tarif_non_privat'),
            DB::raw('rs47_sementara.rs6 + rs47_sementara.rs7 as tarif_privat'),
            DB::raw('rs47_sementara.pss + rs47_sementara.psp as tarif_presidential')
        );

        // Filter Status
        if ($status === 'aktif') {
            $sub = DB::table('rs47_sementara')
                ->select('rs1', DB::raw('MAX(id1) as max_id'))
                ->whereNull('tgl_hapus')
                ->where(function ($q) {
                    $q->where('hidden', '!=', '1')->orWhereNull('hidden');
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('tgl_mulai_berlaku')->orWhere('tgl_mulai_berlaku', '<=', $today);
                })
                ->groupBy('rs1');

            $query->joinSub($sub, 'latest_aktif', function ($join) {
                $join->on('rs47_sementara.id1', '=', 'latest_aktif.max_id');
            });
        } elseif ($status === 'aktif_draft') {
            $sub = DB::table('rs47_sementara')
                ->select('rs1', DB::raw('MAX(id1) as max_id'))
                ->whereNull('tgl_hapus')
                ->where(function ($q) {
                    $q->where('hidden', '!=', '1')->orWhereNull('hidden');
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('tgl_mulai_berlaku')->orWhere('tgl_mulai_berlaku', '<=', $today);
                })
                ->groupBy('rs1');

            $query->leftJoinSub($sub, 'latest_aktif', function ($join) {
                $join->on('rs47_sementara.id1', '=', 'latest_aktif.max_id');
            })
            ->whereNull('rs47_sementara.tgl_hapus')
            ->where(function ($q) {
                $q->where('rs47_sementara.hidden', '!=', '1')->orWhereNull('rs47_sementara.hidden');
            })
            ->where(function ($q) use ($today) {
                $q->where('rs47_sementara.tgl_mulai_berlaku', '>', $today)
                    ->orWhereNotNull('latest_aktif.max_id');
            });
        } elseif ($status === 'draft') {
            $query->whereNull('rs47_sementara.tgl_hapus')
                ->where(function ($q) {
                    $q->where('rs47_sementara.hidden', '!=', '1')->orWhereNull('rs47_sementara.hidden');
                })
                ->where('rs47_sementara.tgl_mulai_berlaku', '>', $today);
        } elseif ($status === 'history') {
            $sub = DB::table('rs47_sementara')
                ->select('rs1', DB::raw('MAX(id1) as max_id'))
                ->whereNull('tgl_hapus')
                ->where(function ($q) {
                    $q->where('hidden', '!=', '1')->orWhereNull('hidden');
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('tgl_mulai_berlaku')->orWhere('tgl_mulai_berlaku', '<=', $today);
                })
                ->groupBy('rs1');

            $query->leftJoinSub($sub, 'latest_aktif', function ($join) {
                $join->on('rs47_sementara.id1', '=', 'latest_aktif.max_id');
            })
            ->whereNull('rs47_sementara.tgl_hapus')
            ->where(function ($q) {
                $q->where('rs47_sementara.hidden', '!=', '1')->orWhereNull('rs47_sementara.hidden');
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('rs47_sementara.tgl_mulai_berlaku')->orWhere('rs47_sementara.tgl_mulai_berlaku', '<=', $today);
            })
            ->whereNull('latest_aktif.max_id');
        } elseif ($status === 'dihapus') {
            $query->where(function ($q) {
                $q->whereNotNull('rs47_sementara.tgl_hapus')
                    ->orWhere('rs47_sementara.hidden', '1');
            });
        }

        // Filter Tipe Radiologi
        if ($request->filled('tipe')) {
            $query->where('rs47_sementara.rs3', $request->input('tipe'));
        }

        // Pencarian
        if ($request->filled('q')) {
            $keyword = $request->input('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('rs47_sementara.rs2', 'like', '%' . $keyword . '%')
                    ->orWhere('rs47_sementara.rs1', 'like', '%' . $keyword . '%')
                    ->orWhere('rs47_sementara.rs3', 'like', '%' . $keyword . '%');
            });
        }

        $data = $query->orderBy('rs47_sementara.rs2', 'ASC')
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
                $cekNama = MasterPemeriksaanRadiologiSementara::where('rs2', $request->nama)->first();
                if ($cekNama) throw new Exception('Nama Pemeriksaan sudah ada');
                // ambil id terakhir
                $lastData = MasterPemeriksaanRadiologiSementara::orderBy('idx', 'DESC')->first();
                $cektotal = (int) substr($lastData->rs1, 2);
                $akhir = (int) $cektotal + (int) 1;
                $kode = 'RD' . str_pad($akhir, 5, '0', STR_PAD_LEFT);
            } else {;
                $cektotal = (int) substr($request->rs1, 2);
                $kode = $request->rs1;
            }
            $cekData = MasterPemeriksaanRadiologiSementara::where('rs1', $kode)->whereDate('tgl_mulai_berlaku', '>', today())->first();
            if (!$cekData) {
                $result['simpan'] = MasterPemeriksaanRadiologiSementara::create(
                    [
                        'rs1' => $kode,
                        'tgl_mulai_berlaku' => $request->tgl_mulai_berlaku,
                        'rs2' => $request->rs2,
                        'rs3' => $request->rs3 ?? '',
                        'rs4' => $request->rs4 ?? 0,
                        'rs5' => $request->rs5 ?? 0,
                        'rs6' => $request->rs6 ?? 0,
                        'rs7' => $request->rs7 ?? 0,
                        'pss' => $request->pss ?? 0,
                        'psp' => $request->psp ?? 0,
                        'dasar_perubahan' => $request->dasar_perubahan,
                        'idx' => $cektotal
                    ]
                );
            } else {
                $result['simpan'] = $cekData->update(
                    [
                        'tgl_mulai_berlaku' => $request->tgl_mulai_berlaku,
                        'rs2' => $request->rs2,
                        'rs3' => $request->rs3 ?? '',
                        'rs4' => $request->rs4 ?? 0,
                        'rs5' => $request->rs5 ?? 0,
                        'rs6' => $request->rs6 ?? 0,
                        'rs7' => $request->rs7 ?? 0,
                        'pss' => $request->pss ?? 0,
                        'psp' => $request->psp ?? 0,
                        'tgl_hapus' => null,
                        'hidden' => '',
                        'dasar_perubahan' => $request->dasar_perubahan,
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
        $cekNull = MasterPemeriksaanRadiologiSementara::where('id1', $request->id1)->first();
        if ($request->action == 'delete') {
            if ($cekNull->tgl_mulai_berlaku == null) return new JsonResponse(['message' => 'Data Ini tidak boleh dihapus'], 410);
            $data = MasterPemeriksaanRadiologiSementara::where('id1', $request->id1)->whereNotNull('tgl_mulai_berlaku')->first();
            if (!$data) return new JsonResponse(['message' => 'Data Tidak ditemukan'], 410);
            $data->delete();
            return new JsonResponse(['message' => 'Data Dihapus'], 200);
        } else {
            if ($cekNull->tgl_mulai_berlaku == null) return new JsonResponse(['message' => 'Data Ini tidak boleh dijadikan sebagai dasar penghapusan. silahkan edit data terlebih dahulu, kemudian jadikan data tersebut sebagai dasar penghapusan'], 410);
            $data = MasterPemeriksaanRadiologiSementara::where('id1', $request->id1)->whereNotNull('tgl_mulai_berlaku')->first();
            if (!$data) return new JsonResponse(['message' => 'Data Tidak ditemukan'], 410);
            $data->tgl_hapus = $request->tgl_mulai_berlaku;
            $data->hidden = '1';
            $data->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
            $data->save();
            return new JsonResponse(['message' => 'Data menjadi Dasar penghapusan tarif'], 200);
        }
    }
    public function showAgain(Request $request)
    {
        $data = MasterPemeriksaanRadiologiSementara::where('id1', $request->id1)->first();
        $data->tgl_hapus = null;
        $data->hidden = '';
        $data->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
        $data->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }
    public static function pindahKeTabelMaster()
    {
        try {
            $msg = [];
            DB::beginTransaction();
            $adaTarifBerubah = MasterPemeriksaanRadiologiSementara::whereDate('tgl_mulai_berlaku', date('Y-m-d'))->get();
            if ($adaTarifBerubah) {
                foreach ($adaTarifBerubah as $baru) {
                    $simpantindakan = Mpemeriksaanradiologi::withTrashed()->updateOrCreate(
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
                            'pss' => $baru['pss'],
                            'psp' => $baru['psp'],
                            'hidden' => $baru['hidden'],
                        ]
                    );
                    $data = Mpemeriksaanradiologi::where('rs1', $baru['rs1'])->first();
                    $dataSudahHapus = Mpemeriksaanradiologi::onlyTrashed()->where('rs1', $baru['rs1'])->first();
                    if ($data && $baru['tgl_hapus'] != null && !$dataSudahHapus) {
                        $data->delete();
                    }
                    if ($baru['tgl_hapus'] == null && $dataSudahHapus) {
                        $dataSudahHapus->restore();
                    }
                }
            }
            DB::commit();
            return ['jumlah Tarif Radiologi' => count($adaTarifBerubah)];
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
