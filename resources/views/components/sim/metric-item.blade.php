{{--
    Item metrik untuk x-sim.metric-strip dengan mikro-visualisasi.

    Menampilkan angka ringkasan secara padat dilengkapi mikro-ikon semantik
    dan visualisasi batang rasio (progress/ratio bar) bila terdapat data persentase.
--}}
@props([
    'label',
    'nilai',
    'satuan' => null,
    'ikon' => null,
    'warna' => 'teal',
    'prosentase' => null,
    'keterangan' => null,
    'url' => null,
    'mendesak' => false,
    'perhatian' => false,
    'kedip' => false,
])

@php
    $tag = $url ? 'a' : 'div';

    // Peta warna tema institusional (ui-spec.md palet Kementerian)
    $palet = [
        'teal' => [
            'ikon' => 'bg-teal-50 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300',
            'bar' => 'bg-teal-600 dark:bg-teal-400',
            'teks' => 'text-teal-700 dark:text-teal-300',
        ],
        'emerald' => [
            'ikon' => 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-400',
            'bar' => 'bg-green-600 dark:bg-green-400',
            'teks' => 'text-green-700 dark:text-green-400',
        ],
        'navy' => [
            'ikon' => 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
            'bar' => 'bg-brand-600 dark:bg-brand-400',
            'teks' => 'text-brand-700 dark:text-brand-300',
        ],
        'gold' => [
            'ikon' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
            'bar' => 'bg-amber-500 dark:bg-amber-400',
            'teks' => 'text-amber-700 dark:text-amber-300',
        ],
        'red' => [
            'ikon' => 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-400',
            'bar' => 'bg-red-600 dark:bg-red-400',
            'teks' => 'text-red-700 dark:text-red-400',
        ],
    ];

    $tema = $palet[$warna] ?? $palet['teal'];

    $isMendesak = (bool) $mendesak;
    $isPerhatian = (bool) $perhatian;

    $kelasKedip = match (true) {
        $isMendesak => ' motion-safe:animate-pulse bg-red-500/10 dark:bg-red-500/15 ring-1 ring-inset ring-red-500/40 rounded-lg',
        $isPerhatian => ' motion-safe:animate-pulse bg-amber-500/10 dark:bg-amber-500/15 ring-1 ring-inset ring-amber-500/30 rounded-lg',
        $kedip => ' motion-safe:animate-pulse',
        default => '',
    };
@endphp

<{{ $tag }} @if ($url) href="{{ $url }}" @endif
    {{ $attributes->merge([
        'class' => 'group block p-3 sm:px-4 sm:py-3 transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500'
            . $kelasKedip
            . ($url ? ' hover:bg-gray-50/80 dark:hover:bg-white/[0.02]' : ''),
    ]) }}>
    <div class="flex items-center gap-2.5">
        @if ($ikon)
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $tema['ikon'] }}" aria-hidden="true">
                @if ($ikon === 'keluarga')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                @elseif ($ikon === 'hunian')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                @elseif ($ikon === 'penduduk')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                @elseif ($ikon === 'lokasi')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                @elseif ($ikon === 'poktan' || $ikon === 'kelompok')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                @elseif ($ikon === 'alsintan' || $ikon === 'mesin')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233l-.715-1.43a2.25 2.25 0 00-1.608-1.17l-1.92-.32a2.25 2.25 0 00-1.884.664l-.946.946m8.073 3.31l1.43.715a2.25 2.25 0 002.378-.295l.407-.338a2.25 2.25 0 00.57-2.327l-.604-1.812a2.25 2.25 0 00-1.17-1.353l-1.503-.601" />
                    </svg>
                @elseif ($ikon === 'saprotan' || $ikon === 'benih')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.942A4.5 4.5 0 0115.918 17H8.082a4.5 4.5 0 01-2.312-.758L4.2 15.3M19.8 15.3A4.5 4.5 0 0121 18.75V19.5a1.5 1.5 0 01-1.5 1.5H4.5A1.5 1.5 0 013 19.5v-.75a4.5 4.5 0 011.2-3.45" />
                    </svg>
                @elseif ($ikon === 'tanaman' || $ikon === 'tanam')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.439a11.956 11.956 0 004.5-2.074M6.75 18a11.956 11.956 0 014.5-2.074m0 0a8.25 8.25 0 01-2.25-5.25c0-4.556 3.694-8.25 8.25-8.25.345 0 .685.021 1.018.062a8.25 8.25 0 01-8.25 13.438z" />
                    </svg>
                @elseif ($ikon === 'panen')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                @elseif ($ikon === 'lahan')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934a1.12 1.12 0 01-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689A1.125 1.125 0 003 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934a1.12 1.12 0 011.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />
                    </svg>
                @elseif ($ikon === 'infrastruktur')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                @elseif ($ikon === 'komoditas')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                    </svg>
                @elseif ($ikon === 'pengaduan')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v5.518z" />
                    </svg>
                @elseif ($ikon === 'pengguna')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                @elseif ($ikon === 'rusak')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63" />
                    </svg>
                @elseif ($ikon === 'perhatian' || $ikon === 'peringatan')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                @elseif ($ikon === 'mendesak')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                @elseif ($ikon === 'aset' || $ikon === 'inventaris')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                @elseif ($ikon === 'fasilitas')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.333A12.012 12.012 0 0012 9c-2.474 0-4.78.749-6.75 2.033V21m13.5 0H3" />
                    </svg>
                @else
                    {!! $ikon !!}
                @endif
            </span>
        @endif

        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold tabular-nums text-gray-800 sm:text-base dark:text-white/90">
                {{ $nilai }}
                @if ($satuan)
                    <span class="text-theme-xs font-normal text-gray-500 dark:text-gray-400">{{ $satuan }}</span>
                @endif
            </p>
        </div>
    </div>

    <div class="mt-1 flex items-center gap-1.5 min-w-0">
        <p class="truncate text-theme-xs font-medium text-gray-500 dark:text-gray-400">
            {{ $label }}
        </p>
        @if ($isMendesak)
            <span class="relative flex h-2 w-2 shrink-0" aria-label="Mendesak">
                <span class="motion-safe:animate-ping absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-red-600"></span>
            </span>
        @elseif ($isPerhatian)
            <span class="relative flex h-2 w-2 shrink-0" aria-label="Perlu perhatian">
                <span class="motion-safe:animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-60"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
            </span>
        @endif
    </div>

    {{-- Mikro-Visualisasi: Rasio Proporsi atau Keterangan --}}
    @if ($prosentase !== null)
        <div class="mt-1.5 flex items-center gap-2">
            <div class="h-1 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                <div class="h-full rounded-full {{ $tema['bar'] }}" style="width: {{ min(100, max(0, (float) $prosentase)) }}%"></div>
            </div>
            <span class="text-[11px] font-semibold tabular-nums {{ $tema['teks'] }}">
                {{ number_format((float) $prosentase, 1, ',', '.') }}%
            </span>
        </div>
    @elseif ($keterangan)
        <p class="mt-1 truncate text-[11px] text-gray-400 dark:text-gray-500">
            {{ $keterangan }}
        </p>
    @endif
</{{ $tag }}>
