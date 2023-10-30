<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\PenggunaRuang;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Ruang;
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
        $pegawai = Pegawai::find($user->pegawai_id);
        $idpegawai = Pegawai::select('id')->where('kode_ruang', $pegawai->kode_ruang)->get();

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
            if ($pegawai->role_id !== 1) {
                if ($pegawai->kode_ruang === 'Gd-02010102') {
                    $pemesanan->whereIn('created_by', $idpegawai)->orWhere('created_by', 0);
                } else {
                    $pemesanan->whereIn('created_by', [$user->pegawai_id, 0]);
                }
            }

            $data = $pemesanan->with('perusahaan', 'dibuat',  'details.barangrs.barang108', 'details.satuan')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            /*
            * Penerimaan
            */
        } else if ($nama === 'Penerimaan') {


            $penerimaan->when(request('q'), function ($search) {
                $search->where('nomor', 'LIKE', '%' . request('q') . '%');
            })
                ->when(request('from'), function ($w) {
                    $w->whereBetween('tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59']);
                })
                ->when(request('kontrak'), function ($w) {
                    $w->where('kontrak', 'LIKE', '%' . request('kontrak') . '%');
                });

            $data = $penerimaan->with('perusahaan',  'details.barangrs.barang108', 'details.satuan')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            /*
            * transaksi gudang
            */
        } else if ($nama === 'Gudang') {

            $penerimaan->when(request('q'), function ($search) {
                $search->where('nomor', 'LIKE', '%' . request('q') . '%');
            })
                ->when(request('from'), function ($w) {
                    $w->whereBetween('tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59']);
                });
            // if (request('q')) {
            //     $penerimaan->where('nomor', 'LIKE', '%' . request('q') . '%');
            // }
            // if (request('from')) {
            //     $penerimaan->whereBetween('tanggal', [request('from'), request('to')]);
            // }
            $data = $gudang->with('asal', 'tujuan', 'details.barangrs.barang108', 'details.satuan')
                ->latest('tanggal')
                ->paginate(request('per_page'));
            // permintaan ruangan
        } else if ($nama === 'Permintaan Ruangan') {
            // $user = auth()->user();
            // $pegawai = Pegawai::find($user->pegawai_id);

            if ($pegawai) {
                if ($pegawai->role_id === 5) {
                    $pengguna = PenggunaRuang::where('kode_ruang', $pegawai->kode_ruang)->first();
                    $ruang = PenggunaRuang::where('kode_pengguna', $pengguna->kode_pengguna)->get();
                    $raw = collect($ruang);
                    $only = $raw->map(function ($y) {
                        return $y->kode_ruang;
                    });

                    $filterRuangan = $permintaan->whereIn('kode_ruang', $only);
                } else if ($pegawai->role_id === 4) {
                    $filterRuangan = $permintaan->where('status', '>=', 4);
                } else {
                    $filterRuangan = $permintaan;
                }
            } else {
                $filterRuangan = $permintaan;
            }
            $filterRuangan->when(request('q'), function ($search) {
                $search->where('no_permintaan', 'LIKE', '%' . request('q') . '%');
            })
                ->when(request('from'), function ($w) {
                    $w->whereBetween('tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59']);
                })
                ->when(request('ruang'), function ($w) {
                    $ru = Ruang::select('kode')->where('uraian', 'LIKE', '%' . request('ruang') . '%')->get();
                    $w->whereIn('kode_ruang', $ru);
                });
            $data = $filterRuangan->filter(request(['q']))
                ->with([
                    'details.barangrs.barang108',
                    'details.satuan',
                    'pj',
                    'pengguna',
                    'details.gudang',
                    'details.ruang',
                    'details.sisastok',
                    'ruangan'
                ])
                // ->latest('tanggal')
                ->orderBy(request('order_by'), request('sort'))
                ->paginate(request('per_page'));
            /*
            * Distribusi depo
            */
        } else if ($nama === 'Distribusi Depo') {


            if (request('from')) {
                $distribusidepo->whereBetween('tanggal', [request('from'), request('to')]);
            }
            if ($pegawai->role_id === 4) {
                $distribusidepo->where('kode_depo', $pegawai->kode_ruang);
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
            'ids' => $idpegawai,
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
        $terima = Penerimaan::with('details')->find($request->id);
        if ($terima->details) {
            $kode = collect($terima->details)->map(function ($x) {
                return $x->kode_rs;
            });
            $pesan = Pemesanan::select(
                'pemesanans.nomor',
                'pemesanans.id',
                'pemesanans.status',
                'detail_pemesanans.kode_rs',
                'detail_pemesanans.pemesanan_id',
            )->join('detail_pemesanans', function ($anu) use ($kode) {
                $anu->on('pemesanans.id', '=', 'detail_pemesanans.pemesanan_id')
                    ->whereIn('detail_pemesanans.kode_rs', $kode);
            })->where('pemesanans.nomor', $terima->nomor)
                ->first();
        }
        // $balik['terima'] = $terima;
        // $balik['kode'] = $kode;
        // $balik['pesan'] = $pesan;
        // return [
        //     $balik,
        //     'status' => 200,
        // ];

        $return = Penerimaan::find($request->id);
        $return->delete();
        if (!$return) {
            return ['message' => 'Data gagal di hapus', $return, 'status' => 410];
        }
        RecentStokUpdate::where('no_penerimaan', $return->no_penerimaan)->delete();
        if ($pesan) {
            if ($pesan->status === 4) {
                $pesan->update(['status' => 3]);
            }
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
