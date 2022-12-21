<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Pemakaianruangan\DetailsPemakaianruangan;
use App\Models\Sigarang\Transaksi\Pemakaianruangan\Pemakaianruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemakaianruanganController extends Controller
{
    //
    public function store(Request $request)
    {
        $request->validate([
            'reff' => 'required',
            'kode_penanggungjawab' => 'required',
            'kode_pengguna' => 'required',
            'tanggal' => 'required',
        ]);
        $pakai = Pemakaianruangan::updateOrCreate(['id' => $request->id], $request->all());

        if ($request->details) {
            foreach ($request->details as $key) {
                $pakai->details()->updateOrCreate(
                    [
                        'id' => $key['id']
                        // 'id' => $key->id
                    ],
                    $key
                );
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
        // return new JsonResponse($request->all(), 500);
        $pakai = Pemakaianruangan::create($request->all());
        $pakai->update(['tanggal' => date('Y-m-d H:m:s')]);

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
