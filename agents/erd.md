# erd.md
## Rancangan Basis Data â€” Sistem Informasi Digitalisasi Monitoring Pertanian dan Tata Kelola Data Kawasan Transmigrasi Kobalima Timur

Dokumen ini adalah **skema final** yang menjadi acuan tunggal penamaan tabel, kolom, dan relasi. Berkas `docs/20260809_T10_22_39.349Z.sql` diperlakukan sebagai **referensi**, bukan sumber kebenaran; seluruh struktur di sini disusun ulang dari nol dengan memperhitungkan koreksi pada `notes.md` bagian 1 serta temuan tambahan pada bagian 8 dokumen ini.

Rincian tipe data, panjang, nullability, nilai enum, dan aturan validasi tiap kolom ada di `data-dictionary.md`.

---

## 1. Konvensi Penamaan

| Aspek | Aturan | Contoh |
|---|---|---|
| Nama tabel | Bahasa Indonesia, huruf kecil, `snake_case`, **bentuk tunggal** | `transmigran`, `lahan`, `hasil_panen` |
| Primary key | `id_` + nama tabel | `id_transmigran`, `id_lahan` |
| Foreign key | nama tabel rujukan + `_id` | `transmigran_id`, `lahan_id` |
| Kolom biasa | `snake_case` Bahasa Indonesia | `nama_kepala_keluarga`, `tahun_perolehan` |
| Kolom boolean | awalan `is_` | `is_unggulan` |
| Kolom tanggal | awalan `tanggal_` | `tanggal_masuk`, `tanggal_keluar` |
| Kolom waktu sistem | Bahasa Inggris (bawaan Laravel) | `created_at`, `updated_at`, `deleted_at` |
| Tabel pivot | gabungan dua nama tabel, urut alfabet | `komoditas_poktan` |

**Catatan penting:** pola PK `id_transmigran` dan FK `transmigran_id` **berbeda dengan asumsi bawaan Eloquent** (yang mengharapkan PK bernama `id`). Konsekuensinya, setiap model wajib mendeklarasikan:

```php
protected $primaryKey = 'id_transmigran';
```

dan setiap relasi wajib menyebutkan kunci secara eksplisit:

```php
// Satu transmigran memiliki banyak lahan
public function lahan()
{
    return $this->hasMany(Lahan::class, 'transmigran_id', 'id_transmigran');
}
```

Pola ini dipilih agar kode mudah dibaca (`transmigran_id` langsung terbaca menunjuk ke mana), dengan konsekuensi tambahan dua argumen pada setiap definisi relasi.

**Aturan umum lain:**
1. Semua tabel memiliki `created_at` dan `updated_at`.
2. Tabel data utama memiliki `deleted_at` (soft delete) sesuai `rules.md` Â§5.1 catatan 4.
3. Semua tabel memakai `ENGINE=InnoDB`, `CHARSET=utf8mb4`, `COLLATE=utf8mb4_unicode_ci`.
4. Kolom dokumen menyimpan **path file**, bukan BLOB (`rules.md` Â§14a.3â€“4).
5. Koordinat disimpan sebagai dua kolom `lintang` dan `bujur` bertipe `DECIMAL(10,7)`, bukan `GEOMETRY`.

---

## 2. Daftar Tabel

Total **44 tabel bisnis aktif**, dikelompokkan menjadi 9 domain. (Angka "37" pada revisi
awal dokumen ini **usang**: Rombongan B menambah `anggota_keluarga`; Rombongan C menambah
`rute_aksesibilitas_sp`; Putaran 7 memecah alsintan/saprotan menjadi induk + `*_distribusi`,
menambah `dokumen_lahan_bidang`, `infrastruktur_sp`, `fasilitas_sp_cakupan`; data master
`referensi` menjadi tabel; dan `status_kondisi_sp` ditambahkan 2026-09-01. Skema DDL final
ada pada `database/transmigrasi.sql` — lihat `notes.md` butir 2026-09-01.)

| # | Domain | Tabel |
|---|---|---|
| 1 | Pengguna & Sistem | `role`, `permission`, `role_permission`, `user`, `kode_pemulihan_sandi`, `audit_log` |
| 2 | Master Wilayah & SP | `provinsi`, `kabupaten`, `kecamatan`, `desa`, `kawasan_transmigrasi`, `satuan_permukiman`, `user_satuan_permukiman`, `rute_aksesibilitas_sp` |
| 3 | Aset SP | `inventaris_sp`, `fasilitas_sp`, `fasilitas_sp_cakupan` |
| 4 | Master Referensi & Penilaian | `satuan`, `komoditas`, `referensi`, `parameter_penilaian_sp`, `status_kondisi_sp`, `penilaian_sp` |
| 5 | Kependudukan | `transmigran`, `anggota_keluarga`, `rumah`, `riwayat_penghunian`, `riwayat_kepala_keluarga` |
| 6 | Kelembagaan & Sarana | `poktan`, `anggota_poktan`, `alsintan`, `alsintan_distribusi`, `saprotan`, `saprotan_distribusi` |
| 7 | Lahan | `lahan`, `dokumen_lahan`, `dokumen_lahan_bidang` |
| 8 | Produksi Pertanian | `komoditas_poktan`, `penanaman`, `hasil_panen` |
| 9 | Infrastruktur & Pengaduan | `infrastruktur`, `infrastruktur_sp`, `pengaduan`, `penanganan_pengaduan` |

> Tabel yang **dicabut** dan tidak ada di skema final: `musim_tanam`, `verifikasi`,
> `pengaduan_objek`. `hasil_panen.poktan_id`, `lahan.status_hak`, `lahan.kategori_lahan`,
> `hasil_panen.kualitas`, `poktan.hubungan_ketua`, `anggota_poktan.nama_wakil/nik_wakil/
> hubungan_dengan_kk`, `alsintan.kepemilikan/transmigran_id`, `saprotan.transmigran_id`,
> `transmigran.jumlah_anggota_keluarga` juga dicabut sebagai kolom (lihat `notes.md`).

---

## 3. Diagram Relasi

### 3.1 Hierarki wilayah

Hierarki bercabang dua dari `kabupaten`. Cabang **administratif** mencatat pembagian pemerintahan, cabang **program** mencatat kawasan transmigrasi. Keduanya bertemu di `satuan_permukiman`.

```
                    â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
                    â”‚ provinsi â”‚
                    â””â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”˜
                         â”‚ 1:N
                    â”Œâ”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â”
                    â”‚ kabupaten â”‚
                    â””â”€â”€â”¬â”€â”€â”€â”€â”€â”¬â”€â”€â”˜
         cabang        â”‚     â”‚        cabang
    administratif 1:N  â”‚     â”‚  1:N   program
                  â”Œâ”€â”€â”€â”€â–¼â”€â”€â”€â” â”‚ â”Œâ”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
                  â”‚kecamatanâ”‚ â”‚ â”‚ kawasan_transmigrasiâ”‚
                  â””â”€â”€â”€â”€â”¬â”€â”€â”€â”˜ â”‚ â””â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜
                       â”‚ 1:N â”‚        â”‚ 1:N
                  â”Œâ”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”â”‚       â”‚
                  â”‚   desa   â”‚â”‚       â”‚
                  â””â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”˜â”‚       â”‚
                       â”‚      â”‚       â”‚
                       â”‚ 1:N  â”‚       â”‚ 1:N
                       â””â”€â”€â”€â”€â”€â”€â”¼â”€â”€â”€â”€â”€â”€â”€â”¤
                              â”‚       â”‚
                       â”Œâ”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â”
                       â”‚  satuan_permukiman  â”‚
                       â”‚  desa_id + kawasan_idâ”‚
                       â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜
```

