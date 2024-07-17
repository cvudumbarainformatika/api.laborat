<?php

namespace App\Models\Simrs\Ranap;

use App\Models\Simrs\Kasir\Rstigalimax;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mruangranap extends Model
{
    use HasFactory;
    protected $table = 'rs24';
    protected $gurded = ['id'];
    public $timestamps = false;
    protected $keyType = 'string';
    protected $connection = 'mysql';

    public function rstigalimax()
    {
        return $this->hasMany(Rstigalimax::class, 'rs16', 'rs4');
    }
}
