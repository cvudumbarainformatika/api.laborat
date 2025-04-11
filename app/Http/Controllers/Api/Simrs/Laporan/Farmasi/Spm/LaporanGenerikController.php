<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Spm;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\SistemBayar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanGenerikController extends Controller
{
    //
    public function getLaporanGenerik()
    {
        $raw = Resepkeluarheder::select(
            'noresep',
            'tgl_permintaan',
            'dokter',
            'depo',
            'sistembayar',
        )->where('tgl_permintaan', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
            ->when(request('sistem_bayar'), function ($query) {
                $query->whereIn('sistembayar', request('sistem_bayar'));
            })
            ->when(request('depo'), function ($query) {
                $query->whereIn('depo', request('depo'));
            })
            ->with([
                'rincian:noresep,kdobat,jumlah',
                'rincianracik:noresep,kdobat,jumlah',
                'permintaanresep:noresep,kdobat,jumlah',
                'permintaanracikan:noresep,kdobat,jumlah',

                'rincian',
                'rincianracik',
                'permintaanresep.mobat:kd_obat,nama_obat,kelompok_penyimpanan,status_generik,status_forkid,status_fornas,obat_program',
                'permintaanracikan.mobat:kd_obat,nama_obat,kelompok_penyimpanan,status_generik,status_forkid,status_fornas,obat_program',

                'sistembayar:rs1,rs2',
                'ketdokter:kdpegsimrs,nama',
            ])
            ->whereIn('flag', ['1', '2', '3', '4'])
            // ->limit(100)
            ->paginate(100);
        $data = collect($raw)['data'];
        $meta = collect($raw)->except('data');
        return new JsonResponse([
            'req' => request()->all(),
            'data' => $data,
            'meta' => $meta,
        ]);
    }
    public function getLaporanResponseTime()
    {
        return new JsonResponse([
            'req' => request()->all(),

        ]);
    }
    public function getLaporanKesesuaianObat()
    {
        return new JsonResponse([
            'req' => request()->all(),

        ]);
    }
    public function getOptionKelompok()
    {
        $data = Mobatnew::select('jenis_perbekalan as kode', 'jenis_perbekalan as nama')
            ->distinct('jenis_perbekalan')
            ->groupBy('jenis_perbekalan')
            ->get();

        return new JsonResponse($data);
    }
    public function getOptionSistemBayar()
    {

        $data = SistemBayar::select('rs1 as kode', 'rs2 as nama', 'groups')
            ->where('hidden', '')
            ->get();

        return new JsonResponse($data);
    }
}