**Cara membacanya:** satu SP berdiri di **satu desa** (menentukan kecamatan dan kabupaten secara transitif) sekaligus menjadi bagian dari **satu kawasan transmigrasi**. Kolom `kecamatan_id` tidak disimpan di SP karena sudah terbaca lewat `desa_id`.

### 3.2 Relasi keseluruhan

```
                     â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
      â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”¤ satuan_permukiman   â”œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
      â”‚              â””â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”˜               â”‚
      â”‚ 1:N             â”‚ 1:N   â”‚ 1:N  â”‚ 1:N               â”‚ 1:N
â”Œâ”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â”€â”€â”  â”Œâ”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”  â”‚  â”Œâ”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”  â”Œâ”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
â”‚ inventaris_spâ”‚  â”‚fasilitas â”‚  â”‚  â”‚ infrastrukturâ”‚  â”‚    poktan      â”‚
â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜  â”‚   _sp    â”‚  â”‚  â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜  â””â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”˜
                  â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜  â”‚                        â”‚ 1:N    â”‚ N:M
                                â”‚                  â”Œâ”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â” â”‚
                                â”‚                  â”‚anggota_    â”‚ â”‚
                                â”‚                  â”‚  poktan    â”‚ â”‚
                                â”‚                  â””â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”˜ â”‚
                                â”‚                        â”‚        â”‚
      â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”¼â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜        â”‚
      â”‚ 1:N                     â”‚ 1:N                             â”‚
â”Œâ”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â–¼â”                          â”Œâ”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
â”‚         transmigran           â”‚                          â”‚komoditas_poktanâ”‚
â””â”€â”€â”¬â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”¬â”€â”€â”€â”˜                          â””â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜
   â”‚1:1 â”‚1:N â”‚1:N â”‚1:N â”‚1:N â”‚1:N                                  â”‚ N:M
   â”‚    â”‚    â”‚    â”‚    â”‚    â”‚                                     â”‚
â”Œâ”€â”€â–¼â”€â”€â”€â”â”‚    â”‚    â”‚    â”‚    â”‚                              â”Œâ”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”
â”‚rumah â”‚â”‚    â”‚    â”‚    â”‚    â”‚                    â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”¤ komoditas  â”‚
â””â”€â”€â”¬â”€â”€â”€â”˜â”‚    â”‚    â”‚    â”‚    â”‚                    â”‚  N:1    â””â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”˜
   â”‚1:N â”‚    â”‚    â”‚    â”‚    â”‚                    â”‚                â”‚ N:1
â”Œâ”€â”€â–¼â”€â”€â”€â”€â–¼â”€â”€â” â”‚    â”‚    â”‚    â”‚            â”Œâ”€â”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â”  â”Œâ”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”
â”‚ riwayat_ â”‚ â”‚    â”‚    â”‚    â”‚            â”‚ penanaman    â”‚  â”‚   satuan   â”‚
â”‚penghunianâ”‚ â”‚    â”‚    â”‚    â”‚            â””â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”˜  â””â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”˜
â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜ â”‚    â”‚    â”‚    â”‚                â”‚ N:1  â”‚ 1:N         â”‚ N:1
             â”‚    â”‚    â”‚    â”‚       â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”  â”‚      â”Œâ”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”
        â”Œâ”€â”€â”€â”€â–¼â”€â”€â” â”‚    â”‚    â”‚       â”‚            â”‚  â””â”€â”€â”€â”€â”€â”€â–º hasil_panenâ”‚
        â”‚ lahan â”‚ â”‚    â”‚    â”‚       â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜         â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜
        â””â”€â”€â”€â”¬â”€â”€â”€â”˜ â”‚    â”‚    â”‚                                     â–²
            â”‚1:N  â”‚    â”‚    â”‚                                     â”‚ N:1
      â”Œâ”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”â”‚   â”‚    â”‚                                     â”‚
      â”‚  dokumen_ â”‚â”‚   â”‚    â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜
      â”‚   lahan   â”‚â”‚   â”‚
      â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜â”‚   â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
                   â”‚              â”‚
            â”Œâ”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”   â”Œâ”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”   â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
            â”‚ alsintan â”‚   â”‚  saprotan  â”‚   â”‚ pengaduan â”‚
            â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜   â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜   â””â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”˜
                                                  â”‚ 1:N
                                          â”Œâ”€â”€â”€â”€â”€â”€â”€â–¼â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
                                          â”‚   penanganan_    â”‚
                                          â”‚    pengaduan     â”‚
                                          â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜

â”Œâ”€â”€â”€â”€â”€â”€â”  1:1 (opsional)   â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”        â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
â”‚ user â”œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â–º transmigranâ”‚        â”‚ audit_log â”‚
â””â”€â”€â”€â”¬â”€â”€â”˜                   â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜        â””â”€â”€â”€â”€â”€â–²â”€â”€â”€â”€â”€â”˜
    â”‚ 1:N                                              â”‚ N:1
    â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜
```

---

## 4. Daftar Relasi Lengkap

Kolom "Aturan hapus" memakai istilah SQL: `RESTRICT` mencegah penghapusan induk selama anak masih ada, `CASCADE` ikut menghapus anak, `SET NULL` mengosongkan FK.

| # | Tabel anak | Kolom FK | Tabel induk | Kardinalitas | Aturan hapus |
|---|---|---|---|---|---|
| 1 | `kabupaten` | `provinsi_id` | `provinsi` | N:1 | RESTRICT |
| 2 | `kecamatan` | `kabupaten_id` | `kabupaten` | N:1 | RESTRICT |
| 3 | `desa` | `kecamatan_id` | `kecamatan` | N:1 | RESTRICT |
| 3a | `kawasan_transmigrasi` | `kabupaten_id` | `kabupaten` | N:1 | RESTRICT |
| 4 | `satuan_permukiman` | `desa_id` | `desa` | N:1 | RESTRICT |
| 4a | `satuan_permukiman` | `kawasan_id` | `kawasan_transmigrasi` | N:1 | RESTRICT |
| 5 | `satuan_permukiman` | `user_id` | `user` | N:1 | SET NULL |
| 6 | `inventaris_sp` | `satuan_permukiman_id` | `satuan_permukiman` | N:1 | CASCADE |
| 7 | `fasilitas_sp` | `satuan_permukiman_id` | `satuan_permukiman` | N:1 | CASCADE |
| 8 | `user` | `role_id` | `role` | N:1 | RESTRICT |
| 9 | `transmigran` | `satuan_permukiman_id` | `satuan_permukiman` | N:1 | RESTRICT |
| 10 | `rumah` | `satuan_permukiman_id` | `satuan_permukiman` | N:1 | RESTRICT |
| 11 | `rumah` | `transmigran_id` | `transmigran` | **1:1 (UNIQUE, nullable)** | SET NULL |
| 12 | `riwayat_penghunian` | `rumah_id` | `rumah` | N:1 | CASCADE |
| 13 | `riwayat_penghunian` | `transmigran_id` | `transmigran` | N:1 | RESTRICT |
| 13a | `riwayat_kepala_keluarga` | `transmigran_id` | `transmigran` | N:1 | RESTRICT |
| 14 | `lahan` | `transmigran_id` | `transmigran` | **N:1** | CASCADE |
| 15 | `lahan` | `satuan_permukiman_id` | `satuan_permukiman` | N:1 | RESTRICT |
| 16 | `lahan` | `poktan_id` | `poktan` | N:1 (nullable) | SET NULL |
| 17 | `dokumen_lahan` | `lahan_id` | `lahan` | N:1 | CASCADE |
| 18 | `poktan` | `satuan_permukiman_id` | `satuan_permukiman` | N:1 | RESTRICT |
| 19 | `poktan` | `ketua_transmigran_id` | `transmigran` | N:1 (nullable) | SET NULL |

