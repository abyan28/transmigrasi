{{--
    Laporan Daftar Poktan.

    Kolom mengikuti "Poktan Wilayah Transmigrasi.xlsx" di refs/: tiap
    kelompok tani memuat baris anggota beserta NIK, nomor HP, luas lahan
    (sawah/basah dan kering), dan titik koordinat, ditutup subtotal luas
    per poktan.

    Potret keadaan terkini kelembagaan tani, bukan rekap lintas tahun.
--}}
@extends('layouts.app')

@php
    $angka = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
@endphp

@section('content')
    <x-sim.kerangka-laporan slug="poktan"
        cakupan="Seluruh kelompok tani beserta anggotanya di kawasan transmigrasi Kobalima Timur."
        dasar-periode="Potret keadaan terkini kelembagaan tani, bukan rekap lintas tahun."
        sumber-label="Data Kelompok Tani" :sumber-url="route('poktan.index')">

        @forelse ($poktan as $p)
            <div class="mb-6 overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
                <table class="min-w-full text-theme-sm">
                    <caption class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-left dark:border-gray-800 dark:bg-white/[0.03]">
                        <span class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $p['nama'] }}</span>
                        <span class="mt-0.5 block text-theme-xs font-normal text-gray-500 dark:text-gray-400">
                            {{ $p['sp'] }} &middot; Desa {{ $p['desa'] }}, Kec. {{ $p['kecamatan'] }}, Kab. Malaka &middot; Ketua: {{ $p['ketua'] }}
                        </span>
                    </caption>
                    <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                        <tr>
                            <th scope="col" rowspan="2" class="px-3 py-2 text-left align-bottom">No</th>
                            <th scope="col" rowspan="2" class="px-3 py-2 text-left align-bottom">Nama Petani</th>
                            <th scope="col" rowspan="2" class="px-3 py-2 text-left align-bottom">NIK</th>
                            <th scope="col" rowspan="2" class="px-3 py-2 text-left align-bottom">No HP</th>
                            <th scope="col" colspan="2" class="px-3 py-2 text-center">Luas Lahan (ha)</th>
                            <th scope="col" colspan="2" class="px-3 py-2 text-center">Titik Koordinat</th>
                            <th scope="col" rowspan="2" class="px-3 py-2 text-left align-bottom">Keterangan</th>
                        </tr>
                        <tr>
                            <th scope="col" class="px-3 py-2 text-right">Sawah (Basah)</th>
                            <th scope="col" class="px-3 py-2 text-right">Kering</th>
                            <th scope="col" class="px-3 py-2 text-right">Lintang</th>
                            <th scope="col" class="px-3 py-2 text-right">Bujur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($p['anggota'] as $j => $a)
                            <tr class="text-gray-700 dark:text-gray-300">
                                <td class="px-3 py-2">{{ $j + 1 }}</td>
                                <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">{{ $a['nama'] }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $a['nik'] }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $a['telepon'] }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($a['luas_basah']) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($a['luas_kering']) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $a['lintang'] }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $a['bujur'] }}</td>
                                <td class="px-3 py-2">{{ $a['status'] === 'Aktif' ? '' : $a['status'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada anggota tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (! empty($p['anggota']))
                        <tfoot>
                            <tr class="bg-gray-50 font-semibold text-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                                <td class="px-3 py-2" colspan="4">Jumlah</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($p['jumlah_basah']) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($p['jumlah_kering']) }}</td>
                                <td class="px-3 py-2" colspan="3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-200 p-6 text-center text-gray-500 dark:border-gray-800 dark:text-gray-400">
                Belum ada kelompok tani pada data contoh.
            </div>
        @endforelse
    </x-sim.kerangka-laporan>
@endsection
