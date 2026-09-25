# SPESIFIKASI METADATA & KAMUS QUERY RESUME MEDIS (MEDICAL DISCHARGE SUMMARY)
**Sistem SIMRS Lama (Legacy SIMRS dr. Mohamad Saleh)**

Dokumen ini mendokumentasikan secara rinci dan komprehensif seluruh struktur data, tabel database, query SQL, aturan bisnis (business rules), kondisi fallback, dan penanganan data klinis yang digunakan pada modul Resume Medis Rawat Inap SIMRS Lama.

- **URL Asal**: `http://192.168.11.1/ranap/pelayanan/resume.php?cmd=lihat&noreg={noreg}&flagbill=lihat`
- **File Sumber**:
  1. `/var/www/html/ranap/pelayanan/headeroriginil.php` (Header identitas pasien & admisi)
  2. `/var/www/html/ranap/pelayanan/resume.php` (12 Bagian Resume Medis & Tanda Tangan QR DPJP)
- **Koneksi Database Terkait**:
  - `rs` (Database SIMRS Utama)
  - `farmasi` (Database Manajemen Obat & Apotek)
  - `kepegx` (Database Kepegawaian & Profesi Nakes)

---

## 1. RINGKASAN SELURUH TABEL YANG DIGUNAKAN

| No | Nama Tabel | Database | Fungsi / Keterangan Data Klinis Pasien |
|:---|:---|:---:|:---|
| 1 | `rs15` | `rs` | Master Pasien (Nama, No RM, Tgl Lahir, Kelamin, Alamat, Agama, Suku Bangsa) |
| 2 | `rs23` | `rs` | Registrasi / Kunjungan Rawat Inap Pasien (Tgl Masuk, Tgl Keluar, DPJP, Cara Keluar, Sebab Kematian, dll.) |
| 3 | `rs23_sambung` | `rs` | Data Tambahan KRS Ranap (Keterangan Tindak Lanjut Lanjutan) |
| 4 | `rs24` | `rs` | Master Ruangan / Kamar Rawat Inap & Kelas Pelayanan |
| 5 | `rs9` | `rs` | Master Sistem Bayar (BPJS, Umum, Jasa Raharja, dll.) |
| 6 | `rs17` | `rs` | Registrasi Kunjungan Gawat Darurat (IGD) |
| 7 | `rs21` | `rs` | Master Tenaga Medis / Dokter DPJP |
| 8 | `tflag_covid` | `rs` | Status Skrining / Penandaan Pasien Covid-19 |
| 9 | `anamnesis_kebidanan_igd` | `rs` | Anamnesis Khusus Kebidanan IGD (Keluhan Utama Bersalin/Kandungan) |
| 10 | `rs209` | `rs` | Catatan Anamnesis (Keluhan Utama, RPS, RPD, Alergi, Riwayat Pekerjaan Zat Berbahaya) |
| 11 | `rs209_nyeri` | `rs` | Pengkajian Skala Nyeri Ranap (Wong Baker Faces, CPOT, NRS, dll.) |
| 12 | `rs250` | `rs` | Tanda-tanda Vital IGD (Tekanan Darah, Pernapasan, Nadi, Suhu, BB, TB, SpO2, GCS) |
| 13 | `rs251` | `rs` | Data Tambahan Triage & Obstetri IGD |
| 14 | `rs253` | `rs` | Pemeriksaan Fisik Anatomi Tubuh (Kepala, Leher, Dada, Punggung, Perut, Tangan, Kaki, Neuro, Genitalia) |
| 15 | `rs253_sambung` | `rs` | Tanda-tanda Vital Lanjutan Pemeriksaan Fisik Ranap |
| 16 | `rs51` | `rs` | Transaksi Pemeriksaan Laboratorium Pasien |
| 17 | `rs49` | `rs` | Master Parameter / Item Pemeriksaan Laboratorium |
| 18 | `rs151` | `rs` | Hasil Ekspertise / Pembacaan Radiologi |
| 19 | `rs268` | `rs` | Pemeriksaan Elektrokardiografi (EKG) |
| 20 | `rs303` | `rs` | Pemeriksaan Echocardiography (USG Jantung / ECG) |
| 21 | `rs265` | `rs` | Catatan Pelayanan Hemodialisa (Cuci Darah) |
| 22 | `memodiagnosadokter` | `rs` | Diagnosis Klinis Bebas dari Dokter DPJP Rawat Inap |
| 23 | `rs101` | `rs` | Koding Diagnosis Pasien ICD-10 (Primer, Sekunder, Tambahan, Komplikasi) |
| 24 | `rs99x` | `rs` | Master Kamus Kode & Deskripsi ICD-10 Internal RS |
| 25 | `codesystemidrg` | `rs` | Master Standar Kode ICD-10 INA-CBG / E-Klaim |
| 26 | `prosedur_klaimx` | `rs` | Tindakan Prosedur Medis ICD-9-CM (Operatif & Non Operatif) |
| 27 | `keterangantindakan` | `rs` | Keterangan Narasi Tambahan Mengenai Tindakan Pasien |
| 28 | `rs213` | `rs` | Resume Keadaan Keluar Pasien Waktu KRS (Pilihan Utama) |
| 29 | `cppts` | `rs` | Catatan Perkembangan Pasien Terintegrasi (CPPT - Fallback KRS jika rs213 kosong) |
| 30 | `rs27` | `rs` | Master Kategori Prognosis Medis Pasien |
| 31 | `kepegx.pegawai` | `kepegx` | Master Identitas Pegawai & Profesi Tenaga Medis (`kdgroupnakes`) |
| 32 | `farmasi.resep_keluar_h` | `farmasi` | Header Resep Rawat Inap & Pulang |
| 33 | `farmasi.resep_keluar_r` | `farmasi` | Rincian Item Resep Obat Non Racikan |
| 34 | `farmasi.resep_keluar_racikan_r`| `farmasi` | Rincian Item Resep Obat Racikan |
| 35 | `farmasi.retur_penjualan_r` | `farmasi` | Rincian Retur Obat Pasien |
| 36 | `farmasi.new_masterobat` | `farmasi` | Master Data Obat Farmasi |
| 37 | `nurse_notes` | `rs` | Catatan Khusus Perawat (Pemberian Obat Non Resep via JSON) |

