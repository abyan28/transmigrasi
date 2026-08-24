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

        /*
         * PENYARING SILANG (ditambahkan 2026-08-24). Tab menentukan baris APA,
         * penyaring menentukan baris MANA. Keduanya sumbu terpisah, dan justru
         * gabungannya yang berguna: "berapa produksi jagung di SP Weain" tidak
         * dapat dijawab tanpa keduanya.
         *
         * Penyaring yang dirender berbeda tiap tab, sebab menyaring SP pada
         * tab Per SP tidak menyaring apa pun yang berarti - ia hanya
         * menyisakan satu baris yang sudah terlihat sejak awal.
         */
        $filterSp = $kelompok !== 'sp' ? request('sp') : null;
        $filterKomoditas = $kelompok !== 'komoditas' ? request('komoditas') : null;

        /*
         * Opsi dihitung dari PENANAMAN pada tahun terpilih, bukan dari data
         * master. Master memuat enam SP dan lima komoditas, sedangkan tahun
         * 2025 hanya memiliki satu dari masing-masing; menawarkan sisanya
         * berarti menyuguhkan pilihan yang DIJAMIN menghasilkan tabel kosong.
         */
        $opsiFilter = DummyData::opsiFilterRekapPanen($tahunPanen);

        /*
         * Nilai yang tidak lagi tersedia DILEPAS, bukan dibiarkan menghasilkan
         * tabel kosong tanpa penjelasan.
         *
         * Keadaannya nyata: petugas menyaring CABAI pada 2026, lalu berpindah
         * ke 2025 - dan cabai tidak ditanam tahun itu. Tanpa pelepasan ini
         * halaman tampak rusak; tanpa pemberitahuannya, petugas mengira
         * penyaringnya yang tidak bekerja.
         */
        $filterDilepas = [];

        if ($filterSp !== null && $filterSp !== '' && ! in_array($filterSp, $opsiFilter['sp'], true)) {
            $filterDilepas[] = 'Satuan Permukiman '.$filterSp;
            $filterSp = null;
        }

        if ($filterKomoditas !== null && $filterKomoditas !== '' && ! in_array($filterKomoditas, $opsiFilter['komoditas'], true)) {
            $filterDilepas[] = 'Komoditas '.$filterKomoditas;
            $filterKomoditas = null;
        }

        // String kosong berarti "semua", bukan penyaring bernilai kosong.
        $filterSp = $filterSp !== '' ? $filterSp : null;
        $filterKomoditas = $filterKomoditas !== '' ? $filterKomoditas : null;

        $rekap = DummyData::rekapPanen($kelompok, $tahunPanen, $filterSp, $filterKomoditas);

        // Dipakai judul tabel dan baris total. Angka rekap tanpa cakupannya
        // tidak dapat disalin ke laporan mana pun.
        $cakupanFilter = array_values(array_filter([$filterSp, $filterKomoditas]));
        $adaFilter = $cakupanFilter !== [];

        $totalPoktan = array_sum(array_column($rekap, 'jumlah_poktan'));
        $totalLuas = array_sum(array_column($rekap, 'luas_lahan'));
        $totalBenih = array_sum(array_column($rekap, 'volume_benih'));
        $totalTanam = array_sum(array_column($rekap, 'realisasi_tanam'));
        $totalPanen = array_sum(array_column($rekap, 'hasil_panen'));
        $totalPuso = array_sum(array_column($rekap, 'puso'));
        $totalBelum = array_sum(array_column($rekap, 'belum_dipanen'));
        $totalProduksi = array_sum(array_column($rekap, 'produksi_ton'));
        $totalNilai = array_sum(array_column($rekap, 'nilai_jual'));

        /*
         * Produktivitas total pun TERTIMBANG, bukan rata-rata kolomnya.
         *
         * Contoh nyata pada tahun 2026: produksi 10,151 ton dibagi luas
         * dipanen 3,45 ha menghasilkan 2,942 ton/ha. Rata-rata naif ketiga
         * baris justru 1,452 - tertarik turun oleh baris yang gagal total dan
         * berproduktivitas nol, padahal luas panennya nol pula sehingga tidak
         * seharusnya ikut menimbang.
         */
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

        /*
         * KOLOM KEDUA BERBEDA TIAP TAB, ditetapkan pemilik proyek 2026-08-24.
         *
         * - Per SP dan Per Poktan  -> Luas Lahan, sebab lahan memang milik poktan
         * - Per Komoditas          -> Volume Benih
         *
         * Luas lahan sengaja TIDAK ditampilkan pada tab komoditas: satu poktan
         * menanam beberapa komoditas, sehingga lahannya akan terhitung
         * berkali-kali dan totalnya melampaui luas kawasan yang sebenarnya.
         *
         * Cacah poktan tidak ditampilkan pada tab poktan, sebab nilainya
         * selalu satu.
         */
        $tampilkanCacahPoktan = $kelompok !== 'poktan';
        $tampilkanLuasLahan = $kelompok !== 'komoditas';
        $tampilkanVolumeBenih = $kelompok === 'komoditas';

        // Cacah kolom, dipakai memeriksa kesejajaran baris total. Tetap: nama,
        // 4 kolom luas, produktivitas, produksi, nilai jual.
        $cacahKolom = 8 + (int) $tampilkanCacahPoktan + (int) $tampilkanLuasLahan + (int) $tampilkanVolumeBenih;
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
        Penyaring. Formulir GET biasa, sehingga tetap bekerja tanpa JavaScript.

        Tautan tab sengaja TIDAK membawa penyaringnya: tab dan penyaring adalah
        dua sumbu terpisah, dan menggabungkannya membuat alamat tautan tetap
        tidak lagi dapat digilas menjadi berkas statis.
    --}}
    <form method="GET" action="{{ route('panen.rekap.kelompok', ['kelompok' => $kelompok]) }}"
        class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="filter_tahun"
                    class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                    Tahun Panen
                </label>
                <select id="filter_tahun" name="tahun"
                    class="h-10 w-40 rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
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

            {{--
                Penyaring SP tidak dirender pada tab Per SP: menyaringnya di
                sana hanya menyisakan satu baris yang sudah terlihat sejak awal,
                dan kontrol yang tidak berguna sama saja dengan kontrol mati.
            --}}
            @if ($kelompok !== 'sp')
                <div>
                    <label for="filter_sp"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                        Satuan Permukiman
                    </label>
                    <select id="filter_sp" name="sp"
                        class="h-10 w-52 rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua SP</option>
                        @foreach ($opsiFilter['sp'] as $nama)
                            <option value="{{ $nama }}" @selected($filterSp === $nama)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($kelompok !== 'komoditas')
                <div>
                    <label for="filter_komoditas"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                        Komoditas
                    </label>
                    <select id="filter_komoditas" name="komoditas"
                        class="h-10 w-48 rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua komoditas</option>
                        @foreach ($opsiFilter['komoditas'] as $nama)
                            <option value="{{ $nama }}" @selected($filterKomoditas === $nama)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button type="submit"
                class="h-10 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                Terapkan Filter
            </button>

            @if ($adaFilter)
                <a href="{{ route('panen.rekap.kelompok', ['kelompok' => $kelompok]) }}?tahun={{ $tahunPanen }}"
                    class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    Bersihkan Filter
                </a>
            @endif
        </div>

        {{--
            Penyaring yang DILEPAS wajib diberitahukan. Tanpa ini petugas yang
            berpindah tahun akan melihat penyaringnya kembali ke "Semua" tanpa
            tahu sebabnya, dan mengira sistem mengabaikan pilihannya.
        --}}
        @if ($filterDilepas !== [])
            <p class="mt-3 rounded-lg bg-yellow-50 p-3 text-theme-xs text-yellow-800 dark:bg-yellow-500/10 dark:text-yellow-400">
                Filter {{ implode(' dan ', $filterDilepas) }} dilepas,
                sebab tidak ada penanamannya pada tahun panen {{ $tahunPanen }}.
            </p>
        @endif

        <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
            Pilihan penyaring hanya memuat yang benar-benar ada pada tahun terpilih.
            Rekap selalu terikat satu tahun panen; luas tidak dijumlahkan lintas tahun,
            sebab bidang yang sama memang ditanami berulang kali.
        </p>
    </form>
    {{-- Tabel agregat, tanpa kartu statistik --}}
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            {{--
                Periode DAN penyaring ditulis pada JUDUL, bukan disembunyikan
                di formulir: angka rekap tanpa cakupannya tidak dapat disalin
                ke laporan mana pun, dan pembaca yang menyalinnya akan mengira
                itu angka seluruh kawasan.
            --}}
            <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                Rekap per {{ $labelKelompok[$kelompok] ?? 'Satuan Permukiman' }}
                &middot; Tahun Panen {{ $tahunPanen }}
                @if ($adaFilter)
                    @foreach ($cakupanFilter as $nilai)
                        &middot; {{ $nilai }}
                    @endforeach
                @endif
            </h2>
            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                {{ count($rekap) }} kelompok, diurutkan dari produksi terbesar.
                @if ($adaFilter)
                    Seluruh angka pada tabel ini hanya mencakup penyaring di atas.
                @endif
            </p>
        </div>

        @if (empty($rekap))
            <x-sim.empty-state judul="Tidak ada data yang cocok"
                :pesan="$adaFilter
                    ? 'Tidak ada penanaman pada tahun ' . $tahunPanen . ' yang cocok dengan penyaring ' . implode(' dan ', $cakupanFilter) . '.'
                    : 'Belum ada penanaman pada tahun ' . $tahunPanen . '. Pilih tahun lain, atau catat hasil panen lebih dulu.'" />
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
                            {{--
                                Luas Lahan hanya pada tab SP dan Poktan. Pada
                                tab komoditas ia mustahil benar: satu poktan
                                menanam beberapa komoditas, sehingga lahannya
                                terhitung berkali-kali dan totalnya melampaui
                                luas kawasan yang sebenarnya.
                            --}}
                            @if ($tampilkanLuasLahan)
                                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                    Luas Lahan (ha)
                                </th>
                            @endif
                            {{--
                                Volume Benih menggantikannya pada tab komoditas.
                                Di situ ia justru bermakna: takaran benih per
                                komoditas dapat dibandingkan antarbaris, dan
                                seluruh benih kini tercatat sebab yang swadaya
                                pun wajib didaftarkan lebih dulu.
                            --}}
                            @if ($tampilkanVolumeBenih)
                                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                    Volume Benih (kg)
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
                                Produktivitas (ton/ha)
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Produksi (ton)
                            </th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Perkiraan Nilai Jual
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($rekap as $r)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                {{--
                                    Pada tab Kelompok Tani, SP asal disisipkan
                                    di bawah nama poktan. Nama poktan sendiri
                                    tidak menyatakan lokasinya, sehingga tanpa
                                    keterangan ini pembaca harus mengingat
                                    sendiri kelompok mana ada di SP mana.
                                --}}
                                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                    {{ $r['nama'] }}
                                    @if ($kelompok === 'poktan' && $r['sp'] !== [])
                                        <p class="mt-0.5 text-theme-xs font-normal text-gray-500 dark:text-gray-400">
                                            {{ implode(', ', $r['sp']) }}
                                        </p>
                                    @endif
                                </td>
                                @if ($tampilkanCacahPoktan)
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($r['jumlah_poktan'], 0, ',', '.') }}
                                    </td>
                                @endif
                                @if ($tampilkanLuasLahan)
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($r['luas_lahan'], 2, ',', '.') }}
                                    </td>
                                @endif
                                @if ($tampilkanVolumeBenih)
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($r['volume_benih'], 2, ',', '.') }}
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
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($r['produktivitas_ton'], 3, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($r['produksi_ton'], 3, ',', '.') }}
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
                            {{--
                                Baris total IKUT MENYEMPIT ketika penyaring aktif,
                                dan itu wajib dinyatakan. Tanpa keterangan ini,
                                angkanya dapat disalin ke laporan sebagai total
                                kawasan padahal hanya mencakup satu komoditas.
                            --}}
                            <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                                Total tahun panen {{ $tahunPanen }}
                                @if ($adaFilter)
                                    <span class="block text-theme-xs font-normal text-gray-500 dark:text-gray-400">
                                        {{ implode(', ', $cakupanFilter) }} saja
                                    </span>
                                @endif
                            </td>
                            @if ($tampilkanCacahPoktan)
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($totalPoktan, 0, ',', '.') }}
                                </td>
                            @endif
                            @if ($tampilkanLuasLahan)
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($totalLuas, 2, ',', '.') }}
                                </td>
                            @endif
                            @if ($tampilkanVolumeBenih)
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($totalBenih, 2, ',', '.') }}
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
                            {{--
                                TERTIMBANG, bukan rata-rata kolom di atasnya.
                                Total produksi dibagi total luas dipanen; pada
                                2026 menghasilkan 10,151 / 3,45 = 2,942 ton/ha,
                                sedangkan rata-rata naif ketiga barisnya hanya
                                1,452 - tertarik turun oleh baris gagal total
                                yang luas panennya nol.
                            --}}
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($produktivitasTotal, 3, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalProduksi, 3, ',', '.') }}
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
