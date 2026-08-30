{{--
    Tabel ringkas tanpa pencarian, filter, maupun paginasi.

    Berbeda dari `x-sim.data-table` yang dipakai halaman daftar utama, komponen
    ini untuk potongan data pendek di dalam tab halaman detail, misalnya daftar
    transmigran pada satu satuan permukiman.

    Tetap menyediakan tata letak kartu untuk layar sempit lewat slot `kartu`,
    agar tidak ada gulir mendatar di ponsel (agents/ui-spec.md bagian 8). Bila
    slot itu tidak diisi, tabel dibungkus wadah bergulir sebagai jalan tengah
    yang masih diizinkan.

    Judul kolom yang disebutkan pada `kolomKanan` dirender rata kanan. Dipakai
    untuk kolom aksi, agar tombolnya sejajar dengan tepi tabel dan tidak
    menggantung di tengah ruang kosong.

    Pemakaian:
        <x-sim.tabel-ringkas :kolom="['Nama', 'NIK', 'Aksi']" :kolom-kanan="['Aksi']">
            <tr><td class="px-5 py-3">...</td></tr>
        </x-sim.tabel-ringkas>
--}}
@props([
    'kolom' => [],
    'kolomKanan' => [],

    // Nama tabel bagi pembaca layar, dirender sebagai <caption> tersembunyi.
    'judul' => null,
])

{{--
    `relative` mengurung elemen ber-`position: absolute` di dalam tabel
    (mis. `<caption class="sr-only">` dan `<span class="sr-only">` pada kolom
    aksi). Tanpa itu, ketika tabel lebih lebar dari wadahnya, elemen sr-only
    "kabur" ke blok pengurung `<html>` dan menyeret scrollbar mendatar ke
    seluruh badan halaman — bug yang tercatat 2026-08-30.
--}}
<div {{ $attributes->merge(['class' => 'relative overflow-x-auto']) }}>
    <table class="w-full text-left">
        @if ($judul)
            <caption class="sr-only">{{ $judul }}</caption>
        @endif
        <thead class="bg-gray-50 dark:bg-white/[0.02]">
            <tr class="border-b border-gray-200 dark:border-gray-800">
                @foreach ($kolom as $judul)
                    <th scope="col"
                        class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400 {{ in_array($judul, (array) $kolomKanan, true) ? 'text-right' : '' }}">{{ $judul }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            {{ $slot }}
        </tbody>
    </table>
</div>
