{{--
    Rekap pengaduan.

    Memakai komposisi halaman rekap: tabel agregat dengan baris total
    ditegaskan, tanpa kartu statistik (agents/ui-spec.md bagian 2.2).

    Rekap per kategori, status, dan SP adalah sumber indikator isu prioritas
    pada dashboard (agents/rules.md bagian 10b poin 8).
--}}
@extends('layouts.app')

@section('content')
    @php
        // `$kelompok` dan `$rekap` datang dari closure `susunRekapPengaduan`
        // pada routes/web.php, yang dipakai bersama rute `pengaduan.rekap` dan
        // `pengaduan.rekap.kelompok`.
        $totalJumlah = array_sum(array_column($rekap, 'jumlah'));
        $totalSelesai = array_sum(array_column($rekap, 'selesai'));
        $totalBelum = array_sum(array_column($rekap, 'belum_selesai'));
        $totalMendesak = array_sum(array_column($rekap, 'mendesak'));

        /*
         * Daftar ini WAJIB sejalan dengan batasan `where` pada rute
         * `pengaduan.rekap.kelompok` dan larik pada DaftarTautanStatis.
         * Ketiganya mengunci hal yang sama, dan mengubah salah satunya saja
         * membuat halaman terbit membalas 404.
         */
        $labelKelompok = [
            'kategori' => 'Kategori',
            'status' => 'Status Penanganan',
            'sp' => 'Satuan Permukiman',
            'prioritas' => 'Prioritas',
            'bidang' => 'Bidang Penanganan',
        ];
    @endphp

    <x-sim.page-header judul="Rekap Pengaduan"
        keterangan="Sebaran laporan warga sebagai dasar penentuan isu prioritas kawasan."
        :remah="\App\Helpers\RemahHelper::untuk('/pengaduan/rekap')">
        <x-slot:aksi>
            <a href="{{ route('pengaduan.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Kembali ke Daftar Pengaduan
            </a>

            <x-sim.tombol-ekspor />
        </x-slot:aksi>
    </x-sim.page-header>

    <nav aria-label="Dasar pengelompokan rekap"
        class="mb-6 flex flex-wrap gap-2 rounded-2xl border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-white/[0.03]">
        @foreach ($labelKelompok as $nilai => $label)
            @php $aktif = $kelompok === $nilai; @endphp
            {{-- Tautan tetap, bukan kueri, agar tiap tab punya halamannya sendiri saat digilas jadi berkas statis --}}
            <a href="{{ route('pengaduan.rekap.kelompok', $nilai) }}"
                @if ($aktif) aria-current="page" @endif
                class="rounded-lg px-3 py-2 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 {{ $aktif
                    ? 'bg-brand-500 text-white'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}">
                Per {{ $label }}
            </a>
        @endforeach
    </nav>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                Rekap per {{ $labelKelompok[$kelompok] ?? 'Kategori' }}
            </h2>
            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                {{ count($rekap) }} kelompok, diurutkan dari jumlah laporan terbanyak.
            </p>
        </div>

        @if (empty($rekap))
            <x-sim.empty-state judul="Belum ada pengaduan untuk direkap"
                pesan="Rekap akan terisi setelah laporan warga masuk." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $labelKelompok[$kelompok] ?? 'Kategori' }}
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Jumlah Laporan
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Belum Selesai
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Selesai
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Mendesak
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($rekap as $r)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                    {{ $r['nama'] }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($r['jumlah'], 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($r['belum_selesai'], 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($r['selesai'], 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums">
                                    @if ($r['mendesak'] > 0)
                                        {{-- Aksen gold menandai yang perlu perhatian lebih dulu --}}
                                        <span class="font-semibold text-gold-700 dark:text-gold-400">
                                            {{ $r['mendesak'] }}
                                        </span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">0</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="motif-baris-total">
                            <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">Total</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalJumlah, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalBelum, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalSelesai, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalMendesak, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
        Kelompok dengan laporan belum selesai terbanyak menjadi petunjuk isu prioritas kawasan,
        dan angka inilah yang dibaca dashboard pada indikator ke-13.
    </p>
@endsection
