# KAMUS DATA & METADATA PERHITUNGAN BILLING RAWAT INAP (SIMRS)
## Arsitektur Data Billing Rumah Sakit untuk Casemix AI Assistant & Early Warning System (EWS)

Dokumen ini disusun sebagai referensi teknis dan kamus metadata komprehensif bagi **Casemix AI Assistant** untuk melakukan:
1. **Perhitungan Real Cost Pasien Rawat Inap** (riil tagihan rumah sakit).
2. **Komparasi Tarif Riil vs Tarif INA-CBGs / Klaim Asuransi** (Gap Analysis & Cost Containment).
3. **Early Warning System (EWS) Biaya**: Mendeteksi dini potensi klaim minus, pembengkakan biaya (cost overrun), obat kronis/mahal, transfusi darah berlebih, maupun perpindahan kelas rawat (naik/turun kelas) dan multijaminan (COB BPJS + Jasa Raharja).

---

## 1. STRUKTUR MASTER DATA & TABEL TRANSAKSI

| Nama Tabel | Deskripsi Entitas | Primary Key / Index Utama | Kolom Kunci yang Digunakan |
| :--- | :--- | :--- | :--- |
| **`rs15`** | Master Data Pasien | `rs1` (Norm) | `rs1` (Norm), `rs2` (Nama Pasien), `rs4` (Alamat), `rs16` (Tgl Lahir), `rs17` (Kelamin) |
| **`rs17`** | Registrasi Masuk IRD / IGD | `rs1` (Noreg IRD) | `rs1` (Noreg), `rs8` (Kode Ruang IRD: `POL014`), `rs14` (Kode Sistem Bayar IRD) |
| **`rs23`** | Registrasi Masuk Rawat Inap | `rs1` (Noreg Ranap) | `rs1` (Noreg), `rs2` (Norm), `rs3` (Tgl Masuk), `rs4` (Tgl Keluar), `rs5` (Kd Kamar), `rs10` (Dokter DPJP), `rs19` (Sistem Bayar Masuk) |
| **`rs24`** | Master Ruang Perawatan / Paviliun | `rs1` (Kode Kamar) | `rs1` (Kd Kamar), `rs2` (Nama Ruangan), `rs3` (Kelas Rawat: `1`,`2`,`3`,`VIP`,`VVIP`,`IC`,`ICC`,`NICU`,`IN`,`HCU`), `rs4` (Kode Ruangan Poli) |
| **`rs21`** | Master Dokter & Paramedis | `rs1` (Kode Dokter) | `rs1` (Kode), `rs2` (Nama Dokter/Nakes), `rs13` (Flag Profesi: `1`=Dokter, `2`/`3`=Perawat/Bidan) |
| **`rs9`** | Master Sistem Bayar / Penjamin | `rs1` (Kode Sistem Bayar) | `rs1` (Kode: misal `BPJS01`,`AR32`), `rs2` (Nama Penjamin), `rs9` (Grup Penjamin) |
| **`rs30tarif`** | Master Tarif Standar Rumah Sakit | `rs1` (Kode Tarif), `rs3` (Kelompok) | `rs1` (Kode), `rs3` (Kategori: `A1#`=Adm, dll), `rs6`..`rs17` (Komponen Tarif per Kelas Rawat) |
| **`rs35`** | Transaksi Billing Induk Ranap | `rs1` (Noreg) | `rs1` (Noreg), `rs3` (Flag Kategori: `M1#`=Materai, `AB#`=Ambulan), `rs7` (Biaya), `rs8` (Sistem Bayar) |
| **`rs35x`** | Transaksi Billing Detail / Riwayat Ruang | `rs1` (Noreg), `rs4` (No Urut) | `rs1` (Noreg), `rs3` (`K1#`=Kamar Ranap, `A2#`=Adm IRD), `rs7` (Sarana), `rs8` (Sistem Bayar), `rs14` (Pelayanan), `rs16` (Kd Ruang), `rs17` (Kelas) |
| **`rs73`** | Tindakan Medik Dokter & Paramedis | `rs1` (Noreg), `rs2` (No Nota) | `rs1` (Noreg), `rs4` (Kd Tindakan), `rs5` (Jumlah), `rs7` (Jasa Sarana), `rs8` (Kd Dokter), `rs13` (Jasa Pelayanan), `rs22` (Kd Ruangan/Unit), `rs24` (Sistem Bayar) |
| **`rs140`** | Visite & Konsultasi Dokter Ranap | `rs1` (Noreg) | `rs1` (Noreg), `rs3` (Kd Dokter), `rs4`/`rs5`/`rs7`/`rs10` (Biaya Sarana & Jasa Visite), `rs6` (Kelas), `rs8` (Kd Ruang), `rs9` (Sistem Bayar) |
| **`rs202`** | Pelayanan Gizi & Makan Pasien | `rs1` (Noreg) | `rs1` (Noreg), `rs3` (Kode Tarif Gizi: `K00013`=Asuhan Gizi, `K00003`/`K00004`=Makan), `rs4`+`rs5` (Biaya), `rs8` (Kd Ruang), `rs9` (Sistem Bayar) |
| **`rs203`** | Jasa Asuhan Keperawatan Ranap | `rs1` (Noreg) | `rs1` (Noreg), `rs3` (Kd Tarif), `rs4`+`rs5` (Biaya), `rs8` (Kd Ruang), `rs9` (Sistem Bayar) |
| **`rs205`** | Pemakaian Oksigen (Gas Medis) | `rs1` (Noreg) | `rs1` (Noreg), `rs3` (Kd Tarif), `rs4`+`rs5` (Tarif Satuan), `rs6` (Liter/Volume), `rs8` (Kd Ruang), `rs9` (Sistem Bayar) |
| **`rs48` / `rs47`** | Pelayanan Radiologi | `rs48.rs1` (Noreg) | `rs48.rs4` (Kd Pemeriksaan), `rs48.rs6`+`rs48.rs8` (Biaya), `rs48.rs24` (Jumlah), `rs48.rs26` (Ruang Asal), `rs48.rs27` (Sistem Bayar) |
| **`rs51` / `rs49`** | Pelayanan Laboratorium PK/PA | `rs51.rs1` (Noreg), `rs51.rs2` (Nota) | `rs51.rs4` (Kd Lab), `rs51.rs5` (Jumlah), `rs51.rs6`+`rs51.rs13` (Biaya), `rs51.rs21` (Hasil/Status), `rs51.rs23` (Ruang Asal), `rs51.rs24` (Sistem Bayar), `rs51.lunas` |
| **`rs54` / `rs53`** | Pelayanan Operasi Ranap & Endoskopi | `rs54.rs1` (Noreg) | `rs54.rs4` (Kd Operasi), `rs54.rs5`..`rs54.rs7` (Biaya), `rs54.rs8` (Qty), `rs54.rs14` (Sistem Bayar), `rs54.rs15` (Ruang/Unit Asal) |
| **`rs226`** | Pelayanan Operasi Cito IRD | `rs226.rs1` (Noreg) | `rs226.rs4` (Kd Operasi), `rs226.rs5`..`rs226.rs7` (Biaya), `rs226.rs8` (Qty), `rs226.rs15` (`POL014`), `rs226.rs19` (Sistem Bayar) |
| **`rs231`** | Pelayanan Transfusi & Penggunaan Darah | `rs231.rs1` (Noreg) | `rs231.rs12`+`rs231.rs13`+`biayalain2` (Biaya Kantong & Crossmatch), `rs231.rs14` (Ruang Asal), `rs231.rs16` (Sistem Bayar) |
| **`rs38`, `rs39`, `rs40`** | Resep & Obat Non-Racikan / Racikan | `rs38.rs1`, `rs39.rs1` (Noreg) | `rs38.rs6`*`rs38.rs8`+`rs38.rs10` (Subtotal), `rs38.rs21`/`rs39.rs16` (Sistem Bayar), `rs38.rs24` (`IRD`/Ranap), `rs38.rs25` (`CENTRAL`/`IGD`), `lunas` |
| **`rs62`, `rs63`, `rs64`** | Resep & Obat Ranap Bebas/Kronis | `rs62.rs1`, `rs63.rs1` (Noreg) | Serupa dengan `rs38`/`rs39` |
| **`rs87`, `rs88`** | Retur Obat / Alkes Farmasi | `rs87.rs7` (Noreg) | `rs88.rs3` (Qty Retur) * `rs88.rs4` (Harga Satuan), `rs87.rs9` (Sistem Bayar) |
| **`rsjr`** | Materai Khusus Jasa Raharja / Tagihan | `rs1` (Noreg) | `rs5` (Biaya Materai), `rs7` (`IRD` / Unit Asal) |

