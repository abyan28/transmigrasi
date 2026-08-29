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
    $konfigFilter = $filter + ['cakupanBawaan' => $cakupan];

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
        </x-slot:aksi>
    </x-sim.page-header>
@endunless

{{--
    Kertas dokumen. Tabel lebar bergulir DI DALAM kertas, bukan melawan
    sidebar -- itulah keluhan "berantakan" yang memicu Putaran 3. Kelas
    orientasi mengatur kepadatan selnya lewat app.css.
--}}
<article x-data="filterLaporan(@js($konfigFilter))"
    class="kertas-dokumen dokumen-{{ $orientasi }} mx-auto {{ $lebarKertas }} overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
    {{--
        Masthead dokumen: judul, lalu cakupan sebagai TEKS (rules.md 12 poin
        8). Angka rekap tanpa cakupannya tidak dapat disalin ke laporan mana
        pun (rules.md 9), jadi di sinilah cakupan itu dinyatakan.

        Kalimat "Wilayah" disusun ulang oleh Alpine mengikuti filter yang
        aktif (D3): dokumen yang dicetak atau difoto kehilangan kontrol
        filternya, jadi cakupan yang sedang berlaku wajib ikut tercetak.
    --}}
    <header class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
        <h1 class="text-theme-lg font-semibold text-gray-900 dark:text-white">{{ $judulLaporan }}</h1>
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

    @if (! empty($filter))
        <x-sim.filter-laporan :sp="$filter['sp'] ?? []" :tahun="$filter['tahun'] ?? false"
            :label-tahun="$filter['labelTahun'] ?? 'Tahun'" :daftar-tahun="$filter['daftarTahun'] ?? []"
            :dimensi="$filter['dimensi'] ?? []" />
    @endif

    <div class="space-y-8 p-6">
        {{ $slot }}
    </div>
</article>

@unless ($dokumen)
    {{-- Unduh: jujur "segera hadir", bukan tombol yang tampak berfungsi (R-26) --}}
    <div class="cetak-sembunyi mx-auto mt-6 flex {{ $lebarKertas }} flex-wrap gap-2">
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
