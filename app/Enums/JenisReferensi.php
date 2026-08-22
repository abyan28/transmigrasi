<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Jenis daftar pilihan yang dikelola Admin lewat data master referensi.
 *
 * ENUM INI SENDIRI TIDAK IKUT MENJADI DATA MASTER, dan itu disengaja. Ia
 * menyatakan daftar mana saja yang ada, bukan isi daftarnya. Menjadikannya
 * data juga berarti Admin dapat membuat jenis baru yang tidak satu pun kolom
 * database menunjuknya, sehingga isian yang dikelolanya tidak pernah tampil
 * di mana pun.
 *
 * Setiap nilai di sini WAJIB punya kolom yang membacanya. Menambah satu nilai
 * karena itu selalu berpasangan dengan menyunting kolom pada kamus data.
 *
 * Dua jenis memakai kolom tambahan pada tabel `referensi`:
 * - `kondisi` memakai `nilai_skor`, sebab nilainya dipakai menghitung skor
 *   kondisi SP (agents/rules.md bagian 10c). `kondisi_rumah` TIDAK, meski
 *   tampak serupa; lihat keterangan pada berskor().
 * - `prioritas_pengaduan` memakai `urutan`, sebab ia skala berjenjang yang
 *   dipakai menyortir daftar pengaduan.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.37.
 */
enum JenisReferensi: string
{
    use PunyaLabel;

    case SumberDana = 'sumber_dana';
    case StatusPenyerahan = 'status_penyerahan';
    case Kondisi = 'kondisi';
    case KondisiRumah = 'kondisi_rumah';
    case StatusHunian = 'status_hunian';
    case TipeKomoditas = 'tipe_komoditas';
    case PrioritasPengaduan = 'prioritas_pengaduan';
    case JenisDokumenLahan = 'jenis_dokumen_lahan';
    case JabatanAnggotaPoktan = 'jabatan_anggota_poktan';
    case JenisInfrastruktur = 'jenis_infrastruktur';
    case JenisFasilitas = 'jenis_fasilitas';
    case BidangPengaduan = 'bidang_pengaduan';
    case KategoriPengaduan = 'kategori_pengaduan';

    /**
     * Teks yang tampil sebagai judul tab pada halaman data master.
     *
     * @return string Label berbahasa Indonesia
     */
    public function label(): string
    {
        return match ($this) {
            self::SumberDana => 'Sumber Dana',
            self::StatusPenyerahan => 'Status Penyerahan',
            self::Kondisi => 'Kondisi Aset',
            self::KondisiRumah => 'Kondisi Rumah',
            self::StatusHunian => 'Status Hunian',
            self::TipeKomoditas => 'Tipe Komoditas',
            self::PrioritasPengaduan => 'Prioritas Pengaduan',
            self::JenisDokumenLahan => 'Jenis Dokumen Lahan',
            self::JabatanAnggotaPoktan => 'Jabatan Anggota Poktan',
            self::JenisInfrastruktur => 'Jenis Infrastruktur',
            self::JenisFasilitas => 'Jenis Fasilitas',
            self::BidangPengaduan => 'Bidang Penanganan',
            self::KategoriPengaduan => 'Kategori Pengaduan',
        };
    }

    /**
     * Kelompok tempat daftar ini tampil pada halaman indeks data master.
     *
     * WAJIB ADA UNTUK SETIAP JENIS. Jenis tanpa kelompok tidak muncul di
     * indeks sama sekali, dan karena indeks adalah satu-satunya jalan menuju
     * halamannya, daftar itu menjadi tidak terjangkau tanpa mengetik alamatnya
     * sendiri. `match` tanpa `default` di sini disengaja: jenis baru yang lupa
     * dikelompokkan akan memerah saat itu juga, bukan menghilang diam-diam.
     *
     * Pengelompokannya mengikuti MODUL YANG MEMAKAINYA, bukan kemiripan
     * bentuk daftarnya. Petugas mencari `jenis_fasilitas` karena sedang
     * mengurus aset satuan permukiman, bukan karena ingat isinya sembilan.
     *
     * @return KelompokReferensi Kelompok pada halaman indeks
     */
    public function kelompok(): KelompokReferensi
    {
        return match ($this) {
            self::SumberDana,
            self::StatusPenyerahan,
            self::Kondisi,
            self::JenisInfrastruktur,
            self::JenisFasilitas => KelompokReferensi::AsetInfrastruktur,

            self::KondisiRumah,
            self::StatusHunian,
            self::JenisDokumenLahan => KelompokReferensi::RumahLahan,

            self::TipeKomoditas,
            self::JabatanAnggotaPoktan => KelompokReferensi::Pertanian,

            self::KategoriPengaduan,
            self::BidangPengaduan,
            self::PrioritasPengaduan => KelompokReferensi::Pengaduan,
        };
    }

