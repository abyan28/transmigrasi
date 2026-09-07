# Perbaikan Semantik Metric Strip Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Memastikan seluruh `x-sim.metric-item` bukan hanya database-backed, tetapi juga memakai populasi, periode, grain, satuan, keterangan, dan cakupan yang sesuai dengan arti labelnya.

**Architecture:** Pertahankan `x-sim.metric-strip` dan `x-sim.metric-item` sebagai komponen presentasi tanpa query. Perbaikan dilakukan pada sumber agregat yang sudah ada dan pada caller Blade; gunakan `RekapDashboard::jumlahKkJiwa()` sebagai satu definisi penduduk aktif, enum yang sudah tersedia untuk kondisi rumah, dan label dinamis untuk periode/konteks. Tidak ada migration, dependency, atau abstraction baru.

**Tech Stack:** Laravel, Blade, Eloquent, Pest/PHPUnit, MySQL runtime, Vite.

**Baseline:** commit `22d681e` (`main`), audit 7 September 2026, 67 `metric-item` pada 17 template.

---

## 1. Keputusan Semantik Sebelum Implementasi

Keputusan ini mencegah perbaikan yang justru melanggar aturan domain:

1. **Inventaris/fasilitas tetap dihitung per record untuk kondisi umum.**
   - `agents/data-dictionary.md:412-420,437-446` dan `agents/rules.md:564` menetapkan `kondisi` sebagai penilaian umum petugas dan `rincian_kondisi` sebagai histogram unit.
   - Karena query controller menghitung record, perbaikannya adalah mengganti label/satuan metric dari `unit` menjadi grain record yang jujur; jangan diam-diam mengganti query menjadi jumlah histogram.
   - `Total Unit` tetap memakai `sum(jumlah)`.
2. **Ringkasan halaman daftar tetap kawasan/cakupan akses penuh, bukan mengikuti filter tabel.**
   - Controller terkait sudah menyatakan hal ini secara eksplisit.
   - Perbaikan hanya menambahkan keterangan UI bahwa filter tabel tidak mengubah ringkasan; jangan menduplikasi seluruh query filter untuk metric.
3. **Penduduk aktif memakai definisi canonical dashboard.**
   - Kepala keluarga aktif + anggota keluarga aktif milik keluarga aktif.
   - Jangan menghitung kepala keluarga semua status lalu mencampurnya dengan anggota aktif.
4. **Volume produksi harus menyatakan periodenya.**
   - `RekapDashboard::sebaranKomoditas()` dan `ringkasan()` memakai tahun terakhir/acuan, bukan seluruh histori.
5. **`Rumah Terhuni` dibagi total rumah.**
   - Persentase keterhunian bukan rasio terhadap jumlah KK aktif.
6. **Nilai kondisi rumah tetap vocabulary baku.**
   - Gunakan `KondisiRumah::TidakRusak->value`, bukan literal baru atau master/helper tambahan.
7. **Teks geografis tidak boleh mengarang rentang SP.**
   - Ganti `SP 1 sampai SP 4` dan konteks statis yang dapat basi dengan keterangan generik/dinamis dari data yang sudah tersedia.

## 2. Matriks Perbaikan

| Temuan | Keputusan perbaikan | Lokasi utama |
|---|---|---|
| Total penduduk mencampur populasi semua-status dan aktif | Publikasikan helper existing `jumlahKkJiwa()` dan gunakan hasilnya pada controller transmigran | `app/Support/RekapDashboard.php`, `app/Http/Controllers/TransmigranController.php` |
| Persentase rumah terhuni pada detail SP dibagi KK | Bagi `rumah_terhuni` dengan `rumah_total`; ubah teks fallback menjadi “dari rumah terdata” | `app/Http/Controllers/SpController.php`, `resources/views/pages/sp/detail.blade.php` |
| Kondisi rumah memakai string literal | Gunakan `KondisiRumah::TidakRusak->value` | `app/Http/Controllers/RumahController.php` |
| Metric fasilitas/inventaris menghitung record tetapi bersatuan unit | Ubah label/satuan menjadi record/fasilitas/jenis barang; pertahankan `Total Unit` | `resources/views/pages/sp/fasilitas.blade.php`, `resources/views/pages/sp/inventaris.blade.php` |
| Panen tahun terakhir disebut total tanpa periode | Kirim `tahunPanen`; ubah label/keterangan menjadi eksplisit tahun | controller/view Komoditas, SP detail, galeri komponen |
| `SP 1 sampai SP 4` salah terhadap 6 SP runtime | Ganti dengan keterangan dinamis/generik | `resources/views/pages/transmigran/index.blade.php` |
| Contoh kategori/geografi statis dapat basi | Ganti dengan deskripsi netral atau data yang sudah ada | view transmigran, poktan, SP, penanaman, panen, galeri |
| Ringkasan tidak mengikuti filter tabel | Pertahankan desain; jelaskan “seluruh data dalam cakupan akses” | `halaman-daftar.blade.php` dan lima halaman daftar custom |
| Galeri dev memakai daftar SP tanpa scope eksplisit | Gunakan `SatuanPermukiman::opsiTerlihat()` | `routes/internal.php` |

