# Laporan Audit Menyeluruh Daftar Pilihan dan Choice Field

**Aplikasi:** Sistem Informasi Digitalisasi Monitoring Pertanian dan Tata Kelola Data Kawasan Transmigrasi Kobalima Timur  
**Tanggal audit:** 7 September 2026  
**Metode:** audit statis repository aktual + pemeriksaan database runtime MySQL  
**Status:** audit saja; tidak ada implementasi atau perubahan source code

---

## 1. Executive Summary

Audit dilakukan terhadap current working tree repository aktual tanpa mengubah source code aplikasi.

### Cakupan yang diperiksa

- 162 route Laravel.
- 155 Blade.
- 52 Blade produksi yang memiliki choice control.
- Controller, model, enum, provider, support/service, middleware, migration, seeder, import, laporan, dashboard, dan JavaScript.
- 71 migration; seluruhnya berstatus `Ran` pada database MySQL runtime.
- 65 tabel fisik.
- 39 PHP enum.
- Menu dan implementasi CRUD Daftar Pilihan.
- Custom searchable select berbasis Alpine.
- Import CSV/XLSX dan dropdown template XLSX.
- Validasi controller dan `ValidationRules`.
- Query, report, dashboard, notification, dan business logic yang membandingkan nilai pilihan.

Tidak ditemukan Livewire, Select2, atau Tom Select. Searchable select dibuat melalui komponen Alpine internal:

- `resources/views/components/sim/pilih-cari.blade.php`
- `resources/views/components/sim/pilih-cari-banyak.blade.php`
- `resources/views/components/sim/wilayah-picker.blade.php`

### Angka utama

| Metrik | Hasil |
|---|---:|
| Konstruksi choice pada Blade produksi | 152 |
| `<select>` biasa | 114 |
| Searchable single select | 9 |
| Searchable multi-select | 4 |
| Input radio | 4 input / 3 field logis |
| Template checkbox pilihan | 8 |
| Datalist/autocomplete | 1 |
| Wilayah picker bertingkat | 4 |
| Filter rentang tahun | 3 |
| Pemilih jumlah baris eksplisit | 5 |
| PHP enum | 39 |
| Jenis di tabel `daftar_pilihan` | 14 |
| Nilai `daftar_pilihan` runtime | 76 |
| Nilai nonaktif runtime | 1 |
| Reference/policy master lain di luar Daftar Pilihan | 7 set logis |
| Kandidat master baru P0 | 0 |
| Kandidat P1 | 2 |
| Kandidat P2 | 3 |
| Slot master dorman/YAGNI | 1 |
| Enum jelas tetap teknis/state machine | 22 |
| Enum duplikat/compatibility terhadap DB master | 12 |

### Kesimpulan arsitektural

Aplikasi sebenarnya sudah memiliki cakupan Daftar Pilihan yang cukup luas. Masalah paling mendesak bukan kekurangan submenu baru, melainkan:

1. Beberapa pilihan yang terlihat editable sebenarnya masih dibatasi enum PHP atau MySQL.
2. Sebagian pilihan editable dipakai sebagai kode business logic, security scope, statistik, atau state machine.
3. Sebagian besar record bisnis menyimpan teks nilai pilihan, bukan FK atau kode stabil.
4. Mengubah nama pilihan dapat menghasilkan dua vocabulary berbeda antara master dan data historis.
5. Menonaktifkan nilai menjaga tampilan histori, tetapi dapat membuat form edit record lama gagal validasi.
6. Import/template masih memiliki beberapa sumber yang berbeda dari form.

### Jawaban utama audit

Pilihan bisnis yang benar-benar belum ada di menu dan layak dipertimbangkan:

- **P1:** Jenis Saprotan.
- **P1:** Status Sertifikat.
- **P2:** Pola Permukiman.
- **P2:** Tingkat Kesuburan Tanah.
- **P2:** Bentuk Wilayah/Topografi.

Namun kelimanya tidak boleh langsung dipindahkan secara mekanis. Beberapa membutuhkan kode stabil atau atribut perilaku agar perubahan admin tidak merusak logic.

Tidak ada kandidat P0 baru yang layak langsung ditambahkan. P0 sebaiknya digunakan untuk memperbaiki integritas 14 master yang sudah ada.

---

## 2. Inventarisasi Semua Choice Field

Tabel berikut mengelompokkan pemakaian create/edit/modal/filter/report yang menggunakan sumber sama. Satu partial form umumnya dipakai oleh modal tambah dan ubah.

