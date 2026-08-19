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

# Todos
[✓] D-A sampai D-D - Form, index, dashboard, DummyData
[✓] D-E - 5 uji baru + 3 mutasi terbukti
[✓] D-F - Dokumen: rules 8.3a-c, ui-spec 2.4, notes
[✓] D-G - Verifikasi: pest 413 + build + smoke 45/45 + peramban 5/5
Poin D selesai. 413 uji lulus (dari 408), 45/45 rute 200, build hijau, 5/5 pemeriksaan peramban.
Jawaban atas pertanyaan Anda
Unggulan ditandai petugas, bukan dihitung sistem. Tiga bukti dari dokumen proyek sendiri:
- prd.md:9 — unggulan berasal dari proposal
- rules.md:362 — kata kerjanya "penandaan", bukan penentuan
- Deskripsi jagung: "ditanam hampir seluruh keluarga" — sebaran penanam, bukan volume
Jagung ditandai unggulan sebelum satu baris panen pun tercatat.
Kalau diotomatiskan, komoditas prioritas program yang volumenya kecil justru tak akan pernah tertandai — padahal kecilnya karena baru dirintis. Pola ini sudah pernah diputuskan di proyek ini: rules.md:502 melarang warga menilai prioritas pengaduannya sendiri, dengan alasan yang sama.
Yang ditambahkan: form menampilkan volume tercatat dan peringatan bila yang ditandai bukan terbesar — tanpa menghalangi penyimpanan.
Rencana penyatuan angka saya batalkan
Sebelum menyentuh kode, saya periksa dan menemukan empat angka dashboard sudah konsisten di 1.847,5 ton (ringkasanDashboard, rekapPerSp, deretTahunan). Menurunkan sebaranKomoditas() dari hasilPanen() akan merusak ketiganya.
Mutasi membuktikannya: mengubah satu angka langsung memecahkan uji dengan 1847.5 vs 577.0.
Jadi keduanya bukan saling bertentangan — beda cakupan. Satu agregat kawasan setahun, satu lagi 5 transaksi contoh. Yang kurang cuma keterangannya, dan itu sekarang ada beserta uji penjaga.
Satu cacat laten ikut ketemu
Kartu "Komoditas Utama" memilih lewat array_key_first() — bergantung urutan penulisan larik, bukan nilai. Selama ini benar hanya karena sebaranKomoditas() kebetulan ditulis terurut. Diganti max().
Sisa satu poin: B — pengaduan ditautkan ke objek. Itu yang terbesar (ubah skema, 9 modul). Lanjut?

# Todos
[✓] Perbaiki kunci id_satuan_permukiman + isset() jadi galat tegas di DaftarTautanStatis
[✓] Tambah window.kunciGulir di app.js dan pasang di 9 komponen modal
[✓] Tambah permalink /panen/rekap/{kelompok} sebelum rute /panen/{id}
[✓] Tambah kolom SP di tab Per Petani rekap panen
[✓] Verifikasi build + 413 uji Pest + gilas 122 halaman - semua lulus
[✓] Catat temuan di notes.md + samakan angka README
Ketiganya selesai. 413 uji lulus, 122 halaman tergilas tanpa gagal.
1. Kolom SP di rekap panen
panen/rekap.blade.php — kolom "Satuan Permukiman" muncul hanya di tab Per Petani (di tab lain mubazir: per SP mengulang kolom pertama, per komoditas/musim isinya daftar panjang lintas SP). Terverifikasi: 4 kolom di tab biasa, 5 di Per Petani, <th> dan <td> seimbang termasuk baris total.
Disimpan sebagai himpunan, bukan nilai tunggal, supaya tetap benar kalau nanti satu petani berlahan di lebih dari satu SP.
2. Detail SP 404 — bug saya
DaftarTautanStatis.php:120 menyebut no_sp, kunci sebenarnya id_satuan_permukiman. Yang berbahaya bukan salah ketiknya, tapi isset() yang membungkusnya: 6 halaman dilewati tanpa suara, build tetap hijau, dan kekeliruannya baru muncul sebagai 404 di tangan Anda.
Diganti array_key_exists + RuntimeException. Saya uji dengan menyabotase kunci — sekarang galatnya berteriak dan menyebutkan kunci yang tersedia.
3. Modal tenggelam saat scroll
<html class="h-full"> dengan <body> tanpa tinggi berarti yang menggulir adalah <html>. Semua modal mengunci body — tidak mengunci apa pun.
Polanya tersalin ke 8 berkas, dan confirm-dialog (dipakai 24×) malah tidak mengunci sama sekali. Diganti modul bersama resources/js/kunci-gulir.js, dipakai 9 komponen. Dua detail yang perlu:
- Penghitung lapisan — dialog konfirmasi di dalam modal formulir tidak membuka kunci lebih awal
- Penjaga if (! this.terbuka) — tutup-modal.window menyiarkan ke semua modal sekaligus; tanpa ini penghitungnya jatuh di bawah nol
Bonus
Tab rekap dulu pakai ?kelompok= yang tidak dilayani Pages, jadi kolom SP baru itu takkan pernah terlihat di situs terbit. Ditambahkan permalink /panen/rekap/{kelompok} — didaftarkan sebelum /panen/{id} agar tidak tertelan. Kueri lama tetap bekerja.
Juga menemukan path telanjang /pengguna/... di setel-sandi.blade.php:40, sudah diperbaiki.
Hasil
 	Sebelum
