{{--
    Laporan Monografi SP.

    Berkas rujukan "LAPORAN MONOGRAFI UPT KAPITAN MEO 2025.doc" adalah dokumen
    naratif lima bab. Yang disajikan di sini Bab II "Keadaan Wilayah" tiap
    satuan permukiman (letak, batas, luas dan bentuk, tanah, topografi, iklim,
    sumberdaya air, aksesibilitas), didahului satu tabel ikhtisar indikator
    kependudukan dan pertanian per SP.

    Bab kependudukan, sosial ekonomi, dan sosial budaya menyusul begitu modul
    terkait menyimpan datanya.
--}}
@extends('layouts.app')

@php
    $angka = fn ($n, $desimal = 2) => \App\Support\LaporanData::angka($n, $desimal);
    $isi = fn ($v) => $v !== null && trim((string) $v) !== '' ? $v : 'belum dicatat';
@endphp

@section('content')
    <x-sim.kerangka-laporan slug="monografi-sp"
        cakupan="Seluruh satuan permukiman di kawasan transmigrasi Kobalima Timur."
        dasar-periode="Potret keadaan terkini tiap SP pada tahun berjalan, bukan rekap lintas tahun."
        sumber-label="Data Satuan Permukiman" :sumber-url="route('sp.index')">
        <x-slot:catatan>
            Bab II "Keadaan Wilayah" diisi lewat modul Satuan Permukiman. Bagian
            yang belum diisi tetap tampil dengan penanda "belum dicatat".
        </x-slot:catatan>

        <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
            <table class="min-w-full text-theme-sm">
                <caption class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-left text-theme-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                    Ikhtisar indikator kependudukan dan pertanian tiap satuan permukiman
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

        <div class="mt-10 space-y-8">
            <h2 class="text-theme-lg font-semibold text-gray-800 dark:text-white/90">Bab II. Keadaan Wilayah</h2>

            @foreach ($monografi as $m)
                <section class="rounded-2xl border border-gray-200 p-5 dark:border-gray-800 sm:p-6">
                    <header class="border-b border-gray-200 pb-3 dark:border-gray-800">
                        <h3 class="text-theme-md font-semibold text-gray-800 dark:text-white/90">{{ $m['sp'] }} <span class="font-normal text-gray-500 dark:text-gray-400">({{ $m['kode'] }})</span></h3>
                        <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                            Desa {{ $m['desa'] }}, Kecamatan {{ $m['kecamatan'] }}, Kabupaten {{ $m['kabupaten'] }}, Provinsi {{ $m['provinsi'] }}. Ditempatkan sejak {{ $m['tahun_penempatan'] }}.
                        </p>
                    </header>

                    @unless ($m['ada_isi'])
                        <p class="mt-4 rounded-lg bg-gray-50 px-4 py-6 text-center text-theme-sm text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                            Keadaan wilayah SP ini belum dicatat pada modul Satuan Permukiman.
                        </p>
                    @endunless

                    <div class="mt-4 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                        @foreach ($m['kelompok'] as $judul => $daftar)
                            <div>
                                <h4 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ $judul === 'Batas Wilayah' ? 'Batas-Batas Alam' : $judul }}
                                </h4>
                                <dl class="mt-2 space-y-2 text-theme-sm">
                                    @foreach ($daftar as $label => $nilai)
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                            <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $isi($nilai) }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-800">
                        <h4 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Aksesibilitas / Pencapaian Lokasi</h4>
                        @if (count($m['rute']) === 0)
                            <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">Rute pencapaian belum dicatat.</p>
                        @else
                            <div class="mt-3 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                                <table class="min-w-full text-theme-sm">
                                    <caption class="px-4 py-2.5 text-left text-theme-xs text-gray-500 dark:text-gray-400">
                                        Cara pencapaian menuju {{ $m['sp'] }}
                                    </caption>
                                    <thead class="border-y border-gray-200 bg-gray-50 text-theme-xs text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-4 py-2 text-left">Rute</th>
                                            <th scope="col" class="px-4 py-2 text-right">Jarak</th>
                                            <th scope="col" class="px-4 py-2 text-left">Sarana Angkutan</th>
                                            <th scope="col" class="px-4 py-2 text-left">Kondisi Jalan</th>
                                            <th scope="col" class="px-4 py-2 text-left">Waktu Tempuh</th>
                                            <th scope="col" class="px-4 py-2 text-right">Ongkos</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @foreach ($m['rute'] as $r)
                                            <tr class="text-gray-700 dark:text-gray-300">
                                                <td class="px-4 py-2 font-medium text-gray-800 dark:text-white/90">
                                                    {{ $r['rute'] }}
                                                    @if (! empty($r['keterangan']))
                                                        <span class="mt-0.5 block text-theme-xs font-normal text-gray-500 dark:text-gray-400">{{ $r['keterangan'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 text-right tabular-nums">{{ $r['jarak_km'] !== null ? $angka($r['jarak_km'], 1) . ' km' : '-' }}</td>
                                                <td class="px-4 py-2">{{ $r['sarana_angkutan'] }}</td>
                                                <td class="px-4 py-2">{{ $r['kondisi_jalan'] }}</td>
                                                <td class="px-4 py-2">{{ $r['waktu_tempuh'] }}</td>
                                                <td class="px-4 py-2 text-right tabular-nums">{{ $r['ongkos_rp'] !== null ? 'Rp ' . number_format((float) $r['ongkos_rp'], 0, ',', '.') : '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </x-sim.kerangka-laporan>
@endsection
