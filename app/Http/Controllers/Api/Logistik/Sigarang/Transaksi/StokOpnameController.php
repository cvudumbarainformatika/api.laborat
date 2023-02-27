<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Helpers\StokHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\MonthlyStokUpdate;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\StokOpname;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StokOpnameController extends Controller
{
    // data gudang dan depo sigarang
    public function getDataGudangDepo()
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $raw = Gudang::query();
        if ($pegawai->role_id === 4) {
            $raw->where('kode', $pegawai->kode_ruang);
        } else {
            $raw->where('gedung', 2)
                ->where('lantai', '>', 0)
                ->where('gudang', '>', 0);
        }
        $data = $raw->get();
        return new JsonResponse($data);
    }
    // ambil data stok current ->
    // masukkan ke tabel stok opname bulanan ->
    // tampilkan ->
    // jika ada perbedaan tulis jumlah dan sisanya di tabel stok opname
    public function index(Request $request)
    {
        $request->validate(['gudang' => 'required']);
        $data = RecentStokUpdate::where('kode_ruang', $request->gudang)
            ->filter([$request->search])
            ->paginate(10);

        return new JsonResponse($data);
    }

    public function getDataTransaksi()
    {
        $header = (object)[];
        $bulan = request('bulan') ? '-' . request('bulan') : date('m');
        $tahun = request('tahun') ? request('tahun') : date('Y');
        $hari = '-31';
        $anu = (int)request('bulan') - 1;
        $prevTahun = request('bulan') === '01' ? strval((int)$tahun - 1) : $tahun;
        $prevbulan = request('bulan') === '01' ? '-12' : ($anu < 10 ? '-0' . $anu : '-' . $anu);

        $header->thisMonthFrom = $tahun . $bulan . '-01' . ' 00:00:00';
        $header->thisMonthTo = $tahun . $bulan . $hari . ' 23:59:59';
        $header->from = $tahun . $bulan . '-01';
        $header->to = $tahun . $bulan . $hari;
        $header->prevMonthFrom = $prevTahun . $prevbulan . '-01' . ' 00:00:00';
        $header->prevMonthTo = $prevTahun . $prevbulan . $hari . ' 23:59:59';

        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $depo = Gudang::where('kode', $pegawai->kode_ruang)->first();
        $header->pegawai = $pegawai;

        $penerimaan = StokHelper::hitungTransaksiPenerimaan($header);
        $distribusi_depo = StokHelper::hitungTransaksiDistribusiDepo($header);
        $permintaan_ruangan = StokHelper::hitungTransaksiPermintaanRuangan($header);

        $awal = MonthlyStokUpdate::selectRaw('kode_rs,kode_ruang,sum(sisa_stok) as stok')
            ->whereBetween('tanggal', [$header->prevMonthFrom, $header->prevMonthTo])
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        return [
            'penerimaan' => $penerimaan,
            'distribusi_depo' => $distribusi_depo,
            'permintaan_ruangan' => $permintaan_ruangan,
            'awal' => $awal,
            'header' => $header
        ];
    }

    public function getDataTransaksiByKodeRs($kode_rs)
    {
        $header = (object)[];
        $header->kode_rs = $kode_rs;
        $bulan = request('bulan') ? '-' . request('bulan') : date('m');
        $tahun = request('tahun') ? request('tahun') : date('Y');
        $hari = '-31';
        $anu = (int)request('bulan') - 1;
        $prevTahun = request('bulan') === '01' ? strval((int)$tahun - 1) : $tahun;
        $prevbulan = request('bulan') === '01' ? '-12' : ($anu < 10 ? '-0' . $anu : '-' . $anu);

        $header->thisMonthFrom = $tahun . $bulan . '-01' . ' 00:00:00';
        $header->thisMonthTo = $tahun . $bulan . $hari . ' 23:59:59';
        $header->from = $tahun . $bulan . '-01';
        $header->to = $tahun . $bulan . $hari;
        $header->prevMonthFrom = $prevTahun . $prevbulan . '-01' . ' 00:00:00';
        $header->prevMonthTo = $prevTahun . $prevbulan . $hari . ' 23:59:59';

        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $depo = Gudang::where('kode', $pegawai->kode_ruang)->first();
        $header->pegawai = $pegawai;

        // transaksi awal yaitu 31 des 2022
        // $dataAwal = MonthlyStokUpdate::whereBetween('tanggal', ['2022-12-01 00:00:00', '2022-12-31 23:59:59'])
        //     ->with('barang')->get();
        /*

            * transaksi berpengaruh :
            * penerimaan,
            * distribusi depo,
            * permintaan ruangan,
            * distribusi langsung
        */

        $penerimaan = StokHelper::hitungTransaksiPenerimaanByKodeBarang($header);
        $distribusi_depo = StokHelper::hitungTransaksiDistribusiDepoByKodeBarang($header);
        $permintaan_ruangan = StokHelper::hitungTransaksiPermintaanRuanganByKodeBarang($header);
        $awal = MonthlyStokUpdate::selectRaw('kode_rs ,kode_ruang, sum(sisa_stok) as stok')
            ->whereBetween('tanggal', [$header->prevMonthFrom, $header->prevMonthTo])
            ->where('kode_rs', $header->kode_rs)
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        return [
            'penerimaan' => $penerimaan,
            'distribusi_depo' => $distribusi_depo,
            'permintaan_ruangan' => $permintaan_ruangan,
            'awal' => $awal,
            'header' => $header
        ];
    }

    public function getDataStokOpname()
    {
        $bulan = request('bulan') ? request('bulan') : date('m');
        $tahun = request('tahun') ? request('tahun') : date('Y');

        $awal = $tahun . '-' . $bulan . '-1' . ' 00:00:00';
        $akhir = $tahun . '-' . $bulan . '-31' . ' 23:59:59';

        $tAwal = $tahun . '-' . $bulan . '-1';
        $tAkhir = $tahun . '-' . $bulan . '-31';

        $anu = (int)request('bulan') - 1;
        $prevTahun = request('bulan') === '01' ? strval((int)$tahun - 1) : $tahun;
        $prevbulan = request('bulan') === '01' ? '-12' : ($anu < 10 ? '-0' . $anu : '-' . $anu);

        $from = $prevTahun . '-' . $prevbulan . '-01' . ' 00:00:00';
        $to = $prevTahun . '-' . $prevbulan . '-31' . ' 23:59:59';

        $raw = MonthlyStokUpdate::selectRaw('*, sum(sisa_stok) as totalStok')
            ->whereBetween('tanggal', [$awal, $akhir])
            // ->where('tanggal', '<=', $tahun . '-' . $bulan . '-31')
            ->with([
                'penyesuaian',
                'barang.detailPenerimaan.penerimaan' => function ($wew) use ($tAwal, $tAkhir) {
                    $wew->whereBetween('tanggal', [$tAwal, $tAkhir]);
                },
                // 'barang.detailPermintaanruangan',
                'barang.detailPermintaanruangan.permintaanruangan' => function ($wew) use ($awal, $akhir) {
                    $wew->whereBetween('tanggal', [$awal, $akhir])
                        ->where('status', '>=', 7)
                        ->where('status', '<=', 8);
                },
                'barang.detailTransaksiGudang.transaction' => function ($wew) use ($tAwal, $tAkhir) {
                    $wew->whereBetween('tanggal', [$tAwal, $tAkhir]);
                },
                'barang.detailDistribusiDepo.distribusi' => function ($wew) use ($awal, $akhir) {
                    $wew->whereBetween('tanggal', [$awal, $akhir]);
                },
                'barang.detailDistribusiLangsung.distribusi' => function ($wew) use ($awal, $akhir) {
                    $wew->whereBetween('tanggal', [$awal, $akhir]);
                },
                'depo',
                'ruang',
                'barang.monthly' => function ($wew) use ($from, $to) {
                    $wew->whereBetween('tanggal', [$from, $to]);
                },
            ])
            ->filter(request(['q']))
            ->groupBy('kode_rs', 'kode_ruang')
            ->paginate(request('per_page'));

        // foreach ($raw as $key) {
        //     $apem = $this->getDataTransaksiByKodeRs($key->kode_rs);
        //     $key->transaksi = $apem;
        // return new JsonResponse(['key' => $key, 'apem' => $apem]);
        // }
        $col = collect($raw);
        $meta = $col->except('data');
        $meta->all();

        // $transaksi = $this->getDataTransaksi();

        $data = $col->only('data');
        $data['meta'] = $meta;
        // $data['transaksi'] = $transaksi;
        return new JsonResponse($data);
    }

    public function getDataStokOpnameByDepo()
    {
        $bulan = request('bulan') ? request('bulan') : date('m');
        $tahun = request('tahun') ? request('tahun') : date('Y');
        $awal = $tahun . '-' . $bulan . '-1' . ' 00:00:00';
        $akhir = $tahun . '-' . $bulan . '-31' . ' 23:59:59';

        // $raw = MonthlyStokUpdate::where('tanggal', '>=', $tahun . '-' . $bulan . '-1')
        // ->where('tanggal', '<=', $tahun . '-' . $bulan . '-31')
        // ->where('kode_ruang', '=', request('search'))
        $raw = MonthlyStokUpdate::whereBetween('tanggal', [$awal, $akhir])
            ->where('kode_ruang', request('search'))
            ->filter(request(['q']))
            ->with('penyesuaian', 'barang.mapingbarang.barang108', 'depo', 'ruang')
            ->paginate(request('per_page'));
        $col = collect($raw);
        $meta = $col->except('data');
        $meta->all();

        $data = $col->only('data');
        $data['meta'] = $meta;
        $data['meta'] = $meta;
        $data['request'] = request()->all();
        return new JsonResponse($data);
    }


    public function storeMonthly()
    {


        $today = date('Y-m-d');
        // $today = date('2023-02-28');
        $lastDay = date('Y-m-t', strtotime($today));
        $dToday = date_create($today);
        $dLastDay = date_create($lastDay);
        $diff = date_diff($dToday, $dLastDay);


        if ($diff->d === 0) {
            // ambil data barang yang ada stoknya di tabel sekarang
            $recent = RecentStokUpdate::where('sisa_stok', '>', 0)
                // ->where('kode_ruang', 'like', '%Gd-%')
                ->with('barang')
                ->get();

            // return new JsonResponse([
            //     'today' => $today,
            //     'last day' => $lastDay,
            //     'diff' => $diff->d,
            //     'request' => request()->all(),
            //     'recent' => $recent,
            //     // 'awal' => $dataAwal,
            // ], 410);
            $total = [];
            $tanggal = $today . ' 23:59:59';
            foreach ($recent as $key) {
                $data = MonthlyStokUpdate::updateOrCreate([
                    'tanggal' => $tanggal,
                    'kode_rs' => $key->kode_rs,
                    'kode_ruang' => $key->kode_ruang,
                    'no_penerimaan' => $key->no_penerimaan,
                ], [
                    // 'tanggal' => $tanggal,
                    // 'kode_rs' => $key->kode_rs,
                    // 'kode_ruang' => $key->kode_ruang,
                    // 'no_penerimaan' => $key->no_penerimaan,
                    'harga' => $key->harga,
                    'sisa_stok' => $key->sisa_stok,
                    'sisa_fisik' => $key->sisa_stok,
                    'satuan' => $key->satuan !== null ? $key->satuan : 'Belum ada satuan',
                    'kode_satuan' => $key->kode_satuan !== null ? ($key->barang ? $key->barang->kode_satuan : '71') : '71',
                ]);
                array_push($total, $data);
            }
            if (count($recent) !== count($total)) {
                return new JsonResponse(['message' => 'ada kesalahan dalam penyimpanan data stok opname, hubungi tim IT'], 409);
            }
            return new JsonResponse(['message' => 'data berhasil disimpan'], 201);
        }

        return new JsonResponse(['message' => 'Stok opname dapat dilakukan di hari terakhir tiap bulan'], 410);
        // return new JsonResponse(['message' => 'Anda tidak terdaftar sebagai petugas Depo'], 422);
    }

    public function storePenyesuaian(Request $request)
    {
        $monthlyStok = MonthlyStokUpdate::find($request->id);

        $recent = RecentStokUpdate::where('kode_rs', $monthlyStok->kode_rs)
            ->where('kode_ruang', $monthlyStok->kode_ruang)
            ->where('no_penerimaan', $monthlyStok->no_penerimaan)->first();

        // return new JsonResponse([
        //     'monthly' => $monthlyStok,
        //     'recent' => $recent,
        //     'request' => $request->all(),
        // ], 200);

        $penyesuaian = StokOpname::updateOrCreate(
            [
                'monthly_stok_update_id' => $monthlyStok->id,
            ],
            $request->all()
        );

        // $recent->update([
        //     'sisa_stok' => $request->jumlah
        // ]);

        if ($penyesuaian->wasRecentlyCreated) {
            return new JsonResponse(['message' => 'data berhasil disimpan'], 201);
        }
        if ($penyesuaian->wasChanged()) {
            return new JsonResponse(['message' => 'data berhasil disimpan'], 201);
        }

        return new JsonResponse(['message' => 'Tidak ada perubahan data'], 417);
    }
    public function updateStokFisik(Request $request)
    {
        $data = MonthlyStokUpdate::find($request->id);
        $data->update([
            'stok_fisik' => $request->stok_fisik
        ]);

        if ($data->wasChanged()) {
            return new JsonResponse(['message' => 'data berhasil disimpan', 'data' => $data], 201);
        }
        return new JsonResponse([$request->all(), 'message' => 'Data tidak disimpan'], 410);
    }
}