---

## 2. MODUS PERHITUNGAN BILLING

SIMRS memiliki 4 modus perhitungan tagihan:
1. **Modus Global (`ALL__ALL`)**: Menghitung seluruh biaya pasien selama dirawat di semua ruangan dan semua sistem bayar (`billing.php`).
2. **Modus Per Sistem Bayar (`ALL__<KD_SB>`)**: Menghitung riil tagihan yang dibebankan kepada penjamin tertentu (misal Jasa Raharja `AR32` atau BPJS `BPJS3`), mengabaikan biaya yang dijamin oleh penjamin lainnya (`billingbysistembayar.php`).
3. **Modus Per Ruangan (`<KD_RUANG>__ALL`)**: Menghitung biaya hanya selama pasien dirawat di paviliun/ruangan singgah tertentu (`billingbyruangan.php`).
4. **Modus Per Ruangan & Sistem Bayar (`<KD_RUANG>__<KD_SB>`)**: Menghitung porsi tagihan spesifik pada ruangan tertentu dan penjamin tertentu (`billingbyruanganbysistembayar.php`).

---

## 3. FORMULA DAN QUERY LENGKAP PER KOMPONEN BIAYA

### 1. Administrasi Rawat Inap
* **Fungsi**: Biaya registrasi masuk rawat inap berdasarkan kelas perawatan saat pertama kali admisi.
* **Tabel Terkait**: `rs35x`, `rs30tarif` (kategori `rs3 = 'A1#'`).
* **Logika**:
  - Ambil kelas dan sistem bayar admisi pertama:
    ```sql
    SELECT rs8 AS kodesistembayar, rs17 AS kelas 
    FROM rs35x 
    WHERE rs1 = :noreg AND rs3 = 'K1#' 
    ORDER BY rs4 ASC LIMIT 1;
    ```
  - Jika Modus By Sistem Bayar: Biaya bernilai `0` jika sistem bayar admisi awal tidak sama dengan penjamin yang dipilih (`if (rs8 == flagsistembayar)`).
  - Nilai Tarif dari `rs30tarif WHERE rs3='A1#'`:
    * Kelas 3: `rs6 + rs7`
    * Kelas 2: `rs8 + rs9`
    * Kelas 1, ICU, ICCU, NICU, Intermediate, HCU: `rs10 + rs11`
    * Kelas Utama: `rs12 + rs13`
    * Kelas VIP: `rs14 + rs15`
    * Kelas VVIP: `rs16 + rs17`

