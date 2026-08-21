{{--
    Isian titik koordinat lintang dan bujur.

    Menyediakan tiga cara mengisi, dari yang paling cepat ke yang paling
    dapat diandalkan:

    1. Tombol pengambilan lokasi lewat Geolocation API.
    2. Tombol pemilihan di peta, untuk membetulkan titik ketika GPS meleset.
       GPS ponsel di lokus kerap meleset puluhan meter, sehingga titik yang
       diambil otomatis belum tentu menunjuk lokasi yang dimaksud.
    3. Pengetikan manual, yang tetap berfungsi walau GPS mati dan peta gagal
       dimuat karena jaringan lemah (agents/ui-spec.md bagian 6.7).

    Peta memakai Leaflet dengan ubin OpenStreetMap, tanpa kunci API. Pustakanya
    dimuat secara dinamis saat peta dibuka pertama kali, sehingga halaman yang
    tidak memerlukan peta tidak ikut menanggung beban unduhannya.

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
    $idPeta = 'peta-' . $namaLintang . '-' . uniqid();
@endphp

<div x-data="{
        lintang: @js($lintang),
        bujur: @js($bujur),
        mengambil: false,
        galat: '',
        petaTerbuka: false,
        petaGagal: false,
        petaSiap: false,
        peta: null,

        ambilLokasi() {
            this.galat = '';

            if (!navigator.geolocation) {
                this.galat = 'Perangkat ini tidak mendukung pengambilan lokasi otomatis. Silakan isi manual atau pilih di peta.';
                return;
            }

            this.mengambil = true;

            navigator.geolocation.getCurrentPosition(
                (posisi) => {
                    this.lintang = posisi.coords.latitude.toFixed(7);
                    this.bujur = posisi.coords.longitude.toFixed(7);
                    this.mengambil = false;

                    // Peta yang sedang terbuka ikut berpindah, agar petugas
                    // langsung melihat apakah titiknya sudah tepat.
                    if (this.peta) {
                        this.peta.pindahkan(this.lintang, this.bujur);
                    }
                },
                (kesalahan) => {
                    this.mengambil = false;
                    // Pesan ditulis dengan bahasa yang dimengerti operator lapangan,
                    // bukan istilah teknis (agents/rules.md bagian 13.3 poin 7).
                    this.galat = {
                        1: 'Izin lokasi ditolak. Aktifkan izin lokasi pada peramban, pilih di peta, atau isi koordinat manual.',
                        2: 'Lokasi tidak dapat ditemukan. Pastikan GPS aktif, pilih di peta, atau isi koordinat manual.',
                        3: 'Pengambilan lokasi terlalu lama. Coba lagi, pilih di peta, atau isi koordinat manual.',
                    }[kesalahan.code] ?? 'Lokasi gagal diambil. Silakan pilih di peta atau isi koordinat manual.';
                },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        },

        async bukaPeta() {
            this.petaTerbuka = true;
            this.petaGagal = false;
            window.kunciGulir?.kunci();

            await this.$nextTick();

            try {
                this.peta = await window.petaSim.buka(this.$refs.wadahPeta, {
                    lintang: this.lintang,
                    bujur: this.bujur,
                    dapatDipilih: true,
                    saatPindah: (lintang, bujur) => {
                        this.lintang = lintang;
                        this.bujur = bujur;
                    },
                });

                this.petaSiap = true;

                // Ukuran dihitung ulang setelah wadahnya benar-benar tampil,
                // sebab peta yang dibuat di dalam elemen tersembunyi salah
                // menghitung tingginya.
                setTimeout(() => this.peta?.segarkan(), 60);
            } catch (e) {
                // Ubin peta memerlukan sambungan. Bila gagal, isian manual dan
                // tombol GPS tetap dipakai, jadi kegagalan ini tidak boleh
                // menghentikan pengisian form.
                this.petaGagal = true;
            }
        },

        tutupPeta() {
            if (! this.petaTerbuka) {
                return;
            }

            this.petaTerbuka = false;
            this.petaSiap = false;
            window.kunciGulir?.lepas();

            this.peta?.musnahkan();
            this.peta = null;
        },

        get tautanPeta() {
            if (!this.lintang || !this.bujur) return null;
            return `https://www.openstreetmap.org/?mlat=${this.lintang}&mlon=${this.bujur}#map=16/${this.lintang}/${this.bujur}`;
        },
    }"
    x-on:keydown.escape.window="petaTerbuka && tutupPeta()"
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

        {{--
            Pemilihan di peta selalu tersedia, tidak bergantung pada koordinat
            yang sudah terisi: justru ketika isian masih kosong petugas paling
            memerlukannya.
        --}}
        <button type="button" @click="bukaPeta()"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />
            </svg>
            Pilih di peta
        </button>

        {{-- Tautan peta hanya muncul bila koordinat sudah terisi, agar tidak ada kontrol mati --}}
        <template x-if="tautanPeta">
            <a :href="tautanPeta" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 text-theme-sm font-medium text-teal-700 hover:underline dark:text-teal-300">
                Buka di peta penuh
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

    {{-- Modal peta pemilih titik --}}
        <div x-show="petaTerbuka" x-cloak class="fixed inset-0 z-99999" role="dialog"
        aria-modal="true" aria-labelledby="judul-{{ $idPeta }}">

        <div x-show="petaTerbuka" x-transition.opacity @click="tutupPeta()" class="fixed inset-0 bg-gray-900/50"
            aria-hidden="true"></div>

        {{-- Pola gulir sama dengan `modal-form`; lihat komentar rinci di sana. --}}
        <div class="flex h-full items-end justify-center overflow-hidden sm:items-start sm:p-4">
            <div x-show="petaTerbuka" x-transition
                class="relative flex max-h-full w-full flex-col bg-white shadow-xl sm:my-auto sm:max-h-[calc(100vh-2rem)] sm:max-w-3xl sm:rounded-2xl dark:bg-gray-900">

                <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h2 id="judul-{{ $idPeta }}"
                            class="text-lg font-semibold text-gray-800 dark:text-white/90">
                            Pilih Titik Lokasi
                        </h2>
                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            Geser penanda atau ketuk peta untuk membetulkan titik bila GPS kurang tepat.
                        </p>
                    </div>
                    <button type="button" @click="tutupPeta()" aria-label="Tutup peta"
                        class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-5 py-4">
                    {{-- Wadah peta. Tinggi ditetapkan agar Leaflet dapat menghitung ubinnya. --}}
                    <div x-ref="wadahPeta" class="h-80 w-full overflow-hidden rounded-xl bg-gray-100 dark:bg-white/5"
                        role="application" aria-label="Peta pemilih titik koordinat"></div>

                    {{--
                        Jalan keluar bila ubin peta gagal dimuat. Peta bergantung
                        pada sambungan, sedangkan isian manual tidak.
                    --}}
                    <div x-show="petaGagal" x-cloak
                        class="mt-3 rounded-lg border border-yellow-300 bg-yellow-50 p-3.5 dark:border-yellow-500/30 dark:bg-yellow-500/10">
                        <p class="text-theme-xs text-yellow-800 dark:text-yellow-200">
                            Peta gagal dimuat, kemungkinan karena jaringan sedang lemah. Koordinat tetap dapat
                            diisi manual atau diambil lewat tombol lokasi.
                        </p>
                    </div>

                    {{--
                        Pesan galat diulang di dalam modal. Pesan di luar terhalang
                        lapisan modal yang menutup seluruh layar, sehingga penolakan
                        izin lokasi tidak akan terlihat dan tombolnya tampak diam
                        saja ketika ditekan.
                    --}}
                    <p x-show="galat" x-cloak x-text="galat"
                        class="mt-3 rounded-lg bg-error-50 p-3 text-theme-xs text-error-600 dark:bg-error-500/10 dark:text-error-400"></p>

                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        <span>
                            Lintang <span class="font-medium tabular-nums text-gray-800 dark:text-white/90"
                                x-text="lintang || '-'"></span>
                        </span>
                        <span>
                            Bujur <span class="font-medium tabular-nums text-gray-800 dark:text-white/90"
                                x-text="bujur || '-'"></span>
                        </span>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                    {{--
                        Pengambilan lokasi diulang di dalam modal. Tombol yang sama
                        di luar modal tidak dapat dijangkau selagi peta terbuka,
                        sehingga petugas yang ingin kembali ke posisi sebenarnya
                        setelah menggeser penanda terpaksa menutup peta lebih dulu.
                    --}}
                    <button type="button" @click="ambilLokasi()" :disabled="mengambil"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        <span x-show="mengambil" x-cloak
                            class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-brand-500"
                            aria-hidden="true"></span>
                        <svg x-show="!mengambil" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        <span x-text="mengambil ? 'Mengambil lokasi...' : 'Ambil lokasi saat ini'"></span>
                    </button>

                    <div class="flex flex-col-reverse gap-2 sm:flex-row">
                    <button type="button" @click="tutupPeta()"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Batal
                    </button>
                    <button type="button" @click="tutupPeta()"
                        class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Gunakan Titik Ini
                    </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>