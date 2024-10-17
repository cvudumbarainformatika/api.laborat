<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\BankDarah;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mbdrs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BankDarahController extends Controller
{

    public function getmaster()
    {

       $data = Cache::rememberForever('m_bdrs', function () {
          $master = Mbdrs::all();
          $master->makeHidden(['created_at','updated_at']);
          return $master;
        });


       return new JsonResponse($data);
    }

    // public function getnota()
    // {
    //     $nota = PermintaanOperasi::select('rs2 as nota')->where('rs1', request('noreg'))
    //         ->orderBy('id', 'DESC')->get();
    //     return new JsonResponse($nota);
    // }
    // public function getdata()
    // {
    //     $data = PermintaanOperasi::select('*')->where('rs1', request('noreg'))
    //     ->with('petugas:kdpegsimrs,nik,nama,kdgroupnakes')
    //     ->orderBy('id', 'DESC')->get();
    //     return new JsonResponse($data);
    // }

    // public function simpandata(Request $request)
    // {

    //   $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=','1')->get();

    //   if (count($cekKasir) > 0) {
    //     return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal '.$cekKasir[0]->rs42], 500);
    //   }

    //   DB::select('call nota_permintaanbedah(@nomor)');
    //   $x = DB::table('rs1')->select('rs27')->get();
    //   $wew = $x[0]->rs27;

    //   $nota = $request->nota ?? FormatingHelper::formatallpermintaan($wew, '/POK-RI');

    //   $userid = FormatingHelper::session_user();
    //   $simpan = PermintaanOperasi::firstOrCreate(
    //       [
    //           'rs1' => $request->noreg,
    //           'rs2' => $nota,
    //       ],
    //       [
    //           'rs3' => date('Y-m-d H:i:s'),
    //           'rs4' => $request->permintaan,
    //           'rs8' => $request->kodedokter, //$request->kodedokter
    //           'rs9' => '1',
    //           'rs10' => $request->kodepoli, // ruangan
    //           'rs11' => $userid['kodesimrs'],
    //           'rs13' => $request->kdgroup_ruangan, // group_ruangan
    //           'rs14' => $request->kodesistembayar, //$request->kd_akun
    //           'rs15' => date('Y-m-d H:i:s'),
    //           'cito' => $request->cito === 'Iya' ? 'Ya' : '',
    //       ]
    //   );

    //   if (!$simpan) {
    //       return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
    //   }
    //   $nota = PermintaanOperasi::select('rs2 as nota')->where('rs1', $request->noreg)
    //       ->groupBy('rs2')->orderBy('id', 'DESC')->get();

    //   return new JsonResponse(
    //       [
    //           'message' => 'Permintaan Operasi Berhasil di Simpan',
    //           'result' => $simpan->load('petugas:kdpegsimrs,nama,nik,kdgroupnakes'),
    //           'nota' => $nota
    //       ],
    //       200
    //   );
    // }

    // public function hapusdata(Request $request)
    // {
    //     $cari = PermintaanOperasi::find($request->id);
    //     if (!$cari) {
    //         return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
    //     }

    //     $kunci = $cari->rs12 === '1';
    //     if ($kunci) {
    //         return new JsonResponse(['message' => 'Maaf, Data telah dikunci'], 500);
    //     }

    //     $hapus = $cari->delete();
    //     if (!$hapus) {
    //         return new JsonResponse(['message' => 'gagal dihapus'], 500);
    //     }
    //     $nota = PermintaanOperasi::select('rs2 as nota')->where('rs1', $request->noreg)
    //         ->groupBy('rs2')->orderBy('id', 'DESC')->get();
    //     return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    // }
}
