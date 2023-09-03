<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokrealController;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananRinci;
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
                //'penerimaan'
                'penerimaan' => function ($penerimaan) {
                    //$penerimaan->select('nopemesanan', 'penerimaan.penerimaanrinci:nopemesanan,kdobat,jml_terima');
                    $penerimaan->select('nopenerimaan', 'nopemesanan')->with('penerimaanrinci:kdobat,nopenerimaan,jml_terima');
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

            $jumlahpesan = PemesananRinci::select('jumlahdpesan')
                ->with(['pemesananheder'])
                ->where('nopemesanan', $request->nopemesanan)
                ->where('kdobat', $request->kdobat)->sum('jumlahdpesan');

            if ($jumlahpesan === $request->jml_all_penerimaan) {
                PemesananRinci::where('nopemesanan', $request->nopemesanan)->where('kdobat', $request->kdobat)
                    ->update(['flag' => '1']);
            }

            return new JsonResponse([
                'message' => 'ok',
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
                    'subtotal' => $request->subtotal,
                ]
            );
            if (!$simpanrinci) {
                PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)->first()->delete();
                return new JsonResponse(['message' => 'Data Heder Gagal Disimpan...!!!'], 500);
            }
            $stokrealsimpan = StokrealController::stokreal($request->nopenerimaan, $request);
            if ($stokrealsimpan !== 200) {
                PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)->first()->delete();
                PenerimaanRinci::where('nopenerimaan', $request->nopenerimaan)->first()->delete();
                return new JsonResponse(['message' => 'Gagal Tersimpan Ke Stok...!!!'], 500);
            }
            $jumlahpesan = PemesananRinci::select('jumlahdpesan')
                ->with(['pemesananheder'])
                ->where('nopemesanan', $request->nopemesanan)
                ->where('kdobat', $request->kdobat)->sum('jumlahdpesan');

            if ($jumlahpesan === $request->jml_all_penerimaan) {
                PemesananRinci::where('nopemesanan', $request->nopemesanan)->where('kdobat', $request->kdobat)
                    ->update(['flag' => '1']);
            }
            return new JsonResponse(['rinci' => $simpanrinci]);
        }
    }

    public function listepenerimaan()
    {
        $idpegawai = auth()->user()->pegawai_id;
        $kodegudang = Pegawai::find($idpegawai);

        if ($kodegudang->kode_ruang === '') {
            $listpenerimaan = PenerimaanHeder::select(
                'penerimaan_h.nopenerimaan as nopenerimaan',
                'penerimaan_h.nopemesanan as nopemesanan',
                'penerimaan_h.tglpenerimaan as tglpenerimaan',
                'penerimaan_h.kdpbf as kodepbf',
                'siasik.pihak_ketiga.nama as pbf',
                'penerimaan_h.pengirim as pengirim',
                'penerimaan_h.jenissurat as jenissurat',
                'penerimaan_h.nomorsurat as nomorsurat',
                'penerimaan_h.tglsurat as tglsurat',
                'penerimaan_h.batasbayar as batasbayar',
                'penerimaan_h.total_faktur_pbf as total',
            )
                ->leftjoin('siasik.pihak_ketiga', 'siasik.pihak_ketiga.kode', 'penerimaan_h.kdpbf')
                ->where('penerimaan_h.nopenerimaan', 'Like', '%' . request('cari') . '%')
                ->orWhere('penerimaan_h.nopemesanan', 'Like', '%' . request('cari') . '%')
                ->orWhere('penerimaan_h.tglpenerimaan', 'Like', '%' . request('cari') . '%')
                ->orWhere('siasik.pihak_ketiga.nama', 'Like', '%' . request('cari') . '%')
                ->orWhere('penerimaan_h.pengirim', 'Like', '%' . request('cari') . '%')
                ->orWhere('penerimaan_h.jenissurat', 'Like', '%' . request('cari') . '%')
                ->orWhere('penerimaan_h.nomorsurat', 'Like', '%' . request('cari') . '%')
                ->paginate(request('per_page'));
            return new JsonResponse($listpenerimaan);
        } else {
            $listpenerimaan = PenerimaanHeder::select(
                'penerimaan_h.nopenerimaan as nopenerimaan',
                'penerimaan_h.nopemesanan',
                'penerimaan_h.tglpenerimaan',
                'penerimaan_h.kdpbf',
                'siasik.pihak_ketiga.nama',
                'penerimaan_h.pengirim',
                'penerimaan_h.jenissurat',
                'penerimaan_h.nomorsurat',
                'penerimaan_h.tglsurat',
                'penerimaan_h.batasbayar',
                'penerimaan_h.total_faktur_pbf',
            )
                ->leftjoin('siasik.pihak_ketiga', 'siasik.pihak_ketiga.kode', 'penerimaan_h.kdpbf')
                ->where('gudang', $kodegudang)
                ->where('penerimaan_h.nopenerimaan', 'Like', '%' . request('cari') . '%')
                ->orWhere('penerimaan_h.nopemesanan', 'Like', '%' . request('cari') . '%')
                ->orWhere('penerimaan_h.tglpenerimaan', 'Like', '%' . request('cari') . '%')
                ->orWhere('siasik.pihak_ketiga.nama', 'Like', '%' . request('cari') . '%')
                ->orWhere('penerimaan_h.pengirim', 'Like', '%' . request('cari') . '%')
                ->orWhere('penerimaan_h.jenissurat', 'Like', '%' . request('cari') . '%')
                ->orWhere('penerimaan_h.nomorsurat', 'Like', '%' . request('cari') . '%')
                ->paginate(request('per_page'));
            return new JsonResponse($listpenerimaan);
        }
    }
}
