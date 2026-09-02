{{--
    Unggahan BEBERAPA berkas untuk satu domain (Putaran 14).

    Berbeda dari `x-sim.file-upload` yang melayani satu berkas dan tetap
    dipakai 22 titik lain. Komponen ini untuk domain yang memang memegang
    banyak berkas lewat pivot `*_berkas` (rules.md 14a.8b): beberapa titik
    kerusakan infrastruktur, beberapa foto bukti pengaduan, atau KTP dan KK
    yang memang dua dokumen berbeda.

    Ketentuan rules.md 14a tetap berlaku dan diperiksa PER BERKAS:
    - batas 5 MB per berkas, bukan total,
    - hanya gambar dan PDF,
    - pemeriksaan di sisi klien lebih dulu agar pengguna berjaringan lemah
      tidak menunggu unggahan yang pasti ditolak peladen.

    Pemakaian:
        <x-sim.berkas-unggah nama="berkas" label="Foto Kondisi"
            :tersimpan="$berkasInfrastruktur" peran="foto" />
--}}
@props([
    'nama' => 'berkas',
    'label' => 'Berkas Pendukung',
    'keterangan' => null,
    'hanyaGambar' => false,

    // Wajib hanya berarti PALING SEDIKIT satu berkas ada. Bila sudah ada
    // berkas tersimpan, isian tidak ditandai required lagi, sebab menyunting
    // data lain tidak boleh memaksa pengunggahan ulang.
    'wajib' => false,

    /*
        Berkas yang SUDAH tersimpan, disuplai pemanggil lewat rute atau
        ViewServiceProvider. Ditampilkan terpisah dari yang baru dipilih, sebab
        keduanya berbeda keadaan: yang tersimpan sudah punya tautan buka,
        yang baru dipilih belum ada di mana pun.
    */
    'tersimpan' => [],

    // Batas jumlah berkas; null berarti tanpa batas.
    'maksBerkas' => null,
])

@php
    $tipeDiterima = $hanyaGambar ? '.jpg,.jpeg,.png,.webp' : '.jpg,.jpeg,.png,.webp,.pdf';
    $daftarTipe = $hanyaGambar ? 'JPG, PNG, atau WEBP' : 'JPG, PNG, WEBP, atau PDF';
@endphp

<div x-data="{
        berkas: [],
        galat: [],
        maksByte: {{ 5 * 1024 * 1024 }},
        maksBerkas: @js($maksBerkas),

        pilih(event) {
            this.galat = [];

            for (const f of Array.from(event.target.files)) {
                /*
                    Batas diperiksa PER BERKAS, dan pesannya menyebut nama
                    berkasnya. Pesan tanpa nama membuat petugas yang memilih
                    lima berkas sekaligus tidak tahu mana yang ditolak.
                */
                if (f.size > this.maksByte) {
                    this.galat.push(f.name + ' melampaui 5 MB (' + this.ukuran(f.size) + ').');
                    continue;
                }

                if (this.maksBerkas && this.jumlahTotal >= this.maksBerkas) {
                    this.galat.push('Batas ' + this.maksBerkas + ' berkas sudah tercapai.');
                    break;
                }

                this.berkas.push({
                    id: Date.now() + Math.random(),
                    nama: f.name,
                    ukuran: this.ukuran(f.size),
                    pratinjau: f.type.startsWith('image/') ? URL.createObjectURL(f) : null,
                    keterangan: '',
                });
            }

            // Isian dikosongkan agar berkas yang sama dapat dipilih ulang
            // setelah dihapus; tanpa ini peramban mengabaikan pilihan kedua.
            event.target.value = '';
        },

        hapus(id) {
            this.berkas = this.berkas.filter((b) => b.id !== id);
        },

        get jumlahTotal() {
            return this.berkas.length + @js(count($tersimpan));
        },

        ukuran(byte) {
            if (byte < 1024) return byte + ' B';
            if (byte < 1048576) return (byte / 1024).toFixed(1) + ' KB';
            return (byte / 1048576).toFixed(1) + ' MB';
        },
    }"
    {{ $attributes->merge(['class' => 'w-full']) }}>

    <p class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
        {{ $label }}{!! $wajib ? '<span class="text-error-500">*</span>' : '' !!}
    </p>

    {{--
        Berkas yang sudah tersimpan. Dipisahkan dari yang baru dipilih sebab
        keduanya berbeda keadaan, dan menyatukannya membuat petugas mengira
        berkas lama ikut terunggah ulang setiap kali menyimpan.

        Hanya NAMA yang ditampilkan, tanpa tautan buka. Tautannya milik panel
        rincian, dan form ini di-include halaman rincian yang sama, sehingga
        memasangnya di sini menerbitkan tautan kembar ke berkas yang sama.
    --}}
    @if (! empty($tersimpan))
        <ul class="mb-3 space-y-2">
            @foreach ($tersimpan as $b)
                <li class="flex items-center gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-white/5">
                        <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25" />
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-theme-sm text-gray-800 dark:text-white/90">
                            {{ $b['nama_file'] }}
                        </span>
                        <span class="block text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $b['keterangan'] ?? $b['peran'] ?? '' }}
                            @if (! empty($b['ukuran']))
                                &middot; {{ number_format($b['ukuran'] / 1024, 0, ',', '.') }} KB
                            @endif
                        </span>
                    </span>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Area unggah, tetap terlihat sebab berkas boleh ditambah berkali --}}
    <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center transition hover:border-brand-400 hover:bg-gray-50 dark:border-gray-700 dark:hover:border-brand-600 dark:hover:bg-white/[0.02]">
        <svg class="mb-2 h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
        </svg>
        <span class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">
            Pilih berkas untuk diunggah
        </span>
        <span class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
            {{ $daftarTipe }}, maksimal 5 MB per berkas. Boleh memilih beberapa sekaligus.
        </span>
        <input type="file" id="{{ $nama }}" name="{{ $nama }}[]" multiple
            accept="{{ $tipeDiterima }}" @change="pilih" class="sr-only"
            @if ($wajib && empty($tersimpan)) required @endif />
    </label>

    {{-- Galat per berkas, menyebut namanya agar jelas mana yang ditolak --}}
    <template x-if="galat.length">
        <ul class="mt-2 space-y-1" role="alert">
            <template x-for="(g, i) in galat" :key="i">
                <li class="text-theme-xs text-error-600 dark:text-error-400" x-text="g"></li>
            </template>
        </ul>
    </template>

    {{-- Berkas yang baru dipilih, masing-masing dapat dihapus --}}
    <ul x-show="berkas.length" x-cloak class="mt-3 space-y-2">
        <template x-for="(b, i) in berkas" :key="b.id">
            <li class="rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <template x-if="b.pratinjau">
                        <img :src="b.pratinjau" alt="" class="h-10 w-10 shrink-0 rounded-lg object-cover" />
                    </template>
                    <template x-if="! b.pratinjau">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 dark:bg-red-500/15">
                            <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25" />
                            </svg>
                        </span>
                    </template>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-theme-sm text-gray-800 dark:text-white/90" x-text="b.nama"></span>
                        <span class="block text-theme-xs text-gray-500 dark:text-gray-400" x-text="b.ukuran"></span>
                    </span>
                    <button type="button" @click="hapus(b.id)" :aria-label="'Hapus berkas ' + b.nama"
                        class="shrink-0 rounded-lg p-1.5 text-gray-400 transition hover:bg-error-50 hover:text-error-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:hover:bg-error-500/15">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </li>
        </template>
    </ul>

    @if ($keterangan)
        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $keterangan }}</p>
    @endif
</div>
