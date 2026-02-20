<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiPanjar;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Siasik\Master\RekeningBank;
use App\Models\Siasik\TransaksiPjr\NPD_UP;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class NPD_UPController extends Controller
{
    public function bendaharapengeluaran()
    {
        $bendpengeluaran = Mpegawaisimpeg::whereIn('jabatan', ['J00035'])
        ->where('aktif', 'AKTIF')
        ->select('pegawai.nip',
                'pegawai.nama')
        ->first();
        return new JsonResponse($bendpengeluaran);
    }
    public function masterbank()
    {
        $bank = RekeningBank::where('noRek', '0121111136')->first();
        // ->when(request('q'), function ($query) {
        //     $cari = request('q');
        //     $query->where('noRek', 'like', '%' . $cari . '%')
        //           ->orWhere('namaRek', 'like', '%' . $cari . '%')
        //           ->orWhere('jabatan', 'like', '%' . $cari . '%');

        // })->get();
        return new JsonResponse($bank);
    }
    public function index(){
        $data = NPD_UP::all();
        return new JsonResponse($data);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'tglTrans' => 'required',
            'kdBendaharaKeluar' => 'required',
            'bendaharaKeluar' => 'required',
            'jumlahspp' => 'required',
            'bank' => 'required',
            'kodeRek' => 'required',
            'uraian' => 'required',
            
        ], [
            'tglTrans.required' => 'Tanggal Transaksi Harus di isi.',
            'kdBendaharaKeluar.required' => 'Kode Bendahara Pengeluaran Harus di isi.',
            'bendaharaKeluar.required' => 'Nama Bendahara Pengeluaran Harus di isi.',
            'jumlahspp.required' => 'Jumlah UP Harus di isi.',
            'bank.required' => 'Bank Harus di isi.',
            'kodeRek.required' => 'Kode Rekening Harus di isi.',
            'uraian.required' => 'Uraian Harus di isi.',
        ]);

        try {
            $time = date('Y-m-d H:i:s');
            $user = auth()->user()->pegawai_id;
            $pg= Pegawai::find($user);
            $pegawai= $pg->kdpegsimrs;

            if (empty($request->nosppup)) {
                DB::connection('siasik')->select('call nosppup(@nomor)');
                $x = DB::connection('siasik')->table('conter')->select('nosppup')->first();

                if (!$x) {
                    throw new \Exception('Gagal mendapatkan nomor dari prosedur notadinas');
                }
                $nomer = (int)$x->nosppup;
                $nosppup = FormatingHelper::nonotadinas($nomer, 'NPD-UP');
            } else {
                $nosppup = $request->nosppup;
            }
            DB::beginTransaction();

            $data = NPD_UP::updateOrCreate(
                [
                    'nosppup' => $nosppup
                ],
                [
                    'tglTrans' => $validated['tglTrans'],
                    'kdBendaharaKeluar' => $validated['kdBendaharaKeluar'],
                    'bendaharaKeluar' => $validated['bendaharaKeluar'],
                    'jumlahspp' => $validated['jumlahspp'],
                    'bank' => $validated['bank'],
                    'kodeRek' => $validated['kodeRek'],
                    'uraian' => $validated['uraian'],
                    'userentry' => $pegawai,
                    'tglentry' => $time,
                ]
            );

            DB::commit();
            return new JsonResponse(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $data]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['status' => 'error', 'message' => 'Data gagal disimpan: ' . $e->getMessage()], 500);
        }
    }

    public function kunci(Request $request)
    {
        try {
            $time = date('Y-m-d H:i:s');
            $data = NPD_UP::where('nosppup', $request->nosppup)->first();
            if (!$data) {
                return new JsonResponse(['status' => 'error', 'message' => 'Data Tidak Ditemukan'], 404);
            }
            if ($data->kunci == '1') {
                $user = auth()->user()->pegawai_id;
                $pg = Pegawai::find($user);
                 if (!$pg || $pg->kdpegsimrs !== 'sa') {
                    return response()->json(['message' => 'Anda tidak Memiliki Izin Membuka Kunci Data ini, Silahkan Hubungi Admin'], 403);
                }
                if (!empty($data->verif || $data->buktiCreateSpm)) {
                    return response()->json(['message' => 'Data Sudah Terverifikasi'], 400);
                }
                $data->kunci = '';
                $data->save();
                return new JsonResponse(['message' => 'Kunci Berhasil Dibuka'],200);
            } else {
                $data->kunci = '1';
                $data->tgl_kunci = $time;
                $data->save();
                return new JsonResponse(['message' => 'Data Berhasil Dikunci'],200);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => 'Data Gagal Dikunci: ' . $e->getMessage()], 500);
        }   
    }

    public function delete(Request $request)
    {
        $terkunci = NPD_UP::where('nosppup', $request->nosppup)
        ->where('kunci', 1)
        ->exists();
        if ($terkunci) {
            return new JsonResponse(['status' => 'error', 'message' => 'Data Masih Terkunci'], 404);
        }
        $finddata = NPD_UP::where('nosppup', $request->nosppup)->first();
        if (!$finddata) {
            return new JsonResponse(['status' => 'error', 'message' => 'Data Tidak Ditemukan'], 404);
        }
        try {
            NPD_UP::where('nosppup', $request->nosppup)->delete();
            return new JsonResponse(['status' => 'success', 'message' => 'Data Berhasil Dihapus']);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => 'Data Gagal Dihapus: ' . $e->getMessage()], 500);
        }   
    }


    public function belumVerif(){
        $data = NPD_UP::where('verif', '')->where('kunci', '1')->get();
        return new JsonResponse($data);
    }
    
     public function sudahVerif(){
        $data = NPD_UP::where('verif', '1')->where('kunci', '1')->get();
        return new JsonResponse($data);
    }
    public function verif(Request $request)
    {
        try {
            $time = date('Y-m-d H:i:s');
            $data = NPD_UP::where('nosppup', $request->nosppup)->first();
            if (!$data) {
                return new JsonResponse(['status' => 'error', 'message' => 'Data Tidak Ditemukan'], 404);
            }
            $user = auth()->user()->pegawai_id;
            $pg = Pegawai::find($user);
                if (!$pg || $pg->kdpegsimrs !== 'sa') {
                    return response()->json(['message' => 'Anda tidak Memiliki Izin Memverifikasi Data ini, Silahkan Hubungi Admin'], 403);
                }
            if ($data->verif == '1') {
                return response()->json([
                    'message' => 'Data sudah Diverifikasi'
                ], 400);
            }
        
            $data->verif = '1';
            $data->tgl_verif = $time;
            $data->userverif = $pg->kdpegsimrs;
            $data->kodeuserverif = $pg->kdpegsimrs;
            $data->save();

            return response()->json([
                'message' => 'Data Berhasil Diverifikasi'
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => 'Data Gagal Diverifikasi: ' . $e->getMessage()], 500);
        }   
    }

    public function databelumcreate(){
        $data = NPD_UP::where('verif', '1')->where('kunci', '1')->where('buktiCreateSpm', '')->get();
        return new JsonResponse($data);
    }
    public function datasudahcreate(){
        $data = NPD_UP::where('verif', '1')->where('kunci', '1')->where('buktiCreateSpm', '1')->get();
        return new JsonResponse($data);
    }
    
    public function createnpk(Request $request)
    {
        try {
            $time = date('Y-m-d H:i:s');
            $data = NPD_UP::where('nosppup', $request->nosppup)->first();
            if (!$data) {
                return new JsonResponse(['status' => 'error', 'message' => 'Data Tidak Ditemukan'], 404);
            }
            $user = auth()->user()->pegawai_id;
            $pg = Pegawai::find($user);
                if (!$pg || $pg->kdpegsimrs !== 'sa') {
                    return response()->json(['message' => 'Anda tidak Memiliki Izin Memverifikasi Data ini, Silahkan Hubungi Admin'], 403);
                }
            if ($data->buktiCreateSpm == '1') {
                return response()->json([
                    'message' => 'Data sudah Dibuat NPK'
                ], 400);
            }
        
            $data->buktiCreateSpm = '1';
            // $data->tgl_buktiCreateSpm = $time;
            // $data->userverif = $pg->kdpegsimrs;
            // $data->kodeuserverif = $pg->kdpegsimrs;
            $data->save();

            return response()->json([
                'message' => 'Data Berhasil Dibuat NPK'
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => 'Data Gagal Dibuat NPK: ' . $e->getMessage()], 500);
        }   
    }
}
