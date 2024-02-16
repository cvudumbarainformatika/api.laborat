<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Ruangan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Ruangan\PemakaianH;
use App\Models\Simrs\Penunjang\Farmasinew\Ruangan\PemakaianR;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemakaianRuanganController extends Controller
{
    //
    public function getStokRuangan()
    {
        $obat = Stokreal::selectRaw('*, sum(jumlah) as stok')
            ->with('obat:kd_obat,nama_obat,satuan_k', 'ruang:kode,uraian')
            ->where('jumlah', '>', 0)
            ->where('kdruang', request('kdruang'))
            ->when(request('q'), function ($query) {
                $kode = Mobatnew::select('kd_obat')
                    ->where('kd_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->get();
                $query->whereIn('kdobat', $kode);
            })
            ->groupBy('kdobat', 'kdruang')
            ->paginate(request('per_page'));

        return new JsonResponse($obat);
    }

    public function simpanpemaikaianruangan(Request $request)
    {
        // return new JsonResponse($request->all());
        try {
            DB::beginTransaction();

            if (!$request->nopemakaian) {
                DB::connection('farmasi')->select('call pemakaianruangan(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('pemakaianruangan')->get();
                $wew = $x[0]->pemakaianruangan;
                $pemakaianruangan = FormatingHelper::nopemakaianruangan($wew, 'RUA-FAR');
            } else {
                $pemakaianruangan = $request->nopemakaian;
            }
            $user = FormatingHelper::session_user();
            $kode = $user['kodesimrs'];
            // header
            $header = [
                'nopemakaian' => $pemakaianruangan,
                'tgl' => $request->tgl ? $request->tgl . date(' H:i:s') : date('Y-m-d H:i:s'),
                'kdruang' => $request->kdruang,
                'user' => $kode,
            ];

            // rinci
            $rinci = [];
            foreach ($request->obats as $rin) {
                if ((int)$rin['dipakai'] > 0) {
                    $stok = Stokreal::where('kdobat', $rin['kdobat'])->where('kdruang', $request->kdruang)->orderBy('tglexp', 'ASC')->get();
                    $index = 0;
                    $dipakai = (int)$rin['dipakai'];
                    while ($dipakai > 0) {
                        $ada = (float)$stok[$index]->jumlah;
                        if ($ada < $dipakai) {
                            $temp = [
                                'nopemakaian' => $pemakaianruangan,
                                'kd_obat' => $rin['kdobat'],
                                'nopenerimaan' => $stok[$index]->nopenerimaan,
                                'nobatch' => $stok[$index]->nobatch,
                                'jumlah' => $ada,
                                'flag' => '',
                            ];
                            $rinci[] = $temp;
                            $sisa = $dipakai - $ada;
                            $index += 1;
                            $dipakai = $sisa;
                        } else {
                            $temp = [
                                'nopemakaian' => $pemakaianruangan,
                                'kd_obat' => $rin['kdobat'],
                                'nopenerimaan' => $stok[$index]->nopenerimaan,
                                'nobatch' => $stok[$index]->nobatch,
                                'jumlah' => $dipakai,
                                'flag' => '',
                            ];
                            $rinci[] = $temp;
                            $dipakai = 0;
                            $sisa = $ada - $dipakai;
                        }
                    }
                }
            }
            //simpan header
            $simpahHead = PemakaianH::firstOrCreate(['nopemakaian' => $pemakaianruangan], $header);
            // if (!$simpahHead) {
            //     return new JsonResponse(['message' => 'Header Data Gagal Disimpan'], 410);
            // }
            // simpan rinci
            $simpanRinci = PemakaianR::insert($rinci);
            // update stok
            $rinc = PemakaianR::where('nopemakaian', $pemakaianruangan)->where('flag', '')->get();
            $st = [];
            $ss = [];
            foreach ($rinc as $rin) {
                $stok = Stokreal::where('kdobat', $rin['kd_obat'])
                    ->where('kdruang', $request->kdruang)
                    ->where('nopenerimaan', $rin['nopenerimaan'])
                    ->first();
                $st[] = $stok;
                if ($stok->jumlah > 0) {

                    $sisa = $stok->jumlah - $rin['jumlah'];
                    $ss[] = ['sisa' => $sisa, 'stok' => $stok->jumlah, 'dipakai' => $rin['jumlah']];
                    $stok->jumlah = $sisa;
                    $stok->save();
                    $rin['flag'] = '1';
                    $rin->save();
                }
            }
            DB::commit();
            return new JsonResponse([
                'head' => $simpahHead,
                'rinci' => $simpanRinci,
                'message' => 'Pemakaian Disimpan, stok berkurang. '
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan...!!!',
                'result' => $e,
                'rinc' => $rinc,
                'st' => $st,
                'ss' => $ss,
            ], 410);
        }
    }

    public function selesaiPakai(Request $request)
    {
        $data = PemakaianH::where('nopemakaian', $request->nopemakaian)->first();
        $data->flag = '1';
        $data->save();
        return new JsonResponse([
            'message' => 'Data sudah dikunci.',
            'data' => $data
        ]);
    }
}
