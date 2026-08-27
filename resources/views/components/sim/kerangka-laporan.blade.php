{{--
    Kerangka satu halaman laporan.

    Ditambahkan 2026-08-28 (rules.md 12 poin 6). Laporan adalah dokumen
    bernama berformat tetap, bukan potret tabel yang sedang tersaring.

    Yang WAJIB ada pada setiap laporan:
    - judul dokumen,
    - pernyataan cakupan sebagai TEKS di kepala dokumen, bukan kontrol filter
      (rules.md 12 poin 8): wilayah yang dilaporkan dan dasar periodenya,
    - tempat tabel. Format kolomnya menyusul dari dinas, jadi untuk sekarang
      hanya penampung berlabel jujur,
    - tombol unduh yang jujur "segera hadir": pembangkitan PDF dan Excel
      dikerjakan Tahap 10, sehingga dirender sebagai teks, bukan tombol yang
      tampak berfungsi (ANTISLOP-ID R-26).

    Halaman laporan TIDAK punya penyaring sendiri. Penyaringan diwarisi dari
    halaman daftar pasangan lewat pintasan (belum dipasang per 2026-08-28)
    atau lewat pemilih periode untuk laporan lintas-modul.

    Pemakaian:
        <x-sim.kerangka-laporan slug="hasil-panen"
            cakupan="Seluruh satuan permukiman di kawasan Kobalima Timur."
            dasar-periode="Dikelompokkan menurut tahun pengadaan bantuan, bukan tahun panen (rules.md 9 poin 16).">
            <x-slot:catatan>
                Bagian pupuk hanya menampilkan penyalurannya ...
            </x-slot:catatan>
        </x-sim.kerangka-laporan>
--}}
@props([
    'slug',
    'cakupan',
    'dasarPeriode',
    'sumberLabel' => null,
    'sumberUrl' => null,
])

@php
    // Judul dokumen dibaca dari menu, sehingga nama laporan hanya ditulis di
    // satu tempat. Setiap slug pasti ada di menu; fallback hanya jaga-jaga.
    $butirMenu = collect(\App\Helpers\MenuHelper::definisiMenu())
        ->flatMap(fn ($k) => $k['items'])
        ->flatMap(fn ($i) => $i['subItems'] ?? [])
        ->firstWhere('path', '/laporan/' . $slug);
    $judulLaporan = is_array($butirMenu) ? ($butirMenu['name'] ?? 'Laporan') : 'Laporan';
@endphp

<x-sim.page-header :judul="$judulLaporan"
    keterangan="Dokumen laporan berformat tetap untuk kebutuhan dinas, pendamping, dan kementerian."
    :remah="\App\Helpers\RemahHelper::untuk('/laporan/' . $slug)">
    <x-slot:aksi>
        <a href="{{ route('laporan.index') }}"
            class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg border border-gray-300 px-4 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
            Kembali ke Semua Laporan
        </a>
    </x-slot:aksi>
</x-sim.page-header>

{{--
    Cakupan sebagai teks. Angka rekap tanpa cakupannya tidak dapat disalin ke
    laporan mana pun (rules.md 9), jadi di sinilah cakupan itu dinyatakan.
--}}
<section aria-labelledby="cakupan-laporan"
    class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
    <h2 id="cakupan-laporan" class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
        Cakupan laporan
    </h2>
    <dl class="mt-3 grid gap-x-6 gap-y-3 text-theme-sm sm:grid-cols-2">
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
    <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
        Cakupan ditetapkan lewat pintasan dari halaman daftar terkait, bukan
        disaring di halaman ini. Pemilihan periode untuk laporan lintas modul
        dikerjakan pada tahap berikutnya.
    </p>
</section>

@isset($catatan)
    <div class="mb-6 rounded-2xl border border-yellow-300 bg-yellow-50 p-4 text-theme-sm text-yellow-800 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-200"
        role="note">
        {{ $catatan }}
    </div>
@endisset

{{-- Tempat tabel. Format kolomnya belum ditetapkan dinas. --}}
<div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center dark:border-gray-700 dark:bg-white/[0.02]">
    <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
        stroke-width="1.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6zM3.75 9h16.5M9 20.25V9" />
    </svg>
    <p class="mt-3 text-theme-sm font-medium text-gray-700 dark:text-gray-300">
        Format kolom laporan ini sedang disusun bersama dinas.
    </p>
    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
        Tabel dengan data contoh akan tampil di sini setelah susunan kolomnya ditetapkan.
    </p>
</div>

{{-- Unduh: jujur "segera hadir", bukan tombol yang tampak berfungsi (R-26) --}}
<div class="mt-6 flex flex-wrap gap-2">
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
