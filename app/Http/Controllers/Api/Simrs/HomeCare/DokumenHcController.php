<?php

namespace App\Http\Controllers\Api\Simrs\HomeCare;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Homecare\HomeCareKunjungan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DokumenHcController extends Controller
{
    public function catatanHomeCare(Request $request)
    {
        if ((int) $request->tahunakhir < (int) $request->tahunawal) {
            return new JsonResponse(['message' => 'Inputan tahun salah'], 422);
        }

        $tahunawal = (string) $request->tahunawal . '-01-01 00:00:00';
        $tahunakhir = (string) $request->tahunakhir . '-12-31 23:59:59';

        $data = HomeCareKunjungan::with([
            'pegawai',
            'anamnesis',
            'edukasi',
            'pemeriksaanfisik' => function ($d) {
                $d->orderBy('id', 'DESC');
            },
            'diagnosa' => function ($a) {
                $a->with(['masterdiagnosa', 'dokter']);
            },
            'diagnosakeperawatan' => function ($dk) {
                $dk->with(['intervensi.masterintervensi', 'masterperawat']);
            },
            'tindakan' => function ($tindakan) {
                $tindakan->select('rs73.rs1', 'rs30.rs2 as tindakan', 'rs73.rs20 as keterangan')
                    ->join('rs30', 'rs30.rs1', 'rs73.rs4');
            },
            'laborats' => function ($q) {
                $q->with('details.pemeriksaanlab')->orderBy('id', 'DESC');
            },
            'laboratold' => function ($t) {
                $t->with('pemeriksaanlab')->orderBy('id', 'DESC');
            },
            'fisio' => function ($q) {
                $q->where('rs2', '!=', '')
                    ->groupBy('rs2')->orderBy('id', 'DESC');
            },
            'newapotekrajal' => function ($q) {
                $q->with([
                    'permintaanresep.mobat:kd_obat,nama_obat,bentuk_sediaan,satuan_k,jenis_perbekalan',
                    'permintaanracikan.mobat:kd_obat,nama_obat,bentuk_sediaan,satuan_k,jenis_perbekalan',
                ])->orderBy('id', 'DESC');
            },
        ])
            ->where('norm', $request->norm)
            ->whereBetween('tgl_kunjungan', [$tahunawal, $tahunakhir])
            ->orderBy('tgl_kunjungan', 'DESC')
            ->get();

        $result = $data->map(function ($item) {
            $laboratList = [];
            if ($item->laborats && count($item->laborats)) {
                foreach ($item->laborats as $lab) {
                    if ($lab->details && count($lab->details)) {
                        foreach ($lab->details as $det) {
                            $laboratList[] = [
                                'pemeriksaanlab' => [
                                    'rs2' => $det->pemeriksaanlab->rs2 ?? '-'
                                ],
                                'rs21' => $det->hasil ?? '-'
                            ];
                        }
                    }
                }
            }
            if ($item->laboratold && count($item->laboratold)) {
                foreach ($item->laboratold as $oldLab) {
                    $laboratList[] = [
                        'pemeriksaanlab' => [
                            'rs2' => $oldLab->pemeriksaanlab->rs2 ?? '-'
                        ],
                        'rs21' => $oldLab->rs21 ?? '-'
                    ];
                }
            }

            $obatList = [];
            $obatRacikanList = [];
            if ($item->newapotekrajal && count($item->newapotekrajal)) {
                foreach ($item->newapotekrajal as $resep) {
                    if ($resep->permintaanresep && count($resep->permintaanresep)) {
                        foreach ($resep->permintaanresep as $pr) {
                            $namaObat = $pr->mobat->nama_obat ?? '-';
                            $obatList[] = [
                                'obat' => $namaObat . ($pr->jumlah ? ' (' . $pr->jumlah . ')' : '') . ($pr->aturan ? ' - ' . $pr->aturan : '')
                            ];
                        }
                    }
                    if ($resep->permintaanracikan && count($resep->permintaanracikan)) {
                        foreach ($resep->permintaanracikan as $rc) {
                            $namaObat = $rc->mobat->nama_obat ?? '-';
                            $obatRacikanList[] = [
                                'obat' => $namaObat . ($rc->jumlah ? ' (' . $rc->jumlah . ')' : '')
                            ];
                        }
                    }
                }
            }

            $item->laborat = $laboratList;
            $item->apotekrajal = $obatList;
            $item->apotekracikanrajal = $obatRacikanList;
            $item->rs3 = $item->tgl_kunjungan;

            return $item;
        });

        return new JsonResponse($result);
    }
}
