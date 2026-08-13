{{--
    Tajuk pemisah antarbagian pada halaman panjang.

    Dashboard memuat belasan visualisasi. Tanpa pemisah, seluruhnya terbaca
    sebagai satu tumpukan panjang dan pembaca tidak dapat membangun gambaran
    utuh tentang satu topik pun. Tajuk ini mengelompokkannya per pokok bahasan,
    sehingga grafik yang membahas hal serupa berdekatan dan dapat dibaca
    sebagai satu kesatuan.

    Memakai <h2> karena judul halaman memakai <h1> dan judul kartu grafik
    memakai <h3>. Tanpa lapisan ini hierarki tajuk melompat, dan pembaca layar
    kehilangan penanda pindah bagian.

    Pemakaian:
        <x-sim.judul-bagian judul="Kependudukan"
            keterangan="Berapa banyak warga dan bagaimana perpindahannya." />
--}}
@props([
    'judul',
    'keterangan' => null,
])

<div {{ $attributes->merge(['class' => 'mb-4 mt-8 flex items-center gap-3']) }}>
    {{-- Garis tegak sebagai penanda visual, menegaskan awal bagian baru --}}
    <span class="h-9 w-1 shrink-0 rounded-full bg-brand-500" aria-hidden="true"></span>

    <div class="min-w-0">
        <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $judul }}</h2>
        @if ($keterangan)
            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $keterangan }}</p>
        @endif
    </div>
</div>
