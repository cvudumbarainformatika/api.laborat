<?php

namespace App\Models\Simrs\Billing\Rajal;

use App\Models\Antrean\Dokter;
use App\Models\Simrs\Kasir\Kwitansilog;
use App\Models\Simrs\Kasir\Pembayaran;
use App\Models\Simrs\Master\Dokter as MasterDokter;
use App\Models\Simrs\Master\Mobat;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Penjaminan\Klaimrajal;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajallalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanhedlalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanrincilalu;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use App\Models\Simrs\Psikologitrans\Psikologitrans;
use App\Models\Simrs\Tindakan\Tindakan;
use App\Models\Simrs\Visite\Visite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Allbillrajal extends Model
{
    use HasFactory;
    protected $table = 'rs17';
    protected $guarded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';

    public function masterpasien()
    {
        return $this->hasMany(Mpasien::class, 'rs1', 'rs2');
    }

    public function relmpoli()
    {
        return $this->belongsTo(Mpoli::class, 'rs8', 'rs1');
    }

    public function msistembayar()
    {
        return $this->belongsTo(Msistembayar::class, 'rs14', 'rs1');
    }

    public function apotekrajalpolilaluumum()
    {
        return $this->hasMany(Apotekrajallalu::class, 'rs1', 'rs1');
    }

    public function apotekracikanrajalumum()
    {
        return $this->hasManyThrough(
            Apotekrajalracikanrincilalu::class,
            Apotekrajalracikanhedlalu::class,
            'rs1',
            'rs1'
        );
    }
    public function apotekrajalpolilalu()
    {
        return $this->hasMany(Apotekrajallalu::class, 'rs1', 'rs1');
    }

    public function apotekracikanrajal()
    {
        return $this->hasManyThrough(
            Apotekrajalracikanrincilalu::class,
            Apotekrajalracikanhedlalu::class,
            'rs1',
            'rs1'
        );
    }

    public function laborat()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }

    public function radiologi()
    {
        return $this->hasMany(Transpermintaanradiologi::class, 'rs1', 'rs1');
    }

    // public function radiologi()
    // {
    //     return $this->hasManyThrough(
    //         Transradiologi::class,
    //         Transpermintaanradiologi::class,
    //         'rs1','rs1'
    //     );
    // }

    public function dokter()
    {
        return $this->hasOne(MasterDokter::class, 'rs1', 'rs9');
    }

    public function rekammdedikumum()
    {
        return $this->hasMany(Pembayaran::class, 'rs1', 'rs1');
    }

    public function tindakanpoliumum()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function visiteumum()
    {
        return $this->hasMany(Visite::class, 'rs1', 'rs1');
    }

    public function psikologtransumum()
    {
        return $this->hasMany(Psikologitrans::class, 'rs1', 'rs1');
    }

    public function pendapatanumum()
    {
        return $this->hasMany(Kwitansilog::class, 'noreg', 'rs1');
    }

    public function pendapatanallbpjs()
    {
        return $this->hasMany(Klaimrajal::class, 'noreg', 'rs1');
    }

    public function biayarekammedik()
    {
        return $this->hasMany(Pembayaran::class, 'rs1', 'rs1');
    }

    public function biayakartuidentitas()
    {
        return $this->hasMany(Pembayaran::class, 'rs1', 'rs1');
    }

    public function biayapelayananpoli()
    {
        return $this->hasMany(Pembayaran::class, 'rs1', 'rs1');
    }

    public function biayakonsulantarpoli()
    {
        return $this->hasMany(Pembayaran::class, 'rs1', 'rs1');
    }

    public function tindakanall()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function kamaroperasi()
    {
        return $this->hasMany(Kamaroperasi::class, 'rs1','rs1');
    }

    public function tindakanoperasi()
    {
        return $this->hasMany(Tindakan::class, 'rs1','rs1');
    }

    public function tindakanfisioterapi()
    {
        return $this->hasMany(Tindakan::class, 'rs1','rs1');
    }

    public function tindakanhd()
    {
        return $this->hasMany(Tindakan::class, 'rs1','rs1');
    }

    public function tindakananastesidiluarokdanicu()
    {
        return $this->hasMany(Tindakan::class, 'rs1','rs1');
    }

    public function tindakanendoscopy()
    {
        return $this->hasMany(Tindakan::class, 'rs1','rs1');
    }

    public function tindakandokterperawat()
    {
        return $this->hasMany(Tindakan::class, 'rs1','rs1');
    }

    public function tindakancardio()
    {
        return $this->hasMany(Tindakan::class, 'rs1','rs1');
    }

    public function tindakaneeg()
    {
        return $this->hasMany(Tindakan::class, 'rs1','rs1');
    }

}
