<?php

namespace App\Http\Controllers\Api\Antrean\master;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Video;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Mockery\Undefined;

class VideoController extends Controller
{
    public function index()
    {
        $data = Video::when(request('q'), function ($search, $q) {
            $search->where('nama', 'LIKE', '%' . $q . '%');
        })
            // ->with(['layanan'])
            ->latest()
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    // public function store(Request $request)
    // {

    //     $kode_layanan = null;
    //     if ($request->layanan_id !== null) {
    //         $layanan = Layanan::where('id_layanan', '=', $request->layanan_id)->first();
    //         $kode_layanan = $layanan->kode;
    //     }

    //     $data = Unit::updateOrCreate(
    //         [
    //             'id' => $request->id,
    //         ],
    //         [
    //             'loket' => $request->loket,
    //             'loket_no' => $request->loket_no,
    //             'layanan_id' => $request->layanan_id,
    //             'kuotajkn' => $request->kuotajkn,
    //             'kuotanonjkn' => $request->kuotanonjkn,
    //             'kode_layanan' => $kode_layanan,
    //             'display_id' => $request->display_id
    //         ]
    //     );

    //     if (!$data) {
    //         return new JsonResponse(['message' => "Gagal Menyimpan"], 500);
    //     }

    //     return new JsonResponse(['message' => "success"], 200);
    // }

    // public function destroy(Request $request)
    // {
    //     $id = $request->id;
    //     $data = Unit::where('id', $id);
    //     $del = $data->delete();

    //     if (!$del) {
    //         return response()->json([
    //             'message' => 'Error on Delete'
    //         ], 500);
    //     }

    //     // $user->log("Menghapus Data Jabatan {$data->nama}");
    //     return response()->json([
    //         'message' => 'Data sukses terhapus'
    //     ], 200);
    // }
}
