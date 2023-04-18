<?php

namespace App\Http\Controllers\Api\Pegawai\User;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Alpha;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\Libur;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LiburController extends Controller
{
    //
    public function index()
    {
        // Model1::where('postID', $postID)
        // ->join('database2.table2 as db2', 'Model1.id', '=', 'db2.id')
        // ->select(['Model1.*', 'db2.firstName', 'db2.lastName'])
        // ->orderBy('score', 'desc')
        // ->get();
        $data = Libur::with(['user' => function ($q) {
            $q->when(request('q'), function ($a, $b) {
                $a->where('nama', 'LIKE', '%' . $b . '%');
            });
        }])
            ->orderBy(request('order_by'), request('sort'))
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
    public function ramadhan(Request $request)
    {
        $anu = [];
        foreach ($request->all() as $key) {

            if ($key['kategory'] === 1) {
                $temp = JadwalAbsen::where('kategory_id', $key['kategory'])
                    ->whereIn('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis'])
                    ->update(['pulang' => $key['pulang']]);
                $temp1 = JadwalAbsen::where('kategory_id', $key['kategory'])
                    ->whereIn('hari', ['Jumat'])
                    ->update(['pulang' => $key['Jumat']]);
                if ($temp) {
                    array_push($anu, $temp);
                }
                if ($temp1) {
                    array_push($anu, $temp1);
                }
            }
            // if ($key['kategory'] === 2) {
            //     $temp2 = JadwalAbsen::where('kategory_id', $key['kategory'])
            //         ->whereIn('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])
            //         ->update(['masuk' => $key['masuk']]);
            //     array_push($anu, $temp2);
            // }
        }
        return new JsonResponse(['message' => 'Jadwal diganti ke Jadwal Ramdhan']);
    }
    public function lebaran()
    {
        $temp = JadwalAbsen::where('kategory_id', 1)
            ->whereIn('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis'])
            ->update(['pulang' => '16:00:00']);
        $temp1 = JadwalAbsen::where('kategory_id', 1)
            ->whereIn('hari', ['Jumat'])
            ->update(['pulang' => '13:00:00']);

        // $temp2 = JadwalAbsen::where('kategory_id', 2)
        //     ->whereIn('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])
        //     ->update(['masuk' => '07:00:00']);

        return new JsonResponse(['messaga' => 'Jadwal kembali Normal']);
    }

    public function tulisTidakMasuk()
    {
        $today = date('l');
        $date = date('Y-m-d');
        $jadwal = JadwalAbsen::where('day', $today)
            ->where('status', 2)
            ->get();
        $absen = TransaksiAbsen::where('tanggal', $date)->get();
        $peg = collect($absen)->map(function ($x) {
            return $x->pegawai_id;
        });
        $not = collect($jadwal)->whereNotIn('pegawai_id', $peg);
        foreach ($not as $tidak) {
            Alpha::updateOrCreate(
                [
                    'pegawai_id' => $tidak->pegawai_id,
                    'tanggal' => $date
                ],
                ['flag' => 'ABSEN']
            );
        }
        $tidakDaftar = Pegawai::where('account_pass', null)->where('aktif', 'AKTIF')->get();
        foreach ($tidakDaftar as $tidak) {
            Alpha::updateOrCreate(
                [
                    'pegawai_id' => $tidak->id,
                    'tanggal' => $date
                ],
                ['flag' => 'ABSEN']
            );
        }

        // $data['tidak masuk'] = Alpha::where('tanggal', $date)->get();

        return new JsonResponse(['message' => 'sudah di tulis']);
    }

    public function delete(Request $request)
    {
        $data = Libur::find($request->id);
        $data->delete();
        if (!$data) {
            return new JsonResponse(['message' => 'Data gagal dihapus'], 410);
        }
        return new JsonResponse(['message' => 'Data sudah dihapus'], 200);
    }
}
