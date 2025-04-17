<!DOCTYPE>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1"> -->
    <link rel="stylesheet" type="text/css" media="print" href="<?php echo e(URL::asset('print')); ?>/mystyles.scss">
    <link rel="stylesheet" type="text/css" href="<?php echo e(URL::asset('styles')); ?>/main.scss">
    <title></title>
</head>

<body topmargin="0" leftmargin="0" rightmargin="0">
    <div class="page">
        <div class="row">
            <div class="">
                <img class="logo" src="<?php echo e(URL::asset('images')); ?>/logo-rsud.png" alt="logo-kota-grayscale" style="width:70px;" />
            </div>
            <div class="mt-10 ml-10">
                <div class="title bold"><?php echo e($header->title); ?></div>
                <div class="subtitle"><?php echo e($header->sub); ?></div>
                <div class="subtitle"><?php echo e($header->sub2); ?></div>
            </div>
        </div>
        <hr />
        <?php if ($jenis === 'pengantar') { ?>
            <div class="title bold mb-10 text-center">PERMINTAAN LABORAT</div>
        <?php } else { ?>

            <div class="title bold underline text-center">HASIL PERMINTAAN LABORAT</div>
            <hr />
            <!-- <div class="title mb-10 italic text-center">LABORATORY EXAMINATION RESULTS </div> -->

        <?php } ?>
        <?php if ($jenis === 'pengantar') { ?>
            <div class="row justify-between">
                <div class="column">
                    <div class="row">
                        <div class="w-x">Nama</div>
                        <div>: <?php echo e($details[0]->nama); ?></div>
                    </div>
                    <div class="row">
                        <div class="w-x">Kelamin</div>
                        <div>: <?php echo e($details[0]->kelamin); ?></div>
                    </div>
                    <div class="row">
                        <div class="w-x">Alamat</div>
                        <div>: <?php echo e($details[0]->alamat); ?></div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="row flex-right">
                        <div>Nota : </div>
                        <div> <?php echo e($details[0]->nota); ?></div>
                    </div>
                    <div class="row flex-right">
                        <div>Dokter Pengirim : </div>
                        <div> <?php echo e($details[0]->pengirim); ?></div>
                    </div>
                    <div class="row flex-right">
                        <div>Tanggal : </div>
                        <div> <?php echo e($details[0]->tgl); ?></div>
                    </div>
                </div>
            </div>
        <?php } else {

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
                return $y . " Thn " . $m . " Bln " . $d . " Hr";
            }
        ?>
            <div class="column">
                <div class="row justify-between">
                    <div class="left">
                        <div class="row">
                            <div style="width:120px">Nama </div>
                            <div>: <?php echo e($details[0]->nama); ?></div>
                        </div>
                        <div class="row">
                            <div style="width:120px">tgl Lahir / Umur</div>
                            <div>: <?php echo e(date('d-m-Y', strtotime($details[0]->tgl_lahir))); ?> / <?php echo e(hitung_umur($details[0]->tgl_lahir)); ?></div>
                        </div>
                        <div class="row">
                            <div style="width:120px">No Reg</div>
                            <div>: <?php echo e($details[0]->nota); ?></div>
                        </div>
                        <div class="row">
                            <div style="width:120px">No RM</div>
                            <div>: -</div>
                        </div>
                        <div class="row">
                            <div style="width:120px">Alamat</div>
                            <div>: <?php echo e($details[0]->alamat); ?></div>
                        </div>
                    </div>
                    <div class="right">
                        <div class="row">
                            <div style="width:100px">A/n Permintaan </div>
                            <div style="width:150px">: <?php echo e($details[0]->pengirim); ?></div>
                        </div>
                        <div class="row">
                            <div style="width:100px">Tgl Permintaan </div>
                            <div>: <?php echo e($details[0]->tgl); ?></div>
                        </div>
                        <div class="row">
                            <div style="width:100px">Tgl Validasi </div>
                            <div>: <?php echo e($details[0]->sampel_selesai); ?> <?php echo e($details[0]->jam_sampel_selesai); ?></div>
                        </div>
                        <div class="row">
                            <div style="width:100px">TAT </div>
                            <div>: <?php echo e($details[0]->tat); ?></div>
                        </div>
                    </div>
                </div>
                <!-- <div class="row">
                    <div class="w-xx">Nama / <span class="italic"> Name</span></div>
                    <div>: <?php echo e($details[0]->nama); ?></div>
                </div>
                <div class="row">
                    <div class="w-xx">Alamat / <span class="italic"> Address</span></div>
                    <div>: <?php echo e($details[0]->alamat); ?></div>
                </div>
                <div class="row">
                    <div class="w-xx">Dokter Pengirim / <span class="italic"> Sending Doctor</span></div>
                    <div>: <?php echo e($details[0]->pengirim); ?></div>
                </div>
                <div class="row">
                    <div class="w-xx">Sampel diambil / <span class="italic"> Sample Taken </span></div>
                    <div>: <?php echo e($details[0]->sampel_diambil); ?>, Jam/Clock: <?php echo e($details[0]->jam_sampel_diambil); ?></div>
                </div>
                <div class="row">
                    <div class="w-xx">Sampel Selesai Diperiksa / <span class="italic"> Sample Has Been Checked </span></div>
                    <div>: <?php echo e($details[0]->sampel_selesai); ?>, Jam/Clock: <?php echo e($details[0]->jam_sampel_selesai); ?></div>
                </div> -->
            </div>
        <?php } ?>

        <?php $gg = collect($details)->groupBy('pemeriksaan_laborat.rs21')->toArray(); ?>

        <?php if ($jenis === 'pengantar') { ?>
            <table class="table mt-10">
                <thead>
                    <tr>
                        <th class="text-left" width="5%">
                            No
                        </th>
                        <th class="text-left">
                            Pemeriksaan
                        </th>

                        <th class="text-right">
                            Jumlah
                        </th>
                        <th class="text-right">
                            Biaya
                        </th>
                        <th class="text-right">
                            Subtotal
                        </th>
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
                                    <td> <?php echo e($no); ?> </td>
                                    <td> <?php echo e($values[$n]['pemeriksaan_laborat']['rs2']); ?> </td>
                                    <td class="text-right"> <?php echo e($values[$n]['jml']); ?> </td>
                                    <td class="text-right"> <?php echo e(number_format($values[$n]['biaya'], 0, ',', '.')); ?> </td>
                                    <td class="text-right"> <?php echo e(number_format($values[$n]['subtotal'], 0, ',', '.')); ?> </td>
                                </tr>
                            <?php } elseif ($values[0]['pemeriksaan_laborat']['rs21'] !== '' && $n === 0) {
                                $total +=  $values[0]['subtotal'];
                            ?>
                                <tr>
                                    <td> <?php echo e($i>1?$no+1:$no); ?> </td>
                                    <td> <?php echo e($values[0]['pemeriksaan_laborat']['rs21']); ?> </td>
                                    <td class="text-right"> <?php echo e($values[0]['jml']); ?> </td>
                                    <td class="text-right"> <?php echo e(number_format($values[0]['biaya'], 0, ',', '.')); ?> </td>
                                    <td class="text-right"> <?php echo e(number_format(($values[0]['tarif_sarana'] + $values[0]['tarif_pelayanan']) * $values[0]['jml'], 0, ',', '.')); ?> </td>
                                </tr>
                                <tr class="sub">
                                    <td></td>
                                    <td colspan="4"> - <?php echo e($values[0]['pemeriksaan_laborat']['rs2']); ?> </td>
                                </tr>
                            <?php } else {
                            ?>
                                <tr class="sub">
                                    <td></td>
                                    <td colspan="4"> - <?php echo e($values[$n]['pemeriksaan_laborat']['rs2']); ?> </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    <?php $i++;
                    } ?>
                    <tr style="border-top: solid 1px rgb(190, 190, 190); border-bottom: solid 1px rgb(190, 190, 190);">
                        <td colspan="2" class=" bold">JUMLAH PEMERIKSAAN: <?php echo e($details->count('nota')); ?> </td>
                        <td colspan="2" class="text-right bold">TOTAL </td>
                        <td class="text-right bold"> <?php echo e(number_format($total, 0, ',', '.')); ?> </td>
                    </tr>
                </tbody>
            </table>
            <hr />
            <div class="row justify-between">
                <div></div>
                <div class="flex-right"> Probolinggo, <?php echo e(date('j F, Y', strtotime($details[0]->tgl))); ?></div>
            </div>
            <div class="row justify-between">
                <div></div>
                <div class="flex-right"> Petugas, </div>
            </div>
        <?php } else { ?>
            <table width="100%" class="table" cellpadding="0" cellspacing="0" border="1" bordercolor="#006699" bordercolordark="#666666" bordercolorlight="#003399">
                <thead>
                    <tr valign="middle" align="left" style="border-bottom: solid 1px rgb(190, 190, 190);">
                        <td><b>PEMERIKSAAN</b></td>
                        <td width="5%"></td>
                        <td><b>HASIL</b></td>
                        <td><b>NILAI NORMAL</b></td>
                        <td><b>SATUAN</b></td>
                        <td><b>METODE</b></td>
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
                                    <td> <?php echo e($values[$n]['pemeriksaan_laborat']['rs2']); ?> </td>
                                    <td class="<?= $values[$n]['hl'] ? 'redColor' : ''; ?>"> <?php echo e($values[$n]['hl']); ?> </td>
                                    <td class="<?= $values[$n]['hl'] ? 'redColor' : ''; ?>"> <?php echo e($values[$n]['hasil']); ?> </td>
                                    <!-- <td> <?php echo e($values[0]['hasil']); ?> </td> -->
                                    <td> <?php echo e($values[$n]['pemeriksaan_laborat']['nilainormal']); ?> </td>
                                    <td> <?php echo e($values[$n]['pemeriksaan_laborat']['satuan']); ?> </td>
                                    <td> <?php echo e($values[$n]['metode']); ?> </td>
                                </tr>
                            <?php } elseif ($values[0]['pemeriksaan_laborat']['rs21'] !== '' && $n === 0) {
                                $total +=  $values[0]['subtotal'];
                            ?>
                                <tr>
                                    <td colspan="4"> <?php echo e($values[0]['pemeriksaan_laborat']['rs21']); ?> </td>

                                </tr>
                                <tr class="list">
                                    <td> - <?php echo e($values[0]['pemeriksaan_laborat']['rs2']); ?> </td>
                                    <td class="<?= $values[0]['hl'] ? 'redColor' : ''; ?>"> <?php echo e($values[0]['hl']); ?> </td>
                                    <td class="<?= $values[0]['hl'] ? 'redColor' : ''; ?>"> <?php echo e($values[0]['hasil']); ?> </td>
                                    <td> <?php echo e($values[0]['pemeriksaan_laborat']['nilainormal']); ?> </td>
                                    <td> <?php echo e($values[0]['pemeriksaan_laborat']['satuan']); ?> </td>
                                    <td> <?php echo e($values[0]['metode']); ?> </td>
                                </tr>
                            <?php } else {
                            ?>
                                <tr class="list">
                                    <td> - <?php echo e($values[$n]['pemeriksaan_laborat']['rs2']); ?> </td>
                                    <td class="<?= $values[$n]['hl'] ? 'redColor' : ''; ?>"> <?php echo e($values[$n]['hl']); ?> </td>
                                    <td class="<?= $values[$n]['hl'] ? 'redColor' : ''; ?>"> <?php echo e($values[$n]['hasil']); ?> </td>
                                    <td> <?php echo e($values[$n]['pemeriksaan_laborat']['nilainormal']); ?> </td>
                                    <td> <?php echo e($values[$n]['pemeriksaan_laborat']['satuan']); ?> </td>
                                    <td> <?php echo e($values[$n]['metode']); ?> </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    <?php $i++;
                    } ?>

                </tbody>
            </table>


            <br>
            <p>Saran : </p>
            <p>interpretasi : </p>
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
            <!-- Scan disini untuk verifikasi :<br> -->
        <?php } ?>
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
<?php /**PATH /var/www/resources/views/print/permintaan_laborat_luar.blade.php ENDPATH**/ ?>