---

## 2. DETAIL QUERY, STRUKTUR, DAN ATURAN LOGIKA PER BAGIAN

### HEADER: Identitas Pasien & Riwayat Admisi
File: `headeroriginil.php`

#### Query Utama:
```sql
SELECT 
    tanggalmasuk, noreg, norm, nama, alamat, kelamin,
    IF(thn < 1, 
        IF(bln < 1, CONCAT(hari, ' hari'), CONCAT(bln, ' bln')), 
        CONCAT(thn, ' thn')
    ) AS umur,
    ruang_poli, kelas, kd_ruangpoli, tglkeluar, kodedokter, sbayar, sistembayar, suku, agama 
FROM (
    SELECT DISTINCT 
        rs23.rs1 AS noreg,
        rs23.rs2 AS norm,
        IF(rs15.rs16 = '1900-01-01', 
            FLOOR((DATEDIFF(rs23.rs3, '1970-01-01') / 365)), 
            FLOOR((DATEDIFF(rs23.rs3, rs15.rs16) / 365))
        ) AS thn,
        IF(rs15.rs16 = '1900-01-01', 
            FLOOR((DATEDIFF(rs23.rs3, '1970-01-01') - (FLOOR((DATEDIFF(rs23.rs3, '1970-01-01') / 365)) * 365)) / 30), 
            FLOOR((DATEDIFF(rs23.rs3, rs15.rs16) - (FLOOR((DATEDIFF(rs23.rs3, rs15.rs16) / 365)) * 365)) / 30)
        ) AS bln,
        IF(rs15.rs16 = '1900-01-01', 
            (DATEDIFF(rs23.rs3, '1970-01-01') - ((FLOOR((DATEDIFF(rs23.rs3, '1970-01-01') / 365)) * 365) + (FLOOR((DATEDIFF(rs23.rs3, '1970-01-01') - (FLOOR((DATEDIFF(rs23.rs3, '1970-01-01') / 365)) * 365)) / 30) * 30))),
            (DATEDIFF(rs23.rs3, rs15.rs16) - ((FLOOR((DATEDIFF(rs23.rs3, rs15.rs16) / 365)) * 365) + (FLOOR((DATEDIFF(rs23.rs3, rs15.rs16) - (FLOOR((DATEDIFF(rs23.rs3, rs15.rs16) / 365)) * 365)) / 30) * 30)))
        ) AS hari,
        rs23.rs19 AS kd_akun,
        rs15.rs2 AS nama,
        rs15.rs3 AS sapaan,
        rs15.rs4 AS alamat,
        rs15.rs5 AS kelurahan,
        rs15.rs6 AS kecamatan,
        rs15.rs7 AS rt,
        rs15.rs8 AS rw,
        rs15.rs10 AS propinsi,
        rs15.rs11 AS kabupaten,
        rs15.rs16 AS tgllahir,
        rs15.rs17 AS kelamin,
        rs15.rs36 AS normlama,
        rs15.rs39 AS suku,
        rs15.rs37 AS tmplahir,
        rs23.rs3 AS tanggalmasuk,
        rs23.rs11 AS penanggungjawab,
        rs23.rs13 AS kodeasalrujukan,
        rs15.rs22 AS agama,
        rs23.rs20 AS asalpendaftaran,
        rs23.rs16 AS namaperujuk,
        rs23.rs5 AS koderuang_poli,
        rs24.rs2 AS ruang_poli,
        rs23.rs30 AS userid,
        rs23.rs28 AS status,
        rs9.rs2 AS sistembayar,
        'Ranap' AS flag,
        IF(rs23.rs4 = '0000-00-00 00:00:00', 
            DATEDIFF(CURDATE(), rs23.rs3), 
            DATEDIFF(rs23.rs4, rs23.rs3)
        ) AS lama,
        rs24.rs3 AS kelas,
        rs24.rs4 AS kd_ruangpoli,
        rs23.rs4 AS tglkeluar,
        rs23.rs10 AS kodedokter,
        rs9.rs9 AS sbayar
    FROM rs15, rs23, rs24, rs9 
    WHERE rs15.rs1 = rs23.rs2 
      AND rs23.rs5 = rs24.rs1 
      AND rs9.rs1 = rs23.rs19 
      AND rs23.rs1 = :noreg
) AS v_15_17;
```

