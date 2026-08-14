{{--
    Badge status berwarna.

    Menerima langsung objek enum yang memakai trait PunyaWarnaBadge, sehingga
    warna tidak perlu ditulis ulang di setiap halaman. Nilai teks diambil dari
    enum, bukan ditulis keras di view (agents/ui-spec.md bagian 11.7).

    Pemakaian:
        <x-sim.status-badge :status="$rumah->kondisi" />
        <x-sim.status-badge :status="$pengaduan->status" :catatan="$alasan" />

    Bila hanya tersedia teks mentah dari data contoh, pakai atribut teks dan warna:
        <x-sim.status-badge teks="Selesai" warna="success" />
--}}
@props([
    'status' => null,
    'teks' => null,
    'warna' => 'gray',
    'catatan' => null,
    'ukuran' => 'md',
])

@php
    // Enum menang atas atribut manual, agar sumber kebenaran tetap satu.
    $labelTampil = $status?->label() ?? $teks ?? '-';
    $warnaTampil = $status?->warna() ?? $warna;

    $gaya = [
        'gray' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300',
        'teal' => 'bg-teal-50 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300',
        'success' => 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-400',
        'warning' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400',
        'error' => 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    ];

    $ukuranGaya = [
        'sm' => 'px-2 py-0.5 text-theme-xs',
        'md' => 'px-2.5 py-1 text-theme-xs',
    ];

    $kelas = $gaya[$warnaTampil] ?? $gaya['gray'];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full font-medium ' . $kelas . ' ' . ($ukuranGaya[$ukuran] ?? $ukuranGaya['md']),
]) }}
    @if ($catatan) title="{{ $catatan }}" @endif>
    {{-- Titik penanda membantu pembedaan bagi pengguna dengan buta warna --}}
    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true"></span>
    {{ $labelTampil }}
</span>
