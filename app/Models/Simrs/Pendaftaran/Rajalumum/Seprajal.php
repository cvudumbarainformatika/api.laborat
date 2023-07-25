<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seprajal extends Model
{
    use HasFactory;
    protected $table = 'rs222';
    protected $guarded = ['id'];
    protected $appends = ['noref'];
    public $timestamps = false;

    public function getNorefAttribute()
    {
        if ($this->noDpjp === '') {
            $noref = $this->rs5;
        }
        return $noref;
    }
}
