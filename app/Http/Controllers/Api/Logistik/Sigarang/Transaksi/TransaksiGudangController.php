<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\RecentStokUpdate;
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
        // $second->asal = null;
        $second->tujuan = 'Gd-00000000';
        $second->nama = 'PENERIMAAN GUDANG';
        $detail = $second->details;

        unset($second['reff']);
        try {
            DB::beginTransaction();
            $gudang = TransaksiGudang::updateOrCreate(['reff' => $first],  $second->only('nama', 'nomor', 'no_penerimaan', 'tanggal', 'asal', 'tujuan', 'kode_penanggungjawab', 'kode_penerima', 'total', 'status'));
            // $header = (object) array('no_penerimaan' => $second->no_penerimaan);
            // $header->kode_ruang = $second->tujuan;
            foreach ($detail as &$value) {
                $satu = $value->kode_rs;
                unset($value['kode_rs']);
                $gudang->details()->updateOrCreate(['kode_rs' => $satu],  $value->only('kode_108', 'qty', 'harga', 'kode_satuan', 'sub_total'));
                // $header->kode_rs = $satu;
                // $header->harga = $value->harga;
                // $header->sisa_stok = $value->sub_total;
                // $this->terimaStokGudang($header);
                RecentStokUpdate::create([
                    'no_penerimaan' => $second->no_penerimaan,
                    'kode_ruang' => $second->tujuan,
                    'kode_rs' => $satu,
                    'harga' => $value->harga,
                    'sisa_stok' => $value->qty,

                ]);
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

    public function terimaStokGudang($header)
    {
        $data = RecentStokUpdate::create($header->all());
        return 'ok';
    }
}
