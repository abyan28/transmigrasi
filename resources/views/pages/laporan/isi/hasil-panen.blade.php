{{--
    Isi Laporan Hasil Panen. Di-include oleh halaman berbingkai
    (pages/laporan/hasil-panen) maupun rute dokumen polos
    (pages/laporan/dokumen) supaya keduanya memuat tabel yang sama persis.

    Kolom mengikuti "Lap. Akhir Panen Jagung Polri MT. I 2025" di refs/:
    identitas poktan, luas dan volume benih, lalu realisasi tanam sampai
    produksi. Dikelompokkan per Satuan Permukiman dengan subtotal, ditutup
    total kawasan. Belum Dipanen dihitung: realisasi tanam - realisasi panen
    - puso. Rantai penelusuran hasil_panen -> penanaman.saprotan_id ->
    saprotan.tahun_pengadaan disusun di App\Support\LaporanData::hasilPanen().
--}}
@php
    $angka = fn ($n, $desimal = 2) => \App\Support\LaporanData::angka($n, $desimal);
    $kolomAngka = [
        ['luas_lahan', 'Luas Lahan (ha)'],
        ['volume_benih', 'Volume Benih (kg)'],
        ['realisasi_tanam', 'Realisasi Tanam (ha)'],
        ['belum_ditanam', 'Belum Ditanam (ha)'],
        ['realisasi_panen', 'Realisasi Panen (ha)'],
        ['puso', 'Puso (ha)'],
        ['belum_dipanen', 'Belum Dipanen (ha)'],
        ['produktivitas_tertimbang', 'Produktivitas (ton/ha)'],
        ['produksi_ton', 'Produksi (ton)'],
    ];

    /*
        Ungkapan x-text sel subtotal/total (D3). Produktivitas adalah rasio
        tertimbang -- Sigma produksi / Sigma realisasi panen, BUKAN rata-rata
        produktivitas per baris -- jadi memakai rasioTampak(). Sisanya jumlah
        biasa. $penanda null pada baris total (menjumlah seluruh baris cocok).
    */
    $selHitung = function (string $kunci, ?string $penanda): string {
        $d = in_array($kunci, ['volume_benih'], true) ? 0 : 2;
        $arg4 = $penanda !== null ? ', '.$penanda : '';

        return $kunci === 'produktivitas_tertimbang'
            ? "rasioTampak(\$el.closest('table'), 'produksi_ton', 'realisasi_panen', 2{$arg4})"
            : "jumlahTampak(\$el.closest('table'), '{$kunci}', {$d}{$arg4})";
    };
@endphp

