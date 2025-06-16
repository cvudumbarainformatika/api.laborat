<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Pemakaian;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemakaianRuanganFsController extends Controller
{
    //

    public function getRuangan()
    {
        $data = Ruang::select(
            'kepegx.ruangs.kode',
            'kepegx.ruangs.uraian'
        )
            ->leftJoin('farmasi.permintaan_h', 'kepegx.ruangs.kode', '=', 'farmasi.permintaan_h.dari')
            ->whereNotNull('farmasi.permintaan_h.dari')
            ->groupBy('kepegx.ruangs.kode')
            ->get();

        return new JsonResponse([
            'data' => $data
        ]);
    }
    public function getData()
    {
        $kode_ruang = request('kode_ruang');
        $bulan = request('bulan');
        $tahun = request('tahun');
        $kodeOb = Mutasigudangkedepo::select('kd_obat')
            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
            ->when(
                $kode_ruang == 'all',
                function ($q) {
                    $q->where('dari', 'LIKE', 'R-%');
                },
                function ($q) use ($kode_ruang) {
                    $q->where('dari', '=', $kode_ruang);
                }
            )
            ->where('tgl_terima_depo', 'LIKE', '%' . $tahun . '-' . $bulan . '%')
            ->distinct()->pluck('kd_obat')->toArray();
        $obat = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'jenis_perbekalan',
            'bentuk_sediaan',
            'satuan_k'
        )
            ->with([
                'mutasi' => function ($q) use ($kode_ruang, $bulan, $tahun) {
                    $q->select(
                        'mutasi_gudangdepo.no_permintaan',
                        'kd_obat',
                        'jml',
                        'harga',
                        'dari',
                        'tgl_terima_depo'
                    )
                        ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->when(
                            $kode_ruang == 'all',
                            function ($q) {
                                $q->where('dari', 'LIKE', 'R-%');
                            },
                            function ($q) use ($kode_ruang) {
                                $q->where('dari', '=', $kode_ruang);
                            }
                        )
                        ->where('tgl_terima_depo', 'LIKE', '%' . $tahun . '-' . $bulan . '%');
                }
            ])
            ->whereIn('kd_obat', $kodeOb)
            ->paginate(request('per_page'));
        $data['data'] = collect($obat)['data'];
        $data['meta'] = collect($obat)->except('data');
        return new JsonResponse($data);
    }
}
