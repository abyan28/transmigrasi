{{--
    Isi Laporan Transmigran, Rumah, dan Lahan.
    Di-include oleh halaman berbingkai (pages/laporan/transmigran) maupun rute dokumen polos (pages/laporan/dokumen).

    Mendukung 4 mode tampilan:
    1. Mode Gabungan (Terpadu): Satu tabel utama memadukan Transmigran, Rumah, dan Lahan.
    2. Mode Transmigran: Data demografi lengkap kepala keluarga.
    3. Mode Rumah: Data kondisi fisik dan status hunian rumah.
    4. Mode Lahan: Data bidang lahan pekarangan dan lahan usaha.
--}}
@php
    $isDokumen = request()->routeIs('laporan.dokumen');
    $angka = fn ($n, $d = 0) => \App\Support\LaporanData::angka($n, $d);
    $rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $tgl = function ($t) {
        try {
            return \Illuminate\Support\Carbon::parse($t)->translatedFormat('d F Y');
        } catch (\Throwable $e) {
            return $t;
        }
    };

    // Peta relasi rumah berdasarkan nama penghuni (nama KK)
    $petaRumah = [];
    foreach ($rumah as $r) {
        if (!empty($r['penghuni'])) {
            $petaRumah[$r['penghuni']] = $r;
        }
    }

    // Peta relasi bidang lahan berdasarkan transmigran_id
    $petaLahan = [];
    foreach ($lahan as $l) {
        $petaLahan[$l['transmigran_id']][] = $l;
    }
@endphp

{{-- Bilah Pengalih Mode Tampilan (Hanya di Web, Dihilangkan pada Dokumen Cetak/Resmi) --}}
@unless ($isDokumen)
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <nav aria-label="Mode Tampilan Laporan Transmigran"
            class="inline-flex rounded-xl bg-gray-100 p-1.5 dark:bg-gray-800">
            <button type="button" @click="modeTampilan = 'gabungan'"
                :class="modeTampilan === 'gabungan' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-white font-semibold' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white font-medium'"
                class="rounded-lg px-3.5 py-1.5 text-theme-xs transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                Mode Gabungan (Terpadu)
            </button>
            <button type="button" @click="modeTampilan = 'transmigran'"
                :class="modeTampilan === 'transmigran' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-white font-semibold' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white font-medium'"
                class="rounded-lg px-3.5 py-1.5 text-theme-xs transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                Data Transmigran
            </button>
            <button type="button" @click="modeTampilan = 'rumah'"
                :class="modeTampilan === 'rumah' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-white font-semibold' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white font-medium'"
                class="rounded-lg px-3.5 py-1.5 text-theme-xs transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                Data Rumah
            </button>
            <button type="button" @click="modeTampilan = 'lahan'"
                :class="modeTampilan === 'lahan' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-white font-semibold' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white font-medium'"
                class="rounded-lg px-3.5 py-1.5 text-theme-xs transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                Data Lahan
            </button>
        </nav>

        <div class="text-theme-xs text-gray-500 dark:text-gray-400">
            <span x-show="modeTampilan === 'gabungan'">Ikhtisar Terpadu: Profil KK, Rumah Hunian, dan Hak Pengelolaan Lahan</span>
            <span x-show="modeTampilan === 'transmigran'" x-cloak>Rincian Demografi Lengkap Kepala Keluarga Transmigran</span>
            <span x-show="modeTampilan === 'rumah'" x-cloak>Rincian Kondisi Fisik dan Status Penghunian Rumah SP</span>
            <span x-show="modeTampilan === 'lahan'" x-cloak>Rincian Bidang Lahan Pekarangan dan Lahan Usaha Pertanian</span>
        </div>
    </div>
@endunless

