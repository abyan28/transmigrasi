# Rencana Eksekusi — Rombongan B: Anggota Keluarga + Usia + Agama

Ditulis 2026-08-28 sesuai `rules.md` §20b. Sementara, boleh ditimpa.

## Lingkup (dari `notes.md` bagian 6)

- **B1** Field `usia` pada form transmigran, auto hitung dari `tanggal_lahir`,
  bertambah sendiri tiap tahun. Field **Agama** juga.
- **B2** Pendataan anggota keluarga (istri + anak) lewat dynamic form fields
  saat mengisi/menyunting data kepala keluarga.
  - Istri: field mirip suami, minus nomor KK.
  - Anak: Nama Lengkap, NIK, Jenis Kelamin, Tempat Lahir, Tanggal Lahir,
    Agama, "Pendidikan/Kerja" (multi-level: bila pilih Kerja, munculkan
    pendidikan terakhir + pekerjaan + pendapatan per bulan).

## TEMUAN PENTING: ini membalik keputusan berlingkup PRD

`erd.md` §7.4 dan `data-dictionary.md` (§6.1, §6.5, §11.35, dan tabel
`anggota_poktan`) menyatakan **berkali-kali** dan sengaja:

> "sistem tidak mendata anggota keluarga satu per satu (di luar lingkup PRD)"

Keputusan itu menopang tiga hal:
1. `transmigran.jumlah_anggota_keluarga` **disimpan** sebagai angka (bukan
   dihitung) justru karena tidak ada baris anggota keluarga untuk dihitung.
2. `anggota_poktan` (`nama_wakil`, `nik_wakil`, `hubungan_dengan_kk`) dan
   `poktan.ketua` (`hubungan_ketua`) **wajib diketik petugas**, sebab "tidak
   ada relasi yang dapat dibaca".
3. `riwayat_kepala_keluarga.hubungan_pengganti` diketik petugas; identitas
   pengganti tidak ditebak dari daftar anggota.
4. Enum `HubunganKeluarga` "sengaja kasar, tidak dirinci sampai anak kedua".

Rombongan B menambahkan tabel anggota keluarga, sehingga alasan (1) gugur dan
(2)(3) menjadi bisa-dibaca-dari-relasi. Pemilik proyek memintanya secara
eksplisit; ini keputusannya. Yang WAJIB:
- `erd.md` §7.4 + catatan lingkup PRD direvisi dengan jejak bertanggal
  (tidak dihapus, ditandai dibalik 2026-08-28).
- **Blast radius dibatasi putaran ini.** Yang dikerjakan: tabel anggota
  keluarga + form + tampil + `jumlah_anggota_keluarga` jadi turunan.
  DITUNDA (dicatat, bukan dikerjakan): mengubah `anggota_poktan` /
  `poktan.ketua` / `riwayat_kepala_keluarga` agar memilih dari daftar
  anggota. Ketiganya tetap diketik manual sampai putaran tersendiri.

## Rancangan data (Tahap 2 = larik DummyData; skema untuk Tahap 3)

### Tabel baru `anggota_keluarga`
Satu baris per anggota selain kepala keluarga.

| Kolom | Tipe | Null | Ket |
|---|---|---|---|
| `id_anggota_keluarga` | BIGINT UNSIGNED AI | TIDAK | PK |
| `transmigran_id` | BIGINT UNSIGNED | TIDAK | FK, IDX; keluarga yang dinaungi |
| `hubungan` | ENUM | TIDAK | Pasangan (Istri/Suami), Anak, Anak Angkat, Orang Tua, Famili Lain |
| `nama_lengkap` | VARCHAR(255) | TIDAK | huruf kapital otomatis |
| `nik` | CHAR(16) | YA | boleh kosong bagi bayi/balita yang belum punya NIK |
| `jenis_kelamin` | ENUM | TIDAK | Laki-laki / Perempuan |
| `tempat_lahir` | VARCHAR(100) | YA | |
| `tanggal_lahir` | DATE | YA | sumber usia (dihitung, tidak disimpan) |
| `agama` | ENUM | YA | lihat enum Agama |
| `kegiatan` | ENUM | YA | Belum Sekolah / Masih Sekolah / Bekerja / Tidak Bekerja |
| `pendidikan_terakhir` | ENUM | YA | wajib bila kegiatan Bekerja atau Tidak Bekerja atau (Masih Sekolah -> jenjang berjalan) |
| `pekerjaan` | VARCHAR(100) | YA | wajib bila kegiatan Bekerja |
| `pendapatan_per_bulan` | DECIMAL(12,2) | YA | hanya bila kegiatan Bekerja |
| `telepon` | VARCHAR(20) | YA | opsional |
| `keterangan` | VARCHAR(1000) | YA | |

Pasangan (Istri/Suami): pakai kolom yang sama; `kegiatan` juga berlaku
(banyak istri berdagang / bertani), sehingga tidak perlu cabang terpisah -
menyederhanakan "istri mirip suami".

### `transmigran.jumlah_anggota_keluarga` -> turunan
Tidak lagi diisi petugas. Dihitung: 1 (kepala) + jumlah baris
`anggota_keluarga`. Ditampilkan di form sebagai keterangan read-only.
Konsisten dengan `poktan.jumlah_anggota` dan `alsintan` yang sudah turunan.
Kolom `jumlah_anggota_keluarga` di kamus ditandai "diturunkan, tidak diisi".

### `transmigran.agama` -> kolom baru ENUM YA
### Usia -> dihitung dari `tanggal_lahir`, TIDAK ada kolom

## Enum baru