<div class="overflow-x-auto overflow-y-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
    <table class="tabel-dokumen min-w-full text-theme-xs">
        <caption class="border-b border-gray-200 bg-gray-50 px-4 py-2.5 text-left text-theme-xs font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
            Realisasi tanam dan hasil panen per kelompok tani, dikelompokkan menurut satuan permukiman
        </caption>
        <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
            <tr>
                <th scope="col" class="px-2 py-1.5 text-left">No</th>
                <th scope="col" class="px-2 py-1.5 text-left">Kecamatan</th>
                <th scope="col" class="px-2 py-1.5 text-left">Desa</th>
                <th scope="col" class="px-2 py-1.5 text-left">Kelompok Tani</th>
                <th scope="col" class="px-2 py-1.5 text-left">Ketua</th>
                <th scope="col" class="px-2 py-1.5 text-right">Anggota</th>
                <th scope="col" class="px-2 py-1.5 text-left">Komoditas / Varietas</th>
                @foreach ($kolomAngka as [$kunci, $label])
                    <th scope="col" class="px-2 py-1.5 text-right">{{ $label }}</th>
                @endforeach
                <th scope="col" class="px-2 py-1.5 text-left">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($kelompok as $grup)
                <tr x-show="! kosong($el.closest('table'), selSp({{ $grup['sp_id'] }}))"
                    class="bg-gray-50 dark:bg-white/[0.03]">
                    <th scope="colgroup" colspan="{{ 7 + count($kolomAngka) + 1 }}"
                        class="px-2 py-1.5 text-left text-theme-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                        {{ $grup['sp'] }} &middot; Kec. {{ $grup['kecamatan'] }}
                    </th>
                </tr>
                @foreach ($grup['baris'] as $b)
                    <tr data-baris data-sp="{{ $b['sp_id'] }}" data-tahun="{{ $b['tahun_pengadaan'] }}"
                        data-komoditas="{{ $b['komoditas'] }}"
                        data-luas_lahan="{{ $b['luas_lahan'] }}"
                        data-volume_benih="{{ $b['volume_benih'] }}" data-realisasi_tanam="{{ $b['realisasi_tanam'] }}"
                        data-belum_ditanam="{{ $b['belum_ditanam'] }}"
                        data-realisasi_panen="{{ $b['realisasi_panen'] }}" data-puso="{{ $b['puso'] }}"
                        data-belum_dipanen="{{ $b['belum_dipanen'] }}" data-produksi_ton="{{ $b['produksi_ton'] }}"
                        x-show="cocok($el)" class="text-gray-700 dark:text-gray-300">
                        <td class="px-2 py-1.5 tabular-nums" data-nomor></td>
                        <td class="px-2 py-1.5">{{ $b['kecamatan'] }}</td>
                        <td class="px-2 py-1.5">{{ $b['desa'] }}</td>
                        <td class="px-2 py-1.5 font-medium text-gray-800 dark:text-white/90">{{ $b['poktan'] }}</td>
                        <td class="px-2 py-1.5">{{ $b['ketua'] }}</td>
                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $b['jumlah_anggota'] }}</td>
                        <td class="px-2 py-1.5">
                            <span class="font-medium text-gray-800 dark:text-white/90">{{ $b['komoditas'] }}</span>
                            @if ($b['varietas'] && $b['varietas'] !== '-')
                                <span class="block text-[11px] text-gray-500 dark:text-gray-400">{{ $b['varietas'] }}</span>
                            @endif
                        </td>
                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $angka($b['luas_lahan']) }}</td>
                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $angka($b['volume_benih'], 0) }}</td>
                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $angka($b['realisasi_tanam']) }}</td>
                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $angka($b['belum_ditanam']) }}</td>
                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $angka($b['realisasi_panen']) }}</td>
                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $angka($b['puso']) }}</td>
                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $angka($b['belum_dipanen']) }}</td>
                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $angka($b['produktivitas']) }}</td>
                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $angka($b['produksi_ton']) }}</td>
                        <td class="px-2 py-1.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $b['keterangan'] ?? '-' }}</td>
                    </tr>
                @endforeach
                <tr x-show="! kosong($el.closest('table'), selSp({{ $grup['sp_id'] }}))"
                    class="bg-gray-50 font-medium text-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                    <td class="px-2 py-1.5" colspan="7">Subtotal {{ $grup['sp'] }}</td>
                    @foreach ($kolomAngka as [$kunci, $label])
                        <td class="px-2 py-1.5 text-right tabular-nums"
                            x-text="{!! $selHitung($kunci, 'selSp('.$grup['sp_id'].')') !!}">{{ $angka($grup['subtotal'][$kunci], $kunci === 'volume_benih' ? 0 : 2) }}</td>
                    @endforeach
                    <td class="px-2 py-1.5"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 7 + count($kolomAngka) + 1 }}" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        Belum ada catatan hasil panen pada data contoh.
                    </td>
                </tr>
            @endforelse
            @if (! empty($kelompok))
                <tr x-show="kosong($el.closest('table'))" x-cloak>
                    <td colspan="{{ 7 + count($kolomAngka) + 1 }}" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        Tidak ada catatan panen yang cocok dengan filter.
                        <button type="button" @click="bersihkan()"
                            class="ml-1 rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">Bersihkan
                            filter</button>
                    </td>
                </tr>
            @endif
        </tbody>
        @if (! empty($kelompok))
            <tfoot>
                <tr class="motif-baris-total bg-gray-100 text-gray-900 dark:bg-white/[0.06] dark:text-white">
                    <td class="px-2 py-2" colspan="7">
                        Total Kawasan Kobalima Timur
                        <span x-show="adaFilter" x-cloak class="font-normal text-gray-600 dark:text-gray-300"
                            x-text="'(' + kalimatCakupan + ')'"></span>
                    </td>
                    @foreach ($kolomAngka as [$kunci, $label])
                        <td class="px-2 py-2 text-right tabular-nums"
                            x-text="{!! $selHitung($kunci, null) !!}">{{ $angka($total[$kunci], $kunci === 'volume_benih' ? 0 : 2) }}</td>
                    @endforeach
                    <td class="px-2 py-2"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
