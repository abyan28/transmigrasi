{{--
    Dashboard monitoring kawasan.

    Memuat 17 indikator sesuai pemetaan pada agents/ui-spec.md bagian 9.
    Komposisi mengikuti dial RITME 2: baris kartu statistik di atas, lalu grid
    grafik dua kolom yang TIDAK sama lebar, agar dashboard terbaca berbeda dari
    halaman daftar dan halaman rekap (bagian 2.2).

    Seluruh angka masih berasal dari app/Support/DummyData.php. Penggantian ke
    query nyata dikerjakan pada Task 9.1 tanpa mengubah berkas ini, karena nama
    kunci array sudah mengikuti kamus data.

    Filter wilayah dan periode sudah berbentuk kontrol nyata yang menulis query
    string, tetapi penyaringan datanya baru aktif pada Task 9.2. Kontrol tetap
    berfungsi mengubah URL, bukan tombol mati (ANTISLOP-ID R-26).
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `beranda`, termasuk
        `$dataGrafik` yang menjadi bahan grafiknya. Lihat routes/web.php.
    --}}

    <x-sim.page-header judul="Dashboard Kawasan Kobalima Timur"
        keterangan="Ringkasan kependudukan, lahan, produksi, dan pengaduan di enam satuan permukiman."
        :remah="\App\Helpers\RemahHelper::untuk('/')" />

    {{--
        Filter global yang kompak dan efisien ruang. Memengaruhi seluruh visualisasi mulai Task 9.2; saat ini
        pilihannya tersimpan di query string sehingga tetap bertahan setelah halaman dimuat ulang.
    --}}
    <form method="GET" action="{{ route('beranda') }}"
        class="mb-6 rounded-2xl border border-gray-200 bg-white p-3.5 sm:p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="filter_sp" class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                    Satuan Permukiman
                </label>
                <select id="filter_sp" name="sp"
                    class="h-9.5 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                    <option value="">Seluruh kawasan</option>
                    @foreach ($daftarSp as $sp)
                        <option value="{{ $sp['id_satuan_permukiman'] }}"
                            @selected(request('sp') == $sp['id_satuan_permukiman'])>
                            {{ $sp['nama'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter_tahun_awal"
                    class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                    Tahun Mulai
                </label>
                <select id="filter_tahun_awal" name="tahun_awal"
                    class="h-9.5 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm tabular-nums text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                    @foreach ($deret['tahun'] as $tahun)
                        <option value="{{ $tahun }}" @selected(request('tahun_awal', $deret['tahun'][0]) == $tahun)>
                            {{ $tahun }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter_tahun_akhir"
                    class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                    Tahun Akhir
                </label>
                <select id="filter_tahun_akhir" name="tahun_akhir"
                    class="h-9.5 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm tabular-nums text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                    @foreach ($deret['tahun'] as $tahun)
                        <option value="{{ $tahun }}" @selected(request('tahun_akhir', end($deret['tahun'])) == $tahun)>
                            {{ $tahun }}
                        </option>
                    @endforeach
                </select>
            </div>

            <x-sim.tombol-filter :ada-filter="request()->hasAny(['sp', 'tahun_awal', 'tahun_akhir'])"
                :url-bersih="route('beranda')" />
        </div>
    </form>

    {{-- ============================================================
         1. RINGKASAN KAWASAN
         ============================================================ --}}
    <x-sim.judul-bagian judul="Ringkasan Kawasan"
        keterangan="Angka pokok kawasan pada satu pandangan." class="!mt-0" />

    @php
        $persenPanenDariTanam = $ringkasan['realisasi_tanam_ha'] > 0 ? round($ringkasan['hasil_panen_ha'] / $ringkasan['realisasi_tanam_ha'] * 100, 1) : 0;
        $persenSisaDariTanam = $ringkasan['realisasi_tanam_ha'] > 0 ? round($ringkasan['belum_dipanen_ha'] / $ringkasan['realisasi_tanam_ha'] * 100, 1) : 0;
        $persenKomoditasUtama = $ringkasan['volume_panen_ton'] > 0 ? round($sebaranKomoditas[$komoditasUtama] / $ringkasan['volume_panen_ton'] * 100, 1) : 0;
    @endphp

    <div class="mb-6 grid gap-5 grid-cols-1 lg:grid-cols-3">
        {{-- Pilar Kependudukan & Perumahan --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 flex flex-col justify-between dark:border-gray-800 dark:bg-white/[0.03]">
            <div>
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800/80">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </span>
                        <h3 class="text-theme-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Kependudukan & Hunian
                        </h3>
                    </div>
                    <a href="{{ url('/kependudukan/rekap') }}" class="rounded text-theme-xs font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-400">
                        Rincian &rarr;
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">Total Kepala Keluarga</p>
                    <p class="mt-1 flex items-baseline gap-1.5">
                        <span class="text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($ringkasan['jumlah_kk'], 0, ',', '.') }}
                        </span>
                        <span class="text-theme-sm font-medium text-gray-500 dark:text-gray-400">KK</span>
                    </p>
                    <p class="mt-0.5 text-theme-xs text-gray-600 dark:text-gray-400">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ number_format($ringkasan['jumlah_jiwa'], 0, ',', '.') }}</span> jiwa di seluruh kawasan
                    </p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3 border-t border-gray-100 pt-4 dark:border-gray-800/80">
                <a href="{{ url('/rumah') }}" class="group rounded-xl border border-gray-100 bg-gray-50/60 p-3 transition hover:border-brand-200 hover:bg-brand-50/20 dark:border-gray-800 dark:bg-white/[0.02] dark:hover:border-brand-800/60">
                    <div class="flex items-center gap-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        <svg class="h-3.5 w-3.5 text-gray-400 group-hover:text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Rumah Terhuni</span>
                    </div>
                    <p class="mt-1 text-theme-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                        {{ number_format($ringkasan['rumah_terhuni'], 0, ',', '.') }} <span class="text-theme-xs font-normal text-gray-500 dark:text-gray-400">/ {{ number_format($ringkasan['rumah_total'], 0, ',', '.') }}</span>
                    </p>
                    <div class="mt-2 flex items-center gap-2">
                        <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-1.5 rounded-full bg-teal-500" style="width: {{ min(100, $persenHuni) }}%"></div>
                        </div>
                        <span class="text-theme-xs font-medium tabular-nums text-gray-600 dark:text-gray-400">{{ $persenHuni }}%</span>
                    </div>
                </a>

                <a href="{{ url('/transmigran') }}" class="group rounded-xl border border-gray-100 bg-gray-50/60 p-3 transition hover:border-brand-200 hover:bg-brand-50/20 dark:border-gray-800 dark:bg-white/[0.02] dark:hover:border-brand-800/60">
                    <div class="flex items-center gap-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        <svg class="h-3.5 w-3.5 text-gray-400 group-hover:text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Jumlah Petani</span>
                    </div>
                    <p class="mt-1 text-theme-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                        {{ number_format($ringkasan['jumlah_petani'], 0, ',', '.') }} <span class="text-theme-xs font-normal text-gray-500 dark:text-gray-400">orang</span>
                    </p>
                    <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ round($ringkasan['jumlah_petani'] / $ringkasan['jumlah_kk'] * 100) }}%</span> dari total KK
                    </p>
                </a>
            </div>
        </div>

        {{-- Pilar Pengelolaan Lahan & Siklus Tanam --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 flex flex-col justify-between dark:border-gray-800 dark:bg-white/[0.03]">
            <div>
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800/80">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-600 dark:bg-teal-500/15 dark:text-teal-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                        </span>
                        <h3 class="text-theme-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Lahan & Siklus Tanam
                        </h3>
                    </div>
                    <a href="{{ url('/lahan') }}" class="rounded text-theme-xs font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-400">
                        Rincian &rarr;
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">Luas Lahan Tergarap</p>
                    <p class="mt-1 flex items-baseline gap-1.5">
                        <span class="text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($ringkasan['luas_lahan_total'], 2, ',', '.') }}
                        </span>
                        <span class="text-theme-sm font-medium text-gray-500 dark:text-gray-400">ha</span>
                    </p>
                    <p class="mt-0.5 text-theme-xs text-gray-600 dark:text-gray-400">
                        Tersebar di {{ count($daftarSp) }} satuan permukiman
                    </p>
                </div>
            </div>

            <div class="mt-5 space-y-2.5 border-t border-gray-100 pt-4 dark:border-gray-800/80">
                <a href="{{ url('/penanaman') }}" class="group block rounded-xl border border-gray-100 bg-gray-50/60 p-2.5 transition hover:border-brand-200 hover:bg-brand-50/20 dark:border-gray-800 dark:bg-white/[0.02] dark:hover:border-brand-800/60">
                    <div class="flex items-center justify-between text-theme-xs">
                        <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                            <svg class="h-3.5 w-3.5 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span>Realisasi Tanam</span>
                        </span>
                        <span class="font-bold tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($ringkasan['realisasi_tanam_ha'], 2, ',', '.') }} ha <span class="font-normal text-gray-500">({{ $persenTanam }}% dari luas lahan)</span>
                        </span>
                    </div>

                    <div class="mt-2 flex h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700" title="Distribusi Hasil Tanam">
                        <div class="bg-teal-500 transition-all" style="width: {{ $persenPanenDariTanam }}%" title="Panen: {{ $persenPanenDariTanam }}%"></div>
                        <div class="bg-amber-500 transition-all" style="width: {{ $persenPuso }}%" title="Puso: {{ $persenPuso }}%"></div>
                        <div class="bg-gray-400/50 transition-all" style="width: {{ $persenSisaDariTanam }}%" title="Belum Panen: {{ $persenSisaDariTanam }}%"></div>
                    </div>
                </a>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <a href="{{ url('/panen/rekap') }}" class="group rounded-xl border border-gray-100 bg-gray-50/60 p-2.5 transition hover:border-brand-200 hover:bg-brand-50/20 dark:border-gray-800 dark:bg-white/[0.02] dark:hover:border-brand-800/60">
                        <div class="flex items-center gap-1 text-theme-xs text-gray-500 dark:text-gray-400">
                            <span class="inline-block h-2 w-2 rounded-full bg-teal-500"></span>
                            <span>Hasil Panen</span>
                        </div>
                        <p class="mt-0.5 text-theme-xs font-bold tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($ringkasan['hasil_panen_ha'], 2, ',', '.') }} ha
                        </p>
                        <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                            {{ number_format($ringkasan['belum_dipanen_ha'], 2, ',', '.') }} ha sisa
                        </p>
                    </a>

                    <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-2.5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                                <span>Puso (Gagal)</span>
                            </div>
                            <span class="inline-flex items-center rounded-md bg-amber-50 px-1.5 py-0.2 text-[10px] font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                                {{ $persenPuso }}%
                            </span>
                        </div>
                        <p class="mt-0.5 text-theme-xs font-bold tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($ringkasan['puso_ha'], 2, ',', '.') }} ha
                        </p>
                        <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                            dari luas tanam
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pilar Produksi & Nilai Pasar --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 flex flex-col justify-between dark:border-gray-800 dark:bg-white/[0.03]">
            <div>
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800/80">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <h3 class="text-theme-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Produksi & Nilai Pasar
                        </h3>
                    </div>
                    <a href="{{ url('/panen') }}" class="rounded text-theme-xs font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-400">
                        Rincian &rarr;
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">Volume Panen {{ $tahunTerakhir }}</p>
                    <p class="mt-1 flex items-baseline gap-1.5">
                        <span class="text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($ringkasan['volume_panen_ton'], 3, ',', '.') }}
                        </span>
                        <span class="text-theme-sm font-medium text-gray-500 dark:text-gray-400">ton</span>
                    </p>
                    <p class="mt-0.5 text-theme-xs text-gray-600 dark:text-gray-400">
                        Hasil konversi seluruh komoditas ke ton
                    </p>
                </div>
            </div>

            <div class="mt-5 space-y-2.5 border-t border-gray-100 pt-4 dark:border-gray-800/80">
                <a href="{{ url('/komoditas') }}" class="group block rounded-xl border border-sand-200 bg-sand-50/50 p-2.5 transition hover:border-sand-300 hover:bg-sand-100/50 dark:border-sand-800/60 dark:bg-sand-950/20 dark:hover:border-sand-700/80">
                    <div class="flex items-center justify-between text-theme-xs">
                        <span class="flex items-center gap-1.5 font-medium text-sand-800 dark:text-sand-300">
                            <span class="flex h-4 w-4 items-center justify-center rounded-full bg-sand-200 text-sand-700 dark:bg-sand-800 dark:text-sand-300">★</span>
                            <span>Komoditas Utama</span>
                        </span>
                        <span class="rounded-md bg-sand-100 px-1.5 py-0.5 text-[11px] font-bold text-sand-800 dark:bg-sand-900/50 dark:text-sand-300">
                            {{ $komoditasUtama }}
                        </span>
                    </div>
                    <p class="mt-1 text-right text-[11px] text-gray-600 dark:text-gray-400">
                        <span class="font-semibold text-gray-800 dark:text-white/90">{{ number_format($sebaranKomoditas[$komoditasUtama], 1, ',', '.') }} ton dipanen</span> ({{ $persenKomoditasUtama }}% kontribusi)
                    </p>
                </a>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-2.5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <div class="flex items-center gap-1 text-theme-xs text-gray-500 dark:text-gray-400">
                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span>Produktivitas Rata-rata</span>
                        </div>
                        <p class="mt-0.5 text-theme-xs font-bold tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($ringkasan['produktivitas_ton_ha'], 3, ',', '.') }} <span class="font-normal text-gray-500">t/ha</span>
                        </p>
                        <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                            Rata-rata tertimbang
                        </p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-2.5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <div class="flex items-center gap-1 text-theme-xs text-gray-500 dark:text-gray-400">
                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span>Harga Rata-rata</span>
                        </div>
                        <p class="mt-0.5 text-theme-xs font-bold tabular-nums text-gray-800 dark:text-white/90">
                            Rp {{ number_format($ringkasan['harga_rata_rata'], 0, ',', '.') }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                            Per ton komoditas
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert Card Status Operasional Pengaduan --}}
    <div class="mb-8 rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                            Pengaduan Belum Selesai
                        </h3>
                        <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-theme-xs font-bold text-rose-700 dark:bg-rose-500/20 dark:text-rose-400">
                            {{ number_format($ringkasan['pengaduan_terbuka'], 0, ',', '.') }} Kasus
                        </span>
                    </div>
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        Laporan warga yang masih menunggu tindak lanjut dan penyelesaian dari petugas lapangan.
                    </p>
                </div>
            </div>
            <a href="{{ url('/pengaduan') }}"
                class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-theme-xs font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <span>Kelola Pengaduan</span>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. KEPENDUDUKAN
         ============================================================ --}}
    <x-sim.judul-bagian judul="Kependudukan"
        keterangan="Berapa banyak warga, bagaimana perpindahannya, dan dari apa mereka hidup." />

    <div class="mb-6 grid gap-6 xl:grid-cols-3">
        <x-sim.chart-card class="xl:col-span-2" id="grafikPenduduk" judul="Pertumbuhan Penduduk Kawasan"
            keterangan="Jumlah transmigran, kepala keluarga, dan petani per tahun."
            tinggi="340">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <caption class="sr-only">Pertumbuhan penduduk kawasan per tahun</caption>
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Jiwa</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">KK</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Petani</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($deret['tahun'] as $i => $tahun)
                            <tr>
                                <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">{{ $tahun }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($deret['jumlah_jiwa'][$i], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($deret['jumlah_kk'][$i], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($deret['jumlah_petani'][$i], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        <x-sim.chart-card id="grafikMutasiKk" judul="Kepala Keluarga Masuk dan Keluar"
            keterangan="Perpindahan KK tiap tahun." tinggi="300">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <caption class="sr-only">Kepala keluarga masuk dan keluar per tahun</caption>
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Masuk</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Keluar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($deret['tahun'] as $i => $tahun)
                            <tr>
                                <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">{{ $tahun }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($deret['kk_masuk'][$i], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($deret['kk_keluar'][$i], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        <x-sim.chart-card class="xl:col-span-2" id="grafikPekerjaan" judul="Pekerjaan Kepala Keluarga"
            keterangan="Sebaran mata pencaharian utama di seluruh kawasan." tinggi="320">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <caption class="sr-only">Pekerjaan kepala keluarga</caption>
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Pekerjaan</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Jumlah KK</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($sebaranPekerjaan as $nama => $jumlah)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-white/90">{{ $nama }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($jumlah, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        <x-sim.chart-card id="grafikPenghuni" judul="Status Tinggal Penghuni"
            keterangan="Rekap kepala keluarga menurut keberadaannya di kawasan." tinggi="300">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <caption class="sr-only">Status tinggal penghuni kawasan</caption>
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Jumlah KK</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($rekapPenghuni as $status => $jumlah)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-white/90">{{ $status }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($jumlah, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>
    </div>

    {{-- ============================================================
         3. PERTANIAN DAN EKONOMI
         ============================================================ --}}
    <x-sim.judul-bagian judul="Pertanian dan Ekonomi"
        keterangan="Apa yang ditanam, berapa hasilnya, dan bagaimana dampaknya bagi pendapatan keluarga." />

    <div class="mb-6 grid gap-6 xl:grid-cols-3">
        <x-sim.chart-card class="xl:col-span-2" id="grafikPanen" judul="Volume Panen per Tahun"
            keterangan="Seluruh komoditas dikonversi ke ton sebelum dijumlahkan." tinggi="320">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <caption class="sr-only">Volume panen kawasan per tahun</caption>
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Volume (ton)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($deret['tahun'] as $i => $tahun)
                            <tr>
                                <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">{{ $tahun }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($deret['volume_panen'][$i], 3, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        <x-sim.chart-card id="grafikKomoditas" judul="Sebaran Komoditas"
            keterangan="Volume panen per komoditas, dalam ton." tinggi="340">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <caption class="sr-only">Sebaran volume panen per komoditas</caption>
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Komoditas</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Volume (ton)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($sebaranKomoditas as $nama => $volume)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-white/90">{{ $nama }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($volume, 1, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        <x-sim.chart-card class="xl:col-span-2" id="grafikPendapatan" judul="Pendapatan Keluarga per Bulan"
            keterangan="Rata-rata pendapatan kepala keluarga tiap tahun." tinggi="300">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <caption class="sr-only">Pendapatan keluarga per bulan tiap tahun</caption>
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($deret['tahun'] as $i => $tahun)
                            <tr>
                                <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">{{ $tahun }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    Rp {{ number_format($deret['pendapatan_rata_rata'][$i], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        <x-sim.chart-card id="grafikHarga" judul="Harga Jual Rata-rata"
            keterangan="Rupiah per ton, seluruh komoditas." tinggi="320">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <caption class="sr-only">Harga jual rata-rata per tahun</caption>
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Harga</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($deret['tahun'] as $i => $tahun)
                            <tr>
                                <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">{{ $tahun }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    Rp {{ number_format($deret['harga_rata_rata'][$i], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>
    </div>

    {{-- ============================================================
         4. INFRASTRUKTUR DAN LAYANAN
         ============================================================ --}}
    <x-sim.judul-bagian judul="Infrastruktur dan Layanan"
        keterangan="Kesiapan layanan dasar tiap permukiman beserta laporan warga yang menunggu ditangani." />

    <div class="mb-6 grid gap-6 xl:grid-cols-3">
        <x-sim.chart-card class="xl:col-span-2" id="grafikInfrastruktur" judul="Kondisi Infrastruktur SP"
            keterangan="Jumlah aset menurut kondisi terkini, dipecah per jenis." tinggi="320">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <caption class="sr-only">Kondisi infrastruktur per jenis</caption>
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Jenis</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Baik</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Rusak Ringan</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Rusak Berat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($statusInfra as $baris)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-white/90">{{ $baris['jenis'] }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $baris['baik'] }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $baris['rusak_ringan'] }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $baris['rusak_berat'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        <x-sim.chart-card id="grafikStatusPengaduan" judul="Pengaduan per Status"
            keterangan="Seluruh laporan warga menurut tahap penanganannya." tinggi="320">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <caption class="sr-only">Pengaduan menurut status penanganan</caption>
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($rekapStatusPengaduan as $baris)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-white/90">{{ $baris['nama'] }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($baris['jumlah'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>
    </div>

    {{-- Kontainer Status Kondisi SP --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 p-5 dark:border-gray-800">
            <div>
                <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Kondisi Layanan Dasar per SP</h3>
                <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Menilai ketersediaan dan kondisi infrastruktur serta fasilitas, bukan penghuninya.
                </p>
            </div>
            <a href="{{ route('sp.index') }}"
                class="rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Lihat Seluruh SP
            </a>
        </div>

        {{-- Ringkasan status --}}
        <div class="grid gap-4 border-b border-gray-200 p-5 sm:grid-cols-3 dark:border-gray-800">
            @foreach (\App\Enums\StatusKondisiSp::cases() as $status)
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <div class="flex items-center justify-between gap-2">
                        <x-sim.status-badge :status="$status" ukuran="sm" />
                        <span class="text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                            {{ $rekapKondisi[$status->value] }}
                        </span>
                    </div>
                    <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $status->keterangan() }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- Tabel per SP --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-left">
                <caption class="sr-only">Kondisi layanan dasar per satuan permukiman</caption>
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Satuan Permukiman</th>
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Skor</th>
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Yang Perlu Diperhatikan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($penilaianSp as $p)
                        @php $penyebab = $penyebabSp[$p['satuan_permukiman_id']]; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-3">
                                <a href="{{ route('sp.detail', $p['satuan_permukiman_id']) }}"
                                    class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                    {{ $p['satuan_permukiman'] }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($p['skor'], 2, ',', '.') }}
                            </td>
                            <td class="px-5 py-3">
                                <x-sim.status-badge :status="$p['status']" />
                            </td>
                            <td class="px-5 py-3 text-theme-xs text-gray-600 dark:text-gray-400">
                                @if (empty($penyebab))
                                    <span class="text-gray-500 dark:text-gray-400">Seluruh layanan berfungsi baik</span>
                                @else
                                    {{ implode(', ', array_slice($penyebab, 0, 3)) }}
                                    @if (count($penyebab) > 3)
                                        <span class="text-gray-500 dark:text-gray-400">
                                            dan {{ count($penyebab) - 3 }} lainnya
                                        </span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Kartu untuk layar sempit --}}
        <div class="divide-y divide-gray-200 md:hidden dark:divide-gray-800">
            @foreach ($penilaianSp as $p)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <a href="{{ route('sp.detail', $p['satuan_permukiman_id']) }}"
                            class="rounded text-theme-sm font-medium text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90">
                            {{ $p['satuan_permukiman'] }}
                        </a>
                        <x-sim.status-badge :status="$p['status']" ukuran="sm" />
                    </div>
                    <p class="mt-1 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                        Skor {{ number_format($p['skor'], 2, ',', '.') }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Kontainer Isu Prioritas Pengaduan --}}
    <div class="mb-8 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-5 dark:border-gray-800">
            <div>
                <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Isu Prioritas per SP</h3>
                <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    {{ count($isuPrioritas) }} pengaduan menunggu ditindaklanjuti, diurutkan dari yang paling mendesak.
                </p>
            </div>
            <a href="{{ route('pengaduan.index') }}"
                class="rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Lihat Semua Pengaduan
            </a>
        </div>

        @if (empty($isuPrioritas))
            <x-sim.empty-state judul="Tidak ada pengaduan terbuka"
                pesan="Seluruh pengaduan di kawasan ini sudah selesai ditangani." />
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left">
                    <caption class="sr-only">Pengaduan yang belum selesai</caption>
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nomor</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Perihal</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Satuan Permukiman</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Prioritas</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($isuPrioritas as $isu)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $isu['nomor_pengaduan'] }}
                                </td>
                                <td class="px-5 py-3">
                                    <a href="{{ route('pengaduan.detail', $isu['id_pengaduan']) }}"
                                        class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                        {{ $isu['judul'] }}
                                    </a>
                                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                        {{ $isu['kategori'] }}
                                    </p>
                                </td>
                                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                    {{ $isu['satuan_permukiman'] }}
                                </td>
                                <td class="px-5 py-3">
                                    <x-sim.status-badge
                                        :status="\App\Enums\PrioritasPengaduan::from($isu['prioritas'])" />
                                </td>
                                <td class="px-5 py-3">
                                    <x-sim.status-badge :status="\App\Enums\StatusPengaduan::from($isu['status'])" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-200 md:hidden dark:divide-gray-800">
                @foreach ($isuPrioritas as $isu)
                    <div class="p-4">
                        <a href="{{ route('pengaduan.detail', $isu['id_pengaduan']) }}"
                            class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                            {{ $isu['judul'] }}
                        </a>
                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $isu['nomor_pengaduan'] }} &middot; {{ $isu['satuan_permukiman'] }}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <x-sim.status-badge :status="\App\Enums\PrioritasPengaduan::from($isu['prioritas'])"
                                ukuran="sm" />
                            <x-sim.status-badge :status="\App\Enums\StatusPengaduan::from($isu['status'])"
                                ukuran="sm" />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ============================================================
         5. PERBANDINGAN ANTAR SATUAN PERMUKIMAN (DUAL Y-AXIS)
         ============================================================ --}}
    <x-sim.judul-bagian judul="Perbandingan Antar Satuan Permukiman"
        keterangan="Menempatkan keenam permukiman berdampingan untuk melihat yang tertinggal." />

    <div class="mb-6">
        <x-sim.chart-card id="grafikPerSp" judul="Perbandingan Antar Satuan Permukiman"
            keterangan="Klik salah satu batang untuk membuka rincian satuan permukiman tersebut." tinggi="360">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <caption class="sr-only">Perbandingan antar satuan permukiman</caption>
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Satuan Permukiman</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">KK</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Rumah Terhuni</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Luas Lahan (ha)</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Panen (ton)</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Rincian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($rekapSp as $baris)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-white/90">
                                    {{ $baris['satuan_permukiman'] }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($baris['jumlah_kk'], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($baris['rumah_terhuni'], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($baris['luas_lahan'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($baris['volume_panen'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('sp.detail', $baris['satuan_permukiman_id']) }}"
                                        class="rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                        Buka rincian
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>
    </div>

    @push('scripts')
        <script type="module">
            const data = @json($dataGrafik);
            const { buatGrafik, angka, rupiah, angkaSingkat, warnaKondisi, warnaKomoditas, warnaStatusPengaduan, drilldownSp } = window.grafikSim;

            // 1. Pertumbuhan Penduduk Kawasan (Tingkat kontras multi-series optimal)
            buatGrafik('grafikPenduduk', {
                chart: { type: 'line', height: 340 },
                series: [
                    { name: 'Jiwa', data: data.jiwa },
                    { name: 'Kepala Keluarga', data: data.kk },
                    { name: 'Petani', data: data.petani },
                ],
                colors: ['#163B54', '#0BA5EC', '#C09546'],
                stroke: { curve: 'smooth', width: 2.5 },
                markers: { size: 0, hover: { size: 5 } },
                xaxis: { categories: data.tahun },
                yaxis: { labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 0) + ' orang' } },
            });

            // 2. Mutasi Kepala Keluarga
            buatGrafik('grafikMutasiKk', {
                chart: { type: 'bar', height: 300 },
                series: [
                    { name: 'KK Masuk', data: data.kkMasuk },
                    { name: 'KK Keluar', data: data.kkKeluar },
                ],
                colors: ['#33809C', '#C09546'],
                plotOptions: { bar: { columnWidth: '60%', borderRadius: 3, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.tahun },
                yaxis: { labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 0) + ' KK' } },
            });

            // 3. Pekerjaan Kepala Keluarga
            buatGrafik('grafikPekerjaan', {
                chart: { type: 'bar', height: 320 },
                series: [{ name: 'Kepala Keluarga', data: data.pekerjaanNilai }],
                plotOptions: { bar: { horizontal: true, barHeight: '62%', borderRadius: 4, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.pekerjaanNama, labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 0) + ' KK' } },
            });

            // 4. Status Tinggal Penghuni
            buatGrafik('grafikPenghuni', {
                chart: { type: 'donut', height: 300 },
                series: data.penghuniNilai,
                labels: data.penghuniNama,
                colors: ['#12b76a', '#f79009', '#98a2b3', '#667085'],
                plotOptions: { pie: { donut: { size: '62%' } } },
                tooltip: { y: { formatter: (v) => angka(v, 0) + ' KK' } },
            });

            // 5. Volume Panen per Tahun
            buatGrafik('grafikPanen', {
                chart: { type: 'bar', height: 320 },
                series: [{ name: 'Volume Panen', data: data.volumePanen }],
                colors: ['#265F73'],
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.tahun },
                yaxis: { labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 3) + ' ton' } },
            });

            // 6. Sebaran Komoditas (Palet Kategorikal Khusus Pertanian Tanpa Warna Biru Kembar)
            buatGrafik('grafikKomoditas', {
                chart: { type: 'donut', height: 340 },
                series: data.komoditasNilai,
                labels: data.komoditasNama,
                colors: warnaKomoditas,
                plotOptions: {
                    pie: {
                        donut: {
                            size: '62%',
                            labels: {
                                show: true,
                                total: { show: true, label: 'Total', formatter: () => angka(data.komoditasNilai.reduce((a, b) => a + b, 0), 1) + ' t' },
                            },
                        },
                    },
                },
                tooltip: { y: { formatter: (v) => angka(v, 1) + ' ton' } },
            });

            // 7. Tren Pendapatan Keluarga (Smooth Area Chart)
            buatGrafik('grafikPendapatan', {
                chart: { type: 'area', height: 300 },
                series: [{ name: 'Pendapatan', data: data.pendapatan }],
                colors: ['#163B54'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 90, 100],
                    },
                },
                stroke: { curve: 'smooth', width: 2.5 },
                markers: { size: 0, hover: { size: 5 } },
                xaxis: { categories: data.tahun },
                yaxis: { labels: { formatter: (v) => angkaSingkat(v) } },
                tooltip: { y: { formatter: (v) => rupiah(v) + ' per bulan' } },
            });

            // 8. Harga Jual Rata-rata
            buatGrafik('grafikHarga', {
                chart: { type: 'line', height: 320 },
                series: [{ name: 'Harga Rata-rata', data: data.harga }],
                colors: ['#C09546'],
                stroke: { curve: 'smooth', width: 2.5 },
                markers: { size: 0, hover: { size: 5 } },
                xaxis: { categories: data.tahun },
                yaxis: { labels: { formatter: (v) => angkaSingkat(v) } },
                tooltip: { y: { formatter: (v) => rupiah(v) + ' per ton' } },
            });

            // 9. Status Infrastruktur SP (Stacked Bar)
            buatGrafik('grafikInfrastruktur', {
                chart: { type: 'bar', height: 320, stacked: true },
                series: [
                    { name: 'Baik', data: data.infraBaik },
                    { name: 'Rusak Ringan', data: data.infraRusakRingan },
                    { name: 'Rusak Berat', data: data.infraRusakBerat },
                ],
                colors: [warnaKondisi.baik, warnaKondisi.rusakRingan, warnaKondisi.rusakBerat],
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 3, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.infraJenis },
                yaxis: { labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 0) + ' aset' } },
            });

            // 10. Pengaduan per Status (Palet Semantik Selaras Status Badge)
            buatGrafik('grafikStatusPengaduan', {
                chart: { type: 'donut', height: 320 },
                series: data.statusPengaduanNilai,
                labels: data.statusPengaduanNama,
                colors: data.statusPengaduanNama.map((nama) => warnaStatusPengaduan[nama] || '#64748B'),
                plotOptions: {
                    pie: {
                        donut: {
                            size: '62%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: () => angka(data.statusPengaduanNilai.reduce((a, b) => a + b, 0), 0),
                                },
                            },
                        },
                    },
                },
                legend: { position: 'bottom' },
                tooltip: { y: { formatter: (v) => angka(v, 0) + ' pengaduan' } },
            });

            // 11. Perbandingan Antar SP (Dual Y-Axis: KK Navy kiri, Ton Gold kanan)
            buatGrafik('grafikPerSp', {
                chart: {
                    type: 'bar',
                    height: 360,
                    events: { dataPointSelection: drilldownSp(data.spId, @js(url('/sp'))) },
                },
                series: [
                    { name: 'Kepala Keluarga', data: data.spKk },
                    { name: 'Volume Panen (ton)', data: data.spPanen },
                ],
                colors: ['#163B54', '#C09546'],
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.spNama, labels: { rotate: -25, trim: true, hideOverlappingLabels: false } },
                yaxis: [
                    {
                        title: { text: 'Kepala Keluarga (KK)', style: { fontSize: '12px', fontWeight: 500 } },
                        labels: { formatter: (v) => angka(v, 0) },
                    },
                    {
                        opposite: true,
                        title: { text: 'Volume Panen (ton)', style: { fontSize: '12px', fontWeight: 500 } },
                        labels: { formatter: (v) => angka(v, 0) },
                    },
                ],
                tooltip: {
                    y: {
                        formatter: (v, { seriesIndex }) => seriesIndex === 0 ? angka(v, 0) + ' KK' : angka(v, 2) + ' ton'
                    }
                },
                states: { active: { filter: { type: 'darken', value: 0.85 } } },
            });
        </script>
    @endpush
@endsection
