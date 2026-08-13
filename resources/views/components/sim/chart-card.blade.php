{{--
    Kartu pembungkus satu visualisasi dashboard.

    Setiap grafik wajib menyediakan tabel data alternatif demi aksesibilitas
    (agents/ui-spec.md bagian 9 poin 7), sehingga komponen ini menyediakan
    pengalih "Lihat tabel data" yang menampilkan slot `tabel`. Tanpa itu,
    isi grafik tidak terbaca sama sekali oleh pembaca layar.

    Wadah grafik dirender kosong dan diisi dari skrip halaman, sehingga
    keadaan kosong bawaan ApexCharts ("Data belum tersedia") ikut terpakai
    bila datanya nihil (bagian 9 poin 5).

    Pemakaian:
        <x-sim.chart-card id="grafikKk" judul="Jumlah KK per Tahun"
            keterangan="Sumber: pendataan kawasan" tinggi="320">
            <x-slot:tabel> ... </x-slot:tabel>
        </x-sim.chart-card>
--}}
@props([
    'id',
    'judul',
    'keterangan' => null,
    'tinggi' => 320,
    'lebar' => null,
])

<div {{ $attributes->merge([
    'class' => 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]',
]) }}>
    <div x-data="{ tabelTerbuka: false }">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $judul }}</h3>
                @if ($keterangan)
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $keterangan }}</p>
                @endif
            </div>

            @isset($tabel)
                <button type="button" @click="tabelTerbuka = !tabelTerbuka"
                    :aria-expanded="tabelTerbuka" aria-controls="tabel-{{ $id }}"
                    class="shrink-0 rounded-lg border border-gray-300 px-2.5 py-1.5 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <span x-text="tabelTerbuka ? 'Lihat grafik' : 'Lihat tabel data'"></span>
                </button>
            @endisset
        </div>

        {{--
            Wadah grafik, diisi oleh skrip halaman.

            Pembatas lebar WAJIB ada. ApexCharts menghitung lebar kanvasnya satu
            kali saat digambar, dari lebar elemen ini. Pada tab yang dibuka di
            latar belakang, peramban belum melakukan layout sehingga lebarnya
            terbaca nol, lalu ApexCharts jatuh ke lebar bawaannya yang jauh
            lebih besar.

            Tanpa pembatas, kanvas berlebih itu mendorong kartunya ikut melebar
            dan merusak tata letak seluruh halaman. Dengan pembatas, kartunya
            tetap utuh sekalipun grafiknya belum sempat menyesuaikan diri.
        --}}
        <div x-show="!tabelTerbuka" id="{{ $id }}" style="min-height: {{ $tinggi }}px"
            class="w-full max-w-full overflow-hidden {{ $lebar ?? '' }}"></div>

        {{-- Tabel alternatif yang setara isinya dengan grafik --}}
        @isset($tabel)
            <div x-show="tabelTerbuka" x-cloak id="tabel-{{ $id }}" class="overflow-x-auto">
                {{ $tabel }}
            </div>
        @endisset

        {{ $slot }}
    </div>
</div>
