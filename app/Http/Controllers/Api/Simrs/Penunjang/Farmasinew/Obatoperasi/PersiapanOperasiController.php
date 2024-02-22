<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Obatoperasi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersiapanOperasiController extends Controller
{
    //
    public function getPermintaan()
    {
        $flag = request('flag') ?? [];
        $data = PersiapanOperasi::with('rinci.obat:kd_obat,nama_obat,satuan_k', 'pasien:rs1,rs2')
            ->whereIn('flag', $flag)
            ->whereBetween('tgl_permintaan', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->orderBy('tgl_permintaan', "desc")
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function simpanPermintaan(Request $request)
    {
        return new JsonResponse($request->all());
    }
    public function simpanDistribusi(Request $request)
    {
        try {
            DB::beginTransaction();
            $rinci = $request->rinci;
            $user = FormatingHelper::session_user();
            $kode = $user['kodesimrs'];

            // pastikan ada data
            if (count($rinci) > 0) {
                $data = [];
                foreach ($rinci as $key) {

                    // update rinci
                    $dataRinci = PersiapanOperasiRinci::find($key['id']);
                    if (!$dataRinci) {
                        return new JsonResponse(['message' => 'Data Rinci tidak ditemukan']);
                    }
                    $dataRinci->jumlah_distribusi = $key['jumlah_distribusi'];
                    $dataRinci->save();

                    // lanjut ngisi data by fifo
                    $distribusi = (float)$key['jumlah_distribusi'];

                    // pastikan jumlah distribusi lebih dari 0
                    if ($distribusi > 0) {
                        $stok = Stokreal::where('kdobat', $key['kd_obat'])
                            ->where('kdruang', 'Gd-04010103')
                            ->where('jumlah', '>', 0)
                            ->orderBy('tglExp', 'ASC')
                            ->get();
                        $index = 0;

                        while ($distribusi > 0) {
                            $ada = (float)$stok[$index]->jumlah;
                            if ($ada < $distribusi) {
                                $temp = [
                                    'nopermintaan' => $key['nopermintaan'],
                                    'kd_obat' => $key['kd_obat'],
                                    'nopenerimaan' => $stok[$index]->nopenerimaan,
                                    'jumlah' => $ada,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                                $data[] = $temp;
                                $sisa = $distribusi - $ada;
                                $index += 1;
                                $distribusi = $sisa;
                            } else {
                                $temp = [
                                    'nopermintaan' => $key['nopermintaan'],
                                    'kd_obat' => $key['kd_obat'],
                                    'nopenerimaan' => $stok[$index]->nopenerimaan,
                                    'jumlah' => $distribusi,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                                $data[] = $temp;
                                $distribusi = 0;
                            }
                        }
                    }
                }
            }

            // update header
            $head = PersiapanOperasi::where('nopermintaan', $request->nopermintaan)->first();
            if (!$head) {
                return new JsonResponse(['message' => 'Data Header tidak ditemukan'], 410);
            }
            $head->flag = '2';
            $head->user_distribusi = $kode;
            $head->tgl_distribusi = date('Y-m-d H:i:s');
            $head->save();

            //simpan ditribusi
            $dist = PersiapanOperasiDistribusi::insert($data); // ini hasilnya kalo berhasil itu true
            if (!$dist) {
                return new JsonResponse(['message' => 'Data gagal disimpan!'], 410);
            }
            // update stok
            $dataDist = PersiapanOperasiDistribusi::where('nopermintaan', $request->nopermintaan)->get();
            foreach ($dataDist as $rin) {
                $stok = Stokreal::where('kdobat', $rin['kd_obat'])
                    ->where('kdruang', 'Gd-04010103')
                    ->where('nopenerimaan', $rin['nopenerimaan'])
                    ->first();

                if ($stok->jumlah <= 0) {
                    return new JsonResponse(['message' => 'Data stok kurang dari 0'], 410);
                }
                $sisa = $stok->jumlah - $rin['jumlah'];
                $stok->jumlah = $sisa;
                $stok->save();
            }

            DB::commit();

            return new JsonResponse([
                'rinci' => $rinci,
                'data' => $dist,
                'head' => $head,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan...!!!',
                'result' => $e,
            ], 410);
        }
    }
    public function terimaPengembalian(Request $request)
    {
        return new JsonResponse($request->all());
    }
}
