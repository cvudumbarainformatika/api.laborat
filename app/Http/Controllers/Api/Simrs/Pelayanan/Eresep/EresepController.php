<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Eresep;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

;

class EresepController extends Controller
{
    public function listresepbynorm()
    {
        $history = Resepkeluarheder::with(
            [
                'rincian.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'rincianracik.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'permintaanresep.aturansigna:signa,jumlah',
                'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,kekuatan_dosis,status_kronis,kelompok_psikotropika',
                'poli',
                'info',
                'ruanganranap',
                'sistembayar',
                'sep:rs1,rs8',
                'dokter:kdpegsimrs,nama',
                'datapasien' => function ($quer) {
                    $quer->select(
                        'rs1',
                        'rs2 as nama',
                        'rs46 as noka',
                        'rs16 as tgllahir',
                        DB::raw('concat(rs4," KEL ",rs5," RT ",rs7," RW ",rs8," ",rs6," ",rs11," ",rs10) as alamat'),
                    );
                }
            ]
        )
            ->where('norm', request('norm'))
            ->orderBy('flag', 'ASC')
            ->orderBy('tgl_permintaan', 'ASC')
            ->get()
            ->chunk(10);
        // return new JsonResponse(request()->all());
        $collapsed = $history->collapse();


        return new JsonResponse($collapsed->all());
    }
}
