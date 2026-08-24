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

        // Dasar pengelompokan datang dari dua arah: segmen rute yang menjadi
        // tautan tetap, dan kueri `?kelompok=` milik tautan lama. Yang pertama
        // membuat ketiga tab tetap dapat dibuka pada build statis.
        $kelompok = $kelompokRute ?? request('kelompok', 'sp');

        /*
         * PERIODE SELALU TERIKAT, tidak pernah kumulatif sejak awal waktu.
         *
         * Bawaannya TAHUN BERJALAN sesuai keputusan pemilik proyek 2026-08-24.
         *
         * MEMAKAI TAHUN PANEN, bukan tahun tanam (diubah 2026-08-24). Ini
         * rekap PANEN, sehingga yang menggolongkan adalah peristiwa panennya.
         * Bentuk lama membuang panen April 2026 dari rekap 2026 hanya karena
         * penanamannya bermula November 2025, padahal timbangannya nyata
         * terjadi tahun itu.
         *
         * Penanaman yang belum dipanen digolongkan ke tahun berjalan, sebab
         * di situlah panennya masih mungkin terjadi. Lihat
         * DummyData::tahunRekapPanen().
         */
        $daftarTahun = DummyData::tahunPanenTercatat();
        $tahunPanen = (int) request('tahun', date('Y'));

        $rekap = DummyData::rekapPanen($kelompok, $tahunPanen);

        // Tab Tahun tidak ada; urutannya seragam menurut produksi terbesar.
        $totalPoktan = array_sum(array_column($rekap, 'jumlah_poktan'));
        $totalTanam = array_sum(array_column($rekap, 'realisasi_tanam'));
        $totalPanen = array_sum(array_column($rekap, 'hasil_panen'));
        $totalPuso = array_sum(array_column($rekap, 'puso'));
        $totalBelum = array_sum(array_column($rekap, 'belum_dipanen'));
        $totalProduksi = array_sum(array_column($rekap, 'produksi_ton'));
        $totalNilai = array_sum(array_column($rekap, 'nilai_jual'));

        // Produktivitas total pun TERTIMBANG, bukan rata-rata kolomnya.
        $produktivitasTotal = $totalPanen > 0 ? $totalProduksi / $totalPanen : 0.0;

        /*
         * Daftar ini WAJIB sejalan dengan batasan `where` pada rute
         * `panen.rekap.kelompok` dan larik pada DaftarTautanStatis. Ketiganya
         * mengunci hal yang sama, dan mengubah salah satunya saja membuat
         * halaman terbit membalas 404 tanpa penjaga apa pun (notes.md 1e.5).
         */
        $labelKelompok = [
            'sp' => 'Satuan Permukiman',
            'komoditas' => 'Komoditas',
            'poktan' => 'Kelompok Tani',
        ];

        // Kolom cacah poktan mubazir pada tab poktan: nilainya selalu 1.
        $tampilkanCacahPoktan = $kelompok !== 'poktan';
    @endphp

    <x-sim.page-header judul="Rekap Hasil Panen"
        keterangan="Realisasi tanam beserta panennya, dihitung dari catatan penanaman."
        :remah="\App\Helpers\RemahHelper::untuk('/panen/rekap')">
        <x-slot:aksi>
            <a href="{{ route('panen.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Kembali ke Daftar Panen
            </a>

            <x-sim.tombol-ekspor />
        </x-slot:aksi>
    </x-sim.page-header>

    {{-- Pemilih dasar pengelompokan, memenuhi rekap per wilayah, komoditas, dan periode --}}
    <nav aria-label="Dasar pengelompokan rekap"
        class="mb-6 flex flex-wrap gap-2 rounded-2xl border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-white/[0.03]">
        @foreach ($labelKelompok as $nilai => $label)
            @php $aktif = $kelompok === $nilai; @endphp
            <a href="{{ route('panen.rekap.kelompok', ['kelompok' => $nilai]) }}"
                @if ($aktif) aria-current="page" @endif
                class="rounded-lg px-3 py-2 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 {{ $aktif
                    ? 'bg-brand-500 text-white'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}">
                Per {{ $label }}
            </a>
        @endforeach
    </nav>

    {{--
        Penyaring periode. Formulir GET biasa, sehingga tetap bekerja tanpa
        JavaScript. Tahun terpilih ikut dibawa saat berpindah tab lewat isian
        tersembunyi pada tiap tautan? Tidak - tautan tab sengaja TIDAK membawa
        tahunnya, sebab tab dan periode adalah dua sumbu terpisah dan
        menggabungkannya membuat alamat tautan tetap tidak lagi dapat digilas
        menjadi berkas statis.
    --}}
    <form method="GET" action="{{ route('panen.rekap.kelompok', ['kelompok' => $kelompok]) }}"
        class="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div>
            <select id="filter_tahun" name="tahun"
                class="h-10 w-56 rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                {{--
                    Tahun berjalan selalu ditawarkan meski belum ada
                    penanamannya, sebab itulah bawaan halaman ini. Tanpa
                    baris ini, pilihan yang sedang aktif justru tidak ada di
                    dalam daftarnya sendiri setiap awal tahun.
                --}}
                @foreach (array_unique(array_merge([(int) date('Y')], $daftarTahun)) as $t)
                    <option value="{{ $t }}" @selected($tahunPanen === $t)>{{ $t }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit"
            class="h-10 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
            Terapkan Tahun
        </button>

        <p class="ml-auto max-w-md text-theme-xs text-gray-500 dark:text-gray-400">
            Rekap selalu terikat satu tahun panen. Luas tidak dijumlahkan lintas tahun,
            sebab bidang yang sama memang ditanami berulang kali.
        </p>
    </form>

    {{-- Tabel agregat, tanpa kartu statistik --}}
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            {{-- Periode ditulis pada JUDUL, bukan disembunyikan di penyaring:
                 angka rekap tanpa periodenya tidak dapat disalin ke laporan
                 mana pun. --}}
            <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                Rekap per {{ $labelKelompok[$kelompok] ?? 'Satuan Permukiman' }}
                &middot; Tahun Panen {{ $tahunPanen }}
            </h2>
            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                {{ count($rekap) }} kelompok, diurutkan dari produksi terbesar.
            </p>
        </div>

        @if (empty($rekap))
            <x-sim.empty-state judul="Belum ada panen pada tahun {{ $tahunPanen }}"
                pesan="Pilih tahun lain, atau catat hasil panen lebih dulu." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $labelKelompok[$kelompok] ?? 'Satuan Permukiman' }}
                            </th>
                            {{--
                                Cacah poktan menggantikan "Jumlah Catatan" yang dicabut
                                2026-08-24. Cacah catatan menghitung baris entri, bukan
                                besaran lapangan: poktan yang panen bertahap tiga kali
                                tampak "lebih banyak" daripada yang panen sekali meski
                                luasnya lebih kecil. Pada tab poktan kolom ini mubazir,
                                sebab nilainya selalu satu.
                            --}}
                            @if ($tampilkanCacahPoktan)
                                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                    Poktan
                                </th>
                            @endif
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Realisasi Tanam (ha)
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Realisasi Panen (ha)
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Puso (ha)
                            </th>
                            {{--
                                "Menunggu Panen", bukan "Belum Dipanen"
                                (diganti 2026-08-24).

                                Istilah lama dicabut dari form dan halaman
                                rincian bersama panen bertahap, dan memakainya
                                di sini dengan arti yang BERBEDA justru
                                membingungkan: di sana ia berarti sisa dari
                                panen yang setengah jalan, sedangkan di sini
                                berarti penanaman yang belum dipanen sama
                                sekali.

                                Kunci array `belum_dipanen` sengaja tidak ikut
                                diganti: ia nama teknis, dan menggantinya
                                menyeret helper beserta ujinya tanpa manfaat
                                setara. Yang membingungkan petugas adalah label
                                di layar, bukan nama kunci.
                            --}}
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Menunggu Panen (ha)
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Produksi (ton)
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Produktivitas (ton/ha)
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
                                @if ($tampilkanCacahPoktan)
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($r['jumlah_poktan'], 0, ',', '.') }}
                                    </td>
                                @endif
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($r['realisasi_tanam'], 2, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($r['hasil_panen'], 2, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($r['puso'], 2, ',', '.') }}
                                </td>
                                {{--
                                    Sisa tanam. Inilah kolom yang mustahil ada pada basis
                                    hasil panen, sebab penanaman tanpa panen tidak punya
                                    baris untuk dihitung.
                                --}}
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($r['belum_dipanen'], 2, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($r['produksi_ton'], 3, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($r['produktivitas_ton'], 3, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    Rp {{ number_format($r['nilai_jual'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    {{-- Baris total ditegaskan memakai motif identitas garis atas navy --}}
                    <tfoot>
                        {{-- "Total tahun ini", BUKAN "Total" saja: yang kedua
                             terbaca sebagai total sejak sistem berdiri. --}}
                        <tr class="motif-baris-total">
                            <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                                Total tahun panen {{ $tahunPanen }}
                            </td>
                            @if ($tampilkanCacahPoktan)
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($totalPoktan, 0, ',', '.') }}
                                </td>
                            @endif
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalTanam, 2, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalPanen, 2, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalPuso, 2, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalBelum, 2, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalProduksi, 3, ',', '.') }}
                            </td>
                            {{-- Tertimbang, bukan rata-rata kolom di atasnya --}}
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($produktivitasTotal, 3, ',', '.') }}
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

    <div class="mt-4 space-y-2 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
        <p>
            Dihitung dari <strong>catatan penanaman</strong>, bukan dari catatan panen. Dengan begitu
            kelompok yang sudah menanam tetapi belum panen tetap terlihat beserta luas yang
            masih menunggu; pada perhitungan berbasis panen, baris semacam itu hilang sama sekali.
        </p>
        <p>
            Produksi dicatat dalam satuan baku tiap komoditas, lalu dikonversi ke ton saat direkap
            memakai faktor pada data master satuan: ton 1, kuintal 0,1, dan kilogram 0,001.
            Tanpa konversi ini, penjumlahan lintas komoditas akan menghasilkan angka yang keliru.
        </p>
        <p>
            Produktivitas adalah <strong>total produksi dibagi total luas dipanen</strong>, bukan
            rata-rata kolom produktivitas. Merata-ratakannya mencampur ton per hektare dengan
            kilogram per hektare dan menghasilkan angka yang tidak ada di lapangan.
        </p>
    </div>
@endsection
