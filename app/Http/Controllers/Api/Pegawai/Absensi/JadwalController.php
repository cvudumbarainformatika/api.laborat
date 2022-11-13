<?php

namespace App\Http\Controllers\Api\Pegawai\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Hari;
use App\Models\Pegawai\Jadwal;
use App\Models\Pegawai\Kategory;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class JadwalController extends Controller
{

    public function index()
    {
        // return new JsonResponse(['to' => $to, 'from' => $from]);
        $data = Jadwal::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->with('pegawai', 'ruang', 'kategory')
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function getKategories()
    {
        $data = Kategory::with(
            'jam_reguler',
            'jam_jumat',
            'pertama',
            'kedua',
            'ketiga',
            'keempat',
            'kelima',
            'keenam',
        )->get();
        return new JsonResponse($data);
    }

    public function getDays()
    {
        $data = Hari::get();
        return new JsonResponse($data);
    }

    public function getByUser()
    {
        // return new JsonResponse(['to' => $to, 'from' => $from]);
        $data = Jadwal::where('user_id', request('user_id'))
            ->orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            //validate data
            $valid = Validator::make($request->all(), ['user_id' => 'required']);
            if ($valid->fails()) {
                return new JsonResponse([$valid->errors(), 422]);
            }
            // when valid
            $data = Jadwal::updateOrCreate(
                ['user_id' => $request->user_id],
                $request->all()
            );

            DB::commit();
            if (!$data->wasRecentlyCreated) {
                $status = 200;
                $pesan = 'Data telah di perbarui';
            } else {
                $status = 201;
                $pesan = 'Data telah di tambakan';
            }
            return new JsonResponse(['message' => $pesan], $status);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }
}