---

### 2. Akomodasi / Kamar Rawat Inap
* **Fungsi**: Biaya sewa tempat tidur rawat inap (jumlah hari rawat per kelas dikalikan tarif per hari).
* **Tabel Terkait**: `rs35x` (`rs3 = 'k1#'`).
* **Formula Perhitungan**:
  $$\text{Subtotal Kamar} = \sum (\text{Jml Hari} \times (\text{rs7} + \text{rs14}))$$
* **Query SQL**:
  ```sql
  SELECT 
    (rs7 + rs14) AS biaya,
    COUNT(*) AS jml,
    SUM(rs7 + rs14) AS subtotal,
    CASE rs17 
      WHEN '3' THEN 'Kelas III'
      WHEN '2' THEN 'Kelas II'
      WHEN '1' THEN 'Kelas I'
      WHEN 'IC' THEN 'Kelas ICU'
      WHEN 'ICC' THEN 'Kelas ICCU'
      WHEN 'NICU' THEN 'Kelas Nicu'
      WHEN 'IN' THEN 'Kelas Intermediate'
      WHEN 'HCU' THEN 'HCU'
      WHEN 'Utama' THEN 'Utama'
      WHEN 'VIP' THEN 'VIP'
      WHEN 'VVIP' THEN 'VVIP'
      ELSE rs17
    END AS kelas
  FROM rs35x
  WHERE rs3 = 'k1#' 
    AND rs1 = :noreg
    /* Tambahan filter jika per ruangan: AND rs16 = :flagruangan */
    /* Tambahan filter jika per penjamin: AND rs8 = :flagsistembayar */
  GROUP BY rs17, rs7, rs14;
  ```

