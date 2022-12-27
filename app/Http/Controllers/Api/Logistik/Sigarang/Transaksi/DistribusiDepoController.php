<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DetailDistribusiDepo;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DistribusiDepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistribusiDepoController extends Controller
{
    public function index()
    {
        $data = DistribusiDepo::latest('id')
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function toDistribute()
    {
        $data = DistribusiDepo::where('status', '=', 1)
            ->latest('id')
            ->with('details.barangrs', 'details.barang108', 'depo')
            ->get();

        return new JsonResponse($data);
    }

    public function getDistribusi()
    {
        // $data = DistribusiDepo::where('status', '=', 1)
        //     ->with('details')
        //     ->get();
        $data = DetailDistribusiDepo::selectRaw('kode_rs,sum(jumlah) as jml')
            ->whereHas('distribusi', function ($a) {
                $a->where('status', '=', 1);
            })->groupBy('kode_rs')->get();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'details' => 'required',
            // 'no_penerimaan' => 'required'
        ]);
        //
        $details = $request->details;
        $data = DistribusiDepo::create($request->only('reff', 'no_distribusi',  'kode_depo'));
        if ($data) {
            foreach ($details as $key) {
                $data->details()->create($key);
            }
        }
        if (!$data->wasRecentlyCreated) {
            return new JsonResponse(['message' => 'data gagal dibuat'], 500);
        }
        return new JsonResponse(['message' => 'data telah dibuat'], 201);
    }

    public function diterimaDepo(Request $request)
    {
        $tanggal = date('Y-m-d H:i:s');
        $data = DistribusiDepo::with('details')->find($request->id);


        foreach ($data->details as $key) {
            $jumlah = $key->jumlah;
            $stok = RecentStokUpdate::where('kode_ruang', 'Gd-02010100')
                ->where('kode_rs', $key->kode_rs)
                ->where('sisa_stok', '>', 0)
                ->oldest()
                ->get();
            $index = 0;
            $sisaStok = collect($stok)->sum('sisa_stok');

            if ($jumlah > $sisaStok) {
                return new JsonResponse(['message' => 'Stok tidak mencukupi permintaan', $jumlah, $sisaStok], 413);
            }
            $masuk = $jumlah;
            // pengecekan stok FIFO
            do {
                $ada = $stok[$index]->sisa_stok;
                // return new JsonResponse(['message' => 'Stok tidak mencukupi permintaan'], 413);
                if ($ada <= $masuk) {
                    $sisa = $masuk - $ada;
                    RecentStokUpdate::create([
                        'kode_rs' => $key->kode_rs,
                        'kode_ruang' => $data->kode_depo,
                        'sisa_stok' => $key->jumlah,
                        'harga' => $stok[$index]->harga,
                        'no_penerimaan' => $stok[$index]->no_penerimaan,
                    ]);
                    $stok[$index]->update([
                        'sisa_stok' => 0
                    ]);
                    $data->details()->update(['no_penerimaan' => $stok[$index]->no_penerimaan]);
                    $index = $index + 1;
                    $loop = true;
                }
                $sisa = $ada - $masuk;

                RecentStokUpdate::create([
                    'kode_rs' => $key->kode_rs,
                    'kode_ruang' => $data->kode_depo,
                    'sisa_stok' => $key->jumlah,
                    'harga' => $stok[$index]->harga,
                    'no_penerimaan' => $stok[$index]->no_penerimaan,
                ]);

                $stok[$index]->update([
                    'sisa_stok' => $sisa
                ]);

                $data->details()->update(['no_penerimaan' => $stok[$index]->no_penerimaan]);
                $loop = false;
            } while ($loop);


            // $stok = RecentStokUpdate::where('kode_ruang', 'Gd-02010100')
            //     ->where('kode_rs', $key->kode_rs)
            //     ->where('no_penerimaan', $data->no_penerimaan)
            //     ->first();
            // $diStok = $stok->sisa_stok;
            // $jumlah = $key->jumlah;
            // if ($diStok > $jumlah) {
            //     $sisa = $diStok - $jumlah;
            //     $stok->update([
            //         'sisa_stok' => $sisa
            //     ]);
            //     RecentStokUpdate::create([
            //         'kode_rs' => $key->kode_rs,
            //         'sisa_stok' => $key->jumlah,
            //         'harga' => $stok->harga,
            //         'no_penerimaan' => $data->no_penerimaan,
            //     ]);
            // } else {
            //     // cari sisa pengurangan
            //     $sisaKurang = $jumlah - $diStok;
            //     //kurangi stok lama
            //     $stok->update([
            //         'sisa_stok' => 0
            //     ]);
            //     // buat update dengan nomor terkait
            //     RecentStokUpdate::create([
            //         'kode_rs' => $key->kode_rs,
            //         'sisa_stok' => $key->$diStok,
            //         'harga' => $stok->harga,
            //         'no_penerimaan' => $data->no_penerimaan,
            //     ]);
            //     // ambil stok baru
            //     $stok2 = RecentStokUpdate::where('kode_ruang', 'Gd-02010100')
            //         ->where('kode_rs', $key->kode_rs)
            //         ->where('sisa_stok', '>', 0)
            //         ->first();
            //     // hitung dengan stok yang baru
            //     $diStok2 = $stok2->sisa_stok;
            //     $sisa2 = $diStok2 - $sisaKurang;
            //     $stok2->update([
            //         'sisa_stok' => $sisa2
            //     ]);
            //     RecentStokUpdate::create([
            //         'kode_rs' => $key->kode_rs,
            //         'sisa_stok' => $key->$diStok2,
            //         'harga' => $stok2->harga,
            //         'no_penerimaan' => $stok2->no_penerimaan,
            //     ]);
            // }
        }

        $data->update([
            'tanggal' => $tanggal,
            'status' => 2,
        ]);
        if (!$data->wasChanged()) {
            return new JsonResponse(['message' => 'data gagal diterima'], 500);
        }
        return new JsonResponse(['message' => 'data telah diterima'], 200);
    }
}
