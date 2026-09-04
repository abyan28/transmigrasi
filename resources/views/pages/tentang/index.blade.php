{{--
    Halaman Tentang Sistem.

    Menyajikan profil sistem, latar belakang kawasan transmigrasi Kobalima Timur,
    tim pengembang resmi (ITS Surabaya), kolaborasi instansi kementerian dan daerah,
    arsitektur teknologi, lisensi, serta narahubung teknis operasional.
--}}
@extends('layouts.app')

@section('content')
    <x-sim.page-header judul="Tentang Sistem"
        keterangan="Informasi sistem, lokus kawasan, tim pengembang, kolaborasi instansi, dan arsitektur teknologi."
        :remah="\App\Helpers\RemahHelper::untuk('/tentang')">
        <x-slot:aksi>
            <a href="{{ route('panduan') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
                Buka Panduan Penggunaan
            </a>
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="space-y-6">
        {{-- Kartu 1: Profil & Ikhtisar Sistem --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-500/10 p-2 dark:bg-brand-500/20">
                        <img src="{{ asset('images/logo/logo-kementrans-128.png') }}"
                            alt="Logo Kementerian Transmigrasi"
                            class="h-10 w-10 shrink-0"
                            width="40"
                            height="40" />
                    </div>
                    <div>
                        <h2 class="text-theme-xl font-bold text-gray-800 dark:text-white/90">
                            Sistem Informasi Monitoring Pertanian &amp; Tata Kelola Kawasan
                        </h2>
                        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                            Kawasan Transmigrasi Kobalima Timur &bull; Kabupaten Malaka, Provinsi Nusa Tenggara Timur
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-theme-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Status: Purwarupa Antarmuka (Tahap 2)
                    </span>
                </div>
            </div>

            <hr class="my-5 border-gray-200 dark:border-gray-800" />

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                        Latar Belakang &amp; Tujuan
                    </h3>
                    {{-- Narasi diatur dari Pengelolaan Konten (Task 9.6). --}}
                    <p class="mt-2 whitespace-pre-line text-theme-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ \App\Support\KontenSistem::tentang() }}</p>
                </div>
                <div>
                    <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                        Cakupan Wilayah &amp; Lokus Kawasan
                    </h3>
                    <p class="mt-2 text-theme-sm leading-relaxed text-gray-600 dark:text-gray-400">
                        Sistem ini melayani <strong>6 Satuan Permukiman (SP)</strong> binaan yang tersebar di 4 wilayah kecamatan di Kabupaten Malaka:
                    </p>
                    <ul class="mt-2.5 grid grid-cols-2 gap-2 text-theme-xs text-gray-700 dark:text-gray-300">
                        <li class="rounded-lg bg-gray-50 p-2 dark:bg-white/[0.02]">
                            <span class="font-medium text-gray-800 dark:text-white/90">SP Kapitan Meo</span>
                            <span class="block text-gray-500 dark:text-gray-400">Kec. Laen Manen</span>
                        </li>
                        <li class="rounded-lg bg-gray-50 p-2 dark:bg-white/[0.02]">
                            <span class="font-medium text-gray-800 dark:text-white/90">SP Tniumanu</span>
                            <span class="block text-gray-500 dark:text-gray-400">Kec. Laen Manen</span>
                        </li>
                        <li class="rounded-lg bg-gray-50 p-2 dark:bg-white/[0.02]">
                            <span class="font-medium text-gray-800 dark:text-white/90">SP Harekakae</span>
                            <span class="block text-gray-500 dark:text-gray-400">Kec. Malaka Tengah</span>
                        </li>
                        <li class="rounded-lg bg-gray-50 p-2 dark:bg-white/[0.02]">
                            <span class="font-medium text-gray-800 dark:text-white/90">SP Weoe / Uluk Lubuk</span>
                            <span class="block text-gray-500 dark:text-gray-400">Kec. Wewiku</span>
                        </li>
                        <li class="rounded-lg bg-gray-50 p-2 dark:bg-white/[0.02]">
                            <span class="font-medium text-gray-800 dark:text-white/90">SP Tualaran</span>
                            <span class="block text-gray-500 dark:text-gray-400">Kec. Rinhat</span>
                        </li>
                        <li class="rounded-lg bg-gray-50 p-2 dark:bg-white/[0.02]">
                            <span class="font-medium text-gray-800 dark:text-white/90">SP Weain</span>
                            <span class="block text-gray-500 dark:text-gray-400">Kec. Rinhat</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Kartu 2: Tim Pengembang & Peneliti (ITS Surabaya) --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                <div>
                    <h3 class="text-theme-base font-semibold text-gray-800 dark:text-white/90">
                        Tim Peneliti &amp; Pengembang Sistem
                    </h3>
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        Institut Teknologi Sepuluh Nopember (ITS) Surabaya &bull; Program Kolaborasi Kawasan Transmigrasi 2026
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-brand-200 bg-brand-50 px-2.5 py-1 text-theme-xs font-semibold text-brand-700 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300">
                    Tim ITS Surabaya
                </span>
            </div>

            <div class="mt-6 space-y-6">
                {{-- Ketua Tim --}}
                <div class="rounded-xl border border-brand-200 bg-brand-50/50 p-5 dark:border-brand-500/20 dark:bg-brand-500/5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-500 text-theme-base font-bold text-white shadow-xs"
                            aria-hidden="true">
                            BS
                        </span>
                        <div class="grow">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-brand-500 px-2.5 py-0.5 text-[11px] font-semibold text-white">
                                    Ketua Tim / Peneliti Utama
                                </span>
                            </div>
                            <h4 class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">
                                Dr. Budi Setiyono, S.Si., M.T.
                            </h4>
                            <p class="text-theme-xs text-gray-600 dark:text-gray-400">
                                Koordinator Pengembangan Sistem &bull; Institut Teknologi Sepuluh Nopember (ITS)
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Anggota Tim --}}
                <div>
                    <h4 class="mb-3 text-theme-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Anggota Tim Peneliti &amp; Pengembang
                    </h4>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @php
                            $anggotaTim = [
                                ['nama' => 'Leonardi Paris Hasugian', 'inisial' => 'LH', 'peran' => 'Anggota Tim Pengembang'],
                                ['nama' => 'Muhammad Abyan Dzaka', 'inisial' => 'AD', 'peran' => 'Anggota Tim Pengembang'],
                                ['nama' => 'Reyner Marvi Leiwakabessy', 'inisial' => 'RL', 'peran' => 'Anggota Tim Pengembang'],
                                ['nama' => 'Muhammad Rias Ramadan', 'inisial' => 'RR', 'peran' => 'Anggota Tim Pengembang'],
                                ['nama' => 'Heaven Happyna Putra Febriyono', 'inisial' => 'HF', 'peran' => 'Anggota Tim Pengembang'],
                            ];
                        @endphp

                        @foreach ($anggotaTim as $anggota)
                            <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-3.5 dark:border-gray-800 dark:bg-white/[0.02]">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-200 text-theme-xs font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                    aria-hidden="true">
                                    {{ $anggota['inisial'] }}
                                </span>
                                <div class="min-w-0">
                                    <h5 class="truncate text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                                        {{ $anggota['nama'] }}
                                    </h5>
                                    <p class="truncate text-theme-xs text-gray-500 dark:text-gray-400">
                                        {{ $anggota['peran'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Kartu 3: Kolaborasi Kelembagaan --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-theme-base font-semibold text-gray-800 dark:text-white/90">
                Kolaborasi Kelembagaan &amp; Pemangku Kepentingan
            </h3>
            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Sinergi kementerian, pemerintah daerah, dinas teknis, dan perguruan tinggi pelaksana
            </p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500/10 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400 font-bold text-xs">
                            KTR
                        </span>
                        <h4 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Kementerian Transmigrasi RI</h4>
                    </div>
                    <p class="mt-2 text-theme-xs text-gray-600 dark:text-gray-400">
                        Instansi pembina kebijakan nasional bidang ketransmigrasian dan fasilitasi program kawasan transmigrasi.
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500/10 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400 font-bold text-xs">
                            MAL
                        </span>
                        <h4 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Dinas Transmigrasi Kab. Malaka</h4>
                    </div>
                    <p class="mt-2 text-theme-xs text-gray-600 dark:text-gray-400">
                        Instansi pengelola kawasan di daerah yang bertanggung jawab atas data warga, perumahan, lahan, dan infrastruktur permukiman.
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500/10 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400 font-bold text-xs">
                            TAN
                        </span>
                        <h4 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Dinas Pertanian Kab. Malaka</h4>
                    </div>
                    <p class="mt-2 text-theme-xs text-gray-600 dark:text-gray-400">
                        Instansi mitra teknis pembinaan kelompok tani, penyaluran alsintan, bantuan saprotan, serta pemantauan hasil panen.
                    </p>
                </div>
            </div>
        </div>

        {{-- Kartu 4: Arsitektur Teknologi & Lisensi --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-theme-base font-semibold text-gray-800 dark:text-white/90">
                Arsitektur Teknologi &amp; Lisensi Perangkat Lunak
            </h3>
            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Komponen open-source dan fondasi rekayasa sistem yang digunakan
            </p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                    <span class="block text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Fondasi Backend</span>
                    <span class="mt-1 block text-theme-sm font-bold text-gray-800 dark:text-white/90">Laravel 12.x &bull; PHP 8.2</span>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                    <span class="block text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Antarmuka &amp; Gaya</span>
                    <span class="mt-1 block text-theme-sm font-bold text-gray-800 dark:text-white/90">Tailwind CSS v4 &bull; Alpine.js</span>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                    <span class="block text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Grafik &amp; Peta</span>
                    <span class="mt-1 block text-theme-sm font-bold text-gray-800 dark:text-white/90">ApexCharts 5.x &bull; Leaflet OSM</span>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                    <span class="block text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Lisensi Template</span>
                    <span class="mt-1 block text-theme-sm font-bold text-gray-800 dark:text-white/90">TailAdmin (MIT License)</span>
                </div>
            </div>

            <p class="mt-4 text-theme-xs text-gray-500 dark:text-gray-400">
                Sistem ini dibangun mengikuti standar antarmuka web modern dengan aksesibilitas ramah kontras (WCAG AA), mode terang/gelap terintegrasi, serta optimasi tata letak responsif untuk perangkat komputer kerja maupun telepon pintar.
            </p>
        </div>

        {{-- Kartu 5: Narahubung & Bantuan --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-theme-base font-semibold text-gray-800 dark:text-white/90">
                Narahubung &amp; Bantuan Teknis
            </h3>
            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Saluran komunikasi untuk pertanyaan, masukan pengembangan, dan bantuan operasional
            </p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="flex items-start gap-3 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-theme-xs text-gray-500 dark:text-gray-400">Sekretariat Pelaksana &bull; Dinas Transmigrasi</span>
                        <span class="mt-0.5 block text-theme-sm font-semibold text-gray-800 dark:text-white/90">Kantor Dinas Transmigrasi Kab. Malaka</span>
                        <span class="block text-theme-xs text-gray-600 dark:text-gray-400">Betun, Kabupaten Malaka, Nusa Tenggara Timur</span>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-theme-xs text-gray-500 dark:text-gray-400">Tim Riset &amp; Pengembang Teknologi</span>
                        <span class="mt-0.5 block text-theme-sm font-semibold text-gray-800 dark:text-white/90">Institut Teknologi Sepuluh Nopember (ITS)</span>
                        <span class="block text-theme-xs text-gray-600 dark:text-gray-400">Kampus ITS Sukolilo, Surabaya, Jawa Timur</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
