<?php

namespace App\Http\Controllers\Api\Antrean\master;

use App\Events\AnjunganEvent;
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
            ->with(['unit'])
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
        $data = Unit::where('display_id', request('kode'))
            ->with(['display', 'layanan', 'layanan.bookings' => function ($q) use ($hr_ini) {
                $q->whereBetween('tanggalperiksa', [$hr_ini . ' 00:00:00', $hr_ini . ' 23:59:59'])
                    ->where('statuscetak', '=', 1)
                    // ->where('statuspanggil', '=', 1)
                    ->orderBy('angkaantrean', 'DESC');
            }])
            ->get();
        if (!$data) {
            return response()->json('Maaf display belum ada');
        }

        return response()->json($data);
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
}
