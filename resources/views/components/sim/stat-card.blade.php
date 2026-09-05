{{--
    Kartu indikator untuk dashboard.

    Memakai kelas motif-judul-kartu yang menampilkan garis pendek gold di atas
    label, sebagai motif identitas (agents/ui-spec.md bagian 2.3).

    Angka memakai tabular-nums agar digit sejajar antar kartu (bagian 3.4).

    Pemakaian:
        <x-sim.stat-card
            label="Jumlah Kepala Keluarga"
            nilai="1.140"
            satuan="KK"
            keterangan="Naik 12 KK dari tahun lalu"
            tren="naik"
            url="/kependudukan/rekap"
        />
--}}
@props([
    'label',
    'nilai',
    'satuan' => null,
    'keterangan' => null,
    'tren' => null,
    'url' => null,
    'ikon' => null,
    'mendesak' => false,
])

@php
    $tagPembungkus = $url ? 'a' : 'div';

    $warnaTren = match ($tren) {
        'naik' => 'text-green-600 dark:text-green-400',
        'turun' => 'text-red-600 dark:text-red-400',
        default => 'text-gray-500 dark:text-gray-400',
    };
@endphp

{{--
    Alamat dilewatkan `url()` agar tetap benar ketika sistem disajikan pada
    sub-path, misalnya build statis GitHub Pages. Alamat lengkap yang sudah
    memuat skema dibiarkan apa adanya.
--}}
<{{ $tagPembungkus }} @if ($url) href="{{ str_contains($url, '://') ? $url : url($url) }}" @endif
    {{ $attributes->merge([
        'class' => 'block rounded-2xl border bg-white p-5 dark:bg-white/[0.03]'
            . ($mendesak
                ? ' border-gold-500 ring-1 ring-gold-500/30 dark:border-gold-500'
                : ' border-gray-200 dark:border-gray-800')
            . ($url ? ' transition hover:border-brand-300 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:hover:border-brand-700' : ''),
    ]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            {{-- Garis gold pendek di atas label, motif identitas --}}
            <p class="motif-judul-kartu flex items-center gap-2 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                {{ $label }}
                @if ($mendesak)
                    <span class="relative flex h-2.5 w-2.5" aria-label="Memerlukan perhatian segera">
                        <span class="motion-safe:animate-ping absolute inline-flex h-full w-full rounded-full bg-gold-500 opacity-70"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-gold-500"></span>
                    </span>
                @endif
            </p>

            <p class="mt-1 flex items-baseline gap-1.5">
                <span class="text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                    {{ $nilai }}
                </span>
                @if ($satuan)
                    <span class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $satuan }}</span>
                @endif
            </p>

            @if ($keterangan)
                <p class="mt-1.5 text-theme-xs {{ $warnaTren }}">
                    @if ($tren === 'naik')
                        <span aria-hidden="true">&uarr;</span>
                    @elseif ($tren === 'turun')
                        <span aria-hidden="true">&darr;</span>
                    @endif
                    {{ $keterangan }}
                </p>
            @endif
        </div>

        @if ($ikon)
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">
                {!! $ikon !!}
            </span>
        @endif
    </div>
</{{ $tagPembungkus }}>