| No | Menu/Form | Field pilihan | Sumber | Klasifikasi | Bisa dikelola admin? |
|---:|---|---|---|---|---|
| 1 | Login | `ingat_saya` | Boolean checkbox | System preference | Tidak perlu |
| 2 | Kawasan | Provinsi, kabupaten | Query tabel wilayah | Relationship/reference | Ya, melalui menu Wilayah |
| 3 | SP | Kawasan, desa, penanggung jawab | Query entity terkait | Relationship | Melalui menu entity masing-masing |
| 4 | SP | `pola_permukiman` | `PolaPermukiman` enum | Business classification | Belum |
| 5 | SP | `tingkat_kesuburan_tanah` | `TingkatKesuburanTanah` enum | Business classification | Belum |
| 6 | SP | `bentuk_wilayah` | `BentukWilayah` enum | Business classification | Belum |
| 7 | Wilayah | Tingkat wilayah, parent wilayah | Array teknis + relationship | Navigation/relationship | Tingkat tetap; data dikelola menu Wilayah |
| 8 | Inventaris SP | Jenis inventaris | `daftar_pilihan` | Database master | Ya |
| 9 | Inventaris SP | Sumber dana | `daftar_pilihan` | Database master | Ya |
| 10 | Inventaris SP | Status penyerahan | `daftar_pilihan` | Business state berisiko | Ya, tetapi tidak sepenuhnya aman |
| 11 | Inventaris SP | Kondisi umum/rincian kondisi | `daftar_pilihan` | Business state berisiko | Ya, tetapi logic mengenal nilai tertentu |
| 12 | Fasilitas SP | SP lokasi/cakupan | Query SP | Relationship/cascading | Tidak masuk Daftar Pilihan |
| 13 | Fasilitas SP | Jenis fasilitas | DB master + PHP enum + MySQL enum | Duplicate source | Tampak bisa, tetapi nilai baru dapat ditolak |
| 14 | Fasilitas SP | Sumber dana, status penyerahan, kondisi | `daftar_pilihan` | Database master | Ya, dengan risiko business logic |
| 15 | Transmigran | SP, daerah asal | Query SP/kabupaten | Relationship | Tidak masuk Daftar Pilihan |
| 16 | Transmigran | Jenis kelamin | `JenisKelamin` enum | Standard/system vocabulary | Tidak |
| 17 | Transmigran | Agama | `Agama` enum | Standard nasional | Tidak direkomendasikan editable |
| 18 | Transmigran | Pendidikan terakhir | `PendidikanTerakhir` enum | Standard pelaporan | Tidak direkomendasikan editable |
| 19 | Transmigran | Pekerjaan | Input bebas + datalist dari data aktual | Open vocabulary/computed | Tidak perlu master |
| 20 | Transmigran | Status tinggal | `StatusTinggal` enum | State machine | Tidak |
| 21 | Transmigran/Lahan | Status sertifikat | `StatusSertifikat` enum | Business/data-quality state | Belum; kandidat P1 |
| 22 | Anggota keluarga | Hubungan keluarga | `HubunganAnggotaKeluarga` enum | Structural invariant | Tidak |
| 23 | Anggota keluarga | Jenis kelamin, agama, pendidikan | Enum yang sama dengan kepala keluarga | Standard vocabulary | Tidak |
| 24 | Anggota keluarga | Kegiatan | `KegiatanAnggota` enum | Form branching | Tidak |
| 25 | Anggota keluarga | Status/peristiwa | `StatusAnggotaKeluarga` enum | State machine | Tidak |
| 26 | Suksesi KK | Alasan pergantian, nasib ketua poktan | Enum/action array | Workflow | Tidak |
| 27 | Rumah | Penghuni, SP | Query transmigran/SP | Relationship | Tidak masuk Daftar Pilihan |
| 28 | Rumah | Kondisi rumah | `daftar_pilihan` + enum legacy | Database master berisiko | Ya |
| 29 | Rumah | Status hunian | `daftar_pilihan` + enum logic | State/invariant | Tampak editable, tetapi HIGH RISK |
| 30 | Lahan | Pemilik, SP | Query transmigran/SP | Relationship | Tidak masuk Daftar Pilihan |
| 31 | Lahan | Peruntukan/kategori lahan pada filter | Hard-coded derived dari kolom lahan | Technical projection | Tidak |
| 32 | Poktan | SP, ketua, anggota keluarga | Query entity | Relationship/cascading | Tidak masuk Daftar Pilihan |
| 33 | Poktan | Asal ketua/wakil | `AsalWakilPoktan` enum | Structural branching | Tidak |
| 34 | Poktan | Jabatan anggota | `daftar_pilihan` + enum compatibility | Business master | Ya |
| 35 | Poktan | Status keanggotaan | `StatusKeaktifanAnggota` enum | State machine | Tidak |
| 36 | Alsintan | Jenis alat | `daftar_pilihan` | Business master | Ya |
| 37 | Alsintan | Sumber dana | `daftar_pilihan` | Business master | Ya |
| 38 | Alsintan | Poktan penerima | Searchable multi relationship | Relationship | Tidak masuk Daftar Pilihan |
| 39 | Alsintan distribusi | Kondisi, penanda tangan | Master kondisi + relationship anggota | Mixed | Kondisi ya; penanda tangan tidak |
| 40 | Saprotan | Jenis saprotan | `JenisSaprotan` enum | Business type + branching | Belum; kandidat P1 |
| 41 | Saprotan | Komoditas, satuan, poktan penerima | Query DB | Relationship/reference | Tidak masuk Daftar Pilihan |
| 42 | Saprotan | Sumber dana | `daftar_pilihan` | Database master | Ya |
| 43 | Komoditas | Tipe komoditas | `daftar_pilihan` + enum pada import | Database master/duplicate source | Ya, tetapi import belum sepenuhnya sinkron |
| 44 | Komoditas | Satuan | Tabel `satuan` | Separate database master | Ya, menu Satuan |
| 45 | Komoditas | Unggulan | Boolean checkbox | Business flag | Tidak perlu daftar |
| 46 | Penanaman | Poktan, komoditas | Query entity | Relationship | Tidak masuk Daftar Pilihan |
| 47 | Penanaman | Benih digunakan | Query distribusi saprotan bersisa | Computed relationship | Tidak masuk Daftar Pilihan |
| 48 | Penanaman/filter | Status panen | `StatusPanen` derived enum | Computed state | Tidak |
| 49 | Panen | Penanaman/satuan | Relationship + derived dari komoditas | Relationship/computed | Tidak |
| 50 | Infrastruktur | SP lokasi/cakupan, poktan pengelola | Relationship | Tidak masuk Daftar Pilihan |
| 51 | Infrastruktur | Jenis infrastruktur | `daftar_pilihan` + enum compatibility | Business master | Ya, perlu satu sumber |
| 52 | Infrastruktur | Sumber dana, kondisi | `daftar_pilihan` | Database master | Ya, dengan risiko logic |
| 53 | Pengaduan publik/internal | SP | Query SP menurut scope | Relationship/security scoped | Tidak masuk Daftar Pilihan |
| 54 | Pengaduan publik/internal | Kategori | `daftar_pilihan` | Business master | Ya |
| 55 | Pengaduan internal | Bidang | `daftar_pilihan` | Master + security routing | Ya, HIGH RISK |
| 56 | Pengaduan | Prioritas | `daftar_pilihan` + enum logic | Queue/business state | Ya, HIGH RISK |
| 57 | Pengaduan | Status | `StatusPengaduan` enum | State machine | Tidak |
| 58 | Pengaduan | Sumber laporan | `SumberLaporan` enum/hidden value | Provenance system | Tidak |
| 59 | Penanganan pengaduan | Status berikutnya | Computed dari transition map | State machine | Tidak |
| 60 | Pengguna | Role | Query tabel role | Relationship/security | Dikelola menu Role, bukan Daftar Pilihan |
| 61 | Pengguna | SP yang ditugaskan | Multi-select SP | Relationship/security scope | Tidak masuk Daftar Pilihan |
| 62 | Pengguna | Aktif/nonaktif | Boolean | Account state | Tidak |
| 63 | Role | Cakupan data | `CakupanData` enum | Security invariant | Tidak |
| 64 | Role | Izin modul/aksi | Permission table + checkbox matrix | Security capability | Tidak masuk Daftar Pilihan |
| 65 | CMS | Aktif, tampilkan tanda tangan | Boolean | Presentation setting | Tidak |
| 66 | CMS | Jenis/konten bagian | Controller constants | Fixed CMS schema | Tidak |
| 67 | Daftar Pilihan | Jenis daftar | `JenisDaftarPilihan` route enum | System schema | Tidak editable |
| 68 | Daftar Pilihan | Bidang bawaan kategori | Self-FK ke bidang | Master dependency | Ya |
| 69 | Daftar Pilihan | Aktif/nonaktif | Boolean | Lifecycle master | Ya |
| 70 | Penilaian Kondisi | Parameter, kebutuhan, aktif | DB policy + enum `TingkatKebutuhan` | Policy master | Dikelola menu khusus |
| 71 | Penilaian Kondisi | Status akhir | `status_kondisi_sp` | Separate DB master/policy | Dikelola menu khusus |
| 72 | Filter daftar | SP, kategori, jenis, status, kondisi | Sumber form/master/relationship yang sama atau distinct query | Filter | Sebagian dikelola sesuai sumber |
| 73 | Filter tahun | Range/database years/current year | Computed | Tidak |
| 74 | Filter audit | Aksi, pengguna, tahun | Distinct audit data/query | Computed/system | Tidak |
| 75 | Filter jumlah baris | 10/25/50/100 | `Paginasi::PILIHAN` | Technical constant | Tidak |
| 76 | Rekap | Dasar pengelompokan | Hard-coded route-safe values | Report dimension | Tidak |
| 77 | Laporan | SP, tahun, status, kondisi, jenis, komoditas | DB master atau distinct report data | Filter/report | Ikuti source utama |
| 78 | Laporan | A4/F4, mode tampilan | UI technical constant | Technical | Tidak |
| 79 | Import | Pilihan kolom XLSX | Enum/DB master/template range | Import validation | Sebagian belum sinkron |
| 80 | Template import | CSV/XLSX | Hard-coded format | Protocol/system | Tidak |