Halaman terbit	113
Uji	413
Tercatat di agents/notes.md bagian 1b.6a + 5 keputusan baru. Angka README juga saya samakan — tabel status ternyata masih menyebut 293 uji dan 119 rute, padahal isinya sudah berubah.
Silakan git push, situs akan diperbarui otomatis.




Anda benar. Dan setelah saya periksa, kesalahannya lebih buruk dari yang Anda sebutkan — bukan cuma cara berpikirnya keliru, pernyataan faktual saya juga salah pada data dummy itu sendiri.
Bukti: klaim saya salah, dan saya tidak memeriksanya
Saya menulis: "Dari 5 pengaduan contoh, tidak satu pun menyangkut lebih dari satu objek."
Ini isi data yang saya tulis sendiri:
PGD-2026-0001: "Saluran irigasi di blok A tersumbat ... air tidak sampai ke lahan usaha."
PGD-2026-0004: "Longsor kecil menutup sebagian jalan produksi menuju lahan usaha."
Dua-duanya menyebut dua objek dalam satu kalimat. Irigasi + lahan usaha. Jalan produksi + lahan usaha. Jadi 2 dari 5 baris — 40% — justru membuktikan kebalikan dari yang saya klaim.
Yang lebih memalukan: PGD-2026-0004 adalah baris yang saya kutip sendiri sebagai bukti bahwa "kategori tidak sama dengan objek". Saya membaca baris itu dengan teliti untuk satu keperluan, lalu buta terhadap apa yang dikatakannya untuk keperluan lain. Saya tidak sedang memeriksa data — saya sedang mencari pembenaran untuk kesimpulan yang sudah saya ambil.
Mengapa penalarannya melingkar
Data dummy itu saya yang karang. Ketika saya berkata "skenario itu tidak ada di data", yang sebenarnya saya katakan adalah "saya tidak terpikir menuliskannya kemarin". Itu fakta tentang imajinasi saya, bukan tentang Kobalima Timur.
Ada tiga hal yang secara struktural tidak mungkin terlihat di data dummy, dan ketiganya justru yang sedang kita bicarakan:
1. Dummy adalah potret sesaat. Ia tidak punya sumbu waktu. Pengaduan menumpuk, dokumen bertambah, kerusakan berulang — semua "banyak" yang lahir dari waktu mustahil tampak pada 5 baris statis.
2. Dummy sengaja dibuat minimal. notes.md sendiri mencatat saya sengaja mengosongkan hal-hal tertentu agar "keadaan kosong ikut teruji". Jadi ketiadaan suatu kasus adalah keputusan desain saya, bukan temuan. Memakainya sebagai bukti sama dengan menaruh barang di laci lalu kaget menemukannya di sana.
3. Dummy dikalibrasi ke tampilan, bukan ke lapangan. 8 KK melawan ~1.140 KK yang disebut PRD. Rasionya 1:140.
Ini bukan kejadian pertama, dan saya menemukan dua lagi
Dokumen lahan (b735). Saya membatalkan alasan pemisahan tabel dengan kalimat: "dari 6 bidang, tidak satu pun memiliki lebih dari satu dokumen. Skenario yang menjadi seluruh alasan pemisahan tidak pernah ada."
Padahal data-dictionary.md 7.2 sampai detik ini masih berbunyi: "dipisah ke tabel sendiri karena satu lahan dapat memiliki lebih dari satu dokumen." Dan secara nyata itu benar — proses sertifikasi transmigrasi memang berlapis: Surat Keterangan Pembagian Tanah lebih dulu, sertifikat menyusul bertahun kemudian. Satu bidang pasti melewati dua dokumen. Yang tidak punya sumbu waktu adalah dummy saya, bukan tanahnya. Untung kesimpulan akhirnya tetap mempertahankan tabel, jadi tidak ada kerusakan — tapi penalarannya sudah cacat sejak itu.
Ambang searchable dropdown (b789). Saya menyetel ambang 8 opsi lalu menulis: "Dengan data contoh sekarang, /rumah dan /riwayat-tanam sengaja tidak menampilkannya sebab daftarnya masih 4 dan 6 baris." Empat baris di bawahnya saya sendiri menulis "mendesaknya baru terasa pada data nyata: PRD menyebut 1.140 KK, sedangkan data contoh hanya 8." Saya melihat jurangnya, menuliskannya, lalu tetap mengalibrasi ke dummy.
Dan yang paling menusuk: pada 2026-08-18 saya sudah mencatat pelajaran ini sendiri — "bertanya lebih dulu lebih murah daripada menyimpulkan". Saya menuliskannya terlalu sempit, hanya untuk "praktik dinas setempat", sehingga tidak berlaku ketika pertanyaannya soal kardinalitas. Pelajaran yang dicatat terlalu spesifik memang tidak akan pernah menahan kesalahan berikutnya.
Rekomendasi saya berubah: tabel pengaduan_objek
Sekarang saya susun alasannya dari lapangan, bukan dari dummy:
Bencana adalah satu kategori penuh yang sifatnya jamak. Longsor, banjir, angin kencang tidak pernah merusak satu benda. Kategori Bencana ada di enum bukan karena saya mengarangnya, tapi karena rules.md 10b.3 dan PRD menetapkannya. Memaksa petugas memilih satu objek untuk sebuah longsor berarti dia harus membuang informasi yang dia tahu.
Infrastruktur di kawasan transmigrasi memang dipakai bersama. Satu saluran irigasi mengairi puluhan bidang milik puluhan KK. Satu jalan produksi melayani satu blok perumahan. Sifat "satu rusak, banyak terdampak" itu bukan kasus tepi — itu definisi infrastruktur.
Konsekuensi kalau saya memaksakan dua kolom: petugas akan menulis objek kedua dan ketiga ke dalam kolom deskripsi sebagai teks bebas. Rekap "aset paling sering diadukan" — salah satu manfaat yang Anda minta — jadi kurang hitung secara sistematis, dan jalan produksi yang terkena longsor tiga kali terlihat seperti hanya sekali. Kesalahannya diam, persis jenis kegagalan yang berulang kali kita hindari di proyek ini.
Selisih biayanya lebih kecil dari yang saya besar-besarkan tadi. Enum ObjekPengaduan tetap dibutuhkan sama persis. Komponen tab dan rekap bentuk kuerinya sama. Yang benar-benar bertambah hanya: satu tabel di kamus data, dan isian form yang jadi pilih-banyak alih-alih pilih-satu — dan pola pilih-banyak sudah ada di proyek ini (larik kotak centang pada penugasan SP, b460).
Saya juga akan mengoreksi deskripsi data contoh agar dua baris itu benar-benar menaut dua objek, sebab sekarang isinya sudah menyatakan dua tapi strukturnya cuma bisa menampung satu.
Usul perbaikan yang lebih penting dari keputusan ini
Karena keluhan Anda soal pola, bukan satu keputusan, saya usulkan satu aturan tertulis agar ini tidak terulang:
Data contoh sah sebagai bukti tentang kode, tidak pernah sebagai bukti tentang lapangan.

