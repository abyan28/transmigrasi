{{--
    Pemilih "Tampilkan N baris", diekstrak dari `x-sim.data-table` (Fase 1,
    2026-09-05) supaya halaman yang TIDAK memakai komponen itu -- saat ini
    hanya `pages/master/wilayah.blade.php`, sebab sumber datanya gabungan
    4 model lewat pemotongan larik, bukan satu query Eloquent -- tetap
    mendapat kontrol yang sama persis, bukan menyalin markup terpisah yang
    bisa diam-diam berbeda.

    Pemakaian (di dalam <form method="GET"> yang sudah ada):
        <x-sim.pilih-per-halaman :per-halaman="$perHalaman" />
--}}
@props([
    'perHalaman' => 25,
])

<label class="hidden items-center gap-2 text-theme-xs text-gray-500 sm:flex dark:text-gray-400">
    Tampilkan
    <select name="per_halaman" onchange="this.form.submit()"
        class="rounded-lg border border-gray-300 bg-transparent px-2 py-1.5 text-theme-xs text-gray-700 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300">
        @foreach ([10, 25, 50, 100] as $pilihan)
            <option value="{{ $pilihan }}" @selected($pilihan === $perHalaman)>{{ $pilihan }}</option>
        @endforeach
    </select>
    baris
</label>
