<?php

namespace App\Providers;

use App\Enums\Agama;
use App\Enums\BentukWilayah;
use App\Enums\HubunganAnggotaKeluarga;
use App\Enums\JenisKelamin;
use App\Enums\JenisReferensi;
use App\Enums\KegiatanAnggota;
use App\Enums\PendidikanTerakhir;
use App\Enums\PolaPermukiman;
use App\Enums\StatusKeaktifanAnggota;
use App\Enums\StatusPanen;
use App\Enums\TingkatKesuburanTanah;
use App\Models\AnggotaPoktan;
use App\Models\Transmigran;
use App\Support\DataWilayah;
use App\Support\DummyData;
use App\Support\PetaPenggunaTampilan;
use App\Support\RekapLahan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Penyuplai data rujukan untuk berkas form yang dipakai bersama.
 *
 * Halaman yang punya rute sendiri menerima datanya dari rute, sebab di situlah
 * kelak controller Tahap 4 mengambil alih. Berkas form tidak dapat mengikuti
 * pola itu: satu berkas form disisipkan dari INDUK YANG BERBEDA-BEDA, yakni
 * modal tambah dan modal ubah pada halaman daftar, serta modal ubah pada
 * halaman rincian. Menyalurkan opsinya lewat rute berarti tiga rute wajib
 * mengoper isian yang sama persis, dan satu saja yang terlewat menghasilkan
 * dropdown kosong tanpa galat apa pun.
 *
 * Composer menyelesaikannya dari sisi yang benar: opsinya melekat pada FORM,
 * bukan pada siapa yang menyisipkannya. Penyuplaian berjalan malas, hanya
 * ketika form itu benar-benar dirender.
 *
 * Saat Tahap 4 masuk, yang berubah hanya isi `nilaiRujukan()`, dari pembacaan
 * `DummyData` menjadi kueri. Tidak ada satu pun view maupun rute yang ikut
 * disunting.
 */
class ViewServiceProvider extends ServiceProvider
{
    /**
     * Rujukan yang dibutuhkan setiap berkas form.
     *
     * Ditulis mendatar dan lengkap, bukan dibangkitkan dari pola nama, supaya
     * `grep` atas satu nama kunci langsung menunjukkan siapa saja pemakainya.
     *
     * @var array<string, list<string>>
     */
    private const RUJUKAN_FORM = [
        'pages.alsintan.form' => ['daftarPoktan', 'opsiJenisAlsintan', 'opsiKondisi', 'opsiSumberDana', 'anggotaPerPoktan'],
        'pages.saprotan.form' => ['daftarPoktan', 'daftarSatuan', 'daftarKomoditas', 'opsiSumberDana'],
        'pages.infrastruktur.form' => ['daftarSp', 'opsiJenisInfrastruktur', 'opsiSumberDana', 'opsiKondisi'],
        'pages.komoditas.form' => ['daftarSatuan', 'sebaran', 'opsiTipeKomoditas'],
        'pages.rumah.form' => ['transmigranTanpaRumah', 'daftarTransmigran', 'daftarSp', 'opsiKondisiRumah', 'opsiStatusHunian'],
        'pages.poktan.form' => ['daftarSp', 'daftarTransmigran', 'kontakTransmigran', 'lahanTransmigran', 'anggotaKeluargaPerKeluarga', 'opsiJabatanAnggota', 'anggotaPoktanPerPoktan'],
        'pages.poktan.form-anggota' => ['daftarTransmigran', 'kontakTransmigran', 'lahanTransmigran', 'opsiJabatanAnggota', 'anggotaKeluargaPerKeluarga'],
        'pages.lahan.form' => ['daftarTransmigran', 'transmigranTanpaLahan', 'daftarSp'],
        'pages.transmigran.form' => ['daftarSp', 'saranPekerjaan', 'opsiDaerahAsal', 'opsiAgama', 'opsiHubunganAnggota', 'opsiKegiatanAnggota', 'opsiPendidikan', 'opsiJenisKelamin'],
        'pages.panen.form' => ['satuanKomoditas', 'simbolSatuan', 'penanamanUntukPanen'],
        'pages.penanaman.form' => ['daftarPoktan', 'daftarKomoditas', 'petaPoktan', 'petaBenih'],
        'pages.pengaduan.form' => ['petaBidang', 'opsiKategoriPengaduan', 'opsiBidang', 'opsiPrioritasPengaduan', 'daftarSp'],
        'pages.sp.form' => ['daftarDesa', 'daftarKawasan', 'petaKawasanKabupaten', 'opsiPolaPermukiman', 'opsiKesuburanTanah', 'opsiBentukWilayah'],
        'pages.sp.form-kawasan' => ['daftarProvinsi', 'daftarKabupaten'],
        'pages.sp.form-inventaris' => ['daftarSp', 'opsiJenisInventaris', 'opsiSumberDana', 'opsiStatusPenyerahan', 'opsiKondisi'],
        'pages.sp.form-fasilitas' => ['daftarSp', 'opsiJenisFasilitas', 'opsiSumberDana', 'opsiStatusPenyerahan', 'opsiKondisi'],
        'pages.pengguna.form' => ['daftarRole', 'daftarSp'],

        // Modal rincian akun, disisipkan halaman daftar. Berperilaku seperti
        // berkas form: satu berkas melayani seluruh baris secara bergantian.
        'pages.pengguna.detail' => ['daftarPengguna', 'riwayatAkun'],

        'pages.pengguna.form-role' => ['kelompokIzin', 'izinPerRole'],
        'pages.master.form-wilayah' => ['wilayah'],
        'pages.master.form-referensi' => ['daftarBidang'],
    ];

