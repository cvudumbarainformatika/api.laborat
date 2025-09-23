<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Pembayaran;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CariKarcisController extends Controller
{
    public function carikarcis()
    {
        $noreg = request('noreg');
        $data = Pembayaran::where('rs1', $noreg)->where('rs3', 'K2#')->first();
        return new JsonResponse(['data' => $data], 200);
    }

    public function cariobat()
    {
        $noreg = request('noreg');
        $data = Resepkeluarheder::with(
            [
                'rincian.mobat:kd_obat,nama_obat,satuan_k',
                'rincianracik.mobat:kd_obat,nama_obat,satuan_k',
                'retur' => function ($ret) {
                    $ret->with([
                            'rinci'
                        ]);
                },
            ]
        )
        ->where('noreg', $noreg)
        ->whereIn('flag', ['3', '4'])
        ->get();

       $R_racikan = Resepkeluarheder::select(
            'resep_keluar_racikan_r.noresep',
            'resep_keluar_racikan_r.namaracikan',
            DB::raw('SUM(resep_keluar_racikan_r.nilai_r) as subtotal')
        )
        ->Join('resep_keluar_racikan_r', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
        ->where('resep_keluar_h.noreg', $noreg)
        ->groupBy('resep_keluar_racikan_r.noresep', 'resep_keluar_racikan_r.namaracikan')
        ->get()
        ->values();

        return new JsonResponse(
            [
                'data' => $data,
                'racikan_R' => $R_racikan,
            ], 200);
    }

    public function caritindakan()
    {
        $noreg = request('noreg');
        $data = Tindakan::from('rs73')
        ->select(
            'rs1',
            'rs2',
            'rs3',
            DB::raw('SUM( (rs7 + rs13) * rs5 ) as total')
        )
        ->where('rs1', $noreg)
        ->groupBy('rs2')
        ->get();
        return new JsonResponse($data);
    }

    public function caritindakanpsikologi()
    {
        $noreg = request('noreg');
        $data =  DB::table('psikologi_trans')
        ->join('rs30', 'rs30.rs1', '=', 'psikologi_trans.rs4')
        ->select('psikologi_trans.rs2 as nota','psikologi_trans.rs3 as tgl',
            'rs30.rs2 as keterangan',
            DB::raw('((psikologi_trans.rs7 + psikologi_trans.rs13) * psikologi_trans.rs5) as subtotal')
        )
        ->where('psikologi_trans.rs1', $noreg)
        ->where('psikologi_trans.rs22', '<>', 'OPERASI')
        ->groupBy('psikologi_trans.rs2')
        ->get();
        return new JsonResponse($data);
    }

    public function caritindakanoperasi()
    {
        $noreg = request('noreg');
        $data = Tindakan::from('rs54')
        ->select(
            'rs1',
            'rs2',
            'rs3',
            DB::raw('SUM( (rs5 + rs6 + rs7) * rs8 ) as total')
        )
        ->where('rs1', $noreg)
        ->groupBy('rs2')
        ->get();
        return new JsonResponse($data);
    }

    public function carilaborat()
    {
        $noreg = trim(request('noreg'));

    $qNormal = DB::table('rs51')
        ->join('rs49', 'rs49.rs1', '=', 'rs51.rs4')
        ->where('rs51.rs1', $noreg)
        ->where('rs49.rs21', '')
        ->selectRaw('rs51.rs2 AS nota,rs51.rs3, ((rs51.rs6 + rs51.rs13) * rs51.rs5) AS subtotal');

    // Baris khusus: rs49.rs21 <> '' -> ambil SATU harga per nota (contoh: MAX)
    $qKhusus = DB::table('rs51')
        ->join('rs49', 'rs49.rs1', '=', 'rs51.rs4')
        ->where('rs51.rs1', $noreg)
        ->where('rs49.rs21', '<>', '')
        ->groupBy('rs51.rs2') // satu baris per nota
        ->selectRaw('rs51.rs2 AS nota,rs51.rs3, MAX((rs51.rs6 + rs51.rs13) * rs51.rs5) AS subtotal');

    // UNION lalu SUM per nota
    $union = $qNormal->unionAll($qKhusus);

    $result = DB::table(DB::raw("({$union->toSql()}) AS vx"))
        ->mergeBindings($union)
        ->selectRaw('vx.nota,vx.rs3, SUM(vx.subtotal) AS total_subtotal')
        ->groupBy('vx.nota')
        ->orderBy('vx.nota')
        ->get();
        return new JsonResponse($result);
    }

    public function cariradiologi()
    {
        $noreg = request('noreg');
        $data = Transradiologi::from('rs48')
        ->select(
            'rs1',
            'rs2',
            'rs3',
            DB::raw('SUM( (rs6 + rs8) * rs24 ) as total')
        )
        ->where('rs1', $noreg)
        ->groupBy('rs2')
        ->get();
        return new JsonResponse($data);
    }

    public function getSharingRajal()
    {
        $noreg = trim(request('noreg'));

        $data = DB::table('sharingRajal')
            ->select(
                'sharingRajal.id as id',
                DB::raw('DATE(sharingRajal.tglEntry) as tgl'),
                'sharingRajal.kode as kode',
                'sharingRajal.nominal as nominal',
                'sharingRajal.jumlah as jumlah',
                DB::raw('(sharingRajal.nominal * sharingRajal.jumlah) as subtotal')
            )
            ->where('sharingRajal.noreg', $noreg)
            ->get();

        return response()->json($data);
    }
}
