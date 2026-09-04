{{--
    Kerangka satu halaman laporan.

    Ditambahkan 2026-08-28 (rules.md 12 poin 6). Laporan adalah dokumen
    bernama berformat tetap. Sejak Putaran 3 (D2, 2026-08-28) isinya disajikan
    sebagai "kertas" berbingkai, dan setiap laporan punya rute dokumen polos
    di /laporan/{slug}/dokumen yang dibuka di tab baru untuk tampilan penuh.

    Judul dan metadata kepala dokumen (cakupan, dasar periode, sumber,
    catatan) seluruhnya dibaca dari LaporanData::meta($slug) -- satu sumber
    untuk nama, urutan, izin, kolom, dan orientasi laporan.

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
    // Judul, cakupan, dan metadata lain dari satu sumber: LaporanData::meta().
    $meta = \App\Support\LaporanData::meta($slug);
    $judulLaporan = $meta['judul'] ?? 'Laporan';
    $cakupan = $meta['cakupan'] ?? '';
    $dasarPeriode = $meta['dasarPeriode'] ?? '';
    $sumberLabel = $meta['sumberLabel'] ?? null;
    $sumberUrl = isset($meta['sumberRute']) ? route($meta['sumberRute']) : null;
    $catatan = $meta['catatan'] ?? null;

    // Konfigurasi bilah filter (D3). Larik kosong berarti laporan ini belum
    // berfilter; cakupan Alpine tetap dipasang agar partial isi yang memakai
    // x-show tidak pecah pada laporan tanpa filter.
    $filter = \App\Support\LaporanData::filterLaporan($slug);
    $konfigFilter = $filter + [
        'cakupanBawaan' => $cakupan,
        // Tahun rujukan untuk baris "TAHUN ..." kop dokumen (Putaran 5).
        'tahunBawaanDokumen' => \App\Support\LaporanData::tahunDokumenBawaan(),
    ];

    // Orientasi diturunkan dari jumlah kolom, bukan dipilih tangan (D2b).
    $orientasi = \App\Support\LaporanData::orientasi($slug);
    $landscape = $orientasi === 'landscape';

    /*
        Lebar kertas.

        Di dalam aplikasi, laporan landscape MEMENUHI ruang yang tersedia
        (keputusan pemilik proyek): itulah yang paling jarang memunculkan
        gulir mendatar, sebab area konten sudah dibatasi breakpoint-2xl
        dikurangi sidebar. Laporan potret tetap dibatasi agar terbaca sebagai
        dokumen, bukan sebagai layar penuh.

        Pada rute dokumen barulah proporsi A4 sesungguhnya ditegakkan.
        Memakai max-w, BUKAN w-[...px]: penjaga "tidak memakai lebar tetap
        yang berlaku pada layar sempit" melarang w-[NNNpx] di atas 360.
    */
    $lebarKertas = $dokumen
        ? ($landscape ? 'max-w-[1200px]' : 'max-w-[820px]')
        : ($landscape ? 'max-w-full' : 'max-w-5xl');
@endphp

{{--
    Ukuran kertas saat dicetak. @page tidak dapat dibatasi per elemen,
    sehingga aturannya didorong ke <head> lewat stack; layout dirender paling
    akhir, jadi push dari dalam @section tetap sampai.
--}}
@push('gaya')
    <style>
        @page {
            size: A4 {{ $orientasi }};
            margin: {{ $landscape ? '10mm' : '12mm' }};
        }
    </style>
@endpush

{{--
    Cakupan Alpine dipasang pada <div> pembungkus (Putaran 5) -- bukan lagi
    pada <article> -- supaya tombol "Generate Laporan" di dalam page-header
    (di luar <article>) ikut membaca keadaan filter untuk menyusun hash.
--}}
<div x-data="filterLaporan(@js($konfigFilter))">
    @unless ($dokumen)
        <x-sim.page-header :judul="$judulLaporan"
            keterangan="Dokumen laporan berformat tetap untuk kebutuhan dinas, pendamping, dan kementerian."
            :remah="\App\Helpers\RemahHelper::untuk('/laporan/' . $slug)">
            <x-slot:aksi>
                <div class="flex flex-wrap items-center gap-2.5">
                    {{-- Pemilih Ukuran Kertas (Opsi 2: Pill Selector) --}}
                    <div class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-gray-800 dark:bg-gray-800/80">
                        <span class="px-2 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kertas:</span>
                        <button type="button" @click="ukuranKertas = 'a4'"
                            :class="ukuranKertas === 'a4' ? 'bg-white font-semibold text-gray-900 shadow-sm dark:bg-gray-900 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white font-medium'"
                            class="rounded-md px-2.5 py-1 text-theme-xs transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                            A4
                        </button>
                        <button type="button" @click="ukuranKertas = 'f4'"
                            :class="ukuranKertas === 'f4' ? 'bg-white font-semibold text-gray-900 shadow-sm dark:bg-gray-900 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white font-medium'"
                            class="rounded-md px-2.5 py-1 text-theme-xs transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                            F4 (Folio)
                        </button>
                    </div>

                    {{--
                        "Generate Laporan": membuka rute dokumen di tab baru dengan
                        keadaan filter dibawa lewat FRAGMEN HASH (#sp=..&td=..&kertas=..).
                        GitHub Pages tidak melayani query string (notes.md 1b.5),
                        tetapi hash murni sisi peramban. `hashFilter` kosong berarti
                        href = alamat polos.
                    --}}
                    <a :href="@js(route('laporan.dokumen', $slug)) + hashFilter" target="_blank" rel="noopener"
                        class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                        Generate Laporan<span class="sr-only">, terbuka di tab baru</span>
                    </a>
                </div>
            </x-slot:aksi>
        </x-sim.page-header>
    @endunless

    {{--
        Kertas dokumen. Tabel lebar bergulir DI DALAM kertas, bukan melawan
        sidebar -- itulah keluhan "berantakan" yang memicu Putaran 3. Kelas
        orientasi mengatur kepadatan selnya lewat app.css. Pada rute dokumen
        bingkai kartu (rounded + shadow) dilepas supaya terbaca sebagai kertas.
    --}}
    <article
        :class="{
            'kertas-f4': ukuranKertas === 'f4',
            @if ($dokumen)
                'max-w-[1320px]': ukuranKertas === 'f4' && {{ $landscape ? 'true' : 'false' }},
                'max-w-[880px]': ukuranKertas === 'f4' && ! {{ $landscape ? 'true' : 'false' }},
                'max-w-[1200px]': ukuranKertas !== 'f4' && {{ $landscape ? 'true' : 'false' }},
                'max-w-[820px]': ukuranKertas !== 'f4' && ! {{ $landscape ? 'true' : 'false' }},
            @else
                '{{ $landscape ? 'max-w-full' : 'max-w-5xl' }}': true,
            @endif
        }"
        class="kertas-dokumen dokumen-{{ $orientasi }} mx-auto overflow-hidden border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 {{ $dokumen ? '' : 'rounded-2xl shadow-sm' }}">
        @if ($dokumen)
            {{-- Kop surat resmi (Putaran 5), menggantikan blok "Cakupan laporan". --}}
            <x-sim.kop-laporan :slug="$slug" />
        @else
            {{--
                Masthead ringkas halaman berbingkai: judul, lalu cakupan sebagai
                TEKS (rules.md 12 poin 8). Kalimat "Wilayah" disusun ulang Alpine
                mengikuti filter aktif.
            --}}
            <header class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                <h1 class="text-theme-lg font-semibold text-gray-900 dark:text-white">{{ $judulLaporan }}</h1>
                @if ($slug === 'hasil-panen')
                    <p class="mt-1 text-theme-xs font-semibold uppercase text-brand-600 dark:text-brand-400" x-text="tahunDokumen">
                        TAHUN ANGGARAN {{ \App\Support\LaporanData::tahunDokumenBawaan() }}
                    </p>
                @endif
                <h2 class="mt-4 text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Cakupan laporan
                </h2>
                <dl class="mt-2 grid gap-x-6 gap-y-2 text-theme-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Wilayah</dt>
                        <dd class="mt-0.5 font-medium text-gray-800 dark:text-white/90"
                            x-text="kalimatCakupan">{{ $cakupan }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Dasar periode</dt>
                        <dd class="mt-0.5 font-medium text-gray-800 dark:text-white/90">{{ $dasarPeriode }}</dd>
                    </div>
                    @if ($sumberLabel)
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500 dark:text-gray-400">Sumber data</dt>
                            <dd class="mt-0.5 font-medium text-gray-800 dark:text-white/90">
                                @if ($sumberUrl)
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
        @endif

        @if (! empty($filter) && ! $dokumen)
            <x-sim.filter-laporan :sp="$filter['sp'] ?? []" :tahun="$filter['tahun'] ?? false"
                :tahun-tunggal="$filter['tahunTunggal'] ?? false" :tahun-bawaan="$filter['tahunBawaan'] ?? null"
                :label-tahun="$filter['labelTahun'] ?? 'Tahun'" :daftar-tahun="$filter['daftarTahun'] ?? []"
                :dimensi="$filter['dimensi'] ?? []" />
        @endif

        <div class="space-y-8 p-6">
            {{ $slot }}
        </div>
    </article>

    @unless ($dokumen)
        {{--
            Unduh (Task 10.1/10.2, 2026-09-05): sepenuhnya sisi peramban,
            tanpa paket Composer -- keputusan pemilik proyek membalik
            penundaan sebelumnya (rules.md 12 poin 11). PDF memakai ulang
            @media print yang sudah matang lewat dialog cetak peramban;
            Excel membaca tabel yang SUDAH DIRENDER (dan sudah tersaring
            filter Alpine) lewat SheetJS -- lihat resources/js/export-laporan.js.
        --}}
        <div class="cetak-sembunyi mx-auto mt-6 flex {{ $lebarKertas }} flex-wrap gap-2">
            <a :href="@js(route('laporan.dokumen', $slug)) + hashFilterCetak" target="_blank" rel="noopener"
                class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Unduh PDF
                <span class="sr-only">, membuka dokumen di tab baru lalu memicu dialog cetak peramban</span>
            </a>
            <button type="button"
                @click="window.exportLaporan.keExcel($root, @js($slug))"
                class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Unduh Excel
            </button>
        </div>
    @endunless
</div>
