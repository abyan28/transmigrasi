{{--
    Dashboard monitoring kawasan.

    Memuat 15 indikator sesuai pemetaan pada agents/ui-spec.md bagian 9.
    Komposisi mengikuti dial RITME 2: baris kartu statistik di atas, lalu grid
    grafik dua kolom yang TIDAK sama lebar, agar dashboard terbaca berbeda dari
    halaman daftar dan halaman rekap (bagian 2.2).

    Seluruh angka masih berasal dari app/Support/DummyData.php. Penggantian ke
    query nyata dikerjakan pada Task 9.1 tanpa mengubah berkas ini, karena nama
    kunci array sudah mengikuti kamus data.

    Filter wilayah dan periode sudah berbentuk kontrol nyata yang menulis query
    string, tetapi penyaringan datanya baru aktif pada Task 9.2. Kontrol tetap
    berfungsi mengubah URL, bukan tombol mati (ANTISLOP-ID R-26).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        use App\Support\PenilaianKondisiSp;

        $ringkasan = DummyData::ringkasanDashboard();
        $deret = DummyData::deretTahunan();
        $rekapSp = DummyData::rekapPerSp();

        // Indikator ke-16: kondisi layanan dasar tiap SP
        $penilaianSp = PenilaianKondisiSp::nilaiSeluruhSp();
        $rekapKondisi = PenilaianKondisiSp::rekapStatus();
        $statusInfra = DummyData::statusInfrastruktur();
        $sebaranPekerjaan = DummyData::sebaranPekerjaan();
        $rekapStatusPengaduan = DummyData::rekapPengaduan('status');
        $sebaranKomoditas = DummyData::sebaranKomoditas();
        $rekapPenghuni = DummyData::rekapPenghuni();
        $daftarSp = DummyData::satuanPermukiman();
        $pengaduan = DummyData::pengaduan();

        $persenHuni = round($ringkasan['rumah_terhuni'] / $ringkasan['rumah_total'] * 100);

        // Komoditas dengan volume terbesar, dipakai kartu komoditas utama.
        //
        // Dipilih berdasarkan NILAI, bukan urutan larik. `array_key_first()`
        // sempat dipakai di sini dan kebetulan benar hanya karena
        // `sebaranKomoditas()` ditulis terurut; begitu urutannya berubah,
        // kartu ini akan menampilkan komoditas yang keliru tanpa ada yang
        // menyadarinya.
        //
        // "Utama" berbeda dari "unggulan": yang ini dihitung dari volume dan
        // berubah mengikuti musim, sedangkan unggulan ditetapkan menurut
        // proposal atau kebijakan dinas (`rules.md` 8.1) dan ditandai petugas.
        $komoditasUtama = array_search(max($sebaranKomoditas), $sebaranKomoditas, true);

        // Isu prioritas: pengaduan yang belum selesai, diurutkan dari yang paling mendesak
        $urutanPrioritas = ['Mendesak' => 0, 'Tinggi' => 1, 'Sedang' => 2, 'Rendah' => 3];
        $isuPrioritas = array_filter($pengaduan, fn ($p) => $p['status'] !== 'Selesai');
        usort($isuPrioritas, fn ($a, $b) => $urutanPrioritas[$a['prioritas']] <=> $urutanPrioritas[$b['prioritas']]);

        // Bahan grafik disusun di sini, bukan di dalam blok skrip, karena
        // direktif @json tidak dapat mengurai array bersarang bertingkat.
        $dataGrafik = [
            'tahun' => $deret['tahun'],
            'jiwa' => $deret['jumlah_jiwa'],
            'kk' => $deret['jumlah_kk'],
            'petani' => $deret['jumlah_petani'],
            'pendapatan' => $deret['pendapatan_rata_rata'],
            'kkMasuk' => $deret['kk_masuk'],
            'kkKeluar' => $deret['kk_keluar'],
            'volumePanen' => $deret['volume_panen'],
            'harga' => $deret['harga_rata_rata'],
            'statusPengaduanNama' => array_column($rekapStatusPengaduan, 'nama'),
            'statusPengaduanNilai' => array_column($rekapStatusPengaduan, 'jumlah'),
            'pekerjaanNama' => array_keys($sebaranPekerjaan),
            'pekerjaanNilai' => array_values($sebaranPekerjaan),
            'komoditasNama' => array_keys($sebaranKomoditas),
            'komoditasNilai' => array_values($sebaranKomoditas),
            'penghuniNama' => array_keys($rekapPenghuni),
            'penghuniNilai' => array_values($rekapPenghuni),
            'infraJenis' => array_column($statusInfra, 'jenis'),
            'infraBaik' => array_column($statusInfra, 'baik'),
            'infraRusakRingan' => array_column($statusInfra, 'rusak_ringan'),
            'infraRusakBerat' => array_column($statusInfra, 'rusak_berat'),
            'spNama' => array_column($rekapSp, 'satuan_permukiman'),
            'spKk' => array_column($rekapSp, 'jumlah_kk'),
            'spPanen' => array_column($rekapSp, 'volume_panen'),
            // Dipakai penelusuran klik menuju /dashboard/sp/{id}
            'spId' => array_column($rekapSp, 'satuan_permukiman_id'),
        ];
    @endphp

    <x-sim.page-header judul="Dashboard Kawasan Kobalima Timur"
        keterangan="Ringkasan kependudukan, lahan, produksi, dan pengaduan di enam satuan permukiman."
        :remah="\App\Helpers\RemahHelper::untuk('/')">
        {{--
            Rekap indikator kawasan untuk laporan kementerian (rules.md 12
            poin 4). Dahulu berada di halaman laporan terpusat; dipindah ke
            sini karena dashboard memang sumber seluruh indikatornya, dan
            ekspor kini menempel pada data yang ditampilkan.
        --}}
        <x-slot:aksi>
            <x-sim.tombol-ekspor label="Ekspor Laporan Kawasan" />
        </x-slot:aksi>
    </x-sim.page-header>

    {{--
        Filter global. Memengaruhi seluruh visualisasi mulai Task 9.2; saat ini
        pilihannya tersimpan di query string sehingga tetap bertahan setelah
        halaman dimuat ulang.
    --}}
    <form method="GET" action="{{ route('beranda') }}"
        class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="filter_sp" class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                    Satuan Permukiman
                </label>
                <select id="filter_sp" name="sp"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                    <option value="">Seluruh kawasan</option>
                    @foreach ($daftarSp as $sp)
                        <option value="{{ $sp['id_satuan_permukiman'] }}"
                            @selected(request('sp') == $sp['id_satuan_permukiman'])>
                            {{ $sp['nama'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter_tahun_awal"
                    class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                    Tahun Mulai
                </label>
                <select id="filter_tahun_awal" name="tahun_awal"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm tabular-nums text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                    @foreach ($deret['tahun'] as $tahun)
                        <option value="{{ $tahun }}" @selected(request('tahun_awal', $deret['tahun'][0]) == $tahun)>
                            {{ $tahun }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter_tahun_akhir"
                    class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                    Tahun Akhir
                </label>
                <select id="filter_tahun_akhir" name="tahun_akhir"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm tabular-nums text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                    @foreach ($deret['tahun'] as $tahun)
                        <option value="{{ $tahun }}" @selected(request('tahun_akhir', end($deret['tahun'])) == $tahun)>
                            {{ $tahun }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="h-10 flex-1 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    Terapkan Filter
                </button>
                @if (request()->hasAny(['sp', 'tahun_awal', 'tahun_akhir']))
                    <a href="{{ route('beranda') }}"
                        class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Bersihkan
                    </a>
                @endif
            </div>
        </div>
    </form>

    <x-sim.judul-bagian judul="Ringkasan Kawasan"
        keterangan="Angka pokok kawasan pada satu pandangan." class="!mt-0" />

    {{--
        Baris kartu statistik. Dimuat lebih dulu, grafik menyusul di bawahnya
        (agents/ui-spec.md bagian 9 poin 4).

        Indikator 6, 8, 14, dan 15 disajikan sebagai kartu.
    --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-sim.stat-card label="Jumlah Kepala Keluarga"
            :nilai="number_format($ringkasan['jumlah_kk'], 0, ',', '.')" satuan="KK"
            :keterangan="number_format($ringkasan['jumlah_jiwa'], 0, ',', '.') . ' jiwa di seluruh kawasan'"
            url="/kependudukan/rekap" />

        <x-sim.stat-card label="Rumah Terhuni"
            :nilai="number_format($ringkasan['rumah_terhuni'], 0, ',', '.')"
            :keterangan="$persenHuni . '% dari ' . number_format($ringkasan['rumah_total'], 0, ',', '.') . ' rumah'"
            url="/rumah" />

        <x-sim.stat-card label="Luas Lahan Tergarap"
            :nilai="number_format($ringkasan['luas_lahan_total'], 2, ',', '.')" satuan="ha"
            :keterangan="'Tersebar di ' . count($daftarSp) . ' satuan permukiman'" url="/lahan" />

        <x-sim.stat-card label="Jumlah Petani"
            :nilai="number_format($ringkasan['jumlah_petani'], 0, ',', '.')" satuan="orang"
            :keterangan="round($ringkasan['jumlah_petani'] / $ringkasan['jumlah_kk'] * 100) . '% dari seluruh kepala keluarga'"
            url="/transmigran" />
    </div>

    {{-- Baris kedua kartu: produksi dan pengaduan --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-sim.stat-card label="Volume Panen Tahun Ini"
            :nilai="number_format($ringkasan['volume_panen_ton'], 3, ',', '.')" satuan="ton"
            keterangan="Hasil konversi seluruh komoditas ke ton" url="/panen" />

        <x-sim.stat-card label="Harga Jual Rata-rata"
            :nilai="'Rp ' . number_format($ringkasan['harga_rata_rata'], 0, ',', '.')"
            keterangan="Per ton, seluruh komoditas" />

        <x-sim.stat-card label="Komoditas Utama" :nilai="$komoditasUtama"
            :keterangan="number_format($sebaranKomoditas[$komoditasUtama], 1, ',', '.') . ' ton dipanen'"
            url="/komoditas" />

        <x-sim.stat-card label="Pengaduan Belum Selesai"
            :nilai="number_format($ringkasan['pengaduan_terbuka'], 0, ',', '.')"
            keterangan="Menunggu ditindaklanjuti petugas" url="/pengaduan" />
    </div>

    <x-sim.judul-bagian judul="Kependudukan"
        keterangan="Berapa banyak warga, bagaimana perpindahannya, dan dari apa mereka hidup." />

    {{--
        Grid tiga kolom yang sengaja TIDAK sama lebar, sebagai penanda
        komposisi dashboard pada dial RITME 2 (agents/ui-spec.md bagian 2.2).

        Tiap baris dijaga berjumlah tiga kolom, yaitu satu kartu lebar
        berdampingan dengan satu kartu sempit, agar tidak ada kolom
        menganggur di ujung baris.
    --}}
    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Indikator 1, 2, 3: pertumbuhan penduduk kawasan --}}
        <x-sim.chart-card class="xl:col-span-2" id="grafikPenduduk" judul="Pertumbuhan Penduduk Kawasan"
            keterangan="Jumlah transmigran, kepala keluarga, dan petani per tahun."
            tinggi="340">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Jiwa</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">KK</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Petani</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($deret['tahun'] as $i => $tahun)
                            <tr>
                                <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">{{ $tahun }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($deret['jumlah_jiwa'][$i], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($deret['jumlah_kk'][$i], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($deret['jumlah_petani'][$i], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        {{-- Indikator 5: KK masuk dan keluar --}}
        <x-sim.chart-card id="grafikMutasiKk" judul="Kepala Keluarga Masuk dan Keluar"
            keterangan="Perpindahan KK tiap tahun." tinggi="300">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Masuk</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Keluar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($deret['tahun'] as $i => $tahun)
                            <tr>
                                <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">{{ $tahun }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($deret['kk_masuk'][$i], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($deret['kk_keluar'][$i], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        {{-- Indikator 7: pekerjaan kepala keluarga --}}
        <x-sim.chart-card class="xl:col-span-2" id="grafikPekerjaan" judul="Pekerjaan Kepala Keluarga"
            keterangan="Sebaran mata pencaharian utama di seluruh kawasan." tinggi="320">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Pekerjaan</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Jumlah KK</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($sebaranPekerjaan as $nama => $jumlah)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-white/90">{{ $nama }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($jumlah, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        {{-- Indikator 14: rekap penghuni kawasan --}}
        <x-sim.chart-card id="grafikPenghuni" judul="Status Tinggal Penghuni"
            keterangan="Rekap kepala keluarga menurut keberadaannya di kawasan." tinggi="300">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Jumlah KK</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($rekapPenghuni as $status => $jumlah)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-white/90">{{ $status }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($jumlah, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>
    </div>

    <x-sim.judul-bagian judul="Pertanian dan Ekonomi"
        keterangan="Apa yang ditanam, berapa hasilnya, dan bagaimana dampaknya bagi pendapatan keluarga." />

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Indikator 10: volume panen per tahun --}}
        <x-sim.chart-card class="xl:col-span-2" id="grafikPanen" judul="Volume Panen per Tahun"
            keterangan="Seluruh komoditas dikonversi ke ton sebelum dijumlahkan." tinggi="320">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Volume (ton)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($deret['tahun'] as $i => $tahun)
                            <tr>
                                <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">{{ $tahun }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($deret['volume_panen'][$i], 3, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        {{-- Indikator 9: komoditas utama --}}
        <x-sim.chart-card id="grafikKomoditas" judul="Sebaran Komoditas"
            keterangan="Volume panen per komoditas, dalam ton." tinggi="340">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Komoditas</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Volume (ton)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($sebaranKomoditas as $nama => $volume)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-white/90">{{ $nama }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($volume, 1, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        {{-- Indikator 4: pendapatan keluarga --}}
        <x-sim.chart-card class="xl:col-span-2" id="grafikPendapatan" judul="Pendapatan Keluarga per Bulan"
            keterangan="Rata-rata pendapatan kepala keluarga tiap tahun." tinggi="300">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($deret['tahun'] as $i => $tahun)
                            <tr>
                                <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">{{ $tahun }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    Rp {{ number_format($deret['pendapatan_rata_rata'][$i], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        {{-- Indikator 11: harga rata-rata --}}
        <x-sim.chart-card id="grafikHarga" judul="Harga Jual Rata-rata"
            keterangan="Rupiah per ton, seluruh komoditas." tinggi="320">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Harga</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($deret['tahun'] as $i => $tahun)
                            <tr>
                                <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">{{ $tahun }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    Rp {{ number_format($deret['harga_rata_rata'][$i], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>
    </div>

    <x-sim.judul-bagian judul="Infrastruktur dan Layanan"
        keterangan="Kesiapan layanan dasar tiap permukiman beserta laporan warga yang menunggu ditangani." />

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Indikator 12: status infrastruktur --}}
        <x-sim.chart-card class="xl:col-span-2" id="grafikInfrastruktur" judul="Kondisi Infrastruktur SP"
            keterangan="Jumlah aset menurut kondisi terkini, dipecah per jenis." tinggi="320">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Jenis</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Baik</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Rusak Ringan</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Rusak Berat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($statusInfra as $baris)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-white/90">{{ $baris['jenis'] }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $baris['baik'] }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $baris['rusak_ringan'] }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $baris['rusak_berat'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>

        {{--
            Indikator 17: rekap pengaduan per status.

            Mengisi satu kolom yang sebelumnya menganggur di samping kartu
            pekerjaan. Data rekapPengaduan sudah tersedia tetapi belum pernah
            ditampilkan pada dashboard utama, padahal menjawab pertanyaan yang
            paling sering muncul: berapa laporan yang masih menunggu ditangani.
        --}}
        <x-sim.chart-card id="grafikStatusPengaduan" judul="Pengaduan per Status"
            keterangan="Seluruh laporan warga menurut tahap penanganannya." tinggi="320">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($rekapStatusPengaduan as $baris)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-white/90">{{ $baris['nama'] }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($baris['jumlah'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>
    </div>

    {{--
        Indikator 16: status kondisi SP.

        Disajikan sebagai kartu ringkasan beserta tabel, bukan grafik, karena
        yang dibutuhkan petugas adalah daftar SP yang dapat langsung
        ditindaklanjuti beserta penyebabnya (agents/ui-spec.md bagian 9).
    --}}
    <div class="mt-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 p-5 dark:border-gray-800">
            <div>
                <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Kondisi Layanan Dasar per SP</h3>
                <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Menilai ketersediaan dan kondisi infrastruktur serta fasilitas, bukan penghuninya.
                </p>
            </div>
            <a href="{{ route('sp.index') }}"
                class="rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Lihat Seluruh SP
            </a>
        </div>

        {{-- Ringkasan jumlah SP per status --}}
        <div class="grid gap-4 border-b border-gray-200 p-5 sm:grid-cols-3 dark:border-gray-800">
            @foreach (\App\Enums\StatusKondisiSp::cases() as $status)
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <div class="flex items-center justify-between gap-2">
                        <x-sim.status-badge :status="$status" ukuran="sm" />
                        <span class="text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                            {{ $rekapKondisi[$status->value] }}
                        </span>
                    </div>
                    <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $status->keterangan() }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- Tabel per SP beserta penyebabnya --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                            Satuan Permukiman</th>
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                            Skor</th>
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                            Status</th>
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                            Yang Perlu Diperhatikan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($penilaianSp as $p)
                        @php $penyebab = PenilaianKondisiSp::penyebabUtama($p); @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-3">
                                <a href="{{ route('dashboard.sp', $p['satuan_permukiman_id']) }}"
                                    class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                    {{ $p['satuan_permukiman'] }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($p['skor'], 2, ',', '.') }}
                            </td>
                            <td class="px-5 py-3">
                                <x-sim.status-badge :status="$p['status']" />
                            </td>
                            <td class="px-5 py-3 text-theme-xs text-gray-600 dark:text-gray-400">
                                @if (empty($penyebab))
                                    <span class="text-gray-500 dark:text-gray-400">Seluruh layanan berfungsi baik</span>
                                @else
                                    {{ implode(', ', array_slice($penyebab, 0, 3)) }}
                                    @if (count($penyebab) > 3)
                                        <span class="text-gray-500 dark:text-gray-400">
                                            dan {{ count($penyebab) - 3 }} lainnya
                                        </span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tata letak kartu untuk layar sempit --}}
        <div class="divide-y divide-gray-200 md:hidden dark:divide-gray-800">
            @foreach ($penilaianSp as $p)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <a href="{{ route('dashboard.sp', $p['satuan_permukiman_id']) }}"
                            class="rounded text-theme-sm font-medium text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90">
                            {{ $p['satuan_permukiman'] }}
                        </a>
                        <x-sim.status-badge :status="$p['status']" ukuran="sm" />
                    </div>
                    <p class="mt-1 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                        Skor {{ number_format($p['skor'], 2, ',', '.') }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    {{--
        Indikator 13: isu prioritas dari fitur Pengaduan.
        Disajikan sebagai tabel beserta badge, bukan grafik, karena yang
        dibutuhkan petugas adalah daftar yang dapat langsung ditindaklanjuti
        (agents/ui-spec.md bagian 9).
    --}}
    <div class="mt-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-5 dark:border-gray-800">
            <div>
                <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Isu Prioritas per SP</h3>
                <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    {{ count($isuPrioritas) }} pengaduan menunggu ditindaklanjuti, diurutkan dari yang paling mendesak.
                </p>
            </div>
            <a href="{{ route('pengaduan.index') }}"
                class="rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Lihat Semua Pengaduan
            </a>
        </div>

        @if (empty($isuPrioritas))
            <x-sim.empty-state judul="Tidak ada pengaduan terbuka"
                pesan="Seluruh pengaduan di kawasan ini sudah selesai ditangani." />
        @else
            {{-- Tabel untuk layar lebar --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nomor</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Perihal</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Satuan Permukiman</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Prioritas</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($isuPrioritas as $isu)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $isu['nomor_pengaduan'] }}
                                </td>
                                <td class="px-5 py-3">
                                    <a href="{{ route('pengaduan.detail', $isu['id_pengaduan']) }}"
                                        class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                        {{ $isu['judul'] }}
                                    </a>
                                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                        {{ $isu['kategori'] }}
                                    </p>
                                </td>
                                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                    {{ $isu['satuan_permukiman'] }}
                                </td>
                                <td class="px-5 py-3">
                                    <x-sim.status-badge
                                        :status="\App\Enums\PrioritasPengaduan::from($isu['prioritas'])" />
                                </td>
                                <td class="px-5 py-3">
                                    <x-sim.status-badge :status="\App\Enums\StatusPengaduan::from($isu['status'])" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Daftar kartu untuk layar sempit, mencegah gulir mendatar --}}
            <div class="divide-y divide-gray-200 md:hidden dark:divide-gray-800">
                @foreach ($isuPrioritas as $isu)
                    <div class="p-4">
                        <a href="{{ route('pengaduan.detail', $isu['id_pengaduan']) }}"
                            class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                            {{ $isu['judul'] }}
                        </a>
                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $isu['nomor_pengaduan'] }} &middot; {{ $isu['satuan_permukiman'] }}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <x-sim.status-badge :status="\App\Enums\PrioritasPengaduan::from($isu['prioritas'])"
                                ukuran="sm" />
                            <x-sim.status-badge :status="\App\Enums\StatusPengaduan::from($isu['status'])"
                                ukuran="sm" />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <x-sim.judul-bagian judul="Perbandingan Antar Satuan Permukiman"
        keterangan="Menempatkan keenam permukiman berdampingan untuk melihat yang tertinggal." />

    {{--
        Perbandingan antar SP. Inilah satu-satunya grafik yang sumbunya berupa
        satuan permukiman, sehingga di sinilah penelusuran klik dipasang
        (agents/rules.md bagian 11 poin 5). Grafik lain bersumbu tahun atau
        kategori, yang tidak dapat diterjemahkan menjadi satu SP tertentu.
    --}}
    <div>
        <x-sim.chart-card id="grafikPerSp" judul="Perbandingan Antar Satuan Permukiman"
            keterangan="Klik salah satu batang untuk membuka rincian satuan permukiman tersebut." tinggi="360">
            <x-slot:tabel>
                <table class="w-full text-left text-theme-xs">
                    <thead class="border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Satuan Permukiman</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">KK</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Rumah Terhuni</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Luas Lahan (ha)</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Panen (ton)</th>
                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Rincian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($rekapSp as $baris)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-white/90">
                                    {{ $baris['satuan_permukiman'] }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($baris['jumlah_kk'], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($baris['rumah_terhuni'], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($baris['luas_lahan'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($baris['volume_panen'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2">
                                    {{-- Tautan teks menyediakan jalur setara bagi pengguna keyboard,
                                         karena klik pada batang grafik tidak dapat dijangkau Tab (R-32) --}}
                                    <a href="{{ route('dashboard.sp', $baris['satuan_permukiman_id']) }}"
                                        class="rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                        Buka rincian
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot:tabel>
        </x-sim.chart-card>
    </div>

    @push('scripts')
        <script type="module">
            // Data disuntikkan dari PHP sebagai JSON agar berkas Blade tidak
            // menyusun string JavaScript secara manual.
            const data = @json($dataGrafik);

            const { buatGrafik, angka, rupiah, angkaSingkat, warnaKondisi, drilldownSp } = window.grafikSim;

            // Indikator 1, 2, 3: pertumbuhan penduduk
            buatGrafik('grafikPenduduk', {
                chart: { type: 'line', height: 340 },
                series: [
                    { name: 'Jiwa', data: data.jiwa },
                    { name: 'Kepala Keluarga', data: data.kk },
                    { name: 'Petani', data: data.petani },
                ],
                stroke: { curve: 'smooth', width: 2.5 },
                markers: { size: 0, hover: { size: 5 } },
                xaxis: { categories: data.tahun },
                yaxis: { labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 0) + ' orang' } },
            });

            // Indikator 9: komoditas utama
            buatGrafik('grafikKomoditas', {
                chart: { type: 'donut', height: 340 },
                series: data.komoditasNilai,
                labels: data.komoditasNama,
                plotOptions: {
                    pie: {
                        donut: {
                            size: '62%',
                            labels: {
                                show: true,
                                total: { show: true, label: 'Total', formatter: () => angka(data.komoditasNilai.reduce((a, b) => a + b, 0), 1) + ' t' },
                            },
                        },
                    },
                },
                tooltip: { y: { formatter: (v) => angka(v, 1) + ' ton' } },
            });

            // Indikator 4: pendapatan keluarga
            buatGrafik('grafikPendapatan', {
                chart: { type: 'bar', height: 300 },
                series: [{ name: 'Pendapatan', data: data.pendapatan }],
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.tahun },
                yaxis: { labels: { formatter: (v) => angkaSingkat(v) } },
                tooltip: { y: { formatter: (v) => rupiah(v) + ' per bulan' } },
            });

            // Indikator 5: KK masuk dan keluar
            buatGrafik('grafikMutasiKk', {
                chart: { type: 'bar', height: 300 },
                series: [
                    { name: 'KK Masuk', data: data.kkMasuk },
                    { name: 'KK Keluar', data: data.kkKeluar },
                ],
                colors: ['#33809C', '#C09546'],
                plotOptions: { bar: { columnWidth: '60%', borderRadius: 3, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.tahun },
                yaxis: { labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 0) + ' KK' } },
            });

            // Indikator 14: rekap penghuni kawasan
            buatGrafik('grafikPenghuni', {
                chart: { type: 'donut', height: 300 },
                series: data.penghuniNilai,
                labels: data.penghuniNama,
                colors: ['#12b76a', '#f79009', '#98a2b3', '#667085'],
                plotOptions: { pie: { donut: { size: '62%' } } },
                tooltip: { y: { formatter: (v) => angka(v, 0) + ' KK' } },
            });

            // Indikator 7: pekerjaan kepala keluarga, histogram mendatar
            buatGrafik('grafikPekerjaan', {
                chart: { type: 'bar', height: 320 },
                series: [{ name: 'Kepala Keluarga', data: data.pekerjaanNilai }],
                plotOptions: { bar: { horizontal: true, barHeight: '62%', borderRadius: 4, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.pekerjaanNama, labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 0) + ' KK' } },
            });

        // Indikator 17: pengaduan per status
        buatGrafik('grafikStatusPengaduan', {
            chart: { type: 'donut', height: 320 },
            series: data.statusPengaduanNilai,
            labels: data.statusPengaduanNama,
            plotOptions: {
                pie: {
                    donut: {
                        size: '62%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: () => angka(data.statusPengaduanNilai.reduce((a, b) => a + b, 0), 0),
                            },
                        },
                    },
                },
            },
            legend: { position: 'bottom' },
            tooltip: { y: { formatter: (v) => angka(v, 0) + ' pengaduan' } },
        });

            // Indikator 10: volume panen per tahun
            buatGrafik('grafikPanen', {
                chart: { type: 'bar', height: 320 },
                series: [{ name: 'Volume Panen', data: data.volumePanen }],
                colors: ['#265F73'],
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.tahun },
                yaxis: { labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 3) + ' ton' } },
            });

            // Indikator 11: harga rata-rata
            buatGrafik('grafikHarga', {
                chart: { type: 'line', height: 320 },
                series: [{ name: 'Harga Rata-rata', data: data.harga }],
                colors: ['#C09546'],
                stroke: { curve: 'smooth', width: 2.5 },
                markers: { size: 0, hover: { size: 5 } },
                xaxis: { categories: data.tahun },
                yaxis: { labels: { formatter: (v) => angkaSingkat(v) } },
                tooltip: { y: { formatter: (v) => rupiah(v) + ' per ton' } },
            });

            // Indikator 12: status infrastruktur, batang bertumpuk
            buatGrafik('grafikInfrastruktur', {
                chart: { type: 'bar', height: 320, stacked: true },
                series: [
                    { name: 'Baik', data: data.infraBaik },
                    { name: 'Rusak Ringan', data: data.infraRusakRingan },
                    { name: 'Rusak Berat', data: data.infraRusakBerat },
                ],
                colors: [warnaKondisi.baik, warnaKondisi.rusakRingan, warnaKondisi.rusakBerat],
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 3, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.infraJenis },
                yaxis: { labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 0) + ' aset' } },
            });

            // Perbandingan antar SP. Satu-satunya grafik bersumbu satuan
            // permukiman, sehingga klik pada batangnya dapat diterjemahkan
            // menjadi satu SP tertentu.
            buatGrafik('grafikPerSp', {
                chart: {
                    type: 'bar',
                    height: 360,
                    events: { dataPointSelection: drilldownSp(data.spId) },
                },
                series: [
                    { name: 'Kepala Keluarga', data: data.spKk },
                    { name: 'Volume Panen (ton)', data: data.spPanen },
                ],
                plotOptions: { bar: { columnWidth: '60%', borderRadius: 4, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.spNama, labels: { rotate: -35, trim: true, hideOverlappingLabels: false } },
                yaxis: { labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 2) } },
                // Kursif penunjuk memberi petunjuk bahwa batang dapat diklik
                states: { active: { filter: { type: 'darken', value: 0.85 } } },
            });
        </script>
    @endpush
@endsection
