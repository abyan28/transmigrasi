{{--
    Tombol ekspor data tabel.

    Ditaruh menempel pada tabelnya, bukan dikumpulkan di satu halaman laporan
    tersendiri. Alasannya `rules.md` 12 poin 5: laporan wajib dapat difilter
    sebelum diekspor. Halaman daftar sudah memiliki pencarian dan filter yang
    bekerja, sedangkan halaman laporan terpusat tidak pernah memilikinya,
    sehingga petugas hanya dapat mengunduh seluruh isi tabel.

    **Filter yang sedang aktif ikut terbawa.** Query string halaman disalin
    apa adanya ke alamat ekspor, sehingga hasil unduhan sama persis dengan
    yang terlihat di layar. Tanpa ini petugas yang sudah menyaring data satu
    SP tetap menerima berkas berisi seluruh kawasan, dan perbedaannya baru
    disadari setelah berkas dibuka.

    Belum menghasilkan berkas: pembangkitan Excel dan PDF dikerjakan pada
    Tahap 10. Karena itu tombol ini dirender sebagai teks berlabel jujur,
    bukan tombol yang tampak berfungsi (ANTISLOP-ID R-26).

    Pemakaian:
        <x-sim.tombol-ekspor />
        <x-sim.tombol-ekspor label="Ekspor Laporan Kawasan" />
--}}
@props([
    'label' => 'Ekspor',
])

@php
    // Disusun di sini, bukan di tiap halaman, agar seluruh tombol ekspor
    // mewarisi filter dengan cara yang sama. Halaman yang menyusunnya sendiri
    // cepat atau lambat akan melewatkan satu parameter.
    $parameter = request()->query();
    $keterangan = $parameter === []
        ? 'Mengekspor seluruh data pada tabel ini.'
        : 'Mengekspor data sesuai filter yang sedang aktif.';
@endphp

<span data-ekspor data-parameter="{{ http_build_query($parameter) }}"
    title="{{ $keterangan }} Pembangkitan berkas dikerjakan pada tahap berikutnya."
    class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
        aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
    </svg>
    {{ $label }}, segera hadir
</span>
