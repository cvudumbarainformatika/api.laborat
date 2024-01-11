<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EresepController extends Controller
{

    public function lihatstokobateresepBydokter()
    {
        $groupsistembayar = request('groups');
        if ($groupsistembayar == '1') {
            $sistembayar = ['SEMUA', 'BPJS'];
        } else {
            $sistembayar = ['SEMUA', 'UMUM'];
        }
        $cariobat = Stokreal::select(
            'stokreal.kdobat as kdobat',
            'stokreal.kdruang as kdruang',
            'new_masterobat.nama_obat as namaobat',
            'new_masterobat.kandungan as kandungan',
            'new_masterobat.bentuk_sediaan as bentuk_sediaan',
            'new_masterobat.satuan_k as satuankecil',
            'new_masterobat.status_fornas as fornas',
            'new_masterobat.status_forkid as forkit',
            'new_masterobat.status_generik as generik',
            'new_masterobat.kode108',
            'new_masterobat.uraian108',
            'new_masterobat.kode50',
            'new_masterobat.uraian50',
            'new_masterobat.kekuatan_dosis as kekuatandosis',
            'new_masterobat.volumesediaan as volumesediaan',
            DB::raw('sum(stokreal.jumlah) as total')
        )
            ->with(
                [
                    'minmax',
                    'transnonracikan'
                    => function ($transnonracikan) {
                        $transnonracikan->select(
                            'resep_keluar_r.kdobat as kdobat',
                            'resep_keluar_h.depo as kdruang',
                            DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                        )
                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_keluar_r.noresep')
                            ->where('resep_keluar_h.depo', request('kdruang'))
                            ->groupBy('resep_keluar_r.kdobat');
                    },
                ]
            )
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->where('stokreal.kdruang', request('kdruang'))
            ->whereIn('new_masterobat.sistembayar', $sistembayar)
            ->where(function ($query) {
                $query->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('new_masterobat.kandungan', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('stokreal.kdobat', 'LIKE', '%' . request('q') . '%');
            })
            ->groupBy('stokreal.kdobat')
            ->get();
        $wew = collect($cariobat)->map(function ($x, $y) {
            $total = $x->total ?? 0;
            $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            $x->alokasi = $total - $jumlahtrans;
            return $x;
        });
        return new JsonResponse(
            [
                'dataobat' => $wew
            ]
        );
    }

    public function pembuatanresep(Request $request)
    {
        $user = FormatingHelper::session_user();
        if ($user['kdgroupnakes'] != '1') {
            return new JsonResponse(['message' => 'Maaf Anda Bukan Dokter...!!!'], 500);
        }

        $cekjumlahstok = Stokreal::select(DB::raw('sum(jumlah) as jumlahstok'))
            ->where('kdobat', $request->kodeobat)->where('kdruang', $request->kodedepo)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();
        $jumlahstok = $cekjumlahstok[0]->jumlahstok;
        if ($request->jumlah > $jumlahstok) {
            return new JsonResponse(['message' => 'Maaf Stok Tidak Mencukupi...!!!'], 500);
        }

        if ($request->kodedepo === 'Gd-04010102') {
            $procedure = 'resepkeluardeporanap(@nomor)';
            $colom = 'deporanap';
            $lebel = 'D-RI';
        } elseif ($request->kodedepo === 'Gd-04010103') {
            $procedure = 'resepkeluardepook(@nomor)';
            $colom = 'depook';
            $lebel = 'D-KO';
        } elseif ($request->kodedepo === 'Gd-05010101') {

            $procedure = 'resepkeluardeporajal(@nomor)';
            $colom = 'deporajal';
            $lebel = 'D-RJ';
        } else {
            $procedure = 'resepkeluardepoigd(@nomor)';
            $colom = 'depoigd';
            $lebel = 'D-IR';
        }

        if ($request->noresep === '' || $request->noresep === null) {
            DB::connection('farmasi')->select('call ' . $procedure);
            $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
            $wew = $x[0]->$colom;
            $noresep = FormatingHelper::resep($wew, $lebel);
        } else {
            $noresep = $request->noresep;
        }

        $simpan = Resepkeluarheder::updateOrCreate(
            [
                'noresep' => $noresep,
                'noreg' => $request->noreg,
            ],
            [
                'norm' => $request->norm,
                'tgl_permintaan' => date('Y-m-d H:i:s'),
                'depo' => $request->kodedepo,
                'ruangan' => $request->kdruangan,
                'dokter' =>  $user['kodesimrs'],
                'sistembayar' => $request->sistembayar,
                'diagnosa' => $request->diagnosa,
                'kodeincbg' => $request->kodeincbg,
                'uraianinacbg' => $request->uraianinacbg,
                'tarifina' => $request->tarifina,
                'tagihanrs' => $request->tagihanrs ?? 0,
            ]
        );

        if (!$simpan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $gudang = ['Gd-05010100', 'Gd-03010100'];
        $cariharga = Stokreal::select(DB::raw('max(harga) as harga'))
            ->whereIn('kdruang', $gudang)
            ->where('kdobat', $request->kodeobat)
            ->orderBy('tglpenerimaan', 'desc')
            ->limit(5)
            ->get();
        $harga = $cariharga[0]->harga;

        // $caristok = Stokreal::where('kdobat', $request->kodeobat)->where('kdruang', $request->kodedepo)
        //     ->where('jumlah', '!=', 0)
        //     ->orderBy('tglexp')
        //     ->get();


        if ($request->groupsistembayar == 1) {
            if ($harga <= 50000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 28 / (int) 100;
            } elseif ($harga > 50000 && $harga <= 250000) {
                $hargajualx = (int) $harga + ((int) $harga * (int) 26 / (int) 100);
            } elseif ($harga > 250000 && $harga <= 500000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 21 / (int) 100;
            } elseif ($harga > 500000 && $harga <= 1000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 16 / (int)100;
            } elseif ($harga > 1000000 && $harga <= 5000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 11 /  (int)100;
            } elseif ($harga > 5000000 && $harga <= 10000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 9 / (int) 100;
            } elseif ($harga > 10000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 7 / (int) 100;
            }
        } else {
            $hargajualx = (int) $harga + (int) $harga * (int) 25 / (int)100;
        }

        if ($request->jenisresep == 'Racikan') {
            if ($request->jenisracikan == 'DTD') {
                $simpandtd = Permintaanresepracikan::create(
                    [
                        'noreg' => $request->noreg,
                        'noresep' => $noresep,
                        'namaracikan' => $request->namaracikan,
                        'tiperacikan' => $request->jenisracikan,
                        'jumlahdibutuhkan' => $request->jumlahdibutuhkan,
                        'aturan' => $request->aturan,
                        'konsumsi' => $request->konsumsi,
                        'keterangan' => $request->keterangan,
                        'kdobat' => $request->kodeobat,
                        'kandungan' => $request->kandungan,
                        'fornas' => $request->fornas,
                        'forkit' => $request->forkit,
                        'generik' => $request->generik,
                        'r' => 500,
                        'hpp' => $harga,
                        'harga_jual' => $hargajualx,
                        'kode108' => $request->kode108,
                        'uraian108' => $request->uraian108,
                        'kode50' => $request->kode50,
                        'uraian50' => $request->uraian50,
                        'stokalokasi' => $request->stokalokasi,
                        'dosisobat' => $request->dosisobat,
                        'dosismaksimum' => $request->dosismaksimum,
                        'jumlah' => $request->jumlah,
                        'keteranganx' => $request->keteranganx,
                        'user' => $user['kodesimrs']
                    ]
                );
            } else {
                $simpannondtd = Permintaanresepracikan::create(
                    [
                        'noreg' => $request->noreg,
                        'noresep' => $noresep,
                        'namaracikan' => $request->namaracikan,
                        'tiperacikan' => $request->tiperacikan,
                        'jumlahdibutuhkan' => $request->jumlahdibutuhkan,
                        'aturan' => $request->aturan,
                        'konsumsi' => $request->konsumsi,
                        'keterangan' => $request->keterangan,
                        'kdobat' => $request->kodeobat,
                        'kandungan' => $request->kandungan,
                        'fornas' => $request->fornas,
                        'forkit' => $request->forkit,
                        'generik' => $request->generik,
                        'r' => 500,
                        'hpp' => $harga,
                        'harga_jual' => $hargajualx,
                        'kode108' => $request->kode108,
                        'uraian108' => $request->uraian108,
                        'kode50' => $request->kode50,
                        'uraian50' => $request->uraian50,
                        'stokalokasi' => $request->stokalokasi,
                        // 'dosisobat' => $request->dosisobat,
                        // 'dosismaksimum' => $request->dosismaksimum,
                        'jumlah' => $request->jumlah,
                        'keteranganx' => $request->keteranganx,
                        'user' => $user['kodesimrs']
                    ]
                );
            }
        } else {
            $simpanrinci = Permintaanresep::create(
                [
                    'noreg' => $request->noreg,
                    'noresep' => $noresep,
                    'kdobat' => $request->kodeobat,
                    'kandungan' => $request->kandungan,
                    'fornas' => $request->fornas,
                    'forkit' => $request->forkit,
                    'generik' => $request->generik,
                    'kode108' => $request->kode108,
                    'uraian108' => $request->uraian108,
                    'kode50' => $request->kode50,
                    'uraian50' => $request->uraian50,
                    'stokalokasi' => $request->stokalokasi,
                    'r' => 300,
                    'jumlah' => $request->jumlah_diminta,
                    'hargabeli' => $hargajualx,
                    'aturan' => $request->aturan,
                    'konsumsi' => $request->konsumsi,
                    'keterangan' => $request->keterangan ?? '',
                    'user' => $user['kodesimrs']
                ]
            );
        }
        return new JsonResponse([
            'heder' => $simpan,
            'rinci' => $simpanrinci ?? 0,
            'rincidtd' => $simpandtd ?? 0,
            'rincinondtd' => $simpannondtd ?? 0,
            'nota' => $noresep,
            'message' => 'Data Berhasil Disimpan...!!!'
        ], 200);
    }

    public function listresepbydokter()
    {
        $listresep = Resepkeluarheder::select(
            'farmasi.resep_keluar_h.*',
            'kepegx.pegawai.nama as dokter',
            'rs.rs9.rs2 as sistembayar',
            'rs.rs15.rs2 as namapasien'
        )
            ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', 'farmasi.resep_keluar_h.dokter')
            ->leftjoin('rs.rs9', 'rs.rs9.rs1', 'farmasi.resep_keluar_h.sistembayar')
            ->leftjoin('rs.rs15', 'rs.rs15.rs1', 'farmasi.resep_keluar_h.norm')
            ->with(
                [
                    'rincian.mobat:kd_obat,nama_obat'
                ]
            )
            ->where(function ($query) {
                $query->where('rs.rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('farmasi.resep_keluar_h.noresep', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('farmasi.resep_keluar_h.norm', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('farmasi.resep_keluar_h.noreg', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs.rs9.rs2', 'LIKE', '%' . request('q') . '%');
            })
            ->get();
        return new JsonResponse(
            [
                'result' => $listresep
            ]
        );
    }

    public function kirimresep(Request $request)
    {
        $kirimresep = Resepkeluarheder::where('noresep', $request->noresep)->first();
        $kirimresep->flag = '1';
        $kirimresep->tgl_kirim = date('Y-m-d H:i:s');
        $kirimresep->save();

        return new JsonResponse(['message' => 'Resep Berhasil Dikirim Kedepo Farmasi...!!!'], 200);
    }

    public function eresepobatkeluar(Request $request)
    {
        $cekjumlahstok = Stokreal::select(DB::raw('sum(jumlah) as jumlahstok'))
            ->where('kdobat', $request->kodeobat)->where('kdruang', $request->kodedepo)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();
        $jumlahstok = $cekjumlahstok[0]->jumlahstok;
        if ($request->jumlah > $cekjumlahstok[0]->jumlahstok) {
            return new JsonResponse(['message' => 'Maaf Stok Tidak Mencukupi...!!!'], 500);
        }
    }
}