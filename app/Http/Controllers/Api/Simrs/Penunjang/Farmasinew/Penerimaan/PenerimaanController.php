<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokrealController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenerimaanController extends Controller
{
    public function listpemesananfix()
    {
        $listpemesanan = PemesananHeder::select('nopemesanan', 'tgl_pemesanan', 'kdpbf')
            ->with([
                'pihakketiga:kode,nama,alamat,telepon,npwp,cp',
                'rinci:nopemesanan,kdobat,jumlahdpesan',
                'rinci.masterobat:kd_obat,nama_obat,merk,kandungan,bentuk_sediaan,satuan_b,satuan_k,kekuatan_dosis,volumesediaan,kelas_terapi',
                'penerimaan' => function ($penerimaan) {
                    $penerimaan->select('nopemesanan', 'jml_terima')->where('nopemesanan', request('nopemesanan'))
                        ->where('kdobat', request('nopemesanan'));
                },
            ])
            ->where('flag', '1')
            ->get();
        return new JsonResponse($listpemesanan);
    }

    public function simpanpenerimaan(Request $request)
    {
        if ($request->nopenerimaan === '' || $request->nopenerimaan === null) {
            if ($request->gudang === 'Gd-05010100') {
                $procedure = 'penerimaan_obat_ko(@nomor)';
                $colom = 'penerimaanko';
                $lebel = 'G-KO';
            } else {
                $procedure = 'penerimaan_obat_fs(@nomor)';
                $colom = 'penerimaanfs';
                $lebel = 'G-FS';
            }
            DB::connection('farmasi')->select('call ' . $procedure);
            $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
            $wew = $x[0]->$colom;
            $nopenerimaan = FormatingHelper::penerimaanobat($wew, $lebel);
            $simpanheder = PenerimaanHeder::firstOrCreate(
                ['nopenerimaan' => $nopenerimaan],
                [
                    'nopemesanan' => $request->nopemesanan,
                    'tglpenerimaan' => $request->tglpenerimaan,
                    'kdpbf' => $request->kdpbf,
                    'pengirim' => $request->pengirim,
                    'jenissurat' => $request->jenissurat,
                    'nomorsurat' => $request->nomorsurat,
                    'tglsurat' => $request->tglsurat,
                    'batasbayar' => $request->batasbayar,
                    'user' => auth()->user()->pegawai_id,
                    'gudang' => $request->kdruang,
                    'total_faktur_pbf' => $request->total_faktur_pbf,
                ]
            );
            if (!$simpanheder) {
                return new JsonResponse(['message' => 'not ok'], 500);
            }
            $simpanrinci = PenerimaanRinci::create(
                [
                    'nopenerimaan' => $nopenerimaan,
                    'kdobat' => $request->kdobat,
                    'no_batch' => $request->no_batch,
                    'tgl_exp' => $request->tgl_exp,
                    'satuan_bsr' => $request->satuan_bsr,
                    'satuan_kcl' => $request->satuan_kcl,
                    'isi' => $request->isi,
                    'harga' => $request->harga,
                    'harga_kcl' => $request->harga_kcl,
                    'diskon' => $request->diskon,
                    'diskon_rp' => $request->diskon_rp,
                    'ppn' => $request->ppn,
                    'ppn_rp' => $request->ppn_rp,
                    'harga_netto' => $request->harga_netto,
                    'jml_pesan' => $request->jml_pesan,
                    'jml_terima' => $request->jumlah,
                    'jml_terima_lalu' => $request->jml_terima_lalu,
                    'jml_all_penerimaan' => $request->jml_all_penerimaan,
                    'subtotal' => $request->subtotal,
                ]
            );
            if (!$simpanrinci) {
                PenerimaanHeder::where('nopenerimaan', $nopenerimaan)->first()->delete();
                return new JsonResponse(['message' => 'Data Heder Gagal Disimpan...!!!'], 500);
            }
            $stokrealsimpan = StokrealController::stokreal($nopenerimaan, $request);
            if ($stokrealsimpan !== 200) {
                PenerimaanHeder::where('nopenerimaan', $nopenerimaan)->first()->delete();
                PenerimaanRinci::where('nopenerimaan', $nopenerimaan)->first()->delete();
                return new JsonResponse(['message' => 'Gagal Tersimpan Ke Stok...!!!'], 500);
            }
            return new JsonResponse([
                'heder' => $simpanheder,
                'rinci' => $simpanrinci
            ]);
        } else {
            $simpanrinci = PenerimaanRinci::create(
                [
                    'nopenerimaan' => $request->nopenerimaan,
                    'kdobat' => $request->kdobat,
                    'no_batch' => $request->no_batch,
                    'tgl_exp' => $request->tgl_exp,
                    'satuan_bsr' => $request->satuan_bsr,
                    'satuan_kcl' => $request->satuan_kcl,
                    'isi' => $request->isi,
                    'harga' => $request->harga,
                    'harga_kcl' => $request->harga_kcl,
                    'diskon' => $request->diskon,
                    'diskon_rp' => $request->diskon_rp,
                    'ppn' => $request->ppn,
                    'ppn_rp' => $request->ppn_rp,
                    'harga_netto' => $request->harga_netto,
                    'jml_pesan' => $request->jml_pesan,
                    'jml_terima' => $request->jumlah,
                    'jml_terima_lalu' => $request->jml_terima_lalu,
                    'jml_all_penerimaan' => $request->jml_all_penerimaan,
                    'total_faktur_pbf' => $request->total_faktur_pbf,
                    'subtotal' => $request->subtotal,
                ]
            );
            if (!$simpanrinci) {
                PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)->first()->delete();
                return new JsonResponse(['message' => 'Data Heder Gagal Disimpan...!!!'], 500);
            }
            $stokrealsimpan = StokrealController::stokreal($request->nopenerimaan, $request);
            return ($stokrealsimpan);
            if ($stokrealsimpan !== 200) {
                PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)->first()->delete();
                PenerimaanRinci::where('nopenerimaan', $request->nopenerimaan)->first()->delete();
                return new JsonResponse(['message' => 'Gagal Tersimpan Ke Stok...!!!'], 500);
            }
            return new JsonResponse(['rinci' => $simpanrinci]);
        }
    }
}