---

### Task 1: Tambahkan regression test untuk definisi penduduk aktif

**Objective:** Mengunci bahwa angka `Total Penduduk` pada halaman transmigran memakai populasi aktif yang sama dengan dashboard.

**Files:**
- Create: `tests/Feature/MetricRingkasanTest.php`
- Modify later: `app/Support/RekapDashboard.php:627-640`
- Modify later: `app/Http/Controllers/TransmigranController.php:92-99`

**Step 1: Tulis test gagal**

Gunakan data dari `DataMasterSeeder` yang sudah otomatis tersedia pada suite Feature. Tambahkan test:

```php
<?php

use App\Support\RekapDashboard;

it('menyamakan penduduk transmigran dengan populasi aktif dashboard', function () {
    $respons = $this->get(route('transmigran.index'))->assertOk();
    $aktif = RekapDashboard::jumlahKkJiwa();

    expect($respons->viewData('totalAktif'))->toBe($aktif['jumlah_kk'])
        ->and($respons->viewData('totalJiwa'))->toBe($aktif['jumlah_jiwa']);
});
```

**Step 2: Jalankan RED**

Run:

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="menyamakan penduduk"
```

Expected: FAIL karena `jumlahKkJiwa()` masih private; setelah visibilitas dibuka tetapi controller belum diubah, assertion `totalJiwa` tetap gagal pada fixture yang memiliki keluarga nonaktif.

**Step 3: Implementasi minimum**

- Ubah visibilitas `RekapDashboard::jumlahKkJiwa()` dari `private` menjadi `public`; jangan menyalin query ke controller.
- Pada `TransmigranController@index`, panggil helper sekali sebelum `return view`:

```php
$pendudukAktif = RekapDashboard::jumlahKkJiwa();
```

- Pertahankan `$totalKk` sebagai seluruh KK terdata untuk metric “Kepala Keluarga”.
- Isi:

```php
'totalAktif' => $pendudukAktif['jumlah_kk'],
'totalJiwa' => $pendudukAktif['jumlah_jiwa'],
```

- Tambahkan import `App\Support\RekapDashboard` bila belum ada.

**Step 4: Jalankan GREEN**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="menyamakan penduduk"
php artisan test tests/Database/RekapDashboardTest.php
```

Expected: PASS. Jalankan serial; grup Database memakai MySQL bersama.

---

### Task 2: Benarkan persentase Rumah Terhuni pada detail SP

**Objective:** Menghitung occupancy dari `rumah_terhuni / rumah_total`, bukan dari jumlah KK aktif.

**Files:**
- Modify: `tests/Feature/MetricRingkasanTest.php`
- Modify: `app/Http/Controllers/SpController.php:164-176,209`
- Modify: `resources/views/pages/sp/detail.blade.php:187-189,204-207`

**Step 1: Tulis test gagal**

```php
it('menghitung persentase rumah terhuni terhadap total rumah di SP', function () {
    $sp = \App\Models\SatuanPermukiman::query()
        ->whereHas('rumah')
        ->firstOrFail();

    $respons = $this->get(route('sp.detail', $sp->id_satuan_permukiman))->assertOk();
    $ringkasan = RekapDashboard::ringkasan($sp->id_satuan_permukiman);
    $expected = $ringkasan['rumah_total'] > 0
        ? round($ringkasan['rumah_terhuni'] / $ringkasan['rumah_total'] * 100)
        : 0;

    expect($respons->viewData('persenHuni'))->toBe($expected);
});
```

