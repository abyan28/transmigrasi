{{--
    Menu pengguna pada header.

    Data akun masih diambil dari penyedia data contoh; penyambungan ke sesi
    sungguhan dikerjakan pada Tahap 3 bersama autentikasi. Avatar memakai
    inisial nama, bukan foto orang karangan (ANTISLOP-ID R-18 dan R-23).

    Seluruh tautan di sini menunjuk halaman yang benar-benar ada, sesuai
    larangan kontrol mati (R-24 dan R-26).
--}}
@php
    $pengguna = \App\Support\DummyData::penggunaSaatIni();
@endphp

<div class="relative" x-data="{ terbuka: false }" @click.away="terbuka = false"
    @keydown.escape.window="terbuka = false">
    {{-- Tombol pemicu --}}
    <button type="button" class="flex items-center gap-3 rounded-lg text-gray-700 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400"
        @click="terbuka = !terbuka" :aria-expanded="terbuka" aria-haspopup="true" aria-label="Menu pengguna">
        <span
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-500 text-theme-sm font-semibold text-white"
            aria-hidden="true">
            {{ \App\Support\DummyData::inisial($pengguna['nama']) }}
        </span>

        <span class="hidden text-left sm:block">
            <span class="block text-theme-sm font-medium text-gray-800 dark:text-white/90">
                {{ $pengguna['nama'] }}
            </span>
            <span class="block text-theme-xs text-gray-500 dark:text-gray-400">
                {{ $pengguna['role']['nama'] }}
            </span>
        </span>

        <svg class="h-5 w-5 transition-transform duration-200" :class="{ 'rotate-180': terbuka }" fill="none"
            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    {{-- Isi dropdown --}}
    <div x-show="terbuka" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95" x-cloak
        class="absolute right-0 mt-3 flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark">

        <div class="border-b border-gray-200 pb-3 dark:border-gray-800">
            <span class="block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $pengguna['nama'] }}
            </span>
            <span class="mt-0.5 block truncate text-theme-xs text-gray-500 dark:text-gray-400">
                {{ $pengguna['email'] }}
            </span>
        </div>

        <ul class="flex flex-col gap-1 border-b border-gray-200 py-3 dark:border-gray-800">
            <li>
                <a href="{{ route('profil') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    Profil Saya
                </a>
            </li>
            <li>
                <a href="{{ route('profil.kata-sandi') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Ubah Kata Sandi
                </a>
            </li>
        </ul>

        {{-- Keluar memakai POST agar tidak dapat dipicu lewat prefetch peramban --}}
        <form method="POST" action="{{ route('logout') }}" class="pt-3">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
                Keluar
            </button>
        </form>
    </div>
</div>