    public function boot(): void
    {
        $this->suplaiRujukanForm();
        $this->suplaiBerkasBersama();
    }

    /**
     * Tata letak dan komponen yang dipakai LINTAS halaman.
     *
     * Ketiganya tidak dapat menerima data dari rute mana pun: `layouts.app`
     * membungkus setiap halaman, sedangkan kedua komponen disisipkan dari
     * tempat yang berbeda-beda. Menyalurkannya lewat rute berarti setiap rute
     * wajib mengoper isian yang sama, dan satu yang terlewat menghasilkan
     * halaman yang rusak tanpa galat.
     */
    private function suplaiBerkasBersama(): void
    {
        // Penanda data contoh, wajib tampil selama aplikasi belum tersambung
        // ke data nyata (ANTISLOP-ID R-17 dan R-38). Saat Tahap 4 masuk, nilai
        // ini berpindah menjadi pengaturan, bukan tetapan pada penyedia data.
        View::composer(['layouts.app', 'layouts.dokumen'], function ($tampilan): void {
            $tampilan->with('memakaiDataContoh', DummyData::MEMAKAI_DATA_CONTOH);
        });

        // Menu pengguna di header, disisipkan `layouts.app` pada setiap halaman.
        // Task 3.13: dari pengguna sungguhan yang masuk, bukan `DummyData`.
        View::composer('components.header.user-dropdown', function ($tampilan): void {
            $pengguna = PetaPenggunaTampilan::untuk(Auth::user());

            $tampilan->with('pengguna', $pengguna)
                ->with('inisialPengguna', DummyData::inisial($pengguna['nama']));
        });

        /*
         * Catatan log membaca PROPNYA SENDIRI, sehingga composernya menerima
         * `namaTabel` dan `recordId` dari data view, bukan dari rute.
         *
         * Komponen ini melayani dua belas halaman rincian, dan baris yang
         * ditampilkannya ditentukan pemanggilnya. Menyalurkannya lewat rute
         * berarti dua belas rute wajib tahu tabel dan id apa yang sedang
         * dirender komponen di dalam halamannya.
         */
        View::composer('components.sim.catatan-log', function ($tampilan): void {
            $data = $tampilan->getData();

            $tampilan->with('riwayat', DummyData::riwayatData(
                $data['namaTabel'],
                (int) $data['recordId'],
            ));
        });
    }

