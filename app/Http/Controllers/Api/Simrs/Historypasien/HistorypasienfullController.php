<?php

namespace App\Http\Controllers\Api\Simrs\Historypasien;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistorypasienfullController extends Controller
{
    public function historypasienfull()
    {
        $norm = request('norm');
        $historyx = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs2 as norm',
            'rs17.rs3 as tanggal',
            'rs19.rs2 as ruangan',
            'rs21.rs2 as dpjp'

        )
            ->join('rs19', 'rs19.rs1', '=', 'rs17.rs8')
            ->join('rs21', 'rs21.rs1', '=', 'rs17.rs9')
            ->where('rs17.rs2', $norm);
        $history = Kunjunganranap::select(
            'rs23.rs1',
            'rs23.rs2 as norm',
            'rs23.rs3 as tanggal',
            'rs24.rs2 as ruangan',
            'rs21.rs2 as dpjp'
        )
            ->join('rs24', 'rs24.rs1', '=', 'rs23.rs5')
            ->join('rs21', 'rs21.rs1', '=', 'rs23.rs10')
            ->where('rs23.rs2', $norm)
            ->union($historyx)
            ->with(
                [
                    'anamnesis',
                    'pemeriksaanfisik' => function ($p) {
                        $p->with(['gambars','detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                            ->orderBy('id', 'DESC');
                    },
                    'diagnosa' => function ($a) {
                        $a->with(['masterdiagnosa'])
                            ->orderBy('id', 'DESC');
                    },
                    //    'diagnosa.masterdiagnosa:rs1,rs4',
                    'tindakan' => function ($t) {
                        $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url','sambungan:rs73_id,ket')
                            ->orderBy('id', 'DESC');
                    },
                    //    'tindakan.mastertindakan:rs1,rs2',
                    'laborat' => function ($a) {
                        $a->with(['pemeriksaanlab'])
                            ->orderBy('id', 'DESC');
                    },
                    //    'laborat.pemeriksaanlab:rs1,rs2,rs21,nilainormal,satuan',
                    'laborats',
                    'transradiologi:rs1,rs4',
                    'transradiologi.relmasterpemeriksaan:rs1,rs2,rs3,kdmeta',
                    'hasilradiologi',
                    'apotekranap',
                    'apotekranap.masterobat',
                    'apotekranaplalu',
                    'apotekranaplalu.masterobat',
                    'apotekranapracikanheder',
                    'apotekranapracikanheder.apotekranapracikanrinci',
                    'apotekranapracikanheder.apotekranapracikanrinci.masterobat',
                    'apotekranapracikanhederlalu',
                    'apotekranapracikanhederlalu.apotekranapracikanrincilalu',
                    'apotekranapracikanhederlalu.apotekranapracikanrincilalu.masterobat',
                    'apotekrajal',
                    'apotekrajal.masterobat',
                    'apotekrajalpolilalu.masterobat',
                    'apotekracikanrajal',
                    'apotekracikanrajal.masterobat',
                    'apotekracikanrajallalu',
                    'apotekracikanrajallalu.masterobat',
                    'dokumenluar'=> function($a){
                        $a->with(['pegawai:id,nama']);
                    },
                    'kamaroperasi' => function ($kamaroperasi) {
                        $kamaroperasi->with(['mastertindakanoperasi']);
                    }
                ]
            )
            ->orderby('tanggal', 'DESC')
            ->get();
            //->paginate(request('per_page'));
        return new JsonResponse(['data' => $history]);
    }
}
