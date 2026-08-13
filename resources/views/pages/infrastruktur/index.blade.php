{{--
    Infrastruktur SP.

    Modul ini berisi PENDATAAN ASET, bukan pelaporan masalah. Kerusakan
    dilaporkan lewat modul Pengaduan (agents/rules.md bagian 10 poin 1).
    Karena itu halaman ini tidak menyediakan tombol lapor kerusakan,
    melainkan menaut ke kanal pengaduan.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::infrastruktur();
        $statusJenis = DummyData::statusInfrastruktur();

        $cari = trim((string) request('cari', ''));
        $filterSp = request('sp');
        $filterJenis = request('jenis');
        $filterKondisi = request('kondisi');

        $baris = array_values(array_filter($semua, function ($i) use ($cari, $filterSp, $filterJenis, $filterKondisi) {
            if ($cari !== '' && ! str_contains(mb_strtolower($i['nama']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterSp && (string) $i['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }
            if ($filterJenis && $i['jenis'] !== $filterJenis) {
                return false;
            }
            if ($filterKondisi && $i['kondisi'] !== $filterKondisi) {
                return false;
            }

            return true;
        }));

        $adaFilter = $cari !== '' || $filterSp || $filterJenis || $filterKondisi;
        $rusakBerat = count(array_filter($semua, fn ($i) => $i['kondisi'] === 'Rusak Berat'));
        $perluPerbaikan = count(array_filter($semua, fn ($i) => $i['kondisi'] !== 'Baik'));
    @endphp

    <x-sim.halaman-daftar judul="Infrastruktur SP"
        keterangan="Pendataan aset irigasi, air, jalan produksi, listrik, dan gudang."
        :remah="[['label' => 'Infrastruktur'], ['label' => 'Daftar Infrastruktur']]"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('infrastruktur.index')"
        placeholder-cari="Cari nama infrastruktur" judul-kosong="Belum ada data infrastruktur"
        pesan-kosong="Aset infrastruktur akan tampil di sini setelah didata.">

        <x-slot:aksi>
            {{--
                Impor massal diletakkan mendahului tombol tambah namun bergaya
                sekunder, sebab menambah satu data tetap tindakan yang paling
                sering dipakai (PRD 8.1).
            --}}
            <button type="button" @click="$dispatch('buka-modal', 'imporInfrastruktur')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Impor Data
            </button>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahInfrastruktur')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Aset
            </button>
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Aset Terdata" :nilai="count($semua)" />
            <x-sim.stat-card label="Kondisi Baik" :nilai="count($semua) - $perluPerbaikan" />
            <x-sim.stat-card label="Perlu Perbaikan" :nilai="$perluPerbaikan" />
            <x-sim.stat-card label="Rusak Berat" :nilai="$rusakBerat"
                keterangan="Perlu penanganan segera" />
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
                    <label for="filter_jenis"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Jenis</label>
                    <select id="filter_jenis" name="jenis"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua jenis</option>
                        @foreach (\App\Enums\JenisInfrastruktur::opsi() as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($filterJenis === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_kondisi"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Kondisi</label>
                    <select id="filter_kondisi" name="kondisi"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua kondisi</option>
                        @foreach (\App\Enums\Kondisi::opsi() as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($filterKondisi === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="h-10 flex-1 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Terapkan Filter
                    </button>
                    @if ($adaFilter)
                        <a href="{{ route('infrastruktur.index') }}"
                            class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Bersihkan
                        </a>
                    @endif
                </div>
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jenis</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Satuan Permukiman</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kapasitas</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tahun</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kondisi</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Verifikasi</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $i)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $i['nama'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $i['jenis'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $i['satuan_permukiman'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $i['kapasitas'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $i['tahun_perolehan'] }}</td>
                <td class="px-5 py-3">
                    <x-sim.status-badge :status="\App\Enums\Kondisi::from($i['kondisi'])" />
                </td>
                <td class="px-5 py-3">
                    <x-sim.status-badge :status="\App\Enums\StatusVerifikasi::from($i['status_verifikasi'])" />
                </td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris :rincian-url="route('infrastruktur.detail', $i['id_infrastruktur'])"
                        modal-ubah="formUbahInfrastrukturBaris"
                        :data-baris="$i + ['id' => $i['id_infrastruktur']]"
                        :hapus-url="'/infrastruktur/' . $i['id_infrastruktur']"
                        konfirmasi-hapus="hapusInfrastruktur" :label="$i['nama']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $i)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $i['nama'] }}</p>
                        <x-sim.status-badge :status="\App\Enums\Kondisi::from($i['kondisi'])" ukuran="sm" />
                    </div>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $i['jenis'] }} &middot; {{ $i['satuan_permukiman'] }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>

        <x-slot:setelahTabel>
            {{-- Rekap kondisi per jenis, sumber indikator ke-12 dashboard --}}
            <div class="mt-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="border-b border-gray-200 p-5 dark:border-gray-800">
                    <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Rekap Kondisi per Jenis</h2>
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        Angka ini menjadi sumber indikator status infrastruktur pada dashboard.
                    </p>
                </div>
                <x-sim.tabel-ringkas :kolom="['Jenis', 'Baik', 'Rusak Ringan', 'Rusak Berat', 'Total']">
                    @foreach ($statusJenis as $s)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                {{ $s['jenis'] }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-green-700 dark:text-green-400">
                                {{ $s['baik'] }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-yellow-700 dark:text-yellow-400">
                                {{ $s['rusak_ringan'] }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-red-700 dark:text-red-400">
                                {{ $s['rusak_berat'] }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                {{ $s['baik'] + $s['rusak_ringan'] + $s['rusak_berat'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="motif-baris-total">
                        <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">Total kawasan</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            {{ array_sum(array_column($statusJenis, 'baik')) }}</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            {{ array_sum(array_column($statusJenis, 'rusak_ringan')) }}</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            {{ array_sum(array_column($statusJenis, 'rusak_berat')) }}</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            {{ array_sum(array_column($statusJenis, 'baik')) + array_sum(array_column($statusJenis, 'rusak_ringan')) + array_sum(array_column($statusJenis, 'rusak_berat')) }}
                        </td>
                    </tr>
                </x-sim.tabel-ringkas>
            </div>

            <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                Modul ini mendata aset, bukan menerima laporan kerusakan. Kerusakan disampaikan lewat
                <a href="{{ route('pengaduan.index') }}"
                    class="rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">modul
                    Pengaduan</a>, agar penanganannya terlacak beserta petugas dan tanggalnya.
            </p>
        </x-slot:setelahTabel>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahInfrastruktur" judul="Tambah Aset Infrastruktur"
        keterangan="Pendataan aset. Keluhan warga disampaikan lewat modul pengaduan."
        :aksi="route('infrastruktur.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.infrastruktur.form', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahInfrastrukturBaris" judul="Ubah Data Aset"
        keterangan="Kondisi diperbarui petugas saat pendataan berkala."
        pola-aksi="/infrastruktur/:id" metode="PUT" ukuran="lg"
        label-simpan="Simpan Perubahan">
        @include('pages.infrastruktur.form', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusInfrastruktur" judul="Hapus data ini?"
        pesan="Data yang dihapus masih tercatat pada audit log dan dapat dipulihkan admin." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporInfrastruktur" judul="Impor Aset Infrastruktur"
        entitas="infrastruktur"
        :kolom-wajib="['satuan_permukiman', 'jenis', 'nama_aset', 'kondisi']" />
@endsection
