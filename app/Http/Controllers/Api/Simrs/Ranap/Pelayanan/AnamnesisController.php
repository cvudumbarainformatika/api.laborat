<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Anamnesis\KeluhanNyeri;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnamnesisController extends Controller
{
    public function list()
    {
      
      $data = self::getdata(request('noreg'));
       return new JsonResponse($data);
    }

    public static function getdata($noreg){
      $akun = auth()->user()->pegawai_id;
      $nakes = Petugas::select('kdgroupnakes')->find($akun)->kdgroupnakes;

       $data = Anamnesis::select([
        'rs209.id','rs209.rs1','rs209.rs1 as noreg',
        'rs209.rs2 as norm',
        'rs209.rs3 as tgl',
        'rs209.rs4 as keluhanUtama',
        'rs209.riwayatpenyakit',
        'rs209.riwayatalergi',
        'rs209.keteranganalergi',
        'rs209.riwayatpengobatan',
        'rs209.riwayatpenyakitsekarang',
        'rs209.riwayatpenyakitkeluarga',
        'rs209.user',
        'pegawai.nama as petugas',
        'pegawai.kdgroupnakes as nakes',
       ])
       ->leftJoin('kepegx.pegawai as pegawai', 'rs209.user', '=', 'pegawai.kdpegsimrs')
       ->where('rs1', $noreg)
       ->where('pegawai.kdgroupnakes', '=', $nakes)
       ->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes','keluhannyeri'])
       ->get();

       return $data;
    }
    
    public function simpananamnesis(Request $request)
    {

      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;

      DB::beginTransaction();
      try {
        if ($request->has('id')) {
            $hasil = Anamnesis::where('id', $request->id)->update(
                [
                  'rs1' => $request->noreg,
                  'rs2' => $request->norm,
                  'rs3' => date('Y-m-d H:i:s'),
                  'rs4' => $request->form['keluhanUtama'] ?? '',
                  'riwayatpenyakit' => $request->form['rwPenyDhl'] ?? '',
                  'riwayatalergi' => $request->form['rwAlergi'] ?? '', // array
                  'keteranganalergi' => $request->form['ketRwAlergi'] ?? '',
                  'riwayatpengobatan' => $request->form['rwPengobatan'] ?? '',
                  'riwayatpenyakitsekarang' => $request->form['rwPenySkr'] ?? '',
                  'riwayatpenyakitkeluarga' => $request->form['rwPenyKlrg'] ?? '',
                  'riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya' => $request->form['rwPkrjDgZatBahaya'] ?? '',
                  // 'skreeninggizi' => $request->skreeninggizi ?? 0,
                  // 'asupanmakan' => $request->asupanmakan ?? 0,
                  // 'kondisikhusus' => $request->kondisikhusus ?? '',
                  // 'skor' => $request->skor ?? 0,
                  // 'scorenyeri' => $request->skorNyeri ?? 0,
                  // 'keteranganscorenyeri' => $request->keluhanNyeri ?? '',
                  'user'  => $kdpegsimrs,
                ]
            );
            if ($hasil === 1) { 
                $simpananamnesis = Anamnesis::where('id', $request->id)->first();
            } else {
                $simpananamnesis = null;
            }
        } else {
          $simpananamnesis = Anamnesis::create(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->form['keluhanUtama'] ?? '',
                'riwayatpenyakit' => $request->form['rwPenyDhl'] ?? '',
                'riwayatalergi' => $request->form['rwAlergi'] ?? '', // array
                'keteranganalergi' => $request->form['ketRwAlergi'] ?? '',
                'riwayatpengobatan' => $request->form['rwPengobatan'] ?? '',
                'riwayatpenyakitsekarang' => $request->form['rwPenySkr'] ?? '',
                'riwayatpenyakitkeluarga' => $request->form['rwPenyKlrg'] ?? '',
                'riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya' => $request->form['rwPkrjDgZatBahaya'] ?? '',
                // 'skreeninggizi' => $request->skreeninggizi ?? 0,
                // 'asupanmakan' => $request->asupanmakan ?? 0,
                // 'kondisikhusus' => $request->kondisikhusus ?? '',
                // 'skor' => $request->skor ?? 0,
                // 'scorenyeri' => $request->skorNyeri ?? 0,
                // 'keteranganscorenyeri' => $request->keluhanNyeri ?? '',
                'user'  => $kdpegsimrs,
            ]
          );
        }
      

        // save nyeri
        $nyeri = KeluhanNyeri::updateOrCreate(
          ['rs209_id'=> $simpananamnesis->id],
          [
            'noreg'=> $request->noreg,
            'norm'=> $request->norm,
            'dewasa'=> $request->form['keluhannyeri'] ?? null, // array
            'skor'=> $request->form['keluhannyeri']['skorNyeri'],
            'keluhan'=> $request->form['keluhannyeri']['ket'],
            'user_input'=> $kdpegsimrs,
            'grup_nakes' => $user->kdgroupnakes
  
          ]
        );


        DB::commit();
        return new JsonResponse([
            'message' => 'BERHASIL DISIMPAN',
            'result' => self::getdata($request->noreg),
        ], 200);
      } catch (\Throwable $th) {
        DB::rollBack();
        return new JsonResponse(['message' => 'GAGAL DISIMPAN','err'=>$th], 500);
      }
      
        
    }

    public function hapusanamnesis(Request $request)
    {
        $cari = Anamnesis::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'MAAF DATA TIDAK DITEMUKAN'], 500);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
        // return new JsonResponse($cari, 200);
    }

    public function historyanamnesis()
    {
        $raw = [];
        $history = Anamnesis::select(
            'id',
            'rs2 as norm',
            'rs3 as tgl',
            'rs4 as keluhanutama',
            'riwayatpenyakit',
            'riwayatalergi',
            'keteranganalergi',
            'riwayatpengobatan',
            'riwayatpenyakitsekarang',
            'riwayatpenyakitkeluarga',
            'skreeninggizi',
            'asupanmakan',
            'kondisikhusus',
            'skor',
            'scorenyeri',
            'keteranganscorenyeri',
            'user',
        )
            ->where('rs2', request('norm'))
            ->where('rs3', '<', Carbon::now()->toDateString())
            ->with('datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs')
            ->orderBy('tgl', 'DESC')
            ->get()
            ->chunk(10);

        $collapsed = $history->collapse();


        return new JsonResponse($collapsed->all());
    }
}
