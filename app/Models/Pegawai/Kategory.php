<?php

namespace App\Models\Pegawai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategory extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];
    protected $appends = [];
    protected $referenceDate = null;
    protected static $ramadhanCache = [];

    public function setReferenceDate($date)
    {
        $this->referenceDate = $date;
        return $this;
    }

    public function getMasukAttribute($value)
    {
        if ($this->id == 1 || $this->id == 2) {
            $checkDate = $this->referenceDate ?? now()->toDateString();
            $cacheKey = $this->id . '_' . $checkDate;

            if (!isset(self::$ramadhanCache[$cacheKey])) {
                self::$ramadhanCache[$cacheKey] = \Illuminate\Support\Facades\DB::connection('kepex')->table('ramadhans')
                    ->where('kategory_id', $this->id)
                    ->where('dari', '<=', $checkDate)
                    ->where('sampai', '>=', $checkDate)
                    ->first();
            }

            $ramadhan = self::$ramadhanCache[$cacheKey];

            if ($ramadhan) {
                return $ramadhan->masuk;
            }
        }
        return $value;
    }

    public function getPulangAttribute($value)
    {
        if ($this->id == 1 || $this->id == 2) {
            $checkDate = $this->referenceDate ?? now()->toDateString();
            $cacheKey = $this->id . '_' . $checkDate;

            if (!isset(self::$ramadhanCache[$cacheKey])) {
                self::$ramadhanCache[$cacheKey] = \Illuminate\Support\Facades\DB::connection('kepex')->table('ramadhans')
                    ->where('kategory_id', $this->id)
                    ->where('dari', '<=', $checkDate)
                    ->where('sampai', '>=', $checkDate)
                    ->first();
            }

            $ramadhan = self::$ramadhanCache[$cacheKey];

            if ($ramadhan) {
                return $ramadhan->pulang;
            }
        }
        return $value;
    }

    // protected $casts = [
    //     'hari' => 'array',
    //     'jam' => 'array',
    // ];

    public function pertama()
    {
        return $this->belongsTo(Hari::class, 'hari_01');
    }

    public function kedua()
    {
        return $this->belongsTo(Hari::class, 'hari_02');
    }
    public function ketiga()
    {
        return $this->belongsTo(Hari::class, 'hari_03');
    }
    public function keempat()
    {
        return $this->belongsTo(Hari::class, 'hari_04');
    }
    public function kelima()
    {
        return $this->belongsTo(Hari::class, 'hari_05');
    }
    public function keenam()
    {
        return $this->belongsTo(Hari::class, 'hari_06');
    }

    public function jam_reguler()
    {
        return $this->belongsTo(Jam::class, 'jam_01');
    }
    public function jam_jumat()
    {
        return $this->belongsTo(Jam::class, 'jam_02');
    }
    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nama', 'LIKE', '%' . $query . '%');
            // ->orWhere('kode', 'LIKE', '%' . $query . '%');
        });
    }
}
