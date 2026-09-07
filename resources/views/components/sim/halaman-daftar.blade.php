{{--
    Kerangka halaman daftar sederhana.

    Dipakai modul gelombang 2 yang polanya seragam: kepala halaman, kartu
    ringkasan opsional, lalu satu tabel. Ditulis sekali di sini agar 18
    halaman tidak menyalin markup yang sama, dan agar perubahan pola cukup
    dilakukan di satu tempat.

    BEDA dengan `x-sim.data-table`: komponen itu menangani tabelnya saja,
    sedangkan komponen ini menangani seluruh halaman beserta kepala, filter,
    dan pembungkus formnya.

    Modul yang memerlukan susunan khusus, seperti transmigran dan pengaduan
    pada gelombang 1, tetap menulis halamannya sendiri. Kerangka ini untuk
    yang polanya memang seragam, bukan untuk memaksa semua halaman sama.

    Pemakaian:
        <x-sim.halaman-daftar judul="Data Komoditas" keterangan="..."
            :remah="[['label' => 'Pertanian'], ['label' => 'Komoditas']]"
            :jumlah="count($baris)" :kata-kunci="$cari"
            :aksi-url="route('komoditas.index')">
            <x-slot:kartu> ... </x-slot:kartu>
            <x-slot:kepala> ... </x-slot:kepala>
            ...baris tabel...
        </x-sim.halaman-daftar>
--}}
@props([
    'judul',
    'keterangan' => null,
    'remah' => [],
    'jumlah' => 0,
    'kataKunci' => null,
    'aksiUrl' => null,
    'placeholderCari' => 'Cari data',
    'judulKosong' => 'Belum ada data',
    'pesanKosong' => null,
    'paginator' => null, // Fase 1: diteruskan apa adanya ke x-sim.data-table
    'kolomMetrik' => 4,
])

<x-sim.page-header :judul="$judul" :keterangan="$keterangan" :remah="$remah">
    @isset($aksi)
        <x-slot:aksi>{{ $aksi }}</x-slot:aksi>
    @endisset
</x-sim.page-header>

{{-- Wadah Ringkasan: Mendukung Bilah Ramping (Metrik) dan Kartu Klasik dengan Alih Tampilan --}}
@if (isset($metrik) || isset($ringkasan))
    @php
        $prefKey = 'pref_ringkasan_' . \Illuminate\Support\Str::slug($judul);
    @endphp
    @if (isset($metrik) && isset($ringkasan))
        <div x-data="{
                modeRingkas: localStorage.getItem('{{ $prefKey }}') !== 'kartu',
                toggleMode() {
                    this.modeRingkas = !this.modeRingkas;
                    localStorage.setItem('{{ $prefKey }}', this.modeRingkas ? 'strip' : 'kartu');
                }
            }"
            class="mb-6">

            {{-- Baris kontrol alih tampilan kecil --}}
            <div class="mb-2 flex items-center justify-between gap-2 px-1">
                <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Ringkasan {{ $judul }}
                </p>
                <button type="button" @click="toggleMode()"
                    :title="modeRingkas ? 'Beralih ke tampilan kartu' : 'Beralih ke tampilan bilah ramping'"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-theme-xs font-medium text-gray-600 shadow-2xs hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/5">
                    <template x-if="modeRingkas">
                        <span class="inline-flex items-center gap-1">
                            <svg class="h-3.5 w-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                            Tampilan Kartu
                        </span>
                    </template>
                    <template x-if="!modeRingkas">
                        <span class="inline-flex items-center gap-1">
                            <svg class="h-3.5 w-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                            </svg>
                            Tampilan Ramping
                        </span>
                    </template>
                </button>
            </div>

            {{-- Desain 1: Compact Metric Strip (Default Ramping & Visual) --}}
            <div x-show="modeRingkas" x-transition.opacity>
                <x-sim.metric-strip :kolom="$kolomMetrik">
                    {{ $metrik }}
                </x-sim.metric-strip>
            </div>

            {{-- Desain 2: Kartu Kotak Lama (Opsi Revert / Fallback Penuh) --}}
            <div x-show="!modeRingkas" x-cloak x-transition.opacity class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {{ $ringkasan }}
            </div>
        </div>
    @elseif (isset($metrik))
        <div class="mb-6">
            <x-sim.metric-strip :kolom="$kolomMetrik">
                {{ $metrik }}
            </x-sim.metric-strip>
        </div>
    @else
        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {{ $ringkasan }}
        </div>
    @endif
@endif

{{-- Pencarian dan filter dibungkus satu form agar terkirim bersama --}}
<form method="GET" @if ($aksiUrl) action="{{ $aksiUrl }}" @endif>
    {{--
        Judul halaman diteruskan menjadi <caption> tabelnya. Dengan begitu
        seluruh halaman yang memakai kerangka ini memperoleh nama tabel bagi
        pembaca layar tanpa satu pun perlu disunting.
    --}}
    <x-sim.data-table :judul="$judul" :jumlah="$jumlah" :kata-kunci="$kataKunci"
        :placeholder-cari="$placeholderCari" :judul-kosong="$judulKosong"
        :pesan-kosong="$pesanKosong" :paginator="$paginator">

        @isset($filter)
            <x-slot:filter>{{ $filter }}</x-slot:filter>
        @endisset

        <x-slot:aksiKanan>
            <button type="submit"
                class="h-10 shrink-0 rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Cari
            </button>

            {{--
                Tombol ekspor DICABUT dari sini 2026-08-28 (rules.md 12 poin
                7). Sebelumnya ia muncul otomatis di setiap halaman daftar,
                belasan tombol tanpa laporan di baliknya (kontrol mati R-26).
                Laporan kini dokumen bernama di menu "Laporan" tersendiri.
            --}}
        </x-slot:aksiKanan>

        @isset($aksiKosong)
            <x-slot:aksiKosong>{{ $aksiKosong }}</x-slot:aksiKosong>
        @endisset

        <x-slot:kepala>{{ $kepala }}</x-slot:kepala>

        {{ $slot }}

        @isset($kaki)
            <x-slot:kaki>{{ $kaki }}</x-slot:kaki>
        @endisset

        @isset($kartu)
            <x-slot:kartu>{{ $kartu }}</x-slot:kartu>
        @endisset
    </x-sim.data-table>
</form>

@isset($setelahTabel)
    {{ $setelahTabel }}
@endisset
