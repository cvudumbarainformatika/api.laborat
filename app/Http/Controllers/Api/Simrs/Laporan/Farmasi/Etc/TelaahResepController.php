<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Etc;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Penunjang\Farmasinew\TelaahResep;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        if (request('jenis') == 'Rinci') {
            $raw = TelaahResep::query();
            $raw
                ->whereBetween('telaah_reseps.created_at', [$from, $to])
                ->when(request('kode_ruang') != 'all', function ($q) {
                    $q->select('telaah_reseps.*')
                        ->leftJoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'telaah_reseps.noresep')
                        ->where('resep_keluar_h.depo', '=', request('kode_ruang'));
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
        } else {
            // $peg = Petugas::query()
            //     ->select('kepegx.pegawai.nama', 'kepegx.pegawai.id', 'kepegx.pegawai.kdpegsimrs', DB::raw('COUNT(farmasi.telaah_reseps.id) as total_telaah'))
            //     ->leftJoin('farmasi.telaah_reseps', 'farmasi.telaah_reseps.user_input', '=', 'kepegx.pegawai.kdpegsimrs')
            //     ->whereBetween('farmasi.telaah_reseps.created_at', [$from, $to])
            //     ->when(request('kode_ruang') != 'all', function ($q) {
            //         $q->leftJoin('farmasi.resep_keluar_h', 'farmasi.resep_keluar_h.noresep', '=', 'farmasi.telaah_reseps.noresep')
            //             ->where('farmasi.resep_keluar_h.depo', request('kode_ruang'));
            //     })
            //     ->groupBy('kepegx.pegawai.id');
            $rekap = TelaahResep::query()
                ->select(
                    'telaah_reseps.user_input',
                    DB::raw('COUNT(telaah_reseps.id) as total_telaah')
                )
                ->whereBetween('telaah_reseps.created_at', [$from, $to])
                ->when(request('kode_ruang') != 'all', function ($q) {
                    $q->join('resep_keluar_h as r', 'r.noresep', '=', 'telaah_reseps.noresep')
                        ->where('r.depo', request('kode_ruang'));
                })
                ->groupBy('telaah_reseps.user_input');
            $user = (clone $rekap)->pluck('user_input')->toArray();
            $peg = Petugas::query()
                ->select(
                    'pegawai.id',
                    'pegawai.nama',
                    'pegawai.kdpegsimrs',
                    'rekap.total_telaah'
                )
                ->leftJoinSub($rekap, 'rekap', function ($join) {
                    $join->on('rekap.user_input', '=', 'pegawai.kdpegsimrs');
                })->whereIn('kdpegsimrs', $user);


            $totalCount = (clone $peg)->count();
            $data = $peg->simplePaginate(request('per_page'));
            $resp = ResponseHelper::responseGetSimplePaginate($data, $req, $totalCount);
        }


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