---

### 3. Biaya Dokumen & Materai Ranap
* **Fungsi**: Biaya bea materai untuk klaim tagihan rawat inap.
* **Query SQL**:
  ```sql
  SELECT SUM(rs7) AS subtotal 
  FROM rs35 
  WHERE rs3 = 'M1#' AND rs1 = :noreg
    /* AND rs8 = :flagsistembayar */
  ```

---

### 4. Jasa / Tindakan Dokter di Ruangan
* **Fungsi**: Tindakan medis operatif minor, tindakan diagnostik/terapeutik di ruang rawat oleh dokter spesialis/umum.
* **Tabel Terkait**: `rs73`, `rs30`, `rs21`.
* **Kriteria Filter**: `rs21.rs13 = '1'` (Profesi Dokter).
* **Query SQL**:
  ```sql
  SELECT SUM((rs73.rs7 + rs73.rs13) * rs73.rs5) AS subtotal
  FROM rs73
  JOIN rs30 ON rs30.rs1 = rs73.rs4
  JOIN rs21 ON rs21.rs1 = SUBSTRING_INDEX(rs73.rs8, ';', 1)
  WHERE rs21.rs13 = '1'
    AND rs73.rs1 = :noreg
    AND (rs73.rs22 IN ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR'))
    /* AND rs73.rs24 = :flagsistembayar */
    /* AND rs73.rs22 = :flagruangan */
  ```

---

### 5. Visite / Konsultasi / Oncall Dokter
* **Fungsi**: Biaya visite harian DPJP dan konsul antar spesialis di ruang rawat inap.
* **Tabel Terkait**: `rs140`, `rs21`, `rs30tarif`.
* **Query SQL**:
  ```sql
  SELECT SUM(rs140.rs4 + rs140.rs5) AS subtotal
  FROM rs140
  JOIN rs21 ON rs21.rs1 = rs140.rs3
  JOIN rs30tarif ON rs30tarif.rs3 = rs140.rs6
  WHERE rs140.rs1 = :noreg
    /* AND rs140.rs9 = :flagsistembayar */
    /* AND rs140.rs8 = :flagruangan */
  ```

---

### 6. Tindakan Keperawatan / Kebidanan
* **Fungsi**: Tindakan asuhan keperawatan dan kebidanan langsung (pemasangan infus, kateter, NGT, rawat luka, dsb).
* **Tabel Terkait**: `rs73`, `rs30`, `rs21`.
* **Kriteria Filter**: `rs21.rs13 IN ('2', '3')` (Perawat / Bidan).
* **Query SQL**:
  ```sql
  SELECT SUM((rs73.rs7 + rs73.rs13) * rs73.rs5) AS subtotal
  FROM rs73
  JOIN rs30 ON rs30.rs1 = rs73.rs4
  JOIN rs21 ON rs21.rs1 = SUBSTRING_INDEX(rs73.rs8, ';', 1)
  WHERE (rs21.rs13 = '2' OR rs21.rs13 = '3')
    AND rs73.rs1 = :noreg
    AND (rs73.rs22 IN ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR'))
    /* AND rs73.rs24 = :flagsistembayar */
  ```

---

### 7 & 8. Pelayanan Gizi (Asuhan Gizi & Makan Pasien)
* **Tabel Terkait**: `rs202`, `rs30tarif`.
* **Asuhan Gizi (Konsultasi Dietetik)**:
  ```sql
  SELECT SUM(rs202.rs4 + rs202.rs5) AS subtotal
  FROM rs202
  JOIN rs30tarif ON rs30tarif.rs1 = rs202.rs3
  WHERE rs202.rs1 = :noreg AND rs30tarif.rs1 = 'K00013'
    /* AND rs202.rs9 = :flagsistembayar */
  ```
* **Makan Pasien (Menu Harian Pasien)**:
  ```sql
  SELECT SUM(rs202.rs4 + rs202.rs5) AS subtotal
  FROM rs202
  JOIN rs30tarif ON rs30tarif.rs1 = rs202.rs3
  WHERE rs202.rs1 = :noreg AND rs30tarif.rs1 IN ('K00004', 'K00003')
    /* AND rs202.rs9 = :flagsistembayar */
  ```

