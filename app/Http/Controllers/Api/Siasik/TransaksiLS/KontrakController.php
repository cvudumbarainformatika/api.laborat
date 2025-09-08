<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use Illuminate\Http\Request;
use App\Helpers\FormatingHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use DateTime;

class KontrakController extends Controller
{
    public function listkontrak()
    {
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->nip;
        $sa = $pg->kdpegsimrs;
        $tahunawal=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $tahun=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $data = KontrakPengerjaan::whereBetween('tgltrans', [$tahunawal.'-01-01', $tahun.'-12-31']);
        if ($sa !== 'sa') {
            $data->where('kodepptk', $pegawai);
        }
        $kontrak=$data->when(request('q'),function ($query) {
            $query->where('nokontrak', 'LIKE', '%' . request('q') . '%')
            ->orWhere('namaperusahaan', 'LIKE', '%' . request('q') . '%')
            ->orWhere('nilaikontrak', 'LIKE', '%' . request('q') . '%')
            ->orWhere('nokontrakx', 'LIKE', '%' . request('q') . '%')
            ->orWhere('kegiatanblud', 'LIKE', '%' . request('q') . '%');
        })
        ->orderBy('tglentry', 'desc')
        ->get();
        // ->paginate(request('per_page'));

        return new JsonResponse($kontrak);
    }
    public function simpankontrak(Request $request)
    {
        $time = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;
        $nomor = $request->nokontrak ?? self::buatnomor();
        try {
            DB::beginTransaction();
             $simpan = KontrakPengerjaan::updateOrCreate(
            [
                'nokontrak'=> $nomor,
            ],
            [
                'kodeperusahaan' => $request->kodeperusahaan ?? '',
                'namaperusahaan' => $request->namaperusahaan ?? '',
                'tglmulaikontrak' => $request->tglmulaikontrak ?? '',
                'tglakhirkontrak' => $request->tglakhirkontrak,
                'tgltrans' => $request->tgltrans ?? '',
                'tglentry' => $time ?? '',
                'kodepptk' => $request->kodepptk ?? '',
                'namapptk' => $request->namapptk ?? '',
                'program' => 'PROGRAM PENUNJANG URUSAN PEMERINTAH DAERAH KABUPATEN/KOTA',
                'kegiatan' => 'PELAYANAN DAN PENUNJANG PELAYANAN BLUD',
                'kodekegiatanblud' => $request->kodekegiatanblud ?? '',
                'kegiatanblud' => $request->kegiatanblud ?? '',
                'kodemapingrs' => $request->kodemapingrs ?? '',
                'namasuplier' => $request->namasuplier ?? '',
                'nilaikontrak' => $request->nilaikontrak ?? '',
                'kodeBagian' => $request->kodeBagian ?? '',
                'nokontrakx' => $request->nokontrakx ?? '',
                'termin' => $request->termin ?? '',
                'userentry'=>$pegawai ?? '',
                'kunci'=> '1'
            ]
        );
        return new JsonResponse(
                [
                    'message' => 'Data Berhasil disimpan...!!!',
                    'result' => $simpan,
                ], 200);
        } catch (\Exception $er) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Ada Kesalahan',
                'error' => $er
            ], 500);
        }

    }
    public function deletedata(Request $request){
        $data = KontrakPengerjaan::where('nokontrak', $request->nokontrak)->first();
        if(!$data){
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 404);
        }
        // Validasi field kunci
        if ($data->kunci === '1') {
            return new JsonResponse(['message' => 'Data tidak dapat dihapus karena terkunci'], 403);
        }

        $data->delete();
        return new JsonResponse([
            'message' => 'Data Berhasil dihapus',
             'data' => $data
        ]);
    }

    public static function buatnomor()
    {
        // Panggil stored procedure kontrakPekerjaan
        DB::connection('siasik')->select('call kontrakPekerjaan(@nomor)');
        $x = DB::connection('siasik')->table('conter')->select('kontrakPekerjaan')->first();

        if (!$x) {
            throw new \Exception('Gagal mendapatkan nomor dari prosedur kontrakPekerjaan');
        }
        $no = (int)$x->kontrakPekerjaan; // Pastikan $no adalah integer

        $huruf = 'KP';
        date_default_timezone_set('Asia/Jakarta');
        $rom = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $thn = date('Y');

        // Format $no ke 5 digit dengan padding nol
        $no = str_pad($no, 5, '0', STR_PAD_LEFT);

        // Gabungkan format nomor kontrak
        $sambung = $no . '/' . $rom[date('n')] . '/' . strtoupper($huruf) . '/' . $thn;

        return $sambung;
    }

    public function kuncidata(Request $request)
    {
        $request->validate([
            'nokontrak' => 'required|string'
        ]);

        try {
            $header = KontrakPengerjaan::where('nokontrak', $request->nokontrak)->first();

            if (!$header) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }

            if ($header->kunci == '1') {
                // Buka kunci → harus superadmin
                $user = auth()->user()->pegawai_id;
                $pg = Pegawai::find($user);

                if (!$pg || $pg->kdpegsimrs !== 'sa') {
                    return response()->json(['message' => 'Anda tidak Memiliki Izin Membuka Kunci Data ini, Silahkan Hubungi Admin'], 403);
                }

                if (!empty($header->nonpdls)) {
                    return response()->json(['message' => 'Serahterima Sudah di NPD-LS'], 400);
                }

                $header->kunci = '';
                $header->save();

                return response()->json(['message' => 'Kunci berhasil dibuka'], 200);
            } else {
                $header->kunci = '1';
                $header->save();

                return response()->json(['message' => 'Data berhasil dikunci'], 200);
            }

        } catch (\Exception $e) {
            // Log::error('Gagal membuka kunci serahterima: ' . $e->getMessage());

            return response()->json([
                'message' => 'Terjadi kesalahan saat membuka kunci',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
