{{--
    Rincian satu satuan permukiman.

    Memakai pola dua kolom asimetris pada dial RITME 2: ringkasan identitas
    SP menetap di kiri, konten bertab di kanan (agents/ui-spec.md 2.2).

    Seluruh data dikelompokkan ke dalam sistem Tab Domain terpadu agar
    pengguna tidak perlu menggulir panjang dan seluruh dimensi data SP
    (kependudukan, perumahan, pertanian, aset, pengaduan, dan monografi)
    dapat diakses secara instan.
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `sp.detail`.
        Lihat routes/web.php.
    --}}

    <x-sim.page-header :judul="$sp['nama']"
        :keterangan="'Desa ' . $sp['desa'] . ' • Kecamatan ' . $sp['kecamatan'] . ' • Kawasan ' . $sp['kawasan']"
        :remah="\App\Helpers\RemahHelper::untuk('/sp', $sp['nama'])">
        <x-slot:aksi>
            <a href="{{ route('sp.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Kembali ke Daftar SP
            </a>

            <button type="button" @click="$dispatch('buka-modal', 'formUbahSp')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                </svg>
                Ubah Data SP
            </button>
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
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

                {{-- Keterisian ditampilkan sebagai progress bar --}}
                <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <div class="mb-1.5 flex items-center justify-between text-theme-xs">
                        <span class="text-gray-500 dark:text-gray-400">Keterisian Daya Tampung</span>
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

        {{-- Kolom kanan: Sistem Tab Domain Terpadu --}}
        <div class="min-w-0 space-y-6">
            <div x-data="hashTabs('ringkasan')"
                class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">

                {{-- Tab list header --}}
                <div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian domain data satuan permukiman">
                    @foreach ([
                        'ringkasan' => 'Ringkasan & Kondisi',
                        'warga' => 'Warga & Hunian (' . count($transmigran) . ')',
                        'pertanian' => 'Pertanian & Lahan (' . (count($lahan) + count($panen)) . ')',
                        'aset' => 'Aset & Fasilitas (' . (count($infrastruktur) + count($fasilitas) + count($inventaris)) . ')',
                        'pengaduan' => 'Pengaduan (' . count($pengaduan) . ')',
                        'monografi' => 'Keadaan Wilayah',
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

                {{-- ====================================================================== --}}
                {{-- TAB 1: Ringkasan & Kondisi --}}
                {{-- ====================================================================== --}}
                <div x-show="tab === 'ringkasan'" role="tabpanel" class="p-5 space-y-6 sm:p-6">
                    {{-- 4 Stat Cards KPI --}}
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

                    {{-- 2 Grafik Tren SP --}}
                    <div class="grid gap-6 xl:grid-cols-2">
                        <x-sim.chart-card id="grafikKkSp" judul="Pertumbuhan Kepala Keluarga"
                            keterangan="Perkembangan jumlah kepala keluarga di satuan permukiman ini." tinggi="260">
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

                    {{-- Kondisi Layanan Dasar SP (16 Parameter) --}}
                    <x-sim.rincian-kondisi-sp :penilaian="$penilaian" />
                </div>

                {{-- ====================================================================== --}}
                {{-- TAB 2: Warga & Hunian --}}
                {{-- ====================================================================== --}}
                <div x-show="tab === 'warga'" x-cloak role="tabpanel" class="p-5 space-y-6 sm:p-6">
                    {{-- Warga Transmigran --}}
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                                Warga Transmigran ({{ count($transmigran) }} KK)
                            </h3>
                            <a href="{{ route('transmigran.index', ['sp' => $sp['id_satuan_permukiman']]) }}"
                                class="text-theme-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                Buka di Halaman Transmigran &rarr;
                            </a>
                        </div>
                        @if (empty($transmigran))
                            <x-sim.empty-state judul="Belum ada data transmigran"
                                :pesan="'Data kepala keluarga di ' . $sp['nama'] . ' akan tampil di sini setelah ditambahkan.'" />
                        @else
                            <x-sim.tabel-ringkas judul="Daftar transmigran di SP ini" :kolom="['Nama Kepala Keluarga', 'NIK', 'Pekerjaan']">
                                @foreach ($transmigran as $baris)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                        <td class="px-5 py-3">
                                            <a href="{{ route('transmigran.detail', $baris['id_transmigran']) }}"
                                                class="rounded font-medium text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
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

                    {{-- Rumah & Hunian --}}
                    <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                                Rumah & Hunian ({{ count($rumah) }} Unit)
                            </h3>
                            <a href="{{ route('rumah.index', ['sp' => $sp['id_satuan_permukiman']]) }}"
                                class="text-theme-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                Buka di Halaman Rumah &rarr;
                            </a>
                        </div>
                        @if (empty($rumah))
                            <x-sim.empty-state judul="Belum ada data rumah"
                                pesan="Data rumah akan tampil di sini setelah ditambahkan." />
                        @else
                            <x-sim.tabel-ringkas judul="Daftar rumah di SP ini" :kolom="['Nomor Rumah', 'Penghuni', 'Kondisi', 'Status Hunian']">
                                @foreach ($rumah as $baris)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                        <td class="px-5 py-3">
                                            <a href="{{ route('rumah.detail', $baris['id_rumah']) }}"
                                                class="rounded font-medium text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
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
                </div>

                {{-- ====================================================================== --}}
                {{-- TAB 3: Pertanian & Lahan --}}
                {{-- ====================================================================== --}}
                <div x-show="tab === 'pertanian'" x-cloak role="tabpanel" class="p-5 space-y-6 sm:p-6">
                    {{-- Bidang Lahan --}}
                    <div>
                        @php
                            // Satu baris per KELUARGA sejak Putaran 15, sehingga
                            // cacah BIDANG != cacah baris: satu baris dapat memuat
                            // pekarangan dan lahan usaha sekaligus.
                            $jumlahBidangSp = collect($lahan)->filter(fn ($l) => $l['luas_pekarangan'] !== null)->count()
                                + collect($lahan)->filter(fn ($l) => $l['luas_usaha'] !== null)->count();
                        @endphp
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                                Bidang Lahan ({{ $jumlahBidangSp }} Bidang / {{ count($lahan) }} KK)
                            </h3>
                            <a href="{{ route('lahan.index', ['sp' => $sp['id_satuan_permukiman']]) }}"
                                class="text-theme-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                Buka di Halaman Data Lahan &rarr;
                            </a>
                        </div>
                        @if (empty($lahan))
                            <x-sim.empty-state judul="Belum ada data lahan"
                                pesan="Data lahan akan tampil di sini setelah ditambahkan." />
                        @else
                            <x-sim.tabel-ringkas judul="Daftar lahan di SP ini" :kolom="['Kode Lahan', 'Pemilik', 'Pekarangan (ha)', 'Lahan Usaha (ha)', 'Total (ha)']">
                                @foreach ($lahan as $baris)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                        <td class="px-5 py-3">
                                            <a href="{{ route('lahan.detail', $baris['id_lahan']) }}"
                                                class="rounded font-medium text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                                {{ $baris['kode_lahan'] }}
                                            </a>
                                        </td>
                                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                            {{ $baris['pemilik'] }}
                                        </td>
                                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                            {{ $baris['luas_pekarangan'] === null ? 'belum menerima' : number_format($baris['luas_pekarangan'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                            {{ $baris['luas_usaha'] === null ? 'belum menerima' : number_format($baris['luas_usaha'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                            {{ number_format((float) ($baris['luas_pekarangan'] ?? 0) + (float) ($baris['luas_usaha'] ?? 0), 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </x-sim.tabel-ringkas>
                        @endif
                    </div>

                    {{-- Kelompok Tani --}}
                    <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                                Kelompok Tani ({{ count($poktan) }} Poktan)
                            </h3>
                            <a href="{{ route('poktan.index', ['sp' => $sp['id_satuan_permukiman']]) }}"
                                class="text-theme-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                Buka di Halaman Kelompok Tani &rarr;
                            </a>
                        </div>
                        @if (empty($poktan))
                            <x-sim.empty-state judul="Belum ada kelompok tani"
                                pesan="Kelompok tani binaan di SP ini akan tampil di sini setelah didata." />
                        @else
                            <x-sim.tabel-ringkas judul="Daftar kelompok tani di SP ini" :kolom="['Nama Poktan', 'Ketua Kelompok', 'Jumlah Anggota Transmigran']">
                                @foreach ($poktan as $baris)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                        <td class="px-5 py-3">
                                            <a href="{{ route('poktan.detail', $baris['id_poktan']) }}"
                                                class="rounded font-medium text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                                {{ $baris['nama'] }}
                                            </a>
                                        </td>
                                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                            {{ $baris['nama_ketua'] ?? '-' }}
                                        </td>
                                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                            {{ $baris['jumlah_anggota'] ?? 0 }} orang
                                        </td>
                                    </tr>
                                @endforeach
                            </x-sim.tabel-ringkas>
                        @endif
                    </div>

                    {{-- Hasil Panen --}}
                    <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                                Catatan Hasil Panen ({{ count($panen) }} Catatan)
                            </h3>
                            <a href="{{ route('panen.index', ['sp' => $sp['id_satuan_permukiman']]) }}"
                                class="text-theme-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                Buka di Halaman Hasil Panen &rarr;
                            </a>
                        </div>
                        @if (empty($panen))
                            <x-sim.empty-state judul="Belum ada data panen"
                                pesan="Hasil panen akan tampil di sini setelah dicatat." />
                        @else
                            <x-sim.tabel-ringkas judul="Hasil panen di SP ini" :kolom="['Komoditas', 'Kelompok Tani', 'Periode Panen', 'Produksi']">
                                @foreach ($panen as $baris)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                        <td class="px-5 py-3">
                                            <a href="{{ route('panen.detail', $baris['id_hasil_panen']) }}"
                                                class="rounded font-medium text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
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
                </div>

                {{-- ====================================================================== --}}
                {{-- TAB 4: Aset & Fasilitas --}}
                {{-- ====================================================================== --}}
                <div x-show="tab === 'aset'" x-cloak role="tabpanel" class="p-5 space-y-6 sm:p-6">
                    {{-- Infrastruktur SP --}}
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                                Infrastruktur Kawasan ({{ count($infrastruktur) }} Aset)
                            </h3>
                            <a href="{{ route('infrastruktur.index', ['sp' => $sp['id_satuan_permukiman']]) }}"
                                class="text-theme-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                Buka di Halaman Infrastruktur &rarr;
                            </a>
                        </div>
                        @if (empty($infrastruktur))
                            <x-sim.empty-state judul="Belum ada data infrastruktur"
                                pesan="Aset infrastruktur akan tampil di sini setelah didata." />
                        @else
                            <x-sim.tabel-ringkas judul="Infrastruktur di SP ini" :kolom="['Nama Infrastruktur', 'Jenis', 'Tahun', 'Kondisi']">
                                @foreach ($infrastruktur as $baris)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                        <td class="px-5 py-3">
                                            <a href="{{ route('infrastruktur.detail', $baris['id_infrastruktur']) }}"
                                                class="rounded font-medium text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                                {{ $baris['nama'] }}
                                            </a>
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

                    {{-- Fasilitas Umum SP --}}
                    <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                                Fasilitas Umum SP ({{ count($fasilitas) }} Fasilitas)
                            </h3>
                            <a href="{{ route('sp.fasilitas', ['sp' => $sp['id_satuan_permukiman']]) }}"
                                class="text-theme-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                Buka di Halaman Fasilitas SP &rarr;
                            </a>
                        </div>
                        @if (empty($fasilitas))
                            <x-sim.empty-state judul="Belum ada data fasilitas"
                                pesan="Gedung dan fasilitas SP akan tampil di sini setelah didata." />
                        @else
                            <x-sim.tabel-ringkas judul="Fasilitas umum di SP ini" :kolom="['Nama Fasilitas', 'Jenis Fasilitas', 'Kondisi']">
                                @foreach ($fasilitas as $baris)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                        <td class="px-5 py-3">
                                            <a href="{{ route('sp.fasilitas.detail', $baris['id_fasilitas_sp']) }}"
                                                class="rounded font-medium text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                                {{ $baris['nama_fasilitas'] }}
                                            </a>
                                        </td>
                                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                            {{ $baris['jenis_fasilitas'] }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <x-sim.status-badge :status="\App\Enums\Kondisi::from($baris['kondisi'])" />
                                        </td>
                                    </tr>
                                @endforeach
                            </x-sim.tabel-ringkas>
                        @endif
                    </div>

                    {{-- Inventaris SP --}}
                    <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                                Inventaris Operasional ({{ count($inventaris) }} Barang)
                            </h3>
                            <a href="{{ route('sp.inventaris', ['sp' => $sp['id_satuan_permukiman']]) }}"
                                class="text-theme-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                Buka di Halaman Inventaris SP &rarr;
                            </a>
                        </div>
                        @if (empty($inventaris))
                            <x-sim.empty-state judul="Belum ada data inventaris"
                                pesan="Barang dan peralatan kantor SP akan tampil di sini setelah didata." />
                        @else
                            <x-sim.tabel-ringkas judul="Inventaris di SP ini" :kolom="['Nama Barang', 'Jumlah Unit', 'Kondisi', 'Status Penyerahan']">
                                @foreach ($inventaris as $baris)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                        <td class="px-5 py-3">
                                            <a href="{{ route('sp.inventaris.detail', $baris['id_inventaris_sp']) }}"
                                                class="rounded font-medium text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                                {{ $baris['nama_barang'] }}
                                            </a>
                                        </td>
                                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                            {{ $baris['jumlah'] }} unit
                                        </td>
                                        <td class="px-5 py-3">
                                            <x-sim.status-badge :status="\App\Enums\Kondisi::from($baris['kondisi'])" />
                                        </td>
                                        <td class="px-5 py-3">
                                            <x-sim.status-badge :status="\App\Enums\StatusPenyerahan::from($baris['status_penyerahan'])" />
                                        </td>
                                    </tr>
                                @endforeach
                            </x-sim.tabel-ringkas>
                        @endif
                    </div>
                </div>

                {{-- ====================================================================== --}}
                {{-- TAB 5: Pengaduan Warga --}}
                {{-- ====================================================================== --}}
                <div x-show="tab === 'pengaduan'" x-cloak role="tabpanel" class="p-5 sm:p-6">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                            Pengaduan Warga ({{ count($pengaduan) }} Laporan)
                        </h3>
                        <a href="{{ route('pengaduan.index', ['sp' => $sp['id_satuan_permukiman']]) }}"
                            class="text-theme-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                            Buka di Halaman Pengaduan &rarr;
                        </a>
                    </div>
                    @if (empty($pengaduan))
                        <x-sim.empty-state judul="Belum ada pengaduan"
                            :pesan="'Tidak ada pengaduan tercatat dari ' . $sp['nama'] . '.'" />
                    @else
                        <x-sim.tabel-ringkas judul="Pengaduan di SP ini" :kolom="['Nomor Tiket', 'Perihal Laporan', 'Prioritas', 'Status']">
                            @foreach ($pengaduan as $baris)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $baris['nomor_pengaduan'] }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <a href="{{ route('pengaduan.detail', $baris['id_pengaduan']) }}"
                                            class="rounded font-medium text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
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

                {{-- ====================================================================== --}}
                {{-- TAB 6: Keadaan Wilayah & Monografi --}}
                {{-- ====================================================================== --}}
                <div x-show="tab === 'monografi'" x-cloak role="tabpanel" class="p-5 space-y-6 sm:p-6">
                    @php
                        $adaKeadaan = collect([
                            'lintang_utara', 'jarak_ke_kecamatan_km', 'batas_utara', 'nomor_sk_pencadangan',
                            'pola_permukiman', 'tingkat_kesuburan_tanah', 'bentuk_wilayah', 'curah_hujan_tahunan_mm',
                            'suhu_rata_c', 'sumber_air_bersih',
                        ])->contains(fn ($k) => ($sp[$k] ?? null) !== null && $sp[$k] !== '');

                        $rentang = fn ($min, $maks, $satuan = '') => ($sp[$min] ?? null) !== null || ($sp[$maks] ?? null) !== null
                            ? trim(rtrim(rtrim(number_format((float) ($sp[$min] ?? 0), 2, ',', '.'), '0'), ',')
                                . ' – ' . rtrim(rtrim(number_format((float) ($sp[$maks] ?? 0), 2, ',', '.'), '0'), ',') . ' ' . $satuan)
                            : null;
                    @endphp

                    <div>
                        <div class="mb-4">
                            <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                                Keadaan Wilayah & Kondisi Geografis
                            </h3>
                            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                Rujukan resmi Bab II Laporan Monografi Satuan Permukiman.
                            </p>
                        </div>

                        @if (! $adaKeadaan)
                            <p class="rounded-lg bg-gray-50 px-4 py-6 text-center text-theme-sm text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                                Data keadaan wilayah belum dicatat. Lengkapi lewat tombol Ubah Data SP agar Laporan Monografi SP lengkap.
                            </p>
                        @else
                            @php
                                $kelompokKeadaan = [
                                    'Letak & Jarak' => [
                                        'Letak astronomis' => ($sp['lintang_utara'] ?? null) !== null
                                            ? number_format((float) $sp['lintang_utara'], 6) . ' – ' . number_format((float) $sp['lintang_selatan'], 6) . ' LS, '
                                                . number_format((float) $sp['bujur_barat'], 6) . ' – ' . number_format((float) $sp['bujur_timur'], 6) . ' BT'
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
                                    'Iklim & Cuaca' => [
                                        'Curah hujan rata-rata per tahun' => ($sp['curah_hujan_tahunan_mm'] ?? null) !== null
                                            ? number_format((float) $sp['curah_hujan_tahunan_mm'], 2, ',', '.') . ' mm' : null,
                                        'Curah hujan bulanan' => $rentang('curah_hujan_bulan_min_mm', 'curah_hujan_bulan_maks_mm', 'mm'),
                                        'Suhu udara' => $rentang('suhu_min_c', 'suhu_maks_c', '°C')
                                            . (($sp['suhu_rata_c'] ?? null) !== null ? ' (rata-rata ' . rtrim(rtrim(number_format((float) $sp['suhu_rata_c'], 1, ',', '.'), '0'), ',') . ' °C)' : ''),
                                        'Kecepatan angin' => $rentang('angin_min_knot', 'angin_maks_knot', 'knot')
                                            . (($sp['angin_rata_knot'] ?? null) !== null ? ' (rata-rata ' . rtrim(rtrim(number_format((float) $sp['angin_rata_knot'], 1, ',', '.'), '0'), ',') . ' knot)' : ''),
                                        'Penyinaran matahari' => $rentang('penyinaran_min_persen', 'penyinaran_maks_persen', '%')
                                            . (($sp['penyinaran_rata_persen'] ?? null) !== null ? ' (rata-rata ' . rtrim(rtrim(number_format((float) $sp['penyinaran_rata_persen'], 1, ',', '.'), '0'), ',') . '%)' : ''),
                                    ],
                                    'Sumberdaya Air' => [
                                        'Sumber air bersih' => $sp['sumber_air_bersih'] ?? null,
                                        'Sumber air pertanian' => $sp['sumber_air_pertanian'] ?? null,
                                    ],
                                ];
                            @endphp

                            <div class="grid gap-x-8 gap-y-5 sm:grid-cols-2">
                                @foreach ($kelompokKeadaan as $judulKelompok => $isi)
                                    <div>
                                        <h4 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $judulKelompok }}</h4>
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
                    </div>

                    {{-- Rute Aksesibilitas --}}
                    <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
                        <div class="mb-3">
                            <h4 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Rute Aksesibilitas (Tabel 2.1 Monografi)
                            </h4>
                            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                Rute pencapaian menuju {{ $sp['nama'] }} dari pusat pemerintahan dan simpul transportasi.
                            </p>
                        </div>

                        @if (count($ruteAksesibilitas) === 0)
                            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Belum dicatat.</p>
                        @else
                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                                <table class="min-w-full text-theme-sm">
                                    <caption class="sr-only">Cara pencapaian menuju {{ $sp['nama'] }}</caption>
                                    <thead class="border-b border-gray-200 bg-gray-50 text-theme-xs text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-4 py-2.5 text-left font-medium">Rute</th>
                                            <th scope="col" class="px-4 py-2.5 text-right font-medium">Jarak</th>
                                            <th scope="col" class="px-4 py-2.5 text-left font-medium">Sarana</th>
                                            <th scope="col" class="px-4 py-2.5 text-left font-medium">Kondisi Jalan</th>
                                            <th scope="col" class="px-4 py-2.5 text-left font-medium">Waktu Tempuh</th>
                                            <th scope="col" class="px-4 py-2.5 text-right font-medium">Ongkos</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @foreach ($ruteAksesibilitas as $r)
                                            <tr class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                                <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-white/90">
                                                    {{ $r['rute'] }}
                                                    @if (! empty($r['keterangan']))
                                                        <span class="mt-0.5 block text-theme-xs font-normal text-gray-500 dark:text-gray-400">{{ $r['keterangan'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2.5 text-right tabular-nums">{{ $r['jarak_km'] !== null ? rtrim(rtrim(number_format((float) $r['jarak_km'], 1, ',', '.'), '0'), ',') . ' km' : '-' }}</td>
                                                <td class="px-4 py-2.5">{{ $r['sarana_angkutan'] ?? '-' }}</td>
                                                <td class="px-4 py-2.5">{{ $r['kondisi_jalan'] ?? '-' }}</td>
                                                <td class="px-4 py-2.5">{{ $r['waktu_tempuh'] ?? '-' }}</td>
                                                <td class="px-4 py-2.5 text-right tabular-nums">{{ $r['ongkos_rp'] !== null ? 'Rp ' . number_format((float) $r['ongkos_rp'], 0, ',', '.') : '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Ubah Satuan Permukiman --}}
    <x-sim.modal-form nama="formUbahSp" judul="Ubah Satuan Permukiman"
        keterangan="Satu SP menempel pada desa sekaligus kawasan transmigrasi."
        :pola-aksi="'/sp/' . $sp['id_satuan_permukiman']" ukuran="xl"
        :langkah="['Identitas & Wilayah', 'Lokasi & Batas', 'Keadaan Alam & Iklim', 'Aksesibilitas & Berkas']">
        @include('pages.sp.form', [
            'awalan' => 'ubah',
            'data' => $sp,
        ])
    </x-sim.modal-form>

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