---

### 9. Biaya Oksigen / Gas Medis
* **Tabel Terkait**: `rs205`, `rs30tarif`.
* **Formula**: Volume Liter (`rs6`) $\times$ Tarif Satuan (`rs4 + rs5`).
* **Query SQL**:
  ```sql
  SELECT SUM((rs205.rs4 + rs205.rs5) * rs205.rs6) AS subtotal
  FROM rs205
  JOIN rs30tarif ON rs30tarif.rs1 = rs205.rs3
  WHERE rs205.rs1 = :noreg
    /* AND rs205.rs9 = :flagsistembayar */
  ```

---

### 10. Jasa Asuhan Keperawatan (Harian)
* **Tabel Terkait**: `rs203`, `rs30tarif`.
* **Query SQL**:
  ```sql
  SELECT SUM(rs203.rs4 + rs203.rs5) AS subtotal
  FROM rs203
  JOIN rs30tarif ON rs30tarif.rs1 = rs203.rs3
  WHERE rs203.rs1 = :noreg
    /* AND rs203.rs9 = :flagsistembayar */
  ```

---

### 11. Pelayanan Penunjang Medis

| Unit Penunjang | Tabel & Kondisi Identifikasi | Keterangan & Catatan Casemix |
| :--- | :--- | :--- |
| **Laboratorium** | `rs51` join `rs49`<br>`rs51.rs21 <> '' AND rs51.lunas <> '1'`<br>Ruangan: `rs51.rs23 IN ('BG','BR','DA','FA','IC',...)` | Dilakukan UNION ALL antara parameter lab utama & parameter turunan (`rs49.rs21`). |
| **Radiologi** | `rs48` join `rs47`<br>`rs48.rs26 IN ('BG','BR','DA','FA','IC',...)` | Formula: `(rs48.rs6 + rs48.rs8) * rs48.rs24` (X-Ray, CT-Scan, USG). |
| **Endoskopi** | `rs54` join `rs53`<br>`rs54.rs15 IN ('BG','BR','DA','FA','IC',...)` | Tindakan endoskopi saluran cerna / bronkoskopi. |
| **Kamar Operasi (OK)** | `rs54` join `rs53`<br>`rs54.rs15 IN ('BG','BR','DA','FA','IC',...)` | Operasi terjadwal / elektif rawat inap. |
| **Ruang RR (Recovery Room)** | `rs73` join `rs30`<br>`rs73.rs22 = 'OPERASI'` | Observasi pasca-anestesi dan pemulihan operasi. |
| **Fisioterapi / Rehab** | `rs73` join `rs30`<br>`rs73.rs22 = 'FISIO'` | Layanan rehabilitasi medik. |
| **Hemodialisa (Cuci Darah)** | `rs73` join `rs30`<br>`rs73.rs22 = 'PEN005'` | Sesi cuci darah reguler/cito. Sangat penting untuk EWS biaya kronis. |
| **Cardio / Jantung** | `rs73` join `rs30`<br>`rs73.rs22 = 'POL026'` | Rekam jantung / Treadmill / Ekokardiografi. |
| **EEG (Elektroensefalografi)** | `rs73` join `rs30`<br>`rs73.rs22 = 'POL024'` | Rekam aktivitas listrik otak. |
| **Penggunaan Darah (UTD/BDRS)**| `rs231`<br>`rs231.rs14 <> 'POL014'` | `SUM(rs12 + rs13 + biayalain2)`. Biaya kantong darah & uji saring. |
| **Penunjang Lainnya** | `rs19` (`penunjang_lain = '1'`) join `rs73` | Biaya unit penunjang penunjang kustom lainnya. |

---

