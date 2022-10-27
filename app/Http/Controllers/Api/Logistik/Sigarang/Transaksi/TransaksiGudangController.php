<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Gudang\TransaksiGudang;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiGudangController extends Controller
{
    //

    public static function fromPenerimaan($data)
    {
        $terima = Penerimaan::where('id', $data)->with('details')->get();
        $first = $terima[0]->reff;
        $second = $terima[0];
        $second['tanggal'] = date('Y-m-d H:i:s');
        $second->asal = 'Gud-0000001';
        $second->tujuan = 'Gud-01000';
        $second->nama = 'PENERIMAAN GUDANG';
        $detail = $second->details;
        unset($second['reff']);
        try {
            DB::beginTransaction();
            $gudang = TransaksiGudang::updateOrCreate(['reff' => $first],  $second->only('nama', 'nomor', 'no_penerimaan', 'tanggal', 'asal', 'tujuan', 'kode_penanggungjawab', 'kode_penerima', 'total', 'status'));

            foreach ($detail as &$value) {
                $satu = $value->kode_rs;
                unset($value['kode_rs']);
                $gudang->details()->updateOrCreate(['kode_rs' => $satu],  $value->only('kode_108', 'qty', 'harga', 'kode_satuan', 'sub_total'));
            }
            DB::commit();
            return new JsonResponse(['message' => 'success', 'data' => $gudang], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }
}
