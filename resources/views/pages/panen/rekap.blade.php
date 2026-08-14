{{--
    Rekap hasil panen.

    Halaman rekap adalah jenis komposisi KEEMPAT pada dial RITME 2: tabel
    agregat dengan baris total yang ditegaskan, TANPA kartu statistik
    (agents/ui-spec.md bagian 2.2). Ini sengaja dibedakan dari halaman daftar
    yang memakai kartu ringkasan di atas tabelnya.

    Seluruh penjumlahan lintas komoditas memakai hasil konversi ke ton, karena
    menjumlahkan ton dan kilogram begitu saja menghasilkan angka yang keliru
    (agents/rules.md bagian 8a poin 5).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::hasilPanen();
        $kelompok = request('kelompok', 'sp');

        // Menyusun rekap menurut kolom pengelompokan yang dipilih.
        $peta = [];

        foreach ($semua as $p) {
            $kunci = match ($kelompok) {
                'komoditas' => $p['komoditas'],
                'musim' => $p['musim_tanam'],
                'petani' => $p['petani'],
                default => $p['satuan_permukiman'],
            };

            if (! isset($peta[$kunci])) {
                $peta[$kunci] = [
                    'nama' => $kunci,
                    'jumlah_catatan' => 0,
                    'volume_ton' => 0.0,
                    'nilai_jual' => 0.0,
                ];
            }

            $peta[$kunci]['jumlah_catatan']++;
            $peta[$kunci]['volume_ton'] += DummyData::keTon($p['volume'], $p['satuan']);
            $peta[$kunci]['nilai_jual'] += ($p['harga_jual'] ?? 0) * $p['volume'];
        }

        // Diurutkan dari volume terbesar agar yang paling berpengaruh terbaca dulu.
        $rekap = array_values($peta);
        usort($rekap, fn ($a, $b) => $b['volume_ton'] <=> $a['volume_ton']);

        $totalCatatan = array_sum(array_column($rekap, 'jumlah_catatan'));
        $totalVolume = array_sum(array_column($rekap, 'volume_ton'));
        $totalNilai = array_sum(array_column($rekap, 'nilai_jual'));

        $labelKelompok = [
            'sp' => 'Satuan Permukiman',
            'komoditas' => 'Komoditas',
            'musim' => 'Musim Tanam',
            'petani' => 'Petani',
        ];
    @endphp

    <x-sim.page-header judul="Rekap Hasil Panen"
        keterangan="Agregat volume panen yang seluruhnya sudah dikonversi ke ton."
        :remah="[
            ['label' => 'Pertanian'],
            ['label' => 'Hasil Panen', 'url' => route('panen.index')],
            ['label' => 'Rekap Panen'],
        ]">
        <x-slot:aksi>
            <a href="{{ route('panen.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Kembali ke Daftar Panen
            </a>
        </x-slot:aksi>
    </x-sim.page-header>

    {{-- Pemilih dasar pengelompokan, memenuhi rekap per wilayah, komoditas, dan periode --}}
    <nav aria-label="Dasar pengelompokan rekap"
        class="mb-6 flex flex-wrap gap-2 rounded-2xl border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-white/[0.03]">
        @foreach ($labelKelompok as $nilai => $label)
            @php $aktif = $kelompok === $nilai; @endphp
            <a href="{{ route('panen.rekap', ['kelompok' => $nilai]) }}"
                @if ($aktif) aria-current="page" @endif
                class="rounded-lg px-3 py-2 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 {{ $aktif
                    ? 'bg-brand-500 text-white'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}">
                Per {{ $label }}
            </a>
        @endforeach
    </nav>

    {{-- Tabel agregat, tanpa kartu statistik --}}
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                Rekap per {{ $labelKelompok[$kelompok] ?? 'Satuan Permukiman' }}
            </h2>
            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                {{ count($rekap) }} kelompok, diurutkan dari volume terbesar.
            </p>
        </div>

        @if (empty($rekap))
            <x-sim.empty-state judul="Belum ada data panen untuk direkap"
                pesan="Rekap akan terisi setelah petugas mencatat hasil panen." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $labelKelompok[$kelompok] ?? 'Satuan Permukiman' }}
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Jumlah Catatan
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Volume (ton)
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Perkiraan Nilai Jual
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($rekap as $r)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                    {{ $r['nama'] }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($r['jumlah_catatan'], 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($r['volume_ton'], 3, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    Rp {{ number_format($r['nilai_jual'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    {{-- Baris total ditegaskan memakai motif identitas garis atas navy --}}
                    <tfoot>
                        <tr class="motif-baris-total">
                            <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">Total kawasan</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalCatatan, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalVolume, 3, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                Rp {{ number_format($totalNilai, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
        Volume dicatat dalam satuan baku tiap komoditas, lalu dikonversi ke ton saat direkap
        memakai faktor pada data master satuan: ton 1, kuintal 0,1, dan kilogram 0,001.
        Tanpa konversi ini, penjumlahan lintas komoditas akan menghasilkan angka yang keliru.
    </p>
@endsection
