{{--
    Data master wilayah administratif.

    Hierarki bercabang dua di tingkat kabupaten (agents/rules.md bagian 4a):
    cabang administratif provinsi, kabupaten, kecamatan, desa; dan cabang
    program berupa kawasan transmigrasi. Keduanya bertemu di SP.

    Halaman ini menampilkan cabang administratif dalam tab bertingkat, karena
    keempat tingkatnya jarang diubah tetapi perlu dapat ditelusuri.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $wilayah = DummyData::wilayah();
    @endphp

    <x-sim.page-header judul="Data Master Wilayah"
        keterangan="Wilayah administratif tempat kawasan transmigrasi berada."
        :remah="[['label' => 'Pengaturan'], ['label' => 'Data Master Wilayah']]" />

    {{-- Penjelasan hierarki, karena percabangannya tidak lazim --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Susunan Wilayah</h2>
        <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
            Wilayah bercabang dua di tingkat kabupaten. Cabang administratif mencatat pembagian
            pemerintahan, sedangkan kawasan transmigrasi adalah wilayah perencanaan yang dapat
            memotong batas kecamatan. Keduanya bertemu di satuan permukiman.
        </p>
        <p class="mt-3 rounded-lg bg-gray-50 p-3.5 font-mono text-theme-xs text-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
            provinsi &rarr; kabupaten &rarr; kecamatan &rarr; desa &nbsp;&#8600;<br>
            <span class="pl-[7.5rem]">satuan permukiman</span><br>
            provinsi &rarr; kabupaten &rarr; kawasan transmigrasi &nbsp;&#8599;
        </p>
    </div>

    <div x-data="hashTabs('kecamatan')"
        class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
            role="tablist" aria-label="Tingkat wilayah">
            @foreach ([
                'provinsi' => 'Provinsi (' . count($wilayah['provinsi']) . ')',
                'kabupaten' => 'Kabupaten (' . count($wilayah['kabupaten']) . ')',
                'kecamatan' => 'Kecamatan (' . count($wilayah['kecamatan']) . ')',
                'desa' => 'Desa (' . count($wilayah['desa']) . ')',
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

        <div x-show="tab === 'provinsi'" role="tabpanel">
            <x-sim.tabel-ringkas :kolom="['Nama Provinsi', 'Kode']">
                @foreach ($wilayah['provinsi'] as $b)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $b['nama'] }}</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                            {{ $b['kode'] }}</td>
                    </tr>
                @endforeach
            </x-sim.tabel-ringkas>
        </div>

        <div x-show="tab === 'kabupaten'" x-cloak role="tabpanel">
            <x-sim.tabel-ringkas :kolom="['Nama Kabupaten', 'Provinsi', 'Kode']">
                @foreach ($wilayah['kabupaten'] as $b)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $b['nama'] }}</td>
                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $b['provinsi'] }}</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                            {{ $b['kode'] }}</td>
                    </tr>
                @endforeach
            </x-sim.tabel-ringkas>
        </div>

        <div x-show="tab === 'kecamatan'" x-cloak role="tabpanel">
            <x-sim.tabel-ringkas :kolom="['Nama Kecamatan', 'Kabupaten', 'Jumlah Desa']">
                @foreach ($wilayah['kecamatan'] as $b)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $b['nama'] }}</td>
                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $b['kabupaten'] }}</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                            {{ $b['jumlah_desa'] }}</td>
                    </tr>
                @endforeach
            </x-sim.tabel-ringkas>
        </div>

        <div x-show="tab === 'desa'" x-cloak role="tabpanel">
            <x-sim.tabel-ringkas :kolom="['Nama Desa', 'Kecamatan', 'Jumlah SP']">
                @foreach ($wilayah['desa'] as $b)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $b['nama'] }}</td>
                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $b['kecamatan'] }}</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                            {{ $b['jumlah_sp'] }}</td>
                    </tr>
                @endforeach
            </x-sim.tabel-ringkas>
        </div>
    </div>
@endsection
