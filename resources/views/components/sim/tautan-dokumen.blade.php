{{--
    Tautan untuk membuka satu berkas yang sudah diunggah.

    Berkas privat tidak dapat ditaut langsung ke folder storage, sebab
    seluruh dokumen sistem ini berada di luar public dan hanya dapat dibuka
    lewat rute berpemeriksa izin (agents/rules.md A14a). Komponen ini
    membungkus rute tersebut agar pemakaiannya seragam.

    Dibuat setelah ditemukan bahwa DokumenController beserta rutenya sudah
    lengkap, tetapi tidak satu pun halaman memakainya: nama berkas hanya
    ditampilkan sebagai teks, sehingga petugas tidak punya cara membuka
    dokumen yang sudah diunggahnya sendiri.

    Terbuka di tab baru, sebab petugas biasanya membandingkan isi dokumen
    dengan data pada halaman yang sedang dibuka.

    Pemakaian:
        <x-sim.tautan-dokumen modul="alsintan" :id="$data['id_alsintan']"
            :berkas="$data['foto'] ?? null" />
--}}
@props([
    'modul',
    'id',
    'berkas',
    'label' => null,
])

@php
    $namaBerkas = basename($berkas ?? '');
    $teks = $label ?? $namaBerkas;
@endphp

@if ($namaBerkas === '')
    {{-- Tanpa berkas, tanda hubung dipakai konsisten dengan kolom kosong lain --}}
    <span class="text-theme-xs text-gray-500 dark:text-gray-400">&ndash;</span>
@else
    <a href="{{ route('dokumen.tampilkan', ['modul' => $modul, 'id' => $id, 'namaBerkas' => $namaBerkas]) }}"
        target="_blank" rel="noopener"
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded text-theme-xs font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300']) }}>
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
            aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
        </svg>
        <span class="truncate">{{ $teks }}</span>
        <span class="sr-only">, terbuka di tab baru</span>
    </a>
@endif