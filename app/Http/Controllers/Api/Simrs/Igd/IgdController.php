<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IgdController extends Controller
{
    public function kunjunganpasienigd()
    {
        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('to') . ' 00:00:00';
            $tglx = request('from') . ' 23:59:59';
        }
        $status = request('status') ?? '';
        $kunjungan = KunjunganPoli::select(
            'rs17.rs1', // iki tak munculne maneh gawe relasi with
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs17.rs19 as flagpelayanan',
            'kepegx.pegawai.nama as dokter',
            //'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs19 as pendidikan',
            'rs15.rs22 as agama',
            'rs15.rs37 as templahir',
            'rs15.rs39 as suku',
            'rs15.rs40 as jenispasien',
            'rs15.rs46 as noka',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
            'rs15.bahasa as bahasa',
            'rs15.bacatulis as bacatulis',
            'rs15.kdhambatan as kdhambatan',
            'rs15.rs2 as name',
            'rs222.rs8 as sep',
            'gencons.norm as generalconsent',
            'gencons.ttdpasien as ttdpasien',
            'rs17.rs19 as statpasien'
            // 'bpjs_respon_time.taskid as taskid',
            // TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15 . rs16, now()), rs15 . rs16), now(), " Hari ")
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
         //   ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
            ->leftjoin('gencons', 'gencons.norm', '=', 'rs17.rs2')
            // ->leftjoin('bpjs_respon_time', 'bpjs_respon_time.noreg', '=', 'rs17.rs1')
            ->whereBetween('rs17.rs3', [$tgl, $tglx])
            ->where('rs17.rs8', 'POL014')
            // ->where(function ($q) {
            //     // 'rs9.rs9', '=', request('kdbayar') ?? 'BPJS'
            //     if (request('kdbayar') !== 'ALL') {
            //         $q->where('rs9.rs9', '=', 'BPJS');
            //     }
            // })
            ->where(function ($sts) use ($status) {
                if ($status !== 'all') {
                    if ($status === '') {
                        $sts->where('rs17.rs19', '!=', '1');
                    } else {
                        $sts->where('rs17.rs19', '=', $status);
                    }
                }
            })
            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                 //   ->orWhere('kepegx.pegawai.nama', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            })
            ->with(
                ['generalcons:norm,ttdpasien,ttdpetugas,hubunganpasien'])
            ->groupBy('rs17.rs1')
            ->orderby('rs17.rs3', 'DESC')
            ->paginate(request('per_page'));

        return new JsonResponse( $kunjungan);
    }

    public function terimapasien(Request $request)
    {
        $terima = KunjunganPoli::where('rs1', $request->noreg)->first();
        $terima->rs19 = '1';
        $terima->save();

        return new JsonResponse(['data' => $terima], 200);
    }
}