#### Query Pendukung Header:
1. **Dokter DPJP**:
   ```sql
   SELECT rs21.rs2 AS dokter FROM rs21 WHERE rs21.rs1 = :kodedokter;
   ```
2. **Status Covid-19**:
   ```sql
   SELECT flagcovid 
   FROM tflag_covid 
   WHERE noreg = :noreg 
     AND stat = 'MASUK' 
     AND ruang <> 'POL014' 
   ORDER BY id DESC LIMIT 1;
   ```
3. **Tanggal Masuk IGD**:
   ```sql
   SELECT rs3 FROM rs17 WHERE rs1 = :noreg;
   ```
   *Aturan*: Jika pasien masuk lewat IGD (`rs17` ada), baris "Tanggal Masuk IGD" ditampilkan di header.

---

### BAGIAN 1: Alasan Pasien Dirawat IGD
File: `resume.php` (baris 24-52)

#### Aturan & Alur Penentuan:
1. **Prioritas 1**: Cek apakah pasien memiliki catatan kebidanan IGD di tabel `anamnesis_kebidanan_igd`.
   ```sql
   SELECT keluhanutama FROM anamnesis_kebidanan_igd WHERE noreg = :noreg;
   ```
2. **Prioritas 2 (Fallback)**: Jika tidak ada di tabel kebidanan, ambil keluhan utama dari dokter IGD di tabel `rs209`:
   ```sql
   SELECT rs209.rs4 AS keluhanutama 
   FROM rs209 
   LEFT JOIN kepegx.pegawai ON kepegx.pegawai.kdpegsimrs = rs209.user 
   WHERE rs209.rs1 = :noreg 
     AND kepegx.pegawai.kdgroupnakes = '1' 
     AND (rs209.kdruang = 'POL014' OR rs209.kdruang = '');
   ```
   *Aturan Penting*:
   - `kdgroupnakes = '1'` menandakan tenaga medis adalah **Dokter**.
   - `kdruang = 'POL014'` adalah kode poli/ruang **IGD (Instalasi Gawat Darurat)**.

---

### BAGIAN 2: Anamnese Awal IGD
File: `resume.php` (baris 54-95)

