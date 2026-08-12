{{--
    Halaman 404, alamat tidak ditemukan.

    Diletakkan di resources/views/errors/ agar Laravel memakainya otomatis
    untuk setiap respons 404, termasuk abort(404) pada rute modul.

    Memakai tata letak layar penuh karena pengunjung bisa jadi belum masuk;
    merender sidebar berisi menu petugas akan menjadi kontrol mati bagi mereka
    (ANTISLOP-ID R-24 dan R-26).

    Pesan ditulis dalam bahasa yang dimengerti operator lapangan, bukan istilah
    teknis (agents/rules.md bagian 13.3 poin 7).
--}}
@extends('layouts.fullscreen-layout')

@section('content')
    <x-sim.halaman-galat kode="404" judul="Halaman tidak ditemukan" ilustrasi="404"
        pesan="Alamat yang Anda buka tidak ada, atau datanya sudah dihapus. Periksa kembali tautannya, atau kembali ke halaman sebelumnya.">
        <x-slot:aksi>
            <a href="{{ url()->previous() }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-5 py-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Kembali ke Halaman Sebelumnya
            </a>
            <a href="{{ route('beranda') }}"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-3 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                Buka Dashboard
            </a>
        </x-slot:aksi>
    </x-sim.halaman-galat>
@endsection
