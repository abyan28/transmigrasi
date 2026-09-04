{{--
    Kerangka halaman daftar sederhana.

    Dipakai modul gelombang 2 yang polanya seragam: kepala halaman, kartu
    ringkasan opsional, lalu satu tabel. Ditulis sekali di sini agar 18
    halaman tidak menyalin markup yang sama, dan agar perubahan pola cukup
    dilakukan di satu tempat.

    BEDA dengan `x-sim.data-table`: komponen itu menangani tabelnya saja,
    sedangkan komponen ini menangani seluruh halaman beserta kepala, filter,
    dan pembungkus formnya.

    Modul yang memerlukan susunan khusus, seperti transmigran dan pengaduan
    pada gelombang 1, tetap menulis halamannya sendiri. Kerangka ini untuk
    yang polanya memang seragam, bukan untuk memaksa semua halaman sama.

    Pemakaian:
        <x-sim.halaman-daftar judul="Data Komoditas" keterangan="..."
            :remah="[['label' => 'Pertanian'], ['label' => 'Komoditas']]"
            :jumlah="count($baris)" :kata-kunci="$cari"
            :aksi-url="route('komoditas.index')">
            <x-slot:kartu> ... </x-slot:kartu>
            <x-slot:kepala> ... </x-slot:kepala>
            ...baris tabel...
        </x-sim.halaman-daftar>
--}}
@props([
    'judul',
    'keterangan' => null,
    'remah' => [],
    'jumlah' => 0,
    'kataKunci' => null,
    'aksiUrl' => null,
    'placeholderCari' => 'Cari data',
    'judulKosong' => 'Belum ada data',
    'pesanKosong' => null,
    'paginator' => null, // Fase 1: diteruskan apa adanya ke x-sim.data-table
])

<x-sim.page-header :judul="$judul" :keterangan="$keterangan" :remah="$remah">
    @isset($aksi)
        <x-slot:aksi>{{ $aksi }}</x-slot:aksi>
    @endisset
</x-sim.page-header>

{{-- Kartu ringkasan, hanya dirender bila halaman memerlukannya --}}
@isset($ringkasan)
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {{ $ringkasan }}
    </div>
@endisset

{{-- Pencarian dan filter dibungkus satu form agar terkirim bersama --}}
<form method="GET" @if ($aksiUrl) action="{{ $aksiUrl }}" @endif>
    {{--
        Judul halaman diteruskan menjadi <caption> tabelnya. Dengan begitu
        seluruh halaman yang memakai kerangka ini memperoleh nama tabel bagi
        pembaca layar tanpa satu pun perlu disunting.
    --}}
    <x-sim.data-table :judul="$judul" :jumlah="$jumlah" :kata-kunci="$kataKunci"
        :placeholder-cari="$placeholderCari" :judul-kosong="$judulKosong"
        :pesan-kosong="$pesanKosong" :paginator="$paginator">

        @isset($filter)
            <x-slot:filter>{{ $filter }}</x-slot:filter>
        @endisset

        <x-slot:aksiKanan>
            <button type="submit"
                class="h-10 shrink-0 rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Cari
            </button>

            {{--
                Tombol ekspor DICABUT dari sini 2026-08-28 (rules.md 12 poin
                7). Sebelumnya ia muncul otomatis di setiap halaman daftar,
                belasan tombol tanpa laporan di baliknya (kontrol mati R-26).
                Laporan kini dokumen bernama di menu "Laporan" tersendiri.
            --}}
        </x-slot:aksiKanan>

        @isset($aksiKosong)
            <x-slot:aksiKosong>{{ $aksiKosong }}</x-slot:aksiKosong>
        @endisset

        <x-slot:kepala>{{ $kepala }}</x-slot:kepala>

        {{ $slot }}

        @isset($kaki)
            <x-slot:kaki>{{ $kaki }}</x-slot:kaki>
        @endisset

        @isset($kartu)
            <x-slot:kartu>{{ $kartu }}</x-slot:kartu>
        @endisset
    </x-sim.data-table>
</form>

@isset($setelahTabel)
    {{ $setelahTabel }}
@endisset
