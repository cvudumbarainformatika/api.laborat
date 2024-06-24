<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\TemplateEresep;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use App\Models\Simrs\Penunjang\Farmasinew\Template\Templateresep;
use App\Models\Simrs\Penunjang\Farmasinew\Template\TemplateResepRacikan;
use App\Models\Simrs\Penunjang\Farmasinew\Template\TemplateResepRinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

class TemplateController extends Controller
{
    public function cariobat()
    {
      $listobat = Mobatnew::query()
      ->select(
        'new_masterobat.kd_obat',
        'new_masterobat.nama_obat as namaobat',
        'new_masterobat.kandungan as kandungan',
        'new_masterobat.bentuk_sediaan as bentuk_sediaan',
        'new_masterobat.satuan_k as satuankecil',
        'new_masterobat.status_fornas as fornas',
        'new_masterobat.status_forkid as forkit',
        'new_masterobat.status_generik as generik',
        'new_masterobat.status_kronis as kronis',
        'new_masterobat.status_prb as prb',
        'new_masterobat.kode108',
        'new_masterobat.uraian108',
        'new_masterobat.kode50',
        'new_masterobat.uraian50',
        'new_masterobat.kekuatan_dosis as kekuatandosis',
        'new_masterobat.volumesediaan as volumesediaan',
        'new_masterobat.kelompok_psikotropika as psikotropika'
    )
      ->where(function ($query) {
      $query->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%')
          ->orWhere('new_masterobat.kandungan', 'LIKE', '%' . request('q') . '%');
      })->limit(30)->get();
        return new JsonResponse(
          ['dataobat' => $listobat]
        );
    }

    public function simpantemplate(Request $request)
    {
        $user = auth()->user()->pegawai_id;
        $request->request->add(['pegawai_id' => $user]);

        $cek = Templateresep::where('pegawai_id', $user)->where('nama', $request->nama)->where('kodedepo', $request->kodedepo)->first();
        if ($cek) {
          // return new JsonResponse(['message' => 'Maaf ... Template sudah ada, ganti nama'], 406);
          return self::updatetemplate($request, $cek);
        }

        // iF save not update
        return self::store($request);
    }