#### Aturan & Query:
1. **Riwayat Klinis Dokter (`kdgroupnakes = '1'`):**
   ```sql
   SELECT 
       riwayatpenyakitsekarang,
       riwayatpenyakit,
       riwayatpengobatan,
       riwayatpenyakitkeluarga,
       riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya,
       riwayatalergi,
       keteranganalergi,
       scorenyeri,
       keteranganscorenyeri
   FROM rs209
   LEFT JOIN kepegx.pegawai ON kepegx.pegawai.kdpegsimrs = rs209.user 
   WHERE rs209.rs1 = :noreg 
     AND kepegx.pegawai.kdgroupnakes = '1' 
     AND (rs209.kdruang = 'POL014' OR rs209.kdruang = '');
   ```
2. **Skor Nyeri Perawat IGD (`kdgroupnakes = '2'`):**
   ```sql
   SELECT scorenyeri 
   FROM rs209
   LEFT JOIN kepegx.pegawai ON kepegx.pegawai.kdpegsimrs = rs209.user 
   WHERE rs209.rs1 = :noreg 
     AND kepegx.pegawai.kdgroupnakes = '2' 
     AND (rs209.kdruang = 'POL014' OR rs209.kdruang = '')
   LIMIT 1;
   ```
   *Aturan*: Pada tampilan resume, nilai `scorenyeri` yang dicetak di baris "Skore Nyeri" diambil khusus dari input perawat (`kdgroupnakes = '2'`).

---

### BAGIAN 3: Pemeriksaan Fisik IGD
File: `resume.php` (baris 97-146)

#### Aturan & Query:
1. **Pemeriksaan Organ Anatomi Tubuh (Dokter IGD):**
   ```sql
   SELECT rs253.* 
   FROM rs253 
   LEFT JOIN kepegx.pegawai ON kepegx.pegawai.kdpegsimrs = rs253.user 
   WHERE rs253.rs1 = :noreg 
     AND kepegx.pegawai.kdgroupnakes = '1' 
     AND rs253.kdruang = 'POL014';
   ```
   *Pemetaan Kolom Anatomi*:
   - `rs5` : Bagian Kepala
   - `rs6` : Bagian Leher
   - `rs7` : Bagian Dada
   - `rs8` : Bagian Punggung
   - `rs9` : Bagian Perut
   - `rs10`: Tangan
   - `rs11`: Kaki
   - `rs12`: Status Neurologis
   - `rs13`: Bagian Genetalia
   *(Catatan: Bagian organ yang kosong/blank tidak dicetak)*

2. **Tanda-tanda Vital IGD:**
   ```sql
   SELECT 
       rs250.*,
       rs250.rs8 AS sax,
       rs250.rs9 AS srec,
       rs250.rs10 AS pernapasan,
       rs250.rs11 AS nadi,
       rs250.rs13 AS bb,
       rs250.rs21 AS tinggi,
       rs251.* 
   FROM rs250 
   LEFT JOIN rs251 ON rs250.rs1 = rs251.rs1 
   WHERE rs250.rs1 = :noreg 
     AND rs250.rs5 = 'POL014';
   ```
   *Aturan Logika Nilai*:
   - **Tensi**: Jika `rs250.rs12` kosong atau `NULL`, gunakan format `sistole / diastole` (contoh: `120/80`). Jika `rs250.rs12` terisi, gunakan nilai `rs250.rs12`.
   - **GCS**: Format `rs14 / rs15 / rs16` (Eye / Motorik / Verbal).
   - **Vital Sign Lain**: `sax` (Suhu Axila °C), `srec` (Suhu Rektal °C), `pernapasan` (/menit), `nadi` (x/menit), `bb` (Kg), `tinggi` (Cm), `spo2` (%).

---

### BAGIAN 4: Anamnese Rawat Inap
File: `resume.php` (baris 148-193)

#### Aturan & Query:
1. **Skor Nyeri Ranap (`rs209_nyeri`):**
   ```sql
   SELECT skor, keluhan FROM rs209_nyeri WHERE noreg = :noreg ORDER BY id ASC LIMIT 1;
   ```
   *Contoh hasil*: `2 Nyeri Ringan`.

