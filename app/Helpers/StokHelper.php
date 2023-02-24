<?php

namespace App\Helpers;

use App\Models\Sigarang\BarangRS;

class StokHelper
{
    public static function barangByDepo()
    {

        $raw = BarangRS::get();
        $col = collect($raw);
        $barang = $col->groupBy('kode_depo');
        return $barang;
    }
    public static function hitungTransaksiPemesanan($header)
    {
    }
    public static function hitungTransaksiPenerimaan($header)
    {
    }
    public static function hitungTransaksiDistribusiDepo($header)
    {
    }
    public static function hitungTransaksiDistribusiLangsung($header)
    {
    }
    public static function hitungTransaksiPermintaanRuangan($header)
    {
    }
    // by kode barang
    public static function hitungTransaksiPemesananByKodeBarang($header)
    {
    }
    public static function hitungTransaksiPenerimaanByKodeBarang($header)
    {
    }
    public static function hitungTransaksiDistribusiDepoByKodeBarang($header)
    {
    }
    public static function hitungTransaksiDistribusiLangsungByKodeBarang($header)
    {
    }
    public static function hitungTransaksiPermintaanRuanganByKodeBarang($header)
    {
    }
}
