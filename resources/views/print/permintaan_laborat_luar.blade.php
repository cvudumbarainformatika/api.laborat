<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" media="print" href="{{ URL::asset('print') }}/mystyles.scss">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('styles') }}/main.scss">
    <title></title>
</head>

<body topmargin="0" leftmargin="0" rightmargin="0" >
    <div class="page">
        <div class="row">
            <div class="">
                <img class="logo" src="{{ URL::asset('images') }}/logo-kota-grayscale.jpg" alt="logo-kota-grayscale"/>
            </div>
            <div class="mt-10 ml-10">
                <div class="title bold">{{ $header->title }}</div>
                <div class="subtitle">{{ $header->sub }}</div>
                <div class="subtitle">{{ $header->sub2 }}</div>
            </div>
        </div>
        <hr />
        <div class="title bold mb-10 text-center">PERMINTAAN LABORAT</div>

        <div class="row justify-between">
            <div class="column">
                <div class="row">
                    <div class="w-x">Nama</div>
                    <div>: {{ $details[0]->nama }}</div>
                </div>
                <div class="row">
                    <div class="w-x">Kelamin</div>
                    <div>: {{ $details[0]->kelamin }}</div>
                </div>
                <div class="row">
                    <div class="w-x">Alamat</div>
                    <div>: {{ $details[0]->alamat }}</div>
                </div>
            </div>
            <div class="text-right">
                <div class="row flex-right">
                    <div >Nota : </div>
                    <div > {{ $details[0]->nota }}</div>
                </div>
                <div class="row flex-right">
                    <div >Dokter Pengirim : </div>
                    <div > {{ $details[0]->pengirim }}</div>
                </div>
                <div class="row flex-right">
                    <div >Tanggal : </div>
                    <div > {{ $details[0]->tgl }}</div>
                </div>
            </div>
        </div>

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
            @foreach($details as $i => $item)
                @if( $item->pemeriksaan_laborat->rs21 == '' )
                <tr>
                    <td> {{ $i+1 }} </td>
                    <td> {{ $item->pemeriksaan_laborat->rs2 }} </td>
                    <td class="text-right"> {{ $item->jml }} </td>
                    <td class="text-right"> {{ number_format($item->biaya, 0, ',', '.') }} </td>
                    <td class="text-right"> {{ number_format(($item->tarif_sarana + $item->tarif_pelayanan) * $item->jml, 0, ',', '.') }} </td>
                </tr>
                @else

                <tr>
                    <td> {{ $i+1 }} </td>
                    <td> {{ $item->pemeriksaan_laborat->rs21 }} </td>
                    <td class="text-right"> {{ $item->jml }} </td>
                    <!-- <td class="text-right"> {{ number_format($item->tarif_sarana + $item->tarif_pelayanan, 0, ',', '.') }} </td> -->
                    <td class="text-right"> {{ number_format($item->biaya, 0, ',', '.') }} </td>
                    <td class="text-right"> {{ number_format($item->subtotal, 0, ',', '.') }} </td>
                    <!-- <td class="text-right"> {{ number_format(($item->tarif_sarana + $item->tarif_pelayanan) * $item->jml, 0, ',', '.') }} </td> -->
                </tr>
                    <?php
                        $total += $item->subtotal;
                        $subs= App\Models\PemeriksaanLaborat::where('rs21', '=', $item->pemeriksaan_laborat->rs21)->get();
                    ?>
                    @foreach($subs as $n => $sub)
                    <tr class="sub">
                        <td></td>
                        <td colspan="4"> -  {{ $sub->rs2 }} </td>
                        <!-- <td class="text-right"> {{ number_format($item->tarif_sarana + $item->tarif_pelayanan, 0, ',', '.') }} </td>
                        <td class="text-right"> {{ number_format(($item->tarif_sarana + $item->tarif_pelayanan) * $item->jml, 0, ',', '.') }} </td> -->
                    </tr>
                    @endforeach
                @endif
            @endforeach
            <tr style="border-top: solid 1px rgb(190, 190, 190);">
                <td colspan="2" class=" bold">JUMLAH PEMERIKSAAN: {{ $details->count('nota') }} </td>
                <td colspan="2" class="text-right bold">TOTAL </td>
                <td class="text-right bold"> {{ number_format($details->sum('subtotal'), 0, ',', '.') }} </td>
            </tr>
            </tbody>
        </table>
        <hr />
        <div class="row justify-between">
            <div></div>
            <div class="flex-right"> Probolinggo, {{ date('j F, Y', strtotime($details[0]->tgl)) }}</div>
        </div>
        <div class="row justify-between">
            <div></div>
            <div class="flex-right"> Petugas, </div>
        </div>
    </div>
</body>


</html>

<script language="javascript">
    window.print();
    window.onafterprint = function () {
        window.close();
    }
    setTimeout(function(){
             window.close();
    }, 1000);
</script>
