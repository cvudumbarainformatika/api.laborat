<?php

namespace App\Http\Controllers\Api\Simrs\Master\Tarif;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\MpemeriksaaanLabSementara;
use App\Models\Simrs\Master\Mpemeriksaanlab;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Event\Code\Throwable;

class PemeriksaanLaboratControllr extends Controller
{
    //
    public function list(Request $request)
    {
        $today = today()->toDateString();
        $status = $request->input('status', 'aktif');

        $query = MpemeriksaaanLabSementara::select(
            'rs49_sementara.id',
            'rs49_sementara.rs1',
            'rs49_sementara.rs1 as kode',
            'rs49_sementara.rs2 as nama',
            'rs49_sementara.rs3 as hs1', // harga sarana 1
            'rs49_sementara.rs4 as hp1', // harga pelayanan 1
            DB::raw('rs49_sementara.rs3+rs49_sementara.rs4 as tf1'),
            'rs49_sementara.rs5 as hs2', // harga sarana 2
            'rs49_sementara.rs6 as hp2', // harga pelayanan 2
            DB::raw('rs49_sementara.rs5+rs49_sementara.rs6 as tf2'),
            'rs49_sementara.pss', // presiden suit sarana
            'rs49_sementara.psp', // presiden suit pelayanan
            DB::raw('rs49_sementara.pss+rs49_sementara.psp as tfps'),
            'rs49_sementara.hcus', // HCU sarana
            'rs49_sementara.hcup', // HCU pelayanan
            DB::raw('rs49_sementara.hcus+rs49_sementara.hcup as tfhcu'),
            'rs49_sementara.hcs', // Home Care sarana
            'rs49_sementara.hcp', // Home Care pelayanan
            DB::raw('rs49_sementara.hcs+rs49_sementara.hcp as tfhc'),
            'rs49_sementara.rs21 as kelompok', // nama paket 
            'rs49_sementara.rs22 as satuan',
            'rs49_sementara.rs23 as flag',
            'rs49_sementara.rs24',
            'rs49_sementara.rs25 as cito',
            'rs49_sementara.hidden',
            'rs49_sementara.nilainormal',
            'rs49_sementara.satuan',
            'rs49_sementara.tampilanurut',
            'rs49_sementara.jenislab',
            'rs49_sementara.loinc',
            'rs49_sementara.display_loinc',
            'rs49_sementara.loinc_paket',
            'rs49_sementara.display_loinc_paket',
            'rs49_sementara.tgl_mulai_berlaku',
            'rs49_sementara.tgl_hapus',
            'rs49_sementara.dasar_perubahan'
        );

        // Filter Status
        if ($status === 'aktif') {
            $sub = DB::table('rs49_sementara')
                ->select('rs1', DB::raw('MAX(id) as max_id'))
                ->whereNull('tgl_hapus')
                ->where(function ($q) {
                    $q->where('hidden', '!=', '1')->orWhereNull('hidden');
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('tgl_mulai_berlaku')->orWhere('tgl_mulai_berlaku', '<=', $today);
                })
                ->groupBy('rs1');

            $query->joinSub($sub, 'latest_aktif', function ($join) {
                $join->on('rs49_sementara.id', '=', 'latest_aktif.max_id');
            });
        } elseif ($status === 'aktif_draft') {
            $sub = DB::table('rs49_sementara')
                ->select('rs1', DB::raw('MAX(id) as max_id'))
                ->whereNull('tgl_hapus')
                ->where(function ($q) {
                    $q->where('hidden', '!=', '1')->orWhereNull('hidden');
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('tgl_mulai_berlaku')->orWhere('tgl_mulai_berlaku', '<=', $today);
                })
                ->groupBy('rs1');

            $query->leftJoinSub($sub, 'latest_aktif', function ($join) {
                $join->on('rs49_sementara.id', '=', 'latest_aktif.max_id');
            })
            ->whereNull('rs49_sementara.tgl_hapus')
            ->where(function ($q) {
                $q->where('rs49_sementara.hidden', '!=', '1')->orWhereNull('rs49_sementara.hidden');
            })
            ->where(function ($q) use ($today) {
                $q->where('rs49_sementara.tgl_mulai_berlaku', '>', $today)
                    ->orWhereNotNull('latest_aktif.max_id');
            });
        } elseif ($status === 'draft') {
            $query->whereNull('rs49_sementara.tgl_hapus')
                ->where(function ($q) {
                    $q->where('rs49_sementara.hidden', '!=', '1')->orWhereNull('rs49_sementara.hidden');
                })
                ->where('rs49_sementara.tgl_mulai_berlaku', '>', $today);
        } elseif ($status === 'history') {
            $sub = DB::table('rs49_sementara')
                ->select('rs1', DB::raw('MAX(id) as max_id'))
                ->whereNull('tgl_hapus')
                ->where(function ($q) {
                    $q->where('hidden', '!=', '1')->orWhereNull('hidden');
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('tgl_mulai_berlaku')->orWhere('tgl_mulai_berlaku', '<=', $today);
                })
                ->groupBy('rs1');

            $query->leftJoinSub($sub, 'latest_aktif', function ($join) {
                $join->on('rs49_sementara.id', '=', 'latest_aktif.max_id');
            })
            ->whereNull('rs49_sementara.tgl_hapus')
            ->where(function ($q) {
                $q->where('rs49_sementara.hidden', '!=', '1')->orWhereNull('rs49_sementara.hidden');
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('rs49_sementara.tgl_mulai_berlaku')->orWhere('rs49_sementara.tgl_mulai_berlaku', '<=', $today);
            })
            ->whereNull('latest_aktif.max_id');
        } elseif ($status === 'dihapus') {
            $query->where(function ($q) {
                $q->whereNotNull('rs49_sementara.tgl_hapus')
                    ->orWhere('rs49_sementara.hidden', '1');
            });
        }

        // Filter Kelompok / Paket
        if ($request->filled('kelompok')) {
            $query->where('rs49_sementara.rs21', $request->input('kelompok'));
        }

        // Pencarian
        if ($request->filled('q')) {
            $keyword = $request->input('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('rs49_sementara.rs2', 'like', '%' . $keyword . '%')
                    ->orWhere('rs49_sementara.rs1', 'like', '%' . $keyword . '%')
                    ->orWhere('rs49_sementara.rs21', 'like', '%' . $keyword . '%');
            });
        }

