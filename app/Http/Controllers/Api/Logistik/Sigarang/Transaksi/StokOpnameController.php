<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\MonthlyStokUpdate;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\StokOpname;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StokOpnameController extends Controller
{
    // data gudang dan depo sigarang
    public function getDataGudangDepo()
    {

        $data = Gudang::where('gedung', 2)
            ->where('lantai', '>', 0)
            ->where('gudang', '>', 0)
            ->get();
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

    public function getDataStokOpnameByDepo()
    {
        $bulan = request('bulan') ? request('bulan') : date('m');
        $tahun = request('tahun') ? request('tahun') : date('Y');

        $raw = MonthlyStokUpdate::where('tanggal', '>=', $tahun . '-' . $bulan . '-1')
            ->where('tanggal', '<=', $tahun . '-' . $bulan . '-31')
            ->where('kode_ruang', '=', request('search'))
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

    public function storePenyesuaian(Request $request)
    {
        $monthlyStok = MonthlyStokUpdate::find($request->id);

        $recent = RecentStokUpdate::where('kode_rs', $monthlyStok->kode_rs)
            ->where('kode_ruang', $monthlyStok->kode_ruang)
            ->where('no_penerimaan', $monthlyStok->no_penerimaan)->first();

        // return new JsonResponse([
        //     'monthly' => $monthlyStok,
        //     'recent' => $recent,
        //     'request' => $request->all(),
        // ], 200);

        $penyesuaian = StokOpname::updateOrCreate(
            [
                'monthly_stok_update_id' => $monthlyStok->id,
            ],
            $request->all()
        );

        $recent->update([
            'sisa_stok' => $request->jumlah
        ]);

        if ($penyesuaian->wasRecentlyCreated) {
            return new JsonResponse(['message' => 'data berhasil disimpan'], 201);
        }
        if ($penyesuaian->wasChanged()) {
            return new JsonResponse(['message' => 'data berhasil disimpan'], 201);
        }

        return new JsonResponse(['message' => 'Tidak ada perubahan data'], 417);
    }
}