- `App\Enums\Agama`: Islam, Kristen, Katolik, Hindu, Buddha, Konghucu.
  (6 agama yang dilayani Dukcapil. "Penghayat Kepercayaan" = butir konfirmasi.)
- `App\Enums\HubunganAnggotaKeluarga`: Istri, Suami, Anak, Anak Angkat,
  Orang Tua, Famili Lain. (BEDA dari `HubunganKeluarga` yang dipakai
  anggota_poktan/riwayat - itu "kasar" dan tetap.)
- `App\Enums\KegiatanAnggota`: Belum Sekolah, Masih Sekolah, Bekerja,
  Tidak Bekerja.

## Berkas yang disentuh

**Dokumen**
- `agents/data-dictionary.md` - tabel `anggota_keluarga` baru (§6.x), kolom
  `transmigran.agama`, catatan `jumlah_anggota_keluarga` jadi turunan, enum
  §11.x baru
- `agents/erd.md` §7.4 - revisi berjejak, §7.x relasi baru
- `agents/rules.md` §7a - aturan pendataan anggota keluarga + usia dihitung
- `agents/ui-spec.md` §6.x - komponen dynamic repeater bila dibuat komponen

**Kode**
- `app/Enums/{Agama,HubunganAnggotaKeluarga,KegiatanAnggota}.php` baru
- `app/Support/DummyData.php` - `anggotaKeluarga()` baru, `agama` pada baris
  transmigran, `jumlah_anggota_keluarga` dibaca dari cacah pada tampilan
- `app/Providers/ViewServiceProvider.php` - kunci rujukan form transmigran
  (`opsiAgama`, `opsiHubunganAnggota`, `opsiKegiatan`, `saranPekerjaan` sudah ada)
- `routes/web.php` - rute `transmigran.detail` memuat `anggotaKeluarga`
- `resources/views/pages/transmigran/form.blade.php` - field Agama, usia
  read-only auto, section "Anggota Keluarga" dynamic (Alpine x-for repeater)
- `resources/views/pages/transmigran/detail.blade.php` - tampil agama, usia,
  daftar anggota keluarga
- `tests/Feature/HalamanTest.php` - penjaga

## Penjaga uji (rencana)
- Usia dihitung dari tanggal lahir, tidak ada `name="usia"` yang dikirim
  (input read-only tanpa name, atau `disabled`).
- Tiap field anggota keluarga punya tempat tampil di detail (penjaga 1f
  otomatis, tetapi ditegaskan).
- `jumlah_anggota_keluarga` tidak lagi `<input name=...>` yang bisa diketik.
- Anak berkegiatan Bekerja: pendidikan + pekerjaan + pendapatan `:required`
  bersyarat, dibuktikan mutasi (pola varietas saprotan).
- Repeater: minimal satu template `x-for`, tombol tambah dan hapus baris.
- `anggota_keluarga` di DummyData: minimal satu keluarga punya istri + anak,
  minimal satu anak berstatus Bekerja, satu anak tanpa NIK.
- Dokumen: `erd.md` §7.4 memuat penanda "dibalik 2026-08-28".

## Verifikasi
1. `vendor/bin/pest.bat` hijau, >= 648
2. `vendor/bin/pint.bat --test` <= 31
3. Render nyata `/transmigran`, `/transmigran/1` (keluarga berisi), form
   tambah + ubah dibuka, repeater diuji di peramban bila memungkinkan
4. Catat ke `notes.md` 1p + `tasklist.md`

## Keputusan pemilik proyek (2026-08-28)

1. **Lingkup: opsi 1 + opsi 3.** Pendataan anggota keluarga + jumlah jadi
   turunan, SEKALIGUS rombak `anggota_poktan` dan suksesi KK agar memilih
   dari daftar anggota keluarga, bukan diketik manual.
2. **Kegiatan anak: 4 opsi** - Belum Sekolah / Masih Sekolah / Bekerja /
   Tidak Bekerja.
3. **Agama: 6 agama Dukcapil** - Islam, Kristen, Katolik, Hindu, Buddha,
   Konghucu. (Bukan data master, bukan + Penghayat.)

## Pelaksanaan bertahap

- **Stage B1 SELESAI** (commit) - fondasi + transmigran: 3 enum, tabel
  `anggota_keluarga` (29 baris), `transmigran.agama`,
  `jumlah_anggota_keluarga` jadi turunan, usia dihitung, form repeater +
  detail tab, penjaga. 654 uji hijau, pint 31. `erd.md` §7.4 direvisi
  berjejak, `data-dictionary.md` §6.1a + §11.38-40, `rules.md` §6 poin
  2a/2b/9-9d.
- **Stage B2 SELESAI** (commit) - `anggota_poktan.anggota_keluarga_id` +
  `poktan.ketua_anggota_keluarga_id`; kedua form memilih orangnya dari
  daftar; `nama_wakil`/`nik_wakil`/`hubungan_dengan_kk`/`hubungan_ketua`
  dicabut. `DummyData::poktan()` menyelesaikan identitas ketua. 656 uji
  hijau, pint 31. data-dictionary §6.5/§8.2, rules §7a.2a/3a, notes 1p.3.
- **Stage B3 SELESAI** (commit) - suksesi KK: `calonPenggantiKk()` (pasangan
  lalu usia); modal `<select pengganti_anggota_keluarga_id>` + isian
  tersembunyi; `hubungan_pengganti` -> §11.39; rute Tahap 5 menghapus baris
  anggota pengganti. rules §6.5d, erd §7.4a, dict §6.4/§11.35. 657 uji, pint 31.

**ROMBONGAN B SELESAI (B1+B2+B3).** `erd.md` §7.4 dan §7.4a dibalik berjejak.
