<?php

namespace App\Models;

use App\Models\Simrs\Homecare\HomeCareKunjungan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mpyw\EloquentHasByJoin\EloquentHasByJoinServiceProvider;

class TransaksiLaborat extends Model
{
    use HasFactory;
    protected $table = 'rs51';

    protected $guarded = ['id'];

    public $timestamps = false;
    
    protected $connection = 'mysql';

    public function kunjungan_poli()
    {
        return $this->belongsTo(KunjunganPoli::class, 'rs1', 'rs1');
    }
    public function kunjungan_rawat_inap()
    {
        return $this->belongsTo(KunjunganRawatInap::class, 'rs1', 'rs1');
    }
    public function homecare_kunjungan()
    {
        return $this->belongsTo(HomeCareKunjungan::class, 'rs1', 'noreg');
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

    public function pasien_kunjungan_poli()
    {
        return $this->hasOneThrough(
            Pasien::class,
            KunjunganPoli::class,
            'rs1', // Foreign key on the kunjungan poli table...
            'rs1', // Foreign key on the pasien table...
            'rs1', // Local key on the transaksi laborat table...
            'rs2' // Local key on the pasien table...
        );
    }
    public function pasien_kunjungan_rawat_inap()
    {
        return $this->hasOneThrough(
            Pasien::class,
            KunjunganRawatInap::class,
            'rs1', // Foreign key on the kunjungan rawat inap table...
            'rs1', // Foreign key on the pasien table...
            'rs1', // Local key on the transaksi laborat table...
            'rs2' // Local key on the pasien table...
        );
    }

    // sistembayar
    public function sb_kunjungan_poli()
    {
        return $this->hasOneThrough(
            SistemBayar::class,
            KunjunganPoli::class,
            'rs1', // Foreign key on the kunjungan poli table...
            'rs1', // Foreign key on the pasien table...
            'rs1', // Local key on the transaksi laborat table...
            'rs14' // Local key on the pasien table...
        );
    }
    public function sb_kunjungan_rawat_inap()
    {
        return $this->hasOneThrough(
            SistemBayar::class,
            KunjunganRawatInap::class,
            'rs1', // Foreign key on the kunjungan poli table...
            'rs1', // Foreign key on the pasien table...
            'rs1', // Local key on the transaksi laborat table...
            'rs19' // Local key on the sistembayar table...
        );
    }

    public function scopeFilter($search, array $reqs)
    {

        $search->when($reqs['q'] ?? false, function ($search, $query) use ($reqs) {
            $filterBy = $reqs['filter_by'];
            if ($filterBy == 1 || $filterBy == 2) {
                $column = $filterBy == 1 ? 'rs2' : 'rs1';
                $search->where(function ($visits) use ($column, $query) {
                    $visits->hasByNonDependentSubquery('kunjungan_poli', function ($visit) use ($column, $query) {
                        $visit->hasByNonDependentSubquery('pasien', fn ($patient) => $patient->where($column, 'LIKE', '%' . $query . '%'));
                    })
                        ->orHasByNonDependentSubquery('kunjungan_rawat_inap', function ($visit) use ($column, $query) {
                            $visit->hasByNonDependentSubquery('pasien', fn ($patient) => $patient->where($column, 'LIKE', '%' . $query . '%'));
                        })
                        ->orHasByNonDependentSubquery('homecare_kunjungan', function ($visit) use ($column, $query) {
                            $visit->hasByNonDependentSubquery('masterpasien', fn ($patient) => $patient->where($column, 'LIKE', '%' . $query . '%'));
                        });
                });
            } else {
                $search->where('rs2', 'LIKE', '%' . $query . '%');
            }
        });
        $search->when($reqs['periode'] ?? false, function ($search, $query) {
            // pasien hari ini sudah
            if ($query == 2) {
                return $search
                    ->whereDate('rs51.rs3', '=', date('Y-m-d'))
                    ->where('rs51.rs20', '<>', '');
            } elseif ($query == 3) {
                // pasien lalu
                return
                    $search->whereDate('rs51.rs3', '<', date('Y-m-d'))
                    ->where('rs51.rs20', '=', '');
            } elseif ($query == 4) {
                // pasien lalu sudah
                return $search->whereDate('rs51.rs3', '<', date('Y-m-d'))
                    ->where('rs51.rs20', '<>', '');
            } else {
                // pasien hari ini
                return $search->whereDate('rs51.rs3', '=', date('Y-m-d'))
                    ->where('rs51.rs20', '=', '');
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
