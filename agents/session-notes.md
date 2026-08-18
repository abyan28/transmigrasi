Rencana: Kelompok 3 — Empat Poin Revisi Tersisa
Urutan C → D → A → B, masing-masing diverifikasi sebelum lanjut.
C. Dokumen lahan masuk ke form lahan
Dasar keputusan lama sudah gugur. notes.md:487 menyatakan dokumen dipisah karena "satu lahan dapat memiliki HPL dan SHM sekaligus" — tetapi dari 6 lahan, tak satu pun punya lebih dari 1 dokumen (LP-001 HPL, LU-001 HPL, LU-003 SHM, LU-004 HPL, dua sisanya kosong). Skenario yang menjadi seluruh alasannya tidak pernah ada di data.
Ketidakkonsistenannya terukur: 12 tabel pakai dokumen_pendukung satu kolom, 1 tabel punya tabel dokumen sendiri.
rules.md:317 hanya mewajibkan dokumen "dapat diunggah dan ditautkan" — tidak menentukan di form mana.
Tindakan
Tambah blok "Dokumen Status Lahan": jenis_dokumen, nomor_dokumen, tanggal_terbit, file_dokumen
Hapus paragraf b186-189 yang menjelaskan pemisahan
Tab Dokumen tetap ada untuk dokumen kedua dan seterusnya, keterangannya disesuaikan
Tabel dokumen_lahan tetap, relasi tetap N:1
Ketiga kolom metadata dipertahankan — nomor sertifikat itu data legal yang harus dapat dicari, bukan sekadar lampiran. Inilah yang membedakannya dari dokumen_pendukung biasa.
Kasus lazim (satu dokumen) jadi satu langkah; kasus jarang tetap terlayani.
D. Komoditas unggulan: manual + peringkat volume
Temuan inti: ada dua konsep paralel yang tak pernah dipertemukan.
 	Sumber