---

## 3. Master Data yang Sudah Ada

### Struktur menu saat ini

```text
Data Master
├── Wilayah
│   ├── Provinsi
│   ├── Kabupaten/Kota
│   ├── Kecamatan
│   └── Desa
├── Satuan
├── Daftar Pilihan
│   ├── Sumber Dana
│   ├── Status Penyerahan
│   ├── Kondisi
│   ├── Kondisi Rumah
│   ├── Status Hunian
│   ├── Tipe Komoditas
│   ├── Prioritas Pengaduan
│   ├── Jabatan Anggota Poktan
│   ├── Jenis Infrastruktur
│   ├── Jenis Fasilitas
│   ├── Bidang Pengaduan
│   ├── Kategori Pengaduan
│   ├── Jenis Alsintan
│   └── Jenis Inventaris
└── Penilaian Kondisi
    ├── Parameter Penilaian SP
    └── Status Kondisi SP
```

### Implementasi end-to-end Daftar Pilihan

- Enum jenis: `app/Enums/JenisDaftarPilihan.php:8`
- Pengelompokan: `app/Enums/KelompokDaftarPilihan.php`
- Model/query opsi: `app/Models/DaftarPilihan.php:35`
- CRUD: `app/Http/Controllers/MasterDaftarPilihanController.php`
- Route: `routes/internal.php`
- Permission: `app/Support/PetaIzinRute.php:43-46`
- Index: `resources/views/pages/master/daftar-pilihan.blade.php`
- Detail/CRUD: `resources/views/pages/master/detail-daftar-pilihan.blade.php`
- Seeder: `database/seeders/DaftarPilihanSeeder.php`
- Tabel: `database/migrations/2026_09_03_100115_create_daftar_pilihan_table.php`
- Penyedia opsi form: `app/Providers/ViewServiceProvider.php:480`

### Fitur lifecycle yang sudah benar

- Nilai mempunyai `urutan`.
- Nilai dapat dinonaktifkan.
- Tidak tersedia route delete.
- Nilai nonaktif tetap tersimpan.
- Kategori pengaduan dapat mempunyai bidang bawaan.
- Parameter penilaian memakai FK ID ke `daftar_pilihan`.
- Nilai tertentu mempunyai `nilai_skor`.

### Isi database runtime saat audit

- Wilayah: 38 provinsi, 514 kabupaten, 4 kecamatan, dan 6 desa.
- Satuan: 5.
- Daftar pilihan: 76 baris dalam 14 jenis, dengan 1 baris nonaktif.
- Parameter penilaian SP: 18.
- Status kondisi SP: 3.
- Komoditas: 5.

---

## 4. Master Data Ada tetapi Belum Masuk Daftar Pilihan

### 4.1 Master yang memang tepat berada di menu lain