{{-- ========================================================================= --}}
{{-- 1. MODE GABUNGAN (TERPADU: TRANSMIGRAN + RUMAH + LAHAN)                   --}}
{{-- ========================================================================= --}}
<div x-show="modeTampilan === 'gabungan'">
    <div class="overflow-x-auto overflow-y-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
        <table class="tabel-dokumen min-w-full text-theme-xs">
            <caption class="border-b border-gray-200 bg-gray-50 px-4 py-2.5 text-left text-theme-xs font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                Daftar terpadu kepala keluarga transmigran beserta rumah dan lahannya di kawasan Kobalima Timur
            </caption>
            <thead class="text-theme-xs">
                {{-- Header Tingkat 1: Pengelompokan Domain --}}
                <tr class="border-b border-gray-200 dark:border-gray-800">
                    <th colspan="6" scope="colgroup"
                        class="border-r border-gray-200 bg-gray-100/90 px-2 py-1.5 text-left font-semibold uppercase tracking-wider text-gray-800 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-200">
                        1. Identitas Kepala Keluarga
                    </th>
                    <th colspan="3" scope="colgroup"
                        class="border-r border-blue-200 bg-blue-50/90 px-2 py-1.5 text-left font-semibold uppercase tracking-wider text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-200">
                        2. Rumah &amp; Hunian
                    </th>
                    <th colspan="3" scope="colgroup"
                        class="bg-emerald-50/90 px-2 py-1.5 text-left font-semibold uppercase tracking-wider text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                        3. Hak Pengelolaan Lahan
                    </th>
                </tr>
                {{-- Header Tingkat 2: Nama Kolom Detail --}}
                <tr class="bg-gray-50/80 text-gray-600 dark:bg-white/[0.02] dark:text-gray-400">
                    <th scope="col" class="px-2 py-1.5 text-left">No</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Nama KK &amp; NIK</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Satuan Permukiman</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Daerah Asal</th>
                    <th scope="col" class="px-2 py-1.5 text-center">Tahun Datang</th>
                    <th scope="col" class="border-r border-gray-200 px-2 py-1.5 text-center dark:border-gray-800">Status</th>
                    <th scope="col" class="px-2 py-1.5 text-left">No. Rumah</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Kondisi Fisik</th>
                    <th scope="col" class="border-r border-blue-200 px-2 py-1.5 text-left dark:border-blue-900/60">Status Hunian</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Lahan Pekarangan</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Lahan Usaha (Komposisi)</th>
                    <th scope="col" class="px-2 py-1.5 text-right">Total Lahan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($transmigran as $t)
                    @php
                        $r = $petaRumah[$t['nama_kepala_keluarga']] ?? null;
                        $lahans = $petaLahan[$t['id_transmigran']] ?? [];
                        $pekarangan = array_values(array_filter($lahans, fn ($x) => ($x['peruntukan_lahan'] ?? '') === \App\Enums\PeruntukanLahan::LahanPekarangan->value));
                        $usaha = array_values(array_filter($lahans, fn ($x) => ($x['peruntukan_lahan'] ?? '') === \App\Enums\PeruntukanLahan::LahanUsaha->value));
                        $totalLuasLahan = array_sum(array_column($lahans, 'luas'));
                        $kodesLahan = implode(' ', array_column($lahans, 'kode_lahan'));
                        $stringCari = strtolower(implode(' ', [
                            $t['nama_kepala_keluarga'],
                            $t['nik'],
                            $t['no_kk'],
                            $t['daerah_asal'],
                            $t['satuan_permukiman'],
                            $r ? ($r['no_rumah'] . ' ' . $r['kondisi'] . ' ' . $r['status_hunian']) : '',
                            $kodesLahan,
                        ]));
                    @endphp
                    <tr data-baris data-sp="{{ $t['satuan_permukiman_id'] }}"
                        data-tahun="{{ $t['tahun_kedatangan'] }}"
                        data-status="{{ $t['status_tinggal'] }}"
                        data-cari="{{ $stringCari }}"
                        x-show="cocok($el)"
                        class="text-gray-700 transition-colors hover:bg-gray-50/60 dark:text-gray-300 dark:hover:bg-white/[0.02]">
                        {{-- No --}}
                        <td class="px-2 py-1.5 tabular-nums text-gray-500 dark:text-gray-400" data-nomor></td>

                        {{-- Nama KK & NIK --}}
                        <td class="px-2 py-1.5">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $t['nama_kepala_keluarga'] }}</div>
                            <div class="text-[11px] tabular-nums text-gray-500 dark:text-gray-400">NIK {{ $t['nik'] }}</div>
                        </td>

                        {{-- Satuan Permukiman --}}
                        <td class="px-2 py-1.5 font-medium text-gray-800 dark:text-white/90">
                            {{ $t['satuan_permukiman'] }}
                        </td>

                        {{-- Daerah Asal --}}
                        <td class="px-2 py-1.5">{{ $t['daerah_asal'] }}</td>

                        {{-- Tahun Datang --}}
                        <td class="px-2 py-1.5 text-center tabular-nums">{{ $t['tahun_kedatangan'] }}</td>

                        {{-- Status Tinggal --}}
                        <td class="border-r border-gray-200 px-2 py-1.5 text-center dark:border-gray-800">
                            @if ($t['status_tinggal'] === \App\Enums\StatusTinggal::Aktif->value)
                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                                    {{ $t['status_tinggal'] }}
                                </span>
                            @endif
                        </td>

                        {{-- No Rumah --}}
                        <td class="px-2 py-1.5">
                            @if ($r)
                                <span class="font-semibold text-blue-900 dark:text-blue-200">{{ $r['no_rumah'] }}</span>
                            @else
                                <span class="text-[11px] italic text-gray-400 dark:text-gray-500">Belum ada</span>
                            @endif
                        </td>

                        {{-- Kondisi Fisik Rumah --}}
                        <td class="px-2 py-1.5">
                            @if ($r)
                                @php
                                    $warnaKondisi = match ($r['kondisi']) {
                                        'Tidak Rusak' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                                        'Rusak Ringan' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-950/50 dark:text-yellow-300',
                                        'Rusak Sedang' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
                                        'Rusak Berat' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300',
                                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $warnaKondisi }}">
                                    {{ $r['kondisi'] }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        {{-- Status Hunian --}}
                        <td class="border-r border-blue-200 px-2 py-1.5 dark:border-blue-900/60">
                            @if ($r)
                                <span class="inline-flex items-center gap-1 text-[11px] {{ $r['status_hunian'] === 'Dihuni' ? 'font-medium text-emerald-700 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $r['status_hunian'] === 'Dihuni' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                    {{ $r['status_hunian'] }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        {{-- Lahan Pekarangan --}}
                        <td class="px-2 py-1.5">
                            @if (count($pekarangan) > 0)
                                @foreach ($pekarangan as $lp)
                                    <div class="text-theme-xs">
                                        <span class="font-medium text-emerald-900 dark:text-emerald-200">{{ $lp['kode_lahan'] }}</span>
                                        <span class="tabular-nums text-gray-500 dark:text-gray-400">({{ $angka($lp['luas'], 2) }} ha)</span>
                                    </div>
                                @endforeach
                            @else
                                <span class="text-[11px] italic text-gray-400 dark:text-gray-500">Belum ada</span>
                            @endif
                        </td>

                        {{-- Lahan Usaha --}}
                        <td class="px-2 py-1.5">
                            @if (count($usaha) > 0)
                                <div class="space-y-0.5">
                                    @foreach ($usaha as $lu)
                                        <div class="text-theme-xs">
                                            <span class="font-semibold text-emerald-900 dark:text-emerald-200">{{ $lu['kode_lahan'] }}</span>
                                            <span class="font-medium tabular-nums text-gray-800 dark:text-gray-200">{{ $angka($lu['luas'], 2) }} ha</span>
                                            @if ($lu['luas_kering'] !== null || $lu['luas_basah'] !== null)
                                                <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                                    (K: {{ $lu['luas_kering'] !== null ? $angka($lu['luas_kering'], 2) : '0' }} | B: {{ $lu['luas_basah'] !== null ? $angka($lu['luas_basah'], 2) : '0' }})
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-[11px] italic text-gray-400 dark:text-gray-500">Belum ada</span>
                            @endif
                        </td>

                        {{-- Total Luas Seluruh Lahan --}}
                        <td class="px-2 py-1.5 text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                            {{ $totalLuasLahan > 0 ? $angka($totalLuasLahan, 2) . ' ha' : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                            Belum ada data transmigran pada data contoh.
                        </td>
                    </tr>
                @endforelse

                @if (count($transmigran) > 0)
                    <tr x-show="cacahTampak($el.closest('tbody')) === 0" x-cloak>
                        <td colspan="12" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data yang cocok dengan kriteria filter atau pencarian.
                            <button type="button" @click="bersihkan()"
                                class="ml-1.5 rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                Bersihkan filter
                            </button>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- 2. MODE DATA TRANSMIGRAN (DEMOGRAFI LENGKAP KEPALA KELUARGA)              --}}
{{-- ========================================================================= --}}
<div x-show="modeTampilan === 'transmigran'" x-cloak>
    <div class="overflow-x-auto overflow-y-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
        <table class="tabel-dokumen min-w-full text-theme-xs">
            <caption class="border-b border-gray-200 bg-gray-50 px-4 py-2.5 text-left text-theme-xs font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                Daftar kepala keluarga transmigran di seluruh satuan permukiman
            </caption>
            <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-1.5 py-1.5 text-left">No</th>
                    <th scope="col" class="px-1.5 py-1.5 text-left">NIK</th>
                    <th scope="col" class="px-1.5 py-1.5 text-left">Nama Kepala Keluarga</th>
                    <th scope="col" class="px-1.5 py-1.5 text-left">No. KK</th>
                    <th scope="col" class="px-1.5 py-1.5 text-left">JK</th>
                    <th scope="col" class="px-1.5 py-1.5 text-left">Tempat, Tgl Lahir</th>
                    <th scope="col" class="px-1.5 py-1.5 text-left">Pendidikan</th>
                    <th scope="col" class="px-1.5 py-1.5 text-left">Pekerjaan</th>
                    <th scope="col" class="px-1.5 py-1.5 text-right">Anggota</th>
                    <th scope="col" class="px-1.5 py-1.5 text-right">Pendapatan</th>
                    <th scope="col" class="px-1.5 py-1.5 text-left">Daerah Asal</th>
                    <th scope="col" class="px-1.5 py-1.5 text-center">Tahun</th>
                    <th scope="col" class="px-1.5 py-1.5 text-left">Satuan Permukiman</th>
                    <th scope="col" class="px-1.5 py-1.5 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($transmigran as $t)
                    @php
                        $stringCariT = strtolower(implode(' ', [
                            $t['nama_kepala_keluarga'],
                            $t['nik'],
                            $t['no_kk'],
                            $t['tempat_lahir'],
                            $t['pendidikan_terakhir'],
                            $t['pekerjaan_kepala_keluarga'],
                            $t['daerah_asal'],
                            $t['satuan_permukiman'],
                        ]));
                    @endphp
                    <tr data-baris data-sp="{{ $t['satuan_permukiman_id'] }}"
                        data-tahun="{{ $t['tahun_kedatangan'] }}"
                        data-status="{{ $t['status_tinggal'] }}"
                        data-cari="{{ $stringCariT }}"
                        x-show="cocok($el)"
                        class="text-gray-700 transition-colors hover:bg-gray-50/60 dark:text-gray-300 dark:hover:bg-white/[0.02]">
                        <td class="px-1.5 py-1.5 tabular-nums text-gray-500 dark:text-gray-400" data-nomor></td>
                        <td class="px-1.5 py-1.5 tabular-nums whitespace-nowrap text-gray-800 dark:text-white/90">{{ $t['nik'] }}</td>
                        <td class="px-1.5 py-1.5 font-medium text-gray-900 dark:text-white">{{ $t['nama_kepala_keluarga'] }}</td>
                        <td class="px-1.5 py-1.5 tabular-nums whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $t['no_kk'] }}</td>
                        <td class="px-1.5 py-1.5 text-center">{{ $t['jenis_kelamin'] === 'Laki-laki' ? 'L' : ($t['jenis_kelamin'] === 'Perempuan' ? 'P' : $t['jenis_kelamin']) }}</td>
                        <td class="px-1.5 py-1.5">
                            <div class="font-medium text-gray-800 dark:text-white/90">{{ $t['tempat_lahir'] }}</div>
                            <div class="text-[11px] tabular-nums text-gray-500 dark:text-gray-400">{{ $tgl($t['tanggal_lahir']) }}</div>
                        </td>
                        <td class="px-1.5 py-1.5">{{ $t['pendidikan_terakhir'] }}</td>
                        <td class="px-1.5 py-1.5">{{ $t['pekerjaan_kepala_keluarga'] }}</td>
                        <td class="px-1.5 py-1.5 text-right tabular-nums">{{ $t['jumlah_anggota_keluarga'] }}</td>
                        <td class="px-1.5 py-1.5 text-right tabular-nums whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ $rupiah($t['pendapatan_per_bulan']) }}</td>
                        <td class="px-1.5 py-1.5">{{ $t['daerah_asal'] }}</td>
                        <td class="px-1.5 py-1.5 text-center tabular-nums">{{ $t['tahun_kedatangan'] }}</td>
                        <td class="px-1.5 py-1.5 font-medium text-gray-800 dark:text-white/90">{{ $t['satuan_permukiman'] }}</td>
                        <td class="px-1.5 py-1.5 text-center">
                            @if ($t['status_tinggal'] === \App\Enums\StatusTinggal::Aktif->value)
                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                                    {{ $t['status_tinggal'] }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                            Belum ada data transmigran pada data contoh.
                        </td>
                    </tr>
                @endforelse

                @if (count($transmigran) > 0)
                    <tr x-show="cacahTampak($el.closest('tbody')) === 0" x-cloak>
                        <td colspan="14" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data kepala keluarga yang cocok dengan kriteria filter atau pencarian.
                            <button type="button" @click="bersihkan()"
                                class="ml-1.5 rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                Bersihkan filter
                            </button>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- 3. MODE DATA RUMAH (KONDISI FISIK DAN STATUS PENGHUNIAN)                  --}}
{{-- ========================================================================= --}}
<div x-show="modeTampilan === 'rumah'" x-cloak>
    <div class="overflow-x-auto overflow-y-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
        <table class="tabel-dokumen min-w-full text-theme-xs">
            <caption class="border-b border-gray-200 bg-gray-50 px-4 py-2.5 text-left text-theme-xs font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                Rumah transmigran beserta kondisi dan status huniannya
            </caption>
            <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-2 py-1.5 text-left">No</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Nomor Rumah</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Satuan Permukiman</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Penghuni (Nama KK)</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Kondisi Bangunan</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Status Hunian</th>
                    <th scope="col" class="px-2 py-1.5 text-center">Tahun Bangun</th>
                    <th scope="col" class="px-2 py-1.5 text-right">Luas Bangunan (m²)</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($rumah as $r)
                    @php
                        $warnaKondisi = match ($r['kondisi']) {
                            'Tidak Rusak' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                            'Rusak Ringan' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-950/50 dark:text-yellow-300',
                            'Rusak Sedang' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
                            'Rusak Berat' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300',
                            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                        };
                        $stringCariR = strtolower(implode(' ', [
                            $r['no_rumah'],
                            $r['satuan_permukiman'],
                            $r['penghuni'] ?? '',
                            $r['kondisi'],
                            $r['status_hunian'],
                            $r['alasan_tidak_dihuni'] ?? '',
                        ]));
                    @endphp
                    <tr data-baris data-sp="{{ $r['satuan_permukiman_id'] }}"
                        data-kondisi="{{ $r['kondisi'] }}"
                        data-status-hunian="{{ $r['status_hunian'] }}"
                        data-cari="{{ $stringCariR }}"
                        x-show="cocok($el)"
                        class="text-gray-700 transition-colors hover:bg-gray-50/60 dark:text-gray-300 dark:hover:bg-white/[0.02]">
                        <td class="px-2 py-1.5 tabular-nums text-gray-500 dark:text-gray-400" data-nomor></td>
                        <td class="px-2 py-1.5 font-semibold text-blue-900 dark:text-blue-200">{{ $r['no_rumah'] }}</td>
                        <td class="px-2 py-1.5 font-medium text-gray-800 dark:text-white/90">{{ $r['satuan_permukiman'] }}</td>
                        <td class="px-2 py-1.5">
                            @if ($r['penghuni'])
                                <span class="font-medium text-gray-900 dark:text-white">{{ $r['penghuni'] }}</span>
                            @else
                                <span class="text-[11px] italic text-gray-400 dark:text-gray-500">Kosong (Belum ada)</span>
                            @endif
                        </td>
                        <td class="px-2 py-1.5">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $warnaKondisi }}">
                                {{ $r['kondisi'] }}
                            </span>
                        </td>
                        <td class="px-2 py-1.5">
                            <span class="inline-flex items-center gap-1 text-[11px] {{ $r['status_hunian'] === 'Dihuni' ? 'font-medium text-emerald-700 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $r['status_hunian'] === 'Dihuni' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                {{ $r['status_hunian'] }}
                            </span>
                        </td>
                        <td class="px-2 py-1.5 text-center tabular-nums">{{ $r['tahun_pembangunan'] }}</td>
                        <td class="px-2 py-1.5 text-right tabular-nums font-medium text-gray-900 dark:text-white">
                            {{ $angka($r['luas_bangunan']) }} m²
                        </td>
                        <td class="px-2 py-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                            {{ ($r['alasan_tidak_dihuni'] ?? '') ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                            Belum ada data rumah pada data contoh.
                        </td>
                    </tr>
                @endforelse

                @if (count($rumah) > 0)
                    <tr x-show="cacahTampak($el.closest('tbody')) === 0" x-cloak>
                        <td colspan="9" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data rumah yang cocok dengan kriteria filter atau pencarian.
                            <button type="button" @click="bersihkan()"
                                class="ml-1.5 rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                Bersihkan filter
                            </button>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- 4. MODE DATA LAHAN (BIDANG PEKARANGAN DAN LAHAN USAHA)                    --}}
{{-- ========================================================================= --}}
<div x-show="modeTampilan === 'lahan'" x-cloak>
    <div class="overflow-x-auto overflow-y-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
        <table class="tabel-dokumen min-w-full text-theme-xs">
            <caption class="border-b border-gray-200 bg-gray-50 px-4 py-2.5 text-left text-theme-xs font-semibold text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                Lahan yang dibagikan kepada transmigran menurut peruntukannya
            </caption>
            <thead class="bg-gray-50 text-theme-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-2 py-1.5 text-left">No</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Kode Lahan</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Pemilik (Kepala Keluarga)</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Satuan Permukiman</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Peruntukan</th>
                    <th scope="col" class="px-2 py-1.5 text-right">Luas Total (ha)</th>
                    <th scope="col" class="px-2 py-1.5 text-right">Luas Kering (ha)</th>
                    <th scope="col" class="px-2 py-1.5 text-right">Luas Basah (ha)</th>
                    <th scope="col" class="px-2 py-1.5 text-left">Pola Tanam</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($lahan as $l)
                    @php
                        $stringCariL = strtolower(implode(' ', [
                            $l['kode_lahan'],
                            $l['pemilik'],
                            $l['satuan_permukiman'],
                            $l['peruntukan_lahan'],
                            $l['pola_tanam'] ?? '',
                        ]));
                    @endphp
                    <tr data-baris data-sp="{{ $l['satuan_permukiman_id'] }}"
                        data-peruntukan="{{ $l['peruntukan_lahan'] }}"
                        data-cari="{{ $stringCariL }}"
                        x-show="cocok($el)"
                        class="text-gray-700 transition-colors hover:bg-gray-50/60 dark:text-gray-300 dark:hover:bg-white/[0.02]">
                        <td class="px-2 py-1.5 tabular-nums text-gray-500 dark:text-gray-400" data-nomor></td>
                        <td class="px-2 py-1.5 font-semibold text-emerald-900 dark:text-emerald-200">{{ $l['kode_lahan'] }}</td>
                        <td class="px-2 py-1.5 font-medium text-gray-900 dark:text-white">{{ $l['pemilik'] }}</td>
                        <td class="px-2 py-1.5 font-medium text-gray-800 dark:text-white/90">{{ $l['satuan_permukiman'] }}</td>
                        <td class="px-2 py-1.5">
                            @if ($l['peruntukan_lahan'] === \App\Enums\PeruntukanLahan::LahanUsaha->value)
                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                    Lahan Usaha
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                    Lahan Pekarangan
                                </span>
                            @endif
                        </td>
                        <td class="px-2 py-1.5 text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                            {{ $angka($l['luas'], 2) }}
                        </td>
                        <td class="px-2 py-1.5 text-right tabular-nums">
                            {{ $l['luas_kering'] !== '' && $l['luas_kering'] !== null ? $angka($l['luas_kering'], 2) : '-' }}
                        </td>
                        <td class="px-2 py-1.5 text-right tabular-nums">
                            {{ $l['luas_basah'] !== '' && $l['luas_basah'] !== null ? $angka($l['luas_basah'], 2) : '-' }}
                        </td>
                        <td class="px-2 py-1.5 text-[11px] text-gray-600 dark:text-gray-400">
                            {{ $l['pola_tanam'] ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                            Belum ada data lahan pada data contoh.
                        </td>
                    </tr>
                @endforelse

                @if (count($lahan) > 0)
                    <tr x-show="cacahTampak($el.closest('tbody')) === 0" x-cloak>
                        <td colspan="9" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data bidang lahan yang cocok dengan kriteria filter atau pencarian.
                            <button type="button" @click="bersihkan()"
                                class="ml-1.5 rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                Bersihkan filter
                            </button>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

