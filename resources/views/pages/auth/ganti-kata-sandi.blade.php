{{--
    Halaman wajib ganti kata sandi.

    Muncul otomatis setelah admin menyetel ulang kata sandi seseorang, yaitu
    ketika `user.password_harus_diganti` bernilai TRUE. Selama belum diganti,
    pengguna tidak dapat mengakses halaman lain (agents/rules.md bagian 14b
    poin 9).

    Karena itu halaman ini sengaja memakai tata letak layar penuh tanpa sidebar
    dan tanpa tautan keluar selain tombol keluar. Menampilkan menu navigasi di
    sini akan menjadi kontrol mati, sebab seluruh tujuannya diblokir middleware
    (ANTISLOP-ID R-24 dan R-26).

    Kata sandi lama tidak diminta karena pengguna baru saja menerimanya dari
    admin dan sudah membuktikan kepemilikan lewat proses masuk.

    Pemaksaan sebenarnya lewat middleware dikerjakan pada Tahap 3.
--}}
@extends('layouts.fullscreen-layout')

@section('content')
    <div class="flex min-h-screen items-center justify-center bg-gray-50 p-6 dark:bg-gray-900">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <img src="/images/logo/logo-kementrans-128.png" alt="Logo Kementerian Transmigrasi"
                    class="mx-auto h-14 w-14" width="56" height="56" />
                <h1 class="mt-4 text-title-sm font-semibold text-gray-800 dark:text-white/90">
                    Ganti Kata Sandi Dulu
                </h1>
                <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">
                    Kata sandi Anda baru disetel ulang oleh admin.
                    Buat kata sandi baru sebelum melanjutkan.
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                {{-- Keterangan mengapa halaman ini muncul, agar pengguna tidak merasa tersesat --}}
                <div class="mb-5 flex items-start gap-3 rounded-lg border border-yellow-300 bg-yellow-50 p-3.5 dark:border-yellow-500/30 dark:bg-yellow-500/10"
                    role="status">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-yellow-600 dark:text-yellow-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    <p class="text-theme-xs text-yellow-800 dark:text-yellow-200">
                        Halaman lain terkunci sampai kata sandi diganti.
                        Kata sandi sementara dari admin hanya berlaku sekali pakai.
                    </p>
                </div>

                <form method="POST" action="{{ route('ganti-kata-sandi.simpan') }}" class="space-y-5"
                    x-data="{ baru: '', konfirmasi: '' }">
                    @csrf

                    <x-sim.input-kata-sandi nama="password" label="Kata Sandi Baru" autocomplete="new-password"
                        :wajib="true" x-model="baru"
                        keterangan="Minimal 8 karakter, memuat huruf dan angka." />

                    <x-sim.input-kata-sandi nama="password_confirmation" label="Ulangi Kata Sandi Baru"
                        autocomplete="new-password" :wajib="true" x-model="konfirmasi" />

                    <p x-show="konfirmasi.length > 0 && baru !== konfirmasi" x-cloak
                        class="text-theme-xs text-error-500">
                        Kedua kata sandi belum sama.
                    </p>

                    <button type="submit"
                        class="w-full rounded-lg bg-brand-500 px-4 py-3 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Simpan Kata Sandi Baru
                    </button>
                </form>
            </div>

            {{--
                Satu-satunya jalan keluar dari halaman ini adalah keluar dari akun,
                karena seluruh halaman lain diblokir sampai kata sandi diganti.
            --}}
            <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
                @csrf
                <button type="submit"
                    class="rounded text-theme-sm font-medium text-gray-600 underline-offset-4 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400">
                    Keluar dari akun
                </button>
            </form>
        </div>

        {{-- Tombol ganti mode terang atau gelap, sama seperti halaman masuk --}}
        <div class="fixed right-6 bottom-6 z-50">
            <button type="button" aria-label="Ganti mode terang atau gelap" @click.prevent="$store.theme.toggle()"
                class="inline-flex size-12 items-center justify-center rounded-full bg-brand-500 text-white transition-colors hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                </svg>
                <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                </svg>
            </button>
        </div>
    </div>
@endsection
