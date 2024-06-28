<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasi\MapingObat;
use App\Models\Simrs\Penunjang\Farmasi\StokOpname;
use App\Models\Simrs\Penunjang\Farmasi\StokReal;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_r;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\PenyesuaianStok;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname as StokStokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal as FarmasinewStokreal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SetNewStokController extends Controller
{

    public function setNewStok()
    {
        $create = date('Y-m-d H:i:s');
        $mapingGudang = [
            ['nama' => 'Gudang Farmasi ( Kamar Obat )', 'kode' => 'Gd-05010100', 'lama' => 'GU0001'],
            ['nama' => 'Gudang Farmasi (Floor Stok)', 'kode' => 'Gd-03010100', 'lama' => 'GU0002'],
            ['nama' => 'Floor Stock 1 (AKHP)', 'kode' => 'Gd-03010101', 'lama' => 'RC0001'],
            ['nama' => 'Depo Rawat inap', 'kode' => 'Gd-04010102', 'lama' => 'AP0002'],
            ['nama' => 'Depo OK', 'kode' => 'Gd-04010103', 'lama' => 'AP0005'],
            ['nama' => 'Depo Rawat Jalan', 'kode' => 'Gd-05010101', 'lama' => 'AP0001'],
            ['nama' => 'Depo IGD', 'kode' => 'Gd-02010104', 'lama' => 'AP0007']
        ];
        $gudBaru = ['05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];

        $mapingDep = ['GU0001', 'GU0002', 'RC0001', 'AP0002', 'AP0005', 'AP0001', 'AP0007'];

        $mapingObat = MapingObat::with([
            'master:rs1,rs4',
            'stok' => function ($stok) use ($mapingDep) {
                $stok->where('rs2', '>', 0)
                    ->whereIn('rs4', $mapingDep);
            },
            'rincipenerimaan' => function ($tr) {
                $tr->select(
                    'rs82.rs1',
                    'rs82.rs2',
                    'rs82.rs6',
                    'rs82.rs7',
                    'rs81.rs2 as tanggal',
                )
                    ->leftJoin('rs81', 'rs81.rs1', '=', 'rs82.rs1')
                    ->orderBy('rs81.rs2', 'DESC');
                // ->limit(5);
            }
        ])
            ->where('obatbaru', '<>', '')
            ->orderBy('obatbaru', 'ASC')
            // ->limit(10)
            ->get();
        $newStok = [];
        foreach ($mapingObat as $key) {
            foreach ($key['stok'] as $st) {
                $raw = collect($mapingGudang)
                    ->where('lama', $st['rs4'])
                    ->map(function ($it, $key) {
                        return $it['kode'] ?? null;
                    });
                /**
                 * Catatan:
                 * $anu dan $item ada karena key nya ($anu) dinamis, maka key nya harus dicari berdasarkan nilai objeck yang sekarang ($item)
                 * key nya tidak bisa langsung diambil dari $raw, karena $raw masih belum menjadi nilai dari object, maka nilai dari object harus di akses terlebih dahulu di $item
                 */
                $item = current((array)$raw); // value of current object
                $anu = key((array)$item); // key of the object value
                if ($item[$anu] === 'Gd-05010100') $nPen = 'G-KO';
                else if ($item[$anu] === 'Gd-03010100') $nPen = 'G-FO';
                else if ($item[$anu] === 'Gd-03010101') $nPen = 'D-FO';
                else if ($item[$anu] === 'Gd-04010102') $nPen = 'D-RI';
                else if ($item[$anu] === 'Gd-04010103') $nPen = 'D-OK';
                else if ($item[$anu] === 'Gd-05010101') $nPen = 'D-RJ';
                else if ($item[$anu] === 'Gd-02010104') $nPen = 'D-IGD';
                else  $nPen = 'NDF';
                $temp = [
                    'nopenerimaan' => '001/' . date('m/Y') . '/awal/' . $nPen,
                    'tglpenerimaan' => $key['rincipenerimaan']['tanggal'] ?? $create,
                    'kdobat' => $key['obatbaru'],
                    'jumlah' => (float)$st['rs2'],
                    'kdruang' => $item[$anu],
                    'harga' => (float)$key['master']['rs4'],
                    'tglexp' => $key['rincipenerimaan']['rs7'] ?? null,
                    'nobatch' => $key['rincipenerimaan']['rs6'] ?? '',
                    'created_at' => $create,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $newStok[] = $temp;
            }
        }

        if (count($newStok) <= 0) {
            return new JsonResponse($newStok);
        }

        FarmasinewStokreal::truncate();
        foreach (array_chunk($newStok, 100) as $t) {
            $data['ins'] = FarmasinewStokreal::insert($t);
        }
        // if (count($daftarHarga) > 0) {
        //     DaftarHarga::truncate();
        //     $uni = array_unique($daftarHarga, SORT_REGULAR);
        //     foreach (array_chunk($uni, 1000) as $t) {
        //         $data['ins'] = DaftarHarga::insert($t);
        //     }
        // }

        // $data['mapingObat'] = $mapingObat;
        // sleep(20);
        $data['new stok'] = $newStok;
        // $data['har'] = $this->cekHargaGud();

        return new JsonResponse($data);
    }
    public function cekHargaGud()
    {
        $gKo = 'Gd-05010100';
        $gFo = 'Gd-03010100';
        $dFo = 'Gd-03010101';
        $dep = ['Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
        $obKo = FarmasinewStokreal::select('kdobat')->where('kdruang', $gKo)->distinct()->get('kdobat');
        $obFo = FarmasinewStokreal::select('kdobat')->where('kdruang', $gFo)->distinct()->get('kdobat');
        $obDFo = FarmasinewStokreal::where('kdruang', $dFo)->whereNotIn('kdobat', $obFo)->groupBy('kdobat')->get();
        $obDep = FarmasinewStokreal::whereIn('kdruang', $dep)->whereNotIn('kdobat', $obKo)->groupBy('kdobat')->get();
        $stok = [];
        if (count($obDFo) > 0) {
            foreach ($obDFo as $key) {
                $temp['nopenerimaan'] = $key['nopenerimaan'];
                $temp['tglpenerimaan'] = $key['tglpenerimaan'];
                $temp['kdobat'] = $key['kdobat'];
                $temp['jumlah'] = 0;
                $temp['kdruang'] = 'Gd-03010100';
                $temp['harga'] = (float)$key['harga'] ?? 0;
                $temp['flag'] = $key['flag'];
                $temp['tglexp'] = $key['tglexp'];
                $temp['nobatch'] = $key['nobatch'];
                $temp['nodistribusi'] = $key['nodistribusi'];
                $temp['created_at'] = date('Y-m-d H:i:s');
                $temp['updated_at'] = date('Y-m-d H:i:s');
                $stok[] = $temp;
            }
        }
        if (count($obDep) > 0) {
            foreach ($obDep as $key) {

                $temp['nopenerimaan'] = $key['nopenerimaan'];
                $temp['tglpenerimaan'] = $key['tglpenerimaan'];
                $temp['kdobat'] = $key['kdobat'];
                $temp['jumlah'] = 0;
                $temp['kdruang'] = 'Gd-05010100';
                $temp['harga'] = $key['harga'];
                $temp['flag'] = $key['flag'];
                $temp['tglexp'] = $key['tglexp'];
                $temp['nobatch'] = $key['nobatch'];
                $temp['nodistribusi'] = $key['nodistribusi'];
                $temp['created_at'] = date('Y-m-d H:i:s');
                $temp['updated_at'] = date('Y-m-d H:i:s');
                $stok[] = $temp;
            }
        }
        if (count($stok) <= 0) {
            return [
                'stok' => false,
            ];
        }
        foreach (array_chunk($stok, 1000) as $t) {
            $data = FarmasinewStokreal::insert($t);
        }
        // sleep(20);
        return [
            'obDFo' => $obDFo,
            'stok' => $stok,
            'data' => $data ?? false,
        ];
    }

    public function insertHarga()
    {

        // insert harga
        $harga = [];
        $allGud = ['Gd-05010100', 'Gd-03010100'];
        $obAllDep = FarmasinewStokreal::selectRaw('* ,sum(jumlah) as total, avg(harga) as rharga')
            ->whereNotNull('harga')
            ->where('harga', '>', 0)
            ->groupBy('kdobat')
            ->get();

        if (count($obAllDep) > 0) {
            foreach ($obAllDep as $key) {

                // if ((float)$key['harga'] > 0) {
                $tHarga['nopenerimaan'] = $key['nopenerimaan'];
                $tHarga['kd_obat'] = $key['kdobat'];
                $tHarga['harga'] = (float)$key['harga'] > 0 ? (float)$key['harga'] : (float)$key['rharga'];
                $tHarga['tgl_mulai_berlaku'] = date('Y-m-d H:i:s');
                $tHarga['created_at'] = date('Y-m-d H:i:s');
                $tHarga['updated_at'] = date('Y-m-d H:i:s');
                $harga[] = $tHarga;
                // }
            }
        }
        if (count($harga) <= 0) {
            return [
                'harga' => false,
                'data' => $data ?? false,
            ];
        }
        DaftarHarga::truncate();
        foreach (array_chunk($harga, 1000) as $t) {
            $dataHarga = DaftarHarga::insert($t);
        }

        return [
            'obAllDep' => $obAllDep,
            'harga' => $dataHarga,
            'data' => $data ?? false,
        ];
    }

    public function setStokOpnameAwal()
    {
        $tanggal = StokOpname::select('rs5')->distinct()->orderBy('rs5', 'DESC')->first('rs5');
        $tglVal = $tanggal['rs5'];
        $newOpname = [];
        $opname = StokStokopname::whereNotBetween('tglopname', [$tglVal, $tglVal])->get();
        foreach ($opname as $key) {
            $temp = [
                'nopenerimaan' => $key['nopenerimaan'],
                'tglpenerimaan' => $key['tglpenerimaan'],
                'kdobat' => $key['kdobat'],
                'jumlah' => $key['jumlah'],
                'kdruang' => $key['kdruang'],
                'harga' => $key['harga'],
                'flag' => $key['flag'],
                'tglexp' => $key['tglexp'],
                'nobatch' => $key['nobatch'],
                'nodistribusi' => $key['nodistribusi'],
                'tglopname' => $key['tglopname'],
                'created_at' => $key['created_at'],
                'updated_at' => $key['updated_at'],
            ];
            $newOpname[] = $temp;
        }
        $mapingGudang = [
            ['nama' => 'Gudang Farmasi ( Kamar Obat )', 'kode' => 'Gd-05010100', 'lama' => 'GU0001'],
            ['nama' => 'Gudang Farmasi (Floor Stok)', 'kode' => 'Gd-03010100', 'lama' => 'GU0002'],
            ['nama' => 'Floor Stock 1 (AKHP)', 'kode' => 'Gd-03010101', 'lama' => 'RC0001'],
            ['nama' => 'Depo Rawat inap', 'kode' => 'Gd-04010102', 'lama' => 'AP0002'],
            ['nama' => 'Depo OK', 'kode' => 'Gd-04010103', 'lama' => 'AP0005'],
            ['nama' => 'Depo Rawat Jalan', 'kode' => 'Gd-05010101', 'lama' => 'AP0001'],
            ['nama' => 'Depo IGD', 'kode' => 'Gd-02010104', 'lama' => 'AP0007']
        ];

        $mapingDep = ['GU0001', 'GU0002', 'RC0001', 'AP0002', 'AP0005', 'AP0001', 'AP0007'];

        $mapingObat = MapingObat::with([
            // 'master:rs1,rs4',
            'stokopname' => function ($stok) use ($mapingDep, $tglVal) {
                $stok->whereIn('rs4', $mapingDep)
                    ->whereBetween('rs5', [$tglVal, $tglVal]);
            },
            'rincipenerimaan' => function ($tr) {
                $tr->select(
                    'rs82.rs1',
                    'rs82.rs2',
                    'rs82.rs6',
                    'rs82.rs7',
                    'rs81.rs2 as tanggal',
                )
                    ->leftJoin('rs81', 'rs81.rs1', '=', 'rs82.rs1')
                    ->orderBy('rs81.rs2', 'DESC');
                // ->limit(10);
            }
        ])
            ->where('obatbaru', '<>', '')
            // ->limit(50)
            ->get();

        foreach ($mapingObat as $key) {
            foreach ($key['stokopname'] as $st) {
                $raw = collect($mapingGudang)
                    ->where('lama', $st['rs4'])
                    ->map(function ($it, $key) {
                        return $it['kode'] ?? null;
                    });

                $item = current((array)$raw); // value of current object
                $anu = key((array)$item); // key of the object value
                $ruang = $item[$anu] ?? $st['rs4'];
                if ($ruang === 'Gd-05010100') $nPen = 'G-KO';
                else if ($ruang === 'Gd-03010100') $nPen = 'G-FO';
                else if ($ruang === 'Gd-03010101') $nPen = 'D-FO';
                else if ($ruang === 'Gd-04010102') $nPen = 'D-RI';
                else if ($ruang === 'Gd-04010103') $nPen = 'D-OK';
                else if ($ruang === 'Gd-05010101') $nPen = 'D-RJ';
                else if ($ruang === 'Gd-02010104') $nPen = 'D-IGD';
                else  $nPen = 'NDF';
                $temp = [
                    'nopenerimaan' => '001/' . date('m/Y') . '/opnameAwal/' . $nPen,
                    'tglpenerimaan' => $key['rincipenerimaan']['tanggal'] ?? date('Y-m-d H:i:s'),
                    'kdobat' => $key['obatbaru'],
                    'jumlah' => $st['rs2'],
                    'kdruang' => $ruang,
                    'harga' => $st['rs3'],
                    'flag' => '',
                    'tglexp' => $key['rincipenerimaan']['rs7'] ?? null,
                    'nobatch' => $key['rincipenerimaan']['rs6'] ?? '',
                    'nodistribusi' => '',
                    'tglopname' => $st['rs5'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $newOpname[] = $temp;
            }
        }
        if (count($newOpname) > 0) {
            StokStokopname::truncate();
            foreach (array_chunk($newOpname, 100) as $t) {
                $data['ins'] = StokStokopname::insert($t);
            }
        }
        // $data['mapingObat'] = $mapingObat;
        $data['newOpname'] = $newOpname;
        return new JsonResponse($data);
    }

    public function newPerbaikanStok(Request $request)
    {
        $depo = $request->kdruang;
        $obat = $request->kdobat;
        $data = self::getDataTrans($depo, $obat);


        return new JsonResponse($data);
    }
    public static function getDataTrans($koderuangan, $kdobat)
    {
        // $mapingGudang = [
        //     ['nama' => 'Gudang Farmasi ( Kamar Obat )', 'kode' => 'Gd-05010100', 'lama' => 'GU0001'],
        //     ['nama' => 'Gudang Farmasi (Floor Stok)', 'kode' => 'Gd-03010100', 'lama' => 'GU0002'],
        //     ['nama' => 'Floor Stock 1 (AKHP)', 'kode' => 'Gd-03010101', 'lama' => 'RC0001'],
        //     ['nama' => 'Depo Rawat inap', 'kode' => 'Gd-04010102', 'lama' => 'AP0002'],
        //     ['nama' => 'Depo OK', 'kode' => 'Gd-04010103', 'lama' => 'AP0005'],
        //     ['nama' => 'Depo Rawat Jalan', 'kode' => 'Gd-05010101', 'lama' => 'AP0001'],
        //     ['nama' => 'Depo IGD', 'kode' => 'Gd-02010104', 'lama' => 'AP0007']
        // ];
        $gudangs = ['Gd-05010100', 'Gd-03010100'];
        $depos = ['Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
        $koderuangan = $koderuangan;
        $bulan = date('m');
        $tahun = date('Y');
        // $bulan = request('bulan');
        // $tahun = request('tahun');
        $x = $tahun . '-' . $bulan;
        $tglAwal = $x . '-01';
        $tglAkhir = $x . '-31';
        $dateAwal = Carbon::parse($tglAwal);
        $dateAkhir = Carbon::parse($tglAkhir);
        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m-d');
        $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-d');

        $message = 'Stok sudah Sesuai tidak ada yang perlu di update';
        if (in_array($koderuangan, $gudangs)) {
            $saldoAwal = StokStokopname::select('tglopname', 'jumlah', 'kdobat', DB::raw('sum(jumlah) as total'))
                ->whereBetween('tglopname', [$blnLaluAwal, $blnLaluAkhir])
                ->where('kdruang', $koderuangan)
                ->where('kdobat', $kdobat)
                ->groupBy('tglopname', 'kdruang', 'kdobat')
                ->first();
            $stokid = FarmasinewStokreal::select('id')->where('kdruang', $koderuangan)
                ->where('kdobat', $kdobat)
                ->pluck('id');
            $penyesuaian = PenyesuaianStok::select('stokreal_id', DB::raw('sum(penyesuaian) as jumlah'))
                ->whereIn('stokreal_id', $stokid)
                ->groupBy('stokreal_id')
                ->first();
            $penerimaan = PenerimaanRinci::select(
                'penerimaan_r.kdobat',
                DB::raw('sum(jml_terima_k) as jumlah')
            )
                ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                ->whereBetween('penerimaan_h.tglpenerimaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->where('penerimaan_h.gudang', $koderuangan)
                ->where('penerimaan_h.kunci', '1')
                ->where('penerimaan_r.kdobat', $kdobat)
                ->groupBy('penerimaan_r.kdobat')
                ->first();
            $mutasiMasuk = Mutasigudangkedepo::select(
                'mutasi_gudangdepo.kd_obat as kdobat',
                DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
            )
                ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                ->whereBetween('permintaan_h.tgl_terima_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->where('permintaan_h.dari', $koderuangan)
                ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                ->groupBy('mutasi_gudangdepo.kd_obat')
                ->first();
            $mutasiKeluar = Mutasigudangkedepo::select(
                'mutasi_gudangdepo.kd_obat as kdobat',
                DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
            )
                ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                ->whereBetween('permintaan_h.tgl_kirim_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->where('permintaan_h.tujuan', $koderuangan)
                ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                ->groupBy('mutasi_gudangdepo.kd_obat')
                ->first();
            $totalStok = FarmasinewStokreal::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                ->where('kdruang', $koderuangan)->first();
            $tts = $totalStok->jumlah ?? 0;
            $sal = $saldoAwal->jumlah ?? 0;
            $peny = $penyesuaian->jumlah ?? 0;
            $trm = $penerimaan->jumlah ?? 0;
            $mutma = $mutasiMasuk->jumlah ?? 0;
            $mutkel = $mutasiKeluar->jumlah ?? 0;
            $masuk = (int)$sal + (int)$peny + (int)$trm + (int)$mutma;
            $keluar = (int)$mutkel;
            $sisa = (int)$masuk - (int)$keluar;
            if ((int)$sisa != (int)$tts) {
                $masuk = $sisa;
                $index = 0;
                $stok = FarmasinewStokreal::where('kdobat', $kdobat)
                    ->where('kdruang', $koderuangan)
                    ->orderBy('tglexp', 'DESC')
                    ->orderBy('nodistribusi', 'DESC')
                    ->get();
                while ($masuk > 0) {
                    $penrimaanrinci = PenerimaanRinci::where('nopenerimaan', $stok[$index]['nopenerimaan'])
                        ->where('kdobat', $stok[$index]['kdobat'])
                        ->first();
                    if ($penrimaanrinci) {
                        $ada = $penrimaanrinci->jml_terima_k;
                        if ($ada > $masuk) {
                            $stok[$index]->update([
                                'jumlah' => $masuk
                            ]);
                            $masuk = 0;
                        } else {
                            $sisax = $masuk - $ada;
                            $stok[$index]->update([
                                'jumlah' => $ada
                            ]);
                            $masuk = $sisax;
                            $index += 1;
                        }
                    } else {
                        $stok[$index]->update([
                            'jumlah' => $masuk
                        ]);
                        $masuk = 0;
                    }
                }
                $message = 'Cek Stok Gudang selesai, Stok sudah di update';
            }


            return [
                'saldoAwal' => $saldoAwal,
                'stokid' => $stokid,
                'penyesuaian' => $penyesuaian,
                'penerimaan' => $penerimaan,
                'mutasiMasuk' => $mutasiMasuk,
                'mutasiKeluar' => $mutasiKeluar,
                'totalStok' => $totalStok,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'sisa' => $sisa,
                'sal' => $sal,
                'peny' => $peny,
                'trm' => $trm,
                'mutma' => $mutma,
                'mutkel' => $mutkel,
                'stok' => $stok ?? [],
                'message' => $message
            ];
        } else {
            $saldoAwal = StokStokopname::select('tglopname', 'jumlah', 'kdobat', DB::raw('sum(jumlah) as total'))
                ->whereBetween('tglopname', [$blnLaluAwal, $blnLaluAkhir])
                ->where('kdruang', $koderuangan)
                ->where('kdobat', $kdobat)
                ->groupBy('tglopname', 'kdruang', 'kdobat')
                ->first();
            $stokid = FarmasinewStokreal::select('id')->where('kdruang', $koderuangan)
                ->where('kdobat', $kdobat)
                ->pluck('id');
            $penyesuaian = PenyesuaianStok::select('stokreal_id', DB::raw('sum(penyesuaian) as jumlah'))
                ->whereIn('stokreal_id', $stokid)
                // ->whereNull('flag') // local only
                ->groupBy('stokreal_id')
                ->first();

            $mutasiMasuk = Mutasigudangkedepo::select(
                'mutasi_gudangdepo.kd_obat as kdobat',
                DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
            )
                ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                ->whereBetween('permintaan_h.tgl_terima_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->where('permintaan_h.dari', $koderuangan)
                ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                ->groupBy('mutasi_gudangdepo.kd_obat')
                ->first();
            $mutasiKeluar = Mutasigudangkedepo::select(
                'mutasi_gudangdepo.kd_obat as kdobat',
                DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
            )
                ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                ->whereBetween('permintaan_h.tgl_kirim_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                ->where('permintaan_h.tujuan', $koderuangan)
                ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                ->groupBy('mutasi_gudangdepo.kd_obat')
                ->first();

            // jika bukan depo ok
            if ($koderuangan !== 'Gd-04010103') {

                $noresep = Resepkeluarrinci::select(
                    'resep_keluar_r.noresep',
                )
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                    ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('resep_keluar_h.depo', $koderuangan)
                    ->where('resep_keluar_r.kdobat', $kdobat)
                    ->pluck('resep_keluar_r.noresep');

                $resepKeluar = Resepkeluarrinci::select(
                    'resep_keluar_r.kdobat',
                    DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                )
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                    ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('resep_keluar_h.depo', $koderuangan)
                    ->where('resep_keluar_r.kdobat', $kdobat)
                    ->groupBy('resep_keluar_r.kdobat')
                    ->first();
                $retur = Returpenjualan_r::select(
                    'kdobat',
                    DB::raw('sum(jumlah_retur) as jumlah')
                )
                    ->whereIn('noresep', $noresep)
                    ->where('kdobat', $kdobat)
                    ->groupBy('kdobat')
                    ->first();

                $resepKeluarRacikan = Resepkeluarrinciracikan::select(
                    'resep_keluar_racikan_r.kdobat',
                    DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                )
                    ->join('resep_keluar_h', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                    ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('resep_keluar_h.depo', $koderuangan)
                    ->where('resep_keluar_racikan_r.kdobat', $kdobat)
                    ->groupBy('resep_keluar_racikan_r.kdobat')
                    ->first();
            } else {
                $noresep = PersiapanOperasiRinci::select(
                    'persiapan_operasi_rincis.noresep',
                )->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                    ->whereBetween('persiapan_operasis.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('persiapan_operasi_rincis.kd_obat', $kdobat)
                    ->groupBy('persiapan_operasi_rincis.noresep')
                    ->pluck('persiapan_operasi_rincis.noresep');

                $resepKeluar = Resepkeluarrinci::select(
                    'resep_keluar_r.kdobat',
                    DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                )
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                    ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('resep_keluar_h.depo', $koderuangan)
                    ->where('resep_keluar_r.kdobat', $kdobat)
                    ->whereNotIn('resep_keluar_h.noresep', $noresep)
                    ->groupBy('resep_keluar_r.kdobat')
                    ->first();
                $retur = Returpenjualan_r::select(
                    'kdobat',
                    DB::raw('sum(jumlah_retur) as jumlah')
                )
                    ->whereIn('noresep', $noresep)
                    ->where('kdobat', $kdobat)
                    ->groupBy('kdobat')
                    ->first();

                $resepKeluarRacikan = Resepkeluarrinciracikan::select(
                    'resep_keluar_racikan_r.kdobat',
                    DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                )
                    ->join('resep_keluar_h', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                    ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('resep_keluar_h.depo', $koderuangan)
                    ->where('resep_keluar_racikan_r.kdobat', $kdobat)
                    ->whereNotIn('resep_keluar_h.noresep', $noresep)
                    ->groupBy('resep_keluar_racikan_r.kdobat')
                    ->first();

                $persiapanOperasi = PersiapanOperasiRinci::select(
                    'persiapan_operasi_rincis.kd_obat',
                    DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as minta'),
                    DB::raw('sum(persiapan_operasi_rincis.jumlah_distribusi) as distribusi'),
                    DB::raw('sum(persiapan_operasi_rincis.jumlah_kembali) as kembali'),
                    DB::raw('sum(persiapan_operasi_rincis.jumlah_resep) as resep'),
                )->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                    ->whereBetween('persiapan_operasis.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('persiapan_operasi_rincis.kd_obat', $kdobat)
                    ->first();

                $persiapanOperasiDistribusi = PersiapanOperasiDistribusi::select(
                    'persiapan_operasi_distribusis.kd_obat',
                    DB::raw('sum(persiapan_operasi_distribusis.jumlah) as distribusi'),
                    DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as kembali'),
                )->join('persiapan_operasis', 'persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                    ->whereBetween('persiapan_operasis.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('persiapan_operasi_distribusis.kd_obat', $kdobat)
                    ->first();
            }



            $totalStok = FarmasinewStokreal::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                ->where('kdruang', $koderuangan)->first();

            $tts = $totalStok->jumlah ?? 0;
            $sal = $saldoAwal->jumlah ?? 0;
            $peny = $penyesuaian->jumlah ?? 0;
            $mutma = $mutasiMasuk->jumlah ?? 0;
            $ret = $retur->jumlah ?? 0;
            $kem = $persiapanOperasiDistribusi->kembali ?? 0;
            //keluar
            $dist = $persiapanOperasiDistribusi->distribusi ?? 0;
            $mutkel = $mutasiKeluar->jumlah ?? 0;
            $reskel = $resepKeluar->jumlah ?? 0;
            $reskelrac = $resepKeluarRacikan->jumlah ?? 0;
            if ($koderuangan === 'Gd-04010103') {
                $masuk = (int)$sal + (int)$peny + (int)$mutma + (int)$kem + (int) $ret;
                $keluar = (int)$mutkel + (int)$dist + (int)$reskel + (int)$reskelrac;
                $sisa = (int)$masuk - (int)$keluar;
                if ((int)$sisa != (int)$tts) {
                    // cek ketorolac
                    $masuk = $sisa;
                    $index = 0;
                    $stok = FarmasinewStokreal::where('kdobat', $kdobat)
                        ->where('kdruang', $koderuangan)
                        ->orderBy('tglexp', 'DESC')
                        ->orderBy('nodistribusi', 'DESC')
                        ->get();
                    foreach ($stok as $st) {
                        $st->update([
                            'jumlah' => 0
                        ]);
                    }
                    while ($masuk > 0) {
                        $distribusi = Mutasigudangkedepo::where('kd_obat', $stok[$index]['kdobat'])
                            ->where('no_permintaan', $stok[$index]['nodistribusi'])
                            ->where('nobatch', $stok[$index]['nobatch'])
                            ->orderBy('id', 'DESC') // jaga2 data double
                            ->first();
                        if ($distribusi) {
                            $ada = $distribusi->jml;
                            if ($ada > $masuk) {
                                $stok[$index]->update([
                                    'jumlah' => $masuk
                                ]);
                                $masuk = 0;
                            } else {
                                $sisax = $masuk - $ada;
                                $stok[$index]->update([
                                    'jumlah' => $ada
                                ]);
                                $masuk = $sisax;
                                $index += 1;
                            }
                        } else {
                            $distribusi2 = Mutasigudangkedepo::where('kd_obat', $stok[$index]['kdobat'])
                                ->where('no_permintaan', $stok[$index]['nodistribusi'])
                                ->orderBy('id', 'DESC') // jaga2 data double
                                ->first();
                            if ($distribusi2) {
                                $ada = $distribusi2->jml;
                                if ($ada > $masuk) {
                                    $stok[$index]->update([
                                        'jumlah' => $masuk
                                    ]);
                                    $masuk = 0;
                                } else {
                                    $sisax = $masuk - $ada;
                                    $stok[$index]->update([
                                        'jumlah' => $ada
                                    ]);
                                    $masuk = $sisax;
                                    $index += 1;
                                }
                            } else {
                                $stok[$index]->update([
                                    'jumlah' => $masuk
                                ]);
                                $masuk = 0;
                            }
                        }
                    }
                    $message = 'Cek Stok Depo Ok selesai, Stok sudah di update';
                }
            } else {
                $masuk = (int)$sal + (int)$peny + (int)$mutma + (int) $ret;
                $keluar = (int)$mutkel + (int)$reskel + (int)$reskelrac;
                $sisa = (int)$masuk - (int)$keluar;

                if ((int)$sisa != (int)$tts) {
                    // cek ketorolac
                    $masuk = $sisa;
                    $index = 0;
                    $stok = FarmasinewStokreal::where('kdobat', $kdobat)
                        ->where('kdruang', $koderuangan)
                        ->orderBy('tglexp', 'DESC')
                        ->orderBy('nodistribusi', 'DESC')
                        ->get();
                    foreach ($stok as $st) {
                        $st->update([
                            'jumlah' => 0
                        ]);
                    }
                    while ($masuk > 0) {
                        $distribusi = Mutasigudangkedepo::where('kd_obat', $stok[$index]['kdobat'])
                            ->where('no_permintaan', $stok[$index]['nodistribusi'])
                            ->where('nobatch', $stok[$index]['nobatch'])
                            ->orderBy('id', 'DESC') // jaga2 data double
                            ->first();
                        if ($distribusi) {
                            $ada = $distribusi->jml;
                            if ($ada > $masuk) {
                                $stok[$index]->update([
                                    'jumlah' => $masuk
                                ]);
                                $masuk = 0;
                            } else {
                                $sisax = $masuk - $ada;
                                $stok[$index]->update([
                                    'jumlah' => $ada
                                ]);
                                $masuk = $sisax;
                                $index += 1;
                            }
                        } else {
                            $distribusi2 = Mutasigudangkedepo::where('kd_obat', $stok[$index]['kdobat'])
                                ->where('no_permintaan', $stok[$index]['nodistribusi'])
                                ->orderBy('id', 'DESC') // jaga2 data double
                                ->first();
                            if ($distribusi2) {
                                $ada = $distribusi2->jml;
                                if ($ada > $masuk) {
                                    $stok[$index]->update([
                                        'jumlah' => $masuk
                                    ]);
                                    $masuk = 0;
                                } else {
                                    $sisax = $masuk - $ada;
                                    $stok[$index]->update([
                                        'jumlah' => $ada
                                    ]);
                                    $masuk = $sisax;
                                    $index += 1;
                                }
                            } else {
                                $stok[$index]->update([
                                    'jumlah' => $masuk
                                ]);
                                $masuk = 0;
                            }
                        }
                    }
                    $message = 'Cek Stok Depo selesai, Stok sudah di update';
                }
            }



            return [
                'saldoAwal' => $saldoAwal,
                'stokid' => $stokid,
                'penyesuaian' => $penyesuaian,
                'penerimaan' => 0,
                'mutasiMasuk' => $mutasiMasuk,
                'mutasiKeluar' => $mutasiKeluar,
                'noresep' => $noresep,
                'resepKeluar' => $resepKeluar,
                'retur' => $retur,
                'resepKeluarRacikan' => $resepKeluarRacikan,
                'persiapanOperasi' => $persiapanOperasi,
                'persiapanOperasiDistribusi' => $persiapanOperasiDistribusi,
                'tts' => $tts,
                'sal' => $sal,
                'peny' => $peny,
                'mutma' => $mutma,
                'ret' => $ret,
                'mutkel' => $mutkel,
                'reskel' => $reskel,
                'reskelrac' => $reskelrac,
                'kem' => $kem,
                'dist' => $dist,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'sisa' => $sisa,
                'message' => $message
            ];
        }
    }
    public function perbaikanStok(Request $request)
    {
        // $mapingGudang = [
        //     ['nama' => 'Gudang Farmasi ( Kamar Obat )', 'kode' => 'Gd-05010100', 'lama' => 'GU0001'],
        //     ['nama' => 'Gudang Farmasi (Floor Stok)', 'kode' => 'Gd-03010100', 'lama' => 'GU0002'],
        //     ['nama' => 'Floor Stock 1 (AKHP)', 'kode' => 'Gd-03010101', 'lama' => 'RC0001'],
        //     ['nama' => 'Depo Rawat inap', 'kode' => 'Gd-04010102', 'lama' => 'AP0002'],
        //     ['nama' => 'Depo OK', 'kode' => 'Gd-04010103', 'lama' => 'AP0005'],
        //     ['nama' => 'Depo Rawat Jalan', 'kode' => 'Gd-05010101', 'lama' => 'AP0001'],
        //     ['nama' => 'Depo IGD', 'kode' => 'Gd-02010104', 'lama' => 'AP0007']
        // ];
        // return new JsonResponse([
        //     // 'data' => $data,
        //     'message' => 'Cek Stok Untuk penenyesuaian sudan ditutup'
        // ], 410);
        $depo = $request->kdruang;
        // $forbid = ['Gd-05010100', 'Gd-03010100', 'Gd-04010103'];
        // if (in_array($depo, $forbid)) {

        //     return new JsonResponse([
        //         // 'data' => $data,
        //         'message' => 'Cek Stok Tidak Untuk Gudang dan atau Depo OK'
        //     ], 410);
        // }
        $obat = $request->kdobat;

        // CARI PENYESUAIAN
        $caristok = FarmasinewStokreal::where('kdobat', $obat)->where('kdruang', $depo)->get();
        $idtok = collect($caristok)->map(function ($st) {
            return $st->id;
        });
        $penye = PenyesuaianStok::whereIn('stokreal_id', $idtok)->sum('penyesuaian');
        $data['penyesuaian'] = $penye;

        $raw = self::kertuStok($depo, $obat);
        $col = collect($raw);

        $data['dataAll'] = $col;
        $data['awal'] = $col[0]->saldoawal[0]->jumlah ?? 0;
        // $data['stok_id'] = $col->map(function ($st) {
        //     return $st->stok->map(function ($an) {
        //         return $an->id;
        //     });
        // });
        $data['stok'] = $col->sum(function ($sa) {
            return $sa->stok->sum('jumlah');
        });
        $data['masuk'] = $col->sum(function ($sa) {
            return $sa->mutasimasuk->sum('jml');
        });
        $data['keluar'] = $col->sum(function ($sa) {
            return $sa->mutasikeluar->sum('jml');
        });
        $data['resep'] = $col->sum(function ($sa) {
            return $sa->resepkeluar->sum('jumlah');
        });
        $data['returres'] = $col->sum(function ($sa) {
            return $sa->resepkeluar->sum(function ($he) {
                return $he->retur->sum(function ($ri) {
                    return $ri->rinci->sum('jumlah_retur');
                });
            });
        });
        $data['racikan'] = $col->sum(function ($sa) {
            return $sa->resepkeluarracikan->sum('jumlah');
        });
        $data['returrrac'] = $col->sum(function ($sa) {
            return $sa->resepkeluarracikan->sum(function ($he) {
                return $he->retur->sum(function ($ri) {
                    return $ri->rinci->sum('jumlah_retur');
                });
            });
        });
        $data['operasidist'] = $col->sum(function ($sa) {
            return $sa->persiapanoperasiretur->sum('jumlah_distribusi');
        });
        $data['operasiret'] = $col->sum(function ($sa) {
            return $sa->persiapanoperasiretur->sum('jumlah_kembali');
        });
        $data['allmasuk'] = (int)$data['masuk'] + (int)$data['returres'] + (int)$data['returrrac'];
        $data['allkeluar'] = (int)$data['keluar'] + (int)$data['resep'] + (int)$data['racikan'];
        $data['op'] = (int)$data['operasidist'] - (int)$data['operasiret'];
        $data['awalandmas'] = (int)$data['awal'] + (int)$data['allmasuk'] + (int)$penye;
        if ($depo === 'Gd-04010103') {
            $data['akhir'] = (int)$data['awalandmas'] - (int)+(int)$data['allkeluar'] - (int)$data['op'];
        } else {
            $data['akhir'] = (int)$data['awalandmas'] - (int)+(int)$data['allkeluar'];
        }
        if ((int)$data['stok'] === (int)$data['akhir']) {
            return new JsonResponse(['message' => 'Data Sudah sesuai, tidak perlu penyesuaian']);
        }
        $stok = FarmasinewStokreal::where('kdobat', $obat)
            ->where('kdruang', $depo)->orderBy('tglexp', 'DESC')
            ->orderBy('nodistribusi', 'DESC')->get();
        $data['mutasiantar'] = [];

        $sisa = $data['akhir'];
        if (count($stok) > 0) {
            foreach ($stok as $st) {
                $distribusi = Mutasigudangkedepo::where('kd_obat', $st['kdobat'])->where('no_permintaan', $st['nodistribusi'])->first();
                $jmldist = $distribusi->jml ?? 0;
                if ($sisa > 0) {
                    if ($jmldist < $sisa) {
                        $st['jumlah'] =  $jmldist;
                        $st->save();
                        $sisa -=  $jmldist;
                    } else {
                        $st['jumlah'] = $sisa;
                        $st->save();
                        $sisa = 0;
                    }
                } else {
                    $st['jumlah'] = 0;
                    $st->save();
                }
                $data['mutasiantar'][] = $distribusi;
                // $data['mutasiantar'][] = $st['nodistribusi'];
            }
            $data['getStok'] = $stok;
        }

        return new JsonResponse([
            'data' => $data,
            'message' => 'Data Sudah disesuaikan'
        ]);
    }

    public static function kertuStok($koderuangan, $kdobat)
    {
        $koderuangan = $koderuangan;
        $bulan = date('m');
        $tahun = date('Y');
        // $bulan = request('bulan');
        // $tahun = request('tahun');
        $x = $tahun . '-' . $bulan;
        $tglAwal = $x . '-01';
        $tglAkhir = $x . '-31';
        $dateAwal = Carbon::parse($tglAwal);
        $dateAkhir = Carbon::parse($tglAkhir);
        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m-d');
        $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-d');


        $list = Mobatnew::query()
            ->select('kd_obat', 'nama_obat', 'satuan_k', 'satuan_b', 'id', 'flag', 'merk', 'kandungan')
            ->with([
                'saldoawal' => function ($saldo) use ($blnLaluAwal, $blnLaluAkhir, $koderuangan) {
                    $saldo->whereBetween('tglopname', [$blnLaluAwal, $blnLaluAkhir])
                        ->where('kdruang', $koderuangan)->select('tglopname', 'jumlah', 'kdobat');
                },
                'stok' => function ($st) use ($koderuangan) {
                    $st->select(
                        'id',
                        'kdobat',
                        'nopenerimaan',
                        'jumlah',
                        'kdruang',
                        'nodistribusi',
                    )
                        ->where('kdruang', $koderuangan);
                    // ->with('ssw:stokreal_id,penyesuaian');
                },
                // hanya ada jika koderuang itu adalah gudang
                'penerimaanrinci' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'penerimaan_r.kdobat as kdobat',
                        'penerimaan_r.jml_all_penerimaan as jml_all_penerimaan',
                        'penerimaan_r.jml_terima_b as jml_terima_b',
                        'penerimaan_r.jml_terima_k as jml_terima_k',
                        'penerimaan_h.nopenerimaan as nopenerimaan',
                        'penerimaan_h.tglpenerimaan as tglpenerimaan',
                        'penerimaan_h.gudang as gudang',
                        'penerimaan_h.jenissurat as jenissurat',
                        'penerimaan_h.jenis_penerimaan as jenis_penerimaan',
                        'penerimaan_h.kunci as kunci',

                    )
                        ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                        // ->join('sigarang.gudangs as gudangs', 'penerimaan_h.gudang', '=', 'gudangs.kode')
                        ->whereBetween('penerimaan_h.tglpenerimaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('penerimaan_h.gudang', $koderuangan);
                },


                // mutasi masuk baik dari gudang, ataupun depo termasuk didalamnya mutasi antar depo dan antar gudang÷
                'mutasimasuk' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {

                    $q->select(
                        'mutasi_gudangdepo.kd_obat as kd_obat',
                        'mutasi_gudangdepo.jml as jml',
                        'mutasi_gudangdepo.no_permintaan as no_permintaan',
                        'permintaan_h.tgl_permintaan as tgl_permintaan',
                        'permintaan_h.tujuan as tujuan',
                        'permintaan_h.dari as dari',
                    )
                        ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_terima_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('dari', $koderuangan);
                },


                // mutasi keluar baik ke gudang(mutasi antar gudang), ataupun ke depo dan juga ke ruangan
                'mutasikeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {

                    $q->select(
                        'mutasi_gudangdepo.kd_obat as kd_obat',
                        'mutasi_gudangdepo.jml as jml',
                        'mutasi_gudangdepo.no_permintaan as no_permintaan',
                        'permintaan_h.tgl_permintaan as tgl_permintaan',
                        'permintaan_h.tujuan as tujuan',
                        'permintaan_h.dari as dari',
                    )
                        ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_kirim_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('tujuan', $koderuangan);
                },

                'resepkeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan, $kdobat) {
                    $q->select(
                        'resep_keluar_h.depo',
                        'resep_keluar_r.noresep',
                        'resep_keluar_r.kdobat',
                        'resep_keluar_r.nopenerimaan',
                        'resep_keluar_r.jumlah',
                    )
                        ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                        ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)

                        ->with([
                            'retur' => function ($ret) use ($kdobat) {
                                $ret->select(
                                    'noretur',
                                    'noresep',
                                )
                                    ->with([
                                        'rinci' => function ($ri) use ($kdobat) {
                                            $ri->select(
                                                'noretur',
                                                'kdobat',
                                                'jumlah_retur',
                                            )
                                                ->where('kdobat', $kdobat);
                                        }
                                    ]);
                            }
                        ]);
                },

                'resepkeluarracikan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan, $kdobat) {
                    $q->select(
                        'resep_keluar_h.depo',
                        'resep_keluar_racikan_r.noresep',
                        'resep_keluar_racikan_r.kdobat',
                        'resep_keluar_racikan_r.nopenerimaan',
                        'resep_keluar_racikan_r.jumlah',
                    )
                        ->join('resep_keluar_h', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)

                        ->with([
                            'retur' => function ($ret) use ($kdobat) {
                                $ret->select(
                                    'noretur',
                                    'noresep',
                                )
                                    ->with([
                                        'rinci' => function ($ri) use ($kdobat) {
                                            $ri->select(
                                                'noretur',
                                                'kdobat',
                                                'jumlah_retur',
                                            )
                                                ->where('kdobat', $kdobat);
                                        }
                                    ]);
                            }
                        ]);
                },

                // ini jika $koderuangan = Gd-04010103 (Depo OK) ini nanti di front end
                'persiapanoperasiretur' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'persiapan_operasi_rincis.kd_obat',
                        'persiapan_operasi_rincis.jumlah_distribusi',
                        'persiapan_operasi_rincis.jumlah_kembali'
                    )->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                        ->whereBetween('persiapan_operasis.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                },
                // ini jika $koderuangan = Gd-04010103 (Depo OK)
                // ini keluarnya nanti jumlah_distribusi harus dikurangi jumlah_resep karena resep nanti akan di ambil juga
                // 'persiapanoperasikeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                //     $q->select(
                //         'persiapan_operasi_rincis.kd_obat',
                //         'persiapan_operasi_rincis.jumlah_kembali',
                //     )->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                //         ->whereBetween('persiapan_operasis.tgl_distribusi', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                // },
                // 'returpenjualan'

            ])

            ->orderBy('id', 'asc')
            ->where('flag', '')
            ->where('kd_obat', $kdobat)
            ->get();

        return $list;
    }
}
