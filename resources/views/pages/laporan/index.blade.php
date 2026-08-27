{{--
    Ikhtisar menu Laporan.

    Ditambahkan 2026-08-28 (rules.md 12 poin 6, membalik keputusan
    2026-08-17). Laporan adalah dokumen bernama berformat tetap, bukan potret
    tabel yang sedang tersaring. Halaman ini hanya pintu masuknya; tiap
    laporan punya halamannya sendiri dengan cakupan dan kolom baku.

    `$daftarLaporan` datang dari rute `laporan.index` pada routes/web.php,
    dipakai bersama pendaftaran rute tiap laporan agar keduanya tidak dapat
    berselisih.
--}}
@extends('layouts.app')

@section('content')
    <x-sim.page-header judul="Laporan"
        keterangan="Dokumen laporan berformat tetap untuk kebutuhan desa, dinas, pendamping, dan kementerian."
        :remah="\App\Helpers\RemahHelper::untuk('/laporan')" />

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-sm text-gray-600 dark:text-gray-400">
            Tiap laporan adalah satu halaman berformat tetap dengan kolom yang
            ditentukan dinas. Cakupan wilayah dan periodenya ditulis di kepala
            dokumen, bukan disaring di halaman laporan. Susunan kolomnya sedang
            disiapkan, sehingga halaman di bawah baru memuat kerangkanya.
        </p>

        <ul class="mt-5 grid gap-3 sm:grid-cols-2">
            @foreach ($daftarLaporan as $slug => $judul)
                <li>
                    <a href="{{ route('laporan.' . $slug) }}"
                        class="flex items-start gap-3 rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-800 dark:hover:border-brand-500/40 dark:hover:bg-white/5">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 4.5H7.5A1.5 1.5 0 006 6v13.5A1.5 1.5 0 007.5 21h9a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H15M9 12h6M9 15.5h6" />
                        </svg>
                        <span class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $judul }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endsection
