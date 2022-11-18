<?php

namespace App\Http\Controllers\Api\Pegawai\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Hari;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\Kategory;
use App\Models\Sigarang\Pegawai;
use App\Models\User;
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
    // mobile auth jwt
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
    // desktop auth api
    public function getByUserDesk()
    {
        // return new JsonResponse(['to' => $to, 'from' => $from]);
        $user = auth()->user();
        $data = JadwalAbsen::where('user_id', $user->id)
            ->orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public static function toMatch($id, $absen)
    {
        // isinya match jadwal dengan user ybs
        $data = User::find($id);
        $jadwal = JadwalAbsen::where('user_id', $id)->get();
        $today = date('D');
        $result = [
            'data' => $data,
            'jadwal' => $jadwal,
            'today' => $today,
            'absen' => $absen,
        ];
        return $result;
    }
    public function store(Request $request)
    {


        // try {
        //     DB::beginTransaction();
        //validate data
        $valid = Validator::make($request[0], ['user_id' => 'required']);
        if ($valid->fails()) {
            return new JsonResponse([$valid->errors(), 422]);
        }
        $jadwal = JadwalAbsen::where('user_id', $request[0]['user_id'])->first();
        if (!$jadwal) {
            $jumlah = count($request->all());
            if ($jumlah < 7) {
                return new JsonResponse(['message' => 'jumlah data yang di kirim kurang'], 411);
            }
            foreach ($request->all() as $key) {
                // return new JsonResponse($key);
                // update atau buat baru jika tidak ada masalah
                $data = JadwalAbsen::create(
                    [
                        'user_id' => $key['user_id'],
                        'pegawai_id' => $key['pegawai_id'],
                        'ruang_id' => $key['ruang_id'],
                        'day' => $key['day'],
                        'hari' => $key['hari']
                    ]
                );
                if ($key['status'] === '1') {
                    $data->update([
                        'status' => $key['status'],
                        'masuk' => $key['masuk'],
                        'pulang' => $key['pulang'],
                        'jam' => $key['jam'],
                        'menit' => $key['menit'],
                    ]);
                } else {
                    $data->update([
                        'status' => $key['status'],
                        'masuk' => null,
                        'pulang' => null,
                        'jam' => null,
                        'menit' => null,
                    ]);
                }
            }
            return new JsonResponse(['message' => 'Jadwal telah dibuat'], 201);
        }
        // $data = User::with('jadwal')->find($request[0]['user_id']);
        // return new JsonResponse($data->jadwal);
        foreach ($request->all() as $key) {

            $data = JadwalAbsen::where('day', '=', $key['day'])->first();
            if ($key['status'] === '1') {
                $data->update([
                    'status' => $key['status'],
                    'masuk' => $key['masuk'],
                    'pulang' => $key['pulang'],
                    'jam' => $key['jam'],
                    'menit' => $key['menit'],
                ]);
            } else {
                $data->update([
                    'status' => $key['status'],
                    'masuk' => null,
                    'pulang' => null,
                    'jam' => null,
                    'menit' => null,
                ]);
            }
        }

        // DB::commit();
        // if (!$data->wasRecentlyCreated) {
        $status = 200;
        $pesan = 'Data telah di perbarui';
        // } else {
        // $status = 201;
        // $pesan = 'Data telah di tambakan';
        // }
        return new JsonResponse(['message' => $pesan], $status);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return new JsonResponse([
        //         'message' => 'ada kesalahan',
        //         'error' => $e
        //     ], 500);
        // }
    }
    public function destroy(Request $request)
    {
        // $auth = auth()->user()->id;
        $data = JadwalAbsen::find($request->id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Jadwal Absen {$data->nama}");
        return new JsonResponse([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
