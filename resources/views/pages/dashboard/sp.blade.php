{{--
    Rincian satu satuan permukiman.

    Halaman tujuan penelusuran (drill-down) dari dashboard kawasan, memenuhi
    kewajiban agar rekap gabungan dapat diklik untuk melihat rincian per SP
    (agents/rules.md bagian 11 poin 5).

    Komposisi mengikuti pola halaman detail pada dial RITME 2: ringkasan
    entitas menetap di kiri, konten bertab di kanan (agents/ui-spec.md 2.2).
    Ini sengaja dibedakan dari dashboard kawasan yang memakai grid grafik,
    agar pengguna langsung tahu sedang berada di jenis halaman yang berbeda.

    Seluruh angka masih data contoh. Deret tahunan per SP diturunkan secara
    proporsional dari deret kawasan, bukan pendataan sebenarnya, dan diganti
    query nyata pada Task 9.1.
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `dashboard.sp`.
        Lihat routes/web.php.
    --}}

    <x-sim.page-header :judul="$sp['nama']"
        :keterangan="'Desa ' . $sp['desa'] . ', Kecamatan ' . $sp['kecamatan'] . ', Kawasan ' . $sp['kawasan'] . '.'"
        {{--
            Menempel pada menu Satuan Permukiman, BUKAN pada Dashboard.

            Sebelumnya remah ini menunjuk `/`, sehingga terbaca
            "Beranda / Dashboard / SP Kapitan Meo". Alasan yang dulu ditulis,
            bahwa halaman ini menyajikan rekap kawasan per SP, tidak cocok
            dengan isinya: yang ditampilkan adalah profil SP beserta
            transmigran, rumah, lahan, panen, dan pengaduan MILIK SP itu.
            Ia halaman rincian satuan permukiman, dan letaknya di menu
            mengikuti isinya, bukan mengikuti alamat rutenya.

            Alamat `/dashboard/sp/{id}` sengaja dibiarkan: 21 tempat menautkan
            ke sana, dan memindahkannya ke `/sp/{id}` berisiko tertangkap
            rute `/sp/inventaris` serta `/sp/fasilitas` yang sudah ada.
        --}}
        :remah="\App\Helpers\RemahHelper::untuk('/sp', $sp['nama'])">
        <x-slot:aksi>
            <a href="{{ route('beranda') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Kembali ke Dashboard Kawasan
            </a>
        </x-slot:aksi>
    </x-sim.page-header>

    {{-- Pindah cepat antar SP tanpa kembali ke dashboard lebih dulu --}}
    <nav aria-label="Pindah satuan permukiman"
        class="mb-6 flex gap-2 overflow-x-auto rounded-2xl border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-white/[0.03]">
        @foreach ($daftarSp as $lain)
            @php $aktif = $lain['id_satuan_permukiman'] === $sp['id_satuan_permukiman']; @endphp
            <a href="{{ route('dashboard.sp', $lain['id_satuan_permukiman']) }}"
                @if ($aktif) aria-current="page" @endif
                class="shrink-0 rounded-lg px-3 py-2 text-theme-xs font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 {{ $aktif
                    ? 'bg-brand-500 text-white'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}">
                {{ $lain['nama'] }}
            </a>
        @endforeach
    </nav>

    <div class="grid gap-6 lg:grid-cols-[19rem_1fr]">
        {{-- Kolom kiri: profil SP yang menetap --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Profil Satuan Permukiman</h2>

                <dl class="mt-4 space-y-3 text-theme-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Kode SP</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $sp['kode_sp'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Tahun penempatan</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $sp['tahun_penempatan'] }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Luas lahan</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($sp['luas_lahan'], 2, ',', '.') }} ha
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Daya tampung</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($sp['jumlah_kk_terisi'], 0, ',', '.') }} dari
                            {{ number_format($sp['jumlah_kk_rencana'], 0, ',', '.') }} KK
                        </dd>
                    </div>
                    {{--
                        Catatan dan dokumen penetapan, ditambahkan 2026-08-25.

                        Form SP sudah lama menyediakan kedua isian ini, tetapi
                        halaman rincian SP adalah halaman ini, dan ia tidak
                        pernah menampilkannya. Akibatnya SK penempatan yang
                        diunggah petugas tidak punya cara dibuka kembali, persis
                        keadaan yang sudah dibereskan di modul lain (R-26).
                    --}}
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Dokumen penetapan</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            <x-sim.tautan-dokumen modul="satuan_permukiman"
                                :id="$sp['id_satuan_permukiman']"
                                :berkas="$sp['dokumen_pendukung'] ?? null" />
                        </dd>
                    </div>
                </dl>

                <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">Catatan</p>
                    <p class="mt-0.5 text-theme-sm leading-relaxed text-gray-800 dark:text-white/90">
                        {{ $sp['keterangan'] ?? 'Tidak ada catatan tambahan.' }}
                    </p>
                </div>

                {{-- Keterisian ditampilkan sebagai batang, bukan grafik utuh,
                     karena hanya satu angka yang perlu dibandingkan --}}
                <div class="mt-4">
                    <div class="mb-1.5 flex items-center justify-between text-theme-xs">
                        <span class="text-gray-500 dark:text-gray-400">Keterisian</span>
                        <span class="font-medium tabular-nums text-gray-800 dark:text-white/90">{{ $persenIsi }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10"
                        role="img" aria-label="Keterisian {{ $persenIsi }} persen dari rencana">
                        <div class="h-full rounded-full bg-brand-500" style="width: {{ $persenIsi }}%"></div>
                    </div>
                </div>

                <h3 class="mt-6 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Titik Koordinat</h3>
                <p class="mt-2 text-theme-xs tabular-nums text-gray-700 dark:text-gray-300">
                    {{ number_format($sp['lintang'], 6, '.', '') }}, {{ number_format($sp['bujur'], 6, '.', '') }}
                </p>
                        <x-sim.tautan-peta class="mt-1.5" :lintang="$sp['lintang']" :bujur="$sp['bujur']"
                            :label="$sp['nama']" />
            </div>
        </aside>

        {{-- Kolom kanan: indikator dan rincian data --}}
        <div class="min-w-0 space-y-6">
            {{-- Kartu indikator SP --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-sim.stat-card label="Kepala Keluarga"
                    :nilai="number_format($rekap['jumlah_kk'], 0, ',', '.')" satuan="KK" />

                <x-sim.stat-card label="Rumah Terhuni"
                    :nilai="number_format($rekap['rumah_terhuni'], 0, ',', '.')"
                    :keterangan="$persenHuni . '% dari KK terdata'" />

                <x-sim.stat-card label="Luas Lahan"
                    :nilai="number_format($rekap['luas_lahan'], 2, ',', '.')" satuan="ha" />

                <x-sim.stat-card label="Volume Panen"
                    :nilai="number_format($rekap['volume_panen'], 2, ',', '.')" satuan="ton" />
            </div>

            {{-- Dua grafik ringkas khusus SP ini --}}
            <div class="grid gap-6 xl:grid-cols-2">
                <x-sim.chart-card id="grafikKkSp" judul="Pertumbuhan Kepala Keluarga"
                    keterangan="Perkiraan porsi SP ini terhadap deret kawasan." tinggi="260">
                    <x-slot:tabel>
                        <table class="w-full text-left text-theme-xs">
                            <caption class="sr-only">Pertumbuhan kepala keluarga satuan permukiman ini</caption>
                            <thead class="border-b border-gray-200 dark:border-gray-800">
                                <tr>
                                    <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                                    <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">KK</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                @foreach ($deretSp['tahun'] as $i => $tahun)
                                    <tr>
                                        <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">
                                            {{ $tahun }}</td>
                                        <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                            {{ number_format($deretSp['jumlah_kk'][$i], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </x-slot:tabel>
                </x-sim.chart-card>

                <x-sim.chart-card id="grafikPanenSp" judul="Volume Panen per Tahun"
                    keterangan="Seluruh komoditas dikonversi ke ton." tinggi="260">
                    <x-slot:tabel>
                        <table class="w-full text-left text-theme-xs">
                            <caption class="sr-only">Volume panen satuan permukiman ini per tahun</caption>
                            <thead class="border-b border-gray-200 dark:border-gray-800">
                                <tr>
                                    <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                                    <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Volume (ton)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                @foreach ($deretSp['tahun'] as $i => $tahun)
                                    <tr>
                                        <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">
                                            {{ $tahun }}</td>
                                        <td class="px-3 py-2 tabular-nums text-gray-600 dark:text-gray-400">
                                            {{ number_format($deretSp['volume_panen'][$i], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </x-slot:tabel>
                </x-sim.chart-card>
            </div>

            {{--
                Kondisi layanan dasar SP ini, indikator ke-16.
                Rincian pembentuk skor wajib ditampilkan agar petugas tahu
                penyebabnya, bukan hanya labelnya (agents/rules.md 10c.1 poin 4).
            --}}
            <x-sim.rincian-kondisi-sp :penilaian="$penilaian" />

            {{--
                Keadaan Wilayah (Bab II Laporan Monografi), ditambahkan
                2026-08-28 (Rombongan C). Seluruhnya dokumenter dan opsional;
                bagian yang belum diisi tetap dirender sebagai "belum dicatat"
                agar petugas tahu apa yang kurang untuk laporan.
            --}}
            @php
                $adaKeadaan = collect([
                    'lintang_utara', 'jarak_ke_kecamatan_km', 'batas_utara', 'nomor_sk_pencadangan',
                    'pola_permukiman', 'tingkat_kesuburan_tanah', 'bentuk_wilayah', 'curah_hujan_tahunan_mm',
                    'suhu_rata_c', 'sumber_air_bersih',
                ])->contains(fn ($k) => ($sp[$k] ?? null) !== null && $sp[$k] !== '');

                $rentang = fn ($min, $maks, $satuan = '') => ($sp[$min] ?? null) !== null || ($sp[$maks] ?? null) !== null
                    ? trim(rtrim(rtrim(number_format((float) ($sp[$min] ?? 0), 2, ',', '.'), '0'), ',')
                        . ' sampai ' . rtrim(rtrim(number_format((float) ($sp[$maks] ?? 0), 2, ',', '.'), '0'), ',') . ' ' . $satuan)
                    : null;
            @endphp
            <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Keadaan Wilayah</h2>
                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                    Bahan Bab II Laporan Monografi SP. Bagian yang belum diisi ditandai "belum dicatat".
                </p>

                @if (! $adaKeadaan)
                    <p class="mt-4 rounded-lg bg-gray-50 px-4 py-6 text-center text-theme-sm text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                        Data keadaan wilayah belum dicatat. Lengkapi lewat tombol Ubah agar Laporan Monografi SP lengkap.
                    </p>
                @else
                    @php
                        $kelompokKeadaan = [
                            'Letak' => [
                                'Letak astronomis' => ($sp['lintang_utara'] ?? null) !== null
                                    ? number_format((float) $sp['lintang_utara'], 6) . ' sampai ' . number_format((float) $sp['lintang_selatan'], 6) . ' LS, '
                                        . number_format((float) $sp['bujur_barat'], 6) . ' sampai ' . number_format((float) $sp['bujur_timur'], 6) . ' BT'
                                    : null,
                                'Jarak ke Ibu Kota Kecamatan' => ($sp['jarak_ke_kecamatan_km'] ?? null) !== null ? rtrim(rtrim(number_format((float) $sp['jarak_ke_kecamatan_km'], 1, ',', '.'), '0'), ',') . ' km' : null,
                                'Jarak ke Ibu Kota Kabupaten' => ($sp['jarak_ke_kabupaten_km'] ?? null) !== null ? rtrim(rtrim(number_format((float) $sp['jarak_ke_kabupaten_km'], 1, ',', '.'), '0'), ',') . ' km' : null,
                                'Jarak ke Ibu Kota Provinsi' => ($sp['jarak_ke_provinsi_km'] ?? null) !== null ? rtrim(rtrim(number_format((float) $sp['jarak_ke_provinsi_km'], 1, ',', '.'), '0'), ',') . ' km' : null,
                            ],
                            'Batas Wilayah' => [
                                'Sebelah Utara' => $sp['batas_utara'] ?? null,
                                'Sebelah Timur' => $sp['batas_timur'] ?? null,
                                'Sebelah Selatan' => $sp['batas_selatan'] ?? null,
                                'Sebelah Barat' => $sp['batas_barat'] ?? null,
                            ],
                            'Luas dan Bentuk' => [
                                'Nomor SK Pencadangan Areal' => $sp['nomor_sk_pencadangan'] ?? null,
                                'Tanggal SK Pencadangan' => ($sp['tanggal_sk_pencadangan'] ?? null)
                                    ? \Illuminate\Support\Carbon::parse($sp['tanggal_sk_pencadangan'])->translatedFormat('d F Y') : null,
                                'Pola permukiman' => $sp['pola_permukiman'] ?? null,
                            ],
                            'Tanah dan Topografi' => [
                                'Tingkat kesuburan tanah' => $sp['tingkat_kesuburan_tanah'] ?? null,
                                'pH tanah' => $rentang('ph_tanah_min', 'ph_tanah_maks'),
                                'Bentuk wilayah' => $sp['bentuk_wilayah'] ?? null,
                                'Kemiringan lereng' => $rentang('kemiringan_min_persen', 'kemiringan_maks_persen', '%'),
                            ],
                            'Iklim' => [
                                'Curah hujan rata-rata per tahun' => ($sp['curah_hujan_tahunan_mm'] ?? null) !== null
                                    ? number_format((float) $sp['curah_hujan_tahunan_mm'], 2, ',', '.') . ' mm' : null,
                                'Curah hujan bulanan' => $rentang('curah_hujan_bulan_min_mm', 'curah_hujan_bulan_maks_mm', 'mm'),
                                'Suhu udara' => $rentang('suhu_min_c', 'suhu_maks_c', 'derajat C')
                                    . (($sp['suhu_rata_c'] ?? null) !== null ? ', rata-rata ' . rtrim(rtrim(number_format((float) $sp['suhu_rata_c'], 1, ',', '.'), '0'), ',') . ' derajat C' : ''),
                                'Kecepatan angin' => $rentang('angin_min_knot', 'angin_maks_knot', 'knot')
                                    . (($sp['angin_rata_knot'] ?? null) !== null ? ', rata-rata ' . rtrim(rtrim(number_format((float) $sp['angin_rata_knot'], 1, ',', '.'), '0'), ',') . ' knot' : ''),
                                'Penyinaran matahari' => $rentang('penyinaran_min_persen', 'penyinaran_maks_persen', '%')
                                    . (($sp['penyinaran_rata_persen'] ?? null) !== null ? ', rata-rata ' . rtrim(rtrim(number_format((float) $sp['penyinaran_rata_persen'], 1, ',', '.'), '0'), ',') . '%' : ''),
                            ],
                            'Sumberdaya Air' => [
                                'Sumber air bersih' => $sp['sumber_air_bersih'] ?? null,
                                'Sumber air pertanian' => $sp['sumber_air_pertanian'] ?? null,
                            ],
                        ];
                    @endphp

                    <div class="mt-4 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                        @foreach ($kelompokKeadaan as $judulKelompok => $isi)
                            <div>
                                <h3 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $judulKelompok }}</h3>
                                <dl class="mt-2 space-y-2 text-theme-sm">
                                    @foreach ($isi as $label => $nilai)
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                            <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                                                {{ $nilai !== null && trim((string) $nilai) !== '' ? $nilai : 'belum dicatat' }}
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Rute Aksesibilitas (Tabel 2.1 Monografi, Stage C2) --}}
                <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-800">
                    <h3 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rute Aksesibilitas</h3>
                    @if (count($ruteAksesibilitas) === 0)
                        <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">Belum dicatat.</p>
                    @else
                        <div class="mt-3 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                            <table class="min-w-full text-theme-sm">
                                <caption class="px-4 py-2.5 text-left text-theme-xs text-gray-500 dark:text-gray-400">
                                    Cara pencapaian menuju {{ $sp['nama'] }}
                                </caption>
                                <thead class="border-y border-gray-200 bg-gray-50 text-theme-xs text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-4 py-2 text-left">Rute</th>
                                        <th scope="col" class="px-4 py-2 text-right">Jarak</th>
                                        <th scope="col" class="px-4 py-2 text-left">Sarana</th>
                                        <th scope="col" class="px-4 py-2 text-left">Kondisi Jalan</th>
                                        <th scope="col" class="px-4 py-2 text-left">Waktu</th>
                                        <th scope="col" class="px-4 py-2 text-right">Ongkos</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($ruteAksesibilitas as $r)
                                        <tr class="text-gray-700 dark:text-gray-300">
                                            <td class="px-4 py-2 font-medium text-gray-800 dark:text-white/90">
                                                {{ $r['rute'] }}
                                                @if (! empty($r['keterangan']))
                                                    <span class="mt-0.5 block text-theme-xs font-normal text-gray-500 dark:text-gray-400">{{ $r['keterangan'] }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-right tabular-nums">{{ $r['jarak_km'] !== null ? rtrim(rtrim(number_format((float) $r['jarak_km'], 1, ',', '.'), '0'), ',') . ' km' : '-' }}</td>
                                            <td class="px-4 py-2">{{ $r['sarana_angkutan'] ?? '-' }}</td>
                                            <td class="px-4 py-2">{{ $r['kondisi_jalan'] ?? '-' }}</td>
                                            <td class="px-4 py-2">{{ $r['waktu_tempuh'] ?? '-' }}</td>
                                            <td class="px-4 py-2 text-right tabular-nums">{{ $r['ongkos_rp'] !== null ? 'Rp ' . number_format((float) $r['ongkos_rp'], 0, ',', '.') : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

            {{--
                Rincian data SP dalam tab, agar halaman tidak memanjang
                (agents/rules.md bagian 13.2 poin 2).
            --}}
            <div x-data="hashTabs('transmigran')"
                class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian data satuan permukiman">
                    @foreach ([
                        'transmigran' => 'Transmigran (' . count($transmigran) . ')',
                        'rumah' => 'Rumah (' . count($rumah) . ')',
                        'lahan' => 'Lahan (' . count($lahan) . ')',
                        'panen' => 'Panen (' . count($panen) . ')',
                        'pengaduan' => 'Pengaduan (' . count($pengaduan) . ')',
                        'infrastruktur' => 'Infrastruktur (' . count($infrastruktur) . ')',
                    ] as $kunci => $label)
                        <button type="button" role="tab" @click="setTab('{{ $kunci }}')"
                            :aria-selected="tab === '{{ $kunci }}'"
                            :class="tab === '{{ $kunci }}'
                                ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                            class="shrink-0 border-b-2 px-4 py-2.5 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Transmigran --}}
                <div x-show="tab === 'transmigran'" role="tabpanel">
                    @if (empty($transmigran))
                        <x-sim.empty-state judul="Belum ada data transmigran"
                            :pesan="'Data kepala keluarga di ' . $sp['nama'] . ' akan tampil di sini setelah ditambahkan.'" />
                    @else
                        <x-sim.tabel-ringkas judul="Transmigran di satuan permukiman ini" :kolom="['Nama', 'NIK', 'Pekerjaan']">
                            @foreach ($transmigran as $baris)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('transmigran.detail', $baris['id_transmigran']) }}"
                                            class="rounded text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                            {{ $baris['nama_kepala_keluarga'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $baris['nik'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $baris['pekerjaan_kepala_keluarga'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{-- Rumah --}}
                <div x-show="tab === 'rumah'" x-cloak role="tabpanel">
                    @if (empty($rumah))
                        <x-sim.empty-state judul="Belum ada data rumah"
                            pesan="Data rumah akan tampil di sini setelah ditambahkan." />
                    @else
                        <x-sim.tabel-ringkas judul="Rumah di satuan permukiman ini" :kolom="['Nomor', 'Penghuni', 'Kondisi', 'Status Hunian']">
                            @foreach ($rumah as $baris)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('rumah.detail', $baris['id_rumah']) }}"
                                            class="rounded text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                            {{ $baris['no_rumah'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $baris['penghuni'] ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-sim.status-badge
                                            :status="\App\Enums\KondisiRumah::from($baris['kondisi'])" />
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-sim.status-badge
                                            :status="\App\Enums\StatusHunian::from($baris['status_hunian'])"
                                            :catatan="$baris['alasan_tidak_dihuni'] ?? null" />
                                    </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{-- Lahan --}}
                <div x-show="tab === 'lahan'" x-cloak role="tabpanel">
                    @if (empty($lahan))
                        <x-sim.empty-state judul="Belum ada data lahan"
                            pesan="Data lahan akan tampil di sini setelah ditambahkan." />
                    @else
                        <x-sim.tabel-ringkas judul="Lahan di satuan permukiman ini" :kolom="['Kode', 'Pemilik', 'Jenis', 'Luas (ha)']">
                            @foreach ($lahan as $baris)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('lahan.detail', $baris['id_lahan']) }}"
                                            class="rounded text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                            {{ $baris['kode_lahan'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $baris['pemilik'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $baris['peruntukan_lahan'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($baris['luas'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{-- Hasil panen --}}
                <div x-show="tab === 'panen'" x-cloak role="tabpanel">
                    @if (empty($panen))
                        <x-sim.empty-state judul="Belum ada data panen"
                            pesan="Hasil panen akan tampil di sini setelah dicatat." />
                    @else
                        <x-sim.tabel-ringkas judul="Hasil panen di satuan permukiman ini" :kolom="['Komoditas', 'Kelompok Tani', 'Periode Panen', 'Produksi']">
                            @foreach ($panen as $baris)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('panen.detail', $baris['id_hasil_panen']) }}"
                                            class="rounded text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                            {{ $baris['komoditas'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $baris['poktan'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ \Illuminate\Support\Carbon::parse($baris['periode_panen'] . '-01')->translatedFormat('F Y') }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($baris['produksi'], 3, ',', '.') }} {{ $baris['satuan'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{-- Pengaduan --}}
                <div x-show="tab === 'pengaduan'" x-cloak role="tabpanel">
                    @if (empty($pengaduan))
                        <x-sim.empty-state judul="Belum ada pengaduan"
                            :pesan="'Tidak ada pengaduan tercatat dari ' . $sp['nama'] . '.'" />
                    @else
                        <x-sim.tabel-ringkas judul="Pengaduan di satuan permukiman ini" :kolom="['Nomor', 'Perihal', 'Prioritas', 'Status']">
                            @foreach ($pengaduan as $baris)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $baris['nomor_pengaduan'] }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <a href="{{ route('pengaduan.detail', $baris['id_pengaduan']) }}"
                                            class="rounded text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                            {{ $baris['judul'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-sim.status-badge
                                            :status="\App\Enums\PrioritasPengaduan::from($baris['prioritas'])" />
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-sim.status-badge
                                            :status="\App\Enums\StatusPengaduan::from($baris['status'])" />
                                    </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{-- Infrastruktur --}}
                <div x-show="tab === 'infrastruktur'" x-cloak role="tabpanel">
                    @if (empty($infrastruktur))
                        <x-sim.empty-state judul="Belum ada data infrastruktur"
                            pesan="Aset infrastruktur akan tampil di sini setelah didata." />
                    @else
                        <x-sim.tabel-ringkas judul="Infrastruktur di satuan permukiman ini" :kolom="['Nama', 'Jenis', 'Tahun', 'Kondisi']">
                            @foreach ($infrastruktur as $baris)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                                        {{ $baris['nama'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $baris['jenis'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $baris['tahun_perolehan'] }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-sim.status-badge :status="\App\Enums\Kondisi::from($baris['kondisi'])" />
                                    </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script type="module">
            const data = @json($dataGrafik);
            const { buatGrafik, angka } = window.grafikSim;

            buatGrafik('grafikKkSp', {
                chart: { type: 'line', height: 260 },
                series: [{ name: 'Kepala Keluarga', data: data.kk }],
                stroke: { curve: 'smooth', width: 2.5 },
                markers: { size: 0, hover: { size: 5 } },
                xaxis: { categories: data.tahun },
                yaxis: { labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 0) + ' KK' } },
            });

            buatGrafik('grafikPanenSp', {
                chart: { type: 'bar', height: 260 },
                series: [{ name: 'Volume Panen', data: data.panen }],
                colors: ['#265F73'],
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.tahun },
                yaxis: { labels: { formatter: (v) => angka(v, 0) } },
                tooltip: { y: { formatter: (v) => angka(v, 2) + ' ton' } },
            });
        </script>
    @endpush
@endsection