| Master | Lokasi | Rekomendasi |
|---|---|---|
| Provinsi–Desa | Tabel wilayah/menu Wilayah | `KEEP_AS_DATABASE_MASTER` |
| Satuan | Tabel `satuan`/menu Satuan | `KEEP_AS_DATABASE_MASTER` |
| Komoditas | Modul Komoditas | `KEEP_AS_DATABASE_MASTER`; merupakan entity bisnis |
| Kawasan | Modul Kawasan | `KEEP_AS_RELATIONSHIP` |
| SP | Modul SP | `KEEP_AS_RELATIONSHIP` |
| Role | Menu Role | `KEEP_AS_DATABASE_MASTER`; security domain |
| Permission | Seeder + code | `KEEP_AS_CONSTANT`; tidak boleh dibuat bebas |
| Status kondisi SP | Menu Penilaian Kondisi | `KEEP_AS_DATABASE_MASTER` |
| Parameter penilaian | Menu Penilaian Kondisi | `KEEP_AS_DATABASE_MASTER` |

### 4.2 Slot master tersembunyi/dorman

`berkas.jenis_berkas_id` memiliki FK nullable ke `daftar_pilihan`:

- Migration: `database/migrations/2026_09_03_100116_create_berkas_table.php`
- Relationship: `app/Models/Berkas.php`
- Delete rule: `SET NULL`.

Namun:

- `JenisDaftarPilihan` tidak mempunyai `jenis_dokumen`.
- Runtime yang diperiksa belum memakai nilai tersebut.
- Tidak ada CRUD atau dropdown aktif untuk klasifikasi dokumen.

**Rekomendasi:** jangan ditambahkan sekarang. Ini YAGNI. Tambahkan hanya ketika pencarian, retensi, atau pelaporan berdasarkan jenis dokumen benar-benar dibutuhkan.

---

## 5. Hard-coded Choices

Hard-coded tidak selalu salah. Berikut kelompok hard-coded yang ditemukan.

| Choice | Lokasi/pola | Klasifikasi | Rekomendasi |
|---|---|---|---|
| Jumlah baris 10/25/50/100 | `app/Support/Paginasi.php` | Technical | `KEEP_AS_CONSTANT` |
| Format template CSV/XLSX | `TemplateImporController` | Protocol | `KEEP_AS_CONSTANT` |
| Aksi permission lihat/tambah/ubah/hapus | `PengaturanRoleController.php:136` | Security | `KEEP_AS_CONSTANT` |
| Level wilayah | `WilayahController::TINGKAT` | Schema navigation | `KEEP_AS_CONSTANT` |
| Mode rekap | Rekap panen, kependudukan, pengaduan | Route/report contract | `KEEP_AS_CONSTANT` |
| A4/F4 | Kerangka laporan | Presentation | `KEEP_AS_CONSTANT` |
| Kering/basah/peruntukan lahan | Filter/derived columns | Computed | `KEEP_AS_CONSTANT` |
| Aktif/nonaktif, ya/tidak | Checkbox/filter | Boolean | `KEEP_AS_CONSTANT` |
| Nasib ketua ketika suksesi | Form suksesi | Workflow action | `KEEP_AS_CONSTANT` |
| Sumber laporan publik/petugas | Hidden field + enum | Provenance | `KEEP_AS_ENUM` |
| Status hasil import | Import engine | Internal protocol | `KEEP_AS_CONSTANT` |
| Status berikutnya pengaduan | Transition map | State machine | `KEEP_AS_ENUM` |
| Pekerjaan | Input bebas + datalist data aktual | Open vocabulary | Jangan dipaksa menjadi master |

Tidak ditemukan business-choice config khusus dalam `config/*.php`.

---

## 6. Enum Audit

### Ringkasan

- 39 enum aktual.
- 22 jelas harus tetap fixed.
- 12 tumpang tindih dengan master DB atau digunakan sebagai compatibility vocabulary.
- 5 merupakan kandidat master baru/opsional.

