<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KontrakPengerjaan extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $table = 'kontrakpengerjaan_header';
    protected $fillable = [];

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nokontrak', 'LIKE', '%' . $query . '%')
                ->orWhere('namaperusahaan', 'LIKE', '%' . $query . '%');
            // ->orWhere('kodemapingrs', 'LIKE', '%' . $query . '%');
        });
    }
}