    /**
     * Menandai jenis yang nilainya dipakai menghitung skor kondisi SP.
     *
     * HANYA `kondisi`, bukan `kondisi_rumah`. Keduanya tampak sebagai skala
     * kerusakan yang sama, tetapi hanya `kondisi` yang dibaca
     * `PenilaianKondisiSp`; kondisi rumah murni tampilan dan tidak pernah
     * masuk perhitungan mana pun. Memberi `nilai_skor` kepadanya berarti
     * menyediakan isian yang tidak menentukan apa pun, dan Admin yang
     * menyuntingnya akan menyangka skor SP ikut berubah.
     *
     * Bagi jenis berskor, mengubah nilainya mengubah cara penilaian BERIKUTNYA
     * dihitung, tetapi TIDAK mengubah penilaian yang sudah tersimpan:
     * `penilaian_sp.rincian` menyalin nilai yang berlaku saat penilaian dibuat
     * (kamus data 5.5).
     *
     * @return bool True bila jenis ini berskor
     */
    public function berskor(): bool
    {
        return $this === self::Kondisi;
    }

    /**
     * Menandai jenis yang urutannya bermakna, bukan sekadar tampilan.
     *
     * Prioritas pengaduan adalah skala berjenjang: Rendah sampai Mendesak.
     * Daftar pengaduan menyortir memakai urutan ini, sehingga menukarnya
     * mengubah urutan antrean petugas.
     *
     * @return bool True bila urutannya bermakna
     */
    public function berjenjang(): bool
    {
        return $this === self::PrioritasPengaduan;
    }

    /**
     * Menandai jenis yang nilainya dirujuk parameter penilaian kondisi SP.
     *
     * `parameter_penilaian_sp.jenis_rujukan` menunjuk satu nilai pada kedua
     * daftar ini, misalnya parameter `air_bersih` menunjuk jenis infrastruktur
     * `Air`. Rujukan itu memakai `referensi_id`, bukan teks: bila Admin
     * memperbaiki ejaan `Air` menjadi `Air Bersih`, rujukan berbasis teks putus
     * tanpa pesan apa pun dan parameter itu diam-diam menilai SP sebagai tidak
     * punya air, sehingga status SP jatuh karena satu penyuntingan ejaan.
     *
     * Inilah satu-satunya pengecualian dari aturan "yang tersimpan adalah
     * teks" pada kamus data 5.6, dan alasannya justru dampaknya: sembilan
     * daftar lain hanya menampilkan teksnya kembali, sedangkan dua daftar ini
     * menentukan hasil perhitungan.
     *
     * @return bool True bila nilainya dirujuk parameter penilaian
     */
    public function dirujukParameter(): bool
    {
        return $this === self::JenisInfrastruktur || $this === self::JenisFasilitas;
    }

    /**
     * Menandai jenis yang memakai kolom `bidang_id`.
     *
     * Hanya `kategori_pengaduan`. Kolom itu menyimpan bidang penanganan
     * BAWAAN sebuah kategori, menggantikan `match` pada
     * `BidangPengaduan::dariKategori()`. Selama peta itu berupa `match` di
     * dalam kode, kategori tidak boleh ditambah lewat data master: kategori
     * baru akan melempar `UnhandledMatchError` begitu ada yang memilihnya.
     *
     * NULL BERMAKNA, bukan sekadar kosong. Ia menyatakan kategori yang dapat
     * jatuh ke dua dinas sekaligus, sehingga bidangnya wajib ditetapkan
     * petugas sebelum status maju ke Diproses (rules.md 10b poin 7b). Karena
     * itu kolomnya boleh null, dan Admin yang mengosongkannya sedang menyatakan
     * "kategori ini memang perlu ditimbang", bukan "saya lupa mengisi".
     *
     * @return bool True bila jenis ini memakai bidang_id
     */
    public function berbidang(): bool
    {
        return $this === self::KategoriPengaduan;
    }
}
