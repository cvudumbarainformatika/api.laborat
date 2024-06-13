<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KonsinyasiController extends Controller
{
    //
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
        return new JsonResponse($resep);
        $rwpenye = PenerimaanHeder::select('kdpbf')->where('jenis_penerimaan', '=', 'Konsinyasi')->whereNull('tgl_bast')->distinct('kdpbf')->get();
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
                'resep:noresep,norm,noreg,dokter',
                'resep.dokter:kdpegsimrs,nama',
                'resep.datapasien:rs1,rs2',
                'rincian',
                'penerimaanrinci'
                // 'rincian' => function ($r) {
                //     $r->select('resep_keluar_r.noresep', 'resep_keluar_r.kdobat', 'resep_keluar_r.jumlah', 'resep_keluar_r.harga_beli')
                //         ->leftJoin('persiapan_operasi_rincis', function ($j) {
                //             $j->on('persiapan_operasi_rincis.noresep', '=', 'resep_keluar_r.noresep')
                //                 ->on('persiapan_operasi_rincis.kd_obat', '=', 'resep_keluar_r.kdobat');
                //         });
                // },
                // 'penerimaanrinci' => function ($p) {
                //     $p->select(
                //         'penerimaan_r.nopenerimaan',
                //         'penerimaan_r.bebaspajak',
                //         'penerimaan_r.kdobat',
                //         'penerimaan_r.satuan_kcl',
                //         'penerimaan_r.harga_kcl',
                //         'penerimaan_r.ppn',
                //         'penerimaan_r.ppn_rp_kecil',
                //         'penerimaan_r.diskon',
                //         'penerimaan_r.diskon_rp_kecil',
                //         'penerimaan_r.harga_netto_kecil',
                //         'penerimaan_r.jml_terima_k',
                //     )
                //         ->leftJoin('persiapan_operasi_distribusis', function ($j) {
                //             $j->on('persiapan_operasi_distribusis.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
                //                 ->on('persiapan_operasi_distribusis.kd_obat', '=', 'penerimaan_r.kdobat');
                //         })
                //         ->with('header:nopenerimaan,tglpenerimaan');
                // }

            ])
            ->leftJoin('persiapan_operasi_distribusis', function ($q) {
                $q->on('persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                    ->on('persiapan_operasi_distribusis.kd_obat', '=', 'persiapan_operasi_rincis.kd_obat');
            })
            ->where('persiapan_operasi_rincis.jumlah_resep', '>', 0)
            ->where('persiapan_operasi_rincis.status_konsinyasi', '=', '1')
            ->whereIn('persiapan_operasi_distribusis.nopenerimaan', $pene)
            ->get();
        $data = $resep;
        // $data['pene'] = $pene;
        return new JsonResponse($data);
    }
}
