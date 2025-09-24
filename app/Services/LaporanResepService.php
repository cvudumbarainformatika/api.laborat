<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use Illuminate\Support\Facades\DB;

class LaporanResepService
{
  public static function getLaporan(array $params)
  {
    $tipe = $params['tipe'] ?? 'noreg';
    $jenis = $params['jenis'] ?? 'detail';
    $depo = $params['depo'] ?? 'all';
    $from = $params['from'] . ' 00:00:00' ?? now()->startOfMonth()->toDateTimeString();
    $to = $params['to'] . ' 23:59:59' ?? now()->endOfDay()->toDateTimeString();
    $page = $params['page'] ?? 1;
    $perPage = $params['per_page'] ?? 100;

    $query = DB::table('farmasi.resep_keluar_h as h');

    // =========================
    // 🔹 TIPE N O R E G
    // =========================
    if ($tipe === 'noreg') {
      if ($jenis === 'detail') {
        $query->select(
          'h.depo',
          'h.dokter',
          'h.noreg',
          'h.noresep',
          'h.norm',
          'h.tgl_permintaan',
        )
          ->joinSub(
            DB::table('farmasi.resep_keluar_h')
              ->select('noreg')
              ->whereBetween('tgl_permintaan', [$from, $to])
              ->groupBy('noreg')
              ->havingRaw('COUNT(DISTINCT noresep) > 1'),
            'multi',
            'h.noreg',
            '=',
            'multi.noreg'
          )
          ->whereBetween('h.tgl_permintaan', [$from, $to])
          ->orderBy('h.noreg')
          ->orderBy('h.tgl_permintaan');
      } else { // jenis = rekap
        $query->select(
          'h.noreg',
          DB::raw('COUNT(DISTINCT h.noresep) as total_resep')
        )
          ->whereBetween('h.tgl_permintaan', [$from, $to])
          ->groupBy('h.noreg')
          ->havingRaw('COUNT(DISTINCT h.noresep) > 1')
          ->orderBy('h.noreg');
      }
    }

    // =========================
    // 🔹 TIPE I T E M
    // =========================
    if ($tipe === 'item') {
      // if ($jenis === 'detail') {
      //   $sub = DB::table('farmasi.resep_permintaan_keluar as rpk')
      //     ->select('rpk.noresep')
      //     ->join('farmasi.resep_keluar_h as h', 'h.noresep', '=', 'rpk.noresep')
      //     ->whereBetween('h.tgl_permintaan', [$from, $to])
      //     ->groupBy('rpk.noresep')
      //     ->havingRaw('COUNT(DISTINCT rpk.kdobat) > 5');

      //   $query->select(
      //     'h.noreg',
      //     'h.noresep',
      //     DB::raw("DATE_FORMAT(h.tgl_permintaan, '%Y-%m-%d %H:%i:%s') as tgl_permintaan"),
      //     'r.kdobat',
      //     'm.nama_obat',
      //     'r.jumlah as jumlah_resep',
      //     DB::raw("IFNULL(SUM(k.jumlah),0) as jumlah_diberikan")
      //   )
      //     ->join('farmasi.resep_permintaan_keluar as r', 'h.noresep', '=', 'r.noresep')
      //     ->leftJoin('farmasi.new_masterobat as m', 'm.kd_obat', '=', 'r.kdobat')
      //     ->leftJoin('farmasi.resep_keluar_r as k', function ($join) {
      //       $join->on('r.noresep', '=', 'k.noresep')
      //         ->on('r.kdobat', '=', 'k.kdobat');
      //     })
      //     ->whereBetween('h.tgl_permintaan', [$from, $to])
      //     ->whereIn('h.noresep', $sub)
      //     ->groupBy(
      //       'h.noreg',
      //       'h.noresep',
      //       'h.tgl_permintaan',
      //       'r.kdobat',
      //       'r.jumlah',
      //       'm.nama_obat'
      //     );
      // } else { // jenis = rekap
      // }
      $query->select(
        'h.noreg',
        'h.noresep',
        'h.tgl_permintaan',
        DB::raw('COUNT(DISTINCT r.kdobat) as total_item'),
        DB::raw('SUM(r.jumlah) as total_diminta'),
        DB::raw('SUM(IFNULL(k.jumlah,0)) as total_diberikan')
      )
        ->join('farmasi.resep_permintaan_keluar as r', 'h.noresep', '=', 'r.noresep')
        ->leftJoin('farmasi.resep_keluar_r as k', function ($join) {
          $join->on('r.noresep', '=', 'k.noresep')
            ->on('r.kdobat', '=', 'k.kdobat');
        })
        ->whereBetween('h.tgl_permintaan', [$from, $to])
        ->groupBy('h.noresep')
        ->havingRaw('COUNT(DISTINCT r.kdobat) > 5')
        ->orderBy('h.noresep');
    }

    // =========================
    // 🔹 Join poli dan dokter
    // =========================
    $query->leftJoin('rs.rs19 as pol', 'pol.rs1', '=', 'h.ruangan')
      ->leftJoin('kepegx.pegawai as peg', 'peg.kdpegsimrs', '=', 'h.dokter')
      ->addSelect('pol.rs2 as poli', 'peg.nama as dokter');
    // =========================
    // 🔹 FILTER DEPO (optional)
    // =========================
    if ($depo !== 'all') {
      $query->where('h.depo', $depo);
    }
    // rajal aja
    $query->where('h.depo', 'Gd-05010101');



    $total = (clone $query)->count();
    $data = $query->simplePaginate($params['per_page']);
    $resp = ResponseHelper::responseGetSimplePaginate($data, $params, $total);
    $listNorsp = [];
    if ($jenis === 'detail') {
      if ($tipe === 'noreg') {
        $resep = [];
        $listNorsp = collect($resp['data'])->pluck('noresep');
        $details = Resepkeluarrinci::select('kdobat', 'noresep', 'jumlah')->whereIn('noresep', $listNorsp)->with('mobat:kd_obat,nama_obat')->get();
        $result = collect($resp['data'])->map(function ($h) use ($details) {
          $h->detail = $details->where('noresep', $h->noresep)->values();
          return $h;
        });
        $resep['data'] = $result;
      } else {
        $resep = [];
        $listNorsp = collect($resp['data'])->pluck('noresep');
        $details = Permintaanresep::select(
          'resep_permintaan_keluar.kdobat',
          'resep_permintaan_keluar.noresep',
          'resep_permintaan_keluar.jumlah as jumlah_resep',
          DB::raw("IFNULL(r.jumlah,0) as jumlah_diberikan")
        )
          ->leftJoin('farmasi.resep_keluar_r as r', function ($join) {
            $join->on('r.noresep', '=', 'resep_permintaan_keluar.noresep')
              ->on('r.kdobat', '=', 'resep_permintaan_keluar.kdobat');
          })
          ->whereIn('resep_permintaan_keluar.noresep', $listNorsp)
          ->with('mobat:kd_obat,nama_obat')
          ->get();
        $result = collect($resp['data'])->map(function ($h) use ($details) {
          $h->detail = $details->where('noresep', $h->noresep)->values();
          return $h;
        });
        $resep['data'] = $result;
      }
    }
    // return [
    //   'data' => $resp,
    //   'listNorsp' => $listNorsp,
    //   'result' => $result ?? null,
    //   'details' => $details ?? null,
    // ];
    return $resp;
  }
}
