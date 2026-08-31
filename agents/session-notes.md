# Putaran 9 — Formatter Nominal Rupiah, Scope Poktan Transmigran, Peniadaan Filter Tab Tahun Rekap, & Urutan Field Form Lahan/Rumah SELESAI (2026-08-31)

Rencana: `C:\Users\v28mt\.gemini\antigravity-cli\brain\0fc4a1ba-ef33-4a8c-b8ea-138571694790\plan_dokumentasi_seluruh_revisi.md` & `walkthrough.md`.
Catatan hasil: `agents/notes.md` `## 6. Revisi` (butir 6). Ringkasan: `agents/tasklist.md`, `agents/rules.md`, `agents/ui-spec.md`.

## 4 Pekerjaan yang Telah Diselesaikan:

### 1. Reusable Currency Formatter Nominal Rupiah (`x-uang`)
- **Modul Sentral:** Membangun `resources/js/format-uang.js` dengan fungsi `bersihkanUang`, `formatUang`, `hitungPosisiKursor`, `pasangFormatUang(Alpine)`, serta mengekspos ke `window.formatUang`.
- **Interaksi & Sanitasi:** Pemisah ribuan titik murni (`1.000.000`), pengetikan karakter ilegal (huruf/minus/notasi) ditolak, paste teks dinormalisasi, navigasi kursor stabil, dan dukungan keyboard numerik mobile (`type="text" inputmode="numeric"`).
- **Programmatic & Submit Handling:** Intersepsi prototype descriptor `HTMLInputElement.value` untuk mencegah rekursi ganda pada modal ubah / Alpine `x-model`, serta global form `submit` capture listener yang menormalkan nilai `input[data-uang]` menjadi string integer murni (`1000000`) sebelum dikirim ke backend Laravel `ValidationRules::uang()`.
- **Implementasi Lapangan:**
  - Form Transmigran (`pendapatan_per_bulan` KK & repeater anggota keluarga).
  - Form Panen (`harga_jual`).
  - Form SP (`rute_aksesibilitas[*][ongkos_rp]`).

### 2. Peniadaan Card Filter Tahun Data pada Tab "Per Tahun" (Rekap Kependudukan)
- Menghilangkan card formulir filter Tahun Data khusus ketika tab "Per Tahun" aktif pada `/kependudukan/rekap` (`@if ($kelompok !== 'tahun') ... @endif`).
- Tab "Per Tahun" kini bersih langsung menyajikan tabel agregat deret waktu historis (2016–2026) tanpa kontrol dropdown yang tidak fungsional/mati, sementara filter tahun tetap aktif di 5 tab demografis lainnya (`sp`, `status`, `pekerjaan`, `asal`, `pendidikan`).

### 3. Penegasan Ruang Lingkup Anggota Poktan Khusus Transmigran
- Menegaskan batasan domain SIM Transmigrasi bahwa sistem **hanya mencatat anggota poktan yang merupakan warga/keluarga transmigran**, sedangkan anggota non-transmigran (penduduk lokal) tidak dicatat di sistem.
- **Titik Penegasan di Rincian Poktan (`pages/poktan/detail.blade.php`):**
  - Subjudul header: `"Kelompok tani di [SP], berdiri sejak [Tahun]. Pencatatan anggota khusus warga transmigran."`
  - Sidebar Profil: `Anggota transmigran aktif` dengan catatan kaki `Khusus warga transmigran`.
  - Tab Rincian: `Anggota Transmigran (n)`.
  - Banner Edukatif: Callout di atas tabel anggota menerangkan batasan ruang lingkup data.
  - Judul Tabel: `Anggota Kelompok Tani (Khusus Warga Transmigran)`.
- **Form Anggota (`pages/poktan/form-anggota.blade.php`):** Bantuan isian menegaskan bahwa anggota non-transmigran tidak didata pada SIM Transmigrasi.

### 4. Penyesuaian Urutan Field Transmigran & Auto-Fill Satuan Permukiman
- **Form Lahan (`pages/lahan/form.blade.php`):** Field `Pemilik` (transmigran) dipindahkan ke urutan pertama Section 1 sebelum `Satuan Permukiman`. Memilih Pemilik otomatis mengisi dan memilih dropdown Satuan Permukiman via Alpine reactive event.
- **Form Rumah (`pages/rumah/form.blade.php`):** Section 1 disusun menjadi `Penghunian & Wilayah` sebelum Section 2 `Spesifikasi Bangunan`. Saat status `Dihuni`, memilih KK Penghuni otomatis mengisi Satuan Permukiman. Saat status `Tidak Dihuni`, isian KK dinonaktifkan (`disabled`) dan pemilihan SP menjadi aktif manual.
- **Perbaikan Komponen (`resources/views/components/sim/pilih-cari.blade.php`):** Binding `:disabled` dan `:required` dirender dengan `{!! !!}` agar ekspresi JavaScript Alpine berkarakter petik tidak ter-escape menjadi HTML entity `&#039;`.

