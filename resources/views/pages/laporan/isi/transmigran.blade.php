{{--
    Isi Laporan Daftar Transmigran beserta data Rumah dan Lahan. Di-include
    oleh halaman berbingkai maupun rute dokumen polos.

    Tanpa berkas rujukan; kolom disusun dari data yang sudah ada. Tiga bagian
    dalam satu laporan: transmigran, rumah, lahan. Rumah tertaut ke
    transmigran lewat nama penghuni, lahan lewat transmigran_id.
--}}
@php
    $angka = fn ($n, $d = 0) => \App\Support\LaporanData::angka($n, $d);
    $rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $tgl = function ($t) {
        try {
            return \Illuminate\Support\Carbon::parse($t)->translatedFormat('d F Y');
        } catch (\Throwable $e) {
            return $t;
        }
    };
@endphp

<h2 class="mb-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Bagian A. Kepala Keluarga Transmigran</h2>
<div class="mb-8 overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
    <table class="tabel-dokumen min-w-full text-theme-sm">
        <caption class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-left text-theme-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
            Daftar kepala keluarga transmigran di seluruh satuan permukiman
        </caption>
        <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
            <tr>
                <th scope="col" class="px-3 py-2 text-left">No</th>
                <th scope="col" class="px-3 py-2 text-left">NIK</th>
                <th scope="col" class="px-3 py-2 text-left">Nama Kepala Keluarga</th>
                <th scope="col" class="px-3 py-2 text-left">No KK</th>
                <th scope="col" class="px-3 py-2 text-left">Jenis Kelamin</th>
                <th scope="col" class="px-3 py-2 text-left">Tempat, Tanggal Lahir</th>
                <th scope="col" class="px-3 py-2 text-left">Pendidikan</th>
                <th scope="col" class="px-3 py-2 text-left">Pekerjaan</th>
                <th scope="col" class="px-3 py-2 text-right">Anggota Keluarga</th>
                <th scope="col" class="px-3 py-2 text-right">Pendapatan per Bulan</th>
                <th scope="col" class="px-3 py-2 text-left">Daerah Asal</th>
                <th scope="col" class="px-3 py-2 text-right">Tahun Kedatangan</th>
                <th scope="col" class="px-3 py-2 text-left">Satuan Permukiman</th>
                <th scope="col" class="px-3 py-2 text-left">Status Tinggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transmigran as $t)
                <tr data-baris data-sp="{{ $t['satuan_permukiman_id'] }}"
                    data-tahun="{{ $t['tahun_kedatangan'] }}" data-status="{{ $t['status_tinggal'] }}"
                    x-show="cocok($el)" class="text-gray-700 dark:text-gray-300">
                    <td class="px-3 py-2 tabular-nums" data-nomor></td>
                    <td class="px-3 py-2 tabular-nums">{{ $t['nik'] }}</td>
                    <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">{{ $t['nama_kepala_keluarga'] }}</td>
                    <td class="px-3 py-2 tabular-nums">{{ $t['no_kk'] }}</td>
                    <td class="px-3 py-2">{{ $t['jenis_kelamin'] }}</td>
                    <td class="px-3 py-2">{{ $t['tempat_lahir'] }}, {{ $tgl($t['tanggal_lahir']) }}</td>
                    <td class="px-3 py-2">{{ $t['pendidikan_terakhir'] }}</td>
                    <td class="px-3 py-2">{{ $t['pekerjaan_kepala_keluarga'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $t['jumlah_anggota_keluarga'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $rupiah($t['pendapatan_per_bulan']) }}</td>
                    <td class="px-3 py-2">{{ $t['daerah_asal'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $t['tahun_kedatangan'] }}</td>
                    <td class="px-3 py-2">{{ $t['satuan_permukiman'] }}</td>
                    <td class="px-3 py-2">{{ $t['status_tinggal'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        Belum ada data transmigran pada data contoh.
                    </td>
                </tr>
            @endforelse
            @if (count($transmigran) > 0)
                <tr x-show="cacahTampak($el.closest('tbody')) === 0" x-cloak>
                    <td colspan="14" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        Tidak ada kepala keluarga yang cocok dengan filter.
                        <button type="button" @click="bersihkan()"
                            class="ml-1 rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">Bersihkan
                            filter</button>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<h2 class="mb-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Bagian B. Data Rumah</h2>
<div class="mb-8 overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
    <table class="tabel-dokumen min-w-full text-theme-sm">
        <caption class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-left text-theme-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
            Rumah transmigran beserta kondisi dan status huniannya
        </caption>
        <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
            <tr>
                <th scope="col" class="px-3 py-2 text-left">No</th>
                <th scope="col" class="px-3 py-2 text-left">Nomor Rumah</th>
                <th scope="col" class="px-3 py-2 text-left">Satuan Permukiman</th>
                <th scope="col" class="px-3 py-2 text-left">Penghuni</th>
                <th scope="col" class="px-3 py-2 text-left">Kondisi</th>
                <th scope="col" class="px-3 py-2 text-left">Status Hunian</th>
                <th scope="col" class="px-3 py-2 text-right">Tahun Pembangunan</th>
                <th scope="col" class="px-3 py-2 text-right">Luas Bangunan (m2)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rumah as $r)
                <tr data-baris data-sp="{{ $r['satuan_permukiman_id'] }}" x-show="cocok($el)"
                    class="text-gray-700 dark:text-gray-300">
                    <td class="px-3 py-2 tabular-nums" data-nomor></td>
                    <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">{{ $r['no_rumah'] }}</td>
                    <td class="px-3 py-2">{{ $r['satuan_permukiman'] }}</td>
                    <td class="px-3 py-2">{{ $r['penghuni'] ?: '-' }}</td>
                    <td class="px-3 py-2">{{ $r['kondisi'] }}</td>
                    <td class="px-3 py-2">{{ $r['status_hunian'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $r['tahun_pembangunan'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $angka($r['luas_bangunan']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        Belum ada data rumah pada data contoh.
                    </td>
                </tr>
            @endforelse
            @if (count($rumah) > 0)
                <tr x-show="cacahTampak($el.closest('tbody')) === 0" x-cloak>
                    <td colspan="8" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        Tidak ada rumah yang cocok dengan filter.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<h2 class="mb-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Bagian C. Data Lahan</h2>
<div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
    <table class="tabel-dokumen min-w-full text-theme-sm">
        <caption class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-left text-theme-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
            Lahan yang dibagikan kepada transmigran menurut peruntukannya
        </caption>
        <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
            <tr>
                <th scope="col" class="px-3 py-2 text-left">No</th>
                <th scope="col" class="px-3 py-2 text-left">Kode Lahan</th>
                <th scope="col" class="px-3 py-2 text-left">Pemilik</th>
                <th scope="col" class="px-3 py-2 text-left">Satuan Permukiman</th>
                <th scope="col" class="px-3 py-2 text-left">Peruntukan</th>
                <th scope="col" class="px-3 py-2 text-right">Luas (ha)</th>
                <th scope="col" class="px-3 py-2 text-right">Luas Kering (ha)</th>
                <th scope="col" class="px-3 py-2 text-right">Luas Basah (ha)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lahan as $l)
                <tr data-baris data-sp="{{ $l['satuan_permukiman_id'] }}" x-show="cocok($el)"
                    class="text-gray-700 dark:text-gray-300">
                    <td class="px-3 py-2 tabular-nums" data-nomor></td>
                    <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">{{ $l['kode_lahan'] }}</td>
                    <td class="px-3 py-2">{{ $l['pemilik'] }}</td>
                    <td class="px-3 py-2">{{ $l['satuan_permukiman'] }}</td>
                    <td class="px-3 py-2">{{ $l['peruntukan_lahan'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $angka($l['luas'], 2) }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $l['luas_kering'] !== '' && $l['luas_kering'] !== null ? $angka($l['luas_kering'], 2) : '-' }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $l['luas_basah'] !== '' && $l['luas_basah'] !== null ? $angka($l['luas_basah'], 2) : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        Belum ada data lahan pada data contoh.
                    </td>
                </tr>
            @endforelse
            @if (count($lahan) > 0)
                <tr x-show="cacahTampak($el.closest('tbody')) === 0" x-cloak>
                    <td colspan="8" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        Tidak ada lahan yang cocok dengan filter.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