| Enum | Digunakan untuk | Rekomendasi | Alasan/risiko |
|---|---|---|---|
| `Agama` | Transmigran/anggota/import/report | `KEEP_AS_ENUM` | Vocabulary nasional, bukan kebijakan lokal |
| `AksiAuditLog` | Audit security | `KEEP_AS_ENUM` | Event code sistem |
| `AksiPermission` | Permission matrix | `KEEP_AS_ENUM` | Terikat middleware/code |
| `AlasanPergantianKK` | Suksesi KK/report | `KEEP_AS_ENUM` | Mengubah perilaku histori |
| `AsalWakilPoktan` | Cabang identitas ketua/wakil | `KEEP_AS_ENUM` | Mengontrol FK dan form branch |
| `BentukWilayah` | Profil SP/monografi | P2 `ADD_TO_DAFTAR_PILIHAN` | Business classification, tetapi jarang berubah |
| `BidangPengaduan` | Scope keamanan/routing | `NEEDS_REDESIGN` | Sudah ada master, tetapi kode membandingkan nilai |
| `CakupanData` | Scope seluruh query | `KEEP_AS_ENUM` | Security invariant |
| `HubunganAnggotaKeluarga` | Struktur keluarga/suksesi | `KEEP_AS_ENUM` | `Istri/Suami` dipakai memilih pasangan |
| `HubunganKeluarga` | Legacy/tidak lagi direferensikan | Pertahankan sementara atau hapus dalam audit cleanup terpisah | Bukan master baru |
| `JabatanAnggotaPoktan` | Jabatan anggota/import | `MERGE_WITH_EXISTING_MASTER` | Daftar Pilihan sudah menjadi sumber runtime |
| `JenisDaftarPilihan` | Menentukan jenis master yang diakui | `KEEP_AS_ENUM` | Admin tidak boleh menciptakan schema master baru |
| `JenisFasilitas` | Fasilitas/penilaian/import/DB enum | `MERGE_WITH_EXISTING_MASTER` + redesign | Konflik tiga sumber |
| `JenisInfrastruktur` | Infrastruktur/import/report | `MERGE_WITH_EXISTING_MASTER` | Master DB sudah tersedia |
| `JenisKelamin` | Identitas/report | `KEEP_AS_ENUM` | Standard fixed vocabulary |
| `JenisNotifikasi` | Renderer/link notifikasi | `KEEP_AS_ENUM` | Terikat kode |
| `JenisSaprotan` | Saprotan dan cabang khusus benih | P1 `NEEDS_REDESIGN` | Jenis bisnis dinamis, tetapi `Benih` mengontrol logic |
| `KategoriPengaduan` | Kategori seed/default | `MERGE_WITH_EXISTING_MASTER` | DB sudah menjadi sumber runtime |
| `KegiatanAnggota` | Branch form anggota | `KEEP_AS_ENUM` | Menentukan field wajib/tersembunyi |
| `KelompokDaftarPilihan` | Pengelompokan menu | `KEEP_AS_ENUM` | Struktur UI |
| `Kondisi` | Banyak aset, dashboard, scoring | `NEEDS_REDESIGN` | Nilai tertentu dibandingkan literal |
| `KondisiRumah` | Rumah/filter/statistik | `MERGE_WITH_EXISTING_MASTER` dengan code stabil | Nilai selain “Tidak Rusak” dianggap rusak |
| `PendidikanTerakhir` | Demografi/report/import | `KEEP_AS_ENUM` | Standard pelaporan |
| `PolaPermukiman` | Profil SP | P2 `ADD_TO_DAFTAR_PILIHAN` | Klasifikasi bisnis, tidak mengontrol logic besar |
| `PrioritasPengaduan` | Urutan antrean/notifikasi/dashboard | `NEEDS_REDESIGN` | Mendesak mempunyai perilaku khusus |
| `StatusAnggotaKeluarga` | Peristiwa/mutasi | `KEEP_AS_ENUM` | State machine histori |
| `StatusHunian` | Penghuni/FK/riwayat | `KEEP_AS_ENUM` atau redesign coded master | Status baru tanpa aturan akan merusak invariant |
| `StatusKeaktifanAnggota` | Keanggotaan aktif tunggal | `KEEP_AS_ENUM` | Constraint bisnis |
| `StatusKondisiSp` | Hasil scoring/notification | `KEEP_AS_ENUM` + DB policy | Nilai akhir terikat threshold |
| `StatusPanen` | Derived dari relasi panen | `KEEP_AS_ENUM` | Computed, bukan data input bebas |
| `StatusPengaduan` | Transition state machine | `KEEP_AS_ENUM` | Transisi wajib berurutan |
| `StatusPenyerahan` | Inventaris/fasilitas | `NEEDS_REDESIGN` | Sudah master tetapi mempunyai makna workflow |
| `StatusSertifikat` | Sertifikasi keluarga/lahan | P1 `NEEDS_REDESIGN` | Business choice dapat berkembang, tetapi “Belum Didata” punya makna data-quality |
| `StatusTinggal` | Tahun keluar/dashboard/scope | `KEEP_AS_ENUM` | State machine |
| `SumberDana` | Banyak modul/report | `MERGE_WITH_EXISTING_MASTER` | Sudah dikelola DB; enum seharusnya bukan validator kedua |
| `SumberLaporan` | Publik vs petugas | `KEEP_AS_ENUM` | Provenance/security |
| `TingkatKebutuhan` | Penilaian kondisi | `KEEP_AS_ENUM` | Terikat scoring dan tampilan |
| `TingkatKesuburanTanah` | Profil SP | P2 `ADD_TO_DAFTAR_PILIHAN` | Business classification, risiko rendah |
| `TipeKomoditas` | Komoditas/import | `MERGE_WITH_EXISTING_MASTER` | Form sudah DB; template masih enum |

---

## 7. Validation Audit

Tidak ada direktori `app/Http/Requests`; validasi berada di controller dan support.

### Pola validasi

| Pola | Penggunaan | Penilaian |
|---|---|---|
| `ValidationRules::daftarPilihan()` | Kolom REF ke 14 jenis | Benar sebagai pusat validasi, tetapi hanya menerima nilai aktif |
| `Rule::enum(...)` | System states dan beberapa duplicate master | Benar untuk state machine; bermasalah untuk pilihan yang sudah editable |
| `Rule::in([...])` | Format, aksi permission, mode teknis | Benar sebagai constant |
| `Rule::exists(...)` | SP, transmigran, poktan, komoditas, satuan | Benar untuk relationship |
| Custom validation | Distribusi, status hunian, suksesi, transition | Harus tetap di kode |
| Query `distinct/pluck` | Filter report/audit | Computed, bukan master |

Definisi REF master berada di `app/Support/ValidationRules.php:444-466`.

### Temuan penting

1. Validasi nilai Daftar Pilihan memeriksa `is_aktif=true`.
2. Form umumnya juga hanya memuat nilai aktif.
3. Record lama yang memakai nilai nonaktif tetap dapat ditampilkan karena teks tersimpan pada record.
4. Namun saat record lama diedit, nilai nonaktif tidak lagi tersedia dan tidak lolos validasi bila dikirim ulang.

Karena itu nonaktif aman untuk histori baca, tetapi belum sepenuhnya aman untuk siklus edit data lama.

---

## 8. Import Audit

Ditemukan 28 kolom import yang mempunyai pilihan terstruktur pada 10 dari 14 template import.

