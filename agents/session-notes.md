# Rencana Eksekusi — Putaran 4: Submenu Laporan + Form Transmigran Bertahap

Ditulis 2026-08-29 sesuai `rules.md` §20b. Sementara, boleh ditimpa.
E3 (dokumen) SUDAH selesai + commit `03558ff`.

## Lingkup

1. **E1 — Submenu Laporan**: urut ulang, dua nama diganti, "Semua Laporan"
   + halaman `/laporan` dihapus seluruhnya. Nama laporan disatukan ke
   `LaporanData::meta()`.
2. **E2 — Form transmigran bertahap**: `x-sim.modal-form` diberi prop
   `langkah` (opsional, komponen dipakai ulang); form transmigran dipecah
   4 langkah.

## Keputusan pemilik proyek

| # | Keputusan |
|---|---|
| 1 | Halaman `/laporan` dihapus seluruhnya, bukan hanya butir menunya |
| 2 | Form transmigran 4 langkah: Identitas / Penempatan / Anggota Keluarga / Berkas |
| 3 | Komponen dipakai ulang; putaran ini hanya dipasang pada form transmigran |

## E1 — urutan + nama baru

| # | slug (TAK berubah) | judul baru |
|---|---|---|
| 1 | indikator-kawasan | Rekap Indikator Kawasan |
| 2 | monografi-sp | Laporan Monografi SP |
| 3 | transmigran | **Laporan Transmigran** (dulu "Laporan Daftar Transmigran") |
| 4 | poktan | **Laporan Poktan** (dulu "Laporan Daftar Poktan") |
| 5 | alsintan | Laporan Alsintan |
| 6 | saprotan | Laporan Saprotan |
| 7 | hasil-panen | Laporan Hasil Panen |

**Nama disatukan:** `LaporanData::meta()` menambah `judul` + `izin`; urutan
lariknya = urutan submenu. `MenuHelper` membangun `subItems` Laporan dari
`meta()`. `routes/web.php` menurunkan `$daftarLaporan` dari `meta()`.
`kerangka-laporan` baca judul dari `meta()` langsung.

**Bongkar `/laporan`:** butir menu `MenuHelper.php:202-206`, rute
`laporan.index` (`web.php`), berkas `pages/laporan/index.blade.php`, tombol
"Kembali ke Semua Laporan" (`kerangka-laporan`). `RemahHelper` tak terdampak.
`DaftarTautanStatis` menyesuaikan sendiri: 223 → **222** alamat.

**Uji:** `HalamanTest.php` — `:4467` `get('/laporan')->assertOk()` dicabut;
`:4478` `toContain('/laporan')` dicabut; `:4502-4510` dataset dua nama
diperbarui; `:4660`/`:4673` judul uji diselaraskan; `:4748` penjaga kosong
diganti. **Penjaga baru:** urutan submenu Laporan `toBe([...])` (meniru
`PengaturanPenilaianTest.php:221`); nama laporan hanya dari `meta()`;
`/laporan` → 404.

## E2 — form bertahap

**`modal-form` prop `:langkah="[...]"`** (tanpa prop = perilaku lama):
- Penunjuk langkah di kepala (angka BERTEKS, meniru `modal-impor:173-198`).
- Kaki menyesuaikan: Batal/Kembali kiri; Lanjut kanan; Simpan hanya langkah
  terakhir (meniru `modal-impor:366-391`).
- `buka()` reset `langkah = 1`.
- State per modal (halaman `/transmigran` punya DUA salinan form).

**Validasi:**
- Lanjut → `checkValidity()` isian di wadah langkah ini saja; gagal →
  `reportValidity()` + fokus, jangan maju.
- Simpan → periksa seluruh form; ada yang gagal → **LOMPAT ke langkah
  pemuatnya** lalu `reportValidity()`.
- Isian wajib: `:required="langkah === n"`. **TANPA `:disabled`** (nilai
  harus terkirim; `isiFormulir()` mengisi lewat `name`). Bintang tetap statis.

**Pembagian:**
| Langkah | Isi |
|---|---|
| 1 Identitas | Bagian 1 sekarang + pekerjaan & pendapatan dari Bagian 3 |
| 2 Penempatan | Bagian 2 sekarang |
| 3 Anggota Keluarga | Bagian 4 sekarang (repeater utuh) |
| 4 Berkas | Catatan (Bagian 3) + Dokumen Pendukung (Bagian 5) |

`nama_kepala_keluarga` tetap isian pertama DOM di langkah 1 (`buka()` fokus,
`#tambah_nama` uji peramban).

**Jebakan (dari notes.md):**
- `required` tersembunyi → form menolak diam-diam. Repo kena 3x (1877, 2197,
  2299). Karena itu Simpan melompat ke langkah, bukan menolak.
- `uji-gulir-modal.mjs:282` pakai form transmigran sebagai "Modal form
  panjang" dan menuntut isinya > layar. Dipindah ke form SP (22 isian,
  tak dipecah).

## Berkas

Kode: `LaporanData.php`, `MenuHelper.php`, `routes/web.php`,
`components/sim/modal-form.blade.php`, `components/sim/kerangka-laporan.blade.php`,
`pages/transmigran/form.blade.php`. Hapus `pages/laporan/index.blade.php`.
Uji: `HalamanTest.php`, `uji-gulir-modal.mjs`, `uji-form-transmigran.mjs` (baru,
PORT 9351).

## Verifikasi

1. `pest.bat` hijau, naik dari 678
2. `pint.bat --test` ≤ 31
3. `sim:tautan-statis` **222**, lolos regex `deploy.yml:110`
4. Render 7 `/laporan/*` + `/laporan/*/dokumen` = 200; `/laporan` = 404
5. `npm run build`; `uji-lebar-dokumen.mjs` tetap hijau
6. `uji-gulir-modal.mjs` hijau setelah kasus dipindah
7. `uji-form-transmigran.mjs` hijau (termasuk: Simpan dengan isian langkah 1
   kosong → modal LOMPAT ke langkah 1, bukan menolak diam-diam)
8. Modal Tambah + modal Ubah `/transmigran`: langkah masing-masing sendiri
