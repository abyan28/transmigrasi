{{--
    Kolom aksi baku untuk satu baris tabel: Rincian, Ubah, Hapus.

    Dibuat setelah audit menemukan kolom aksi berbeda-beda di sembilan belas
    halaman daftar: lima halaman memakai ikon lengkap, empat memakai teks
    tanpa tombol hapus, dan delapan tidak memiliki kolom aksi sama sekali.
    Petugas karena itu harus menebak letak dan bentuk tindakan setiap kali
    berpindah modul.

    Bentuk IKON dipilih, bukan teks, agar kolom aksi tetap sempit pada tabel
    yang sudah padat. Setiap ikon tetap membawa aria-label lengkap, sebab
    ikon tanpa label tidak terbaca pembaca layar (ui-spec.md 11.1, R-32).

    Tindakan yang tidak berlaku cukup dikosongkan parameternya, dan tombolnya
    tidak dirender sama sekali. Merender tombol lalu menolaknya di server
    melanggar R-26.

    Pemakaian:
        <x-sim.aksi-baris
            :rincian-url="route('transmigran.detail', $t['id_transmigran'])"
            modal-ubah="formUbahTransmigranBaris"
            :data-baris="$t + ['id' => $t['id_transmigran']]"
            :hapus-url="route('transmigran.hapus', $t['id_transmigran'])"
            konfirmasi-hapus="hapusTransmigran"
            label="YOHANES BERE" />
--}}
@props([
    'rincianUrl' => null,
    'modalUbah' => null,
    'dataBaris' => [],
    'hapusUrl' => null,
    'konfirmasiHapus' => null,
    'label' => '',
    'tambahan' => null,
])

@php
    $kelasIkon = 'rounded-lg p-2 text-gray-500 transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400';
@endphp

<div class="flex items-center justify-end gap-1">
    {{-- Rincian --}}
    @if ($rincianUrl)
        <a href="{{ $rincianUrl }}" aria-label="Lihat rincian {{ $label }}"
            class="{{ $kelasIkon }} hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </a>
    @endif

    {{-- Ubah --}}
    @if ($modalUbah)
        <button type="button"
            @click.prevent="$dispatch('buka-modal-baris', { nama: '{{ $modalUbah }}', data: @js($dataBaris) })"
            aria-label="Ubah data {{ $label }}"
            class="{{ $kelasIkon }} hover:bg-gray-100 hover:text-brand-600 dark:hover:bg-white/5">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
            </svg>
        </button>
    @endif

    {{-- Ikon tambahan khusus modul, contoh penanganan pengaduan --}}
    {{ $tambahan }}

    {{-- Hapus, selalu paling kanan agar tidak tertukar dengan tindakan lain --}}
    @if ($hapusUrl && $konfirmasiHapus)
        <button type="button"
            @click.prevent="$dispatch('buka-konfirmasi', { nama: '{{ $konfirmasiHapus }}', aksi: '{{ $hapusUrl }}' })"
            aria-label="Hapus data {{ $label }}"
            class="{{ $kelasIkon }} hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
            </svg>
        </button>
    @endif
</div>