<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\PenjualanBebas;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanBebasController extends Controller
{
    public function getPihakTiga(){
        $data=Mpihakketiga::select('nama', 'kode')
        ->where('nama', 'LIKE', '%' . request('q') . '%')
        ->limit(30)
        ->get();
        return new JsonResponse($data);
    }
    public function pencarianObat()
    {
        $sistembayar = ['SEMUA', 'UMUM'];
        $limitHargaTertinggi = 5;
        
        $listobat = Mobatnew::query()
            ->select(
                'new_masterobat.kd_obat',
                'new_masterobat.nama_obat as namaobat',
                'new_masterobat.kandungan as kandungan',
                'new_masterobat.bentuk_sediaan as bentuk_sediaan',
                'new_masterobat.satuan_k as satuankecil',
                'new_masterobat.status_fornas as fornas',
                'new_masterobat.status_forkid as forkit',
                'new_masterobat.status_generik as generik',
                'new_masterobat.status_kronis as kronis',
                'new_masterobat.status_prb as prb',
                'new_masterobat.kode108',
                'new_masterobat.uraian108',
                'new_masterobat.kode50',
                'new_masterobat.uraian50',
                'new_masterobat.kekuatan_dosis as kekuatandosis',
                'new_masterobat.volumesediaan as volumesediaan',
                'new_masterobat.kelompok_psikotropika as psikotropika',
                'stokreal.kdobat as kdobat',
                'stokreal.jumlah as jumlah',
                DB::raw('SUM(
                    CASE When stokreal.kdruang="' . request('kdruang') . '" AND stokreal.kdobat = new_masterobat.kd_obat Then stokreal.jumlah Else 0 End )
                     as total'),
            )
            ->leftjoin('stokreal', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->where('new_masterobat.status_konsinyasi', '')
            ->whereIn('new_masterobat.sistembayar', $sistembayar)
            
            ->where(function ($query) {
                $query->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('new_masterobat.kandungan', 'LIKE', '%' . request('q') . '%');
            })
            ->with('permintaandeporinci', function ($q) {
                $q->select(
                    'permintaan_r.no_permintaan',
                    'permintaan_r.kdobat',
                    'permintaan_h.tujuan as kdruang',
                    DB::raw('sum(permintaan_r.jumlah_minta) as jumlah_minta')
                )
                    ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                    ->leftJoin('mutasi_gudangdepo', function ($anu) {
                        $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                            ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                    })
                    ->where('permintaan_h.tujuan', request('kdruang'))
                    ->whereIn('permintaan_h.flag', ['', '1', '2'])
                    ->groupBy('permintaan_r.kdobat');
            })

            ->with('transracikan', function ($q) {
                $q->select(
                    'resep_permintaan_keluar_racikan.kdobat as kdobat',
                    'resep_keluar_h.depo as kdruang',
                    DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                )
                    ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                    ->where('resep_keluar_h.depo', request('kdruang'))
                    ->whereIn('resep_keluar_h.flag', ['', '1', '2']);
            })

            ->with('transnonracikan', function ($q) {
                $q->select(
                    'resep_permintaan_keluar.kdobat as kdobat',
                    'resep_keluar_h.depo as kdruang',
                    DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                )
                    ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                    ->where('resep_keluar_h.depo', request('kdruang'))
                    ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                    ->groupBy('resep_permintaan_keluar.kdobat');
            })
            // ->addSelect([
            //     'harga_tertinggi_ids' => DaftarHarga::query()
            //             ->selectRaw("SUBSTRING_INDEX(GROUP_CONCAT(daftar_hargas.id order by tgl_mulai_berlaku desc, ','), ',', {$limitHargaTertinggi})")
            //             ->whereColumn('daftar_hargas.kd_obat','=', 'stokreal.kdobat')
            //             ->limit($limitHargaTertinggi)
            //     ])
              ->addSelect([
                'harga_beli' => DaftarHarga::query()
                        ->selectRaw("MAX(harga)" )
                        ->whereColumn('daftar_hargas.kd_obat','=', 'stokreal.kdobat')
                        ->orderBy('tgl_mulai_berlaku','desc')
                        ->limit($limitHargaTertinggi)
                ])


            ->groupBy('new_masterobat.kd_obat')
            ->orderBy('total', 'DESC')
            ->limit(20)
            ->get();

        $wew = collect($listobat)->map(function ($x, $y) {
            $total = $x->total ?? 0;
            $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
            $permintaanobatrinci = $x['permintaandeporinci'][0]->jumlah_minta ?? 0; // mutasi antar depo
            $x->alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci;
            return $x;
        });

        return new JsonResponse(
            [
                'dataobat' => $wew
            ]
        );
    }

    public static function setNomor($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 6 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n . "/" . date("d") . "/" . date("m") . "/" . date("Y") . "/" . $kode;
    }
    public function  simpan(Request $request){
        // ini  nanti ngisi di resep_keluar_h dan resep_keluar_r
        // bikin baru tabel registrasi penjualan bebas
        // tiap obat harus cek alokasi, jadi bikin seperti template resep untuk keluarnya.
        // noresp
        DB::connection('farmasi')->select('call registrasipenjumum(@nomor)' );
        $x = DB::connection('farmasi')->table('conter')->select('regbebas')->first();
        $nom=$x->regbebas;
        $nomor=self::setNomor($nom,'R-PJB');
        return new JsonResponse($nomor);
    }
}
