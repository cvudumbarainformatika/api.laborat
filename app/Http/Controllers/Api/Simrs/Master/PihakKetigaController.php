<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class PihakKetigaController extends Controller
{
    public function index(){
       
        $data = Supplier::where('hidden', '')
        ->when(request('q'),function ($query) {
            $query->where(function ($q) {
                $q->where('kode', 'LIKE', '%' . request('q') . '%')
                  ->orWhere('nama', 'LIKE', '%' . request('q') . '%')
                  ->orWhere('alamat', 'LIKE', '%' . request('q') . '%')
                  ->orWhere('npwp', 'LIKE', '%' . request('q') . '%')
                  ->orWhere('norek', 'LIKE', '%' . request('q') . '%')
                  ->orWhere('bank', 'LIKE', '%' . request('q') . '%');
            });
        })
        ->orderBy('nama', 'asc')
        ->get();
        return new JsonResponse($data);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            // 'kode' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'npwp' => 'required',
            'telepon' => 'required',
            'norek' => 'required',
            'bank' => 'required',
            'cp' => 'required',
            
        ], [
            'nama.required' => 'Nama Harus Di isi.',
            'alamat.required' => 'Alamat Harus Di isi.',
            'npwp.required' => 'NPWP Harus Di isi.',
            'telepon.required' => 'Telepon Harus Di isi.',
            'norek.required' => 'No Rekening Harus Di isi.',
            'bank.required' => 'Bank Harus Di isi.',
            'cp.required' => 'Contact Person Harus Di isi.',
        ]);

        try {
           
            if (empty($request->kode)) {
                DB::connection('siasik')->select('call master_pihak_ketiga(@nomor)');
                $x = DB::connection('siasik')->table('conter')->select('pihak_ketiga')->first();

                if (!$x) {
                    throw new \Exception('Gagal mendapatkan nomor dari prosedur notadinas');
                }
                $nomer = (int)$x->pihak_ketiga;
                $kode = FormatingHelper::kodeakun_lak($nomer, 'PK');
            } else {
                $kode = $request->kode;
            }
            DB::beginTransaction();

            $data = Supplier::updateOrCreate(
                [
                    'kode' => $kode
                ],
                [
                    'nama' => $validated['nama'],
                    'alamat' => $validated['alamat'],
                    'npwp' => $validated['npwp'],
                    'telepon' => $validated['telepon'],
                    'norek' => $validated['norek'],
                    'bank' => $validated['bank'],
                    'cp' => $validated['cp'],
                    'kodemapingrs' => $request->kodemapingrs ?? null,
                    'namasuplier' => $request->namasuplier ?? null,
                ]
            );

            DB::commit();
            return new JsonResponse(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $data]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['status' => 'error', 'message' => 'Data gagal disimpan: ' . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            // Validasi dulu biar gak kosong
            $request->validate([
                'id' => 'required'
            ]);

            DB::beginTransaction();

            $data = Supplier::find($request->id)
                ->where('hidden', '')
                ->firstOrFail();

            // $data->delete();
            $data->update([
                'hidden' => '1'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus',
                'data' => $data
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
