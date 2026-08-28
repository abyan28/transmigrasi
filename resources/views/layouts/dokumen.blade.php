<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Laporan' }} | {{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo/favicon-16.png') }}">
    <meta name="theme-color" content="#163B54">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{--
        Tata letak POLOS untuk dokumen laporan yang dibuka di tab baru.
        Tanpa sidebar dan header aplikasi -- hanya kertasnya, supaya siap
        dibaca, dicetak, atau difoto lalu diserahkan ke dinas.

        Sengaja BUKAN memakai layouts/fullscreen-layout: berkas itu peninggalan
        template yang tidak dipakai satu halaman pun, dan store sidebar-nya
        tertinggal (masih membaca window.innerWidth mentah tanpa pembantu
        lebarLayar() yang sudah dibetulkan di layouts/app).

        Menerapkan mode gelap sedini mungkin, sama seperti layouts/app: skrip
        berjalan saat document.body belum ada, jadi kelas gelap dipasang pada
        <html> lebih dulu dan pada <body> menunggu DOM siap.
    --}}
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const gelap = (savedTheme || systemTheme) === 'dark';

            document.documentElement.classList.toggle('dark', gelap);

            document.addEventListener('DOMContentLoaded', function() {
                document.body.classList.toggle('dark', gelap);
                document.body.classList.toggle('bg-gray-900', gelap);
            });
        })();
    </script>
</head>

<body class="min-h-full bg-gray-100 dark:bg-gray-900">
    <main class="mx-auto max-w-6xl p-4 md:p-8">
        {{--
            Penanda data contoh. Wajib tampil selama aplikasi belum tersambung
            ke data nyata (ANTISLOP-ID R-17 dan R-38). Disuplai composer yang
            sama dengan layouts/app.
        --}}
        @if ($memakaiDataContoh)
            <div class="mx-auto mb-5 flex max-w-5xl items-start gap-3 rounded-xl border border-yellow-300 bg-yellow-50 p-3.5 dark:border-yellow-500/30 dark:bg-yellow-500/10 cetak-sembunyi"
                role="status">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-yellow-600 dark:text-yellow-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <p class="text-theme-xs text-yellow-800 dark:text-yellow-200">
                    <span class="font-semibold">Data contoh.</span>
                    Seluruh angka dan nama pada halaman ini adalah contoh untuk keperluan
                    pembangunan tampilan, bukan data lapangan yang sebenarnya.
                </p>
            </div>
        @endif

        @yield('content')
    </main>
</body>

@stack('scripts')

</html>
