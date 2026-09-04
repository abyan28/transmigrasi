<?php

/**
 * Uji perenderan halaman Tahap 2.
 *
 * Menjaga agar halaman yang sudah dibangun tetap merender tanpa galat dan
 * tetap memenuhi aturan yang mudah tergeser tanpa disadari, terutama larangan
 * kontrol mati (ANTISLOP-ID R-24 dan R-26) serta kewajiban penanda data contoh
 * (R-17 dan R-38).
 */

use App\Enums\Agama;
use App\Enums\AksiPermission;
use App\Enums\AlasanPergantianKK;
use App\Enums\AsalWakilPoktan;
use App\Enums\BentukWilayah;
use App\Enums\BidangPengaduan;
use App\Enums\HubunganAnggotaKeluarga;
use App\Enums\JabatanAnggotaPoktan;
use App\Enums\JenisSaprotan;
use App\Enums\KegiatanAnggota;
use App\Enums\Kondisi;
use App\Enums\PendidikanTerakhir;
use App\Enums\PolaPermukiman;
use App\Enums\StatusAnggotaKeluarga;
use App\Enums\StatusPanen;
use App\Enums\StatusPengaduan;
use App\Enums\SumberDana;
use App\Enums\TingkatKesuburanTanah;
use App\Helpers\MenuHelper;
use App\Helpers\RemahHelper;
use App\Models\Pengaduan;
use App\Models\SatuanPermukiman;
use App\Models\User;
use App\Support\DummyData;
use App\Support\LaporanData;
use App\Support\SkemaImpor;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\Support\BerkasBlade;

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

it('merender dashboard beserta seluruh wadah grafiknya', function () {
    $respons = $this->get(route('beranda'));

    $respons->assertOk();

    // Sebelas wadah grafik memuat 17 indikator; sebagian indikator disajikan
    // sebagai kartu statistik dan tabel, sesuai pemetaan ui-spec.md bagian 9.
    foreach ([
        'grafikPenduduk', 'grafikKomoditas', 'grafikPendapatan', 'grafikMutasiKk',
        'grafikPenghuni', 'grafikPekerjaan', 'grafikPanen', 'grafikHarga',
        'grafikInfrastruktur', 'grafikStatusPengaduan',
    ] as $idGrafik) {
        $respons->assertSee('id="'.$idGrafik.'"', false);
        $respons->assertSee("buatGrafik('".$idGrafik."'", false);
    }
});

it('menyediakan tabel data alternatif untuk setiap grafik', function () {
    // Grafik wajib punya padanan tabel agar isinya terbaca pembaca layar
    // (agents/ui-spec.md bagian 9 poin 7). Jumlahnya dibandingkan terhadap
    // banyaknya grafik, bukan angka tetap, agar grafik baru yang lupa diberi
    // tabel langsung tertangkap tanpa perlu menyunting uji ini.
    $isi = $this->get(route('beranda'))->getContent();

    $jumlahGrafik = substr_count($isi, 'buatGrafik(');
    $jumlahTabel = substr_count($isi, 'Lihat tabel data');

    expect($jumlahTabel)->toBe($jumlahGrafik)
        ->and($jumlahGrafik)->toBeGreaterThan(0);
});

it('menampilkan penanda data contoh pada dashboard', function () {
    $this->get(route('beranda'))->assertSee('Data contoh');
});

it('menampilkan angka ringkasan dalam format Indonesia', function () {
    $ringkasan = DummyData::ringkasanDashboard();

    // Titik sebagai pemisah ribuan (agents/rules.md bagian 13.3 poin 3).
    $this->get(route('beranda'))
        ->assertSee(number_format($ringkasan['jumlah_kk'], 0, ',', '.'))
        ->assertSee(number_format($ringkasan['volume_panen_ton'], 3, ',', '.'));
});

it('menyimpan filter dashboard pada query string', function () {
    // Filter belum menyaring data sampai Task 9.2, tetapi kontrolnya sudah
    // nyata: pilihan bertahan setelah halaman dimuat ulang.
    $this->get(route('beranda', ['sp' => 2]))
        ->assertOk()
        ->assertSee('Bersihkan');
});

/*
|--------------------------------------------------------------------------
| Drill-down per satuan permukiman
|--------------------------------------------------------------------------
*/

it('merender halaman rincian untuk keenam satuan permukiman', function () {
    foreach (DummyData::satuanPermukiman() as $sp) {
        $this->get(route('sp.detail', $sp['id_satuan_permukiman']))
            ->assertOk()
            ->assertSee($sp['nama'])
            ->assertSee($sp['kecamatan']);
    }
});

it('membalas 404 untuk satuan permukiman yang tidak ada', function () {
    // Alamat karangan tidak boleh menghasilkan halaman kosong yang membingungkan.
    $this->get('/sp/99')->assertNotFound();
    $this->get('/sp/0')->assertNotFound();
});

it('mengalihkan alamat lama dashboard.sp ke rute RESTful sp.detail', function () {
    $this->get('/dashboard/sp/1')
        ->assertStatus(301)
        ->assertRedirect(route('sp.detail', 1));
});

it('menampilkan hanya data milik satuan permukiman yang dibuka', function () {
    // SP Tniumanu memuat PETRUS NAHAK, bukan YOHANES BERE yang ada di SP Kapitan Meo.
    $this->get(route('sp.detail', 2))
        ->assertSee('PETRUS NAHAK')
        ->assertDontSee('YOHANES BERE');
});

it('menyediakan enam tab rincian beserta label domainnya', function () {
    $respons = $this->get(route('sp.detail', 1));

    foreach (['Ringkasan & Kondisi', 'Warga & Hunian (', 'Pertanian & Lahan (', 'Aset & Fasilitas (', 'Pengaduan (', 'Keadaan Wilayah'] as $label) {
        $respons->assertSee($label);
    }
});

it('menyertakan tabel alternatif untuk grafik halaman rincian SP', function () {
    $isi = $this->get(route('sp.detail', 1))->getContent();

    expect(substr_count($isi, 'Lihat tabel data'))->toBe(substr_count($isi, 'buatGrafik('));
});

it('menampilkan keadaan kosong pada tab yang tidak punya data', function () {
    // SP Tualaran belum memiliki data rumah, lahan, panen, dan infrastruktur
    // pada data contoh, sehingga keempat tabnya wajib memakai keadaan kosong.
    $this->get(route('sp.detail', 5))->assertSee('Belum ada data');
});

it('menautkan dashboard kawasan ke rincian setiap SP', function () {
    // Penelusuran wajib punya jalur teks, bukan hanya klik pada grafik, agar
    // tetap dapat dijangkau pengguna keyboard (ANTISLOP-ID R-32).
    $respons = $this->get(route('beranda'));

    foreach (DummyData::rekapPerSp() as $baris) {
        $respons->assertSee(route('sp.detail', $baris['satuan_permukiman_id']), false);
    }
});

it('memasang penelusuran klik pada grafik bersumbu satuan permukiman', function () {
    $this->get(route('beranda'))
        ->assertSee('id="grafikPerSp"', false)
        ->assertSee('drilldownSp(data.spId,', false)
        ->assertSee(url('/sp'), false);
});

/*
|--------------------------------------------------------------------------
| Profil dan kata sandi
|--------------------------------------------------------------------------
*/

it('merender halaman profil beserta identitas akun', function () {
    // Task 3.13: profil membaca Auth::user() sungguhan, bukan DummyData.
    $pengguna = User::factory()->create([
        'nama' => 'PETUGAS UJI PROFIL',
        'username' => 'petugas.uji.profil',
    ]);

    $this->actingAs($pengguna)->get(route('profil'))
        ->assertOk()
        ->assertSee('PETUGAS UJI PROFIL')
        ->assertSee('petugas.uji.profil')
        ->assertSee($pengguna->role->nama);
});

it('menampilkan nama dan username sebagai teks, bukan isian yang dapat diubah', function () {
    // Keduanya hanya dapat diubah admin (agents/rules.md bagian 14b poin 1),
    // sehingga tidak boleh dirender sebagai input yang tampak dapat disunting.
    $isi = $this->get(route('profil'))->getContent();

    expect($isi)->not->toContain('name="nama"')
        ->and($isi)->not->toContain('name="username"');
});

it('merender halaman ubah kata sandi beserta ketiga isiannya', function () {
    $this->get(route('profil.kata-sandi'))
        ->assertOk()
        ->assertSee('name="password_lama"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false);
});

it('merender halaman wajib ganti kata sandi tanpa meminta kata sandi lama', function () {
    // Pengguna baru saja menerima kata sandi sementara dari admin dan sudah
    // membuktikan kepemilikan lewat proses masuk.
    $respons = $this->get(route('ganti-kata-sandi'));

    $respons->assertOk()->assertSee('name="password"', false);

    expect($respons->getContent())->not->toContain('name="password_lama"');
});

it('menyediakan jalan keluar dari halaman wajib ganti kata sandi', function () {
    // Seluruh halaman lain terkunci, sehingga tombol keluar wajib ada agar
    // pengguna tidak terjebak tanpa pilihan.
    $this->get(route('ganti-kata-sandi'))->assertSee(route('logout'), false);
});

/*
|--------------------------------------------------------------------------
| Modul transmigran
|--------------------------------------------------------------------------
*/

it('merender daftar transmigran beserta seluruh barisnya', function () {
    $respons = $this->get(route('transmigran.index'));

    $respons->assertOk();

    foreach (DummyData::transmigran() as $baris) {
        $respons->assertSee($baris['nama_kepala_keluarga'])->assertSee($baris['nik']);
    }
});

it('menyaring daftar transmigran menurut kata kunci', function () {
    $this->get(route('transmigran.index', ['cari' => 'YOHANES']))
        ->assertOk()
        ->assertSee('YOHANES BERE')
        ->assertDontSee('PETRUS NAHAK');
});

it('mencari transmigran memakai NIK maupun nomor KK', function () {
    $this->get(route('transmigran.index', ['cari' => '5321011505800001']))
        ->assertSee('YOHANES BERE')
        ->assertDontSee('MARIA DA COSTA');
});

it('menyaring daftar transmigran menurut satuan permukiman', function () {
    // SP Tniumanu hanya memuat PETRUS NAHAK pada data contoh.
    $this->get(route('transmigran.index', ['sp' => 2]))
        ->assertSee('PETRUS NAHAK')
        ->assertDontSee('YOHANES BERE');
});

it('menampilkan keadaan pencarian nihil beserta jalan keluarnya', function () {
    // Keadaan ini wajib dibedakan dari daftar yang memang belum berisi data
    // (agents/ui-spec.md bagian 7).
    $this->get(route('transmigran.index', ['cari' => 'ZZZTIDAKADA']))
        ->assertSee('Tidak ditemukan hasil untuk')
        ->assertSee('Bersihkan Filter');
});

it('merender rincian setiap transmigran', function () {
    foreach (DummyData::transmigran() as $baris) {
        $this->get(route('transmigran.detail', $baris['id_transmigran']))
            ->assertOk()
            ->assertSee($baris['nama_kepala_keluarga'])
            ->assertSee($baris['no_kk']);
    }
});

it('membalas 404 untuk transmigran yang tidak ada', function () {
    $this->get('/transmigran/99')->assertNotFound();
});

it('menautkan rincian transmigran ke satuan permukimannya', function () {
    $this->get(route('transmigran.detail', 1))
        ->assertSee(route('sp.detail', 1), false);
});

it('menyediakan seluruh rute tulis modul transmigran', function () {
    // Setiap tombol pada antarmuka wajib punya tujuan nyata, bukan kontrol mati.
    // Halaman daftar memuat seluruh tindakan tulis: tambah lewat modal, ubah
    // lewat pola aksi per baris, dan hapus lewat kolom aksi.
    $isi = $this->get(route('transmigran.index'))->getContent();

    foreach ([
        route('transmigran.simpan'),
        route('transmigran.hapus', 1),
    ] as $tujuan) {
        expect($isi)->toContain($tujuan);
    }
});

it('memakai nama kolom kamus data pada isian form', function () {
    // Nama isian mengikuti agents/data-dictionary.md bagian 6.1, sehingga Form
    // Request pada Tahap 5 dapat membaca nama yang sama tanpa menyunting Blade.
    $isi = $this->get(route('transmigran.index'))->getContent();

    foreach ([
        'nama_kepala_keluarga', 'nik', 'no_kk', 'jenis_kelamin', 'agama', 'tempat_lahir',
        'tanggal_lahir', 'pendidikan_terakhir', 'pekerjaan_kepala_keluarga',
        'pendapatan_per_bulan', 'daerah_asal_kabupaten_id',
        'tahun_kedatangan', 'status_tinggal', 'telepon',
        'keterangan', 'satuan_permukiman_id',
    ] as $kolom) {
        expect($isi)->toContain('name="'.$kolom.'"');
    }

    /*
        `dokumen_pendukung` DICABUT dari daftar di atas pada Putaran 14.

        KTP, KK, dan SK penempatan adalah tiga dokumen berbeda, tetapi dulu
        berbagi satu kolom, sehingga mengunggah KK menimpa KTP yang sudah ada
        tanpa peringatan apa pun. Putaran 12 memindahkannya ke registri
        `berkas` lewat pivot berperan, dan kolomnya sudah tidak ada lagi pada
        schema.sql. Ketiganya kini punya isian sendiri-sendiri.
    */
    foreach (['ktp', 'kk', 'sk'] as $peran) {
        expect($isi)->toContain('name="'.$peran.'[]"');
    }

    expect($isi)->not->toContain('name="dokumen_pendukung"');

    // `status_anggota_poktan` sengaja BUKAN isian (rules.md 7a.8). Nilainya
    // turunan dari keanggotaan berstatus Aktif pada `anggota_poktan`, dan
    // menyediakannya sebagai pilihan Ya/Tidak di sini menciptakan dua sumber
    // kebenaran yang tidak pernah tersinkron: petugas dapat menyatakan "Ya"
    // tanpa seorang pun mendaftarkannya ke kelompok mana pun.
    expect($isi)->not->toContain('name="status_anggota_poktan"');

    // `jumlah_anggota_keluarga` DAN `usia` sengaja BUKAN isian sejak
    // 2026-08-28 (Rombongan B). Keduanya diturunkan: jumlah dari cacah baris
    // Anggota Keluarga, usia dari tanggal lahir. Menyediakannya sebagai isian
    // membuat nilainya dapat berselisih dengan sumbernya (erd.md 7.4 dibalik).
    expect($isi)->not->toContain('name="jumlah_anggota_keluarga"');
    expect($isi)->not->toContain('name="usia"');
});

/*
|--------------------------------------------------------------------------
| Anggota keluarga, usia, dan agama (Rombongan B, 2026-08-28)
|--------------------------------------------------------------------------
|
| Membalik keputusan erd.md 7.4 ("sistem tidak mendata anggota keluarga satu
| per satu") atas permintaan pemilik proyek. jumlah_anggota_keluarga jadi
| turunan, usia dihitung dari tanggal lahir.
*/

it('menurunkan jumlah anggota keluarga dari cacah baris aktif, bukan menyimpannya', function () {
    // Anggota yang meninggal atau pindah (Putaran 6) tetap tercatat tetapi
    // tidak lagi dihitung sebagai jiwa keluarga.
    $cacah = [];
    foreach (DummyData::anggotaKeluarga() as $a) {
        if ($a['status'] !== StatusAnggotaKeluarga::Aktif->value) {
            continue;
        }
        $cacah[$a['transmigran_id']] = ($cacah[$a['transmigran_id']] ?? 0) + 1;
    }

    foreach (DummyData::transmigran() as $t) {
        $harusnya = 1 + ($cacah[$t['id_transmigran']] ?? 0);
        expect($t['jumlah_anggota_keluarga'])->toBe($harusnya, "keluarga {$t['id_transmigran']}");
    }

    // Buktikan datanya bisa membedakan benar dari salah: minimal satu keluarga
    // punya anggota, sehingga turunannya bukan sekadar 1 untuk semua.
    expect(max(array_column(DummyData::transmigran(), 'jumlah_anggota_keluarga')))->toBeGreaterThan(1);

    // Dan minimal satu anggota non-aktif ada, supaya penyaringan di atas
    // benar-benar teruji.
    expect(collect(DummyData::anggotaKeluarga())->contains(fn ($a) => $a['status'] !== 'Aktif'))
        ->toBeTrue('Data contoh wajib memuat anggota keluarga yang meninggal atau pindah');
});

it('menandai status anggota keluarga dan menyediakan pencatatan peristiwa', function () {
    // Putaran 6: anggota yang meninggal atau pindah tetap tampil, ditandai
    // badge, dan tidak lagi punya tombol "Catat Peristiwa".
    $isi = $this->get('/transmigran/3')->assertOk()->getContent();

    // Anggota non-aktif (ROSALIA SERAN, id 12) tetap dirender beserta
    // tanggal peristiwanya.
    expect($isi)
        ->toContain('ROSALIA SERAN')
        ->toContain('Meninggal')
        ->toContain('Catat Peristiwa')
        ->toContain('formPeristiwaAnggota')
        ->toContain('catat-peristiwa')
        ->toContain(':id');

    // Kolom Status hadir di kepala tabel.
    expect($isi)->toContain('>Status</th>');
});

it('menyediakan rute pencatatan peristiwa anggota keluarga', function () {
    expect(Route::has('transmigran.anggota.catat-peristiwa'))->toBeTrue();

    $this->post('/transmigran/3/anggota/9/catat-peristiwa', [
        'status' => 'Pindah',
        'tanggal_peristiwa' => '2026-08-01',
        'keterangan_peristiwa' => 'Pindah ke Atambua.',
    ])->assertRedirect('/transmigran/3?tab=keluarga');

    // Sejak Task 5.2 rutenya sungguh menyimpan: peristiwa wajib disebut,
    // sehingga kiriman kosong ditolak alih-alih memulangkan pesan sukses.
    $this->post('/transmigran/3/anggota/10/catat-peristiwa')
        ->assertSessionHasErrors('status');
});

it('menyunting hanya anggota keluarga aktif pada form multi-langkah', function () {
    // Anggota non-aktif tidak boleh menjadi baris repeater yang tersunting;
    // ia hanya ditampilkan sebagai bacaan.
    $isi = $this->get('/transmigran/3')->assertOk()->getContent();

    expect($isi)->toContain('sudah dicatat meninggal atau pindah');
});

it('menghitung usia dari tanggal lahir pada halaman rincian transmigran', function () {
    $data = collect(DummyData::transmigran())->firstWhere('id_transmigran', 1);
    $usia = Carbon::parse($data['tanggal_lahir'])->age;

    $isi = $this->get('/transmigran/1')->assertOk()->getContent();

    // Usia tampil, dan nilainya persis hitungan Carbon (bukan angka tetap).
    expect($isi)->toContain($usia.' tahun');

    // Form tidak mengirim usia; ia dihitung ulang tiap kali dibaca.
    expect($isi)->not->toContain('name="usia"');
});

it('menyediakan tempat tampil bagi setiap kolom anggota keluarga', function () {
    // Penjaga 1f untuk tabel baru: tiap kolom yang diisi form wajib punya
    // tempat tampil di halaman rincian.
    $isi = $this->get('/transmigran/1')->assertOk()->getContent();

    $keluarga = collect(DummyData::anggotaKeluarga())
        ->firstWhere('transmigran_id', 1);

    expect($isi)
        ->toContain($keluarga['nama_lengkap'])
        ->toContain($keluarga['hubungan'])
        ->toContain('Kegiatan')
        ->toContain('Agama')
        ->toContain('NIK');

    // Anggota tanpa NIK ditandai, bukan dibiarkan kosong.
    $tanpaNik = collect(DummyData::anggotaKeluarga())
        ->first(fn ($a) => $a['transmigran_id'] === 1 && $a['nik'] === null);
    expect($tanpaNik)->not->toBeNull('Data contoh wajib memuat anggota tanpa NIK');
});

it('menyusun form anggota keluarga sebagai daftar dinamis bersyarat', function () {
    $isi = $this->get(route('transmigran.index'))->assertOk()->getContent();

    // Repeater: template x-for, tombol tambah, tombol hapus.
    expect($isi)
        ->toContain('x-for="(a, i) in anggota"')
        ->toContain('tambahAnggota()')
        ->toContain('hapusAnggota(i)');

    // Nama isian berpola larik agar Form Request Tahap 5 membacanya sebagai
    // `anggota_keluarga[i][kolom]`.
    expect($isi)->toContain('anggota_keluarga[${i}][nama_lengkap]');

    // Cabang bersyarat: pendidikan + pekerjaan + pendapatan hanya untuk yang
    // Bekerja; pendidikan tetap tampil untuk yang bersekolah. Diperiksa pada
    // keluaran terender, bukan sumber (pola penjaga varietas saprotan).
    expect($isi)
        ->toContain("a.kegiatan === 'Bekerja'")
        ->toContain("a.kegiatan && a.kegiatan !== 'Belum Sekolah'");
});

it('memvariasikan data contoh anggota keluarga sesuai cabang kegiatannya', function () {
    $anggota = DummyData::anggotaKeluarga();

    $hubungan = array_column($anggota, 'hubungan');
    expect($hubungan)->toContain('Istri')->toContain('Suami')->toContain('Anak');

    $kegiatan = array_column($anggota, 'kegiatan');
    expect($kegiatan)
        ->toContain('Bekerja')
        ->toContain('Masih Sekolah')
        ->toContain('Belum Sekolah');

    // Yang Bekerja mengisi pekerjaan + pendapatan; yang Belum Sekolah tidak.
    foreach ($anggota as $a) {
        if ($a['kegiatan'] === 'Bekerja') {
            expect($a['pekerjaan'])->not->toBeNull("anggota {$a['id_anggota_keluarga']} Bekerja tanpa pekerjaan");
            expect($a['pendapatan_per_bulan'])->not->toBeNull();
        }
        if ($a['kegiatan'] === 'Belum Sekolah') {
            expect($a['pendidikan_terakhir'])->toBeNull();
            expect($a['pekerjaan'])->toBeNull();
        }
    }

    // Agama tiap baris (transmigran maupun anggota) memakai nilai enum yang sah.
    $agamaSah = Agama::nilai();
    foreach (DummyData::transmigran() as $t) {
        expect($agamaSah)->toContain($t['agama']);
    }
    foreach ($anggota as $a) {
        if ($a['agama'] !== null) {
            expect($agamaSah)->toContain($a['agama']);
        }
    }
});

it('mengunci daftar enum baru Rombongan B', function () {
    expect(Agama::nilai())->toBe(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']);
    expect(HubunganAnggotaKeluarga::nilai())
        ->toBe(['Istri', 'Suami', 'Anak', 'Anak Angkat', 'Orang Tua', 'Famili Lain']);
    expect(KegiatanAnggota::nilai())
        ->toBe(['Belum Sekolah', 'Masih Sekolah', 'Bekerja', 'Tidak Bekerja']);
});

/*
|--------------------------------------------------------------------------
| Modul rumah dan hunian
|--------------------------------------------------------------------------
*/

it('merender daftar rumah beserta seluruh barisnya', function () {
    $respons = $this->get(route('rumah.index'));

    $respons->assertOk();

    foreach (DummyData::rumah() as $baris) {
        $respons->assertSee($baris['no_rumah']);
    }
});

it('hanya menawarkan keluarga yang belum menempati rumah', function () {
    // Satu KK hanya boleh menempati satu rumah, dan pembatasan itu wajib
    // terlihat sejak di antarmuka (agents/rules.md bagian 6a poin 8).
    $isi = $this->get(route('rumah.index'))->getContent();

    $sudahPunyaRumah = array_filter(array_column(DummyData::rumah(), 'penghuni'));

    foreach ($sudahPunyaRumah as $nama) {
        expect($isi)->not->toContain($nama.' (');
    }

    // Sebaliknya, yang belum punya rumah wajib muncul sebagai pilihan.
    foreach (DummyData::transmigranTanpaRumah() as $kk) {
        expect($isi)->toContain($kk['nama_kepala_keluarga'].' (');
    }
});

it('menyaring daftar rumah menurut status hunian', function () {
    $this->get(route('rumah.index', ['status_hunian' => 'Tidak Dihuni']))
        ->assertOk()
        ->assertSee('A-03');
});

it('menampilkan riwayat penghunian sebagai jejak yang tidak menimpa', function () {
    // Rumah 3 pernah dihuni lalu ditinggalkan; jejaknya wajib tetap terbaca
    // beserta alasan keluarnya (agents/rules.md bagian 6a poin 9).
    $this->get(route('rumah.detail', 3))
        ->assertOk()
        ->assertSee('DOMINGGUS TAEK')
        ->assertSee('Pindah mengikuti keluarga ke SP Weoe.')
        ->assertSee('Sudah keluar');
});

it('menjelaskan alasan rumah tidak dihuni', function () {
    $this->get(route('rumah.detail', 3))
        ->assertSee('Rumah tidak dihuni')
        ->assertSee('Atap rusak berat setelah angin kencang, sedang menunggu perbaikan.');
});

it('membalas 404 untuk rumah yang tidak ada', function () {
    $this->get('/rumah/99')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Modul lahan
|--------------------------------------------------------------------------
*/

it('merender daftar lahan beserta total luasnya', function () {
    $respons = $this->get(route('lahan.index'));

    $respons->assertOk();

    // Rekap luas wajib MENJUMLAH KOLOM, bukan baris (Putaran 15, rules.md 7.10):
    // pekarangan dan lahan usaha kini kolom pada satu baris per keluarga.
    $total = array_sum(array_map(
        fn ($l) => (float) ($l['luas_pekarangan'] ?? 0) + (float) ($l['luas_usaha'] ?? 0),
        DummyData::lahan()
    ));
    $respons->assertSee(number_format($total, 2, ',', '.'));
});

it('menyaring daftar lahan menurut peruntukan dan kategori', function () {
    // Penyaring peruntukan kini menanyakan "keluarga ini punya bidang itu?",
    // sebab satu baris memuat kedua bidang. LH-003 hanya memegang lahan usaha.
    $this->get(route('lahan.index', ['peruntukan_lahan' => 'Lahan Pekarangan']))
        ->assertOk()
        ->assertSee('LH-001')
        ->assertDontSee('LH-003');

    $this->get(route('lahan.index', ['peruntukan_lahan' => 'Lahan Usaha']))
        ->assertOk()
        ->assertSee('LH-001')
        ->assertSee('LH-003');

    // Nilai penyaring komposisi adalah `basah`, bukan nama enum lama.
    $this->get(route('lahan.index', ['kategori_lahan' => 'basah']))
        ->assertOk()
        ->assertSee('LH-004')
        ->assertDontSee('LH-003');
});

it('menampilkan legalitas lahan dari tempatnya yang benar', function () {
    // Lahan TIDAK memegang dokumennya sendiri (Putaran 12). SHM meliputi
    // seluruh lahan satu keluarga sehingga melekat pada transmigran; HPL
    // adalah alas hak kawasan milik instansi sehingga melekat pada kawasan
    // (rules.md 7.4a). Tab Legalitas menampilkan keduanya sebagai BACAAN.
    $isi = $this->get(route('lahan.detail', 2))->assertOk()->getContent();

    expect($isi)->toContain('Sertifikat keluarga (SHM)')
        ->and($isi)->toContain('Alas hak kawasan (HPL)')
        // Status sertifikat dibaca dari keluarganya, bukan dari bidangnya.
        ->and($isi)->toContain('Status sertifikat')
        // Tautan ke tempat penyuntingannya, sebab di sini hanya bacaan.
        ->and($isi)->toContain('Buka data keluarga')
        ->and($isi)->toContain('Buka data kawasan');
});

it('tidak lagi menyediakan unggahan dokumen pada halaman lahan', function () {
    // Menyediakannya di sini akan melahirkan salinan sertifikat yang sama pada
    // tiap bidang, lalu satu digit salah hanya terbetulkan di sebagian.
    $isi = $this->get(route('lahan.detail', 1))->assertOk()->getContent();

    expect($isi)->not->toContain('formDokumenLahan')
        ->and($isi)->not->toContain('Tambah Dokumen Lahan');

    // Ketiga kolom pengelolaan dicabut beserta tabnya.
    $sumber = file_get_contents(resource_path('views/pages/lahan/form.blade.php'));
    expect($sumber)->not->toContain('name="pola_tanam"')
        ->and($sumber)->not->toContain('name="peralatan_pertanian"')
        ->and($sumber)->not->toContain('name="kendala"');
});

it('membalas 404 untuk lahan yang tidak ada', function () {
    $this->get('/lahan/99')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Modul hasil panen
|--------------------------------------------------------------------------
*/

it('merender daftar panen beserta satuan asli tiap komoditas', function () {
    $this->get(route('panen.index'))
        ->assertOk()
        ->assertSee('320,500 Kilogram')
        ->assertSee('4,250 Ton');
});

it('menampilkan setara ton untuk satuan selain ton', function () {
    // Operator perlu melihat dua angka sekaligus: yang ia catat di lapangan,
    // dan yang dipakai sistem saat merekap.
    $this->get(route('panen.index'))->assertSee('setara 0,321 ton');
});

it('menjumlahkan volume panen memakai hasil konversi, bukan angka mentah', function () {
    // Menjumlahkan ton dan kilogram begitu saja menghasilkan
    // angka yang keliru (agents/rules.md bagian 8a poin 5).
    $benar = array_sum(array_map(
        fn ($p) => DummyData::keTon($p['produksi'], $p['satuan']),
        DummyData::hasilPanen()
    ));

    $mentah = array_sum(array_column(DummyData::hasilPanen(), 'produksi'));

    expect(round($benar, 3))->toBe(14.051)
        ->and($mentah)->toBeGreaterThan($benar);

    $this->get(route('panen.index'))->assertSee(number_format($benar, 3, ',', '.'));
});

it('mengubah volume ke ton memakai faktor satuannya', function () {
    expect(DummyData::keTon(1, 'Ton'))->toBe(1.0)
        ->and(DummyData::keTon(1, 'Kuintal'))->toBe(0.1)
        ->and(DummyData::keTon(1000, 'Kilogram'))->toBe(1.0);
});

it('merender rekap panen pada keempat dasar pengelompokan', function () {
    // Yang dijaga adalah baris totalnya terender, bukan kalimatnya. Mengunci
    // teks membuat penyuntingan wording memerahkan uji padahal tidak ada yang
    // rusak, sehingga penandanya yang diperiksa.
    foreach (['sp', 'komoditas', 'poktan'] as $kelompok) {
        $this->get(route('panen.rekap', ['kelompok' => $kelompok]))
            ->assertOk()
            ->assertSee('motif-baris-total', false);
    }
});

it('memakai baris total bermotif pada halaman rekap', function () {
    // Rekap adalah jenis komposisi keempat: tabel agregat dengan baris total
    // yang ditegaskan, tanpa kartu statistik (agents/ui-spec.md bagian 2.2).
    $isi = $this->get(route('panen.rekap'))->getContent();

    expect($isi)->toContain('motif-baris-total')
        ->and($isi)->not->toContain('motif-judul-kartu');
});

it('tidak menganggap rekap sebagai id panen', function () {
    // Rute /panen/rekap wajib didaftarkan sebelum /panen/{id}.
    $this->get(route('panen.rekap'))->assertOk()->assertSee('Rekap per');
});

it('membalas 404 untuk catatan panen yang tidak ada', function () {
    $this->get('/panen/99')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Rekap panen berbasis penanaman
|--------------------------------------------------------------------------
*/

it('menampilkan poktan yang sudah menanam meski belum panen sama sekali', function () {
    // INILAH ALASAN basisnya penanaman, bukan hasil panen. Pada basis lama,
    // kelompok yang sudah menanam tetapi belum panen HILANG dari rekap,
    // sehingga dinas membaca "tidak ada masalah" justru pada keadaan yang
    // paling perlu ditengok.
    //
    // POKTAN SUBUR MAKMUR menanam 1 ha pada 2026 dan belum panen sama sekali.
    $rekap = DummyData::rekapPanen('poktan', 2026);
    $baris = collect($rekap)->firstWhere('nama', 'POKTAN SUBUR MAKMUR');

    expect($baris)->not->toBeNull()
        ->and($baris['realisasi_tanam'])->toBe(1.0)
        ->and($baris['hasil_panen'])->toBe(0.0)
        ->and($baris['belum_dipanen'])->toBe(1.0)
        ->and($baris['produksi_ton'])->toBe(0.0);

    // Dibuktikan pula pada halamannya, bukan hanya pada helpernya.
    $this->get(route('panen.rekap.kelompok', ['kelompok' => 'poktan']).'?tahun=2026')
        ->assertOk()
        ->assertSee('POKTAN SUBUR MAKMUR');
});

it('mengikat rekap panen pada satu tahun panen, bukan seluruh riwayat', function () {
    // Total kumulatif menyesatkan pada dua hal: ia hanya dapat naik sehingga
    // musim yang hancur pun tampak sebagai kabar baik, dan LUAS tidak boleh
    // dijumlahkan lintas tahun sebab bidang yang sama ditanami berulang kali.
    $tanam2025 = array_sum(array_column(DummyData::rekapPanen('sp', 2025), 'realisasi_tanam'));
    $tanam2026 = array_sum(array_column(DummyData::rekapPanen('sp', 2026), 'realisasi_tanam'));

    expect($tanam2025)->toBeGreaterThan(0.0)
        ->and($tanam2026)->toBeGreaterThan(0.0)
        ->and($tanam2025)->not->toBe($tanam2026);

    // Periode wajib tertulis pada halamannya, bukan tersembunyi di penyaring:
    // angka rekap tanpa periodenya tidak dapat disalin ke laporan mana pun.
    $this->get(route('panen.rekap.kelompok', ['kelompok' => 'sp']).'?tahun=2025')
        ->assertOk()
        ->assertSee('Tahun Panen 2025')
        ->assertSee('Total tahun panen 2025');
});

it('menggolongkan penanaman menurut tahun panennya, bukan tahun tanamnya', function () {
    // DIUBAH 2026-08-24 atas keterangan pemilik proyek: ini rekap PANEN,
    // sehingga yang menggolongkan adalah peristiwa panennya.
    //
    // Bentuk lama membuang panen April 2026 dari rekap 2026 hanya karena
    // penanamannya bermula November 2025, padahal timbangannya nyata terjadi
    // tahun itu.
    foreach (DummyData::penanaman() as $tanam) {
        $tahunTanam = (int) substr($tanam['periode_tanam'], 0, 4);
        $tahunRekap = DummyData::tahunRekapPanen($tanam['id_penanaman']);

        $panen = collect(DummyData::hasilPanen())
            ->firstWhere('penanaman_id', $tanam['id_penanaman']);

        if ($panen !== null) {
            expect($tahunRekap)->toBe((int) substr($panen['periode_panen'], 0, 4));
        } else {
            // Belum dipanen: digolongkan ke tahun berjalan, sebab di situlah
            // panennya masih mungkin terjadi.
            expect($tahunRekap)->toBe((int) date('Y'));
        }

        unset($tahunTanam);
    }

    // Penanaman #1 ditanam November 2025 tetapi dipanen April 2026, sehingga
    // ia direkap pada 2026 - BUKAN 2025. Inilah yang dahulu keliru.
    expect(DummyData::tahunRekapPanen(1))->toBe(2026);

    $poktan2026 = array_column(DummyData::rekapPanen('poktan', 2026), 'nama');
    $poktan2025 = array_column(DummyData::rekapPanen('poktan', 2025), 'nama');

    expect($poktan2026)->toContain('POKTAN MEKAR JAYA')
        ->and($poktan2026)->toContain('POKTAN SUBUR MAKMUR');

    // Penanaman #5 tanam Juni 2025 dan panen November 2025, satu-satunya yang
    // tuntas dalam tahun yang sama.
    expect(DummyData::tahunRekapPanen(5))->toBe(2025)
        ->and($poktan2025)->toContain('POKTAN MEKAR JAYA');
});

it('menjaga satu penanaman hanya muncul pada satu tahun rekap', function () {
    // Ditegaskan pemilik proyek: satu penanaman tidak boleh muncul di dua
    // tahun, sebab luasnya akan terhitung dua kali.
    $terlihat = [];

    foreach (DummyData::tahunPanenTercatat() as $tahun) {
        foreach (DummyData::penanaman() as $tanam) {
            if (DummyData::tahunRekapPanen($tanam['id_penanaman']) === $tahun) {
                $terlihat[$tanam['id_penanaman']][] = $tahun;
            }
        }
    }

    foreach ($terlihat as $id => $tahunnya) {
        expect($tahunnya)->toHaveCount(1, "penanaman {$id} muncul di lebih dari satu tahun");
    }

    // Seluruh penanaman terwakili, sehingga tidak ada yang hilang dari rekap.
    expect($terlihat)->toHaveCount(count(DummyData::penanaman()));
});

it('menghitung produktivitas rekap secara tertimbang, bukan rata-rata kolom', function () {
    // Merata-ratakan kolom produktivitas mencampur ton/ha dengan kg/ha:
    // jagung 3,4 ton/ha dan cabai 1.282 kg/ha dirata-rata menjadi 642 ton/ha,
    // angka yang tidak ada di alam.
    foreach (DummyData::rekapPanen('sp', 2026) as $baris) {
        if ($baris['hasil_panen'] <= 0) {
            continue;
        }

        $benar = round($baris['produksi_ton'] / $baris['hasil_panen'], 3);

        expect($baris['produktivitas_ton'])->toBe($benar, "produktivitas {$baris['nama']}");
    }

    /*
     * Rata-rata naif dihitung terang-terangan lalu dibuktikan BERBEDA JAUH,
     * agar uji ini memerah bila kelak seseorang menggantinya.
     *
     * CABAI dipilih sebab satuannya kilogram: 1.282 kg/ha menjadi 1,282
     * ton/ha setelah dikonversi, seribu kali lebih kecil.
     */
    $cabai = collect(DummyData::rekapPanen('komoditas', 2026))->firstWhere('nama', 'CABAI');
    $panenCabai = collect(DummyData::hasilPanen())->firstWhere('komoditas', 'CABAI');

    expect($cabai['produktivitas_ton'])->toBeLessThan((float) $panenCabai['produktivitas'] / 100);

    // Rata-rata naif lintas komoditas menghasilkan angka yang mustahil.
    $rataNaif = collect(DummyData::hasilPanen())->avg('produktivitas');

    expect($rataNaif)->toBeGreaterThan(100.0);

    $totalTertimbang = collect(DummyData::rekapPanen('sp', 2026))->sum('produksi_ton')
        / collect(DummyData::rekapPanen('sp', 2026))->sum('hasil_panen');

    expect($totalTertimbang)->toBeLessThan(10.0);
});
it('menghitung cacah poktan dari himpunan, bukan dari jumlah baris penanaman', function () {
    // POKTAN MEKAR JAYA memiliki empat penanaman pada 2025. Menghitung baris
    // akan menyatakan "4 poktan" di SP Kapitan Meo, padahal hanya satu.
    $sp = collect(DummyData::rekapPanen('sp', 2025))->firstWhere('nama', 'SP Kapitan Meo');

    $penanamanDiSp = collect(DummyData::penanaman())
        ->filter(fn ($t) => $t['satuan_permukiman'] === 'SP Kapitan Meo'
            && str_starts_with($t['periode_tanam'], '2025'))
        ->count();

    expect($sp['jumlah_poktan'])->toBe(1)
        ->and($penanamanDiSp)->toBeGreaterThan(1);
});

it('meniadakan kolom jumlah catatan pada rekap panen', function () {
    // Cacah catatan menghitung baris entri, bukan besaran lapangan.
    $isi = $this->get(route('panen.rekap'))->assertOk()->getContent();

    expect($isi)->not->toContain('Jumlah Catatan')
        ->and($isi)->toContain('Realisasi Tanam (ha)')
        ->and($isi)->toContain('Produktivitas (ton/ha)');
});

it('menyeragamkan istilah luas panen di seluruh halaman', function () {
    /*
     * "Realisasi Panen", bukan "Hasil Panen" (diganti 2026-08-24).
     *
     * Sejajar dengan Realisasi Tanam, dan sama persis dengan kolom laporan
     * lapangan. Bila satu halaman berkata "Realisasi Panen" sementara halaman
     * lain berkata "Hasil Panen", petugas akan mengira keduanya angka berbeda.
     *
     * "Menunggu Panen" pula yang dipakai pada rekap, bukan "Belum Dipanen":
     * istilah kedua sudah dicabut dari form bersama panen bertahap, dan
     * memakainya di sini dengan arti berbeda justru membingungkan.
     */
    $halaman = [
        route('panen.rekap') => ['Realisasi Panen (ha)', 'Menunggu Panen (ha)'],
        route('panen.detail', 1) => ['Realisasi panen'],
        route('penanaman.detail', 3) => ['Realisasi Panen (ha)'],
    ];

    foreach ($halaman as $alamat => $wajibAda) {
        $isi = $this->get($alamat)->assertOk()->getContent();

        // Dipanggil tanpa pesan tambahan: Pest memperlakukan argumen kedua
        // `toContain` sebagai nilai LAIN yang ikut dicari, bukan keterangan,
        // sehingga pesannya sendiri akan ikut diperiksa dan uji memerah.
        foreach ($wajibAda as $teks) {
            expect($isi)->toContain($teks);
        }

        // Istilah lama tidak boleh tersisa sebagai LABEL KOLOM. Kata "Hasil
        // Panen" sendiri tetap sah sebagai nama modul, judul halaman, dan
        // label tab - yang dilarang hanya bentuk berkurung satuan.
        expect($isi)->not->toContain('Hasil Panen (ha)')
            ->and($isi)->not->toContain('Belum Dipanen (ha)');
    }

    // Label isian pada form ikut berganti; namanya memang sudah
    // `realisasi_panen` sejak awal, hanya labelnya yang tertinggal.
    $form = $this->get(route('panen.index'))->assertOk()->getContent();

    expect($form)->toContain('Realisasi Panen<span')
        ->and($form)->not->toContain('Hasil Panen<span');
});

it('menawarkan tahun berjalan meski belum ada penanamannya', function () {
    // Bawaan halaman ini tahun berjalan. Bila tahun itu tidak ikut di dalam
    // daftar pilihan, pilihan yang sedang aktif justru tidak ada pada
    // penyaringnya sendiri setiap awal tahun.
    $isi = $this->get(route('panen.rekap'))->assertOk()->getContent();

    expect($isi)->toContain('value="'.date('Y').'"');
});

it('menjaga keenam tab rekap kependudukan membagi jumlah KK yang sama', function () {
    /*
     * Keenam tab membagi KELUARGA YANG SAMA menurut sudut pandang berbeda,
     * sehingga totalnya wajib sama. Total yang berlainan berarti salah satu
     * pembagiannya bocor - dan pembaca yang berpindah tab akan mengira
     * salah satunya rusak.
     */
    $target = DummyData::ringkasanDashboard()['jumlah_kk'];

    $tab = [
        'status' => DummyData::rekapPenghuni(),
        'pekerjaan' => DummyData::sebaranPekerjaan(),
        'asal' => DummyData::sebaranDaerahAsal(),
        'pendidikan' => DummyData::sebaranPendidikan(),
    ];

    foreach ($tab as $nama => $peta) {
        expect(array_sum($peta))->toBe($target, "tab {$nama}");
    }
});

it('mengurutkan pendidikan menurut jenjang, bukan menurut jumlah', function () {
    /*
     * Pendidikan bertingkat. Mengurutkannya menurut jumlah membuat SD
     * mendahului Tidak Sekolah, dan pembaca kehilangan bentuk piramidanya.
     */
    $punya = array_keys(DummyData::sebaranPendidikan());

    expect($punya)->toBe(PendidikanTerakhir::nilai());

    // Bila diurutkan menurut jumlah, urutannya AKAN berbeda - sehingga uji
    // di atas benar-benar menguji sesuatu.
    $menurutJumlah = DummyData::sebaranPendidikan();
    arsort($menurutJumlah);

    expect(array_keys($menurutJumlah))->not->toBe($punya);
});

it('menampilkan jenjang pendidikan yang kosong sebagai nol', function () {
    // "Tidak ada lulusan S3 di kawasan ini" adalah informasi; baris yang
    // hilang membuat pembaca tidak dapat membedakannya dari data yang
    // belum didata.
    $peta = DummyData::sebaranPendidikan();

    expect($peta)->toHaveCount(count(PendidikanTerakhir::nilai()));

    $kosong = array_filter($peta, fn ($j) => $j === 0);

    expect($kosong)->not->toBeEmpty();

    $isi = $this->get(route('kependudukan.rekap.kelompok', ['kelompok' => 'pendidikan']))
        ->assertOk()
        ->getContent();

    foreach (array_keys($kosong) as $jenjang) {
        expect($isi)->toContain($jenjang);
    }
});

it('menyediakan tautan tetap bagi tiap tab rekap kependudukan', function () {
    /*
     * Kueri `?kelompok=` TIDAK dilayani berkas statis di GitHub Pages,
     * sehingga tanpa tautan tetap hanya tab bawaan yang terbuka di situs
     * terbit - lima tab lain tidak dapat dicapai sama sekali.
     *
     * Cacat yang sama pernah ditemukan pada rekap panen (notes.md 1b.6a)
     * lalu diperbaiki, tetapi kependudukan terlewat sampai 2026-08-25.
     */
    $tab = ['tahun', 'sp', 'status', 'pekerjaan', 'asal', 'pendidikan'];

    foreach ($tab as $kelompok) {
        $this->get(route('kependudukan.rekap.kelompok', ['kelompok' => $kelompok]))
            ->assertOk()
            ->assertSee('Rekap per');
    }

    // Kelompok karangan membalas 404, bukan halaman kosong: daftar yang
    // tidak ada dan daftar yang kebetulan kosong adalah dua keadaan
    // berbeda, dan menyamakannya membuat salah ketik tampak seperti data
    // yang belum diisi.
    $this->get('/kependudukan/rekap/karangan')->assertNotFound();

    // Alamat lama tetap bekerja, sehingga tautan yang sudah tersebar tidak
    // mendadak mati.
    $this->get(route('kependudukan.rekap', ['kelompok' => 'asal']))->assertOk();
});

it('merender keenam tab rekap kependudukan beserta isinya', function () {
    // Yang diperiksa BARISNYA terender, bukan sekadar halaman membalas 200:
    // tab yang tabelnya kosong tetap membalas 200 dan tampak sehat.
    // Daerah asal memakai varian BERLABEL, sebab `sebaranDaerahAsal()`
    // berkunci id kabupaten sejak 2026-09-02 sedangkan yang dirender
    // halaman adalah namanya. Totalnya sama, hanya kuncinya yang berbeda.
    $harapan = [
        'asal' => DummyData::sebaranDaerahAsalBerlabel(),
        'pendidikan' => DummyData::sebaranPendidikan(),
        'pekerjaan' => DummyData::sebaranPekerjaan(),
    ];

    foreach ($harapan as $kelompok => $peta) {
        $isi = $this->get(route('kependudukan.rekap.kelompok', ['kelompok' => $kelompok]))
            ->assertOk()
            ->getContent();

        foreach (array_keys($peta) as $nama) {
            expect($isi)->toContain($nama);
        }

        // Baris totalnya ikut terender, memakai motif identitas rekap.
        expect($isi)->toContain('motif-baris-total')
            ->and($isi)->toContain(number_format(array_sum($peta), 0, ',', '.'));
    }
});

it('menyaring rekap kependudukan per tahun dengan total yang selalu konsisten', function () {
    $tahunUji = 2020;
    $deret = DummyData::deretTahunan();
    $idx = array_search($tahunUji, $deret['tahun'], true);
    $targetKk = $deret['jumlah_kk'][$idx]; // 968

    // Periksa konsistensi seluruh helper data pada tahun 2020
    expect(array_sum(array_column(DummyData::rekapPerSp($tahunUji), 'jumlah_kk')))->toBe($targetKk)
        ->and(array_sum(DummyData::rekapPenghuni($tahunUji)))->toBe($targetKk)
        ->and(array_sum(DummyData::sebaranPekerjaan($tahunUji)))->toBe($targetKk)
        ->and(array_sum(DummyData::sebaranDaerahAsal($tahunUji)))->toBe($targetKk)
        ->and(array_sum(DummyData::sebaranPendidikan($tahunUji)))->toBe($targetKk);

    // Periksa rendering view dengan filter tahun
    $tab = ['sp', 'status', 'pekerjaan', 'asal', 'pendidikan'];
    foreach ($tab as $kelompok) {
        $isi = $this->get(route('kependudukan.rekap.kelompok', ['kelompok' => $kelompok, 'tahun' => $tahunUji]))
            ->assertOk()
            ->getContent();

        expect($isi)->toContain("Tahun {$tahunUji}")
            ->and($isi)->toContain(number_format($targetKk, 0, ',', '.'))
            ->and($isi)->toContain('id="filter_tahun"')
            ->and($isi)->toContain('Terapkan Filter');
    }

    // Tab 'tahun' menyajikan seluruh deret waktu historis sehingga card filter_tahun tidak dirender
    $isiTahun = $this->get(route('kependudukan.rekap.kelompok', ['kelompok' => 'tahun']))
        ->assertOk()
        ->getContent();

    expect($isiTahun)->not->toContain('id="filter_tahun"')
        ->and($isiTahun)->not->toContain('Terapkan Filter');
});

it('merender halaman Tentang Sistem beserta seluruh data tim pengembang dan kolaborator', function () {
    $response = $this->get(route('tentang'))->assertOk();
    $konten = $response->getContent();

    expect($konten)->toContain('Tentang Sistem')
        ->and($konten)->toContain('Dr. Budi Setiyono, S.Si., M.T.')
        ->and($konten)->toContain('Leonardi Paris Hasugian')
        ->and($konten)->toContain('Muhammad Abyan Dzaka')
        ->and($konten)->toContain('Reyner Marvi Leiwakabessy')
        ->and($konten)->toContain('Muhammad Rias Ramadan')
        ->and($konten)->toContain('Heaven Happyna Putra Febriyono')
        ->and($konten)->toContain('Institut Teknologi Sepuluh Nopember')
        ->and($konten)->toContain('Kementerian Transmigrasi')
        ->and($konten)->toContain('Kabupaten Malaka')
        ->and($konten)->toContain('SP Kapitan Meo')
        ->and($konten)->toContain('SP Tniumanu')
        ->and($konten)->toContain('SP Harekakae')
        ->and($konten)->toContain('SP Weoe')
        ->and($konten)->toContain('SP Tualaran')
        ->and($konten)->toContain('SP Weain');
});

it('merender halaman Panduan Penggunaan beserta seluruh bab dan daftar isi', function () {
    $response = $this->get(route('panduan'))->assertOk();
    $konten = $response->getContent();

    expect($konten)->toContain('Panduan Penggunaan')
        ->and($konten)->toContain('Daftar Isi Panduan')
        ->and($konten)->toContain('peran-akses')
        ->and($konten)->toContain('dashboard-indikator')
        ->and($konten)->toContain('master-wilayah')
        ->and($konten)->toContain('kependudukan-lahan')
        ->and($konten)->toContain('kelembagaan-pertanian')
        ->and($konten)->toContain('pengaduan-warga')
        ->and($konten)->toContain('laporan-ekspor')
        ->and($konten)->toContain('faq-bantuan')
        ->and($konten)->toContain('Unduh Panduan (PDF)');
});

it('menampilkan submenu Bantuan & Info pada sidebar aplikasi', function () {
    $response = $this->get(route('beranda'))->assertOk();
    $konten = $response->getContent();

    expect($konten)->toContain('Bantuan &amp; Info')
        ->and($konten)->toContain('Panduan Penggunaan')
        ->and($konten)->toContain('Tentang Sistem');
});

it('menampilkan footer ramping pada layout aplikasi dan footer informatif pada layout publik', function () {
    // CMS Layout: footer ramping
    $kontenApp = $this->get(route('beranda'))->assertOk()->getContent();
    expect($kontenApp)->toContain('Kementerian Transmigrasi RI')
        ->and($kontenApp)->toContain('Pemerintah Kabupaten Malaka')
        ->and($kontenApp)->toContain('ITS Surabaya')
        ->and($kontenApp)->toContain('TailAdmin');

    // Public Layout: footer informatif
    $kontenPublik = $this->get(route('pengaduan-warga'))->assertOk()->getContent();
    expect($kontenPublik)->toContain('Kawasan Kobalima Timur')
        ->and($kontenPublik)->toContain('SP Kapitan Meo')
        ->and($kontenPublik)->toContain('Kementerian Transmigrasi RI')
        ->and($kontenPublik)->toContain('Masuk sebagai Petugas Sistem');
});

it('menyusun kolom rekap sesuai dasar pengelompokannya', function () {
    /*
     * Kolom kedua BERBEDA tiap tab (ditetapkan pemilik proyek 2026-08-24):
     *
     * - Per SP dan Per Poktan -> Luas Lahan, sebab lahan memang milik poktan
     * - Per Komoditas         -> Volume Benih
     *
     * Luas lahan sengaja TIDAK ada pada tab komoditas: satu poktan menanam
     * beberapa komoditas, sehingga lahannya terhitung berkali-kali dan
     * totalnya melampaui luas kawasan yang sebenarnya.
     */
    $harapan = [
        'sp' => [
            'ada' => ['Poktan', 'Luas Lahan (ha)'],
            'tiada' => ['Volume Benih (kg)', 'Jumlah Anggota'],
        ],
        'komoditas' => [
            'ada' => ['Poktan', 'Volume Benih (kg)'],
            'tiada' => ['Luas Lahan (ha)', 'Jumlah Anggota'],
        ],
        /*
         * Cacah poktan tidak dirender pada tab poktan: nilainya selalu satu.
         *
         * Jumlah Anggota JUSTRU hanya di sini (ditambahkan 2026-08-25). Pada
         * tab lain ia menjumlahkan anggota beberapa poktan sekaligus - angka
         * yang benar secara aritmetika tetapi tidak menjawab pertanyaan apa
         * pun, sebab yang dinilai di sana wilayah dan komoditas.
         */
        'poktan' => [
            'ada' => ['Jumlah Anggota', 'Luas Lahan (ha)'],
            'tiada' => ['Volume Benih (kg)'],
        ],
    ];

    foreach ($harapan as $tab => $periksa) {
        $isi = $this->get(route('panen.rekap.kelompok', ['kelompok' => $tab]))
            ->assertOk()
            ->getContent();

        foreach ($periksa['ada'] as $teks) {
            expect($isi)->toContain($teks);
        }

        foreach ($periksa['tiada'] as $teks) {
            expect($isi)->not->toContain($teks);
        }

        // Kolom tetap yang wajib ada pada ketiga tab.
        foreach (['Realisasi Tanam (ha)', 'Realisasi Panen (ha)', 'Puso (ha)',
            'Menunggu Panen (ha)', 'Produktivitas (ton/ha)', 'Produksi (ton)'] as $teks) {
            expect($isi)->toContain($teks);
        }
    }
});

it('menyejajarkan cacah kolom header, badan, dan baris total pada rekap', function () {
    /*
     * Baris total yang bergeser satu kolom tidak memerahkan apa pun tanpa
     * penjagaan ini - kekeliruan yang sudah pernah terjadi saat kolom Musim
     * dicabut. Bahayanya berlipat sejak kolom kedua berbeda tiap tab.
     */
    foreach (['sp', 'komoditas', 'poktan'] as $tab) {
        $isi = $this->get(route('panen.rekap.kelompok', ['kelompok' => $tab]))
            ->assertOk()
            ->getContent();

        preg_match('/<thead.*?<\/thead>/s', $isi, $kepala);
        preg_match('/<tfoot.*?<\/tfoot>/s', $isi, $kaki);
        preg_match('/<tbody.*?<\/tbody>/s', $isi, $badan);

        $cacahKepala = preg_match_all('/<th\s/', $kepala[0] ?? '');
        $cacahKaki = preg_match_all('/<td\s/', $kaki[0] ?? '');

        // Baris pertama badan saja; seluruh baris berbentuk sama.
        $barisPertama = explode('</tr>', $badan[0] ?? '')[0];
        $cacahBadan = preg_match_all('/<td\s/', $barisPertama);

        expect($cacahKepala)->toBeGreaterThan(0)
            ->and($cacahKaki)->toBe($cacahKepala)
            ->and($cacahBadan)->toBe($cacahKepala);
    }
});

it('menyejajarkan cacah kolom pada daftar penanaman dan hasil panen', function () {
    /*
     * Baris total yang bergeser satu kolom tidak memerahkan apa pun tanpa
     * penjagaan ini - kekeliruan yang sudah pernah terjadi saat kolom Musim
     * dicabut, dan berulang risikonya tiap kolom ditambah.
     *
     * `colspan` ikut dihitung, sebab baris total kedua halaman memakainya
     * untuk merentang beberapa kolom sekaligus.
     */
    foreach ([route('penanaman'), route('panen.index')] as $alamat) {
        $isi = $this->get($alamat)->assertOk()->getContent();

        preg_match('/<thead.*?<\/thead>/s', $isi, $kepala);
        preg_match('/<tfoot.*?<\/tfoot>/s', $isi, $kaki);
        preg_match('/<tbody.*?<\/tbody>/s', $isi, $badan);

        $cacahKepala = preg_match_all('/<th\s/', $kepala[0] ?? '');
        $barisPertama = explode('</tr>', $badan[0] ?? '')[0];
        $cacahBadan = preg_match_all('/<td\s/', $barisPertama);

        // Sel bercolspan dihitung sesuai rentangnya, bukan sebagai satu sel.
        preg_match_all('/<td(?:\s+colspan="(\d+)")?/', $kaki[0] ?? '', $sel);

        $cacahKaki = 0;
        foreach ($sel[1] as $rentang) {
            $cacahKaki += $rentang === '' ? 1 : (int) $rentang;
        }

        expect($cacahKepala)->toBeGreaterThan(0)
            ->and($cacahBadan)->toBe($cacahKepala)
            ->and($cacahKaki)->toBe($cacahKepala);
    }
});

it('menampilkan jumlah anggota dan luas lahan pada daftar penanaman', function () {
    /*
     * Keduanya DIHITUNG dari keanggotaan aktif dan data lahan, tidak disimpan
     * pada tabel penanaman (rules.md 7d.3). Angka yang disimpan akan basi
     * begitu satu anggota keluar atau satu bidang dibetulkan.
     */
    $isi = $this->get(route('penanaman'))->assertOk()->getContent();

    expect($isi)->toContain('Jumlah Anggota')
        ->and($isi)->toContain('Luas Lahan (ha)');

    // Angkanya benar-benar terender, bukan sekadar judulnya.
    foreach (DummyData::penanaman() as $tanam) {
        $kekuatan = DummyData::rekapLahanPoktan($tanam['poktan_id']);

        expect($isi)->toContain(number_format($kekuatan['luas_total'], 2, ',', '.'));
    }

    // Kolom itu memang turunan: tidak ada pada tabel penanaman.
    foreach (DummyData::penanaman() as $tanam) {
        expect($tanam)->not->toHaveKey('jumlah_anggota')
            ->and($tanam)->not->toHaveKey('luas_lahan');
    }
});

it('meniadakan kolom produktivitas dari daftar hasil panen', function () {
    /*
     * DICABUT 2026-08-25 atas keputusan pemilik proyek: nilainya dapat
     * dihitung sendiri dari Produksi dibagi Realisasi Panen, sehingga tidak
     * ada data yang hilang - yang hemat justru satu kolom pada tabel padat.
     *
     * Kolomnya tetap ada pada REKAP, sebab di sana ia agregat tertimbang yang
     * tidak dapat dihitung ulang pembaca dari dua kolom di layar.
     */
    $isi = $this->get(route('panen.index'))->assertOk()->getContent();

    /*
     * Diperiksa pada KEPALA TABEL saja, bukan seluruh halaman: form modal
     * ikut dirender di sini dan memang masih memuat isian produktivitas -
     * yang dicabut kolom tabelnya, bukan isiannya.
     */
    preg_match('/<thead.*?<\/thead>/s', $isi, $kepala);

    expect($kepala[0] ?? '')->not->toContain('Produktivitas');

    // Kolom yang menggantikannya, sesuai daftar pemilik proyek.
    foreach (['Kelompok Tani', 'Volume Benih', 'Luas Lahan (ha)',
        'Realisasi Panen (ha)', 'Puso (ha)', 'Periode Panen',
        'Produksi', 'Perkiraan Nilai Jual'] as $kolom) {
        expect($isi)->toContain($kolom);
    }

    // Datanya tetap disimpan meski kolomnya tidak ditampilkan.
    foreach (DummyData::hasilPanen() as $panen) {
        expect($panen)->toHaveKey('produktivitas');
    }
});

it('membaca volume benih daftar panen lewat penanamannya', function () {
    /*
     * Volume benih milik PENANAMAN, bukan milik catatan panen. Membacanya
     * lewat relasi menjaga satu angka tetap punya satu sumber; menyalinnya
     * ke tabel panen berarti dua tempat yang dapat berbeda diam-diam.
     */
    $panen = collect(DummyData::hasilPanen())->firstWhere('id_hasil_panen', 1);
    $tanam = collect(DummyData::penanaman())->firstWhere('id_penanaman', $panen['penanaman_id']);

    expect($panen)->not->toHaveKey('volume_benih')
        ->and($tanam['volume_benih'])->not->toBeNull();

    $isi = $this->get(route('panen.index'))->assertOk()->getContent();

    $tampil = rtrim(rtrim(number_format($tanam['volume_benih'], 2, ',', '.'), '0'), ',');

    expect($isi)->toContain($tampil.' kg');
});

it('menyisipkan satuan permukiman di bawah nama poktan pada rekap', function () {
    // Nama poktan tidak menyatakan lokasinya, sehingga tanpa keterangan ini
    // pembaca harus mengingat sendiri kelompok mana ada di SP mana.
    $isi = $this->get(route('panen.rekap.kelompok', ['kelompok' => 'poktan']))
        ->assertOk()
        ->getContent();

    $rekap = DummyData::rekapPanen('poktan', (int) date('Y'));

    expect($rekap)->not->toBeEmpty();

    foreach ($rekap as $baris) {
        expect($isi)->toContain($baris['nama']);

        foreach ($baris['sp'] as $sp) {
            expect($isi)->toContain($sp);
        }
    }
});

it('menghitung jumlah anggota rekap dari himpunan poktan, bukan tiap penanaman', function () {
    /*
     * POKTAN MEKAR JAYA memiliki tiga penanaman pada tahun yang sama.
     * Menjumlahkan anggotanya per baris penanaman menghasilkan 9 orang untuk
     * kelompok yang sebenarnya beranggota 3 - dan angka itu tampak wajar
     * sekilas, sehingga tidak akan ada yang menyadarinya.
     *
     * Cacat ini nyata dan lolos mutasi sebelumnya: uji luas lahan tidak
     * menangkapnya, sebab luas memang dihimpun terpisah.
     */
    foreach (DummyData::rekapPanen('poktan', 2026) as $baris) {
        // Pada tab poktan, satu baris berarti satu kelompok - sehingga
        // angkanya wajib sama persis dengan kekuatan kelompok itu.
        $poktan = collect(DummyData::poktan())->firstWhere('nama', $baris['nama']);

        expect($poktan)->not->toBeNull();

        $kekuatan = DummyData::rekapLahanPoktan($poktan['id_poktan']);

        expect($baris['jumlah_anggota'])->toBe($kekuatan['jumlah_anggota'], $baris['nama']);
    }

    // MEKAR JAYA dipakai sebagai penjaga tersendiri: ia satu-satunya yang
    // memiliki lebih dari satu penanaman, sehingga perbedaan cara menghitung
    // benar-benar terasa di sana.
    $mekar = collect(DummyData::rekapPanen('poktan', 2026))->firstWhere('nama', 'POKTAN MEKAR JAYA');

    expect($mekar['jumlah_anggota'])->toBe(3);

    $cacahPenanaman = collect(DummyData::penanaman())
        ->filter(fn ($t) => $t['poktan'] === 'POKTAN MEKAR JAYA'
            && DummyData::tahunRekapPanen($t['id_penanaman']) === 2026)
        ->count();

    expect($cacahPenanaman)->toBeGreaterThan(1);
});

it('menghitung luas lahan rekap dari himpunan poktan, bukan tiap penanaman', function () {
    /*
     * POKTAN MEKAR JAYA memiliki beberapa penanaman pada tahun yang sama.
     * Menjumlahkan luas lahannya per baris penanaman akan menghitung lahan
     * yang sama berkali-kali, dan totalnya melampaui luas kawasan.
     */
    $sp = collect(DummyData::rekapPanen('sp', 2026))->firstWhere('nama', 'SP Kapitan Meo');

    // Dua poktan di SP itu: MEKAR JAYA 4,25 ha + SUBUR MAKMUR 2,00 ha.
    expect($sp['jumlah_poktan'])->toBe(2)
        ->and($sp['luas_lahan'])->toBe(6.25);

    // Penanamannya lebih banyak daripada poktannya, sehingga perbedaan cara
    // menghitung benar-benar terasa.
    $cacahPenanaman = collect(DummyData::penanaman())
        ->filter(fn ($t) => $t['satuan_permukiman'] === 'SP Kapitan Meo'
            && DummyData::tahunRekapPanen($t['id_penanaman']) === 2026)
        ->count();

    expect($cacahPenanaman)->toBeGreaterThan($sp['jumlah_poktan']);
});

it('menuntun petugas mendaftarkan benih ketika belum ada yang tersedia', function () {
    /*
     * Benih wajib sejak 2026-08-24, sehingga dropdown yang kosong menjadi
     * jalan buntu - kontrol mati yang dilarang ui-spec.md R-26.
     *
     * Yang tampil karena itu keterangan beserta TAUTAN ke form saprotan,
     * bukan sekadar dropdown kosong.
     */
    $isi = $this->get(route('penanaman'))->assertOk()->getContent();

    expect($isi)->toContain('Belum ada benih terdaftar')
        ->and($isi)->toContain(route('saprotan.index'))
        ->and($isi)->toContain('Daftarkan Benih di Saprotan');

    // Pilihan lama yang membiarkan penanaman tanpa benih sudah dicabut.
    expect($isi)->not->toContain('Tanpa benih tercatat');
});

it('menyaring rekap panen secara silang menurut sp dan komoditas', function () {
    /*
     * Tab menentukan baris APA, penyaring menentukan baris MANA. Dua sumbu
     * terpisah, dan justru gabungannya yang berguna: "berapa produksi jagung
     * di SP Weain" tidak dapat dijawab tanpa keduanya.
     *
     * Yang diuji PERILAKUNYA - cacah baris benar-benar menyempit - bukan
     * keberadaan markup penyaring. Penyaring yang tidak menyaring apa pun
     * tetap merender dropdown yang tampak sehat.
     */
    $semua = DummyData::rekapPanen('sp', 2026);
    $jagung = DummyData::rekapPanen('sp', 2026, null, 'JAGUNG');

    expect($semua)->not->toBeEmpty()
        ->and(count($jagung))->toBeLessThan(count($semua))
        ->and($jagung)->not->toBeEmpty();

    // Angkanya ikut menyempit, bukan hanya barisnya.
    $tanamSemua = array_sum(array_column($semua, 'realisasi_tanam'));
    $tanamJagung = array_sum(array_column($jagung, 'realisasi_tanam'));

    expect($tanamJagung)->toBeLessThan($tanamSemua);

    // Penyaring SP pada tab komoditas.
    $weain = DummyData::rekapPanen('komoditas', 2026, 'SP Weain');

    expect($weain)->toHaveCount(1)
        ->and($weain[0]['nama'])->toBe('PADI');

    // Tab poktan menerima kedua penyaring sekaligus.
    $keduanya = DummyData::rekapPanen('poktan', 2026, 'SP Kapitan Meo', 'JAGUNG');

    foreach ($keduanya as $baris) {
        expect($baris['sp'])->toBe(['SP Kapitan Meo']);
    }

    expect($keduanya)->not->toBeEmpty();
});

it('menyusun pilihan penyaring rekap dari data, bukan dari master', function () {
    /*
     * Master memuat enam satuan permukiman dan lima komoditas, sedangkan
     * tahun 2025 hanya memiliki satu dari masing-masing. Menawarkan sisanya
     * berarti menyuguhkan pilihan yang DIJAMIN menghasilkan tabel kosong -
     * kontrol mati yang dilarang ui-spec.md R-26.
     */
    $opsi2025 = DummyData::opsiFilterRekapPanen(2025);
    $opsi2026 = DummyData::opsiFilterRekapPanen(2026);

    expect($opsi2025['sp'])->toBe(['SP Kapitan Meo'])
        ->and($opsi2025['komoditas'])->toBe(['JAGUNG']);

    // Tahun berbeda menghasilkan daftar berbeda; bila sama, uji ini tidak
    // membuktikan bahwa opsinya benar-benar dihitung dari data.
    expect($opsi2026['sp'])->not->toBe($opsi2025['sp'])
        ->and(count($opsi2026['komoditas']))->toBeGreaterThan(count($opsi2025['komoditas']));

    // Lebih sedikit daripada master, dan itu memang maksudnya.
    expect(count($opsi2026['sp']))->toBeLessThan(count(DummyData::satuanPermukiman()))
        ->and(count($opsi2026['komoditas']))->toBeLessThan(count(DummyData::komoditas()));

    // Seluruh opsi benar-benar menghasilkan baris, bukan tabel kosong.
    foreach ($opsi2026['komoditas'] as $nama) {
        expect(DummyData::rekapPanen('sp', 2026, null, $nama))->not->toBeEmpty();
    }
});

it('merender penyaring rekap sesuai tabnya', function () {
    /*
     * Menyaring SP pada tab Per SP hanya menyisakan satu baris yang sudah
     * terlihat sejak awal; kontrol yang tidak berguna sama saja dengan
     * kontrol mati.
     */
    $harapan = [
        'sp' => ['ada' => ['name="komoditas"'], 'tiada' => ['name="sp"']],
        'komoditas' => ['ada' => ['name="sp"'], 'tiada' => ['name="komoditas"']],
        'poktan' => ['ada' => ['name="sp"', 'name="komoditas"'], 'tiada' => []],
    ];

    foreach ($harapan as $tab => $periksa) {
        $isi = $this->get(route('panen.rekap.kelompok', ['kelompok' => $tab]))
            ->assertOk()
            ->getContent();

        foreach ($periksa['ada'] as $teks) {
            expect($isi)->toContain($teks);
        }

        foreach ($periksa['tiada'] as $teks) {
            expect($isi)->not->toContain($teks);
        }

        // Penyaring tahun selalu ada pada ketiga tab.
        expect($isi)->toContain('name="tahun"');
    }
});

it('melepas penyaring rekap yang tidak tersedia pada tahun terpilih', function () {
    /*
     * Keadaannya nyata: petugas menyaring CABAI pada 2026, lalu berpindah ke
     * 2025 - dan cabai tidak ditanam tahun itu.
     *
     * Tanpa pelepasan ini halaman tampak rusak; tanpa pemberitahuannya,
     * petugas mengira penyaringnya yang tidak bekerja.
     */
    expect(DummyData::opsiFilterRekapPanen(2025)['komoditas'])->not->toContain('CABAI');

    $isi = $this->get(route('panen.rekap.kelompok', ['kelompok' => 'poktan']).'?tahun=2025&komoditas=CABAI')
        ->assertOk()
        ->getContent();

    expect($isi)->toContain('dilepas')
        ->and($isi)->toContain('CABAI');

    // Yang penting: tabelnya TIDAK kosong. Penyaring dilepas, bukan
    // dipertahankan lalu menyisakan tabel hampa.
    expect(DummyData::rekapPanen('poktan', 2025))->not->toBeEmpty();
});

it('menyebut cakupan penyaring pada judul dan baris total rekap', function () {
    /*
     * Baris total IKUT MENYEMPIT ketika penyaring aktif. Tanpa keterangan
     * ini, angkanya dapat disalin ke laporan sebagai total kawasan padahal
     * hanya mencakup satu komoditas.
     */
    $isi = $this->get(route('panen.rekap.kelompok', ['kelompok' => 'sp']).'?tahun=2026&komoditas=JAGUNG')
        ->assertOk()
        ->getContent();

    expect($isi)->toContain('Tahun Panen 2026')
        ->and($isi)->toContain('JAGUNG saja')
        ->and($isi)->toContain('hanya mencakup penyaring');

    // Tanpa penyaring, keterangan itu tidak muncul - kalau selalu muncul, ia
    // berhenti menjadi peringatan.
    $tanpa = $this->get(route('panen.rekap.kelompok', ['kelompok' => 'sp']).'?tahun=2026')
        ->assertOk()
        ->getContent();

    expect($tanpa)->not->toContain('hanya mencakup penyaring')
        ->and($tanpa)->not->toContain('saja');
});

/*
|--------------------------------------------------------------------------
| Status panen pada kedua daftar
|--------------------------------------------------------------------------
*/

it('menyaring daftar penanaman menurut status panennya', function () {
    // Yang diuji PERILAKUNYA, bukan keberadaan markup penyaring. Uji string
    // akan tetap hijau meski penyaringnya tidak menyaring apa pun, dan
    // kekeliruan seperti itu sudah dua kali lolos (notes.md b799 dan 1d.2).
    //
    // Yang dihitung TAUTAN RINCIAN tiap baris, bukan nama poktannya. Nama
    // poktan juga muncul pada dropdown di dalam modal form, sehingga
    // assertDontSee atasnya akan memerah meski penyaringnya bekerja benar.
    $barisTampil = function (array $kueri): array {
        $isi = $this->get(route('penanaman', $kueri))->assertOk()->getContent();
        $ada = [];

        foreach (DummyData::penanaman() as $tanam) {
            if (str_contains($isi, route('penanaman.detail', $tanam['id_penanaman']))) {
                $ada[] = $tanam['id_penanaman'];
            }
        }

        return $ada;
    };

    $belum = $barisTampil(['status' => StatusPanen::BelumDipanen->value]);
    $selesai = $barisTampil(['status' => StatusPanen::SelesaiDipanen->value]);
    $semua = $barisTampil([]);

    // Keduanya berisi, sehingga penyaringnya benar-benar teruji.
    expect($belum)->not->toBeEmpty()
        ->and($selesai)->not->toBeEmpty();

    // Tidak ada baris yang muncul pada kedua penyaring sekaligus, dan
    // gabungannya menutup seluruh penanaman.
    expect(array_intersect($belum, $selesai))->toBeEmpty()
        ->and(count($belum) + count($selesai))->toBe(count($semua));

    // Dicocokkan dengan keadaan yang benar-benar dihitung dari datanya.
    foreach (DummyData::penanaman() as $tanam) {
        $id = $tanam['id_penanaman'];
        $status = DummyData::statusPanen($id);

        expect($status === StatusPanen::BelumDipanen ? $belum : $selesai)->toContain($id);
    }
});

it('tidak menawarkan penyaring status pada daftar hasil panen', function () {
    // DICABUT 2026-08-24 bersama panen bertahap. Setiap baris pada halaman
    // hasil panen pasti berasal dari penanaman yang sudah selesai dipanen -
    // sebab barisnya sendiri yang menuntaskannya - sehingga penyaring dengan
    // satu-satunya nilai yang mungkin tidak menyaring apa pun.
    //
    // Kontrol semacam itu dilarang ui-spec.md R-26.
    $isi = $this->get(route('panen.index'))->assertOk()->getContent();

    expect($isi)->not->toContain('name="status"');

    // Halaman Penanaman JUSTRU wajib memilikinya, sebab di sana kedua status
    // benar-benar ada.
    $penanaman = $this->get(route('penanaman'))->assertOk()->getContent();

    expect($penanaman)->toContain('name="status"');

    foreach (StatusPanen::cases() as $status) {
        expect($penanaman)->toContain('value="'.$status->value.'"');
    }
});

it('menegaskan puso pada daftar hasil panen', function () {
    // Kolom Puso menggantikan kolom Status 2026-08-24. Status kini seragam
    // pada seluruh baris sehingga tidak membedakan apa pun; puso justru yang
    // membedakan panen mulus, gagal sebagian, dan gagal total.
    $isi = $this->get(route('panen.index'))->assertOk()->getContent();

    expect($isi)->toContain('Puso (ha)')
        ->and($isi)->toContain('gagal total');
});

/*
|--------------------------------------------------------------------------
| Halaman rincian penanaman dan panen
|--------------------------------------------------------------------------
*/

it('menjumlahkan panen pada rincian penanaman memakai kunci yang benar', function () {
    // CACAT NYATA yang diperbaiki 2026-08-24: halaman ini menjumlahkan kunci
    // `volume` yang sudah dihapus pada perombakan 2026-08-22, sehingga baris
    // "Total volume" SELALU 0,00 sejak hari itu.
    //
    // Yang diuji ANGKANYA, bukan keberadaan string. Uji lama tetap hijau
    // meski angkanya nol, sebab yang diperiksa hanya markup barisnya.
    $panen = array_values(array_filter(
        DummyData::hasilPanen(),
        fn ($p) => ($p['penanaman_id'] ?? null) === 3,
    ));

    $ton = array_sum(array_map(
        fn ($p) => DummyData::keTon($p['produksi'], $p['satuan']),
        $panen
    ));

    // Penjagaan terhadap ujinya sendiri: bila datanya berubah menjadi nol,
    // uji di bawah akan lolos secara palsu.
    expect($ton)->toBeGreaterThan(0);

    $this->get(route('penanaman.detail', 3))
        ->assertOk()
        ->assertSee(number_format($ton, 3, ',', '.').' ton');
});

it('menyejajarkan judul kolom dengan isinya pada rincian penanaman', function () {
    // CACAT NYATA: header berbunyi "Hasil Panen" lalu "Produksi", sedangkan
    // selnya mencetak produksi lalu hasil panen. Angka ton tampil di bawah
    // judul hektare tanpa ada yang menegur.
    $penuh = $this->get(route('penanaman.detail', 3))->assertOk()->getContent();

    /*
     * Dipotong ke tabelnya saja, dan "Puso (ha)" dipakai sebagai penanda awal
     * alih-alih "Hasil Panen". Dua sebab, keduanya sudah memerahkan uji ini:
     *
     * - "Produksi" juga muncul pada menu sidebar "Produksi Pertanian",
     * - "Hasil Panen" juga muncul sebagai label tab beserta cacahnya.
     *
     * Keduanya membuat perbandingan posisi mengukur benda yang salah.
     */
    $awal = strpos($penuh, 'Puso (ha)');
    expect($awal)->not->toBeFalse();

    $isi = substr($penuh, $awal, 3000);

    $posisiProduksi = strpos($isi, 'Produksi');
    $posisiHarga = strpos($isi, 'Harga Jual');

    // Urutan header wajib Puso -> Produksi -> Harga Jual.
    expect($posisiProduksi)->not->toBeFalse()
        ->and($posisiHarga)->not->toBeFalse()
        ->and($posisiProduksi)->toBeLessThan($posisiHarga);

    // Kolom hasil panen wajib mendahului puso pada barisan header.
    $header = substr($penuh, 0, $awal);
    expect(strrpos($header, 'Hasil Panen'))->not->toBeFalse();

    // Satuannya ikut tercetak pada sel produksi, sehingga tertukarnya kolom
    // akan terlihat mata alih-alih menjadi angka yang diam-diam salah.
    $panen = collect(DummyData::hasilPanen())->firstWhere('penanaman_id', 3);

    expect($isi)->toContain(number_format($panen['produksi'], 2, ',', '.').' '.$panen['satuan']);
});

it('mencetak periode tanam sebagai bulan, bukan tanggal karangan', function () {
    // Kolomnya CHAR(7) berisi bulan. Mencetaknya 'd F Y' memunculkan tanggal
    // 01 yang berasal dari imbuhan Carbon, bukan dari pendataan - presisi
    // palsu yang justru dihindari keputusan bulan-saja (rules.md 7d.9).
    $isi = $this->get(route('penanaman.detail', 1))->assertOk()->getContent();

    expect($isi)->toContain('November 2025')
        ->and($isi)->not->toContain('01 November 2025')
        ->and($isi)->not->toContain('Tanggal tanam');
});

it('menampilkan seluruh isian form pada rincian penanaman dan panen', function () {
    // R-26: isian yang tidak punya tempat tampil membuat petugas mengetik
    // sesuatu lalu tidak menemukannya lagi.
    $penanaman = $this->get(route('penanaman.detail', 1))->assertOk()->getContent();

    expect($penanaman)->toContain('Belum ditanam')
        ->and($penanaman)->toContain('bast-tanam-jagung-nov-2025.pdf');

    $panen = collect(DummyData::hasilPanen())->firstWhere('id_hasil_panen', 1);
    $isi = $this->get(route('panen.detail', 1))->assertOk()->getContent();

    expect($isi)->toContain($panen['keterangan'])
        ->and($isi)->toContain($panen['dokumen_pendukung'])
        ->and($isi)->toContain('Realisasi tanam');
});

it('menyediakan catatan dan dokumen pada setiap baris hasil panen', function () {
    // Kamus data 9.3 SUDAH mencantumkan keduanya dan form SUDAH punya
    // isiannya, tetapi datanya tidak pernah memilikinya. Halaman rincian
    // membacanya lewat `?? '-'`, sehingga selalu bertuliskan "-" tanpa
    // pernah memerah: petugas mengetik catatan, menyimpan, dan catatannya
    // lenyap tanpa pesan apa pun.
    foreach (DummyData::hasilPanen() as $p) {
        expect($p)->toHaveKey('keterangan')
            ->and($p)->toHaveKey('dokumen_pendukung');
    }

    // Sekurang-kurangnya satu baris benar-benar terisi, agar tampilan
    // terisinya ikut teruji dan bukan hanya cabang kosongnya.
    $berdokumen = collect(DummyData::hasilPanen())->filter(fn ($p) => $p['dokumen_pendukung'] !== null);

    expect($berdokumen)->not->toBeEmpty();
});

it('tidak mencetak setara ton dua kali pada rincian panen', function () {
    // Dua tempat untuk satu angka membuat pembaca mengira keduanya berbeda
    // lalu mencari-cari bedanya.
    $isi = $this->get(route('panen.detail', 4))->assertOk()->getContent();

    // Yang dihitung KALIMAT KONVERSINYA, bukan kata "setara" begitu saja:
    // kata itu juga muncul pada catatan kaki yang menerangkan aturan
    // konversi, dan kalimat itu memang boleh ada.
    expect(substr_count($isi, 'Setara ton'))->toBe(0)
        ->and(substr_count($isi, 'ton saat direkap'))->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Modul pengaduan
|--------------------------------------------------------------------------
*/

it('merender daftar pengaduan beserta seluruh barisnya', function () {
    $respons = $this->get(route('pengaduan.index'));

    $respons->assertOk();

    foreach (DummyData::pengaduan() as $baris) {
        $respons->assertSee($baris['nomor_pengaduan'])->assertSee($baris['judul']);
    }
});

it('menyaring pengaduan menurut status, kategori, dan prioritas', function () {
    // Nomornya dibaca dari data contoh, bukan diketik: sejak nomor memuat
    // bagian acak, mengetiknya berarti mengunci nilai yang bukan bagian
    // dari perilaku yang dijaga. Membacanya lewat penyaring yang sama juga
    // membuat uji ini menegakkan HUBUNGAN status dengan barisnya, bukan
    // sekadar mencocokkan teks.
    $nomor = fn (string $kunci, $nilai) => collect(DummyData::pengaduan())
        ->firstWhere($kunci, $nilai)['nomor_pengaduan'];

    $this->get(route('pengaduan.index', ['status' => 'Selesai']))
        ->assertSee($nomor('status', 'Selesai'))
        ->assertDontSee($nomor('status', 'Diproses'));

    $this->get(route('pengaduan.index', ['prioritas' => 'Mendesak']))
        ->assertSee($nomor('prioritas', 'Mendesak'));

    $this->get(route('pengaduan.index', ['kategori' => 'Rumah']))
        ->assertSee($nomor('kategori', 'Rumah'));
});

it('hanya menawarkan satu status berikutnya yang sah', function () {
    // Inti aturan modul ini: alur wajib berurutan dan tidak boleh melompat
    // (agents/rules.md bagian 10b poin 4). Antarmuka mencegahnya dengan
    // merender tepat satu tombol tujuan, bukan daftar seluruh status.
    $harapan = [
        3 => 'Diterima',   // sedang Menunggu Diterima
        2 => 'Diproses',   // sedang Diterima
        1 => 'Selesai',    // sedang Diproses
    ];

    foreach ($harapan as $id => $tujuan) {
        $isi = $this->get(route('pengaduan.detail', $id))->getContent();

        expect($isi)->toContain('Tandai '.$tujuan)
            ->and($isi)->toContain('name="status_sesudah" value="'.$tujuan.'"');

        // Status lain tidak boleh ikut ditawarkan sebagai nilai kiriman.
        foreach (StatusPengaduan::cases() as $lain) {
            if ($lain->value !== $tujuan) {
                expect($isi)->not->toContain('name="status_sesudah" value="'.$lain->value.'"');
            }
        }
    }
});

it('tidak menawarkan tahap lanjutan pada pengaduan yang sudah selesai', function () {
    // Tombol mati lebih menyesatkan daripada tidak ada tombol (R-26).
    $isi = $this->get(route('pengaduan.detail', 5))->getContent();

    expect($isi)->toContain('Tidak ada tahap lanjutan')
        ->and($isi)->not->toContain('name="status_sesudah"');
});

it('menuntut catatan tindakan pada setiap penanganan', function () {
    // Riwayat tanpa catatan tidak menjelaskan apa pun kepada pembacanya
    // (agents/rules.md bagian 10b poin 5).
    $isi = $this->get(route('pengaduan.detail', 1))->getContent();

    expect($isi)->toContain('name="catatan"')
        ->and($isi)->toContain('Catatan Tindakan');
});

it('menampilkan riwayat penanganan berurutan', function () {
    $this->get(route('pengaduan.detail', 5))
        ->assertOk()
        ->assertSee('Laporan serangan hama diterima Dinas Pertanian.')
        ->assertSee('Pendampingan penyemprotan selesai, kondisi tanaman membaik. Petani diberi panduan pengendalian hama.');
});

it('menampilkan keadaan kosong untuk pengaduan yang belum ditangani', function () {
    // Pengaduan 3 dan 4 masih Menunggu Diterima, belum punya riwayat.
    $this->get(route('pengaduan.detail', 3))->assertSee('Belum ada penanganan');
});

it('mengisi bidang penanganan dari kategori sebagai nilai awal', function () {
    // Diubah 2026-08-19: bidang kini berupa PILIHAN, bukan tampilan baca-saja.
    // Nilainya terisi otomatis dari kategori, tetapi selalu dapat ditimpa
    // petugas sebab penentuan dinas bergantung isi laporan yang tidak selalu
    // terbaca dari kategori (rules.md 10b poin 7c).
    $isi = $this->get(route('pengaduan.index'))->getContent();

    expect($isi)->toContain('id="tambah_bidang"')
        ->and($isi)->toContain('petaBidang')
        // Empat kategori netral wajib menghasilkan peringatan, bukan tebakan.
        ->and($isi)->toContain('bidangNetral');
});

it('menandai pengaduan berprioritas mendesak yang belum selesai', function () {
    $this->get(route('pengaduan.detail', 4))
        ->assertSee('Pengaduan berprioritas mendesak');
});

it('merender rekap pengaduan pada seluruh dasar pengelompokan', function () {
    // Memeriksa penanda baris total, bukan kalimatnya. Lihat alasannya pada
    // uji rekap panen di atas.
    foreach (['kategori', 'status', 'sp', 'prioritas', 'bidang'] as $kelompok) {
        $this->get(route('pengaduan.rekap', ['kelompok' => $kelompok]))
            ->assertOk()
            ->assertSee('motif-baris-total', false);
    }
});

it('menjumlahkan rekap pengaduan sesuai jumlah datanya', function () {
    $rekap = DummyData::rekapPengaduan('kategori');

    expect(array_sum(array_column($rekap, 'jumlah')))->toBe(count(DummyData::pengaduan()));

    // Selesai dan belum selesai wajib genap membagi keseluruhan.
    foreach ($rekap as $baris) {
        expect($baris['selesai'] + $baris['belum_selesai'])->toBe($baris['jumlah']);
    }
});

it('memakai komposisi rekap tanpa kartu statistik', function () {
    $isi = $this->get(route('pengaduan.rekap'))->getContent();

    expect($isi)->toContain('motif-baris-total')
        ->and($isi)->not->toContain('motif-judul-kartu');
});

it('tidak menganggap rekap sebagai id pengaduan', function () {
    $this->get(route('pengaduan.rekap'))->assertOk()->assertSee('Rekap per');
});

it('membalas 404 untuk pengaduan yang tidak ada', function () {
    $this->get('/pengaduan/99')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Halaman publik tanpa login
|--------------------------------------------------------------------------
*/

it('merender halaman pengaduan warga tanpa perlu masuk', function () {
    $this->get(route('pengaduan-warga'))
        ->assertOk()
        ->assertSee('Sampaikan Pengaduan Anda');
});

it('tidak merender navigasi petugas pada halaman publik', function () {
    // Seluruh tujuan menu petugas memerlukan login, sehingga merendernya di
    // halaman warga berarti mengirim kontrol mati (R-24 dan R-26).
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    // Diperiksa sebagai atribut href lengkap, bukan potongan alamat, karena
    // "/pengaduan" adalah awalan dari "/pengaduan-warga" sehingga pencocokan
    // potongan akan selalu cocok dan ujinya jadi tidak bermakna.
    expect($isi)->not->toContain('id="sidebar"')
        ->and($isi)->not->toContain('href="'.route('transmigran.index').'"')
        ->and($isi)->not->toContain('href="'.route('pengaduan.index').'"')
        ->and($isi)->not->toContain('href="'.route('rumah.index').'"');
});

it('menyembunyikan sidebar dan spanduk petugas dari halaman publik', function () {
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    expect($isi)->not->toContain('id="sidebar"')
        ->and($isi)->not->toContain('Seluruh angka dan nama pada halaman ini');
});

it('tidak meminta warga memilih bidang maupun prioritas', function () {
    // Penilaian kegentingan dan pembagian dinas adalah tugas petugas, bukan
    // beban pelapor (agents/ui-spec.md bagian 4.1a poin 2).
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    expect($isi)->not->toContain('name="prioritas"')
        ->and($isi)->not->toContain('name="bidang"');
});

it('menampilkan nomor pengaduan besar setelah pengiriman berhasil', function () {
    // Nomor adalah satu-satunya cara warga melacak laporannya, sehingga wajib
    // tampil jelas beserta anjuran mencatatnya (ui-spec.md bagian 4.1a poin 3).
    $this->post(route('pengaduan-warga.kirim'), [
        'nama_pelapor' => 'TEST WARGA',
        'kontak_pelapor' => '081200000000',
        'satuan_permukiman_id' => 1,
        'kategori' => 'Rumah',
        'tanggal_pengaduan' => '2026-08-11',
        'judul' => 'Uji kirim',
        'deskripsi' => 'Uji alur kirim pengaduan.',
    ])->assertSessionHas('nomor_pengaduan');

    $this->followingRedirects()
        ->get(route('pengaduan-warga'))
        ->assertOk();
});

it('merender halaman lacak pengaduan', function () {
    $this->get(route('lacak-pengaduan'))
        ->assertOk()
        ->assertSee('Lacak Pengaduan');
});

it('menampilkan status dan catatan penanganan pada halaman lacak', function () {
    // Nomornya DIBACA dari data contoh, tidak diketik (2026-09-02). Sejak
    // nomor pengaduan memuat bagian acak, mengetiknya di sini berarti
    // mengunci nilai yang memang bukan bagian dari perilaku yang dijaga.
    $nomor = DummyData::pengaduan()[4]['nomor_pengaduan'];

    $this->get(route('lacak-pengaduan', ['nomor' => $nomor]))
        ->assertOk()
        ->assertSee('Serangan hama pada tanaman jagung')
        ->assertSee('Pendampingan penyemprotan selesai, kondisi tanaman membaik. Petani diberi panduan pengendalian hama.');
});

it('tidak pernah menampilkan data pribadi pelapor pada halaman lacak', function () {
    // Aturan privasi paling penting modul ini: siapa pun yang menebak nomor
    // pengaduan tidak boleh memanen data pribadi warga lain
    // (agents/rules.md bagian 10b poin 1c).
    $nomor = DummyData::pengaduan()[4]['nomor_pengaduan'];

    $isi = $this->get(route('lacak-pengaduan', ['nomor' => $nomor]))->getContent();

    $pengaduan = collect(DummyData::pengaduan())->firstWhere('nomor_pengaduan', $nomor);

    expect($isi)->not->toContain($pengaduan['nama_pelapor'])
        ->and($isi)->not->toContain($pengaduan['kontak_pelapor']);

    // Nama petugas penangan pun tidak ditampilkan kepada warga.
    foreach (DummyData::penangananPengaduan($nomor) as $jejak) {
        expect($isi)->not->toContain($jejak['petugas']);
    }
});

it('menjelaskan jalan keluar saat nomor pengaduan tidak ditemukan', function () {
    $this->get(route('lacak-pengaduan', ['nomor' => 'SALAH123']))
        ->assertOk()
        ->assertSee('Nomor pengaduan tidak ditemukan')
        ->assertSee('Kirim Pengaduan Baru');
});

it('memberi keterangan pada pengaduan yang belum ditangani', function () {
    // Baris ke-3 masih Menunggu Diterima dan belum punya riwayat.
    $this->get(route('lacak-pengaduan', ['nomor' => DummyData::pengaduan()[2]['nomor_pengaduan']]))
        ->assertSee('Belum ada catatan penanganan');
});

it('menautkan halaman masuk petugas ke kanal pengaduan warga', function () {
    // Suite Feature diautentikasi global (Task 3.2b); halaman /login ber-`guest`.
    auth()->logout();

    $this->get(route('login'))
        ->assertSee(route('pengaduan-warga'), false)
        ->assertSee(route('lacak-pengaduan'), false);
});

/*
|--------------------------------------------------------------------------
| Halaman galat
|--------------------------------------------------------------------------
*/

it('merender halaman 404 untuk alamat yang tidak ada', function () {
    $this->get('/alamat-yang-tidak-pernah-ada')
        ->assertNotFound()
        ->assertSee('Halaman tidak ditemukan');
});

it('memakai halaman 404 kustom pada seluruh modul', function () {
    // abort(404) pada rute modul wajib memakai halaman yang sama, bukan
    // tampilan bawaan Laravel.
    foreach (['/transmigran/99', '/rumah/99', '/lahan/99', '/panen/99', '/pengaduan/99', '/sp/99'] as $alamat) {
        $this->get($alamat)->assertNotFound()->assertSee('Galat 404');
    }
});

it('menyediakan jalan keluar pada halaman 404', function () {
    // Halaman galat tanpa jalan keluar memaksa pengguna menekan tombol kembali
    // peramban, dan itu bukan penanganan keadaan yang layak (ui-spec.md 7).
    $this->get('/alamat-yang-tidak-pernah-ada')
        ->assertSee('Buka Dashboard');
});

it('merender halaman 403 beserta jalan keluarnya', function () {
    // Rute uji dibuat sementara karena RBAC yang memicu 403 baru aktif pada
    // Tahap 3; yang diperiksa di sini adalah tampilannya.
    Route::get('/uji-403', fn () => abort(403));

    $this->get('/uji-403')
        ->assertForbidden()
        ->assertSee('Galat 403')
        ->assertSee('Anda tidak memiliki akses ke halaman ini')
        ->assertSee('Kembali ke Dashboard');
});

it('menyediakan ilustrasi galat untuk kedua mode tema', function () {
    // Mode terang dan gelap sama-sama wajib berfungsi penuh (R-34).
    $isi = $this->get('/alamat-yang-tidak-pernah-ada')->getContent();

    expect($isi)->toContain('/images/error/404.svg')
        ->and($isi)->toContain('/images/error/404-dark.svg');
});

/*
|--------------------------------------------------------------------------
| Lima pola keadaan halaman
|--------------------------------------------------------------------------
*/

it('menyediakan kelima pola keadaan pada galeri komponen', function () {
    // Kelimanya wajib ditangani setiap halaman daftar dan rincian
    // (agents/ui-spec.md bagian 7).
    $this->get(route('galeri-komponen'))
        ->assertOk()
        ->assertSee('Belum ada data lahan')          // kosong
        ->assertSee('Tidak ditemukan hasil untuk')   // pencarian nihil
        ->assertSee('Sedang memuat data')            // memuat
        ->assertSee('Data gagal ditampilkan')        // galat
        ->assertSee('Buka Halaman 403');             // tanpa izin
});

it('memakai skeleton untuk keadaan memuat, bukan spinner layar penuh', function () {
    // Spinner menutupi seluruh halaman sehingga pengguna kehilangan konteks;
    // skeleton memberi tahu bentuk konten yang sedang datang.
    $isi = $this->get(route('galeri-komponen'))->getContent();

    expect($isi)->toContain('animate-pulse')
        ->and($isi)->toContain('role="status"');
});

it('menulis pesan galat tanpa istilah teknis', function () {
    // Pesan wajib dimengerti operator lapangan (agents/rules.md 13.3 poin 7).
    $isi = $this->get(route('galeri-komponen'))->getContent();

    expect($isi)->toContain('jaringan sedang tidak stabil')
        ->and($isi)->toContain('Coba Lagi');

    // Istilah teknis dilarang muncul sebagai pesan bagi pengguna.
    foreach (['HTTP 500', 'Internal Server Error', 'stack trace', 'Exception'] as $istilah) {
        expect($isi)->not->toContain($istilah);
    }
});

/*
|--------------------------------------------------------------------------
| Kesiapan dua mode tema dan responsif
|--------------------------------------------------------------------------
*/

it('menyediakan tombol ganti tema pada seluruh jenis tata letak', function () {
    // Mode terang dan gelap sama-sama wajib berfungsi penuh (R-34), termasuk
    // pada halaman galat dan halaman publik yang memakai tata letak berbeda.
    // Uji menyentuh beranda (auth) DAN login (guest) sekaligus, jadi lepaskan
    // pengalihan `guest` alih-alih logout (Task 3.2b).
    $this->withoutMiddleware(RedirectIfAuthenticated::class);

    foreach ([route('beranda'), route('login'), route('pengaduan-warga')] as $alamat) {
        expect($this->get($alamat)->getContent())->toContain('theme.toggle');
    }

    expect($this->get('/alamat-tidak-ada')->getContent())->toContain('theme.toggle');
});

it('menyeimbangkan tag HTML pada seluruh berkas Blade', function () {
    // Uji ini lahir dari kegagalan nyata: penyuntingan berkas lewat pemotongan
    // indeks string meninggalkan </form> yatim dan dua </div> berlebih pada
    // layouts/app-header.blade.php. Akibatnya peramban menutup <header> lebih
    // awal dan seluruh konten terlempar keluar wadahnya.
    //
    // Yang membuatnya berbahaya: PHP, Blade, dan seluruh uji berbasis HTTP
    // tetap hijau, karena hanya peramban yang terdampak saat membangun DOM.
    $pelanggaran = [];

    foreach (BerkasBlade::semua() as $path) {
        $galat = BerkasBlade::periksaTag(file_get_contents($path));

        if ($galat !== []) {
            $pelanggaran[] = BerkasBlade::namaPendek($path).': '.implode(', ', $galat);
        }
    }

    expect($pelanggaran)->toBe([]);
});

it('tidak memakai teks putih di atas permukaan yang terang', function () {
    // Uji ini lahir dari cacat nyata: nama sistem pada sidebar memakai
    // `text-white` tanpa pasangan mode terang, sementara sidebar berlatar
    // putih pada mode terang dan navy pada mode gelap. Akibatnya nama sistem
    // tidak terbaca sama sekali di mode terang (R-25 dan R-34).
    //
    // Cacat itu luput berbulan-bulan karena verifikasi hanya dilakukan pada
    // mode gelap, tempat teksnya kebetulan terbaca.
    $pelanggaran = [];

    // Latar berwarna pekat: teks putih di atasnya memang benar.
    $latarPekat = '/bg-(brand|navy|teal|gold|green|red|blue|error|success|warning|gray-[6-9]00|black)/';

    foreach (BerkasBlade::semua() as $path) {
        $isi = BerkasBlade::bersihkan(file_get_contents($path));
        preg_match_all('/class="([^"]*)"/', $isi, $cocok);

        foreach ($cocok[1] as $kelas) {
            $putihPolos = preg_match('/(^|\s)text-white(\s|$)/', $kelas);

            if (! $putihPolos) {
                continue;
            }

            // Aman bila latar pekat ditulis langsung pada kelas yang sama.
            if (preg_match($latarPekat, $kelas)) {
                continue;
            }

            // Aman bila sudah punya pasangan warna untuk mode terang.
            if (str_contains($kelas, 'dark:text-')) {
                continue;
            }

            // Aman bila latar disuntikkan lewat ekspresi Blade, contoh
            // `{{ $gayaTombol }}` yang berisi bg-red-600 atau bg-brand-500.
            // Kasus semacam ini tidak dapat dinilai dari kelasnya saja.
            if (str_contains($kelas, '{{')) {
                continue;
            }

            $pelanggaran[] = BerkasBlade::namaPendek($path).': '.$kelas;
        }
    }

    expect($pelanggaran)->toBe([]);
});

it('memasangkan setiap latar terang dengan varian gelapnya', function () {
    // Latar terang tanpa pasangan dark: akan menghasilkan teks putih di atas
    // latar putih saat mode gelap aktif.
    $pelanggaran = [];

    foreach (BerkasBlade::semua() as $path) {
        $isi = BerkasBlade::bersihkan(file_get_contents($path));
        preg_match_all('/class="([^"]*)"/', $isi, $cocok);

        foreach ($cocok[1] as $kelas) {
            $latarTerang = preg_match('/(^|\s)bg-(white|gray-50|gray-100)(\s|$)/', $kelas);

            if ($latarTerang && ! str_contains($kelas, 'dark:bg-')) {
                $pelanggaran[] = BerkasBlade::namaPendek($path).': '.$kelas;
            }
        }
    }

    expect($pelanggaran)->toBe([]);
});

it('tidak memakai lebar tetap yang berlaku pada layar sempit', function () {
    // Lebar tetap besar adalah penyebab paling sering gulir mendatar di ponsel
    // (agents/ui-spec.md bagian 8).
    //
    // Dua koreksi dari bentuk awal uji ini:
    // 1. SVG dibuang lebih dulu, karena koordinat path seperti "361" sempat
    //    terbaca sebagai angka lebar kelas.
    // 2. Hanya kelas TANPA prefix titik henti yang diperiksa. Kelas seperti
    //    `sm:w-[361px]` hanya aktif pada layar 640px ke atas, sehingga tidak
    //    pernah berlaku di layar 360px dan bukan pelanggaran.
    $pelanggaran = [];

    foreach (BerkasBlade::semua() as $path) {
        $isi = BerkasBlade::bersihkan(file_get_contents($path));
        preg_match_all('/class="([^"]*)"/', $isi, $cocok);

        foreach ($cocok[1] as $daftarKelas) {
            foreach (preg_split('/\s+/', $daftarKelas) as $kelas) {
                if (preg_match('/^w-\[(\d+)px\]$/', $kelas, $ukuran) && (int) $ukuran[1] > 360) {
                    $pelanggaran[] = BerkasBlade::namaPendek($path).': '.$kelas;
                }
            }
        }
    }

    expect($pelanggaran)->toBe([]);
});

it('membungkus setiap tabel agar tidak meluber di layar sempit', function () {
    $pelanggaran = [];

    foreach (BerkasBlade::semua() as $path) {
        $isi = file_get_contents($path);

        // Tabel wajib berada dalam wadah bergulir, atau disertai tata letak
        // kartu untuk layar sempit lewat slot kartu.
        if (str_contains($isi, '<table')
            && ! str_contains($isi, 'overflow-x-auto')
            && ! str_contains($isi, 'slot:kartu')) {
            $pelanggaran[] = BerkasBlade::namaPendek($path);
        }
    }

    expect($pelanggaran)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Delivery Gate ANTISLOP
|--------------------------------------------------------------------------
*/

it('tidak memuat em dash pada teks antarmuka', function () {
    // R-02: em dash dilarang pada seluruh teks yang tampil di antarmuka.
    // Dokumen di folder agents dikecualikan (rules.md bagian 13.0).
    $pelanggaran = [];

    foreach (BerkasBlade::semua() as $path) {
        if (str_contains(BerkasBlade::bersihkan(file_get_contents($path)), "\u{2014}")) {
            $pelanggaran[] = BerkasBlade::namaPendek($path);
        }
    }

    expect($pelanggaran)->toBe([]);
});

it('tidak menyisakan teks bawaan template berbahasa Inggris', function () {
    // Komentar dibuang lebih dulu, karena komentar yang MENJELASKAN larangan
    // sebuah teks sempat terbaca sebagai pelanggaran larangan itu sendiri.
    $terlarang = ['Search or type', 'Back to Home Page', 'Card Title Here', 'Toggle Sidebar', 'Get Started'];
    $pelanggaran = [];

    foreach (BerkasBlade::semua() as $path) {
        $isi = BerkasBlade::bersihkan(file_get_contents($path));

        foreach ($terlarang as $teks) {
            if (str_contains($isi, $teks)) {
                $pelanggaran[] = BerkasBlade::namaPendek($path).': '.$teks;
            }
        }
    }

    expect($pelanggaran)->toBe([]);
});

it('memakai CTA yang menyebut objeknya', function () {
    // R-15: tombol wajib menyebut objek yang dikenainya, bukan kata kerja
    // telanjang seperti "Simpan" saja.
    $isi = $this->get(route('transmigran.index'))->getContent();

    expect($isi)->toContain('Simpan Data Transmigran')
        ->and($isi)->toContain('Tambah Data Transmigran');

    expect($this->get(route('panen.index'))->getContent())
        ->toContain('Simpan Hasil Panen');
});

it('menampilkan penanda data contoh pada seluruh halaman petugas', function () {
    // R-17 dan R-38: angka contoh dilarang disajikan seolah data nyata.
    foreach ([
        route('beranda'),
        route('transmigran.index'),
        route('rumah.index'),
        route('lahan.index'),
        route('panen.index'),
        route('pengaduan.index'),
    ] as $alamat) {
        $this->get($alamat)->assertSee('Data contoh');
    }
});

/*
|--------------------------------------------------------------------------
| Navigasi jujur
|--------------------------------------------------------------------------
*/

it('menautkan modul kependudukan, lahan, dan poktan satu sama lain', function () {
    // Operator kerap berpindah dari satu entitas ke entitas terkait; tanpa
    // tautan ini ia harus kembali ke daftar dan mencari ulang.
    $transmigran = $this->get(route('transmigran.detail', 1))->getContent();

    expect($transmigran)->toContain(route('rumah.detail', 1))
        ->and($transmigran)->toContain(route('lahan.detail', 1))
        // Tautan ke PANEN diganti tautan ke POKTAN pada 2026-08-22. Panen
        // kini dicatat per kelompok, bukan per orang, sehingga menaut ke satu
        // baris panen dari halaman keluarga berarti menyiratkan panen itu
        // miliknya sendiri - padahal satu poktan berisi banyak keluarga.
        ->and($transmigran)->toContain(route('poktan.detail', 1));

    // Sebaliknya, rincian rumah dan lahan menaut balik ke pemiliknya.
    expect($this->get(route('rumah.detail', 1))->getContent())
        ->toContain(route('transmigran.detail', 1));

    expect($this->get(route('lahan.detail', 1))->getContent())
        ->toContain(route('transmigran.detail', 1));
});

it('menautkan halaman rincian SP ke seluruh modul terkait', function () {
    $isi = $this->get(route('sp.detail', 1))->getContent();

    expect($isi)->toContain(route('transmigran.detail', 1))
        ->and($isi)->toContain(route('rumah.detail', 1))
        ->and($isi)->toContain(route('lahan.detail', 1))
        ->and($isi)->toContain(route('panen.detail', 1))
        ->and($isi)->toContain(route('pengaduan.detail', 1));
});

it('menautkan isu prioritas dashboard ke rincian pengaduannya', function () {
    // Indikator ke-13 wajib dapat ditelusuri sampai ke laporan aslinya
    // (agents/ui-spec.md bagian 9).
    $this->get(route('beranda'))->assertSee(route('pengaduan.detail', 1), false);
});

it('menautkan setiap item menu sidebar ke halaman yang benar-benar ada', function () {
    // Uji ini lahir dari kegagalan gate: laporan menyatakan "0 tautan mati
    // dari 726", padahal 18 item menu sidebar membalas 404. Skrip gate saat
    // itu hanya memeriksa apakah href kosong atau bernilai '#', sehingga
    // tautan ke /poktan dianggap sah karena bentuknya benar, tanpa pernah
    // menanyakan apakah tujuannya ada.
    //
    // Ini pola kesalahan yang sama dengan dua cacat sebelumnya: memeriksa
    // BENTUK, bukan KENYATAAN. Karena itu uji ini membuka setiap tujuan
    // menu ke aplikasi sungguhan (R-24).
    $mati = [];
    $diperiksa = 0;

    foreach (MenuHelper::definisiMenu() as $kelompok) {
        foreach ($kelompok['items'] as $item) {
            // Sejak sidebar memakai submenu, sebagian besar path berada satu
            // tingkat lebih dalam. Menelusuri 'items' saja membuat uji ini
            // melewati hampir seluruh menu lalu lulus tanpa memeriksa apa pun.
            $tujuan = isset($item['subItems'])
                ? $item['subItems']
                : [$item];

            foreach ($tujuan as $t) {
                $path = $t['path'] ?? null;

                if ($path === null) {
                    continue;
                }

                $diperiksa++;
                $status = $this->get($path)->getStatusCode();

                if ($status !== 200) {
                    $mati[] = $t['name'].' ('.$path.') -> '.$status;
                }
            }
        }
    }

    expect($mati)->toBe([]);

    // Penjaga agar uji tidak diam-diam berhenti memeriksa ketika struktur
    // menu berubah lagi. Angkanya sengaja longgar, yang penting bukan nol.
    expect($diperiksa)->toBeGreaterThanOrEqual(20);
});

it('menautkan menu pengguna hanya ke halaman yang benar-benar ada', function () {
    $isi = $this->get(route('beranda'))->getContent();

    foreach ([route('profil'), route('profil.kata-sandi'), route('logout')] as $tujuan) {
        expect($isi)->toContain($tujuan);
    }
});

it('membuat rute profil dan kata sandi dapat diakses', function () {
    // `login` (guest) dipindah ke uji halaman masuk sendiri (Task 3.2b).
    foreach (['profil', 'profil.kata-sandi', 'ganti-kata-sandi'] as $namaRute) {
        $this->get(route($namaRute))->assertOk();
    }
});

/*
|--------------------------------------------------------------------------
| Asap seluruh rute
|--------------------------------------------------------------------------
*/

it('merender setiap rute GET yang terdaftar tanpa galat', function () {
    // Uji-uji di atas menyebut halaman satu per satu, sehingga halaman yang
    // ditambahkan belakangan mudah luput dari pengujian tanpa ada yang
    // menyadarinya. Audit menemukan 19 dari 41 rute GET tidak pernah dibuka
    // satu uji pun.
    //
    // Uji ini membaca daftar rute langsung dari router, bukan dari daftar
    // yang ditulis tangan, sehingga rute baru otomatis ikut teruji.
    $lewati = [
        'up',               // health check bawaan Laravel
        'uji-403',          // sengaja mengembalikan 403
        'logout',           // mengubah keadaan, diuji terpisah
        'infrastruktur',    // rute lama dengan redirect 301, diuji terpisah
        'master/referensi', // alamat lama daftar pilihan, redirect 301, diuji terpisah
        'login',            // ber-`guest`: mengalihkan user uji yang sudah masuk (Task 3.2b)
        'lupa-kata-sandi',  // ber-`guest`, diuji terpisah
        'verifikasi-kode',  // ber-`guest`, diuji terpisah
    ];

    $gagal = [];

    foreach (Route::getRoutes() as $rute) {
        if (! in_array('GET', $rute->methods(), true)) {
            continue;
        }

        $uri = $rute->uri();

        // Rute berparameter memerlukan nilai yang sahih, sedangkan uji ini
        // hanya menjamin halaman tanpa parameter dapat dibuka. Rute
        // berparameter sudah dicakup uji khusus per modul di atas.
        if (str_contains($uri, '{') || in_array($uri, $lewati, true)) {
            continue;
        }

        $status = $this->get('/'.ltrim($uri, '/'))->getStatusCode();

        if ($status !== 200) {
            $gagal[] = $uri.' -> '.$status;
        }
    }

    expect($gagal)->toBe([]);
});

it('memberi scope pada setiap header kolom tabel', function () {
    // Pembaca layar memakai scope untuk mengaitkan sel dengan headernya.
    // Tanpa itu, tabel padat pada dashboard terbaca sebagai deretan angka
    // tanpa makna. Aturan ini mudah tergeser karena header baru biasanya
    // disalin dari baris tetangga.
    $tanpaScope = [];

    foreach (BerkasBlade::semua() as $berkas) {
        // Komentar dokumentasi di dalam komponen memuat contoh markup yang
        // akan terbaca sebagai pelanggaran bila tidak dibersihkan lebih dulu.
        $isi = BerkasBlade::bersihkan(file_get_contents($berkas));

        if (preg_match('/<th(?![^>]*scope=)(\s[^>]*)?>/', $isi)) {
            $tanpaScope[] = BerkasBlade::namaPendek($berkas);
        }
    }

    expect($tanpaScope)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Manajemen pengguna dan role
|--------------------------------------------------------------------------
*/

it('menyediakan seluruh modal manajemen pengguna', function () {
    $isi = $this->get(route('pengguna.index'))->getContent();

    foreach ([
        'formTambahPengguna',
        'buka-detail-pengguna',
        'buka-setel-sandi',
        'nonaktifkanPengguna',
    ] as $penanda) {
        expect($isi)->toContain($penanda);
    }
});

it('menyembunyikan tombol nonaktifkan pada admin aktif terakhir', function () {
    // rules.md 14b poin 16. Merender tombol lalu menolaknya di server berarti
    // memasang kontrol yang tidak berfungsi, yang dilarang R-26. Data contoh
    // hanya memuat satu akun Admin aktif, sehingga akun itu wajib dilindungi.
    $adminAktif = array_values(array_filter(
        DummyData::pengguna(),
        fn ($u) => $u['role'] === 'Admin' && $u['is_aktif'],
    ));

    expect($adminAktif)->toHaveCount(1);

    $isi = $this->get(route('pengguna.index'))->getContent();

    // Yang dijaga adalah tidak adanya jalur penonaktifan, bukan penandanya.
    // Sejak 2026-08-17 label "Admin terakhir" per baris dihapus; alasannya
    // dinyatakan sekali lewat keterangan di bawah tabel.
    expect($isi)->not->toContain('/pengguna/'.$adminAktif[0]['id_user'].'/nonaktifkan')
        ->and($isi)->toContain('tidak memiliki tombol nonaktifkan');
});

it('tidak pernah menampilkan kolom kata sandi pada modal ubah pengguna', function () {
    // rules.md 14b poin 14. Sistem hanya menyimpan sidik, sehingga nilai lama
    // tidak dapat dibaca siapa pun. Menyediakan kolomnya akan menyiratkan
    // sebaliknya.
    //
    // Diperiksa langsung pada berkas Blade, bukan pada halaman terender,
    // sebab modal ubah baru muncul setelah baris tabel dipilih.
    $sumber = file_get_contents(resource_path('views/pages/pengguna/form.blade.php'));

    expect($sumber)->toContain("mode === 'tambah'")
        ->and($sumber)->toContain('Kata sandi tidak dapat disunting di sini');

    // Sejak 2026-08-14 kata sandi awal tidak lagi diketik Admin melainkan
    // dibuatkan sistem, sehingga tidak ada kolom isian kata sandi di mana pun
    // pada form ini, baik mode tambah maupun ubah.
    expect($sumber)->not->toContain('nama="password_awal"')
        ->and($sumber)->not->toContain('name="password_awal"');

    // Keterangan pembuatan kata sandi wajib berada di dalam cabang tambah,
    // sebab modal ubah tidak boleh menyinggung kata sandi sama sekali.
    $posisiCabang = strpos($sumber, "mode === 'tambah'");
    $posisiKeterangan = strpos($sumber, 'Kata sandi sementara dibuatkan sistem');

    expect($posisiKeterangan)->toBeGreaterThan($posisiCabang);
});

it('menampilkan pilihan penugasan SP hanya untuk role bercakupan Per SP', function () {
    // rules.md 14b poin 2. Pilihan dikendalikan Alpine lewat peta cakupan,
    // sehingga yang diuji adalah keberadaan pengendali itu, bukan sekadar
    // ada tidaknya daftar SP.
    $isi = $this->get(route('pengguna.index'))->getContent();

    expect($isi)->toContain('perluSp')
        ->and($isi)->toContain('Penugasan Satuan Permukiman');
});

it('merender matriks izin role sesuai tabel rules.md 5.1', function () {
    $isi = $this->get(route('pengaturan.role'))->getContent();

    // Jumlah kotak tercentang tiap role wajib sama dengan jumlah izinnya.
    foreach (DummyData::role() as $role) {
        $izin = DummyData::izinRole($role['id_role']);
        $jumlah = array_sum(array_map('count', $izin));

        expect($jumlah)->toBe($role['jumlah_izin']);
    }

    expect($isi)->toContain('Kewenangan per Fitur');
});

it('menyajikan role terkunci sebagai hanya baca', function () {
    // rules.md 5.0a. Role Admin tidak dapat dikurangi izinnya, sehingga
    // matriksnya dirender tanpa kotak centang sama sekali, bukan dengan
    // kotak yang tampak dapat diklik lalu ditolak diam-diam.
    $adminRole = collect(DummyData::role())->firstWhere('is_terkunci', true);

    expect($adminRole)->not->toBeNull();

    $isi = $this->get(route('pengaturan.role'))->getContent();

    expect($isi)->toContain('Role ini terkunci dan hanya dapat dilihat');

    // Modal role terkunci wajib tanpa kotak centang sama sekali.
    //
    // Titik potong memakai atribut id, bukan sekadar nama penandanya, sebab
    // nama itu muncul dua kali per modal: sekali pada aria-labelledby milik
    // pembungkus, sekali pada id judulnya. Memotong dari kemunculan pertama
    // menghasilkan potongan yang berhenti sebelum isi modal, sehingga uji
    // lulus tanpa memeriksa apa pun.
    $awal = strpos($isi, 'id="judul-formRole'.$adminRole['id_role'].'"');
    $berikutnya = strpos($isi, 'id="judul-formRole', $awal + 20);
    $potongan = substr($isi, $awal, $berikutnya === false ? null : $berikutnya - $awal);

    expect($potongan)->not->toContain('name="izin[');

    // Jumlah tanda centang hanya-baca wajib sama dengan jumlah izin Admin,
    // sehingga matriks benar-benar menampilkan data, bukan tabel kosong.
    expect(substr_count($potongan, '&#10003;'))->toBe($adminRole['jumlah_izin']);
});

// Rute tulis `pengguna.*` kini controller nyata yang menyentuh tabel `user`
// (Task 3.5) -- diuji di tests/Database/PengaturanPenggunaTest, bukan di suite
// Feature yang tanpa basis data. Rute tulis `role.*` sejak Task 3.3 C5 juga.

/*
|--------------------------------------------------------------------------
| Pemulihan kata sandi
|--------------------------------------------------------------------------
*/

it('merender halaman pemulihan kata sandi', function () {
    auth()->logout(); // rute ber-`guest` (Task 3.2b)

    $this->get(route('lupa-kata-sandi'))->assertOk();
    $this->get(route('verifikasi-kode'))->assertOk();
});

it('tidak membocorkan apakah sebuah akun terdaftar', function () {
    auth()->logout(); // rute ber-`guest` (Task 3.2b)

    // rules.md 14b poin 9. Pesan yang membedakan "terkirim" dan "tidak
    // ditemukan" mengubah halaman publik ini menjadi alat memeriksa siapa
    // saja yang memiliki akun dinas.
    $isi = $this->get(route('verifikasi-kode'))->getContent();

    expect($isi)->toContain('Bila alamat yang Anda masukkan terdaftar');

    foreach (['tidak ditemukan', 'tidak terdaftar', 'akun tidak ada'] as $bocor) {
        expect($isi)->not->toContain($bocor);
    }
});

it('menawarkan jalur admin pada setiap halaman pemulihan', function () {
    // rules.md 14b poin 11. Jalur Admin satu-satunya yang bekerja tanpa
    // sambungan surel, sehingga tidak boleh hilang dari tampilan ketika
    // jalur mandiri ditambahkan.
    //
    // Yang dijaga adalah keberadaan ajakan menghubungi admin, bukan kalimat
    // persisnya. Ketiga halaman menuliskannya dengan susunan berbeda sesuai
    // konteks masing-masing, dan penyuntingan teks tidak boleh membuat uji ini
    // gagal selama jalurnya masih ditawarkan.
    auth()->logout(); // rute ber-`guest` (Task 3.2b)

    foreach ([route('lupa-kata-sandi'), route('verifikasi-kode'), route('login')] as $tujuan) {
        $isi = strtolower($this->get($tujuan)->getContent());

        expect($isi)->toContain('hubungi admin');
    }
});

it('mengirim kode enam digit, bukan tautan sekali klik', function () {
    auth()->logout(); // rute ber-`guest` (Task 3.2b)

    $isi = $this->get(route('verifikasi-kode'))->getContent();

    expect($isi)->toContain('pattern="[0-9]{6}"')
        ->and($isi)->toContain('one-time-code')
        ->and($isi)->toContain('15 menit');
});

it('menautkan halaman masuk ke pemulihan kata sandi', function () {
    auth()->logout(); // rute ber-`guest` (Task 3.2b)

    $isi = $this->get(route('login'))->getContent();

    expect($isi)->toContain(route('lupa-kata-sandi'))
        ->and($isi)->not->toContain('tidak menyediakan halaman pemulihan mandiri');
});

it('membuat rute tulis pemulihan mengembalikan redirect', function () {
    auth()->logout(); // rute ber-`guest`: pertahankan redirect rutenya sendiri, bukan pengalihan guest (Task 3.2b)

    $this->post(route('lupa-kata-sandi.kirim'))->assertRedirect();
    $this->post(route('atur-ulang-sandi'))->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Konsistensi daftar izin dengan dokumen acuan
|--------------------------------------------------------------------------
*/

it('menyamakan daftar izin dengan kamus data dan rules', function () {
    // Uji ini lahir dari cacat nyata. Pembanding pertama hanya mengadu
    // daftarIzin() dengan rules.md 5.1, lalu menyatakan "cocok sempurna"
    // padahal modul fasilitas_sp sama sekali tidak ada di dalamnya.
    // Penyebabnya: rules.md sendiri menggabungkan Inventaris dan Fasilitas SP
    // menjadi satu baris, sementara kamus data memisahkannya.
    //
    // Memeriksa satu sumber memberi rasa aman yang keliru ketika sumbernya
    // sendiri belum sejalan. Karena itu uji ini mengadu dengan KEDUANYA.
    $aksiUrut = ['lihat', 'tambah', 'ubah', 'hapus'];
    $huruf = ['L' => 'lihat', 'T' => 'tambah', 'U' => 'ubah', 'H' => 'hapus'];

    $kamus = preg_split('/\r\n|\r|\n/', file_get_contents(base_path('agents/data-dictionary.md')));
    $rules = preg_split('/\r\n|\r|\n/', file_get_contents(base_path('agents/rules.md')));

    // Sumber 1: kamus 13.1, modul beserta aksi yang tersedia padanya.
    //
    // Pembacaan dibatasi pada wilayah tabel 13.1 saja. Sebelumnya pembatasnya
    // hanyalah "jumlah sel sama dengan jumlah aksi", dan itu bekerja secara
    // kebetulan selama tabel 13.1 punya 5 kolom. Ketika kewenangan `export`
    // dicabut dan kolomnya menjadi 4, pola yang sama mulai mencocoki 343 baris
    // tabel kolom database di seluruh kamus data, sebab bentuknya memang sama:
    // satu nama berbingkai backtick diikuti empat sel.
    //
    // Menambatkannya pada judul bagian membuat uji ini tidak lagi bergantung
    // pada kebetulan jumlah kolom.
    $awal = null;
    $akhir = null;
    foreach ($kamus as $i => $b) {
        if (str_starts_with(trim($b), '### 13.1 ')) {
            $awal = $i;

            continue;
        }
        if ($awal !== null && str_starts_with(trim($b), '### ')) {
            $akhir = $i;
            break;
        }
    }

    expect($awal)->not->toBeNull('Bagian "### 13.1" tidak ditemukan pada data-dictionary.md');

    $baris131 = array_slice($kamus, $awal, ($akhir ?? count($kamus)) - $awal);

    $modulKamus = [];
    foreach ($baris131 as $b) {
        if (! preg_match('/^\|\s*`([a-z_]+)`\s*\|(.+)\|\s*$/', trim($b), $m)) {
            continue;
        }
        $sel = array_map('trim', explode('|', $m[2]));
        if (count($sel) !== count($aksiUrut)) {
            continue;
        }
        $aksi = [];
        foreach ($sel as $i => $isi) {
            if ($isi !== '') {
                $aksi[] = $aksiUrut[$i];
            }
        }
        $modulKamus[$m[1]] = $aksi;
    }

    expect($modulKamus)->not->toBeEmpty();

    // Yang dipakai kode.
    $dariKode = [];
    foreach (DummyData::daftarIzin() as $kelompok) {
        foreach ($kelompok['modul'] as $modul) {
            $dariKode[$modul['kunci']] = $modul['aksi'];
        }
    }

    $selisih = [];

    foreach ($modulKamus as $kunci => $aksi) {
        if (! isset($dariKode[$kunci])) {
            $selisih[] = "modul '{$kunci}' ada di kamus 13.1 tetapi tidak di daftarIzin()";

            continue;
        }
        $a = $aksi;
        $b2 = $dariKode[$kunci];
        sort($a);
        sort($b2);
        if ($a !== $b2) {
            $selisih[] = "aksi '{$kunci}' berbeda antara kamus dan kode";
        }
    }

    foreach (array_keys($dariKode) as $kunci) {
        if (! isset($modulKamus[$kunci])) {
            $selisih[] = "modul '{$kunci}' ada di daftarIzin() tetapi tidak di kamus 13.1";
        }
    }

    // Sumber 2: rules 5.1, izin tiap role bawaan.
    $petaNama = [
        'Manajemen pengguna' => 'pengguna', 'Pengaturan role' => 'role', 'Audit log' => 'audit_log',
        'Data master wilayah' => 'wilayah', 'Kawasan transmigrasi' => 'kawasan',
        'Satuan permukiman (SP)' => 'sp', 'Inventaris SP' => 'inventaris_sp',
        'Fasilitas SP' => 'fasilitas_sp', 'Data master satuan' => 'satuan',
        'Transmigran' => 'transmigran', 'Rumah & hunian' => 'rumah',
        'Riwayat penghunian' => 'riwayat_penghunian',
        'Riwayat kepala keluarga' => 'riwayat_kepala_keluarga',
        'Data master daftar pilihan' => 'daftar_pilihan',
        'Penilaian kondisi SP' => 'penilaian_kondisi', 'Lahan' => 'lahan',
        'Kelompok tani' => 'poktan',
        'Anggota poktan' => 'anggota_poktan', 'Alsintan' => 'alsintan', 'Saprotan' => 'saprotan',
        'Komoditas' => 'komoditas',
        'Penanaman' => 'penanaman', 'Hasil panen' => 'hasil_panen',
        'Infrastruktur SP' => 'infrastruktur', 'Pengaduan' => 'pengaduan',
        'Penanganan pengaduan' => 'penanganan_pengaduan',
        'Pengelolaan Konten (CMS)' => 'cms', 'Dashboard' => 'dashboard',
    ];

    $izinRules = [];
    foreach ($rules as $b) {
        if (! str_starts_with(trim($b), '|')) {
            continue;
        }
        $sel = array_map('trim', explode('|', trim($b, " \t|")));
        if (count($sel) !== 5 || ! isset($petaNama[$sel[0]])) {
            continue;
        }
        for ($r = 1; $r <= 4; $r++) {
            $a = [];
            if ($sel[$r] !== '-' && $sel[$r] !== '') {
                foreach (preg_split('/\s+/', $sel[$r]) as $h) {
                    if (isset($huruf[$h])) {
                        $a[] = $huruf[$h];
                    }
                }
            }
            $izinRules[$r][$petaNama[$sel[0]]] = $a;
        }
    }

    expect($izinRules[1] ?? [])->toHaveCount(count($modulKamus));

    foreach ([1, 2, 3, 4] as $r) {
        $kode = DummyData::izinRole($r);

        foreach ($izinRules[$r] as $kunci => $harusnya) {
            $ada = $kode[$kunci] ?? [];
            sort($harusnya);
            sort($ada);
            if ($harusnya !== $ada) {
                $selisih[] = "role {$r} modul '{$kunci}' berbeda antara rules 5.1 dan kode";
            }
        }

        foreach (array_keys($kode) as $kunci) {
            if (! isset($izinRules[$r][$kunci])) {
                $selisih[] = "role {$r} modul '{$kunci}' ada di kode tetapi tidak di rules 5.1";
            }
        }
    }

    expect($selisih)->toBe([]);
});

it('mengelompokkan izin sesuai kamus data 13.2', function () {
    // Pengelompokan mengikuti urutan menu sidebar, sehingga admin menemukan
    // modul di tempat yang sama seperti ketika menavigasi sistem.
    $kamus = preg_split('/\r\n|\r|\n/', file_get_contents(base_path('agents/data-dictionary.md')));

    $kelompokKamus = [];
    foreach ($kamus as $b) {
        if (! preg_match('/^\|\s*([A-Za-z ]+)\s*\|\s*(`[a-z_]+`(?:,\s*`[a-z_]+`)*)\s*\|\s*$/', trim($b), $m)) {
            continue;
        }
        preg_match_all('/`([a-z_]+)`/', $m[2], $mm);
        $kelompokKamus[trim($m[1])] = $mm[1];
    }

    expect($kelompokKamus)->not->toBeEmpty();

    $kelompokKode = [];
    foreach (DummyData::daftarIzin() as $kelompok) {
        $kelompokKode[$kelompok['kelompok']] = array_column($kelompok['modul'], 'kunci');
    }

    $selisih = [];

    foreach ($kelompokKamus as $nama => $modul) {
        if (! isset($kelompokKode[$nama])) {
            $selisih[] = "kelompok '{$nama}' ada di kamus 13.2 tetapi tidak di daftarIzin()";

            continue;
        }
        $a = $modul;
        $b2 = $kelompokKode[$nama];
        sort($a);
        sort($b2);
        if ($a !== $b2) {
            $selisih[] = "isi kelompok '{$nama}' berbeda antara kamus dan kode";
        }
    }

    expect($selisih)->toBe([]);
});

it('mencatat jumlah izin yang sama dengan hasil hitungan', function () {
    // jumlah_izin dipakai halaman role sebagai ringkasan. Angka yang ditulis
    // manual mudah meleset tanpa ada yang menyadari: tiga dari empat angka
    // awal ternyata salah, dan koreksi pertamanya pun masih salah.
    foreach (DummyData::role() as $role) {
        $terhitung = array_sum(array_map('count', DummyData::izinRole($role['id_role'])));

        expect($role['jumlah_izin'])->toBe($terhitung);
    }
});

it('memisahkan inventaris dan fasilitas SP sebagai dua modul', function () {
    // Keduanya dua tabel, dua halaman, dan dua izin (rules.md 5.1 catatan 5).
    // Sempat tergabung menjadi satu modul, sehingga fasilitas_sp hilang dari
    // matriks izin tanpa ada yang menyadarinya.
    $kunci = [];
    foreach (DummyData::daftarIzin() as $kelompok) {
        $kunci = array_merge($kunci, array_column($kelompok['modul'], 'kunci'));
    }

    expect($kunci)->toContain('inventaris_sp')
        ->and($kunci)->toContain('fasilitas_sp');

    foreach ([1, 2, 3, 4] as $r) {
        $izin = DummyData::izinRole($r);

        expect($izin)->toHaveKey('inventaris_sp')
            ->and($izin)->toHaveKey('fasilitas_sp');
    }
});

/*
|--------------------------------------------------------------------------
| Kelengkapan form isian
|--------------------------------------------------------------------------
*/

it('menyediakan modal form pada setiap halaman daftar', function () {
    // Uji ini lahir dari celah nyata. Tahap 2 membangun 51 halaman, tetapi
    // form isian hanya dibuat untuk lima modul: Task 2.13 sampai 2.18 hanya
    // menulis "Membuat halaman", sehingga form-nya tidak pernah masuk lingkup
    // dan menyatu ke task CRUD Tahap 4 sampai 8.
    //
    // Akibatnya 14 halaman daftar hanya dapat dibaca tanpa ada yang
    // menyadarinya, sebab halaman baca-saja tidak pernah tampak rusak.
    // Daftar di bawah dibaca dari berkas, bukan ditulis tangan, agar halaman
    // daftar baru ikut terperiksa.
    $tanpaForm = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $nama = BerkasBlade::namaPendek($berkas);
        $isi = file_get_contents($berkas);

        // Komponen bersama memuat contoh pemakaian pada komentarnya, sehingga
        // ikut tertangkap bila tidak dikecualikan.
        if (str_starts_with($nama, 'components/')) {
            continue;
        }

        // Hanya halaman daftar yang wajib punya tombol tambah.
        if (! str_contains($isi, '<x-sim.halaman-daftar')) {
            continue;
        }

        // Halaman rekap memang tidak menerima input; ia menampilkan agregat.
        if (str_contains($nama, 'rekap') || str_contains($nama, 'audit-log')) {
            continue;
        }

        /*
            Dua bentuk yang sama-sama sah, dan itu bukan kelonggaran.

            Sampai 2026-08-27 halaman menuliskan sendiri tombolnya beserta
            `$dispatch('buka-modal', ...)`. Sejak blok itu diangkat menjadi
            `x-sim.aksi-daftar`, halaman hanya menyebut NAMA modalnya lewat
            prop `modal-tambah`, dan komponennyalah yang memuat dispatchnya.

            Uji ini karena itu sempat memerah pada delapan halaman padahal
            tombol Tambahnya terender persis seperti sebelumnya - terbukti
            lewat pembandingan render yang tidak berbeda satu byte pun.

            Yang dijaga tetap sama: halaman daftar tidak boleh baca-saja.
        */
        if (! str_contains($isi, 'buka-modal') && ! str_contains($isi, 'modal-tambah')) {
            $tanpaForm[] = $nama;
        }
    }

    expect($tanpaForm)->toBe([]);
});

it('membuat setiap modal form menunjuk rute yang benar-benar ada', function () {
    // Modal yang aksinya menunjuk rute tak terdaftar akan gagal saat dikirim,
    // dan kegagalan itu baru terlihat ketika pengguna menekan Simpan.
    $namaRute = [];

    foreach (Route::getRoutes() as $rute) {
        if ($rute->getName() !== null) {
            $namaRute[] = $rute->getName();
        }
    }

    $mati = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $isi = file_get_contents($berkas);

        preg_match_all('/:aksi="route\(\'([a-z0-9._-]+)\'/i', $isi, $cocok);

        foreach ($cocok[1] as $nama) {
            if (! in_array($nama, $namaRute, true)) {
                $mati[] = BerkasBlade::namaPendek($berkas).' -> '.$nama;
            }
        }
    }

    expect($mati)->toBe([]);
});

it('menyediakan halaman rincian bagi modul yang dapat disunting', function () {
    // Pola baku sejak Task 2.7: Tambah di halaman daftar, Ubah di halaman
    // rincian. Modul tanpa rincian karena itu tidak punya tempat menaruh
    // tombol Ubah.
    foreach (['alsintan', 'saprotan', 'komoditas', 'sp/infrastruktur'] as $modul) {
        $this->get('/'.$modul.'/1')->assertOk();
        $this->get('/'.$modul.'/999')->assertNotFound();
    }
});

it('menjaga aturan modul pada form yang mudah tergeser', function () {
    // Empat aturan yang tertulis di dokumen tetapi mudah hilang saat form
    // ditata ulang. Masing-masing punya alasan yang tidak terlihat dari
    // tampilannya sendiri.
    $sumber = fn (string $path) => file_get_contents(resource_path('views/pages/'.$path));

    // Anggota poktan ditandai keluar, bukan dihapus, agar catatan penyaluran
    // saprotan tetap memiliki penerima yang jelas.
    expect($sumber('poktan/form-anggota.blade.php'))
        ->toContain('Sudah Keluar')
        ->and($sumber('poktan/form-anggota.blade.php'))->not->toContain('name="hapus"');

    // Penerima saprotan SELALU poktan, tidak pernah perorangan. Aturan lama
    // "hanya anggota aktif" ikut hilang bersama pilihan penerima individu:
    // yang menerima kini kelompok, dan pembagian ke anggota diatur poktan
    // sendiri di luar sistem.
    expect($sumber('saprotan/form.blade.php'))
        ->not->toContain('name="jenis_penerima"')
        ->and($sumber('saprotan/form.blade.php'))->not->toContain('nama="transmigran_id"');

    // Infrastruktur adalah pendataan aset, bukan pelaporan kerusakan.
    expect($sumber('infrastruktur/form.blade.php'))
        ->not->toContain('Lapor Kerusakan')
        ->and($sumber('infrastruktur/form.blade.php'))->toContain('fitur pengaduan');

    // Alsintan tidak lagi menanyakan jenis kepemilikan: pemiliknya selalu
    // kelompok tani sejak 2026-08-22, sehingga radio pilihan dan isian
    // transmigran pemilik ikut dicabut.
    expect($sumber('alsintan/form.blade.php'))
        ->not->toContain('name="kepemilikan"')
        ->and($sumber('alsintan/form.blade.php'))->not->toContain('nama="transmigran_id"');
});

it('memakai nilai enum pada data contoh, bukan teks yang menyerupainya', function () {
    // Data contoh alsintan sempat memakai ''Milik Pribadi'' sedangkan enumnya
    // bernilai ''Pribadi'', sehingga filter kepemilikan pada halaman daftar
    // tidak pernah cocok dan selalu menghasilkan nol baris. Cacat semacam ini
    // tidak terlihat pada tampilan biasa, hanya muncul ketika filter dipakai.
    //
    // Kepemilikan sendiri sudah dicabut 2026-08-22, tetapi cacat yang sama
    // ternyata masih bersembunyi pada kolom lain: ''Pembelian Sendiri'' pada
    // sumber dana alsintan juga bukan nilai enum mana pun. Uji ini karena itu
    // dialihkan ke sana, bukan dihapus.
    $sumberSah = array_column(SumberDana::cases(), 'value');
    $kondisiSah = array_column(Kondisi::cases(), 'value');

    // `sumber_dana` / `tahun_pengadaan` diseragamkan dari `sumber_perolehan`
    // / `tahun_perolehan` pada 2026-08-28 agar sama dengan saprotan dan
    // kamus data 8.3. Sejak Putaran 7 `kondisi` pindah ke baris distribusi.
    foreach (DummyData::alsintan() as $baris) {
        expect($sumberSah)->toContain($baris['sumber_dana']);
    }
    foreach (DummyData::alsintanDistribusi() as $baris) {
        expect($kondisiSah)->toContain($baris['kondisi']);
    }

    // Saprotan memakai `sumber_dana`, diseragamkan dari `sumber` 2026-08-27
    // agar sama dengan kamus data 8.4.
    foreach (DummyData::saprotan() as $baris) {
        expect($sumberSah)->toContain($baris['sumber_dana']);
    }
});

it('menyeragamkan nama field alsintan dengan saprotan dan kamus data', function () {
    // Butir 1 daftar tunggu (notes.md 1o.4): alsintan memakai
    // `tahun_perolehan` / `sumber_perolehan` sementara saprotan dan kedua
    // berkas rujukan memakai "Tahun Pengadaan" / "Sumber Dana". Diseragamkan
    // 2026-08-28. Modul lain (inventaris_sp, fasilitas_sp, infrastruktur)
    // TIDAK ikut: mereka tetap `tahun_perolehan`.
    foreach (DummyData::alsintan() as $baris) {
        expect($baris)->toHaveKeys(['tahun_pengadaan', 'sumber_dana']);
        expect($baris)->not->toHaveKey('tahun_perolehan');
        expect($baris)->not->toHaveKey('sumber_perolehan');
    }

    $form = file_get_contents(resource_path('views/pages/alsintan/form.blade.php'));
    expect(str_contains($form, 'name="tahun_pengadaan"'))->toBeTrue('form alsintan belum memakai name="tahun_pengadaan"');
    expect(str_contains($form, 'name="sumber_dana"'))->toBeTrue('form alsintan belum memakai name="sumber_dana"');
    expect(str_contains($form, 'name="tahun_perolehan"'))->toBeFalse('form alsintan masih memakai nama lama tahun_perolehan');
    expect(str_contains($form, 'name="sumber_perolehan"'))->toBeFalse('form alsintan masih memakai nama lama sumber_perolehan');

    // Halaman rincian alsintan menampilkan kedua nilai.
    $rincian = $this->get('/alsintan/1')->assertOk()->getContent();
    expect($rincian)->toContain('Tahun pengadaan')->toContain('Sumber dana');

    // Laporan Alsintan tetap utuh setelah rename.
    $laporan = $this->get('/laporan/alsintan')->assertOk()->getContent();
    expect($laporan)->toContain('Sumber Dana')->toContain('Tahun Pengadaan');
});

it('mencatat rincian kondisi per unit pada form dan rincian aset SP', function () {
    // Putaran 7 F2: "dua dari tiga pos lapuk" jadi angka. Form fasilitas dan
    // inventaris memuat isian rincian per nilai kondisi; halaman rincian
    // menampilkan sebarannya bila lebih dari satu kondisi.
    $fasilitasForm = $this->get('/sp/fasilitas')->assertOk()->getContent();
    expect($fasilitasForm)
        ->toContain('name="rincian_kondisi[')
        ->toContain('Rincian Kondisi per Unit');

    $inventarisForm = $this->get('/sp/inventaris')->assertOk()->getContent();
    expect($inventarisForm)->toContain('name="rincian_kondisi[');

    // POS KAMLING (fasilitas 5): 1 Baik + 2 Rusak Berat.
    $pos = collect(DummyData::fasilitasSp())->firstWhere('id_fasilitas_sp', 5);
    expect($pos['rincian_kondisi'])->toBe(['Baik' => 1, 'Rusak Berat' => 2]);

    $rincianHal = $this->get('/sp/fasilitas/5')->assertOk()->getContent();
    expect($rincianHal)->toContain('per unit');
});

/*
|--------------------------------------------------------------------------
| Keterbacaan mode gelap dan akses berkas
|--------------------------------------------------------------------------
*/

it('memberi warna pada daftar dropdown di mode gelap', function () {
    // Uji ini lahir dari cacat nyata. Seluruh <select> memakai bg-transparent
    // agar menyatu dengan kartu di belakangnya. Pada mode gelap, daftar yang
    // TERBUKA dirender peramban memakai latar bawaannya yang putih, sementara
    // teksnya mewarisi warna terang. Akibatnya isi dropdown tidak terbaca.
    //
    // Luput lama karena verifikasi visual hanya melihat select dalam keadaan
    // tertutup. Yang tertutup tampak baik, sebab yang dirender saat itu adalah
    // kotaknya, bukan daftarnya.
    $css = file_get_contents(resource_path('css/app.css'));

    // Diperiksa sebagai blok aturan, bukan sekadar keberadaan teksnya.
    // Mencari '.dark select' saja tidak cukup: teks itu tetap ditemukan di
    // dalam '.dark select option', sehingga uji lulus meski aturan untuk
    // elemen select-nya sendiri sudah terhapus.
    expect($css)->toMatch('/\.dark\s+select\s*\{/')
        ->and($css)->toMatch('/\.dark\s+select\s+option\s*\{/');

    // Aturan wajib benar-benar menetapkan warna latar dan teks.
    preg_match('/\.dark\s+select\s*\{([^}]*)\}/', $css, $blok);

    expect($blok[1] ?? '')->toContain('background-color')
        ->and($blok[1] ?? '')->toContain('color:');
});

it('menyediakan cara membuka setiap berkas yang sudah diunggah', function () {
    // DokumenController beserta rutenya sudah lengkap sejak awal, tetapi tidak
    // satu pun halaman memakainya: nama berkas hanya ditampilkan sebagai teks.
    // Petugas karena itu tidak punya cara membuka dokumen yang diunggahnya
    // sendiri.
    $tanpaTautan = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $nama = BerkasBlade::namaPendek($berkas);
        $isi = file_get_contents($berkas);

        if (str_starts_with($nama, 'components/')) {
            continue;
        }

        // Halaman yang menampilkan nama berkas wajib menyediakan tautannya.
        if (! str_contains($isi, 'basename(')) {
            continue;
        }

        if (! str_contains($isi, 'tautan-dokumen') && ! str_contains($isi, 'dokumen.tampilkan')) {
            $tanpaTautan[] = $nama;
        }
    }

    expect($tanpaTautan)->toBe([]);
});

it('tidak pernah menaut berkas lewat path penyimpanan', function () {
    // Berkas berada di luar folder public, sehingga path mentah tidak dapat
    // dibuka peramban sekaligus melewati pemeriksaan izin pada controller.
    $langsung = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $isi = BerkasBlade::bersihkan(file_get_contents($berkas));

        if (preg_match('/href="\{\{\s*\$\w+\[.(dokumen_pendukung|file_dokumen|foto_rumah)/', $isi)) {
            $langsung[] = BerkasBlade::namaPendek($berkas);
        }
    }

    expect($langsung)->toBe([]);
});

it('menyusun sidebar sesuai kelompok yang disepakati', function () {
    // Susunan menu mengikuti cara petugas bekerja, bukan struktur tabel.
    // Dua penempatan yang mudah tergeser kembali: lahan berada di bawah
    // Kependudukan sebab selalu melekat pada satu kepala keluarga, dan
    // Infrastruktur SP di bawah Wilayah sebab asetnya milik SP, bukan poktan.
    $kelompok = array_column(MenuHelper::definisiMenu(), 'title');

    expect($kelompok)->toBe([
        'Menu',
        'Transmigrasi',
        'Pertanian',
        'Pengaduan',
        'Laporan',
        'Administrasi Sistem',
    ]);

    $letak = [];

    foreach (MenuHelper::definisiMenu() as $grup) {
        foreach ($grup['items'] as $item) {
            foreach ($item['subItems'] ?? [$item] as $sub) {
                if (isset($sub['path'])) {
                    $letak[$sub['path']] = $grup['title'];
                }
            }
        }
    }

    expect($letak['/lahan'] ?? null)->toBe('Transmigrasi')
        ->and($letak['/sp/infrastruktur'] ?? null)->toBe('Transmigrasi')
        ->and($letak['/panen/rekap'] ?? null)->toBe('Pertanian');
});

it('menyaring submenu menurut izin, bukan hanya item induknya', function () {
    // Induk submenu tidak punya izinnya sendiri, sehingga kelayakannya
    // ditentukan submenu yang tersisa. Induk yang seluruh submenunya tersaring
    // akan membuka daftar kosong bila tetap dirender.
    foreach (MenuHelper::getMenuGroups() as $grup) {
        foreach ($grup['items'] as $item) {
            if (isset($item['subItems'])) {
                expect($item['subItems'])->not->toBeEmpty();
            }
        }
    }
});

it('menyembunyikan menu berizin dari pengguna tanpa kewenangan (Task 3.4b)', function () {
    // Pengguna tanpa role: punyaIzin() menjawab false untuk semua, sehingga
    // hanya item ber-permission null yang tersisa (Panduan, Tentang). Kelompok
    // lain hilang seluruhnya karena tak menyisakan satu item pun.
    $this->actingAs(new User(['nama' => 'TANPA IZIN']));

    $grup = MenuHelper::getMenuGroups();
    $judul = array_column($grup, 'title');

    expect($judul)->toBe(['Administrasi Sistem']);

    $tautan = [];
    foreach ($grup[0]['items'] as $item) {
        foreach ($item['subItems'] ?? [$item] as $sub) {
            $tautan[] = $sub['path'];
        }
    }
    expect($tautan)->toEqualCanonicalizing(['/panduan', '/tentang']);
});

it('menampilkan seluruh menu bagi pengguna bertanda semuaIzin', function () {
    // Bypass lokal + beforeEach suite ini memakai pengguna semu semuaIzin.
    $semu = new User(['nama' => 'DEV']);
    $semu->semuaIzin = true;
    $this->actingAs($semu);

    $judul = array_column(MenuHelper::getMenuGroups(), 'title');

    expect($judul)->toContain('Transmigrasi', 'Pertanian', 'Pengaduan', 'Laporan', 'Administrasi Sistem');
});

/*
|--------------------------------------------------------------------------
| Kelengkapan aksi baris dan kanal pengaduan
|--------------------------------------------------------------------------
*/

it('menyediakan tombol ubah pada setiap baris tabel yang dapat disunting', function () {
    // Sebelumnya hanya Hapus yang tersedia di baris, sementara Ubah harus
    // lewat halaman rincian. Susunan itu janggal: menghapus jauh lebih
    // berisiko daripada menyunting, tetapi justru lebih mudah dijangkau.
    $tanpaUbah = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $nama = BerkasBlade::namaPendek($berkas);
        $isi = file_get_contents($berkas);

        if (str_starts_with($nama, 'components/') || str_contains($nama, 'galeri-komponen')) {
            continue;
        }

        // Hanya halaman DAFTAR yang diperiksa, dikenali dari perulangan baris
        // tabel. Halaman rincian menampilkan satu baris saja, sehingga tombol
        // ubahnya berada di kepala halaman, bukan di dalam tabel.
        //
        // Tidak memakai penanda <x-sim.halaman-daftar>: lima halaman utama
        // justru tidak memakainya, sehingga penyaringan seperti itu membuat
        // uji melewati modul yang paling perlu diperiksa.
        if (! str_contains($isi, '@foreach ($baris as')) {
            continue;
        }

        // Halaman daftar yang punya tombol hapus per baris wajib punya
        // tombol ubah juga. Menghapus jauh lebih berisiko daripada menyunting.
        if (! str_contains($isi, 'buka-konfirmasi')) {
            continue;
        }

        if (! str_contains($isi, 'buka-modal-baris')) {
            $tanpaUbah[] = $nama;
        }
    }

    expect($tanpaUbah)->toBe([]);
});

it('memakai satu modal ubah untuk seluruh baris, bukan satu per baris', function () {
    // Merender modal di dalam perulangan baris akan menggandakan form
    // sebanyak baris. Satu halaman berisi dua puluh baris berarti dua puluh
    // salinan isian yang sama.
    $isi = $this->get(route('transmigran.index'))->getContent();

    // Banyak tombol, tetapi hanya satu modal.
    expect(substr_count($isi, 'buka-modal-baris'))->toBeGreaterThan(1)
        ->and(substr_count($isi, 'id="judul-formUbahTransmigranBaris"'))->toBe(1);
});

it('mengisi aksi modal berbaris dari data baris yang diklik', function () {
    // Pola aksi memuat penanda :id yang diganti nilai sebenarnya saat modal
    // dibuka, sehingga satu modal cukup melayani seluruh baris.
    $isi = $this->get(route('transmigran.index'))->getContent();

    // Pola aksi dirender lewat @js(), sehingga garis miringnya ter-escape.
    // Yang diperiksa adalah polanya, bukan bentuk penulisannya.
    expect(str_replace('\\/', '/', $isi))->toContain('/transmigran/:id')
        ->and($isi)->toContain(':action="aksi"');
});

it('menyediakan surel opsional pada pengaduan warga', function () {
    // Surel adalah pelengkap, bukan syarat. Sistem tidak boleh bergantung
    // padanya: jaringan di lokus tidak selalu memadai dan sebagian warga
    // tidak memilikinya (rules.md 10b poin 1c-1).
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    expect($isi)->toContain('name="email_pelapor"')
        ->and($isi)->toContain('boleh dikosongkan');

    // Kolom surel wajib TIDAK bertanda required.
    preg_match('/<input[^>]*id="email_pelapor"[^>]*>/', $isi, $cocok);

    expect($cocok[0] ?? '')->not->toContain('required');

    // Nomor HP tetap wajib, sebab itulah jalur yang paling terjangkau warga.
    preg_match('/<input[^>]*id="kontak_pelapor"[^>]*>/', $isi, $hp);

    expect($hp[0] ?? '')->toContain('required');
});

/*
|--------------------------------------------------------------------------
| Keseragaman navigasi dan kolom aksi
|--------------------------------------------------------------------------
*/

it('memakai ikon yang benar-benar terdaftar pada setiap menu', function () {
    // getIconSvg() mengembalikan ikon bintang untuk nama yang tidak dikenal.
    // Empat menu sempat memakai nama yang tidak ada dalam daftar, sehingga
    // keempatnya tampak seragam berbentuk bintang tanpa ada yang menyadari
    // bahwa itu sebenarnya penanda ikon yang hilang.
    $bintang = 'M12 2l3.09 6.26';
    $tanpaIkon = [];

    foreach (MenuHelper::definisiMenu() as $kelompok) {
        foreach ($kelompok['items'] as $item) {
            $svg = MenuHelper::getIconSvg($item['icon'] ?? '');

            if (str_contains($svg, $bintang)) {
                $tanpaIkon[] = $item['name'].' ('.($item['icon'] ?? '-').')';
            }
        }
    }

    expect($tanpaIkon)->toBe([]);
});

it('memberi ikon berbeda pada setiap kelompok menu', function () {
    // Ikon yang berulang membuat kelompok sulit dibedakan sekilas.
    $ikon = [];

    foreach (MenuHelper::definisiMenu() as $kelompok) {
        foreach ($kelompok['items'] as $item) {
            $ikon[] = $item['icon'] ?? '';
        }
    }

    expect($ikon)->toBe(array_unique($ikon));
});

it('membuka submenu pada halaman turunan, bukan hanya halaman daftarnya', function () {
    // Membuka /transmigran/1 berarti pengguna masih berada di dalam cakupan
    // menu Transmigran. Menutup submenunya membuat ia kehilangan jejak posisi.
    $isi = $this->get(route('transmigran.detail', 1))->getContent();

    expect($isi)->toContain("startsWith('transmigran/')");

    // Sorotan memakai jalur terpanjang yang cocok, agar membuka /sp/inventaris
    // tidak menyorot Satuan Permukiman sekaligus Inventaris SP.
    expect($isi)->toContain('semuaJalur');
});

it('tidak mempercayai lebar layar bernilai nol', function () {
    // Tab yang dibuka di latar belakang belum dilukis peramban, sehingga
    // innerWidth-nya sempat bernilai 0. Sidebar mengira layarnya sempit lalu
    // menyempit, dan tampilan baru pulih setelah halaman disegarkan.
    foreach (['layouts/app.blade.php', 'layouts/fullscreen-layout.blade.php'] as $berkas) {
        // TIDAK memakai BerkasBlade::bersihkan(), sebab pembantu itu membuang
        // seluruh isi <script> sementara justru di sanalah kode yang diperiksa
        // berada. Yang dibuang cukup komentarnya saja: penjelasan mengenai
        // cacat ini menyebut innerWidth dan akan terbaca sebagai pelanggaran.
        $isi = file_get_contents(resource_path('views/'.$berkas));
        $isi = preg_replace('#/\*[\s\S]*?\*/#', '', $isi);
        $isi = preg_replace('#//[^\n]*#', '', $isi);

        // Setiap pembacaan innerWidth wajib punya nilai cadangan, sebab tab
        // yang belum dilukis mengembalikan nol.
        preg_match_all('/window\.innerWidth(?![\s]*\|\|)/', $isi, $telanjang);

        expect($telanjang[0])->toBe([], $berkas.' memakai innerWidth tanpa nilai cadangan');
    }
});

it('menyeragamkan kolom aksi berbentuk ikon di seluruh halaman daftar', function () {
    // Audit menemukan tiga tingkat ketidakkonsistenan sekaligus: kelengkapan
    // tindakan, bentuk ikon versus teks, dan delapan halaman tanpa kolom aksi
    // sama sekali. Petugas karena itu harus menebak letak dan bentuk tindakan
    // setiap kali berpindah modul.
    $bermasalah = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $nama = BerkasBlade::namaPendek($berkas);
        $isi = file_get_contents($berkas);

        if (str_starts_with($nama, 'components/') || str_contains($nama, 'galeri-komponen')) {
            continue;
        }

        // Hanya halaman utuh yang diperiksa. Berkas form adalah potongan yang
        // di-include ke dalam modal; audit log dan halaman di menu Laporan
        // memang hanya dibaca, tanpa penyuntingan baris.
        if (str_contains($nama, '/form') || str_contains($nama, 'audit-log')
            || str_contains($nama, 'laporan/')) {
            continue;
        }

        // Halaman daftar dikenali dari perulangan baris tabelnya.
        if (! preg_match('/@foreach \(\$(baris|satuan|kawasan)\b/', $isi)) {
            continue;
        }

        // Setiap halaman daftar wajib menyediakan jalan menyunting barisnya.
        if (! str_contains($isi, 'buka-modal-baris') && ! str_contains($isi, 'aksi-baris')) {
            $bermasalah[] = $nama.' (tanpa aksi baris)';
        }
    }

    expect($bermasalah)->toBe([]);
});

it('tidak meminta warga menilai prioritas laporannya sendiri', function () {
    // Warga tidak mengetahui skala prioritas dinas, dan meminta ia menilainya
    // membuat hampir seluruh laporan ditandai mendesak sehingga penandanya
    // kehilangan makna. Prioritas awal diturunkan dari kategori, lalu direvisi
    // petugas saat meninjau (rules.md 10b poin 6a dan 6b).
    //
    // Koordinat SEBELUMNYA juga tidak diminta di sini, tetapi keputusan itu
    // direvisi: koordinat kini diminta sebagai isian opsional, sebab titik
    // lokasi membantu petugas menemukan masalah tanpa bertanya ulang, dan
    // sifatnya yang opsional menjaga kanal ini tetap terbuka (poin 6c).
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    expect($isi)->not->toContain('name="prioritas"');
});

it('memisahkan penanganan pengaduan dari penyuntingan datanya', function () {
    // Dua tindakan ini tercatat berbeda pada audit log. Menggabungkannya
    // berisiko status ikut berubah ketika petugas hanya membetulkan salah
    // ketik.
    $isi = $this->get(route('pengaduan.index'))->getContent();

    expect($isi)->toContain('buka-tangani-pengaduan')
        ->and($isi)->toContain('buka-modal-baris')
        ->and($isi)->toContain('hanya dapat maju satu langkah');
});

/*
|--------------------------------------------------------------------------
| Struktur tabel
|--------------------------------------------------------------------------
*/

it('menempatkan setiap sel tabel di dalam barisnya', function () {
    // Uji ini lahir dari cacat nyata. Penyisipan kolom aksi memakai nomor
    // baris yang meleset satu posisi, sehingga <td> berakhir SETELAH </tr>.
    // Peramban tidak dapat merender sel di luar baris, lalu melemparnya ke
    // luar tabel, dan tata letak halaman hancur.
    //
    // Cacat itu lolos dari uji keseimbangan tag, sebab <tr><td></td></tr><td></td>
    // seimbang secara pasangan: setiap tag punya penutupnya. Yang salah adalah
    // KEDUDUKANNYA, dan itu tidak pernah diperiksa.
    $bermasalah = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $isi = BerkasBlade::bersihkan(file_get_contents($berkas));

        // <td> yang muncul tepat setelah </tr>, hanya dipisahkan spasi.
        if (preg_match('#</tr>\s*<td[\s>]#', $isi)) {
            $bermasalah[] = BerkasBlade::namaPendek($berkas);
        }
    }

    expect($bermasalah)->toBe([]);
});

it('menutup slot kepala setelah seluruh kolom judul', function () {
    // Header yang tersisip setelah </x-slot:kepala> tidak ikut masuk ke
    // <thead>, sehingga jumlah kolom judul dan kolom isi menjadi berbeda.
    $bermasalah = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $isi = BerkasBlade::bersihkan(file_get_contents($berkas));

        if (! str_contains($isi, '</x-slot:kepala>')) {
            continue;
        }

        // Bagian setelah penutup slot tidak boleh memuat <th> lagi.
        [, $sesudah] = explode('</x-slot:kepala>', $isi, 2);

        // Batasi sampai sebelum slot berikutnya, agar tabel lain pada halaman
        // yang sama tidak ikut terbaca.
        $sesudah = explode('<x-slot:', $sesudah, 2)[0];

        if (preg_match('/<th[\s>]/', $sesudah)) {
            $bermasalah[] = BerkasBlade::namaPendek($berkas);
        }
    }

    expect($bermasalah)->toBe([]);
});

it('menyamakan jumlah kolom judul dengan jumlah sel pada halaman daftar', function () {
    // Pemeriksaan paling langsung: kolom yang tidak sejajar berarti tabelnya
    // tampak bergeser, sekalipun seluruh tagnya berpasangan dengan benar.
    $halaman = [
        'sp.index', 'sp.inventaris', 'sp.fasilitas', 'poktan.index',
        'penanaman', 'master.satuan',
        'alsintan.index', 'saprotan.index', 'komoditas.index',
        'infrastruktur.index', 'pengguna.index',
        'transmigran.index', 'rumah.index', 'lahan.index',
        'panen.index', 'pengaduan.index',
    ];

    $bermasalah = [];

    foreach ($halaman as $namaRute) {
        $isi = $this->get(route($namaRute))->getContent();

        if (! preg_match('/<thead[\s\S]*?<\/thead>/', $isi, $kepala)) {
            continue;
        }

        $jumlahJudul = preg_match_all('/<th[\s>]/', $kepala[0]);

        // Baris data pertama di dalam tbody.
        if (! preg_match('/<tbody[\s\S]*?<tr[\s\S]*?<\/tr>/', $isi, $baris)) {
            continue;
        }

        $jumlahSel = preg_match_all('/<td[\s>]/', $baris[0]);

        if ($jumlahJudul !== $jumlahSel) {
            $bermasalah[] = $namaRute.": {$jumlahJudul} judul, {$jumlahSel} sel";
        }
    }

    expect($bermasalah)->toBe([]);
});

it('merapatkan kolom aksi ke tepi kanan tabel', function () {
    // Tombol aksi yang menggantung di tengah kolom membuat mata harus
    // mencari-cari letaknya pada setiap baris.
    $isi = $this->get(route('wilayah'))->getContent();

    expect($isi)->toMatch('/text-right[^>]*>\s*Aksi/');
});

/*
|--------------------------------------------------------------------------
| Tata letak dan peta koordinat
|--------------------------------------------------------------------------
*/

it('mencegah konten meluber di samping sidebar', function () {
    // Sidebar berposisi fixed, sehingga ia keluar dari alur dan tidak memakan
    // ruang di dalam pembungkus flex. Tanpa min-w-0, flex-1 menghitung lebar
    // penuh layar lalu margin 290px menambahkannya lagi, sehingga lebar total
    // menjadi 100% + 290px dan konten paling kanan terpotong.
    //
    // Gejalanya paling terlihat pada tab yang dibuka lewat "buka di tab baru",
    // dan pulih setelah disegarkan, sehingga mudah dikira masalah pemuatan
    // padahal cacatnya ada pada tata letak.
    $isi = file_get_contents(resource_path('views/layouts/app.blade.php'));

    expect($isi)->toContain('min-w-0 flex-1');
});

it('memuat pustaka peta hanya ketika diperlukan', function () {
    // Leaflet berukuran sekitar 150 KB. Hanya enam form yang memerlukan peta,
    // sedangkan seluruh halaman lain tidak; menyertakannya pada bundel utama
    // membebani setiap halaman tanpa alasan.
    $appJs = file_get_contents(resource_path('js/app.js'));

    // Bundel utama tidak boleh mengimpor Leaflet secara statis.
    expect($appJs)->not->toContain("from 'leaflet'")
        ->and($appJs)->not->toContain('import leaflet');

    // Pemuatannya lewat impor dinamis pada modul terpisah.
    $petaJs = file_get_contents(resource_path('js/peta.js'));

    expect($petaJs)->toContain("import('leaflet')");
});

it('menyediakan pemilihan titik lewat peta pada setiap isian koordinat', function () {
    // GPS ponsel di lokus kerap meleset puluhan meter, sedangkan pengisi paling
    // mengetahui letak sebenarnya (rules.md 10b poin 6d).
    $isi = file_get_contents(resource_path('views/components/sim/koordinat-input.blade.php'));

    expect($isi)->toContain('Pilih di peta')
        ->and($isi)->toContain('Ambil lokasi saat ini')
        // Isian manual wajib tetap ada sebagai jalan terakhir.
        ->and($isi)->toContain('name="{{ $namaLintang }}"')
        // Kegagalan memuat peta tidak boleh menghentikan pengisian.
        ->and($isi)->toContain('petaGagal');
});

it('menampilkan tautan peta pada halaman yang memuat koordinat', function () {
    // Koordinat berupa angka tidak berarti apa-apa bagi petugas tanpa cara
    // melihatnya di peta.
    $halaman = [
        ['sp.detail', 1],
        ['rumah.detail', 1],
        ['lahan.detail', 1],
        ['pengaduan.detail', 1],
        ['poktan.detail', 1],
    ];

    $tanpaTautan = [];

    foreach ($halaman as [$rute, $id]) {
        if (! str_contains($this->get(route($rute, $id))->getContent(), 'Lihat di peta')) {
            $tanpaTautan[] = $rute;
        }
    }

    expect($tanpaTautan)->toBe([]);
});

it('tidak menampilkan tautan peta ketika koordinat kosong', function () {
    // Tombol yang tidak menuju ke mana pun dilarang (R-26). Pengaduan kedua
    // pada data contoh sengaja dibiarkan tanpa koordinat untuk menguji ini.
    $tanpaTitik = collect(DummyData::pengaduan())
        ->first(fn ($p) => empty($p['lintang']));

    expect($tanpaTitik)->not->toBeNull();

    expect($this->get(route('pengaduan.detail', $tanpaTitik['id_pengaduan']))->getContent())
        ->not->toContain('Lihat di peta');
});

it('menyediakan koordinat opsional pada pengaduan warga', function () {
    // Warga melapor lewat ponsel berjaringan terbatas, sehingga koordinat
    // membantu tetapi tidak boleh menghalangi pengiriman
    // (rules.md 10b poin 6c).
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    expect($isi)->toContain('name="lintang"')
        ->and($isi)->toContain('Pilih di peta')
        ->and($isi)->toContain('boleh dikosongkan');

    preg_match('/<input[^>]*id="lintang"[^>]*>/', $isi, $cocok);

    expect($cocok[0] ?? '')->not->toContain('required');
});

it('melengkapi koordinat pada data contoh yang kamus datanya memuatnya', function () {
    // Tautan peta tidak pernah tampil bila datanya kosong, sehingga fiturnya
    // tampak tidak berfungsi padahal kodenya benar.
    $kosong = [];

    foreach (['rumah', 'poktan'] as $modul) {
        foreach (DummyData::{$modul}() as $baris) {
            if (empty($baris['lintang']) || empty($baris['bujur'])) {
                $kosong[] = $modul;

                break;
            }
        }
    }

    // Lahan memakai DUA PASANG koordinat sejak Putaran 15: pekarangan dan usaha
    // terpisah. Lahan usaha SELALU terisi (setiap keluarga pada data contoh
    // memegangnya); koordinat pekarangan wajib terisi HANYA bila keluarganya
    // sudah menerima pekarangan - null di sana berarti belum menerima, bukan
    // data yang hilang.
    foreach (DummyData::lahan() as $baris) {
        if (empty($baris['lintang_usaha']) || empty($baris['bujur_usaha'])) {
            $kosong[] = 'lahan usaha';

            break;
        }
    }

    foreach (DummyData::lahan() as $baris) {
        if ($baris['luas_pekarangan'] !== null && (empty($baris['lintang_pekarangan']) || empty($baris['bujur_pekarangan']))) {
            $kosong[] = 'lahan pekarangan';

            break;
        }
    }

    expect($kosong)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Ketahanan grafik pada tab latar belakang
|--------------------------------------------------------------------------
*/

it('membatasi lebar wadah grafik', function () {
    // Uji ini lahir dari cacat nyata yang bertahan setelah perbaikan pertama.
    // ApexCharts menghitung lebar kanvasnya sekali saat digambar, dari lebar
    // elemen wadahnya. Pada tab yang dibuka di latar belakang, peramban belum
    // melakukan layout sehingga lebar itu terbaca nol, lalu ApexCharts jatuh
    // ke lebar bawaannya yang jauh lebih besar.
    //
    // Tanpa pembatas, kanvas berlebih itu mendorong kartunya ikut melebar dan
    // merusak tata letak seluruh halaman. Pembatas ini penahan terakhir:
    // kartunya tetap utuh sekalipun grafiknya belum sempat menyesuaikan diri.
    $isi = file_get_contents(resource_path('views/components/sim/chart-card.blade.php'));

    expect($isi)->toContain('w-full max-w-full overflow-hidden');
});

it('menggambar ulang grafik ketika wadahnya benar-benar terlihat', function () {
    // Pembatas lebar hanya menahan tata letak; grafiknya sendiri tetap perlu
    // dihitung ulang agar isinya pas di dalam kartu.
    $isi = file_get_contents(resource_path('js/chart-config.js'));

    expect($isi)->toContain('IntersectionObserver')
        // Pengamat wajib dilepas setelah dipakai, agar tidak menumpuk pada
        // halaman yang memuat banyak grafik.
        ->and($isi)->toContain('pengamat.disconnect()');
});

it('membuat grafik menyesuaikan diri saat wadahnya berubah lebar', function () {
    // Pengaman berkelanjutan, bukan hanya saat pemuatan pertama: sidebar yang
    // dilipat dan jendela yang diubah ukurannya sama-sama mengubah lebar wadah.
    $isi = file_get_contents(resource_path('js/chart-config.js'));

    expect($isi)->toContain('redrawOnParentResize: true');
});

/*
|--------------------------------------------------------------------------
| Pengambilan lokasi di dalam modal peta
|--------------------------------------------------------------------------
*/

it('menyediakan tombol ambil lokasi di dalam modal peta pemilih', function () {
    // Tombol pengambilan lokasi sudah ada di luar modal, tetapi tidak dapat
    // dijangkau selagi peta terbuka: modal menutup seluruh layar. Petugas yang
    // sudah menggeser penanda lalu ingin kembali ke posisi sebenarnya terpaksa
    // menutup peta lebih dulu, dan itu memutus pekerjaannya di tengah jalan.
    $isi = file_get_contents(resource_path('views/components/sim/koordinat-input.blade.php'));

    // Kaki modal, tempat tombol Batal dan Gunakan Titik Ini berada.
    $kakiModal = substr($isi, strrpos($isi, 'border-t border-gray-200'));

    expect($kakiModal)->toContain('ambilLokasi()');
});

it('menampilkan galat lokasi di dalam modal peta yang sedang terbuka', function () {
    // Pesan galat yang berada di luar modal terhalang lapisan penutup layar.
    // Tanpa salinan di dalam, izin lokasi yang ditolak tidak menghasilkan
    // tanda apa pun dan tombolnya tampak rusak.
    $isi = file_get_contents(resource_path('views/components/sim/koordinat-input.blade.php'));

    $isiModal = substr($isi, strpos($isi, 'Modal peta pemilih titik'));

    expect($isiModal)->toContain('x-text="galat"');
});

it('tidak menaruh tombol ambil lokasi pada modal peta baca-saja', function () {
    // Komponen ini dipakai di halaman rincian, tempat koordinat hanya DILIHAT.
    // Titiknya milik lahan atau rumah yang sedang dibuka, bukan milik pengguna,
    // sehingga tombol yang memindahkannya akan mengubah data yang seharusnya
    // tidak dapat disunting dari sana.
    $isi = file_get_contents(resource_path('views/components/sim/tautan-peta.blade.php'));

    expect($isi)->not->toContain('ambilLokasi');
});

/*
|--------------------------------------------------------------------------
| Pengelompokan dashboard
|--------------------------------------------------------------------------
*/

it('mengelompokkan visualisasi dashboard ke dalam bagian bertajuk', function () {
    // Dashboard memuat 12 grafik, 8 kartu, dan 2 tabel besar. Tanpa tajuk
    // pemisah seluruhnya terbaca sebagai satu tumpukan panjang, dan pembaca
    // tidak dapat membangun gambaran utuh tentang satu topik pun.
    $isi = $this->get(route('beranda'))->getContent();

    foreach ([
        'Ringkasan Kawasan', 'Kependudukan', 'Pertanian dan Ekonomi',
        'Infrastruktur dan Layanan', 'Perbandingan Antar Satuan Permukiman',
    ] as $tajuk) {
        expect($isi)->toContain($tajuk);
    }
});

it('mengurutkan bagian dashboard menurut topik, bukan nomor indikator', function () {
    // Inti perbaikannya justru pada urutan. Sebelumnya grafik disusun menurut
    // nomor indikator PRD, sehingga pembaca dilempar dari penduduk ke
    // pertanian, kembali ke penduduk, lalu ke pengaduan. Topik pertanian
    // bahkan terpecah di tiga tempat yang berjauhan.
    $isi = $this->get(route('beranda'))->getContent();

    // Dicocokkan pada tajuk <h2> saja. Menu sidebar memuat "Rekap
    // Kependudukan", yang akan tertangkap lebih dulu bila seluruh halaman
    // yang dicari, dan urutannya jadi salah dibaca.
    preg_match_all('/<h2[^>]*>([^<]+)<\/h2>/', $isi, $cocok);
    $tajuk = array_map('trim', $cocok[1]);

    expect($tajuk)->toBe([
        'Ringkasan Kawasan',
        'Kependudukan',
        'Pertanian dan Ekonomi',
        'Infrastruktur dan Layanan',
        'Perbandingan Antar Satuan Permukiman',
    ]);
});

it('mengumpulkan grafik pertanian dalam satu bagian yang sama', function () {
    // Sebelumnya Sebaran Komoditas berada di posisi kedua, sedangkan Volume
    // Panen dan Harga Jual di posisi kedelapan dan kesembilan: terpisah enam
    // kartu, sehingga ketiganya tidak pernah terbaca sebagai satu pokok.
    $isi = $this->get(route('beranda'))->getContent();

    $awal = strpos($isi, 'Pertanian dan Ekonomi');
    $akhir = strpos($isi, 'Infrastruktur dan Layanan');
    $bagian = substr($isi, $awal, $akhir - $awal);

    foreach (['grafikPanen', 'grafikKomoditas', 'grafikHarga', 'grafikPendapatan'] as $id) {
        expect($bagian)->toContain('id="'.$id.'"');
    }
});

it('menjaga hierarki tajuk dashboard tidak melompat', function () {
    // Judul halaman memakai <h1> dan judul kartu grafik memakai <h3>. Tanpa
    // lapisan <h2> di antaranya, pembaca layar kehilangan penanda pindah
    // bagian dan urutan tajuknya melompat.
    $isi = $this->get(route('beranda'))->getContent();

    expect($isi)->toContain('<h2');
});

/*
|--------------------------------------------------------------------------
| Impor data massal
|--------------------------------------------------------------------------
*/

it('menyediakan tombol impor pada modul berdata banyak', function (string $url, string $namaModal) {
    // PRD 8.1 mewajibkan template luring: sinyal di lokus tidak selalu stabil,
    // sehingga petugas mengunduh template, mengisinya di lapangan, lalu
    // mengunggahnya saat sambungan tersedia. Tanpa jalur ini, pendataan
    // ratusan kepala keluarga harus dikerjakan satu per satu sambil daring.
    $isi = $this->get($url)->assertOk()->getContent();

    // Diperiksa keduanya: tombol pemicunya DAN komponen modalnya. Memeriksa
    // nama modal saja tidak cukup, sebab nama itu juga muncul pada komponen
    // modalnya sendiri, sehingga tombol yang hilang tetap lolos.
    expect($isi)->toContain("buka-modal', '".$namaModal."')")
        ->and($isi)->toContain('Impor Data')
        ->and($isi)->toContain('judul-'.$namaModal);
})->with([
    ['/transmigran', 'imporTransmigran'],
    ['/rumah', 'imporRumah'],
    ['/lahan', 'imporLahan'],
    ['/panen', 'imporPanen'],
    ['/penanaman', 'imporPenanaman'],
    ['/sp/infrastruktur', 'imporInfrastruktur'],
    ['/sp/inventaris', 'imporInventaris'],
    ['/wilayah', 'imporWilayah'],
    ['/master/satuan', 'imporSatuan'],
    ['/poktan', 'imporPoktan'],
    ['/alsintan', 'imporAlsintan'],
    ['/saprotan', 'imporSaprotan'],
    ['/sp/fasilitas', 'imporFasilitas'],
    ['/komoditas', 'imporKomoditas'],
    // Musim tanam sempat berada di daftar ini sejak 2026-08-19. Ikut hilang
    // 2026-08-22 bersama fiturnya, bukan karena aturannya berubah.
    ['/penanaman', 'imporPenanaman'],
]);

it('tidak menyediakan impor pada modul yang tidak boleh diisi massal', function (string $url) {
    // Pengecualian ini disengaja dan punya alasan masing-masing:
    //
    // - Pengaduan datang dari kanal publik satu per satu, dan nomornya wajib
    //   memuat bagian acak (rules.md 10b poin 4) sehingga tidak dapat
    //   disiapkan lebih dulu di dalam berkas Excel.
    // - Pengguna: kata sandi awal diserahkan langsung kepada orangnya
    //   (rules.md 14b poin 3). Impor massal berarti kata sandi berkeliaran
    //   di dalam berkas yang berpindah tangan.
    // - Role, Kawasan, dan SP berjumlah tetap menurut prd.md: satu kawasan,
    //   enam SP, dan empat role bawaan. Angkanya berasal dari dokumen acuan,
    //   bukan dari menghitung baris data contoh.
    //
    // Musim Tanam sempat dikeluarkan dari daftar ini pada 2026-08-19, lalu
    // fiturnya sendiri dicabut 2026-08-22.
    $isi = $this->get($url)->assertOk()->getContent();

    expect($isi)->not->toContain("buka-modal', 'impor");
})->with([
    '/pengaduan',
    '/pengguna',
    '/pengaturan/role',
    '/kawasan',
    '/sp',
]);

it('memandu impor lewat tiga langkah beserta kolom wajibnya', function () {
    // Alurnya dipecah agar tiap tahap punya satu pekerjaan saja. Langkah
    // ketiga yang paling menentukan: impor yang hanya berkata "gagal"
    // memaksa petugas menebak barisnya, padahal berkas berisi ratusan baris
    // tidak mungkin diperiksa manual.
    $isi = $this->get('/transmigran')->getContent();

    expect($isi)->toContain('Unduh template')
        ->and($isi)->toContain('Unggah berkas')
        ->and($isi)->toContain('Baris yang perlu diperbaiki')
        // Kolom wajib ditampilkan agar petugas tahu isian apa yang diperlukan
        // sebelum berangkat ke lapangan.
        ->and($isi)->toContain('nama_lengkap');
});

it('menyatakan terus terang bahwa impor entitas berantai belum tersambung backend', function () {
    // Tombolnya terlihat berfungsi penuh padahal penyimpanannya belum ada.
    // Tanpa peringatan ini petugas dapat mengira datanya sudah masuk, lalu
    // kehilangan hasil pendataan sehari penuh. Berlaku untuk enam entitas
    // berantai (Task 10.4, 2/2 -- belum dikerjakan): poktan salah satunya.
    $this->get('/poktan')->assertSee('Fitur belum aktif.');
});

it('tidak lagi menampilkan spanduk belum aktif pada delapan entitas mandiri (Task 10.4)', function () {
    // transmigran salah satu dari delapan entitas yang mesin impornya sudah
    // sungguhan (App\Support\ImporEngine::entitasAktif()).
    $this->get('/transmigran')->assertDontSee('Fitur belum aktif.');
});

it('menyediakan rute unduh template CSV untuk seluruh entitas (Task 10.6)', function () {
    // Satu rute melayani semua entitas, sebab yang membedakan hanya susunan
    // kolomnya -- dibaca dari App\Support\SkemaImpor.
    foreach (SkemaImpor::entitas() as $entitas) {
        $r = $this->get(route('template-impor', $entitas))->assertOk();

        expect($r->headers->get('content-type'))->toContain('text/csv')
            ->and($r->headers->get('content-disposition'))->toContain('template-impor-'.$entitas.'.csv');

        // Baris judul kolom nyata, bukan sekadar komentar.
        $isi = $r->streamedContent();
        $kolomPertama = SkemaImpor::kolom($entitas)[0]['kolom'];
        expect($isi)->toContain($kolomPertama)->toContain('# TEMPLATE IMPOR');
    }

    // Entitas tak dikenal -> 404.
    $this->get(route('template-impor', 'tidak-ada'))->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Akun dan role
|--------------------------------------------------------------------------
*/

it('menyediakan jalur mengaktifkan kembali akun yang dinonaktifkan', function () {
    // Cacat nyata: akun nonaktif sebelumnya terkunci selamanya. Tombol
    // nonaktifkan hanya dirender untuk akun aktif, dan tidak ada cabang bagi
    // akun yang sudah mati, padahal akun memang tidak pernah dihapus.
    $isi = $this->get(route('pengguna.index'))->assertOk()->getContent();

    $nonaktif = collect(DummyData::pengguna())->firstWhere('is_aktif', false);

    expect($nonaktif)->not->toBeNull()
        ->and($isi)->toContain('Aktifkan kembali akun '.$nonaktif['nama'])
        ->and($isi)->toContain('/pengguna/'.$nonaktif['id_user'].'/aktifkan');
});

it('tidak menawarkan pengaktifan pada akun yang sudah aktif', function () {
    // Kontrol yang tidak menuju ke mana pun dilarang (R-26): akun aktif tidak
    // boleh punya tombol aktifkan.
    $isi = $this->get(route('pengguna.index'))->getContent();

    foreach (collect(DummyData::pengguna())->where('is_aktif', true) as $akun) {
        expect($isi)->not->toContain('Aktifkan kembali akun '.$akun['nama']);
    }
});

it('tetap melindungi admin aktif terakhir dari penonaktifan', function () {
    // Penambahan tombol aktifkan tidak boleh melemahkan perlindungan yang
    // sudah ada (rules.md 14b poin 16).
    $isi = $this->get(route('pengguna.index'))->getContent();

    expect($isi)->not->toContain('Nonaktifkan akun SITI RAHMAWATI')
        ->and($isi)->toContain('tidak memiliki tombol nonaktifkan');
});

it('tidak menyediakan izin hapus pada modul pengguna', function () {
    // Akun tidak pernah dihapus, hanya dinonaktifkan. Menawarkan kotak centang
    // bagi kewenangan yang mustahil dijalankan menyesatkan admin penyusun role.
    $modul = collect(DummyData::daftarIzin())
        ->flatMap(fn ($kelompok) => $kelompok['modul'])
        ->keyBy('kunci');

    expect($modul['pengguna']['aksi'])->not->toContain('hapus')
        // Role JUSTRU boleh dihapus selama bukan bawaan dan tidak dipakai
        // akun mana pun (rules.md 5.0c poin 9), jadi izinnya tetap ada.
        ->and($modul['role']['aksi'])->toContain('hapus');
});

it('membuat akun baru tanpa isian username dan tanpa toggle aktif', function () {
    // Username dibuat petugas sendiri saat pertama kali masuk, sebab dialah
    // yang akan mengetiknya setiap hari. Aktif/nonaktif hanya lewat tombol
    // pada halaman daftar, agar riwayat audit tidak terpecah dua jalur.
    $isi = $this->get(route('pengguna.index'))->getContent();

    expect($isi)->not->toContain('name="username"')
        ->and($isi)->not->toContain('name="is_aktif"')
        ->and($isi)->toContain('Username dibuat petugas');
});

it('mewajibkan surel pada akun baru', function () {
    // Konsekuensi username dibuat petugas: surel menjadi satu-satunya
    // kredensial yang dimilikinya saat pertama kali masuk.
    expect($this->get(route('pengguna.index'))->getContent())
        ->toContain('name="email" required');
});

it('membuatkan kata sandi sementara alih-alih meminta admin mengetiknya', function () {
    // Kata sandi karangan manusia cenderung berpola dan dipakai ulang untuk
    // banyak akun sekaligus.
    $isi = $this->get(route('pengguna.index'))->getContent();

    expect($isi)->not->toContain('name="password_awal"')
        ->and($isi)->toContain('Kata sandi sementara dibuatkan sistem');
});

it('menyatakan terus terang bahwa pengiriman email belum aktif', function () {
    // Tampilannya sudah lengkap, tetapi pengirimannya menunggu backend. Tanpa
    // keterangan ini admin dapat mengira petugas sudah menerima emailnya, lalu
    // tidak menyerahkan kata sandi secara langsung.
    //
    // Spanduk kredensial hanya muncul SETELAH akun dibuat, sehingga sesinya
    // perlu diisi lebih dulu. Membuka halaman biasa tidak akan pernah
    // menampilkannya, dan uji yang tidak menyadarinya akan lulus tanpa
    // benar-benar memeriksa apa pun.
    $isi = $this->withSession(['kredensial_baru' => [
        'nama' => 'PETUGAS UJI',
        'email' => 'petugas.uji@malakakab.go.id',
        'password' => 'Tmg-7K4pQ2',
    ]])->get(route('pengguna.index'))->assertOk()->getContent();

    expect($isi)->toContain('belum aktif')
        // Sekaligus menjaga istilahnya, sebab teks ini termasuk yang dilihat
        // pengguna (ui-spec.md 10.1).
        ->and(mb_strtolower($isi))->not->toContain('surel');
});

it('menampilkan tombol hapus hanya pada role yang memang dapat dihapus', function () {
    // Role bawaan dan role yang masih dipakai akun tidak boleh dihapus
    // (rules.md 5.0c poin 8 dan 9). Merender tombol lalu menolaknya di server
    // berarti memasang kontrol mati.
    $isi = $this->get(route('pengaturan.role'))->assertOk()->getContent();

    $dapatDihapus = collect(DummyData::role())
        ->filter(fn ($r) => ! $r['is_bawaan'] && $r['jumlah_pengguna'] === 0);

    expect($dapatDihapus)->not->toBeEmpty();

    foreach ($dapatDihapus as $role) {
        expect($isi)->toContain('/pengaturan/role/'.$role['id_role']);
    }

    foreach (collect(DummyData::role())->where('is_bawaan', true) as $role) {
        expect($isi)->not->toContain("aksi: '/pengaturan/role/".$role['id_role']."'");
    }
});

it('menyediakan contoh role buatan admin, bukan hanya bawaan sistem', function () {
    // Tanpa satu pun role buatan sendiri, keadaan "dapat dihapus" tidak pernah
    // terlihat pada antarmuka dan bentuk tampilannya tidak dapat dinilai.
    $buatanSendiri = collect(DummyData::role())->where('is_bawaan', false);

    expect($buatanSendiri)->not->toBeEmpty();

    foreach ($buatanSendiri as $role) {
        expect(DummyData::izinRole($role['id_role']))->not->toBeEmpty();
    }
});

/*
|--------------------------------------------------------------------------
| Pengaduan: prioritas, dokumen, dan penanganan
|--------------------------------------------------------------------------
*/

it('menyerahkan penentuan prioritas sepenuhnya kepada petugas', function () {
    // Penurunan otomatis dari kategori dibatalkan pada 2026-08-14. Kategori
    // hanya menyatakan pokok masalah, sedangkan kegentingan bergantung pada
    // keadaan lapangan yang tidak terbaca dari kategori: dua laporan
    // berkategori sama dapat berbeda jauh kemendesakannya.
    expect(method_exists(PrioritasPengaduan::class, 'dariKategori'))->toBeFalse();

    // Form petugas tetap menyediakan pilihannya, sebab dialah yang menilai.
    expect($this->get(route('pengaduan.index'))->getContent())
        ->toContain('name="prioritas"');
});

it('menampilkan dokumen tindak lanjut pada riwayat penanganan', function () {
    // Modal penanganan sudah lama menyediakan isian unggahnya, tetapi hasilnya
    // tidak pernah ditampilkan kembali sehingga berkas yang sudah diunggah
    // petugas tidak dapat dibuka siapa pun.
    $riwayat = DummyData::penangananPengaduan(DummyData::pengaduan()[0]['nomor_pengaduan']);

    $berdokumen = collect($riwayat)->firstWhere('dokumen_tindak_lanjut', '!=', null);

    expect($berdokumen)->not->toBeNull();

    $this->get(route('pengaduan.detail', 1))
        ->assertOk()
        ->assertSee(basename($berdokumen['dokumen_tindak_lanjut']));
});

it('memberitahu warga adanya dokumen tanpa membuka berkasnya', function () {
    // Halaman lacak terbuka tanpa login dan hanya berbekal nomor pengaduan,
    // sehingga siapa pun yang mengetahui nomornya akan ikut memperoleh
    // berkasnya. Dokumen tindak lanjut kerap memuat nama petugas dan hasil
    // peninjauan.
    $isi = $this->get(route('lacak-pengaduan', ['nomor' => DummyData::pengaduan()[0]['nomor_pengaduan']]))
        ->assertOk()
        ->getContent();

    expect($isi)->toContain('melampirkan dokumen tindak lanjut')
        // Berkasnya sendiri tidak boleh dapat diunduh dari sini.
        ->and($isi)->not->toContain('BeritaAcaraPeninjauan');
});

it('menyediakan satu tombol penanganan saja pada halaman rincian pengaduan', function () {
    // Sebelumnya tombol yang sama dirender dua kali: di kepala halaman dan di
    // kolom kiri. Yang dipertahankan adalah yang berdampingan dengan stepper
    // alur, sebab di sanalah petugas melihat konteks tahapnya.
    $isi = $this->get(route('pengaduan.detail', 1))->getContent();

    expect(substr_count($isi, "buka-modal', 'formPenanganan'"))->toBe(1);
});

it('menyamakan isian penanganan pada daftar dengan yang di halaman rincian', function () {
    // Dua modal untuk satu tindakan yang sama tidak boleh meminta hal berbeda,
    // sebab jejak yang dihasilkannya akan timpang: sebagian bertanggal dan
    // berdokumen, sebagian tidak.
    $isi = $this->get(route('pengaduan.index'))->getContent();

    foreach (['tanggal_penanganan', 'catatan', 'dokumen_tindak_lanjut', 'status_sesudah'] as $isian) {
        expect($isi)->toContain('name="'.$isian.'"');
    }

    // Unggahan berkas mustahil terkirim tanpa enctype, dan kegagalannya
    // berlangsung diam-diam.
    expect($isi)->toContain('enctype="multipart/form-data"');
});

it('menyediakan penyalinan nomor pengaduan bagi warga', function () {
    // Nomor pengaduan adalah satu-satunya bekal warga untuk melacak laporannya,
    // sedangkan mengetik ulang sederet nomor di ponsel mudah keliru.
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    // Panel nomor hanya muncul setelah pengiriman, sehingga yang diperiksa di
    // sini adalah berkas sumbernya.
    $sumber = file_get_contents(resource_path('views/pages/publik/pengaduan.blade.php'));

    expect($sumber)->toContain('navigator.clipboard')
        ->and($sumber)->toContain('Ketuk nomor untuk menyalin')
        // Menyalin hanya menaruh nomor di papan klip yang mudah tertimpa,
        // sehingga ajakan mencatat tetap wajib ada. Dicocokkan pada intinya
        // saja, bukan kalimat penuh, agar wording bebas disunting.
        ->and($isi)->toContain('Catat atau foto');
});

it('mengarahkan tombol lacak ke nomor yang benar-benar ada', function () {
    // Task 8.3/8.7: nomor DIBUAT sistem saat pengiriman, lalu tombol "Lihat
    // Perkembangan Laporan" wajib menuju laporan yang benar-benar ada -- bukan
    // ke keadaan nomor tidak ditemukan (R-26).
    $this->post(route('pengaduan-warga.kirim'), [
        'nama_pelapor' => 'WARGA UJI',
        'kontak_pelapor' => '081200000000',
        'satuan_permukiman_id' => SatuanPermukiman::value('id_satuan_permukiman'),
        'kategori' => 'Infrastruktur',
        'tanggal_pengaduan' => '2026-08-20',
        'judul' => 'Jalan produksi berlubang',
        'deskripsi' => 'Uji alur kirim pengaduan warga.',
    ])->assertSessionHas('nomor_pengaduan');

    $nomor = session('nomor_pengaduan');

    expect($nomor)->toStartWith('PGD-')
        ->and(Pengaduan::where('nomor_pengaduan', $nomor)->exists())->toBeTrue();

    $this->get(route('lacak-pengaduan', ['nomor' => $nomor]))
        ->assertOk()
        ->assertDontSee('tidak ditemukan');
});

/*
|--------------------------------------------------------------------------
| Catatan log pada halaman rincian
|--------------------------------------------------------------------------
*/

it('menyaring riwayat memakai nama tabel DAN nomor barisnya', function () {
    // Keduanya wajib dipakai bersama. Menyaring nama tabel saja membuat setiap
    // baris menampilkan riwayat baris lain pada tabel yang sama, sehingga
    // pembaca mengira datanya pernah diubah padahal tidak.
    $riwayat = DummyData::riwayatData('transmigran', 1);

    expect($riwayat)->not->toBeEmpty();

    foreach ($riwayat as $jejak) {
        expect($jejak['nama_tabel'])->toBe('transmigran')
            ->and((int) $jejak['record_id'])->toBe(1);
    }

    // Baris lain pada tabel yang sama tidak boleh ikut terbawa.
    $nomorLain = collect(DummyData::riwayatData('transmigran', 4))->pluck('id_audit_log');
    $nomorIni = collect($riwayat)->pluck('id_audit_log');

    expect($nomorIni->intersect($nomorLain))->toBeEmpty();
});

it('mengurutkan riwayat data dari yang terbaru', function () {
    // Yang pertama dicari pembaca biasanya perubahan terakhir, bukan asal-usul
    // datanya.
    $waktu = collect(DummyData::riwayatData('transmigran', 1))->pluck('waktu')->all();

    $urut = $waktu;
    rsort($urut);

    expect($waktu)->toBe($urut);
});

it('menyediakan tab catatan log pada setiap halaman rincian utama', function (string $url, string $namaTabel, int $recordId) {
    // Pertanyaan "siapa yang memasukkan data ini dan siapa yang mengubahnya"
    // dijawab di tempat datanya dibaca, bukan dengan menelusuri halaman audit
    // log yang memuat seluruh sistem.
    $isi = $this->get($url)->assertOk()->getContent();

    expect($isi)->toContain('Catatan Log')
        ->and($isi)->toContain("tab === 'log'");

    // Isi tabnya wajib benar-benar memuat jejak milik baris ini.
    foreach (DummyData::riwayatData($namaTabel, $recordId) as $jejak) {
        expect($isi)->toContain($jejak['ringkasan']);
    }
})->with([
    ['/transmigran/1', 'transmigran', 1],
    ['/rumah/1', 'rumah', 1],
    ['/lahan/1', 'lahan', 1],
    ['/poktan/1', 'poktan', 1],
    ['/pengaduan/1', 'pengaduan', 1],
    ['/alsintan/1', 'alsintan', 1],
    ['/saprotan/1', 'saprotan', 1],
    ['/sp/infrastruktur/1', 'infrastruktur', 1],
    ['/komoditas/1', 'komoditas', 1],
    ['/panen/1', 'hasil_panen', 1],
]);

it('membedakan riwayat kosong dari kegagalan pencatatan', function () {
    // Riwayat kosong berarti datanya memang belum pernah disentuh sejak
    // dicatat, bukan berarti pencatatannya gagal. Transmigran 2 sengaja
    // dibiarkan tanpa jejak agar keadaan ini ikut teruji.
    expect(DummyData::riwayatData('transmigran', 2))->toBeEmpty();

    $this->get('/transmigran/2')
        ->assertOk()
        ->assertSee('Belum ada perubahan tercatat');
});

it('tidak menggantikan halaman audit log dengan tab catatan log', function () {
    // Keduanya menjawab pertanyaan berbeda: audit log menjawab apa saja yang
    // terjadi di seluruh sistem, tab ini menjawab apa yang terjadi pada satu
    // data saja. Tab karena itu menautkan ke halaman audit log, bukan
    // menggantikannya.
    $this->get(route('audit-log'))->assertOk();

    expect($this->get('/transmigran/1')->getContent())
        ->toContain(route('audit-log'));
});

it('menyaring riwayat akun pengguna menurut akun yang dibuka', function () {
    // Cacat lama: penyaringan hanya memakai nama_tabel, sehingga setiap akun
    // menampilkan riwayat akun orang lain. Komentar lamanya bahkan mengaku
    // mencocokkan nomor baris, padahal kodenya tidak melakukannya.
    //
    // Modal ini melayani seluruh baris secara bergantian, sehingga akun yang
    // dibuka baru diketahui saat modal dipanggil; penyaringannya karena itu
    // berada di sisi klien.
    $isi = $this->get(route('pengguna.index'))->getContent();

    expect($isi)->toContain('Number(baris.record_id) === Number(this.akun.id_user)');
});

it('memasang catatan log pada SETIAP halaman rincian yang ada', function () {
    // Penjaga kelengkapan. Daftar halamannya dibaca dari tabel rute, bukan
    // ditulis tetap, sehingga halaman rincian baru yang lupa dipasangi Catatan
    // Log langsung tertangkap tanpa perlu menyunting uji ini.
    //
    // Uji ini lahir dari kelalaian nyata: pemasangan pertama hanya menyentuh
    // lima halaman yang kebetulan sudah bertab, sedangkan lima halaman lain
    // terlewat justru karena belum bertab.
    // Pola menerima URI BERUAS DUA MAUPUN TIGA.
    //
    // Sebelumnya polanya `^[a-z-]+/\{id\}$` yang hanya cocok pada dua ruas,
    // sehingga `sp/inventaris/{id}` dan `sp/fasilitas/{id}` TIDAK PERNAH ikut
    // terperiksa. Keduanya kebetulan sudah memasang tab log, tetapi penjaganya
    // diam-diam melewatkannya - kegagalan yang justru dibuat untuk dicegah uji
    // ini sendiri. Ditemukan saat penyisiran 2026-08-20.
    $rute = collect(app('router')->getRoutes())
        ->filter(fn ($r) => in_array('GET', $r->methods(), true))
        ->filter(fn ($r) => preg_match('#^[a-z-]+(/[a-z-]+)?/\{id\}$#', $r->uri()) === 1)
        ->map(fn ($r) => str_replace('{id}', '1', $r->uri()))
        ->values();

    expect($rute)->not->toBeEmpty();

    // Halaman rincian SP wajib benar-benar ikut terjaring, jika tidak
    // perbaikan pola di atas tidak berarti apa pun.
    expect($rute)->toContain('sp/inventaris/1')->toContain('sp/fasilitas/1');

    $tanpaLog = [];

    foreach ($rute as $jalur) {
        $balasan = $this->get('/'.$jalur);

        if ($balasan->status() !== 200) {
            continue;
        }

        if (! str_contains($balasan->getContent(), "tab === 'log'")) {
            $tanpaLog[] = $jalur;
        }
    }

    expect($tanpaLog)->toBe([]);
});

it('menyeragamkan seluruh halaman rincian memakai tab', function () {
    // ui-spec.md 2.2 menetapkan komposisi halaman detail: ringkasan entitas
    // menetap di kiri, tab konten di kanan. Lima halaman sempat memakai kartu
    // bersusun tanpa tab, sehingga letak Catatan Log berbeda-beda antarmodul
    // dan petugas harus menebaknya tiap berpindah.
    foreach ([
        '/transmigran/1', '/rumah/1', '/lahan/1', '/poktan/1', '/pengaduan/1',
        '/alsintan/1', '/saprotan/1', '/sp/infrastruktur/1', '/komoditas/1', '/panen/1',
    ] as $jalur) {
        expect($this->get($jalur)->getContent())
            ->toContain('hashTabs(')
            ->and($this->get($jalur)->getContent())->toContain('role="tablist"');
    }
});

/*
|--------------------------------------------------------------------------
| Penandaan isian wajib
|--------------------------------------------------------------------------
*/

it('menandai wajib setiap isian yang kolomnya tidak boleh kosong', function () {
    // Penjaga berbasis kamus data, bukan daftar tetap. Kolom bertanda
    // "Null = TIDAK" berarti wajib di database, sehingga formnya pun harus
    // menuntutnya sejak di peramban.
    //
    // Uji ini lahir dari audit yang menemukan 43 isian tanpa penanda apa pun.
    // Cacatnya ternyata mengelompok: seluruh form master dan aset tidak pernah
    // dilewati penandaan, sedangkan form kependudukan sudah benar sejak awal.
    $wajibPerTabel = kolomWajibDariKamusData();

    // Berkas form dipetakan ke tabelnya. Yang tidak tercantum di sini belum
    // punya form tersendiri pada Tahap 2.
    $peta = [
        'master/form-satuan' => 'satuan',
        'sp/form-kawasan' => 'kawasan_transmigrasi',
        'sp/form' => 'satuan_permukiman',
        'sp/form-inventaris' => 'inventaris_sp',
        'sp/form-fasilitas' => 'fasilitas_sp',
        'transmigran/form' => 'transmigran',
        'rumah/form' => 'rumah',
        'lahan/form' => 'lahan',
        'poktan/form' => 'poktan',
        'poktan/form-anggota' => 'anggota_poktan',
        'alsintan/form' => 'alsintan',
        'saprotan/form' => 'saprotan',
        'komoditas/form' => 'komoditas',
        'penanaman/form' => 'penanaman',
        'panen/form' => 'hasil_panen',
        'infrastruktur/form' => 'infrastruktur',
        'pengaduan/form' => 'pengaduan',
    ];

    // Kolom yang sengaja tidak diminta lewat formulir.
    $dikecualikan = [
        // Diisi sistem, bukan pengguna.
        'user' => ['username', 'password', 'password_harus_diganti', 'is_aktif'],
        'role' => ['is_bawaan', 'is_terkunci'],
        // Kolom boolean berbawaan tidak memerlukan required; memasangnya pada
        // kotak centang justru berarti "harus dicentang".
        'komoditas' => ['is_unggulan'],
        // Diturunkan sistem dari kategori, bukan dipilih pengguna.
        'pengaduan' => ['bidang_penanganan', 'sumber_laporan', 'status', 'nomor_pengaduan'],
        'hasil_panen' => ['satuan_id'],
        'inventaris_sp' => ['status_penyerahan'],
        'fasilitas_sp' => ['status_penyerahan'],
        'infrastruktur' => ['kondisi'],
        'rumah' => ['kode_rumah'],
    ];

    $bolong = [];

    foreach ($peta as $berkas => $tabel) {
        $sumber = file_get_contents(resource_path("views/pages/{$berkas}.blade.php"));

        foreach ($wajibPerTabel[$tabel] ?? [] as $kolom) {
            if (in_array($kolom, $dikecualikan[$tabel] ?? [], true)) {
                continue;
            }

            // Isian boleh tidak ada sama sekali; yang dilarang adalah ada
            // tetapi tanpa penanda wajib.
            if (! str_contains($sumber, 'name="'.$kolom.'"')) {
                continue;
            }

            $pola = preg_quote($kolom, '/');

            // Isian tersembunyi selalu terisi nilai dari sistem, sehingga
            // `required` di sana tidak menambah apa pun.
            if (preg_match('/<input type="hidden" name="'.$pola.'"/', $sumber) === 1) {
                continue;
            }

            // Select tanpa <option value=""> mustahil dikirim kosong: pilihan
            // pertamanya sudah menjadi nilai bawaan.
            if (preg_match('/<select[^>]*name="'.$pola.'"(.*?)<\/select>/s', $sumber, $m) === 1
                && ! str_contains($m[1], 'value=""')) {
                continue;
            }

            // Menerima `required` maupun `:required` Alpine. Isian yang hanya
            // berlaku pada salah satu cabang form wajib memakai bentuk
            // bersyarat: menandainya wajib tanpa syarat akan memblokir
            // pengiriman ketika cabangnya sedang tersembunyi. Pola ini sudah
            // dipakai form poktan dan form lahan.
            //
            // Sengaja TIDAK memakai `[^>]*` sebagai pembatas. Nilai atribut
            // Blade kerap memuat tanda `>`, misalnya `{{ $asal->value }}`,
            // sehingga pembatas itu memutus pencocokan di tengah tag dan
            // melaporkan isian yang sebenarnya sudah bertanda wajib. Yang
            // dipakai adalah jendela 200 karakter sesudah `name`, cukup untuk
            // satu tag dan tidak sampai menjangkau tag berikutnya.
            $berRequired = preg_match(
                '/name="'.$pola.'".{0,200}?\s:?required/s',
                $sumber,
            ) === 1;

            if (! $berRequired) {
                $bolong[] = "{$berkas} -> {$kolom}";
            }
        }
    }

    expect($bolong)->toBe([]);
});

it('memasangkan bintang wajib dengan atribut required', function () {
    // Keduanya wajib berpasangan. Bintang tanpa `required` menjanjikan sesuatu
    // yang tidak ditegakkan, dan cacat persis itu ditemukan pada halaman masuk:
    // bintang sudah terpasang sejak awal, `required` tidak pernah ada.
    $timpang = [];

    $berkas = array_merge(
        glob(resource_path('views/pages/*/form*.blade.php')),
        glob(resource_path('views/pages/auth/*.blade.php')),
    );

    foreach ($berkas as $path) {
        $sumber = file_get_contents($path);
        $nama = str_replace(resource_path('views/pages/'), '', $path);

        // Hitung bintang pada label dan isian ber-required. Komponen bersama
        // memancarkan keduanya sekaligus, sehingga ikut dihitung lewat prop
        // `wajib`. Larik kotak centang ditegakkan Alpine saat submit, sebab
        // `required` di sana berarti "setiap kotak harus dicentang".
        $bintang = substr_count($sumber, 'text-error-500">*</span>');
        $required = preg_match_all('/\srequired(\s|\/|>)/', $sumber)
            + substr_count($sumber, ':wajib="true"')
            + substr_count($sumber, ':required=')
            + substr_count($sumber, '$event.preventDefault()');

        if ($bintang > $required) {
            $timpang[] = "{$nama}: {$bintang} bintang, {$required} required";
        }
    }

    expect($timpang)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Istilah pada teks antarmuka
|--------------------------------------------------------------------------
*/

it('memakai istilah email, bukan surel, pada teks yang dilihat pengguna', function (string $jalur) {
    // ui-spec.md 10.1. Diperiksa pada halaman TERENDER, bukan berkas sumber,
    // sebab komentar kode bebas memakai istilah mana pun; yang mengikat hanya
    // teks yang benar-benar sampai ke pengguna.
    //
    // Dataset mencampur halaman auth (guest) dan internal (auth); lepaskan
    // pengalihan `guest` agar halaman /login dsb. tetap dirender (Task 3.2b).
    $this->withoutMiddleware(RedirectIfAuthenticated::class);

    // Sesi diisi lebih dulu agar spanduk yang hanya muncul setelah pengiriman
    // formulir ikut terperiksa. Tanpa itu, sebagian teks tidak pernah dirender
    // dan uji lulus tanpa memeriksa apa pun.
    $isi = $this->withSession([
        'kredensial_baru' => ['nama' => 'UJI', 'email' => 'uji@malakakab.go.id', 'password' => 'Rahasia123'],
        'nomor_pengaduan' => DummyData::pengaduan()[2]['nomor_pengaduan'],
        'email_pelapor' => 'warga@contoh.id',
    ])->get($jalur)->assertOk()->getContent();

    expect(mb_strtolower($isi))->not->toContain('surel');
})->with([
    '/login',
    '/lupa-kata-sandi',
    '/verifikasi-kode',
    '/pengguna',
    '/pengaturan/role',
    '/pengaduan-warga',
    '/lacak-pengaduan',
    '/profil',
    '/profil/kata-sandi',
    /*
     * Halaman RINCIAN menyusul 2026-08-25. Daftar semula hanya memuat halaman
     * auth dan publik, sehingga label "Surel" yang sempat ditulis pada rincian
     * pengaduan lolos tanpa memerahkan satu uji pun. Larangannya sudah benar;
     * yang kurang adalah tempat ia diperiksa.
     */
    '/pengaduan/1',
    '/poktan/1',
    /*
     * Kop surat dokumen laporan menyebut kontak instansi (Putaran 5). Label
     * "Surel" yang sempat ditulis di sana lolos tanpa memerahkan uji sampai
     * pemilik proyek memergokinya -- rute dokumen tidak ada di daftar ini.
     */
    '/laporan/indikator-kawasan/dokumen',
    '/laporan/transmigran/dokumen',
]);

it('menuliskan baris total tanpa penanda cakupan', function (string $jalur) {
    // Judul halaman dan filter yang sedang aktif sudah menyatakan cakupannya,
    // sehingga "Total kawasan" mengulang informasi yang ada tepat di atasnya.
    $isi = $this->get($jalur)->assertOk()->getContent();

    expect($isi)->toContain('motif-baris-total')
        ->and($isi)->not->toContain('>Total kawasan<');
})->with([
    '/panen/rekap',
    '/pengaduan/rekap',
    '/kependudukan/rekap',
    '/sp',
    '/kawasan',
    '/sp/infrastruktur',
]);

it('memakai istilah fitur dan kewenangan pada teks yang dilihat pengguna', function (string $jalur) {
    // ui-spec.md 10.1. "Modul" dan "izin" adalah istilah pengembang; petugas
    // dinas lebih mengenali "fitur" dan "kewenangan".
    //
    // Diperiksa pada TEKS terender, bukan berkas sumber. Tag dibuang lebih
    // dulu sebab atribut seperti type="module" dan name="izin[]" memang tetap
    // memakai istilah teknis, dan tidak pernah dibaca pengguna.
    $isi = $this->get($jalur)->assertOk()->getContent();

    $teks = preg_replace('/<script.*?<\/script>/s', ' ', $isi);
    $teks = preg_replace('/<[^>]+>/', ' ', $teks);

    // "Izin lokasi" adalah istilah peramban yang muncul pada pesan Geolocation
    // API, bukan istilah sistem ini. Menggantinya justru membuat pesannya tidak
    // cocok dengan yang dilihat pengguna pada dialog peramban.
    $teks = str_ireplace('izin lokasi', '', $teks);

    expect($teks)->not->toMatch('/\bmodul\b/i')
        // "diizinkan" dan "mengizinkan" adalah kata kerja yang tetap sah;
        // yang dilarang hanya kata benda "izin" berdiri sendiri.
        ->and($teks)->not->toMatch('/\bizin\b/i');
})->with([
    '/pengaturan/role',
    '/pengguna',
    '/sp/infrastruktur',
    '/sp/infrastruktur/1',
    '/audit-log',
    '/galeri-komponen',
]);

it('menyaring ketikan bukan angka pada isian angka', function () {
    // `type="number"` menolak huruf, tetapi masih menerima notasi ilmiah `e`,
    // tanda minus, dan tempelan teks. Ketiganya baru tertangkap saat
    // penyimpanan, padahal petugas sudah telanjur mengisi seluruh formulir.
    $sumber = file_get_contents(resource_path('js/input-angka.js'));

    expect($sumber)->toContain("isian.type !== 'number'")
        ->and($sumber)->toContain('keydown')
        ->and($sumber)->toContain('paste')
        // Isian berlangkah pecahan tetap menerima koma desimal, sedangkan
        // tahun dan jumlah unit tidak.
        ->and($sumber)->toContain('menerimaPecahan');

    // Terpasang sekali pada app.js, bukan disalin ke tiap isian. Dicocokkan
    // pada baris yang benar-benar dijalankan, sebab pemanggilan yang sekadar
    // dikomentari tetap lolos bila hanya kata kuncinya yang dicari.
    $app = file_get_contents(resource_path('js/app.js'));

    expect($app)->toMatch('/^pasangPenjagaAngka\(\);$/m')
        ->and($app)->toContain("from './input-angka'");
});
/*
|--------------------------------------------------------------------------
| Laporan adalah dokumen bernama, bukan ekspor tampilan tabel
|--------------------------------------------------------------------------
|
| Dibalik 2026-08-28 (rules.md 12 poin 6, membalik keputusan 2026-08-17).
| Keputusan lama menempelkan tombol ekspor pada tiap tabel; ternyata itu
| belasan kontrol mati (R-26) tanpa laporan di baliknya. Kini tiap laporan
| satu halaman bernama di menu "Laporan", dan cakupannya ditulis sebagai
| teks di kepala dokumen, bukan sebagai kontrol filter (poin 8).
*/

it('tidak lagi menempelkan tombol ekspor pada halaman daftar mana pun', function (string $jalur) {
    // Kebalikan langsung dari penjaga lama. Atribut `data-ekspor` adalah
    // penanda satu-satunya komponen tombol-ekspor; nol kemunculan berarti
    // kontrol matinya benar-benar tercabut, bukan sekadar disembunyikan.
    $this->get($jalur)->assertOk()->assertDontSee('data-ekspor', false);
})->with([
    '/transmigran',
    '/rumah',
    '/lahan',
    '/panen',
    '/pengaduan',
    '/poktan',
    '/alsintan',
    '/saprotan',
    '/komoditas',
    '/sp/infrastruktur',
    '/sp',
    '/pengguna',
    '/audit-log',
    '/panen/rekap',
    '/pengaduan/rekap',
    '/kependudukan/rekap',
    '/',
]);

it('mencabut komponen tombol ekspor beserta rujukannya di kerangka bersama', function () {
    // Komponennya dihapus. Rujukan yang tertinggal di berkas Blade mana pun
    // akan memerahkan penjaga komponen yatim dan menyesatkan pembaca
    // berikutnya, jadi diperiksa langsung ke seluruh berkas.
    expect(file_exists(resource_path('views/components/sim/tombol-ekspor.blade.php')))
        ->toBeFalse('Berkas komponen tombol-ekspor masih ada');

    foreach (BerkasBlade::semua() as $path) {
        expect(str_contains(file_get_contents($path), 'x-sim.tombol-ekspor'))
            ->toBeFalse("Masih ada tag <x-sim.tombol-ekspor> di {$path}");
    }
});

it('menyediakan menu Laporan berisi halaman laporan bernama', function () {
    // Butir "Semua Laporan" + halaman `/laporan` DICABUT 2026-08-29:
    // submenu sudah memuat ketujuh laporan langsung, jadi halaman indeks
    // hanya mengulang isinya.
    $this->get('/laporan')->assertNotFound();

    $kelompok = collect(MenuHelper::definisiMenu())->firstWhere('title', 'Laporan');

    expect($kelompok)->not->toBeNull('Kelompok menu "Laporan" tidak ada');

    $tautan = collect($kelompok['items'])
        ->flatMap(fn ($i) => $i['subItems'] ?? [$i])
        ->pluck('path')
        ->all();

    // Tidak ada lagi pintu masuk ke /laporan dari menu.
    expect($tautan)->not->toContain('/laporan');

    // Tiap butir menu Laporan wajib menunjuk rute terdaftar yang membalas
    // 200. Menu ke halaman tidak ada melanggar R-24 dan baru ketahuan saat
    // petugas mengkliknya.
    foreach ($tautan as $path) {
        $this->get($path)->assertOk("Menu Laporan menunjuk {$path} yang tidak membalas 200");
    }
});

it('menyusun submenu Laporan dalam urutan yang disepakati, satu sumber nama', function () {
    // Urutan dan nama laporan hanya ditulis di LaporanData::meta(). Dikunci
    // di sini seperti submenu Administrasi Sistem dikunci di
    // PengaturanPenilaianTest -- tanpa penjaga urutan, submenu bisa bergeser
    // tanpa memerahkan apa pun.
    $submenu = collect(MenuHelper::definisiMenu())
        ->firstWhere('title', 'Laporan')['items'][0]['subItems'];

    expect(collect($submenu)->pluck('name')->all())->toBe([
        'Rekap Indikator Kawasan',
        'Laporan Monografi SP',
        'Laporan Transmigran',
        'Laporan Poktan',
        'Laporan Alsintan',
        'Laporan Saprotan',
        'Laporan Hasil Panen',
    ]);

    // Nama di menu, di <title>, dan di <h1> ketiganya dari meta().
    foreach (LaporanData::meta() as $slug => $m) {
        $isi = $this->get('/laporan/'.$slug)->assertOk()->getContent();
        expect($isi)
            ->toContain('<title>'.$m['judul'].' |')
            ->toContain('>'.$m['judul'].'<');   // h1 di kop + h1 di kertas
    }

    // Tak satu pun berkas Blade menyebut rute atau butir yang dicabut.
    foreach (BerkasBlade::semua() as $path) {
        $isi = file_get_contents($path);
        expect(str_contains($isi, 'laporan.index'))->toBeFalse(BerkasBlade::namaPendek($path).' menyebut laporan.index');
        expect(str_contains($isi, 'Semua Laporan'))->toBeFalse(BerkasBlade::namaPendek($path).' menyebut "Semua Laporan"');
    }
});

it('memberi tiap halaman laporan judul, pernyataan cakupan, dan unduh yang jujur', function (string $slug, string $judul) {
    $isi = $this->get('/laporan/'.$slug)->assertOk()->getContent();

    // Judul dokumen dipastikan lewat <title>, sebab nama tiap laporan juga
    // muncul di sidebar sehingga str_contains biasa selalu benar.
    expect($isi)
        ->toContain('<title>'.$judul.' |')
        ->toContain('Cakupan laporan')      // cakupan sebagai teks (poin 8)
        ->toContain('Dasar periode')
        ->toContain('segera hadir')         // unduh jujur, bukan tombol berfungsi (R-26)
        ->toContain('Data contoh.');        // R-17 / R-38

    // Tidak ada tombol ekspor di halaman laporan.
    expect($isi)->not->toContain('data-ekspor');
})->with([
    ['hasil-panen', 'Laporan Hasil Panen'],
    ['monografi-sp', 'Laporan Monografi SP'],
    ['alsintan', 'Laporan Alsintan'],
    ['saprotan', 'Laporan Saprotan'],
    ['indikator-kawasan', 'Rekap Indikator Kawasan'],
    ['poktan', 'Laporan Poktan'],
    ['transmigran', 'Laporan Transmigran'],
]);

it('memisahkan Laporan Alsintan dari Laporan Saprotan', function () {
    // Pemilik proyek menegaskan dua laporan terpisah, bukan satu gabungan,
    // mengikuti dua berkas rujukan terpisah di refs/.
    $tautan = collect(MenuHelper::definisiMenu())
        ->firstWhere('title', 'Laporan')['items'][0]['subItems'];
    $paths = collect($tautan)->pluck('path')->all();

    expect($paths)->toContain('/laporan/alsintan')->toContain('/laporan/saprotan');

    // Judul dokumen keduanya berdiri sendiri.
    expect($this->get('/laporan/alsintan')->getContent())->toContain('<title>Laporan Alsintan |');
    expect($this->get('/laporan/saprotan')->getContent())->toContain('<title>Laporan Saprotan |');
});

/*
|--------------------------------------------------------------------------
| Isi tabel tiap halaman laporan (Tahap 2c, 2026-08-28)
|--------------------------------------------------------------------------
|
| Lima laporan mengikuti berkas rujukan di refs/, dua dirancang dari kolom
| data yang ada. Datanya disusun App\Support\LaporanData (bukan DummyData di
| view). Setiap tabel wajib punya <caption> - dijaga terpisah oleh penjaga
| "memberi nama pada setiap tabel", tetapi diulang di sini agar kegagalan
| menunjuk laporan yang salah.
*/

it('mengisi tiap halaman laporan dengan tabel berdata, bukan penampung kosong', function (string $slug) {
    $isi = $this->get('/laporan/'.$slug)->assertOk()->getContent();

    // Penampung "format menyusul" TIDAK boleh muncul lagi.
    expect($isi)->not->toContain('Format kolom laporan ini sedang disusun');

    // Ada tabel, dan tiap tabel punya caption sebagai anak pertama.
    expect(substr_count($isi, '<table'))->toBeGreaterThan(0);
    expect(substr_count($isi, '<caption'))->toBe(substr_count($isi, '<table'));

    // Cakupan tetap dinyatakan sebagai teks (rules.md 12 poin 8).
    expect($isi)->toContain('Cakupan laporan')->toContain('segera hadir');
})->with(['hasil-panen', 'monografi-sp', 'alsintan', 'saprotan', 'indikator-kawasan', 'poktan', 'transmigran']);

it('memuat kolom kunci tiap laporan sesuai berkas rujukannya', function () {
    // Laporan Hasil Panen: kolom Polri MT. I 2025.
    expect($this->get('/laporan/hasil-panen')->getContent())
        ->toContain('Luas Lahan')
        ->toContain('Volume Benih')
        ->toContain('Realisasi Tanam')
        ->toContain('Belum Ditanam')
        ->toContain('Realisasi Panen')
        ->toContain('Puso')
        ->toContain('Produktivitas')
        ->toContain('Produksi (ton)')
        ->toContain('Keterangan');

    // Laporan Alsintan: kolom berkas gambar.
    expect($this->get('/laporan/alsintan')->getContent())
        ->toContain('Jenis Alat')
        ->toContain('Sumber Dana')
        ->toContain('Tahun Pengadaan')
        ->toContain('Poktan Penerima')
        ->toContain('Jumlah (Unit)');

    // Laporan Saprotan: dua bagian, kolom berkas gambar.
    $saprotan = $this->get('/laporan/saprotan')->getContent();
    expect($saprotan)
        ->toContain('Bantuan benih')
        ->toContain('Varietas Benih')
        ->toContain('Volume Benih')
        ->toContain('Jadwal Tanam')
        ->toContain('pupuk, pestisida, dan mulsa');

    // Laporan Daftar Poktan: kolom xlsx.
    expect($this->get('/laporan/poktan')->getContent())
        ->toContain('Nama Petani')
        ->toContain('Sawah (Basah)')
        ->toContain('Titik Koordinat');

    // Laporan Daftar Transmigran: tiga bagian dari data yang ada.
    expect($this->get('/laporan/transmigran')->getContent())
        ->toContain('Kepala Keluarga Transmigran')
        ->toContain('Data Rumah')
        ->toContain('Data Lahan');
});

it('menjumlahkan hasil panen per SP lalu ke total kawasan tanpa selisih', function () {
    $data = LaporanData::hasilPanen();

    expect($data['kelompok'])->not->toBeEmpty();

    // Total kawasan = jumlah seluruh subtotal SP, untuk tiap kolom angka.
    foreach (['luas_lahan', 'realisasi_tanam', 'belum_ditanam', 'realisasi_panen', 'puso', 'produksi_ton', 'volume_benih'] as $kolom) {
        $jumlahSubtotal = array_sum(array_column(
            array_column($data['kelompok'], 'subtotal'), $kolom
        ));
        expect(round($jumlahSubtotal, 2))->toBe(round($data['total'][$kolom], 2), "kolom {$kolom} tidak konsisten");
    }

    // Belum Ditanam = luas lahan - realisasi tanam, tak pernah negatif.
    // Belum Dipanen = realisasi tanam - realisasi panen - puso, tak pernah negatif.
    foreach ($data['kelompok'] as $grup) {
        foreach ($grup['baris'] as $b) {
            $tanamSisa = max(0.0, round($b['luas_lahan'] - $b['realisasi_tanam'], 2));
            expect($b['belum_ditanam'])->toBe($tanamSisa);
            expect($b['belum_ditanam'])->toBeGreaterThanOrEqual(0);

            $panenSisa = max(0.0, round($b['realisasi_tanam'] - $b['realisasi_panen'] - $b['puso'], 2));
            expect($b['belum_dipanen'])->toBe($panenSisa);
            expect($b['belum_dipanen'])->toBeGreaterThanOrEqual(0);
        }
    }
});

it('menelusuri varietas dan tahun pengadaan laporan panen sampai ke saprotan benih', function () {
    // Inti rules.md 9 poin 16: dasar laporan panen adalah tahun pengadaan
    // BANTUAN, dibaca lewat penanaman.saprotan_distribusi_id ->
    // saprotan_distribusi -> pengadaan.tahun_pengadaan (Putaran 7).
    $distribusi = collect(DummyData::saprotanDistribusi())->keyBy('id_saprotan_distribusi');
    $penanaman = collect(DummyData::penanaman())->keyBy('id_penanaman');

    $adaVarietas = false;

    // Pembuktian langsung: panen 1 -> penanaman 1 -> distribusi 1 -> pengadaan 1.
    $benih = $distribusi[$penanaman[1]['saprotan_distribusi_id']];
    expect($benih['tahun_pengadaan'])->not->toBeNull();
    expect($benih['varietas'])->not->toBeNull();

    // Setidaknya satu baris laporan membawa varietas dari benihnya.
    foreach (LaporanData::hasilPanen()['kelompok'] as $grup) {
        foreach ($grup['baris'] as $b) {
            if ($b['varietas'] !== '-' && $b['tahun_pengadaan'] !== null) {
                $adaVarietas = true;
            }
        }
    }
    expect($adaVarietas)->toBeTrue();
});

it('memisahkan bantuan benih dari pupuk pada Laporan Saprotan', function () {
    // rules.md 9 poin 16, notes 1m.4: pupuk tidak tertaut ke penanaman,
    // jadi hanya penyalurannya yang dilaporkan, di bagian terpisah.
    $data = LaporanData::saprotan();

    expect($data['benih'])->not->toBeEmpty();
    expect($data['nonBenih'])->not->toBeEmpty();

    // Tiap baris benih membawa varietas (wajib bila jenis Benih).
    foreach ($data['benih'] as $b) {
        expect($b['varietas'])->not->toBe('-');
        expect($b['volume_benih'])->toBeGreaterThan(0);
    }

    // Bagian non-benih tidak boleh memuat jenis Benih.
    foreach ($data['nonBenih'] as $n) {
        expect($n['jenis'])->not->toBe('Benih');
    }
});

it('menjumlahkan luas lahan anggota tiap poktan pada Laporan Poktan', function () {
    $data = LaporanData::poktan();

    expect($data['poktan'])->not->toBeEmpty();

    foreach ($data['poktan'] as $p) {
        $basah = round(array_sum(array_column($p['anggota'], 'luas_basah')), 2);
        $kering = round(array_sum(array_column($p['anggota'], 'luas_kering')), 2);
        expect($p['jumlah_basah'])->toBe($basah);
        expect($p['jumlah_kering'])->toBe($kering);
    }
});

it('menyusun Laporan Transmigran dari tiga modul tanpa kehilangan baris', function () {
    $data = LaporanData::transmigran();

    expect(count($data['transmigran']))->toBe(count(DummyData::transmigran()));
    expect(count($data['rumah']))->toBe(count(DummyData::rumah()));
    expect(count($data['lahan']))->toBe(count(DummyData::lahan()));
});

/*
|--------------------------------------------------------------------------
| Dokumen laporan: orientasi, garis tabel, rute polos (Putaran 3 D2b)
|--------------------------------------------------------------------------
|
| Peninjauan pemilik proyek atas D2: laporan berkolom banyak dipaksa ke
| kertas potret sehingga selalu perlu digulir, dan tabelnya tanpa garis
| pemisah kolom sama sekali.
|
| Rute /laporan/{slug}/dokumen yang dibuat D2 ternyata TANPA PENJAGA sama
| sekali: dua penyapu rute global melewatkannya karena URI-nya memuat
| `{slug}`, sehingga ia bisa membalas 500 tanpa memerahkan apa pun.
*/

/**
 * Menghitung jumlah kolom terlebar dari HTML terender.
 *
 * Dihitung dari baris PERTAMA di dalam <thead> dengan menjumlahkan colspan,
 * bukan mencacah <th>. Keduanya berbeda pada dua kasus yang justru ada di
 * laporan ini: kepala dua tingkat Laporan Poktan (5 kolom tunggal + 2 kolom
 * ber-colspan 2 = 9, bukan 7) dan kolom dinamis Laporan Hasil Panen yang
 * baru ada setelah dirender.
 */
function kolomTerlebarDariHtml(string $html): int
{
    preg_match_all('#<table\b.*?</table>#is', $html, $tabel);

    $terlebar = 0;

    foreach ($tabel[0] as $satu) {
        if (! preg_match('#<thead\b.*?</thead>#is', $satu, $thead)) {
            continue; // Tabel dua kolom tanpa thead, mis. blok Indikator Kawasan.
        }

        if (! preg_match('#<tr\b.*?</tr>#is', $thead[0], $baris)) {
            continue;
        }

        preg_match_all('#<t[hd]\b([^>]*)>#i', $baris[0], $sel);

        $kolom = 0;
        foreach ($sel[1] as $atribut) {
            $kolom += preg_match('#colspan="(\d+)"#i', $atribut, $c) ? (int) $c[1] : 1;
        }

        $terlebar = max($terlebar, $kolom);
    }

    return $terlebar;
}

it('menyajikan tiap laporan pada rute dokumen polos yang isinya sama', function (string $slug) {
    // Penjaga pertama untuk /laporan/{slug}/dokumen. Sebelumnya nol uji:
    // penyapu `merender setiap rute GET` melewati URI ber-`{`, dan penyapu
    // alamat aksi mengganti {slug} dengan `1` lalu 404 dan continue.
    $dokumen = $this->get('/laporan/'.$slug.'/dokumen')->assertOk()->getContent();
    $berbingkai = $this->get('/laporan/'.$slug)->assertOk()->getContent();

    // Rute dokumen = DOKUMEN RESMI berkop (Putaran 5), bukan lagi blok
    // "Cakupan laporan". Dua lambang, blok judul di tengah, baris "TAHUN ...",
    // lalu kalimat cakupan (wajib tetap tercetak -- rules.md 12 poin 8).
    expect($dokumen)
        ->toContain('KEMENTERIAN TRANSMIGRASI')
        ->toContain('Logo Kementerian Transmigrasi')     // alt gambar kiri
        ->toContain('Lambang Kabupaten Malaka')          // alt gambar kanan
        ->toContain('x-text="tahunDokumen"')
        ->toContain('x-text="kalimatCakupan"')           // cakupan tetap ada
        ->toContain('Data contoh.')
        ->not->toContain("\xE2\x80\x94")                 // R-02, tanpa em dash
        ->not->toContain('Cakupan laporan');            // blok lama dicabut di dokumen

    // Tanpa kromo aplikasi, tanpa bilah filter, tanpa tombol Generate.
    expect($dokumen)
        ->not->toContain('id="sidebar"')
        ->not->toContain('aria-label="Penyaring laporan"')
        ->not->toContain('Generate Laporan')
        ->not->toContain('Buka di tab baru');

    // Kop WAJIB non-<table> (penjaga kolomTerlebarDariHtml).
    $awalKop = strpos($dokumen, 'KEMENTERIAN TRANSMIGRASI');
    expect(substr($dokumen, max(0, $awalKop - 600), 1400))->not->toContain('<table');

    // Isi tabelnya sama persis dengan halaman berbingkai. Keduanya
    // meng-include partial yang sama, jadi selisih berarti ada yang lepas.
    foreach (['<table', '<caption', 'tabel-dokumen'] as $penanda) {
        expect(substr_count($dokumen, $penanda))
            ->toBe(substr_count($berbingkai, $penanda), "jumlah {$penanda} berbeda pada {$slug}");
    }
})->with(['hasil-panen', 'monografi-sp', 'alsintan', 'saprotan', 'indikator-kawasan', 'poktan', 'transmigran']);

it('menurunkan orientasi kertas dari jumlah kolom yang sebenarnya', function (string $slug) {
    // Jumlah kolom pada meta() adalah angka yang ditulis tangan; di sini ia
    // dihitung ulang dari HTML terender. Menambah kolom tanpa memperbarui
    // meta() memerahkan uji ini, bukan diam-diam membuat orientasinya salah.
    $html = $this->get('/laporan/'.$slug.'/dokumen')->assertOk()->getContent();

    $terhitung = kolomTerlebarDariHtml($html);
    $tercatat = LaporanData::meta($slug)['kolom'] ?? 0;

    expect($terhitung)->toBe($tercatat, "jumlah kolom {$slug} tercatat {$tercatat}, terhitung {$terhitung}");

    // Orientasi diturunkan dari angka itu, bukan dipilih tangan.
    $harusnya = $terhitung >= LaporanData::KOLOM_LANDSCAPE ? 'landscape' : 'portrait';
    expect(LaporanData::orientasi($slug))->toBe($harusnya);

    // Kelas orientasi benar-benar sampai ke kertasnya, beserta aturan cetak.
    expect($html)
        ->toContain('dokumen-'.$harusnya)
        ->toContain('size: A4 '.$harusnya);
})->with(['hasil-panen', 'monografi-sp', 'alsintan', 'saprotan', 'indikator-kawasan', 'poktan', 'transmigran']);

it('memberi garis pemisah kolom pada setiap tabel laporan', function () {
    // Sebelum D2b tabel laporan hanya punya garis baris (`divide-y`), sehingga
    // enam belas kolom Laporan Hasil Panen berdempetan tanpa pemisah.
    //
    // Kelasnya WAJIB polos tanpa varian arbitrer ber-`>` semacam
    // `[&>td]:border-r`: penjaga "memberi nama pada setiap tabel" memakai
    // regex /<table\b[^>]*>/ yang berhenti pada `>` pertama, sehingga kelas
    // ber-`>` memecah tag dan membuat <caption> tak lagi terbaca sebagai anak
    // pertama.
    $galat = [];

    foreach (BerkasBlade::semua() as $path) {
        $nama = BerkasBlade::namaPendek($path);

        if (! str_starts_with($nama, 'pages/laporan/isi/')) {
            continue;
        }

        $isi = file_get_contents($path);

        preg_match_all('/<table\b[^>]*>/', $isi, $cocok);

        foreach ($cocok[0] as $tag) {
            if (! str_contains($tag, 'tabel-dokumen')) {
                $galat[] = $nama.': '.trim($tag);
            }
        }
    }

    expect($galat)->toBe([]);

    // Aturannya benar-benar ada di CSS, bukan hanya kelas yang menggantung.
    $css = file_get_contents(resource_path('css/app.css'));
    expect($css)
        ->toContain('.tabel-dokumen th,')
        ->toContain('.dokumen-landscape .tabel-dokumen th,');
});

it('menegaskan baris total laporan dengan motif resmi, bukan garis abu-abu', function () {
    // ui-spec.md 2.3: baris total memakai garis atas navy-500 setebal 2px,
    // "bukan garis abu-abu biasa". Kelas .motif-baris-total sudah dipakai 15
    // halaman lain, tetapi berkas laporan sempat menulis border-t-2
    // border-gray-300 (persis yang dilarang) dan Laporan Poktan bahkan tidak
    // punya garis atas sama sekali.
    $tanpaMotif = [];

    foreach (BerkasBlade::semua() as $path) {
        $nama = BerkasBlade::namaPendek($path);

        if (! str_starts_with($nama, 'pages/laporan/')) {
            continue;
        }

        $isi = file_get_contents($path);

        expect(str_contains($isi, 'border-t-2 border-gray-300'))
            ->toBeFalse("{$nama} memakai garis abu-abu pada baris total, lihat ui-spec.md 2.3");

        // Setiap tfoot laporan adalah baris total, jadi wajib bermotif.
        if (str_contains($isi, '<tfoot>') && ! str_contains($isi, 'motif-baris-total')) {
            $tanpaMotif[] = $nama;
        }
    }

    expect($tanpaMotif)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Bilah filter per halaman laporan (Putaran 3 D3, 2026-08-29)
|--------------------------------------------------------------------------
|
| rules.md 12 poin 5: tiap halaman laporan punya bilah filternya sendiri,
| dikerjakan Alpine di sisi peramban (GitHub Pages tidak melayani query
| string, notes.md 1b.5). Blade merender SELURUH baris; Alpine menyembunyikan
| <tr> yang tak cocok. Perilaku penyembunyiannya diuji tests/Browser/
| uji-filter-laporan.mjs; di sini dijaga kerangkanya.
|
| D3-1 baru memasang Laporan Transmigran. Laporan lain menyusul (D3-2..D3-5).
*/

it('memasang bilah filter Alpine di halaman Laporan Transmigran (berbingkai)', function () {
    $isi = $this->get('/laporan/transmigran')->assertOk()->getContent();

    // Cakupan Alpine pada kertas, dan bilah filternya.
    expect($isi)
        ->toContain('x-data="filterLaporan(')
        ->toContain('aria-label="Penyaring laporan"')
        ->toContain('id="filter-laporan-sp"')
        ->toContain('x-text="kalimatCakupan"');

    // Bilah tidak ikut tercetak: section-nya memakai .cetak-sembunyi.
    $awal = strpos($isi, 'aria-label="Penyaring laporan"');
    expect(substr($isi, max(0, $awal - 200), 400))->toContain('cetak-sembunyi');

    // Tiap baris data membawa penanda SP supaya Alpine dapat menyaringnya,
    // dan nomor urut dikosongkan (diisi penghitung CSS, bukan indeks Blade).
    expect(substr_count($isi, 'data-baris'))->toBeGreaterThan(3);
    expect($isi)->toContain('data-sp=')->toContain('data-nomor');
});

it('membawa filter ke rute dokumen lewat hash, bukan bilah (Putaran 5)', function () {
    // Rute dokumen TANPA bilah filter. Cakupan Alpine tetap dipasang supaya
    // hash (#sp=..) yang dibawa tombol "Generate Laporan" diterapkan; baris
    // disembunyikan lewat x-show. Tombol Generate hanya di halaman berbingkai.
    $dokumen = $this->get('/laporan/transmigran/dokumen')->assertOk()->getContent();

    expect($dokumen)
        ->toContain('x-data="filterLaporan(')            // untuk dariHash()
        ->toContain('data-baris')
        ->toContain('data-sp=')
        ->not->toContain('aria-label="Penyaring laporan"')
        ->not->toContain('id="filter-laporan-sp"');

    // Tombol "Generate Laporan" di halaman berbingkai membawa keadaan filter
    // lewat fragmen hash pada :href (bukan query string).
    $berbingkai = $this->get('/laporan/transmigran')->getContent();
    expect($berbingkai)
        ->toContain('Generate Laporan')
        ->toContain(':href="')
        ->toContain('+ hashFilter');
});

it('menyusun kop dokumen laporan dari satu sumber identitas (Putaran 5)', function () {
    $instansi = LaporanData::instansi();

    // Kabupaten & provinsi diturunkan dari DummyData::kawasan(), tidak ditulis
    // ulang. Dua lambang: Kementerian + Kabupaten.
    $kawasan = DummyData::kawasan()[0];
    expect($instansi['dinas'])->toContain($kawasan['kabupaten']);
    expect($instansi['alamat'])->toContain($kawasan['provinsi']);
    expect($instansi['logoKementerian'])->toBe('images/logo/logo-kementrans-128.png');
    expect($instansi['lambangKabupaten'])->toBe('images/logo/lambang-malaka.png');
    expect(public_path('images/logo/lambang-malaka.png'))->toBeFile();

    // Tahun rujukan dokumen = tahun terakhir deret data, bukan date('Y').
    $deret = DummyData::deretTahunan()['tahun'];
    expect(LaporanData::tahunDokumenBawaan())->toBe((int) end($deret));

    // Kop HANYA di rute dokumen, tidak di halaman berbingkai.
    expect($this->get('/laporan/poktan')->getContent())->not->toContain('KEMENTERIAN TRANSMIGRASI');
    $dokumen = $this->get('/laporan/poktan/dokumen')->getContent();
    expect($dokumen)
        ->toContain('lambang-malaka.png')
        ->toContain('logo-kementrans-128.png')
        ->toContain('TAHUN '.LaporanData::tahunDokumenBawaan());
});

it('menyaring laporan lewat Alpine, bukan query string yang mati di GitHub Pages', function () {
    // notes.md 1b.5: query string tidak dilayani situs statis. Bilah filter
    // laporan karena itu TIDAK boleh berupa <form> yang men-submit.
    $isi = $this->get('/laporan/transmigran')->getContent();

    // Ambil hanya penggal <section aria-label="Penyaring laporan"> ... </section>.
    expect($isi)->toContain('aria-label="Penyaring laporan"');
    $mulai = strpos($isi, 'aria-label="Penyaring laporan"');
    $penggal = substr($isi, $mulai, 4000);

    expect($penggal)
        ->not->toContain('<form')
        ->not->toContain('type="submit"')
        ->toContain('x-model="sp"');
});

it('menyusun opsi SP filter laporan dari data master, bukan cacahan baris contoh', function () {
    // rules.md 19a: keputusan tidak boleh bersandar pada cacahan baris contoh.
    // Daftar SP di sini adalah master (DummyData::satuanPermukiman()), sah.
    $konfig = LaporanData::filterLaporan('transmigran');

    $idMaster = array_column(DummyData::satuanPermukiman(), 'id_satuan_permukiman');
    $idFilter = array_column($konfig['sp'], 'id');

    expect($idFilter)->toBe($idMaster);
    expect($konfig['cakupanBawaan'])->toBe(LaporanData::meta('transmigran')['cakupan']);

    // Opsi tahun kedatangan terurut naik dan unik.
    $tahun = $konfig['daftarTahun'];
    expect($tahun)->toBe(array_values(array_unique($tahun)));
    expect($tahun)->toBe(collect($tahun)->sort()->values()->all());
});

it('menandai hanya laporan yang bilah filternya sudah dipasang', function () {
    // D3 bertahap: begitu satu laporan memperoleh filter, arm-nya di sini.
    // Larik kosong = belum berfilter, dan kerangka-laporan tidak merender bilah.
    $berfilter = [];

    foreach (array_keys(LaporanData::meta()) as $slug) {
        if (LaporanData::filterLaporan($slug) !== []) {
            $berfilter[] = $slug;
        }

        // Halaman tetap membalas 200 apa pun keadaan filternya.
        $this->get('/laporan/'.$slug)->assertOk();
    }

    // Ketujuh laporan kini berfilter (urutan mengikuti LaporanData::meta()).
    expect($berfilter)->toBe([
        'indikator-kawasan', 'monografi-sp', 'transmigran',
        'poktan', 'alsintan', 'saprotan', 'hasil-panen',
    ]);
});

it('menjaga jumlah enam SP = angka kawasan pada Rekap Indikator Kawasan', function () {
    // rules.md 12 poin 10: angka tingkat kawasan tetap dari dashboard;
    // rincian per SP berjumlah PERSIS sama dengannya, sehingga filter SP yang
    // menjumlah ulang tabel per SP tidak pernah membantah blok ringkasan.
    $perSp = LaporanData::indikatorKawasan()['perSp'];
    $kawasan = DummyData::ringkasanDashboard();

    // Hanya indikator yang bersifat JUMLAH. Rata-rata (produktivitas, harga)
    // dan cacah kelembagaan dikecualikan: bukan penjumlahan lintas SP.
    $petaan = [
        'jumlah_kk' => 'jumlah_kk',
        'rumah_terhuni' => 'rumah_terhuni',
        'luas_lahan' => 'luas_lahan_total',
        'volume_panen' => 'volume_panen_ton',
        'pengaduan_terbuka' => 'pengaduan_terbuka',
    ];

    foreach ($petaan as $kolomSp => $kolomKawasan) {
        $jumlah = round(array_sum(array_column($perSp, $kolomSp)), 2);
        expect($jumlah)->toBe(round((float) $kawasan[$kolomKawasan], 2), "indikator {$kolomSp} tidak menjumlah ke angka kawasan");
    }

    // Baris total tabel per SP dijumlah ulang oleh Alpine, bukan angka tetap.
    // Pemilih TAHUN TUNGGAL (Putaran 5): tabel per SP dirender 6 SP x 5 tahun,
    // blok ringkasan mengikuti tahun terpilih (nilaiTahun), bukan menyempit SP.
    $isi = $this->get('/laporan/indikator-kawasan')->getContent();
    expect($isi)
        ->toContain('data-baris data-sp=')
        ->toContain('data-tahun=')
        ->toContain("jumlahTampak(\$el.closest('table'), 'jumlah_kk', 0)")
        ->toContain('x-text="nilaiTahun(')                       // blok kawasan ikut tahun
        ->toContain('id="filter-laporan-tahun"')
        ->toContain('tetap menampilkan angka tingkat kawasan');   // catatan kejujuran

    expect(substr_count($isi, '<tr data-baris data-sp='))->toBe(6 * 5);

    $konfig = LaporanData::filterLaporan('indikator-kawasan');
    expect($konfig['tahunTunggal'])->toBeTrue();
    expect($konfig['ringkasanTahun'][LaporanData::tahunDokumenBawaan()])
        ->toBe(DummyData::indikatorKawasanTahun()[LaporanData::tahunDokumenBawaan()]);
});

it('menyembunyikan tabel poktan seutuhnya lewat penanda SP, bukan per baris', function () {
    // Satu poktan milik tepat satu SP, jadi penyaring SP menyembunyikan
    // wadah tabelnya, bukan baris anggotanya.
    $isi = $this->get('/laporan/poktan')->assertOk()->getContent();

    expect($isi)
        ->toContain('x-data="filterLaporan(')
        ->toContain('data-poktan-wadah')
        ->toContain('Tidak ada kelompok tani yang cocok dengan filter');

    // Lebih dari satu wadah poktan, dan tiap wadah membawa data-sp lalu x-show.
    expect(substr_count($isi, 'data-poktan-wadah data-sp='))->toBeGreaterThan(1);
    expect(substr_count($isi, 'data-poktan-wadah data-sp='))
        ->toBe(substr_count($isi, 'data-poktan-wadah data-sp="'));
});

it('menghitung ulang subtotal per SP dan total kawasan saat Laporan Alsintan disaring', function () {
    // Laporan grup-per-SP (kelompokkanPerSp): tiap baris data ber-data-*, tiap
    // subtotal dan total memakai x-text yang menjumlah ulang baris yang cocok.
    $isi = $this->get('/laporan/alsintan')->assertOk()->getContent();

    expect($isi)
        ->toContain('x-data="filterLaporan(')
        ->toContain('data-baris data-sp=')
        ->toContain('data-jumlah=')
        // Subtotal per SP: dijumlah ulang lewat selSp(), bukan angka Blade tetap.
        ->toContain("jumlahTampak(\$el.closest('table'), 'jumlah', 0, selSp(")
        // Total kawasan: dijumlah ulang atas seluruh baris yang cocok.
        ->toContain("jumlahTampak(\$el.closest('table'), 'jumlah', 0)")
        // rules.md 8o: baris total yang menyempit menyatakan cakupan aktif.
        ->toContain("x-text=\"'(' + kalimatCakupan + ')'\"")
        // Baris grup-header dan subtotal ikut sembunyi saat grupnya kosong.
        ->toContain("x-show=\"! kosong(\$el.closest('table'), selSp(");

    // Angka Blade tetap dipertahankan sebagai jaring bila JS mati.
    expect($isi)->toContain('Total Kawasan Kobalima Timur');

    // Setiap baris data membawa keempat penanda filternya.
    $baris = substr_count($isi, 'data-baris data-sp=');
    expect($baris)->toBeGreaterThan(1);
    expect(substr_count($isi, 'data-jumlah='))->toBe($baris);
    expect(substr_count($isi, 'data-jenis='))->toBe($baris);
});

it('menyaring kedua bagian Laporan Saprotan tanpa subtotal (tabel datar)', function () {
    // Saprotan dua tabel datar (benih + non-benih), tanpa grup per SP.
    // Penyaring hanya menyembunyikan baris; tak ada subtotal untuk dihitung.
    $isi = $this->get('/laporan/saprotan')->assertOk()->getContent();

    expect($isi)
        ->toContain('x-data="filterLaporan(')
        ->toContain('data-baris data-sp=')
        ->toContain('data-komoditas=')                       // dimensi benih
        ->toContain('data-jenis=')                           // dimensi non-benih
        ->toContain('Tidak ada penyaluran benih yang cocok dengan filter')
        ->toContain('Tidak ada penyaluran sarana non-benih yang cocok dengan filter')
        ->not->toContain('jumlahTampak(');                   // tak ada subtotal

    // Dimensi komoditas hanya di baris benih, jenis hanya di non-benih.
    expect(substr_count($isi, 'data-komoditas='))->toBeLessThan(substr_count($isi, 'data-baris data-sp='));
});

it('menghitung ulang produktivitas tertimbang, bukan merata-ratakannya, pada Laporan Hasil Panen', function () {
    // rules.md 16a: sumbu tahun laporan panen = tahun anggaran bantuan.
    // Produktivitas subtotal/total adalah Sigma produksi / Sigma realisasi
    // panen (rasioTampak), bukan rata-rata produktivitas per baris.
    $isi = $this->get('/laporan/hasil-panen')->assertOk()->getContent();

    expect($isi)
        ->toContain('x-data="filterLaporan(')
        ->toContain('data-produksi_ton=')
        ->toContain('data-realisasi_panen=')
        ->toContain('data-sumber_dana=')
        ->toContain('data-poktan_id=')
        ->toContain("rasioTampak(\$el.closest('table'), 'produksi_ton', 'realisasi_panen', 2")
        ->toContain("jumlahTampakPoktanUnik(\$el.closest('table'), 'luas_lahan'")
        ->toContain("belumDitanamTampak(\$el.closest('table'), 2")
        ->toContain("jumlahTampak(\$el.closest('table'), 'puso'")
        ->toContain('Tidak ada catatan panen yang cocok dengan filter')
        ->toContain("x-text=\"'(' + kalimatCakupan + ')'\"");

    // Label pemilih tahun menegaskan sumbu anggaran (rules 16a).
    expect(LaporanData::filterLaporan('hasil-panen')['labelTahun'])->toBe('Tahun Anggaran');

    // Subtotal per SP dihitung ulang lewat selSp().
    expect($isi)->toContain(', selSp(');
});

it('menyaring tabel ikhtisar dan tiap bab Monografi SP dengan pemilih SP + tahun tunggal', function () {
    // Monografi = potret per SP: pemilih SP menyembunyikan baris ikhtisar SP
    // lain DAN section Bab II SP lain. Pemilih TAHUN TUNGGAL (Putaran 5),
    // BUKAN rentang: ikhtisar dirender 6 SP x 5 tahun, Bab II satu section/SP.
    $isi = $this->get('/laporan/monografi-sp')->assertOk()->getContent();

    expect($isi)
        ->toContain('x-data="filterLaporan(')
        ->toContain('data-baris data-sp=')
        ->toContain('data-tahun=')
        ->toContain('<section data-baris data-sp=')
        ->toContain('id="filter-laporan-tahun"')            // pemilih tahun tunggal
        ->toContain('x-text="iklimTahun(')                  // Keadaan Wilayah iklim ikut tahun
        ->toContain('x-text="nilaiKependudukan(')           // keadaan penduduk sekarang ikut tahun
        ->toContain('Tidak ada satuan permukiman yang cocok dengan filter')
        ->not->toContain('id="filter-laporan-tahun-dari"'); // BUKAN rentang tahun

    // Judul bagian tanpa awalan "Bab X." (Putaran 6).
    expect($isi)
        ->toContain('Pendahuluan')
        ->toContain('>Keadaan Wilayah</h4>')
        ->toContain('>Kependudukan</h4>')
        ->toContain('>Sosial Ekonomi</h4>')
        ->toContain('>Sosial Budaya</h4>')
        ->toContain('Struktur penduduk menurut kelompok umur')
        ->toContain('Mutasi penduduk kumulatif sejak penempatan')
        ->not->toContain('Bab II');

    // Ikhtisar: 6 SP x 5 tahun = 30 baris. Section tetap satu per SP = 6.
    expect(substr_count($isi, '<tr data-baris data-sp='))->toBe(6 * 5);
    expect(substr_count($isi, '<section data-baris data-sp='))->toBe(6);

    $konfig = LaporanData::filterLaporan('monografi-sp');
    expect($konfig['tahun'])->toBeFalse();
    expect($konfig['tahunTunggal'])->toBeTrue();
    expect($konfig['daftarTahun'])->toBe(DummyData::tahunLaporan());
    expect($konfig['tahunBawaan'])->toBe(LaporanData::tahunDokumenBawaan());
    expect($konfig)->toHaveKey('kependudukanTahun');
});

it('menomori baris laporan lewat penghitung CSS supaya rapat setelah disaring', function () {
    // Penyaring D3 menyembunyikan <tr> dengan display:none, dan elemen
    // ber-display:none TIDAK menaikkan penghitung CSS. Aturannya wajib benar
    // ada di CSS, bukan kelas yang menggantung.
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('.tabel-dokumen tbody {')
        ->toContain('counter-reset: baris-laporan;')
        ->toContain('.tabel-dokumen tr[data-baris] {')
        ->toContain('counter-increment: baris-laporan;')
        ->toContain('.tabel-dokumen td[data-nomor]::before {')
        ->toContain('content: counter(baris-laporan);');
});

/*
|--------------------------------------------------------------------------
| Filter rentang tahun pada halaman daftar bersumbu waktu
|--------------------------------------------------------------------------
|
| Ditambahkan 2026-08-28 (rules.md 12 poin 12). Penyaring tahun tunggal pada
| /panen dan /penanaman diganti sepasang dari-sampai; /audit-log memperoleh
| penyaring tahun untuk pertama kalinya. Rekap agregat DIKECUALIKAN: rekap
| panen yang dijumlah lintas tahun melanggar 9 poin 8b.
*/

it('menyaring helper rentang tahun pada batas kosong, terbalik, dan tahun hilang', function () {
    $baris = [['t' => 2023], ['t' => 2025], ['t' => 2026], ['t' => null]];
    $ambil = fn ($b) => $b['t'];

    // Dua batas kosong: kembalikan apa adanya, tanpa menyentuh baris tak bertahun.
    expect(DummyData::saringRentangTahun($baris, null, null, $ambil))->toHaveCount(4);

    // Satu batas dipasang: baris tanpa tahun ikut tersaring keluar.
    expect(DummyData::saringRentangTahun($baris, 2025, null, $ambil))->toEqual([['t' => 2025], ['t' => 2026]]);
    expect(DummyData::saringRentangTahun($baris, null, 2025, $ambil))->toEqual([['t' => 2023], ['t' => 2025]]);

    // Batas terbalik ditukar, bukan menghasilkan daftar kosong tanpa penjelasan.
    expect(DummyData::saringRentangTahun($baris, 2026, 2023, $ambil))
        ->toEqual(DummyData::saringRentangTahun($baris, 2023, 2026, $ambil));

    // String kosong dari query diperlakukan seperti batas tak dipasang.
    expect(DummyData::saringRentangTahun($baris, '', '', $ambil))->toHaveCount(4);
});

it('menyaring daftar panen menurut rentang tahun', function () {
    // Data contoh: 5 panen 2026, 1 panen 2025.
    $penuh = $this->get('/panen')->assertOk()->viewData('baris');
    $r2025 = $this->get('/panen?tahun_dari=2025&tahun_sampai=2025')->assertOk()->viewData('baris');
    $r2026 = $this->get('/panen?tahun_dari=2026&tahun_sampai=2026')->assertOk()->viewData('baris');

    $tahun = fn ($rows) => collect($rows)
        ->map(fn ($p) => (int) substr((string) $p['periode_panen'], 0, 4))
        ->unique()->sort()->values()->all();

    expect($tahun($r2025))->toBe([2025]);
    expect($tahun($r2026))->toBe([2026]);
    expect(count($r2025))->toBeGreaterThan(0);
    expect(count($r2026))->toBeGreaterThan(0);
    // Tiap baris jatuh tepat ke satu rentang: tidak ada yang hilang atau ganda.
    expect(count($r2025) + count($r2026))->toBe(count($penuh));

    // Kedua kolom penyaring dirender.
    $this->get('/panen')->assertSee('name="tahun_dari"', false)->assertSee('name="tahun_sampai"', false);
});

it('menyaring daftar penanaman menurut rentang tahun dan menukar batas terbalik', function () {
    $penuh = $this->get('/penanaman')->assertOk()->viewData('baris');
    $benar = $this->get('/penanaman?tahun_dari=2025&tahun_sampai=2026')->assertOk()->viewData('baris');
    $terbalik = $this->get('/penanaman?tahun_dari=2026&tahun_sampai=2025')->assertOk()->viewData('baris');
    $hanya2026 = $this->get('/penanaman?tahun_dari=2026&tahun_sampai=2026')->assertOk()->viewData('baris');

    expect(count($benar))->toBe(count($penuh));
    expect(count($terbalik))->toBe(count($benar));
    expect(count($hanya2026))->toBeGreaterThan(0)->toBeLessThan(count($penuh));
});

it('memberi audit log filter rentang tahun untuk pertama kalinya', function () {
    $this->get('/audit-log')->assertOk()
        ->assertSee('name="tahun_dari"', false)
        ->assertSee('name="tahun_sampai"', false);

    // Rentang di luar jangkauan wajib menyaring habis. `baris` kini paginator
    // Eloquent (Task 3.12), sehingga dibuktikan lewat `total()`, bukan `[]`.
    expect($this->get('/audit-log?tahun_dari=2099')->assertOk()->viewData('baris')->total())->toBe(0);
    expect($this->get('/audit-log?tahun_sampai=2000')->assertOk()->viewData('baris')->total())->toBe(0);
});

it('tidak memasang filter rentang tahun pada halaman rekap agregat', function () {
    // rules.md 9 poin 8b: rekap panen tak boleh dijumlah lintas tahun, sebab
    // luas 2 ha yang ditanami tiga tahun akan terbaca 6 ha.
    $rekapPanen = $this->get('/panen/rekap')->assertOk();
    $rekapPanen->assertDontSee('name="tahun_dari"', false)
        ->assertDontSee('name="tahun_sampai"', false);

    // Rekap panen tetap memakai penyaring tahun TUNGGAL.
    $rekapPanen->assertSee('name="tahun"', false);

    // Komponen rentang tak muncul di halaman rekap mana pun.
    foreach (['/panen/rekap', '/pengaduan/rekap', '/kependudukan/rekap'] as $jalur) {
        $this->get($jalur)->assertOk()->assertDontSee('filter_tahun_dari', false);
    }

    // Sumbernya pun tidak memanggil komponennya.
    expect(file_get_contents(resource_path('views/pages/panen/rekap.blade.php')))
        ->not->toContain('filter-rentang-tahun');
});

it('mencabut kewenangan export dari seluruh sumber kebenaran', function () {
    // Ekspor kini mengikuti `lihat`, sebab ia hanya cara lain membaca data
    // yang sudah boleh dilihat (rules.md 5.1 catatan 5). Nilai yang tertinggal
    // di salah satu sumber akan menghidupkan kembali kotak centang yang tidak
    // menjaga apa pun.
    expect(array_column(AksiPermission::cases(), 'value'))
        ->toBe(['lihat', 'tambah', 'ubah', 'hapus']);

    foreach (DummyData::daftarIzin() as $kelompok) {
        foreach ($kelompok['modul'] as $modul) {
            expect($modul['aksi'])->not->toContain('export');
        }
    }

    foreach ([1, 2, 3, 4, 5] as $idRole) {
        foreach (DummyData::izinRole($idRole) as $aksi) {
            expect($aksi)->not->toContain('export');
        }
    }

    // Huruf E pada matriks rules.md 5.1 ikut dicabut, agar dokumen tidak
    // menjanjikan kewenangan yang tidak lagi ada di kode.
    expect(file_get_contents(base_path('agents/rules.md')))
        ->not->toContain('**E** = export');

    // Sumber kelima, yang justru terlewat: tampilan. `form-role.blade.php`
    // menyalin daftar aksi dengan tangan dan masih merender kolom `export`
    // tiga hari setelah enum-nya dibersihkan. Kolomnya selalu kosong pada
    // setiap fitur, sehingga tidak ada yang tampak rusak dan tidak ada uji
    // yang memerah. Sekarang view membaca AksiPermission::opsi().
    $formRole = file_get_contents(resource_path('views/pages/pengguna/form-role.blade.php'));

    expect($formRole)->toContain('AksiPermission::opsi()');
    expect($formRole)->not->toContain("'export' =>");
    expect(array_keys(AksiPermission::opsi()))
        ->toBe(['lihat', 'tambah', 'ubah', 'hapus']);

    // Kalimat pengantar pada halaman role juga menjanjikan "mengekspor"
    // sebagai kewenangan tersendiri.
    expect(file_get_contents(resource_path('views/pages/pengguna/role.blade.php')))
        ->not->toContain('mengekspor data');
});

/*
|--------------------------------------------------------------------------
| Data master wilayah dan kawasan
|--------------------------------------------------------------------------
*/

it('menyatukan keempat tingkat wilayah dalam satu tabel, bukan empat tab', function () {
    // Menggantikan uji tab bawaan (dicabut 2026-09-02 bersama tabnya).
    // Sejak provinsi dan kabupaten dibaca dari data referensi nasional, tab
    // Kabupaten memuat ratusan baris tanpa pencarian; menyatukannya juga
    // menghapus keharusan menebak satu nama berada di tab mana.
    $isi = $this->get(route('wilayah'))->assertOk()->getContent();

    // Tab benar-benar tidak ada lagi, bukan sekadar tersembunyi.
    $sumber = file_get_contents(resource_path('views/pages/master/wilayah.blade.php'));
    expect($sumber)->not->toContain('hashTabs(')
        ->and($sumber)->not->toContain('role="tablist"');

    // Tingkat berpindah menjadi kolom sekaligus penyaring.
    expect($isi)->toContain('name="tingkat"')
        ->and($isi)->toContain('Tingkat Wilayah');

    // Keempat tingkat tetap terjangkau dari satu halaman.
    foreach (['provinsi', 'kabupaten', 'kecamatan', 'desa'] as $tingkat) {
        expect($isi)->toContain('value="'.$tingkat.'"');
    }
});

it('menyaring daftar wilayah menurut tingkat beserta jumlahnya', function () {
    // Judul tab lama menampilkan jumlah per tingkat. Menghapus tab tanpa
    // memindahkan angka itu berarti pembaca kehilangan keterangan yang
    // sebelumnya ada, dan perombakannya berubah menjadi kemunduran.
    $w = DummyData::wilayah();

    $isi = $this->get(route('wilayah'))->assertOk()->getContent();

    foreach (['provinsi', 'kabupaten', 'kecamatan', 'desa'] as $tingkat) {
        expect($isi)->toContain('('.count($w[$tingkat]).')');
    }

    // Penyaringnya benar-benar menyempitkan, bukan sekadar tampil.
    $desa = $this->get(route('wilayah', ['tingkat' => 'desa']))->assertOk()->getContent();

    expect(substr_count($desa, 'formUbahWilayahBaris'))
        ->toBeLessThan(substr_count($isi, 'formUbahWilayahBaris'));

    // Pencarian mencakup induknya, sebab petugas kerap mengingat
    // kabupatennya ketika nama kecamatannya sendiri sudah kabur.
    $this->get(route('wilayah', ['cari' => 'Malaka']))->assertOk()->assertSee('Laen Manen');
});

it('menandai wajib isian induk wilayah secara bersyarat', function () {
    // Ketiga isian induk saling meniadakan: hanya satu berlaku pada satu
    // waktu. Dengan `required` tetap, peramban menuntut ketiganya terisi
    // sekaligus dan form TIDAK PERNAH dapat dikirim untuk tingkat apa pun,
    // sementara pesan galatnya menunjuk elemen tersembunyi sehingga petugas
    // tidak melihat apa yang kurang.
    //
    // DIPERIKSA PADA HTML HASIL RENDER, bukan teks sumber (diubah
    // 2026-09-02). Dua isian teratas kini memakai `x-sim.pilih-cari` yang
    // merender atributnya di dalam komponen, sehingga pemeriksaan berbasis
    // sumber mengunci CARA implementasinya, bukan perilakunya.
    $isi = $this->get(route('wilayah'))->assertOk()->getContent();

    foreach (['provinsi_id', 'kabupaten_id', 'kecamatan_id'] as $isian) {
        expect($isi)->toContain('name="'.$isian.'"');
    }

    // Tidak boleh ada `required` tetap pada ketiganya.
    expect(preg_match('/name="(provinsi|kabupaten|kecamatan)_id"[^>]*\srequired[\s>]/', $isi))->toBe(0);

    // Sebaliknya, ketiganya wajib memakai pasangan bersyarat Alpine.
    $sumber = file_get_contents(resource_path('views/pages/master/form-wilayah.blade.php'));

    expect(preg_match_all('/:?required="tingkat === /', $sumber))->toBe(3)
        ->and(preg_match_all('/:?disabled="tingkat !== /', $sumber))->toBe(3);
});

it('memilih kabupaten kawasan lewat dua tingkat', function () {
    // Menyodorkan daftar kabupaten se-Indonesia tanpa menanyakan provinsinya
    // membuat petugas mencari di antara lima ratusan nama yang sebagian besar
    // tidak pernah relevan, dan nama kabupaten pun tidak selalu unik
    // antar-provinsi.
    $isi = $this->get(route('kawasan'))->assertOk()->getContent();

    expect($isi)->toContain('name="provinsi_id"')
        ->and($isi)->toContain('name="kabupaten_id"')
        // Daftar kabupaten dirender Alpine agar dapat disaring.
        ->and($isi)->toContain('kabupatenTersaring')
        // Ajakan yang jujur ketika provinsi belum dipilih.
        ->and($isi)->toContain('Pilih provinsi lebih dulu')
        // Jaminan tanpa JavaScript, mengikuti pola pilih-cari.
        ->and($isi)->toContain('<noscript>');

    $sumber = file_get_contents(resource_path('views/pages/sp/form-kawasan.blade.php'));

    // Provinsi hanya menyaring dan tidak disimpan, sehingga tidak boleh wajib:
    // menandainya wajib memblokir pengiriman ketika JavaScript mati dan isian
    // ini disembunyikan.
    expect(preg_match('/name="provinsi_id"\s+required/', $sumber))->toBe(0);

    // Kabupatennya yang wajib, dan `required`-nya dipasang Alpine dengan alasan
    // yang sama.
    expect($sumber)->toContain(':required="provinsiId !== \'\'"')
        ->and($sumber)->toContain('gantiProvinsi()');
});

it('menyediakan provinsi induk bagi setiap kabupaten', function () {
    // Penyaringan bertingkat hanya mungkin bila setiap kabupaten menyatakan
    // provinsinya. Tanpa kunci ini daftar tersaring akan selalu kosong, dan
    // kegagalannya berlangsung diam-diam: dropdown tampil terbuka tetapi tidak
    // menawarkan apa pun.
    $wilayah = DummyData::wilayah();
    $idProvinsi = array_column($wilayah['provinsi'], 'id_provinsi');

    expect($wilayah['kabupaten'])->not->toBeEmpty();

    foreach ($wilayah['kabupaten'] as $kabupaten) {
        expect($kabupaten)->toHaveKey('provinsi_id');
        expect(in_array($kabupaten['provinsi_id'], $idProvinsi, true))
            ->toBeTrue("kabupaten {$kabupaten['nama']} menunjuk provinsi yang tidak ada");
    }
});

/*
|--------------------------------------------------------------------------
| Pergantian kepala keluarga
|--------------------------------------------------------------------------
*/

it('menyediakan suksesi sebagai tindakan tersendiri, bukan lewat form ubah', function () {
    // Bila suksesi lahir dari penyuntingan nama pada form biasa, setiap
    // perbaikan ejaan akan mengotori riwayat suksesi. Audit log pun tidak dapat
    // membedakan keduanya, sebab keduanya berbentuk aksi Ubah pada kolom yang
    // sama (rules.md 6 poin 5a dan 5b).
    $isi = $this->get(route('transmigran.detail', 1))->assertOk()->getContent();

    expect($isi)->toContain('formGantiKepalaKeluarga')
        ->and($isi)->toContain('Ganti Kepala Keluarga')
        // Modal tersendiri, bukan isian tambahan pada modal ubah.
        ->and($isi)->toContain('formUbahTransmigran');

    // Rutenya berdiri sendiri dan bermetode POST, bukan menumpang PUT perbarui.
    expect(Route::has('transmigran.ganti-kepala-keluarga'))->toBeTrue();

    // Sejak Task 5.2 suksesi sungguh dijalankan; kiriman sah (pengganti dari
    // anggota keluarga + nasib jabatan ketua) diarahkan ke tab riwayat.
    $this->post(route('transmigran.ganti-kepala-keluarga', 1), [
        'pengganti_anggota_keluarga_id' => 1,
        'no_kk_baru' => '5321010102150001',
        'tanggal_pergantian' => '2026-01-01',
        'alasan' => 'Meninggal',
        'nasib_ketua_poktan' => 'kosongkan',
    ])->assertRedirect(route('transmigran.detail', ['id' => 1, 'tab' => 'riwayat-kk']));
});

it('memilih pengganti kepala keluarga dari daftar anggota keluarga', function () {
    // Sejak Stage B3 (2026-08-28) pengganti DIPILIH dari daftar anggota
    // keluarga, tidak diketik (erd.md 7.4a dibalik). Identitasnya "naik"
    // menimpa baris transmigran. Urutan Dukcapil (pasangan lalu anak tertua)
    // hanya diurutkan sebagai penunjuk, bukan ditegakkan (rules.md 6.5d).
    $isi = $this->get(route('transmigran.detail', 1))->assertOk()->getContent();

    // Pilihan pengganti, dari daftar anggota keluarga keluarga ini.
    expect($isi)->toContain('name="pengganti_anggota_keluarga_id"');

    $calon = DummyData::calonPenggantiKk(1);
    expect($calon)->not->toBeEmpty();
    expect($isi)->toContain($calon[0]['nama']);

    // nama_baru / nik_baru / hubungan_pengganti tetap terkirim dengan nama
    // yang sama, tetapi sebagai isian tersembunyi yang dibaca dari pilihan.
    foreach (['nama_baru', 'nik_baru', 'hubungan_pengganti', 'no_kk_baru', 'tanggal_pergantian', 'alasan'] as $isian) {
        expect($isi)->toContain('name="'.$isian.'"');
    }
    expect($isi)->toContain(':value="pengganti?.nama');

    // Sisi lama dikirim tanpa diketik ulang, agar riwayat menyimpan keduanya.
    foreach (['nik_lama', 'nama_lama', 'no_kk_lama'] as $isian) {
        expect($isi)->toContain('name="'.$isian.'"');
    }
});

it('mengurutkan calon pengganti kepala keluarga: pasangan lalu usia tertua', function () {
    $calon = DummyData::calonPenggantiKk(1);

    // Keluarga 1: istri MARIA BERE HOAR lalu anak menurut usia menurun.
    expect($calon[0]['hubungan'])->toBe('Istri');
    $usiaAnak = array_values(array_filter(
        array_column($calon, 'usia'),
        fn ($u, $i) => $calon[$i]['hubungan'] !== 'Istri',
        ARRAY_FILTER_USE_BOTH
    ));
    expect($usiaAnak)->toBe(collect($usiaAnak)->sortDesc()->values()->all());

    // Tiap calon membawa NIK dan hubungan yang akan naik ke baris transmigran.
    expect($calon[0])->toHaveKeys(['id', 'nama', 'nik', 'hubungan', 'usia']);
});

it('menuntut petugas memutuskan nasib jabatan ketua poktan saat suksesi', function () {
    // Jabatan ketua dipilih anggota dan TIDAK diwariskan. Tanpa pemeriksaan
    // ini, menyunting baris transmigran akan membuat kepala keluarga baru
    // menjadi ketua tanpa seorang pun memutuskan (rules.md 6 poin 5e).
    //
    // Keluarga 1 menjabat ketua POKTAN MEKAR JAYA lewat jalur Kepala Keluarga.
    $adaKetua = $this->get(route('transmigran.detail', 1))->assertOk()->getContent();

    expect($adaKetua)->toContain('name="nasib_ketua_poktan"')
        ->and($adaKetua)->toContain('value="kosongkan"')
        ->and($adaKetua)->toContain('value="teruskan"')
        ->and($adaKetua)->toContain('POKTAN MEKAR JAYA');

    // Keluarga 8 hanya anggota, bukan ketua: pilihan itu tidak boleh muncul,
    // sebab kontrol yang tidak menentukan apa pun adalah kontrol mati (R-26).
    $bukanKetua = $this->get(route('transmigran.detail', 8))->assertOk()->getContent();

    expect($bukanKetua)->not->toContain('name="nasib_ketua_poktan"');

    // Ketua yang berupa anggota keluarga TIDAK ikut terpengaruh, sebab ia punya
    // nama dan NIK tersendiri. Keluarga 3 diketuai istrinya di POKTAN TANI
    // BERSATU, sehingga suksesi kepala keluarganya tidak menyentuh jabatan itu.
    expect(DummyData::poktanDiketuaiKeluarga(3))->toBeEmpty();
    expect(DummyData::poktanDiketuaiKeluarga(1))->not->toBeEmpty();
});

it('memberi tahu bahwa keanggotaan poktan mengikuti, tanpa meminta keputusan', function () {
    // Berbeda dari jabatan ketua, keanggotaan MEMANG mengikuti sebab melekat
    // pada keluarga (rules.md 7a poin 3a). Petugas cukup diberi tahu.
    $isi = $this->get(route('transmigran.detail', 1))->assertOk()->getContent();

    expect($isi)->toContain('mengikuti kepala keluarga baru')
        ->and($isi)->toContain('POKTAN MEKAR JAYA');
});

it('menyajikan riwayat suksesi beserta kedua sisi identitasnya', function () {
    // Kedua sisi disimpan agar riwayat terbaca berdiri sendiri, tanpa perlu
    // merangkainya dari baris berikutnya.
    $this->get(route('transmigran.detail', 6))
        ->assertOk()
        ->assertSee('Riwayat Kepala Keluarga (1)')
        ->assertSee('YAKOBUS BRIA')
        ->assertSee('FRANSISKA BRIA')
        ->assertSee('Meninggal');

    // Keadaan kosong dinyatakan apa adanya, bukan tab yang hilang.
    $this->get(route('transmigran.detail', 1))
        ->assertOk()
        ->assertSee('Belum pernah berganti kepala keluarga');
});

it('menampilkan nomor KK hanya bila benar-benar berubah', function () {
    // Menampilkan dua nomor yang sama membuat pembaca menduga ada perubahan
    // yang tidak ada. Keluarga 6 nomornya berganti, keluarga 4 tidak.
    $berubah = $this->get(route('transmigran.detail', 6))->assertOk()->getContent();
    $tetap = $this->get(route('transmigran.detail', 4))->assertOk()->getContent();

    expect($berubah)->toContain('5321010102160006')
        ->and($tetap)->toContain('tidak berubah');
});

it('menyelaraskan riwayat suksesi dengan data transmigran terkini', function () {
    // Sisi BARU pada riwayat wajib sama dengan keadaan sekarang: suksesi
    // menyunting baris yang ada, sehingga nama, NIK, dan nomor KK terakhir
    // harus cocok. Data contoh yang tidak selaras akan menampilkan riwayat
    // yang bertentangan dengan kartu profil di halaman yang sama.
    foreach (DummyData::riwayatKepalaKeluarga() as $jejak) {
        $keluarga = DummyData::cariTransmigran($jejak['transmigran_id']);

        expect($keluarga)->not->toBeNull();
        expect($keluarga['nama_kepala_keluarga'])->toBe($jejak['nama_baru'])
            ->and($keluarga['nik'])->toBe($jejak['nik_baru'])
            ->and($keluarga['no_kk'])->toBe($jejak['no_kk_baru']);

        // Pengganti tidak boleh orang yang sama (aturan integritas 28).
        expect($jejak['nik_baru'])->not->toBe($jejak['nik_lama']);
    }

    // Kedua sebab yang paling lazim wajib terwakili, dan satu di antaranya
    // wajib bernomor KK tetap agar cabang tampilannya ikut terlihat.
    $alasan = array_column(DummyData::riwayatKepalaKeluarga(), 'alasan');

    expect($alasan)->toContain('Meninggal')->toContain('Pindah atau Merantau');

    $kkTetap = array_filter(
        DummyData::riwayatKepalaKeluarga(),
        fn ($r) => $r['no_kk_lama'] === $r['no_kk_baru']
    );

    expect($kkTetap)->not->toBeEmpty('data contoh wajib memuat suksesi tanpa perubahan nomor KK');
});

it('tidak menyediakan penghapusan riwayat kepala keluarga', function () {
    // Riwayat suksesi menyatakan siapa pemegang jatah lahan pada rentang waktu
    // tertentu, sehingga menghapusnya menghilangkan dasar penguasaan lahan
    // (rules.md 5.1 catatan 8). Admin pun hanya memegang ubah.
    foreach ([1, 2, 3, 4] as $idRole) {
        $izin = DummyData::izinRole($idRole)['riwayat_kepala_keluarga'] ?? [];

        expect($izin)->not->toContain('hapus');
    }

    expect(DummyData::izinRole(1)['riwayat_kepala_keluarga'])->toBe(['lihat', 'tambah', 'ubah']);
    expect(DummyData::izinRole(2)['riwayat_kepala_keluarga'])->toBe(['lihat', 'tambah']);
    expect(DummyData::izinRole(3)['riwayat_kepala_keluarga'])->toBe(['lihat']);
    expect(DummyData::izinRole(4)['riwayat_kepala_keluarga'])->toBe(['lihat']);

    expect(Route::has('riwayat-kepala-keluarga.hapus'))->toBeFalse();
});

it('membedakan alasan pergantian dari status tinggal keluarga', function () {
    // Keduanya menjawab pertanyaan berbeda: status tinggal menyatakan keadaan
    // terkini sebuah KELUARGA, sedangkan alasan pergantian merekam satu
    // PERISTIWA bertanggal. Ketika kepala keluarga meninggal lalu istrinya
    // menggantikan, keluarganya tetap Aktif (data-dictionary.md 11.36).
    expect(AlasanPergantianKK::nilai())
        ->toBe(['Meninggal', 'Pindah atau Merantau', 'Cerai', 'Lainnya']);

    foreach (DummyData::riwayatKepalaKeluarga() as $jejak) {
        $keluarga = DummyData::cariTransmigran($jejak['transmigran_id']);

        // Keluarga 6 kepala keluarganya meninggal, tetapi keluarganya sendiri
        // tetap aktif sebab istrinya menempati rumah yang sama.
        if ($jejak['alasan'] === AlasanPergantianKK::Meninggal->value) {
            expect($keluarga['status_tinggal'])->not->toBe('Meninggal');
        }
    }
});

/*
|--------------------------------------------------------------------------
| Kelompok tani: ketua, jabatan, dan keanggotaan
|--------------------------------------------------------------------------
*/

it('menyediakan tiga jalur pengisian ketua poktan', function () {
    // Diperluas 2026-08-20 dari dua jalur menjadi tiga (rules.md 7a.2a).
    // Boolean `is_ketua_transmigran` hanya sanggup membedakan dua keadaan,
    // sedangkan keadaan lapangan ada tiga: kepala keluarga, anggota keluarga
    // yang mewakili, dan penduduk setempat yang bukan peserta program.
    $isi = $this->get(route('poktan.index'))->assertOk()->getContent();

    expect($isi)->toContain('name="asal_ketua"')
        // Kolom boolean lama tidak boleh tertinggal di mana pun.
        ->and($isi)->not->toContain('name="is_ketua_transmigran"')
        // Keluarga yang diwakili, terisi pada dua jalur pertama.
        ->and($isi)->toContain('name="ketua_transmigran_id"')
        // Sejak Stage B2: jalur Anggota Keluarga MEMILIH orangnya dari daftar,
        // tidak mengetik. Kolom hubungan_ketua dicabut.
        ->and($isi)->toContain('name="ketua_anggota_keluarga_id"')
        ->and($isi)->not->toContain('name="hubungan_ketua"')
        // nama_ketua / nik_ketua hanya untuk jalur Bukan Transmigran.
        ->and($isi)->toContain('name="nama_ketua"')
        ->and($isi)->toContain('name="nik_ketua"')
        // Hanya jalur non-transmigran.
        ->and($isi)->toContain('name="luas_kering_ketua"')
        ->and($isi)->toContain('name="luas_basah_ketua"');

    // Ketiga nilai enum benar-benar ditawarkan sebagai pilihan.
    foreach (AsalWakilPoktan::cases() as $asal) {
        expect($isi)->toContain('value="'.$asal->value.'"');
    }
});

it('meneruskan required, disabled, dan change milik pemanggil pilih-cari', function () {
    // Blade memperlakukan `:nama` sebagai atribut TERIKAT: nilainya dievaluasi
    // sebagai PHP lalu disimpan pada kunci TANPA titik dua. Komponen sempat
    // membacanya sebagai `:required`, sehingga selalu bernilai null.
    //
    // Akibatnya berlangsung diam-diam sejak 2026-08-17: isian pada cabang form
    // yang sedang tersembunyi tetap aktif dan ikut terkirim, dan autofill
    // telepon ketua tidak pernah berjalan sebab `@change` juga tidak pernah
    // terpasang. Tidak satu pun uji memerah, sebab seluruhnya hanya memeriksa
    // keberadaan atribut `name` (notes.md 1d.2).
    $isi = $this->get(route('poktan.index'))->assertOk()->getContent();

    $awal = strpos($isi, 'id="tambah_ketua_transmigran_id"');
    expect($awal)->not->toBeFalse();

    $tag = substr($isi, $awal, 500);

    expect($tag)->toContain(':required="dariKeluarga"')
        ->and($tag)->toContain(':disabled="! dariKeluarga"')
        // `@change` pemanggil digabung setelah `selaraskan()`, bukan menimpanya.
        ->and($tag)->toContain('selaraskan();')
        // Sejak Stage B2 pemanggil memanggil gantiKeluarga(), yang mengganti
        // keluarga, mengosongkan pilihan anggota lama, lalu isiKontak().
        ->and($tag)->toContain('gantiKeluarga(');
});

it('membatasi wakil anggota poktan pada keluarga transmigran', function () {
    // Berbeda dari ketua, anggota TIDAK boleh berasal dari penduduk setempat:
    // seluruh anggota wajib berasal dari keluarga transmigran (rules.md 7a.3).
    // Menawarkannya sebagai pilihan akan melahirkan data yang aturannya sendiri
    // melarang.
    expect(AsalWakilPoktan::nilaiAnggota())
        ->toBe(['Kepala Keluarga', 'Anggota Keluarga']);

    $sumber = file_get_contents(resource_path('views/pages/poktan/form-anggota.blade.php'));

    // Daftar pilihan dibangkitkan dari enum, bukan ditulis tangan, agar
    // penambahan nilai berikutnya tidak melewatkan pembatasan ini.
    expect($sumber)->toContain('AsalWakilPoktan::nilaiAnggota()');

    // Yang benar-benar menentukan adalah HTML terender, bukan sumber Blade:
    // komentar boleh menyebut nilai terlarang untuk menerangkan alasannya.
    $isi = $this->get(route('poktan.detail', 1))->assertOk()->getContent();

    preg_match_all('/name="asal_wakil"[^>]*value="([^"]+)"/', $isi, $cocok);

    expect($cocok[1])->not->toBeEmpty()
        ->and(array_unique($cocok[1]))->toBe(['Kepala Keluarga', 'Anggota Keluarga']);
});

it('menegaskan bahwa pencatatan anggota poktan khusus warga transmigran', function () {
    $isi = $this->get(route('poktan.detail', 1))->assertOk()->getContent();

    expect($isi)
        ->toContain('Pencatatan anggota khusus warga transmigran')
        ->toContain('Anggota transmigran aktif')
        ->toContain('Khusus warga transmigran')
        ->toContain('Anggota Transmigran (')
        ->toContain('Catatan Ruang Lingkup:')
        ->toContain('Sistem ini hanya mendata anggota kelompok tani yang merupakan warga transmigran')
        ->toContain('Anggota Kelompok Tani (Khusus Warga Transmigran)');
});

it('menautkan keanggotaan poktan ke keluarga, bukan ke kepala keluarga', function () {
    // Ditetapkan 2026-08-20: yang terdaftar adalah orang yang benar-benar
    // menggarap, dan ia tidak selalu kepala keluarga (rules.md 7a.3a). Sejak
    // Stage B2 (2026-08-28) wakil non-kepala-keluarga DIPILIH dari daftar
    // anggota keluarga, tidak diketik.
    $isi = $this->get(route('poktan.detail', 1))->assertOk()->getContent();

    expect($isi)->toContain('name="asal_wakil"')
        ->and($isi)->toContain('name="anggota_keluarga_id"')
        ->and($isi)->toContain('name="telepon_wakil"')
        // Kolom yang dulu diketik sudah dicabut.
        ->and($isi)->not->toContain('name="nama_wakil"')
        ->and($isi)->not->toContain('name="nik_wakil"')
        ->and($isi)->not->toContain('name="hubungan_dengan_kk"');

    // Data contoh wajib memuat satu wakil non-kepala-keluarga, jika tidak
    // cabang kedua tidak pernah terlihat saat peninjauan.
    $anggota = DummyData::anggotaPoktan();
    $wakilKeluarga = array_values(array_filter(
        $anggota,
        fn ($a) => $a['asal_wakil'] === AsalWakilPoktan::AnggotaKeluarga->value
    ));

    expect($wakilKeluarga)->not->toBeEmpty('data contoh wajib memuat wakil bukan kepala keluarga');

    // Identitas yang tampil dibaca dari baris anggota_keluarga yang ditunjuk,
    // bukan dari kepala keluarganya.
    $wakil = $wakilKeluarga[0];
    $anggotaKeluarga = DummyData::cariAnggotaKeluarga($wakil['anggota_keluarga_id']);

    expect($anggotaKeluarga)->not->toBeNull('anggota_keluarga_id wajib menunjuk baris yang ada');
    expect($wakil['nama'])->toBe($anggotaKeluarga['nama_lengkap'])
        ->and($wakil['nik'])->toBe($anggotaKeluarga['nik'])
        ->and($wakil['hubungan_wakil'])->toBe($anggotaKeluarga['hubungan']);

    // Namanya BUKAN nama kepala keluarganya.
    $kepala = collect(DummyData::transmigran())->firstWhere('id_transmigran', $wakil['transmigran_id']);
    expect($wakil['nama'])->not->toBe($kepala['nama_kepala_keluarga']);
});

it('memilih wakil dan ketua poktan dari daftar anggota keluarga', function () {
    // Stage B2 (2026-08-28): jalur "Anggota Keluarga" tidak lagi mengetik
    // nama dan NIK; ia memilih orangnya dari daftar anggota keluarga yang
    // bersangkutan, yang menyempit begitu keluarganya dipilih.

    // Form profil poktan (di halaman daftar): pilih ketua dari daftar.
    $index = $this->get(route('poktan.index'))->assertOk()->getContent();
    expect($index)
        ->toContain('name="ketua_anggota_keluarga_id"')
        ->toContain('x-for="a in daftarAnggotaKeluarga"')
        ->toContain('anggotaKeluargaKeluarga');

    // Form anggota poktan (di halaman rincian): pilih wakil dari daftar.
    $detail = $this->get(route('poktan.detail', 1))->assertOk()->getContent();
    expect($detail)
        ->toContain('name="anggota_keluarga_id"')
        ->toContain('x-for="a in daftarAnggotaKeluarga"');

    // Peta yang menyempitkan daftar dikelompokkan per keluarga.
    $peta = DummyData::anggotaKeluargaPerKeluarga();
    expect($peta)->toHaveKey(1);
    expect($peta[1][0])->toHaveKeys(['id', 'nama', 'hubungan']);
});

it('membaca identitas ketua poktan jalur Anggota Keluarga dari relasi', function () {
    // POKTAN TANI BERSATU (id 3) diketuai anggota keluarga PETRUS NAHAK.
    $poktan = collect(DummyData::poktan())->firstWhere('id_poktan', 3);

    expect($poktan['asal_ketua'])->toBe(AsalWakilPoktan::AnggotaKeluarga->value);
    expect($poktan['ketua_anggota_keluarga_id'])->not->toBeNull();

    // DummyData::poktan() sudah menyelesaikan identitasnya; yang tampil harus
    // sama dengan baris anggota_keluarga yang ditunjuk, bukan PETRUS NAHAK.
    $anggota = DummyData::cariAnggotaKeluarga($poktan['ketua_anggota_keluarga_id']);

    expect($poktan['nama_ketua'])->toBe($anggota['nama_lengkap'])
        ->and($poktan['nik_ketua'])->toBe($anggota['nik'])
        ->and($poktan['hubungan_ketua'])->toBe($anggota['hubungan']);

    $kepala = collect(DummyData::transmigran())->firstWhere('id_transmigran', $poktan['ketua_transmigran_id']);
    expect($poktan['nama_ketua'])->not->toBe($kepala['nama_kepala_keluarga']);

    // Halaman rincian menampilkan nama itu.
    $isi = $this->get(route('poktan.detail', 3))->assertOk()->getContent();
    expect($isi)->toContain($anggota['nama_lengkap']);
});

it('menurunkan luas lahan wakil poktan dari bidang milik keluarganya', function () {
    // Luas lahan TIDAK disimpan sebagai kolom: nilainya akan basi begitu
    // petugas membetulkan luas di modul lahan, kekeliruan yang sama dengan
    // `jumlah_anggota` yang sudah dicabut (erd.md 7.3).
    foreach (DummyData::anggotaPoktan() as $anggota) {
        $rekap = DummyData::rekapLahanKeluarga($anggota['transmigran_id']);

        expect($anggota['luas_kering'])->toBe($rekap['kering'])
            ->and($anggota['luas_basah'])->toBe($rekap['basah']);
    }

    // Hanya lahan usaha yang dihitung; pekarangan tidak berkomposisi.
    // Keluarga 1 memiliki pekarangan 0,25 ha dan lahan usaha 1,50 ha kering.
    $keluarga1 = DummyData::rekapLahanKeluarga(1);

    expect($keluarga1['total'])->toBe(1.5)
        ->and($keluarga1['kering'])->toBe(1.5)
        ->and($keluarga1['jumlah_bidang'])->toBe(1);

    // Keluarga tanpa lahan menghasilkan rekap kosong, bukan galat.
    $tanpaLahan = DummyData::rekapLahanKeluarga(999);

    expect($tanpaLahan['kering'])->toBe(0.0)
        ->and($tanpaLahan['lintang'])->toBeNull()
        ->and($tanpaLahan['jumlah_bidang'])->toBe(0);
});

it('memisahkan alasan keluar dari catatan anggota poktan', function () {
    // Kolom `keterangan` sempat dipakai dua maksud sekaligus: kamus data
    // menyebutnya catatan umum, sedangkan form melabelinya "Alasan Keluar",
    // sehingga catatan keanggotaan biasa tidak punya tempat (rules.md 7a.4e).
    $isi = $this->get(route('poktan.detail', 1))->assertOk()->getContent();

    expect($isi)->toContain('name="alasan_keluar"')
        ->and($isi)->toContain('name="keterangan"');

    // Keduanya benar-benar dipakai untuk maksud berbeda pada data contoh.
    $anggota = DummyData::anggotaPoktan();

    $adaAlasan = array_filter($anggota, fn ($a) => ! empty($a['alasan_keluar']));
    $adaCatatan = array_filter($anggota, fn ($a) => ! empty($a['keterangan']));

    expect($adaAlasan)->not->toBeEmpty()
        ->and($adaCatatan)->not->toBeEmpty();
});

it('menyimpan kontak ketua, bukan kontak kelompok, pada poktan', function () {
    // Nama kolom lama (`telepon`, `email`, `alamat_sekretariat`) menyatakan
    // kontak kelompok, padahal data contoh dan halaman rincian sejak awal
    // memperlakukannya sebagai kontak ketua. Penamaan disamakan agar dokumen
    // dan kode menyebut hal yang sama (rules.md 7a.2b).
    $isi = $this->get(route('poktan.index'))->assertOk()->getContent();

    expect($isi)->toContain('name="telepon_ketua"')
        ->and($isi)->toContain('name="email_ketua"')
        ->and($isi)->toContain('name="alamat_ketua"');
});

it('mencabut Ketua dari pilihan jabatan anggota poktan', function () {
    // Ketua ditetapkan pada profil poktan. Menyediakannya juga di daftar
    // anggota membuat satu poktan dapat memiliki dua ketua berbeda tanpa
    // penjaga apa pun (rules.md 7a.4b).
    expect(array_column(JabatanAnggotaPoktan::cases(), 'value'))
        ->toBe(['Sekretaris', 'Bendahara', 'Anggota']);

    foreach (DummyData::anggotaPoktan() as $anggota) {
        expect($anggota['jabatan'])->not->toBe('Ketua');
    }
});

it('menyediakan jalur mengubah data anggota poktan', function () {
    // Tanpa ini, status keaktifan dan tanggal keluar tidak pernah dapat diisi
    // setelah anggota tersimpan, padahal justru keduanya yang berubah
    // belakangan (rules.md 7a.4a). Sebelumnya halaman rincian hanya punya
    // tombol tambah.
    $isi = $this->get(route('poktan.detail', 1))->assertOk()->getContent();

    // Pola aksi dikirim ke Alpine lewat @js, sehingga garis miringnya lolos
    // sebagai `\/`. Dicocokkan setelah lolosan itu dibuang agar uji tidak
    // bergantung pada cara Blade menuliskan JSON.
    $tanpaLolosan = str_replace('\\/', '/', $isi);

    expect($isi)->toContain('formUbahAnggotaPoktan')
        ->and($tanpaLolosan)->toContain('/anggota-poktan/:id');

    // Rutenya benar-benar ada, bukan hanya modal yang menganga.
    expect(Route::has('anggota-poktan.perbarui'))->toBeTrue();
});

it('tidak menyediakan penghapusan anggota poktan', function () {
    // Anggota yang berhenti ditandai Sudah Keluar agar catatan penyaluran
    // saprotan di masa lalu tetap memiliki penerima yang jelas. Karena itu
    // huruf H dicabut dari matriks kewenangan agar dokumen tidak menjanjikan
    // tindakan yang memang tidak ada.
    expect(Route::has('anggota-poktan.hapus'))->toBeFalse();

    expect(DummyData::izinRole(1)['anggota_poktan'])
        ->not->toContain('hapus');

    expect(file_get_contents(base_path('agents/rules.md')))
        ->toContain('| Anggota poktan | L T U | L | L T U | L T U |');
});

it('menetapkan keanggotaan poktan dari sisi poktan saja', function () {
    // Dua sumber kebenaran untuk satu fakta selalu berakhir berbeda: petugas
    // dapat menyatakan "Ya" pada form transmigran tanpa seorang pun
    // mendaftarkannya ke kelompok mana pun (rules.md 7a.8).
    $transmigran = $this->get(route('transmigran.index'))->assertOk()->getContent();

    expect($transmigran)->not->toContain('name="status_anggota_poktan"');

    // Sebaliknya, form anggota poktan tetap memegang penetapannya.
    $poktan = $this->get(route('poktan.detail', 1))->assertOk()->getContent();

    expect($poktan)->toContain('name="transmigran_id"');
});

/*
|--------------------------------------------------------------------------
| Dokumen pendukung dan pilihan berdaftar panjang
|--------------------------------------------------------------------------
*/

it('menyediakan unggahan dokumen pada modul yang kolomnya sudah ada', function (string $jalur, string $isian) {
    // Kedelapan kolom dokumen sudah lama tercatat pada data-dictionary.md,
    // tetapi tujuh form tidak pernah punya isiannya. Akibatnya SK pembentukan
    // poktan dan berita acara penyaluran saprotan tidak dapat diunggah ke mana
    // pun, padahal justru keduanya yang diminta saat pemeriksaan.
    // Isian jamak menulis name="foto[]", sebab satu aset dapat punya beberapa
    // berkas (Putaran 14). Yang dijaga adalah ADANYA isian bagi kolom itu,
    // bukan tunggal atau jamaknya.
    $isi = $this->get($jalur)->assertOk()->getContent();

    $ada = str_contains($isi, 'name="'.$isian.'"')
        || str_contains($isi, 'name="'.$isian.'[]"');

    expect($ada)->toBeTrue("isian berkas {$isian} tidak ada pada {$jalur}");
})->with([
    ['/sp', 'dokumen_pendukung'],
    ['/sp/inventaris', 'dokumen_pendukung'],
    ['/sp/fasilitas', 'dokumen_pendukung'],
    ['/sp/infrastruktur', 'dokumen_pendukung'],
    // Infrastruktur punya dua kolom terpisah: foto merekam kondisi lapangan,
    // dokumen menyimpan berkas administratifnya.
    ['/sp/infrastruktur', 'foto'],
    // Inventaris dan fasilitas SP ikut memisahkan foto dari dokumen sejak
    // 2026-08-20, mengikuti pola infrastruktur.
    ['/sp/inventaris', 'foto'],
    ['/sp/fasilitas', 'foto'],
    ['/poktan', 'dokumen_pendukung'],
    ['/alsintan', 'dokumen_pendukung'],
    ['/saprotan', 'dokumen_pendukung'],
    // Saprotan memisahkan foto dari dokumen sejak 2026-08-22. Satu slot untuk
    // keduanya memaksa petugas memilih salah satu, dan yang mengunggah dokumen
    // setelah foto kehilangan fotonya tanpa peringatan apa pun.
    ['/saprotan', 'foto'],
    // Alsintan INDUK mendapat foto barang pada Putaran 15 (keputusan 11
    // Putaran 12), sejajar saprotan. Foto KONDISI PER UNIT tetap melekat pada
    // baris distribusi, diunggah dari halaman rincian.
    ['/alsintan', 'foto'],
    ['/alsintan/1', 'foto'],
    // SHM keluarga diunggah dari form lahan sejak 2026-09-03 (satu keluarga
    // tepat satu baris lahan menghapus risiko salinan ganda per-bidang).
    ['/lahan', 'shm'],
]);

it('mengirim unggahan lewat form yang benar-benar menerima berkas', function () {
    // Tanpa enctype multipart, berkas yang dipilih petugas tidak pernah
    // terkirim dan kegagalannya berlangsung diam-diam: form tetap tersimpan,
    // hanya berkasnya yang hilang.
    expect(file_get_contents(resource_path('views/components/sim/modal-form.blade.php')))
        ->toContain('enctype="multipart/form-data"');
});

it('menyediakan pencarian pada setiap halaman berisian pilih-cari', function () {
    // Kotak pencarian TIDAK lagi bergantung jumlah opsi (ui-spec.md 6.0a.5).
    // Ambang 8 dicabut 2026-08-20 sebab dasarnya membandingkan jumlah baris
    // DummyData, yaitu data karangan sendiri; kriterianya kini sifat sumber.
    //
    // Delapan halaman ini memuat isian bersumber tabel operasional, sehingga
    // seluruhnya wajib menampilkan kotak pencarian meski data contoh pendek.
    $halaman = [
        '/poktan', '/alsintan', '/saprotan', '/lahan',
        '/panen', '/rumah', '/penanaman', '/poktan/1',
    ];

    foreach ($halaman as $jalur) {
        $this->get($jalur)->assertOk()->assertSee('Ketik untuk menyaring daftar');
    }
});

it('tidak memakai pilih-cari pada tabel referensi kecil', function () {
    // `satuan` memang dapat ditambah Admin lewat data master, tetapi satuan
    // takaran tidak akan pernah menuntut pencarian. Pengecualian ini disebut
    // satu per satu, bukan dinyatakan sebagai ambang (ui-spec.md 6.0a.5c).
    foreach (['komoditas/form', 'saprotan/form'] as $berkas) {
        $sumber = file_get_contents(resource_path("views/pages/{$berkas}.blade.php"));

        expect($sumber)->not->toContain('pilih-cari nama="satuan_id"');
        expect($sumber)->toContain('name="satuan_id"');
    }
});

it('mencabut ambang jumlah opsi dari komponen pilih-cari', function () {
    // Ambang membuat satu komponen berperilaku dua macam tanpa dapat diduga
    // pemakainya: dropdown yang sama kadang berkotak cari, kadang tidak.
    $sumber = file_get_contents(resource_path('views/components/sim/pilih-cari.blade.php'));

    expect($sumber)->not->toContain('ambangCari')
        ->and($sumber)->not->toContain('pakaiCari');

    // Aturannya ikut dicabut dari dokumen, agar tidak ada yang memasangnya lagi
    // dengan alasan "sudah tertulis di spesifikasi".
    expect(file_get_contents(base_path('agents/ui-spec.md')))
        ->not->toContain('hanya dirender bila daftarnya mencapai 8 opsi');
});

it('memakai pilih-cari pada setiap pilihan yang bersumber tabel data', function () {
    // Ambang 8 opsi yang menentukan kapan kotak pencarian muncul, bukan halaman
    // yang memutuskannya. Karena itu komponen ini dipasang pada SELURUH pilihan
    // yang daftarnya tumbuh mengikuti data, meski data contoh masih pendek:
    // begitu data nyata masuk, pencariannya sudah ada tanpa menyunting form.
    $peta = [
        // Alsintan tanpa `transmigran_id`: pemiliknya selalu poktan sejak
        // 2026-08-22, sehingga isian transmigran pemilik sudah tidak ada.
        'alsintan/form' => ['poktan_id'],
        // Saprotan tanpa `transmigran_id`: penerimanya selalu poktan sejak
        // 2026-08-22, sehingga isian penerima perorangan sudah tidak ada.
        'saprotan/form' => ['poktan_id'],
        'penanaman/form' => ['poktan_id'],
        'panen/form' => ['penanaman_id'],
        'rumah/form' => ['transmigran_id'],
        'lahan/form' => ['transmigran_id'],
        'poktan/form' => ['ketua_transmigran_id'],
        'poktan/form-anggota' => ['transmigran_id'],
    ];

    $bolong = [];

    foreach ($peta as $berkas => $isian) {
        $sumber = file_get_contents(resource_path("views/pages/{$berkas}.blade.php"));

        foreach ($isian as $nama) {
            // Yang dilarang adalah isian bersumber tabel yang masih memakai
            // `<select>` bertulis tangan, sebab daftarnya tidak akan pernah
            // menawarkan pencarian berapa pun panjangnya.
            if (preg_match('/<select[^>]{0,200}name="'.preg_quote($nama, '/').'"/', $sumber) === 1) {
                $bolong[] = "{$berkas} -> {$nama}";

                continue;
            }

            if (! str_contains($sumber, 'nama="'.$nama.'"')) {
                $bolong[] = "{$berkas} -> {$nama} (tidak ditemukan)";
            }
        }
    }

    expect($bolong)->toBe([]);
});

it('membaca catatan tanam pada form panen dari data, bukan dari daftar tertulis', function () {
    // Isian ini sempat memuat tiga label musim harfiah sementara namanya
    // `penanaman_id`. Dua hal keliru sekaligus: nilai yang terkirim berupa
    // teks label bukan id, dan daftarnya tidak pernah bertambah ketika
    // penanaman baru didata - sehingga panen berikutnya tidak dapat dicatat.
    $sumber = file_get_contents(resource_path('views/pages/panen/form.blade.php'));

    /*
        Yang dijaga adalah pilihannya DIBANGKITKAN DARI KUMPULAN, bukan
        ditulis satu per satu.

        Sampai 2026-08-27 baris ini berbunyi `toContain('DummyData::penanaman()')`,
        yakni mengunci dari MANA datanya diambil. Ketika pengambilan data
        dipindahkan dari view ke ViewServiceProvider, uji ini memerah padahal
        tidak ada satu pun perilaku yang berubah - halaman tetap menawarkan
        penanaman yang sama persis, dan bagian kedua uji ini membuktikannya.

        Itu bentuk yang sudah tercatat pada notes.md 1g.5: uji yang menjaga
        tujuan wajib memeriksa tujuannya, bukan bentuk pemanggilannya.
    */
    expect($sumber)->not->toContain("'MT1 2026', 'MT2 2025', 'MT1 2025'")
        ->and($sumber)->toContain(':opsi="$daftarPenanaman"');

    // Nilai yang ditawarkan wajib berupa id yang benar-benar ada.
    $isi = $this->get('/panen')->assertOk()->getContent();

    $ditawarkan = 0;

    foreach (DummyData::penanaman() as $baris) {
        // Bulan tanam menggantikan label musim sejak 2026-08-22. Ia yang
        // membedakan dua penanaman komoditas yang sama oleh kelompok yang sama.
        $label = $baris['komoditas'].' - '.$baris['poktan']
            .' - '.Carbon::parse($baris['periode_tanam'].'-01')->translatedFormat('M Y');

        /*
         * HANYA YANG BELUM DIPANEN yang ditawarkan (sejak 2026-08-24).
         *
         * Satu penanaman hanya boleh satu panen, sehingga menawarkan yang
         * sudah dipanen berarti mengundang baris kedua yang tidak sah - dan
         * luasnya akan terhitung dua kali pada rekap.
         */
        if (DummyData::statusPanen($baris['id_penanaman']) === StatusPanen::BelumDipanen) {
            expect($isi)->toContain($label);
            $ditawarkan++;
        } else {
            expect($isi)->not->toContain($label);
        }
    }

    // Penjagaan terhadap ujinya sendiri: bila seluruh penanaman kebetulan
    // sudah dipanen, cabang pertama tidak pernah dijalankan.
    expect($ditawarkan)->toBeGreaterThan(0);
});

it('meniadakan seluruh jejak musim tanam', function () {
    // Fitur musim tanam dicabut 2026-08-22: poktan menanam secara fleksibel,
    // tidak mengikuti periode baku MT1/MT2 yang ditetapkan dari meja.
    //
    // Diperiksa dari sumbernya, bukan dari tampilan, sebab sisa rute atau
    // method yang tertinggal tidak selalu memerahkan halaman mana pun - ia
    // hanya menunggu sampai ada yang memanggilnya.
    expect(method_exists(DummyData::class, 'musimTanam'))->toBeFalse();

    expect(Route::has('musim-tanam'))->toBeFalse()
        ->and(Route::has('musim-tanam.detail'))->toBeFalse()
        ->and(Route::has('musim-tanam.simpan'))->toBeFalse()
        ->and(Route::has('musim-tanam.perbarui'))->toBeFalse()
        ->and(Route::has('musim-tanam.hapus'))->toBeFalse();

    $this->get('/musim-tanam')->assertNotFound();

    // Halamannya benar-benar dihapus, bukan sekadar tidak terhubung rute.
    foreach (['musim-tanam', 'form-musim-tanam', 'detail-musim-tanam'] as $berkas) {
        expect(file_exists(resource_path("views/pages/komoditas/{$berkas}.blade.php")))
            ->toBeFalse("berkas {$berkas} masih ada");
    }

    // Izinnya ikut lepas, sebab izin yatim tetap dapat dicentang Admin pada
    // halaman role dan menyiratkan fitur yang tidak ada.
    $kunci = [];
    foreach (DummyData::daftarIzin() as $kelompok) {
        $kunci = array_merge($kunci, array_column($kelompok['modul'], 'kunci'));
    }

    expect($kunci)->not->toContain('musim_tanam');

    // Menu tidak lagi menawarkannya.
    expect(json_encode(MenuHelper::definisiMenu()))->not->toContain('musim-tanam');

    // Rekap panen kehilangan pengelompokan per musim, tetapi rekap per periode
    // yang diwajibkan rules.md 8b.8 tetap ada lewat penyaringan tahun.
    $this->get('/panen/rekap/musim')->assertNotFound();
    $this->get('/panen/rekap/komoditas')->assertOk();
});

it('memakai nama Penanaman, bukan Riwayat Tanam', function () {
    // Diubah 2026-08-22 atas keberatan pemilik proyek: kata "riwayat"
    // menyiratkan catatan masa lalu, padahal barisnya justru dibuat ketika
    // penanaman baru dimulai dan panennya belum ada.
    //
    // Rename setengah jalan lebih buruk daripada tidak sama sekali: alamat
    // yang masih `/riwayat-tanam` sementara menunya berbunyi "Penanaman"
    // membuat petugas dan petugas berikutnya menyebut satu hal dengan dua
    // nama. Karena itu yang dijaga bukan hanya labelnya, melainkan seluruh
    // lapisan sekaligus.
    expect(method_exists(DummyData::class, 'penanaman'))->toBeTrue()
        ->and(method_exists(DummyData::class, 'riwayatTanam'))->toBeFalse();

    // Kunci larik ikut berganti, sebab nama kolom inilah yang kelak menjadi
    // kolom sungguhan pada Tahap 7.
    foreach (DummyData::penanaman() as $baris) {
        expect($baris)->toHaveKey('id_penanaman')
            ->and($baris)->not->toHaveKey('id_riwayat_tanam');
    }

    // Alamat dan nama rute.
    expect(Route::has('penanaman'))->toBeTrue()
        ->and(Route::has('penanaman.detail'))->toBeTrue()
        ->and(Route::has('riwayat-tanam'))->toBeFalse()
        ->and(Route::has('riwayat-tanam.detail'))->toBeFalse();

    $this->get('/penanaman')->assertOk();
    $this->get('/riwayat-tanam')->assertNotFound();

    // Berkas blade berada di FOLDERNYA SENDIRI sejak 2026-08-25.
    //
    // Sebelumnya ketiganya menumpang di folder komoditas dengan akhiran
    // `-penanaman` agar tidak bertabrakan. Penanaman adalah modul penuh
    // beralamat `/penanaman`, bukan `/komoditas/penanaman`, sehingga
    // foldernya dahulu bertentangan dengan alamatnya sendiri.
    foreach (['index', 'form', 'detail'] as $ada) {
        expect(file_exists(resource_path("views/pages/penanaman/{$ada}.blade.php")))
            ->toBeTrue("berkas penanaman/{$ada} tidak ada");
    }

    // Tidak tersisa di tempat lamanya, dan nama lamanya pun tidak kembali.
    foreach ([
        'komoditas/penanaman', 'komoditas/form-penanaman', 'komoditas/detail-penanaman',
        'komoditas/riwayat-tanam', 'komoditas/form-riwayat-tanam', 'komoditas/detail-riwayat-tanam',
        'penanaman/riwayat-tanam',
    ] as $tiada) {
        expect(file_exists(resource_path("views/pages/{$tiada}.blade.php")))
            ->toBeFalse("berkas {$tiada} masih ada");
    }

    // Kunci izin, sebab ia dipakai menyusun matriks role.
    $kunci = [];
    foreach (DummyData::daftarIzin() as $kelompok) {
        $kunci = array_merge($kunci, array_column($kelompok['modul'], 'kunci'));
    }

    expect($kunci)->toContain('penanaman')
        ->and($kunci)->not->toContain('riwayat_tanam');

    // Menu dan remah roti. Remah dibaca dari MenuHelper, sehingga label yang
    // tertinggal di satu tempat akan terbawa ke seluruh halaman modul ini.
    $menu = json_encode(MenuHelper::definisiMenu());

    expect($menu)->toContain('Penanaman')
        ->and($menu)->not->toContain('Riwayat Tanam')
        ->and($menu)->not->toContain('riwayat-tanam');

    // Panen menaut ke penanaman, dan nama isiannya ikut berganti. Bila ini
    // tertinggal, form panen mengirim kolom yang tidak dikenal peladen.
    $formPanen = file_get_contents(resource_path('views/pages/panen/form.blade.php'));

    expect($formPanen)->toContain('nama="penanaman_id"')
        ->and($formPanen)->not->toContain('riwayat_tanam_id');
});

it('mengirim nilai lewat isian bernama kolomnya, bukan lewat panel', function () {
    // Panel hanyalah antarmuka. Nilai yang terkirim berasal dari isian bernama
    // sama seperti kolomnya, sehingga Form Request pada tahap backend tidak
    // perlu tahu komponen ini ada.
    //
    // Isian itu BUKAN `type="hidden"`: peramban mengabaikan `required` pada
    // isian tersembunyi, sehingga form akan terkirim tanpa peringatan meski
    // isian wajib masih kosong. Dipakai `sr-only` agar tetap dapat divalidasi.
    $isi = $this->get('/lahan')->assertOk()->getContent();

    expect($isi)->toContain('name="transmigran_id"')
        ->and($isi)->toMatch('/name="transmigran_id"[^>]*class="sr-only"/');
});

it('menyediakan cadangan tanpa JavaScript pada pilihan berpanel', function () {
    // Sinyal di lokus tidak selalu stabil, dan form yang mustahil diisi karena
    // satu berkas gagal diunduh adalah kegagalan yang tidak perlu. Isi
    // <noscript> hanya diuraikan peramban ketika JavaScript benar-benar mati.
    $isi = $this->get('/lahan')->assertOk()->getContent();

    expect($isi)->toContain('<noscript>')
        // Cadangannya berupa select asli yang tetap membawa nama kolom.
        ->and($isi)->toMatch('/<noscript>.*?<select name="transmigran_id"/s');
});

it('menandai pilihan berpanel dengan peran ARIA yang benar', function () {
    // Tidak ada preseden combobox di repositori ini, sehingga perannya ditulis
    // eksplisit: tanpa role dan aria-expanded, pembaca layar hanya mengumumkan
    // sebuah tombol tanpa memberi tahu bahwa ada daftar yang dapat dibuka
    // (ANTISLOP-ID R-32).
    $isi = $this->get('/lahan')->assertOk()->getContent();

    expect($isi)->toContain('role="combobox"')
        ->and($isi)->toContain('role="listbox"')
        ->and($isi)->toContain('role="option"')
        ->and($isi)->toContain('aria-haspopup="listbox"');
});

it('mendefinisikan x-cloak secara global', function () {
    // Sebelum 2026-08-17 aturan ini hanya ada inline di components/ui/modal,
    // dan komponen itu tidak dipakai satu halaman pun. Akibatnya 96 pemakaian
    // x-cloak di repositori tidak berfungsi: panel dan modal sempat berkedip
    // terlihat setiap kali halaman dimuat.
    expect(file_get_contents(resource_path('css/app.css')))
        ->toMatch('/\[x-cloak\]\s*\{/');
});

it('tidak menyentuh document.body sebelum DOM siap', function () {
    // Skrip tema berjalan di dalam <head>, saat document.body belum ada.
    // Versi sebelumnya melempar "Cannot read properties of null" pada setiap
    // pemuatan halaman; galatnya tidak menghentikan apa pun, tetapi membanjiri
    // konsol dan menyamarkan galat lain yang benar-benar penting.
    foreach (['layouts/app', 'layouts/fullscreen-layout'] as $berkas) {
        $sumber = file_get_contents(resource_path("views/{$berkas}.blade.php"));

        // Ambil hanya skrip anti-kedip yang berada di dalam <head>.
        preg_match('/const savedTheme.*?\}\)\(\);/s', $sumber, $cocok);

        expect($cocok)->not->toBeEmpty("Skrip tema tidak ditemukan pada {$berkas}");
        expect($cocok[0])->toContain('DOMContentLoaded');
    }
});

/*
|--------------------------------------------------------------------------
| Bidang lahan: peruntukan, status hak, dan dokumen
|--------------------------------------------------------------------------
*/

it('menyediakan unggahan SHM dan status sertifikat pada form lahan, HPL tetap bacaan', function () {
    // Langkah 3 form lahan dahulu meminta petugas MEMILIH jenis dokumen
    // (HPL/SHM) per bidang lalu mengunggah berkasnya - bangkai dari sebelum
    // Putaran 12 (tabel `dokumen_lahan` dicabut, kolom tidak ada).
    //
    // Sejak satu keluarga tepat satu baris lahan (Putaran 15), risiko salinan
    // sertifikat ganda per-bidang lenyap. Pemilik proyek menetapkan form lahan
    // sebagai tempat kanonis SHM dan status sertifikat (rules.md 7.6a,
    // 2026-09-03). Berkasnya tetap tersimpan pada `transmigran_berkas` peran
    // `shm`, statusnya pada `transmigran.status_sertifikat`.
    $sumber = file_get_contents(resource_path('views/pages/lahan/form.blade.php'));

    // Isian dokumen per bidang tetap dicabut seluruhnya.
    expect($sumber)->not->toContain('name="jenis_dokumen"')
        ->and($sumber)->not->toContain('name="file_dokumen"')
        ->and($sumber)->not->toContain('name="nomor_dokumen"')
        ->and($sumber)->not->toContain('name="tanggal_terbit"');

    $isi = $this->get(route('lahan.index'))->assertOk()->getContent();

    // SHM diunggah, status sertifikat dipilih - keduanya dari form lahan.
    $adaShm = str_contains($isi, 'name="shm"') || str_contains($isi, 'name="shm[]"');
    expect($adaShm)->toBeTrue('form lahan wajib punya unggahan SHM');
    expect($isi)->toContain('name="status_sertifikat"');

    // HPL tetap BACAAN, diunggah dari Data Kawasan.
    expect($isi)->toContain('Alas hak kawasan (HPL)');

    // Status sertifikat tidak lagi di form transmigran (pindah ke form lahan).
    expect(file_get_contents(resource_path('views/pages/transmigran/form.blade.php')))
        ->not->toContain('name="status_sertifikat"');
});

it('mencatat lahan satu baris per KK dengan dua pasang koordinat', function () {
    // SATU BARIS = SATU KELUARGA (Putaran 15). Pekarangan dan lahan usaha
    // adalah KOLOM pada baris yang sama; koordinatnya TETAP DUA PASANG sebab
    // kedua bidang berada di tempat berbeda.
    $isi = $this->get(route('lahan.index'))->assertOk()->getContent();

    foreach ([
        'luas_pekarangan', 'luas_usaha',
        'lintang_pekarangan', 'bujur_pekarangan',
        'lintang_usaha', 'bujur_usaha',
    ] as $isian) {
        expect($isi)->toContain('name="'.$isian.'"');
    }

    // Enum peruntukan dicabut seluruhnya; kedua bidang kini kolom.
    expect(enum_exists('App\Enums\PeruntukanLahan'))->toBeFalse();

    // Nama kolom lama tidak boleh tertinggal di FORM (yang di-include di sini).
    $form = file_get_contents(resource_path('views/pages/lahan/form.blade.php'));
    expect($form)->not->toContain('name="peruntukan_lahan"')
        ->and($form)->not->toContain('name="jenis_lahan"')
        ->and($form)->not->toContain('name="status_kepemilikan"')
        ->and($form)->not->toContain('PeruntukanLahan');

    // Penyaring peruntukan tetap ada di HALAMAN DAFTAR (bukan form): ia kini
    // menanyakan "punya bidang ini?" bukan "barisnya berperuntukan ini".
    expect($isi)->toContain('name="peruntukan_lahan"')
        ->and($isi)->not->toContain('Lahan Usaha I<')
        ->and($isi)->not->toContain('Lahan Usaha II');
});

it('mencabut status hak atas tanah dari seluruh modul lahan', function () {
    // Dicabut 2026-08-20 atas keputusan pemilik proyek. Penghapusannya
    // MENYELURUH, bukan hanya dari form: isian ini satu-satunya jalan mengisi
    // kolomnya, sehingga menyisakan tampilannya di halaman rincian akan
    // membuat keterangan yang tidak pernah terisi - kontrol mati yang dilarang
    // R-26, dan pola yang sama dipakai saat mencabut batas wilayah SP.
    $isiDaftar = $this->get(route('lahan.index'))->assertOk()->getContent();
    $isiRincian = $this->get(route('lahan.detail', 1))->assertOk()->getContent();

    foreach ([$isiDaftar, $isiRincian] as $isi) {
        expect($isi)->not->toContain('name="status_hak"')
            ->and($isi)->not->toContain('Status Hak Atas Tanah')
            ->and($isi)->not->toContain('Belum Bersertifikat')
            // Nama kolom lama yang sudah pernah dicabut sebelumnya.
            ->and($isi)->not->toContain('name="status_kepemilikan"');
    }
});

it('menampilkan komposisi kering dan basah lahan usaha, bukan kategori tunggal', function () {
    // Satu bidang usaha dapat digarap sebagian kering dan sebagian basah
    // sekaligus (rules.md 7.5). Kolom enum lama memaksa memilih salah satu,
    // sehingga separuh luasnya hilang dari rekap tanpa ada yang menyadarinya.
    $isiDaftar = $this->get(route('lahan.index'))->assertOk()->getContent();

    expect($isiDaftar)->toContain('name="luas_kering"')
        ->and($isiDaftar)->toContain('name="luas_basah"')
        // Enumnya dicabut seluruhnya, bukan sekadar disembunyikan.
        ->and($isiDaftar)->not->toContain('name="kategori_lahan" ')
        ->and(enum_exists('App\Enums\KategoriLahan'))->toBeFalse();

    // Lahan usaha campuran wajib terbaca sebagai satu bidang berkomposisi.
    // LH-002 milik MARIA DA COSTA adalah 1,25 ha kering + 0,75 ha basah.
    $this->get(route('lahan.detail', 2))
        ->assertOk()
        ->assertSee('Lahan kering')
        ->assertSee('Lahan basah')
        ->assertSee('1,25 ha')
        ->assertSee('0,75 ha');
});

it('menyaring lahan usaha yang memiliki bagian basah, bukan yang seluruhnya basah', function () {
    // Inti perubahan 2026-08-20: penyaring menanyakan "punya bagian basah?".
    // Lahan usaha campuran wajib muncul pada kedua penyaring sekaligus, dan
    // itulah yang membedakannya dari kolom enum lama.
    $basah = $this->get(route('lahan.index', ['kategori_lahan' => 'basah']))
        ->assertOk()
        // LH-002 campuran, LH-004 seluruhnya basah.
        ->assertSee('LH-002')
        ->assertSee('LH-004')
        // LH-003 seluruhnya kering, tidak boleh muncul.
        ->assertDontSee('LH-003');

    expect($basah)->not->toBeNull();

    $this->get(route('lahan.index', ['kategori_lahan' => 'kering']))
        ->assertOk()
        ->assertSee('LH-002')
        ->assertSee('LH-003')
        ->assertDontSee('LH-004');
});

it('menjumlahkan luas lahan usaha dari kolomnya, bukan mencocokkan teks peruntukan', function () {
    // Sejak satu baris per KK, luas usaha dibaca dari kolom `luas_usaha`
    // langsung, bukan dengan menyaring baris menurut nilai teks peruntukan.
    $luasUsaha = array_sum(array_map(
        fn ($l) => (float) ($l['luas_usaha'] ?? 0),
        DummyData::lahan()
    ));

    // 1,50 + 2,00 + 1,25 + 0,75 = 5,50 hektare pada data contoh.
    expect($luasUsaha)->toBe(5.5);

    // Total luas seluruh lahan tetap 6,0 ha sebelum dan sesudah penggabungan.
    $luasPekarangan = array_sum(array_map(
        fn ($l) => (float) ($l['luas_pekarangan'] ?? 0),
        DummyData::lahan()
    ));
    expect(round($luasPekarangan + $luasUsaha, 2))->toBe(6.0);

    $this->get(route('lahan.index'))
        ->assertOk()
        ->assertSee(number_format($luasUsaha, 2, ',', '.'));
});

it('menghidupkan kembali batas wilayah SP secara lengkap, bukan sebagian', function () {
    // Keempat kolom `batas_*` dicabut 2026-08-18 (isinya naratif, tak dipakai
    // hitungan/peta), lalu DIHIDUPKAN KEMBALI 2026-08-28 (Rombongan C) sebab
    // Bab II Laporan Monografi memuat Batas-Batas Alam.
    //
    // Uji ini menjaga agar kebangkitannya utuh: form tanpa kolom, atau kolom
    // tanpa form, atau kamus tanpa keduanya, sama-sama menyesatkan.
    $arah = ['batas_utara', 'batas_timur', 'batas_selatan', 'batas_barat'];

    $formSp = $this->get(route('sp.index'))->assertOk()->getContent();
    $rincianSp = $this->get(route('sp.detail', 1))->assertOk()->getContent();

    foreach ($arah as $kolom) {
        expect($formSp)->toContain('name="'.$kolom.'"');
    }
    // Rincian menampilkan nilainya (SP 1 dari berkas monografi).
    expect($rincianSp)->toContain('Batas Wilayah')->toContain('Desa Tesa');

    // Data contoh memuat keempat kunci pada setiap SP.
    foreach (DummyData::satuanPermukiman() as $sp) {
        foreach ($arah as $kolom) {
            expect($sp)->toHaveKey($kolom);
        }
    }

    // Kamus data mencatatnya kembali.
    expect(file_get_contents(base_path('agents/data-dictionary.md')))
        ->toContain('`batas_utara`');
});

it('menyediakan tempat tampil bagi setiap field Keadaan Wilayah SP', function () {
    // Penjaga 1f untuk field baru Rombongan C: tiap isian yang diisi form SP
    // wajib punya tempat tampil di halaman rincian SP.
    $form = $this->get(route('sp.index'))->assertOk()->getContent();
    $rincian = $this->get(route('sp.detail', 1))->assertOk()->getContent();

    // Setiap isian Keadaan Wilayah pada form terender wajib punya name=.
    $isianWajib = [
        'lintang_utara', 'bujur_timur', 'jarak_ke_provinsi_km',
        'batas_utara', 'batas_barat', 'nomor_sk_pencadangan', 'tanggal_sk_pencadangan',
        'pola_permukiman', 'tingkat_kesuburan_tanah', 'ph_tanah_min', 'ph_tanah_maks',
        'bentuk_wilayah', 'kemiringan_min_persen', 'curah_hujan_tahunan_mm',
        'curah_hujan_bulan_maks_mm', 'suhu_rata_c', 'angin_rata_knot',
        'penyinaran_rata_persen', 'sumber_air_bersih', 'sumber_air_pertanian',
    ];
    foreach ($isianWajib as $nama) {
        expect(str_contains($form, 'name="'.$nama.'"'))->toBeTrue("isian {$nama} tidak ada di form SP");
    }

    // Blok tampilnya di halaman rincian, per kelompok Bab II.
    $wajibTampil = [
        'Letak astronomis', 'Jarak ke Ibu Kota', 'Batas Wilayah', 'SK Pencadangan',
        'Pola permukiman', 'kesuburan tanah', 'pH tanah', 'Bentuk wilayah',
        'Kemiringan lereng', 'Curah hujan', 'Suhu udara', 'Kecepatan angin',
        'Penyinaran matahari', 'Sumber air bersih', 'Sumber air pertanian',
    ];
    foreach ($wajibTampil as $label) {
        expect($rincian)->toContain($label);
    }

    // SP 1 memakai angka persis dari berkas Monografi Kapitan Meo, dan
    // nilainya benar-benar dirender.
    $sp1 = collect(DummyData::satuanPermukiman())->firstWhere('id_satuan_permukiman', 1);
    expect($sp1['curah_hujan_tahunan_mm'])->toBe(1607.18)
        ->and($sp1['suhu_rata_c'])->toBe(27.7)
        ->and($sp1['nomor_sk_pencadangan'])->toBe('79/HK/2018');
    expect($rincian)->toContain('79/HK/2018')->toContain('Desa Tesa');
});

it('mengunci enum Keadaan Wilayah SP', function () {
    expect(PolaPermukiman::nilai())
        ->toBe(['Konsentris', 'Papan Catur', 'Linear', 'Menyebar']);
    expect(TingkatKesuburanTanah::nilai())
        ->toBe(['Subur', 'Sedang', 'Kurang Subur']);
    expect(BentukWilayah::nilai())
        ->toBe(['Datar', 'Bergelombang', 'Berbukit', 'Bergunung']);
});

it('mendata rute aksesibilitas SP sebagai daftar dinamis dengan tempat tampil', function () {
    // Stage C2 (2026-08-28): Tabel 2.1 Monografi jadi daftar rute per SP.
    $semua = DummyData::ruteAksesibilitasSp();
    expect($semua)->not->toBeEmpty();

    $sp1 = DummyData::ruteAksesibilitasSp(1);
    expect($sp1)->not->toBeEmpty();
    foreach ($sp1 as $r) {
        expect($r['satuan_permukiman_id'])->toBe(1);
    }
    // SP 1 dari Tabel 2.1 berkas monografi.
    expect(collect($sp1)->pluck('rute')->implode(' | '))->toContain('Kupang');

    // Form SP: repeater rute.
    $form = $this->get(route('sp.index'))->assertOk()->getContent();
    expect($form)
        ->toContain('x-for="(r, i) in rute"')
        ->toContain('tambahRute()')
        ->toContain('hapusRute(i)')
        ->toContain('rute_aksesibilitas[${i}][rute]');

    // Halaman rincian SP menampilkan tabel rute berisi (dengan caption).
    $rincian = $this->get(route('sp.detail', 1))->assertOk()->getContent();
    expect($rincian)
        ->toContain('Rute Aksesibilitas')
        ->toContain('Cara pencapaian menuju')
        ->toContain($sp1[0]['rute']);
});

it('menyusun Bab II Keadaan Wilayah per SP pada Laporan Monografi SP', function () {
    // Stage C3 (2026-08-28): Laporan Monografi SP kini merender Bab II penuh
    // per SP, bukan hanya satu baris indikator.
    $data = LaporanData::monografiSp();

    expect($data)->toHaveKeys(['baris', 'monografi']);
    expect($data['monografi'])->toHaveCount(count(DummyData::satuanPermukiman()));

    $kapitanMeo = collect($data['monografi'])->firstWhere('kode', 'SP-01');
    expect($kapitanMeo)->not->toBeNull();
    expect($kapitanMeo['ada_isi'])->toBeTrue();
    expect(array_keys($kapitanMeo['kelompok']))
        ->toBe(['Letak', 'Batas Wilayah', 'Luas dan Bentuk', 'Tanah dan Topografi', 'Iklim', 'Sumberdaya Air']);
    expect($kapitanMeo['kelompok']['Batas Wilayah']['Sebelah Utara'])->toBe('Desa Tesa');
    expect($kapitanMeo['kelompok']['Iklim']['Curah hujan rata-rata per tahun'])->toBe('1.607,18 mm');
    expect($kapitanMeo['rute'])->not->toBeEmpty();

    // Nilai kosong tetap dibawa sebagai null, bukan dibuang.
    expect($kapitanMeo['kelompok']['Tanah dan Topografi'])->toHaveKey('pH tanah');

    $html = $this->get(route('laporan.monografi-sp'))->assertOk()->getContent();
    expect($html)
        ->toContain('Keadaan Wilayah')
        ->toContain('Batas-Batas Alam')
        ->toContain('Aksesibilitas')
        ->toContain('1.607,18 mm')
        ->toContain('Cara pencapaian menuju SP Kapitan Meo');

    // ANTISLOP-ID R-02: tanpa em dash.
    expect(str_contains($html, "\xE2\x80\x94"))->toBeFalse('Laporan Monografi SP memuat em dash');
});

/*
|--------------------------------------------------------------------------
| Komoditas unggulan: penandaan manusia, bukan hitungan
|--------------------------------------------------------------------------
*/

it('menandai komoditas unggulan lewat penandaan petugas, bukan hitungan volume', function () {
    // rules.md 8.1 menyebut unggulan sebagai yang "disebut dalam proposal",
    // dan 8.3 memakai kata "penandaan". Jagung sudah ditandai unggulan sebelum
    // satu baris panen pun tercatat, sehingga menghitungnya dari volume berarti
    // menjawab pertanyaan yang berbeda.
    //
    // Menghitung otomatis juga menutup kasus yang justru paling perlu
    // ditandai: komoditas prioritas program yang volumenya masih kecil.
    $isi = $this->get(route('komoditas.index'))->assertOk()->getContent();

    expect($isi)->toContain('name="is_unggulan"')
        ->and($isi)->toContain('Ditetapkan menurut proposal atau kebijakan dinas');
});

it('menampilkan volume tercatat sebagai bahan pertimbangan penandaan', function () {
    // Petugas tetap memutuskan, tetapi tidak lagi menebak: keadaan volume
    // ditampilkan di samping centang.
    $this->get(route('komoditas.detail', 1))
        ->assertOk()
        ->assertSee('Volume tercatat')
        // Jagung volumenya terbesar, sehingga tidak diperingatkan.
        ->assertDontSee('bukan yang volumenya terbesar');
});

it('memperingatkan bila yang ditandai unggulan bukan volume terbesar', function () {
    // Peringatan, bukan penolakan. Unggulan bervolume kecil adalah keadaan
    // yang sah; yang tidak boleh adalah petugas menandainya tanpa menyadari.
    $this->get(route('komoditas.detail', 3))
        ->assertOk()
        ->assertSee('bukan yang volumenya terbesar');
});

it('memilih komoditas utama dashboard menurut nilai, bukan urutan larik', function () {
    /*
        `array_key_first()` sempat dipakai dan kebetulan benar hanya karena
        sebaranKomoditas() ditulis terurut. Begitu urutannya berubah, kartu ini
        menampilkan komoditas yang keliru tanpa ada yang menyadarinya.

        Diperiksa pada HALAMAN TERENDER, bukan pada berkas sumbernya.

        Sampai 2026-08-27 uji ini membaca `dashboard/index.blade.php` dan
        mencari string `max($sebaranKomoditas)`, yakni mengunci berkas MANA
        yang menghitungnya. Ketika perhitungannya pindah dari view ke rute,
        uji memerah padahal kartunya menampilkan komoditas yang sama persis.
        Lihat notes.md 1g.5.
    */
    $sebaran = DummyData::sebaranKomoditas();
    $terbesar = array_search(max($sebaran), $sebaran, true);

    /*
        Pemeriksaan sumber DIPERTAHANKAN, dan alasannya penting.

        Data contoh saat ini tersusun menurun, sehingga `array_key_first()` dan
        `max()` menghasilkan jawaban yang sama persis. Justru itu sebabnya
        kekeliruan aslinya tidak terlihat, dan itu pula sebabnya halaman
        terender TIDAK dapat membedakan keduanya. Di sini bentuk kodenyalah
        yang menjadi satu-satunya pembeda.

        Yang diperbaiki hanya CAKUPANNYA: sampai 2026-08-27 uji ini hanya
        membaca `dashboard/index.blade.php`, sehingga memerah begitu
        perhitungannya pindah ke rute padahal kartunya tidak berubah. Kini
        disisir dari seluruh tempat yang mungkin memuatnya.
    */
    $sumber = file_get_contents(base_path('routes/web.php'))
        .file_get_contents(base_path('routes/internal.php'))
        .file_get_contents(resource_path('views/pages/dashboard/index.blade.php'));

    expect($sumber)->toContain('max($sebaranKomoditas)')
        ->and($sumber)->not->toContain('array_key_first($sebaranKomoditas)');

    // Lalu hasilnya, dibaca dari kartu yang benar-benar dirender.
    $isi = $this->get(route('beranda'))->assertOk()->getContent();

    expect($isi)->toContain('Komoditas Utama')
        ->and($isi)->toContain($terbesar)
        ->and($isi)->toContain(number_format($sebaran[$terbesar], 1, ',', '.').' ton dipanen');
});

it('menyamakan nama komoditas pada sebaran dengan data master', function () {
    // Ketidakcocokan penamaan memaksa tiap pemakainya menormalkan huruf
    // sendiri-sendiri, dan salah satunya keliru: `/komoditas` memakai
    // `ucfirst(mb_strtolower(...))` yang hanya mengapitalkan huruf PERTAMA,
    // sehingga KACANG TANAH dicari sebagai `Kacang tanah` dan tidak ketemu.
    $master = array_column(DummyData::komoditas(), 'nama');

    foreach (array_keys(DummyData::sebaranKomoditas()) as $nama) {
        // `Lainnya` penampung sisa, bukan komoditas, sehingga memang tidak
        // ada pada data master.
        if ($nama === 'Lainnya') {
            continue;
        }

        expect($master)->toContain($nama);
    }
});

it('menampilkan volume tercatat untuk setiap komoditas, termasuk nama dua kata', function () {
    // KACANG TANAH dan UBI KAYU sempat menampilkan tanda hubung seolah belum
    // pernah panen, padahal keduanya tercatat 118,4 dan 68,2 ton. Yang gagal
    // hanya nama dua kata; nama satu kata kebetulan berhasil, sehingga
    // kekeliruannya tidak terlihat pada pemeriksaan sepintas.
    //
    // KEGAGALANNYA SENYAP: tanda hubung terbaca sebagai "belum ada panen",
    // padahal artinya "kodenya tidak menemukan datanya". Dua keadaan berbeda
    // ditampilkan sama, persis pola yang sudah tercatat pada notes.md 1b.6a.
    $isi = $this->get(route('komoditas.index'))->assertOk()->getContent();
    $sebaran = DummyData::sebaranKomoditas();

    $duaKata = 0;

    foreach (DummyData::komoditas() as $k) {
        expect($sebaran)->toHaveKey($k['nama']);

        // Angkanya benar-benar terender, bukan sekadar ada di larik.
        expect($isi)->toContain(number_format($sebaran[$k['nama']], 1, ',', '.').' ton');

        if (str_contains($k['nama'], ' ')) {
            $duaKata++;
        }
    }

    // Penjagaan terhadap ujinya sendiri: bila kelak seluruh komoditas
    // bernama satu kata, uji di atas tidak lagi menguji apa pun.
    expect($duaKata)->toBeGreaterThan(0);
});

/*
|--------------------------------------------------------------------------
| Indikator produksi kawasan pada dashboard
|--------------------------------------------------------------------------
*/

it('menjaga agregat produksi kawasan memenuhi kedua identitas aritmetika', function () {
    // Identitas yang sama seperti pada tabel transaksi (rules.md 9.9 dan
    // 9.11), tetapi pada tingkat kawasan. Tanpa penjagaan ini, dua kartu yang
    // bersebelahan dapat saling membantah tanpa ada yang menegur.
    $r = DummyData::ringkasanDashboard();

    // realisasi tanam = hasil panen + puso + belum dipanen
    $jumlah = $r['hasil_panen_ha'] + $r['puso_ha'] + $r['belum_dipanen_ha'];

    expect(abs($jumlah - $r['realisasi_tanam_ha']))->toBeLessThan(0.01);

    // volume panen = hasil panen x produktivitas
    $produksi = $r['hasil_panen_ha'] * $r['produktivitas_ton_ha'];

    expect(abs($produksi - $r['volume_panen_ton']))->toBeLessThan(0.5);
});

it('menjaga luas tanam kawasan tidak melebihi lahan yang tergarap', function () {
    // Realisasi tanam yang melampaui luas lahan mustahil, dan angka semacam
    // itu akan lolos tanpa terasa sebab keduanya sama-sama ratusan hektare.
    $r = DummyData::ringkasanDashboard();

    expect($r['realisasi_tanam_ha'])->toBeLessThan($r['luas_lahan_total']);

    // Puso pun tidak dapat melebihi luas yang ditanam.
    expect($r['puso_ha'])->toBeLessThan($r['realisasi_tanam_ha']);
});

it('menampilkan keempat indikator produksi kawasan pada dashboard', function () {
    // Keempatnya lahir dari perombakan menu Pertanian dan sebelumnya tidak
    // terwakili sama sekali: dashboard hanya menyebut volume panen, sehingga
    // pembaca tahu berapa ton dihasilkan tetapi tidak tahu dari berapa
    // hektare, berapa yang gagal, dan berapa yang masih menunggu.
    $r = DummyData::ringkasanDashboard();
    $isi = $this->get(route('beranda'))->assertOk()->getContent();

    expect($isi)->toContain('Realisasi Tanam')
        ->and($isi)->toContain('Puso')
        ->and($isi)->toContain('Produktivitas Rata-rata');

    // Angkanya benar-benar terender, bukan hanya labelnya.
    foreach (['realisasi_tanam_ha', 'hasil_panen_ha', 'puso_ha'] as $kunci) {
        expect($isi)->toContain(number_format($r[$kunci], 2, ',', '.'));
    }

    expect($isi)->toContain(number_format($r['produktivitas_ton_ha'], 3, ',', '.'));
});

it('memakai koma sebagai pemisah desimal pada porsi produksi', function () {
    // `round()` menghasilkan "19.5" bertitik, sedangkan seluruh angka lain di
    // halaman ini memakai koma. Satu angka bertitik di antara puluhan angka
    // berkoma terbaca sebagai kekeliruan cetak.
    $isi = $this->get(route('beranda'))->assertOk()->getContent();

    expect($isi)->toContain('19,5% dari')
        ->and($isi)->not->toContain('19.5% dari');
});

it('menyebut tahun pada kartu volume panen, bukan mengatakan tahun ini', function () {
    // Angkanya tetap dan kebetulan cocok hanya karena deret berakhir pada
    // tahun berjalan. Begitu tahun berganti, label "Tahun Ini" berbohong
    // tanpa ada yang menegur - sifat cacat yang sama dengan baris total rekap
    // yang sudah diperbaiki.
    $deret = DummyData::deretTahunan();
    $tahunTerakhir = end($deret['tahun']);

    $isi = $this->get(route('beranda'))->assertOk()->getContent();

    expect($isi)->toContain('Volume Panen '.$tahunTerakhir)
        ->and($isi)->not->toContain('Volume Panen Tahun Ini');
});

it('memisahkan agregat kawasan dari transaksi panen contoh', function () {
    // Keduanya menjawab pertanyaan berbeda dan tidak boleh diturunkan satu
    // dari yang lain: sebaranKomoditas() adalah agregat kawasan setahun,
    // hasilPanen() hanya beberapa transaksi contoh untuk menguji tampilan.
    //
    // Uji ini menjaga agar keduanya tidak "diperbaiki" menjadi konsisten,
    // sebab menyamakannya akan membuat dashboard menampilkan belasan ton untuk
    // kawasan berisi ribuan keluarga.
    $agregat = array_sum(DummyData::sebaranKomoditas());

    $transaksi = 0.0;
    foreach (DummyData::hasilPanen() as $panen) {
        $transaksi += DummyData::keTon((float) $panen['produksi'], $panen['satuan']);
    }

    expect($agregat)->toBeGreaterThan($transaksi * 10);

    // Empat angka dashboard wajib tetap saling konsisten dengan agregat.
    $ringkasan = DummyData::ringkasanDashboard();
    $totalSp = array_sum(array_column(DummyData::rekapPerSp(), 'volume_panen'));
    $deret = DummyData::deretTahunan();

    expect(round($ringkasan['volume_panen_ton'], 1))->toBe(round($agregat, 1))
        ->and(round($totalSp, 1))->toBe(round($agregat, 1))
        ->and(round((float) end($deret['volume_panen']), 1))->toBe(round($agregat, 1));
});

/*
|--------------------------------------------------------------------------
| Halaman rincian Inventaris dan Fasilitas SP
|--------------------------------------------------------------------------
*/

it('membuka halaman rincian inventaris dan fasilitas SP', function (string $jalur) {
    $this->get($jalur)->assertOk();
})->with([
    '/sp/inventaris/1',
    '/sp/fasilitas/1',
]);

it('menolak rincian aset SP yang tidak ada', function (string $jalur) {
    $this->get($jalur)->assertNotFound();
})->with([
    '/sp/inventaris/999',
    '/sp/fasilitas/999',
]);

it('menyediakan tombol rincian pada daftar inventaris dan fasilitas SP', function () {
    expect($this->get('/sp/inventaris')->getContent())->toContain('/sp/inventaris/1')
        ->and($this->get('/sp/fasilitas')->getContent())->toContain('/sp/fasilitas/1');
});

/*
|--------------------------------------------------------------------------
| Privasi halaman lacak publik
|--------------------------------------------------------------------------
*/

it('tidak menampilkan data aset pada halaman lacak publik', function () {
    // Halaman lacak terbuka tanpa login dan hanya berbekal nomor pengaduan,
    // sehingga menampilkan "Rumah A-12" atau "LU-001" berarti menyiarkan
    // alamat maupun lahan keluarga tertentu kepada siapa pun yang mengetahui
    // nomornya. rules.md 10b poin 1c membatasinya pada status, tanggal, dan
    // catatan penanganan saja.
    $isi = $this->get('/lacak-pengaduan/'.DummyData::pengaduan()[0]['nomor_pengaduan'])->getContent();

    expect($isi)->not->toContain('SALURAN IRIGASI BLOK A')
        ->and($isi)->not->toContain('LU-001')
        ->and($isi)->not->toContain('Objek yang diadukan');
});

/*
|--------------------------------------------------------------------------
| Bidang penanganan pengaduan
|--------------------------------------------------------------------------
*/

it('menyediakan filter bidang pada daftar pengaduan', function () {
    // rules.md 10b poin 7e. Paling berguna bagi Admin dan Dinas Transmigrasi
    // yang daftarnya memuat laporan kedua dinas sekaligus.
    $isi = $this->get('/pengaduan')->getContent();

    expect($isi)->toContain('name="bidang"')
        ->and($isi)->toContain('Semua bidang')
        ->and($isi)->toContain('Belum ditentukan');
});

it('menyaring pengaduan menurut bidangnya', function () {
    // Nomornya dibaca lewat bidangnya sendiri, bukan diketik: menyaring satu
    // bidang wajib menyingkirkan bidang yang lain, dan itulah yang dijaga.
    $nomor = fn ($bidang) => collect(DummyData::pengaduan())
        ->firstWhere('bidang', $bidang)['nomor_pengaduan'];

    $pertanian = $this->get('/pengaduan?bidang=Pertanian')->getContent();

    expect($pertanian)->toContain($nomor('Pertanian'))
        ->and($pertanian)->not->toContain($nomor('Ketransmigrasian'));
});

it('menyaring pengaduan yang bidangnya belum ditetapkan', function () {
    // Inilah antrean penyaringan awal Admin dan Dinas Transmigrasi.
    $nomor = fn ($bidang) => collect(DummyData::pengaduan())
        ->firstWhere('bidang', $bidang)['nomor_pengaduan'];

    $belum = $this->get('/pengaduan?bidang=belum')->getContent();

    expect($belum)->toContain($nomor(null))
        ->and($belum)->not->toContain($nomor('Pertanian'));
});

it('menyatakan bidang kosong sebagai keterangan, bukan sel hampa', function () {
    // Sel hampa terbaca sebagai data gagal termuat, padahal yang sebenarnya
    // terjadi adalah laporan belum disaring petugas.
    expect($this->get('/pengaduan')->getContent())
        ->toContain('Belum ditentukan');
});

it('menyediakan isian bidang yang dapat ditimpa petugas', function () {
    // rules.md 10b poin 7c: bidang berupa pilihan, bukan tampilan baca-saja,
    // sebab penentuan dinas bergantung isi laporan yang tidak selalu terbaca
    // dari kategori.
    $isi = $this->get('/pengaduan')->getContent();

    expect($isi)->toContain('id="tambah_bidang"')
        ->and($isi)->toContain('gantiKategori(')
        ->and($isi)->toContain('disentuh');
});

it('menyediakan penetapan bidang pada modal penanganan', function () {
    // Laporan kanal publik berkategori netral tiba tanpa bidang, sehingga
    // petugas harus dapat menetapkannya saat meninjau tanpa membuka modal lain.
    expect($this->get('/pengaduan/4')->getContent())
        ->toContain('id="penanganan_bidang"');
});

it('mengisi bidang seluruh pengaduan yang sudah diproses', function () {
    // rules.md 10b poin 7b: wajib terisi sebelum status maju ke Diproses.
    $sudahLewat = [StatusPengaduan::Diproses->value, StatusPengaduan::Selesai->value];

    foreach (DummyData::pengaduan() as $baris) {
        if (! in_array($baris['status'], $sudahLewat, true)) {
            continue;
        }

        expect(! empty($baris['bidang']))
            ->toBeTrue("Pengaduan {$baris['nomor_pengaduan']} berstatus {$baris['status']} tanpa bidang");
    }
});

it('menyisakan contoh pengaduan yang bidangnya belum ditetapkan', function () {
    // Keadaan ini pasti muncul di lapangan saat laporan baru masuk, sehingga
    // wajib ikut terlihat pada data contoh.
    $belum = array_filter(DummyData::pengaduan(), fn ($p) => empty($p['bidang']));

    expect($belum)->not->toBeEmpty();

    foreach ($belum as $baris) {
        expect($baris['status'])->toBe(StatusPengaduan::MenungguDiterima->value);
    }
});

it('menyelaraskan bidang data contoh dengan peta kategori', function () {
    // Kategori netral boleh berbidang apa pun sebab ditetapkan petugas;
    // kategori bermuatan wajib cocok dengan turunannya.
    foreach (DummyData::pengaduan() as $baris) {
        $bawaan = BidangPengaduan::dariKategori($baris['kategori']);

        if ($bawaan === null) {
            continue;
        }

        expect($baris['bidang'])->toBe(
            $bawaan->value,
            "Bidang {$baris['nomor_pengaduan']} tidak cocok dengan kategorinya"
        );
    }
});

/*
|--------------------------------------------------------------------------
| Remah roti
|--------------------------------------------------------------------------
*/

it('menyusun remah dari struktur menu, bukan dari teks yang ditulis tangan', function () {
    // Sebelum 2026-08-20 setiap halaman mengarang sendiri ruas pertamanya, dan
    // TIDAK SATU PUN cocok dengan menu yang benar-benar dipakai: transmigran
    // menulis "Kependudukan" padahal menunya "Penduduk & Lahan", poktan menulis
    // "Kelembagaan" padahal menunya "Poktan & Sarana", dan halaman daftar
    // inventaris menulis "Wilayah dan SP" sedangkan halaman rinciannya sendiri
    // menulis "Wilayah dan Aset SP".
    //
    // Remah yang tidak sejalan dengan menu lebih buruk daripada tidak ada remah
    // sama sekali: ia menyatakan pengguna berada pada cabang yang tidak pernah
    // ia lewati.
    $berkas = collect(File::allFiles(resource_path('views/pages')))
        ->filter(fn ($f) => str_ends_with($f->getFilename(), '.blade.php'));

    $ditulisTangan = [];

    foreach ($berkas as $f) {
        $isi = file_get_contents($f->getPathname());

        if (! str_contains($isi, ':remah=')) {
            continue;
        }

        if (! preg_match('/:remah="\\\\App\\\\Helpers\\\\RemahHelper::untuk\(/', $isi)) {
            $ditulisTangan[] = str_replace(resource_path('views/pages').DIRECTORY_SEPARATOR, '', $f->getPathname());
        }
    }

    expect($ditulisTangan)->toBe([]);
});

it('menempelkan rincian satuan permukiman pada menunya, bukan pada Dashboard', function () {
    // Halaman ini sempat memakai RemahHelper::untuk('/'), sehingga remahnya
    // terbaca "Beranda / Dashboard / SP Kapitan Meo". Alasan yang dulu
    // ditulis, bahwa ia menyajikan rekap kawasan per SP, tidak cocok dengan
    // isinya: yang ditampilkan adalah profil SP beserta transmigran, rumah,
    // lahan, panen, dan pengaduan MILIK SP itu.
    $remah = RemahHelper::untuk('/sp', 'SP Kapitan Meo');
    $label = array_column($remah, 'label');

    expect($label)->toBe(['Wilayah & SP', 'Satuan Permukiman', 'SP Kapitan Meo']);

    // "Satuan Permukiman" bertaut kembali ke daftarnya, ruas terakhir tidak.
    expect($remah[1]['url'] ?? null)->toBe(url('/sp'));
    expect($remah[2]['url'] ?? null)->toBeNull();

    // Halamannya benar-benar memakai remah itu, bukan remah Dashboard.
    $berkas = file_get_contents(resource_path('views/pages/sp/detail.blade.php'));

    expect($berkas)->toContain("RemahHelper::untuk('/sp'");
    expect($berkas)->not->toContain("RemahHelper::untuk('/'");
});

it('tidak menempelkan halaman rincian mana pun pada Dashboard', function () {
    // Penjaga umum, sebab cacat di atas lolos justru karena tidak ada yang
    // memeriksanya: remah yang menunjuk `/` selalu terbaca "Dashboard", dan
    // itu hanya benar bagi Dashboard itu sendiri.
    $salah = [];

    foreach (File::allFiles(resource_path('views/pages')) as $berkas) {
        $isi = file_get_contents($berkas->getPathname());

        // Dashboard sendiri memang berhak, dan ia memanggilnya tanpa ruas
        // rincian. Yang dilarang adalah halaman rincian, yang mengirim ruas
        // kedua sebagai nama datanya.
        if (preg_match("/RemahHelper::untuk\('\/',\s*\S/", $isi) === 1) {
            $salah[] = $berkas->getFilename();
        }
    }

    expect($salah)->toBe([]);
});
it('memakai nama submenu yang sama persis dengan sidebar', function () {
    // Inti perbaikannya: ruas kedua remah wajib nama submenu sesungguhnya.
    // Diuji terhadap MenuHelper, bukan terhadap daftar yang ditulis di sini,
    // sehingga penggantian nama menu langsung terbaca tanpa menyunting uji.
    $peta = [
        '/transmigran' => ['Penduduk & Lahan', 'Transmigran'],
        '/lahan' => ['Penduduk & Lahan', 'Data Lahan'],
        '/poktan' => ['Poktan & Sarana', 'Kelompok Tani'],
        '/sp/infrastruktur' => ['Wilayah & SP', 'Infrastruktur SP'],
        '/sp/inventaris' => ['Wilayah & SP', 'Inventaris SP'],
        '/wilayah' => ['Data Master', 'Wilayah'],
    ];

    foreach ($peta as $jalur => [$induk, $nama]) {
        $remah = RemahHelper::untuk($jalur);

        expect($remah)->toHaveCount(2, "remah {$jalur} bukan dua ruas");
        expect($remah[0]['label'])->toBe($induk);
        expect($remah[1]['label'])->toBe($nama);

        // Ruas terakhir tidak boleh bertaut ke dirinya sendiri.
        expect($remah[1])->not->toHaveKey('url');
    }
});

it('menambahkan ruas rincian tanpa melepas tautan halaman daftarnya', function () {
    // Pada halaman rincian, ruas daftar JUSTRU harus bertaut: itulah jalan
    // kembali yang dicari pengguna.
    $remah = RemahHelper::untuk('/transmigran', 'YOHANES BERE');

    expect($remah)->toHaveCount(3)
        ->and($remah[1])->toHaveKey('url')
        ->and($remah[2]['label'])->toBe('YOHANES BERE')
        ->and($remah[2])->not->toHaveKey('url');
});

it('tetap menyusun remah untuk halaman di luar sidebar', function () {
    // Profil dan galeri komponen tidak ada di menu. Remah kosong akan membuat
    // kepala halaman kehilangan penunjuk posisi sama sekali.
    expect(RemahHelper::untuk('/profil'))
        ->toBe([['label' => 'Profil']]);

    expect(RemahHelper::untuk('/profil', 'Ubah Kata Sandi'))
        ->toBe([['label' => 'Profil'], ['label' => 'Ubah Kata Sandi']]);
});
it('menyediakan isian catatan pada setiap modul yang kolomnya ada di kamus data', function (string $berkas) {
    // Empat modul sempat punya kolom `keterangan` pada kamus data tanpa satu
    // pun isian, sehingga hal yang tidak tertampung kolom baku tidak dapat
    // dicatat ke mana pun (ui-spec.md 6.4a poin 2).
    $sumber = file_get_contents(resource_path("views/pages/{$berkas}.blade.php"));

    expect($sumber)->toContain('name="keterangan"');
})->with([
    'alsintan/form',
    'infrastruktur/form',
    'poktan/form',
    'saprotan/form',
    'sp/form',
    'sp/form-inventaris',
    'sp/form-fasilitas',
    'sp/form-kawasan',
    'lahan/form',
    'transmigran/form',
    'panen/form',
    'penanaman/form',
    'poktan/form-anggota',
]);

it('menyeragamkan label isian catatan', function () {
    // Sebelum 2026-08-20 empat penamaan dipakai bergantian untuk satu maksud
    // yang sama. Kolom databasenya tetap `keterangan`; yang diseragamkan adalah
    // teks yang dibaca petugas (ui-spec.md 6.4a poin 1).
    $menyimpang = [];

    foreach (File::allFiles(resource_path('views/pages')) as $f) {
        if (! str_contains($f->getFilename(), 'form')) {
            continue;
        }

        $isi = file_get_contents($f->getPathname());

        if (! str_contains($isi, 'name="keterangan"')) {
            continue;
        }

        // Label lama yang tidak boleh muncul lagi pada isian catatan umum.
        if (str_contains($isi, '>Keterangan</label>')) {
            $menyimpang[] = str_replace(resource_path('views/pages').DIRECTORY_SEPARATOR, '', $f->getPathname());
        }
    }

    expect($menyimpang)->toBe([]);
});

it('menampilkan catatan pada setiap halaman rincian yang modulnya punya kolom itu', function (string $jalur) {
    // Catatan yang hanya dapat diketik tetapi tidak pernah terbaca sama saja
    // dengan tidak dicatat (ui-spec.md 6.4a poin 3). Lima halaman rincian
    // sempat tidak menampilkannya sama sekali.
    $this->get($jalur)->assertOk()->assertSee('Catatan', false);
})->with([
    '/alsintan/1',
    '/saprotan/1',
    '/sp/infrastruktur/1',
    '/poktan/1',
    '/sp/inventaris/1',
    '/sp/fasilitas/1',
    '/lahan/1',
    '/transmigran/1',
]);

it('menyediakan cara membuka berkas dari halaman rincian modulnya', function (string $jalur, int $jumlahBerkas) {
    // Unggahan yang tidak punya jalan dibuka adalah kontrol mati (R-26):
    // petugas mengunggah berita acara lalu tidak menemukan cara membacanya.
    // Ketiga halaman aset sempat menerima unggahan tanpa menampilkannya sama
    // sekali (ui-spec.md 6.4).
    $isi = $this->get($jalur)->assertOk()->getContent();

    preg_match_all('#/dokumen/[a-z_]+/\d+/[^"]+#', $isi, $cocok);

    expect($cocok[0])->toHaveCount($jumlahBerkas, "berkas tertaut pada {$jalur} tidak sesuai");
})->with([
    ['/poktan/1', 1],
    // Saprotan memisahkan foto barang dari dokumen: dua tautan pada induk.
    ['/saprotan/1', 2],
    // Alsintan: foto barang + dokumen pengadaan pada INDUK (Putaran 15,
    // keputusan 11 Putaran 12), ditambah satu foto kondisi unit pada baris
    // distribusi pertama = tiga tautan.
    ['/alsintan/1', 3],
    // Tiga foto titik kerusakan ditambah satu dokumen pendukung sejak
    // Putaran 14. Tautannya hanya di panel rincian; form yang di-include
    // halaman ini menampilkan nama berkas saja agar tidak kembar.
    ['/sp/infrastruktur/1', 4],
    // Foto barang dijamakkan Putaran 14 (dua sudut) + satu dokumen.
    ['/sp/inventaris/2', 3],
    // Foto bangunan dijamakkan Putaran 14 (dua sisi) + satu dokumen.
    ['/sp/fasilitas/3', 3],
    // Rincian SP adalah halaman detail SP, satu-satunya tempat dokumen
    // penetapan dapat dibuka.
    ['/sp/1', 1],
    // Bukti dari pelapor, terpisah dari dokumen tindak lanjut milik petugas.
    ['/pengaduan/1', 2],
    // Empat berkas keluarga: SHM, KTP, KK, dan SK penempatan. Panel Dokumen
    // sempat membaca kolom `dokumen_pendukung` yang sudah dicabut Putaran 12,
    // sehingga keempatnya terunggah tetapi tidak dapat dibuka dari mana pun.
    ['/transmigran/2', 4],
    // Dua foto sisi rumah. Pivot `rumah_berkas` ada di schema.sql sejak
    // Putaran 12 tetapi belum pernah punya data contoh.
    ['/rumah/1', 2],
    // Kawasan tidak punya halaman rincian sendiri, sehingga kartu pada
    // halaman daftar inilah satu-satunya tempat berkasnya dapat dibuka.
    // Dulu hanya SK yang tampil; HPL dan peta tidak dapat dicapai.
    ['/kawasan', 3],
]);

it('menaruh catatan sebelum unggahan pada setiap form', function () {
    // Isian berkas menuntut perhatian lebih lama daripada isian teks:
    // petugas berhenti mengetik, membuka penjelajah berkas, mencari, lalu
    // kembali. Menaruhnya di tengah memutus alur pengisian, dan catatan
    // yang berada sesudahnya kerap terlewat.
    //
    // Tujuh form sempat menempatkannya terbalik (ui-spec.md 6.4a poin 5).
    // Diperiksa dari sumbernya sebab yang dijaga adalah URUTAN MARKUP,
    // bukan keberadaan isiannya.
    $terbalik = [];

    foreach (File::allFiles(resource_path('views/pages')) as $berkas) {
        if (! str_starts_with($berkas->getFilename(), 'form')) {
            continue;
        }

        $isi = file_get_contents($berkas->getPathname());

        $posUnggah = strpos($isi, 'x-sim.file-upload');
        $posCatatan = strpos($isi, 'name="keterangan"');

        // Hanya form yang punya KEDUANYA yang dapat dinilai urutannya.
        if ($posUnggah === false || $posCatatan === false) {
            continue;
        }

        if ($posCatatan > $posUnggah) {
            $terbalik[] = $berkas->getRelativePathname();
        }
    }

    expect($terbalik)->toBe([]);
});

it('meniadakan keterangan satuan lokal dari hasil panen', function () {
    // Dicabut 2026-08-22 atas keputusan pemilik proyek. Padanan satuan
    // setempat kini ditulis pada kolom catatan biasa bila memang perlu.
    //
    // Kolomnya dahulu satu dari tiga pengecualian penamaan "Catatan" pada
    // ui-spec.md 6.4a, sehingga pencabutannya menyentuh dokumen acuan pula.
    foreach (DummyData::hasilPanen() as $p) {
        expect($p)->not->toHaveKey('keterangan_satuan_lokal');
    }

    $form = file_get_contents(resource_path('views/pages/panen/form.blade.php'));
    $detail = file_get_contents(resource_path('views/pages/panen/detail.blade.php'));

    expect($form)->not->toContain('keterangan_satuan_lokal')
        ->and($detail)->not->toContain('keterangan_satuan_lokal');
});

/*
|--------------------------------------------------------------------------
| Penjaga hasil audit menyeluruh 2026-08-25
|--------------------------------------------------------------------------
|
| Kelima uji berikut menjaga KELAS kekeliruan, bukan satu kejadiannya saja.
| Masing-masing lahir dari cacat nyata yang lolos dari 609 uji sebelumnya,
| dan seluruhnya diperiksa dari berkas sumber sebab yang dijaga adalah
| markup serta pemanggilan, bukan hasil render satu halaman.
|
*/

it('merangkai setiap form autentikasi sampai dapat dikirim', function (string $berkas, string $rute) {
    /*
        Halaman masuk sempat memuat `<form>` telanjang: tanpa `action`, tanpa
        `method`, tanpa `@csrf`, dan tanpa rute penerima. Tombol Masuk hanya
        memuat ulang halaman.

        Yang membuatnya luput bukan kerumitan, melainkan tidak adanya yang
        memeriksa. Tiga form autentikasi lain sudah lengkap sejak awal,
        sehingga kekeliruannya tampak seperti keputusan yang disengaja.

        Diperiksa dari sumber, sebab `action` kosong tetap merender `<form>`
        yang sah dan tidak memerahkan uji berbasis HTTP mana pun.
    */
    $isi = file_get_contents(resource_path("views/pages/auth/{$berkas}.blade.php"));

    /*
        Ketiga syarat dikumpulkan lebih dulu, bukan diperiksa satu per satu
        lewat `toContain` berantai. `toContain` pada Pest memperlakukan
        argumen kedua sebagai NILAI yang ikut dicari, bukan pesan galat,
        sehingga rantai berpesan justru memerah pada berkas yang sehat.
    */
    $kurang = [];

    if (! str_contains($isi, 'method="POST"')) {
        $kurang[] = 'method="POST"';
    }

    if (! str_contains($isi, "route('{$rute}')")) {
        $kurang[] = "action ke route('{$rute}')";
    }

    if (! str_contains($isi, '@csrf')) {
        $kurang[] = '@csrf';
    }

    // Rutenya wajib benar-benar ada, bukan sekadar tertulis di markup.
    if (! Route::has($rute)) {
        $kurang[] = "rute {$rute} belum terdaftar";
    }

    expect($kurang)->toBe([]);
})->with([
    ['signin', 'login.kirim'],
    ['lupa-kata-sandi', 'lupa-kata-sandi.kirim'],
    ['ganti-kata-sandi', 'ganti-kata-sandi.simpan'],
]);

it('menyediakan tempat menampilkan galat pada halaman masuk', function () {
    /*
        Tanpa ini, kegagalan masuk pada Tahap 3 akan tampak seperti tombol
        rusak: halaman termuat ulang, isian terisi kembali lewat `old()`,
        dan tidak ada satu pun keterangan mengapa. Persis kontrol mati yang
        dilarang R-26.

        Kunci galatnya `kredensial`, sama dengan nama isiannya, agar rute
        Tahap 3 tahu persis nama apa yang harus dipakai saat menolak.
    */
    $isi = file_get_contents(resource_path('views/pages/auth/signin.blade.php'));

    expect($isi)->toContain("@error('kredensial')")
        ->and($isi)->toContain("old('kredensial')");
});

it('tidak menuliskan alamat mutlak pada modul JavaScript', function () {
    /*
        `chart-config.js` sempat memuat '/dashboard/sp/' secara harfiah,
        sehingga penelusuran 17 grafik dashboard membalas 404 pada penyajian
        statis yang berada di sub-path `/transmigrasi/`.

        Berkas JavaScript tidak mengenal `url()`, jadi satu-satunya sumber
        alamat yang benar adalah Blade. Larangannya sudah tertulis pada
        notes.md 1b.3 sejak 2026-08-17, tetapi tidak pernah punya penjaga,
        dan justru itulah sebabnya pelanggaran ini bertahan.

        Diperiksa pada `window.location`, `fetch`, dan `axios`, yakni tiga
        cara sebuah modul dapat menuju alamat sendiri.
    */
    $galat = [];

    foreach (glob(resource_path('js/*.js')) as $berkas) {
        $isi = file_get_contents($berkas);
        $nama = basename($berkas);

        foreach (preg_split('/\r?\n/', $isi) as $nomor => $baris) {
            if (! preg_match('/(window\.location\S*\s*=|fetch\(|axios\.\w+\()/', $baris)) {
                continue;
            }

            /*
                Alamat mutlak yang diketik langsung, contoh '/dashboard/sp/'.
                Diawali kutip lalu garis miring, lalu HURUF.

                Syarat huruf itu yang membedakannya dari '/' telanjang sebagai
                pemisah ruas, dan dari '//' milik protokol. Tanpa syarat itu
                penggabungan alamat yang sudah benar pun ikut memerah.
            */
            if (preg_match("#['\"]/[A-Za-z]#", $baris)) {
                $galat[] = $nama.':'.($nomor + 1).' '.trim($baris);
            }
        }
    }

    expect($galat)->toBe([]);
});

it('memberi nama pada setiap tombol yang isinya hanya ikon', function () {
    /*
        Empat tombol pada header bersama tidak punya nama sama sekali:
        menu aplikasi, ganti tema, lonceng notifikasi, dan tutup notifikasi.
        Header itu muncul di SELURUH halaman berautentikasi, sehingga
        pembaca layar hanya mengumumkan "tombol" sebanyak empat kali di
        setiap halaman.

        Diperiksa pada layout dan komponen header saja, yakni tempat tombol
        ikon polos memang lazim. Tombol di dalam halaman umumnya berteks,
        dan memaksakan pemeriksaan ke seluruh 128 berkas akan menghasilkan
        positif palsu pada tombol yang namanya datang dari teks isinya.
    */
    $berkas = array_merge(
        glob(resource_path('views/layouts/*.blade.php')),
        glob(resource_path('views/components/header/*.blade.php')),
    );

    $galat = [];

    foreach ($berkas as $path) {
        $isi = file_get_contents($path);
        $nama = BerkasBlade::namaPendek($path);

        // Setiap <button ...> beserta isinya sampai penutupnya.
        preg_match_all('/<button\b([^>]*)>([\s\S]*?)<\/button>/i', $isi, $cocok, PREG_SET_ORDER);

        foreach ($cocok as $tombol) {
            [$utuh, $atribut, $dalam] = $tombol;

            $punyaNama = preg_match('/(:?aria-label|aria-labelledby)\s*=/i', $atribut) === 1
                || str_contains($dalam, 'sr-only');

            if ($punyaNama) {
                continue;
            }

            // Teks yang benar-benar terbaca, setelah SVG dan komentar dibuang.
            $teks = trim(strip_tags(BerkasBlade::bersihkan($dalam)));

            if ($teks === '') {
                $galat[] = $nama.': '.trim(preg_replace('/\s+/', ' ', substr($utuh, 0, 60)));
            }
        }
    }

    expect($galat)->toBe([]);
});

it('menuliskan seluruh teks antarmuka dalam bahasa Indonesia', function () {
    /*
        Komponen lonceng notifikasi masih memuat "Notification" warisan
        TailAdmin, terlihat di setiap halaman. Melanggar ui-spec.md 11.2.

        Daftar kata sengaja pendek dan menyasar sisa template, bukan mencoba
        mendeteksi bahasa Inggris secara umum. Uji yang terlalu pintar di
        sini akan memerah pada nama kelas dan atribut.
    */
    $terlarang = ['Notification', 'View All', 'Search', 'Settings', 'Sign In', 'Sign Out', 'Dashboard Overview'];
    $galat = [];

    foreach (BerkasBlade::semua() as $path) {
        $nama = BerkasBlade::namaPendek($path);

        if (str_contains($nama, 'galeri-komponen')) {
            continue;
        }

        $isi = BerkasBlade::bersihkan(file_get_contents($path));

        // Hanya teks di antara tag, bukan atribut maupun nama kelas.
        preg_match_all('/>([^<>{}]+)</', $isi, $cocok);

        foreach ($cocok[1] as $teks) {
            foreach ($terlarang as $kata) {
                if (str_contains($teks, $kata)) {
                    $galat[] = "{$nama}: {$kata}";
                }
            }
        }
    }

    expect(array_values(array_unique($galat)))->toBe([]);
});

it('tidak menuliskan ulang blok tombol yang sudah punya komponen', function () {
    /*
        Penjaga ide B audit 1g, ditambahkan 2026-08-27.

        Blok tombol Impor-Tambah dan blok tombol filter sempat ditulis identik
        di empat belas dan tujuh belas halaman, lengkap dengan kelas Tailwind
        sepanjang dua ratus karakter. Duplikasi sepanjang itu tidak bertahan
        seragam: cukup satu halaman disunting sendiri, dan sisanya menyimpang
        tanpa ada yang menyadari.

        Yang dijaga bukan jumlah barisnya melainkan SATU SUMBER: markup itu
        hidup di komponennya, dan halaman menyebut nama modal atau alamat
        saja.
    */
    $penanda = [
        'Terapkan Filter' => 'components/sim/tombol-filter.blade.php',
        "buka-modal', 'impor" => 'components/sim/aksi-daftar.blade.php',
    ];

    /*
        Dua pengecualian yang disengaja: `panen/rekap` dan `kependudukan/rekap`.
        Keduanya merupakan halaman rekap berpenyaring tahun/komoditas terpadu
        pada bilah atas, bukan tombol penyaring modal daftar berbaris.
    */
    $dikecualikan = ['pages/panen/rekap.blade.php', 'pages/kependudukan/rekap.blade.php'];

    $galat = [];

    foreach (BerkasBlade::semua() as $path) {
        $nama = str_replace('\\', '/', BerkasBlade::namaPendek($path));

        if (in_array($nama, $dikecualikan, true)) {
            continue;
        }

        $isi = file_get_contents($path);

        foreach ($penanda as $cari => $rumah) {
            if (str_contains($nama, $rumah)) {
                continue;
            }

            if (str_contains($isi, $cari)) {
                $galat[] = "{$nama} menuliskan sendiri '{$cari}'";
            }
        }
    }

    expect($galat)->toBe([]);
});

it('tidak menyimpan komponen yang tidak dipakai siapa pun', function () {
    /*
        Penjaga temuan 7 audit 1g, butir tindak lanjut 14, ditambahkan
        2026-08-27.

        Tiga belas komponen bawaan TailAdmin bertahan tanpa satu pun pemakai
        selama berbulan-bulan, sebab polanya diserap ke `x-sim.*` alih-alih
        dibungkus. Komponen mati tidak merusak apa pun, dan justru itu
        sebabnya ia menumpuk: tidak ada yang memerah, tidak ada yang menegur.

        Yang dihitung PEMAKAIAN NYATA, yakni tag komponennya dipakai berkas
        lain. Kelas View Component tidak dihitung, sebab setiap kelas menyebut
        viewnya sendiri dan penyebutan itu bukan pemakaian - persis jebakan
        yang membuat penyisiran pertama saya melaporkan nol yatim.

        `galeri-komponen` DIHITUNG sebagai pemakai yang sah. Ia halaman
        peninjauan yang memang merender komponen, dan dijadwalkan dihapus;
        saat itu tiba, `error-state` dan `skeleton` wajib ditimbang ulang
        sebab hanya galeri yang memakainya.
    */
    $berkas = [];
    $isiPer = [];

    foreach (BerkasBlade::semua() as $path) {
        $rapi = str_replace('\\', '/', $path);
        $isiPer[$rapi] = file_get_contents($path);

        if (str_contains($rapi, '/components/')) {
            $berkas[] = $rapi;
        }
    }

    $yatim = [];

    foreach ($berkas as $path) {
        $rel = substr($path, strpos($path, '/components/') + strlen('/components/'));
        $tag = '<x-'.str_replace('/', '.', substr($rel, 0, -strlen('.blade.php')));

        $dipakai = false;

        foreach ($isiPer as $lain => $isi) {
            // Pemakaian oleh dirinya sendiri tidak dihitung.
            if ($lain !== $path && str_contains($isi, $tag)) {
                $dipakai = true;
                break;
            }
        }

        if (! $dipakai) {
            $yatim[] = $rel;
        }
    }

    // Penjaga terhadap ujinya sendiri: bila pengumpulan berkasnya gagal,
    // daftar yatimnya kosong dan uji ini hijau tanpa memeriksa apa pun.
    expect(count($berkas))->toBeGreaterThan(25);
    expect($yatim)->toBe([]);
});

it('tidak menyisakan isian form yatim yang tak berpadanan di schema.sql', function () {
    /*
        Penjaga isian yatim, ditambahkan Putaran 15 (2026-09-02).

        AKAR MASALAH. Putaran 12 mencabut tabel `dokumen_lahan` beserta kolom
        `jenis_dokumen` dan `file_dokumen`, tetapi isian keduanya tetap hidup
        pada form lahan selama berhari-hari. Skema diperiksa dengan impor MariaDB
        nyata; ISIAN FORM tidak pernah dibandingkan terhadap skema, sehingga
        isian yang kolomnya sudah tiada tidak memerahkan apa pun - persis nasib
        empat kontrol mati Putaran 14.

        Penjaga ini menutup celah itu: setiap atribut name= (juga nama= pada
        komponen isian) pada berkas form di bawah pages/ wajib berpadanan di
        schema.sql, entah sebagai KOLOM tabel mana pun, entah sebagai PERAN
        BERKAS pada registry berkas (rules.md 14a.8).

        DAFTAR KECUALI ditulis eksplisit beserta alasannya. Penjaga tanpa daftar
        kecuali akan dimatikan orang berikutnya begitu ia memerah sekali secara
        keliru.

        DIUJI DENGAN ISIAN PALSU. Menambahkan `name="kolom_palsu_xyz"` ke salah
        satu form membuat uji ini MERAH; mencabutnya kembali HIJAU. Dilakukan
        saat penjaga ini dibuat, tidak ditinggalkan di kode.
    */

    // Sumber kebenaran: seluruh nama kolom pada seluruh CREATE TABLE.
    $sql = file_get_contents(base_path('database/data/schema.sql'));
    preg_match_all('/^\s+`([a-z_]+)`\s+[A-Z]/m', $sql, $m);
    $kolom = array_flip($m[1]);

    // Nama pembungkus repeater: barisnya larik, dan SUB-KUNCI-nya diperiksa
    // tersendiri di bawah. Nama pembungkusnya sendiri bukan kolom.
    $pembungkusRepeater = [
        'anggota_keluarga' => 'baris anggota_keluarga (tabel anggota_keluarga)',
        'anggota' => 'baris anggota poktan (tabel anggota_poktan)',
        'distribusi' => 'baris distribusi alsintan (tabel alsintan_distribusi)',
        'rute_aksesibilitas' => 'baris rute aksesibilitas (tabel rute_aksesibilitas_sp)',
        'izin' => 'matriks kewenangan role (pivot role_permission), bukan kolom',
    ];

    // Peran berkas: berkasnya hidup di registry `berkas` lewat pivot/FK
    // (rules.md 14a.8), bukan sebagai kolom pada tabel domainnya.
    $peranBerkas = [
        'foto' => 'peran berkas foto pada *_berkas',
        'dokumen_pendukung' => 'peran berkas dokumen pada *_berkas',
        'foto_rumah' => 'peran berkas foto pada rumah_berkas',
        'dokumen_kawasan' => 'peran berkas pada kawasan_transmigrasi_berkas',
        'bukti' => 'peran berkas bukti pada pengaduan_berkas',
        'ktp' => 'peran berkas KTP pada transmigran_berkas',
        'kk' => 'peran berkas KK pada transmigran_berkas',
        'sk' => 'peran berkas SK penempatan pada transmigran_berkas',
        'shm' => 'peran berkas SHM pada transmigran_berkas (diunggah dari form lahan sejak 2026-09-03)',
    ];

    // Isian yang memang BUKAN kolom: penyaring, token, pivot penugasan, dan
    // isian bantu yang nilainya diturunkan bukan disimpan.
    $dikecualikan = [
        'satuan_permukiman' => 'isian pivot user_satuan_permukiman (penugasan SP), bukan kolom',
        'satuan_permukiman_ids_lain' => 'isian pivot infrastruktur_sp / fasilitas_sp_cakupan (SP lain yang dilayani aset bersama), bukan kolom',
        '_token' => 'token CSRF Laravel',
        '_method' => 'penanda metode HTTP Laravel',
        'cari' => 'kata kunci pencarian, bukan isian data',
        'peruntukan_lahan' => 'penyaring daftar lahan (punya bidang ini?), bukan isian form; kolomnya dicabut Putaran 15',
        'kategori_lahan' => 'penyaring komposisi lahan (punya bagian ini?), bukan kolom',
        'tab' => 'penanda tab aktif pada URL',
        '_anggota_disunting' => 'penanda bahwa form memuat daftar anggota keluarga (Task 5.2); absen pada modal ubah per baris yang tak memuatnya, bukan kolom',
    ];

    $orphan = [];

    foreach (BerkasBlade::semua() as $path) {
        $nama = BerkasBlade::namaPendek($path);

        if (! preg_match('#/form[^/]*\.blade\.php$#', $nama) || ! str_contains($nama, 'pages/')) {
            continue;
        }

        $isi = BerkasBlade::bersihkan(file_get_contents($path));

        $terkumpul = [];

        // 1. name="x" dan name="x[]" statik.
        preg_match_all('/\bname="([a-zA-Z_][a-zA-Z0-9_]*)(\[\])?"/', $isi, $c1);
        foreach ($c1[1] as $n) {
            $terkumpul[$n] = 'name';
        }

        // 2. Sub-kunci repeater: name="wadah[${i}][sub]" -> sub.
        preg_match_all('/\[\$\{[a-z]+\}\]\[([a-z_]+)\]/', $isi, $c2);
        foreach ($c2[1] as $n) {
            $terkumpul[$n] = 'repeater-subkunci';
        }

        // 3. Nama pembungkus repeater: name="wadah[${i}]...".
        preg_match_all('/:?name="`?([a-z_]+)\[\$\{/', $isi, $c3);
        foreach ($c3[1] as $n) {
            $terkumpul[$n] = 'repeater-pembungkus';
        }

        // 4. Komponen isian: file-upload/berkas-unggah nama=, koordinat-input
        //    nama-lintang/nama-bujur, pilih-cari nama=.
        preg_match_all('/<x-sim\.(?:file-upload|berkas-unggah)\b[^>]*?\bnama="([a-z_]+)"/s', $isi, $c4);
        foreach ($c4[1] as $n) {
            $terkumpul[$n] = 'berkas';
        }
        preg_match_all('/\bnama-(?:lintang|bujur)="([a-z_]+)"/', $isi, $c5);
        foreach ($c5[1] as $n) {
            $terkumpul[$n] = 'koordinat';
        }
        preg_match_all('/<x-sim\.pilih-cari\b[^>]*?\bnama="([a-z_]+)"/s', $isi, $c6);
        foreach ($c6[1] as $n) {
            $terkumpul[$n] = 'pilih-cari';
        }

        foreach ($terkumpul as $isian => $asal) {
            if (isset($kolom[$isian])
                || isset($pembungkusRepeater[$isian])
                || isset($peranBerkas[$isian])
                || isset($dikecualikan[$isian])) {
                continue;
            }

            $orphan[] = "{$nama}: name=\"{$isian}\" ({$asal}) tak berpadanan di schema.sql";
        }
    }

    // Penjaga terhadap ujinya sendiri: bila pengumpulannya gagal, daftar
    // yatimnya kosong dan uji ini hijau tanpa memeriksa apa pun.
    expect($kolom)->toHaveKey('nama_kepala_keluarga');
    expect($orphan)->toBe([]);
});

it('tidak mengirimkan aksi ke alamat berakar domain', function () {
    /*
        Penjaga temuan 8 audit 1g, butir tindak lanjut 13, ditambahkan
        2026-08-27.

        37 pemanggil mengoper alamat mentah semacam '/alsintan/3' pada
        `:hapus-url` dan `pola-aksi`. Pada penyajian statis yang berada di
        sub-path `/transmigrasi/`, seluruhnya mengirim ke akar domain dan
        tidak pernah sampai.

        DIPERIKSA PADA KELUARAN TERENDER, bukan pada bentuk kode pemanggilnya.

        Sejak alamatnya dibungkus `url()` di dalam `aksi-baris` dan
        `modal-form`, mengoper alamat mentah dari pemanggil TIDAK LAGI keliru;
        komponennya yang membereskan. Uji yang melarang bentuk itu di
        pemanggil justru akan melarang kode yang benar. Yang wajib dijaga
        adalah hasilnya, dan hasilnya hanya terlihat setelah dirender.

        Ini kebalikan dari penjaga komoditas utama pada 1h.5, yang justru
        wajib memeriksa sumber sebab datanya tidak dapat membedakan benar dari
        salah. Pembedanya satu pertanyaan: apakah keluarannya berbeda.
    */
    $galat = [];
    $diperiksa = 0;

    foreach (Route::getRoutes() as $rute) {
        if (! in_array('GET', $rute->methods(), true)) {
            continue;
        }

        $uri = $rute->uri();
        $uri = preg_replace('/\{jenis\??\}/', 'kondisi', $uri);
        $uri = preg_replace('/\{kelompok\??\}/', 'sp', $uri);
        $uri = preg_replace('/\{nomor\??\}/', DummyData::pengaduan()[0]['nomor_pengaduan'], $uri);
        $uri = preg_replace('/\{[a-zA-Z_]+\??\}/', '1', $uri);

        if (str_contains($uri, '{')) {
            continue;
        }

        $res = $this->get('/'.ltrim($uri, '/'));

        // Bukan 200 berarti bukan halaman; 403 dan 404 di sini disengaja.
        if ($res->getStatusCode() !== 200) {
            continue;
        }

        $diperiksa++;
        $html = $res->getContent();

        /*
            Dua bentuk yang dicari. Yang pertama alamat hapus pada tombol
            baris, tertulis apa adanya. Yang kedua pola aksi modal, dikeluarkan
            `@js()` sehingga garis miringnya ter-escape.
        */
        foreach (["aksi: '/", "polaAksi: '\\/"] as $pola) {
            if (str_contains($html, $pola)) {
                $galat[] = "/{$uri} memuat {$pola}";
            }
        }
    }

    // Penjaga terhadap ujinya sendiri: bila tidak ada halaman yang terperiksa,
    // uji ini hijau tanpa membuktikan apa pun.
    expect($diperiksa)->toBeGreaterThan(40);
    expect($galat)->toBe([]);
});

it('menanyakan varietas hanya untuk benih, seperti komoditas', function () {
    /*
        Revisi 2026-08-27: benih wajib menyebut varietasnya, jenis lain tidak
        (rules.md 7c poin 12). Aturannya sama persis dengan komoditas
        (poin 7), sehingga penjaganya pun sejajar.

        DIPERIKSA PADA KELUARAN TERENDER, bukan pada berkas sumber: yang
        dijaga adalah kedua field muncul di halaman dan bersyarat pada jenis
        yang sama. Bentuk kodenya boleh berubah selama perilakunya tetap.
    */
    $html = $this->get('/saprotan')->assertOk()->getContent();

    // Kedua field ada, dan keduanya bersyarat pada `benih` yang sama.
    foreach (['komoditas_id', 'varietas'] as $nama) {
        expect($html)->toContain('name="'.$nama.'"');

        preg_match('/<(?:input|select)[^>]*name="'.$nama.'"[^>]*>/', $html, $tag);
        expect($tag[0] ?? '')->toContain(':required="benih"');
        expect($tag[0] ?? '')->toContain(':disabled="! benih"');
    }

    // Data contoh: setiap benih punya varietas, jenis lain kosong.
    foreach (DummyData::saprotan() as $b) {
        if ($b['jenis'] === JenisSaprotan::Benih->value) {
            expect($b['varietas'])->not->toBeNull("benih {$b['nama']} tanpa varietas");
        } else {
            expect($b['varietas'])->toBeNull("{$b['jenis']} {$b['nama']} punya varietas");
        }
    }
});

it('mengelompokkan laporan panen menurut tahun pengadaan bantuannya', function () {
    /*
        Revisi 2026-08-27, dari pertemuan dengan Dinas Pertanian: laporan
        hasil panen dikelompokkan menurut TAHUN ANGGARAN bantuan benihnya,
        bukan tahun panen. Bantuan beranggaran 2025 yang dipanen 2026 tetap
        capaian 2025.

        Yang dibuktikan di sini RANTAI PENELUSURANNYA: hasil_panen ->
        penanaman -> saprotan_distribusi -> pengadaan.tahun_pengadaan wajib
        menghasilkan tahun yang benar, termasuk untuk kasus lintas tahun
        (Putaran 7: grain benih pindah ke distribusi).
    */
    $penanaman = collect(DummyData::penanaman())->keyBy('id_penanaman');
    $distribusi = collect(DummyData::saprotanDistribusi())->keyBy('id_saprotan_distribusi');

    $lintasThn = 0;

    foreach (DummyData::hasilPanen() as $panen) {
        $tanam = $penanaman[$panen['penanaman_id']] ?? null;
        expect($tanam)->not->toBeNull("panen {$panen['id_hasil_panen']} tanpa penanaman");

        $benih = $distribusi[$tanam['saprotan_distribusi_id']] ?? null;
        expect($benih)->not->toBeNull("penanaman {$tanam['id_penanaman']} tanpa benih");

        $thnPengadaan = $benih['tahun_pengadaan'];
        expect($thnPengadaan)->toBeInt();

        // Tahun panen, untuk membandingkan.
        $thnPanen = $panen['periode_panen'] ? (int) substr($panen['periode_panen'], 0, 4) : null;

        if ($thnPanen !== null && $thnPanen !== $thnPengadaan) {
            $lintasThn++;
        }
    }

    // Data contoh WAJIB memuat minimal satu kasus lintas tahun, sebab tanpa
    // itu penggantian sumbu tahun tidak membuktikan apa pun.
    expect($lintasThn)->toBeGreaterThan(0);
});

it('memberi nama pada setiap tabel', function () {
    /*
        Penjaga temuan 6 audit 1g, ditambahkan 2026-08-27.

        Tabel tanpa <caption> memaksa pembaca layar menebak tabel apa yang
        sedang dibacanya. Pada halaman yang memuat tiga belas tabel seperti
        dashboard, tebakan itu praktis mustahil.

        <caption> wajib menjadi ANAK PERTAMA <table>; itulah sebabnya yang
        diperiksa jarak antara tag pembuka dan captionnya, bukan sekadar
        keberadaannya di suatu tempat dalam berkas.

        Dua komponen bersama, `data-table` dan `tabel-ringkas`, merendernya
        dari prop `judul` sehingga captionnya tidak tertulis harfiah. Keduanya
        karena itu diperiksa lewat keberadaan prop tersebut.
    */
    $galat = [];

    foreach (BerkasBlade::semua() as $path) {
        $isi = file_get_contents($path);
        $nama = BerkasBlade::namaPendek($path);

        // Dua komponen yang merender captionnya dari prop.
        if (str_contains($isi, '<caption class="sr-only">{{ $judul }}</caption>')) {
            continue;
        }

        preg_match_all('/<table\b[^>]*>/', $isi, $cocok, PREG_OFFSET_CAPTURE);

        foreach ($cocok[0] as $tabel) {
            [$tag, $posisi] = $tabel;

            // Sesudah tag pembuka, isi berarti pertama wajib <caption>.
            $sesudah = ltrim(substr($isi, $posisi + strlen($tag), 200));

            if (! str_starts_with($sesudah, '<caption')) {
                $galat[] = $nama.': '.trim($tag);
            }
        }
    }

    expect($galat)->toBe([]);
});

it('memakai cangkang dua kolom baku pada halaman detail', function () {
    /*
        Penjaga bug gulir mendatar badan halaman, ditambahkan 2026-08-30.

        Di `/transmigran/1?tab=keluarga` muncul scrollbar mendatar pada BADAN
        halaman (bukan wadah gulir tabel), menggeser seluruh halaman ke kanan
        hingga kolom ringkasan tertutup sidebar. Buktinya di
        `refs/bug tab anggota keluarga transmigran.jpg`; geometrinya dijaga
        `tests/Browser/uji-lebar-halaman.mjs`.

        Akar masalah selalu bentuk yang sama: halaman menyimpang dari cangkang
        dua kolom baku milik `pages/poktan/detail.blade.php` ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â

          - trek grid kanan ditulis `1fr` polos (minimum otomatisnya
            `min-content`), bukan `minmax(0,1fr)`, sehingga isi selebar apa pun
            memaksanya melebar;
          - kartu tab (blok tepat setelah `x-data="hashTabs(...)"`) tidak
            memakai `min-w-0 overflow-hidden`, sehingga tabel tab terlebar
            menembus wadah `.mx-auto max-w-(--breakpoint-2xl)`.

        Penjaga string ini mengunci kedua kelas itu; pelanggarannya tidak
        memerahkan apa pun tanpa uji peramban.
    */
    $galatGrid = [];
    $galatKartu = [];

    foreach (BerkasBlade::semua() as $path) {
        $isi = BerkasBlade::bersihkan(file_get_contents($path));
        $nama = BerkasBlade::namaPendek($path);

        // 1. Setiap trek grid kolom ringkasan wajib dapat menyusut.
        if (str_contains($isi, 'lg:grid-cols-[20rem_1fr]')) {
            $galatGrid[] = $nama;
        }

        // 2. Setiap kartu tab hashTabs wajib mengurung isinya.
        if (preg_match_all('/x-data="hashTabs\(/', $isi, $_, PREG_OFFSET_CAPTURE)) {
            foreach ($_[0] as [$cocok, $posisi]) {
                // Dari titik hashTabs sampai pembuka <button role="tab"> atau
                // role="tablist" pertama: di situlah kartu + bilah tab berada.
                $cuplik = substr($isi, $posisi, 600);
                $batas = strpos($cuplik, 'role="tab');
                $wilayah = $batas === false ? $cuplik : substr($cuplik, 0, $batas);

                if (! str_contains($wilayah, 'min-w-0') || ! str_contains($wilayah, 'overflow-hidden')) {
                    $galatKartu[] = $nama;
                }
            }
        }
    }

    expect($galatGrid)->toBe([]);
    expect(array_values(array_unique($galatKartu)))->toBe([]);

    // 3. Wadah gulir mendatar tabel bersama WAJIB `relative`, supaya
    //    `<caption class="sr-only">` / `<span class="sr-only">` (position
    //    absolute) tetap terkurung saat tabel lebih lebar dari wadahnya.
    //    Tanpa ini, elemen sr-only kabur ke <html> dan menyeret scrollbar
    //    mendatar ke seluruh badan halaman (bug 2026-08-30, dijaga tuntas
    //    oleh tests/Browser/uji-lebar-halaman.mjs).
    foreach (['components/sim/tabel-ringkas.blade.php', 'components/sim/data-table.blade.php'] as $komponen) {
        $isi = file_get_contents(resource_path('views/'.$komponen));

        expect($isi)->toMatch('/[\'"][^\'"]*\brelative\b[^\'"]*\boverflow-x-auto\b/');
    }
});

it('melarang view mengambil datanya sendiri', function () {
    /*
        Penjaga hasil ide C, ditambahkan 2026-08-27.

        View menampilkan; rute dan ViewServiceProvider yang mengambil data.
        Aturannya bukan soal kerapian melainkan biaya Tahap 4: selama view
        memanggil sendiri sumber datanya, mengganti `DummyData` dengan Eloquent
        berarti menyunting berkas viewnya, dan setiap pemanggilan yang berada
        di dalam perulangan berubah menjadi satu kueri per baris.

        Penyisiran ini menemukan tujuh N+1 nyata, seluruhnya berbentuk sama:
        satu pemanggilan yang menelusuri seluruh tabel, diletakkan di dalam
        `@foreach`.

        Larangan ini WAJIB punya penjaga, sebab pelanggarannya tidak
        memerahkan apa pun. View yang memanggil datanya sendiri tetap merender
        halaman yang benar; yang membusuk hanya biaya penggantiannya kelak.
        Itu persis bentuk yang sudah tercatat pada notes.md 1g.5.
    */
    $galat = [];

    foreach (BerkasBlade::semua() as $path) {
        $isi = file_get_contents($path);
        $nama = BerkasBlade::namaPendek($path);

        foreach (preg_split('/\r?\n/', $isi) as $nomor => $baris) {
            // Hanya pemanggilan sungguhan; penyebutan di dalam komentar
            // penjelas dibiarkan, sebab yang dilarang adalah pengambilan data.
            if (preg_match('/DummyData::\w+\s*\(/', $baris) === 1) {
                $galat[] = $nama.':'.($nomor + 1).' '.trim($baris);
            }
        }
    }

    expect($galat)->toBe([]);
});

it('menempatkan field pemilik sebelum satuan permukiman pada form lahan', function () {
    $sumber = file_get_contents(resource_path('views/pages/lahan/form.blade.php'));

    $posPemilik = strpos($sumber, 'nama="transmigran_id"');
    $posSp = strpos($sumber, 'x-sim.wilayah-picker');

    expect($posPemilik)->not->toBeFalse()
        ->and($posSp)->not->toBeFalse()
        ->and($posPemilik)->toBeLessThan($posSp);
});

it('menempatkan section penghunian dan wilayah sebelum spesifikasi bangunan pada form rumah', function () {
    $sumber = file_get_contents(resource_path('views/pages/rumah/form.blade.php'));

    $posPenghunian = strpos($sumber, 'Penghunian & Wilayah');
    $posBangunan = strpos($sumber, 'Spesifikasi Bangunan');
    $posPenghuni = strpos($sumber, 'nama="transmigran_id"');
    $posNoRumah = strpos($sumber, 'name="no_rumah"');

    expect($posPenghunian)->not->toBeFalse()
        ->and($posBangunan)->not->toBeFalse()
        ->and($posPenghunian)->toBeLessThan($posBangunan)
        ->and($posPenghuni)->toBeLessThan($posNoRumah);
});

it('mengalihkan rute lama /infrastruktur dan /infrastruktur/{id} ke /sp/infrastruktur dengan status 301', function () {
    $this->get('/infrastruktur')->assertRedirect('/sp/infrastruktur')->assertStatus(301);
    $this->get('/infrastruktur/1')->assertRedirect('/sp/infrastruktur/1')->assertStatus(301);
});

it('memasang atribut aria-live pada field kalkulasi dinamis', function () {
    expect(file_get_contents(resource_path('views/pages/lahan/form.blade.php')))->toContain('aria-live="polite"')
        ->and(file_get_contents(resource_path('views/pages/panen/form.blade.php')))->toContain('aria-live="polite"')
        ->and(file_get_contents(resource_path('views/pages/transmigran/form.blade.php')))->toContain('aria-live="polite"')
        ->and(file_get_contents(resource_path('views/pages/penanaman/form.blade.php')))->toContain('aria-live="polite"');
});
