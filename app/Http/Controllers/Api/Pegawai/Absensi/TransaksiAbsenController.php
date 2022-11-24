<?php

namespace App\Http\Controllers\Api\Pegawai\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TransaksiAbsenController extends Controller
{
    //

    public function index()
    {
        $thisYear = date('Y');
        $thisMonth = request('month') ? request('month') : date('m');
        $per_page = request('per_page') ? request('per_page') : 10;
        $user = User::where('id', '>', 3)->oldest('id')->filter(request(['q']))->paginate($per_page);
        $userCollections = collect($user);
        $users = $userCollections->only('data');
        $users->all();
        $meta = $userCollections->except('data');
        $meta->all();
        // $temp = [
        //     'data' => $users,
        //     'meta' => $meta,
        // ];
        // return new JsonResponse($temp);
        $data = [];
        foreach ($users['data'] as $key) {
            // return new JsonResponse($key);
            $temp = TransaksiAbsen::whereDate('tanggal', '>=', $thisYear . '-' . $thisMonth . '-01')
                ->whereDate('tanggal', '<=', $thisYear . '-' . $thisMonth . '-31')
                ->where('user_id', $key['id'])
                ->with('user', 'kategory')
                ->get();
            $tanggals = [];
            foreach ($temp as $key) {
                // return new JsonResponse($key);
                $temp = explode('-', $key['tanggal']);
                // $temp = explode('-', $key->tanggal);
                $day = $temp[2];
                // $day = $this->getDayName($temp[2]);
                $key['day'] = $day;

                $toIn = explode(':', $key['kategory']->masuk);
                $act = explode(':', $key['masuk']);
                $jam = (int)$act[0] - (int)$toIn[0];
                $menit =  (int)$act[1] - (int)$toIn[1];
                $detik =  (int)$act[2] - (int)$toIn[2];

                if ($jam > 0 || $menit > 40) {
                    $key['terlambat'] = 'yes';
                } else {
                    $key['terlambat'] = 'no';
                }
                $dMenit = $menit >= 10 ? $menit : '0' . $menit;
                $dDetik = $detik >= 10 ? $detik : '0' . $detik;
                $diff = $jam . ':' . $dMenit . ':' . $dDetik;
                $key['diff'] = $diff;
            }

            // $data[$key['id']] = $temp;
            array_push($data, [$key['id'] => $temp]);
        }
        return new JsonResponse($data);
        // $tanggals = [];
        // foreach ($data as $key) {
        //     return new JsonResponse($key);
        //     $temp = explode('-', $key['tanggal']);
        //     // $temp = explode('-', $key->tanggal);
        //     $day = $temp[2];
        //     // $day = $this->getDayName($temp[2]);
        //     $key['day'] = $day;

        //     $toIn = explode(':', $key['kategory']->masuk);
        //     $act = explode(':', $key['masuk']);
        //     $jam = (int)$act[0] - (int)$toIn[0];
        //     $menit =  (int)$act[1] - (int)$toIn[1];
        //     $detik =  (int)$act[2] - (int)$toIn[2];

        //     if ($jam > 0 || $menit > 40) {
        //         $key['terlambat'] = 'yes';
        //     } else {
        //         $key['terlambat'] = 'no';
        //     }
        //     $dMenit = $menit >= 10 ? $menit : '0' . $menit;
        //     $dDetik = $detik >= 10 ? $detik : '0' . $detik;
        //     $diff = $jam . ':' . $dMenit . ':' . $dDetik;
        //     $key['diff'] = $diff;
        // }

        $collects = collect($data);
        $userGroup = $collects->groupBy('user_id');
        $apem = [];
        foreach ($userGroup as $key => $value) {
            $telat = $value->where('terlambat', 'yes')->count();
            $total = $value->where('terlambat')->count();
            // $key['value'] = $key;
            array_push($apem, ['total' => $total, 'telat' => $telat, 'user_id' => $value[0]->user_id]);
        }
        $userGroup['apem'] = $apem;
        // foreach ($apem as &$key) {
        //     array_push($userGroup[$key['user_id']], $key);
        // }




        return new JsonResponse($userGroup, 200);
        // return new JsonResponse([
        //     'data' => $userGroup,
        //     'telat' => $telat,
        // ], 200);
    }