Pastikan fixture yang dipilih memiliki `rumah_total` berbeda dari jumlah KK aktif; bila seed tidak menjaminnya, buat satu rumah kosong pada test sebelum request.

**Step 2: Jalankan RED**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="persentase rumah terhuni"
```

Expected: FAIL dengan nilai persentase lama berbasis `$jumlahKk`.

**Step 3: Implementasi minimum**

- Tambahkan `rumah_total` ke array `$rekap` bila diperlukan oleh view/test.
- Ubah `persenHuni` menjadi:

```php
'persenHuni' => $ringkasan['rumah_total'] > 0
    ? round($ringkasan['rumah_terhuni'] / $ringkasan['rumah_total'] * 100)
    : 0,
```

- Ubah keterangan stat-card fallback dari `% dari KK terdata` menjadi `% dari rumah terdata`.
- Jangan mengubah query rumah atau relasi SP.

**Step 4: Jalankan GREEN**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="persentase rumah terhuni"
php artisan test tests/Database/RekapDashboardTest.php
```

Expected: PASS.

---

### Task 3: Gunakan enum untuk kondisi rumah

**Objective:** Menghapus literal kondisi rumah tanpa membuat abstraction baru.

**Files:**
- Modify: `tests/Feature/MetricRingkasanTest.php`
- Modify: `app/Http/Controllers/RumahController.php:5-15,66-68`

**Step 1: Tulis test gagal berbasis source guard**

```php
it('menggunakan vocabulary baku kondisi rumah pada agregat kerusakan', function () {
    $sumber = file_get_contents(app_path('Http/Controllers/RumahController.php'));

    expect($sumber)
        ->toContain('KondisiRumah::TidakRusak->value')
        ->not->toContain("whereNot('kondisi', 'Tidak Rusak')");
});
```

**Step 2: Jalankan RED**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="vocabulary baku kondisi rumah"
```

Expected: FAIL karena controller masih memakai literal.

**Step 3: Implementasi minimum**

- Import `App\Enums\KondisiRumah`.
- Ganti literal dengan:

```php
'jumlahRusak' => Rumah::query()
    ->whereNot('kondisi', KondisiRumah::TidakRusak->value)
    ->count(),
```

**Step 4: Jalankan GREEN**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="vocabulary baku kondisi rumah"
php artisan test tests/Database/RumahTest.php
```

Expected: PASS.

---

### Task 4: Selaraskan label metric aset dengan grain query

**Objective:** Menghilangkan klaim `unit`/`jenis` ketika controller sebenarnya menghitung record penilaian umum.

**Files:**
- Modify: `tests/Feature/MetricRingkasanTest.php`
- Modify: `resources/views/pages/sp/fasilitas.blade.php:28-38`
- Modify: `resources/views/pages/sp/inventaris.blade.php:27-36`

**Step 1: Tulis test gagal**

Tambahkan pemeriksaan source/render yang memastikan:

```php
it('membedakan jumlah record aset dari total unitnya', function () {
    $fasilitas = file_get_contents(resource_path('views/pages/sp/fasilitas.blade.php'));
    $inventaris = file_get_contents(resource_path('views/pages/sp/inventaris.blade.php'));

    expect($fasilitas)
        ->toContain('label="Data Fasilitas"')
        ->toContain('label="Kondisi Baik" :nilai="$kondisiBaik" satuan="fasilitas"')
        ->toContain('label="Perlu Perbaikan" :nilai="$rusak" satuan="fasilitas"')
        ->and($inventaris)
        ->toContain('label="Jenis Barang" :nilai="$jenisBarang" satuan="jenis"')
        ->toContain('label="Perlu Perhatian" :nilai="$perluPerhatian" satuan="jenis"');
});
```

Jika format multiline membuat source assertion rapuh, render route dan gunakan `assertSeeInOrder`; jangan menguji class CSS.

