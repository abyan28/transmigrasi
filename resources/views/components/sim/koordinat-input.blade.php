{{--
    Isian titik koordinat lintang dan bujur.

    Menyediakan tombol pengambilan lokasi lewat Geolocation API, tetapi tetap
    dapat diisi manual bila GPS tidak tersedia. Ini penting karena sinyal di
    lokus tidak selalu stabil (agents/ui-spec.md bagian 6.7).

    Nilai ditampilkan dengan 6 angka desimal mengikuti konvensi format
    (agents/ui-spec.md bagian 10).

    Pemakaian:
        <x-sim.koordinat-input :lintang="$data['lintang']" :bujur="$data['bujur']" />
--}}
@props([
    'lintang' => null,
    'bujur' => null,
    'namaLintang' => 'lintang',
    'namaBujur' => 'bujur',
    'wajib' => false,
])

@php
    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm tabular-nums text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
@endphp

<div x-data="{
        lintang: @js($lintang),
        bujur: @js($bujur),
        mengambil: false,
        galat: '',
        ambilLokasi() {
            this.galat = '';

            if (!navigator.geolocation) {
                this.galat = 'Perangkat ini tidak mendukung pengambilan lokasi otomatis. Silakan isi manual.';
                return;
            }

            this.mengambil = true;

            navigator.geolocation.getCurrentPosition(
                (posisi) => {
                    this.lintang = posisi.coords.latitude.toFixed(7);
                    this.bujur = posisi.coords.longitude.toFixed(7);
                    this.mengambil = false;
                },
                (kesalahan) => {
                    this.mengambil = false;
                    // Pesan ditulis dengan bahasa yang dimengerti operator lapangan,
                    // bukan istilah teknis (agents/rules.md bagian 13.3 poin 7).
                    this.galat = {
                        1: 'Izin lokasi ditolak. Aktifkan izin lokasi pada peramban, atau isi koordinat manual.',
                        2: 'Lokasi tidak dapat ditemukan. Pastikan GPS aktif, atau isi koordinat manual.',
                        3: 'Pengambilan lokasi terlalu lama. Coba lagi, atau isi koordinat manual.',
                    }[kesalahan.code] ?? 'Lokasi gagal diambil. Silakan isi koordinat manual.';
                },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        },
        get tautanPeta() {
            if (!this.lintang || !this.bujur) return null;
            return `https://www.openstreetmap.org/?mlat=${this.lintang}&mlon=${this.bujur}#map=16/${this.lintang}/${this.bujur}`;
        },
    }"
    {{ $attributes->merge(['class' => 'space-y-3']) }}>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $namaLintang }}" class="{{ $kelasLabel }}">
                Lintang{!! $wajib ? '<span class="text-error-500">*</span>' : '' !!}
            </label>
            <input type="number" step="0.0000001" min="-90" max="90" id="{{ $namaLintang }}"
                name="{{ $namaLintang }}" x-model="lintang" placeholder="-9.512345"
                class="{{ $kelasKontrol }}" @if ($wajib) required @endif />
        </div>

        <div>
            <label for="{{ $namaBujur }}" class="{{ $kelasLabel }}">
                Bujur{!! $wajib ? '<span class="text-error-500">*</span>' : '' !!}
            </label>
            <input type="number" step="0.0000001" min="-180" max="180" id="{{ $namaBujur }}"
                name="{{ $namaBujur }}" x-model="bujur" placeholder="124.912345"
                class="{{ $kelasKontrol }}" @if ($wajib) required @endif />
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="button" @click="ambilLokasi()" :disabled="mengambil"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
            <span x-show="mengambil" x-cloak
                class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-brand-500"
                aria-hidden="true"></span>
            <svg x-show="!mengambil" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
            </svg>
            <span x-text="mengambil ? 'Mengambil lokasi...' : 'Ambil lokasi saat ini'"></span>
        </button>

        {{-- Tautan peta hanya muncul bila koordinat sudah terisi, agar tidak ada kontrol mati --}}
        <template x-if="tautanPeta">
            <a :href="tautanPeta" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 text-theme-sm font-medium text-teal-700 hover:underline dark:text-teal-300">
                Lihat di peta
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
            </a>
        </template>
    </div>

    <p x-show="galat" x-cloak x-text="galat" class="text-theme-xs text-error-500"></p>

    <p class="text-theme-xs text-gray-500 dark:text-gray-400">
        Koordinat dapat diisi manual bila GPS tidak tersedia. Contoh untuk kawasan
        Kobalima Timur: lintang -9.512345, bujur 124.912345.
    </p>
</div>
