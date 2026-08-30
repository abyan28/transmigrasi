# Putaran 7 & 7 Poin Revisi Frontend SELESAI (2026-08-30)

Pola "induk + distribusi" untuk Alsintan, Saprotan, +3 temuan audit, +F2, dan 7 Poin Revisi Frontend.
Rencana: `C:\Users\v28mt\.claude\plans\linked-sprouting-aho.md` & Implementation Plan.
Catatan hasil: `agents/notes.md` §1w & `## 6. Revisi`. Ringkasan: `agents/tasklist.md`.

## 7 Poin Revisi Frontend & Perbaikan UI Scrollbar (Selesai):
1. **Revisi 1 (Detail Transmigran Scrollbar & Tab Cleanliness)**:
   - Mengubah CSS Grid kolom kanan menjadi `lg:grid-cols-[20rem_minmax(0,1fr)]` dan menyematkan `min-w-0 overflow-hidden` pada wadah kartu tab (`div.rounded-2xl`) serta panel tabel untuk mengisolasi pelebaran tabel 11 kolom dan mencegah bocornya *body horizontal scrollbar*.
   - Menghilangkan horizontal scrollbar native pada deretan Tab Header dengan menyematkan utility class `no-scrollbar` (`scrollbar-width: none` / `::-webkit-scrollbar: display: none`) di Detail Transmigran serta menstandarkannya di seluruh halaman bertab lainnya (Poktan, Lahan, Rumah, Alsintan, Saprotan, Infrastruktur, SP, Fasilitas, Inventaris, Penanaman, Panen, Pengaduan, Profil, Wilayah).
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

## Verifikasi
Pest tests: 714 passed (5.981 assertions), `sim:tautan-statis`: 224 rute HTTP 200, `npm run build` lulus bersih.
