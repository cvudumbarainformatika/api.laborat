<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiLaborat extends Model
{
    use HasFactory;
    protected $table = 'rs51';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function kunjungan_poli()
    {
        return $this->belongsTo(KunjunganPoli::class, 'rs1', 'rs1');
    }
    public function kunjungan_rawat_inap()
    {
        return $this->belongsTo(KunjunganRawatInap::class, 'rs1', 'rs1');
    }
    public function poli()
    {
        return $this->belongsTo(Poli::class, 'rs23', 'rs1');
    }
    public function ruangan_rawat_inap()
    {
        return $this->belongsTo(RuanganRawatInap::class, 'rs23', 'rs4');
    }

    public function pemeriksaan_laborat() // data master
    {
        return $this->belongsTo(PemeriksaanLaborat::class, 'rs4', 'rs1');
    }

    public function dokter() // data master DOKTER
    {
        return $this->belongsTo(Dokter::class, 'rs8', 'rs1');
    }

    public function scopeFilter($search, array $reqs)
    {

        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('rs2', 'LIKE', '%' . $query . '%')
                    ->orWhere('rs23', 'LIKE', '%' . $query . '%')
                    ->orWhereHas('kunjungan_poli.pasien', function($where) use ($query) {
                        return $where->where('rs2', 'LIKE', '%' . $query . '%')
                                ->orWhere('rs1', 'LIKE', '%' . $query . '%');
                    })
                    ->orWhereHas('kunjungan_rawat_inap.pasien', function($where) use ($query) {
                        return $where->where('rs2', 'LIKE', '%' . $query . '%')
                        ->orWhere('rs1', 'LIKE', '%' . $query . '%');
                    });
        });
        $search->when($reqs['periode'] ?? false, function ($search, $query) {
            $y = Carbon::now()->subYears(2);
            if ($query == 2) {
                return $search
                ->whereDate('rs3', '=', date('Y-m-d'))
                ->where('rs20', '<>', '');
            }
            elseif ($query == 3) {
                return $search->whereYear('rs3', $y)
                ->whereDate('rs3', '<', date('Y-m-d'))
                                ->where('rs20', '=', '');
            }
            elseif ($query == 4) {
                return $search->whereYear('rs3', $y)
                ->whereDate('rs3', '<', date('Y-m-d'))
                            ->where('rs20', '<>', '');
            }
            else {
                return $search->whereDate('rs3', '=', date('Y-m-d'))
                ->where('rs20', '=', '');
            }
        });

        // $search->when($reqs['status'] ?? false, function ($search, $sta) {
        //     return $search->where(['status'=>$sta]);
        // });

        // $search->when($reqs['category'] ?? false, function ($search, $query) {
        //     return $search->whereHas('categories', function($finder) use ($query) {
        //         if ($query !== 'all') {
        //             $finder->where('url', $query);
        //         }

        //     });
        // });
    }
}
