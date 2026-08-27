{{--
    Sepasang tombol penutup laci filter: Terapkan dan Bersihkan.

    Diangkat 2026-08-27 dari tujuh belas halaman yang menuliskannya identik,
    termasuk kelas Tailwind sepanjang dua ratus karakter. Duplikasi sepanjang
    itu tidak bertahan seragam: cukup satu halaman disunting sendiri, dan
    tombol filternya mulai berbeda dari enam belas lainnya tanpa ada yang
    menyadari.

    Bersihkan hanya muncul ketika ADA yang perlu dibersihkan. Menampilkannya
    terus-menerus menjadikannya kontrol yang tidak melakukan apa-apa (R-26),
    dan pada laci filter itu justru menyesatkan: pengguna mengira ada
    penyaring aktif padahal tidak.

    Pemakaian:
        <x-sim.tombol-filter :ada-filter="$adaFilter" :url-bersih="route('sp.index')" />
--}}
@props([
    'adaFilter' => false,
    'urlBersih' => null,
])

<div class="flex items-end gap-2">
    <button type="submit"
        class="h-10 flex-1 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
        Terapkan Filter
    </button>
    @if ($adaFilter)
        <a href="{{ $urlBersih }}"
            class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
            Bersihkan
        </a>
    @endif
</div>
