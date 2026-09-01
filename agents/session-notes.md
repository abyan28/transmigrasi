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

---

# Restrukturisasi Halaman Detail Satuan Permukiman (SP) & Rute RESTful (2026-08-31)

### 1. Standardisasi Rute RESTful
- Mengubah rute rincian SP dari `/dashboard/sp/{id}` menjadi `Route::get('/sp/{sp}', ...)->where('sp', '[0-9]+')->name('sp.detail')`.
- Memasang redirect 301 dari `/dashboard/sp/{id}` ke `/sp/{id}` untuk menjamin tautan lama dan penelusuran tetap bekerja.
- Menambahkan rute pembaruan data SP `Route::put('/sp/{sp}', ...)->name('sp.perbarui')`.
- Memperbarui 16 berkas view yang memanggil `route('dashboard.sp', ...)` menjadi `route('sp.detail', ...)`.

### 2. Restrukturisasi UI & Sistem 6 Tab Domain
- Memindahkan view ke `resources/views/pages/sp/detail.blade.php` dengan grid 2-kolom asimetris `lg:grid-cols-[20rem_minmax(0,1fr)]`.
- **Kolom Kiri (Sticky Sidebar):** Profil SP, kode SP, kecamatan/desa, tahun penempatan, luas lahan, status kondisi SP & skor kelayakan, kapasitas/keterisian KK, dokumen SK penetapan, catatan/keterangan wilayah, dan peta mini titik koordinat Leaflet OSM.
- **Kolom Kanan (6 Tab Domain Terpadu via `hashTabs()`):**
  1. `ringkasan`: 4 Stat Cards KPI (Skor Kelayakan, Jumlah KK, Realisasi Lahan Usaha, Total Produksi Panen), 2 ApexCharts (Tren Kependudukan KK & Volume Panen per Tahun), dan rincian 16 Parameter Layanan Dasar SP.
  2. `warga`: Tabel Warga Transmigran / KK dan Tabel Rumah & Hunian.
  3. `pertanian`: Tabel Bidang Lahan, Kelompok Tani (Poktan), dan Catatan Hasil Panen.
  4. `aset`: Tabel Infrastruktur Kawasan, Fasilitas Umum SP, dan Inventaris Operasional SP.
  5. `pengaduan`: Tabel Pengaduan Masuk SP beserta status & prioritasnya.
  6. `monografi`: Profil Geografis, Topografi, Tanah, Iklim Bab II Monografi SP & Tabel Rute Aksesibilitas.
- **Eliminasi Bar Switcher SP:** Bilah navigasi switcher 6 SP di atas halaman dihapus atas arahan user; navigasi antar-SP dilakukan melalui `/sp` atau breadcrumb.
- **Header Action:** Menambahkan tombol primer *"Ubah Data SP"* (membuka modal `formUbahSp`) dan tombol sekunder *"Kembali ke Daftar SP"* (`route('sp.index')`).

### 3. Verifikasi Mutu
- **Pest PHP:** 726 pengujian (6.110 assertions) 100% PASS (Hijau).
- **Vite Build:** `npm run build` terkompilasi bersih tanpa galat.
- **HTTP Endpoint:** `/sp/1` membalas 200 OK, `/dashboard/sp/1` membalas 301 Redirect ke `/sp/1`.


# Audit Menyeluruh Frontend, Phase A (Quick Wins UX/a11y), & Standardisasi Rute `/sp/infrastruktur` (2026-08-31)

### 1. Audit Menyeluruh Frontend
- Melakukan audit 24 tahap pada seluruh arsitektur antarmuka, 96 berkas tampilan Blade, 35 komponen sim, 8 berkas JavaScript, dan integrasi backend-readiness.
- Menyusun laporan audit komprehensif pada artefak `audit_komprehensif_seluruh_frontend.md`.

### 2. Standardisasi Rute RESTful `/sp/infrastruktur`
- Mengubah rute utama menjadi `/sp/infrastruktur` dan `/sp/infrastruktur/{id}` (`name: infrastruktur.index`, `infrastruktur.detail`, `infrastruktur.simpan`, `infrastruktur.perbarui`, `infrastruktur.hapus`).
- Memasang pengalihan permanen (HTTP 301) dari `/infrastruktur` dan `/infrastruktur/{id}` ke `/sp/infrastruktur`.
- Memperbarui `MenuHelper.php`, `RemahHelper.php`, dan view `pages/infrastruktur/*` agar seluruh aset wilayah (`/sp/inventaris`, `/sp/fasilitas`, `/sp/infrastruktur`) memiliki struktur rute simetris.

### 3. Eksekusi Phase A (Quick Wins UX & a11y)
- **A11y Live Regions (`aria-live="polite"`, `aria-atomic="true"`):** Dipasang pada field kalkulasi dinamis (Total Lahan Usaha di `pages/lahan/form.blade.php`, Puso & Produksi di `pages/panen/form.blade.php`, Usia KK di `pages/transmigran/form.blade.php`, dan Sisa Belum Ditanam di `pages/penanaman/form.blade.php`).
- **Indikator Visual Filter Aktif:** Menambahkan aksen latar & border (`border-brand-500 bg-brand-50 text-brand-700`) beserta dot badge pada komponen `x-sim.data-table` ketika ada filter aktif (`adaFilterAktif`).
- **Pembatalan Banner Panduan Rekap:** Banner panduan dokumen resmi pada `/panen/rekap` dan `/kependudukan/rekap` dibatalkan/dihapus sesuai arahan user demi menjaga kelapangan ruang vertikal layar dan kemurnian tata letak tabel agregat (ui-spec.md §2.2).

