<?php

namespace App\Http\Controllers\Api\Simrs\Radiologi;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Radiologi\RadiologiLuar;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RadiologiLuarController extends Controller
{

  public function index()
  {

     $total = $this->query_table()->get()->count();
      $data = $this->query_table()->simplePaginate(request('per_page'));

      $response = (object)[
        'total' => $total,
        'data' => $data
      ];

      return response()->json($response);


    //  $data = RadiologiLuar::select([
    //     'rs1 as notrans',
    //     'rs8 as tglentri',
    //     'rs2 as nama',
    //     'rs3 as alamat',
    //     'rs4 as kelamin',
    //     DB::raw('YEAR(CURDATE()) - YEAR(rs5) as usia'),
    //     'rs6 as dari',
    //     'rs9 as permintaan',
    //     'rs10 as flag',
    //     'jenispembayaran',
    //     'perusahaan'
    // ])
    // ->whereNull('rs11')
    // ->orderByDesc('rs1')
    // ->simplePaginate(request('per_page'));

    // return new JsonResponse($data);
  }

  private function query_table()
  {

    if (request('to') === '' || request('from') === null) {
          $tgl = Carbon::now()->format('Y-m-d 00:00:00');
          $tglx = Carbon::now()->format('Y-m-d 23:59:59');
      } else {
          $tgl = request('to') . ' 00:00:00';
          $tglx = request('from') . ' 23:59:59';
      }

      $sort = request('sort') === 'terbaru'? 'DESC':'ASC';
      $status = request('status') ?? 'Semua';

    $select =  RadiologiLuar::select([
        'rs270.rs1 as notrans',
        'rs270.rs8 as tglentri',
        'rs270.rs2 as nama',
        'rs270.rs3 as alamat',
        'rs270.rs4 as kelamin',
        DB::raw('YEAR(CURDATE()) - YEAR(rs270.rs5) as usia'),
        'rs270.rs6 as dari',
        'rs270.rs9 as permintaan',
        'rs270.rs10 as flag',
        'rs270.jenispembayaran',
        'rs270.perusahaan as perusahaan_id',
        'perusahaan.perusahaan',
        'rs270.nik',
    ]);
    // ->whereNull('rs11')
    // ->orderByDesc('rs1');

    $select->leftJoin('perusahaan', 'perusahaan.id', '=', 'rs270.perusahaan');

    if ($status !== 'Semua') {
      if ($status === 'Terlayani') {
            $select->where('rs270.rs10', '=', '1');
            $select->whereBetween('rs270.rs8', [$tgl, $tglx]);
        } else {
            $select->where('rs270.rs10', '=', '');
        }
    } else {
      $select->whereBetween('rs270.rs8', [$tgl, $tglx]);
    }

    if (request('q') !== '' && request('q') !== null) {
        $select->where('rs270.rs2', 'like', '%' . request('q') . '%')
                ->orWhere('rs270.rs3', 'LIKE', '%' . request('q') . '%')
                ->orWhere('perusahaan.perusahaan', 'LIKE', '%' . request('q') . '%');
    }


    return $select->orderBy('rs1', $sort);
  }

  public function simpanPermintaan(Request $request)
  {

    // return response()->json([
    //   'coba'=> $request->all()
    // ]);

      DB::beginTransaction();

      try {
          $tanggal = Carbon::now()->format('Y-m-d');
          $jam = Carbon::now()->format('y-m-d H:i:s');

          $notax= '';

          if (empty(trim($request->input('notrans')))) {
              // Ambil counter dan buat nomor transaksi
              $counter = DB::table('rs1')->value('rs270') + 1;
              $notax = $this->gennota($counter, 'RL');

              // Update counter
              DB::table('rs1')->update(['rs270' => DB::raw('rs270 + 1')]);

              $tagihan = $request->jnsPembayaran === 'Perusahaan' ? '1' : '';

              // Insert ke rs270
              DB::table('rs270')->insert([
                  'rs1' => $notax,
                  'rs2' => trim($request->nama),
                  'rs3' => trim($request->alamat),
                  'rs4' => trim($request->kelamin),
                  'rs5' => $request->tgllahir,
                  'rs6' => trim($request->yangmemimnta),
                  'rs7' => auth()->user()->pegawai_id,
                  'rs8' => Carbon::now(),
                  'rs9' => trim($request->permintaan),
                  // 'rs10'=>
                  // 'rs11'=>
                  'jenispembayaran' => $request->jnsPembayaran,
                  'perusahaan' => $request->perusahaan_id,
                  'flag_tagihan' => $tagihan,
                  'nik' => $request->nik,

              ]);
          } else {
              $notax = trim($request->notrans);
          }

          // // Loop tindakan
          // $kodeTindakan = explode(';', trim($request->kode_tindakan));
          // $ukuranTindakan = explode(';', trim($request->kode_ukuran));
          // $jumlahTindakan = explode(';', trim($request->kode_jumlah));

          $permintaans = $request->permintaans;
          foreach ($permintaans as $item) {
              DB::table('rs271')->insert([
                  'rs1' => $notax,
                  'rs2' => now()->format('Y-m-d H:i:s'),
                  'rs3' => $item['kode'],
                  'rs4' => $item['sarana'],
                  'rs5' => $item['sarana'],
                  'rs6' => $item['pelayanan'],
                  'rs7' => $item['pelayanan'],
                  'rs8' => auth()->user()->pegawai_id,
                  'rs9' => $item['ukuran'],
                  'rs10' => $item['jumlah'],
                  'rs11' => now()->format('Y-m-d H:i:s'),
              ]);
          }

          DB::commit();
          return new JsonResponse(['message' => 'Data berhasil disimpan', 'data' => $notax], 200);
      } catch (\Exception $e) {
          DB::rollBack();
          // return response("ERR|" . $e->getMessage(), 500);
          return new JsonResponse(['message' => 'Data gagal disimpan', 'error' => $e->getMessage()], 500);
      }
  }

  private function gennota($n, $kode)
  {
    $prefix = now()->format('ymd');
    $number = str_pad($n, 4, '0', STR_PAD_LEFT);
    return "{$prefix}/{$number}{$kode}";
  }


}
