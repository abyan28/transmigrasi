{{--
    Tautan lihat lokasi beserta modal peta baca-saja.

    Dipakai pada halaman rincian, tempat koordinat hanya perlu DILIHAT dan tidak
    disunting. Untuk form yang memerlukan pemilihan titik, gunakan
    x-sim.koordinat-input yang penandanya dapat digeser.

    Peta dimuat lewat impor dinamis dan hanya diunduh ketika modal dibuka, sama
    seperti pada komponen koordinat. Tautan "Buka di peta penuh" tetap tersedia
    di dalam modal sebagai jalan keluar bila ubin gagal dimuat pada jaringan
    yang lemah.

    Tombol tidak dirender sama sekali bila koordinat kosong, sebab kontrol yang
    tidak menuju ke mana pun dilarang (R-26).

    Pemakaian:
        <x-sim.tautan-peta :lintang="$data['lintang']" :bujur="$data['bujur']"
            label="SP Kapitan Meo" />
--}}
@props([
    'lintang' => null,
    'bujur' => null,
    'label' => null,
])

@php
    $adaTitik = ! empty($lintang) && ! empty($bujur);
    $idPeta = 'lihat-peta-' . uniqid();
@endphp

@if ($adaTitik)
    <div x-data="{
            terbuka: false,
            gagal: false,
            peta: null,

            async buka() {
                this.terbuka = true;
                this.gagal = false;
                window.kunciGulir?.kunci();

                await this.$nextTick();

                try {
                    this.peta = await window.petaSim.buka(this.$refs.wadahPeta, {
                        lintang: @js((float) $lintang),
                        bujur: @js((float) $bujur),
                        dapatDipilih: false,
                    });

                    setTimeout(() => this.peta?.segarkan(), 60);
                } catch (e) {
                    this.gagal = true;
                }
            },

            tutup() {
                if (! this.terbuka) {
                    return;
                }

                this.terbuka = false;
                window.kunciGulir?.lepas();
                this.peta?.musnahkan();
                this.peta = null;
            },
        }"
        x-on:keydown.escape.window="terbuka && tutup()"
        {{ $attributes->only('class') }}>

        <button type="button" @click="buka()"
            class="inline-flex items-center gap-1.5 rounded text-theme-xs font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
            </svg>
            Lihat di peta
        </button>

        <div x-show="terbuka" x-cloak class="fixed inset-0 z-99999 overflow-y-auto" role="dialog"
            aria-modal="true" aria-labelledby="judul-{{ $idPeta }}">

            <div x-show="terbuka" x-transition.opacity @click="tutup()" class="fixed inset-0 bg-gray-900/50"
                aria-hidden="true"></div>

            <div class="flex min-h-full items-end justify-center sm:items-center sm:p-4">
                <div x-show="terbuka" x-transition
                    class="relative w-full sm:max-w-3xl bg-white shadow-xl sm:rounded-2xl dark:bg-gray-900">

                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <h2 id="judul-{{ $idPeta }}"
                                class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                Lokasi{{ $label ? ' ' . $label : '' }}
                            </h2>
                            <p class="mt-0.5 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                                {{ number_format((float) $lintang, 6, '.', '') }},
                                {{ number_format((float) $bujur, 6, '.', '') }}
                            </p>
                        </div>
                        <button type="button" @click="tutup()" aria-label="Tutup peta"
                            class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="px-5 py-4">
                        <div x-ref="wadahPeta" class="h-80 w-full overflow-hidden rounded-xl bg-gray-100 dark:bg-white/5"
                            role="application" aria-label="Peta lokasi"></div>

                        <div x-show="gagal" x-cloak
                            class="mt-3 rounded-lg border border-yellow-300 bg-yellow-50 p-3.5 dark:border-yellow-500/30 dark:bg-yellow-500/10">
                            <p class="text-theme-xs text-yellow-800 dark:text-yellow-200">
                                Peta gagal dimuat, kemungkinan karena jaringan sedang lemah. Koordinatnya tetap
                                dapat dibuka lewat tautan di bawah.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 sm:flex-row sm:justify-between dark:border-gray-800">
                        <a href="https://www.openstreetmap.org/?mlat={{ $lintang }}&mlon={{ $bujur }}#map=17/{{ $lintang }}/{{ $bujur }}"
                            target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2.5 text-theme-sm font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                            Buka di peta penuh
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                        <button type="button" @click="tutup()"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif