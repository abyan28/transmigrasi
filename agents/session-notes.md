# Rencana Eksekusi — Rombongan C: Field Keadaan Wilayah SP (Bab II Monografi)

Ditulis 2026-08-28 sesuai `rules.md` §20b. Sementara, boleh ditimpa.

## Lingkup (dari `notes.md` bagian 6)

> "untuk halaman Satuan Pemukiman bakal ada tambahan kolom/field form baru...
> berdasarkan Bab II Keadaan Wilayah pada Laporan Monografi di folder refs...
> nanti datanya ini mau disajikan sebagai sebuah laporan"

Menambah field "Keadaan Wilayah" pada modul SP, lalu Laporan Monografi SP
(dibuat Tahap 2c, kini baru satu baris indikator) memakainya untuk bagian
Bab II yang sesungguhnya.

## Isi Bab II Monografi (terbaca via antiword)

| # | Sub-bagian | Isi contoh (Kapitan Meo) | Rencana field |
|---|---|---|---|
| 1.1 | Letak Administrasi | Desa/Kec/Kab/Prov | SUDAH ADA (desa_id rantai) |
| 1.2 | Letak Astronomis | "9°24'17"-9°25'17" LS, 9°24'40"-9°25'1" BT" | `letak_astronomis` teks |
| 1.3 | Letak Ekonomis | jarak ke IbuKota Kec 2 / Kab 22 / Prov 245 Km | 3 kolom DECIMAL |
| 2.1 | Batas Alam | Utara Desa Tesa, dst | 4 kolom `batas_*` (DIHIDUPKAN, lihat catatan) |
| 2.2 | Aksesibilitas (Tabel 2.1) | 5 baris x 8 kolom rute perjalanan | DITUNDA - butir konfirmasi |
| 3.1 | Luas + SK Pencadangan | 1.500 Ha, SK 79/HK/2018 13 Feb 2018 | luas SUDAH ADA; + `nomor_sk_pencadangan` + `tanggal_sk_pencadangan` |
| 3.2 | Bentuk / Pola Permukiman | "Pola Konsentris" | enum `PolaPermukiman` |
| 4 | Tanah | subur, pH 7,01-7,69 | enum `TingkatKesuburanTanah` + `ph_tanah` teks |
| 5 | Topografi | Datar, kemiringan 8-15% | enum `BentukWilayah` + `kemiringan_lereng` teks |
| 6 | Iklim | curah hujan 1.607 mm/th; suhu 23-31°C rata 27,7; angin 4-4,6 knot; penyinaran 55,6% | `curah_hujan_mm_per_tahun` DECIMAL; `suhu_udara` teks; `kecepatan_angin` teks; `penyinaran_matahari` teks |
| 7 | Sumberdaya Air | air bersih: perpipaan+mata air; air tani: hujan+embung | `sumber_air_bersih` teks; `sumber_air_pertanian` teks |

## CATATAN: batas wilayah dihidupkan kembali

Empat kolom `batas_utara/timur/selatan/barat` **dicabut 2026-08-18** dengan
alasan panjang: isinya sebutan naratif, tak dipakai perhitungan/indikator/peta
mana pun, hanya menyalin berkas penetapan. Catatan pencabutan sendiri
menuliskan jalan menghidupkannya:

> "bila dinas memerlukannya, jalan mengembalikannya jelas: tambahkan kembali
> 4 kolom pada kamus data 3.6, satu bagian pada sp/form, dan satu blok
> tampilan pada dashboard/sp."

Sekarang dinas MEMERLUKANNYA - Bab II Monografi memuatnya. Ini pembalikan
berjejak: `data-dictionary.md` §3.6, `sp/form`, dan `notes.md` bagian 6 butir
pertama disunting; alasan pencabutan dipertahankan sebagai riwayat. `rules.md`
4a.4, `prd.md`, `workflow.md` yang dahulu disunting agar tidak menjanjikan
batas wilayah - dikembalikan.

## Rancangan data (Tahap 2 = DummyData; skema untuk Tahap 3)

Field baru pada `satuan_permukiman` (semua NULLABLE, dokumenter):

| Kolom | Tipe | Ket |
|---|---|---|
| `letak_astronomis` | VARCHAR(255) | rentang LS/BT apa adanya dari berkas |
| `jarak_ke_kecamatan_km` | DECIMAL(6,1) | |
| `jarak_ke_kabupaten_km` | DECIMAL(6,1) | |
| `jarak_ke_provinsi_km` | DECIMAL(6,1) | |
| `batas_utara` | VARCHAR(150) | dihidupkan |
| `batas_timur` | VARCHAR(150) | dihidupkan |
| `batas_selatan` | VARCHAR(150) | dihidupkan |
| `batas_barat` | VARCHAR(150) | dihidupkan |
| `nomor_sk_pencadangan` | VARCHAR(100) | SK Pencadangan Areal SP |
| `tanggal_sk_pencadangan` | DATE | |
| `pola_permukiman` | ENUM | §11.x baru |
| `tingkat_kesuburan_tanah` | ENUM | §11.x baru |
| `ph_tanah` | VARCHAR(40) | kisaran, mis. "7,01 - 7,69" |
| `bentuk_wilayah` | ENUM | §11.x baru |
| `kemiringan_lereng` | VARCHAR(40) | mis. "8 - 15%" |
| `curah_hujan_mm_per_tahun` | DECIMAL(8,2) | rata-rata tahunan |
| `suhu_udara` | VARCHAR(80) | mis. "23 - 31 C, rata-rata 27,7 C" |
| `kecepatan_angin` | VARCHAR(80) | |
| `penyinaran_matahari` | VARCHAR(80) | |
| `sumber_air_bersih` | VARCHAR(255) | |
| `sumber_air_pertanian` | VARCHAR(255) | |

