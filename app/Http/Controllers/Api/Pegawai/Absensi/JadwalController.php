<?php

namespace App\Http\Controllers\Api\Pegawai\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Hari;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\Kategory;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class JadwalController extends Controller
{

    public function index()
    {
        // return new JsonResponse(['to' => $to, 'from' => $from]);
        $data = JadwalAbsen::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->with(
                'pegawai',
                'ruang',
                'kategory',
                'pertama',
                'kedua',
                'ketiga',
                'keempat',
                'kelima',
                'keenam',
                'ketujuh',
                'jam01',
                'jam02',
                'jam03',
                'jam04',
                'jam05',
                'jam06',
                'jam07',
            )
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function getKategories()
    {
        $data = Kategory::get();
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
        $user = JWTAuth::user();
        $data = JadwalAbsen::where('user_id', $user->id)
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
            // kembalikan data jika masih ada jadwal di hari itu
            // $jadwal = JadwalAbsen::where('user_id', $request->user_id)->first();
            // if ($jadwal) {
            //     $today = date('l');
            //     $adaJadwal = [];

            //     foreach ($jadwal->jadwal as $key) {
            //         if ($key['name'] === $today) {
            //             array_push($adaJadwal, $key);
            //         }
            //     }
            //     if (count($adaJadwal)) {
            //         return new JsonResponse(['message' => 'JadwalAbsen Shift hanya bisa di update di hari libur pegawai'], 409);
            //     }

            //     // return new JsonResponse([
            //     //     'today' => $today,
            //     //     'ada' => $adaJadwal,
            //     //     'request' => $request->all(),
            //     //     'existing' => $jadwal
            //     // ], 200);
            // }

            // update atau buat baru jika tidak ada masalah
            $data = JadwalAbsen::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'day' => $request->day,
                    'id' => $request->id
                ],
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
