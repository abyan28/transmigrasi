{{--
    Halaman wajib ganti kata sandi.

    Muncul otomatis ketika `user.password_harus_diganti` bernilai TRUE: akun
    baru dibuat admin, atau kata sandi disetel ulang. Selama belum diganti,
    pengguna tidak dapat mengakses halaman lain (agents/rules.md bagian 14b
    poin 13), ditegakkan middleware PastikanGantiKataSandi.

    Karena itu halaman ini sengaja memakai tata letak layar penuh tanpa sidebar
    dan tanpa tautan keluar selain tombol keluar. Menampilkan menu navigasi di
    sini akan menjadi kontrol mati, sebab seluruh tujuannya diblokir middleware
    (ANTISLOP-ID R-24 dan R-26).

    Kata sandi lama tidak diminta karena pengguna baru saja menerimanya dari
    admin dan sudah membuktikan kepemilikan lewat proses masuk.

    Task 3.14: bila akun masih memakai username SEMENTARA (petugas.xxxxxxxx),
    halaman ini sekaligus meminta petugas membuat usernamenya sendiri
    (rules.md 14b poin 5), dengan pemeriksaan ketersediaan saat diketik
    (poin 5a). $perluUsername datang dari GantiKataSandiController::tampil().
--}}
@extends('layouts.fullscreen-layout')

@section('content')
    <div class="flex min-h-screen items-center justify-center bg-gray-50 p-6 dark:bg-gray-900">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <img src="{{ asset('images/logo/logo-kementrans-128.png') }}" alt="Logo Kementerian Transmigrasi"
                    class="mx-auto h-14 w-14" width="56" height="56" />
                <h1 class="mt-4 text-title-sm font-semibold text-gray-800 dark:text-white/90">
                    @if ($perluUsername)
                        Lengkapi Akun Anda
                    @else
                        Ganti Kata Sandi Dulu
                    @endif
                </h1>
                <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">
                    @if ($perluUsername)
                        Buat username dan kata sandi Anda sendiri untuk mulai memakai sistem.
                    @else
                        Kata sandi Anda baru disetel ulang oleh admin.
                        Buat kata sandi baru sebelum melanjutkan.
                    @endif
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
                        Halaman lain terkunci sampai langkah ini selesai.
                        Kata sandi sementara dari admin hanya berlaku sekali pakai.
                    </p>
                </div>

                <form method="POST" action="{{ route('ganti-kata-sandi.simpan') }}" class="space-y-5"
                    x-data="{
                        baru: '',
                        konfirmasi: '',
                        username: @js(old('username', '')),
                        status: null,
                        memeriksa: false,
                        async periksa() {
                            this.status = null;
                            if (! /^[a-z0-9._]{3,50}$/.test(this.username)) {
                                this.status = this.username.length ? 'format' : null;
                                return;
                            }
                            this.memeriksa = true;
                            try {
                                const r = await fetch(
                                    @js(route('ganti-kata-sandi.cek-username')) + '?username=' + encodeURIComponent(this.username),
                                    { headers: { 'Accept': 'application/json' } },
                                );
                                const d = await r.json();
                                this.status = d.tersedia ? 'tersedia' : 'dipakai';
                            } catch (e) {
                                this.status = null;
                            } finally {
                                this.memeriksa = false;
                            }
                        },
                    }">
                    @csrf

                    @if ($perluUsername)
                        <div>
                            <label for="username"
                                class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                Username<span class="text-error-500">*</span>
                            </label>
                            <input type="text" id="username" name="username" x-model="username"
                                @input.debounce.500ms="periksa()" autocomplete="username" required
                                placeholder="mis. yosef.klau" aria-describedby="username_bantuan"
                                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30" />
                            <p id="username_bantuan" class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                Huruf kecil, angka, titik, dan garis bawah. 3 sampai 50 karakter.
                                Dipakai untuk masuk dan tidak dapat diubah sendiri setelah ini.
                            </p>

                            {{-- Indikator ketersediaan hidup (rules.md 14b poin 5a) --}}
                            <p x-show="memeriksa" x-cloak class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                Memeriksa ketersediaan...
                            </p>
                            <p x-show="! memeriksa && status === 'tersedia'" x-cloak
                                class="mt-1 text-theme-xs text-success-600 dark:text-success-400">
                                Username tersedia.
                            </p>
                            <p x-show="! memeriksa && status === 'dipakai'" x-cloak
                                class="mt-1 text-theme-xs text-error-500">
                                Username ini sudah dipakai akun lain.
                            </p>
                            <p x-show="! memeriksa && status === 'format'" x-cloak
                                class="mt-1 text-theme-xs text-error-500">
                                Hanya huruf kecil, angka, titik, dan garis bawah, panjang 3 sampai 50.
                            </p>

                            @error('username')
                                <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

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
                        @if ($perluUsername) :disabled="memeriksa || status === 'dipakai' || status === 'format'" @endif
                        class="w-full rounded-lg bg-brand-500 px-4 py-3 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 disabled:cursor-not-allowed disabled:opacity-60">
                        Simpan dan Lanjutkan
                    </button>
                </form>
            </div>

            {{--
                Satu-satunya jalan keluar dari halaman ini adalah keluar dari akun,
                karena seluruh halaman lain diblokir sampai langkah ini selesai.
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
