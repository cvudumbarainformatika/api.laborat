<?php

namespace App\Http\Controllers\Api\Pegawai\User;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\Libur;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TroubleController extends Controller
{
    public function index()
    {
        $date = request('tanggal'); // Replace with your date
        $hari = date('l', strtotime($date));
        $anu = JadwalAbsen::select('pegawai_id')
            ->where('kategory_id', '>=', 3)
            ->where('day', $hari);
        if (request('dispen_masuk') === 'true' && request('dispen_pulang') === 'false') {
            $anu->whereBetween('masuk', [request('mulai'), request('selesai')]);
        } else if (request('dispen_masuk') === 'false' && request('dispen_pulang') === 'true') {
            $anu->whereBetween('pulang', [request('mulai'), request('selesai')]);
        } else if (request('dispen_masuk') === 'true' && request('dispen_pulang') === 'false') {
            $anu->where(function ($q) {
                $q->whereBetween('masuk', [request('mulai'), request('selesai')])
                    ->orWhereBetween('pulang', [request('mulai'), request('selesai')]);
            });
        }
        $idpeg = $anu->distinct()
            ->get();
        $data = Pegawai::where('aktif', '=', 'AKTIF')
            ->where(function ($query) {
                $query->when(request('flag') ?? false, function ($search, $q) {
                    return $search->where('flag', '=', $q);
                });
                $query->when(request('ruang') ?? false, function ($search, $q) {
                    return $search->where('ruang', '=', $q);
                });
            })
            ->whereIn('id', $idpeg)
            ->filter(request(['q']))
            ->with(['ruangan', 'user'])
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }
    public function store(Request $request)
    {
        // $data = $request->all();
        $coll = $request->user_ids;
        $ids = explode(',', $coll);

        foreach ($ids as $user_id) {
            Libur::updateOrCreate(
                [
                    'user_id' => $user_id,
                    'tanggal' => $request->tanggal
                ],
                [
                    'flag' => $request->flag,
                    'alasan' => $request->alasan,
                ]
            );
        }
        return new JsonResponse($ids);
    }
}
