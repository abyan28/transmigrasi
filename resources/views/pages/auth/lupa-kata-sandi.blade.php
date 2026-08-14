{{--
    Halaman permintaan kode pemulihan kata sandi.

    Sejak 2026-08-12 sistem menyediakan pemulihan mandiri sebagai PELENGKAP
    jalur Admin, bukan penggantinya (agents/rules.md bagian 14b poin 7
    sampai 12).

    Tiga hal yang dijaga di sini:

    1. Yang dikirim adalah KODE ENAM DIGIT, bukan tautan sekali klik. Kode
       dapat dibaca di ponsel lalu diketik di komputer, sehingga tetap
       berguna ketika surel dan peramban berada di perangkat berbeda.
    2. Jalur Admin disebut sejajar, bukan sebagai catatan kaki. Di lokus
       bersinyal lemah, jalur itulah satu-satunya yang bekerja.
    3. Halaman ini tidak boleh menyiratkan bahwa surel pasti sampai. Karena
       itu jalur cadangan sudah diperkenalkan sebelum tombol kirim ditekan,
       bukan setelah pengguna menunggu sia-sia.
--}}
@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative z-1 bg-white p-6 sm:p-0 dark:bg-gray-900">
        <div class="relative flex h-screen w-full flex-col justify-center sm:p-0 lg:flex-row dark:bg-gray-900">
            <div class="flex w-full flex-1 flex-col lg:w-1/2">
                <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
                    <div>
                        <div class="mb-5 sm:mb-8">
                            <h1 class="text-title-sm sm:text-title-md mb-2 font-semibold text-gray-800 dark:text-white/90">
                                Lupa Kata Sandi
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Masukkan email atau username Anda. Kami mengirim kode enam digit
                                ke alamat email yang terdaftar.
                            </p>
                        </div>

                        <form action="{{ route('lupa-kata-sandi.kirim') }}" method="POST"
                            x-data="{ mengirim: false }" @submit="mengirim = true">
                            @csrf

                            <div class="space-y-5">
                                <div>
                                    <label for="kredensial"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Email atau Username<span class="text-error-500">*</span>
                                    </label>
                                    <input type="text" id="kredensial" name="kredensial" autocomplete="username"
                                        required placeholder="Masukkan email atau username"
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                                </div>

                                <div>
                                    <button type="submit" :disabled="mengirim"
                                        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition disabled:opacity-60 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                        <span x-show="mengirim" x-cloak
                                            class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                            aria-hidden="true"></span>
                                        Kirim Kode Verifikasi
                                    </button>
                                </div>
                            </div>
                        </form>

                        {{--
                            Jalur Admin diperkenalkan di sini, sebelum pengguna
                            menunggu surel yang mungkin tidak kunjung tiba.
                        --}}
                        <div class="mt-5 rounded-lg bg-gray-50 p-4 dark:bg-white/[0.03]">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium text-gray-800 dark:text-white/90">Lupa email dan username?</span>
                                Hubungi admin untuk menyetel ulang kata sandi secara langsung.
                            </p>
                        </div>

                        <div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-800">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Sudah ingat kata sandi Anda?
                                <a href="{{ route('login') }}"
                                    class="font-medium text-brand-500 hover:text-brand-600">Kembali ke halaman masuk</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom kanan: panel jenama, disembunyikan pada layar sempit --}}
            <div class="relative hidden h-full w-full items-center bg-brand-950 lg:grid lg:w-1/2 dark:bg-white/5">
                <div class="flex items-center justify-center z-1">
                    <x-common.common-grid-shape />
                    {{--
                        Panel ini selalu berlatar pekat (brand-950 pada mode
                        terang, putih transparan pada mode gelap), sehingga
                        teks putih benar untuk keduanya. Kelas dark:text-white
                        ditulis eksplisit agar niat itu terbaca dari kelasnya
                        sendiri, tidak perlu ditelusuri ke elemen induk.
                    --}}
                    <div class="flex max-w-xs flex-col items-center">
                        <h2 class="mb-3 text-center text-xl font-semibold text-white dark:text-white">
                            Sistem Informasi Transmigrasi
                        </h2>
                        <p class="text-center text-sm text-gray-400 dark:text-white/60">
                            Kabupaten Malaka, Nusa Tenggara Timur
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
