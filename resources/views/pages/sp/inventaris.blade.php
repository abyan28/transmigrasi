{{--
    Inventaris SP, yaitu barang bergerak milik satuan permukiman.

    Dipisah dari fasilitas SP agar rekap aset kawasan dapat dibedakan antara
    barang bergerak dan bangunan tetap (agents/rules.md bagian 4b poin 1).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::inventarisSp();

        $cari = trim((string) request('cari', ''));
        $filterSp = request('sp');
        $filterStatus = request('status_penyerahan');

        $baris = array_values(array_filter($semua, function ($b) use ($cari, $filterSp, $filterStatus) {
            if ($cari !== '' && ! str_contains(mb_strtolower($b['nama_barang']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterSp && (string) $b['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }
            if ($filterStatus && $b['status_penyerahan'] !== $filterStatus) {
                return false;
            }

            return true;
        }));

        $adaFilter = $cari !== '' || $filterSp || $filterStatus;
        $totalUnit = array_sum(array_column($semua, 'jumlah'));
        $sudahDiserahkan = count(array_filter($semua, fn ($b) => $b['status_penyerahan'] === 'Sudah Diserahkan'));
        $perluPerhatian = count(array_filter($semua, fn ($b) => $b['kondisi'] !== 'Baik'));
    @endphp

    <x-sim.halaman-daftar judul="Inventaris SP"
        keterangan="Barang bergerak milik satuan permukiman beserta status penyerahannya."
        :remah="[['label' => 'Wilayah dan SP'], ['label' => 'Inventaris SP']]"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('sp.inventaris')"
        placeholder-cari="Cari nama barang" judul-kosong="Belum ada data inventaris"
        pesan-kosong="Barang milik satuan permukiman akan tampil di sini setelah didata.">

        <x-slot:ringkasan>
            <x-sim.stat-card label="Jenis Barang" :nilai="count($semua)" />
            <x-sim.stat-card label="Total Unit" :nilai="number_format($totalUnit, 0, ',', '.')" />
            <x-sim.stat-card label="Sudah Diserahkan" :nilai="$sudahDiserahkan"
                :keterangan="'dari ' . count($semua) . ' jenis barang'" />
            <x-sim.stat-card label="Perlu Perhatian" :nilai="$perluPerhatian"
                keterangan="Kondisi rusak ringan atau berat" />
        </x-slot:ringkasan>

        <x-slot:filter>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="filter_sp"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Satuan Permukiman</label>
                    <select id="filter_sp" name="sp"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua SP</option>
                        @foreach (DummyData::satuanPermukiman() as $sp)
                            <option value="{{ $sp['id_satuan_permukiman'] }}"
                                @selected($filterSp == $sp['id_satuan_permukiman'])>{{ $sp['nama'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_status"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Status Penyerahan</label>
                    <select id="filter_status" name="status_penyerahan"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua status</option>
                        @foreach (\App\Enums\StatusPenyerahan::opsi() as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($filterStatus === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="h-10 flex-1 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Terapkan Filter
                    </button>
                    @if ($adaFilter)
                        <a href="{{ route('sp.inventaris') }}"
                            class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Bersihkan
                        </a>
                    @endif
                </div>
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama Barang</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Satuan Permukiman</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tahun</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Sumber Dana</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kondisi</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Penyerahan</th>
        </x-slot:kepala>

        @foreach ($baris as $b)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                    {{ $b['nama_barang'] }}
                    @if ($b['keterangan'])
                        <p class="mt-0.5 text-theme-xs font-normal text-gray-500 dark:text-gray-400">
                            {{ $b['keterangan'] }}</p>
                    @endif
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $b['satuan_permukiman'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $b['jumlah'] }} {{ $b['satuan_barang'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $b['tahun_perolehan'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $b['sumber_dana'] }}</td>
                <td class="px-5 py-3">
                    <x-sim.status-badge :status="\App\Enums\Kondisi::from($b['kondisi'])" />
                </td>
                <td class="px-5 py-3">
                    <x-sim.status-badge :status="\App\Enums\StatusPenyerahan::from($b['status_penyerahan'])" />
                </td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $b)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $b['nama_barang'] }}</p>
                        <x-sim.status-badge :status="\App\Enums\Kondisi::from($b['kondisi'])" ukuran="sm" />
                    </div>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $b['jumlah'] }} {{ $b['satuan_barang'] }} &middot; {{ $b['satuan_permukiman'] }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>
@endsection
