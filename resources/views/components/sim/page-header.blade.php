{{--
    Kepala halaman: judul, keterangan, remah roti, dan tombol aksi.

    Memakai kelas motif-header-halaman yang menampilkan garis bawah bergradasi
    berhenti di sepertiga lebar, sebagai motif identitas (agents/ui-spec.md 2.3).

    Pemakaian:
        <x-sim.page-header
            judul="Data Transmigran"
            keterangan="Daftar kepala keluarga di seluruh satuan permukiman."
            :remah="[['label' => 'Kependudukan'], ['label' => 'Transmigran']]"
        >
            <x-slot:aksi>
                <button type="button">Tambah Transmigran</button>
            </x-slot:aksi>
        </x-sim.page-header>
--}}
@props([
    'judul',
    'keterangan' => null,
    'remah' => [],
])

<div {{ $attributes->merge(['class' => 'motif-header-halaman mb-6']) }}>
    @if (! empty($remah))
        <nav aria-label="Remah roti" class="mb-2">
            <ol class="flex flex-wrap items-center gap-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                <li>
                    <a href="{{ route('beranda') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Beranda</a>
                </li>
                @foreach ($remah as $item)
                    <li aria-hidden="true">/</li>
                    <li @if ($loop->last) class="font-medium text-gray-700 dark:text-gray-300" aria-current="page" @endif>
                        @if (! $loop->last && isset($item['url']))
                            <a href="{{ $item['url'] }}" class="hover:text-gray-700 dark:hover:text-gray-300">
                                {{ $item['label'] }}
                            </a>
                        @else
                            {{ $item['label'] }}
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-title-sm font-semibold text-navy-500 dark:text-white">
                {{ $judul }}
            </h1>
            @if ($keterangan)
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                    {{ $keterangan }}
                </p>
            @endif
        </div>

        @isset($aksi)
            {{-- Tombol aksi diletakkan di kanan atas agar tidak meluber pada layar sempit --}}
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                {{ $aksi }}
            </div>
        @endisset
    </div>
</div>
