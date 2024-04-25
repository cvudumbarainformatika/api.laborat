<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Bast;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastrinciM;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfheder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfrinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BastController extends Controller
{

    public function perusahaan()
    {
        // ambil perusahaan yang sedang belum bast
        $raw = PenerimaanHeder::select('kdpbf')
            ->where(function ($q) {
                $q->whereNull('tgl_bast')
                    ->orWhere('jumlah_bast', '<=', 0);
            })
            ->where('jenis_penerimaan', 'Pesanan')
            ->where('kunci', '1')
            ->get();
        // map kode perusahaan ke bentuk array, biar aman jika nanti ada append
        $temp = collect($raw)->map(function ($y) {
            return $y->kdpbf;
        });
        $data = KontrakPengerjaan::select('kodeperusahaan', 'namaperusahaan')->whereIn('kodeperusahaan', $temp)->distinct()->get();

        return new JsonResponse($data);
    }
    public function pemesanan()
    {
        $data = PemesananHeder::where('kdpbf', request('kdpbf'))->get();
        return new JsonResponse($data);
    }
    public function penerimaan()
    {
        // return new JsonResponse(request()->all());
        $data = PenerimaanHeder::where('kdpbf', request('kdpbf'))
            ->where('nopemesanan', request('nopemesanan'))
            ->where(function ($q) {
                $q->whereNull('tgl_bast')
                    ->orWhere('jumlah_bast', '<=', 0);
            })
            ->where('jenis_penerimaan', 'Pesanan')
            ->with([
                'penerimaanrinci' => function ($tr) {
                    $tr->selectRaw('penerimaan_r.*, sum(retur_penyedia_r.subtotal) as nilai_retur, retur_penyedia_r.jumlah_retur')
                        ->leftJoin('retur_penyedia_r', function ($join) {
                            $join->on('retur_penyedia_r.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
                                ->on('retur_penyedia_r.kd_obat', '=', 'penerimaan_r.kdobat');
                        })
                        ->with('masterobat:kd_obat,nama_obat,satuan_b')
                        ->groupBy('penerimaan_r.kdobat', 'penerimaan_r.nopenerimaan');
                },
                'faktur'
            ])
            ->get();
        return new JsonResponse($data);
    }
    public function simpan(Request $request)
    {
        $trm = [];
        $ba = [];
        $ret = [];
        $user = FormatingHelper::session_user();
        try {
            DB::connection('farmasi')->beginTransaction();

            if ($request->nobast === '' || $request->nobast === null) {
                DB::connection('farmasi')->select('call nobast(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('bast')->get();
                $wew = $x[0]->bast;
                $nobast = FormatingHelper::penerimaanobat($wew, 'BAST-FAR');
            } else {
                $nobast = $request->nobast;
            }
            foreach ($request->penerimaans as $penerimaan) {

                // simpan header penerimaan
                $terima = PenerimaanHeder::where('nopenerimaan', $penerimaan['nopenerimaan'])->first();
                if ($terima) {
                    $terima->update([
                        'nobast' => $nobast,
                        'tgl_bast' => $request->tgl_bast,
                        'jumlah_bast' => $request->jumlah_bast,
                        'nilai_retur' => $penerimaan['subtotal_retur'] ?? 0,
                        'user_bast' => $user['kodesimrs'],
                    ]);
                    $trm[] = $terima;
                }
                if (!$terima) {
                    return new JsonResponse(['message' => 'Gagal BAST, Nomor Penerimaan Tidak ditemukan'], 410);
                }
                // simpan rinci bast
                foreach ($penerimaan['penerimaanrinci'] as $rinci) {

                    $tempRinc = BastrinciM::updateOrCreate(
                        [
                            'nobast' => $nobast,
                            'nopenerimaan' => $penerimaan['nopenerimaan'],
                            'kdobat' => $rinci['kdobat'],

                        ],
                        [
                            'jumlah' => $rinci['jml_terima_k'],
                            'harga' => $rinci['harga_kcl'],
                            'diskon' => $rinci['diskon'],
                            'ppn' => $rinci['ppn'],
                            'harga_net' => $rinci['harga_netto_kecil'],
                            'subtotal' => $penerimaan['subtotal_bast'],
                            'user' => $user['kodesimrs'],
                        ]
                    );
                    $ba[] = $tempRinc;
                    // update retur jika ada
                    if ((float)$rinci['nilai_retur'] > 0) {
                        // $returHead = Returpbfheder::where('nopenerimaan', $penerimaan['nopenerimaan'])->first();
                        $returRin = Returpbfrinci::where('nopenerimaan', $penerimaan['nopenerimaan'])
                            ->where('kd_obat', $rinci['kdobat'])
                            ->first();
                        if ($returRin) {
                            $returRin->update([
                                'harga_net' => $rinci['harga_netto_kecil'],
                                'subtotal' => $rinci['nilai_retur'],
                            ]);
                            $ret[] = $returRin;
                        }
                        // if ($returHead) {
                        // }
                    }
                }
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'BAST Sudah Disimpan',
                'req' => $request->all(),
                'head' => $trm,
                'rinci bast' => $ba,
                'update retur' => $ret,
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 410);
        }
    }
    public function listBast()
    {
        $res1 = PenerimaanHeder::select('nobast')
            ->where('nobast', '<>', '')
            // ->orderBy('nobast', 'DESC')
            // ->orderBy('tgl_bast', 'DESC')
            // ->get();
            ->distinct('nobast')
            ->paginate(request('per_page'));

        $col = collect($res1);
        $data = $col['data'];
        $pen = [];
        foreach ($data as $key) {
            $item = current((array)$key);
            $pen[] =  $item;
        }
        $result = PenerimaanHeder::where('nobast', '<>', '')
            ->whereNotNull('tgl_bast')
            ->with(
                'faktur',
                'penerimaanrinci',
                'pihakketiga',
                'terima:kdpegsimrs,nama',
                'bast:kdpegsimrs,nama',
                'bayar:kdpegsimrs,nama',
            )
            ->whereIn('nobast', $pen)
            ->orderBy('tgl_bast', 'DESC')
            ->orderBy('nobast', 'DESC')
            ->get();

        $groupedResult = $result->groupBy('nobast')->map(function ($group) {
            return $group->map(function ($item) {
                return $item;
            });
        });

        // Convert the result to the desired format
        $formattedResult = $groupedResult->map(function ($items, $kwitansi) {

            return [
                'no_bast' => $kwitansi,
                'totalSemua' =>  $items[0]->jumlah_bast,
                'tanggal' => $items[0]->tgl_bast,
                'nomor' => $items[0]->nopemesanan,
                'terima' => $items[0]->terima,
                'bast' => $items[0]->bast,
                'bayar' => $items[0]->bayar,
                'penyedia' => $items[0]->pihakketiga->nama ?? '',
                'penerimaan' => $items,
            ];
        })->values();
        return new JsonResponse([
            'data' => $formattedResult,
            'meta' => $col->except('data'),
            'res' => $col,
            'dat' => $data,
        ]);
    }
}
