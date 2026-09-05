<?php

/**
 * Uji penjaga pengaturan penilaian kondisi SP.
 *
 * Tiga tuas menentukan skor sebuah satuan permukiman: nilai kondisi aset,
 * bobot parameter, dan ambang predikat. Sampai 2026-08-21 hanya yang pertama
 * dapat disunting, sedangkan dua sisanya terkunci di dalam kode. Separuh
 * perhitungan dapat diatur dinas, separuhnya tidak.
 *
 * Berkas ini menjaga agar ketiganya tetap berupa data, dan menjaga batas yang
 * sengaja dipasang: jumlah status tetap tiga, tingkat parameter primer
 * terkunci, dan parameter tidak dapat ditambah lewat antarmuka.
 */

use App\Enums\JenisDaftarPilihan;
use App\Enums\StatusKondisiSp;
use App\Enums\TingkatKebutuhan;
use App\Helpers\MenuHelper;
use App\Models\ParameterPenilaianSp;
use App\Models\StatusKondisiSp as StatusKondisiSpModel;
use App\Support\DummyData;
use App\Support\PenilaianKondisiSp;
use Illuminate\Support\Facades\Route;

it('menghasilkan parameter dari data master jenis, bukan daftar tulis tangan', function () {
    // Cacat yang diperbaiki: daftar parameter dahulu ditulis tangan tiga belas
    // baris, sehingga jenis yang ditambahkan Admin tidak pernah ikut dinilai.
    // Dropdownnya hidup, petugas dapat mendata asetnya, tetapi skor SP tidak
    // berubah sama sekali.
    $jenisInfra = array_column(DummyData::daftarPilihan(JenisDaftarPilihan::JenisInfrastruktur), 'nilai');
    $jenisFasilitas = array_column(DummyData::daftarPilihan(JenisDaftarPilihan::JenisFasilitas), 'nilai');

    $nilaiJenis = array_column(DummyData::parameterPenilaian(), 'nilai_jenis');

    // SETIAP jenis punya barisnya, termasuk yang belum dinilai.
    foreach (array_merge($jenisInfra, $jenisFasilitas) as $jenis) {
        expect($nilaiJenis)->toContain($jenis);
    }

    expect(DummyData::parameterPenilaian())
        ->toHaveCount(count($jenisInfra) + count($jenisFasilitas));

    // Sumbernya disimpulkan dari jenisnya, tidak diisi manual.
    foreach (DummyData::parameterPenilaian() as $p) {
        $harusnya = $p['jenis'] === JenisDaftarPilihan::JenisFasilitas->value ? 'Fasilitas' : 'Infrastruktur';

        expect($p['sumber'])->toBe($harusnya);
    }
});

it('mengikutkan empat parameter yang dahulu terlewat', function () {
    // POS KAMLING di SP Weain berkondisi Rusak Berat terdata sejak awal,
    // tampil pada daftar fasilitas, dan tidak menyumbang apa pun pada skor
    // semata karena daftar parameter berhenti di baris ke tiga belas.
    $dinilai = array_column(DummyData::parameterDinilai(), 'nilai_jenis');

    expect($dinilai)->toContain('Pendidikan Lanjutan')
        ->toContain('Pasar atau Kios')
        ->toContain('Olahraga')
        ->toContain('Keamanan');

    // Parameter keamanan benar-benar terbaca pada penilaian SP Weain.
    $rincian = collect(PenilaianKondisiSp::nilai(6)['rincian'])->keyBy('kode');

    expect($rincian)->toHaveKey('keamanan');
});