    public static function updatetemplate($request, $template)
    {
       try {
        //code...
        DB::beginTransaction();
        TemplateResepRinci::where('template_id', $template->id)->delete();

        $rinci = $request->items;
        foreach ($rinci as $key => $value) {
            $simpanRincian = [
                'kodeobat'  => $value['kodeobat'],
                'namaobat'  => $value['namaobat'],
                'forkit'  => $value['forkit'],
                'fornas'  => $value['fornas'],
                'generik'  => $value['generik'],
                'kandungan'  => $value['kandungan'],
                'kekuatandosis'  => $value['kekuatandosis'],
                'keterangan'  => $value['keterangan'],
                'kode50'  => $value['kode50'],
                'kode108'  => $value['kode108'],
                'konsumsi'  => $value['konsumsi'],
                'racikan'  => $value['racikan'] === true ? 1 : 0,
                'satuan_kcl'  => $value['satuan_kcl'],
                'signa'  => $value['signa'],
                'jumlah_diminta'  => $value['jumlah_diminta'],
                'tiperacikan'  => $value['tiperacikan'],
                'tiperesep'  => $value['tiperesep'],
                'template_id'  => $template->id
            ];

            $rincian = TemplateResepRinci::create($simpanRincian);
            if ($value['racikan'] === true && $value['kodeobat'] === $rincian->kodeobat) {
              // hapus dulu rincian racikan
              TemplateResepRacikan::where('obat_id', $rincian->id)->delete();
              foreach ($value['rincian'] as $k => $val) {
                $racikan = [
                  'obat_id'  => $rincian->id,
                  'kodeobat'  => $val['kodeobat'],
                  'namaobat'  => $val['namaobat'],
                  'forkit'  => $val['forkit'],
                  'fornas'  => $val['fornas'],
                  'generik'  => $val['generik'],
                  // 'kandungan'  => $val['kandungan'],
                  'kekuatandosis'  => $val['kekuatandosis'],
                  'keterangan'  => $val['keterangan'],
                  'kode50'  => $val['kode50'],
                  'kode108'  => $val['kode108'],
                  // 'konsumsi'  => $val['konsumsi'],
                  'satuan_kcl'  => $val['satuan_kcl'],
                  // 'signa'  => $val['signa'],
                  'jumlah_diminta'  => $val['jumlah_diminta'],
                  'dosis'  => $val['dosis'],
                  // 'tiperacikan'  => $val['tiperacikan'],
                  // 'tiperesep'  => $val['tiperesep'],
                ];

                TemplateResepRacikan::create($racikan);
              }
            }
        };
        DB::commit();
        return new JsonResponse($template->load(['rincian.rincian']), 200);

       } catch (\Throwable $th) {
          DB::rollback();
          return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $th], 500);
       }
    }

    public static function store($request)
    {
        try {
          DB::beginTransaction();
          $saved = Templateresep::create($request->all());
          
          $rinci = $request->items;
        foreach ($rinci as $key => $value) {
            $simpanRincian = [
                'kodeobat'  => $value['kodeobat'],
                'namaobat'  => $value['namaobat'],
                'forkit'  => $value['forkit'],
                'fornas'  => $value['fornas'],
                'generik'  => $value['generik'],
                'kandungan'  => $value['kandungan'],
                'kekuatandosis'  => $value['kekuatandosis'],
                'keterangan'  => $value['keterangan'],
                'kode50'  => $value['kode50'],
                'kode108'  => $value['kode108'],
                'konsumsi'  => $value['konsumsi'],
                'racikan'  => $value['racikan'] === true ? 1 : 0,
                'satuan_kcl'  => $value['satuan_kcl'],
                'signa'  => $value['signa'],
                'jumlah_diminta'  => $value['jumlah_diminta'],
                'tiperacikan'  => $value['tiperacikan'],
                'tiperesep'  => $value['tiperesep'],
                'template_id'  => $saved->id
            ];

            $rincian = TemplateResepRinci::create($simpanRincian);
            if ($value['racikan'] === true && $value['kodeobat'] === $rincian->kodeobat) {
              foreach ($value['rincian'] as $k => $val) {
                $racikan = [
                  'obat_id'  => $rincian->id,
                  'kodeobat'  => $val['kodeobat'],
                  'namaobat'  => $val['namaobat'],
                  'forkit'  => $val['forkit'],
                  'fornas'  => $val['fornas'],
                  'generik'  => $val['generik'],
                  // 'kandungan'  => $val['kandungan'],
                  'kekuatandosis'  => $val['kekuatandosis'],
                  'keterangan'  => $val['keterangan'],
                  'kode50'  => $val['kode50'],
                  'kode108'  => $val['kode108'],
                  // 'konsumsi'  => $val['konsumsi'],
                  'satuan_kcl'  => $val['satuan_kcl'],
                  // 'signa'  => $val['signa'],
                  'jumlah_diminta'  => $val['jumlah_diminta'],
                  'dosis'  => $val['dosis'],
                  // 'tiperacikan'  => $val['tiperacikan'],
                  // 'tiperesep'  => $val['tiperesep'],
                ];

                TemplateResepRacikan::create($racikan);
              }
            }
            
        };
          DB::commit();
          return new JsonResponse($saved->load(['rincian.rincian']), 200);

        } catch (\Throwable $th) {
          DB::rollback();
          return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $th], 500);
        }
    }

    public function gettemplate()
    {
        $data = Templateresep::where('pegawai_id', auth()->user()->pegawai_id)
        ->where('kodedepo', request('kodedepo'))
        ->with('rincian.rincian')
        ->get();

        return new JsonResponse($data, 200);
    }


    public function order(Request $request)
    {
        $user = auth()->user()->pegawai_id;
        $request->request->add(['pegawai_id' => $user]);

        $items = collect($request->items);
        $racikan = $items->where('racikan', true);
        $nonRacikan = $items->where('racikan', false);

        $cekNonRacikan = self::cekStokNonRacikan($nonRacikan, $request->kodedepo, $request->groupsistembayar);
        // return $cekNonRacikan;
        $cekRacikan = self::cekStokRacikan($racikan, $request->kodedepo, $request->groupsistembayar);

        

        $a = count($cekNonRacikan) > 0 ? collect($cekNonRacikan)->pluck('isError')->toArray() : []; 
        $b = count($cekRacikan) > 0 ? collect($cekRacikan)->pluck('isError')->toArray() : []; 
        $merged = array_merge(array_values($a), array_values($b));
        // if (count($a) > 0 && count($b) > 0) {
        //   # code...
        //   $merged = $a->merge($b);
        // }

        // return $merged;
        $msg = 'ok';
        $isError = false;
        if (in_array(true, (array)$merged)) {
            $isError= true;
            $msg = 'Gagal Alokasi Kurang';
        } else {
            $isError= false;
        }



        $data = [
          'message' => $msg,
          'isError' => $isError,
          'nonRacikan' => $cekNonRacikan,
          'racikan' => $cekRacikan
        ];

        return new JsonResponse(
            $data ,
            $isError? 410 : 200);
    }

    public static function cekStokNonRacikan($nonRacikan, $kodedepo, $sistembayar)
    {
        $kodeobat = $nonRacikan->pluck('kodeobat');
        $adaRaw = self::cekJumlahStok($kodeobat, $kodedepo, $sistembayar, false);
        return self::outputRaw($adaRaw, $nonRacikan, $sistembayar);
        // return $adaRaw;
    }

    public static function cekStokRacikan($racikan, $kodedepo, $sistembayar)
    {
        $racik = $racikan->map(function ($x) use ($kodedepo, $sistembayar) {
          $obat['koderacikan'] = $x['kodeobat'];
          $obat['sistembayar'] = $sistembayar;
          $rincian = collect($x['rincian']);
          $obat['rincian'] = count($rincian) > 0 ? $rincian->implode('kodeobat', ','): null;
          $obat['kodedepo'] = $kodedepo;
          return $obat;
        });

        $kode = $racik->pluck('rincian')
        ->map(function (string $kd) {
            return explode(',', $kd);
        })
        ->flatten();

        $adaRaw = self::cekJumlahStok($kode, $kodedepo, $sistembayar, true);
        return self::outputRaw($adaRaw, $racikan, $sistembayar);
    }

    public static function cekJumlahStok($obat, $kodedepo, $sistembayar, $racikan)
    {
      $uniqueObat = $obat;
      $limitHargaTertinggi = 5;
      $cekjumlahstok = Stokreal::query()
      ->select(
        'stokreal.kdobat as kdobat',
        DB::raw('sum(stokreal.jumlah) as jumlahstok'),
        'new_masterobat.nama_obat as nama_obat',
        'new_masterobat.kandungan as kandungan',
        'new_masterobat.status_fornas as fornas',
        'new_masterobat.status_forkid as forkit',
        'new_masterobat.status_generik as generik',
        'new_masterobat.kode108 as kode108',
        'new_masterobat.uraian108 as uraian108',
        'new_masterobat.kode50 as kode50',
        'new_masterobat.uraian50 as uraian50',
        'new_masterobat.obat_program as obat_program',
      )
      ->leftJoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
      ->whereIn('kdobat', $uniqueObat)
      ->where('kdruang', $kodedepo)
      ->where('jumlah', '>', 0)
      ->with([
          'transnonracikan' => function ($transnonracikan) use ($kodedepo) {
              $transnonracikan->select(
                  // 'resep_keluar_r.kdobat as kdobat',
                  'resep_permintaan_keluar.kdobat as kdobat',
                  'resep_keluar_h.depo as kdruang',
                  DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
              )
                  ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                  ->where('resep_keluar_h.depo', $kodedepo)
                  ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                  ->groupBy('resep_permintaan_keluar.kdobat');
          },
          'transracikan' => function ($transracikan) use ($kodedepo) {
              $transracikan->select(
                  // 'resep_keluar_racikan_r.kdobat as kdobat',
                  'resep_permintaan_keluar_racikan.kdobat as kdobat',
                  'resep_keluar_h.depo as kdruang',
                  DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
              )
                  ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                  ->where('resep_keluar_h.depo', $kodedepo)
                  ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                  ->groupBy('resep_permintaan_keluar_racikan.kdobat');
          },
          'permintaanobatrinci' => function ($permintaanobatrinci) use ($kodedepo) {
              $permintaanobatrinci->select(
                  'permintaan_r.no_permintaan',
                  'permintaan_r.kdobat',
                  DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
              )
                  ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                  // biar yang ada di tabel mutasi ga ke hitung
                  ->leftJoin('mutasi_gudangdepo', function ($anu) {
                      $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                          ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                  })
                  ->whereNull('mutasi_gudangdepo.kd_obat')

                  ->where('permintaan_h.tujuan', $kodedepo)
                  ->whereIn('permintaan_h.flag', ['', '1', '2'])
                  ->groupBy('permintaan_r.kdobat');
          },
          'persiapanrinci' => function ($res) use ($kodedepo) {
              $res->select(
                  'persiapan_operasi_rincis.kd_obat',

                  DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as jumlah'),
              )
                  ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                  ->whereIn('persiapan_operasis.flag', ['', '1'])
                  ->groupBy('persiapan_operasi_rincis.kd_obat');
          },
          // 'daftarharga'=>function($q){
          //   $q->select(
          //     DB::raw('MAX(harga) as harga'), 
          //     'tgl_mulai_berlaku', 
          //     'kd_obat'
          //     )
          //   ->orderBy('tgl_mulai_berlaku','desc')
          //   ->take(5);
          // }
      ])
      // ->withCount(['daftarharga' => function ($q){
      //       $q->select(
      //         DB::raw('MAX(harga) as harga'), 
      //         'tgl_mulai_berlaku', 
      //         'kd_obat'
      //         )
      //       ->orderBy('tgl_mulai_berlaku','desc')
      //       ->limit(5);
      // }])
      // ->addSelect([
      //   'harga_tertinggi_ids' => DaftarHarga::query()
      //           ->selectRaw("SUBSTRING_INDEX(GROUP_CONCAT(daftar_hargas.id order by tgl_mulai_berlaku desc, ','), ',', {$limitHargaTertinggi})")
      //           ->whereColumn('daftar_hargas.kd_obat','=', 'stokreal.kdobat')
      //           ->limit($limitHargaTertinggi)
      //   ])
      ->addSelect([
        'harga_tertinggi' => DaftarHarga::query()
                ->selectRaw("MAX(harga)")
                ->whereColumn('daftar_hargas.kd_obat','=', 'stokreal.kdobat')
                ->orderBy('daftar_hargas.tgl_mulai_berlaku','desc')
                ->limit($limitHargaTertinggi)
        ])
      ->orderBy('tglexp')
      ->groupBy('kdobat')
      ->get();

      // $ht = $cekjumlahstok->value('harga_tertinggi');
      // $hrg = self::penentuanHarga($ht, $sistembayar,$cekjumlahstok->pluck('obat_program'));


      // $hrgTertinggiIds = $cekjumlahstok->pluck('harga_tertinggi_ids')
      // ->map(function (string $daftarHargaKodes) {
      //     return explode(',', $daftarHargaKodes);
      // })
      // ->flatten();

      // $hargaTertinggi = DaftarHarga::select('id','kd_obat','tgl_mulai_berlaku','harga')
      // ->whereIn('id', $hrgTertinggiIds)
      // ->orderBy('tgl_mulai_berlaku', 'desc')
      // ->get();

      // $ht = collect($hargaTertinggi);

      // foreach ($cekjumlahstok as $stok) {
      //   // menjadikan array dari string $stok->harga_tertinggi_ids
      //   $ids = explode(',', $stok->harga_tertinggi_ids);

      //   $stokHargaTertinggi = $hargaTertinggi
      //       ->whereIn('id', $ids)
      //       ->sortBy(fn (DaftarHarga $daftarHarga) => array_flip($ids)[$daftarHarga->id])
      //       ->values();
     
      //   // masukkan ke object harga_teringgi_kodes
      //   $stok->setRelation('harga_tertinggi_ids', $stokHargaTertinggi);
      // }



      $alokasiNharga = collect($cekjumlahstok)->map(function ($x, $y) use ($kodedepo) {
        $total = $x->jumlahstok ?? 0;
        $jumlahper = $kodedepo === 'Gd-04010103' ? $x['persiapanrinci'][0]->jumlah ?? 0 : 0;
        $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
        $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
        $permintaanobatrinci = $x['permintaanobatrinci'][0]->allpermintaan ?? 0; // mutasi antar depo
        $x->alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci - (float)$jumlahper;
        // $ids = explode(',', $x->harga_tertinggi_ids);
        // $x->setRelation('harga_tertinggi_ids', $ht->whereIn('id', $ids)
        //   ->sortByDesc('tgl_mulai_berlaku')->values());// = $ht->whereIn('id', $x['ids_harga'])->values();?
        // $x->harga = $ht->whereIn('id', $ids)->max('harga') ?? 0;
        // $x->harga = $hrg;
        return $x;
      });
      
      return $alokasiNharga->toArray();
    }

    public static function outputRaw($adaraw, $requestnya, $sistembayar)
    {
      // return $requestnya;
        $obatYgDiminta = [];
        foreach ($requestnya as $key) {
          // mapping racikan =============================================================================
          if ($key['racikan'] === true) {
            $obat['kdobat'] = $key['kodeobat'];
            $obat['jumlah_diminta'] = $key['jumlah_diminta'];
            $obat['sistembayar'] = $sistembayar;
            $rincian = $key['rincian'];
            $rinci = [];
            foreach ($rincian as $sub) {
              $data = collect($adaraw)->firstWhere('kdobat', $key['kodeobat']);
              $rinci[] = self::mappingObat($data, $sub, $sistembayar);
            }
            $obat['rincian'] = $rinci;
            $ceks = collect($rinci)->pluck('isError');
            $valueToCheck = true;
            $obat['isError'] = false;
            if (in_array($valueToCheck, (array)$ceks)) {
                $obat['isError'] = true;
            } else {
                $obat['isError'] = false;
            }
            $obatYgDiminta[] = $obat;
          } else {
            // mapping non racikan =============================================================================
            $data = collect($adaraw)->firstWhere('kdobat', $key['kodeobat']);
            $obatYgDiminta[] = self::mappingObat($data, $key, $sistembayar);
            // $data = $adaraw;
            // $obatYgDiminta[] = $data;
          }
        }

      

      // $raw = collect($adaraw);
      // $obatYgDiminta = collect($requestnya)->map(function ($x) use ($raw, $sistembayar) {
      //   $obat['kdobat'] = $x['kodeobat'];
      //   $isError = $raw->where('kdobat', $x['kodeobat'])->isEmpty();
      //   $obat['isError'] = $isError;
      //   $obat['errors'] = $isError ? 'Stok Obat Tidak Tersedia' : null;
      //   $obat['sistembayar'] = $sistembayar;
      //   $data  = $raw->where('kdobat', $x['kodeobat'])->first();
      //   $obat['data'] = $raw->where('kdobat', $x['kodeobat'])->first();

      //   $alokasi = $isError ? false : (int)$data['alokasi'];
      //   $jumlahDiminta = $isError ? false : (int)$x['jumlah_diminta'];
      //   $jumlahstok = $isError ? false : $data['jumlahstok'];
      //   $obat['jumlahstok'] = $jumlahstok;
      //   $obat['alokasi'] = $alokasi;
      //   $obat['jumlah_diminta'] = $jumlahDiminta;
      //   if ($alokasi ) {
      //     if ($jumlahDiminta > $alokasi) {
      //       $obat['isError'] = true;
      //       $obat['errors'] = 'Jumlah diminta melebihi Alokasi yang tersedia';
      //     }
      //   }

      //   $harga = $isError ? false : $data['harga_tertinggi'];
      //   if (!$harga) {
      //     $obat['isError'] = true;
      //     $obat['errors'] = 'Belum Ada Harga pada Obat ini';
      //   }
  
      //   $hargajual = $isError ? false : self::penentuanHarga($harga, $sistembayar, $data['obat_program']);
      //   $obat['hargajual'] = $hargajual;
      //   $obat['harga'] = $harga;
      //   return $obat;
      // });
          
      return $obatYgDiminta;
    }

    public static function mappingObat($data, $key, $sistembayar)
    {
      $isError = $data ? false : true;
      $obat['isError'] = $isError;
      $obat['errors'] = $isError ? ['obat'=> 'Stok Obat Tidak Tersedia'] : [];
      $obat['kdobat'] = $key['kodeobat'];
      $obat['sistembayar'] = $sistembayar;
      $obat['data'] = $data;

      $alokasi = $isError ? false : (int)$data['alokasi'];
      $jumlahDiminta = $isError ? false : (int)$key['jumlah_diminta'];
      $jumlahstok = $isError ? false : $data['jumlahstok'];
      $obat['jumlahstok'] = $jumlahstok;
      $obat['alokasi'] = $alokasi;
      $obat['jumlah_diminta'] = $jumlahDiminta;

      // $validasiJmldiminta = (int)$key['jumlah_diminta'] > $alokasi;
      if ($alokasi ) {
        if ($jumlahDiminta > $alokasi) {
          $obat['isError'] = true;
          $obat['errors'] = ['alokasi'=> 'Jumlah diminta melebihi Alokasi yang tersedia'];
        }
        
      }

      $harga = $isError ? false : $data['harga_tertinggi'];
      $hargajual = $isError ? false : self::penentuanHarga($harga, $sistembayar, $data['obat_program']);
      $obat['hargajual'] = $hargajual;
      $obat['harga'] = $harga;

      return $obat;
    }

    public static function penentuanHarga($harga, $sistembayar, $obatprogram)
    {
      $hargajualx = 0;
      if ($obatprogram === '1') {
        $hargajualx = 0;
      } else {
        if ($sistembayar===null || $sistembayar==='1' || $sistembayar===1 || !$sistembayar) {
              if ($harga <= 50000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 28 / (int) 100;
            } elseif ($harga > 50000 && $harga <= 250000) {
                $hargajualx = (int) $harga + ((int) $harga * (int) 26 / (int) 100);
            } elseif ($harga > 250000 && $harga <= 500000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 21 / (int) 100;
            } elseif ($harga > 500000 && $harga <= 1000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 16 / (int)100;
            } elseif ($harga > 1000000 && $harga <= 5000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 11 /  (int)100;
            } elseif ($harga > 5000000 && $harga <= 10000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 9 / (int) 100;
            } elseif ($harga > 10000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 7 / (int) 100;
            }
        } else if ($sistembayar == 2 || $sistembayar == '2') {
            $hargajualx = (int) $harga + (int) $harga * (int) 25 / (int)100;
        } else {
            $hargajualx = (int) $harga + (int) $harga * (int) 30 / (int)100;
        }
      }
      

      return $hargajualx;
    }
}
