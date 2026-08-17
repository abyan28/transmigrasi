{{--
    Isian data master wilayah administratif.

    Satu form untuk empat tingkat, karena strukturnya sama: nama, kode, dan
    satu induk. Memisahkannya menjadi empat form akan mengulang isian yang
    sama persis tanpa alasan.

    Induk berubah mengikuti tingkat yang dipilih, dan tingkat provinsi tidak
    memiliki induk sama sekali. Menampilkan pilihan induk untuk provinsi akan
    menyiratkan hierarki yang tidak ada.

    Nama kolom mengikuti agents/data-dictionary.md bagian 3.1 sampai 3.4.
--}}
@php
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';

    $wilayah = DummyData::wilayah();
@endphp

<div class="space-y-6" x-data="{ tingkat: @js(old('tingkat', $data['tingkat'] ?? 'desa')) }">

    <div>
        <label for="{{ $awalan }}_tingkat" class="{{ $kelasLabel }}">Tingkat Wilayah</label>
        <select id="{{ $awalan }}_tingkat" name="tingkat" x-model="tingkat" class="{{ $kelasKontrol }}">
            <option value="provinsi">Provinsi</option>
            <option value="kabupaten">Kabupaten</option>
            <option value="kecamatan">Kecamatan</option>
            <option value="desa">Desa</option>
        </select>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $awalan }}_nama_wilayah" class="{{ $kelasLabel }}">Nama Wilayah<span class="text-error-500">*</span></label>
            <input type="text" id="{{ $awalan }}_nama_wilayah" name="nama" required
                value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                placeholder="Contoh: Kapitan Meo" class="{{ $kelasKontrol }}" />
        </div>

        <div>
            <label for="{{ $awalan }}_kode_wilayah" class="{{ $kelasLabel }}">Kode Wilayah</label>
            <input type="text" id="{{ $awalan }}_kode_wilayah" name="kode"
                value="{{ old('kode', $data['kode'] ?? '') }}" maxlength="20"
                placeholder="Contoh: 5321" class="{{ $kelasKontrol }} tabular-nums" />
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Mengikuti kode wilayah baku pemerintah bila tersedia.
            </p>
        </div>
    </div>

    {{-- Induk, berubah mengikuti tingkat. Provinsi tidak memilikinya. --}}
    <div x-show="tingkat === 'provinsi'" x-cloak x-transition
        class="rounded-lg bg-gray-50 p-4 dark:bg-white/[0.03]">
        <p class="text-theme-xs text-gray-600 dark:text-gray-400">
            Provinsi berada pada tingkat teratas, sehingga tidak memiliki wilayah induk.
        </p>
    </div>

    <div x-show="tingkat === 'kabupaten'" x-cloak x-transition>
        <label for="{{ $awalan }}_induk_provinsi" class="{{ $kelasLabel }}">Provinsi Induk<span class="text-error-500">*</span></label>
        <select id="{{ $awalan }}_induk_provinsi" name="provinsi_id" required class="{{ $kelasKontrol }}">
            <option value="">Pilih provinsi</option>
            @foreach ($wilayah['provinsi'] as $p)
                <option value="{{ $p['id_provinsi'] }}">{{ $p['nama'] }}</option>
            @endforeach
        </select>
    </div>

    <div x-show="tingkat === 'kecamatan'" x-cloak x-transition>
        <label for="{{ $awalan }}_induk_kabupaten" class="{{ $kelasLabel }}">Kabupaten Induk<span class="text-error-500">*</span></label>
        <select id="{{ $awalan }}_induk_kabupaten" name="kabupaten_id" required class="{{ $kelasKontrol }}">
            <option value="">Pilih kabupaten</option>
            @foreach ($wilayah['kabupaten'] as $k)
                <option value="{{ $k['id_kabupaten'] }}">{{ $k['nama'] }}</option>
            @endforeach
        </select>
    </div>

    <div x-show="tingkat === 'desa'" x-cloak x-transition>
        <label for="{{ $awalan }}_induk_kecamatan" class="{{ $kelasLabel }}">Kecamatan Induk<span class="text-error-500">*</span></label>
        <select id="{{ $awalan }}_induk_kecamatan" name="kecamatan_id" required class="{{ $kelasKontrol }}">
            <option value="">Pilih kecamatan</option>
            @foreach ($wilayah['kecamatan'] as $k)
                <option value="{{ $k['id_kecamatan'] }}">{{ $k['nama'] }}</option>
            @endforeach
        </select>
        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
            Satuan permukiman menempel pada desa, sekaligus pada kawasan transmigrasi yang diatur terpisah.
        </p>
    </div>
</div>
