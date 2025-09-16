<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Etc;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Penunjang\Farmasinew\TelaahResep;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelaahResepController extends Controller
{
    //
    public function getData()
    {
        $req = [
            'per_page' => request('per_page') ?? 10,
        ];
        $from = (request('from') ?? Carbon::now()->format('Y-m-d')) . ' 00:00:00';
        $to = (request('to') ?? Carbon::now()->format('Y-m-d')) . ' 23:59:59';
        $raw = TelaahResep::query()
            ->whereBetween('telaah_reseps.created_at', [$from, $to])
            ->when(request('kode_ruang') != 'all', function ($q) {
                $q->select('telaah_reseps.*')
                    ->leftJoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'telaah_reseps.noresep')
                    ->where('resep_keluar_h.depo', '=', request('kode_ruang'));
            })
            ->when(request('user_input') != 'all', function ($q) {
                $q->where('user_input', request('user_input'));
            })
            ->with(
                'petugas:id,nama,nip,nik',
                'pasien:rs1,rs2,rs16', // rs16 itu tgl lahir
                'resep:id,noresep,depo,dokter,ruangan,tgl_permintaan,tgl_kirim,tgl_diterima,tgl_selesai',
                'resep.ketdokter:kdpegsimrs,nama,nip,nik',
                'resep.poli:rs1,rs2',
                'resep.ruanganranap:rs1,rs2',
            );
        $totalCount = (clone $raw)->count();
        $data = $raw->simplePaginate(request('per_page'));
        $resp = ResponseHelper::responseGetSimplePaginate($data, $req, $totalCount);
        return new JsonResponse($resp);
        // return new JsonResponse([
        //     'data' => $data,
        //     'req' => request()->all(),
        // ]);
    }
    public function getPegawai()
    {

        $user = TelaahResep::select('user_input')->distinct()->pluck('user_input')->toArray();
        $peg = Petugas::select('nama', 'id', 'kdpegsimrs')->whereIn('id', $user)->get();
        $data['user'] = $user;
        $data['data'] = $peg;
        return new JsonResponse($data);
    }
}