it('tidak menilai jenis Lainnya, sebab ia penampung bukan satu jenis barang', function () {
    // Menilai "ketersediaan Lainnya" berarti memberi nilai penuh kepada SP
    // yang memiliki satu benda tak jelas. Dikecualikan lewat penanda pada
    // data, bukan lewat pengecualian khusus di dalam kode.
    foreach (DummyData::parameterPenilaian() as $p) {
        if ($p['nilai_jenis'] === 'Lainnya') {
            expect($p['is_dinilai'])->toBeFalse();
        }
    }

    expect(array_column(DummyData::parameterDinilai(), 'nilai_jenis'))
        ->not->toContain('Lainnya');
});
it('membaca bobot dari data, bukan dari konstanta di dalam kode', function () {
    // Bobot sudah dijanjikan berupa data sejak awal (data-dictionary 5.4 dan
    // rules.md 10c poin 13), tetapi antarmukanya belum pernah dibuat sehingga
    // nilainya tetap terbaca dari TingkatKebutuhan::bobotBawaan().
    $berkas = file_get_contents(app_path('Support/PenilaianKondisiSp.php'));

    expect($berkas)->toContain('ParameterPenilaianSp::query()');
    expect($berkas)->not->toContain("'bobot' => \$p->bobotBawaan()");

    // Hanya parameter yang dicentang yang menambah pembagi skor.
    expect(PenilaianKondisiSp::parameter())
        ->toHaveCount(ParameterPenilaianSp::where('is_dinilai', true)->count());

    $totalBobot = array_sum(array_column(PenilaianKondisiSp::parameter(), 'bobot'));

    expect($totalBobot)->toBe(37);
});

it('membaca ambang predikat dari data, bukan dari angka di dalam kode', function () {
    $berkas = file_get_contents(app_path('Enums/StatusKondisiSp.php'));

    expect($berkas)->toContain('StatusKondisiSpModel::query()');
    expect($berkas)->not->toContain('$skor >= 80');
    expect($berkas)->not->toContain('$skor >= 55');

    // Perilakunya tetap sama seperti sebelum ambang menjadi data.
    expect(StatusKondisiSp::dariSkor(80.0, false))->toBe(StatusKondisiSp::Mandiri)
        ->and(StatusKondisiSp::dariSkor(79.9, false))->toBe(StatusKondisiSp::Berkembang)
        ->and(StatusKondisiSp::dariSkor(55.0, false))->toBe(StatusKondisiSp::Berkembang)
        ->and(StatusKondisiSp::dariSkor(54.9, false))->toBe(StatusKondisiSp::PerluPenanganan);

    // Aturan primer nol tetap mengalahkan ambang berapa pun.
    expect(StatusKondisiSp::dariSkor(100.0, true))->toBe(StatusKondisiSp::PerluPenanganan);
});

it('membaca wording status dari data agar dinas dapat memakai istilahnya sendiri', function () {
    // Nilai enum tetap menjadi kunci di dalam sistem, sedangkan yang tampil di
    // layar dibaca dari data.
    foreach (StatusKondisiSpModel::all() as $baris) {
        $status = StatusKondisiSp::from($baris->kode);

        expect($status->label())->toBe($baris->nama);
        expect($status->keterangan())->toBe($baris->keterangan);
    }

    $berkas = file_get_contents(app_path('Enums/StatusKondisiSp.php'));

    expect($berkas)->toContain('StatusKondisiSpModel::where');
});

it('menjaga status tetap tiga dan tanpa rute tambah maupun hapus', function () {
    // dariSkor() hanya mengembalikan tiga keluaran, sehingga status keempat
    // tidak akan pernah tercapai satuan permukiman mana pun. Menyediakan
    // penambahan berarti menjanjikan sesuatu yang tidak dapat ditepati.
    expect(StatusKondisiSp::cases())->toHaveCount(3);
    expect(DummyData::statusKondisiSp())->toHaveCount(3);

    expect(Route::has('penilaian-kondisi.tambah'))->toBeFalse();
    expect(Route::has('penilaian-kondisi.hapus'))->toBeFalse();

    // Kewenangannya pun hanya lihat dan ubah.
    foreach ([1, 2, 3, 4] as $idRole) {
        $izin = DummyData::izinRole($idRole)['penilaian_kondisi'] ?? [];

        expect($izin)->not->toContain('tambah');
        expect($izin)->not->toContain('hapus');
    }
});

