<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasi\MapingObat;
use App\Models\Simrs\Penunjang\Farmasi\StokOpname;
use App\Models\Simrs\Penunjang\Farmasi\StokReal;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\BarangRusak;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\ReturGudangDetail;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_r;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\PenyesuaianStok;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname as StokStokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
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
        $gudBaru = ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];

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

    public function PerbaikanStokPerDepo(Request $request)
    {
        $depo = $request->kdruang;
        // $obat = $request->kdobat;
        $stok = Stokrel::select('kdobat')
            ->where('kdruang', $depo)
            ->distinct('kdobat')
            ->orderBy('kdobat', 'ASC')
            ->pluck('kdobat');
        $data = [];
        foreach ($stok as $obat) {
            // return new JsonResponse($obat);

            $temp = self::getDataTrans($depo, $obat);
            if ($temp['tts'] !== $temp['sisa']) $data[] = $temp;
        }

        return new JsonResponse([
            'count data' => sizeof($data),
            'data' => $data
        ]);
    }
    public function cekPenerimaan(Request $request)
    {
        $gudangs = ['Gd-05010100', 'Gd-03010100'];
        if (!in_array($request->kdruang, $gudangs)) {
            return new JsonResponse([
                'message' => 'Yang bisa cek penerimaan hanya gudang',
            ], 410);
        }

        $penrimaanrinci = PenerimaanRinci::where('kdobat', $request->kdobat)->get();
        $nope = PenerimaanRinci::where('kdobat', $request->kdobat)->distinct('nopenerimaan')->pluck('nopenerimaan');
        $stok = FarmasinewStokreal::whereIn('nopenerimaan', $nope)
            ->where('kdobat', $request->kdobat)
            ->where('kdruang', $request->kdruang)
            ->get();
        $noba = $stok->pluck('nobatch')->toArray();
        $tgl = $stok[0]->tglpenerimaan ?? null;
        $da = [];
        $msg = 'Tidak Ditemukan data penerimaan yang membutuhkan perubahan';
        if (count($penrimaanrinci) !== count($stok)) {
            foreach ($penrimaanrinci as $key) {
                if (!in_array($key['no_batch'], $noba)) {
                    // $da[]=$key;
                    FarmasinewStokreal::updateOrCreate(
                        [
                            'nopenerimaan' => $key['nopenerimaan'],
                            'kdobat' => $key['kdobat'],
                            'kdruang' => $request->kdruang,
                            'nobatch' => $key['no_batch'],
                        ],
                        [
                            'tglexp' => $key['tgl_exp'],
                            'harga' => $key['harga_netto_kecil'],
                            'tglpenerimaan' => $tgl,
                            'jumlah' => 0,
                            'flag' => ''

                        ]
                    );
                    $msg = 'Ada Penambahan Penerimaan';
                }
            }
        }


        return new JsonResponse([
            'message' => $msg,
            'penrimaanrinci' => $penrimaanrinci,
            'nope' => $nope,
            'stok' => $stok,
            'noba' => $noba,
            'da' => $da,
        ]);
    }
    public function newPerbaikanStok(Request $request)
    {
        $depo = $request->kdruang;
        $obat = $request->kdobat;
        $data = self::getDataTrans($depo, $obat);


        return new JsonResponse($data['data'], $data['status']);
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

        try {
            DB::connection('farmasi')->beginTransaction();
            $data = [];
            $gudangs = ['Gd-05010100', 'Gd-03010100'];
            $depos = ['Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
            $koderuangan = $koderuangan;
            $bulan = date('m');
            $tahun = date('Y');
            // $bulan = request('bulan');
            // $tahun = request('tahun');
            $x = $tahun . '-' . $bulan;
            $tglAwal = $x . '-01';
            $tglAkhir = $x . date('-t', strtotime($x . '-01'));
            $dateAwal = Carbon::parse($tglAwal);
            $dateAkhir = Carbon::parse($tglAkhir);
            $blnLaluAwal = $dateAwal->subMonth()->format('Y-m');
            $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-t');

            $message = 'Stok sudah Sesuai tidak ada yang perlu di update';
            if (in_array($koderuangan, $gudangs)) {
                $saldoAwalRinci = StokStokopname::select('tglopname', 'nopenerimaan', 'kdobat', DB::raw('sum(jumlah) as total'))
                    // ->whereBetween('tglopname', [$blnLaluAwal . ' 00:00:00', $blnLaluAkhir . ' 23:59:59'])
                    ->where('tglopname', 'LIKE', $blnLaluAwal . '%')
                    ->where('kdruang', $koderuangan)
                    ->where('kdobat', $kdobat)
                    ->groupBy('nopenerimaan', 'tglopname', 'kdruang', 'kdobat')
                    ->get();
                $saldoAwal = collect($saldoAwalRinci)->sum('total');
                $stokid = FarmasinewStokreal::select('id')->where('kdruang', $koderuangan)
                    ->where('kdobat', $kdobat)
                    ->pluck('id');
                $penyesuaianRinci = PenyesuaianStok::select('stokreal_id', 'nopenerimaan', DB::raw('sum(penyesuaian) as jumlah'))
                    ->whereIn('stokreal_id', $stokid)
                    ->whereBetween('tgl_penyesuaian', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->groupBy('stokreal_id', 'nopenerimaan')
                    ->get();
                $penyesuaian = collect($penyesuaianRinci)->sum('jumlah');
                $penerimaanRinci = PenerimaanRinci::select(
                    'penerimaan_r.kdobat',
                    'penerimaan_r.nopenerimaan',
                    DB::raw('sum(jml_terima_k) as jumlah')
                )
                    ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                    ->whereBetween('penerimaan_h.tglpenerimaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('penerimaan_h.gudang', $koderuangan)
                    ->where('penerimaan_h.kunci', '1')
                    ->where('penerimaan_r.kdobat', $kdobat)
                    ->groupBy('penerimaan_r.nopenerimaan', 'penerimaan_r.kdobat')
                    ->get();
                $penerimaan = collect($penerimaanRinci)->sum('jumlah');

                $mutasiMasukRinci = Mutasigudangkedepo::select(
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
                )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->whereBetween('permintaan_h.tgl_terima_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('permintaan_h.dari', $koderuangan)
                    ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                    ->groupBy('mutasi_gudangdepo.nopenerimaan', 'mutasi_gudangdepo.kd_obat')
                    ->get();
                $mutasiMasuk = collect($mutasiMasukRinci)->sum('jumlah');

                $mutasiKeluarRinci = Mutasigudangkedepo::select(
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
                )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->whereBetween('permintaan_h.tgl_kirim_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('permintaan_h.tujuan', $koderuangan)
                    ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                    ->groupBy('mutasi_gudangdepo.nopenerimaan', 'mutasi_gudangdepo.kd_obat')
                    ->get();
                $mutasiKeluar = collect($mutasiKeluarRinci)->sum('jumlah');

                $rusakRinci = BarangRusak::select(
                    'kd_obat',
                    'nopenerimaan',
                    DB::raw('sum(jumlah) as jumlah')
                )
                    ->whereBetween('tgl_rusak', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('kd_obat', $kdobat)
                    ->where('kunci', '1')
                    ->groupBy('kd_obat', 'nopenerimaan')
                    ->get();
                $rusak = collect($rusakRinci)->sum('jumlah');

                $returGudangRinci = ReturGudangDetail::select(
                    'retur_gudang_details.kd_obat',
                    'retur_gudang_details.nopenerimaan',
                    DB::raw('sum(retur_gudang_details.jumlah_retur) as jumlah')
                )
                    ->leftJoin('retur_gudangs', 'retur_gudangs.no_retur', '=', 'retur_gudang_details.no_retur')
                    ->where('retur_gudangs.gudang', $koderuangan)
                    ->whereBetween('retur_gudangs.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('retur_gudang_details.kd_obat', $kdobat)
                    ->where('retur_gudangs.kunci', '1')
                    ->groupBy('retur_gudang_details.nopenerimaan', 'retur_gudang_details.kd_obat', 'retur_gudangs.gudang')
                    ->get();
                $returGudang = collect($returGudangRinci)->sum('jumlah');

                $totalStok = FarmasinewStokreal::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                    ->where('kdruang', $koderuangan)->first();
                $tts = $totalStok->jumlah ?? 0;
                $sal = $saldoAwal ?? 0;
                $peny = $penyesuaian ?? 0;
                $trm = $penerimaan ?? 0;
                $mutma = $mutasiMasuk ?? 0;
                $mutkel = $mutasiKeluar ?? 0;
                $rus = $rusak ?? 0;
                $retG = $returGudang ?? 0;
                $masuk = (float)$sal + (float)$peny + (float)$trm + (float)$mutma + (float)$retG;
                $keluar = (float)$mutkel + (float)$rus;
                $sisa = (float)$masuk - (float)$keluar;

                // cek rincian
                $stok = FarmasinewStokreal::lockForUpdate()
                    ->where('kdobat', $kdobat)
                    ->where('kdruang', $koderuangan)
                    ->orderBy('tglexp', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->get();
                $nopeSt = [];
                foreach ($saldoAwalRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($penerimaanRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($mutasiMasukRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($mutasiKeluarRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($rusakRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($returGudangRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($penerimaanRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                $uniNopeSt = array_unique($nopeSt);

                // return [
                //     'stok' => $stok,
                //     'nope' => $nope,
                //     'uniNope' => $uniNope,
                //     'penerimaanRinci' => $penerimaanRinci,
                //     'mutasiKeluarRinci' => $mutasiKeluarRinci,
                //     'saldoAwalRinci' => $saldoAwalRinci,
                //     'tts' => $tts,
                //     'sisa' => $sisa,
                // ];
                if ((float)$sisa != (float)$tts) {
                    $ada = $sisa;
                    $index = 0;
                    $tolalIndex = count($stok) - 1;
                    // // nolkan semua stok
                    foreach ($stok as $st) {
                        $st->update([
                            'jumlah' => 0
                        ]);
                    }
                    $ret = [];
                    if ($ada > 0) {
                        foreach ($uniNopeSt as $key) {
                            $salAwal =  collect($saldoAwalRinci)->firstWhere('nopenerimaan', $key)->total ?? 0;
                            $mutMas =  collect($mutasiMasukRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                            $trm =  collect($penerimaanRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                            $retGu =  collect($returGudangRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                            $peny =  collect($penerimaanRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                            // keluar
                            $mutKel =  collect($mutasiKeluarRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                            $rus =  collect($rusakRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;

                            $maSuk = (float) $salAwal + (float) $mutMas + (float) $peny + (float) $retGu;
                            $keLuar = (float)$mutKel + (float)$rus;
                            $sisanya = $maSuk - $keLuar;

                            if ($sisanya > 0) {
                                // $temp = $anuaad + $sisanya;
                                // $anuaad = $temp;
                                $stokNya = collect($stok)->firstWhere('nopenerimaan', $key);
                                if ($stokNya) {
                                    if ((float)$sisanya >= (float)$ada) {
                                        $sisaJumlah = 0;
                                        $stokNya->update(['jumlah' => $ada]);
                                    } else if ((float)$ada > 0) {
                                        $sisaJumlah = (float)$ada - (float) $sisanya;
                                        $stokNya->update(['jumlah' => $sisanya]);
                                    }
                                    $ada = $sisaJumlah;
                                } else {
                                    return [
                                        'data' => [
                                            'message' => 'stok dengan nomor penerimaan ' . $key . ' tidak ditemukan'
                                        ],
                                        'status' => 410
                                    ];
                                }
                            }
                            // $tmpmas = $anumas + $maSuk;
                            // $anumas = $tmpmas;
                            // $tmpkel = $anukel + $keLuar;
                            // $anumas = $tmpkel;

                        }
                        // masuk
                        // if (sizeof($penerimaanRinci) > 0) {
                        //     foreach ($penerimaanRinci as $trm) {
                        //         $mutKelNya = collect($mutasiKeluarRinci)
                        //             ->where('kdobat', $trm->kdobat)
                        //             ->where('nopenerimaan', $trm->nopenerimaan)
                        //             ->first();
                        //         // error jumlah of non object
                        //         $mu =  0;
                        //         if ($mutKelNya) $mu = (float)$mutKelNya->jumlah;
                        //         $sisaTrm = (float)$trm->jumlah - $mu;
                        //         $sisaTrm = (float)$trm->jumlah - $mu;
                        //         if ($sisaTrm > 0) {
                        //             $stokNya = collect($stok)
                        //                 ->where('kdobat', $trm->kdobat)
                        //                 ->where('nopenerimaan', $trm->nopenerimaan)
                        //                 ->first();
                        //             if ((float)$sisaTrm >= (float)$ada) {
                        //                 $sisaJumlah = 0;
                        //                 $stokNya->update(['jumlah' => $ada]);
                        //             } else if ((float)$ada > 0) {
                        //                 $sisaJumlah = (float)$ada - (float) $sisaTrm;
                        //                 $stokNya->update(['jumlah' => $sisaTrm]);
                        //             }

                        //             $ada = $sisaJumlah;
                        //         }
                        //         $temp = [
                        //             'mutKelNya' => $mutKelNya ?? [],
                        //             'trm' => $trm ?? null,
                        //             'sisaTrm' => $sisaTrm ?? 0,
                        //             'stokNya' => $stokNya ?? null,
                        //             'sisaJumlah' => $sisaJumlah ?? null,
                        //             'ada' => $ada ?? null,
                        //             // 'saldoAwalRinci' => $saldoAwalRinci,
                        //         ];
                        //         $ret[] = $temp;
                        //     }
                        // }
                        // foreach ($saldoAwalRinci as $awl) {
                        //     $mutKelNya = collect($mutasiKeluarRinci)
                        //         ->where('kdobat', $awl->kdobat)
                        //         ->where('nopenerimaan', $awl->nopenerimaan)
                        //         ->first();
                        //     $mu =  0;
                        //     if ($mutKelNya) $mu = (float)$mutKelNya->jumlah;
                        //     $sisaTrm = (float)$trm->jumlah - $mu;
                        //     if ($sisaTrm > 0) {
                        //         $stokNya = collect($stok)
                        //             ->where('kdobat', $awl->kdobat)
                        //             ->where('nopenerimaan', $awl->nopenerimaan)
                        //             ->first();
                        //         if ((float)$sisaTrm >= (float)$ada) {
                        //             $sisaJumlah = 0;
                        //             $stokNya->update(['jumlah' => $ada]);
                        //         } else if ((float)$ada > 0) {
                        //             $sisaJumlah = (float)$ada - (float) $sisaTrm;
                        //             $stokNya->update(['jumlah' => $sisaTrm]);
                        //         }

                        //         $ada = $sisaJumlah;
                        //     }
                        //     $temp = [
                        //         'mutKelNya' => $mutKelNya ?? [],
                        //         'awl' => $awl ?? null,
                        //         'sisaTrm' => $sisaTrm ?? 0,
                        //         'stokNya' => $stokNya ?? null,
                        //         'sisaJumlah' => $sisaJumlah ?? null,
                        //         'ada' => $ada ?? null,
                        //         // 'saldoAwalRinci' => $saldoAwalRinci,
                        //     ];
                        //     $ret[] = $temp;
                        // }
                        $message = 'Cek Stok Gudang selesai, Stok sudah di update';
                    }

                    // $temp = [
                    //     'mutKelNya' => $mutKelNya ?? [],
                    //     'trm' => $trm ?? null,
                    //     'sisaTrm' => $sisaTrm ?? 0,
                    //     'stokNya' => $stokNya ?? null,
                    //     'sisaJumlah' => $sisaJumlah ?? null,
                    //     'saldoAwalRinci' => $saldoAwalRinci,
                    // ];
                    // $ret[] = $temp;


                    if ($sisa == 0) {
                        foreach ($stok as $st) {
                            $st->update([
                                'jumlah' => $sisa
                            ]);
                        }
                        $message = 'Cek Stok Gudang selesai, Stok Habis';
                    }
                    if ($sisa < 0) {
                        $message = 'Sisa Stok kurang dari 0, Stok Tidak diganti silahkan cek transaksi';
                    }
                }


                $data = [
                    'saldoAwal' => $saldoAwal ?? [],
                    'stokid' => $stokid,
                    'penyesuaian' => $penyesuaian,
                    'penerimaan' => $penerimaan,
                    'mutasiMasuk' => $mutasiMasuk,
                    'mutasiKeluar' => $mutasiKeluar,
                    'totalStok' => $totalStok,
                    'masuk' => $masuk,
                    'keluar' => $keluar,

                    'stok' => $stok ?? [],
                    'ret' => $ret ?? [],

                    'uniNope' => $uniNopeSt,
                    'penerimaanRinci' => $penerimaanRinci,
                    'mutasiKeluarRinci' => $mutasiKeluarRinci,
                    'mutasiMasukRinci' => $mutasiMasukRinci,
                    'saldoAwalRinci' => $saldoAwalRinci,
                    'returGudangRinci' => $returGudangRinci,

                    'tts' => $tts,
                    'sisa' => $sisa,
                    'sal' => $sal,
                    'peny' => $peny,
                    'trm' => $trm,
                    'mutma' => $mutma,
                    'mutkel' => $mutkel,
                    'rus' => $rus,
                    'retG' => $retG,
                    // 'stok' => $stok ?? [],
                    'message' => $message
                ];
            } else {
                /*
             * harus memetakan mutasi masuk dan mutasi keluar berdasarkan
             * kode obat, momor penerimaan, dan kalo bisa nomor batch, tgl exp dan harga
             */

                $saldoAwalDepoRinci = StokStokopname::select(
                    'tglopname',
                    'nopenerimaan',
                    'kdobat',
                    'nobatch',
                    'harga',
                    DB::raw('sum(jumlah) as total')
                )
                    // ->whereBetween('tglopname', [$blnLaluAwal . ' 00:00:00', $blnLaluAkhir . ' 23:59:59'])
                    ->where('tglopname', 'LIKE', $blnLaluAwal . '%')
                    ->where('kdruang', $koderuangan)
                    ->where('kdobat', $kdobat)
                    ->groupBy('nopenerimaan', 'tglopname', 'kdruang', 'kdobat')
                    ->get();

                $saldoAwal = collect($saldoAwalDepoRinci)->sum('total');

                $stokid = FarmasinewStokreal::select('id')->where('kdruang', $koderuangan)
                    ->where('kdobat', $kdobat)
                    ->pluck('id');
                $penyesuaian = PenyesuaianStok::select('stokreal_id', DB::raw('sum(penyesuaian) as jumlah'))
                    ->whereIn('stokreal_id', $stokid)
                    // ->whereNull('flag') // local only
                    ->whereBetween('tgl_penyesuaian', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->groupBy('stokreal_id')
                    ->first();

                $mutasiMasukDepoRinci = Mutasigudangkedepo::select(
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    'mutasi_gudangdepo.nobatch',
                    'mutasi_gudangdepo.tglexp',
                    'mutasi_gudangdepo.no_permintaan',
                    'permintaan_h.tgl_terima_depo',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
                )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->whereBetween('permintaan_h.tgl_terima_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('permintaan_h.dari', $koderuangan)
                    ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                    ->groupBy(
                        'mutasi_gudangdepo.kd_obat',
                        'mutasi_gudangdepo.nopenerimaan',
                    )
                    ->orderby('permintaan_h.tgl_terima_depo', 'DESC')
                    ->get();
                $mutasiMasuk = collect($mutasiMasukDepoRinci)->sum('jumlah');

                $mutasiKeluarDepoRinci = Mutasigudangkedepo::select(
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    'mutasi_gudangdepo.nobatch',
                    'mutasi_gudangdepo.tglexp',
                    'mutasi_gudangdepo.no_permintaan',
                    'permintaan_h.tgl_kirim_depo',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
                )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->whereBetween('permintaan_h.tgl_kirim_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('permintaan_h.tujuan', $koderuangan)
                    ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                    ->groupBy(
                        'mutasi_gudangdepo.kd_obat',
                        'mutasi_gudangdepo.nopenerimaan',
                    )
                    ->get();
                $mutasiKeluar = collect($mutasiKeluarDepoRinci)->sum('jumlah');
                // // jika bukan depo ok
                if ($koderuangan !== 'Gd-04010103') {

                    //     // $noresep = Resepkeluarrinci::select(
                    //     //     'resep_keluar_r.noresep',
                    //     // )
                    //     //     ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                    //     //     ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    //     //     ->where('resep_keluar_h.depo', $koderuangan)
                    //     //     ->where('resep_keluar_r.kdobat', $kdobat)
                    //     //     ->pluck('resep_keluar_r.noresep');

                    $resepKeluarRinci = Resepkeluarrinci::select(
                        'resep_keluar_r.kdobat',
                        'resep_keluar_r.nopenerimaan',
                        DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_r.kdobat', $kdobat)
                        ->where('resep_keluar_r.jumlah', '>', 0)
                        ->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan')
                        ->get();
                    $resepKeluar = collect($resepKeluarRinci)->sum('jumlah');
                    $returRinci = Returpenjualan_r::select(
                        'retur_penjualan_r.kdobat',
                        'retur_penjualan_r.nopenerimaan',
                        DB::raw('sum(retur_penjualan_r.jumlah_retur) as jumlah')
                    )
                        ->join('retur_penjualan_h', 'retur_penjualan_r.noretur', '=', 'retur_penjualan_h.noretur')
                        ->join('resep_keluar_h', 'retur_penjualan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('retur_penjualan_h.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        // ->whereIn('noresep', $noresep)
                        ->where('retur_penjualan_r.kdobat', $kdobat)
                        ->groupBy('retur_penjualan_r.kdobat', 'retur_penjualan_r.nopenerimaan')
                        ->get();
                    $retur = collect($returRinci)->sum('jumlah');

                    $resepKeluarRacikanRinci = Resepkeluarrinciracikan::select(
                        'resep_keluar_racikan_r.kdobat',
                        'resep_keluar_racikan_r.nopenerimaan',
                        DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_racikan_r.kdobat', $kdobat)
                        ->groupBy('resep_keluar_racikan_r.kdobat', 'resep_keluar_racikan_r.nopenerimaan')
                        ->get();
                    $resepKeluarRacikan = collect($resepKeluarRacikanRinci)->sum('jumlah');
                } else {
                    $noresep = PersiapanOperasiRinci::select(
                        'persiapan_operasi_rincis.noresep',
                    )->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                        ->whereBetween('persiapan_operasis.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('persiapan_operasi_rincis.kd_obat', $kdobat)
                        ->groupBy('persiapan_operasi_rincis.noresep')
                        ->pluck('persiapan_operasi_rincis.noresep');

                    $resepKeluarRinci = Resepkeluarrinci::select(
                        'resep_keluar_r.kdobat',
                        'resep_keluar_r.nopenerimaan',
                        DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                        ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_r.kdobat', $kdobat)
                        ->whereNotIn('resep_keluar_h.noresep', $noresep)
                        ->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan')
                        ->get();
                    $resepKeluar = collect($resepKeluarRinci)->sum('jumlah');


                    $returRinci = Returpenjualan_r::select(
                        'retur_penjualan_r.kdobat',
                        'retur_penjualan_r.nopenerimaan',
                        DB::raw('sum(retur_penjualan_r.jumlah_retur) as jumlah')
                    )
                        ->join('retur_penjualan_h', 'retur_penjualan_r.noretur', '=', 'retur_penjualan_h.noretur')
                        ->join('resep_keluar_h', 'retur_penjualan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('retur_penjualan_h.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)

                        ->where('retur_penjualan_r.kdobat', $kdobat)
                        ->groupBy('retur_penjualan_r.kdobat', 'retur_penjualan_r.nopenerimaan')
                        ->get();
                    $retur = collect($returRinci)->sum('jumlah');

                    $resepKeluarRacikanRinci = Resepkeluarrinciracikan::select(
                        'resep_keluar_racikan_r.kdobat',
                        'resep_keluar_racikan_r.nopenerimaan',
                        DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_racikan_r.kdobat', $kdobat)
                        ->whereNotIn('resep_keluar_h.noresep', $noresep)
                        ->groupBy('resep_keluar_racikan_r.kdobat', 'resep_keluar_racikan_r.nopenerimaan')
                        ->get();
                    $resepKeluarRacikan = collect($resepKeluarRacikanRinci)->sum('jumlah');

                    //     // $persiapanOperasi = PersiapanOperasiRinci::select(
                    //     //     'persiapan_operasi_rincis.kd_obat',
                    //     //     DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as minta'),
                    //     //     DB::raw('sum(persiapan_operasi_rincis.jumlah_distribusi) as distribusi'),
                    //     //     DB::raw('sum(persiapan_operasi_rincis.jumlah_kembali) as kembali'),
                    //     //     DB::raw('sum(persiapan_operasi_rincis.jumlah_resep) as resep'),
                    //     // )->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                    //     //     ->whereBetween('persiapan_operasis.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    //     //     ->where('persiapan_operasi_rincis.kd_obat', $kdobat)
                    //     //     ->first();

                    $persiapanOperasiDistribusiRinci = PersiapanOperasiDistribusi::select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasi_distribusis.nopenerimaan',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah) as distribusi'),
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as kembali'),
                    )
                        ->join('persiapan_operasis', 'persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                        ->whereBetween('persiapan_operasis.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('persiapan_operasi_distribusis.kd_obat', $kdobat)
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])
                        ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopenerimaan')
                        ->get();
                    $distribusiOk = collect($persiapanOperasiDistribusiRinci)->sum('distribusi');
                    $kembaliOk = collect($persiapanOperasiDistribusiRinci)->sum('kembali');

                    foreach ($persiapanOperasiDistribusiRinci as $key) {
                        $rawNoper[] = $key->nopenerimaan;
                    }
                }

                // retur gudang

                $returGudangRinci = ReturGudangDetail::select(
                    'retur_gudang_details.kd_obat',
                    'retur_gudang_details.nopenerimaan',
                    DB::raw('sum(retur_gudang_details.jumlah_retur) as jumlah')
                )
                    ->leftJoin('retur_gudangs', 'retur_gudangs.no_retur', '=', 'retur_gudang_details.no_retur')
                    ->where('retur_gudangs.depo', $koderuangan)
                    ->whereBetween('retur_gudangs.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('retur_gudang_details.kd_obat', $kdobat)
                    ->where('retur_gudangs.kunci', '1')
                    ->groupBy('retur_gudang_details.kd_obat', 'retur_gudangs.depo', 'retur_gudang_details.nopenerimaan')
                    ->get();
                $returGudang = collect($returGudangRinci)->sum('jumlah');
                $rawNoper = [];
                foreach ($saldoAwalDepoRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($mutasiMasukDepoRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($mutasiKeluarDepoRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($resepKeluarRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($returRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($resepKeluarRacikanRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                // sudut pandang foreach
                $noper = array_unique($rawNoper);

                $totalStok = FarmasinewStokreal::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                    ->where('kdruang', $koderuangan)->first();

                $tts = $totalStok->jumlah ?? 0;
                $sal = $saldoAwal ?? 0;
                $peny = $penyesuaian->jumlah ?? 0;
                $mutma = $mutasiMasuk ?? 0;
                $ret = $retur ?? 0;
                $kem = $kembaliOk ?? 0;
                //keluar
                $dist = $distribusiOk ?? 0;
                $mutkel = $mutasiKeluar ?? 0;
                $reskel = $resepKeluar ?? 0;
                $reskelrac = $resepKeluarRacikan ?? 0;
                $retG = $returGudang ?? 0;



                // return [
                //     'saldoAwalDepoRinci' => $saldoAwalDepoRinci,
                //     'mutasiMasukDepoRinci' => $mutasiMasukDepoRinci,
                //     'mutasiKeluarDepoRinci' => $mutasiKeluarDepoRinci,
                //     'resepKeluarRinci' => $resepKeluarRinci,
                //     'returRinci' => $returRinci,
                //     'resepKeluarRacikanRinci' => $resepKeluarRacikanRinci,
                //     'persiapanOperasiDistribusiRinci' => $persiapanOperasiDistribusiRinci ?? [],
                //     'returGudangRinci' => $returGudangRinci,
                //     'saldoAwal' => $saldoAwal,
                //     'mutasiMasuk' => $mutasiMasuk,
                //     'mutasiKeluar' => $mutasiKeluar,
                //     'resepKeluar' => $resepKeluar,
                //     'retur' => $retur,
                //     'resepKeluarRacikan' => $resepKeluarRacikan,
                //     'distribusiOk' => $distribusiOk ?? null,
                //     'kembaliOk' => $kembaliOk ?? null,
                //     'returGudang' => $returGudang,
                // ];

                $stok = FarmasinewStokreal::lockForUpdate()
                    ->where('kdobat', $kdobat)
                    ->where('kdruang', $koderuangan)
                    ->orderBy('tglexp', 'DESC')
                    ->orderBy('nodistribusi', 'DESC')
                    ->get();
                $hasil = [];
                $anuaad = 0;
                $anumas = 0;
                $anukel = 0;
                if ($koderuangan === 'Gd-04010103') {
                    $masuk = (float)$sal + (float)$peny + (float)$mutma + (float)$kem + (float) $ret;
                    $keluar = (float)$mutkel + (float)$dist + (float)$reskel + (float)$reskelrac + (float)$retG;
                    $sisa = (float)$masuk - (float)$keluar;
                    if ((float)$sisa != (float)$tts) {
                        //     // cek ketorolac
                        $ada = $sisa;
                        if ($sisa > 0) {
                            // nol kan semua
                            foreach ($stok as $st) {
                                $st->update([
                                    'jumlah' => 0
                                ]);
                            }
                            foreach ($noper as $key) {
                                // masuk
                                $salAwal =  collect($saldoAwalDepoRinci)->firstWhere('nopenerimaan', $key)->total ?? 0;
                                $mutMas =  collect($mutasiMasukDepoRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $retDep =  collect($returRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $kemB =  collect($persiapanOperasiDistribusiRinci)->firstWhere('nopenerimaan', $key)->kembali ?? 0;
                                // keluar
                                $disT =  collect($persiapanOperasiDistribusiRinci)->firstWhere('nopenerimaan', $key)->distribusi ?? 0;
                                $mutKel =  collect($mutasiKeluarDepoRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $resKel =  collect($resepKeluarRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $resKelRac =  collect($resepKeluarRacikanRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $retGud =  collect($returGudangRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;

                                $maSuk = (float) $salAwal + (float) $mutMas + (float) $kemB + (float) $retDep;
                                $keLuar = (float)$mutKel + (float)$resKel + (float)$resKelRac + (float)$retGud + (float)$disT;
                                $sisanya = $maSuk - $keLuar;

                                $tmpmas = $anumas + $maSuk;
                                $anumas = $tmpmas;
                                $tmpkel = $anukel + $keLuar;
                                $anumas = $tmpkel;

                                if ($sisanya > 0) {
                                    $temp = $anuaad + $sisanya;
                                    $anuaad = $temp;
                                    $stokNya = collect($stok)->firstWhere('nopenerimaan', $key);
                                    if ($stokNya) {
                                        if ((float)$sisanya >= (float)$ada) {
                                            $sisaJumlah = 0;
                                            $stokNya->update(['jumlah' => $ada]);
                                        } else if ((float)$ada > 0) {
                                            $sisaJumlah = (float)$ada - (float) $sisanya;
                                            $stokNya->update(['jumlah' => $sisanya]);
                                        }
                                        $ada = $sisaJumlah;
                                    } else {
                                        return [
                                            'data' => [
                                                'message' => 'stok dengan nomor penerimaan ' . $key . ' tidak ditemukan'
                                            ],
                                            'status' => 410
                                        ];
                                    }
                                }

                                $hasil[] = [
                                    'nopenerimaan' => $key,
                                    'maSuk' => $maSuk,
                                    'keLuar' => $keLuar,
                                    'sisanya' => $sisanya,
                                    'salAwal' => $salAwal,
                                    'mutMas' => $mutMas,
                                    'retDep' => $retDep,
                                    'mutKel' => $mutKel,
                                    'resKel' => $resKel,
                                    'resKelRac' => $resKelRac,
                                    'retGud' => $retGud,
                                ];
                            }
                            $message = 'Cek Stok Depo selesai, Stok sudah di update';
                        }
                        if ($sisa == 0) {
                            foreach ($stok as $st) {
                                $st->update([
                                    'jumlah' => $sisa
                                ]);
                            }
                            $message = 'Cek Stok Depo Ok selesai, Stok Habis';
                        }
                        if ($sisa < 0) {

                            $message = 'Sisa Stok kurang dari 0, Stok Tidak diganti silahkan cek transaksi';
                        }
                    }
                } else {
                    $masuk = (float)$sal + (float)$peny + (float)$mutma + (float) $ret;
                    $keluar = (float)$mutkel + (float)$reskel + (float)$reskelrac + (float)$retG;
                    $sisa = (float)$masuk - (float)$keluar;

                    if ((float)$sisa != (float)$tts) {
                        //     // cek ketorolac
                        $ada = $sisa;
                        if ($sisa > 0) {
                            // nol kan semua
                            foreach ($stok as $st) {
                                $st->update([
                                    'jumlah' => 0
                                ]);
                            }
                            foreach ($noper as $key) {
                                // masuk
                                $salAwal =  collect($saldoAwalDepoRinci)->firstWhere('nopenerimaan', $key)->total ?? 0;
                                $mutMas =  collect($mutasiMasukDepoRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $retDep =  collect($returRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                // keluar
                                $mutKel =  collect($mutasiKeluarDepoRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $resKel =  collect($resepKeluarRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $resKelRac =  collect($resepKeluarRacikanRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $retGud =  collect($returGudangRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $maSuk = (float) $salAwal + (float) $mutMas + (float) $retDep;
                                $keLuar = (float)$mutKel + (float)$resKel + (float)$resKelRac + (float)$retGud;
                                $sisanya = $maSuk - $keLuar;

                                $tmpmas = $anumas + $maSuk;
                                $anumas = $tmpmas;
                                $tmpkel = $anukel + $keLuar;
                                $anumas = $tmpkel;

                                if ($sisanya > 0) {
                                    $temp = $anuaad + $sisanya;
                                    $anuaad = $temp;
                                    $stokNya = collect($stok)->firstWhere('nopenerimaan', $key);
                                    if ($stokNya) {
                                        if ((float)$sisanya >= (float)$ada) {
                                            $sisaJumlah = 0;
                                            $stokNya->update(['jumlah' => $ada]);
                                        } else if ((float)$ada > 0) {
                                            $sisaJumlah = (float)$ada - (float) $sisanya;
                                            $stokNya->update(['jumlah' => $sisanya]);
                                        }

                                        $ada = $sisaJumlah;
                                    } else {
                                        return [
                                            'data' => [
                                                'message' => 'stok dengan nomor penerimaan ' . $key . ' tidak ditemukan'
                                            ],
                                            'status' => 410
                                        ];
                                    }
                                }

                                $hasil[] = [
                                    'nopenerimaan' => $key,
                                    'maSuk' => $maSuk,
                                    'keLuar' => $keLuar,
                                    'sisanya' => $sisanya,
                                    'salAwal' => $salAwal,
                                    'mutMas' => $mutMas,
                                    'retDep' => $retDep,
                                    'mutKel' => $mutKel,
                                    'resKel' => $resKel,
                                    'resKelRac' => $resKelRac,
                                    'retGud' => $retGud,
                                ];
                            }
                            $message = 'Cek Stok Depo selesai, Stok sudah di update';
                        }
                        if ($sisa == 0) {
                            foreach ($stok as $st) {
                                $st->update([
                                    'jumlah' => $sisa
                                ]);
                            }
                            $message = 'Cek Stok Depo selesai, Stok Habis';
                        }
                        if ($sisa < 0) {
                            $message = 'Sisa Stok kurang dari 0, Stok Tidak diganti silahkan cek transaksi';
                        }
                    }
                }



                $data = [
                    'anuaad' => $anuaad,
                    'anumas' => $anumas,
                    'anukel' => $anukel,
                    'hasil' => $hasil,
                    'saldoAwal' => $saldoAwal,
                    'stokid' => $stokid,
                    'penyesuaian' => $penyesuaian,

                    'saldoAwalDepoRinci' => $saldoAwalDepoRinci,
                    'mutasiMasukDepoRinci' => $mutasiMasukDepoRinci,
                    'mutasiKeluarDepoRinci' => $mutasiKeluarDepoRinci,
                    'resepKeluarRinci' => $resepKeluarRinci,
                    'returRinci' => $returRinci,
                    'resepKeluarRacikanRinci' => $resepKeluarRacikanRinci,
                    'persiapanOperasiDistribusiRinci' => $persiapanOperasiDistribusiRinci ?? [],
                    'returGudangRinci' => $returGudangRinci,
                    'mutasiMasuk' => $mutasiMasuk,
                    'mutasiKeluar' => $mutasiKeluar,
                    'noresep' => $noresep ?? [],
                    'resepKeluar' => $resepKeluar,
                    'retur' => $retur,
                    'resepKeluarRacikan' => $resepKeluarRacikan,
                    'persiapanOperasiDistribusi' => $persiapanOperasiDistribusi ?? null,

                    'rawNoper' => $rawNoper,
                    'noper' => $noper,

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
                    'retG' => $retG,
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'sisa' => $sisa,
                    'message' => $message
                ];
            }
            DB::connection('farmasi')->commit();
            return [
                'data' => $data,
                'status' => 200,
            ];
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return [
                'result' => '' . $e,
                'err' =>  $e,
                'data' => $data,
                'status' => 410
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
