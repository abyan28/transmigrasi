{{--
    Isian satu parameter penilaian kondisi SP.

    Nama kolom mengikuti agents/data-dictionary.md bagian 5.4.

    TIGA HAL SENGAJA TIDAK DAPAT DISUNTING:

    - `jenis` dan `referensi_id`, sebab barisnya dihasilkan dari data master
      jenis. Menggantinya di sini akan membuat parameter mencari aset berjenis
      lain tanpa jejak apa pun pada daftar jenisnya.
    - `kode`, sebab ia penunjuk yang tersalin ke riwayat penilaian. Mengubahnya
      membuat penilaian lama kehilangan pasangannya.
    - `tingkat` pada tiga parameter primer, sebab tingkat itu menentukan aturan
      primer nol. Memindahkan Listrik ke Tersier bukan menurunkan bobotnya,
      melainkan mencabut aturan yang membuat SP tanpa listrik otomatis
      berstatus Perlu Penanganan.
--}}
@php
    use App\Enums\TingkatKebutuhan;

    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';

    // Kode tiga parameter primer yang tingkatnya terkunci.
    $primerTerkunci = ['air_bersih', 'jalan_penghubung', 'listrik'];
@endphp

{{--
    Keadaan form dibaca dari `baris` milik modal-form, bukan dari isian
    tersembunyi. Modal ini dipakai bergantian oleh seluruh baris tabel:
    isiFormulir() menyalin data baris yang diklik ke isian yang namanya cocok,
    sehingga `kode` yang menentukan penguncian tingkat tidak pernah menjadi
    isian dan harus dibaca dari sumbernya langsung.
--}}
<div class="space-y-6"
    x-data="{
        primerTerkunci: @js($primerTerkunci),
        dinilai: false,
        get tingkatTerkunci() { return this.primerTerkunci.includes(this.baris?.kode ?? ''); },
    }"
    x-effect="dinilai = Boolean(baris?.is_dinilai)">

    {{-- Jenis: ditampilkan sebagai keterangan, bukan isian. --}}
    <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/[0.03]">
        <p class="text-theme-xs text-gray-500 dark:text-gray-400">Jenis pada data master</p>
        <p class="mt-0.5 text-theme-sm font-medium text-gray-800 dark:text-white/90"
            x-text="baris?.nilai_jenis ?? '-'"></p>
        <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
            Dibaca dari tabel <span x-text="(baris?.sumber ?? '') === 'Fasilitas' ? 'fasilitas SP' : 'infrastruktur'"></span>.
            Untuk mengubah namanya, sunting jenisnya pada data master referensi.
        </p>
    </div>

    {{--
        Kotak centang penentu, ditaruh paling atas sebab ia menentukan apakah
        isian di bawahnya bermakna.
    --}}
    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
        <label class="inline-flex items-start gap-2 text-theme-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="is_dinilai" value="1" x-model="dinilai"
                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700" />
            <span>
                Ikut dinilai
                <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">
                    Parameter yang tidak dinilai tetap tercatat di sini, tetapi tidak menambah pembagi skor.
                    Aset berjenis ini tetap dapat didata petugas seperti biasa.
                </span>
            </span>
        </label>
    </div>

    <div x-show="dinilai" x-cloak class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="ubahParameter_nama" class="{{ $kelasLabel }}">
                Nama Parameter<span class="text-error-500">*</span>
            </label>
            <input type="text" id="ubahParameter_nama" name="nama" maxlength="100" :required="dinilai"
                value="{{ old('nama', $data['nama'] ?? '') }}" class="{{ $kelasKontrol }}" />
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Boleh berbeda dari nama jenisnya. Jenis menjawab aset ini apa, parameter menjawab apa yang dinilai.
            </p>
        </div>

        <div>
            <label for="ubahParameter_bobot" class="{{ $kelasLabel }}">
                Bobot<span class="text-error-500">*</span>
            </label>
            <input type="number" id="ubahParameter_bobot" name="bobot" min="1" max="99" step="1"
                :required="dinilai" value="{{ old('bobot', $data['bobot'] ?? 1) }}"
                class="{{ $kelasKontrol }} tabular-nums" />
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Makin besar, makin menentukan skor. Bawaan Primer 5, Sekunder 3, Tersier 1.
            </p>
        </div>

        <div class="sm:col-span-2">
            <label for="ubahParameter_tingkat" class="{{ $kelasLabel }}">
                Tingkat Kebutuhan<span class="text-error-500">*</span>
            </label>
            <select id="ubahParameter_tingkat" name="tingkat" :required="dinilai"
                :disabled="tingkatTerkunci" class="{{ $kelasKontrol }}">
                @foreach (TingkatKebutuhan::cases() as $t)
                    <option value="{{ $t->value }}"
                        @selected(old('tingkat', $data['tingkat'] ?? '') === $t->value)>
                        {{ $t->value }} - {{ $t->keterangan() }}
                    </option>
                @endforeach
            </select>

            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400" x-show="! tingkatTerkunci">
                Parameter Primer yang tidak tersedia membuat SP otomatis berstatus Perlu Penanganan,
                berapa pun skornya.
            </p>
            <p class="mt-1.5 text-theme-xs text-warning-700 dark:text-warning-300" x-show="tingkatTerkunci" x-cloak>
                Tingkat parameter ini terkunci. Air bersih, jalan penghubung, dan listrik menentukan kelayakan
                huni: memindahkannya ke tingkat lain bukan menurunkan bobot, melainkan mencabut aturan yang
                membuat SP tanpa salah satunya otomatis berstatus Perlu Penanganan. Bobotnya tetap dapat diubah.
            </p>
        </div>
    </div>
</div>