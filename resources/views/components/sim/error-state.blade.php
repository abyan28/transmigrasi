{{--
    Keadaan galat, saat data gagal dimuat.

    Aturan pada agents/ui-spec.md bagian 7: ikon, pesan ramah berbahasa
    Indonesia, dan tombol "Coba lagi".

    Pesan wajib memakai bahasa yang dimengerti operator lapangan, bukan istilah
    teknis (agents/rules.md bagian 13.3 poin 7). Karena itu pesan bawaan di
    sini menyebut kemungkinan penyebab yang benar-benar sering terjadi di
    lokus, yaitu jaringan yang tidak stabil, bukan istilah seperti "gagal
    memuat sumber daya" atau kode galat HTTP.

    Berbeda dari halaman 403 dan 404 yang menggantikan seluruh halaman,
    komponen ini dipakai untuk satu bagian yang gagal dimuat, sementara bagian
    lain tetap dapat dipakai.

    Pemakaian:
        <div x-show="galat">
            <x-sim.error-state @coba-lagi="muatUlang()" />
        </div>
--}}
@props([
    'judul' => 'Data gagal ditampilkan',
    'pesan' => 'Sambungan ke server terputus atau jaringan sedang tidak stabil. Periksa sambungan Anda, lalu coba lagi.',
    'labelCobaLagi' => 'Coba Lagi',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-16 text-center']) }}
    role="alert">
    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-50 dark:bg-red-500/15">
        <svg class="h-7 w-7 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
    </div>

    <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $judul }}</h3>

    <p class="mt-1.5 max-w-sm text-theme-xs text-gray-500 dark:text-gray-400">{{ $pesan }}</p>

    <div class="mt-5 flex flex-col gap-2 sm:flex-row">
        {{--
            Tombol memakai pemuatan ulang halaman sebagai perilaku bawaan.
            Halaman yang memuat data lewat Alpine dapat menimpanya dengan
            mendengarkan peristiwa `coba-lagi`.
        --}}
        <button type="button" @click="$dispatch('coba-lagi'); window.location.reload()"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            {{ $labelCobaLagi }}
        </button>

        @isset($aksi)
            {{ $aksi }}
        @endisset
    </div>
</div>
