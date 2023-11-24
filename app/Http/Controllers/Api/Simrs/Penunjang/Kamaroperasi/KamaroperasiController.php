<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Kamaroperasi\PermintaanOperasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KamaroperasiController extends Controller
{
    public function permintaanoperasi(Request $request)
    {
        DB::select('call nota_tindakan(@nomor)');
        $x = DB::table('rs1')->select('rs14')->get();
        $wew = $x[0]->rs14;
        $notapermintaanok = $request->nota ?? FormatingHelper::notatindakan($wew, '/POK-RJ');

        $userid = FormatingHelper::session_user();
        $requestoperasi = PermintaanOperasi::create(
            [
                'rs1' => $request->noreg,
                'rs2' => $notapermintaanok,
                'rs3' => date('Y-m-d H:i:s'),
                // ],
                // [
                'rs4' => $request->permintaan,
                'rs8' => $userid['kodesimrs'],
                'rs9' => '1',
                'rs10' => $request->kodepoli,
                'rs11' => $userid['kodesimrs'],
                'rs13' => $request->kodepoli,
                'rs14' => $userid['kodesimrs']
            ]
        );

        if (!$requestoperasi) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $nota = PermintaanOperasi::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();

        return new JsonResponse(
            [
                'message' => 'Permintaan Berhasil Dikirim Ke OK',
                'result' => $requestoperasi,
                'nota' => $nota
            ],
            200
        );
    }

    public function getnota()
    {
        $nota = PermintaanOperasi::select('rs2 as nota')->where('rs1', request('noreg'))
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }

    public function hapuspermintaanok(Request $request)
    {
        $cari = PermintaanOperasi::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }
        // $hapusdetail = PermintaanOperasi::where('rs2', '=', $cari->nota)->delete();
        $hapus = $cari->delete();
        $nota = PermintaanOperasi::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }
}