> **Nullable karena ketua tidak selalu transmigran** (2026-08-17). Diperluas 2026-08-20: bila `asal_ketua` bernilai `Bukan Transmigran`, kolom ini `NULL` dan identitas ketua disimpan pada `nama_ketua` beserta `nik_ketua`. Pada nilai `Anggota Keluarga`, FK ini **tetap terisi** sebab yang ditunjuk adalah keluarga yang diwakili, bukan orangnya. Banyak poktan diketuai penduduk setempat yang bukan peserta program, sehingga mewajibkan FK ini membuat poktan semacam itu tidak dapat didata. Lihat `data-dictionary.md` Â§8.1.
| 20 | `anggota_poktan` | `poktan_id` | `poktan` | N:1 | CASCADE |
| 21 | `anggota_poktan` | `transmigran_id` | `transmigran` | N:1 | RESTRICT |
| 22 | `alsintan` | `satuan_permukiman_id` | `satuan_permukiman` | N:1 | RESTRICT |
| 23 | `alsintan` | `poktan_id` | `poktan` | N:1 | RESTRICT |
| 24 | `saprotan` | `satuan_permukiman_id` | `satuan_permukiman` | N:1 | RESTRICT |
| 25 | `saprotan` | `poktan_id` | `poktan` | N:1 | RESTRICT |
| 26 | `saprotan` | `satuan_id` | `satuan` | N:1 | RESTRICT |
| 26a | `saprotan` | `komoditas_id` | `komoditas` | N:1 (nullable) | RESTRICT |
| 27 | `komoditas` | `satuan_id` | `satuan` | N:1 | RESTRICT |
| 28 | `komoditas_poktan` | `komoditas_id` | `komoditas` | N:M | CASCADE |
| 29 | `komoditas_poktan` | `poktan_id` | `poktan` | N:M | CASCADE |
| 30 | `penanaman` | `poktan_id` | `poktan` | N:1 | CASCADE |
| 32 | `penanaman` | `komoditas_id` | `komoditas` | N:1 | RESTRICT |
| 32a | `penanaman` | `saprotan_id` | `saprotan` | N:1 (nullable) | SET NULL |
| 33 | `hasil_panen` | `penanaman_id` | `penanaman` | N:1 | CASCADE |
| 33a | `hasil_panen` | `poktan_id` | `poktan` | N:1 | RESTRICT |
| 34 | `hasil_panen` | `satuan_id` | `satuan` | N:1 | RESTRICT |
| 35 | `infrastruktur` | `satuan_permukiman_id` | `satuan_permukiman` | N:1 | RESTRICT |
| 36 | `infrastruktur` | `poktan_id` | `poktan` | N:1 (nullable) | SET NULL |
| 37 | `pengaduan` | `user_id` | `user` | N:1 | RESTRICT |
| 38 | `pengaduan` | `satuan_permukiman_id` | `satuan_permukiman` | N:1 | RESTRICT |
| 39 | `penanganan_pengaduan` | `pengaduan_id` | `pengaduan` | N:1 | CASCADE |
| 40 | `penanganan_pengaduan` | `user_id` | `user` | N:1 | RESTRICT |
| 41 | `audit_log` | `user_id` | `user` | N:1 | SET NULL |
| 42 | `role_permission` | `role_id` | `role` | N:M | CASCADE |
| 43 | `role_permission` | `permission_id` | `permission` | N:M | CASCADE |
| 44 | `user_satuan_permukiman` | `user_id` | `user` | N:M | CASCADE |
| 45 | `user_satuan_permukiman` | `satuan_permukiman_id` | `satuan_permukiman` | N:M | CASCADE |

**Rekonsiliasi relasi terhadap keputusan terbaru (diterapkan pada `database/transmigrasi.sql`, 2026-09-01):**

- **#17 `dokumen_lahan.lahan_id`** dicabut. `dokumen_lahan` menjadi **induk** (jenis, nomor, tanggal terbit, berkas); relasi ke bidang lewat pivot M:N **`dokumen_lahan_bidang`** (`dokumen_lahan_id` CASCADE, `lahan_id` CASCADE, UNIQUE gabungan). Putaran 7-G.
- **#22–#23 `alsintan` → `satuan_permukiman`/`poktan`** dicabut. `alsintan` = induk/pengadaan tanpa FK tersebut; distribusi ke poktan pada **`alsintan_distribusi`** (`alsintan_id` CASCADE, `poktan_id` RESTRICT, `penanda_terima_id` → `anggota_poktan` SET NULL). Putaran 7.
- **#24–#26a `saprotan` → `satuan_permukiman`/`poktan`** dicabut. `saprotan` = induk (FK `satuan_id` RESTRICT, `komoditas_id` RESTRICT nullable); distribusi pada **`saprotan_distribusi`** (`saprotan_id` CASCADE, `poktan_id` RESTRICT). Putaran 7.
- **#32a `penanaman.saprotan_id`** menjadi **`penanaman.saprotan_distribusi_id`** → `saprotan_distribusi`, **NOT NULL**, `ON DELETE RESTRICT` (wajib sejak 2026-08-30).
- **#33a `hasil_panen.poktan_id`** dicabut (Putaran 7-H); diturunkan dari `penanaman.poktan_id`.
- Relasi FK baru: `anggota_keluarga.transmigran_id` (CASCADE), `poktan.ketua_anggota_keluarga_id` & `anggota_poktan.anggota_keluarga_id` (SET NULL, nullable), `rute_aksesibilitas_sp.satuan_permukiman_id` (CASCADE), `komoditas_poktan` (dua FK CASCADE), `parameter_penilaian_sp.referensi_id` (RESTRICT), `referensi.bidang_id` (self-FK, SET NULL), `infrastruktur_sp`/`fasilitas_sp_cakupan` (dua FK CASCADE, UNIQUE gabungan), `penilaian_sp.satuan_permukiman_id` (RESTRICT) & `penilaian_sp.user_id` (SET NULL). `status_kondisi_sp` tanpa FK.

---

## 5. Aturan Integritas yang Wajib Dijaga Database

Aturan berikut **tidak boleh** hanya divalidasi di form, karena dapat ditembus lewat impor data atau akses langsung ke database (`rules.md` Â§6a.6).

