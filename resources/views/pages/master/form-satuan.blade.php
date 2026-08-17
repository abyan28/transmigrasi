{{--
    Isian data master satuan beserta faktor konversinya ke ton.

    Inilah tabel yang membuat rekap lintas komoditas sepadan. Tanpa faktor
    konversi, panen 100 kuintal dan 100 ton akan dijumlahkan begitu saja
    (agents/rules.md bagian 8a).

    Pratinjau perhitungan ditampilkan langsung agar kekeliruan faktor terlihat
    saat mengisi, bukan berbulan kemudian ketika rekap tampak janggal.

    Nama kolom mengikuti agents/data-dictionary.md bagian 5.1.
--}}
@php
    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
@endphp

<div class="space-y-6"
    x-data="{ faktor: @js((string) old('faktor_ke_ton', $data['faktor_ke_ton'] ?? '')) }">

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $awalan }}_nama_satuan" class="{{ $kelasLabel }}">Nama Satuan<span class="text-error-500">*</span></label>
            <input type="text" id="{{ $awalan }}_nama_satuan" name="nama" required
                value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="50"
                placeholder="Contoh: Kuintal" class="{{ $kelasKontrol }}" />
        </div>

        <div>
            <label for="{{ $awalan }}_simbol" class="{{ $kelasLabel }}">Simbol<span class="text-error-500">*</span></label>
            <input type="text" id="{{ $awalan }}_simbol" name="simbol" required
                value="{{ old('simbol', $data['simbol'] ?? '') }}" maxlength="10"
                placeholder="Contoh: kw" class="{{ $kelasKontrol }}" />
        </div>

        <div class="sm:col-span-2">
            <label for="{{ $awalan }}_faktor_ke_ton" class="{{ $kelasLabel }}">Faktor Konversi ke Ton<span class="text-error-500">*</span></label>
            <input type="number" id="{{ $awalan }}_faktor_ke_ton" name="faktor_ke_ton" required x-model="faktor"
                min="0" step="0.000001" placeholder="0.1" class="{{ $kelasKontrol }} tabular-nums" />
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Berapa ton nilai satu satuan ini. Ton bernilai 1, kuintal 0,1, kilogram 0,001.
            </p>
        </div>
    </div>

    {{--
        Pratinjau. Memperlihatkan dampak faktor sebelum disimpan, sebab
        kekeliruan di sini menyebar ke seluruh rekap produksi.
    --}}
    <div x-show="faktor !== '' && Number(faktor) > 0" x-cloak x-transition
        class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-xs font-medium text-gray-800 dark:text-white/90">Pratinjau konversi</p>
        <ul class="mt-2 space-y-1 text-theme-xs text-gray-600 dark:text-gray-400">
            <template x-for="jumlah in [1, 10, 100]" :key="jumlah">
                <li class="tabular-nums">
                    <span x-text="jumlah.toLocaleString('id-ID')"></span> satuan ini
                    setara
                    <span class="font-medium text-gray-800 dark:text-white/90"
                        x-text="(jumlah * Number(faktor)).toLocaleString('id-ID', { maximumFractionDigits: 6 })"></span>
                    ton
                </li>
            </template>
        </ul>
        <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
            Periksa angka ini sebelum menyimpan. Faktor yang keliru membuat seluruh rekap panen yang
            memakai satuan ini ikut salah.
        </p>
    </div>
</div>
