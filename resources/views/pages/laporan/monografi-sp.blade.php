{{--
    Laporan Monografi SP.

    Berkas rujukan "LAPORAN MONOGRAFI UPT KAPITAN MEO 2025.doc" adalah dokumen
    naratif lima bab (keadaan wilayah, kependudukan, sosial ekonomi, sosial
    budaya) yang memuat data iklim, topografi, sertifikat tanah, KB, dan
    agama. Data itu belum ada di sistem.

    Yang disajikan di sini indikator per satuan permukiman yang memang kita
    punya. Monografi penuh per SP menyusul bersama field Bab II Keadaan
    Wilayah (Rombongan C).
--}}
@extends('layouts.app')

@php
    $angka = fn ($n, $desimal = 2) => rtrim(rtrim(number_format((float) $n, $desimal, ',', '.'), '0'), ',');
@endphp

@section('content')
    <x-sim.kerangka-laporan slug="monografi-sp"
        cakupan="Seluruh satuan permukiman di kawasan transmigrasi Kobalima Timur, satu baris per SP."
        dasar-periode="Potret keadaan terkini tiap SP pada tahun berjalan, bukan rekap lintas tahun."
        sumber-label="Data Satuan Permukiman" :sumber-url="route('sp.index')">
        <x-slot:catatan>
            Monografi lengkap tiap SP (letak astronomis, topografi, iklim,
            sertifikat tanah, kelembagaan desa) menunggu penambahan field
            Keadaan Wilayah pada modul Satuan Permukiman.
        </x-slot:catatan>

        <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
            <table class="min-w-full text-theme-sm">
                <caption class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-left text-theme-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                    Indikator keadaan wilayah, kependudukan, dan pertanian tiap satuan permukiman
                </caption>
                <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-left">No</th>
                        <th scope="col" class="px-3 py-2 text-left">Satuan Permukiman</th>
                        <th scope="col" class="px-3 py-2 text-left">Kecamatan</th>
                        <th scope="col" class="px-3 py-2 text-left">Desa</th>
                        <th scope="col" class="px-3 py-2 text-right">Tahun Penempatan</th>
                        <th scope="col" class="px-3 py-2 text-right">Luas Wilayah (ha)</th>
                        <th scope="col" class="px-3 py-2 text-right">KK Rencana</th>
                        <th scope="col" class="px-3 py-2 text-right">KK Terisi</th>
                        <th scope="col" class="px-3 py-2 text-right">Rumah Terhuni</th>
                        <th scope="col" class="px-3 py-2 text-right">Poktan</th>
                        <th scope="col" class="px-3 py-2 text-right">Lahan Tergarap (ha)</th>
                        <th scope="col" class="px-3 py-2 text-right">Produksi Panen (ton)</th>
                        <th scope="col" class="px-3 py-2 text-right">Pengaduan Terbuka</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($baris as $i => $b)
                        <tr class="text-gray-700 dark:text-gray-300">
                            <td class="px-3 py-2">{{ $i + 1 }}</td>
                            <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">
                                {{ $b['sp'] }}
                                <span class="block text-theme-xs font-normal text-gray-500 dark:text-gray-400">{{ $b['kode'] }}</span>
                            </td>
                            <td class="px-3 py-2">{{ $b['kecamatan'] }}</td>
                            <td class="px-3 py-2">{{ $b['desa'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $b['tahun_penempatan'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['luas_wilayah']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['kk_rencana'], 0) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['kk_terisi'], 0) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['rumah_terhuni'], 0) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $b['poktan'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['lahan_tergarap']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['produksi_ton']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $b['pengaduan_terbuka'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-sim.kerangka-laporan>
@endsection