| # | Aturan | Mekanisme |
|---|---|---|
| 1 | Satu rumah dihuni tepat satu KK | `UNIQUE` pada `rumah.transmigran_id` (nullable) |
| 2 | Satu KK menempati tepat satu rumah | Konsekuensi otomatis dari FK berada di `rumah` + UNIQUE di atas |
| 3 | Username unik antar-akun | `UNIQUE` pada `user.username` |
| 3a | Nama role unik | `UNIQUE` pada `role.nama` |
| 3b | Nama permission unik | `UNIQUE` pada `permission.nama` |
| 3c | Satu kewenangan tidak dobel pada satu role | `UNIQUE (role_id, permission_id)` pada `role_permission` |
| 3d | Satu SP tidak dobel ditugaskan ke satu pengguna | `UNIQUE (user_id, satuan_permukiman_id)` pada `user_satuan_permukiman` |
| 4 | NIK transmigran unik | `UNIQUE` pada `transmigran.nik` |
| 5 | Nomor KK unik | `UNIQUE` pada `transmigran.no_kk` |
| 6 | Email user unik | `UNIQUE` pada `user.email` (nullable) |
| 6a | Username user unik | `UNIQUE` pada `user.username` |
| 7 | Satu keluarga hanya sekali terdaftar aktif di satu poktan, dan diwakili satu orang saja | `UNIQUE (poktan_id, transmigran_id)` pada `anggota_poktan`; sejak 2026-08-20 `transmigran_id` bermakna keluarga |
| 8 | Satu komoditas tidak dobel pada satu poktan | `UNIQUE (poktan_id, komoditas_id)` pada `komoditas_poktan` |
| 9 | Nama wilayah unik dalam induknya | `UNIQUE (kecamatan_id, nama)` pada `desa`, dan seterusnya berjenjang |
| 9a | Nama kawasan unik dalam kabupatennya | `UNIQUE (kabupaten_id, nama)` pada `kawasan_transmigrasi` |
| 9b | Satu SP hanya berdiri di satu desa dan satu kawasan | `desa_id` dan `kawasan_id` keduanya `NOT NULL` |
| 10 | ~~Satu poktan hanya satu komoditas per periode tanam~~ **DICABUT 2026-09-01** | `UNIQUE` pada `penanaman` dibatalkan; satu poktan dapat menanam bertahap atau menanam dari bantuan berbeda pada bulan yang sama |

**Catatan aturan 1 dan 2:** FK sengaja diletakkan pada `rumah`, bukan pada `transmigran`. Dengan begitu satu constraint `UNIQUE` sudah cukup menjamin relasi satu-ke-satu dua arah, sekaligus membuat kolom penghuni bersifat nullable â€” nilai `NULL` berarti rumah kosong (`rules.md` Â§6a.7).

---

## 6. Indeks

Dashboard dan halaman daftar mengandalkan filter wilayah dan periode, sehingga indeks berikut wajib ada sejak migration pertama (`rules.md` Â§11.7, `notes.md` Â§1.7).

| Tabel | Kolom yang diindeks | Alasan |
|---|---|---|
| `satuan_permukiman` | `kawasan_id`, `desa_id` | Filter dashboard per kawasan dan per desa |
| `kawasan_transmigrasi` | `kabupaten_id` | Daftar kawasan per kabupaten |
| `transmigran` | `satuan_permukiman_id`, `nik`, `tahun_kedatangan`, `pekerjaan_kepala_keluarga` | Filter per SP, pencarian NIK, grafik per tahun, histogram pekerjaan |
| `rumah` | `satuan_permukiman_id`, `status_hunian`, `kondisi` | Rekap rumah terhuni per SP |
| `riwayat_penghunian` | `rumah_id`, `transmigran_id`, `tanggal_masuk`, `tanggal_keluar` | Grafik KK masuk dan keluar per tahun |
| `lahan` | `transmigran_id`, `satuan_permukiman_id`, `peruntukan_lahan` | Rekap luas lahan per SP dan per jenis |
| `penanaman` | `poktan_id`, `komoditas_id`, `periode_tanam`, `saprotan_id` | Rekap tanam per periode dan komoditas; `saprotan_id` dipakai menghitung sisa benih |
| `hasil_panen` | `penanaman_id`, `poktan_id`, `periode_panen` | Grafik produksi per tahun dan rekap per poktan |
| `anggota_poktan` | `poktan_id`, `transmigran_id`, `status` | Daftar anggota aktif |
| `pengaduan` | `satuan_permukiman_id`, `kategori`, `status`, `prioritas`, `tanggal_pengaduan` | Rekap isu prioritas per SP |
| `penanganan_pengaduan` | `pengaduan_id`, `tanggal_penanganan` | Riwayat penanganan berurutan |
| `infrastruktur` | `satuan_permukiman_id`, `jenis`, `kondisi` | Grafik status infrastruktur |
| `alsintan` | `poktan_id`, `satuan_permukiman_id`, `tahun_pengadaan` | Rekap per kelompok dan periode; pemilik selalu poktan sejak 2026-08-22 |
| `saprotan` | `poktan_id`, `satuan_permukiman_id`, `tahun_pengadaan`, `komoditas_id` | Rekap per kelompok dan periode; `komoditas_id` dipakai menyaring benih pada form penanaman |
| `audit_log` | `user_id`, `nama_tabel`, `created_at` | Penelusuran audit |
| `user` | `email`, `username`, `role_id`, `is_aktif` | Pencarian kredensial saat login dan penyaringan daftar pengguna |
| `role_permission` | `role_id`, `permission_id` | Pemeriksaan kewenangan pada setiap permintaan halaman |
| `user_satuan_permukiman` | `user_id`, `satuan_permukiman_id` | Penyaring cakupan data Per SP |
| `pengaduan` | `nomor_pengaduan`, `sumber_laporan`, `ip_pelapor` | Pelacakan publik dan pembatasan laju laporan |

Indeks gabungan `(satuan_permukiman_id, tahun_kedatangan)` pada `transmigran` dan `(satuan_permukiman_id, status)` pada `pengaduan` disiapkan bila query dashboard terbukti lambat setelah data nyata masuk.

---

## 7. Aturan Turunan Antar-Fitur

### 7.0 Hierarki wilayah bercabang dua

`satuan_permukiman` adalah satu-satunya titik temu antara cabang administratif dan cabang program. Konsekuensinya:

1. **Seluruh data operasional menaut ke `satuan_permukiman_id`**, tidak pernah langsung ke desa maupun kawasan. Transmigran, rumah, lahan, poktan, infrastruktur, dan pengaduan semuanya mengikuti aturan ini.
2. **Rekap per kawasan** dibaca lewat SP: `kawasan â†’ satuan_permukiman â†’ data`. Rekap per kecamatan atau desa memakai jalur `desa â†’ satuan_permukiman â†’ data`.
3. **`kecamatan_id` tidak disimpan di SP** karena sudah terbaca dari `desa_id`. Menyimpannya berarti membuka peluang data tidak sinkron, misalnya desa berpindah kecamatan tetapi SP masih mencatat kecamatan lama.
4. **Kabupaten terbaca dari dua jalur** yang seharusnya menghasilkan nilai sama: lewat kawasan dan lewat desa. Bila kelak ada kawasan yang melintasi kabupaten, jalur desa adalah yang dianggap benar untuk urusan administratif.

### 7.0a Hak akses dinamis

Role **tidak dikunci di dalam kode**, melainkan disimpan sebagai data yang dapat dibuat dan diatur Admin lewat antarmuka. Ini menggantikan pendekatan lama yang memakai kolom enum tetap pada `user`.

