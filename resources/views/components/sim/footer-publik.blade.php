{{--
    Footer Informatif untuk Tata Letak Publik (Warga & Pengunjung Luar).

    Menyajikan identitas resmi sistem kawasan Kobalima Timur, daftar lokus
    6 Satuan Permukiman binaan, kerja sama kementerian dan daerah bersama
    ITS Surabaya, serta tautan informasi umum.
--}}
<footer class="mt-12 border-t border-gray-200 bg-white dark:border-navy-700 dark:bg-navy-800">
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Kolom 1: Identitas Sistem & Kawasan --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo/logo-kementrans-128.png') }}"
                        alt="Logo Kementerian Transmigrasi"
                        class="h-8 w-8 shrink-0"
                        width="32"
                        height="32" />
                    <div>
                        <span class="block text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                            {{ config('app.name', 'DIGITRANS') }}
                        </span>
                        <span class="block text-theme-xs text-gray-500 dark:text-gray-400">
                            Kawasan Kobalima Timur &bull; Malaka
                        </span>
                    </div>
                </div>
                <p class="mt-3 text-theme-xs leading-relaxed text-gray-600 dark:text-gray-400">
                    Sistem informasi digitalisasi monitoring pertanian dan tata kelola data kawasan transmigrasi Kabupaten Malaka, NTT.
                </p>
                <div class="mt-3">
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center gap-1 text-theme-xs font-medium text-teal-700 hover:underline dark:text-teal-300">
                        <span>Masuk sebagai Petugas Sistem</span>
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Kolom 2: Lokus Satuan Permukiman --}}
            <div>
                <h3 class="text-theme-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                    Lokus Satuan Permukiman
                </h3>
                <ul class="mt-3 space-y-1 text-theme-xs text-gray-600 dark:text-gray-400">
                    <li>SP Kapitan Meo (Laen Manen)</li>
                    <li>SP Tniumanu (Laen Manen)</li>
                    <li>SP Harekakae (Malaka Tengah)</li>
                    <li>SP Weoe / Uluk Lubuk (Wewiku)</li>
                    <li>SP Tualaran (Rinhat)</li>
                    <li>SP Weain (Rinhat)</li>
                </ul>
            </div>

            {{-- Kolom 3: Kolaborasi Kelembagaan --}}
            <div>
                <h3 class="text-theme-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                    Kerja Sama Kelembagaan
                </h3>
                <ul class="mt-3 space-y-1.5 text-theme-xs text-gray-600 dark:text-gray-400">
                    <li>Kementerian Transmigrasi RI</li>
                    <li>Pemerintah Kabupaten Malaka</li>
                    <li>Institut Teknologi Sepuluh Nopember (ITS)</li>
                </ul>
                <div class="mt-4 flex flex-wrap gap-2 text-theme-xs">
                    <a href="{{ route('pengaduan-warga') }}" class="text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
                        Kirim Aduan
                    </a>
                    <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                    <a href="{{ route('lacak-pengaduan') }}" class="text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
                        Lacak Aduan
                    </a>
                </div>
            </div>
        </div>

        {{-- Hak Cipta --}}
        <div class="mt-8 border-t border-gray-200 pt-5 text-center sm:flex sm:items-center sm:justify-between sm:text-left dark:border-navy-700">
            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                &copy; {{ date('Y') }} Kementerian Transmigrasi RI &amp; Pemkab Malaka. Dikembangkan bersama ITS Surabaya.
            </p>
            <p class="mt-2 text-[11px] text-gray-400 sm:mt-0 dark:text-gray-500">
                Fondasi antarmuka TailAdmin (MIT).
            </p>
        </div>
    </div>
</footer>
