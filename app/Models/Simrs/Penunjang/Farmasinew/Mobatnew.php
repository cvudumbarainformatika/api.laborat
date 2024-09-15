<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\TransaksiLS\NpdLS_rinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\BarangRusak;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_r;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\StokOpnameFisik;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mobatnew extends Model
{
    use HasFactory;
    //   use SoftDeletes;
    protected $table = 'new_masterobat';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';


    public function getHargaAttribute()
    {
        $kdobat = $this->kd_obat;
        $daftar = DaftarHarga::selectRaw('max(harga) as harga')
            ->where('kd_obat', $kdobat)
            ->orderBy('tgl_mulai_berlaku', 'desc')
            ->limit(5)
            ->first();
        $harga = $daftar->harga;
        return $harga;
    }
    public function scopeMobat($data)
    {
        return $data->select([
            'kd_obat as kodeobat',
            'nama_obat as namaobat'
        ]);
    }

    public function kodebelanja()
    {
        return $this->belongsTo(Mkodebelanjaobat::class, 'kode108', 'kode');
    }
    public function scopeFilter($cari, array $reqs)
    {
        $cari->when(
            $reqs['q'] ?? false,
            function ($data, $query) {
                return $data->where('flag', '')
                    ->where('kd_obat', 'LIKE', '%' . $query . '%')
                    ->orWhere('nama_obat', 'LIKE', '%' . $query . '%')
                    ->orderBy('nama_obat');
            }
        );
    }

    public function indikasi()
    {
        return $this->hasMany(IndikasiObat::class, 'kd_obat', 'kd_obat');
    }
    public function mkelasterapi()
    {
        return $this->hasMany(Mapingkelasterapi::class, 'kd_obat', 'kd_obat');
    }


    public function kfa()
    {
        return $this->hasOne(MapingKfa::class, 'kd_obat', 'kd_obat');
    }
    public function onestok()
    {
        return $this->hasOne(Stokreal::class, 'kdobat', 'kd_obat');
    }
    public function onefisik()
    {
        return $this->hasOne(StokOpnameFisik::class, 'kdobat', 'kd_obat');
    }
    public function fisik()
    {
        return $this->hasMany(StokOpnameFisik::class, 'kdobat', 'kd_obat');
    }
    public function stok()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kd_obat');
    }
    public function stokrealgudang()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kd_obat');
    }

    public function stokrealallrs()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kd_obat');
    }

    public function stokmaxrs()
    {
        return $this->hasMany(Mminmaxobat::class, 'kd_obat', 'kd_obat');
    }

    public function perencanaanrinci()
    {
        return $this->hasMany(RencanabeliR::class, 'kdobat', 'kd_obat');
    }
    public function pemesananrinci()
    {
        return $this->hasMany(PemesananRinci::class, 'kdobat', 'kd_obat');
    }

    public function stokrealgudangko()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kd_obat');
    }

    public function stokrealgudangfs()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kd_obat');
    }

    public function stokmaxpergudang()
    {
        return $this->hasMany(Mminmaxobat::class, 'kd_obat', 'kd_obat');
    }
    public function saldoawal()
    {
        return $this->hasMany(Stokopname::class, 'kdobat', 'kd_obat');
    }
    public function saldoakhir()
    {
        return $this->hasMany(Stokopname::class, 'kdobat', 'kd_obat');
    }
    public function oneopname()
    {
        return $this->hasOne(Stokopname::class, 'kdobat', 'kd_obat');
    }

    public function penerimaanrinci()
    {
        return $this->hasMany(PenerimaanRinci::class, 'kdobat', 'kd_obat');
    }
    public function mutasi()
    {
        return $this->hasMany(Mutasigudangkedepo::class, 'kd_obat', 'kd_obat');
    }
    public function mutasimasuk()
    {
        return $this->hasMany(Mutasigudangkedepo::class, 'kd_obat', 'kd_obat');
    }
    public function mutasikeluar()
    {
        return $this->hasMany(Mutasigudangkedepo::class, 'kd_obat', 'kd_obat');
    }

    public function persiapanoperasirinci()
    {
        return $this->hasMany(PersiapanOperasiRinci::class, 'kd_obat', 'kd_obat');
    }
    public function persiapanoperasiretur()
    {
        return $this->hasMany(PersiapanOperasiRinci::class, 'kd_obat', 'kd_obat');
    }
    public function persiapanoperasikeluar()
    {
        return $this->hasMany(PersiapanOperasiRinci::class, 'kd_obat', 'kd_obat');
    }
    public function resepkeluar()
    {
        return $this->hasMany(Resepkeluarrinci::class, 'kdobat', 'kd_obat');
    }
    public function resepkeluarracikan()
    {
        return $this->hasMany(Resepkeluarrinciracikan::class, 'kdobat', 'kd_obat');
    }

    public function permintaandeporinci()
    {
        return $this->hasMany(Permintaandeporinci::class, 'kdobat', 'kd_obat');
    }

    public function transnonracikan()
    {
        // return $this->hasMany(Resepkeluarrinci::class, 'kdobat', 'kdobat'); //diganti ke permintaan
        return $this->hasMany(Permintaanresep::class, 'kdobat', 'kd_obat');
    }

    public function transracikan()
    {
        // return $this->hasMany(Resepkeluarrinciracikan::class, 'kdobat', 'kdobat');
        return $this->hasMany(Permintaanresepracikan::class, 'kdobat', 'kd_obat');
    }

    public function persiapanrinci()
    {
        return $this->hasMany(PersiapanOperasiRinci::class, 'kd_obat', 'kd_obat');
    }
    public function distribusipersiapan()
    {
        return $this->hasMany(PersiapanOperasiDistribusi::class, 'kd_obat', 'kd_obat');
    }
    public function persiapanretur()
    {
        return $this->hasMany(PersiapanOperasiDistribusi::class, 'kd_obat', 'kd_obat');
    }
    public function returpenjualan()
    {
        return $this->hasMany(Returpenjualan_r::class, 'kdobat', 'kd_obat');
    }
    public function permintaanobatrinci()
    {
        return $this->hasMany(Permintaandeporinci::class, 'kdobat', 'kd_obat');
    }
    public function barangrusak()
    {
        return $this->hasMany(BarangRusak::class, 'kd_obat', 'kd_obat');
    }

    public function pagu()
    {
        return $this->belongsTo(PergeseranPaguRinci::class, 'kode108', 'koderek108');
    }
    public function realisasi()
    {
        return $this->hasMany(NpdLS_rinci::class, 'koderek108', 'kode108');
    }
}
