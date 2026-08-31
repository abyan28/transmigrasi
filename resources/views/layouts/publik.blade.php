{{--
    Tata letak halaman publik.

    Dipakai halaman pengaduan warga dan lacak pengaduan. Sengaja terpisah dari
    layouts/app karena pengunjungnya WARGA DESA, bukan petugas sistem
    (agents/ui-spec.md bagian 4.1a poin 1):

    - tanpa sidebar dan tanpa menu petugas, sebab seluruh tujuannya memerlukan
      login sehingga akan menjadi kontrol mati (ANTISLOP-ID R-24 dan R-26),
    - tanpa dropdown pengguna dan tanpa pemberitahuan internal,
    - hanya dua tautan: ajukan pengaduan dan lacak pengaduan.

    Ukuran teks dibuat sedikit lebih besar daripada halaman petugas, karena
    halaman ini dipakai sesekali oleh orang yang belum tentu terbiasa dengan
    antarmuka digital.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Pengaduan Warga' }} | {{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo/apple-touch-icon.png') }}">
    <meta name="theme-color" content="#163B54">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Tema disiapkan sebelum render agar tidak berkedip saat halaman dimuat --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                theme: 'light',
                init() {
                    const tersimpan = localStorage.getItem('theme');
                    const sistem = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    this.theme = tersimpan || sistem;
                    this.updateTheme();
                },
                toggle() {
                    this.theme = this.theme === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.theme);
                    this.updateTheme();
                },
                updateTheme() {
                    document.documentElement.classList.toggle('dark', this.theme === 'dark');
                },
            });
        });
    </script>
    <script>
        (function() {
            const tersimpan = localStorage.getItem('theme');
            const sistem = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.classList.toggle('dark', (tersimpan || sistem) === 'dark');
        })();
    </script>
</head>

<body class="min-h-full bg-gray-50 dark:bg-navy-900">
    {{-- Kepala halaman: logo, nama sistem, dan dua tautan publik --}}
    <header class="border-b border-gray-200 bg-white dark:border-navy-700 dark:bg-navy-800">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6">
            <a href="{{ route('pengaduan-warga') }}"
                class="flex items-center gap-3 rounded focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <img src="{{ asset('images/logo/logo-kementrans-128.png') }}" alt="Logo Kementerian Transmigrasi"
                    class="h-10 w-10 shrink-0" width="40" height="40" />
                <span class="flex flex-col leading-tight">
                    <span class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                        Pengaduan Warga
                    </span>
                    <span class="text-theme-xs text-gray-500 dark:text-gray-400">
                        Kawasan Transmigrasi Kobalima Timur
                    </span>
                </span>
            </a>

            <nav class="flex items-center gap-2" aria-label="Menu utama">
                <a href="{{ route('pengaduan-warga') }}"
                    @if (request()->routeIs('pengaduan-warga')) aria-current="page" @endif
                    class="rounded-lg px-3 py-2 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 {{ request()->routeIs('pengaduan-warga')
                        ? 'bg-brand-500 text-white'
                        : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5' }}">
                    Kirim Pengaduan
                </a>
                <a href="{{ route('lacak-pengaduan') }}"
                    @if (request()->routeIs('lacak-pengaduan')) aria-current="page" @endif
                    class="rounded-lg px-3 py-2 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 {{ request()->routeIs('lacak-pengaduan')
                        ? 'bg-brand-500 text-white'
                        : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5' }}">
                    Lacak Pengaduan
                </a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        @yield('content')
    </main>

    <x-sim.footer-publik />

    {{-- Tombol ganti mode terang atau gelap --}}
    <div class="fixed right-5 bottom-5 z-50">
        <button type="button" aria-label="Ganti mode terang atau gelap" @click="$store.theme.toggle()"
            x-data
            class="inline-flex size-12 items-center justify-center rounded-full bg-brand-500 text-white shadow-lg transition-colors hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
            <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
            </svg>
            <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
            </svg>
        </button>
    </div>

    <x-sim.toast />
</body>

@stack('scripts')

</html>
