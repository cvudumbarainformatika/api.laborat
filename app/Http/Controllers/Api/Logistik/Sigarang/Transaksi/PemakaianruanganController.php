<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\PenggunaRuang;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\Pemakaianruangan\DetailsPemakaianruangan;
use App\Models\Sigarang\Transaksi\Pemakaianruangan\Pemakaianruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemakaianruanganController extends Controller
{
    //ambil data barang dan penanggungjawab ruangan
    public function allData()
    {
        $pengguna = PenggunaRuang::with('ruang', 'pengguna', 'penanggungjawab')
            ->get();
        $temp = collect($pengguna);
        $apem = $temp->map(function ($item, $key) {
            if ($item->kode_penanggungjawab === null || $item->kode_penanggungjawab === '') {
                $item->kode_penanggungjawab = $item->kode_pengguna;
            }
            return $item;
        });

        $apem->all();
        $group = $apem->groupBy('kode_penanggungjawab');

        $rawStok = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->groupBy('kode_rs', 'kode_ruang')
            ->where('kode_ruang', 'LIKE', 'R-' . '%')
            ->get();

        $data['penanggungjawab'] = $group;
        $data['stok'] = $rawStok;

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $request->validate([
            'reff' => 'required',
            'kode_penanggungjawab' => 'required',
            'kode_pengguna' => 'required',
            'tanggal' => 'required',
        ]);
        // $masuk = $request->all();
        $request['kode_ruang'] = $pegawai->kode_ruang;
        $pakai = Pemakaianruangan::updateOrCreate(['id' => $request->id], $request->all());

        if ($request->details) {
            foreach ($request->details as $key) {
                $pakai->details()->updateOrCreate(
                    [
                        'id' => $key['id']
                    ],
                    $key
                );
                $recentStok = RecentStokUpdate::where('kode_ruang', $request->kode_ruang)
                    ->where('kode_rs', $key['kode_rs'])
                    ->first();
                $sisa = $recentStok->sisa_stok - $key['jumlah'];
                $recentStok->update([
                    'sisa_stok' => $sisa
                ]);
            }
        }
        if ($pakai->wasRecentlyCreated) {
            $status = 201;
            $pesan = ['message' => 'Pemakaian Ruangan telah disimpan'];
        } else if ($pakai->wasChanged()) {
            $status = 200;
            $pesan = ['message' => 'Pemakaian Ruangan telah diupdate'];
        } else {
            $status = 500;
            $pesan = ['message' => 'Pemakaian Ruangan gagal dibuat'];
        }
        return new JsonResponse($pesan, $status);
        // } catch (\Exception $e) {
        //         DB::rollBack();
        //         return new JsonResponse([
        //             'message' => 'ada kesalahan',
        //             'error' => $e
        //         ], 500);
        //     }
    }
    public function simpanRusak(Request $request)
    {
        $request->validate([
            'reff' => 'required',
            'kode_pengguna' => 'required',
        ]);
        $tanggal = $request->tanggal !== null ? $request->tanggal : date('Y-m-d H:m:s');
        // return new JsonResponse($request->all(), 500);
        $pakai = Pemakaianruangan::create($request->all());
        $pakai->update(['tanggal' => $request->tanggal]);

        if ($request->details) {
            foreach ($request->details as $key) {
                $pakai->details()->create($key);
            }
        }
        if ($pakai->wasRecentlyCreated) {
            $status = 201;
            $pesan = ['message' => 'Pemakaian Ruangan telah disimpan'];
        } else if ($pakai->wasChanged()) {
            $status = 200;
            $pesan = ['message' => 'Pemakaian Ruangan telah diupdate'];
        } else {
            $status = 500;
            $pesan = ['message' => 'Pemakaian Ruangan gagal dibuat'];
        }
        return new JsonResponse($pesan, $status);
        // } catch (\Exception $e) {
        //         DB::rollBack();
        //         return new JsonResponse([
        //             'message' => 'ada kesalahan',
        //             'error' => $e
        //         ], 500);
        //     }
    }
}
