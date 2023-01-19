<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\MapingBarangDepo;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\Permintaanruangan\DetailPermintaanruangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifPermintaanruanganController extends Controller
{
    // ambil semua permintaan yang sudah selesai di input
    public function getPermintaan()
    {
        $datas = Permintaanruangan::where('status', '=', 5)
            ->with('details.barangrs', 'details.satuan', 'pj', 'pengguna')->get();
        // if (count($data)) {
        //     foreach ($data as $key) {
        //         $key->gudang = collect($key->details)->groupBy('dari');
        //     }
        // }
        // dari itu === kode_depo
        // tujuan itu === kode_ruangn
        foreach ($datas as $key) {
            foreach ($key->details as $detail) {
                $temp = $this->stokRuanganByBarang($detail->kode_rs, $detail->tujuan, $detail->dari);
                $detail->alokasi = $temp->alokasi;
                $detail->stokDepo = $temp->stokDepo;
                $detail->stokRuangan = $temp->stokRuangan;
            }
        }

        return new JsonResponse($datas);
    }
    public function stokRuanganByBarang($kode_rs, $kode_ruangan, $kode_depo)
    {
        // $kode_rs = request('kode_rs');
        // $kode_ruangan = request('kode_ruangan');

        // ambil data barang tidak puerlu karena sudah ada
        // $barang = BarangRS::where('kode', $kode_rs)->first();

        // cari barang ini masuk depo mana
        // $depo = MapingBarangDepo::where('kode_rs', $kode_rs)->first();

        // ambil stok ruangan
        $stokRuangan = RecentStokUpdate::where('kode_rs', $kode_rs)
            ->where('kode_ruang', $kode_ruangan)->get();

        $totalStokRuangan = collect($stokRuangan)->sum('sisa_stok');

        // cari stok di depo
        $stok = RecentStokUpdate::where('kode_rs', $kode_rs)
            ->where('kode_ruang', $kode_depo)->get();

        $totalStok = collect($stok)->sum('sisa_stok');

        // ambil alokasi barang
        $data = DetailPermintaanruangan::whereHas('permintaanruangan', function ($q) {
            $q->where('status', '>=', 5)
                ->where('status', '<', 7);
        })->where('kode_rs', $kode_rs)->get();

        $col = collect($data);

        $gr = $col->map(function ($item) {
            $jumsem = $item->jumlah_disetujui ? $item->jumlah_disetujui : $item->jumlah;
            $item->alokasi = $jumsem;
            return $item;
        });

        $sum = $gr->sum('alokasi');

        $alokasi = 0;
        // ambil permintaan dari ruangan ybs
        $permintaanRuangan = DetailPermintaanruangan::whereHas('permintaanruangan', function ($q) {
            $q->where('status', '>=', 5)
                ->where('status', '<', 7);
        })->where('kode_rs', $kode_rs)->where('tujuan', $kode_ruangan)->first();
        // jumlah alokasi depo dikurangi permintaan ruangan
        $myAlokasi = $sum - $permintaanRuangan->jumlah;
        // hitung alokasi
        if ($totalStok >= $myAlokasi) {
            $alokasi =  $totalStok - $myAlokasi;
        } else {
            $alokasi = 0;
        }
        $barang = (object) [];
        $barang->alokasi = $alokasi;
        $barang->stokDepo = $totalStok;
        $barang->stokRuangan = $totalStokRuangan;
        // $barang->kode_rs = $kode_rs;
        // $barang->kode_ruangan = $kode_ruangan;
        // $barang->kode_depo = $kode_depo;
        return $barang;
    }

    public function updatePermintaan(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'details' => 'required',
        ]);
        $details = $request->details;
        $permintaan = Permintaanruangan::updateOrCreate(['id' => $request->id], $request->only('status', 'tanggal_verif'));

        foreach ($details as $value) {
            $id = $value['id'];
            $permintaan->details()->updateOrCreate(['id' => $id], $value);
        }
        if (!$permintaan->wasChanged()) {
            return new JsonResponse(['message' => 'data gagal di update'], 501);
        }
        return new JsonResponse(['data' => $permintaan, 'message' => 'data berhasil di simpan'], 200);
    }
}
