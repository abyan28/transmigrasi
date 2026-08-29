{{--
    Isi Rekap Indikator Kawasan. Di-include oleh halaman berbingkai maupun
    rute dokumen polos.

    Tanpa berkas rujukan; ikhtisar satu halaman dari angka yang menopang
    dashboard (rules.md 12 poin 11). Indikator produksi memakai tahun panen
    berjalan, beda dari Laporan Hasil Panen yang memakai tahun pengadaan
    bantuan (rules.md 9 poin 16; basis tahun dipisah menurut tujuannya).
--}}
@php
    $angka = fn ($n, $desimal = 0) => \App\Support\LaporanData::angka($n, $desimal);
    $rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $persen = fn ($a, $b) => $b > 0 ? number_format($a / $b * 100, 1, ',', '.') . '%' : '-';

    $r = $ringkasan;
    $blok = [
        'Kependudukan' => [
            ['Kepala keluarga', $angka($r['jumlah_kk'])],
            ['Jiwa', $angka($r['jumlah_jiwa'])],
            ['Petani', $angka($r['jumlah_petani'])],
            ['Rumah terhuni', $angka($r['rumah_terhuni']) . ' dari ' . $angka($r['rumah_total'])],
            ['Tingkat hunian', $persen($r['rumah_terhuni'], $r['rumah_total'])],
        ],
        'Lahan dan Produksi (tahun panen berjalan)' => [
            ['Luas lahan tercatat', $angka($r['luas_lahan_total'], 2) . ' ha'],
            ['Realisasi tanam', $angka($r['realisasi_tanam_ha'], 2) . ' ha'],
            ['Realisasi panen', $angka($r['hasil_panen_ha'], 2) . ' ha'],
            ['Puso', $angka($r['puso_ha'], 2) . ' ha'],
            ['Belum dipanen', $angka($r['belum_dipanen_ha'], 2) . ' ha'],
            ['Produktivitas rata-rata', $angka($r['produktivitas_ton_ha'], 2) . ' ton/ha'],
            ['Volume panen', $angka($r['volume_panen_ton'], 2) . ' ton'],
            ['Harga jual rata-rata', $rupiah($r['harga_rata_rata'])],
        ],
        'Kelembagaan Tani' => [
            ['Kelompok tani', $angka($r['poktan'])],
            ['Catatan alsintan', $angka($r['alsintan'])],
            ['Catatan saprotan', $angka($r['saprotan'])],
        ],
        'Pengaduan Warga' => [
            ['Pengaduan terbuka', $angka($r['pengaduan_terbuka'])],
        ],
    ];
@endphp

<section class="rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-white/[0.03]">
    <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Identitas Kawasan</h2>
    <dl class="mt-3 grid gap-x-6 gap-y-2 text-theme-sm sm:grid-cols-2 lg:grid-cols-3">
        <div><dt class="text-gray-500 dark:text-gray-400">Nama kawasan</dt>
            <dd class="font-medium text-gray-800 dark:text-white/90">{{ $kawasan['nama'] ?? '-' }}</dd></div>
        <div><dt class="text-gray-500 dark:text-gray-400">Kabupaten</dt>
            <dd class="font-medium text-gray-800 dark:text-white/90">{{ $kawasan['kabupaten'] ?? '-' }}</dd></div>
        <div><dt class="text-gray-500 dark:text-gray-400">Provinsi</dt>
            <dd class="font-medium text-gray-800 dark:text-white/90">{{ $kawasan['provinsi'] ?? '-' }}</dd></div>
        <div><dt class="text-gray-500 dark:text-gray-400">Nomor SK penetapan</dt>
            <dd class="font-medium text-gray-800 dark:text-white/90">{{ $kawasan['nomor_sk'] ?? '-' }}</dd></div>
        <div><dt class="text-gray-500 dark:text-gray-400">Tahun penetapan</dt>
            <dd class="font-medium text-gray-800 dark:text-white/90">{{ $kawasan['tahun_penetapan'] ?? '-' }}</dd></div>
        <div><dt class="text-gray-500 dark:text-gray-400">Luas total</dt>
            <dd class="font-medium text-gray-800 dark:text-white/90">{{ $angka($kawasan['luas_total'] ?? 0, 2) }} ha</dd></div>
    </dl>
</section>

