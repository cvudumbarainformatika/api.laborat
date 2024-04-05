<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokrealController;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenerimaanController extends Controller
{
    public function listpemesananfix()
    {
        $listpemesanan = PemesananHeder::select('nopemesanan', 'tgl_pemesanan', 'kdpbf', 'kd_ruang')
            ->with([
                'gudang',
                'pihakketiga:kode,nama,alamat,telepon,npwp,cp',
                'rinci:nopemesanan,kdobat,jumlahdpesan,harga as harga_kcl',
                'rinci.masterobat:kd_obat,nama_obat,merk,kandungan,bentuk_sediaan,satuan_b,satuan_k,kekuatan_dosis,volumesediaan,kelas_terapi',
                //'penerimaan'
                'penerimaan' => function ($penerimaan) {
                    //$penerimaan->select('nopemesanan', 'penerimaan.penerimaanrinci:nopemesanan,kdobat,jml_terima');
                    $penerimaan->select('nopenerimaan', 'nopemesanan')->with('penerimaanrinci:kdobat,nopenerimaan,jml_terima_b,jml_terima_k');
                },
            ])
            ->when(request('gudang'), function ($q) {
                $q->where('kd_ruang', request('gudang'));
            })
            ->where('flag', '1')
            ->get();
        return new JsonResponse($listpemesanan);
    }

    public function simpanpenerimaan(Request $request)
    {

        if ($request->gudang === 'Gd-05010100') {
            $procedure = 'penerimaan_obat_ko(@nomor)';
            $colom = 'penerimaanko';
            $lebel = 'G-KO';
        } else {
            $procedure = 'penerimaan_obat_fs(@nomor)';
            $colom = 'penerimaanfs';
            $lebel = 'G-FS';
        }
        if ($request->nopenerimaan === '' || $request->nopenerimaan === null) {
            DB::connection('farmasi')->select('call ' . $procedure);
            $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
            $wew = $x[0]->$colom;
            $nopenerimaan = FormatingHelper::penerimaanobat($wew, $lebel);
        } else {
            $nopenerimaan = $request->nopenerimaan;
        }

        $user = FormatingHelper::session_user();
        $simpanheder = PenerimaanHeder::updateorcreate(
            [
                'nopenerimaan' => $nopenerimaan,
                'nopemesanan' => $request->nopemesanan,
                'kdpbf' => $request->kdpbf,
                'gudang' => $request->gudang
            ],
            [
                'tglpenerimaan' => $request->tglpenerimaan,
                'pengirim' => $request->pengirim,
                'jenissurat' => $request->jenissurat,
                'jenis_penerimaan' => 'Penerimaan Tidak Langsung',
                'nomorsurat' => $request->nomorsurat,
                'tglsurat' => $request->tglsurat,
                'batasbayar' => $request->batasbayar,
                'user' => $user['kodesimrs'],
                // 'total_faktur_pbf' => $request->total_faktur_pbf,
            ]
        );
        if (!$simpanheder) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }
        $simpanrinci = PenerimaanRinci::updateorcreate(
            [
                'nopenerimaan' => $nopenerimaan,
                'kdobat' => $request->kdobat,
                'no_batch' => $request->no_batch,
                'jml_terima_b' => $request->jml_terima_b,
                'jml_terima_k' => $request->jml_terima_k,
                'harga' => $request->harga,
                'harga_kcl' => $request->harga_kcl,
            ],
            [
                'tgl_exp' => $request->tgl_exp,
                'satuan' => $request->satuan_bsr,
                'satuan_kcl' => $request->satuan_kcl,
                'isi' => $request->isi,
                'diskon' => $request->diskon,
                'diskon_rp' => $request->diskon_rp,
                'diskon_rp_kecil' => $request->diskon_rp_kecil,
                'ppn' => $request->ppn,
                'ppn_rp' => $request->ppn_rp,
                'ppn_rp_kecil' => $request->ppn_rp_kecil,
                'harga_netto' => $request->harga_netto,
                'harga_netto_kecil' => $request->harga_netto_kecil,
                'jml_pesan' => $request->jml_pesan,
                'jml_terima_lalu' => $request->jml_terima_lalu,
                'jml_all_penerimaan' => $request->jml_all_penerimaan,
                'subtotal' => $request->subtotal,
                'user' => $user['kodesimrs']
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

        $jumlahterima = PenerimaanRinci::select('penerimaan_r.jml_terima_k')
            ->join('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
            ->where('penerimaan_h.nopemesanan', $request->nopemesanan)
            ->where('penerimaan_r.kdobat', $request->kdobat)->sum('penerimaan_r.jml_terima_k');

        if ((int) $jumlahpesan === (int)$jumlahterima) {
            PemesananRinci::where('nopemesanan', $request->nopemesanan)->where('kdobat', $request->kdobat)
                ->update(['flag' => '1']);
        }

        $rinciTrm = PenerimaanRinci::where('nopenerimaan', $nopenerimaan)->where('kdobat', $request->kdobat)->latest('id')->first();
        if ($rinciTrm) {
            $rinciTrm->jml_all_penerimaan = $jumlahterima;
            $rinciTrm->save();
        }
        $pesan = PemesananRinci::where('nopemesanan', $request->nopemesanan)->where('flag', '')->get();
        $pesananDikunci = false;
        if (count($pesan) === 0) {
            $kuncipermintaan = PemesananHeder::where('nopemesanan', $request->nopemesanan)->first();
            $kuncipermintaan->flag = '2';
            $kuncipermintaan->save();
            $pesananDikunci = true;
        }

        return new JsonResponse([
            'message' => 'ok',
            'nopenerimaan' => $nopenerimaan,
            'heder' => $simpanheder,
            'rinci' => $simpanrinci,
            'kunci pesanan' => $pesananDikunci,
        ]);
    }

    public function listepenerimaan()
    {
        // $idpegawai = auth()->user()->pegawai_id;
        // $kodegudang = Pegawai::find($idpegawai);
        $kodegudang = FormatingHelper::session_user();

        $temp = Mpihakketiga::select('kode')->where('nama', 'Like', '%' . request('cari') . '%')->get('kode');
        $supl = collect($temp)->map(function ($item, $key) {
            return $item->kode;
        });
        $listpenerimaan = PenerimaanHeder::select(
            'penerimaan_h.nopenerimaan as nopenerimaan',
            'penerimaan_h.nopemesanan as nopemesanan',
            'penerimaan_h.tglpenerimaan as tglpenerimaan',
            'penerimaan_h.kdpbf',
            // 'siasik.pihak_ketiga.nama as pbf',
            'penerimaan_h.pengirim as pengirim',
            'penerimaan_h.jenissurat as jenissurat',
            'penerimaan_h.nomorsurat as nomorsurat',
            'penerimaan_h.tglsurat as tglsurat',
            'penerimaan_h.batasbayar as batasbayar',
            'penerimaan_h.kunci as kunci',
            'penerimaan_h.total_faktur_pbf as total',
        )
            // ->leftJoin('siasik.pihak_ketiga', 'siasik.pihak_ketiga.kode', 'penerimaan_h.kdpbf')
            ->where('penerimaan_h.nopemesanan', 'Like', '%' . request('cari') . '%')
            ->when($kodegudang['kdruang'] !== '', function ($e) use ($kodegudang) {
                $e->where('penerimaan_h.gudang', $kodegudang['kdruang']);
            })
            ->when(count($supl) > 0, function ($e) use ($supl) {
                $e->orWhereIn('penerimaan_h.kdpbf', $supl);
            })
            ->orWhere('penerimaan_h.nopenerimaan', 'Like', '%' . request('cari') . '%')
            ->orWhere('penerimaan_h.tglpenerimaan', 'Like', '%' . request('cari') . '%')
            // ->orWhere('siasik.pihak_ketiga.nama', 'Like', '%' . request('cari') . '%')
            ->orWhere('penerimaan_h.pengirim', 'Like', '%' . request('cari') . '%')
            ->orWhere('penerimaan_h.jenissurat', 'Like', '%' . request('cari') . '%')
            ->orWhere('penerimaan_h.nomorsurat', 'Like', '%' . request('cari') . '%')
            ->with(['penerimaanrinci', 'penerimaanrinci.masterobat', 'pihakketiga:kode,nama'])->orderBy('tglpenerimaan', 'desc')
            ->paginate(request('per_page'));
        return new JsonResponse($listpenerimaan);
    }

    public function kuncipenerimaan(Request $request)
    {
        $masukstok = Stokrel::where('nopenerimaan', $request->nopenerimaan)
            ->update(['flag' => '']);
        if (!$masukstok) {
            return new JsonResponse(['message' => 'Stok Tidak Terupdate,mohon segera cek Data Stok Anda...!!!'], 500);
        }

        $kuncipenerimaan = PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)
            ->update(['kunci' => '1']);
        if (!$kuncipenerimaan) {
            return new JsonResponse(['message' => 'Gagal Mengunci Penerimaan,Cek Lagi Data Yang Anda Input...!!!'], 500);
        }
        return new JsonResponse(['message' => 'Penerimaan Sudah Terkunci, Dan Stok Sudah Bertambah...!!!'], 200);
    }

    public function simpanpenerimaanlangsung(Request $request)
    {
        if ($request->gudang === 'Gd-05010100') {
            $procedure = 'penerimaan_obat_ko(@nomor)';
            $colom = 'penerimaanko';
            $lebel = 'G-KO';
        } else {
            $procedure = 'penerimaan_obat_fs(@nomor)';
            $colom = 'penerimaanfs';
            $lebel = 'G-FS';
        }
        if ($request->nopenerimaan === '' || $request->nopenerimaan === null) {
            DB::connection('farmasi')->select('call ' . $procedure);
            $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
            $wew = $x[0]->$colom;
            $nopenerimaan = FormatingHelper::penerimaanobat($wew, $lebel);
        } else {
            $nopenerimaan = $request->nopenerimaan;
        }
        $user = FormatingHelper::session_user();
        $simpanheder = PenerimaanHeder::updateorcreate(
            [
                'nopenerimaan' => $nopenerimaan,
                'kdpbf' => $request->kdpbf,
                'gudang' => $request->gudang,
            ],
            [
                //'nopemesanan' => $request->nopemesanan,
                'tglpenerimaan' => $request->tglpenerimaan,
                'pengirim' => $request->pengirim,
                'tglsurat' => $request->tglsurat,
                //'batasbayar' => $request->batasbayar,
                'jenissurat' => $request->jenissurat,
                'jenis_penerimaan' => $request->jenispenerimaan,
                'nomorsurat' => $request->nomorsurat,
                'user' => $user['kodesimrs'],
                'total_faktur_pbf' => $request->total_faktur_pbf,
            ]
        );
        if (!$simpanheder) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }
        $simpanrinci = PenerimaanRinci::updateorcreate(
            [
                'nopenerimaan' => $request->nopenerimaan ?? $nopenerimaan,
                'kdobat' => $request->kdobat,
                'no_batch' => $request->no_batch,
                'jml_terima_b' => $request->jml_terima_b,
                'jml_terima_k' => $request->jml_terima_k,
                'harga' => $request->harga,
                'harga_kcl' => $request->harga_kcl,
            ],
            [
                'no_retur_rs' => $request->no_retur_rs ?? '',
                'tgl_exp' => $request->tgl_exp,
                'satuan' => $request->satuan_bsr,
                'satuan_kcl' => $request->satuan_kcl,
                'isi' => $request->isi,
                'diskon' => $request->diskon,
                'diskon_rp' => $request->diskon_rp,
                'diskon_rp_kecil' => $request->diskon_rp_kecil,
                'ppn' => $request->ppn,
                'ppn_rp' => $request->ppn_rp,
                'ppn_rp_kecil' => $request->ppn_rp_kecil,
                'harga_netto' => $request->harga_netto,
                'harga_netto_kecil' => $request->harga_netto_kecil,
                'jml_pesan' => $request->jml_pesan,
                'jml_terima_lalu' => $request->jml_terima_lalu,
                'jml_all_penerimaan' => $request->jml_all_penerimaan,
                'subtotal' => $request->subtotal,
                'user' => $user['kodesimrs']
            ]
        );
        if (!$simpanrinci) {
            PenerimaanHeder::where('nopenerimaan', $nopenerimaan)->first()->delete();
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }
        $stokrealsimpan = StokrealController::stokreal($nopenerimaan, $request);
        if ($stokrealsimpan !== 200) {
            PenerimaanHeder::where('nopenerimaan', $nopenerimaan)->first()->delete();
            PenerimaanRinci::where('nopenerimaan', $nopenerimaan)->first()->delete();
            return new JsonResponse(['message' => 'Gagal Tersimpan Ke Stok...!!!'], 500);
        }


        $simpanrinci->load('masterobat:kd_obat,nama_obat,satuan_b');
        return new JsonResponse([
            'message' => 'ok',
            'nopenerimaan' => $nopenerimaan,
            'heder' => $simpanheder,
            'rinci' => $simpanrinci
        ]);
    }
    public function batalHeader(Request $request)
    {
        $pemesananH = PemesananHeder::where('nopemesanan', $request->nopemesanan)->first();
        $pemesananR = [];
        $penerimaanH = PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)->first();
        $penerimaanR = PenerimaanRinci::where('nopenerimaan', $request->nopenerimaan)->get();
        $stok = Stokreal::where('nopenerimaan', $request->nopenerimaan)->where('flag', '1')->get();

        if (!$penerimaanH) {
            return new JsonResponse(['message' => 'Gagal hapus, data tidak ditemukan'], 410);
        }

        if (count($penerimaanR)) {
            foreach ($penerimaanR as $key) {
                $item = PemesananRinci::where('nopemesanan', $request->nopemesanan)
                    ->where('kdobat', $key['kdobat'])
                    ->get();
                if (count($item)) {
                    if (count($item) > 1) {
                        foreach ($item as $it) {
                            $it->flag = '';
                            $it->save();
                            $pemesananR[] = $it;
                        }
                    } else {
                        $item[0]->flag = '';
                        $item[0]->save();
                        $pemesananR[] = $item[0];
                    }
                }
                $key->delete();
            }
        }

        if ($pemesananH) {
            $pemesananH->flag = '1';
            $pemesananH->save();
        }

        $penerimaanH->delete();

        if (count($stok)) {
            foreach ($stok as $st) {
                $st->delete();
            }
        }
        return new JsonResponse([
            'message' => 'Data berhasil dihapus',
            'pemesanan header' => $pemesananH,
            'pemesanan rinci' => $pemesananR,
            'penerimaan header' => $penerimaanH,
            'penerimaan rinci' => $penerimaanR,
        ]);
    }
    public function batalRinci(Request $request)
    {
        $penerimaanH = PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)->first();
        $penerimaanR = PenerimaanRinci::find($request->id);
        if (!$penerimaanR) {
            return new JsonResponse(['message' => 'gagal dihapus, data tidak ditemukan'], 410);
        }
        $pemesananH = PemesananHeder::where('nopemesanan', $penerimaanH->nopemesanan)->first();

        $pemesananR = PemesananRinci::where('nopemesanan', $penerimaanH->nopemesanan)
            ->where('kdobat', $penerimaanR->kdobat)
            ->get();
        if (count($pemesananR) >= 0) {
            if (count($pemesananR) > 1) {
                foreach ($pemesananR as $it) {
                    $it->flag = '';
                    $it->save();
                    $pemesananR[] = $it;
                }
            } else {
                $pemesananR[0]->flag = '';
                $pemesananR[0]->save();
            }
        }

        if ($pemesananH) {
            $pemesananH->flag = '1';
            $pemesananH->save();
        }

        $stok = Stokreal::where('nopenerimaan', $request->nopenerimaan)->where('kdobat', $penerimaanR->kdobat)->where('flag', '1')->get();
        if (count($stok)) {
            foreach ($stok as $st) {
                $st->delete();
            }
        }

        $penerimaanR->delete();

        $allRinci = PenerimaanRinci::where('nopenerimaan', $request->nopenerimaan)->get();
        if (count($allRinci) <= 0) {
            $penerimaanH->delete();
        }
        return new JsonResponse([
            'message' => 'Data Berhasil dihapus',
            'pemesanan header' => $pemesananH,
            'pemesanan rinci' => $pemesananR,
            'penerimaan header' => $penerimaanH,
            'penerimaan rinci' => $penerimaanR,
            'all rinci' => $allRinci,

        ]);
    }
}
