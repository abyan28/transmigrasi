# Rencana Eksekusi — Putaran 7: pola "induk + distribusi" (Alsintan, Saprotan, +3 temuan audit)

Ditulis 2026-08-30 sesuai `rules.md` §20b. Rencana lengkap + latar di
`C:\Users\v28mt\.claude\plans\linked-sprouting-aho.md`.

**Status masuk:** Putaran 6 SELESAI. pest 705, pint 31, `sim:tautan-statis` 222,
`uji-lebar-dokumen` 28/0, `uji-filter-laporan` 53/0, `uji-gulir-modal` 24/0.

## Masalah
`alsintan`/`saprotan` bawa satu `poktan_id` → satu batch bantuan ke banyak
poktan diketik ulang per poktan, jadi N baris tak saling tahu. Audit menemukan
3 instans sama: `infrastruktur` (lintas SP, MERUSAK skor SP via primer nol),
`dokumen_lahan` (1 dok banyak bidang), `fasilitas_sp`/`inventaris_sp` (bersama +
jumlah>1 satu kondisi). Plus `hasil_panen.poktan_id` salinan tanpa alasan.

## Keputusan pemilik
- Jumlah per poktan: bagi rata otomatis, boleh disunting
- Kondisi alsintan: per poktan (baris distribusi)
- Daftar: satu baris per pengadaan
- Pengadaan tanpa penerima: BOLEH ("belum tersalurkan")
- Tanggal serah: per poktan, opsional (`tanggal_serah` DATE nullable)
- Keempat temuan audit dikerjakan

## Pola: INDUK (benda, tanpa poktan/SP) + DISTRIBUSI (satu baris/penerima)
| Induk | Anak | kolom anak |
|---|---|---|
| alsintan | alsintan_distribusi | poktan_id, jumlah, kondisi, penanda_terima_id, tanggal_serah, foto, keterangan |
| saprotan | saprotan_distribusi | poktan_id, jumlah, tanggal_serah, keterangan |
| infrastruktur | infrastruktur_sp | satuan_permukiman_id (cakupan; wajib memuat SP pangkal) |
| fasilitas_sp | fasilitas_sp_cakupan | satuan_permukiman_id |
| dokumen_lahan | dokumen_lahan_bidang | lahan_id |

DummyData tempel anak ke induk sebagai larik turunan (pola `penanda_terima`).

## Tahap (1 commit/tahap)
- **A** master `jenis_alsintan`: `JenisReferensi::JenisAlsintan` (arm label+kelompok→AsetInfrastruktur), `referensi()` $daftar PALING AKHIR (id infra terpaku), `opsiJenisAlsintan`. `sim:tautan-statis` 222→223.
- **B** `<x-sim.pilih-cari-banyak>` saudara `pilih-cari` (jangan modif; 11 pemanggil). `nilai:[]`, pilih=toggle tak tutup, `<input sr-only name="poktan_id[]">` x-for, chip, `<select multiple>` noscript. Pertahankan 14 aturan ui-spec §6.0a. ui-spec §6.0a +sub-bagian.
- **C** alsintan induk+distribusi. Induk: +`jenis_alsintan`, `jumlah_total`; buang poktan_id/sp/pemilik/kondisi/penanda/foto. `alsintanDistribusi()`. Data contoh: ~4 pengadaan, ≥1 ke ≥2 poktan lintas SP, 1 tanpa distribusi. Form: jenis + pilih-cari-banyak + repeater distribusi (bagi rata otomatis). Index 1 baris/pengadaan. Detail tab Distribusi + "Perbarui Kondisi". `LaporanData::alsintan()` grain tetap distribusi.
- **D** saprotan induk+distribusi — PALING BERISIKO. `saprotanDistribusi()`. **`penanaman.saprotan_id`→`saprotan_distribusi_id`** (batas #33). `sisaBenih()` turun grain ke distribusi (aturan §7c.8 "dihitung tak disimpan" TETAP). `benihTersedia()` iterasi distribusi. Betulkan `satuan` vs `satuan_id` lama. `LaporanData::saprotan()` tetap 2 tabel datar.
- **E** infrastruktur: `satuan_permukiman_id` TETAP (pangkal), +`infrastruktur_sp` cakupan (wajib muat pangkal). `PenilaianKondisiSp.php:98` `===`→`in_array(...ids)` dgn mundur `[$id]`. Form +pilih-cari-banyak "SP lain dilayani". Data: KIOS SAPROTAN DESA cakupan 3 SP.
- **F** (1) fasilitas bersama→`fasilitas_sp_cakupan` (spt E, `:99`). (2) `jumlah`>1: `kondisi` tunggal→rincian kondisi (peta kondisi→jumlah, Σ=jumlah); `kondisi` jadi turunan (terburuk>0); `kondisiTerbaik()` baca rincian. Berlaku fasilitas + inventaris.
- **G** `dokumen_lahan` induk + `dokumen_lahan_bidang`. `dokumenLahan(?lahanId)` tanda tangan tetap. Form pilih bidang via pilih-cari-banyak.
- **H** cabut `hasil_panen.poktan_id`, baca via `penanaman_id`.
- **I** docs: rules §7b/§7c tulis ulang (coretan bertanggal), butir baru; data-dictionary §8.3/§8.4/§10.1/fasilitas/§7.2 + tabel batas #4/#5/#9/#33-35; betulkan hanyutan (§11.37 `kualitas_panen`, §5.6 ref §4.12). ui-spec §6.0a + daftar/detail. notes §1w. tasklist. Bersihkan komentar basi: web.php:1913/2004, poktan/detail:7,280, DummyData:2168.

## Jebakan
- id referensi terpaku → `jenis_alsintan` deklarasi PALING AKHIR
- uji regrain (bukan hapus): DummyDataTest 612/643/667/689/721, HalamanTest 4710/5146/5174/2962/2989
- uji peramban semantik berubah: uji-sp-otomatis, uji-benih-komoditas, uji-form-penanaman
- pilih-cari baca atribut terikat TANPA titik dua (`required` bukan `:required`)
- pint 31 baseline; `\App\Enums\` sebaris di uji → `fully_qualified_strict_types`, pakai `use`
- §19a: keputusan tak boleh berdasar cacah baris contoh

## Verifikasi
pest naik dari 705 · pint ≤31 · `sim:tautan-statis` 223 · `npm run build` ·
peramban suite hijau · manual: 1 pengadaan 3 poktan lintas SP diketik sekali;
SP terlayani irigasi bersama tak jatuh Perlu Penanganan.

**Bila satu tahap membengkak: berhenti di checkpoint bersih, laporkan.**
