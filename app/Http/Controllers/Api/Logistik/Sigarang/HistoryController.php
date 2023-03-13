<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\PenggunaRuang;
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
        $user = auth()->user();

        // pemesanan

        if ($nama === 'Pemesanan') {
            if (request('q')) {
                $pemesanan->where('nomor', 'LIKE', '%' . request('q') . '%');
            }
            if (request('kontrak')) {
                $pemesanan->where('kontrak', 'LIKE', '%' . request('kontrak') . '%');
            }
            if (request('from')) {
                $pemesanan->whereBetween('tanggal', [request('from'), request('to')]);
            }

            $data = $pemesanan->whereIn('created_by', [$user->pegawai_id, 0])
                ->with('perusahaan', 'dibuat',  'details.barangrs.barang108', 'details.satuan')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            /*
            * Penerimaan
            */
        } else if ($nama === 'Penerimaan') {

            if (request('q')) {
                $penerimaan->where('no_permintaan', 'LIKE', '%' . request('q') . '%');
            }
            if (request('kontrak')) {
                $penerimaan->where('kontrak', 'LIKE', '%' . request('kontrak') . '%');
            }
            if (request('from')) {
                $penerimaan->whereBetween('tanggal', [request('from'), request('to')]);
            }

            $data = $penerimaan->with('perusahaan',  'details.barangrs.barang108', 'details.satuan')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            /*
            * transaksi gudang
            */
        } else if ($nama === 'Gudang') {

            if (request('q')) {
                $penerimaan->where('nomor', 'LIKE', '%' . request('q') . '%');
            }
            if (request('from')) {
                $penerimaan->whereBetween('tanggal', [request('from'), request('to')]);
            }
            $data = $gudang->with('asal', 'tujuan', 'details.barangrs.barang108', 'details.satuan')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            // permintaan ruangan
        } else if ($nama === 'Permintaan Ruangan') {
            $user = auth()->user();
            $pegawai = Pegawai::find($user->pegawai_id);

            if ($pegawai->role_id === 5) {

                $pengguna = PenggunaRuang::where('kode_ruang', $pegawai->kode_ruang)->first();
                $ruang = PenggunaRuang::where('kode_pengguna', $pengguna->kode_pengguna)->get();
                $raw = collect($ruang);
                $only = $raw->map(function ($y) {
                    return $y->kode_ruang;
                });

                $filterRuangan = $permintaan->whereIn('kode_ruang', $only);
            } else {
                $filterRuangan = $permintaan;
            }

            if (request('q')) {
                $filterRuangan->where('no_permintaan', 'LIKE', '%' . request('q') . '%');
            }
            if (request('from')) {
                $filterRuangan->whereBetween('tanggal', [request('from'), request('to')]);
            }
            $data = $filterRuangan->filter(request(['q']))
                ->with('details.barangrs.barang108', 'details.satuan', 'pj', 'pengguna', 'details.gudang', 'details.ruang', 'ruangan')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            /*
            * Distribusi depo
            */
        } else if ($nama === 'Distribusi Depo') {


            if (request('from')) {
                $distribusidepo->whereBetween('tanggal', [request('from'), request('to')]);
            }
            $data = $distribusidepo->filter(request(['q']))
                ->with('details.barangrs.barang108', 'details.satuan', 'depo')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            /*
            * pemakaian ruangan
            */
        } else if ($nama === 'Pemakaian Ruangan') {
            if (request('from')) {
                $pemakaianruangan->whereBetween('tanggal', [request('from'), request('to')]);
            }
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

            if (request('q')) {
                $penerimaanruangan->where('no_distribusi', 'LIKE', '%' . request('q') . '%');
            }
            if (request('from')) {
                $penerimaanruangan->whereBetween('tanggal', [request('from'), request('to')]);
            }
            $data = $penerimaanruangan->with('details.barangrs.barang108', 'details.satuan', 'pj', 'pengguna')
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
            'meta' => $data,
            'req' => request()->all(),
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
