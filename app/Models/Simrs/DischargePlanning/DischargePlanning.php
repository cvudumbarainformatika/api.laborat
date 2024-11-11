<?php

namespace App\Models\Simrs\Edukasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DischargePlanning extends Model
{
    use HasFactory;
    protected $table = 'rs242';
    protected $guarded = ['id'];
}
