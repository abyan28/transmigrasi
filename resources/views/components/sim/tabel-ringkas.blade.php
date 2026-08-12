{{--
    Tabel ringkas tanpa pencarian, filter, maupun paginasi.

    Berbeda dari `x-sim.data-table` yang dipakai halaman daftar utama, komponen
    ini untuk potongan data pendek di dalam tab halaman detail, misalnya daftar
    transmigran pada satu satuan permukiman.

    Tetap menyediakan tata letak kartu untuk layar sempit lewat slot `kartu`,
    agar tidak ada gulir mendatar di ponsel (agents/ui-spec.md bagian 8). Bila
    slot itu tidak diisi, tabel dibungkus wadah bergulir sebagai jalan tengah
    yang masih diizinkan.

    Pemakaian:
        <x-sim.tabel-ringkas :kolom="['Nama', 'NIK']">
            <tr><td class="px-5 py-3">...</td></tr>
        </x-sim.tabel-ringkas>
--}}
@props([
    'kolom' => [],
])

<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <table class="w-full text-left">
        <thead class="bg-gray-50 dark:bg-white/[0.02]">
            <tr class="border-b border-gray-200 dark:border-gray-800">
                @foreach ($kolom as $judul)
                    <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ $judul }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            {{ $slot }}
        </tbody>
    </table>
</div>
