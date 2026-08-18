<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Beranda' }} | {{ config('app.name') }}</title>

    {{-- Favicon dan ikon perangkat, diturunkan dari logo resmi Kementerian --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo/favicon-16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo/apple-touch-icon.png') }}">
    <meta name="theme-color" content="#163B54">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    {{-- <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

    <!-- Theme Store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                init() {
                    const savedTheme = localStorage.getItem('theme');
                    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' :
                        'light';
                    this.theme = savedTheme || systemTheme;
                    this.updateTheme();
                },
                theme: 'light',
                toggle() {
                    this.theme = this.theme === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.theme);
                    this.updateTheme();
                },
                updateTheme() {
                    const html = document.documentElement;
                    const body = document.body;
                    if (this.theme === 'dark') {
                        html.classList.add('dark');
                        body.classList.add('dark', 'bg-gray-900');
                    } else {
                        html.classList.remove('dark');
                        body.classList.remove('dark', 'bg-gray-900');
                    }
                }
            });

            Alpine.store('sidebar', {
                // Initialize based on screen size
                isExpanded: (window.innerWidth || document.documentElement.clientWidth || 1280) >= 1280, // nol berarti tab belum dilukis
                isMobileOpen: false,
                isHovered: false,

                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    // When toggling desktop sidebar, ensure mobile menu is closed
                    this.isMobileOpen = false;
                },

                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                    // Don't modify isExpanded when toggling mobile menu
                },

                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },

                setHovered(val) {
                    // Only allow hover effects on desktop when sidebar is collapsed
                    if ((window.innerWidth || document.documentElement.clientWidth || 1280) >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    {{--
        Sama seperti pada `layouts/app.blade.php`: skrip ini berjalan saat
        `document.body` belum ada, sehingga menyentuhnya langsung melempar
        galat pada setiap pemuatan halaman.
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

<body x-data="{ 'loaded': true}" x-init="$store.sidebar.isExpanded = (window.innerWidth || document.documentElement.clientWidth || 1280) >= 1280;
const checkMobile = () => {
    if ((window.innerWidth || document.documentElement.clientWidth || 1280) < 1280) {
        $store.sidebar.setMobileOpen(false);
        $store.sidebar.isExpanded = false;
    } else {
        $store.sidebar.isMobileOpen = false;
        $store.sidebar.isExpanded = true;
    }
};
window.addEventListener('resize', checkMobile);">

    {{-- preloader --}}
    <x-common.preloader/>
    {{-- preloader end --}}

    @yield('content')

</body>

@stack('scripts')

</html>