| Entitas | Choice import | Sumber saat ini | Konsistensi |
|---|---|---|---|
| Transmigran | Jenis kelamin, agama, pendidikan, status tinggal | Enum | Konsisten dengan form |
| Rumah | Kondisi, status hunian | Daftar Pilihan | Konsisten secara sumber |
| Lahan | Status sertifikat | Enum | Konsisten |
| Poktan | Asal ketua/wakil, status anggota | Enum/hard-coded | Konsisten sebagai workflow |
| Poktan | Jabatan anggota | Daftar Pilihan | Konsisten |
| Alsintan | Jenis alsintan, sumber dana | Daftar Pilihan | Konsisten |
| Saprotan | Jenis saprotan | Enum | Konsisten dengan form, tetapi belum editable |
| Saprotan | Sumber dana | Daftar Pilihan | Konsisten |
| Komoditas | Tipe/jenis | Template enum lama, form DB | Tidak konsisten |
| Infrastruktur | Jenis/kondisi/sumber dana | DB master dengan enum compatibility | Berpotensi menyimpang |
| Fasilitas | Jenis fasilitas | PHP enum/MySQL enum | Tidak konsisten dengan dropdown DB |
| Inventaris | Jenis, kondisi, sumber dana, status penyerahan | Daftar Pilihan | Umumnya konsisten |

File utama:

- Skema/template: `app/Support/SkemaImpor.php`
- Parser/validator: `app/Support/ImporEngine.php`
- Generator XLSX: `app/Http/Controllers/TemplateImporController.php`

### Temuan import kritis

#### Jenis Fasilitas

```text
Form              → daftar_pilihan
Controller/import → PHP Enum
Database          → MySQL ENUM
```

Admin dapat menambahkan nilai dan melihatnya di form, tetapi penyimpanan/import dapat menolaknya.

#### Tipe Komoditas

```text
Form + validasi runtime → daftar_pilihan
Template import         → TipeKomoditas enum
```

Nilai baru admin belum tentu muncul pada template import.

---

## 9. Duplicate Source

| Konsep | Sumber ganda | Risiko | Single source yang disarankan |
|---|---|---|---|
| Jenis Fasilitas | DB master + PHP enum + MySQL enum | Critical: opsi tampil tetapi gagal disimpan | Coded DB master atau fixed enum, pilih satu |
| Jenis Infrastruktur | DB master + PHP enum | Import/report berbeda dari form | DB master dengan kode stabil |
| Tipe Komoditas | DB master + PHP enum import | Template tertinggal | DB master |
| Jabatan Anggota Poktan | DB master + PHP enum | Nilai baru dapat tidak dikenal importer | DB master |
| Kategori Pengaduan | DB master + enum seed/default | Drift antar kanal | DB master |
| Bidang Pengaduan | DB master + enum dalam security scope | Nilai baru tanpa scope | Coded master + mapping security |
| Prioritas Pengaduan | DB master + enum/literal logic | Nilai baru tidak punya ordering/notification semantics | Coded master |
| Kondisi | DB master + enum/literal comparisons | Statistik/scoring salah | Coded master + behavior classification |
| Kondisi Rumah | DB master + enum/literal | Status baru otomatis dianggap rusak | Coded master |
| Status Hunian | DB master + enum/invariant | Status baru tidak tahu perlu penghuni atau tidak | Tetap enum atau coded master |
| Status Penyerahan | DB master + enum/literal | Workflow/report drift | Coded master |
| Sumber Dana | DB master + enum | Import/template tertinggal | DB master |

### Temuan tambahan: prioritas pengaduan

Menu master menyimpan `urutan`, tetapi antrean prioritas masih memiliki logic hard-coded. Artinya perubahan urutan admin belum tentu mengubah semua urutan kerja yang dijanjikan UI. Ini bukan sekadar duplikasi label, tetapi duplikasi semantics.

---

## 10. Kandidat Daftar Pilihan Baru dan Prioritas

## P0 — Wajib ditambahkan

Tidak ada pilihan baru P0.

Menambahkan submenu baru sebelum memperbaiki integritas 14 master saat ini akan memperluas pola yang belum aman.

### P0 integritas yang harus diselesaikan lebih dulu

1. Selesaikan konflik Jenis Fasilitas.
2. Pisahkan kode stabil dari label editable.
3. Tentukan aturan rename nilai yang sudah digunakan.
4. Tentukan perilaku edit record dengan nilai nonaktif.
5. Sinkronkan form, validation, import, report, dan filter.
6. Kunci master yang sebenarnya state machine/security.
7. Selaraskan urutan prioritas pengaduan dengan sumber yang dijanjikan UI.

## P1 — Sangat disarankan

### 1. Jenis Saprotan

Rekomendasi: `NEEDS_REDESIGN`, kemudian `ADD_TO_DAFTAR_PILIHAN`.

Alasan:

- Merupakan kategori bisnis.
- Jenis bantuan pertanian dapat berkembang.
- Admin berpotensi membutuhkan jenis baru tanpa deploy.

Risiko:

- `Benih` mengaktifkan komoditas, varietas, dan pemilihan stok benih.
- Nilai baru tidak boleh otomatis dianggap sama dengan Benih.

Kebutuhan minimal:

- kode stabil;
- nama;
- urutan;
- aktif/nonaktif;
- atribut perilaku `adalah_benih` atau kategori sistem yang tidak editable bebas.

### 2. Status Sertifikat

Rekomendasi: `NEEDS_REDESIGN`, kemudian pertimbangkan `ADD_TO_DAFTAR_PILIHAN`.

Alasan:

- Kebutuhan lapangan dapat berkembang menjadi Dalam Proses, Bermasalah, Sertifikat Hilang, dan lain-lain.
- Merupakan konsep administrasi bisnis, bukan state teknis murni.

Risiko:

- Nilai `Belum Didata` adalah status kualitas data.
- Laporan dapat membedakan sudah, belum, dan tidak diketahui.

Kebutuhan minimal:

- kode stabil;
- label;
- urutan;
- aktif/nonaktif;
- kelompok semantik seperti `sudah`, `belum`, `tidak_diketahui`.

## P2 — Opsional

### 3. Pola Permukiman

- Jarang berubah.
- Tidak ditemukan state machine kuat.
- Cukup `kode`, `nama`, `urutan`, `is_aktif`.

### 4. Tingkat Kesuburan Tanah

