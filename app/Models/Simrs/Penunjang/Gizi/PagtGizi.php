<?php

namespace App\Models\Simrs\Penunjang\Gizi;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagtGizi extends Model
{
    use HasFactory;
    protected $table = 'pagt_gizi';
    protected $guarded = ['id'];

    protected $casts = [
      'rw_peny_dhl' => 'array',
    ];


    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'user_id');
    }
}