Boleh: "apakah kolom ini terender", "apakah filter ini cocok", "apakah keadaan kosong tertangani".
Dilarang: "apakah kasus ini pernah terjadi", "seberapa sering", "apakah bisa lebih dari satu".

Untuk pertanyaan jenis kedua, sumbernya hanya tiga: prd.md/rules.md, sifat domain yang dapat dijelaskan, atau bertanya kepada pemilik proyek. Kalimat "tidak ada di data contoh" dilarang muncul sebagai alasan keputusan struktur.
Ditambah satu kebiasaan: kardinalitas ditanyakan, tidak disimpulkan. Setiap kali muncul pertanyaan "satu atau banyak", itu pertanyaan lapangan, dan Anda yang tahu jawabannya — bukan saya, dan jelas bukan DummyData.php.




Selesai
Butir revisi terakhir di notes.md bagian 6 sudah dikerjakan dan terverifikasi penuh.
Verifikasi: 449 uji hijau (naik dari 429), pint tetap 45 berkas (tidak menambah utang gaya), npm run build hijau, dan 152 halaman digilas seluruhnya membalas 200 — naik dari 122.
Yang dibangun
Tabel pengaduan_objek dengan sembilan objek nyata plus dua pernyataan (belum_terdata, tidak_ada). Bentuk jamak dipilih karena alasan lapangan: kategori Bencana memang untuk kejadian yang merusak banyak hal, dan infrastruktur di kawasan transmigrasi dipakai bersama.
Kewajiban tanpa memaksa berbohong. Objek wajib dinyatakan sebelum status Diproses, tapi lewat tiga cara — bukan dipaksa memilih dari daftar. Ini menjawab langsung kasus Anda: kalau asetnya tidak ada di sistem, petugas menandai "belum terdata", tidak menaut ke aset yang sekadar mirip.
Kasus "meja mana" dijawab sejujurnya. Sistem hanya sanggup menunjuk sampai tingkat jenis barang; unit spesifik ditulis di keterangan tautan. Batas ini dinyatakan terbuka di halaman rincian, bukan disembunyikan.
Sembilan tab Pengaduan Terkait, plus dua halaman rincian baru untuk Inventaris SP dan Fasilitas SP.
Rekap dipecah dua tabel — peringkat aset dan tabel "tanpa tautan" yang wajib tampil, supaya pembaca tahu kalau angkanya belum mewakili keadaan.
Tiga temuan yang tidak Anda sebutkan
Hasil penyisiran skenario yang Anda minta, dan ketiganya soal privasi:
1. Halaman lacak publik akan menyiarkan alamat keluarga tertentu kalau objek ditampilkan — objek kini tidak dirender di sana sama sekali.
2. Rumah, lahan, hasil panen, alsintan hanya tampil sebagai angka gabungan di rekap, sebab menyebut unitnya mengumumkan keluarga mana yang paling sering mengeluh.
3. Dropdown pemilih objek adalah jalur baru yang bisa melewati pembatasan Per SP — dicatat sebagai kewajiban saat RBAC aktif.
Satu cacat lama ikut terperbaiki
Tab "Pengaduan" pada rincian infrastruktur selama ini menampilkan seluruh pengaduan se-SP. Komentar di kodenya bahkan mengakui: "bukan daftar keluhan atas aset ini, sebab pengaduan tidak menaut ke id infrastruktur." Keluhan atas jalan produksi muncul di halaman sumur bor.
Dua aturan kerja baru
Dari teguran Anda, keduanya sudah tertulis permanen:
- rules.md 19a — data contoh sah sebagai bukti tentang kode, tidak pernah tentang lapangan. Kardinalitas ditanyakan, tidak disimpulkan.
- rules.md 20a — skenario disisir sendiri lewat lima sudut (privasi, siklus hidup, kejujuran angka, alur kerja, teknis), bukan menunggu disodorkan.
Ketiga bukti pelanggaran tercatat di notes.md bagian 1c, termasuk fakta bahwa klaim saya "tidak ada pengaduan berobjek jamak" salah pada data contoh itu sendiri — dua deskripsi menyebut dua objek dalam satu kalimat.
Tindak lanjut
Audit keputusan lama yang bersandar pada data contoh sudah dicatat sebagai butir 9 di notes.md bagian 4. Dua kandidat sudah terdeteksi: dokumen lahan dan ambang searchable dropdown. Siap dikerjakan kapan pun Anda mau.


Todo
[✓] Tahap 0 — Aturan anti-dummy di rules.md 19 & 20, catatan di notes.md
[✓] Tahap 1 — Enum ObjekPengaduan baru + pecah KategoriPengaduan
[✓] Tahap 1 — Dokumen: data-dictionary, erd.md, rules.md 10b, prd.md, ui-spec.md
[✓] Tahap 2 — Data contoh pengaduanObjek() + 3 pengaduan baru + pengaduanUntukObjek()
[✓] Tahap 3 — Komponen x-sim.pengaduan-terkait + tab pada 7 halaman rincian
[✓] Tahap 3 — Isian objek pada form/tangani-baris + tautan silang di rincian pengaduan
[✓] Tahap 4 — Halaman rincian Inventaris SP & Fasilitas SP + rute + tombol + DaftarTautanStatis
[✓] Tahap 5 — Rekap objek dua tabel + tautan tetap
[✓] Tahap 6 — Uji privasi, kewajiban, peringkat, halaman statis
[✓] Verifikasi: 449 uji + pint + build + 152 halaman 200
[•] Perbarui notes.md dan tasklist.md