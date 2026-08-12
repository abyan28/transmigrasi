{{--
    Unggahan dokumen atau foto pendukung.

    Ketentuan pada agents/rules.md bagian 14a:
    - batas 5 MB per berkas,
    - hanya gambar dan PDF,
    - validasi tipe dan ukuran diperiksa di sisi klien lebih dulu,
    - aturan penamaan ditampilkan agar operator tahu hasil akhirnya.

    Pemakaian:
        <x-sim.file-upload nama="dokumen_pendukung" label="Kartu Keluarga"
            nama-dokumen="KartuKeluarga" nama-pemilik="Yohanes Bere" />
--}}
@props([
    'nama',
    'label' => 'Dokumen pendukung',
    'keterangan' => null,
    'wajib' => false,
    'hanyaGambar' => false,
    'namaDokumen' => null,
    'namaPemilik' => null,
    'berkasSaatIni' => null,
])

@php
    $tipeDiterima = $hanyaGambar ? '.jpg,.jpeg,.png,.webp' : '.jpg,.jpeg,.png,.webp,.pdf';
    $daftarTipe = $hanyaGambar ? 'JPG, PNG, atau WEBP' : 'JPG, PNG, WEBP, atau PDF';

    // Meniru pola penamaan pada PenyimpananDokumen agar operator melihat
    // nama akhir berkas sebelum mengunggah.
    $contohNama = null;
    if ($namaDokumen) {
        $dokumen = str_replace(' ', '', ucwords($namaDokumen));
        $contohNama = $namaPemilik
            ? $dokumen . '_' . \Illuminate\Support\Str::slug($namaPemilik) . '.pdf'
            : $dokumen . '.pdf';
    }
@endphp

<div x-data="{
        berkas: null,
        galat: '',
        pratinjau: null,
        maksByte: {{ 5 * 1024 * 1024 }},
        pilih(event) {
            const f = event.target.files[0];
            this.galat = '';
            this.pratinjau = null;

            if (!f) { this.berkas = null; return; }

            // Diperiksa lebih dulu di sisi klien agar pengguna berjaringan lemah
            // tidak menunggu unggahan yang pasti ditolak server.
            if (f.size > this.maksByte) {
                this.galat = 'Ukuran berkas maksimal 5 MB. Berkas Anda ' + this.ukuran(f.size) + '.';
                event.target.value = '';
                this.berkas = null;
                return;
            }

            this.berkas = { nama: f.name, ukuran: this.ukuran(f.size), tipe: f.type };

            if (f.type.startsWith('image/')) {
                this.pratinjau = URL.createObjectURL(f);
            }
        },
        hapus() {
            this.berkas = null;
            this.pratinjau = null;
            this.galat = '';
            this.$refs.masukan.value = '';
        },
        ukuran(byte) {
            if (byte < 1024) return byte + ' B';
            if (byte < 1048576) return (byte / 1024).toFixed(1) + ' KB';
            return (byte / 1048576).toFixed(1) + ' MB';
        },
    }"
    {{ $attributes->merge(['class' => 'w-full']) }}>

    <label for="{{ $nama }}" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
        {{ $label }}{!! $wajib ? '<span class="text-error-500">*</span>' : '' !!}
    </label>

    {{-- Berkas yang sudah tersimpan sebelumnya --}}
    @if ($berkasSaatIni)
        <p class="mb-2 text-theme-xs text-gray-500 dark:text-gray-400">
            Berkas saat ini: <span class="font-medium">{{ basename($berkasSaatIni) }}</span>.
            Mengunggah berkas baru akan menggantikannya.
        </p>
    @endif

    {{-- Area unggah, tersembunyi setelah berkas dipilih --}}
    <label x-show="!berkas"
        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center transition hover:border-brand-400 hover:bg-gray-50 dark:border-gray-700 dark:hover:border-brand-600 dark:hover:bg-white/[0.02]">
        <svg class="mb-2 h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
        </svg>
        <span class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">
            Pilih berkas untuk diunggah
        </span>
        <span class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
            {{ $daftarTipe }}, maksimal 5 MB
        </span>
        <input x-ref="masukan" type="file" id="{{ $nama }}" name="{{ $nama }}"
            accept="{{ $tipeDiterima }}" @change="pilih" class="sr-only" @if ($wajib) required @endif />
    </label>

    {{-- Ringkasan berkas terpilih --}}
    <div x-show="berkas" x-cloak
        class="flex items-center gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-800">
        <template x-if="pratinjau">
            <img :src="pratinjau" alt="Pratinjau berkas" class="h-12 w-12 rounded-lg object-cover" />
        </template>
        <template x-if="!pratinjau">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-50 dark:bg-red-500/15">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </span>
        </template>

        <div class="min-w-0 flex-1">
            <p class="truncate text-theme-sm font-medium text-gray-800 dark:text-white/90" x-text="berkas?.nama"></p>
            <p class="text-theme-xs text-gray-500 dark:text-gray-400" x-text="berkas?.ukuran"></p>
        </div>

        <button type="button" @click="hapus()" aria-label="Hapus berkas terpilih"
            class="shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:hover:bg-white/5">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Pesan galat sisi klien --}}
    <p x-show="galat" x-cloak x-text="galat" class="mt-1.5 text-theme-xs text-error-500"></p>

    @if ($keterangan)
        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $keterangan }}</p>
    @endif

    @if ($contohNama)
        <p class="mt-1 text-theme-xs text-gray-400 dark:text-gray-500">
            Berkas akan disimpan sebagai <span class="font-mono">{{ $contohNama }}</span>
        </p>
    @endif

    @error($nama)
        <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
