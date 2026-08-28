{{--
    Laporan Hasil Panen.

    Kolom mengikuti "Lap. Akhir Panen Jagung Polri MT. I 2025" di refs/:
    identitas poktan, luas dan volume benih, lalu realisasi tanam sampai
    produksi. Dikelompokkan per Satuan Permukiman dengan subtotal, ditutup
    total kawasan.

    Dasar periode: TAHUN PENGADAAN BANTUAN (tahun anggaran), bukan tahun
    panen (rules.md 9 poin 16). Rantai penelusuran hasil_panen ->
    penanaman.saprotan_id -> saprotan.tahun_pengadaan disusun di
    App\Support\LaporanData::hasilPanen().

    Belum Dipanen dihitung: realisasi tanam - realisasi panen - puso.
--}}
@extends('layouts.app')

@php
    $angka = fn ($n, $desimal = 2) => \App\Support\LaporanData::angka($n, $desimal);
    $kolomAngka = [
        ['volume_benih', 'Volume Benih (kg)'],
        ['realisasi_tanam', 'Realisasi Tanam (ha)'],
        ['realisasi_panen', 'Realisasi Panen (ha)'],
        ['puso', 'Puso (ha)'],
        ['belum_dipanen', 'Belum Dipanen (ha)'],
        ['produktivitas_tertimbang', 'Produktivitas (ton/ha)'],
        ['produksi_ton', 'Produksi (ton)'],
    ];
@endphp

@section('content')
    <x-sim.kerangka-laporan slug="hasil-panen"
        cakupan="Seluruh satuan permukiman di kawasan transmigrasi Kobalima Timur."
        dasar-periode="Dikelompokkan menurut tahun pengadaan bantuan (tahun anggaran), bukan tahun panen."
        sumber-label="Data Hasil Panen" :sumber-url="route('panen.index')">
        <x-slot:catatan>
            Bagian benih menampilkan rantai penuh dari bantuan sampai hasil
            panennya. Bantuan pupuk tidak tertaut ke satu penanaman tertentu,
            sehingga hanya tampil pada Laporan Saprotan.
        </x-slot:catatan>

        <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
            <table class="min-w-full text-theme-sm">
                <caption class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-left text-theme-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                    Realisasi tanam dan hasil panen per kelompok tani, dikelompokkan menurut satuan permukiman
                </caption>
                <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-left">No</th>
                        <th scope="col" class="px-3 py-2 text-left">Kecamatan</th>
                        <th scope="col" class="px-3 py-2 text-left">Desa</th>
                        <th scope="col" class="px-3 py-2 text-left">Kelompok Tani</th>
                        <th scope="col" class="px-3 py-2 text-left">Ketua</th>
                        <th scope="col" class="px-3 py-2 text-right">Anggota</th>
                        <th scope="col" class="px-3 py-2 text-left">Komoditas</th>
                        <th scope="col" class="px-3 py-2 text-left">Varietas</th>
                        <th scope="col" class="px-3 py-2 text-right">Tahun Pengadaan</th>
                        @foreach ($kolomAngka as [$kunci, $label])
                            <th scope="col" class="px-3 py-2 text-right">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @php $nomor = 0; @endphp
                    @forelse ($kelompok as $grup)
                        <tr class="bg-gray-50 dark:bg-white/[0.03]">
                            <th scope="colgroup" colspan="{{ 9 + count($kolomAngka) }}"
                                class="px-3 py-2 text-left text-theme-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                {{ $grup['sp'] }} &middot; Kec. {{ $grup['kecamatan'] }}
                            </th>
                        </tr>
                        @foreach ($grup['baris'] as $b)
                            <tr class="text-gray-700 dark:text-gray-300">
                                <td class="px-3 py-2">{{ ++$nomor }}</td>
                                <td class="px-3 py-2">{{ $b['kecamatan'] }}</td>
                                <td class="px-3 py-2">{{ $b['desa'] }}</td>
                                <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">{{ $b['poktan'] }}</td>
                                <td class="px-3 py-2">{{ $b['ketua'] }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $b['jumlah_anggota'] }}</td>
                                <td class="px-3 py-2">{{ $b['komoditas'] }}</td>
                                <td class="px-3 py-2">{{ $b['varietas'] }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $b['tahun_pengadaan'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['volume_benih'], 0) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['realisasi_tanam']) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['realisasi_panen']) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['puso']) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['belum_dipanen']) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['produktivitas']) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['produksi_ton']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-medium text-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                            <td class="px-3 py-2" colspan="9">Subtotal {{ $grup['sp'] }}</td>
                            @foreach ($kolomAngka as [$kunci, $label])
                                <td class="px-3 py-2 text-right tabular-nums">{{ $angka($grup['subtotal'][$kunci], $kunci === 'volume_benih' ? 0 : 2) }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 9 + count($kolomAngka) }}" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                Belum ada catatan hasil panen pada data contoh.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if (! empty($kelompok))
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 bg-gray-100 font-semibold text-gray-900 dark:border-gray-700 dark:bg-white/[0.06] dark:text-white">
                            <td class="px-3 py-2.5" colspan="9">Total Kawasan Kobalima Timur</td>
                            @foreach ($kolomAngka as [$kunci, $label])
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ $angka($total[$kunci], $kunci === 'volume_benih' ? 0 : 2) }}</td>
                            @endforeach
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-sim.kerangka-laporan>
@endsection