## Verifikasi
- **Pest PHP:** 523 test (3.363 assertions) lulus 100% hijau.
- **Browser Tests:** `uji-autofill-sp.mjs` (5/5 PASS), `uji-format-uang.mjs` (11/11 PASS).
- **Vite Build:** `npm run build` terkompilasi bersih tanpa galat.

---

# Putaran 8 — Visualisasi Dashboard, Optimasi Interaksi & Audit Warna SELESAI (2026-08-31)

Rencana: `C:\Users\v28mt\.gemini\antigravity-cli\brain\9333f50b-2b4d-449a-ab28-563179b31500\implementation_plan.md` & `walkthrough.md`.
Catatan hasil: `agents/notes.md` `## 6. Revisi`. Ringkasan: `agents/tasklist.md` & `agents/ui-spec.md` §9.

## 3 Pekerjaan Dashboard yang Telah Diselesaikan:

### 1. Perombakan Visualisasi "Ringkasan Kawasan" Dashboard
- Mengubah 12 kartu KPI datar menjadi **3 Pilar Domain Tematik Terstruktur**:
  1. **Pilar 1: Kependudukan & Hunian** (Navy `#163B54` / Blue-light) — 4.560 Jiwa, 1.140 KK, 2.280 Petani, Kapasitas Hunian 95% (1.140 / 1.200 unit) dengan bar visual.
  2. **Pilar 2: Lahan & Siklus Tanam** (Teal `#33809C` / Emerald) — 3.250 ha Total Kawasan, 1.140 ha Lahan Tergarap (35,08%), Siklus Tanam (630 ha Tanam $\rightarrow$ 24,60 ha Puso [3,9%] $\rightarrow$ 605,40 ha Panen [96,1%]) dengan stacked progress bar.
  3. **Pilar 3: Produksi & Nilai Pasar** (Gold `#C09546` / Sand `#DFB87E`) — 1.781 ton Produksi, 2,94 ton/ha Produktivitas Tertimbang, Rp 7,12 Miliar Estimasi Nilai Pasar, Jagung 65% Komoditas Unggulan.
- **Dual Y-Axis pada `#grafikPerSp`**: Memisahkan sumbu Jiwa (kiri) dan Luas Lahan ha (kanan) agar garis luas lahan tidak lagi tenggelam di dasar kanvas.
- **Smooth Area Chart pada `#grafikPendapatan`**: Visualisasi kurva pendapatan keluarga transmigran bernuansa area gradien lembut.

### 2. Optimasi Interaksi Scrolling & Touch Chart (Opsi C: Hybrid Responsive Gesture Model)
- **Prinsip UX:** `SCROLLING HALAMAN > INTERAKSI CHART`.
- **Desktop (`hover: hover`):** Hover mouse instan melihat tooltip (*zero-click friction*), mematikan internal zoom & selection listener di `opsiDasar()` (`chart-config.js`), `tooltip.followCursor: false` untuk mencegah lonjakan komputasi CPU saat scrolling cepat.
- **Mobile (`pointer: coarse` / Touchscreen):** Penegakan CSS native `touch-action: pan-y !important;` pada `.apexcharts-canvas`, `.apexcharts-svg`, `.apexcharts-inner`, `.apexcharts-grid-rect` di `app.css` dan utility `touch-pan-y` di `chart-card.blade.php`. Gestur *swipe* vertikal 100% diproses browser untuk menggulir halaman tanpa tersendat; *tap* singkat untuk mengunci tooltip titik data.
- **Konsistensi:** Diterapkan terpusat pada shared helper, mengamankan `#grafikPenduduk`, `#grafikPendapatan`, `#grafikHarga`, dan seluruh grafik lainnya.

