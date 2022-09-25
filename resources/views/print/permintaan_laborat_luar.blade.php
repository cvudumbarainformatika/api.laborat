<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" media="print" href="{{ URL::asset('print') }}/mystyles.scss">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('styles') }}/main.scss">
    <title>Print</title>
</head>

<body>
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
            @foreach($details as $i => $item)
                <tr>
                    <td> {{ $i+1 }} </td>
                    <td> {{ $item->pemeriksaan_laborat->rs2 }} </td>
                    <td class="text-right"> {{ $item->jml }} </td>
                    <td class="text-right"> {{ number_format($item->tarif_sarana + $item->tarif_pelayanan, 0, ',', '.') }} </td>
                    <td class="text-right"> {{ number_format(($item->tarif_sarana + $item->tarif_pelayanan) * $item->jml, 0, ',', '.') }} </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <hr />
    </div>
</body>
<script type="text/javascript">
    window.print();
</script>

</html>
