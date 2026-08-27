{{--
    Sepasang tombol tindakan pada kepala halaman daftar: Impor dan Tambah.

    Diangkat 2026-08-27 dari empat belas halaman yang menuliskannya identik,
    lengkap dengan dua SVG dan kelas Tailwind sepanjang dua ratus karakter.

    IMPOR MENDAHULUI TAMBAH namun bergaya SEKUNDER, dan urutan itu disengaja:
    menambah satu data tetap tindakan yang paling sering dipakai (PRD 8.1),
    sehingga ia yang memperoleh penekanan warna meski letaknya di kanan.

    Keduanya opsional. Halaman yang tidak menyediakan impor cukup tidak
    mengoper `modal-impor`, dan tombolnya tidak dirender sama sekali - bukan
    dirender lalu dinonaktifkan, sebab tombol mati adalah kontrol mati (R-26).

    Slot `tambahan` disisipkan DI ANTARA keduanya, bukan sesudahnya. Halaman
    Hasil Panen menaruh tautan "Lihat Rekap Panen" persis di situ, dan
    memindahkannya ke ujung akan mengubah tampilan demi kerapian kode - harga
    yang tidak sepadan untuk sebuah refactor.

    Pemakaian:
        <x-sim.aksi-daftar modal-impor="imporAlsintan"
            modal-tambah="formTambahAlsintan" label-tambah="Tambah Alsintan" />
--}}
@props([
    'modalImpor' => null,
    'modalTambah' => null,
    'labelImpor' => 'Impor Data',
    'labelTambah' => 'Tambah Data',
])

@if ($modalImpor)
    <button type="button" @click="$dispatch('buka-modal', '{{ $modalImpor }}')"
        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
            aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
        </svg>
        {{ $labelImpor }}
    </button>
@endif

{{ $tambahan ?? '' }}

@if ($modalTambah)
    <button type="button" @click="$dispatch('buka-modal', '{{ $modalTambah }}')"
        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
            aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        {{ $labelTambah }}
    </button>
@endif
