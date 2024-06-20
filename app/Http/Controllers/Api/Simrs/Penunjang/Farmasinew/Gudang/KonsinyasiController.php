<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastKonsinyasi;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\DetailBastKonsinyasi;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KonsinyasiController extends Controller
{
    //
    public function perusahaan()
    {
        $raw = BastKonsinyasi::select('kdpbf')->whereNull('tgl_bast')->distinct()->get();
        $penye = collect($raw)->map(function ($p) {
            return $p->kdpbf;
        });
        $penyedia = Mpihakketiga::select('kode', 'nama')->whereIn('kode', $penye)->get();
        return new JsonResponse($penyedia);
    }
    public function notranskonsi()
    {
        $data = BastKonsinyasi::whereNull('tgl_bast')->where('kdpbf', request('kdpbf'))->get();

        return new JsonResponse($data);
    }
    public function transkonsiwithrinci()
    {
        $data = BastKonsinyasi::with('rinci')
            ->whereNull('tgl_bast')
            ->where('kdpbf', request('kdpbf'))
            ->where('notranskonsi', request('notranskonsi'))
            ->get();

        return new JsonResponse($data);
    }
    public function getPenyedia()
    {
        $res = PersiapanOperasiRinci::select(
            'persiapan_operasi_rincis.nopermintaan',
            'persiapan_operasi_rincis.noresep',
            'persiapan_operasi_rincis.status_konsinyasi',
            'persiapan_operasi_rincis.kd_obat',
            'persiapan_operasi_rincis.jumlah_resep',
            'persiapan_operasi_distribusis.nopenerimaan',
            'persiapan_operasi_distribusis.jumlah',
            'persiapan_operasi_distribusis.jumlah_retur',
        )->leftJoin('persiapan_operasi_distribusis', function ($q) {
            $q->on('persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                ->on('persiapan_operasi_distribusis.kd_obat', '=', 'persiapan_operasi_rincis.kd_obat');
        })
            ->where('persiapan_operasi_rincis.jumlah_resep', '>', 0)
            ->where('persiapan_operasi_rincis.status_konsinyasi', '=', '1')
            ->whereNull('persiapan_operasi_rincis.dibayar')
            ->groupBy('persiapan_operasi_distribusis.nopenerimaan')
            ->get();
        $resep = collect($res)->map(function ($q) {
            return $q->nopenerimaan;
        });
        // return new JsonResponse($resep);
        $rwpenye = PenerimaanHeder::select('kdpbf')->where('jenis_penerimaan', '=', 'Konsinyasi')
            ->whereIn('nopenerimaan', $resep)
            ->distinct('kdpbf')->get();
        $penye = collect($rwpenye)->map(function ($p) {
            return $p->kdpbf;
        });
        $penyedia = Mpihakketiga::select('kode', 'nama')->whereIn('kode', $penye)->get();
        return new JsonResponse($penyedia);
    }
    public function getListPemakaianKonsinyasi()
    {
        $rwpene = PenerimaanHeder::select('nopenerimaan')
            ->where('jenis_penerimaan', '=', 'Konsinyasi')
            ->where('kdpbf', '=', request('penyedia'))
            ->whereNull('tgl_bast')
            ->distinct('nopenerimaan')
            ->get();
        $pene = collect($rwpene)->map(function ($p) {
            return $p->nopenerimaan;
        });
        $resep = PersiapanOperasiRinci::select(
            'persiapan_operasi_rincis.nopermintaan',
            'persiapan_operasi_rincis.noresep',
            'persiapan_operasi_rincis.status_konsinyasi',
            'persiapan_operasi_rincis.kd_obat',
            'persiapan_operasi_rincis.jumlah_resep',
            'persiapan_operasi_distribusis.nopenerimaan',
            'persiapan_operasi_distribusis.jumlah',
            'persiapan_operasi_distribusis.jumlah_retur',
        )
            ->with([
                'obat:kd_obat,nama_obat,satuan_k',
                'header',
                'resep:noresep,norm,noreg,dokter,tgl_permintaan',
                'resep.dokter:kdpegsimrs,nama',
                'resep.datapasien:rs1,rs2',
                'rincian',
                // 'penerimaanrinci'
                // 'rincian' => function ($r) {
                //     $r->select('resep_keluar_r.noresep', 'resep_keluar_r.kdobat', 'resep_keluar_r.jumlah', 'resep_keluar_r.harga_beli')
                //         ->leftJoin('persiapan_operasi_rincis', function ($j) {
                //             $j->on('persiapan_operasi_rincis.noresep', '=', 'resep_keluar_r.noresep')
                //                 ->on('persiapan_operasi_rincis.kd_obat', '=', 'resep_keluar_r.kdobat');
                //         });
                // },
                'penerimaanrinci' => function ($p) {
                    $p->select(
                        'penerimaan_r.nopenerimaan',
                        'penerimaan_r.bebaspajak',
                        'penerimaan_r.kdobat',
                        'penerimaan_r.satuan_kcl',
                        'penerimaan_r.harga_kcl',
                        'penerimaan_r.ppn',
                        'penerimaan_r.ppn_rp_kecil',
                        'penerimaan_r.diskon',
                        'penerimaan_r.diskon_rp_kecil',
                        'penerimaan_r.harga_netto_kecil',
                        'penerimaan_r.jml_terima_k',
                    )
                        ->leftJoin('persiapan_operasi_distribusis', function ($j) {
                            $j->on('persiapan_operasi_distribusis.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
                                ->on('persiapan_operasi_distribusis.kd_obat', '=', 'penerimaan_r.kdobat');
                        })
                        ->with('header:nopenerimaan,tglpenerimaan');
                }

            ])
            ->leftJoin('persiapan_operasi_distribusis', function ($q) {
                $q->on('persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                    ->on('persiapan_operasi_distribusis.kd_obat', '=', 'persiapan_operasi_rincis.kd_obat');
            })
            ->where('persiapan_operasi_rincis.jumlah_resep', '>', 0)
            ->where('persiapan_operasi_rincis.status_konsinyasi', '=', '1')
            ->whereIn('persiapan_operasi_distribusis.nopenerimaan', $pene)
            ->whereNull('dibayar')
            ->get();
        $data = $resep;
        // $data['pene'] = $pene;
        return new JsonResponse($data);
    }
    public function simpanListKonsinyasi(Request $request)
    {
        if (count($request->items) <= 0) {
            return new JsonResponse([
                'message' => 'Tidak ada Data Barang',

                $request->all()
            ], 410);
        }

        try {
            DB::connection('farmasi')->beginTransaction();
            $user = FormatingHelper::session_user();
            if (!$request->notranskonsi) {
                $procedure = 'nokonsinyasi(@nomor)';
                $colom = 'konsinyasi';
                $lebel = 'TR-KONS';
                DB::connection('farmasi')->select('call ' . $procedure);
                $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
                $wew = $x[0]->$colom;
                $notranskonsi = FormatingHelper::resep($wew, $lebel);
            } else {
                $notranskonsi = $request->notranskonsi;
            }
            $create = date('Y-m-d H:i:s');
            $head = BastKonsinyasi::updateOrCreate(
                [
                    'notranskonsi' => $notranskonsi,
                    'kdpbf' => $request->penyedia,
                ],
                [
                    'tgl_trans' => $request->tgl_trans,
                    'jumlah_konsi' => $request->jumlah_konsi,
                    'user_konsi' => $user['kodesimrs'],

                ]
            );
            if (!$head) {
                return new JsonResponse([
                    'message' => 'Gagal Menyimpan Head Transaksi',
                    'notranskonsi' => $notranskonsi,
                    'user' => $user,
                    'req' => $request->all()
                ], 410);
            }
            // rinci
            $rinci = [];
            foreach ($request->items as $key) {
                $temp = [
                    'notranskonsi' => $notranskonsi,
                    'nopermintaan' => $key['nopermintaan'],
                    'nopenerimaan' => $key['nopenerimaan'],
                    'kdobat' => $key['kdobat'],
                    'tgl_pakai' => $key['tgl_pakai'],
                    'tgl_penerimaan' => $key['tgl_penerimaan'],
                    'dokter' => $key['dokter'],
                    'noresep' => $key['noresep'],
                    'noreg' => $key['noreg'],
                    'norm' => $key['norm'],
                    'jumlah' => $key['jumlah'],
                    'harga' => $key['harga'],
                    'diskon' => $key['diskon'],
                    'ppn' => $key['ppn'],
                    'diskon_rp' => $key['diskon_rp'],
                    'ppn_rp' => $key['ppn_rp'],
                    'harga_net' => $key['harga_net'],
                    'subtotal' => $key['subtotal'],
                    'created_at' => $create,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $rinci[] = $temp;
            }

            // hapus resep yang ada
            $delRin = DetailBastKonsinyasi::where('notranskonsi', $notranskonsi)->delete();
            // isi
            $ins = DetailBastKonsinyasi::insert($rinci);

            // update dibayar rinci permintaan operasi
            $datanya = DetailBastKonsinyasi::where('notranskonsi', $notranskonsi)->get();
            $rina = [];
            foreach ($datanya as $det) {
                $rinciPermintaanOP = PersiapanOperasiRinci::where('nopermintaan', $det->nopermintaan)
                    ->where('noresep', $det->noresep)
                    ->where('kd_obat', $det->kdobat)
                    ->first();
                $rina[] = $rinciPermintaanOP;
                if ($rinciPermintaanOP) {
                    $rinciPermintaanOP->update(['dibayar' => '1']);
                }
            }

            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'List Sudah Disimpan',
                'notranskonsi' => $notranskonsi,
                'rinci' => $rinci,
                'user' => $user,
                'datanya' => $datanya,
                'rina' => $rina,
                'req' => $request->all()
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'Ada Kesalahan, Semua Data Gagal Disimpan',
                'result' => 'err ' . $e,
            ], 410);
        }
    }
    public function listKonsinyasi()
    {
        $data = BastKonsinyasi::with([
            'rinci.obat:kd_obat,nama_obat,satuan_k',
            'rinci.iddokter:kdpegsimrs,nama',
            'rinci.pasien:rs1,rs2',
            'penyedia:kode,nama',
            'konsi:kdpegsimrs,nama',
            'bast:kdpegsimrs,nama',
            'bayar:kdpegsimrs,nama',
        ])
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }
    public function bastKonsinyasi()
    {
        $data = BastKonsinyasi::with([
            'rinci.obat:kd_obat,nama_obat,satuan_k',
            'rinci.iddokter:kdpegsimrs,nama',
            'rinci.pasien:rs1,rs2',
            'penyedia:kode,nama',
            'konsi:kdpegsimrs,nama',
            'bast:kdpegsimrs,nama',
            'bayar:kdpegsimrs,nama',
        ])
            ->whereNotNull('tgl_bast')
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }
}
