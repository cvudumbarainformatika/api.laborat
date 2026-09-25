<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Ambulan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Ambulan\ReqAmbulan;
use App\Models\Simrs\Penunjang\Ambulan\TujuanAmbulan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermintaanAmbulanController extends Controller
{
    public function getTujuanAmbulan() {
        $tujuan = TujuanAmbulan::where('flag','')->get();
        return new JsonResponse($tujuan);
    }

    public function simpanpermintaan(Request $request)
    {
        $tujuan = TujuanAmbulan::where('rs1', $request->tujuan)->first();
        if($request->layananperawat === 'Rujukan')
        {
            $jp_perawat1 = $tujuan->rs6;
        }elseif($request->layananperawat === 'Emergency')
        {
            $jp_perawat1 = $tujuan->rs7;
        }else{
            $jp_perawat1 = $tujuan->rs8;
        }
        $isEdit = !empty($request->id) || (!empty($request->nota) && $request->nota !== 'BARU' && $request->nota !== 'SEMUA');
        
        $dataSimpan = [
            'rs1' => $request->noreg,
            'rs2' => $request->norm,
            'rs4' => $request->kdgroup_ruangan,
            'rs5' => $request->kodepoli,
            'rs6' => $request->kodesistembayar,
            'rs9' => $request->kodedokter ?? '',
            'rs10' => $request->tujuan,
            'rs11' => $request->keterangan ?? '',
            'rs12' => $request->layanansupir ?? '',
            'rs13' => $request->perawat1 ?? '',
            'rs14' => $request->perawat2 ?? '',
            'rs15' => $request->layananperawat ?? '',
            'kd_driver' => $request->kd_driver ?? '',
            'alasan_keperluan' => $request->alasan_keperluan ?? '',
            'indikasi_rujuk' => $request->indikasi_rujuk ?? '',
            'jenis_ambulan' => $request->jenis_ambulan ?? '',
            'skor_indeks' => $request->skor_indeks ?? 0,
            'kategori_resiko' => $request->kategori_resiko ?? '0',
            'kualifikasi_petugas' => $request->kualifikasi_petugas ?? '',
            'penilaian_resiko' => is_array($request->penilaian_resiko) ? json_encode($request->penilaian_resiko) : ($request->penilaian_resiko ?? null),
            'rs16' => $jp_perawat1,
        ];

        if ($isEdit) {
            $existing = null;
            if (!empty($request->id)) {
                $existing = ReqAmbulan::find($request->id);
            }
            if (!$existing && !empty($request->nota)) {
                $existing = ReqAmbulan::where('nota', $request->nota)->first();
            }

            if ($existing) {
                $existing->update($dataSimpan);
                $simpan = $existing;
            } else {
                DB::select('call nota_ambulan(@nomor)');
                $x = DB::table('rs1')->select('rs283')->get();
                $wew = $x[0]->rs283;
                $notatindakan = FormatingHelper::notatindakan($wew, 'AMB-RI');
                $dataSimpan['rs3'] = date('Y-m-d H:i:s');
                $dataSimpan['nota'] = $notatindakan;
                $simpan = ReqAmbulan::create($dataSimpan);
            }
        } else {
            DB::select('call nota_ambulan(@nomor)');
            $x = DB::table('rs1')->select('rs283')->get();
            $wew = $x[0]->rs283;
            $notatindakan = FormatingHelper::notatindakan($wew, 'AMB-RI');
            $dataSimpan['rs3'] = date('Y-m-d H:i:s');
            $dataSimpan['nota'] = $notatindakan;
            $simpan = ReqAmbulan::create($dataSimpan);
        }

        if (!$simpan) {
          return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
      }
        $nota = ReqAmbulan::select('nota as nota')->where('rs1', $request->noreg)
          ->orderBy('id', 'DESC')->get();

        return new JsonResponse([
            'message' => 'Permintaan Ambulan Berhasil Dikirim...!',
            'result' => $simpan,
            'nota' => $nota
        ]);
    }

    public function hapusdata(Request $request)
    {
        $cari = ReqAmbulan::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }

        $kunci = $cari->rs7 === '1' || $cari->rs7 === 1; // ini masih tanda tanya
        if ($kunci) {
            return new JsonResponse(['message' => 'Maaf, Data telah dikunci !!!'], 500);
        }

        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        $nota = ReqAmbulan::select('nota as nota')->where('rs1', $request->noreg)
          ->orderBy('id', 'DESC')->get();
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }
}