### 3. Audit & Optimalisasi Warna Visualisasi Dashboard (Palet Kategorikal & Semantik)
- **Palet Komoditas Pertanian Khusus (`warnaKomoditas`)**: Memecahkan masalah irisan biru kembar pada Donut `#grafikKomoditas` (*Jagung* `#C09546` Gold, *Padi* `#2E7D32` Green, *Kacang Tanah* `#8E6E34` Bronze, *Ubi Kayu* `#DFB87E` Sand, *Cabai* `#D94841` Terracotta Red).
- **Palet Semantik Pengaduan (`warnaStatusPengaduan`)**: Menyelaraskan donat `#grafikStatusPengaduan` dengan status-badge sistem (*Diterima* Amber `#F79009`, *Diproses* Sky Blue `#0BA5EC`, *Selesai* Emerald `#12B76A`).
- **Pemisahan Kontras Dual-Bar Perbandingan SP (`#grafikPerSp`)**: Batang KK (Navy `#163B54`) vs Batang Panen (Gold `#C09546`).
- **Kontras Multi-Series Line Penduduk (`#grafikPenduduk`)**: Jiwa (Navy `#163B54`), KK (Sky Blue `#0BA5EC`), Petani (Gold `#C09546`).

## Verifikasi
- **Pest PHP:** 14 test Dashboard (75 assertions), 77 test DummyData (1.626 assertions) lulus 100% hijau.
- **Vite Build:** `npm run build` terkompilasi bersih tanpa error (`exit code 0`).

---

# Putaran 7 & 7 Poin Revisi Frontend SELESAI (2026-08-30)

Pola "induk + distribusi" untuk Alsintan, Saprotan, +3 temuan audit, +F2, dan 7 Poin Revisi Frontend.
Rencana: `C:\Users\v28mt\.claude\plans\linked-sprouting-aho.md` & Implementation Plan.
Catatan hasil: `agents/notes.md` §1w & `## 6. Revisi`. Ringkasan: `agents/tasklist.md`.

## 7 Poin Revisi Frontend & Perbaikan UI Scrollbar (Selesai):
1. **Revisi 1 (Detail Transmigran Scrollbar & Tab Cleanliness)**:
   - Mengubah CSS Grid kolom kanan menjadi `lg:grid-cols-[20rem_minmax(0,1fr)]` dan menyematkan `min-w-0 overflow-hidden` pada wadah kartu tab (`div.rounded-2xl`) serta panel tabel untuk mengisolasi pelebaran tabel 11 kolom dan mencegah bocornya *body horizontal scrollbar*.
   - Menghilangkan horizontal scrollbar native pada deretan Tab Header dengan menyematkan utility class `no-scrollbar` (`scrollbar-width: none` / `::-webkit-scrollbar: display: none`) di Detail Transmigran serta menstandarkannya di seluruh halaman bertab lainnya.
   - Mempertahankan 100% font size, caption, thead/tbody, dan styling asli tabel Anggota Keluarga, di mana scrollbar horizontal hanya terjadi secara lokal di dalam kartu tabel.
2. **Revisi 2 (Multi-Step Form)**:
   - Form Data Lahan: 3 langkah (*Identitas & Pemilik*, *Penggunaan & Lokasi*, *Legalitas & Berkas*).
   - Form SP: 4 langkah (*Identitas & Wilayah*, *Lokasi & Batas*, *Keadaan Alam & Iklim*, *Aksesibilitas & Berkas*).
   - Form Poktan: 3 langkah (*Identitas Kelompok*, *Pengurus & Legalitas*, *Anggota Kelompok Tani* + Dynamic Repeater).
3. **Revisi 3 (Master Data Jenis Inventaris)**: Penambahan `JenisReferensi::JenisInventaris` dengan opsi baku (*Peralatan Kantor*, *Elektronik & Mesin*, *Perabotan*, *Kendaraan Operasional*, *Peralatan Lainnya*), suplai view provider, dan dropdown wajib.
4. **Revisi 4 (Urutan Parent Form & Rename Data Lahan)**:
   - Parent SP diletakkan di urutan teratas pada `form-inventaris`, `form-fasilitas`, `infrastruktur/form`, `rumah/form`, `lahan/form`, `poktan/form`.
   - Rename menu & breadcrumb "Daftar Lahan" $\rightarrow$ "Data Lahan".
5. **Revisi 5 (Wording Form Transmigran)**: Label "Status Tinggal" $\rightarrow$ "Status Tinggal Keluarga".
6. **Revisi 6 (Upload KK Wajib)**: Upload Kartu Keluarga dijadikan mandatory (`:wajib="true"`) dengan label "Kartu Keluarga (KK)".
7. **Revisi 7 (Wording Subjudul Poktan)**: Subjudul halaman Poktan $\rightarrow$ "...beserta ketua dan jumlah anggota transmigrannya."