**Step 2: Jalankan RED**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="membedakan jumlah record aset"
```

Expected: FAIL karena fasilitas/inventaris saat ini menyebut hasil `count()` sebagai unit atau jenis yang ambigu.

**Step 3: Implementasi minimum**

- Fasilitas:
  - `Jenis Fasilitas` → `Data Fasilitas`, satuan `fasilitas`.
  - `Kondisi Baik` dan `Perlu Perbaikan` → satuan `fasilitas`.
  - `Total Fasilitas` yang memakai `sum(jumlah)` tetap satuan `unit`.
- Inventaris:
  - Pertahankan `Jenis Barang`/`jenis` karena satu record merepresentasikan satu jenis barang pada satu SP, sesuai data dictionary.
  - `Sudah Diserahkan` tetap `jenis`.
  - `Perlu Perhatian` ubah dari `unit` menjadi `jenis`.
  - `Total Unit` tetap `unit`.
- Samakan label/satuan pada `x-sim.stat-card` fallback agar mode kartu dan strip tidak saling membantah.
- Jangan menyentuh controller dan jangan menjumlah `rincian_kondisi`; itu indikator berbeda.

**Step 4: Jalankan GREEN**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="membedakan jumlah record aset"
php artisan test tests/Database/AsetSpTest.php
```

Expected: PASS.

---

### Task 5: Nyatakan periode volume panen secara eksplisit

**Objective:** Mengubah label all-time yang menyesatkan menjadi metric tahun acuan yang jujur.

**Files:**
- Modify: `tests/Feature/MetricRingkasanTest.php`
- Modify: `app/Http/Controllers/KomoditasController.php:44-55`
- Modify: `app/Http/Controllers/SpController.php:164-182`
- Modify: `routes/internal.php:297-310`
- Modify: `resources/views/pages/komoditas/index.blade.php:50-52,66-68`
- Modify: `resources/views/pages/sp/detail.blade.php:193-195,215-217`
- Modify: `resources/views/pages/galeri-komponen.blade.php:38-39,54-55`

**Step 1: Tulis test gagal**

```php
it('menampilkan tahun acuan pada metric produksi', function () {
    $tahun = RekapDashboard::tahunTerakhir();

    $this->get(route('komoditas.index'))
        ->assertOk()
        ->assertViewHas('tahunPanen', $tahun)
        ->assertSee('Produksi Tahun '.$tahun);

    $sp = \App\Models\SatuanPermukiman::query()->firstOrFail();
    $this->get(route('sp.detail', $sp->id_satuan_permukiman))
        ->assertOk()
        ->assertViewHas('tahunPanen', $tahun)
        ->assertSee('Tahun '.$tahun);
});
```

Tambahkan assertion galeri hanya bila route tersedia pada environment testing (saat ini tersedia).

**Step 2: Jalankan RED**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="tahun acuan"
```

Expected: FAIL karena `tahunPanen` belum dikirim dan label masih “Total Panen Tercatat”/“Hasil panen terdata”.

**Step 3: Implementasi minimum**

- Di tiap producer payload, hitung sekali:

```php
$tahunPanen = RekapDashboard::tahunTerakhir();
```

- Komoditas:
  - panggil `RekapDashboard::sebaranKomoditas(tahun: $tahunPanen)` secara eksplisit;
  - kirim `tahunPanen`;
  - `Total Panen Tercatat` → `Produksi Tahun {{ $tahunPanen }}`;
  - keterangan → `Agregat kawasan pada tahun acuan`.
- Detail SP:
  - kirim `tahunPanen` yang sama dengan default `RekapDashboard::ringkasan($id)`;
  - keterangan volume → `Tahun {{ $tahunPanen }}`.
- Galeri:
  - kirim `tahunPanen` dari route;
  - keterangan volume → `Tahun {{ $tahunPanen }}`.
- Jangan mengubah helper menjadi all-time; rekap tahunan memang kontrak dashboard.

**Step 4: Jalankan GREEN**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="tahun acuan"
php artisan test tests/Database/KomoditasTest.php
php artisan test tests/Database/RekapDashboardTest.php
```

Expected: PASS. Jalankan file Database satu per satu.

---

### Task 6: Hapus konteks statis yang dapat basi

**Objective:** Memastikan keterangan metric tidak mengklaim rentang/geografi/kategori yang berbeda dari data runtime.

**Files:**
- Modify: `tests/Feature/MetricRingkasanTest.php`
- Modify: `resources/views/pages/transmigran/index.blade.php:82-89`
- Modify: `resources/views/pages/poktan/index.blade.php:28-37`
- Modify: `resources/views/pages/sp/index.blade.php:34-42`
- Modify: `resources/views/pages/penanaman/index.blade.php:41-49`
- Modify: `resources/views/pages/panen/index.blade.php:93-100`
- Modify: `resources/views/pages/galeri-komponen.blade.php:47-55`