- Klasifikasi bisnis.
- Akan berguna jika instansi memakai skala berbeda.
- Jika dipakai untuk scoring nanti, memerlukan atribut numerik tersendiri.

### 5. Bentuk Wilayah/Topografi

- Vocabulary dapat berbeda antar pedoman.
- Risiko logic rendah saat ini.
- Cukup kode, nama, urutan, aktif.

## Tidak diprioritaskan

- Agama.
- Jenis kelamin.
- Pendidikan terakhir.
- Hubungan keluarga.
- Status tinggal.
- Status pengaduan.
- Status anggota keluarga.
- Cakupan data.
- Permission action.
- Pekerjaan, karena sengaja merupakan vocabulary terbuka.

---

## 11. Pilihan yang Jangan Dijadikan Daftar Pilihan

### System state/state machine

- Status Pengaduan.
- Status Tinggal.
- Status Anggota Keluarga.
- Status Keaktifan Anggota Poktan.
- Status Panen.
- Alasan Pergantian KK.
- Kegiatan Anggota.
- Sumber Laporan.

### Security

- Cakupan Data.
- Permission.
- Aksi Permission.
- Jenis/Aksi Audit Log.
- Role bukan daftar pilihan; role adalah security entity tersendiri.
- SP penugasan pengguna adalah relationship.

### Relationship

- SP.
- Kawasan.
- Transmigran.
- Anggota keluarga.
- Rumah.
- Lahan.
- Poktan.
- Komoditas.
- Satuan.
- Penanaman.
- Distribusi saprotan/alsintan.
- Pengguna/penanggung jawab.
- Wilayah administratif.

### Computed

- Tahun dari data.
- Daftar aksi audit aktual.
- Daftar pengguna audit.
- Komoditas yang benar-benar muncul pada periode laporan.
- Benih yang masih memiliki stok distribusi.
- Status panen dari keberadaan hasil panen.
- Pekerjaan autocomplete dari data yang sudah tersimpan.

### Technical/UI

- Jumlah baris.
- CSV/XLSX.
- A4/F4.
- Mode report.
- Dasar pengelompokan rekap.
- Ya/tidak.
- Aktif/nonaktif.
- Kering/basah.
- Remember me.

---

## 12. Rekomendasi Struktur Menu Daftar Pilihan

Struktur final paling sederhana:

```text
Data Master
├── Wilayah
├── Satuan
├── Daftar Pilihan
│   ├── Aset & Infrastruktur
│   │   ├── Jenis Inventaris
│   │   ├── Jenis Infrastruktur
│   │   ├── Jenis Fasilitas
│   │   ├── Jenis Alsintan
│   │   ├── Sumber Dana
│   │   ├── Status Penyerahan
│   │   └── Kondisi
│   ├── Perumahan & Pertanahan
│   │   ├── Kondisi Rumah
│   │   ├── Status Hunian
│   │   └── Status Sertifikat          [P1, setelah redesign]
│   ├── Pertanian & Kelembagaan
│   │   ├── Tipe Komoditas
│   │   ├── Jenis Saprotan             [P1, setelah redesign]
│   │   └── Jabatan Anggota Poktan
│   ├── Pengaduan
│   │   ├── Kategori Pengaduan
│   │   ├── Bidang Pengaduan
│   │   └── Prioritas Pengaduan
│   └── Klasifikasi SP                 [P2]
│       ├── Pola Permukiman
│       ├── Kesuburan Tanah
│       └── Bentuk Wilayah
└── Penilaian Kondisi
```

Tidak direkomendasikan membuat submenu terpisah untuk setiap entity relationship.

---

## 13. Risiko dan Dependency

### 13.1 Label master menjadi identifier

Sebagian besar kolom menyimpan teks seperti:

```text
Baik
Mendesak
Dihuni
APBN
Jalan Penghubung
```

bukan FK `daftar_pilihan_id`.

Contoh akibat perubahan label:

```text
Master: "Rusak Ringan" → "Kerusakan Ringan"
Data lama tetap: "Rusak Ringan"
```

Dampak:

- filter dapat terpecah;
- validasi edit data lama gagal;
- report menghasilkan dua kelompok;
- business logic literal tidak mengenali label baru;
- admin mengira rename telah memperbarui seluruh data, padahal tidak.

Rekomendasi desain nanti: kode stabil immutable dan label terpisah, atau FK.

### 13.2 Nilai nonaktif

Mekanisme nonaktif lebih aman daripada delete dan harus dipertahankan.

Namun perlu aturan:

- create: hanya nilai aktif;
- filter/history: aktif dan nilai historis;
- edit record lama: nilai saat ini tetap diterima walaupun nonaktif;
- nilai nonaktif tidak boleh dipilih untuk mengganti record lain.

### 13.3 Jenis Fasilitas — critical mismatch

- Opsi UI: database master.
- Validasi controller/import: `JenisFasilitas` enum.
- Penyimpanan MySQL: native ENUM.

Ini adalah kasus paling jelas ketika admin mempunyai tombol “Tambah Pilihan”, tetapi pilihan baru belum benar-benar supported.

Bukti utama:

- `app/Enums/JenisDaftarPilihan.php:41`
- `app/Enums/JenisFasilitas.php:17-29`
- `database/migrations/2026_09_03_100126_create_fasilitas_sp_table.php:21-24`
- `app/Models/FasilitasSp.php:33-37`
- `app/Http/Controllers/FasilitasSpController.php:208`
- `app/Support/ImporEngine.php:846`
- `app/Support/SkemaImpor.php:108,347`

### 13.4 Bidang Pengaduan — security risk

Bidang digunakan untuk:

- scope petugas;
- routing pengaduan;
- kategori default;
- dashboard/rekap;
- notification.

Nilai baru tanpa mapping petugas dapat:

- tidak terlihat oleh petugas yang semestinya;
- terlihat oleh scope yang salah;
- tidak mempunyai penerima notifikasi.

### 13.5 Prioritas Pengaduan — workflow risk

