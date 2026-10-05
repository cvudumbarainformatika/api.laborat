<?php

namespace App\Models\Simrs\Anamnesis;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anamnesis extends Model
{
    use HasFactory, LogsActivity;
    protected $connection = 'mysql';
    protected $table = 'rs209';
    protected $guarded = ['id'];

    public function getRiwayatalergiAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        if (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) {
            return array_values(array_filter(array_map('trim', explode(',', $decoded))));
        }
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }


    public function datasimpeg()
    {
        return  $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'user');
    }

    public function petugas()
    {
       return $this->hasOne(Petugas::class, 'kdpegsimrs','user');
    }

    public function keluhannyeri()
    {
        return $this->hasOne(KeluhanNyeri::class, 'rs209_id', 'id');
    }
    public function skreeninggizi()
    {
        return $this->hasOne(SkreeningGizi::class, 'rs209_id', 'id');
    }

    public function kebidanan()
    {
        return $this->hasOne(Kebidanan::class, 'rs209_id', 'id');
    }
    public function neonatal()
    {
        return $this->hasOne(Neonatal::class, 'rs209_id', 'id');
    }
    public function pediatrik()
    {
        return $this->hasOne(Pediatrik::class, 'rs209_id', 'id');
    }
    public function anamnesetambahan()
    {
        return $this->hasMany(AnamnesisTambahan::class, 'id_heder', 'id');
    }
    public function anamnesebps()
    {
        return $this->hasOne(AnamnesisBps::class, 'id_heder', 'id');
    }
    public function anamnesenips()
    {
        return $this->hasOne(AnamnesisNips::class, 'id_heder', 'id');
    }
}