### 12. Biaya Farmasi & Obat Ranap
* **Tabel Terkait**: `rs38` (Resep R/ Bebas), `rs39`/`rs40` (Racikan), `rs62`, `rs63`/`rs64`.
* **Kriteria Filter**: `rs24 <> 'IRD'` (Bukan obat IGD), `rs25 = 'CENTRAL'` (Apotik Sentral Rawat Inap), `lunas <> '1'`.
* **Formula Perhitungan**:
  ```sql
  SELECT ROUND(SUM(subtotalx), 0) AS subtotal FROM (
      -- Obat Non Racikan
      SELECT SUM((ROUND(rs38.rs6, 0) * rs38.rs8) + rs38.rs10) AS subtotalx 
      FROM rs38, rs32, rs9 
      WHERE rs32.rs1 = rs38.rs4 AND rs38.rs1 = :noreg AND rs38.lunas <> '1' 
        AND rs9.rs1 = rs38.rs21 AND rs38.rs24 <> 'IRD'
        /* AND rs38.rs21 = :flagsistembayar */
      UNION ALL
      -- Obat Racikan (rs39 & rs40)
      SELECT SUM(ROUND(rs40.rs7, 0) * rs40.rs5) AS subtotalx
      FROM rs39, rs40, rs32, rs9 
      WHERE rs39.rs2 = rs40.rs2 AND rs32.rs1 = rs40.rs4 AND rs39.rs1 = :noreg AND rs39.lunas <> '1' 
        AND rs9.rs1 = rs39.rs16 AND rs39.rs18 <> 'IRD'
        /* AND rs39.rs16 = :flagsistembayar */
      UNION ALL
      -- Obat Rawat Inap Lanjutan (rs62 & rs63/rs64)
      SELECT SUM((ROUND(rs62.rs6, 0) * rs62.rs8) + rs62.rs10) AS subtotalx 
      FROM rs62, rs32, rs9 
      WHERE rs32.rs1 = rs62.rs4 AND rs62.rs1 = :noreg AND rs62.lunas <> '1' 
        AND rs9.rs1 = rs62.rs21 AND rs62.rs24 <> 'IRD'
        /* AND rs62.rs21 = :flagsistembayar */
      UNION ALL
      SELECT SUM(ROUND(rs64.rs7, 0) * rs64.rs5) AS subtotalx
      FROM rs63, rs64, rs32, rs9 
      WHERE rs63.rs2 = rs64.rs2 AND rs32.rs1 = rs64.rs4 AND rs63.rs1 = :noreg AND rs63.lunas <> '1' 
        AND rs9.rs1 = rs63.rs16 AND rs63.rs18 <> 'IRD'
        /* AND rs63.rs16 = :flagsistembayar */
  ) AS vx;
  ```

---

### 13. Operasi Cito (OK Ranap)
* **Tabel Terkait**:
  1. `rs54` join `rs53` dengan `rs15 = 'POL014'` (Operasi cito di IRD sebelum transfer ranap).
  2. `rs73` join `rs30` dengan `rs22 = 'OPERASI2'` (Operasi cito tambahan).

---

### 14. Biaya Farmasi / Obat IRD
* **Tabel Terkait**: `rs38`, `rs39`, `rs62`, `rs63`.
* **Kriteria Filter**: `rs24 = 'IRD'` dan `rs25 IN ('CENTRAL', 'IGD')`.

---

### 15. Biaya Ambulan
* **Tabel Terkait**: `rs35` (`rs3 = 'AB#'`).
* **Formula**: `SUM(rs7 + rs11)`.

---

### 16. IRD (Instalasi Rawat Darurat / Admisi Masuk Gawat Darurat)
* **Komponen yang Dihitung**:
  1. **Karcis / Admisi IRD (`rs35x.rs3 = 'A2#'`)**:
     ```sql
     SELECT rs35x.rs7 AS subtotal 
     FROM rs35x 
     JOIN rs17 ON rs35x.rs1 = rs17.rs1 
     WHERE rs35x.rs3 = 'A2#' AND rs35x.rs1 = :noreg
       /* AND rs17.rs14 = :flagsistembayar */
     ```
  2. **Tindakan Medis IRD**: `rs73.rs22 = 'POL014'`.
  3. **Laboratorium IRD**: `rs51.rs23 = 'POL014'`.
  4. **Radiologi IRD**: `rs48.rs26 = 'POL014'`.
  5. **Kamar Operasi IRD**: `rs226.rs15 = 'POL014'`.
  6. **Darah IRD**: `rs231.rs14 = 'POL014'`.
  7. **Materai Tagihan IRD (Jasa Raharja)**: `rsjr.rs7 = 'IRD'`.

---

