{{--
    Tampilan saat tidak ada data.

    Wajib disediakan setiap halaman daftar dan detail (agents/ui-spec.md bagian 7).
    Menyediakan dua ragam: keadaan kosong biasa, dan hasil pencarian nihil yang
    menawarkan pembersihan filter.

    Pemakaian:
        <x-sim.empty-state
            judul="Belum ada data transmigran"
            pesan="Data transmigran akan tampil di sini setelah ditambahkan."
        >
            <x-slot:aksi>
                <button type="button" class="...">Tambah Transmigran</button>
            </x-slot:aksi>
        </x-sim.empty-state>
--}}
@props([
    'judul' => 'Belum ada data',
    'pesan' => null,
    'ragam' => 'kosong',
    'kataKunci' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-16 text-center']) }}>
    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-white/5">
        @if ($ragam === 'pencarian')
            {{-- Ikon kaca pembesar untuk hasil pencarian nihil --}}
            <svg class="h-7 w-7 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        @else
            {{-- Ikon dokumen untuk keadaan kosong biasa --}}
            <svg class="h-7 w-7 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        @endif
    </div>

    <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
        @if ($ragam === 'pencarian' && $kataKunci)
            Tidak ditemukan hasil untuk "{{ $kataKunci }}"
        @else
            {{ $judul }}
        @endif
    </h3>

    @if ($pesan)
        <p class="mt-1.5 max-w-sm text-theme-xs text-gray-500 dark:text-gray-400">
            {{ $pesan }}
        </p>
    @endif

    @isset($aksi)
        <div class="mt-5">
            {{ $aksi }}
        </div>
    @endisset
</div>