    public function getRekapByUser()
    {
        $user = JWTAuth::user();
        $thisYear = request('tahun') ? request('tahun') : date('Y');
        $month = request('bulan') ? request('bulan') : date('m');
        $per_page = request('per_page') ? request('per_page') : 10;
        $data = TransaksiAbsen::where('user_id', $user->id)
            ->whereDate('tanggal', '>=', $thisYear . '-' . $month . '-01')
            ->whereDate('tanggal', '<=', $thisYear . '-' . $month . '-31')
            // ->paginate($per_page);
            ->with('kategory')
            ->get();
        $tanggals = [];
        foreach ($data as $key) {
            $temp = date('Y/m/d', strtotime($key['tanggal']));
            $week = date('W', strtotime($key['tanggal']));
            $toIn = explode(':', $key['kategory']->masuk);
            $act = explode(':', $key['masuk']);
            $jam = (int)$act[0] - (int)$toIn[0];
            $menit =  (int)$act[1] - (int)$toIn[1];
            $detik =  (int)$act[2] - (int)$toIn[2];

            if ($jam > 0 || $menit > 10) {
                $key['terlambat'] = 'yes';
            } else {
                $key['terlambat'] = 'no';
            }
            $dMenit = $menit >= 10 ? $menit : '0' . $menit;
            $dDetik = $detik >= 10 ? $detik : '0' . $detik;
            $diff = $jam . ':' . $dMenit . ':' . $dDetik;
            $key['diff'] = $diff;
            $key['week'] = $week;
            array_push($tanggals, $temp);
        };
        $collects = collect($data);
        $grouped = $collects->groupBy('week');
        $telat = $collects->where('terlambat', 'yes')->count();
        return new JsonResponse([
            'telat' => $telat,
            'weeks' => $grouped,
            'tanggals' => $tanggals,
            'data' => $data,
            'user' => $user,
        ], 200);

        return new JsonResponse($data);
    }
    public function getRekapPerUser()
    {
        $user = User::find(request('id'));
        $thisYear = request('tahun') ? request('tahun') : date('Y');
        $month = request('bulan') ? request('bulan') : date('m');
        // $per_page = request('per_page') ? request('per_page') : 10;
        $data = TransaksiAbsen::where('user_id', $user->id)
            ->whereDate('tanggal', '>=', $thisYear . '-' . $month . '-01')
            ->whereDate('tanggal', '<=', $thisYear . '-' . $month . '-31')
            ->orderBy(request('order_by'), request('sort'))
            ->with('kategory')
            ->get();
        $tanggals = [];
        foreach ($data as $key) {
            $temp = date('Y/m/d', strtotime($key['tanggal']));
            $week = date('W', strtotime($key['tanggal']));
            $toIn = explode(':', $key['kategory']->masuk);
            $act = explode(':', $key['masuk']);
            $jam = (int)$act[0] - (int)$toIn[0];
            $menit =  (int)$act[1] - (int)$toIn[1];
            $detik =  (int)$act[2] - (int)$toIn[2];

            if ($jam > 0 || $menit > 10) {
                $key['terlambat'] = 'yes';
            } else {
                $key['terlambat'] = 'no';
            }
            $dMenit = $menit >= 10 ? $menit : '0' . $menit;
            $dDetik = $detik >= 10 ? $detik : '0' . $detik;
            $diff = $jam . ':' . $dMenit . ':' . $dDetik;
            $key['diff'] = $diff;
            $key['week'] = $week;
            array_push($tanggals, $temp);
        };
        $collects = collect($data);
        $grouped = $collects->groupBy('week');
        $telat = $collects->where('terlambat', 'yes')->count();
        return new JsonResponse([
            'telat' => $telat,
            'weeks' => $grouped,
            'tanggals' => $tanggals,
            'data' => $data,
        ], 200);
    }

    public function getDayName($day)
    {
        $temp = '';
        switch ($day) {
            case '01':
                $temp = 'satu';
                break;
            case '02':
                $temp = 'dua';
                break;
            case '03':
                $temp = 'tiga';
                break;
            case '04':
                $temp = 'empat';
                break;
            case '05':
                $temp = 'lima';
                break;
            case '06':
                $temp = 'enam';
                break;
            case '07':
                $temp = 'tujuh';
                break;
            case '08':
                $temp = 'delapan';
                break;
            case '09':
                $temp = 'sembilan';
                break;
            case '10':
                $temp = 'sepuluh';
                break;
            case '11':
                $temp = 'sebelas';
                break;
            case '12':
                $temp = 'duabelas';
                break;
            case '13':
                $temp = 'tigabelas';
                break;
            case '14':
                $temp = 'empatbelas';
                break;
            case '15':
                $temp = 'limabelas';
                break;
            case '16':
                $temp = 'enambelas';
                break;
            case '17':
                $temp = 'tujuhbelas';
                break;
            case '18':
                $temp = 'delapanbelas';
                break;
            case '19':
                $temp = 'sembilanbelas';
                break;
            case '20':
                $temp = 'duapuluh';
                break;
            case '21':
                $temp = 'duapuluhsatu';
                break;
            case '22':
                $temp = 'duapuluhdua';
                break;
            case '23':
                $temp = 'duapuluhtiga';
                break;
            case '24':
                $temp = 'duapuluhempat';
                break;
            case '25':
                $temp = 'duapuluhlima';
                break;
            case '26':
                $temp = 'duapuluhenam';
                break;
            case '27':
                $temp = 'duapuluhtujuh';
                break;
            case '28':
                $temp = 'duapuluhdelapan';
                break;
            case '29':
                $temp = 'duapuluhsembilan';
                break;
            case '30':
                $temp = 'tigapuluh';
                break;
            case '31':
                $temp = 'tigapuluhsatu';
                break;

            default:
                'enol';
                break;
        }
        return $temp;
    }
}
