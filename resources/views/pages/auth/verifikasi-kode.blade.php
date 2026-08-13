{{--
    Halaman pemasukan kode verifikasi beserta kata sandi baru.

    Pesan pada halaman ini SENGAJA tidak menyatakan apakah alamat yang
    dimasukkan sebelumnya terdaftar (agents/rules.md bagian 14b poin 9).
    Kalimat "kode sudah dikirim bila alamat terdaftar" berlaku sama untuk
    kedua keadaan. Pesan yang membedakan keduanya akan mengubah halaman
    publik ini menjadi alat memeriksa siapa saja yang memiliki akun dinas.

    Kode dan kata sandi baru diminta pada satu halaman, bukan dua langkah
    terpisah, agar petugas berjaringan lemah cukup sekali memuat halaman.

    Ketentuan lain yang tampak di sini:
    - kode berlaku 15 menit dan sekali pakai (poin 8),
    - percobaan dibatasi 5 kali per kode (poin 10),
    - jalur Admin tetap ditawarkan bila kode tidak kunjung diterima (poin 11).
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
                                Masukkan Kode Verifikasi
                            </h1>
                            {{--
                                Kalimat netral. Berlaku sama baik akun ditemukan
                                maupun tidak, sehingga tidak membocorkan
                                keberadaan akun.
                            --}}
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Bila alamat yang Anda masukkan terdaftar, kode enam digit sudah dikirim
                                ke surel dinasnya. Periksa juga folder spam.
                            </p>
                        </div>

                        <form action="{{ route('atur-ulang-sandi') }}" method="POST"
                            x-data="{ mengirim: false }" @submit="mengirim = true">
                            @csrf

                            <div class="space-y-5">
                                <div>
                                    <label for="kode"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Kode Verifikasi<span class="text-error-500">*</span>
                                    </label>
                                    <input type="text" id="kode" name="kode" inputmode="numeric"
                                        pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required
                                        placeholder="000000" aria-describedby="kode_bantuan"
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-center text-lg tracking-[0.5em] tabular-nums text-gray-800 placeholder:tracking-[0.5em] placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                                    <p id="kode_bantuan" class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                        Berlaku 15 menit sejak dikirim, dan hanya dapat dipakai satu kali.
                                    </p>
                                </div>

                                <x-sim.input-kata-sandi nama="password_baru" label="Kata Sandi Baru"
                                    autocomplete="new-password" :wajib="true"
                                    keterangan="Minimal 8 karakter, memuat huruf dan angka." />

                                <x-sim.input-kata-sandi nama="password_baru_konfirmasi"
                                    label="Ulangi Kata Sandi Baru" autocomplete="new-password" :wajib="true" />

                                <div>
                                    <button type="submit" :disabled="mengirim"
                                        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition disabled:opacity-60 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                        <span x-show="mengirim" x-cloak
                                            class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                            aria-hidden="true"></span>
                                        Simpan Kata Sandi Baru
                                    </button>
                                </div>
                            </div>
                        </form>

                        {{-- Kode tidak diterima atau telanjur kedaluwarsa --}}
                        <div class="mt-5 rounded-lg bg-gray-50 p-4 dark:bg-white/[0.03]">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                Kode tidak diterima atau sudah kedaluwarsa?
                            </p>
                            <ul class="mt-2 space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                                <li>
                                    &bull;
                                    <a href="{{ route('lupa-kata-sandi') }}"
                                        class="font-medium text-brand-500 hover:text-brand-600">Minta kode baru</a>,
                                    paling banyak tiga kali dalam satu jam. Kode lama langsung hangus.
                                </li>
                                <li>
                                    &bull; Hubungi admin desa atau SP Anda untuk penyetelan ulang secara langsung,
                                    bila sinyal di lokus sedang tidak memadai.
                                </li>
                            </ul>
                        </div>

                        <div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-800">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <a href="{{ route('login') }}"
                                    class="font-medium text-brand-500 hover:text-brand-600">Kembali ke halaman masuk</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative hidden h-full w-full items-center bg-brand-950 lg:grid lg:w-1/2 dark:bg-white/5">
                <div class="flex items-center justify-center z-1">
                    <x-common.common-grid-shape />
                    {{--
                        Panel berlatar pekat pada kedua mode, sehingga teks
                        putih benar. dark:text-white ditulis eksplisit agar
                        niatnya terbaca tanpa menelusuri elemen induk.
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