    /**
     * Memasang composer untuk setiap berkas form yang terdaftar.
     */
    private function suplaiRujukanForm(): void
    {
        foreach (self::RUJUKAN_FORM as $berkas => $daftarKunci) {
            View::composer($berkas, function ($tampilan) use ($daftarKunci): void {
                foreach ($daftarKunci as $kunci) {
                    $tampilan->with($kunci, self::nilaiRujukan($kunci));
                }
            });
        }
    }

    /**
     * Satu-satunya tempat nama kunci diterjemahkan menjadi datanya.
     *
     * `opsiReferensi()` dan `opsiFilterReferensi()` sengaja TIDAK dipertukarkan.
     * Yang pertama hanya memuat nilai aktif sebab form menawarkan pilihan untuk
     * data baru; yang kedua ikut memuat nilai nonaktif sebab filter menyaring
     * data lama. Kunci berawalan `opsiFilter` karena itu milik halaman daftar,
     * dan tidak pernah muncul pada berkas form.
     */
    private static function nilaiRujukan(string $kunci): mixed
    {
        return match ($kunci) {
            'daftarPoktan' => DummyData::poktan(),
            'daftarSatuan' => DummyData::satuan(),
            'daftarKomoditas' => DummyData::komoditas(),
            'daftarSp' => DummyData::satuanPermukiman(),

            // Task 5.1/5.2 -> transmigran ber-Eloquent; Task 5.3 -> rumah juga,
            // sehingga `transmigranTanpaRumah` wajib membaca tabel `rumah` nyata
            // agar rumah yang baru didata langsung menyingkirkan KK-nya dari
            // daftar. `daftarTransmigran` dipakai juga form poktan/lahan (Tahap 6,
            // masih DummyData) -- bentuknya dijaga sama seperti `DummyData::transmigran()`.
            'daftarTransmigran' => self::daftarTransmigran(),
            'transmigranTanpaRumah' => self::daftarTransmigran(fn ($q) => $q->whereDoesntHave('rumah')),
            'transmigranTanpaLahan' => self::daftarTransmigran(fn ($q) => $q->whereDoesntHave('lahan')),
            'sebaran' => DummyData::sebaranKomoditas(),

            // Hanya nama pekerjaannya yang dipakai, sebagai saran `<datalist>`.
            // Cacahnya tidak ikut, sebab isian ini bebas diketik.
            //
            // Pekerjaan SENGAJA tetap bebas diketik, berbeda dari daerah asal
            // di bawah: himpunannya terbuka dan berekor panjang, sehingga
            // mengunci ke data master akan menghalangi petugas ketika menemui
            // pekerjaan yang belum terdaftar.
            'saranPekerjaan' => array_keys(DummyData::sebaranPekerjaan()),

            // Daerah asal justru himpunan TERTUTUP, sehingga dipilih dari data
            // master beserta nama provinsinya sebagai pembeda nama kembar.
            //
            // Namanya `opsiDaerahAsal`, BUKAN `daftarKabupaten`: kunci itu
            // sudah dipakai form kawasan dengan bentuk yang berbeda
            // (`id_kabupaten`, terbatas wilayah lokus). Dua daftar berbeda
            // berbagi satu nama akan saling menimpa diam-diam.
            'opsiDaerahAsal' => DataWilayah::opsiKabupaten(),

            'opsiKondisi' => DummyData::opsiReferensi(JenisReferensi::Kondisi),
            'opsiKondisiRumah' => DummyData::opsiReferensi(JenisReferensi::KondisiRumah),
            'opsiStatusHunian' => DummyData::opsiReferensi(JenisReferensi::StatusHunian),
            'opsiJenisInfrastruktur' => DummyData::opsiReferensi(JenisReferensi::JenisInfrastruktur),
            'opsiJenisAlsintan' => DummyData::opsiReferensi(JenisReferensi::JenisAlsintan),
            'opsiTipeKomoditas' => DummyData::opsiReferensi(JenisReferensi::TipeKomoditas),
            'daftarRole' => DummyData::role(),
            'daftarPengguna' => DummyData::pengguna(),

            /*
             * Riwayat tindakan pada akun.
             *
             * Modal rinciannya melayani seluruh baris secara bergantian,
             * sehingga akun yang sedang dibuka baru diketahui Alpine saat modal
             * dipanggil. Penyaringan per akun karena itu dilakukan di sisi
             * klien memakai `record_id`, bukan di sini.
             */
            'riwayatAkun' => array_values(array_filter(
                DummyData::auditLog(),
                fn ($baris) => $baris['nama_tabel'] === 'user',
            )),

            'wilayah' => DummyData::wilayah(),
            'kelompokIzin' => DummyData::daftarIzin(),

            // Termasuk yang NONAKTIF, sebab form referensi menampilkan bidang
            // penanganan yang sudah tercatat pada baris lama.
            'daftarBidang' => DummyData::referensi(JenisReferensi::BidangPengaduan, true),

            /*
             * Izin milik setiap role, dipetakan menurut id.
             *
             * Formnya membaca satu role saja, yakni yang sedang disunting,
             * tetapi role itu hanya diketahui induk yang menyisipkan form.
             * Memetakan seluruhnya lebih murah daripada memaksa tiga rute
             * mengoper izin role yang berbeda-beda: jumlah role tetap menurut
             * prd.md, dan daftarnya pendek.
             */
            'izinPerRole' => collect(DummyData::role())
                ->mapWithKeys(fn ($r) => [(int) $r['id_role'] => DummyData::izinRole((int) $r['id_role'])])
                ->all(),

            'anggotaPoktanPerPoktan' => AnggotaPoktan::query()
                ->where('status', StatusKeaktifanAnggota::Aktif->value)
                ->orderBy('id_anggota_poktan')
                ->get()
                ->groupBy('poktan_id')
                ->map(fn ($grup) => $grup->map(fn ($a) => [
                    'transmigran_id' => $a->transmigran_id,
                    'jabatan' => $a->jabatan,
                    'keterangan' => $a->keterangan ?? '',
                ])->values()->all())
                ->mapWithKeys(fn ($v, $k) => [(int) $k => $v])
                ->all(),

            'opsiStatusPenyerahan' => DummyData::opsiReferensi(JenisReferensi::StatusPenyerahan),
            'opsiJenisFasilitas' => DummyData::opsiReferensi(JenisReferensi::JenisFasilitas),
            'opsiJenisInventaris' => DummyData::opsiReferensi(JenisReferensi::JenisInventaris),
            'daftarKawasan' => DummyData::kawasan(),
            'daftarProvinsi' => DummyData::wilayah()['provinsi'],
            'daftarKabupaten' => DummyData::wilayah()['kabupaten'],

            // Desa membawa `kabupaten_id` turunan, dibaca lewat kecamatannya.
            // Dipakai form SP untuk menyaring desa menurut kabupaten kawasan
            // terpilih. Diturunkan di sini, bukan di view, sebab view dilarang
            // mengambil datanya sendiri.
            'daftarDesa' => DummyData::desaBerkabupaten(),

            // Peta id kawasan ke id kabupatennya, dibaca Alpine pada form SP.
            'petaKawasanKabupaten' => array_column(
                DummyData::kawasan(), 'kabupaten_id', 'id_kawasan_transmigrasi'
            ),
            'opsiKategoriPengaduan' => DummyData::opsiReferensi(JenisReferensi::KategoriPengaduan),
            'opsiBidang' => DummyData::opsiReferensi(JenisReferensi::BidangPengaduan),
            'opsiPrioritasPengaduan' => DummyData::opsiReferensi(JenisReferensi::PrioritasPengaduan),

            // Peta kategori ke bidang, dibaca Alpine agar bidang terisi seketika
            // saat kategori dipilih. Kategori netral bernilai string kosong, dan
            // nilainya SELALU dapat ditimpa petugas (rules.md 5.0b).
            'petaBidang' => DummyData::petaBidangKategori(),
            'opsiSumberDana' => DummyData::opsiReferensi(JenisReferensi::SumberDana),
            'opsiJabatanAnggota' => DummyData::opsiReferensi(JenisReferensi::JabatanAnggotaPoktan),

            // Enum langsung, bukan lewat data master: keenamnya baku dari
            // Dukcapil dan tidak di-CRUD dinas (keputusan pemilik proyek
            // 2026-08-28, Rombongan B).
            'opsiAgama' => Agama::opsi(),
            'opsiHubunganAnggota' => HubunganAnggotaKeluarga::opsi(),
            'opsiKegiatanAnggota' => KegiatanAnggota::opsi(),
            'opsiPendidikan' => PendidikanTerakhir::opsi(),
            'opsiJenisKelamin' => JenisKelamin::opsi(),

            // Enum "Keadaan Wilayah" SP (Rombongan C, 2026-08-28), baku dari
            // format Monografi, bukan data master.
            'opsiPolaPermukiman' => PolaPermukiman::opsi(),
            'opsiKesuburanTanah' => TingkatKesuburanTanah::opsi(),
            'opsiBentukWilayah' => BentukWilayah::opsi(),

            // Anggota keluarga dikelompokkan per keluarga, agar pilihan wakil
            // maupun ketua poktan menyempit begitu keluarganya dipilih
            // (Stage B2, 2026-08-28).
            'anggotaKeluargaPerKeluarga' => DummyData::anggotaKeluargaPerKeluarga(),

            'kontakTransmigran' => self::petaKeluarga()['kontak'],
            'lahanTransmigran' => self::petaKeluarga()['lahan'],

            /*
             * Peta satuan baku per komoditas dan simbolnya, keduanya dibaca
             * dari data master. Sebelumnya ditulis tangan sebagai larik harfiah
             * di dalam form, sehingga komoditas maupun satuan baru yang didata
             * Admin tidak pernah punya satuan maupun singkatan.
             */
            'satuanKomoditas' => collect(DummyData::komoditas())
                ->pluck('satuan', 'id_komoditas')
                ->all(),
            'simbolSatuan' => collect(DummyData::satuan())
                ->mapWithKeys(fn ($s) => [$s['nama'] => $s['simbol']])
                ->all(),

            'penanamanUntukPanen' => self::penanamanUntukPanen(),

            /*
             * Kekuatan tiap poktan: cacah anggota aktif, luas lahan, dan sisa
             * lahan yang belum ditanami. Dibaca Alpine agar isian terkunci ikut
             * berubah begitu poktan dipilih, tanpa permintaan tambahan ke
             * peladen.
             */
            'petaPoktan' => collect(DummyData::poktan())
                ->mapWithKeys(function ($p) {
                    $rekap = DummyData::rekapLahanPoktan($p['id_poktan']);

                    return [(string) $p['id_poktan'] => [
                        'sp_id' => (string) $p['satuan_permukiman_id'],
                        'sp_nama' => $p['satuan_permukiman'],
                        'anggota' => $rekap['jumlah_anggota'],
                        'luas' => $rekap['luas_total'],
                        'tersedia' => DummyData::lahanTersedia($p['id_poktan']),
                    ]];
                })
                ->all(),

            'petaBenih' => self::petaBenih(),

            /*
             * Anggota AKTIF tiap poktan, dipetakan menurut id poktannya.
             *
             * Dibaca Alpine pada form alsintan agar pilihan "Penerima" ikut
             * berubah begitu kelompok tani dipilih, tanpa permintaan tambahan
             * ke peladen. Anggota yang sudah keluar tidak ditawarkan sebagai
             * penanda tangan serah terima baru.
             */
            'anggotaPerPoktan' => collect(DummyData::anggotaPoktan())
                ->filter(fn ($a) => $a['status'] === 'Aktif')
                ->groupBy(fn ($a) => (string) $a['poktan_id'])
                ->map(fn ($grup) => $grup->map(fn ($a) => [
                    'id' => (string) $a['id_anggota_poktan'],
                    'nama' => $a['nama'],
                    'jabatan' => $a['jabatan'],
                ])->values()->all())
                ->all(),

            default => throw new \InvalidArgumentException("Kunci rujukan tidak dikenal: {$kunci}"),
        };
    }

