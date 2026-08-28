{{--
    Kerangka satu halaman laporan.

    Ditambahkan 2026-08-28 (rules.md 12 poin 6). Laporan adalah dokumen
    bernama berformat tetap. Sejak Putaran 3 (D2, 2026-08-28) isinya disajikan
    sebagai "kertas" berbingkai, dan setiap laporan punya rute dokumen polos
    di /laporan/{slug}/dokumen yang dibuka di tab baru untuk tampilan penuh.

    Metadata kepala dokumen (cakupan, dasar periode, sumber, catatan) dibaca
    dari LaporanData::meta($slug), judul dari MenuHelper -- keduanya satu
    sumber, sehingga halaman berbingkai dan rute dokumen mustahil melenceng.

    Setiap tabel di dalam slot WAJIB memuat caption sebagai anak pertama
    (penjaga Temuan 6). Bungkus tabel lebar dengan div overflow-x-auto.

    Prop:
    - slug     : slug laporan, penentu metadata dan judul.
    - dokumen  : true pada rute dokumen polos -- tanpa kop aplikasi, tanpa
                 tombol, siap dicetak.

    Pemakai (pages/laporan/{slug} dan pages/laporan/dokumen) memanggil
    komponen ini lalu meng-include partial pages/laporan/isi/{slug} sebagai
    slot, dengan data laporan dioper eksplisit -- slot komponen tidak
    mewarisi variabel view.
--}}
@props([
    'slug',
    'dokumen' => false,
])

@php
    // Judul dari menu, metadata dari LaporanData: nama laporan hanya ditulis
    // di satu tempat, kepala dokumen di satu tempat lain.
    $butirMenu = collect(\App\Helpers\MenuHelper::definisiMenu())
        ->flatMap(fn ($k) => $k['items'])
        ->flatMap(fn ($i) => $i['subItems'] ?? [])
        ->firstWhere('path', '/laporan/' . $slug);
    $judulLaporan = is_array($butirMenu) ? ($butirMenu['name'] ?? 'Laporan') : 'Laporan';

    $meta = \App\Support\LaporanData::meta($slug);
    $cakupan = $meta['cakupan'] ?? '';
    $dasarPeriode = $meta['dasarPeriode'] ?? '';
    $sumberLabel = $meta['sumberLabel'] ?? null;
    $sumberUrl = isset($meta['sumberRute']) ? route($meta['sumberRute']) : null;
    $catatan = $meta['catatan'] ?? null;
@endphp

@unless ($dokumen)
    <x-sim.page-header :judul="$judulLaporan"
        keterangan="Dokumen laporan berformat tetap untuk kebutuhan dinas, pendamping, dan kementerian."
        :remah="\App\Helpers\RemahHelper::untuk('/laporan/' . $slug)">
        <x-slot:aksi>
            <a href="{{ route('laporan.dokumen', $slug) }}" target="_blank" rel="noopener"
                class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg border border-gray-300 px-4 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
                Buka di tab baru<span class="sr-only">, terbuka di tab baru</span>
            </a>
            <a href="{{ route('laporan.index') }}"
                class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg border border-gray-300 px-4 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Kembali ke Semua Laporan
            </a>
        </x-slot:aksi>
    </x-sim.page-header>
@endunless

{{--
    Kertas dokumen. Lebarnya dibatasi dan diletakkan di tengah agar tabel
    lebar bergulir DI DALAM kertas, bukan melawan sidebar -- itulah keluhan
    "berantakan" yang memicu Putaran 3.
--}}
<article class="mx-auto max-w-5xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
    {{--
        Masthead dokumen: judul, lalu cakupan sebagai TEKS (rules.md 12 poin
        8). Angka rekap tanpa cakupannya tidak dapat disalin ke laporan mana
        pun (rules.md 9), jadi di sinilah cakupan itu dinyatakan.
    --}}
    <header class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
        <h1 class="text-theme-lg font-semibold text-gray-900 dark:text-white">{{ $judulLaporan }}</h1>
        <h2 class="mt-4 text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Cakupan laporan
        </h2>
        <dl class="mt-2 grid gap-x-6 gap-y-2 text-theme-sm sm:grid-cols-2">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Wilayah</dt>
                <dd class="mt-0.5 font-medium text-gray-800 dark:text-white/90">{{ $cakupan }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Dasar periode</dt>
                <dd class="mt-0.5 font-medium text-gray-800 dark:text-white/90">{{ $dasarPeriode }}</dd>
            </div>
            @if ($sumberLabel)
                <div class="sm:col-span-2">
                    <dt class="text-gray-500 dark:text-gray-400">Sumber data</dt>
                    <dd class="mt-0.5 font-medium text-gray-800 dark:text-white/90">
                        @if ($sumberUrl && ! $dokumen)
                            <a href="{{ $sumberUrl }}"
                                class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">{{ $sumberLabel }}</a>
                        @else
                            {{ $sumberLabel }}
                        @endif
                    </dd>
                </div>
            @endif
        </dl>

        @if ($catatan)
            <p class="mt-4 rounded-lg border border-yellow-300 bg-yellow-50 p-3 text-theme-xs text-yellow-800 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-200"
                role="note">
                {{ $catatan }}
            </p>
        @endif
    </header>

    <div class="space-y-8 p-6">
        {{ $slot }}
    </div>
</article>

@unless ($dokumen)
    {{-- Unduh: jujur "segera hadir", bukan tombol yang tampak berfungsi (R-26) --}}
    <div class="cetak-sembunyi mx-auto mt-6 flex max-w-5xl flex-wrap gap-2">
        @foreach (['PDF', 'Excel'] as $format)
            <span
                title="Pembangkitan berkas {{ $format }} dikerjakan pada tahap berikutnya."
                class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Unduh {{ $format }}, segera hadir
            </span>
        @endforeach
    </div>
@endunless
