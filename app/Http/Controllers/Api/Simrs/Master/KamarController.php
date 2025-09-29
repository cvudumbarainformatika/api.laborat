<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Diagnosa_m;
use App\Models\Simrs\Master\Mkamar;
use App\Models\Simrs\Master\MkamarRanap;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Views\Kunjunganview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KamarController extends Controller
{
    public function listkamar()
    {
      // $listkamar = Mkamar::query()
      // ->selectRaw('rs1,rs2,rs3,rs4,rs6,groups')
      // ->where(function ($q) {
      //   $q->where('rs6', '<>', '1')
      //   ->where('status', '<>', '1');
      // })->distinct('rs1')
      // ->orderBy('rs2', 'DESC')->get();

      $listkamar = Cache::remember('kamar', now()->addDays(7), function () {
        return Mkamar::query()
        ->selectRaw('rs1,rs2,rs3,rs4,rs6,groups')
        ->where(function ($q) {
          $q->where('rs6', '<>', '1')
          ->where('status', '<>', '1');
        })->distinct('rs1')
        ->orderBy('rs2', 'DESC')->get();
      });

      return new JsonResponse($listkamar);
    }


    public function showKamar()
    {
      $data = Mkamar::query()
      ->select('groups','rs5','rs4')
      ->where('status','<>','1')
      ->with(['kamars'=>function($q){
        $q->where('rs7','<>','1')
            ->addSelect([
            'kunjungan'=> Kunjunganview::query()
                ->join('rs24', 'v_15_23.kamar', '=', 'rs24.rs2')
                ->selectRaw("GROUP_CONCAT(v_15_23.noreg order by v_15_23.tgl_masuk asc, ',')" )
                ->whereColumn('v_15_23.no_bed','=', 'rs25.rs2')
                ->whereColumn('v_15_23.kd_kmr','=', 'rs25.rs1')
                // ->whereColumn('v_15_23.kamar','=', 'rs24.rs2')
                ->where('v_15_23.status_inap','=', '')
            ])
            ->orderBy('rs5', 'asc');
          }, 
          'kamars.kamar'=>function($q){
            $q->select('rs1','rs2','rs3','rs4','rs5','groups');
          }
        ])
        ->where('status','<>','1')
        // ->where('groups','=','BG')
        ->distinct('groups')
      ->get();

      $flat = [];
      foreach ($data as $x) {
          $xy=$x->kamars;
          foreach ($xy as $y) {
              if($y->kunjungan !==null) {
                  $temp = [];
                  foreach(explode(',', $y->kunjungan) as $key => $value) {
                      $temp[$key] = $value;
                  }
                  $flat[] = $temp;
              }
          }
      }
      $flatten = collect(array_merge(...$flat))->unique()->values()->all();

      $kunjungan = Kunjunganview::select(
        'noreg','norm','status_inap',
        'tgl_masuk','group_kamar','kd_kelas','no_bed',
        'kelamin','alamat','nama',
        'kd_kmr','kamar','titipan'
      )
      ->whereIn('noreg', $flatten)
      ->where('status_inap','=','')
      ->orderBy('tgl_masuk', 'desc')
      ->groupBy('noreg')
      ->get();

      foreach ($data as $x) {
          $xy=$x->kamars;
          foreach ($xy as $y) {
              $noregs = explode(',', $y->kunjungan);
              $ee = $kunjungan
              ->whereIn('noreg', $noregs)
              ->sortBy(fn (Kunjunganview $kj) => array_flip($noregs)[$kj->noreg])
              ->values();

              // masukkan ke object harga_teringgi_kodes
              $y->setRelation('kunjungan', $ee)->toArray();
          }
      }

    
      return new JsonResponse($data);
    }


    function showKamar2()
    {
      $nama = request('nama'); // sama dengan $_GET['nama']

        $data = DB::table('rs25')
            ->select('rs1')
            ->where('rs5', $nama)
            ->where('rs3', 'A')
            ->where('rs4', 'V')
            ->distinct()
            ->orderBy('rs1')
            ->get();

        return new JsonResponse($data);
    }


    function showBed(){
      $namax = request('namax'); // sama dengan $_GET['namax']
      $nama = request('nama'); // sama dengan $_GET['nama']
      if ($namax === "Extra") {
    // case khusus kalau namax = Extra
      $sql = DB::table('rs25')
        ->join('rs24', 'rs25.rs5', '=', 'rs24.rs1')
        ->select('rs25.rs2')
        ->where('rs24.rs2', $nama)
        ->where('rs25.rs1', $namax)
        ->where('rs25.rs3', 'A')
        ->where('rs25.rs4', 'V')
        ->where('rs25.rs7', '<>', 1)
        ->where('rs25.rs8', '<>', 1)
        ->distinct()
        ->orderByRaw('LENGTH(rs2) asc, rs2 asc')
        ->get();

      } elseif ($nama === "Instalasi Gawat Darurat") {
          // case khusus kalau nama = IGD
          $sql = collect([
              (object)['rs2' => 'IGD']
          ]);

      } else {
          // case umum
          $query1 = DB::table('rs25')
              ->join('rs24', 'rs25.rs5', '=', 'rs24.rs1')
              ->select('rs25.rs2')
              ->where('rs24.rs2', $nama)
              ->where('rs25.rs1', $namax)
              ->where('rs25.rs3', 'A')
              ->where('rs25.rs4', 'V')
              ->where('rs25.rs7', '<>', 1)
              ->where('rs25.rs8', '<>', 1);

          $query2 = DB::table('rs25')
              ->select('rs2')
              ->where('rs6', $rs_glob->groups ?? '')
              ->where('rs1', $namax)
              ->where('rs3', 'A')
              ->where('rs4', 'V')
              ->where('rs5', '-')
              ->where('rs7', '<>', 1)
              ->where('rs8', '<>', 1);

          $sql = $query1
              ->unionAll($query2)
              ->orderByRaw('LENGTH(rs2) asc, rs2 asc')
              ->get();
      }

        return new JsonResponse($sql);
    }
    
}
