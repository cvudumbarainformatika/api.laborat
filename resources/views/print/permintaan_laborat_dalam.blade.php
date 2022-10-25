<!DOCTYPE>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1"> -->
    <link rel="stylesheet" type="text/css" media="print" href="{{ URL::asset('print') }}/mystyles.scss">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('styles') }}/main.scss">
    <title></title>
</head>

<body topmargin="0" leftmargin="0" rightmargin="0">
    <div class="page">
        <div class="row">
            <div class="">
                <img class="logo" src="{{ URL::asset('images') }}/logo-rsud.png" alt="logo-kota-grayscale" style="width:70px;" />
            </div>
            <div class="mt-10 ml-10">
                <div class="title bold">{{ $header->title }}</div>
                <div class="subtitle">{{ $header->sub }}</div>
                <div class="subtitle">{{ $header->sub2 }}</div>
            </div>
        </div>
        <hr />

        <!-- header -->
        <div class="title bold underline text-center">HASIL PERMINTAAN LABORAT</div>
        <div class="title mb-10 italic text-center">LABORATORY EXAMINATION RESULTS </div>
        <?php
        $pasien = $details[0]->poli ? $details[0]->pasien_kunjungan_poli : $details[0]->pasien_kunjungan_rawat_inap;
        $tgl_selesai = date('Y-m-d', strtotime($details[0]->rs29));
        $jam_selesai = date('H:i:s', strtotime($details[0]->rs29));
        function hitung_umur($tanggal_lahir)
        {
            $birthDate = new DateTime($tanggal_lahir);
            $today = new DateTime();
            if ($birthDate > $today) {
                exit("0 tahun 0 bulan 0 hari");
            }
            $y = $today->diff($birthDate)->y;
            $m = $today->diff($birthDate)->m;
            $d = $today->diff($birthDate)->d;
            return $y . " Tahun";
        }
        ?>
        <!-- Detail Pasien -->
        <div class="column">
            <div class="row justify-between">
                <div class="left">
                    <div class="row">
                        <div style="width:100px">TANGGAL</div>
                        <div>: {{ date('d F Y', strtotime($details[0]->tanggal)) }}</div>
                    </div>
                    <div class="row">
                        <div style="width:100px">NOREG</div>
                        <div>: {{$details[0]->rs1}}</div>
                    </div>
                    <div class="row">
                        <div style="width:100px">NORM</div>
                        <div>: {{$details[0]->poli? $details[0]->pasien_kunjungan_poli->rs1:$details[0]->pasien_kunjungan_rawat_inap->rs1}}</div>
                    </div>
                    <div class="row">
                        <div style="width:100px">PENGIRIM</div>
                        <div>: {{$details[0]->dokter->rs2}}</div>
                    </div>
                    <div class="row">
                        <div style="width:100px">POLI/RUANG</div>
                        <div>: {{$details[0]->poli? $details[0]->poli->rs2:$details[0]->kunjungan_rawat_inap->rs6}}</div>
                    </div>
                </div>
                <div class="right">
                    <div class="row">
                        <div>JAM PERMINTAAN </div>
                        <div>: {{ date('H:i:s', strtotime($details[0]->tanggal)) }}</div>
                    </div>
                    <div class="row">
                        <div>NAMA </div>
                        <div>: {{$details[0]->poli? $details[0]->pasien_kunjungan_poli->rs2:$details[0]->pasien_kunjungan_rawat_inap->rs2}}</div>
                    </div>
                    <div class="row">
                        <div>UMUR </div>
                        <div>: {{$details[0]->poli? hitung_umur($details[0]->pasien_kunjungan_poli->rs16):
                            hitung_umur($details[0]->pasien_kunjungan_rawat_inap->rs16)
                        }}</div>
                    </div>
                    <div class="row">
                        <div>SISTEM BAYAR </div>
                        <div>: {{$details[0]->poli? $details[0]->sb_kunjungan_poli->rs2:$details[0]->sb_kunjungan_rawat_inap->rs2}}</div>
                    </div>
                    <!-- <div class="row">
                        <div>ALAMAT </div>
                        <div>: {{$details[0]->poli? $details[0]->pasien_kunjungan_poli->rs4:$details[0]->pasien_kunjungan_rawat_inap->rs4}}</div>
                    </div> -->
                </div>
            </div>
        </div>

        <?php $gg = collect($details)->groupBy('pemeriksaan_laborat.rs21')->toArray(); ?>


        <table width="100%" class="table" cellpadding="0" cellspacing="0" border="1" bordercolor="#006699" bordercolordark="#666666" bordercolorlight="#003399">
            <thead>
                <tr valign="middle" align="center">
                    <td>&nbsp;<b><u>Pemeriksaan</u></b><br><i>Checking Type</i>&nbsp;</td>
                    <td>&nbsp;<b><u>Hasil</u></b><br><i>Result</i>&nbsp;</td>
                    <td>&nbsp;<b><u>Nilai Normal</u></b><br><i>Normal Value</i>&nbsp;</td>
                    <td>&nbsp;<b><u>Tanggal</u></b><br><i>Date</i>&nbsp;</td>
                    <td>&nbsp;<b><u>Jam</u></b><br><i>Clock</i>&nbsp;</td>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1;
                $total = 0;
                $x = 1;
                $no = 1;
                foreach ($gg as $key => $values) { ?>
                    <?php
                    for ($n = 0; $n < count($values); $n++) {
                    ?>
                        <?php if ($values[$n]['pemeriksaan_laborat']['rs21'] === '') {
                            $total +=  $values[$n]['subtotal'];
                            $x = $n;
                            $no = $i + $n;

                        ?>
                            <tr>
                                <td> {{ $values[$n]['pemeriksaan_laborat']['rs2'] }} </td>
                                <td> {{ $values[0]['rs21']}} </td>
                                <td> {{ $values[0]['pemeriksaan_laborat']['rs22'] }} </td>
                                <td> {{ date('Y-m-d', strtotime($values[0]['tanggal'])) }} </td>
                                <td> {{ date('H:i:s', strtotime($values[0]['tanggal'])) }} </td>
                            </tr>
                        <?php } elseif ($values[0]['pemeriksaan_laborat']['rs21'] !== '' && $n === 0) {
                            $total +=  $values[0]['subtotal'];
                        ?>
                            <tr>
                                <td colspan="5"> {{ $values[0]['pemeriksaan_laborat']['rs21'] }} </td>

                            </tr>
                            <tr class="list">
                                <td> - {{ $values[0]['pemeriksaan_laborat']['rs2'] }} </td>
                                <td> {{ $values[0]['rs21']}} </td>
                                <td> {{ $values[0]['pemeriksaan_laborat']['rs22'] }} </td>
                                <td> {{ date('Y-m-d', strtotime($values[0]['tanggal'])) }} </td>
                                <td> {{ date('H:i:s', strtotime($values[0]['tanggal'])) }} </td>
                            </tr>
                        <?php } else {
                        ?>
                            <tr class="list">
                                <td> - {{ $values[$n]['pemeriksaan_laborat']['rs2'] }} </td>
                                <td> {{ $values[$n]['rs21'] }} </td>
                                <td> {{ $values[$n]['pemeriksaan_laborat']['rs22']}} </td>
                                <td> {{ date('Y-m-d', strtotime($values[$n]['tanggal'])) }} </td>
                                <td> {{ date('H:i:s', strtotime($values[$n]['tanggal'])) }} </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                <?php $i++;
                } ?>

            </tbody>
        </table>


        <br>
        <div class="row justify-between">
            <div style="padding-left:10%" class="column">
                <div>&nbsp </div>
                <div>Pemeriksa&nbsp;</div>
                <div style="height:60px;"></div>
                <div>(..................................)&nbsp;</div>
            </div>
            <div style="padding-right:10%" class="column">
                <div>Probolinggo, <?php
                                    $timestamp = time();
                                    $tgl = date('d F Y', $timestamp);
                                    // if ($details[0]->sampel_selesai) {
                                    //     $xtimestamp = time()

                                    // }
                                    echo $tgl;
                                    ?>&nbsp;</div>
                <div>Penanggung Jawab&nbsp;</div>
                <div style="height:60px;"></div>
                <div>(..................................)&nbsp;</div>
            </div>
        </div>
        <br>
        Scan disini untuk verifikasi :<br>
    </div>
</body>


</html>

<script language="javascript">
    window.print();
    window.onafterprint = function() {
        window.close();
    }
    setTimeout(function() {
        window.close();
    }, 1000);
</script>