it('mengunci ambang status terendah pada nol sebagai penampung sisa', function () {
    // Tanpa penampung sisa ada skor yang tidak mendapat status sama sekali.
    $status = DummyData::statusKondisiSp();

    usort($status, fn ($a, $b) => $a['urutan'] <=> $b['urutan']);

    expect(end($status)['ambang_bawah'])->toBe(0);

    // Ambang wajib menurun mengikuti urutan; bila terbalik, status tengah
    // tidak akan pernah tercapai sebab pembacaan berhenti pada ambang
    // tertinggi yang cocok lebih dulu.
    $sebelumnya = null;

    foreach ($status as $baris) {
        if ($sebelumnya !== null) {
            expect($baris['ambang_bawah'])->toBeLessThan($sebelumnya);
        }

        $sebelumnya = $baris['ambang_bawah'];
    }
});
it('mengunci tingkat tiga parameter primer', function () {
    // Memindahkan Listrik ke Tersier bukan menurunkan bobotnya, melainkan
    // mencabut aturan yang membuat SP tanpa listrik otomatis berstatus Perlu
    // Penanganan. Itu perubahan kebijakan, bukan penyetelan angka.
    $primer = array_filter(
        DummyData::parameterDinilai(),
        fn ($p) => $p['tingkat'] === TingkatKebutuhan::Primer
    );

    expect(array_column($primer, 'kode'))
        ->toBe(['air_bersih', 'listrik', 'jalan_penghubung']);

    $form = file_get_contents(resource_path('views/pages/master/form-parameter-penilaian.blade.php'));

    expect($form)->toContain('primerTerkunci');
    expect($form)->toContain(':disabled="tingkatTerkunci"');

    // Jenis dan kode tidak boleh menjadi isian: barisnya dihasilkan dari data
    // master, dan kode tersalin ke riwayat penilaian.
    expect($form)->not->toContain('name="jenis"');
    expect($form)->not->toContain('name="kode"');
});

it('menyediakan halaman pengaturan penilaian beserta kedua tabnya', function () {
    $isi = $this->get(route('master.penilaian-kondisi'))->assertOk()->getContent();

    // Dua tab boleh di sini, berbeda dari data master daftar pilihan yang tabnya
    // dibongkar menjadi kartu: yang membatasi lebar judul terhadap wadahnya,
    // bukan cacah tabnya (ui-spec.md 5.1d).
    expect(substr_count($isi, 'role="tab"'))->toBe(2);

    // Seluruh jenis tampil, termasuk yang belum dinilai.
    foreach (DummyData::parameterPenilaian() as $p) {
        expect($isi)->toContain($p['nilai_jenis']);
    }

    // Ketiga status beserta ambangnya.
    foreach (DummyData::statusKondisiSp() as $s) {
        expect($isi)->toContain($s['nama']);
    }

    expect($isi)->toContain('penampung sisa');
    expect($isi)->toContain('name="is_dinilai"');
    expect($isi)->toContain('name="bobot"');
    expect($isi)->toContain('name="ambang_bawah"');
});

it('memisahkan menu data master dari pengaturan sistem', function () {
    // Menu sebelumnya berisi enam butir dan akan menjadi delapan. Masalahnya
    // bukan sekadar panjang: isinya memang dua hal berbeda, dan tiga butir
    // pertama bahkan sudah berawalan "Data Master".
    $menu = collect(MenuHelper::definisiMenu())
        ->firstWhere('title', 'Administrasi Sistem');

    $nama = array_column($menu['items'], 'name');

    expect($nama)->toBe(['Data Master', 'Pengelolaan Konten', 'Pengaturan Sistem', 'Bantuan & Info']);

    $dataMaster = collect($menu['items'])->firstWhere('name', 'Data Master');
    $pengaturan = collect($menu['items'])->firstWhere('name', 'Pengaturan Sistem');

    expect(array_column($dataMaster['subItems'], 'name'))
        ->toBe(['Wilayah', 'Satuan', 'Daftar Pilihan', 'Penilaian Kondisi SP']);

    expect(array_column($pengaturan['subItems'], 'name'))
        ->toBe(['Pengguna', 'Role & Hak Akses', 'Audit Log']);
});

it('menyebarkan data contoh ke seluruh status agar tampilannya dapat ditinjau', function () {
    // Sebaran semula timpang: 1 Mandiri, 1 Berkembang, 4 Perlu Penanganan.
    // Dua status pertama masing-masing hanya punya satu contoh untuk memeriksa
    // lencana, kartu rekap, dan penyortiran.
    $rekap = PenilaianKondisiSp::rekapStatus();

    foreach (StatusKondisiSp::cases() as $status) {
        expect($rekap[$status->value])->toBe(2, "Status {$status->value} tidak diwakili dua SP");
    }

    // Sedikitnya satu SP kena aturan primer nol, agar aturan itu terlihat di
    // layar dan tidak hanya hidup di dalam uji.
    $primerNol = array_filter(PenilaianKondisiSp::nilaiSeluruhSp(), fn ($p) => $p['ada_primer_nol']);

    expect($primerNol)->not->toBeEmpty();
});