### 4. Verifikasi Mutu
- **Pest PHP:** 728 pengujian (6.120 assertions) **100% PASS (Hijau)**.
- **Vite Build:** `npm run build` sukses bersih.
- **HTTP Endpoint:** `/sp/infrastruktur` membalas 200 OK, `/infrastruktur` membalas 301 Redirect.


# Revisi Komprehensif UX/UI Menu Laporan Transmigran (2026-09-01)

### 1. Implementasi 4 Mode Tampilan Interaktif
- **Mode Gabungan (Terpadu / Alternatif 1):** Menggabungkan data Transmigran + Rumah + Lahan ke dalam satu tabel komprehensif berorientasi Kepala Keluarga dengan Multi-Level Grouped Header dan Sub-cell Stack untuk multi-bidang lahan.
- **Mode Data Transmigran:** Menampilkan rincian demografi 14 kolom lengkap Kepala Keluarga transmigran.
- **Mode Data Rumah:** Menampilkan inventarisasi fisik rumah, nomor rumah, penghuni, kondisi bangunan, status hunian, tahun bangun, dan luas bangunan.
- **Mode Data Lahan:** Menampilkan inventarisasi seluruh bidang lahan pekarangan dan usaha dengan komposisi luas kering/basah dan pola tanam.
- **Pill Tab Selector:** Disediakan bilah navigasi mode di bagian atas tabel dengan deskripsi peran masing-masing mode.

### 2. Penyaring Cerdas & Pencarian Kata Kunci Sisi Klien
- **Pencarian Kata Kunci Instan (`cari` / `q`):** Menambahkan input pencarian teks bebas di bilah filter laporan yang mencocokkan `data-cari` (Nama KK, NIK, No KK, no rumah, kode lahan).
- **Filter Kondisional Per Mode:** Dimensi filter ditampilkan secara kontekstual sesuai mode yang aktif (`statusHunian` & `kondisi` di Mode Rumah, `peruntukan` di Mode Lahan, `status` & `tahun` di Mode Gabungan dan Transmigran).
- **Dimensi Baru Laporan Data:** Menambahkan opsi dimensi `statusHunian`, `kondisi`, dan `peruntukan` pada `LaporanData::filterLaporan('transmigran')`.

### 3. Hierarki Visual & Kontainer Scroll Responsif
- Membungkus setiap tabel dalam `overflow-x-auto rounded-2xl border` agar scrollbar horizontal hanya berada pada container tabel saat layar menyempit.
- Tipografi terstruktur, penggunaan `tabular-nums` untuk angka/NIK/luas, dan badge status semantik (Aktif, Pindah, Kondisi Rumah, Status Hunian).

### 4. Pembersihan Dokumen Resmi Sesuai Mode Terpilih
- Pada rute dokumen resmi (`/laporan/transmigran/dokumen`), navigasi *pill tab switcher* dan subjudul pengantar informal disembunyikan seluruhnya (`$isDokumen`).
- Dokumen yang digenerate murni menyajikan Kop Surat Dinas + Tabel Data Mode Terpilih (Gabungan, Transmigran, Rumah, atau Lahan) beserta parameter filternya yang dibawa melalui URL Hash.

### 5. Peniadaan Scrollbar Horizontal & Vertikal di Dalam Tabel Dokumen
- Menyesuaikan ukuran font ke `text-theme-xs` (11–12px), memadatkan padding sel (`0.25rem 0.375rem`), dan mengoptimalkan sel lebar (TTL 2 baris kompak, NIK/KK/Pendapatan tabular-nums whitespace-nowrap).
- Menegakkan `overflow-y: hidden` pada `.kertas-dokumen .overflow-x-auto` di `app.css`.
- Total lebar tabel 14 kolom menyusut hingga ~1.180px, muat presisi di dalam kontainer dokumen 1.200px tanpa memicu slider horizontal maupun slider vertikal di dalam tabel.

### 6. Dukungan Pemilihan Ukuran Kertas Cetak A4 / F4 (Opsi 2)
- Menambahkan switcher ukuran kertas (`Kertas: [ A4 | F4 ]`) di bilah header laporan (`components/sim/kerangka-laporan.blade.php`).
- Keadaan pilihan ukuran kertas disinkronkan ke dokumen lewat URL Hash (`#kertas=f4&...`) dan ditangani secara reaktif di `resources/js/filter-laporan.js`.
- Menyesuaikan lebar kontainer layar (`max-w-[1320px]` untuk F4 landscape) dan menginjeksi aturan cetak `@page { size: 330mm 215mm; margin: 10mm; }` untuk dokumen F4.

### 7. Penyesuaian Teks & Pembersihan Card Informasi Monografi SP
- Redaksi panduan pemilih tahun disesuaikan menjadi: *"Laporan menampilkan data kependudukan, produksi, dan iklim sesuai tahun yang dipilih. Informasi kondisi fisik wilayah, meliputi letak, batas, luas, tanah, sumber daya air, dan aksesibilitas, merupakan informasi wilayah yang bersifat tetap."*
- Card panduan tersebut disembunyikan pada dokumen resmi yang digenerate (`@unless ($isDokumen)`).

### 8. Verifikasi Mutu
- **Uji Peramban Lebar Dokumen (`node tests/Browser/uji-lebar-dokumen.mjs`):** 28 lulus, 0 gagal (100% muat tanpa gulir mendatar di seluruh 7 laporan resmi).
- **Pest PHP:** 728 pengujian (6.120 assertions) **100% PASS (Hijau)**.
- **Vite Build:** `npm run build` sukses bersih dalam 5.10 detik.
- **A11y & Visual Hierarchy:** Memenuhi WCAG 2.1 AA (caption, th scope, tabular-nums).





