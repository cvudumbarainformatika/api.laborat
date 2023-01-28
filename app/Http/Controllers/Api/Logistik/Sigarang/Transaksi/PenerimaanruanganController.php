<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\MaxRuangan;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\Penerimaanruangan\DetailsPenerimaanruangan;
use App\Models\Sigarang\Transaksi\Penerimaanruangan\Penerimaanruangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenerimaanruanganController extends Controller
{
    //
    public function index()
    { {
            $data = Permintaanruangan::where('status', '=', 7)
                ->with('details.barangrs.barang108', 'details.barangrs.satuan', 'pj', 'pengguna')->get();

            foreach ($data as $key) {
                foreach ($key->details as $detail) {
                    $temp = StockController::getDetailsStok($detail['kode_rs'], $detail['tujuan']);
                    $max = MaxRuangan::where('kode_rs', $detail['kode_rs'])->where('kode_ruang', $detail['tujuan'])->first();
                    $detail['barangrs']->maxStok = $max->max_stok;
                    $detail['barangrs']->alokasi = $temp->alokasi;
                    $detail['temp'] = $temp;
                    $detail['barangrs']->stokDepo = $temp->stok;
                    $detail['barangrs']->stokRuangan = $temp->stokRuangan;
                }
            }

            return new JsonResponse($data);
        }
    }

    // distribusi yang sudah tercatat di tabel penerimaan ruanga
    // tapi masih belum di konfirmasi ruangan
    public function distributedPenerimaan()
    {
        $data = Penerimaanruangan::where('status', 1)
            ->get();

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $stok = [];
        foreach ($request->details as $detail) {
            $dari = RecentStokUpdate::where('kode_ruang', $detail['dari'])
                ->where('kode_rs', $detail['kode_rs'])
                ->get();
            $tujuan = RecentStokUpdate::where('kode_ruang', $detail['tujuan'])
                ->where('kode_rs', $detail['kode_rs'])
                ->get();
            array_push($stok, ['dari' => $dari, 'tujuan' => $tujuan]);
        }
        return new JsonResponse([$stok, $request->all()]);
        try {
            DB::beginTransaction();
            if ($request->has('details')) {
                $detail = $request->details;
            }
            // return new JsonResponse($detail);

            $penerimaan = Penerimaanruangan::updateOrCreate(
                ['id' => $request->id],
                $request->all()
            );
            if ($detail) {
                foreach ($detail as $key) {
                    // update or create detail
                    $penerimaan->details()->updateOrCreate(
                        ['id' => $key['id']],
                        $key
                    );

                    // update or create data recent stok
                    // dari -> depo
                    // tujuan -> ruangan
                }
            }
            if ($request->has('permintaan_id')) {
                $permintaan_id = $request->permintaan_id;
                $permintaan = $this->updatePermintaan($permintaan_id);
                unset($request['permintaan_id']);
            }
            if ($penerimaan->wasRecentlyCreated) {
                $status = 201;
                $pesan = ['message' => 'Penerimaan Ruangan telah disimpan'];
            } else if ($penerimaan->wasChanged()) {
                $status = 200;
                $pesan = ['message' => 'Penerimaan Ruangan telah diupdate'];
            } else {
                $status = 500;
                $pesan = ['message' => 'Penerimaan Ruangan gagal dibuat'];
            }
            DB::commit();
            return new JsonResponse($pesan, $status);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }

    public static function telahDiDistribusikan($request, $permintaanruangan)
    // public static function telahDiDistribusikan($permintaanruangan)
    {
        // return $request->all();
        // $stok = [];

        // check stok masih cukup atau tidak
        //


        foreach ($permintaanruangan->details as $key => $detail) {
            $dari = RecentStokUpdate::where('kode_ruang', $detail['dari'])
                ->where('kode_rs', $detail['kode_rs'])
                ->where('sisa_stok', '>', 0)
                ->with('barang')
                ->get();

            $sisaStok = collect($dari)->sum('sisa_stok');
            // $jumlahDistribusi = $detail['jumlah_distribusi'];
            $jumlahDistribusi = $detail['jumlah_disetujui'];

            // return ['status' => 500, 'message' => $detail, 'key' => $key];
            // kembali jika ada jumlah stok yang kurang dari jumnlah distribusi

            if (count($dari) === 0) {
                $barang = BarangRS::where('kode', $detail['kode_rs'])->first();
                $pesan = 'stok ' .  $barang->nama . ' tidak ada';
                $status = 500;

                return ['status' => $status, 'message' => $pesan,];
            }

            if ($sisaStok < $jumlahDistribusi) {
                $barang = $dari[$key]['barang']['nama'];
                $pesan = 'stok ' .  $barang . ' tidak mencukupi';
                $status = 500;

                return ['status' => $status, 'message' => $pesan,];
            }
        }
        // return [
        //     'sisaStok' => $sisaStok,
        //     'jumlahDistribusi' => $jumlahDistribusi,
        //     'status' => $status,
        //     'pesan' => $pesan,
        //     'barang' => $barang,
        //     'dari' => $dari,
        //     'stok' => $stok,
        //     'permintaan ruangn' => $permintaanruangan,
        //     'request' => $request->all()
        // ];

        // buat penerimaan

        $tmpreff = explode('-', $permintaanruangan->reff);
        $reff = 'TRMR-' . $tmpreff[1];

        $penerimaanruangan = Penerimaanruangan::updateOrCreate(
            [
                'reff' => $reff,
                // 'no_distribusi' => $permintaanruangan->no_distribusi
                'no_distribusi' => $request->no_distribusi
            ],
            [
                'tanggal' => date('Y-m-d H:i:s'),
                'kode_pengguna' => $permintaanruangan->kode_pengguna,
                'kode_penanggungjawab' => $permintaanruangan->kode_penanggungjawab,
            ]
        );

        // detail barang + update recent stok ruangan dan depo
        foreach ($permintaanruangan->details as $detail) {

            $dari = RecentStokUpdate::where('kode_ruang', $detail['dari'])
                ->where('kode_rs', $detail['kode_rs'])
                ->where('sisa_stok', '>', 0)
                ->oldest()
                ->get();

            $sisaStok = collect($dari)->sum('sisa_stok');
            $index = 0;
            // $jumlahDistribusi = $detail['jumlah_distribusi'];
            $jumlahDistribusi = $detail['jumlah_disetujui'];

            // masukkan detail sesuai order FIFO
            $masuk = $jumlahDistribusi;
            do {
                $ada = $dari[$index]->sisa_stok;
                if ($ada < $masuk) {
                    $sisa = $masuk - $ada;

                    RecentStokUpdate::create([
                        'kode_rs' => $detail['kode_rs'],
                        'kode_ruang' => $detail['tujuan'],
                        'sisa_stok' => $ada,
                        'harga' => $dari[$index]->harga,
                        'no_penerimaan' => $dari[$index]->no_penerimaan,
                    ]);
                    $dari[$index]->update([
                        'sisa_stok' => 0
                    ]);
                    $penerimaanruangan->details()->create([
                        'no_penerimaan' => $dari[$index]->no_penerimaan,
                        'jumlah' => $ada,
                        // 'no_distribusi' => $permintaanruangan->no_distribusi,
                        'no_distribusi' => $request->no_distribusi,
                        'kode_rs' => $detail['kode_rs'],
                        'kode_satuan' => $detail['kode_satuan'],
                    ]);
                    $index = $index + 1;
                    $masuk = $sisa;
                    $loop = true;
                } else {
                    $sisa = $ada - $masuk;

                    RecentStokUpdate::create([
                        'kode_rs' => $detail['kode_rs'],
                        'kode_ruang' => $detail['tujuan'],
                        'sisa_stok' => $masuk,
                        'harga' => $dari[$index]->harga,
                        'no_penerimaan' => $dari[$index]->no_penerimaan,
                    ]);

                    $dari[$index]->update([
                        'sisa_stok' => $sisa
                    ]);

                    $penerimaanruangan->details()->create([
                        'no_penerimaan' => $dari[$index]->no_penerimaan,
                        'jumlah' => $masuk,
                        // 'no_distribusi' => $permintaanruangan->no_distribusi,
                        'no_distribusi' => $request->no_distribusi,
                        'kode_rs' => $detail['kode_rs'],
                        'kode_satuan' => $detail['kode_satuan'],
                    ]);

                    $loop = false;
                }
            } while ($loop);
        }
        return [
            'status' => 201
        ];
    }

    public function distribusiDiterima(Request $request)
    {
        // $data = Penerimaanruangan::where('no_distribusi', $request->no_distribusi)->first();
        // $permintaan = Permintaanruangan::find($request->permintaan_id);
        // return new JsonResponse([$data, $permintaan], 410);
        // $permintaanRuangan = Permintaanruangan::with('details')->find($request->permintaan_id);
        // $permintaanRuangan = Permintaanruangan::with('details')->find(3);
        // return new JsonResponse($permintaanRuangan, 500);
        // $temp = $this->telahDiDistribusikan($permintaanRuangan);
        // if ($temp['status'] !== 201) {
        //     return new JsonResponse($temp, $temp['status']);
        // }

        try {
            DB::beginTransaction();
            $data = Penerimaanruangan::where('no_distribusi', $request->no_distribusi)->first();
            $permintaan = Permintaanruangan::find($request->permintaan_id);
            $data->update([
                'reff' => $request->reff,
                'status' => 2
            ]);
            $permintaan->update([
                'status' => 8
            ]);

            DB::commit();

            return new JsonResponse(['message' => 'Penerimaan Ruangan sudah dicatat'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'tidak ada update',
                'error' => $e
            ], 410);
        }
    }
    public function updatePermintaan($id)
    {
        $permintaan = Permintaanruangan::find($id);
        $permintaan->update([
            'status' => 8
        ]);
        if (!$permintaan->wasChanged()) {
            return false;
        }
        return true;
    }
    public function getItems()
    {
        // return new JsonResponse(['message' => 'tak balikno', request()->all()]);
        // $data = DetailsPenerimaanruangan::distinct()->get(['kode_rs']);
        $kode = request('kode_pengguna');
        $data = DetailsPenerimaanruangan::selectRaw('kode_rs, sum(jumlah) as jml')
            ->whereHas('penerimaanruangan', function ($wew) use ($kode) {
                $wew->where('kode_pengguna', '=', $kode)
                    ->where('status', '=', 2);
            })->groupBy('kode_rs')->get();
        return new JsonResponse($data, 200);
    }
    public function getPj()
    {
        $data = Penerimaanruangan::select('kode_penanggungjawab')->with('pj')->distinct()->get();
        $collection = collect($data);
        $maping = $collection->map(function ($item, $key) {
            return $item['pj'];
        });

        return new JsonResponse($maping, 200);
    }
}