**Tiga tabel bekerja bersama:**

```
user â”€â”€N:1â”€â”€> role â”€â”€N:Mâ”€â”€> permission
                â”‚
                â””â”€ cakupan_data: Semua | Per SP
```

1. **`permission`** adalah daftar kewenangan baku yang ditanam sistem lewat seeder, contoh `transmigran.lihat` dan `transmigran.ubah`. Admin **tidak dapat** menambah atau menghapus kewenangan, karena setiap kewenangan harus punya pasangan pemeriksa di dalam kode.
2. **`role`** dibuat bebas oleh Admin, lalu dipasangkan ke sejumlah kewenangan lewat `role_permission`.
3. **`cakupan_data`** menjawab pertanyaan berbeda dari kewenangan. Kewenangan menentukan *boleh melakukan apa*, cakupan menentukan *boleh melihat data siapa*.

**Cara cakupan diterapkan pada query:**

| Nilai | Penyaring | Contoh pemakai |
|---|---|---|
| `Semua` | tanpa penyaring | Admin, Dinas |
| `Per SP` | `WHERE satuan_permukiman_id IN (SP pada user_satuan_permukiman)` | Operator SP |

Penyaring cakupan wajib diterapkan pada **level query**, bukan sekadar menyembunyikan menu (`rules.md` Â§5). Tanpa itu, pengguna masih dapat membuka data di luar cakupannya dengan mengetik alamat URL langsung.

**Perlindungan:** role bertanda `is_bawaan = TRUE` tidak dapat dihapus. Role Admin tidak dapat dihapus maupun dikurangi kewenangannya, agar sistem tidak pernah kehilangan jalur administrasi.

### 7.1 Satuan dan konversi panen
`komoditas.satuan_id` menetapkan satuan baku tiap komoditas. `hasil_panen.produksi` disimpan **apa adanya** dalam satuan tersebut, tanpa dikonversi. Agregasi lintas komoditas mengalikan `volume Ã— satuan.faktor_ke_ton` **hanya saat rekap** (`rules.md` Â§8a.4â€“5). `hasil_panen.satuan_id` disalin dari komoditas saat penyimpanan agar riwayat tetap sahih bila satuan baku komoditas kelak diubah.

### 7.2 Status pengaduan
`pengaduan.status` menyimpan status **terkini** saja. Seluruh perubahan status dicatat sebagai baris baru pada `penanganan_pengaduan` (`notes.md` Â§1.5). Tidak ada kolom `catatan_penanganan` pada `pengaduan`.

### 7.3 Jumlah anggota poktan
`poktan` **tidak** menyimpan kolom `jumlah_anggota`. Nilai tersebut dihitung dari `anggota_poktan` yang berstatus Aktif (`withCount`), agar tidak pernah basi.

Alasan yang sama mencabut `luas_lahan_kelompok` pada 2026-08-20: luas lahan kelompok dijumlahkan dari bidang milik seluruh anggotanya, dan luas lahan ketua maupun anggota dijumlahkan dari bidang milik keluarga masing-masing. Menyimpannya sebagai kolom akan basi begitu petugas membetulkan luas di modul lahan. Pengecualiannya hanya ketua bertanda `Bukan Transmigran`, yang lahannya memang tidak terdata pada tabel `lahan`.

### 7.4 Jumlah anggota keluarga

> **DIBALIK 2026-08-28 (Rombongan B).** Atas permintaan pemilik proyek, sistem kini **mendata anggota keluarga satu per satu** lewat tabel `anggota_keluarga` (`data-dictionary.md` §6.1a). Alasannya: suksesi kepala keluarga dan pemilihan wakil poktan sebelumnya mengetik nama dan NIK dari nol, padahal orang yang sama sudah ada di keluarga itu. Paragraf lama di bawah dipertahankan sebagai jejak keputusan.

Sesudah pembalikan:
- `transmigran.jumlah_anggota_keluarga` **bukan lagi kolom**, melainkan diturunkan: 1 (kepala keluarga) + `COUNT(anggota_keluarga)`. Menyimpannya membuat nilainya dapat berselisih dengan daftar anggota, kekeliruan yang sama dengan `poktan.jumlah_anggota` (§7.3).
- Suksesi kepala keluarga (§7.4a, `rules.md` §6.5) memilih pengganti **dari daftar anggota keluarga**; datanya "naik" menimpa baris `transmigran`.
- `anggota_poktan` dan `poktan.ketua` jalur "Anggota Keluarga" memilih dari daftar yang sama alih-alih mengetik `nama_wakil`/`nik_wakil`.
- `usia` tetap **tidak disimpan**, dihitung dari `tanggal_lahir`.

~~`transmigran.jumlah_anggota_keluarga` **disimpan** sebagai angka, karena sistem tidak mendata anggota keluarga satu per satu (di luar lingkup PRD).~~

~~Keputusan itu berkonsekuensi pada suksesi kepala keluarga: istri dan anak tidak punya baris di mana pun, sehingga identitas pengganti **wajib diketik petugas**. Sistem merekam siapa penggantinya, bukan menebaknya dari daftar anggota keluarga yang memang tidak ada.~~

### 7.4a Pergantian kepala keluarga
Satu baris `transmigran` adalah satu RUMAH TANGGA, bukan satu orang. Ketika kepala keluarganya meninggal atau merantau, barisnya **disunting** dan ketujuh relasi yang menautinya tetap utuh: jatah rumah dan lahan diberikan kepada KK, bukan kepada suaminya secara pribadi.

Peristiwanya direkam pada `riwayat_kepala_keluarga`, sebab `audit_log` tidak dapat membedakan suksesi dari perbaikan salah ketik — keduanya berbentuk aksi `Ubah` pada `nama_kepala_keluarga`. Lihat `data-dictionary.md` 6.4.

**Sejak Stage B3 (2026-08-28)** pengganti **dipilih dari daftar `anggota_keluarga`** keluarga itu, tidak diketik (`erd.md` §7.4 sudah dibalik). Datanya menimpa baris `transmigran`, barisnya sebagai anggota keluarga dihapus, dan `riwayat_kepala_keluarga` menyimpan kedua sisi identitas secara denormalisasi (tanpa FK ke `anggota_keluarga` yang akan menggantung).

### 7.5 Riwayat penghunian
Pergantian penghuni tidak menimpa data lama. Alurnya: baris `riwayat_penghunian` lama diisi `tanggal_keluar` dan `alasan_keluar`, `rumah.transmigran_id` diperbarui, lalu baris riwayat baru dibuat (`rules.md` Â§6a.9).


---

## 8. Perbedaan terhadap `docs/20260809_T10_22_39.349Z.sql`

Bagian ini merangkum seluruh penyimpangan yang disengaja dari berkas SQL referensi, sebagai jejak keputusan.

### 8.1 Koreksi yang sudah tercatat di `notes.md`

