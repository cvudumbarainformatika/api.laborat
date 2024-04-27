<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\BarangRusak;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangRusakController extends Controller
{
    //
    public function cariObat()
    {
        $gudang = ['Gd-05010100', 'Gd-03010100'];
        $data = Mobatnew::select(
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
            'new_masterobat.satuan_b',
            DB::raw('sum(stokreal.jumlah) as jumlah'),
        )
            ->leftJoin('stokreal', 'stokreal.kdobat', '=', 'new_masterobat.kd_obat')
            ->when(request('q'), function ($q) {
                $q->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%');
            })
            ->whereIn('stokreal.kdruang', $gudang)
            ->where('stokreal.jumlah', '>', 0)
            ->groupBy('new_masterobat.kd_obat')
            ->limit(50)
            ->get();
        return new JsonResponse($data);
    }
    public function cariBatch()
    {
        $gudang = ['Gd-05010100', 'Gd-03010100'];
        $data = Stokrel::selectRaw('nobatch')
            ->whereIn('stokreal.kdruang', $gudang)
            ->when(request('batch'), function ($q) {
                $q->where('nobatch', 'LIKE', '%' . request('batch') . '%');
            })
            ->where('kdobat', request('kdobat'))
            ->where('jumlah', '>', 0)
            // ->with(
            //     'penerimaan:nopenerimaan,kdpbf',
            //     'penerimaan.pihakketiga:kode,nama'
            // )
            ->groupBy('nobatch')
            ->limit(50)
            ->get();
        return new JsonResponse($data);
    }
    public function cariPenerimaan()
    {
        $gudang = ['Gd-05010100', 'Gd-03010100'];
        if (!in_array(request('kd_ruang'), $gudang)) {
            return new JsonResponse(['message' => 'Kode Gudang Salah, Apakah Anda Tidak Memeiliki Akses Gudang?'], 410);
        }
        $data = Stokrel::selectRaw('*, sum(jumlah) as total')
            // ->whereIn('stokreal.kdruang', $gudang)
            ->where('stokreal.kdruang', request('kd_ruang'))
            ->when(request('noper'), function ($q) {
                $q->where('nopenerimaan', 'LIKE', '%' . request('noper') . '%');
            })
            ->where('kdobat', request('kdobat'))
            ->where('nobatch', request('nobatch'))
            ->where('jumlah', '>', 0)
            ->with([
                'penerimaan:nopenerimaan,kdpbf',
                'penerimaan.pihakketiga:kode,nama',
                'penerimaan.penerimaanrinci:nopenerimaan,isi,harga_netto_kecil',
                'harga' => function ($h) {
                    $h->select('nopenerimaan', 'harga', 'kd_obat')->where('kd_obat', request('kdobat'))
                        ->orderBy('tgl_mulai_berlaku', 'DESC');
                },
            ])
            ->groupBy('kdobat', 'nobatch', 'nopenerimaan')
            ->limit(50)
            ->get();
        return new JsonResponse($data);
    }
    public function simpan(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $user = FormatingHelper::session_user();
            $data = BarangRusak::updateOrCreate(
                [
                    'kd_obat' => $request->kd_obat,
                    'nopenerimaan' => $request->nopenerimaan,
                    'nobatch' => $request->no_batch,
                    'kdpbf' => $request->kdpbf ?? '',
                    'kunci' => '',
                ],
                [
                    'tgl_rusak' => date('Y-m-d'),
                    'tgl_entry' => date('Y-m-d H:i:s'),
                    'satuan_bsr' => $request->satuan_bsr,
                    'isi' => $request->isi ?? 0,
                    'satuan_kcl' => $request->satuan_kcl,
                    'harga_net' => $request->harga_net,
                    'jumlah' => $request->jumlah,
                    'status' => $request->status,
                    'user_entry' => $user['kodesimrs'],
                ]
            );
            DB::connection('farmasi')->commit();
            $data->load('pihakketiga:kode,nama', 'masterobat:kd_obat,nama_obat');
            return new JsonResponse(['data' => $data, 'message' => 'Data Sudah Tersimpan']);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 410);
        }
    }
    public function getListBelumKunci()
    {
        $data = BarangRusak::with('pihakketiga:kode,nama', 'masterobat:kd_obat,nama_obat')->where('kunci', '')->get();
        return new JsonResponse($data);
    }
    public function getListSudahKunci()
    {
        $obat = [];
        $pbf = [];
        if (request('q')) {
            $ob = Mobatnew::select('kd_obat')
                ->where('nama_obat', 'LIKE', '%' . request('q') . '%')
                ->get();
            $rw = collect($ob);
            $obat = $rw->map(function ($item) {
                return $item->kd_obat;
            });
            $pb = Mpihakketiga::select('kode')
                ->where('nama', 'LIKE', '%' . request('q') . '%')
                ->get();
            $raw = collect($pb);
            $pbf = $raw->map(function ($item) {
                return $item->kode;
            });
        }
        $data = BarangRusak::where('kunci', '1')
            ->when(count($obat) > 0, function ($q) use ($obat) {
                $q->orWhereIn('kd_obat', $obat);
            })
            ->when(count($pbf) > 0, function ($q) use ($pbf) {
                $q->orWhereIn('kdpbf', $pbf);
            })
            ->with('pihakketiga:kode,nama', 'masterobat:kd_obat,nama_obat')
            ->paginate(request('per_page'));

        return new JsonResponse([
            'data' => $data['data'],
            'meta' => $data->except('data'),
            'obat' => $obat,
            'pbf' => $pbf,
        ]);
    }
    public function hapusData(Request $request)
    {
        $pbf = $request->kdpbf ?? '';
        $data = BarangRusak::where('kd_obat', $request->kd_obat)
            ->where('nopenerimaan', $request->nopenerimaan)
            ->where('nobatch', $request->nobatch)
            ->where('kdpbf', $pbf)
            ->where('kunci', '')
            ->first();
        if (!$data) {
            return new JsonResponse([
                'message' => 'Gagal Hapus, Data Tidak Ditemukan'
            ], 410);
        }
        $data->delete();
        return new JsonResponse([
            'request' => $request->all(),
            'data' => $data,
            'message' => 'Data Sudah Dihapus'
        ]);
    }
    public function kunci(Request $request)
    {
        $pbf = $request->kdpbf ?? '';
        $data = BarangRusak::where('kd_obat', $request->kd_obat)
            ->where('nopenerimaan', $request->nopenerimaan)
            ->where('nobatch', $request->nobatch)
            ->where('kdpbf', $pbf)
            ->where('kunci', '')
            ->first();
        $stok = Stokrel::where('kdobat', $request->kd_obat)
            ->where('nopenerimaan', $request->nopenerimaan)
            ->where('kdruang', $request->kd_ruang)
            ->where('nobatch', $request->nobatch)
            ->where('jumlah', '>', 0)
            ->first();
        if (!$data) {
            return new JsonResponse([
                'message' => 'Gagal Kunci, Data Tidak Ditemukan'
            ], 410);
        }
        if (!$stok) {
            return new JsonResponse([
                'message' => 'Gagal Kunci, Stok tidak ditemukan'
            ], 410);
        }
        $sisa = (float) $stok->jumlah - (float) $data->jumlah;
        if ($sisa < 0) {
            return new JsonResponse([
                'message' => 'Gagal Kunci, Jumlah Stok Tidak Mencukupi'
            ], 410);
        }
        $data->kunci = '1';
        $data->save();

        $stok->jumlah = $sisa;
        $stok->save();
        return new JsonResponse([
            'request' => $request->all(),
            'sisa' => $sisa,
            'stok' => $stok,
            'data' => $data,
            'message' => 'Data Sudah Dikunci, dan Stok Sudah Berkurang'
        ]);
    }
}
