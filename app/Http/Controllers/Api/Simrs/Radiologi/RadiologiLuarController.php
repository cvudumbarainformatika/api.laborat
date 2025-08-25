<?php

namespace App\Http\Controllers\Api\Simrs\Radiologi;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Radiologi\HasilRadiologiLuar;
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
        'rs270.rs1',
        'rs270.rs1 as notrans',
        'rs270.rs8 as tglentri',
        'rs270.rs2 as nama',
        'rs270.rs3 as alamat',
        'rs270.rs4 as kelamin',
        DB::raw('YEAR(CURDATE()) - YEAR(rs270.rs5) as usia'),
        'rs270.rs5 as tgllahir',
        'rs270.rs6 as dari',
        'rs270.rs9 as permintaan',
        'rs270.rs10 as flag',
        'rs270.jenispembayaran',
        'rs270.perusahaan as perusahaan_id',
        'perusahaan.perusahaan',
        'rs270.nik',
    ]);

    $select->with(['rincians'=>function($q){
        // $q->select('rs271.id','rs271.rs1','rs271.rs2','rs271.rs3','rs271.rs4','rs271.rs5','rs271.rs6','rs271.rs7','rs271.rs8','rs271.rs9','rs271.rs10','rs271.rs11','rs47.rs2 as nama')
        $q->select('rs271.*','rs47.rs2 as nama', 'rs47.rs3 as jenis', 
        'rs272.hasil as hasil', 
        'rs272.rs8 as kesimpulan',
        'rs272.rs9 as pelaksana',
        )
        // ->leftJoin('rs47', function ($join){$join->on('rs271.rs3', '=', 'rs47.rs1');});
        ->leftJoin('rs47', fn($join) => $join->on('rs271.rs3', '=', 'rs47.rs1'))
        ->leftJoin('rs272', fn($join) => 
            $join->on('rs272.kode', '=', 'rs271.rs3')
                ->on('rs272.rs1', '=', 'rs271.rs1')
        );
    }]);


    $select->leftJoin('perusahaan', 'perusahaan.id', '=', 'rs270.perusahaan');
    
    $select->whereBetween('rs270.rs8', [$tgl, $tglx]);

    if ($status !== 'Semua') {
      if ($status === 'Terlayani') {
            $select->where('rs270.rs10', '=', '1');
            // $select->whereBetween('rs270.rs8', [$tgl, $tglx]);
        } else {
            $select->whereIn('rs270.rs10', [null,'']);
        }
    } else {
      $select->whereIn('rs270.rs10', [null,'', '1', '2', '3']);
              // ->whereBetween('rs270.rs8', [$tgl, $tglx]);
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

  public function simpanHasilRadiologiLuar(Request $request)
  {
      $notrans = trim($request->notrans);
      $tgllahir = $request->tgllahir;

      // Cek apakah permintaan radiologi ada berdasarkan nota dan kode
      $cek = DB::table('rs271')->where('rs1', $notrans)->where('rs3', $request->kode)->exists();

      if ($cek) {
          $simpan = HasilRadiologiLuar::updateOrCreate(
            [
              'rs1' => $notrans,
              'kode' => $request->kode
            ],
            [
              
              'rs2' => trim($request->nama),
              'rs3' => trim($request->alamat),
              'rs4' => trim($request->kelamin),
              'rs5' => $tgllahir,
              'rs6' => trim($request->yangmemimnta),
              'rs7' => Carbon::now()->format('Y-m-d H:i:s'),
              'rs8' => trim($request->kesimpulan),
              'rs9' => trim($request->dokter),
              'rs10' => Carbon::now()->format('Y-m-d H:i:s'), // ini nanti jadi updated at
              'rs11' => auth()->user()->pegawai_id ?? 'system', // fallback jika belum login
              'hasil' => $request->hasil
          ]
        );

          // DB::table('rs270')->where('rs1', $notrans)->update([
          //     'rs11' => 1,
          // ]);

          return new JsonResponse(['message' => 'Data berhasil disimpan', 'data' => $simpan], 200);
      } else {
          return response("Hasil Tidak Bisa Dientry Karena Belum Ada Permintaan...!!");
      }
  }


  public function terimapasienradiologiluar(Request $request)
  {
      $notrans = trim($request->notrans);
      DB::table('rs270')->where('rs1', $notrans)->update([
          'rs10' => '2',
      ]);
      return new JsonResponse(['message' => 'Data berhasil disimpan'], 200);
  }
  public function batalkanpasienradiologiluar(Request $request)
  {
      $notrans = trim($request->notrans);
      DB::table('rs270')->where('rs1', $notrans)->update([
          'rs10' => '3',
      ]);
      return new JsonResponse(['message' => 'Data berhasil disimpan'], 200);
  }
  public function selesaikanlayananradiologiluar(Request $request)
  {
      $notrans = trim($request->notrans);
      DB::table('rs270')->where('rs1', $notrans)->update([
          'rs10' => '1',
      ]);
      return new JsonResponse(['message' => 'Data berhasil disimpan'], 200);
  }


}