| # | Kondisi pada SQL referensi | Keputusan pada skema final |
|---|---|---|
| 1 | 11 arah foreign key terbalik (induk menunjuk anak) | Seluruh FK diletakkan pada sisi "banyak" |
| 2 | Tabel `pertanian` hanya berisi 3 kolom FK | Dihapus; `alsintan` dan `infrastruktur` menaut langsung ke pemiliknya |
| 3 | ENUM berisi nama provinsi/kabupaten/kecamatan/desa | Diganti tabel referensi bertingkat, ditambah `kawasan_transmigrasi` (lihat temuan 26) |
| 4 | `dokumen_pendukung` bertipe `BLOB` | `VARCHAR(255)` berisi path file |
| 5 | `volumen_panen`, `harga_jual`, `luas_lahan` bertipe `VARCHAR` | `DECIMAL` dengan presisi memadai; salah ketik `volumen` diperbaiki jadi `volume` |
| 6 | Tabel `satuan` belum ada | Ditambahkan beserta `faktor_ke_ton` |
| 7 | `pengaduan` punya status ganda dan catatan terduplikasi | `pengaduan.status` menyimpan status terkini; riwayat pindah ke `penanganan_pengaduan` |
| 8 | `komoditas` memakai kolom berulang `_1`, `_2`, `_3` | Dinormalisasi menjadi satu baris per komoditas |
| 9 | Tidak ada kolom `password`, timestamps, dan soft delete | Ditambahkan pada seluruh tabel terkait |
| 10 | Tidak ada `no_kk` dan `jumlah_anggota_keluarga` | Ditambahkan pada `transmigran` |
| 11 | Tidak ada tabel audit log | Ditambahkan sebagai `audit_log` |
| 12 | Tidak ada tabel riwayat penghunian | Ditambahkan sebagai `riwayat_penghunian` |
| 13 | Tidak ada indeks pada kolom yang sering difilter | Lihat bagian 6 |
| 14 | Nilai enum `'Lainnya '` mengandung spasi berlebih | Dibersihkan menjadi `'Lainnya'` |

### 8.2 Temuan tambahan pada sesi ini

| # | Kondisi pada SQL referensi | Keputusan pada skema final | Alasan |
|---|---|---|---|
| 15 | Data panen (`volumen_panen`, `harga_jual`, `kualitas_panen`, `musim_tanam`) menempel sebagai kolom di `lahan_usaha_sp` | Dipindah ke tabel `hasil_panen` tersendiri, ditaut lewat `penanaman` | Struktur lama membatasi satu lahan hanya punya satu panen selamanya, sedangkan PRD Â§7.6 mewajibkan riwayat panen per periode dan grafik volume per tahun |
| 16 | Koordinat memakai tipe `GEOMETRY` | Dua kolom `lintang` dan `bujur` bertipe `DECIMAL(10,7)` | Eloquent tidak mendukung `GEOMETRY` secara natif sehingga butuh raw query atau paket tambahan, padahal kebutuhan hanya menampilkan lintang/bujur 6 desimal (`ui-spec.md` Â§10). Presisi 7 desimal setara Â±1 cm, jauh melebihi kebutuhan lapangan |
| 17 | Tabel `koordinat_lokasi_sp` berisi 4 kolom TEXT bernama Utara/Timur/Selatan/Barat | Dilebur menjadi 4 kolom `batas_utara`, `batas_timur`, `batas_selatan`, `batas_barat` pada `satuan_permukiman` | Isinya deskripsi batas wilayah, bukan koordinat. Relasinya 1:1 wajib, sehingga tabel terpisah hanya menambah join tanpa manfaat | **Keempat kolom itu kemudian dicabut seluruhnya pada 2026-08-18** sebab isinya tidak pernah dipakai perhitungan maupun peta; peleburan tabelnya tetap sah, hanya kolomnya yang tidak jadi disimpan.
| 18 | Empat tabel untuk satu konsep lahan: `lahan_sp`, `lahan_usaha_sp`, `kategori_lahan_sp`, `kategori_lahan` | Digabung menjadi satu tabel `lahan` dengan kolom `jenis_lahan` (Pekarangan/Usaha) dan `kategori_lahan` (Basah/Kering) | `kategori_lahan_sp` dan `lahan_sp` sama-sama memuat ENUM identik (`notes.md` Â§1.7). Kolom khusus lahan usaha dibuat nullable, diisi hanya bila `jenis_lahan` = Usaha. **Peleburannya tetap benar, tetapi `kategori_lahan` dicabut 2026-08-20** dan digantikan kolom `luas_kering` serta `luas_basah`, sebab sifat pengairan ternyata komposisi luas bukan sifat bidang (`data-dictionary.md` 7.1) |
| 19 | `saprotan` tidak menyimpan jenis, jumlah, maupun satuan | Ditambahkan `jenis`, `jumlah`, `satuan_id` | `rules.md` Â§7c.2 mewajibkan pencatatan jenis, jumlah, dan satuan tiap penyaluran |
| 20 | `alsintan` tidak menyimpan jumlah, kondisi, maupun sumber dana | Ditambahkan `jumlah`, `kondisi`, `sumber_dana` (dulu `sumber_perolehan`, diseragamkan 2026-08-28) | `rules.md` Â§7b.2 mewajibkan keempat data tersebut |
| 21 | `infrastruktur_pertanian` tidak menyimpan jenis, kondisi, maupun sumber dana | Ditambahkan `jenis`, `kondisi`, `sumber_dana`, `lintang`, `bujur` | `rules.md` Â§10.2â€“4 mewajibkan jenis (air, irigasi, listrik, jalan produksi, telekomunikasi, gudang), kondisi terkini, sumber dana, dan titik koordinat |
| 22 | ~~`musim_tanam` hanya punya kolom `keterangan`~~ **DIBATALKAN 2026-08-22** | Tabelnya **dihapus seluruhnya**, bukan diperbaiki | Penyelesaian 2026-08-10 menambahkan `nama`, `tahun`, `tanggal_mulai`, `tanggal_selesai` demi grafik panen per tahun. Keterangan lapangan pemilik proyek membatalkannya: poktan menanam **fleksibel**, tidak mengikuti periode baku MT1/MT2, sehingga memaksa setiap penanaman memilih satu musim membuat petugas menebak. Grafik per tahun tetap dapat dihitung dari `penanaman.periode_tanam` dan `hasil_panen.periode_panen` — dua kolom yang memang dicatat dan tidak perlu ditebak. Lihat kamus data §5.3 |
| 23 | `inventaris_sp` dan `fasilitas_sp` tidak menyimpan status penyerahan | Ditambahkan `status_penyerahan` dan `jumlah` | `rules.md` Â§4b.4 mewajibkan pencatatan status penyerahan |
| 24 | `transmigran` tidak menyimpan tahun kedatangan | Ditambahkan `tahun_kedatangan` dan `status_tinggal` | PRD Â§7.8 meminta grafik jumlah transmigran/KK/petani per tahun; tanpa kolom ini agregasi per tahun mustahil |
| 25 | `poktan` menyimpan `nama_ketua_poktan`, `nik_ketua_poktan` sebagai teks sekaligus `id_transmigran` | Cukup `ketua_transmigran_id` menunjuk `transmigran` | Data ketua sudah ada pada tabel transmigran; menyalinnya berisiko tidak sinkron. **Diperluas 2026-08-20:** kolom teks itu ternyata tetap diperlukan bagi ketua yang bukan kepala keluarga maupun bukan transmigran, sebab keduanya tidak punya baris yang dapat dibaca. Yang tetap berlaku adalah larangan menyalin: pada jalur `Kepala Keluarga` kolom teks itu wajib `NULL` |
| 26 | Kawasan transmigrasi tidak punya representasi apa pun; SP langsung menempel ke desa | Tabel `kawasan_transmigrasi` ditambahkan sebagai cabang tersendiri dari `kabupaten`; `satuan_permukiman` menaut ke `kawasan_id` dan `desa_id` sekaligus | Kawasan transmigrasi adalah subjek utama sistem, tetapi pada SQL referensi hanya hidup di judul dokumen. Kawasan juga memotong batas administratif: Kobalima Timur menaungi 6 SP yang tersebar di 4 kecamatan, sehingga mustahil diwakili oleh hierarki administratif saja. Tanpa tabel ini, replikasi ke kawasan lain (`rules.md` Â§4a.4) tidak mungkin dilakukan |
| 27 | Role disimpan sebagai kolom ENUM `kategori_user` pada tabel `user` | Diganti tiga tabel: `role`, `permission`, dan `role_permission`, ditambah kolom `cakupan_data` pada `role` | Menambah atau mengubah role pada bentuk ENUM berarti mengubah struktur tabel, sehingga hanya dapat dilakukan programmer. Dengan tabel tersendiri, Admin dapat menyusun role beserta kewenangannya lewat antarmuka. Ini sekaligus menjawab kebutuhan role Operator SP yang tidak ada pada daftar semula |
| 28 | ~~Tidak ada penyimpanan status verifikasi~~ | **DIBATALKAN 2026-08-14.** Tabel `verifikasi` sempat dirancang, lalu dicabut bersama seluruh fitur verifikasi atas kesepakatan tim | Temuan ini sahih pada masanya: matriks kewenangan memberi hak verifikasi pada 17 fitur tanpa satu pun kolom penyimpannya. Setelah tim memutuskan fitur verifikasi tidak diperlukan, hak tersebut ikut dicabut sehingga temuan ini tidak lagi berlaku |
| 29 | `pengaduan.user_id` wajib, sehingga pelapor harus punya akun | `user_id` menjadi nullable, ditambah `nama_pelapor`, `kontak_pelapor`, `sumber_laporan`, dan `ip_pelapor` | Warga transmigran tidak lagi memiliki akun. Pengaduan dibuka sebagai kanal publik tanpa login agar warga tetap dapat melapor, cukup mengisi nama dan kontak |
| 30 | `user` menyimpan `transmigran_id` untuk akun milik warga | Kolom dicabut | Role Transmigran dan Ketua Poktan dihapus. Seluruh pengguna sistem kini petugas, sehingga tidak ada akun yang perlu ditautkan ke data warga |

