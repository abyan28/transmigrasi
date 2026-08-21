{{--
    Isian satu nilai referensi, dipakai bersama modal tambah dan modal ubah.

    Nama kolom mengikuti agents/data-dictionary.md bagian 5.6.

    Tiga isian hanya berlaku pada jenis tertentu, dan keduanya disembunyikan
    lewat Alpine ketika tidak berlaku:

    - `nilai_skor` hanya untuk jenis berskor, yaitu `kondisi`. Menyediakannya
      pada jenis lain berarti menawarkan isian yang tidak menentukan apa pun.
    - Keterangan urutan berubah pada jenis berjenjang, sebab di sanalah urutan
      benar-benar dipakai menyortir, bukan sekadar mengatur tampilan.
--}}
@php
    use App\Enums\JenisReferensi;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';

    // Jenis DITENTUKAN HALAMANNYA, tidak lagi dipilih di dalam form. Halaman
    // yang sedang dibuka adalah pernyataan paling jelas tentang daftar mana
    // yang hendak ditambah, dan membiarkannya dapat diganti di sini membuat
    // nilai baru dapat mendarat di daftar lain tanpa petugas menyadarinya.
    $jenisAwal = old('jenis', $data['jenis'] ?? $jenis->value);

    $berskor = array_values(array_map(
        fn (JenisReferensi $j) => $j->value,
        array_filter(JenisReferensi::cases(), fn (JenisReferensi $j) => $j->berskor())
    ));

    $berjenjang = array_values(array_map(
        fn (JenisReferensi $j) => $j->value,
        array_filter(JenisReferensi::cases(), fn (JenisReferensi $j) => $j->berjenjang())
    ));

    $berbidang = array_values(array_map(
        fn (JenisReferensi $j) => $j->value,
        array_filter(JenisReferensi::cases(), fn (JenisReferensi $j) => $j->berbidang())
    ));

    $daftarBidang = \App\Support\DummyData::referensi(JenisReferensi::BidangPengaduan, true);
@endphp

<div class="space-y-6"
    x-data="{
        jenis: @js($jenisAwal),
        berskor: @js($berskor),
        berjenjang: @js($berjenjang),
        berbidang: @js($berbidang),
        get jenisBerskor() { return this.berskor.includes(this.jenis); },
        get jenisBerjenjang() { return this.berjenjang.includes(this.jenis); },
        get jenisBerbidang() { return this.berbidang.includes(this.jenis); },
    }">

    {{--
        Jenis dikirim sebagai isian tersembunyi, BUKAN dropdown.

        Nama isiannya sengaja tetap `jenis` agar sisi penyimpanan tidak perlu
        berubah, tetapi nilainya dikunci ke halaman yang sedang dibuka.
        Sebelumnya ini dropdown berisi keempat belas daftar, dan itu wajar
        ketika seluruhnya berada pada satu halaman bertab; kini halaman itu
        sendiri sudah menyatakan daftarnya.
    --}}
    <input type="hidden" name="jenis" value="{{ $jenisAwal }}" />

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $awalan }}_nilai_referensi" class="{{ $kelasLabel }}">
                Nilai<span class="text-error-500">*</span>
            </label>
            <input type="text" id="{{ $awalan }}_nilai_referensi" name="nilai" required maxlength="100"
                value="{{ old('nilai', $data['nilai'] ?? '') }}"
                placeholder="Contoh: APBD Provinsi" class="{{ $kelasKontrol }}" />
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Teks ini yang tampil pada dropdown sekaligus tersimpan pada datanya.
            </p>
        </div>

        <div>
            <label for="{{ $awalan }}_urutan_referensi" class="{{ $kelasLabel }}">
                Urutan<span class="text-error-500">*</span>
            </label>
            <input type="number" id="{{ $awalan }}_urutan_referensi" name="urutan" required min="1" step="1"
                value="{{ old('urutan', $data['urutan'] ?? 1) }}" class="{{ $kelasKontrol }} tabular-nums" />
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400" x-show="! jenisBerjenjang">
                Mengatur urutan tampil pada dropdown.
            </p>
            <p class="mt-1.5 text-theme-xs text-warning-700 dark:text-warning-300" x-show="jenisBerjenjang"
                x-cloak>
                Daftar ini berjenjang: urutannya dipakai menyortir daftar pengaduan, bukan sekadar tampilan.
            </p>
        </div>

        {{-- Skor hanya untuk jenis berskor. Wajib bila berlaku. --}}
        <div x-show="jenisBerskor" x-cloak>
            <label for="{{ $awalan }}_nilai_skor" class="{{ $kelasLabel }}">
                Skor Penilaian<span class="text-error-500">*</span>
            </label>
            <input type="number" id="{{ $awalan }}_nilai_skor" name="nilai_skor" min="0" max="1" step="0.01"
                value="{{ old('nilai_skor', $data['nilai_skor'] ?? '') }}"
                :required="jenisBerskor" :disabled="! jenisBerskor"
                placeholder="1.00" class="{{ $kelasKontrol }} tabular-nums" />
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Bernilai 0 sampai 1. Dipakai menghitung kondisi satuan permukiman.
            </p>
        </div>
    </div>

    {{--
        Bidang penanganan, hanya untuk kategori pengaduan.

        BOLEH DIKOSONGKAN, dan kosong di sini bermakna. Ia menyatakan kategori
        yang dapat jatuh ke dua dinas sekaligus, sehingga bidangnya wajib
        ditimbang petugas dari isi laporan. Karena itu pilihan kosongnya diberi
        label yang menjelaskan maksudnya, bukan sekadar tanda hubung.
    --}}
    <div x-show="jenisBerbidang" x-cloak>
        <label for="{{ $awalan }}_bidang_id" class="{{ $kelasLabel }}">Bidang Penanganan Bawaan</label>
        <select id="{{ $awalan }}_bidang_id" name="bidang_id" :disabled="! jenisBerbidang"
            class="{{ $kelasKontrol }}">
            <option value="">Ditetapkan petugas per laporan</option>
            @foreach ($daftarBidang as $b)
                <option value="{{ $b['id_referensi'] }}"
                    @selected((string) old('bidang_id', $data['bidang_id'] ?? '') === (string) $b['id_referensi'])>
                    {{ $b['nilai'] }}
                </option>
            @endforeach
        </select>
        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
            Mengisi bidang di sini hanya menetapkan nilai AWAL saat kategori dipilih;
            petugas tetap dapat menimpanya. Mengosongkannya berarti kategori ini
            memang perlu ditimbang, bukan terlewat diisi.
        </p>
    </div>

    {{--
        Penonaktifan, bukan penghapusan. Ditaruh sebagai isian tersendiri agar
        petugas melihat bahwa nilai lama memang tidak dibuang.
    --}}
    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
        <label class="inline-flex items-start gap-2 text-theme-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="is_aktif" value="1"
                @checked(old('is_aktif', $data['is_aktif'] ?? true))
                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700" />
            <span>
                Aktif
                <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">
                    Hanya pilihan aktif yang ditawarkan pada data baru. Menonaktifkan tidak menghapus:
                    data lama yang memakainya tetap terbaca apa adanya.
                </span>
            </span>
        </label>
    </div>
</div>