{{--
    Wadah tabel data beserta pencarian, laci filter, dan paginasi.

    Ketentuan pada agents/ui-spec.md bagian 6.1:
    - pencarian di kanan atas, filter dalam laci yang dapat dilipat,
    - paginasi bawaan 25 baris dengan pilihan 10, 25, 50, 100,
    - kolom aksi selalu di kanan,
    - header lengket saat digulir,
    - wajib punya keadaan kosong.

    Pada layar sempit tabel berubah menjadi daftar kartu (bagian 8), sehingga
    halaman pemakai menyediakan dua tata letak lewat slot `kartu`.

    Pemakaian:
        <x-sim.data-table :jumlah="count($data)" kata-kunci="{{ request('cari') }}">
            <x-slot:filter> ... </x-slot:filter>
            <x-slot:kepala>
                <th scope="col">Nama</th><th scope="col">NIK</th>
            </x-slot:kepala>
            <x-slot:kartu> ... </x-slot:kartu>
            <tr><td>...</td></tr>
        </x-sim.data-table>
--}}
@props([
    'jumlah' => 0,
    'kataKunci' => null,
    'judulKosong' => 'Belum ada data',
    'pesanKosong' => null,
    'placeholderCari' => 'Cari data',
    'perHalaman' => 25,

    /*
        Nama tabel bagi pembaca layar, dirender sebagai <caption> tersembunyi.

        Halaman yang memakai `x-sim.halaman-daftar` menerimanya otomatis dari
        judul halamannya, sehingga tidak satu pun perlu disunting. Yang
        memanggil komponen ini langsung wajib mengisinya sendiri.
    */
    'judul' => null,
])

<div x-data="{ filterTerbuka: false }"
    {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]']) }}>

    {{-- Baris pencarian dan pemicu filter --}}
    <div class="flex flex-col gap-3 border-b border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
        <div class="flex items-center gap-2">
            @isset($filter)
                <button type="button" @click="filterTerbuka = !filterTerbuka"
                    :aria-expanded="filterTerbuka" aria-controls="laci-filter"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                    Filter
                </button>
            @endisset

            {{-- Jumlah baris per halaman, bawaan 25 sesuai rules.md 13.3.2 --}}
            <label class="hidden items-center gap-2 text-theme-xs text-gray-500 sm:flex dark:text-gray-400">
                Tampilkan
                <select
                    class="rounded-lg border border-gray-300 bg-transparent px-2 py-1.5 text-theme-xs text-gray-700 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300">
                    @foreach ([10, 25, 50, 100] as $pilihan)
                        <option value="{{ $pilihan }}" @selected($pilihan === $perHalaman)>{{ $pilihan }}</option>
                    @endforeach
                </select>
                baris
            </label>
        </div>

        <div class="flex items-center gap-2">
            <label class="relative flex-1 sm:w-64 sm:flex-none">
                <span class="sr-only">{{ $placeholderCari }}</span>
                <svg class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="search" name="cari" value="{{ $kataKunci }}" placeholder="{{ $placeholderCari }}"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent pr-3 pl-9 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90" />
            </label>

            @isset($aksiKanan)
                {{ $aksiKanan }}
            @endisset
        </div>
    </div>

    {{-- Laci filter, tersembunyi sampai dibuka --}}
    @isset($filter)
        <div id="laci-filter" x-show="filterTerbuka" x-collapse x-cloak
            class="border-b border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
            {{ $filter }}
        </div>
    @endisset

    @if ($jumlah === 0)
        {{-- Keadaan kosong wajib, membedakan data belum ada dan pencarian nihil --}}
        <x-sim.empty-state :judul="$judulKosong" :pesan="$pesanKosong"
            :ragam="$kataKunci ? 'pencarian' : 'kosong'" :kata-kunci="$kataKunci">
            @isset($aksiKosong)
                <x-slot:aksi>{{ $aksiKosong }}</x-slot:aksi>
            @endisset
        </x-sim.empty-state>
    @else
        {{-- Tabel penuh untuk layar lebar --}}
        {{-- `relative` mengurung `<caption class="sr-only">` (position:absolute)
             agar tidak menyeret scrollbar mendatar badan halaman saat tabel
             lebih lebar dari wadahnya (bug 2026-08-30). --}}
        <div class="relative hidden overflow-x-auto md:block">
            <table class="w-full min-w-full text-left">
                {{--
                    WAJIB anak pertama <table>. Pembaca layar mengumumkannya
                    saat memasuki tabel, sehingga pengguna tahu tabel apa yang
                    sedang dibacanya tanpa perlu keluar mencari judul halaman.
                --}}
                @if ($judul)
                    <caption class="sr-only">{{ $judul }}</caption>
                @endif
                <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-white/[0.02]">
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        {{ $kepala }}
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    {{ $slot }}
                </tbody>
                @isset($kaki)
                    <tfoot>
                        {{ $kaki }}
                    </tfoot>
                @endisset
            </table>
        </div>

        {{-- Daftar kartu untuk layar sempit, mencegah gulir mendatar --}}
        <div class="divide-y divide-gray-200 md:hidden dark:divide-gray-800">
            @isset($kartu)
                {{ $kartu }}
            @endisset
        </div>

        {{-- Paginasi --}}
        <div class="flex flex-col gap-3 border-t border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                Menampilkan <span class="font-medium tabular-nums">{{ number_format(min($jumlah, $perHalaman), 0, ',', '.') }}</span>
                dari <span class="font-medium tabular-nums">{{ number_format($jumlah, 0, ',', '.') }}</span> data
            </p>
            @isset($paginasi)
                {{ $paginasi }}
            @endisset
        </div>
    @endif
</div>
