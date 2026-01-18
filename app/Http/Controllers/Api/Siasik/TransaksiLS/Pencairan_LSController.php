<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Siasik\TransaksiLS\NpkLS_heder;
use App\Models\Siasik\TransaksiLS\NpkLS_rinci;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastKonsinyasi;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class Pencairan_LSController extends Controller
{
    public function listdata()
    {
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->nip;
        $sa = $pg->kdpegsimrs;
        $tahunawal=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $tahun=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $data = NpkLS_heder::whereBetween('npkls_heder.tglnpk', [$tahunawal.'-01-01', $tahun.'-12-31'])
            ->where('npkls_heder.nopencairan', '=', '')
            ->where('npkls_heder.kunci', '=', '1')
            ->with(['rincians', 'npdls' => function($que){
                $que->leftJoin('npdls_rinci', 'npdls_rinci.nonpdls', 'npdls_heder.nonpdls');
            }])
            ->when(request('q'), function($q){
                $q->where('npkls_heder.nonpk', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('npkls_heder.tglnpk', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('npkls_heder.subtotal', 'LIKE', '%' . request('q') . '%');
            })
            ->withSum('rincians as subtotal', 'total')
            // ->groupBy('npkls_heder.nonpk')
            ->orderBy('npkls_heder.tglnpk', 'desc')
            ->get();
        return new JsonResponse($data);

    }

    public function pencairan(Request $request)
    {
        $request->validate([
            'nonpk' => 'required|string',
            'tglpencairan' => 'required|date',
            'tglpindahbuku' => 'required|date',
        ]);

        try {
            $user = auth()->user()->pegawai_id;
            $pg = Pegawai::find($user);
            $pegawai = $pg->kdpegsimrs ?? null;
            $time = date('Y-m-d H:i:s');

            if (!$pegawai) {
                return response()->json(['message' => 'Data pegawai tidak ditemukan'], 404);
            }

            if (empty($request->nopencairan)) {
                DB::connection('siasik')->select('call nopencairan(@nomor)');
                $x = DB::connection('siasik')->table('conter')->select('cair')->first();

                if (!$x || !$x->cair) {
                    throw new \Exception('Gagal mendapatkan nomor dari prosedur nopencairan');
                }

                $nomer = (int) $x->cair;
                $nopencairan = FormatingHelper::nonotadinas($nomer, 'CAIR-LS');
            } else {
                $nopencairan = $request->nopencairan;
            }

            DB::beginTransaction();
            $header = NpkLS_heder::where('nonpk', $request->nonpk)->first();

            if (!$header) {
                DB::rollBack();
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }

            $header->update([
                'nopencairan' => $nopencairan,
                'tglpencairan' => $request->tglpencairan,
                'tglpindahbuku' => $request->tglpindahbuku,
                'userentrycair' => $pegawai,
                'tglentrycair' => $time,
            ]);

            NpdLS_heder::where('nonpk', $request->nonpk)
            ->update([
                'nopencairan' => $nopencairan,
            ]);

            // === AMBIL DATA RINCIAN NPK ===
            $npkData = NpkLS_rinci::where('nonpk', $request->nonpk)
                ->select('nonpk', 'nonpdls', 'total', 'tglentrycair')
                ->get();

            if ($npkData->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => 'Data rincian tidak ditemukan'], 404);
            }

            // === AMBIL BENDAHARA PENGELUARAN AKTIF ===
            $bendpengeluaran = Mpegawaisimpeg::where('jabatan', 'J00035')
                ->where('aktif', 'AKTIF')
                ->first();

            if (!$bendpengeluaran) {
                DB::rollBack();
                return response()->json(['message' => 'Bendahara pengeluaran aktif tidak ditemukan'], 404);
            }

            // === UPDATE DATA PENERIMAAN & KONSINYASI ===
            $updatedCount = 0;

            foreach ($npkData as $npk) {
                $tglPencairan = Carbon::parse($request->tglpencairan)->format('Y-m-d');
                // UPDATE FARMASI //
                $updatePenerimaan = PenerimaanHeder::where('no_npd', $npk->nonpdls)
                    ->whereNull('tgl_pencairan_npk')
                    ->update([
                        'tgl_pencairan_npk' => $tglPencairan,
                        'tgl_pembayaran' => $tglPencairan,
                        'nilai_pembayaran' => $npk->total,
                        'total_pembayaran' => $npk->total,
                        'user_bayar' => $bendpengeluaran->kdpegsimrs,
                        'flag_bayar' => '1'
                    ]);

                $updateKonsinyasi = BastKonsinyasi::where('no_npd', $npk->nonpdls)
                    ->whereNull('tgl_pencairan_npk')
                    ->update([
                        'tgl_pencairan_npk' => $tglPencairan,
                        'tgl_pembayaran' => $tglPencairan,
                        'nilai_pembayaran' => $npk->total,
                        'total_pembayaran' => $npk->total,
                        'user_bayar' => $bendpengeluaran->kdpegsimrs,
                        'flag_bayar' => '1'
                    ]);

                // UPDATE SIAGARANG //
                $updateSigarang = Penerimaan::where('nonpdls', $npk->nonpdls)
                    ->whereNull('tanggal_pembayaran')
                    ->update([
                        'tanggal_pembayaran' => $tglPencairan,
                        'nilai_pembayaran' => $npk->total,
                        'pembayaran_by' => $bendpengeluaran->id,
                        'no_pembayaran' => $nopencairan
                    ]);

                $updatedCount += ($updatePenerimaan + $updateKonsinyasi + $updateSigarang);
            }

            DB::commit();

        return response()->json([
                'success' => true,
                'message' => 'Pencairan dan update data berhasil',
                'nopencairan' => $nopencairan,
                'jumlah_data_diupdate' => $updatedCount,
                'npk' => $header->nonpk,
                'data' => $header
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data pencairan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