**Step 1: Tulis test gagal**

```php
it('tidak menghardcode rentang SP atau contoh kategori pada metric', function () {
    $berkas = [
        resource_path('views/pages/transmigran/index.blade.php'),
        resource_path('views/pages/penanaman/index.blade.php'),
        resource_path('views/pages/panen/index.blade.php'),
    ];

    foreach ($berkas as $path) {
        $isi = file_get_contents($path);
        expect($isi)
            ->not->toContain('SP 1 sampai SP 4')
            ->not->toContain('Padi, jagung, palawija dll')
            ->not->toContain('Pangan & perkebunan')
            ->not->toContain('Musim tanam terdata');
    }
});
```

**Step 2: Jalankan RED**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="tidak menghardcode"
```

Expected: FAIL pada string lama.

**Step 3: Implementasi minimum**

Gunakan teks netral yang tetap benar ketika master data berubah:

- Transmigran `Kepala Keluarga`: `Dalam cakupan akses Anda`.
- Transmigran SP: `Tempat data tersebar` atau `Tersebar di {{ $totalSp }} SP`.
- Poktan/SP: `Dalam cakupan akses Anda`.
- Penanaman: `Kegiatan tanam terdata`; ragam komoditas: `Komoditas yang sudah ditanam`.
- Panen: `Komoditas dengan panen aktif`.
- Galeri: `Dalam cakupan akses Anda` dan tahun produksi dinamis dari Task 5.

Jangan mengambil nama kawasan baru hanya untuk teks dekoratif; itu query tambahan tanpa manfaat operasional.

**Step 4: Jalankan GREEN**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="tidak menghardcode"
```

Expected: PASS.

---

### Task 7: Jelaskan bahwa metric tidak mengikuti filter tabel

**Objective:** Menjaga agregat kawasan/cakupan akses yang memang disengaja sambil menghilangkan ambiguitas UX.

**Files:**
- Modify: `tests/Feature/MetricRingkasanTest.php`
- Modify: `resources/views/components/sim/halaman-daftar.blade.php:62-66`
- Modify: `resources/views/pages/transmigran/index.blade.php:62-66`
- Modify: `resources/views/pages/rumah/index.blade.php:54-58`
- Modify: `resources/views/pages/lahan/index.blade.php:52-55`
- Modify: `resources/views/pages/panen/index.blade.php:64-67`
- Modify: `resources/views/pages/pengaduan/index.blade.php:59-62` (sesuaikan dengan posisi aktual)

**Step 1: Tulis test gagal**

```php
it('menjelaskan cakupan ringkasan yang tidak berubah oleh filter tabel', function () {
    $this->get(route('transmigran.index', ['sp' => 1]))
        ->assertOk()
        ->assertSee('Seluruh data dalam cakupan akses; filter tabel tidak mengubah ringkasan.');

    $this->get(route('komoditas.index', ['tipe' => 'Pangan']))
        ->assertOk()
        ->assertSee('Seluruh data dalam cakupan akses; filter tabel tidak mengubah ringkasan.');
});
```

**Step 2: Jalankan RED**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="cakupan ringkasan"
```

Expected: FAIL karena penjelasan belum ada.

**Step 3: Implementasi minimum**

- Pada `x-sim.halaman-daftar`, tambahkan satu teks kecil di baris kontrol ringkasan; semua caller slot metric mendapat penjelasan otomatis.
- Tambahkan teks yang sama pada lima halaman daftar custom yang tidak memakai `halaman-daftar`.
- Jangan menambah prop/config baru; semua ringkasan daftar saat ini memang memakai cakupan akses penuh.
- Detail SP dan galeri tidak perlu teks ini karena tidak memiliki filter tabel yang memengaruhi konteks metric.

**Step 4: Jalankan GREEN**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="cakupan ringkasan"
```

Expected: PASS.

---

### Task 8: Gunakan scope SP existing pada galeri development

**Objective:** Menyamakan daftar SP galeri dengan cakupan pengguna tanpa query mapping khusus.

**Files:**
- Modify: `tests/Feature/MetricRingkasanTest.php`
- Modify: `routes/internal.php:305-308`

**Step 1: Tulis test gagal**

Gunakan pengguna role `PerSp`, tugaskan satu SP, lalu:

