<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\MinMaxDepo;
use App\Models\Sigarang\RecentStokUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    // stok user
    // stok alokasi depo
    // stok alokasi user
    // maks stok maksimal depo
    // maks stok maksimal user
    //  user ? ruangan?
    /*
    * get stok min max depo
    */
    public function stokMinMaxDepo(Request $request)
    {
        $depo = $request->kode_depo;
        $data = MinMaxDepo::where('kode_depo', '=', $depo)->get();
        return new JsonResponse($data, 200);
    }

    public function stokSekarang()
    {
        $perpage = request('per_page') ? request('per_page') : 10;
        $raw = RecentStokUpdate::with('depo', 'ruang', 'barang.barang108')
            ->where('kode_ruang', '<>', 'Gd-02010100')
            ->filter(request(['q']))
            ->paginate($perpage);
        $col = collect($raw);
        $meta = $col->except('data');
        $meta->all();

        $data = $col->only('data');
        $data['meta'] = $meta;
        return new JsonResponse($data);
    }

    public function currentStok()
    {
        // $data = RecentStokUpdate::get();
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }

    // ruang yang punya stok
    public function ruangHasStok()
    {
        $raw = RecentStokUpdate::where('sisa_stok', '>', 0)
            ->where('kode_ruang', '<>', 'Gd-02010100')
            ->with('depo', 'ruang')->get();
        $data = collect($raw)->unique('kode_ruang');
        $data->all();
        return new JsonResponse($data);
    }
    // get data by depo
    public function getDataStokByDepo()
    {

        $raw = RecentStokUpdate::where('kode_ruang', '=', request('search'))
            ->filter(request(['q']))
            ->with('ruang', 'barang.barang108', 'depo')
            ->paginate(request('per_page'));
        $col = collect($raw);
        $meta = $col->except('data');
        $meta->all();

        $data = $col->only('data');
        $data['meta'] = $meta;
        return new JsonResponse($data);
    }
    public function currentHasStok()
    {
        // $data = RecentStokUpdate::get();
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->where('sisa_stok', '>', 0)
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }

    //ambil stok tiap-tiap ruangan
    public function stokRuangan()
    {
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->where('kode_ruang', 'LIKE', 'R-' . '%')
            ->where('sisa_stok', '>', 0)
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }

    //ambil stok tiap-tiap gudang
    public function stokNonRuangan()
    {
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->where('kode_ruang', 'LIKE', 'Gd-' . '%')
            ->where('sisa_stok', '>', 0)
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }
    //ambil stok berdasarkan ruangan
    public function stokByRuangan()
    {
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->where('kode_ruang', request('kode_ruang'))
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }
    // ambil data stok yang masih ada di gudang.
    // data ini berarti data yang belum di distribusikan
    public function currentStokGudang()
    {
        // $data = RecentStokUpdate::get();
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->where('kode_ruang', 'Gd-02010100')
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }

    public function currentStokByRuangan(Request $request)
    {
        $ruang = $request->ruang;
        $data = RecentStokUpdate::where('kode_ruang', $ruang)
            ->get();
        return new JsonResponse($data);
    }

    public function currentStokByPermintaan(Request $request)
    {
        $permintaan = $request->permintaan;
        $data = RecentStokUpdate::where('no_permintaan', $permintaan)
            ->get();
        return new JsonResponse($data);
    }

    public function currentStokByBarang(Request $request)
    {
        $barang = $request->barang;
        $data = RecentStokUpdate::where('kode_rs', $barang)
            ->get();
        return new JsonResponse($data);
    }
    public function currentStokByGudang()
    {
        $data = RecentStokUpdate::where('kode_ruang', 'Gd-02010100')
            ->get();
        return new JsonResponse($data);
    }
}