{{--
    Blok ringkasan tingkat kawasan. Angkanya dari dashboard (rules.md 12 poin
    10) dan TIDAK ikut menyempit saat satu SP dipilih -- dashboard menyimpan
    agregat kawasan, bukan rincian per SP. Yang menyempit adalah tabel "Rincian
    per Satuan Permukiman" di bawah, yang untuk kelima indikatornya berjumlah
    persis sama dengan blok ini (dijaga uji).
--}}
<p x-show="adaFilter" x-cloak
    class="rounded-lg border border-yellow-300 bg-yellow-50 p-3 text-theme-xs text-yellow-800 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-200"
    role="note">
    Blok ringkasan di bawah tetap menampilkan angka tingkat kawasan. Rincian
    yang menyempit mengikuti filter ada pada tabel per satuan permukiman.
</p>

<div class="grid gap-4 sm:grid-cols-2">
    @foreach ($blok as $judulBlok => $isi)
        <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
            <table class="tabel-dokumen min-w-full text-theme-sm">
                <caption class="border-b border-gray-200 bg-gray-50 px-4 py-2.5 text-left text-theme-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                    {{ $judulBlok }}
                </caption>
                <tbody>
                    @foreach ($isi as [$label, $nilai])
                        <tr class="text-gray-700 dark:text-gray-300">
                            <th scope="row" class="px-4 py-2 text-left font-normal text-gray-500 dark:text-gray-400">{{ $label }}</th>
                            <td class="px-4 py-2 text-right font-medium tabular-nums text-gray-800 dark:text-white/90">{{ $nilai }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>

<div>
    <h2 class="mb-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Rincian per Satuan Permukiman</h2>
    <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
        <table class="tabel-dokumen min-w-full text-theme-sm">
            <caption class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-left text-theme-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                Indikator utama tiap satuan permukiman
            </caption>
            <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-3 py-2 text-left">Satuan Permukiman</th>
                    <th scope="col" class="px-3 py-2 text-right">Kepala Keluarga</th>
                    <th scope="col" class="px-3 py-2 text-right">Rumah Terhuni</th>
                    <th scope="col" class="px-3 py-2 text-right">Luas Lahan (ha)</th>
                    <th scope="col" class="px-3 py-2 text-right">Volume Panen (ton)</th>
                    <th scope="col" class="px-3 py-2 text-right">Pengaduan Terbuka</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($perSp as $s)
                    <tr data-baris data-sp="{{ $s['satuan_permukiman_id'] }}"
                        data-jumlah_kk="{{ $s['jumlah_kk'] }}" data-rumah_terhuni="{{ $s['rumah_terhuni'] }}"
                        data-luas_lahan="{{ $s['luas_lahan'] }}" data-volume_panen="{{ $s['volume_panen'] }}"
                        data-pengaduan_terbuka="{{ $s['pengaduan_terbuka'] }}"
                        x-show="cocok($el)" class="text-gray-700 dark:text-gray-300">
                        <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">{{ $s['satuan_permukiman'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $angka($s['jumlah_kk']) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $angka($s['rumah_terhuni']) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $angka($s['luas_lahan'], 2) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $angka($s['volume_panen'], 2) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $s['pengaduan_terbuka'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                            Belum ada data satuan permukiman pada data contoh.
                        </td>
                    </tr>
                @endforelse
                @if (count($perSp) > 0)
                    <tr x-show="kosong($el.closest('tbody'))" x-cloak>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada satuan permukiman yang cocok dengan filter.
                        </td>
                    </tr>
                @endif
            </tbody>
            @if (count($perSp) > 0)
                <tfoot>
                    <tr class="motif-baris-total bg-gray-100 text-gray-900 dark:bg-white/[0.06] dark:text-white">
                        <td class="px-3 py-2.5 font-medium">
                            Jumlah
                            <span x-show="adaFilter" x-cloak class="font-normal text-gray-600 dark:text-gray-300"
                                x-text="'(' + kalimatCakupan + ')'"></span>
                        </td>
                        <td class="px-3 py-2.5 text-right tabular-nums"
                            x-text="jumlahTampak($el.closest('table'), 'jumlah_kk', 0)">{{ $angka(array_sum(array_column($perSp, 'jumlah_kk'))) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums"
                            x-text="jumlahTampak($el.closest('table'), 'rumah_terhuni', 0)">{{ $angka(array_sum(array_column($perSp, 'rumah_terhuni'))) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums"
                            x-text="jumlahTampak($el.closest('table'), 'luas_lahan', 2)">{{ $angka(array_sum(array_column($perSp, 'luas_lahan')), 2) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums"
                            x-text="jumlahTampak($el.closest('table'), 'volume_panen', 2)">{{ $angka(array_sum(array_column($perSp, 'volume_panen')), 2) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums"
                            x-text="jumlahTampak($el.closest('table'), 'pengaduan_terbuka', 0)">{{ array_sum(array_column($perSp, 'pengaduan_terbuka')) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
