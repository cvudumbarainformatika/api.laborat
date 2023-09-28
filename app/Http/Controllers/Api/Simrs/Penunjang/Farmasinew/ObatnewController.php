<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mapingkelasterapi;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObatnewController extends Controller
{
    public function simpan(Request $request)
    {
        if (!$request->kd_obat) {
            DB::connection('farmasi')->select('call master_obat(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('mobat')->get();
            $wew = $x[0]->mobat;
            $kodeobat = FormatingHelper::mobat($wew, 'FAR');
        } else {
            $kodeobat = $request->kd_obat;
        }


        $simpan = Mobatnew::updateOrCreate(
            ['kd_obat' => $kodeobat],

            $request->all()
            // 'nama_obat' => $request->nama_obat,
            // 'merk' => $request->merk,
            // 'kandungan' => $request->kandungan,
            // 'jenis_perbekalan' => $request->jenis_perbekalan,
            // 'bentuk_sediaan' => $request->bentuk_sediaan,
            // 'kode108' => $request->kode108,
            // 'uraian108' => $request->uraian108,
            // 'kode50' => $request->kode50,
            // 'uraian50' => $request->uraian50,
            // 'satuan_b' => $request->satuan_b,
            // 'satuan_k' => $request->satuan_k,
            // 'kelompok_psikotropika' => $request->kelompok_psikotropika,
            // 'kelompok_penyimpanan' => $request->kelompok_penyimpanan,
            // 'kelompok_rko' => $request->kelompok_rko,
            // 'status_generik' =>$request->status_generik,
            // 'status_forkid' =>$request->status_forkid,
            // 'status_fornas' =>$request->status_fornas,
            // 'kekuatan_dosis' =>$request->kekuatan_dosis,
            // 'volumesediaan' =>$request->volumesediaan,
            // 'kelas_terapi' =>$request->kelas_terapi,
            // 'nilai_kdn' =>$request->nilai_kdn,
            // 'sertifikatkdn' =>$request->sertifikatkdn,
            // 'sistembayar' =>$request->sistembayar,
        );
        if ($request->has('kelasterapis')) {
            foreach ($request->kelasterapis as $key) {
                $simpanrinci = Mapingkelasterapi::firstOrCreate([
                    'kd_obat' => $simpan->kd_obat,
                    'kelas_terapi' => $key['kelasterapi']
                ]);
            }
        }
        if (!$simpan) {
            return new JsonResponse(['message' => 'data gagal disimpan'], 500);
        }
        return new JsonResponse(['message' => 'data berhasil disimpan'], 200);
    }

    public function hapus(Request $request)
    {
        $hapus = Mobatnew::find($request->id)->update(['flag' => '1']);

        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }

    public function list()
    {

        $list = Mobatnew::with('mkelasterapi')
            ->where(function ($list) {
                $list->where('nama_obat', 'Like', '%' . request('q') . '%')
                    ->orWhere('merk', 'Like', '%' . request('q') . '%')
                    ->orWhere('kandungan', 'Like', '%' . request('q') . '%');
            })->orderBy('id', 'desc')
            ->where('flag', '')
            ->get();

        return new JsonResponse($list);
    }

    public function cariobat()
    {

        $query = Mobatnew::select(
            'kd_obat as kodeobat',
            'nama_obat as namaobat'
        )->where('flag', '')
            ->where(function ($list) {
                $list->where('nama_obat', 'Like', '%' . request('q') . '%');
            })->orderBy('nama_obat')
            ->get();
        return new JsonResponse($query);
    }

    public function hapusMapingTerapi(Request $request)
    {
        $data = Mapingkelasterapi::find($request->id);
        if (!$data) {
            return new JsonResponse(['message' => 'Tidak ada data yang bisa dihapus'], 422);
        }
        $data->delete();
        return new JsonResponse(['message' => 'Kelas Terapi dihapus'], 200);
    }
}