    /**
     * Daftar transmigran ringkas untuk `pilih-cari` pada form rumah, poktan,
     * dan lahan. Ber-kunci sama seperti `DummyData::transmigran()` yang
     * dipakai berdampingan modul Tahap 6.
     *
     * @param  (callable(Builder): mixed)|null  $saring
     * @return array<int, array<string, mixed>>
     */
    private static function daftarTransmigran(?callable $saring = null): array
    {
        $kueri = Transmigran::query()->with('satuanPermukiman')->orderBy('id_transmigran');

        if ($saring !== null) {
            $saring($kueri);
        }

        return $kueri->get()->map(fn (Transmigran $t) => [
            'id_transmigran' => $t->id_transmigran,
            'nama_kepala_keluarga' => $t->nama_kepala_keluarga,
            'nik' => $t->nik,
            'satuan_permukiman' => $t->satuanPermukiman?->nama,
            'satuan_permukiman_id' => $t->satuan_permukiman_id,
        ])->all();
    }

    /**
     * Peta id keluarga ke telepon dan rekap lahannya, dipakai di sisi klien
     * agar mengisi kontak serta luas ketua tidak menuntut permintaan tambahan
     * ke peladen hanya untuk membaca satu nomor.
     *
     * Disusun sekali lalu diingat, sebab `rekapLahanKeluarga()` menelusuri
     * seluruh bidang lahan untuk SETIAP keluarga. Dahulu perulangan ini
     * ditulis dua kali, di `poktan.form` dan `poktan.form-anggota`, dan
     * keduanya dapat muncul pada halaman yang sama sehingga penelusurannya
     * berjalan dua kali penuh.
     *
     * Ingatannya hidup selama satu permintaan. Saat Tahap 6 menggantinya
     * dengan kueri, yang tepat adalah satu kueri agregat, bukan pengingat ini.
     *
     * @return array{kontak: array<string, string>, lahan: array<string, mixed>}
     */
    private static function petaKeluarga(): array
    {
        static $peta = null;

        if ($peta !== null) {
            return $peta;
        }

        $kontak = [];
        $lahan = [];

        // Task 6: transmigran + lahan ber-Eloquent. Satu keluarga tepat satu
        // baris lahan, jadi `with('lahan')` cukup tanpa agregat.
        foreach (Transmigran::query()->with('lahan')->get() as $t) {
            $kunci = (string) $t->id_transmigran;
            $kontak[$kunci] = $t->telepon ?? '';
            $lahan[$kunci] = RekapLahan::keluarga($t->lahan);
        }

        return $peta = ['kontak' => $kontak, 'lahan' => $lahan];
    }

