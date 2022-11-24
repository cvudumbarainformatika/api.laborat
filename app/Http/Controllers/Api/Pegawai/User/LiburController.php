<?php

namespace App\Http\Controllers\Api\Pegawai\User;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Libur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LiburController extends Controller
{
    //
    public function index()
    {
        $data = Libur::orderBy(request('order_by'), request('sort'))
            ->with('user')
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }

    public function month()
    {
        $tahun = request('tahun') ? request('tahun') : date('Y');
        $bulan = request('bulan') ? request('bulan') : date('m');
        $from = $tahun . '-' . $bulan . '-01';
        $to = $tahun . '-' . $bulan . '-31';
        $data = Libur::where('tanggal', '>=', $from)
            ->where('tanggal', '<=', $to)
            ->with('user')
            ->get();

        foreach ($data as $key) {
            $temp = explode('-', $key['tanggal']);
            $day = $temp[2];
            $key['day'] = $day;
        }
        return new JsonResponse($data);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tanggal' => 'required',
            'flag' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse(['message' => 'isi data yang belum terisi'], 422);
        }
        $path = '';
        $data = Libur::create($request->all());
        if (!$data) {
            return new JsonResponse(['message' => 'Gagal menyimpan data', 'request' => $request->all()], 500);
        }
        if ($request->has('gambar')) {
            $path = $request->file('gambar')->store('image', 'public');
            // array_merge($request, ['image' => $path]);
            $data->update(['image' => $path]);
        }

        return new JsonResponse(['message' => 'Berhasil menyimpan data', 'request' => $request->all()], 201);
    }
}