2. **Anamnese Masuk Ranap oleh Dokter DPJP:**
   ```sql
   SELECT * 
   FROM rs209 
   LEFT JOIN kepegx.pegawai ON kepegx.pegawai.kdpegsimrs = rs209.user 
   WHERE rs209.rs1 = :noreg 
     AND rs209.kdruang <> 'POL014' 
     AND rs209.awal = '1' 
     AND kepegx.pegawai.kdgroupnakes = '1';
   ```
   *Aturan*:
   - `kdruang <> 'POL014'` : Khusus rawat inap (bukan IGD).
   - `awal = '1'` : Merupakan asesmen awal masuk rawat inap.
   - `kdgroupnakes = '1'` : Diinput oleh Dokter.
   - Field yang dicetak:
     - Keluhan Utama (`rs4`)
     - Riwayat Penyakit Sekarang (`riwayatpenyakitsekarang`)
     - Riwayat Penyakit Dahulu (`riwayatpenyakit`)
     - Riwayat Pengobatan (`riwayatpengobatan`)
     - Riwayat Penyakit Keluarga (`riwayatpenyakitkeluarga`)
     - Hubungan Zat Berbahaya (`riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya`)
     - Alergi (`riwayatalergi`) & Keterangan Alergi (`keteranganalergi`)
     - Skor Nyeri (`skor` + `keluhan` dari `rs209_nyeri`)

---

### BAGIAN 5: Pemeriksaan Fisik Rawat Inap
File: `resume.php` (baris 195-247)

#### Aturan & Query:
```sql
SELECT 
    rs253.*,
    rs253_sambung.* 
FROM rs253 
LEFT JOIN rs253_sambung ON rs253.id = rs253_sambung.rs253_id 
LEFT JOIN kepegx.pegawai ON kepegx.pegawai.kdpegsimrs = rs253.user 
WHERE rs253.rs1 = :noreg 
  AND rs253.kdruang <> 'POL014' 
  AND rs253.awal = '1' 
  AND kepegx.pegawai.kdgroupnakes = '1';
```
*Aturan*:
- Pemeriksaan anatomi tubuh dari `rs253` (`rs5` sampai `rs13`).
- Tanda vital diambil dari tabel gabungan `rs253` & `rs253_sambung`: `sax`, `srec`, `pernapasan`, `nadi`, `tensi`, `beratbadan`, `tinggibadan`, `spo`, `suhu`.
- Baris fisik/vital yang bernilai string kosong `""` tidak dicetak ke resume.

---

### BAGIAN 6: Pemeriksaan Penunjang
File: `resume.php` (baris 249-382)

Pemeriksaan penunjang terbagi menjadi 5 sub-bagian klinis:

#### 1. Laboratorium (Khusus yang memiliki Nilai Kritis / Tidak Kosong)
```sql
SELECT 
    rs51.rs1 AS noreg,
    DATE(rs51.rs3) AS tgl,
    rs49.rs2 AS pemeriksaan,
    rs51.rs21 AS hasil,
    rs51.rs27 AS nilaikrotos 
FROM rs51, rs49 
WHERE rs51.rs1 = :noreg 
  AND rs49.rs1 = rs51.rs4 
  AND rs51.rs27 <> '';
```
*Aturan Penting*:
- Resume medis lama **hanya menampilkan hasil laboratorium yang nilai kritisnya tidak kosong** (`rs51.rs27 <> ''`), misalnya nilai High (`H`), Low (`L`), atau catatan kritis dokter patologi.
- Format cetak: `{pemeriksaan} : {hasil} ({nilaikritis}),`.

#### 2. Radiologi
```sql
SELECT DATE(rs2) AS tgl, rs3 AS hasil FROM rs151 WHERE rs1 = :noreg;
```
*Aturan*: Menampilkan hasil ekspertise pembacaan radiolog (`rs3`).

#### 3. Elektrokardiografi (EKG)
```sql
SELECT * FROM rs268 WHERE rs1 = :noreg;
```
*Aturan*: Menampilkan Klasifikasi (`rs4`) dan Impresi (`rs5`).

