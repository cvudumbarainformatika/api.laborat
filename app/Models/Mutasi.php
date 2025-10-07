<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mutasi extends Model
{
    use HasFactory;

    protected $table = 'rs44';
    protected $guarded = ['id'];


    public $timestamps = false;

    public function serah_terima()
    {
        return $this->hasOne(SerahTerima::class, 'rs44_id', 'id');
    }

    
}