    /**
     * Seluruh benih yang masih bersisa, dikelompokkan agar Alpine dapat
     * menyaringnya tanpa permintaan tambahan ke peladen.
     *
     * Benih yang stoknya habis TIDAK ada di sini sama sekali (kamus data 8.4).
     *
     * @return list<array<string, mixed>>
     */
    private static function petaBenih(): array
    {
        /*
         * Simbol satuan dibaca dari data master, bukan disingkat sendiri.
         * Menyingkatnya lewat `substr` atau daftar tulis tangan berarti satuan
         * baru yang didata Admin tidak akan pernah punya singkatan.
         */
        $simbol = collect(DummyData::satuan())->mapWithKeys(fn ($s) => [$s['nama'] => $s['simbol']])->all();

        return collect(DummyData::benihTersedia())
            ->map(fn ($b) => [
                // Sejak Putaran 7 id yang dibawa adalah id_saprotan_distribusi:
                // penanaman menunjuk jatah SATU poktan, bukan pengadaan.
                'id' => (string) $b['id_saprotan_distribusi'],
                'poktan_id' => (string) $b['poktan_id'],
                'komoditas_id' => (string) $b['komoditas_id'],
                'label' => $b['label_benih'],
                'sisa' => $b['sisa_benih'],
                'satuan' => $b['satuan'],

                // Dipakai sebagai sufiks isian, yang ruangnya sempit: nama
                // penuh "Kilogram" menabrak tombol naik-turun bawaan number.
                'simbol' => $simbol[$b['satuan']] ?? $b['satuan'],
            ])
            ->all();
    }