```php
it('membatasi pilihan SP galeri menurut cakupan pengguna', function () {
    // Buat role PerSp + user, attach satu SP, actingAs.
    // Request galeri dan pastikan viewData daftarSp hanya berisi SP tersebut.
});
```

Gunakan pola setup yang sudah ada pada `tests/Feature/InfrastrukturPengaduanRuntimeTest.php:54-72`; jangan membuat helper baru hanya untuk satu test.

**Step 2: Jalankan RED**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="pilihan SP galeri"
```

Expected: FAIL karena route saat ini memakai `SatuanPermukiman::query()->orderBy(...)->get()` tanpa `terlihatOlehPengguna()`.

**Step 3: Implementasi minimum**

Ganti mapping manual dengan helper existing:

```php
'daftarSp' => SatuanPermukiman::opsiTerlihat(),
```

Jangan mengubah akses route; galeri tetap hanya `local/testing`.

**Step 4: Jalankan GREEN**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php --filter="pilihan SP galeri"
php artisan test tests/Feature/InfrastrukturPengaduanRuntimeTest.php
```

Expected: PASS.

---

### Task 9: Verifikasi regresi lengkap dan runtime MySQL

**Objective:** Membuktikan perbaikan benar di SQLite test, MySQL test, runtime development, dan build frontend.

**Files:**
- No production changes expected.
- Review all files changed in Tasks 1-8.

**Step 1: Jalankan focused Feature test**

```bash
php artisan test tests/Feature/MetricRingkasanTest.php
```

Expected: seluruh test PASS.

**Step 2: Jalankan focused Database tests secara serial**

```bash
php artisan test tests/Database/RekapDashboardTest.php
php artisan test tests/Database/TransmigranTest.php
php artisan test tests/Database/RumahTest.php
php artisan test tests/Database/SpTest.php
php artisan test tests/Database/AsetSpTest.php
php artisan test tests/Database/KomoditasTest.php
```

Expected: seluruh file PASS. Jangan menjalankan file Database paralel karena semuanya berbagi schema MySQL test.

**Step 3: Jalankan test UI terkait**

```bash
php artisan test tests/Feature/HalamanTest.php
php artisan test tests/Feature/KontrasTest.php
```

Expected: PASS.

**Step 4: Jalankan formatter/lint**

```bash
vendor/bin/pint --test
```

Expected: PASS tanpa perubahan format tertunda.

**Step 5: Jalankan build**

```bash
npm run build
```

Expected: Vite build selesai tanpa error.

**Step 6: Smoke-test read-only database runtime**

Jalankan query/read-only Tinker untuk membandingkan payload dengan database development:

```bash
php artisan tinker --execute='dump(["penduduk_aktif" => App\Support\RekapDashboard::jumlahKkJiwa(), "tahun_panen" => App\Support\RekapDashboard::tahunTerakhir()]);'
```

Lalu request halaman utama terkait melalui test/client lokal dan pastikan:

- Total penduduk = helper populasi aktif.
- Detail SP menunjukkan persentase rumah terhuni berbasis total rumah.
- Komoditas/detail SP/galeri menyebut tahun produksi.
- Kondisi fasilitas/inventaris tidak lagi disebut unit bila query-nya `count()` record.
- Tidak ada `SP 1 sampai SP 4`.

**Step 7: Periksa scope dan diff**

```bash
git diff --check
git status --short
git diff --stat
git diff -- app/ resources/views/ routes/ tests/Feature/MetricRingkasanTest.php
```

Expected:

- Tidak ada migration/dependency baru.
- `metric-strip.blade.php` dan `metric-item.blade.php` tidak perlu berubah.
- Tidak ada perubahan domain di luar metric/caller terkait.

**Step 8: Commit hanya setelah semua verifikasi hijau**

Jika implementasi memang diminta untuk di-commit:

```bash
git add app/Http/Controllers/TransmigranController.php \
        app/Http/Controllers/RumahController.php \
        app/Http/Controllers/SpController.php \
        app/Http/Controllers/KomoditasController.php \
        app/Support/RekapDashboard.php \
        routes/internal.php \
        resources/views/components/sim/halaman-daftar.blade.php \
        resources/views/pages/ \
        tests/Feature/MetricRingkasanTest.php
git commit -m "fix(ui): selaraskan arti dan cakupan metric ringkasan"
```

