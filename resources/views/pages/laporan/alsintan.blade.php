{{--
    Laporan Alsintan.

    Kolom mengikuti berkas "laporan alsintan.jpeg" di refs/: Jenis Alat,
    Sumber Dana, Tahun Pengadaan, Poktan Penerima, Ketua Poktan, Alamat
    (Kecamatan dan Desa), Jumlah unit. Dikelompokkan per Satuan Permukiman
    dengan subtotal jumlah unit, ditutup total kawasan.

    Laporan terpisah dari Laporan Saprotan, mengikuti dua berkas rujukan.

    CATATAN: field sistem bernama `sumber_perolehan` dan `tahun_perolehan`;
    label kolom di sini mengikuti berkas rujukan. Penyeragaman nama field
    alsintan ke `sumber_dana` / `tahun_pengadaan` (seperti saprotan) menjadi
    usul revisi tersendiri.
--}}
@extends('layouts.app')

@php
    $angka = fn ($n) => number_format((float) $n, 0, ',', '.');
@endphp

@section('content')
    <x-sim.kerangka-laporan slug="alsintan"
        cakupan="Alat dan mesin pertanian milik seluruh kelompok tani di kawasan Kobalima Timur."
        dasar-periode="Dikelompokkan menurut tahun pengadaan bantuan (tahun anggaran)."
        sumber-label="Data Alsintan" :sumber-url="route('alsintan.index')">

        <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
            <table class="min-w-full text-theme-sm">
                <caption class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-left text-theme-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                    Alat dan mesin pertanian per kelompok tani, dikelompokkan menurut satuan permukiman
                </caption>
                <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-left">No</th>
                        <th scope="col" class="px-3 py-2 text-left">Jenis Alat</th>
                        <th scope="col" class="px-3 py-2 text-left">Sumber Dana</th>
                        <th scope="col" class="px-3 py-2 text-right">Tahun Pengadaan</th>
                        <th scope="col" class="px-3 py-2 text-left">Poktan Penerima</th>
                        <th scope="col" class="px-3 py-2 text-left">Ketua Poktan</th>
                        <th scope="col" class="px-3 py-2 text-left">Kecamatan</th>
                        <th scope="col" class="px-3 py-2 text-left">Desa</th>
                        <th scope="col" class="px-3 py-2 text-right">Jumlah (Unit)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @php $nomor = 0; @endphp
                    @forelse ($kelompok as $grup)
                        <tr class="bg-gray-50 dark:bg-white/[0.03]">
                            <th scope="colgroup" colspan="9"
                                class="px-3 py-2 text-left text-theme-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                {{ $grup['sp'] }} &middot; Kec. {{ $grup['kecamatan'] }}
                            </th>
                        </tr>
                        @foreach ($grup['baris'] as $b)
                            <tr class="text-gray-700 dark:text-gray-300">
                                <td class="px-3 py-2">{{ ++$nomor }}</td>
                                <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">{{ $b['jenis_alat'] }}</td>
                                <td class="px-3 py-2">{{ $b['sumber_dana'] }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $b['tahun_pengadaan'] }}</td>
                                <td class="px-3 py-2">{{ $b['poktan'] }}</td>
                                <td class="px-3 py-2">{{ $b['ketua'] }}</td>
                                <td class="px-3 py-2">{{ $b['kecamatan'] }}</td>
                                <td class="px-3 py-2">{{ $b['desa'] }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['jumlah']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-medium text-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                            <td class="px-3 py-2" colspan="8">Subtotal {{ $grup['sp'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $angka($grup['subtotal']['jumlah']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                Belum ada data alsintan pada data contoh.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if (! empty($kelompok))
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 bg-gray-100 font-semibold text-gray-900 dark:border-gray-700 dark:bg-white/[0.06] dark:text-white">
                            <td class="px-3 py-2.5" colspan="8">Total Kawasan Kobalima Timur</td>
                            <td class="px-3 py-2.5 text-right tabular-nums">{{ $angka($total['jumlah']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-sim.kerangka-laporan>
@endsection
