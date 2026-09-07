# Data Excel Demo Impor

Impor berkas sesuai nomor urut **01 sampai 14**. Setiap workbook memiliki sheet `Data` berisi **5 baris siap impor**, plus sheet `Petunjuk` dan `Contoh`.

## Urutan impor

1. `01-satuan-demo.xlsx`
2. `02-wilayah-demo.xlsx`
3. `03-komoditas-demo.xlsx`
4. `04-transmigran-demo.xlsx`
5. `05-rumah-demo.xlsx`
6. `06-lahan-demo.xlsx`
7. `07-poktan-demo.xlsx`
8. `08-saprotan-demo.xlsx`
9. `09-penanaman-demo.xlsx`
10. `10-hasil-panen-demo.xlsx`
11. `11-infrastruktur-demo.xlsx`
12. `12-inventaris-sp-demo.xlsx`
13. `13-fasilitas-sp-demo.xlsx`
14. `14-alsintan-demo.xlsx`

## Keterkaitan data

- Komoditas memakai satuan dari file 01.
- Transmigran memakai SP bawaan aplikasi.
- Rumah dan lahan memakai NIK dari file 04.
- Poktan memakai NIK ketua/anggota dari file 04.
- Saprotan memakai komoditas file 03 dan slug Poktan file 07.
- Penanaman memakai kode Saprotan file 08 dan slug Poktan file 07.
- Hasil panen memakai kode penanaman file 09.

Prasyarat: enam SP aplikasi dan master daftar pilihan bawaan sudah tersedia. Jangan ubah nama/kode penghubung antarfile sebelum seluruh rantai selesai diimpor.

Script `buat-demo-impor.php` hanya untuk meregenerasi workbook. Script `verifikasi-impor.php` dilindungi agar hanya berjalan pada SQLite `demo-verifikasi.sqlite`, bukan database utama.
