<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengembalianPinjamanController extends Controller
{
    //
    public function getPbfPeminjam()
    {
        // ini nanti di moodif cari yang pinjaman nya belum di kembalikan
        $kode = PenerimaanHeder::select('kdpbf')
            ->where('jenis_penerimaan', 'Pinjaman')
            ->where('kunci', '1')
            ->distinct()->pluck('kdpbf');

        $pihaktiga = Mpihakketiga::select('nama', 'kode')->whereIn('kode', $kode)->get();
        return new JsonResponse([
            "data" => $pihaktiga,
            'req' => request()->all(),
        ]);
    }
    public function getNopenerimaan()
    {
        // ini nanti di moodif cari yang pinjaman nya belum di kembalikan

        $data = PenerimaanHeder::select('nopenerimaan')
            ->where('jenis_penerimaan', 'Pinjaman')
            ->where('kdpbf', request('kdpbf'))
            ->with(
                'penerimaanrinci:nopenerimaan,harga_netto_kecil,kdobat,jml_terima_k',
                'penerimaanrinci.masterobat:kd_obat,nama_obat,satuan_k'
            )
            ->where('kunci', '1')
            ->get();
        return new JsonResponse([
            'data' => $data,
            'req' => request()->all(),
        ]);
    }
}
