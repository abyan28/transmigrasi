{{--
    Kerangka halaman galat, dipakai bersama oleh 403, 404, dan galat lain.

    Ditulis sekali agar ketiganya tidak berbeda gaya tanpa alasan, dan agar
    penambahan halaman galat berikutnya tidak menyalin markup.

    Dua hal yang sengaja dijaga:

    1. **Tautan kembali menyesuaikan keadaan.** Halaman galat kerap dibuka
       pengunjung yang belum masuk, sehingga mengarahkan semua orang ke
       dashboard akan memutar mereka ke halaman masuk. Karena itu tujuan
       kembali dapat ditentukan pemanggil.
    2. **Ilustrasi punya varian mode gelap**, mengikuti kewajiban kedua mode
       berfungsi penuh (ANTISLOP-ID R-34).

    Pemakaian:
        <x-sim.halaman-galat kode="404" judul="Halaman tidak ditemukan"
            pesan="..." ilustrasi="404">
            <x-slot:aksi>...</x-slot:aksi>
        </x-sim.halaman-galat>
--}}
@props([
    'kode',
    'judul',
    'pesan' => null,
    'ilustrasi' => null,
])

<div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden p-6">
    <div class="mx-auto w-full max-w-lg text-center">
        {{-- Kode galat ditulis besar sebagai penanda, bukan hiasan --}}
        <p class="text-theme-sm font-semibold tracking-wide text-brand-600 dark:text-brand-400">
            Galat {{ $kode }}
        </p>

        <h1 class="mt-2 text-title-sm font-semibold text-gray-800 sm:text-title-md dark:text-white/90">
            {{ $judul }}
        </h1>

        @if ($ilustrasi)
            <img src="{{ asset('images/error/' . $ilustrasi . '.svg') }}" alt="" role="presentation"
                class="mx-auto mt-6 w-full max-w-sm dark:hidden" />
            <img src="{{ asset('images/error/' . $ilustrasi . '-dark.svg') }}" alt="" role="presentation"
                class="mx-auto mt-6 hidden w-full max-w-sm dark:block" />
        @endif

        @if ($pesan)
            <p class="mt-6 text-theme-sm text-gray-600 sm:text-base dark:text-gray-400">
                {{ $pesan }}
            </p>
        @endif

        @isset($aksi)
            <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                {{ $aksi }}
            </div>
        @endisset
    </div>

    <p class="absolute bottom-6 text-center text-theme-xs text-gray-500 dark:text-gray-400">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </p>

    {{--
        Tombol ganti tema disertakan di sini, bukan di tiap halaman galat,
        agar tidak disalin dua kali. Halaman galat kerap menjadi halaman
        pertama yang dibuka pengguna, sehingga tetap harus dapat disesuaikan
        seperti halaman lain (ANTISLOP-ID R-34).
    --}}
    <div class="fixed right-5 bottom-5 z-50" x-data>
        <button type="button" aria-label="Ganti mode terang atau gelap" @click="$store.theme.toggle()"
            class="inline-flex size-12 items-center justify-center rounded-full bg-brand-500 text-white shadow-lg transition-colors hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
            <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
            </svg>
            <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
            </svg>
        </button>
    </div>
</div>