"Komoditas utama" (dashboard)	sebaranKomoditas()
"Komoditas unggulan" (halaman komoditas)	is_unggulan
Keduanya kebetulan menunjuk Jagung, dan tak ada yang memeriksa apakah masih sepakat. komoditas/index.blade.php bahkan memuat kedua sumber di satu layar.
Angkanya bertentangan 112×: jagung 13,95 ton (transaksi panen) vs 1.284,5 ton (dashboard).
Tindakan
Centang tetap manual
Form menampilkan peringkat volume di samping centang
Peringatan bila yang dicentang bukan volume terbesar
Satukan sumber angka: sebaranKomoditas() diturunkan dari hasilPanen() + keTon()
Dashboard "komoditas utama" pakai max(), bukan array_key_first()
Unggulan bukan sekadar "paling banyak": komoditas prioritas program yang volumenya kecil bisa saja yang ingin ditandai dinas. Karena itu keputusan tetap di tangan manusia.
A. Batas wilayah SP dihapus sepenuhnya
Nilai fungsionalnya nol: 0 perhitungan, 0 indikator, 0 uji, 0 fitur peta. Isinya deskriptif ("Hutan lindung") — mustahil dipakai menggambar batas. peta.js hanya memplot titik, tak punya polygon maupun geojson.
11 titik sentuh:
Berkas	Baris
sp/form.blade.php	9, 121-144
dashboard/sp.blade.php	124-132
DummyData.php	91-94, 109-112, 127-130, 145-148, 163-166, 181-184
UppercaseInput.php	60-63
data-dictionary.md	323-326, 331
rules.md	165
prd.md	135
workflow.md	164, 205
erd.md	361
tasklist.md	269, 635
notes.md	136-138
Menghapusnya tidak memerahkan satu uji pun — itu sekaligus tandanya memang tak dijaga apa pun.
Saya catat di notes.md bahwa nilainya dokumenter (menyalin SK penetapan), agar bila dinas kelak memerlukannya, alasan pencabutan terbaca.
B. Pengaduan ditautkan ke objek
Kerugiannya nyata dan terukur. Di SP Kapitan Meo, 7 aset infrastruktur menampilkan tab "Pengaduan (2)" yang identik. Petugas membuka detail genset dan melihat pengaduan tentang jembatan. Kode sendiri mengakuinya di infrastruktur/detail.blade.php:26.
Preseden sudah ada dan terbukti: audit_log memakai nama_tabel + record_id, dipakai 10 halaman lewat x-sim.catatan-log. Bukan pola baru.
Dan 7 dari 9 kategori pengaduan sudah menyebut jenis objeknya (Rumah, Infrastruktur, Alsintan, Lahan Usaha…). Tinggal melengkapi "yang mana".
Skema — dua kolom nullable:
Kolom	Tipe
objek_tabel	VARCHAR(64) NULL
objek_id	BIGINT UNSIGNED NULL
Nullable karena pengaduan boleh tanpa objek — berbeda dari audit_log yang selalu terisi. Bencana dan "Lainnya" memang tak menunjuk aset mana pun.
Sembilan FK nullable jelas tidak masuk akal, jadi pola pasangan ini yang dipakai. erd.md memperlakukannya sebagai pengecualian sadar, dan itu akan saya catat.
Cakupan:
- Isian objek di form petugas, muncul setelah kategori dipilih dan menyaring daftar sesuai kategori itu — memakai x-sim.pilih-cari yang baru dibangun
- Tab pengaduan di infrastruktur/detail menyaring per aset, bukan per SP
- Tab serupa ditambahkan ke halaman rincian rumah, lahan, poktan, alsintan, saprotan
- Aturan baru di rules.md §10b
Satu keputusan yang belum diambil
notes.md:691 menyisakan pertanyaan Anda sendiri: "Mungkin ini yg menautkannya admin? Atau masyarakat?"
Preseden terdekat rules.md:502 menyerahkan penilaian prioritas sepenuhnya kepada petugas, dengan alasan warga tak mengenal skala kerja dinas.
# Questions
Poin B: siapa yang menautkan pengaduan ke objek?
Petugas saja, saat meninjau laporan (Direkomendasikan)
Tab pengaduan per objek ditambahkan ke halaman rincian mana saja?
Infrastruktur dulu, sisanya menyusul (Direkomendasikan)
Keputusan lengkap. Berikut rencana final.
Rencana Final: Kelompok 3 — Empat Poin Revisi
Dikerjakan berurutan C → D → A → B. Tiap tahap diverifikasi (pest + build + smoke) sebelum lanjut, agar bila ada yang pecah, penyebabnya jelas.
C. Dokumen lahan masuk ke form lahan
Alasan: dasar keputusan lama gugur. notes.md:487 memisahkannya karena "satu lahan dapat memiliki HPL dan SHM sekaligus", padahal dari 6 lahan tak satu pun punya lebih dari 1 dokumen. Rasio pola juga timpang: 12 tabel pakai dokumen_pendukung satu kolom, 1 tabel punya tabel sendiri.
Berkas	Tindakan
lahan/form.blade.php	Tambah blok "Dokumen Status Lahan": jenis_dokumen, nomor_dokumen, tanggal_terbit, file_dokumen. Hapus paragraf b186-189 yang menjelaskan pemisahan
lahan/detail.blade.php	Tab Dokumen tetap ada untuk dokumen kedua dst.; keterangan b201-204 disesuaikan
data-dictionary.md §7.2	Tabel dokumen_lahan tetap, catatan pemisahan direvisi
rules.md §7.6	Tetap terpenuhi — aturannya hanya mewajibkan "dapat diunggah dan ditautkan"
Tiga kolom metadata dipertahankan: nomor sertifikat data legal yang harus dapat dicari. Inilah yang membedakannya dari dokumen_pendukung biasa.
Uji: form lahan memuat keempat isian; tab dokumen tetap berfungsi; dokumen lama tetap tampil.
D. Komoditas unggulan: manual + peringkat volume
Alasan: dua konsep paralel tak pernah dipertemukan — "komoditas utama" (dashboard, otomatis) vs "komoditas unggulan" (manual, badge gold). Angkanya berselisih 112×.
Tindakan	Alasan
Centang tetap manual	ui-spec.md:101 — gold satu-satunya aksen; otomatis membuat jumlah badge tak terkendali
Form menampilkan peringkat volume di samping centang	Petugas memutuskan, tak lagi menebak
Peringatan bila yang dicentang bukan volume terbesar	Menutup celah dua sumber berselisih diam-diam
sebaranKomoditas() diturunkan dari hasilPanen() + keTon()	Menghapus selisih 112×
Dashboard pakai max(), bukan array_key_first()	Sekarang bergantung urutan literal array
Risiko yang saya antisipasi: mengubah sebaranKomoditas() menyentuh angka dashboard (volume_panen_ton, grafik donat, deretTahunan). Saya periksa seluruh pemakainya dulu; bila dampaknya melebar, saya laporkan sebelum lanjut.
A. Batas wilayah SP dihapus sepenuhnya
Alasan: 0 perhitungan, 0 indikator, 0 uji, 0 fitur peta. Isinya deskriptif ("Hutan lindung") — mustahil menggambar batas.
11 titik: sp/form.blade.php (b9, 121-144) · dashboard/sp.blade.php (b124-132) · DummyData.php (24 nilai) · UppercaseInput.php (b60-63) · data-dictionary.md (b323-326, 331) · rules.md (b165) · prd.md (b135) · workflow.md (b164, 205) · erd.md (b361) · tasklist.md (b269, 635) · notes.md (b136-138)
Menghapusnya tak memerahkan satu uji pun — itu sendiri bukti tak dijaga apa pun. Saya catat di notes.md bahwa nilainya dokumenter (menyalin SK penetapan), agar bila dinas kelak memerlukannya, alasan pencabutan terbaca dan mudah dikembalikan.
B. Pengaduan ditautkan ke objek
Bukti kerugian: SP Kapitan Meo punya 7 aset infrastruktur, semuanya menampilkan tab "Pengaduan (2)" yang identik. Kode mengakuinya sendiri di infrastruktur/detail.blade.php:26.
Skema — dua kolom nullable, meniru preseden audit_log yang sudah dipakai 10 halaman:
Kolom	Tipe
objek_tabel	VARCHAR(64) NULL
objek_id	BIGINT UNSIGNED NULL
Nullable karena pengaduan boleh tanpa objek — Bencana dan "Lainnya" memang tak menunjuk aset.
Sesuai keputusan Anda:
- Petugas saja yang menautkan. Form publik tak berubah — warga tak tahu kode aset, dan menampilkan daftar aset internal di kanal tanpa login membuka data yang tak perlu. Sejalan rules.md:502
- Isian objek muncul setelah kategori dipilih, menyaring daftar sesuai kategori itu, memakai x-sim.pilih-cari
- Infrastruktur dulu — perbaiki yang terbukti salah sasaran, buktikan polanya, baru menyusul ke 5 modul lain
Dokumen: aturan baru rules.md §10b, data-dictionary.md §10.2, erd.md (mencatat pengecualian polimorfik kedua).
Verifikasi tiap tahap
vendor\bin\pest + npm run build + smoke 45 rute. Untuk C dan B ditambah pemeriksaan peramban lewat Edge headless, sebab keduanya menyentuh tampilan — dan justru itu yang luput pada combobox kemarin.
Tiap perubahan perilaku dijaga uji baru dan dibuktikan lewat mutasi.
Cakupan total: ~8 berkas kode, 9 dokumen acuan, 8 uji baru.
Ada satu hal yang ingin saya sampaikan sebelum mulai: D berpotensi melebar karena sebaranKomoditas() menyuplai beberapa angka dashboard sekaligus. Kalau saat pengerjaan ternyata dampaknya lebih luas dari dugaan, saya hentikan dan laporkan dulu daripada mengubah angka dashboard tanpa sepengetahuan Anda.
Setuju saya mulai dari C?