#### 4. Echocardiography (ECG / USG Jantung)
```sql
SELECT * FROM rs303 WHERE rs1 = :noreg;
```
*Aturan*: Menampilkan struktur anatomi & fungsi jantung:
- A. Dimensi (Katup: `rs4`, Ruang Jantung: `rs5`)
- B. Color Doppler (Sistolik: `rs6`, Diastolik: `rs7`, Katup: `rs8`)
- C. Analisa Segmental (Kinetik: `rs12`)
- D. Lain-lain (`rs9`)
- E. Kesimpulan (`rs10`)

#### 5. Hemodialisa (Cuci Darah)
```sql
SELECT * FROM rs265 WHERE rs1 = :noreg;
```
*Aturan*: Menampilkan data asesmen HD:
- Alasan Kunjungan (`rs4`), Psikososial (`rs5`), Hub. Keluarga (`rs6`), Psikologis (`rs7`).
- Tanda vital pra-HD: TD (`rs9`), Nadi (`rs10`), Suhu (`rs11`), TB (`rs12`), BB (`rs13`).
- Skrining Nutrisi: Penurunan BB 6 bulan (`rs14`), Penurunan asupan nafsu makan (`rs15`).

---

### BAGIAN 7: Diagnosis
File: `resume.php` (baris 384-446)

#### 1. Diagnosis Bebas DPJP Rawat Inap:
```sql
SELECT * 
FROM memodiagnosadokter 
WHERE noreg = :noreg 
  AND (kdruang <> 'POL014' OR kdruang IS NULL);
```
*Aturan*: Khusus diagnosis DPJP di rawat inap (bukan diagnosis IGD).

#### 2. Diagnosis Utama (ICD-10 Primer):
```sql
SELECT 
    rs101.id,
    rs101.rs3 AS icd_sekunder,
    COALESCE(rs99x.rs4, codesystemidrg.description) AS diagnosa_sekunder 
FROM rs101 
LEFT JOIN codesystemidrg ON codesystemidrg.code = rs101.rs3 
LEFT JOIN rs99x ON rs99x.rs1 = rs101.rs3 
WHERE rs101.rs1 = :noreg 
  AND rs101.rs4 = 'Primer';
```
*Aturan*:
- Menampilkan kode ICD-10 beserta nama deskripsinya.
- Deskripsi diprioritaskan dari `rs99x.rs4` (katalog lokal RS), jika null menggunakan `codesystemidrg.description` (standar INA-CBG).

#### 3. Diagnosis Sekunder / Tambahan / Komplikasi (ICD-10):
```sql
SELECT 
    rs101.id,
    rs101.rs3 AS icd_sekunder,
    COALESCE(rs99x.rs4, codesystemidrg.description) AS diagnosa_sekunder 
FROM rs101 
LEFT JOIN codesystemidrg ON codesystemidrg.code = rs101.rs3 
LEFT JOIN rs99x ON rs99x.rs1 = rs101.rs3 
WHERE rs101.rs1 = :noreg 
  AND rs101.rs4 IN ('Tambahan', 'Sekunder', 'Komplikasi');
```

---

### BAGIAN 8: Pengobatan
File: `resume.php` (baris 463-539)

Pengobatan menggabungkan **Database Farmasi** dan **Catatan Perawat (Obat Non Resep)**:

#### 1. Database Farmasi (`farmasi`):
```sql
SELECT nama_obat 
FROM farmasi.new_masterobat 
WHERE kd_obat IN (
    -- Resep Rawat Inap Non Racikan (Flag 3)
    SELECT DISTINCT rr.kdobat 
    FROM farmasi.resep_keluar_h rh 
    JOIN farmasi.resep_keluar_r rr ON rr.noresep = rh.noresep 
    WHERE rh.flag = '3' AND rh.noreg = :noreg

    UNION

    -- Resep Rawat Inap Racikan (Flag 3)
    SELECT DISTINCT rcr.kdobat 
    FROM farmasi.resep_keluar_h rh 
    JOIN farmasi.resep_keluar_racikan_r rcr ON rcr.noresep = rh.noresep 
    WHERE rh.flag = '3' AND rh.noreg = :noreg

    UNION

    -- Resep Pulang (Flag 4) dikurangi Obat yang telah Diretur Penuh
    SELECT DISTINCT rr.kdobat 
    FROM farmasi.resep_keluar_h rh 
    JOIN farmasi.resep_keluar_r rr ON rr.noresep = rh.noresep 
    LEFT JOIN farmasi.retur_penjualan_r rpr 
        ON rpr.noresep = rr.noresep AND rpr.kdobat = rr.kdobat 
    WHERE rh.flag = '4' 
      AND rh.noreg = :noreg 
      AND (rpr.jumlah_retur IS NULL OR rpr.jumlah_retur < rr.jumlah)
);
```
*Aturan Farmasi*:
- `flag = '3'` : Resep pelayanan rawat inap.
- `flag = '4'` : Resep obat pulang (KRS).
- Obat flag 4 yang diretur penuh (`jumlah_retur >= jumlah`) dieliminasi.

