<?php

namespace App\Exports;

use Generator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Export e-resep menggunakan query SQL langsung.
 * Semua agregasi dilakukan di database dan hasil dibaca dengan cursor agar
 * tidak terjadi N+1 query serta tidak menumpuk di memory PHP.
 */
class DepoEresepExport implements FromGenerator, WithHeadings, WithColumnWidths
{
    private string $from;
    private string $to;
    private string $depo;
    private string $flag;
    private string $format;

    public function __construct(string $from, string $to, string $depo, string $flag, string $format)
    {
        $this->from = $from;
        $this->to = $to;
        $this->depo = $depo;
        $this->flag = $flag;
        $this->format = $format;
    }

    public function headings(): array
    {
        $header = [
            'No', 'No. Resep', 'Tanggal Permintaan', 'Tanggal Kirim', 'Tanggal Selesai',
            'No. RM', 'No. Registrasi', 'Nama Pasien', 'Dokter', 'Poli/Ruangan', 'Depo',
            'Tipe Resep', 'Sistem Bayar', 'Status Resep', 'Status Pemberian', 'Alasan',
        ];

        return $this->format === 'rincian'
            ? array_merge($header, [
                'Jenis Item', 'Nama Racikan', 'Kode Obat', 'Nama Obat', 'Aturan Pakai',
                'Jumlah Diresepkan', 'Jumlah Diberikan', 'Jumlah Retur', 'Satuan', 'Status Item',
            ])
            : $header;
    }

