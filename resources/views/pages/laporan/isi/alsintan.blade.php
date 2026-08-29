{{--
    Isi Laporan Alsintan. Di-include oleh halaman berbingkai maupun rute
    dokumen polos.

    Kolom mengikuti berkas "laporan alsintan.jpeg" di refs/: Jenis Alat,
    Sumber Dana, Tahun Pengadaan, Poktan Penerima, Ketua Poktan, Alamat
    (Kecamatan dan Desa), Jumlah unit. Dikelompokkan per Satuan Permukiman
    dengan subtotal jumlah unit, ditutup total kawasan.
--}}
@php
    $angka = fn ($n, $desimal = 0) => \App\Support\LaporanData::angka($n, $desimal);
@endphp

<div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
    <table class="tabel-dokumen min-w-full text-theme-sm">
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
        <tbody>
            @forelse ($kelompok as $grup)
                {{-- Baris grup dan subtotalnya ikut hilang bila seluruh barisnya tersaring. --}}
                <tr x-show="! kosong($el.closest('table'), selSp({{ $grup['sp_id'] }}))"
                    class="bg-gray-50 dark:bg-white/[0.03]">
                    <th scope="colgroup" colspan="9"
                        class="px-3 py-2 text-left text-theme-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                        {{ $grup['sp'] }} &middot; Kec. {{ $grup['kecamatan'] }}
                    </th>
                </tr>
                @foreach ($grup['baris'] as $b)
                    <tr data-baris data-sp="{{ $b['sp_id'] }}" data-tahun="{{ $b['tahun_pengadaan'] }}"
                        data-jenis="{{ $b['jenis_alat'] }}" data-jumlah="{{ $b['jumlah'] }}"
                        x-show="cocok($el)" class="text-gray-700 dark:text-gray-300">
                        <td class="px-3 py-2 tabular-nums" data-nomor></td>
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
                <tr x-show="! kosong($el.closest('table'), selSp({{ $grup['sp_id'] }}))"
                    class="bg-gray-50 font-medium text-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                    <td class="px-3 py-2" colspan="8">Subtotal {{ $grup['sp'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums"
                        x-text="jumlahTampak($el.closest('table'), 'jumlah', 0, selSp({{ $grup['sp_id'] }}))">{{ $angka($grup['subtotal']['jumlah']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        Belum ada data alsintan pada data contoh.
                    </td>
                </tr>
            @endforelse
            @if (! empty($kelompok))
                <tr x-show="kosong($el.closest('table'))" x-cloak>
                    <td colspan="9" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        Tidak ada alsintan yang cocok dengan filter.
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
                    <td class="px-3 py-2.5" colspan="8">
                        Total Kawasan Kobalima Timur
                        <span x-show="adaFilter" x-cloak class="font-normal text-gray-600 dark:text-gray-300"
                            x-text="'(' + kalimatCakupan + ')'"></span>
                    </td>
                    <td class="px-3 py-2.5 text-right tabular-nums"
                        x-text="jumlahTampak($el.closest('table'), 'jumlah', 0)">{{ $angka($total['jumlah']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