#### 2. Catatan Perawat Obat Non-Resep:
```sql
SELECT reseps 
FROM rs.nurse_notes 
WHERE noreg = :noreg 
  AND reseps LIKE '%Obat Non Resep%';
```
*Aturan Parsing JSON*:
- Kolom `reseps` berisi JSON array.
- Dilakukan looping JSON: jika `uraian === "Obat Non Resep"`, tampilkan atribut `nama_obat`.

---

### BAGIAN 9: Tindakan (ICD-9-CM & Keterangan)
File: `resume.php` (baris 541-600)

#### 1. Tindakan ICD-9-CM (Operatif & Non Operatif):
```sql
SELECT * 
FROM prosedur_klaimx 
WHERE noreg = :noreg 
  AND jenisicd IN ('Operatif', 'Non Operatif') 
ORDER BY jenisicd;
```
*Aturan Pengelompokan Data*:
- Dikelompokkan menjadi 2 array:
  - **Operatif**: `{prosedur} ({kd_prosedur})`
  - **Non Operatif**: `{prosedur} ({kd_prosedur})`
- Di-implode menggunakan pemisah tanda pipa `" | "`.

#### 2. Keterangan Tindakan Tambahan:
```sql
SELECT keterangan FROM keterangantindakan WHERE noreg = :noreg;
```

---

### BAGIAN 10: Keadaan Waktu KRS
File: `resume.php` (baris 602-640)

Bagian ini memiliki **mekanisme fallback ganda**:

#### Prioritas 1: Dari Tabel `rs213` (Resume Keluar Pasien Khusus)
```sql
SELECT * FROM rs213 WHERE rs1 = :noreg ORDER BY rs3 DESC LIMIT 1;
```
*Jika ditemukan (`jmlpq > 0`)*:
- Tampilkan: `- {rs4} <br> - {rs5}`.

#### Prioritas 2 (Fallback): Dari Tabel `cppts` (Catatan Terakhir Perawat/Bidan)
Jika `rs213` kosong, sistem mengambil asesmen & instruksi terakhir dari perawat atau bidan di CPPT:
```sql
SELECT 
    cppts.*,
    rs209.rs4 AS keluhanutama,
    rs253.*,
    rs253_sambung.* 
FROM cppts 
LEFT JOIN rs209 ON rs209.id = cppts.rs209_id 
LEFT JOIN rs253 ON rs253.id = cppts.rs253_id 
LEFT JOIN rs253_sambung ON rs253.id = rs253_sambung.rs253_id 
WHERE cppts.noreg = :noreg 
  AND (cppts.nakes = 2 OR cppts.nakes = 3) 
ORDER BY cppts.id DESC 
LIMIT 1;
```
*Format Cetak Fallback*:
- Keluhan: `- {keluhanutama}`
- Vital Sign: `- Nadi : {nadi} | RR : {pernapasan} | Sis/Dias : {tensi} | Spo2 : {spo} | Suhu : {suhu} | {o_sambung}`
- Instruksi: `- {instruksi}`

---

### BAGIAN 11: Prognosis & Sebab Meninggal
File: `resume.php` (baris 642-660)

#### 1. Prognosis Pasien:
```sql
SELECT rs27.rs2 AS prognosa 
FROM rs23 
LEFT JOIN rs27 ON rs27.rs1 = rs23.rs24 
WHERE rs23.rs1 = :noreg;
```
*Contoh*: Dubia ad Bonam, Dubia ad Malam, Bonam, Malam.

