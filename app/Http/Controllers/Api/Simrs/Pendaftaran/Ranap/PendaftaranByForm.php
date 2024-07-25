<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendaftaranByForm extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
          'norm' => 'required|string|max:6|min:6',
          'nama'=>  'required|string',
          'tglmasuk' => 'required|date_format:Y-m-d H:i:s',
          'tgllahir' => 'required|date_format:Y-m-d'
        ]);

        // $masterpasien = Mpasien::updateOrCreate(
        //   ['rs1' => $request->norm],
        //   [
        //       'rs2' => $request->nama,
        //       'rs3' => $request->sapaan ?? '',
        //       'rs4' => $request->alamat ?? '',
        //       'alamatdomisili' => $request->alamatDomisili ?? '',
        //       'rs5' => $request->kelurahan ?? '',
        //       // 'kd_kel' => $request->kodekelurahan,
        //       'rs6' => $request->kecamatan ?? '',
        //       // 'kd_kec' => $request->kodekecamatan,
        //       'rs7' => $request->rt ?? '',
        //       'rs8' => $request->rw ?? '',
        //       'rs10' => $request->propinsi ?? '',
        //       // 'kd_propinsi' => $request->kodepropinsi ?? '',
        //       'rs11' => $request->kota ?? '',
        //       // 'kd_kota' => $request->kodekabupatenkota,=
        //       'rs49' => $request->noktp ?? '',
        //       'rs37' => $request->tempatlahir ?? '',
        //       'rs16' => $request->tgllahir,
        //       'rs17' => $request->kelamin,
        //       'rs19' => $request->pendidikan,
        //       'kd_kelamin' => $request->kodekelamin,
        //       'rs22' => $request->agama,
        //       'kd_agama' => $request->kodemapagama,
        //       'rs39' => $request->suku,
        //       'rs55' => $request->noteleponhp,
        //       'bahasa' => $request->bahasa,
        //       'noidentitaslain' => $nomoridentitaslain,
        //       'namaibu' => $request->namaibukandung,
        //       'kodepos' => $request->kodepos,
        //       'kd_negara' => $request->negara,
        //       'kd_rt_dom' => $request->rtdomisili,
        //       'kd_rw_dom' => $request->rwdomisili,
        //       'kd_kel_dom' => $request->kodekelurahandomisili,
        //       'kd_kec_dom' => $request->kodekecamatandomisili,
        //       'kd_kota_dom' => $request->kodekabupatenkotadomisili,
        //       'kodeposdom' => $request->kodeposdomisili,
        //       'kd_prov_dom' => $request->kodepropinsidomisili,
        //       'kd_negara_dom' => $request->negaradomisili,
        //       'noteleponrumah' => $noteleponrumah,
        //       'kd_pendidikan' => $request->kodependidikan,
        //       'kd_pekerjaan' => $request->pekerjaan,
        //       'flag_pernikahan' => $request->statuspernikahan,
        //       'rs46' => $nokabpjs,
        //       'rs40' => $request->barulama,
        //       'gelardepan' => $gelardepan,
        //       'gelarbelakang' => $gelarbelakang,
        //       'bacatulis' => $request->bacatulis,
        //       'kdhambatan' => $request->kdhambatan
        //   ]
        // );

      // {
      //   "barulama": "Lama",
      //   // "norm": "000317",
      //   "kewarganegaraan": "WNI",
      //   // "noktp": "3574030511970004",
      //   "paspor": null,
      //   "idsatset": null,
      //   "nokabpjs": "0000112288061",
      //   // "nama": "JEMY TEGUH CHARISMA GUSTI PUTRA",
      //   "ibukandung": "safasf",
      //   // "tempatlahir": "asdsa",
      //   "tanggallahir": "1997-11-05",
      //   "kelamin": "Laki-laki",
      //   // "sapaan": "Bpk.",
      //   "pendidikan": "SMU",
      //   "agama": "Islam",
      //   "agamalain": null,
      //   "suku": "Jawa",
      //   "bahasa": "JAWA",
      //   "bisabacatulis": "Ya",
      //   "statuspernikahan": "Kawin",
      //   "pekerjaan": "Pegawai Swasta / Wirausaha",
      //   "notelp": "214214",
      //   "nohp": "4124214",
      //   // "alamat": "JL.KH MANSYUR 80  MANGUNHARJO  MAYANGAN",
      //   // "rt": "002",
      //   // "rw": "010",
      //   // "kelurahan": "Ganting Kulon",
      //   // "kecamatan": "Maron",
      //   // "kota": "KAB. PROBOLINGGO",
      //   // "propinsi": "JAWA TIMUR",
      //   "negara": "INDONESIA",
      //   "kodepos": "123456",
      //   "country": null,
      //   "city": null,
      //   "region": null,
      //   // "alamatDomisili": "JL.KH MANSYUR 80  MANGUNHARJO  MAYANGAN",
      //   "rtDomisili": "002",
      //   "rwDomisili": "010",
      //   "kelurahanDomisili": "Ganting Kulon",
      //   "kecamatanDomisili": "Maron",
      //   "kotaDomisili": "KAB. PROBOLINGGO",
      //   "propinsiDomisili": "JAWA TIMUR",
      //   "negaraDomisili": "INDONESIA",
      //   "kodeposDomisili": "123456",
      //   "asalrujukan": "AR0002",
      //   "jnsBayar": "1",
      //   "kodesistembayar": "BPJS4",
      //   "kategoriKasus": "10",
      //   "diagnosaAwal": {
      //       "icd": "Q72.2",
      //       "dtd": "264",
      //       "ketindo": "MALFORMASI DAN DEFORMASI KONGENITAL SISTEM MUSKULOSKELETAL LAIN",
      //       "keterangan": "Congenital absence of both lower leg and foot"
      //   },
      //   "kamar": "ME1",
      //   "kelas": "1",
      //   "kode_ruang": "ME",
      //   "flag_ruang": "",
      //   "hakKelasBpjs": "1",
      //   "indikatorPerubahanKelas": null,
      //   "biaya_admin": 0,
      //   "biaya_kamar": 0,
      //   "nama_dokter": "dr. HAMNA FITRIAH, Sp.THT - KL",
      //   "kd_dokter": "D778",
      //   "kd_dokter_bpjs": "14948",
      //   "nama_penanggungjawab": "jsadsada",
      //   "notelp_penanggungjawab": "2353532",
      //   "usia": "26"
      // }
    }
}
