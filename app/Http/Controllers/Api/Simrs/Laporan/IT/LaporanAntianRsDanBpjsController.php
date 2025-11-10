<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\IT;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Bpjs\BpjsHttpRespon;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjsrespontime;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LaporanAntianRsDanBpjsController extends Controller
{
    //
    public function getListBpjsPost(Request $request)
    {
        // $data = $request->all();
        $data = self::getListFromBpjs(request('tgl'));
        return new JsonResponse(['data' => $data]);
    }
    public function getListBpjs()
    {
        // $data = request()->all();
        $data = self::getListFromBpjs(request('tgl'));
        return new JsonResponse(['data' => $data]);
    }
    public function getOneBpjs()
    {

        // $data = request()->all();
        // $data = self::getOneFromBpjs("");
        $data = self::getOneFromBpjs(request('kode'));
        return new JsonResponse(['data' => $data]);
    }
    public static function getListFromBpjs($request)
    {
        $data = BridgingbpjsHelper::get_url(
            'antrean',
            'antrean/pendaftaran/tanggal/' . $request
        );
        return $data;
    }
    public static function getOneFromBpjs($request)
    {
        // return $request;
        $encoded = urlencode($request);
        $data = BridgingbpjsHelper::get_url(
            'antrean',
            'antrean/pendaftaran/kodebooking/' . $encoded
        );
        return $data;
    }
    public function kirimUlangTaskId(Request $request)
    {

        foreach ($request->task as $task) {
            self::reUpdateWaktu($task);
        }
        $data['req'] = $request->all();
        $data['kunjungan'] = KunjunganPoli::select('rs1', 'rs1 as noreg')->where('rs1', $request->noreg)->with([
            'taskid' => function ($q) {
                $q->orderBy('taskid', 'DESC');
            },
            'bpjshttprespon'
        ])->first();
        $data['antrian'] = self::getOneFromBpjs($request->kodebooking);
        return new JsonResponse([
            'message' => 'Task Id sudah dikirim ulang',
            'data' => $data
        ]);
    }
    public static function reUpdateWaktu($head)
    {

        $kodebooking = $head['kodebooking'];
        $noreg = $head['noreg'];
        $taskid = $head['taskid'];
        $waktu = $head['waktu'];
        $user_id = auth()->user()->pegawai_id ?? 0004;

        $tgltobpjshttpres = date('Y-m-d H:i:s');

        Bpjsrespontime::create(
            [
                'kodebooking' => $kodebooking,
                'noreg' => $noreg,
                'taskid' => $taskid,
                'waktu' => $waktu,
                'created_at' =>  date('Y-m-d H:i:s'),
                'user_id' => $user_id
            ]
        );
        $data = [
            "kodebooking" => $kodebooking,
            "taskid" => $taskid,
            'waktu' => $waktu
        ];
        try {
            $updatewaktuantrian = BridgingbpjsHelper::post_url(
                'antrean',
                'antrean/updatewaktu',
                $data
            );

            // kalau sukses baru buat log
            if ($updatewaktuantrian) {
                Bpjs_http_respon::create([
                    'noreg' => $noreg,
                    'method' => 'POST',
                    'request' => $data,
                    'respon' => $updatewaktuantrian,
                    'url' => 'antrean/updatewaktu',
                    'tgl' => $tgltobpjshttpres
                ]);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Timeout atau koneksi gagal
            Log::error('BPJS timeout: ' . $e->getMessage());
        } catch (\Throwable $e) {
            // Error lain
            Log::error('BPJS update waktu gagal: ' . $e->getMessage() . ' noreg ' . $noreg);
        }
    }
    public static function cariPasienPerluKirimUlangTaskId()
    {
        // $tgl = Carbon::now()->subDay(10)->format('Y-m-d'); // buat percobaan
        $tgl = Carbon::now()->format('Y-m-d'); // ini yang real
        $listBpjs = self::getListFromBpjs($tgl);
        $result = $listBpjs['result'];
        $filtered = array_values(array_filter($result, function ($item) {
            return strtolower($item->status) === 'belum dilayani' || strtolower($item->status) === 'sedang dilayani';
        }));

        $nokapstList = array_column(
            array_map(fn($i) => ['nokapst' => $i->nokapst], $filtered),
            'nokapst'
        );
        $kunjungan = KunjunganPoli::query()
            ->select(
                'rs17.rs1', // iki tak munculne maneh gawe relasi with
                'rs17.rs1 as noreg',
                'rs17.rs2 as norm',
                'rs17.rs3 as tgl_kunjungan',
                'rs15.rs46 as noka',
            )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->with([
                'taskid' => function ($q) {
                    $q->orderBy('taskid', 'DESC');
                },
                'bpjshttprespon'
            ])
            ->whereBetween('rs17.rs3', [$tgl . ' 00:00:00', $tgl . ' 23:59:59'])
            ->where('rs17.rs8', '!=', 'POL014')
            ->where('rs19.rs4', '=', 'Poliklinik')
            ->whereIn('rs15.rs46', $nokapstList)
            ->get();
        $data = self::getTaskUpdateList($result, $kunjungan);
        $message = 'Tidak ada kirim ulang task id';
        if (is_array($data) && count($data) > 0) {
            // ada isinya
            $message = 'Ada kirim ulang task id';
            foreach ($data as $task) {
                self::reUpdateWaktu($task);
            }
        }

        return [
            'tgl' => $tgl,
            'message' => $message,
            // 'nokapstList' => $nokapstList,
            // 'data' => $data,
            // 'kunjungan' => $kunjungan,
            // 'filtered' => $filtered,
            // 'listBpjs' => $listBpjs,
        ];
    }
    public function kirirmUlang()
    {
        return self::cariPasienPerluKirimUlangTaskId();
    }
    /**
     * Menentukan data antrean yang perlu diupdate berdasarkan aturan logika
     */
    public static function getTaskUpdateList($result, $kunjungan)
    {
        $validTasks = [];

        foreach ($kunjungan as $k) {
            $taskList = collect($k->taskid ?? [])->map(fn($t) => (object) $t);
            $bpjsRes  = collect($k->bpjshttprespon ?? [])->map(fn($b) => (object) $b);

            $pair = collect($result)->where('nokapst', $k->noka);
            if ($pair->count() !== 1) continue;

            $pst = $pair->first();
            // if (strtolower($pst->status) !== 'belum dilayani') continue;
            $status = strtolower($pst->status);
            if ($status !== 'belum dilayani' && $status !== 'sedang dilayani') {
                continue;
            }

            // 1️⃣ Tentukan sumber data dulu
            $isMobile = $pst->sumberdata === 'Mobile JKN';

            // 2️⃣ Cari taskid terbesar dari data BPJS
            $maxTask  = $taskList->max('taskid') ?? 1;

            // 3️⃣ Cek apakah ada pesan "TaskId terakhir X"
            $lastTaskFromMsg = null;
            foreach ($bpjsRes as $res) {
                $msg = strtolower($res->respon['metadata']['message'] ?? '');
                if (preg_match('/taskid terakhir (\d+)/i', $msg, $matches)) {
                    $found = (int) $matches[1];
                    if (!$lastTaskFromMsg || $found > $lastTaskFromMsg) {
                        $lastTaskFromMsg = $found;
                    }
                }
            }

            if ($lastTaskFromMsg && $lastTaskFromMsg > $maxTask) {
                $maxTask = $lastTaskFromMsg;
            }

            // 4️⃣ Tentukan daftar task yang seharusnya ada
            $taskIds = collect(
                $maxTask == 7
                    ? ($isMobile ? [3, 4, 5, 6, 7] : [1, 2, 3, 4, 5, 6, 7])
                    : ($isMobile ? [3, 4, 5] : [1, 2, 3, 4, 5])
            );

            // 5️⃣ Sort hasil respons BPJS berdasarkan taskid
            $bpjsRes = $bpjsRes->sortBy(fn($r) => $r->request['taskid'] ?? 0)->values();

            // 6️⃣ Isi task yang hilang + waktu default
            $filled = $taskIds->map(function ($id) use ($bpjsRes, $k) {
                $found = $bpjsRes->first(
                    fn($r) =>
                    isset($r->request['taskid']) && (int)$r->request['taskid'] === (int)$id
                );

                if ($found) {
                    $waktu = $found->request['waktu'] ?? null;
                    $before = $bpjsRes->first(
                        fn($r) =>
                        isset($r->request['taskid']) && (int)$r->request['taskid'] === ($id - 1)
                    );
                    $prevWaktu = $before->request['waktu'] ?? null;
                    if ($prevWaktu && ($waktu <= $prevWaktu)) $waktu = $prevWaktu + 4000;

                    return (object)[
                        'taskid' => (int)$id,
                        'waktu'  => $waktu,
                        'code'   => $found->respon['metadata']['code'] ?? null,
                    ];
                }

                $before = $bpjsRes->first(
                    fn($r) =>
                    isset($r->request['taskid']) && (int)$r->request['taskid'] === ($id - 1)
                );
                $after = $bpjsRes->first(
                    fn($r) =>
                    isset($r->request['taskid']) && (int)$r->request['taskid'] === ($id + 1)
                );

                // 💡 kalau before/after dua-duanya gak ada, pakai jam 07:00 hari kunjungan
                $base  = strtotime(($k->tgl_kunjungan ?? now()) . ' 07:00:00');
                $waktu = $before
                    ? $before->request['waktu'] + 1800000
                    : ($after ? $after->request['waktu'] - 1800000 : $base);

                return (object)[
                    'taskid' => (int)$id,
                    'waktu'  => $waktu,
                    'code'   => 201,
                ];
            });

            // 7️⃣ Urutkan ASC dan pastikan waktu naik
            $filled = $filled->sortBy('taskid')->values();

            for ($i = 1; $i < $filled->count(); $i++) {
                if ($filled[$i]->waktu <= $filled[$i - 1]->waktu) {
                    $filled[$i]->waktu = $filled[$i - 1]->waktu + 4000;
                }
            }

            // 8️⃣ Ambil hanya yang code 201
            foreach ($filled as $t) {
                if ($t->code == 201) {
                    $validTasks[] = [
                        'kodebooking' => $pst->kodebooking,
                        'noreg'       => $k->noreg,
                        'taskid'      => $t->taskid,
                        'waktu'       => $t->waktu,
                    ];
                }
            }
        }

        return $validTasks;
    }
}
