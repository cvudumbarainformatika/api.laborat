<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\Transaksi\Pemesanan\Pemesanan;
use App\Models\Sigarang\Transaksi\Penerimaan\DetailPenerimaan;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PenerimaanController extends Controller
{
    public function cariPemesanan()
    {
        $user = auth()->user();
        // $pegawai = Pegawai::find($user->pegawai_id);
        $data = Pemesanan::filter(request(['q']))
            // ->where('status', 2)
            // ->orWhere('status', 3)
            ->whereIn('status', [2, 3])
            ->latest('id')
            ->with(['details.barang108', 'details.barangrs', 'details.satuan', 'perusahaan', 'details_kontrak'])
            ->get();

        return new JsonResponse($data);
    }

    public function jumlahPenerimaan()
    {
        $data = penerimaan::where('nomor', request('nomor'))->get();
        return new JsonResponse(['jumlah' => count($data)]);
    }

    public function penerimaan()
    {

        $pemesanan = Pemesanan::where('reff', '=', request()->reff)
            ->where('status', '>=', 2)
            ->latest('id')->with(['details', 'details.barangrs', 'details.satuan', 'perusahaan', 'details_kontrak'])->get();
        $penerimaanLama = Penerimaan::where('reff', '=', request()->reff)
            ->where('status', '>=', 2)->with('details')->get();

        $penerimaanSkr = Penerimaan::where('reff', '=', request()->reff)
            ->where('status', '=', 1)->with('details')->get();

        $detailLama = DetailPenerimaan::selectRaw('kode_rs, sum(qty) as jml, harga')->groupBy('kode_rs')
            ->whereHas('penerimaan', function ($p) {
                // ->where('nama', '=', 'PENERIMAAN')
                $p->where('status', '>=', 2)
                    ->where('reff', '=', request()->reff);
            })->get();




        $draft = (object) array(
            'pemesanan' => $pemesanan,
            'trmSblm' => $penerimaanLama,
            'trmSkr' => $penerimaanSkr,
            'detailLama' => $detailLama,
        );
        return new JsonResponse($draft);
    }
    public function suratBelumLengkap()
    {
        $data = Penerimaan::where('faktur', '=', null)
            ->orWhere('surat_jalan', '=', null)
            ->latest('id')
            ->get();
        return new JsonResponse($data);
    }

    public function simpanPenerimaan(Request $request)
    {
        $second = $request->all();
        $second['tanggal'] = $request->tanggal !== null ? $request->tanggal : date('Y-m-d H:i:s');

        try {
            DB::beginTransaction();

            $valid = Validator::make($request->all(), ['reff' => 'required']);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }

            $data = Penerimaan::updateOrCreate(['reff' => $request->reff], $second);

            if ($request->has('kode_rs') && $request->has('kode_108') && $request->kode_rs !== null) {
                $data->details()->updateOrCreate(['kode_rs' => $request->kode_rs], $second);
            }

            if ($request->status === 2 && $data) {
                TransaksiGudangController::fromPenerimaan($data->id);
            }

            PemesananController::updateStatus($request->nomor, $request->statuspemesanan);


            DB::commit();

            return new JsonResponse([
                'message' => 'success',
                'data' => $data,
                // 'gudang' => $gudang,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }

    public function lengkapiSurat(Request $request)
    {
        $second = $request->all();

        try {
            DB::beginTransaction();

            $valid = Validator::make($request->all(), [
                'reff' => 'required',
                'faktur' => 'required',
                'surat_jalan' => 'required',
                'tanggal_surat' => 'required',
                'tanggal_faktur' => 'required',
                'tempo' => 'required',
            ]);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }

            $data = Penerimaan::updateOrCreate(['reff' => $request->reff], $second);


            DB::commit();

            return new JsonResponse([
                'message' => 'success',
                'data' => $data,
                // 'gudang' => $gudang,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }



    public function destroy()
    {
        return new JsonResponse(['msg' => 'Masih kosong bos']);
    }
}
