<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Ruangan\PermintaanRetur;
use App\Models\Simrs\Penunjang\Farmasinew\Ruangan\PermintaanReturDetail;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermintaanReturRuanganController extends Controller
{
    //
    public function listPermintaan()
    {
        $req = [
            'page' => request('page') ?? 1,
            'per_page' => request('per_page') ?? 10,
        ];
        $raw = PermintaanRetur::query();
        $raw->when(request('q'), function ($q) {
            $q->where(function ($query) {
                $query->where('noreg', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('norm', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('nopermintaan', 'LIKE', '%' . request('q') . '%');
            });
        })
            ->with([
                'rinci' => function ($q) {
                    $q->with([
                        'mobat:kd_obat,nama_obat,satuan_k',
                        'headerResep:noresep,flag_permintaan_retur'
                    ])
                        ->orderBy('kdobat', 'asc');
                },
                'pegawai:nama,kdpegsimrs',
                'pasien:rs1,rs2'
            ])
            ->whereNull('flag')
            ->orderBy('tgl_permintaan', 'desc');
        $totalCount = (clone $raw)->count();
        $data = $raw->simplePaginate($req['per_page']);
        $resp = ResponseHelper::responseGetSimplePaginate($data, $req, $totalCount);
        return new JsonResponse($resp);

        // return new JsonResponse([
        //     'data' => $data,
        //     'req' => request()->all(),
        // ]);
    }
    public function getObatKeluar(Request $request)
    {
        $noresep = Resepkeluarrinci::select('noresep')->where('noreg', $request->noreg)->pluck('noresep')->toArray();
        $data = Resepkeluarheder::where('noreg', $request->noreg)
            ->with([
                // rincian nya bawa yang ga ada di rincian permintaan retur
                'rincian' => function ($q) {
                    $q->with('mobat:kd_obat,nama_obat,bentuk_sediaan,satuan_k,jenis_perbekalan')
                        ->select('resep_keluar_r.*')
                        ->leftJoin('permintaan_retur_details', function ($join) {
                            $join->on('permintaan_retur_details.noresep', '=', 'resep_keluar_r.noresep')
                                ->on('permintaan_retur_details.kdobat', '=', 'resep_keluar_r.kdobat');
                        })
                        ->whereNull('permintaan_retur_details.kdobat');
                },
                'ruanganranap',
                'poli'
            ])
            ->whereIn('noresep', $noresep)
            ->whereIn('depo', ['Gd-04010102', 'Gd-02010104'])
            ->whereNull('flag_permintaan_retur')
            ->orderBy('id', 'DESC')
            ->get();

        $list = PermintaanRetur::where('noreg', $request->noreg)
            ->with([
                'rinci' => function ($q) {
                    $q->with('mobat:kd_obat,nama_obat,satuan_k')
                        ->orderBy('kdobat', 'asc')
                        ->orderBy('depo', 'asc')
                        ->orderBy('noresep', 'asc');
                },
                'pegawai:nama,kdpegsimrs'
            ])
            // ->whereNull('flag')
            ->get();

        return new JsonResponse([
            'data' => $data,
            'list' => $list,
            'req' => $request->all(),
        ]);
    }
    public function simpanPermintaan(Request $request)
    {

        try {
            DB::connection('farmasi')->beginTransaction();
            // ambil nomor retur
            $adaPermintaan = PermintaanReturDetail::select('permintaan_retur_details.*')
                ->leftJoin('permintaan_returs', 'permintaan_returs.nopermintaan', '=', 'permintaan_retur_details.nopermintaan')
                ->whereNull('flag')
                ->where('permintaan_retur_details.noreg', $request->noreg)
                ->get();
            if (count($adaPermintaan) > 0) {
                $notrans = $adaPermintaan[0]->nopermintaan;
            } else {
                DB::connection('farmasi')->select('call ' . 'permintaan_retur(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('permintaan_retur')->first();
                $wew = $x->permintaan_retur;
                $notrans = FormatingHelper::resep($wew, 'PRT');
            }

            $headerResep = Resepkeluarheder::where('noreg', $request->noreg)->whereIn('noresep', $request->noresep)->whereNull('flag_permintaan_retur')->get();
            $toSimpan = [];
            $existing = [];
            if ($adaPermintaan) {
                $existing = $adaPermintaan->mapWithKeys(function ($item) {
                    return [$item->noresep . '|' . $item->kdobat => true];
                });
            }
            // cek sudah ada data pa belum kalo belum maukkan ke array
            foreach ($request->item as $key) {
                $checkKey = $key['noresep'] . '|' . $key['kdobat'];
                if (!isset($existing[$checkKey])) {
                    $toSimpan[] = [
                        'nopermintaan' => $notrans,
                        'noreg' => $request->noreg,
                        'norm' => $request->norm,
                        'noresep' => $key['noresep'],
                        'depo' => $key['depo'],
                        'kdobat' => $key['kdobat'],
                        'jumlah' => $key['jumlah'],
                        'retur' => $key['retur'],
                        'id_resep_keluar_r' => $key['id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            // insert
            if (!empty($toSimpan)) {
                $user = FormatingHelper::session_user();
                PermintaanReturDetail::upsert(
                    $toSimpan,
                    ['noresep', 'kdobat', 'id_resep_keluar_r'], // kolom untuk cek duplikat
                    ['jumlah', 'retur', 'updated_at'] // kolom yang di-update jika ada
                );
                // bikin header

                $res = array_unique(PermintaanReturDetail::where('noreg', $request->noreg)->pluck('noresep')->toArray());
                $depos = Resepkeluarheder::where('noreg', $request->noreg)->whereIn('noresep', $res)->distinct()->pluck('depo')->toArray();
                $head = PermintaanRetur::updateOrCreate(
                    [
                        'nopermintaan' => $notrans,
                        'noreg' => $request->noreg,
                    ],
                    [
                        'norm' => $request->norm,
                        'tgl_permintaan' => Carbon::now()->format('Y-m-d H:i:s'),
                        'depo' => $depos,
                        'kdpegsimrs' => $user['kodesimrs'],
                    ]
                );
                $head->load([
                    'rinci.mobat:kd_obat,nama_obat,satuan_k',
                    'pegawai'
                ]);
                if (count($headerResep) > 0) {
                    foreach ($headerResep as $he) {
                        $he->update(['flag_permintaan_retur' => '1']);
                    }
                }
            }

            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Berhasil disimpan',
                'notrans' => $notrans,
                'toSimpan' => $toSimpan,
                'headerResep' => $headerResep,
                'head' => $head ?? null,
                'req' => $request->all(),
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'req' => $request->all(),
            ], 410);
        }
    }
}