### 8.3 Tabel SQL referensi yang tidak dilanjutkan

| Tabel lama | Nasib |
|---|---|
| `pertanian` | Dihapus, tabel perantara tanpa atribut |
| `koordinat_lokasi_sp` | Dilebur ke `satuan_permukiman` |
| `kategori_lahan_sp` | Dilebur ke `lahan` sebagai kolom enum |
| `kategori_lahan` | Dilebur ke `lahan` sebagai kolom enum |
| `lahan_usaha_sp` | Dipecah: atribut lahan ke `lahan`, atribut panen ke `hasil_panen` |
| `lahan_sp` | Diganti nama menjadi `lahan` |
| `daftar_anggota` | Diganti nama menjadi `anggota_poktan` |
| `profil_poktan` | Diganti nama menjadi `poktan` |
| `rumah_sp` | Diganti nama menjadi `rumah` |
| `infrastruktur_pertanian` | Diganti nama menjadi `infrastruktur` |
| `status_penanganan` | Diganti nama menjadi `penanganan_pengaduan` |

---

## 9. Tabel Bawaan Laravel

Selain 37 tabel di atas, Laravel membuat tabel infrastrukturnya sendiri. Tabel ini tidak masuk hitungan ERD dan tidak perlu didokumentasikan di data dictionary.

| Tabel | Fungsi |
|---|---|
| `sessions` | Penyimpanan sesi login |
| `cache`, `cache_locks` | Cache aplikasi |
| `jobs`, `job_batches`, `failed_jobs` | Antrean pekerjaan latar |
| `password_reset_tokens` | **Tidak dipakai.** Digantikan `kode_pemulihan_sandi`, lihat catatan di bawah |
| `migrations` | Riwayat migration |

Tabel `users` bawaan Laravel **diganti** oleh tabel `user` milik sistem ini (bentuk tunggal, mengikuti konvensi bagian 1), dengan menyesuaikan `protected $table` pada model.

**Catatan `password_reset_tokens`.** Tabel bawaan ini tetap tidak dipakai, tetapi bukan lagi karena pemulihan mandiri ditiadakan. Sejak 2026-08-12 sistem menyediakan pemulihan lewat **kode verifikasi enam digit**, bukan tautan sekali klik, sehingga struktur bawaan yang menyimpan token panjang tidak cocok. Sistem memakai tabel sendiri `kode_pemulihan_sandi` yang menyimpan sidik kode, waktu kedaluwarsa, dan hitungan percobaan (`rules.md` Â§14b poin 7 sampai 10).

