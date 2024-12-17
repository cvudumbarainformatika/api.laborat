<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Pengembalian;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PengembalianRinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengembalianPinjamanController extends Controller
{
    /**
     * Form Sections
     */
    public function getPbfPeminjam()
    {
        // ini nanti di moodif cari yang pinjaman nya belum di kembalikan
        $kode = PenerimaanHeder::select('kdpbf')
            ->where('jenis_penerimaan', 'Pinjaman')
            ->where('kunci', '1')
            ->distinct()->pluck('kdpbf');

        $pihaktiga = Mpihakketiga::select('nama', 'kode')->whereIn('kode', $kode)->get();
        return new JsonResponse([
            "data" => $pihaktiga,
            'req' => request()->all(),
        ]);
    }
    public function getNopenerimaan()
    {
        // ini nanti di moodif cari yang pinjaman nya belum di kembalikan

        $data = PenerimaanHeder::select('nopenerimaan')
            ->where('jenis_penerimaan', 'Pinjaman')
            ->where('kdpbf', request('kdpbf'))
            ->with([
                'penerimaanrinci:id as id_rincipenerimaan,nopenerimaan,nopenerimaan as nopenerimaan_asal,harga_netto_kecil as harga,no_batch,kdobat,jml_terima_k',
                'penerimaanrinci.masterobat:kd_obat,nama_obat,satuan_k',
                'penerimaanrinci.pengembalian_rinci',
            ])
            ->where('kunci', '1')
            ->get();
        return new JsonResponse([
            'data' => $data,
            'req' => request()->all(),
        ]);
    }
    public function simpan(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            if (!$request->nopengembalian) {
                DB::connection('farmasi')->select('call pengembalian(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('pengembalian')->first();
                $wew = $x->pengembalian;

                $nopengembalian = FormatingHelper::pengembalian($wew);
            } else {
                $nopengembalian = $request->nopengembalian;
            }
            $header = Pengembalian::updateOrCreate(
                [
                    'nopengembalian' => $nopengembalian,
                    'nopenerimaan_asal' => $request->nopenerimaan_asal,
                ],
                [
                    'kdpbf' => $request->kdpbf,
                    'kdruang' => $request->kdruang,
                    'tgl_pengembalian' => $request->tgl_pengembalian,
                ]
            );
            if (!$header) {
                DB::connection('farmasi')->rollBack();
                return new JsonResponse([
                    'message' => 'Data Gagal Disimpan',
                    'req' => $request->all(),
                ], 410);
            }
            $detail = PengembalianRinci::updateOrCreate(
                [
                    'nopengembalian' => $nopengembalian,
                    'nopenerimaan_asal' => $request->nopenerimaan_asal,
                    'kdobat' => $request->kdobat,
                ],
                [
                    'id_rincipenerimaan' => $request->id_rincipenerimaan,
                    'no_batch' => $request->no_batch,
                    'jml_dikembalikan' => $request->jml_dikembalikan,
                    'harga' => $request->harga,
                ]
            );
            if (!$detail) {
                DB::connection('farmasi')->rollBack();
                return new JsonResponse([
                    'message' => 'Data Gagal Disimpan',
                    'req' => $request->all(),
                ], 410);
            }
            $penerimaanRinci = PenerimaanRinci::select(
                'id as id_rincipenerimaan',
                'nopenerimaan as nopenerimaan_asal',
                'nopenerimaan',
                'harga_netto_kecil as harga',
                'no_batch',
                'kdobat',
                'jml_terima_k'
            )
                ->with('pengembalian_rinci', 'masterobat:kd_obat,nama_obat,satuan_k')
                ->find($request->id_rincipenerimaan);

            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Berahasil Disimpan',
                'nopengembalian' => $nopengembalian,
                'penerimaanrinci' => $penerimaanRinci,
                'detail' => $detail,
                'req' => $request->all(),
            ]);
        } catch (\Throwable $th) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan ' . $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'req' => $request->all(),
            ], 410);
        }
    }

    /**
     * List Sections
     */
    public function getList()
    {
        $raw = Pengembalian::with([
            'rincian' => function ($query) {
                $query->with([
                    'masterobat:kd_obat,nama_obat,satuan_k',
                    'stok' => function ($q) {
                        $q->where('kdruang', request('kdruang'))
                            ->where('jumlah', '>', 0);
                    }
                ]);
            },
            'rincian_fifo',
            'pihakketiga:kode,nama',
        ])
            ->where(function ($query) {
                $query->where('nopengembalian', 'like', '%' . request('q') . '%')
                    ->orWhere('nopenerimaan_asal', 'like', '%' . request('q') . '%');
                // ->orWhere('nopengembalian', 'like', '%' . request('q') . '%');
            })
            ->whereBetween('tgl_pengembalian', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->where('kdruang', request('kdruang'))
            ->paginate(request('per_page'));
        $data['data'] = collect($raw)['data'];
        $data['meta'] = collect($raw)->except('data');
        $data['req'] = request()->all();

        return new JsonResponse($data);
    }

    public function kunci(Request $request)
    {
        // sebelum kunci cek stok alokasi dulu. kalo ada alokasi bisa ya lanjut kunci
        return new JsonResponse([
            'message' => 'Data Sudah di Kunci',
            'req' => $request->all(),
        ]);
    }
    public function hapusHeader(Request $request)
    {
        $header = Pengembalian::find($request->id);
        if (!$header) {
            return new JsonResponse(['message' => 'Data tidak ditemukan, gagal hapus'], 410);
        }
        $rincis = PengembalianRinci::where('nopengembalian', $header->nopengembalian)->get();
        if (count($rincis) > 0) {
            foreach ($rincis as $rinci) {
                $rinci->delete();
            }
        }
        $header->delete();
        return new JsonResponse([
            'message' => 'Data Header Berahasil dihapus',
            'req' => $request->all(),
        ]);
    }
    public function hapusRinci(Request $request)
    {
        $rinci = PengembalianRinci::find($request->id);
        if (!$rinci) {
            return new JsonResponse(['message' => 'Data tidak ditemukan, gagal hapus'], 410);
        }
        $header = Pengembalian::where('nopengembalian', $rinci->nopengembalian)->first();
        if (!$header) {
            return new JsonResponse(['message' => 'Data tidak ditemukan, gagal hapus'], 410);
        }
        $hapusHead = 'tidak';
        $rinci->delete();
        $rincis = PengembalianRinci::where('nopengembalian', $rinci->nopengembalian)->get();
        if (count($rincis) === 0) {
            $header->delete();
            $hapusHead = 'ya';
        }

        return new JsonResponse([
            'message' => 'Data Rinci Berahasil dihapus',
            'hapusHead' => $hapusHead,
            'req' => $request->all(),
        ]);
    }
}