        $data = $query->orderBy('rs49_sementara.rs2', 'ASC')
            ->paginate($request->input('per_page', 10));

        $rawRes = collect($data);
        $result['data'] = $rawRes['data'];
        $result['meta'] = $rawRes->except('data');
        return new JsonResponse($result);
    }
    public function listKelompok()
    {
        $data = MpemeriksaaanLabSementara::select('rs21 as kelompok')->distinct()->pluck('kelompok');

        return new JsonResponse($data);
    }
    public function listJenis()
    {
        $data = MpemeriksaaanLabSementara::select('jenislab as jenislab')->distinct()->pluck('jenislab');

        return new JsonResponse($data);
    }
    public function simpan(Request $request)
    {
        try {
            DB::beginTransaction();
            if ($request->kode == '' || $request->kode == null) {
                $cekNama = MpemeriksaaanLabSementara::where('rs2', $request->nama)->first();
                if ($cekNama) throw new Exception('Nama Pemeriksaan sudah ada');
                $cektotal = MpemeriksaaanLabSementara::count();
                $akhir = (int) $cektotal + (int) 1;
                if ($request->jenislab == 'PK' || $request->jenislab == '') $kode = 'LAB' . str_pad($akhir, 4, '0', STR_PAD_LEFT);
                else $kode = $request->jenislab . str_pad($akhir, 4, '0', STR_PAD_LEFT);
            } else {
                $kode = $request->kode;
            }

            $cekData = MpemeriksaaanLabSementara::where('rs1', $kode)->whereDate('tgl_mulai_berlaku', '>', today())->first();
            if (!$cekData) {
                $result['simpan'] = MpemeriksaaanLabSementara::create(
                    [
                        'rs1' => $kode,
                        'tgl_mulai_berlaku' => $request->tgl_mulai_berlaku,
                        'rs2' => $request->nama,
                        'rs3' => $request->hs1 ?? 0,
                        'rs4' => $request->hp1 ?? 0,
                        'rs5' => $request->hs2 ?? 0,
                        'rs6' => $request->hp2 ?? 0,
                        'pss' => $request->pss ?? 0,
                        'psp' => $request->psp ?? 0,
                        'hcus' => $request->hcus ?? 0,
                        'hcup' => $request->hcup ?? 0,
                        'hcs' => $request->hcs ?? 0,
                        'hcp' => $request->hcp ?? 0,
                        'rs21' => $request->kelompok ?? '',
                        'jenislab' => $request->jenislab ?? '',
                        'dasar_perubahan' => $request->dasar_perubahan,
                    ]
                );
            } else {
                $result['simpan'] = $cekData->update(
                    [
                        // 'rs1' => $kode,
                        'tgl_mulai_berlaku' => $request->tgl_mulai_berlaku,
                        'tgl_hapus' => null,
                        'hidden' => '',
                        'rs2' => $request->nama,
                        'rs3' => $request->hs1 ?? 0,
                        'rs4' => $request->hp1 ?? 0,
                        'rs5' => $request->hs2 ?? 0,
                        'rs6' => $request->hp2 ?? 0,
                        'pss' => $request->pss ?? 0,
                        'psp' => $request->psp ?? 0,
                        'hcus' => $request->hcus ?? 0,
                        'hcup' => $request->hcup ?? 0,
                        'hcs' => $request->hcs ?? 0,
                        'hcp' => $request->hcp ?? 0,
                        'rs21' => $request->kelompok ?? '',
                        'jenislab' => $request->jenislab ?? '',
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
        $cekNull = MpemeriksaaanLabSementara::where('id', $request->id)->first();
        if ($request->action == 'delete') {
            if ($cekNull->tgl_mulai_berlaku == null) return new JsonResponse(['message' => 'Data Ini tidak boleh dihapus'], 410);
            $caritindakan = MpemeriksaaanLabSementara::where('id', $request->id)->whereNotNull('tgl_mulai_berlaku')->first();
            if (!$caritindakan) return new JsonResponse(['message' => 'Data Tidak ditemukan'], 410);
            $caritindakan->delete();

            return new JsonResponse(['message' => 'Data Dihapus'], 200);
        } else {
            if ($cekNull->tgl_mulai_berlaku == null) return new JsonResponse(['message' => 'Data Ini tidak boleh dijadikan sebagai dasar penghapusan. silahkan edit data terlebih dahulu, kemudian jadikan data tersebut sebagai dasar penghapusan'], 410);
            $caritindakan = MpemeriksaaanLabSementara::where('id', $request->id)->whereNotNull('tgl_mulai_berlaku')->first();
            if (!$caritindakan) return new JsonResponse(['message' => 'Data Tidak ditemukan'], 410);
            $caritindakan->tgl_hapus = $request->tgl_mulai_berlaku;
            $caritindakan->hidden = '1';
            $caritindakan->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
            $caritindakan->save();
            return new JsonResponse(['message' => 'Data menjadi Dasar penghapusan tindakan'], 200);
        }
    }
    public function showAgain(Request $request)
    {
        $caritindakan = MpemeriksaaanLabSementara::where('id', $request->id)->first();

        $caritindakan->tgl_hapus = null;
        $caritindakan->hidden = '';
        $caritindakan->tgl_mulai_berlaku = $request->tgl_mulai_berlaku;
        $caritindakan->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }
    public static function pindahKeTabelMaster()
    {
        try {
            $msg = [];
            DB::beginTransaction();
            $adaTarifBerubah = MpemeriksaaanLabSementara::whereDate('tgl_mulai_berlaku', date('Y-m-d'))->get();
            if ($adaTarifBerubah) {
                foreach ($adaTarifBerubah as $baru) {
                    $data = Mpemeriksaanlab::where('rs1', $baru['rs1'])->first();
                    $dataSudahHapus = Mpemeriksaanlab::onlyTrashed()->where('rs1', $baru['rs1'])->first();
                    $simpantindakan = Mpemeriksaanlab::withTrashed()->updateOrCreate(
                        [
                            'rs1' => $baru['rs1']
                        ],
                        [
                            'rs2' => $baru['rs2'],
                            'rs3' => $baru['rs3'],
                            'rs4' => $baru['rs4'],
                            'rs5' => $baru['rs5'],
                            'rs6' => $baru['rs6'],
                            'rs21' => $baru['rs21'],
                            'rs22' => $baru['rs22'],
                            'rs23' => $baru['rs23'],
                            'rs24' => $baru['rs24'],
                            'rs25' => $baru['rs25'],
                            'pss' => $baru['pss'],
                            'psp' => $baru['psp'],
                            'hcus' => $baru['hcus'],
                            'hcup' => $baru['hcup'],
                            'hcs' => $baru['hcs'],
                            'hcp' => $baru['hcp'],
                            'hidden' => $baru['hidden'],
                            'jenislab' => $baru['jenislab'],
                        ]
                    );
                    if ($data && $baru['tgl_hapus'] != null && !$dataSudahHapus) {
                        $data->delete();
                    }
                    if ($baru['tgl_hapus'] == null && $dataSudahHapus) {
                        $dataSudahHapus->restore();
                    }
                }
            }
            DB::commit();
            return ['jumlah Pemeriksaan lab' => count($adaTarifBerubah)];
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
