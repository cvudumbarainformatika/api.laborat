<?php

namespace App\Http\Controllers\Api\Antrean\master;

use App\Events\AnjunganEvent;
use App\Events\ChatMessageEvent;
use App\Events\NotifMessageEvent;
use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Display;
use App\Models\Antrean\Panggil;
use App\Models\Antrean\PoliBpjs;
use App\Models\Antrean\Unit;
// use App\Models\Antrean\Unit;
use App\Models\Poli;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Mockery\Undefined;

class DisplayController extends Controller
{
    public function index()
    {
        // return new JsonResponse(['message' => 'ok']);
        $data = Display::when(request('q'), function ($search, $q) {
            $search->where('kode', 'LIKE', '%' . $q . '%');
        })
            // ->with(['unit'])
            ->orderBy('kode', 'ASC')
            // ->orderBy('loket_no', 'ASC')
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {

        $kode = 'A';
        $latest = Display::latest()->first();
        if (!$request->has('id')) {
            if ($latest) {
                $str = $latest->kode;
                $kode = ++$str;
            } else {
                $kode = 'A';
            }
        } else {
            $a = Display::find($request->id)->first();
            $kode = $a->kode;
        }

        // $validator = Validator::make($kode, [
        //     'kode' => 'required|unique:antrean.displays,kode, ' . $request->id
        // ]);

        // if ($validator->fails()) {
        //     return response()->json($validator->errors(), 422);
        // }


        $data = Display::updateOrCreate(
            [
                'id' => $request->id,
                'kode' => $kode,
            ],
            [
                'nama' => $request->nama,
                'keterangan' => $request->keterangan,
            ]
        );

        if (!$data) {
            return new JsonResponse(['message' => "Gagal Menyimpan"], 500);
        }

        return new JsonResponse(['message' => "success"], 200);
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $data = Display::where('id', $id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Jabatan {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }



    public function display()
    {
        $hr_ini = date('Y-m-d');
        $data = Display::where('kode', request('kode'))
            ->with([
                'poli.jumlahkunjunganpoli' => function ($q) use ($hr_ini) {
                    $q->select(
                        'rs17.rs1',
                        'rs17.rs1 as noreg',
                        'rs17.rs2',
                        'rs17.rs3',
                        'rs17.rs8',
                        'rs17.rs19 as status',
                        'antrian_ambil.nomor as noantrian'
                    )
                        ->leftjoin('antrian_ambil', 'antrian_ambil.noreg', 'rs17.rs1')
                        ->whereBetween('rs3', [$hr_ini . ' 00:00:00', $hr_ini . ' 23:59:59'])
                        ->where('antrian_ambil.nomor', 'NOT LIKE', '%FJ%')
                        ->orderby('antrian_ambil.nomor', 'ASC');
                },
                'poli.panggilan' => function ($q) use ($hr_ini) {
                    $q->whereBetween('tglkunjungan', [$hr_ini . ' 00:00:00', $hr_ini . ' 23:59:59'])
                        ->orderby('updated_at', 'DESC');
                }
            ])
            // ->with(['display', 'layanan', 'layanan.bookings' => function ($q) use ($hr_ini) {
            //     $q->whereBetween('tanggalperiksa', [$hr_ini . ' 00:00:00', $hr_ini . ' 23:59:59'])
            //         ->where('statuscetak', '=', 1)
            //         // ->where('statuspanggil', '=', 1)
            //         ->orderBy('angkaantrean', 'DESC');
            // }])
            ->first();
        if (!$data) {
            return response()->json(['message' => 'Maaf display belum ada'], 500);
        }

        return response()->json($data);
    }
    public function displayWithFarmasi()
    {
        $hr_ini = date('Y-m-d');
        $data = Display::where('kode', request('kode'))
            ->when(request('kode') === "B", function ($q) use ($hr_ini) {
                $q->with([
                    'poli.jumlahkunjunganpoli' => function ($q) use ($hr_ini) {
                        $q->select(
                            'rs17.rs1',
                            'rs17.rs1 as noreg',
                            'rs17.rs2',
                            'rs17.rs3',
                            'rs17.rs8',
                            'rs17.rs19 as status',
                            'antrian_ambil.nomor as noantrian'
                        )
                            ->leftjoin('antrian_ambil', 'antrian_ambil.noreg', 'rs17.rs1')
                            ->whereBetween('rs3', [$hr_ini . ' 00:00:00', $hr_ini . ' 23:59:59'])
                            ->where('antrian_ambil.nomor', 'LIKE', '%FJ%')
                            ->orderby('antrian_ambil.nomor', 'ASC');
                    },
                    'poli.panggilan' => function ($q) use ($hr_ini) {
                        $q->whereBetween('tglkunjungan', [$hr_ini . ' 00:00:00', $hr_ini . ' 23:59:59'])
                            ->orderby('updated_at', 'DESC');
                    }
                ]);
            })
            ->when(request('kode') !== "B", function ($q) use ($hr_ini) {
                $q->with([
                    'poli.jumlahkunjunganpoli' => function ($q) use ($hr_ini) {
                        $q->select(
                            'rs17.rs1',
                            'rs17.rs1 as noreg',
                            'rs17.rs2',
                            'rs17.rs3',
                            'rs17.rs8',
                            'rs17.rs19 as status',
                            'antrian_ambil.nomor as noantrian'
                        )
                            ->leftjoin('antrian_ambil', 'antrian_ambil.noreg', 'rs17.rs1')
                            ->whereBetween('rs3', [$hr_ini . ' 00:00:00', $hr_ini . ' 23:59:59'])
                            ->where('antrian_ambil.nomor', 'NOT LIKE', '%FJ%')
                            ->orderby('antrian_ambil.nomor', 'ASC');
                    },
                    'poli.panggilan' => function ($q) use ($hr_ini) {
                        $q->whereBetween('tglkunjungan', [$hr_ini . ' 00:00:00', $hr_ini . ' 23:59:59'])
                            ->orderby('updated_at', 'DESC');
                    }
                ]);
            })
            ->first();
        if (!$data) {
            return response()->json(['message' => 'Maaf display belum ada'], 500);
        }

        return response()->json($data);
    }

    public function send_panggilan(Request $request)
    {
        $msgEvent = [
            'data' => $request->all(),
        ];
        event(new NotifMessageEvent($msgEvent, $request->channel, auth()->user()));


        Panggil::updateOrCreate(
            ['noreg' => $request->noreg, 'noantrian' => $request->noantrian, 'kdpoli' => $request->kdpoli],
            ['channel' => $request->channel, 'tglkunjungan' => $request->tglkunjungan]
        )->increment('counter');
        return 'ok';
    }
    public function delete_panggilan(Request $request)
    {
        Panggil::where('nomorantrean', $request->nomorantrean)->delete();
        $message = array(
            'menu' => 'panggilan-berakhir',
            'data' => $request->nomorantrean
        );
        event(new AnjunganEvent($message));
        return response()->json($request->all(), 200);
    }

    public function get_weather()
    {
        $data = Http::get('http://api.weatherapi.com/v1/forecast.json?key=88a330fe969d462e919175655242101&q=probolinggo&days=3&aqi=no&alerts=no');
        return $data;
    }
}
