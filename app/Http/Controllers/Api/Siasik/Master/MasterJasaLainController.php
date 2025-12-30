<?php

namespace App\Http\Controllers\Api\Siasik\Master;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Siasik\Master\Master_Jasa;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class MasterJasaLainController extends Controller
{
    public function getRekening(){
        $perPage = request('per_page');
        $query = Akun50_2024::where('akun', '5')
            ->where('kelompok', '!=', '2')
            ->where('subrincian_objek', '!=', '');
        // Pencarian
        if (request('q')) {
            $cari = request('q');
            $query->where(function ($q) use ($cari) {
                $q->where('uraian', 'like', '%' . $cari . '%')
                  ->orWhere('kodeall3', 'like', '%' . $cari . '%');
            });
        }

        if ($perPage <= 0) {
            $akun = $query->get();
            return new JsonResponse(['data' => $akun]);
        }

        $akun = $query->simplePaginate($perPage);

        return new JsonResponse($akun);
    }
    public function index(){
       
        $data = Master_Jasa::when(request('q'),function ($query) {
            $query->where('kode', 'LIKE', '%' . request('q') . '%')
            ->orWhere('nama', 'LIKE', '%' . request('q') . '%')
            ;
        })
        ->get();
        return new JsonResponse($data);
    }
    public function save(Request $request)
    {
        $validated = $request->validate([
            // 'kode' => 'required',
            'nama' => 'required',
            
        ], [
            'nama.required' => 'Nama Harus Di isi.',
            
        ]);

        try {
            $time = date('Y-m-d H:i:s');
            $user = auth()->user()->pegawai_id;
            $pg= Pegawai::find($user);
            $pegawai= $pg->kdpegsimrs;

            if (empty($request->kode)) {
                DB::connection('siasik')->select('call kodemasterjasa(@nomor)');
                $x = DB::connection('siasik')->table('conter')->select('kodemasterjasa')->first();

                if (!$x) {
                    throw new \Exception('Gagal mendapatkan nomor dari prosedur notadinas');
                }
                $nomer = (int)$x->kodemasterjasa;
                $kode = FormatingHelper::kodeakun_lak($nomer, 'JS');
            } else {
                $kode = $request->kode;
            }
            DB::beginTransaction();

            $data = Master_Jasa::updateOrCreate(
                [
                    'kode' => $kode
                ],
                [
                    'nama' => $validated['nama'],
                    'userentry' => $pegawai,
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

            $data = Master_Jasa::find($request->id);

            $data->delete();

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
