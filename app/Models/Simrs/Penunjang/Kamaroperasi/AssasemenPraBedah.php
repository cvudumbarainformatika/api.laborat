<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssasemenPraBedah extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'komplikasi' => 'array'
    ];
    public function user()
    {
        return $this->belongsTo(Petugas::class, 'user_input', 'kdpegsimrs');
    }
}
