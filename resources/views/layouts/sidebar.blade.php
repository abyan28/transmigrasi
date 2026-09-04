
@php
    use App\Helpers\MenuHelper;
    $menuGroups = MenuHelper::getMenuGroups();

    // Get current path
    $currentPath = request()->path();
@endphp

<aside id="sidebar"
    class="cetak-sembunyi fixed inset-y-0 left-0 flex flex-col px-5 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 transition-all duration-300 ease-in-out z-99999 border-r border-gray-200"
    x-data="{
        openSubmenus: {},
        init() {
            // Auto-open Dashboard menu on page load
            this.initializeActiveMenus();
        },
        initializeActiveMenus() {
            const currentPath = '{{ $currentPath }}';

            @foreach ($menuGroups as $groupIndex => $menuGroup)
                @foreach ($menuGroup['items'] as $itemIndex => $item)
                    @if (isset($item['subItems']))
                        // Check if any submenu item matches current path
                        {{--
                            Submenu ikut terbuka pada halaman turunan, bukan hanya pada
                            halaman daftarnya. Membuka /transmigran/1 berarti pengguna masih
                            berada di dalam cakupan menu Transmigran, sehingga menutup
                            submenunya membuat ia kehilangan jejak posisinya.

                            Pencocokan memakai awalan diikuti garis miring, bukan awalan
                            polos: tanpa garis miring, /sp akan ikut cocok dengan
                            /sp/inventaris yang sebenarnya submenu berbeda.
                        --}}
                        @foreach ($item['subItems'] as $subItem)
                            @php $jalur = ltrim($subItem['path'], '/'); @endphp
                            if (currentPath === '{{ $jalur }}' ||
                                currentPath.startsWith('{{ $jalur }}/')) {
                                this.openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}'] = true;
                            } @endforeach
            @endif
            @endforeach
            @endforeach
        },
        toggleSubmenu(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            const newState = !this.openSubmenus[key];

            // Close all other submenus when opening a new one
            if (newState) {
                this.openSubmenus = {};
            }

            this.openSubmenus[key] = newState;
        },
        isSubmenuOpen(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            return this.openSubmenus[key] || false;
        },
                isActive(path) {
                    // Item submenu ikut tersorot pada halaman turunannya, sejalan
                    // dengan submenu yang tetap terbuka. Tanpa ini, membuka rincian
                    // membuat tidak ada satu pun item tampak aktif.
                    const jalur = path.replace(/^\//, '');
                    const sekarang = '{{ $currentPath }}';

                    if (sekarang === jalur) {
                        return true;
                    }

                    // Yang paling spesifik menang. Tanpa pemeriksaan ini, membuka
                    // /sp/inventaris menyorot Satuan Permukiman DAN Inventaris SP
                    // sekaligus, sebab keduanya berawalan sp.
                    const semuaJalur = ['', 'kawasan', 'sp', 'sp/inventaris', 'sp/fasilitas', 'sp/infrastruktur', 'infrastruktur', 'transmigran', 'rumah', 'lahan', 'kependudukan/rekap', 'poktan', 'alsintan', 'saprotan', 'komoditas', 'penanaman', 'panen', 'panen/rekap', 'pengaduan', 'pengaduan/rekap', 'laporan', 'wilayah', 'master/satuan', 'master/daftar-pilihan', 'master/penilaian-kondisi', 'cms', 'pengguna', 'pengaturan/role', 'audit-log', 'panduan', 'tentang'];
                    const paling = semuaJalur
                        .filter((j) => j !== '' && (sekarang === j || sekarang.startsWith(j + '/')))
                        .sort((a, b) => b.length - a.length)[0];

                    return paling === jalur;
                }
    }"
    :class="{
        'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
        'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
        'translate-x-0': $store.sidebar.isMobileOpen,
        '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">
    <!-- Logo Section -->
    <div class="pt-8 pb-7 flex items-center justify-between shrink-0"
        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
        'xl:justify-center' :
        'justify-between'">
        {{--
            Logo resmi Kementerian Transmigrasi. Dilarang mengubah warna,
            proporsi, atau memotongnya (agents/ui-spec.md bagian 3.7).
            Saat sidebar diciutkan, hanya lambangnya yang tampil.
        --}}
        <a href="{{ route('beranda') }}" class="flex items-center gap-3" aria-label="Beranda {{ config('app.name') }}">
            <img src="{{ asset('images/logo/logo-kementrans-128.png') }}" alt="Logo Kementerian Transmigrasi"
                class="h-10 w-10 shrink-0" width="40" height="40" />
            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                x-cloak class="flex flex-col leading-tight">
                {{--
                    Sidebar berlatar putih pada mode terang dan navy pada mode
                    gelap, sehingga warna teks wajib menyesuaikan keduanya.
                    Sebelumnya memakai text-white saja, yang membuat nama sistem
                    tidak terbaca sama sekali di mode terang (R-25 dan R-34).
                --}}
                <span class="text-theme-sm font-semibold text-navy-500 dark:text-white">{{ config('app.name') }}</span>
                <span class="text-theme-xs text-gray-500 dark:text-navy-200">Kobalima Timur</span>
            </span>
        </a>

        <!-- Mobile Close Button (visible only on mobile when sidebar is open) -->
        <button type="button" @click="$store.sidebar.setMobileOpen(false)"
            class="flex xl:hidden items-center justify-center w-9 h-9 text-gray-500 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500"
            aria-label="Tutup menu samping">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Navigation Menu -->
    <div class="flex flex-col flex-1 min-h-0 overflow-y-auto duration-300 ease-linear no-scrollbar">
        <nav class="pb-24 sm:pb-8">
            <div class="flex flex-col gap-4">
                @foreach ($menuGroups as $groupIndex => $menuGroup)
                    <div>
                        <!-- Menu Group Title -->
                        <h2 class="mb-4 text-xs uppercase flex leading-[20px] text-gray-400"
                            :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
                            'lg:justify-center' : 'justify-start'">
                            <template
                                x-if="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                                <span>{{ $menuGroup['title'] }}</span>
                            </template>
                            <template x-if="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <path fill-rule="evenodd" clip-rule="evenodd" d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z" fill="currentColor"/>
                                </svg>
                            </template>
                        </h2>

                        <!-- Menu Items -->
                        <ul class="flex flex-col gap-1">
                            @foreach ($menuGroup['items'] as $itemIndex => $item)
                                <li>
                                    @if (isset($item['subItems']))
                                        <!-- Menu Item with Submenu -->
                                        <button @click="toggleSubmenu({{ $groupIndex }}, {{ $itemIndex }})"
                                            class="menu-item group w-full"
                                            :class="[
                                                isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}) ?
                                                'menu-item-active' : 'menu-item-inactive',
                                                !$store.sidebar.isExpanded && !$store.sidebar.isHovered ?
                                                'xl:justify-center' : 'xl:justify-start'
                                            ]">

                                            <!-- Icon -->
                                            <span :class="isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}) ?
                                                    'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                                {!! MenuHelper::getIconSvg($item['icon']) !!}
                                            </span>

                                            <!-- Text -->
                                            <span
                                                x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="menu-item-text flex items-center gap-2">
                                                {{ $item['name'] }}
                                                @if (!empty($item['new']))
                                                    <span class="absolute right-10"
                                                        :class="isActive('{{ $item['path'] ?? '' }}') ?
                                                            'menu-dropdown-badge menu-dropdown-badge-active' :
                                                            'menu-dropdown-badge menu-dropdown-badge-inactive'">
                                                        new
                                                    </span>
                                                @endif
                                            </span>

                                            <!-- Chevron Down Icon -->
                                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="ml-auto w-5 h-5 transition-transform duration-200"
                                                :class="{
                                                    'rotate-180 text-brand-500': isSubmenuOpen({{ $groupIndex }},
                                                        {{ $itemIndex }})
                                                }"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>

                                        <!-- Submenu -->
                                        <div x-show="isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}) && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)">
                                            <ul class="mt-2 space-y-1 ml-9">
                                                @foreach ($item['subItems'] as $subItem)
                                                    <li>
                                                        <a href="{{ url($subItem['path']) }}" class="menu-dropdown-item"
                                                            :class="isActive('{{ $subItem['path'] }}') ?
                                                                'menu-dropdown-item-active' :
                                                                'menu-dropdown-item-inactive'">
                                                            {{ $subItem['name'] }}
                                                            <span class="flex items-center gap-1 ml-auto">
                                                                @if (!empty($subItem['new']))
                                                                    <span
                                                                        :class="isActive('{{ $subItem['path'] }}') ?
                                                                            'menu-dropdown-badge menu-dropdown-badge-active' :
                                                                            'menu-dropdown-badge menu-dropdown-badge-inactive'">
                                                                        new
                                                                    </span>
                                                                @endif
                                                                @if (!empty($subItem['pro']))
                                                                    <span
                                                                        :class="isActive('{{ $subItem['path'] }}') ?
                                                                            'menu-dropdown-badge-pro menu-dropdown-badge-pro-active' :
                                                                            'menu-dropdown-badge-pro menu-dropdown-badge-pro-inactive'">
                                                                        pro
                                                                    </span>
                                                                @endif
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        {{--
                                            Item menu tunggal. Kelas `motif-menu-aktif` menampilkan
                                            batang gold di tepi kiri saat item sedang aktif, sebagai
                                            motif identitas (agents/ui-spec.md bagian 2.3).
                                        --}}
                                        <a href="{{ url($item['path']) }}" class="menu-item group"
                                            @if (MenuHelper::isActive($item['path'])) aria-current="page" @endif
                                            :class="[
                                                isActive('{{ $item['path'] }}') ? 'menu-item-active motif-menu-aktif' :
                                                'menu-item-inactive',
                                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
                                                'xl:justify-center' :
                                                'justify-start'
                                            ]">

                                            <!-- Icon -->
                                            <span
                                                :class="isActive('{{ $item['path'] }}') ? 'menu-item-icon-active' :
                                                    'menu-item-icon-inactive'">
                                                {!! MenuHelper::getIconSvg($item['icon']) !!}
                                            </span>

                                            <!-- Text -->
                                            <span
                                                x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="menu-item-text flex items-center gap-2">
                                                {{ $item['name'] }}
                                                @if (!empty($item['new']))
                                                    <span
                                                        class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-brand-500 text-white">
                                                        new
                                                    </span>
                                                @endif
                                            </span>
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </nav>

    </div>
</aside>
