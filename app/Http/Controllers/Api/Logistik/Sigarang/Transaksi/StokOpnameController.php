<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\MonthlyStokUpdate;
use App\Models\Sigarang\RecentStokUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StokOpnameController extends Controller
{
    // data gudang dan depo sigarang
    public function getDataGudangDepo()
    {
        $umum = Gudang::where('gedung', 0)
            ->first();
        $data = Gudang::where('gedung', 2)
            ->where('depo', '>', 0)
            ->get();
        $data[count($data)] = $umum;
        return new JsonResponse($data);
    }
    // ambil data stok current ->
    // masukkan ke tabel stok opname bulanan ->
    // tampilkan ->
    // jika ada perbedaan tulis jumlah dan sisanya di tabel stok opname
    public function index(Request $request)
    {
        $request->validate(['gudang' => 'required']);
        $data = RecentStokUpdate::where('kode_ruang', $request->gudang)
            ->filter([$request->search])
            ->paginate(10);

        return new JsonResponse($data);
    }

    public function getDataStokOpname()
    {
        $bulan = request('bulan') ? request('bulan') : date('m');
        $tahun = request('tahun') ? request('tahun') : date('Y');

        $raw = MonthlyStokUpdate::where('tanggal', '>=', $tahun . '-' . $bulan . '-1')
            ->where('tanggal', '<=', $tahun . '-' . $bulan . '-31')
            ->with('penyesuaian', 'barang.mapingbarang.barang108', 'depo')
            ->paginate(request('per_page'));
        $col = collect($raw);
        $meta = $col->except('data');
        $meta->all();

        $data = $col->only('data');
        $data['meta'] = $meta;
        return new JsonResponse($data);
    }

    public function storeMonthly()
    {
        $recent = RecentStokUpdate::where('sisa_stok', '>', 0)->get();
        $total = [];
        $tanggal = date('Y-m-d') . ' 23:59:59';
        foreach ($recent as $key) {
            $data = MonthlyStokUpdate::create([
                'tanggal' => $tanggal,
                'kode_rs' => $key->kode_rs,
                'kode_ruang' => $key->kode_ruang,
                'no_penerimaan' => $key->no_penerimaan,
                'harga' => $key->harga,
                'sisa_stok' => $key->sisa_stok,
            ]);
            array_push($total, $data);
        }
        if (count($recent) !== count($total)) {
            return new JsonResponse(['message' => 'ada kesalahan dalam penyimpanan data stok opname, hubungi tim IT'], 409);
        }
        return new JsonResponse(['message' => 'data berhasil disimpan'], 201);
    }
}
