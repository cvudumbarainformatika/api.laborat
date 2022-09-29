<?php

namespace App\Http\Controllers\Api\penunjang;

use App\Http\Controllers\Controller;
use App\Models\LaboratLuar;
use App\Models\Pasien;
use App\Models\PemeriksaanLaborat;
use App\Models\TransaksiLaborat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiLaboratLuarController extends Controller
{
    public function index()
    {
        $now = date('Y-m-d');
        $to = date('2018-05-02');
        $query = LaboratLuar::query()
                ->selectRaw('nota,tgl,nama,kelamin,alamat,tgl_lahir,pengirim,perusahaan_id,lunas,akhir,akhirx, kd_lab')
                ->filter(request(['q']))
                ->with(['perusahaan', 'pemeriksaan_laborat'])
                ->groupBy('nota')
                ->latest('id');
                // ->whereDate('rs3', '=', $now);
        $data = $query->paginate(request('per_page'));
        // $count = collect($query->get())->count();
                // ->simplePaginate(request('per_page'));

       return new JsonResponse($data);
    }

    public function get_details()
    {
        $data = LaboratLuar::query()
                ->where('nota', request('nota'))
                ->with(['perusahaan', 'pemeriksaan_laborat'])
                ->get();

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {

        // try {

        //     DB::beginTransaction();

            $temp = collect($request->details)->pluck('rs1');
            $data = PemeriksaanLaborat::whereIn('rs1',$temp)->get();

            $n = Carbon::now();
            $tgl = $n->toDateTimeString();

            $containers = [];

            foreach ($data as $key) {
                LaboratLuar::create([
                    'kd_lab' => $key->rs1,
                    'nama'=>$request->nama,
                    'kelamin'=>$request->kelamin,
                    'pengirim'=>$request->pengirim,
                    'tgl_lahir'=>$request->tgl_lahir,
                    'nota'=>$request->nota,
                    'alamat'=>$request->alamat,
                    'jenispembayaran'=>$request->jenispembayaran,
                    'nosurat'=>$request->nosurat,
                    'noktp'=>$request->noktp,
                    'agama'=>$request->agama,
                    'nohp'=>$request->nohp,
                    'kode_pekerjaan'=>$request->kode_pekerjaan,
                    'nama_pekerjaan'=>$request->nama_pekerjaan,
                    'sampel_diambil'=>$request->sampel_diambil,
                    'jam_sampel_diambil'=>$request->jam_sampel_diambil,
                    'tgl'=>$tgl,
                    'jml'=>1,
                ]);
            }

            // $data->transaksi_laborat_luar()->saveMany($containers);

            // $data->transaksi_laborat_luar()->createMany([
            //     'nama'=>$request->nama,
            //     'kelamin'=>$request->kelamin,
            //     'pengirim'=>$request->pengirim,
            //     'tgl_lahir'=>$request->tgl_lahir,
            //     'nota'=>$request->nota,
            //     'alamat'=>$request->alamat,
            //     'jenispembayaran'=>$request->jenispembayaran,
            //     'nosurat'=>$request->nosurat,
            //     'noktp'=>$request->noktp,
            //     'agama'=>$request->agama,
            //     'nohp'=>$request->nohp,
            //     // 'pekerjaan'=>$request->pekerjaan,
            //     'sampel_diambil'=>$request->sampel_diambil,
            //     'jam_sampel_diambil'=>$request->jam_sampel_diambil,
            //     'tgl'=>$tgl,
            //     'jml'=>1,
            // ]);

            // if (!$saved) {
            //     return new JsonResponse(['message'=>'Ada Kesalahan'], 500);
            // }
            return new JsonResponse(['message'=>'success'], 201);

        //     DB::commit();
        //     return response()->json(['message' => 'success'], 201);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        // }
    }
}
