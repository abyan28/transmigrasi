# Todos
[✓] J - Dokumen pendukung di 7 form
[✓] K - Komponen searchable dropdown (x-sim.pilih-cari)
[✓] L - Terapkan searchable pada 7 select (transmigran + lahan)
[✓] M - Uji baru (5) + 2 mutasi terbukti
[✓] N - Dokumen: notes, ui-spec, tasklist
[✓] O - Verifikasi: pest 395 + build + smoke 45/45
Kelompok 2 selesai. 395 uji lulus (dari 383), 45/45 rute 200, build hijau.
Dokumen pendukung — bukan pekerjaan baru, komponen yang belum dipasang. 8 kolom sudah lama ada di kamus data dan x-sim.file-upload sudah dipakai 5 form lain. Yang tak ada cuma isiannya. Akibatnya nyata: SK pembentukan poktan dan berita acara penyaluran saprotan tak bisa diunggah ke mana pun, padahal justru itu yang diminta saat pemeriksaan. Infrastruktur dapat 2 isian terpisah (foto untuk kondisi lapangan, dokumen_pendukung untuk berkas administratif) sesuai kamus data 10.1.
x-sim.pilih-cari dipasang di 7 isian. Tiga hal yang saya jaga:
- Isian sesungguhnya tetap <select> biasa — backend tak perlu tahu komponen ini ada, dan halaman tetap jalan kalau JS gagal dimuat
- Kotak cari hanya muncul bila daftar ≥ 8 opsi. Terbukti bekerja: /rumah (4 opsi) dan /riwayat-tanam (6) sengaja polos
- Tanpa dependensi baru — Alpine yang sudah ada cukup, dan petugas di lokus tak perlu mengunduh berkas tambahan
Satu jebakan Alpine ketemu saat pemasangan: x-model pada select yang opsinya dirender lewat x-for menyetel ulang nilainya tiap daftar berubah — pilihan petugas hilang begitu ia mengetik di kotak cari. Diganti @change, dan aturannya saya catat di ui-spec.md 6.0a butir 4.
Uji em dash juga sempat menangkap saya: komponen awalnya pakai &mdash;, melanggar R-02.
Sisa 4 poin — semuanya butuh keputusan Anda, bukan sekadar dikerjakan:
- b690 hapus batas wilayah SP → bentrok rules.md:165 + prd.md:135
- b693 dokumen lahan digabung → sudah pernah diputuskan tetap terpisah
- b694 komoditas unggulan otomatis → dua sumber data saling bertentangan
- b691 pengaduan ditautkan ke objek → perlu kolom baru, paling besar
Lanjut ke Kelompok 3 (tiga poin pertama), atau langsung bahas penautan pengaduan?