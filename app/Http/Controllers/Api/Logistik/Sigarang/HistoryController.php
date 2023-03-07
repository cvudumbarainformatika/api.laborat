<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
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
        // pemesanan
        $user = auth()->user();
        // $pegawai = Pegawai::find($user->pegawai_id);
        if ($nama === 'Pemesanan') {
            // jika status lebih dari tiga ambil penerimaannya.. dan nomor penerimaannya pasti beda lho..
            $data = $pemesanan->filter(request(['q']))
                ->where('created_by', $user->pegawai_id)
                ->orWhere('created_by', null)
                ->with('perusahaan',  'details.barangrs.barang108', 'details.satuan')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            /*
            * Penerimaan
            */
        } else if ($nama === 'Penerimaan') {

            $data = $penerimaan->filter(request(['q']))
                ->with('perusahaan',  'details.barangrs.barang108', 'details.satuan')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            /*
            * transaksi gudang
            */
        } else if ($nama === 'Gudang') {

            $data = $gudang->filter(request(['q']))
                ->with('asal', 'tujuan', 'details.barangrs.barang108', 'details.satuan')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            // permintaan ruangan
        } else if ($nama === 'Permintaan Ruangan') {
            $user = auth()->user();
            $pegawai = Pegawai::find($user->pegawai_id);
            if ($pegawai->role_id === 5) {
                $filterRuangan = $permintaan->where('kode_ruang', $pegawai->kode_ruang);
            } else {
                $filterRuangan = $permintaan;
            }
            $data = $filterRuangan->filter(request(['q']))
                ->with('details.barangrs.barang108', 'details.satuan', 'pj', 'pengguna', 'details.gudang', 'details.ruang', 'ruangan')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            /*
            * Distribusi depo
            */
        } else if ($nama === 'Distribusi Depo') {

            $data = $distribusidepo->filter(request(['q']))
                ->with('details.barangrs.barang108', 'details.satuan', 'depo')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            /*
            * pemakaian ruangan
            */
        } else if ($nama === 'Pemakaian Ruangan') {

            $data = $pemakaianruangan->filter(request(['q']))
                ->with(
                    'details.barangrs.barang108',
                    'details.satuan',
                    'ruangpengguna.pengguna',
                    'ruangpengguna.pj',
                    'pengguna',
                    'pj'
                )
                ->latest('tanggal')
                ->paginate(request('per_page'));
            /*
            * penerimaan ruangan
            */
        } else if ($nama === 'Penerimaan Ruangan') {

            $data = $penerimaanruangan->filter(request(['q']))
                ->with('details.barangrs.barang108', 'details.satuan', 'pj', 'pengguna')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            /*
            * retur
            */
        } else if ($nama === 'Retur') {

            $data = $retur->filter(request(['q']))
                ->with('details.barangrs.barang108', 'details.satuan')
                ->latest('tanggal')
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

    public function destroy(Request $request)
    {
        if ($request->nama === 'PEMESANAN') {
            $data = $this->hapusPemesanan($request);
        } else if ($request->nama === 'PERMINTAAN RUANGAN') {
            $data = $this->hapusPermintaan($request);
        } else if ($request->nama === 'PENERIMAAN') {
            $data = $this->hapusPenerimaan($request);
        } else if ($request->nama === 'PEMAKAIAN RUANGAN') {
            $data = $this->hapusPemakaianRuangan($request);
        } else if ($request->nama === 'DISTRIBUSI DEPO') {
            $data = $this->hapusDistribusiDepo($request);
        } else {
            $data = [
                'message' => 'Transaksi ini tidak bisa di hapus',
                'status' => 410
            ];
        }

        return new JsonResponse($data, $data['status']);
    }

    public function hapusPemesanan($request)
    {
        $return = Pemesanan::find($request->id);
        $return->delete();
        if (!$return) {

            return ['message' => 'Data gagal di hapus', $return, 'status' => 410];
        }
        return ['message' => 'Data sudah di hapus', $return, 'status' => 200];
    }

    public function hapusPermintaan($request)
    {
        $return = Permintaanruangan::find($request->id);
        $return->delete();
        if (!$return) {

            return ['message' => 'Data gagal di hapus', $return, 'status' => 410];
        }
        return ['message' => 'Data sudah di hapus', $return, 'status' => 200];
    }

    public function hapusPenerimaan($request)
    {
        $return = Penerimaan::find($request->id);
        $return->delete();
        if (!$return) {

            return ['message' => 'Data gagal di hapus', $return, 'status' => 410];
        }
        return ['message' => 'Data sudah di hapus', $return, 'status' => 200];
    }

    public function hapusPemakaianRuangan($request)
    {
        $return = Pemakaianruangan::find($request->id);
        $return->delete();
        if (!$return) {

            return ['message' => 'Data gagal di hapus', $return, 'status' => 410];
        }
        return ['message' => 'Data sudah di hapus', $return, 'status' => 200];
    }

    public function hapusDistribusiDepo($request)
    {
        $return = DistribusiDepo::find($request->id);
        $return->delete();
        if (!$return) {

            return ['message' => 'Data gagal di hapus', $return, 'status' => 410];
        }
        return ['message' => 'Data sudah di hapus', $return, 'status' => 200];
    }
}