### 17. Retur Obat & Formula Kurang Bayar
* **Retur Obat Farmasi (`rs87` & `rs88`)**:
  ```sql
  SELECT ROUND(SUM(rs88.rs3 * rs88.rs4), 0) AS subtotal 
  FROM rs87
  JOIN rs88 ON rs87.rs1 = rs88.rs1 
  JOIN rs32 ON rs32.rs1 = rs88.rs2 
  WHERE rs87.rs7 = :noreg
    /* AND rs87.rs9 = :flagsistembayar */
  ```
* **Formula Perhitungan Kurang Bayar (Net Billing)**:
  $$\text{Kurang Bayar} = \text{Grand Total Billing} - \text{Retur Obat} - \sum(\text{Pelunasan/Potongan})$$

---

## 4. INTEGRASI CASEMIX AI & EARLY WARNING SYSTEM (EWS)

### Parameter Indikator EWS Casemix
Untuk mengaktifkan monitoring otomatis terhadap klaim INA-CBGs dan asuransi, Casemix AI Assistant dapat menggunakan parameter ambang batas (threshold) berikut:

1. **Rasio Biaya Riil terhadap Tarif INA-CBGs (Cost-to-Tariff Ratio / CTR)**:
   $$\text{CTR} = \frac{\text{Grand Total Riil (SIMRS)}}{\text{Tarif Grouper INA-CBGs}} \times 100\%$$
   * **🟢 Hijau (Aman)**: $\text{CTR} \le 75\%$ (Rumah sakit menghasilkan margin positif).
   * **🟡 Kuning (Peringatan)**: $75\% < \text{CTR} \le 90\%$ (Biaya mendekati plafon klaim).
   * **🔴 Merah (Defisit / Overclaim)**: $\text{CTR} > 100\%$ (Tagihan riil melampaui klaim BPJS, perlu audit rekam medik / audit klinis DPJP).

2. **Trigger Khusus Pembengkakan Biaya (Cost Drivers)**:
   * **Proporsi Farmasi & Alkes**: Jika `(Biaya Farmasi Ranap + Farmasi IRD) / Total Tagihan > 40%`.
   * **Biaya Penggunaan Darah**: Terjadi transfusi darah berulang (`rs231` $> 4$ kantong tanpa diagnosis anemia berat primer).
   * **Hari Rawat (LOS / Length of Stay)**: Hari rawat melebihi rata-rata ALOS nasional untuk kode CBG terkait.
   * **Intensive Care Unit (ICU / ICCU / PICU / NICU)**: Pemakaian ventilator dan ruangan intensif dengan tarif akomodasi tinggi.

3. **Coordination of Benefit (COB) & Multijaminan**:
   * Pasien kasus laka lantas yang dijamin **Jasa Raharja** (maksimal plafon Rp. 20.000.000) dan sisanya dijamin **BPJS Kesehatan/Ketenagakerjaan**.
   * Casemix AI Assistant dapat langsung membandingkan:
     - `details['ALL__AR32']['grand_total']` vs Plafon Jasa Raharja.
     - `details['ALL__BPJS01']['grand_total']` vs Tarif INA-CBGs BPJS.

---

## 5. LOKASI KODE & IMPLEMENTASI BACKEND

* **Backend Controller**: [`BillingController.php`](file:///Users/it/Developer/www/api.laborat/app/Http/Controllers/Api/Simrs/Ranap/Pelayanan/BillingController.php)
  - `getRekapBilling($request)`: Endpoint API utama yang merespons `global`, `per_ruangan`, dan matriks `details[ruangan__sistembayar]`.
  - `calculateBillingGlobal(...)`: Implementasi `billing.php`.
  - `calculateBillingBySistemBayar(...)`: Implementasi `billingbysistembayar.php`.
  - `calculateBillingPerRuangan(...)`: Implementasi `billingbyruangan.php` & `billingbyruanganbysistembayar.php`.
  - `getFakturDetail($request)`: Rincian baris per baris transaksi pasien (`fakturdetail.php`).
* **Frontend UI**: [`IndexPage.vue`](file:///Users/it/Developer/quasar/simrs-v3/src/pages/simrs/ranap/layanan/billing/rekap/IndexPage.vue)
  - Dropdown interaktif: *Pilih Ruangan* (hanya ruangan singgah) dan *Pilih Sistem Bayar* (hanya penjamin pasien).
  - Tampilan Kertas Cetak A4 identik 100% dengan SIMRS lama.