Jangan push kecuali diminta.

---

## 3. File yang Kemungkinan Berubah

### Production PHP

- `app/Support/RekapDashboard.php`
- `app/Http/Controllers/TransmigranController.php`
- `app/Http/Controllers/RumahController.php`
- `app/Http/Controllers/SpController.php`
- `app/Http/Controllers/KomoditasController.php`
- `routes/internal.php`

### Blade

- `resources/views/components/sim/halaman-daftar.blade.php`
- `resources/views/pages/transmigran/index.blade.php`
- `resources/views/pages/rumah/index.blade.php`
- `resources/views/pages/lahan/index.blade.php`
- `resources/views/pages/panen/index.blade.php`
- `resources/views/pages/pengaduan/index.blade.php`
- `resources/views/pages/poktan/index.blade.php`
- `resources/views/pages/sp/index.blade.php`
- `resources/views/pages/sp/detail.blade.php`
- `resources/views/pages/sp/fasilitas.blade.php`
- `resources/views/pages/sp/inventaris.blade.php`
- `resources/views/pages/penanaman/index.blade.php`
- `resources/views/pages/komoditas/index.blade.php`
- `resources/views/pages/galeri-komponen.blade.php`

### Tests

- Create: `tests/Feature/MetricRingkasanTest.php`

## 4. Non-goals / Yang Sengaja Tidak Dibangun

- Tidak mengubah komponen `metric-strip`/`metric-item`; keduanya sudah presentation-only dan benar.
- Tidak membuat service/repository metric baru.
- Tidak menambah migration atau kolom agregat.
- Tidak menambah dependency.
- Tidak membuat metric mengikuti setiap filter tabel; desain kawasan-penuh dipertahankan dan dijelaskan.
- Tidak mengganti kondisi umum aset dengan histogram `rincian_kondisi`; keduanya punya arti domain berbeda.
- Tidak mengubah data demo/seed runtime hanya demi membuat angka terlihat lebih bagus.
- Tidak mengubah semua label aplikasi di luar call site metric yang diaudit.

## 5. Risiko dan Mitigasi

1. **Global scope Per-SP:** helper penduduk dan daftar SP harus tetap memakai global scope aktif. Verifikasi dengan test role `PerSp`.
2. **Tahun produksi:** `tahunTerakhir()` adalah tahun panen terakhir terlihat pada cakupan pengguna. Jangan hardcode tahun atau menggunakan `date('Y')` sebagai label.
3. **Mode kartu vs strip:** setiap koreksi label/satuan harus diterapkan ke keduanya agar saklar tampilan tidak menghasilkan arti berbeda.
4. **Grain aset:** `count()` record bukan unit; jangan mengubah query menjadi histogram tanpa keputusan produk baru karena aturan domain menyebut `kondisi` umum sebagai sumber badge/cacah.
5. **Shared MySQL schema:** semua Database tests wajib serial; kegagalan DDL akibat test paralel bukan defect produk.

## 6. Acceptance Criteria

- [ ] Seluruh 67 nilai metric tetap database-backed; tidak ada nilai numerik hardcoded baru.
- [ ] Total Penduduk pada daftar transmigran sama dengan definisi populasi aktif `RekapDashboard`.
- [ ] Persentase Rumah Terhuni pada detail SP menggunakan total rumah sebagai denominator.
- [ ] Agregat kondisi rumah memakai `KondisiRumah::TidakRusak->value`.
- [ ] Metric `count()` fasilitas/inventaris tidak disebut `unit`.
- [ ] `Total Unit` fasilitas/inventaris tetap memakai `sum(jumlah)`.
- [ ] Volume produksi pada komoditas, detail SP, dan galeri menyebut tahun acuan.
- [ ] Tidak ada teks `SP 1 sampai SP 4` atau contoh kategori yang dapat bertentangan dengan master runtime.
- [ ] Pengguna memahami bahwa filter tabel tidak mengubah ringkasan kawasan/cakupan akses.
- [ ] Daftar SP galeri development mengikuti cakupan pengguna.
- [ ] Focused Feature tests, Database tests serial, HalamanTest, KontrasTest, Pint, dan `npm run build` lulus.
- [ ] Runtime MySQL development menghasilkan angka yang sama dengan payload halaman.
- [ ] Tidak ada migration, dependency, atau abstraction baru.