`Mendesak` dipakai secara khusus oleh:

- statistik;
- notifikasi;
- antrean;
- rekap.

Admin tidak boleh bebas mengganti makna hanya melalui label. Selain itu, urutan antrean masih hard-coded meskipun master mempunyai kolom `urutan`.

### 13.6 Status Hunian — invariant risk

`Dihuni` menentukan kewajiban `transmigran_id` dan riwayat penghunian. Status baru tidak mempunyai definisi:

- perlu penghuni;
- boleh kosong;
- dihitung sebagai rumah terhuni;
- masuk statistik atau tidak.

### 13.7 Kondisi — scoring/report risk

Logic saat ini mengenal nilai seperti:

- Baik.
- Rusak Ringan.
- Rusak Berat.

Pilihan baru dapat masuk form, tetapi belum tentu:

- masuk klasifikasi rusak;
- mendapat skor;
- memicu notification;
- tampil benar pada chart.

### 13.8 Delete master lain

- Wilayah memakai FK `RESTRICT`; tepat.
- Satuan memakai delete guard/FK; tepat.
- Komoditas dan entity utama memakai soft delete; tepat untuk histori.
- Daftar Pilihan tidak menyediakan delete; tepat.
- `berkas.jenis_berkas_id` memakai `SET NULL`; aman untuk metadata opsional.
- `parameter_penilaian_sp.daftar_pilihan_id` memakai `RESTRICT`; tepat.

---

## 14. Rekomendasi Tahap Implementasi

### Tahap 0 — Tetapkan kontrak master

Sebelum menambah jenis baru:

1. Tentukan perbedaan `kode` dan `label`.
2. Kode tidak dapat diubah admin.
3. Tentukan apakah record bisnis memakai FK atau kode stabil.
4. Tentukan prosedur rename.
5. Tentukan semantics nilai nonaktif pada form edit.
6. Tentukan master mana yang hanya boleh mengubah label/urutan, bukan menambah nilai.

### Tahap 1 — Perbaiki master yang sudah ada

Urutan prioritas:

1. Jenis Fasilitas.
2. Status Hunian.
3. Bidang Pengaduan.
4. Prioritas Pengaduan.
5. Kondisi.
6. Status Penyerahan.
7. Tipe Komoditas/import.
8. Jenis Infrastruktur/import.
9. Jabatan Anggota Poktan/import.
10. Sumber Dana/import/report.

### Tahap 2 — Sinkronkan semua consumer

Untuk setiap master:

```text
Daftar Pilihan
→ Form
→ Validation
→ Import parser
→ Template XLSX
→ Filter
→ Report
→ Dashboard
→ Notification
```

Satu concept tidak boleh memiliki array kedua di enum/template.

### Tahap 3 — Tambahkan kandidat P1

Setelah kontrak stabil:

1. Jenis Saprotan.
2. Status Sertifikat.

Keduanya harus memakai kode/behavior, bukan hanya label.

### Tahap 4 — Evaluasi P2 berdasarkan kebutuhan instansi

- Pola Permukiman.
- Kesuburan Tanah.
- Bentuk Wilayah.

Tambahkan hanya jika dinas memang perlu mengubah taxonomy tanpa deploy.

### Tahap 5 — Slot Jenis Dokumen

Jangan dilakukan sampai ada use case nyata:

- filter arsip;
- retensi;
- klasifikasi dokumen;
- requirement laporan;
- permission berdasarkan jenis dokumen.

---

## 15. Catatan Verifikasi Audit

- `php artisan route:list --json`: 162 route berhasil dimuat.
- Database runtime: 76 nilai Daftar Pilihan, 14 jenis, dan 1 nilai nonaktif.
- Seluruh 71 migration berstatus `Ran` pada database MySQL runtime.
- Pengujian khusus master menghasilkan 20 assertion/test lulus sebelum 9 kegagalan lanjutan akibat setup SQLite `:memory:` antarsuite dengan pesan `no such table`; kegagalan tersebut bukan kegagalan business assertion pada runtime MySQL.
- `git diff --check`: lulus saat verifikasi audit.
- Tidak ada source code aplikasi yang dibuat atau diubah oleh proses audit.
- Working tree telah memiliki perubahan lokal milik pengguna sebelum audit; seluruh perubahan tersebut tidak disentuh.

---

# Kesimpulan Akhir

Menu Daftar Pilihan tidak perlu diperluas secara agresif. Repository sudah mengelola 14 jenis pilihan bisnis dengan 76 nilai.

Prioritas sesungguhnya adalah:

```text
P0: benahi integritas dan single source of truth master yang sudah ada
P1: Jenis Saprotan dan Status Sertifikat setelah redesign
P2: tiga klasifikasi SP bila memang dibutuhkan instansi
JANGAN: state machine, security constant, relationship, computed filter, dan technical choices
```

Temuan paling berisiko adalah:

1. **Jenis Fasilitas mempunyai tiga sumber yang bertentangan:** database master, PHP enum, dan MySQL ENUM.
2. **Rename nilai master berbasis teks tidak memperbarui data lama.**
3. **Nilai nonaktif dapat menyulitkan edit record historis.**
4. **Bidang, Prioritas, Kondisi, Status Hunian, dan Status Penyerahan tampak editable tetapi mempunyai makna kode.**
5. **Template import belum selalu memakai source yang sama dengan form.**
6. **Urutan prioritas pengaduan masih hard-coded walaupun master menyediakan `urutan`.**

Prinsip implementasi yang direkomendasikan:

> Jangan menambah menu baru sebelum pilihan yang sudah ada benar-benar mempunyai satu sumber data, identitas stabil, aturan nonaktif yang aman, dan consumer form/import/report yang konsisten.

Relationship lookup tetap bukan Daftar Pilihan. System state dan security constant tetap berada di kode. Master pilihan hanya untuk taxonomy bisnis yang memang perlu dikelola administrator tanpa deploy.