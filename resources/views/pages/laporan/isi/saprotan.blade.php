{{--
    Isi Laporan Saprotan, dua bagian terpisah (rules.md 9 poin 16, notes 1m.4).
    Di-include oleh halaman berbingkai maupun rute dokumen polos.

    Bagian benih mengikuti kolom "laporan saprotan.jpeg" di refs/. Bagian
    non-benih (pupuk, pestisida, mulsa) hanya menampilkan penyalurannya,
    sebab sarana itu tidak tertaut ke satu penanaman tertentu.
--}}
@php
    $angka = fn ($n, $desimal = 2) => \App\Support\LaporanData::angka($n, $desimal);
    $bulan = function ($ym) {
        if (! $ym) {
            return '-';
        }
        try {
            return \Illuminate\Support\Carbon::parse($ym . '-01')->translatedFormat('F Y');
        } catch (\Throwable $e) {
            return $ym;
        }
    };
@endphp

<h2 class="mb-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Bagian 1. Bantuan benih</h2>
<div class="mb-8 overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
    <table class="tabel-dokumen min-w-full text-theme-sm">
        <caption class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-left text-theme-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
            Penyaluran bantuan benih per kelompok tani penerima
        </caption>
        <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
            <tr>
                <th scope="col" class="px-3 py-2 text-left">No</th>
                <th scope="col" class="px-3 py-2 text-left">Kecamatan</th>
                <th scope="col" class="px-3 py-2 text-left">Desa</th>
                <th scope="col" class="px-3 py-2 text-left">Kelompok Tani</th>
                <th scope="col" class="px-3 py-2 text-left">Nama Ketua</th>
                <th scope="col" class="px-3 py-2 text-left">NIK</th>
                <th scope="col" class="px-3 py-2 text-left">No HP</th>
                <th scope="col" class="px-3 py-2 text-right">Anggota</th>
                <th scope="col" class="px-3 py-2 text-right">Luas Lahan (ha)</th>
                <th scope="col" class="px-3 py-2 text-left">Komoditas</th>
                <th scope="col" class="px-3 py-2 text-left">Varietas Benih</th>
                <th scope="col" class="px-3 py-2 text-right">Volume Benih</th>
                <th scope="col" class="px-3 py-2 text-right">Tahun Pengadaan</th>
                <th scope="col" class="px-3 py-2 text-left">Sumber Dana</th>
                <th scope="col" class="px-3 py-2 text-left">Jadwal Tanam</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($benih as $i => $b)
                <tr class="text-gray-700 dark:text-gray-300">
                    <td class="px-3 py-2">{{ $i + 1 }}</td>
                    <td class="px-3 py-2">{{ $b['kecamatan'] }}</td>
                    <td class="px-3 py-2">{{ $b['desa'] }}</td>
                    <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">{{ $b['poktan'] }}</td>
                    <td class="px-3 py-2">{{ $b['ketua'] }}</td>
                    <td class="px-3 py-2 tabular-nums">{{ $b['nik_ketua'] }}</td>
                    <td class="px-3 py-2 tabular-nums">{{ $b['telepon_ketua'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $b['jumlah_anggota'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['luas_lahan']) }}</td>
                    <td class="px-3 py-2">{{ $b['komoditas'] }}</td>
                    <td class="px-3 py-2">{{ $b['varietas'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $angka($b['volume_benih'], 0) }} {{ $b['satuan'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $b['tahun_pengadaan'] ?? '-' }}</td>
                    <td class="px-3 py-2">{{ $b['sumber_dana'] }}</td>
                    <td class="px-3 py-2">{{ $bulan($b['jadwal_tanam']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        Belum ada penyaluran benih pada data contoh.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="mb-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">
    Bagian 2. Bantuan pupuk, pestisida, dan mulsa
</h2>
<div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
    <table class="tabel-dokumen min-w-full text-theme-sm">
        <caption class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-left text-theme-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
            Penyaluran sarana produksi selain benih, tanpa keterkaitan ke satu penanaman
        </caption>
        <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
            <tr>
                <th scope="col" class="px-3 py-2 text-left">No</th>
                <th scope="col" class="px-3 py-2 text-left">Kelompok Tani</th>
                <th scope="col" class="px-3 py-2 text-left">Satuan Permukiman</th>
                <th scope="col" class="px-3 py-2 text-left">Jenis</th>
                <th scope="col" class="px-3 py-2 text-right">Volume</th>
                <th scope="col" class="px-3 py-2 text-right">Tahun Pengadaan</th>
                <th scope="col" class="px-3 py-2 text-left">Sumber Dana</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($nonBenih as $i => $n)
                <tr class="text-gray-700 dark:text-gray-300">
                    <td class="px-3 py-2">{{ $i + 1 }}</td>
                    <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">{{ $n['poktan'] }}</td>
                    <td class="px-3 py-2">{{ $n['sp'] }}</td>
                    <td class="px-3 py-2">{{ $n['jenis'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $angka($n['volume'], 0) }} {{ $n['satuan'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $n['tahun_pengadaan'] ?? '-' }}</td>
                    <td class="px-3 py-2">{{ $n['sumber_dana'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        Belum ada penyaluran pupuk atau pestisida pada data contoh.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