    /**
     * Penanaman sebagai bahan pilihan pada form panen, sudah lengkap dengan
     * label, status panennya, dan rekap lahan poktannya.
     *
     * Yang MENYARING tetap viewnya, sebab baris yang sedang disunting wajib
     * ikut ditawarkan meski sudah dipanen, dan baris itu hanya diketahui induk
     * yang menyisipkan form. Yang dipindahkan ke sini adalah pengambilan
     * datanya: `statusPanen()` dan `rekapLahanPoktan()` dahulu dipanggil di
     * dalam perulangan seluruh penanaman, dan keduanya menyusuri catatan panen
     * serta keanggotaan poktan setiap kali.
     *
     * @return list<array{id: string, belum_dipanen: bool, baris: array<string, mixed>, peta: array<string, mixed>}>
     */
    private static function penanamanUntukPanen(): array
    {
        static $hasil = null;

        if ($hasil !== null) {
            return $hasil;
        }

        $satuanKomoditas = collect(DummyData::komoditas())->pluck('satuan', 'id_komoditas')->all();
        $simbolSatuan = collect(DummyData::satuan())->mapWithKeys(fn ($s) => [$s['nama'] => $s['simbol']])->all();

        $hasil = [];

        foreach (DummyData::penanaman() as $r) {
            $rekap = DummyData::rekapLahanPoktan($r['poktan_id']);
            $bulan = Carbon::parse($r['periode_tanam'].'-01');
            $satuan = $satuanKomoditas[$r['komoditas_id']] ?? '';

            /*
             * Bulan tanam menggantikan label musim yang dicabut 2026-08-22. Ia
             * yang membedakan dua penanaman komoditas yang sama oleh kelompok
             * yang sama; tanpa itu keduanya tampil sebagai pilihan yang
             * bunyinya identik.
             */
            $label = $r['komoditas'].' - '.$r['poktan'].' - '.$bulan->translatedFormat('M Y');

            $hasil[] = [
                'id' => (string) $r['id_penanaman'],
                'belum_dipanen' => DummyData::statusPanen($r['id_penanaman']) === StatusPanen::BelumDipanen,
                'baris' => $r + ['label_tanam' => $label],
                'peta' => [
                    'poktan' => $r['poktan'],
                    'poktan_id' => (string) $r['poktan_id'],
                    'anggota' => $rekap['jumlah_anggota'],
                    'luas_lahan' => $rekap['luas_total'],
                    'volume_benih' => $r['volume_benih'],
                    'realisasi_tanam' => (float) $r['realisasi_tanam'],
                    'komoditas' => $r['komoditas'],
                    'satuan' => $satuan,
                    'simbol' => $simbolSatuan[$satuan] ?? '',
                    'bulan_tanam' => $bulan->translatedFormat('F Y'),
                    'sp' => $r['satuan_permukiman'],
                ],
            ];
        }

        return $hasil;
    }
}
