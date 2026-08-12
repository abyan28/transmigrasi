{{--
    Isian kata sandi beserta tombol perlihatkan atau sembunyikan.

    Dipakai berulang pada halaman masuk, ubah kata sandi, dan wajib ganti kata
    sandi, sehingga ditulis satu kali di sini agar tidak disalin ke banyak
    berkas (agents/rules.md bagian 19 poin 4).

    Tombol perlihatkan memakai aria-label yang ikut berubah, karena ikon saja
    tidak terbaca pembaca layar (agents/ui-spec.md bagian 11.1, R-32).

    Pemakaian:
        <x-sim.input-kata-sandi nama="password" label="Kata Sandi Baru"
            autocomplete="new-password" :wajib="true" />
--}}
@props([
    'nama',
    'label' => 'Kata Sandi',
    'keterangan' => null,
    'wajib' => false,
    'autocomplete' => 'current-password',
    'placeholder' => 'Masukkan kata sandi',
])

<div x-data="{ tampilkan: false }" {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    <label for="{{ $nama }}" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
        {{ $label }}{!! $wajib ? '<span class="text-error-500">*</span>' : '' !!}
    </label>

    <div class="relative">
        <input :type="tampilkan ? 'text' : 'password'" id="{{ $nama }}" name="{{ $nama }}"
            autocomplete="{{ $autocomplete }}" placeholder="{{ $placeholder }}"
            @if ($wajib) required @endif
            {{ $attributes->except('class') }}
            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-11 pl-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30" />

        <button type="button" @click="tampilkan = !tampilkan"
            :aria-label="tampilkan ? 'Sembunyikan kata sandi' : 'Perlihatkan kata sandi'"
            class="absolute top-1/2 right-3 -translate-y-1/2 rounded p-1 text-gray-500 hover:text-gray-700 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:text-gray-200">
            <svg x-show="!tampilkan" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <svg x-show="tampilkan" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
        </button>
    </div>

    @if ($keterangan)
        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $keterangan }}</p>
    @endif

    @error($nama)
        <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
