<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObatnewController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mobatnew::updateOrCreate(['kd_obat' => $request->kd_obat],
        [
        'nama_obat' => $request->nama_obat,
        'merk' => $request->merk,
        'kandungan' => $request->kadnungan,
        'jenis_perbekalan' => $request->jenis_perbekalan,
        'bentuk_sediaan' => $request->bentuk_sediaan,
        'kode108' => $request->kode108,
        'uraian108' => $request->uraian108,
        'kode50' => $request->kode50,
        'uraian50' => $request->uraian50,
        'satuan_b' => $request->satuan_b,
        'satuan_k' => $request->satuan_k,
        'kelompok_psikotropika' => $request->kelompok_psikotropika,
        'kelompok_penyimpanan' => $request->kelompok_penyimpanan,
        'kelompok_rko' => $request->kelompok_rko,
        'status_generik' =>$request->status_generik,
        'status_forkid' =>$request->status_forkid,
        'status_fornas' =>$request->status_fornas,
        'kekuatan_dosis' =>$request->kekuatan_dosis,
        'volumesediaan' =>$request->volumesediaan,
        'kelas_terapi' =>$request->kelas_terapi,
        'nilai_kdn' =>$request->nilai_kdn,
        'sertifikatkdn' =>$request->sertifikatkdn,
        ]);
        if(!$simpan)
        {
            return new JsonResponse(['message' => 'data gagal disimpan'], 500);
        }
            return new JsonResponse(['message' => 'data berhasil disimpan'], 200);
    }

    public function hapus(Request $request)
    {
        $cari = Mobatnew::where(['kd_obat' => $request->kd_obat]);
        if(!count($cari))
        {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 401);
        }

        foreach($cari as $kunci)
        {
            $hapus = $kunci->delete();
        }
        if(!$hapus)
        {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }

    public function list()
    {
        $list = Mobatnew::where('nama_obat', 'Like' , '%' .request('nama_obat'). '%')
        ->orWhere('merk', 'Like' , '%' .request('merk'). '%')
        ->orWhere('kandungan', 'Like' , '%' .request('kandungan'). '%')
        ->get();
    }
}
