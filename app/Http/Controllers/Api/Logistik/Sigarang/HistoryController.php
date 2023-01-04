<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DistribusiDepo;
use App\Models\Sigarang\Transaksi\Gudang\TransaksiGudang;
use App\Models\Sigarang\Transaksi\Pemakaianruangan\Pemakaianruangan;
use App\Models\Sigarang\Transaksi\Pemesanan\Pemesanan;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use App\Models\Sigarang\Transaksi\Penerimaanruangan\Penerimaanruangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use App\Models\Sigarang\Transaksi\Retur\Retur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    //
    public function index()
    {
        $distribusidepo = DistribusiDepo::query();
        $gudang = TransaksiGudang::query();
        $pemakaianruangan = Pemakaianruangan::query();
        $pemesanan = Pemesanan::query();
        $penerimaan = Penerimaan::query();
        $penerimaanruangan = Penerimaanruangan::query();
        $permintaan = Permintaanruangan::query();
        $retur = Retur::query();
        $nama = request('nama');
        if ($nama === 'Pemesanan') {
            // jika status lebih dari tiga ambil penerimaannya.. dan nomor penerimaannya pasti beda lho..
            $data = $pemesanan->filter(request(['q']))
                ->with('perusahaan',  'details.barangrs', 'details.barang108', 'details.satuan')
                ->latest('id')
                ->paginate(request('per_page'));
        } else if ($nama === 'Penerimaan') {
            $data = $penerimaan->filter(request(['q']))
                ->with('perusahaan',  'details.barangrs', 'details.barang108', 'details.satuan')
                ->latest('id')
                ->paginate(request('per_page'));
        } else if ($nama === 'Gudang') {
            $data = $gudang->filter(request(['q']))
                ->with('asal', 'tujuan', 'details.barangrs', 'details.barang108', 'details.satuan')
                ->latest('id')
                ->paginate(request('per_page'));
        } else if ($nama === 'Permintaan') {
            $data = $permintaan->filter(request(['q']))
                ->with('details.barangrs', 'details.satuan', 'pj', 'pengguna')
                ->latest('id')
                ->paginate(request('per_page'));
        } else if ($nama === 'Distribusi Depo') {
            $data = $distribusidepo->filter(request(['q']))
                ->with('details.barangrs', 'details.satuan', 'details.barang108', 'depo')
                ->latest('id')
                ->paginate(request('per_page'));
        } else if ($nama === 'Pemakaian Ruangan') {
            $data = $pemakaianruangan->filter(request(['q']))
                ->with('details.barangrs', 'details.satuan')
                ->latest('id')
                ->paginate(request('per_page'));
        } else if ($nama === 'Penerimaan Ruangan') {
            $data = $penerimaanruangan->filter(request(['q']))
                ->with('details.barangrs', 'details.satuan')
                ->latest('id')
                ->paginate(request('per_page'));
        } else if ($nama === 'Retur') {
            $data = $retur->filter(request(['q']))
                ->with('details.barangrs', 'details.satuan')
                ->latest('id')
                ->paginate(request('per_page'));
        }
        // $data = request()->all();
        $apem = $data->all();
        return new JsonResponse([
            'data' => $apem,
            'meta' => $data
        ]);
    }
    public function allTransaction()
    {
        $pemesanan = Pemesanan::query()->filter(request(['q']))->with('details')->paginate(request('per_page'));
        $penerimaan = Penerimaan::query()->filter(request(['q']))->with('details')->paginate(request('per_page'));
        $gudang = TransaksiGudang::query()->filter(request(['q']))->with('details')->paginate(request('per_page'));
        $permintaan = Permintaanruangan::query()->filter(request(['q']))->with('details')->paginate(request('per_page'));
        // $data = array_merge($pemesanan, $penerimaan, $gudang);
        return new JsonResponse([
            'pemesanan' => $pemesanan,
            'penerimaan' => $penerimaan,
            'gudang' => $gudang,
            'permintaan' => $permintaan,
        ]);
    }
}
