{{-- Halaman informasi tetap; narasi editorial dikelola melalui /cms. --}}
@extends('layouts.app')

@php
    $tentang = \App\Support\KontenSistem::halamanTentang();
    $instansi = \App\Support\KontenSistem::instansi();
    $kontak = \App\Support\KontenSistem::kontakBantuan();
@endphp

@section('content')
    <x-sim.page-header judul="Tentang Sistem"
        keterangan="Informasi aplikasi, wilayah layanan, pengelola, dan bantuan."
        :remah="\App\Helpers\RemahHelper::untuk('/tentang')">
        <x-slot:aksi>
            <a href="{{ route('panduan') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Buka Panduan Penggunaan
            </a>
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="space-y-6">
        <section class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/logo/logo-kementrans-128.png') }}"
                    alt="Logo Kementerian Transmigrasi" class="h-12 w-12" width="48" height="48" />
                <div>
                    <h2 class="text-theme-xl font-bold text-gray-800 dark:text-white/90">
                        {{ \App\Support\KontenSistem::namaAplikasi() }}
                    </h2>
                    <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                        {{ \App\Support\KontenSistem::subjudul() }}
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <div>
                    <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Latar Belakang &amp; Tujuan</h3>
                    <p class="mt-2 whitespace-pre-line text-theme-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $tentang['latar_belakang'] }}</p>
                </div>
                <div>
                    <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Wilayah Layanan</h3>
                    <ul class="mt-2 grid gap-2 text-theme-xs text-gray-700 sm:grid-cols-2 dark:text-gray-300">
                        @forelse ($daftarSp as $sp)
                            <li class="rounded-lg bg-gray-50 p-2 dark:bg-white/[0.02]">
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $sp['nama'] }}</span>
                                @if ($sp['kecamatan'])
                                    <span class="block text-gray-500 dark:text-gray-400">{{ $sp['kecamatan'] }}</span>
                                @endif
                            </li>
                        @empty
                            <li class="text-gray-500 dark:text-gray-400">Belum ada satuan permukiman.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </section>

        @foreach ([
            'tim' => 'Tim Pengembang',
            'mitra' => 'Mitra Kelembagaan',
            'narahubung' => 'Narahubung & Bantuan',
        ] as $kunci => $judul)
            <section class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-theme-base font-semibold text-gray-800 dark:text-white/90">{{ $judul }}</h3>
                <p class="mt-2 whitespace-pre-line text-theme-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $tentang[$kunci] }}</p>

                @if ($kunci === 'mitra')
                    <ul class="mt-3 space-y-1 text-theme-sm text-gray-600 dark:text-gray-400">
                        @foreach (array_filter($instansi) as $nama)
                            <li>{{ $nama }}</li>
                        @endforeach
                    </ul>
                @elseif ($kunci === 'narahubung')
                    <p class="mt-3 text-theme-sm text-gray-600 dark:text-gray-400">
                        {{ implode(' · ', array_filter($kontak)) }}
                    </p>
                @endif
            </section>
        @endforeach

        <section class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-theme-base font-semibold text-gray-800 dark:text-white/90">Teknologi &amp; Lisensi</h3>
            <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
                Laravel 12.x, PHP 8.2, Tailwind CSS v4, Alpine.js, ApexCharts 5.x, dan Leaflet OpenStreetMap.
                Fondasi antarmuka TailAdmin berlisensi MIT.
            </p>
        </section>
    </div>
@endsection