**Tabel `kode_pemulihan_sandi`.**

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id_kode_pemulihan` | BIGINT PK | |
| `user_id` | BIGINT FK â†’ `user` | Akun sasaran |
| `kode_hash` | VARCHAR(255) | **Sidik kode, bukan kodenya.** Basis data yang bocor tidak boleh langsung memberi jalan masuk |
| `kedaluwarsa_pada` | TIMESTAMP | 15 menit sejak dibuat |
| `percobaan` | TINYINT | Bertambah tiap kode salah dimasukkan, maksimal 5 |
| `dipakai_pada` | TIMESTAMP NULL | Diisi saat kode berhasil dipakai, menjadikannya sekali pakai |
| `created_at` | TIMESTAMP | Dasar penghitungan batas 3 permintaan per jam |

Kode lama milik satu akun wajib dibatalkan ketika kode baru diminta, agar tidak ada dua kode sah beredar bersamaan.

---

## 10. Urutan Migration

Urutan berikut wajib dipatuhi agar foreign key selalu menemukan tabel induknya.

```
1.  provinsi
2.  kabupaten                    (butuh provinsi)
3.  kecamatan                    (butuh kabupaten)
4.  desa                         (butuh kecamatan)
5.  kawasan_transmigrasi         (butuh kabupaten)
6.  satuan
7.  komoditas                    (butuh satuan)
8a. parameter_penilaian_sp
9.  role
10. permission
11. role_permission              (butuh role, permission)
12. user                         (butuh role)
13. kode_pemulihan_sandi         (butuh user)
14. satuan_permukiman            (butuh desa, kawasan_transmigrasi, user)
15. user_satuan_permukiman       (butuh user, satuan_permukiman)
16. inventaris_sp                (butuh satuan_permukiman)
17. fasilitas_sp                 (butuh satuan_permukiman)
18. transmigran                  (butuh satuan_permukiman)
19. rumah                        (butuh satuan_permukiman, transmigran)
20. riwayat_penghunian           (butuh rumah, transmigran)
20a. riwayat_kepala_keluarga     (butuh transmigran)
21. poktan                       (butuh satuan_permukiman, transmigran)
22. anggota_poktan               (butuh poktan, transmigran)
23. lahan                        (butuh transmigran, satuan_permukiman, poktan)
24. dokumen_lahan                (butuh lahan)
25. alsintan                     (butuh poktan, satuan_permukiman)
26. saprotan                     (butuh poktan, satuan_permukiman, satuan, komoditas)
27. komoditas_poktan             (butuh komoditas, poktan)
28. penanaman                    (butuh poktan, komoditas, saprotan)
29. hasil_panen                  (butuh penanaman, poktan, satuan)
30. infrastruktur                (butuh satuan_permukiman, poktan)
31. pengaduan                    (butuh user, satuan_permukiman)
32. penanganan_pengaduan         (butuh pengaduan, user)
33. audit_log                    (butuh user)
34. penilaian_sp                 (butuh satuan_permukiman, user)
```

**Catatan langkah 9 sampai 12:** tabel hak akses dibuat sebelum `user`, karena `user.role_id` menunjuk ke `role`. Tidak ada lagi relasi melingkar antara `user` dan `transmigran`, sebab kolom `transmigran_id` sudah dicabut seiring dihapusnya akun milik warga.

**Catatan langkah 13 dan 14:** `user_satuan_permukiman` diletakkan setelah `satuan_permukiman` karena menaut ke keduanya. Tabel ini hanya terpakai oleh role bercakupan `Per SP`.

**Catatan langkah 5 dan 13:** `kawasan_transmigrasi` hanya bergantung pada `kabupaten`, sehingga dapat dibuat kapan saja setelah langkah 2. Ia diletakkan sebelum `satuan_permukiman` karena SP menaut ke keduanya.

**Sisipan tabel baca (urutan pada `database/transmigrasi.sql`, 2026-09-01):**
`referensi` (setelah `komoditas`, sebelum `parameter_penilaian_sp` yang menautnya) ·
`status_kondisi_sp` (tanpa FK, di mana saja) ·
`anggota_keluarga` (setelah `transmigran`) ·
`rute_aksesibilitas_sp` (setelah `satuan_permukiman`) ·
`alsintan_distribusi` (setelah `alsintan` + `anggota_poktan`) ·
`saprotan_distribusi` (setelah `saprotan` + `poktan`, sebelum `penanaman`) ·
`dokumen_lahan_bidang` (setelah `dokumen_lahan` + `lahan`) ·
`infrastruktur_sp` (setelah `infrastruktur`) ·
`fasilitas_sp_cakupan` (setelah `fasilitas_sp`). Domain Lahan diletakkan **setelah**
Kelembagaan karena `lahan.poktan_id` menaut ke `poktan`.

---

## 11. Data Awal (Seeder)

| Tabel | Isi awal |
|---|---|
| `provinsi` | Nusa Tenggara Timur |
| `kabupaten` | Malaka |
| `kecamatan` | Laen Manen, Malaka Tengah, Wewiku, Rinhat |
| `desa` | Kapitan Meo, Tniumanu (Laen Manen); Harekakae (Malaka Tengah); Weoe (Wewiku); Naet, Weain (Rinhat) |
| `kawasan_transmigrasi` | Kobalima Timur (Kabupaten Malaka) |
| `satuan_permukiman` | 6 SP, seluruhnya di bawah Kawasan Kobalima Timur (lihat tabel di bawah) |

Pemetaan SP terhadap kedua cabang hierarki:

| SP | Desa | Kecamatan | Kawasan |
|---|---|---|---|
| SP Kapitan Meo | Kapitan Meo | Laen Manen | Kobalima Timur |
| SP Tniumanu | Tniumanu | Laen Manen | Kobalima Timur |
| SP Harekakae | Harekakae | Malaka Tengah | Kobalima Timur |
| SP Weoe / Uluk Lubuk | Weoe | Wewiku | Kobalima Timur |
| SP Tualaran | Naet | Rinhat | Kobalima Timur |
| SP Weain | Weain | Rinhat | Kobalima Timur |

Tabel ini memperlihatkan alasan kawasan dipisah dari hierarki administratif: satu kawasan menaungi SP yang tersebar di **4 kecamatan berbeda**.
| `satuan` | Ton (t, 1), Kuintal (kw, 0,1), Kilogram (kg, 0,001) |
| `komoditas` | Jagung (Pangan, satuan Ton, unggulan) sebagai komoditas utama kawasan |
| `permission` | Seluruh kewenangan baku sistem, lihat `data-dictionary.md` Â§13 |
| `role` | 4 role bawaan, lihat tabel di bawah |
| `role_permission` | Pasangan kewenangan untuk keempat role bawaan |
| `user` | Satu akun Admin awal |

**Empat role bawaan.** Dibuat lewat seeder agar sistem langsung dapat dipakai tanpa menyusun kewenangan dari nol. Seluruhnya bertanda `is_bawaan = TRUE` sehingga tidak dapat dihapus, tetapi kewenangannya masih dapat disesuaikan Admin, kecuali role Admin.

| Role | Cakupan data | Ringkasan kewenangan |
|---|---|---|
| **Admin** | Semua | Seluruh kewenangan. **Terkunci**, tidak dapat diubah maupun dihapus |
| **Dinas Transmigrasi** | Semua | Lihat seluruh fitur; tambah dan ubah fitur kependudukan, wilayah, SP, lahan, dan infrastruktur; tangani pengaduan bidang ketransmigrasian |
| **Dinas Pertanian** | Semua | Lihat seluruh fitur; tambah dan ubah fitur poktan, komoditas, panen, alsintan, dan saprotan; tangani pengaduan bidang pertanian |
| **Operator SP** | Per SP | Tambah dan ubah data transmigran, rumah, lahan, dan panen pada SP yang ditugaskan. Tanpa kewenangan hapus, tanpa akses manajemen pengguna dan audit log |

Susunan kewenangan di atas menggantikan matriks tetap pada `rules.md` Â§5.1, yang kini berkedudukan sebagai **acuan konfigurasi awal**, bukan aturan permanen.

Daftar satuan masih menunggu konfirmasi lapangan (`notes.md` Â§4 poin 3); tiga satuan di atas dipakai sebagai nilai awal yang dapat ditambah tanpa mengubah struktur tabel.

---

## 12. Hal yang Masih Terbuka

| # | Pertanyaan | Asumsi sementara |
|---|---|---|
| 0 | Apakah akan ada kawasan transmigrasi yang melintasi lebih dari satu kabupaten? | Diasumsikan tidak; `kawasan_transmigrasi.kabupaten_id` bersifat wajib. Bila kelak terjadi, kabupaten sebenarnya tetap terbaca dari desa milik tiap SP |
| 1 | Apakah lahan pekarangan bisa lebih dari satu per KK? | Struktur dibuat one-to-many agar fleksibel; bila ternyata selalu satu, cukup tambah validasi di sisi aplikasi tanpa mengubah skema |
| 2 | Rumah yang ditinggalkan sementara: tetap Dihuni atau dilepas jadi kosong? | Tetap `Dihuni` dengan penghuni terdaftar; kepindahan sementara dicatat pada `rumah.catatan_hunian` |
| 3 | Daftar satuan final per komoditas | Ton, Kuintal, Kilogram sebagai nilai awal |
| 4 | Apakah satu transmigran bisa masuk lebih dari satu poktan? | Diasumsikan tidak; `rules.md` Â§6.4 menyatakan satu transmigran menjadi anggota satu kelompok tani |
