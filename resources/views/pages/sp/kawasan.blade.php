{{--
    Kawasan transmigrasi.

    Cabang program dari hierarki wilayah. Satu kawasan dapat menaungi SP yang
    tersebar di beberapa kecamatan, dan itulah alasan kawasan dipisahkan dari
    hierarki administratif (agents/rules.md bagian 4a poin 5).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $kawasan = DummyData::kawasan();
        $daftarSp = DummyData::satuanPermukiman();
        $rekap = DummyData::rekapPerSp();

        $totalKk = array_sum(array_column($rekap, 'jumlah_kk'));
        $kecamatan = array_unique(array_column($daftarSp, 'kecamatan'));
    @endphp

    <x-sim.page-header judul="Kawasan Transmigrasi"
        keterangan="Wilayah perencanaan program yang menaungi satuan permukiman."
        :remah="[['label' => 'Wilayah dan SP'], ['label' => 'Kawasan Transmigrasi']]" />

    @foreach ($kawasan as $k)
        <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                        Kawasan {{ $k['nama'] }}
                    </h2>
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                        Kabupaten {{ $k['kabupaten'] }}, {{ $k['provinsi'] }}
                    </p>
                </div>
                <span class="rounded-full bg-teal-50 px-3 py-1 text-theme-xs font-medium text-teal-700 dark:bg-teal-500/15 dark:text-teal-300">
                    {{ $k['kode_kawasan'] }}
                </span>
            </div>

            <dl class="mt-6 grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Tahun penetapan</dt>
                    <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                        {{ $k['tahun_penetapan'] }}</dd>
                </div>
                <div>
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Nomor SK</dt>
                    <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">{{ $k['nomor_sk'] }}</dd>
                </div>
                <div>
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Luas total</dt>
                    <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                        {{ number_format($k['luas_total'], 2, ',', '.') }} ha</dd>
                </div>
                <div>
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Satuan permukiman</dt>
                    <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                        {{ $k['jumlah_sp'] }} SP di {{ count($kecamatan) }} kecamatan</dd>
                </div>
            </dl>
        </div>
    @endforeach

    {{-- Sebaran SP, memperlihatkan mengapa kawasan tidak dapat diwakili struktur administratif --}}
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Sebaran Satuan Permukiman</h2>
            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Enam SP tersebar di empat kecamatan berbeda. Sebaran inilah alasan kawasan
                dicatat terpisah dari hierarki administratif.
            </p>
        </div>

        <x-sim.tabel-ringkas :kolom="['Satuan Permukiman', 'Desa', 'Kecamatan', 'Kepala Keluarga', 'Rincian']">
            @foreach ($daftarSp as $i => $sp)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                    <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                        {{ $sp['nama'] }}</td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $sp['desa'] }}</td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $sp['kecamatan'] }}</td>
                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                        {{ number_format($rekap[$i]['jumlah_kk'], 0, ',', '.') }} KK</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('dashboard.sp', $sp['id_satuan_permukiman']) }}"
                            class="rounded text-theme-sm font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                            Buka rincian
                        </a>
                    </td>
                </tr>
            @endforeach

            <tr class="motif-baris-total">
                <td colspan="3" class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">Total kawasan</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                    {{ number_format($totalKk, 0, ',', '.') }} KK</td>
                <td></td>
            </tr>
        </x-sim.tabel-ringkas>
    </div>
@endsection
