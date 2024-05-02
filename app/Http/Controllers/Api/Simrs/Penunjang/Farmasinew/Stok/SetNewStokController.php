<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasi\MapingObat;
use App\Models\Simrs\Penunjang\Farmasi\StokOpname;
use App\Models\Simrs\Penunjang\Farmasi\StokReal;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname as StokStokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal as FarmasinewStokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SetNewStokController extends Controller
{

    public function setNewStok()
    {
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
                    'tglpenerimaan' => $key['rincipenerimaan']['tanggal'] ?? date('Y-m-d H:i:s'),
                    'kdobat' => $key['obatbaru'],
                    'jumlah' => $st['rs2'],
                    'kdruang' => $item[$anu],
                    'harga' => $key['master']['rs4'],
                    'tglexp' => $key['rincipenerimaan']['rs7'] ?? null,
                    'nobatch' => $key['rincipenerimaan']['rs6'] ?? '',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $newStok[] = $temp;
            }
        }

        if (count($newStok) <= 0) {
            return new JsonResponse($newStok);
        }

        FarmasinewStokreal::truncate();
        foreach (array_chunk($newStok, 1000) as $t) {
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
        $data['har'] = $this->cekHargaGud();

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
        // insert harga
        $harga = [];
        $allGud = ['Gd-05010100', 'Gd-03010100'];
        $obAllDep = FarmasinewStokreal::whereIn('kdruang', $allGud)->groupBy('kdobat')->get();

        if (count($obAllDep) > 0) {
            foreach ($obAllDep as $key) {

                if ((float)$key['harga'] > 0) {
                    $tHarga['nopenerimaan'] = $key['nopenerimaan'];
                    $tHarga['kd_obat'] = $key['kdobat'];
                    $tHarga['harga'] = $key['harga'];
                    $tHarga['tgl_mulai_berlaku'] = date('Y-m-d H:i:s');
                    $tHarga['created_at'] = date('Y-m-d H:i:s');
                    $tHarga['updated_at'] = date('Y-m-d H:i:s');
                    $harga[] = $tHarga;
                }
            }
        }
        if (count($harga) <= 0) {
            return [
                'stok' => $stok,
                'harga' => false,
                'data' => $data ?? false,
            ];
        }
        DaftarHarga::truncate();
        foreach (array_chunk($harga, 1000) as $t) {
            $dataHarga = DaftarHarga::insert($t);
        }
        return [
            'stok' => $stok,
            'harga' => $harga,
            'data' => $data ?? false,
            'data harga' => $dataHarga ?? false,
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
            foreach (array_chunk($newOpname, 1000) as $t) {
                $data['ins'] = StokStokopname::insert($t);
            }
        }
        // $data['mapingObat'] = $mapingObat;
        $data['newOpname'] = $newOpname;
        return new JsonResponse($data);
    }
}