#### 2. Sebab Meninggal (Jika Pasien Meninggal):
```sql
SELECT rs99x.rs4 AS sebabkematian 
FROM rs23 
JOIN rs99x ON rs23.rs25 = rs99x.rs1 
WHERE rs23.rs1 = :noreg;
```
*Aturan*: Mengambil nama deskripsi penyakit ICD-10 dari `rs99x` berdasarkan kode di `rs23.rs25`.

---

### BAGIAN 12: Tindak Lanjut Pasien
File: `resume.php` (baris 662-675)

```sql
SELECT 
    rs23.rs27 AS ket1,
    rs23_sambung.ket AS ket2 
FROM rs23 
LEFT JOIN rs23_sambung ON rs23.rs1 = rs23_sambung.noreg 
WHERE rs23.rs1 = :noreg;
```
*Aturan*:
- Jika `ket2` (`rs23_sambung.ket`) ada isinya dan bukan null, maka gunakan `ket2`.
- Jika `ket2` kosong, gunakan `ket1` (`rs23.rs27`).

---

### BAGIAN 13: Tanda Tangan & Verifikasi QR Code DPJP
File: `resume.php` (baris 678-739)

#### 1. Tanggal Resume Medis:
```sql
SELECT DATE(rs4) AS tgl FROM rs23 WHERE rs1 = :noreg;
```
*Aturan Bisnis*:
- Tanggal pembuatan resume otomatis dihitung **1 hari setelah tanggal keluar** pasien:
  `$tglx = date('Y-m-d', strtotime('+1 day', strtotime($tgl)))`.
- Format tampilan: `Probolinggo, {tglx}`.

#### 2. Validasi Hak Tanda Tangan Dokter:
```sql
SELECT 
    rs21.rs1 AS id,
    rs21.rs2 AS dokter,
    rs23.rs22 AS flag 
FROM rs23, rs21 
WHERE rs23.rs1 = :noreg 
  AND rs23.rs10 = rs21.rs1;
```
*Aturan*:
- Jika nama dokter (`dokterx`) kosong atau status flag pendaftaran (`flag`) kosong, tanda tangan berupa garis kosong: `( ............... )`.
- Jika terverifikasi, sistem meng-generate QR Code Legalitas Elektronik Rumah Sakit.

#### 3. Spesifikasi Payload QR Code:
- **Format String Plain**:
  `{noreg}|DOKUMEN RESUME|RAWAT INAP|{kodedokter}`
  *Contoh*: `45941/09/2026/X|DOKUMEN RESUME|RAWAT INAP|101`
- **Enkripsi Base64**:
  `$qEncoded = base64_encode(noreg . '|DOKUMEN RESUME|RAWAT INAP|' . petugas)`
- **URL Verifikasi Publik**:
  `https://rsud.probolinggokota.go.id/dokumen-simrs/legalitas/{qEncoded}`
- **Penyimpanan File QR**:
  Disimpan ke folder server: `../../ttd_qr/RANAP/{qEncoded}.png`.

---

## 3. ATURAN INTEGRITAS & KESELAMATAN DATA PASIEN

1. **Prinsip Read-Only Server**:
   - Seluruh query resume medis bersifat **hanya membaca (SELECT)** dan tidak boleh mengubah rekam medis historis pasien.
2. **Isolasi Ruangan IGD vs Bangsal Ranap**:
   - Filter `kdruang = 'POL014'` memastikan data IGD dan filter `kdruang <> 'POL014'` memastikan data Rawat Inap tidak saling tercampur atau menimpa.
3. **Penyaringan Profesi Medis (`kdgroupnakes`)**:
   - `1` = Dokter (DPJP / Dokter Jaga)
   - `2` = Perawat
   - `3` = Bidan
   - Alasan dirawat dan diagnosis dokter wajib berasal dari tenaga medis berlisensi dokter (`kdgroupnakes = '1'`).
4. **Pencegahan Data Blank/Null**:
   - Seluruh pemeriksaan fisik anatomi tubuh (`rs5` sampai `rs13`) dan tanda vital diperiksa `!= ''` sebelum dirender agar hasil resume medis tetap rapi dan tidak memunculkan baris kosong tak berguna.
5. **Akurasi Riwayat Obat & Retur Farmasi**:
   - Resep KRS (flag 4) diverifikasi dengan tabel retur `retur_penjualan_r` agar obat yang sudah dikembalikan oleh pasien tidak tercantum sebagai obat yang dibawa pulang.
