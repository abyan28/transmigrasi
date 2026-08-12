{{--
    Keadaan memuat, berupa kerangka yang menyerupai bentuk kontennya.

    Aturan pada agents/ui-spec.md bagian 7: keadaan memuat WAJIB memakai
    skeleton yang menyerupai bentuk konten, bukan spinner layar penuh.
    Alasannya, spinner menutupi seluruh halaman sehingga pengguna kehilangan
    konteks, sedangkan skeleton memberi tahu apa yang sedang datang.

    Komponen preloader bawaan TailAdmin hanya dipakai saat pemuatan awal
    aplikasi, bukan per bagian.

    Ragam yang tersedia mengikuti bentuk konten yang sudah dipakai halaman:
    - `tabel`  : baris tabel pada halaman daftar
    - `kartu`  : kartu statistik pada dashboard dan halaman daftar
    - `grafik` : wadah grafik pada dashboard
    - `teks`   : blok teks pada halaman rincian

    Pemakaian:
        <div x-show="memuat"><x-sim.skeleton ragam="tabel" :baris="5" /></div>
--}}
@props([
    'ragam' => 'teks',
    'baris' => 3,
])

@php
    // Lebar dibuat berbeda-beda agar kerangka terbaca sebagai teks yang wajar,
    // bukan deretan batang seragam yang justru terlihat seperti kesalahan.
    $lebarAcak = ['w-full', 'w-11/12', 'w-4/5', 'w-10/12'];
@endphp

<div {{ $attributes->merge(['class' => 'animate-pulse']) }} role="status" aria-live="polite">
    <span class="sr-only">Sedang memuat data</span>

    @if ($ragam === 'tabel')
        <div class="divide-y divide-gray-200 dark:divide-gray-800">
            @for ($i = 0; $i < $baris; $i++)
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="h-4 w-1/4 rounded bg-gray-200 dark:bg-white/10"></div>
                    <div class="h-4 w-1/5 rounded bg-gray-200 dark:bg-white/10"></div>
                    <div class="h-4 w-1/6 rounded bg-gray-200 dark:bg-white/10"></div>
                    <div class="ml-auto h-6 w-20 rounded-full bg-gray-200 dark:bg-white/10"></div>
                </div>
            @endfor
        </div>
    @elseif ($ragam === 'kartu')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @for ($i = 0; $i < $baris; $i++)
                <div class="rounded-2xl border border-gray-200 p-5 dark:border-gray-800">
                    <div class="h-3 w-24 rounded bg-gray-200 dark:bg-white/10"></div>
                    <div class="mt-3 h-7 w-20 rounded bg-gray-200 dark:bg-white/10"></div>
                    <div class="mt-2 h-3 w-32 rounded bg-gray-200 dark:bg-white/10"></div>
                </div>
            @endfor
        </div>
    @elseif ($ragam === 'grafik')
        <div class="rounded-2xl border border-gray-200 p-5 dark:border-gray-800">
            <div class="h-4 w-40 rounded bg-gray-200 dark:bg-white/10"></div>
            <div class="mt-6 flex h-48 items-end gap-3">
                {{-- Tinggi batang dibuat beragam agar menyerupai grafik sungguhan --}}
                @foreach ([60, 85, 45, 95, 70, 55, 80] as $tinggi)
                    <div class="flex-1 rounded-t bg-gray-200 dark:bg-white/10" style="height: {{ $tinggi }}%"></div>
                @endforeach
            </div>
        </div>
    @else
        <div class="space-y-3">
            @for ($i = 0; $i < $baris; $i++)
                <div class="h-4 rounded bg-gray-200 {{ $lebarAcak[$i % count($lebarAcak)] }} dark:bg-white/10"></div>
            @endfor
        </div>
    @endif
</div>
