<table class="table mt-10">
            <thead>
                <tr>
                <th
                    class="text-left"
                    width="5%"
                >
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
            <?php $total = 0; ?>
            <?php echo e($details); ?>

            <?php $__currentLoopData = $values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if( $item['pemeriksaan_laborat']['rs21'] === '' ): ?>
                    <tr>
                        <td> <?php echo e($i); ?> </td>
                        <td> <?php echo e($item['pemeriksaan_laborat']['rs2']); ?> </td>
                        <td class="text-right"> <?php echo e($item['jml']); ?> </td>
                        <td class="text-right"> <?php echo e(number_format($item['biaya'], 0, ',', '.')); ?> </td>
                        <td class="text-right"> <?php echo e(number_format(($item['tarif_sarana'] + $item['tarif_pelayanan']) * $item['jml'], 0, ',', '.')); ?> </td>
                    </tr>
                    <?php else: ?>
                        <?php if($a === 0): ?>
                        <tr>
                            <td> <?php echo e($i); ?> </td>
                            <td> <?php echo e($item['pemeriksaan_laborat']['rs21']); ?> </td>
                            <td class="text-right"> <?php echo e($item['jml']); ?> </td>
                            <td class="text-right"> <?php echo e(number_format($item['biaya'], 0, ',', '.')); ?> </td>
                            <td class="text-right"> <?php echo e(number_format($item['subtotal'], 0, ',', '.')); ?> </td>
                        </tr>
                        <?php else: ?>
                        <tr class="sub">
                            <td></td>
                            <td colspan="4"> -  <?php echo e($item['pemeriksaan_laborat']['rs2']); ?> </td>
                        </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <!-- <tr style="border-top: solid 1px rgb(190, 190, 190); border-bottom: solid 1px rgb(190, 190, 190);">
                <td colspan="2" class=" bold">JUMLAH PEMERIKSAAN: <?php echo e($details->count('nota')); ?> </td>
                <td colspan="2" class="text-right bold">TOTAL </td>
                <td class="text-right bold"> <?php echo e(number_format($details->sum('subtotal'), 0, ',', '.')); ?> </td>
            </tr> -->
            </tbody>
        </table>
        <hr />
        <div class="row justify-between">
            <div></div>
            <!-- <div class="flex-right"> Probolinggo, <?php echo e(date('j F, Y', strtotime($details[0]->tgl))); ?></div> -->
        </div>
        <div class="row justify-between">
            <div></div>
            <div class="flex-right"> Petugas, </div>
        </div>




        <?php foreach ($gg as $key => $values) {?>
                <?php $i=0;  foreach ($values as $item) {?>
                        <tr>
                    <?php if ($item->pemeriksaan_laborat->rs21 === '') { ?>
                            <td><?php $i++ ?></td>
                            <td> <?php $item->pemeriksaan_laborat->rs2 ?> </td>
                        <?php } ?>
                        </tr>
                <?php } ?>
            <?php } ?>
<?php /**PATH /var/www/resources/views/print/copian.blade.php ENDPATH**/ ?>