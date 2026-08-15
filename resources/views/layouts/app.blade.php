<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Beranda' }} | {{ config('app.name') }}</title>

    {{-- Favicon dan ikon perangkat, diturunkan dari logo resmi Kementerian --}}
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/logo/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/logo/favicon-16.png">
    <link rel="apple-touch-icon" href="/images/logo/apple-touch-icon.png">
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
                /*
                    Lebar layar dibaca lewat pembantu ini, bukan langsung dari
                    window.innerWidth.

                    Tab yang dibuka di latar belakang lewat "buka di tab baru" belum
                    dilukis peramban, sehingga innerWidth-nya sempat bernilai 0.
                    Sidebar mengira layarnya sempit lalu menyempit ke 90px, dan
                    konten di sebelahnya ikut bergeser. Setelah halaman disegarkan,
                    tab sudah aktif dan lebarnya terbaca benar, itulah sebabnya
                    tampilan pulih hanya dengan refresh.

                    Nilai nol karena itu tidak boleh dipercaya. Selama lebar belum
                    terbaca, sidebar dianggap lebar, sebab tampilan desktop adalah
                    keadaan yang paling sering benar bagi petugas dinas.
                */
                lebarLayar() {
                    const lebar = window.innerWidth || document.documentElement.clientWidth || 0;

                    return lebar === 0 ? 1280 : lebar;
                },

                get layarLebar() {
                    return this.lebarLayar() >= 1280;
                },

                isExpanded: (window.innerWidth || document.documentElement.clientWidth || 1280) >= 1280,
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
                    if (this.layarLebar && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    <!-- Apply dark mode immediately to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = savedTheme || systemTheme;
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                document.body.classList.add('dark', 'bg-gray-900');
            } else {
                document.documentElement.classList.remove('dark');
                document.body.classList.remove('dark', 'bg-gray-900');
            }
        })();
    </script>

    {{--
        Helper tab persisten.

        Halaman bertab yang memiliki aksi submit wajib menyimpan tab aktif pada
        query string, bukan hash. Alasannya, fragment hash hilang saat form POST
        dan tidak ikut terkirim lewat Referer, sehingga `return back()` akan
        selalu memulangkan pengguna ke tab pertama.

        Rincian aturan pada agents/rules.md bagian 13.2 poin 1.

        Pemakaian pada Blade:
            <div x-data="hashTabs('umum')">
                <button @click="setTab('umum')" :class="tab === 'umum' && 'aktif'">Umum</button>
                <button @click="setTab('dokumen')">Dokumen</button>
                <div x-show="tab === 'umum'">...</div>
            </div>

        Untuk tab bertingkat, pakai setSubTab() yang menulis parameter `sub`.
    --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('hashTabs', (tabBawaan = '', subBawaan = '') => ({
                tab: tabBawaan,
                sub: subBawaan,

                init() {
                    const params = new URLSearchParams(window.location.search);
                    this.tab = params.get('tab') || tabBawaan;
                    this.sub = params.get('sub') || subBawaan;

                    // Query ditulis ulang saat halaman dimuat agar submit dari
                    // dalam modal tetap membawa posisi tab lewat header Referer.
                    this.syncUrl();
                },

                setTab(nama) {
                    this.tab = nama;
                    this.sub = '';
                    this.syncUrl();
                },

                setSubTab(nama) {
                    this.sub = nama;
                    this.syncUrl();
                },

                /** Menulis tab dan sub-tab aktif ke query string tanpa memuat ulang halaman. */
                syncUrl() {
                    const url = new URL(window.location.href);

                    if (this.tab) {
                        url.searchParams.set('tab', this.tab);
                    } else {
                        url.searchParams.delete('tab');
                    }

                    if (this.sub) {
                        url.searchParams.set('sub', this.sub);
                    } else {
                        url.searchParams.delete('sub');
                    }

                    window.history.replaceState({}, '', url);
                },
            }));
        });
    </script>

</head>

<body
    x-data="{ 'loaded': true}"
    x-init="$store.sidebar.isExpanded = $store.sidebar.layarLebar;
            const sesuaikanLebar = () => {
                if (! $store.sidebar.layarLebar) {
                    $store.sidebar.setMobileOpen(false);
                    $store.sidebar.isExpanded = false;
                } else {
                    $store.sidebar.isMobileOpen = false;
                    $store.sidebar.isExpanded = true;
                }
            };
            window.addEventListener('resize', sesuaikanLebar);">

    {{-- preloader --}}
    <x-common.preloader/>
    {{-- preloader end --}}

    <div class="min-h-screen xl:flex">
        @include('layouts.backdrop')
        @include('layouts.sidebar')

        {{--
            min-w-0 WAJIB ada di sini.

            Sidebar berposisi fixed, sehingga ia keluar dari alur dan tidak
            memakan ruang di dalam pembungkus flex. Akibatnya flex-1 menghitung
            lebar penuh layar, lalu xl:ml-[290px] menambahkan 290px lagi:
            lebar totalnya menjadi 100% + 290px dan kontennya meluber ke kanan.

            Gejalanya paling terlihat pada tab yang dibuka lewat "buka di tab
            baru": menu pengguna terdorong keluar layar, tombol dan kartu paling
            kanan terpotong, dan muncul gulir mendatar. Setelah disegarkan
            tampilan pulih, sebab urutan perhitungan tata letaknya berbeda,
            sehingga cacat ini mudah dikira masalah pemuatan.

            min-w-0 mengizinkan item flex menyusut lebih kecil dari isinya,
            sehingga flex-1 menghitung ruang yang benar-benar tersisa setelah
            margin diperhitungkan.
        --}}
        <div class="min-w-0 flex-1 transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
                'ml-0': $store.sidebar.isMobileOpen
            }">
            <!-- app header start -->
            @include('layouts.app-header')
            <!-- app header end -->
            <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
                {{--
                    Penanda data contoh. Wajib tampil selama aplikasi belum
                    tersambung ke data nyata, agar angka di layar tidak
                    disalahartikan sebagai data lapangan sungguhan
                    (ANTISLOP-ID R-17 dan R-38).
                --}}
                @if (\App\Support\DummyData::MEMAKAI_DATA_CONTOH)
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-yellow-300 bg-yellow-50 p-3.5 dark:border-yellow-500/30 dark:bg-yellow-500/10"
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
            </div>
        </div>

    </div>

    {{-- Pemberitahuan hasil tindakan, dipasang sekali untuk seluruh halaman --}}
    <x-sim.toast />

</body>

@stack('scripts')

</html>
