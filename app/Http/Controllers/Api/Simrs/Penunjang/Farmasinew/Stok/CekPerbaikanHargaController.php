<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_h;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_r;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CekPerbaikanHargaController extends Controller
{
    public function getPerbaikanHarga(Request $request)
    {
        $data['awal'] = Stokopname::where('kdobat', $request->kdobat)
            ->where('nopenerimaan', $request->nopenerimaan)
            ->where('tglopname', 'LIKE', '%2024-05%')
            ->get();
        $data['penerimaan'] = PenerimaanRinci::select('id', 'nopenerimaan', 'kdobat', 'jml_terima_k as jumlah', 'tgl_exp as tglexp', 'no_batch as nobatch', 'harga_netto_kecil as harga')
            ->with('header:nopenerimaan,tglpenerimaan')
            ->where('kdobat', $request->kdobat)
            ->where('nopenerimaan', $request->nopenerimaan)
            ->get();
        $data['mutasi'] = Mutasigudangkedepo::where('nopenerimaan', $request->nopenerimaan)
            ->where('kd_obat', $request->kdobat)
            ->where('no_permintaan', $request->no_permintaan)
            ->get();
        return response()->json([
            'status' => 'success',
            'req' => $request->all(),
            'data' => $data,
        ]);
    }
    public function simpanPerbaikanHarga(Request $request)
    {
        $data = Mutasigudangkedepo::where('nopenerimaan', $request->nopenerimaan)
            ->where('kd_obat', $request->kd_obat)
            ->get();
        if (sizeof($data) <= 0) {
            return new JsonResponse([
                'message' => 'Data Mutasi Tidak Ditemukan',
                'req' => $request->all(),
            ], 410);
        }
        foreach ($data as $key) {
            if ($key->harga != $request->harga) $key->update(['harga' => $request->harga]);
            if ($key->nobatch != $request->nobatch) $key->update(['nobatch' => $request->nobatch]);
            if ($key->tglexp != $request->tglexp) $key->update(['tglexp' => $request->tglexp]);
            if ($key->tglpenerimaan != $request->tglpenerimaan) $key->update(['tglpenerimaan' => $request->tglpenerimaan]);
        }
        // $data = Mutasigudangkedepo::find($request->id);
        // if (!$data) {
        //     return new JsonResponse([
        //         'message' => 'Data Mutasi Tidak Ditemukan',
        //         'req' => $request->all(),
        //     ], 410);
        // }

        // if ($data->harga != $request->harga) $data->update(['harga' => $request->harga]);
        // if ($data->nobatch != $request->nobatch) $data->update(['nobatch' => $request->nobatch]);
        // if ($data->tglexp != $request->tglexp) $data->update(['tglexp' => $request->tglexp]);
        // if ($data->tglpenerimaan != $request->tglpenerimaan) $data->update(['tglpenerimaan' => $request->tglpenerimaan]);

        return new JsonResponse([
            'message' => 'Data Mutasi sudah diganti',
            'data' => $data,
            'req' => $request->all(),
        ]);
    }
    public function simpanPecahNomor(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $data = [];
            if ($request->tipe == 'racikan') {
                foreach ($request->data as $key) {
                    if ($key['satuan_racik'] == null) $key['satuan_racik'] = '';
                    if ($key['id']) {
                        $temp = Resepkeluarrinciracikan::find($key['id']);
                        if (!$temp) return new JsonResponse([
                            'message' => 'Data Racikan Tidak Ditemukan',
                            'req' => $request->all(),
                        ], 410);
                        $temp->update([
                            'jumlah' => $key['jumlah']
                        ]);
                        $data[] = $temp;
                    } else {
                        unset($key['id']);
                        $temp = Resepkeluarrinciracikan::create($key);
                        $data[] = $temp;
                    }
                }
            }
            if ($request->tipe == 'resep') {
                foreach ($request->data as $key) {
                    // if ($key['satuan_racik'] == null) $key['satuan_racik'] = '';
                    if ($key['id']) {
                        $temp = Resepkeluarrinci::find($key['id']);
                        if (!$temp) return new JsonResponse([
                            'message' => 'Data Racikan Tidak Ditemukan',
                            'req' => $request->all(),
                        ], 410);
                        $temp->update([
                            'jumlah' => $key['jumlah']
                        ]);
                        $data[] = $temp;
                    } else {
                        unset($key['id']);
                        $temp = Resepkeluarrinci::create($key);
                        $data[] = $temp;
                    }
                }
            }
            if ($request->tipe == 'mutasi') {
                foreach ($request->data as $key) {
                    // if ($key['satuan_racik'] == null) $key['satuan_racik'] = '';
                    if ($key['id']) {
                        $temp = Mutasigudangkedepo::find($key['id']);
                        if (!$temp) return new JsonResponse([
                            'message' => 'Data Racikan Tidak Ditemukan',
                            'req' => $request->all(),
                        ], 410);
                        $temp->update([
                            'jml' => $key['jml']
                        ]);
                        $data[] = $temp;
                    } else {
                        unset($key['id']);
                        $temp = Mutasigudangkedepo::create($key);
                        $data[] = $temp;
                    }
                }
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Mutasi sudah diganti',
                'data' => $data,
                'req' => $request->all(),
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => '' . $e->getLine(),
                'file' =>  $e->getFile(),
                'req' => $request->all(),
            ], 500);
        }
    }
    public function gantiNomor(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $data = null;
            $str = str_contains($request->targetNoper, 'awal');
            if ($str) {
                $penerimaan = Stokopname::where('nopenerimaan', $request->targetNoper)
                    ->where('kdobat', $request->kdobat)
                    ->where('tglOpname', 'like', '%2024-05%')
                    ->first();
            } else {
                $penerimaan = PenerimaanRinci::select('nopenerimaan', 'kdobat', 'jml_terima_k as jumlah', 'tgl_exp as tglexp', 'no_batch as nobatch', 'harga_netto_kecil as harga')
                    ->with('header:nopenerimaan,tglpenerimaan')
                    ->where('nopenerimaan', $request->targetNoper)
                    ->where('kdobat', $request->kdobat)
                    ->first();
                $penerimaan->tglpenerimaan = $penerimaan->header->tglpenerimaan;
            }
            if ($request->tipe == 'racikan') {
                $data = Resepkeluarrinciracikan::find($request->id);
                if (!$data) return new JsonResponse([
                    'message' => 'Data Racikan Tidak Ditemukan',
                    'req' => $request->all(),
                ], 410);
                $data->update([
                    'nopenerimaan' => $penerimaan->nopenerimaan,
                    'harga_beli' => $penerimaan->harga,
                ]);
            } else if ($request->tipe == 'resep') {
                $data = Resepkeluarrinci::find($request->id);
                if (!$data) return new JsonResponse([
                    'message' => 'Data Resep Tidak Ditemukan',
                    'req' => $request->all(),
                ]);
                $data->update([
                    'nopenerimaan' => $penerimaan->nopenerimaan,
                    'harga_beli' => $penerimaan->harga,
                ]);
            } else if ($request->tipe == 'mutasi') {
                $data = Mutasigudangkedepo::find($request->id);
                if (!$data) return new JsonResponse([
                    'message' => 'Data Mutasi Tidak Ditemukan',
                    'req' => $request->all(),
                ]);
                $data->update(['nopenerimaan' => $penerimaan->nopenerimaan]);
                if ($data->harga != $penerimaan->harga) $data->update(['harga' => $penerimaan->harga]);
                if ($data->nobatch != $penerimaan->nobatch) $data->update(['nobatch' => $penerimaan->nobatch]);
                if ($data->tglexp != $penerimaan->tglexp) $data->update(['tglexp' => $penerimaan->tglexp]);
                if ($data->tglpenerimaan != $penerimaan->tglpenerimaan) $data->update(['tglpenerimaan' => $penerimaan->tglpenerimaan]);
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Mutasi sudah diganti',
                'data' => $data,
                'penerimaan' => $penerimaan,
                'req' => $request->all(),
            ],);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => '' . $e->getLine(),
                'file' =>  $e->getFile(),
                'req' => $request->all(),
            ], 500);
        }
    }

    /**
     * Pengecekan Harga
     */
    public function getObat(Request $request)
    {
        $temp = Mobatnew::select('kd_obat', 'nama_obat')
            ->when($request->q, function ($query) use ($request) {
                $query->where('kd_obat', 'like', '%' . $request->q . '%')
                    ->orWhere('nama_obat', 'like', '%' . $request->q . '%');
            })
            ->paginate($request->per_page);
        $data['data'] = collect($temp)['data'];
        $data['meta'] = collect($temp)->except('data');
        $data['kode'] = collect($data['data'])->pluck('kd_obat');
        if ($request->kdruang) {
            $noper = [];
            $now = $request->tahun . "-" . $request->bulan;
            $data['stok'] = Stokreal::whereIn('kdobat', $data['kode'])->where('kdruang', $request->kdruang)->get();
            $data['opname'] = Stokopname::whereIn('kdobat', $data['kode'])->where('kdruang', $request->kdruang)->where('tglOpname', 'like', '%' . $now . '%')->get();
            $headMut = Permintaandepoheder::select('no_permintaan')
                ->where('dari', $request->kdruang)
                ->where('tgl_terima_depo', 'LIKE', '%' . $now . '%')
                ->pluck('no_permintaan');
            $data['mutasi'] = Mutasigudangkedepo::select('id', 'no_permintaan', 'tglpenerimaan', 'kd_obat as kdobat', 'jml as jumlah', 'tglexp', 'nobatch', 'harga')->whereIn('no_permintaan', $headMut)
                ->whereIn('kd_obat', $data['kode'])
                ->get();

            $headMutKel = Permintaandepoheder::select('no_permintaan')
                ->where('tujuan', $request->kdruang)
                ->where('dari', 'LIKE', 'R-')
                ->where('tgl_kirim_depo', 'LIKE', '%' . $now . '%')
                ->pluck('no_permintaan');
            $data['mutasikeluar'] = Mutasigudangkedepo::select('id', 'no_permintaan', 'tglpenerimaan', 'kd_obat as kdobat', 'jml as jumlah', 'tglexp', 'nobatch', 'harga')->whereIn('no_permintaan', $headMutKel)
                ->whereIn('kd_obat', $data['kode'])
                ->get();

            $haResep = Resepkeluarheder::select('noresep')
                ->where('depo', $request->kdruang)
                ->where('tgl_selesai', 'LIKE', '%' . $now . '%')
                ->whereIn('flag', ['3', '4'])
                ->pluck('noresep');
            $data['resep'] = Resepkeluarrinci::select('id', 'noresep', 'kdobat', 'jumlah', 'harga_beli', 'nopenerimaan')
                ->whereIn('noresep', $haResep)
                ->whereIn('kdobat', $data['kode'])
                ->get();
            $data['racikan'] = Resepkeluarrinciracikan::select('id', 'noresep', 'kdobat', 'jumlah', 'harga_beli', 'nopenerimaan')
                ->whereIn('noresep', $haResep)
                ->whereIn('kdobat', $data['kode'])
                ->get();

            $heRet = Returpenjualan_h::select('retur_penjualan_h.noretur')
                ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'retur_penjualan_h.noresep')
                ->where('resep_keluar_h.depo', $request->kdruang)
                ->where('retur_penjualan_h.tgl_retur', 'LIKE', '%' . $now . '%')
                ->pluck('retur_penjualan_h.noretur');
            $data['retur'] = Returpenjualan_r::select('id', 'noresep', 'noretur', 'kdobat', 'jumlah_retur', 'harga_beli', 'nopenerimaan')
                ->whereIn('noretur', $heRet)
                ->whereIn('kdobat', $data['kode'])
                ->with([
                    'resep' => function ($q) use ($data) {
                        $q->select('id', 'noresep', 'kdobat', 'jumlah', 'harga_beli', 'nopenerimaan')
                            ->whereIn('kdobat', $data['kode']);
                    }
                ])
                ->get();
            $noper = array_merge(
                $data['opname']->pluck('nopenerimaan')->toArray(),
                $data['mutasi']->pluck('nopenerimaan')->toArray(),
                $data['mutasikeluar']->pluck('nopenerimaan')->toArray(),
                $data['resep']->pluck('nopenerimaan')->toArray(),
                $data['racikan']->pluck('nopenerimaan')->toArray(),
                $data['retur']->pluck('nopenerimaan')->toArray(),
                $data['stok']->pluck('nopenerimaan')->toArray()
            );

            $data['noper'] = array_unique($noper);
            $data['awal'] = Stokopname::whereIn('kdobat', $data['kode'])->whereIn('nopenerimaan', $data['noper'])->where('tglOpname', 'like', '%2024-05%')->get();
            $data['penerimaan'] = PenerimaanRinci::select('nopenerimaan', 'kdobat', 'jml_terima_k as jumlah', 'tgl_exp as tglexp', 'no_batch as nobatch', 'harga_netto_kecil as harga')
                ->with('header:nopenerimaan,tglpenerimaan')
                ->whereIn('kdobat', $data['kode'])
                ->whereIn('nopenerimaan', $data['noper'])->get();
        }
        return new JsonResponse([
            'message' => 'OK',
            'data' => $data,
            'req' => $request->all(),
        ]);
    }
}