    public function generator(): Generator
    {
        $number = 1;

        foreach ($this->rows() as $row) {
            $header = [
                $number++, $row->noresep, $row->tgl_permintaan, $row->tgl_kirim, $row->tgl_selesai,
                $row->norm, $row->noreg, $row->nama_pasien ?? '', $row->dokter_nama ?? '',
                $row->ruangan_nama ?? '', $row->depo, $row->tiperesep, $row->sistem_bayar ?? '',
                $row->status_resep, $row->status_pemberian, $row->alasan ?? '',
            ];

            if ($this->format === 'header') {
                yield $header;
                continue;
            }

            yield array_merge($header, [
                $row->jenis_item ?? '', $row->nama_racikan ?? '', $row->kdobat ?? '',
                $row->nama_obat ?? '', $row->aturan ?? '', $row->jumlah_diresepkan ?? '',
                $row->jumlah_diberikan ?? '', $row->jumlah_retur ?? '', $row->satuan ?? '',
                $row->status_item ?? '',
            ]);
        }
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8, 'B' => 22, 'C' => 18, 'D' => 18, 'E' => 18, 'F' => 14, 'G' => 18,
            'H' => 30, 'I' => 26, 'J' => 26, 'K' => 16, 'L' => 14, 'M' => 20, 'N' => 20,
            'O' => 24, 'P' => 40, 'Q' => 14, 'R' => 24, 'S' => 16, 'T' => 36, 'U' => 18,
            'V' => 18, 'W' => 18, 'X' => 14, 'Y' => 20,
        ];
    }

    public function rows(): iterable
    {
        [$baseSql, $baseBindings] = $this->baseQuery();
        $statusPemberian = $this->statusPemberianSql('h');
        $connection = DB::connection('farmasi');
        $temporaryTable = 'tmp_depo_eresep_' . bin2hex(random_bytes(5));

        $connection->statement("CREATE TEMPORARY TABLE `{$temporaryTable}` (
            id BIGINT NOT NULL PRIMARY KEY,
            noresep VARCHAR(191) NOT NULL,
            noresep_asal VARCHAR(191) NULL,
            KEY idx_tmp_noresep (noresep),
            KEY idx_tmp_noresep_asal (noresep_asal)
        ) ENGINE=InnoDB");
        $sourceTable = $temporaryTable . '_sources';

        try {
            $connection->statement(
                "INSERT INTO `{$temporaryTable}` (id, noresep, noresep_asal) {$baseSql}",
                $baseBindings
            );

            if ($this->format === 'header') {
                $sql = "
                SELECT h.noresep, h.tgl_permintaan, h.tgl_kirim, h.tgl_selesai,
                    h.norm, h.noreg, h.depo, h.tiperesep, h.alasan, h.flag,
                    COALESCE(p.rs2, '') AS nama_pasien,
                    COALESCE(d.nama, '') AS dokter_nama,
                    COALESCE(NULLIF(po.rs2, ''), NULLIF(rn.rs2, ''), '') AS ruangan_nama,
                    COALESCE(sb.rs2, '') AS sistem_bayar,
                    {$this->statusResepSql('h')} AS status_resep,
                    {$statusPemberian} AS status_pemberian
                FROM `{$temporaryTable}` t
                JOIN farmasi.resep_keluar_h h ON h.id = t.id
                LEFT JOIN rs.rs15 p ON p.rs1 = h.norm
                LEFT JOIN kepegx.pegawai d ON d.kdpegsimrs = h.dokter
                LEFT JOIN rs.rs19 po ON po.rs1 = h.ruangan
                LEFT JOIN rs.rs24 rn ON rn.rs1 = h.ruangan
                LEFT JOIN rs.rs9 sb ON sb.rs1 = h.sistembayar
                ORDER BY h.id
            ";

                yield from $connection->cursor($sql);
                return;
            }

            $connection->statement("CREATE TEMPORARY TABLE `{$sourceTable}` (
                noresep VARCHAR(191) NOT NULL PRIMARY KEY
            ) ENGINE=InnoDB");
            $connection->statement("INSERT IGNORE INTO `{$sourceTable}` (noresep)
                SELECT noresep FROM `{$temporaryTable}` WHERE noresep <> ''");
            $connection->statement("INSERT IGNORE INTO `{$sourceTable}` (noresep)
                SELECT noresep_asal FROM `{$temporaryTable}`
                WHERE noresep_asal IS NOT NULL AND noresep_asal <> ''");

            $detailTables = $this->prepareDetailTables($connection, $temporaryTable, $sourceTable);
            $itemsTable = $detailTables['items'];
            $givenNonTable = $detailTables['given_non'];
            $givenRacikTable = $detailTables['given_racik'];
            $returTable = $detailTables['retur'];
            $sql = "
            SELECT h.noresep, h.tgl_permintaan, h.tgl_kirim, h.tgl_selesai,
                h.norm, h.noreg, h.depo, h.tiperesep, h.alasan, h.flag,
                COALESCE(p.rs2, '') AS nama_pasien,
                COALESCE(d.nama, '') AS dokter_nama,
                COALESCE(NULLIF(po.rs2, ''), NULLIF(rn.rs2, ''), '') AS ruangan_nama,
                COALESCE(sb.rs2, '') AS sistem_bayar,
                {$this->statusResepSql('h')} AS status_resep,
                {$statusPemberian} AS status_pemberian,
                i.jenis_item, i.nama_racikan, i.kdobat, COALESCE(o.nama_obat, '') AS nama_obat,
                i.aturan, i.jumlah_diresepkan,
                CASE WHEN i.jenis_item = 'Racikan' THEN COALESCE(gr.jumlah_diberikan, 0)
                    ELSE COALESCE(gn.jumlah_diberikan, 0) END AS jumlah_diberikan,
                COALESCE(rt.jumlah_retur, 0) AS jumlah_retur,
                COALESCE(o.satuan_k, '') AS satuan,
                CASE
                    WHEN i.kdobat IS NULL THEN ''
                    WHEN h.flag IN ('3', '4') AND
                        (CASE WHEN i.jenis_item = 'Racikan' THEN COALESCE(gr.jumlah_diberikan, 0)
                            ELSE COALESCE(gn.jumlah_diberikan, 0) END) <= 0 THEN 'Tidak diberikan'
                    WHEN h.flag IN ('3', '4') AND
                        (CASE WHEN i.jenis_item = 'Racikan' THEN COALESCE(gr.jumlah_diberikan, 0)
                            ELSE COALESCE(gn.jumlah_diberikan, 0) END) < i.jumlah_diresepkan THEN 'Diberikan sebagian'
                    WHEN h.flag IN ('3', '4') THEN 'Diberikan semua'
                    WHEN h.flag = '5' THEN 'Tidak diberikan'
                    ELSE 'Belum diproses'
                END AS status_item
            FROM `{$temporaryTable}` t
            JOIN farmasi.resep_keluar_h h ON h.id = t.id
            LEFT JOIN `{$itemsTable}` i ON (
                i.header_noresep = h.noresep
            )
            LEFT JOIN `{$givenNonTable}` gn ON gn.noresep = i.source_noresep AND gn.kdobat = i.kdobat AND i.jenis_item = 'Non Racik'
            LEFT JOIN `{$givenRacikTable}` gr ON gr.noresep = i.source_noresep AND gr.kdobat = i.kdobat
                AND gr.namaracikan = i.nama_racikan AND i.jenis_item = 'Racikan'
            LEFT JOIN `{$returTable}` rt ON rt.noresep = i.source_noresep AND rt.kdobat = i.kdobat
            LEFT JOIN farmasi.new_masterobat o ON o.kd_obat = i.kdobat
            LEFT JOIN rs.rs15 p ON p.rs1 = h.norm
            LEFT JOIN kepegx.pegawai d ON d.kdpegsimrs = h.dokter
            LEFT JOIN rs.rs19 po ON po.rs1 = h.ruangan
            LEFT JOIN rs.rs24 rn ON rn.rs1 = h.ruangan
            LEFT JOIN rs.rs9 sb ON sb.rs1 = h.sistembayar
            ORDER BY h.id, i.jenis_item, i.kdobat
        ";

            yield from $connection->cursor($sql);
        } finally {
            if (isset($detailTables)) {
                foreach ($detailTables as $table) {
                    $connection->statement("DROP TEMPORARY TABLE IF EXISTS `{$table}`");
                }
            }
            $connection->statement("DROP TEMPORARY TABLE IF EXISTS `{$sourceTable}`");
            $connection->statement("DROP TEMPORARY TABLE IF EXISTS `{$temporaryTable}`");
        }
    }

    private function baseQuery(): array
    {
        $from = $this->from . ' 00:00:00';
        $to = $this->to . ' 23:59:59';
        $where = ["h.tiperesep <> 'penjualan'", 'h.depo = ?'];
        $bindings = [$this->depo];

        if ($this->flag === 'semua') {
            $where[] = "((h.flag IN ('1', '2', '3', '4') AND h.tgl_kirim BETWEEN ? AND ?)
                OR (h.flag IN ('', '5') AND h.tgl_permintaan BETWEEN ? AND ?))";
            array_push($bindings, $from, $to, $from, $to);
        } elseif ($this->flag === 'tidak_diberikan_semua') {
            $where[] = "h.flag = '3' AND h.tgl_kirim BETWEEN ? AND ?";
            array_push($bindings, $from, $to);
            $where[] = '(' . $this->missingItemSql('h') . ')';
        } else {
            $where[] = 'h.flag = ?';
            $dateColumn = in_array($this->flag, ['', '5'], true) ? 'tgl_permintaan' : 'tgl_kirim';
            $where[] = "h.{$dateColumn} BETWEEN ? AND ?";
            array_push($bindings, $this->flag, $from, $to);
        }

        return [
            'SELECT h.id, h.noresep, h.noresep_asal FROM farmasi.resep_keluar_h h WHERE ' . implode(' AND ', $where),
            $bindings,
        ];
    }

    private function prepareDetailTables($connection, string $headerTable, string $sourceTable): array
    {
        $suffix = '_' . bin2hex(random_bytes(4));
        $items = $sourceTable . '_items' . $suffix;
        $givenNon = $sourceTable . '_given_non' . $suffix;
        $givenRacik = $sourceTable . '_given_racik' . $suffix;
        $retur = $sourceTable . '_retur' . $suffix;

        $connection->statement("CREATE TEMPORARY TABLE `{$items}` (
            header_noresep VARCHAR(191) NOT NULL,
            source_noresep VARCHAR(191) NOT NULL,
            jenis_item VARCHAR(20) NOT NULL,
            nama_racikan VARCHAR(255) NOT NULL,
            kdobat VARCHAR(100) NOT NULL,
            aturan VARCHAR(255) NULL,
            jumlah_diresepkan DECIMAL(20,6) NULL,
            KEY idx_item_header (header_noresep, kdobat),
            KEY idx_item_source (source_noresep, kdobat)
        ) ENGINE=InnoDB");
        $connection->statement("INSERT INTO `{$items}`
            (header_noresep, source_noresep, jenis_item, nama_racikan, kdobat, aturan, jumlah_diresepkan)
            SELECT s.noresep, pr.noresep, 'Non Racik', '', pr.kdobat, pr.aturan, pr.jumlah
            FROM `{$sourceTable}` s
            JOIN farmasi.resep_permintaan_keluar pr ON pr.noresep = s.noresep");
        $connection->statement("INSERT INTO `{$items}`
            (header_noresep, source_noresep, jenis_item, nama_racikan, kdobat, aturan, jumlah_diresepkan)
            SELECT s.noresep, pr.noresep, 'Racikan', pr.namaracikan, pr.kdobat, pr.aturan, pr.jumlah
            FROM `{$sourceTable}` s
            JOIN farmasi.resep_permintaan_keluar_racikan pr ON pr.noresep = s.noresep");

        $connection->statement("INSERT INTO `{$items}`
            (header_noresep, source_noresep, jenis_item, nama_racikan, kdobat, aturan, jumlah_diresepkan)
            SELECT t.noresep, pr.noresep, 'Non Racik', '', pr.kdobat, pr.aturan, pr.jumlah
            FROM `{$headerTable}` t
            JOIN farmasi.resep_permintaan_keluar pr ON pr.noresep = t.noresep_asal
            WHERE t.noresep_asal <> ''
                AND NOT EXISTS (SELECT 1 FROM farmasi.resep_permintaan_keluar px WHERE px.noresep = t.noresep)");
        $connection->statement("INSERT INTO `{$items}`
            (header_noresep, source_noresep, jenis_item, nama_racikan, kdobat, aturan, jumlah_diresepkan)
            SELECT t.noresep, pr.noresep, 'Racikan', pr.namaracikan, pr.kdobat, pr.aturan, pr.jumlah
            FROM `{$headerTable}` t
            JOIN farmasi.resep_permintaan_keluar_racikan pr ON pr.noresep = t.noresep_asal
            WHERE t.noresep_asal <> ''
                AND NOT EXISTS (SELECT 1 FROM farmasi.resep_permintaan_keluar_racikan px WHERE px.noresep = t.noresep)");

        $connection->statement("CREATE TEMPORARY TABLE `{$givenNon}` AS
            SELECT r.noresep, r.kdobat, SUM(r.jumlah) AS jumlah_diberikan
            FROM farmasi.resep_keluar_r r
            JOIN `{$sourceTable}` s ON s.noresep = r.noresep
            GROUP BY r.noresep, r.kdobat");
        $connection->statement("ALTER TABLE `{$givenNon}` ADD KEY idx_given_non (noresep, kdobat)");

        $connection->statement("CREATE TEMPORARY TABLE `{$givenRacik}` AS
            SELECT r.noresep, r.kdobat, r.namaracikan, SUM(r.jumlah) AS jumlah_diberikan
            FROM farmasi.resep_keluar_racikan_r r
            JOIN `{$sourceTable}` s ON s.noresep = r.noresep
            GROUP BY r.noresep, r.kdobat, r.namaracikan");
        $connection->statement("ALTER TABLE `{$givenRacik}` ADD KEY idx_given_racik (noresep, kdobat, namaracikan)");

        $connection->statement("CREATE TEMPORARY TABLE `{$retur}` AS
            SELECT r.noresep, r.kdobat, SUM(r.jumlah_retur) AS jumlah_retur
            FROM farmasi.retur_penjualan_r r
            JOIN `{$sourceTable}` s ON s.noresep = r.noresep
            GROUP BY r.noresep, r.kdobat");
        $connection->statement("ALTER TABLE `{$retur}` ADD KEY idx_retur (noresep, kdobat)");

        return [
            'items' => $items,
            'given_non' => $givenNon,
            'given_racik' => $givenRacik,
            'retur' => $retur,
        ];
    }

    private function missingItemSql(string $headerAlias): string
    {
        return "
            EXISTS (
                SELECT 1 FROM farmasi.resep_permintaan_keluar pr
                WHERE pr.noresep = {$headerAlias}.noresep
                    AND NOT EXISTS (
                        SELECT 1 FROM farmasi.resep_keluar_r r
                        WHERE r.noresep = pr.noresep AND r.kdobat = pr.kdobat
                    )
            ) OR EXISTS (
                SELECT 1 FROM farmasi.resep_permintaan_keluar_racikan pr
                WHERE pr.noresep = {$headerAlias}.noresep
                    AND NOT EXISTS (
                        SELECT 1 FROM farmasi.resep_keluar_racikan_r r
                        WHERE r.noresep = pr.noresep AND r.kdobat = pr.kdobat
                            AND r.namaracikan = pr.namaracikan
                    )
            )";
    }

    private function statusPemberianSql(string $headerAlias): string
    {
        return "CASE
            WHEN {$headerAlias}.flag IN ('3', '4') AND ({$this->missingItemSql($headerAlias)})
                THEN 'Tidak diberikan semua'
            WHEN {$headerAlias}.flag IN ('3', '4') THEN 'Diberikan semua'
            WHEN {$headerAlias}.flag = '5' THEN 'Tidak diberikan'
            ELSE 'Belum diproses'
        END";
    }

    private function statusResepSql(string $headerAlias): string
    {
        return "CASE COALESCE({$headerAlias}.flag, '')
            WHEN '' THEN 'Belum Dikirimkan'
            WHEN '1' THEN 'Belum Diterima'
            WHEN '2' THEN 'Siap Dikerjakan'
            WHEN '3' THEN 'Selesai'
            WHEN '4' THEN 'Returned'
            WHEN '5' THEN 'Ditolak'
            ELSE 'Tidak diketahui'
        END";
    }
}