Enum baru:
- `PolaPermukiman`: Konsentris, Grid/Papan Catur, Linear/Memanjang, Menyebar
- `TingkatKesuburanTanah`: Subur, Sedang, Kurang Subur
- `BentukWilayah`: Datar, Bergelombang, Berbukit, Bergunung

## Berkas yang disentuh

**Dokumen**
- `agents/data-dictionary.md` §3.6 (kolom baru + catatan batas), §11.x (3 enum)
- `agents/rules.md` §4 (aturan SP), §4a.4 (batas dikembalikan)
- `agents/erd.md` (bila ada catatan batas)
- `agents/notes.md` bagian 6 butir batas + butir Rombongan C
- `agents/prd.md`, `agents/workflow.md` (bila menyebut batas dicabut)

**Kode**
- `app/Enums/{PolaPermukiman,TingkatKesuburanTanah,BentukWilayah}.php`
- `app/Support/DummyData.php` - `satuanPermukiman()` isi 20 field x 6 SP
- `app/Providers/ViewServiceProvider.php` - kunci opsi enum untuk `sp.form`
- `resources/views/pages/sp/form.blade.php` - section "Keadaan Wilayah"
- `resources/views/pages/sp/detail.blade.php` / `dashboard/sp` - tab/blok tampil
- `app/Support/LaporanData.php` - `monografiSp()` sertakan field Bab II
- `resources/views/pages/laporan/monografi-sp.blade.php` - render Bab II
- `tests/Feature/HalamanTest.php` - penjaga

## Penjaga uji (rencana)
- Tiap field baru punya tempat tampil di rincian SP (penjaga 1f otomatis)
- Batas wilayah hidup kembali: 4 `name="batas_*"` di form, tampil di rincian
- `notes.md` bagian 6 butir pertama tak lagi menyatakan batas "dihapus sepenuhnya"
- Laporan Monografi SP memuat sub-judul Bab II (Letak, Batas, Iklim, dst)
- 3 enum baru dikunci

## Verifikasi
1. `vendor/bin/pest.bat` hijau, >= 657
2. `vendor/bin/pint.bat --test` <= 31
3. Render nyata `/sp`, `/sp/1` (atau `/dashboard/sp/1`), form tambah+ubah,
   `/laporan/monografi-sp`
4. Catat ke `notes.md` 1q + `tasklist.md`

## Keputusan pemilik proyek (2026-08-28)

1. **Batas wilayah dihidupkan kembali** (4 kolom `batas_*`).
2. **Rentang dipecah jadi min/maks numerik**, bukan teks:
   - astronomis -> kotak `lintang_utara`/`lintang_selatan`/`bujur_barat`/`bujur_timur` (DECIMAL 10,7)
   - pH -> `ph_tanah_min`/`ph_tanah_maks` (DECIMAL 4,2)
   - suhu -> `suhu_min_c`/`suhu_maks_c`/`suhu_rata_c` (DECIMAL 4,1)
   - angin -> `angin_min_knot`/`angin_maks_knot`/`angin_rata_knot` (DECIMAL 4,1)
   - kemiringan -> `kemiringan_min_persen`/`kemiringan_maks_persen` (DECIMAL 5,2)
   - penyinaran -> `penyinaran_min_persen`/`penyinaran_maks_persen`/`penyinaran_rata_persen` (DECIMAL 5,2)
   - curah hujan -> `curah_hujan_tahunan_mm` (8,2), `curah_hujan_bulan_min_mm`/`curah_hujan_bulan_maks_mm` (7,2)
3. **Tabel 2.1 Aksesibilitas dikerjakan sekarang** - tabel `rute_aksesibilitas_sp` + repeater pada form SP.

## Pelaksanaan bertahap

- **C1 SELESAI** (commit) - 3 enum, ~35 kolom, batas dihidupkan, DummyData
  6 SP, section form SP, blok tampil `dashboard/sp`. 660 uji, pint 31.
  Bug uji `->toContain($a,$b)` (multi-needle) ikut dibetulkan di 2 tempat.
- **C2** - tabel `rute_aksesibilitas_sp` + repeater form SP + tampil. Commit.
- **C3** - Laporan Monografi SP: render Bab II penuh per SP. Commit.

`data-dictionary.md` §3.6, `rules.md` §4a.4, `notes.md` bagian 6 butir batas
disunting di C1 (pembalikan berjejak, alasan pencabutan dipertahankan).
