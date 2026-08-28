{{--
    Daftar alat dan mesin pertanian.

    PEMILIK SELALU KELOMPOK TANI (agents/rules.md bagian 7b poin 1).
    Kepemilikan pribadi dicabut 2026-08-22 mengikuti keputusan pemilik proyek
    bahwa seluruh menu Pertanian mencatat kelompok, bukan individu.
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `alsintan.index`, termasuk
        penyaringan dan angka ringkasannya. Lihat routes/web.php.
    --}}
    <x-sim.halaman-daftar judul="Alsintan"
        keterangan="Alat dan mesin pertanian milik kelompok tani."
        :remah="\App\Helpers\RemahHelper::untuk('/alsintan')"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('alsintan.index')"
        placeholder-cari="Cari nama alat atau pemilik" judul-kosong="Belum ada data alsintan"
        pesan-kosong="Alat dan mesin pertanian akan tampil di sini setelah didata.">

        <x-slot:aksi>
            <x-sim.aksi-daftar modal-impor="imporAlsintan"
                modal-tambah="formTambahAlsintan" label-tambah="Tambah Alsintan" />
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Jenis Alat" :nilai="count($semua)" />
            <x-sim.stat-card label="Total Unit" :nilai="number_format($totalUnit, 0, ',', '.')" />
            <x-sim.stat-card label="Poktan Pemilik" :nilai="$poktanPemilik"
                keterangan="Kelompok yang memiliki alat" />
            <x-sim.stat-card label="Perlu Perbaikan" :nilai="$rusak"
                keterangan="Rusak ringan atau rusak berat" />
        </x-slot:ringkasan>

        <x-slot:filter>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label for="filter_sp"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Satuan Permukiman</label>
                    <select id="filter_sp" name="sp"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua SP</option>
                        @foreach ($daftarSp as $sp)
                            <option value="{{ $sp['id_satuan_permukiman'] }}"
                                @selected($filterSp == $sp['id_satuan_permukiman'])>{{ $sp['nama'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_kondisi"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Kondisi</label>
                    <select id="filter_kondisi" name="kondisi"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua kondisi</option>
                        @foreach ($opsiFilterKondisi as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($filterKondisi === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <x-sim.tombol-filter :ada-filter="$adaFilter" :url-bersih="route('alsintan.index')" />
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama Alat</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Satuan Permukiman</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Pemilik</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tahun Pengadaan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kondisi</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $a)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $a['nama_alat'] }}</td>
                {{--
                    Kolom Kepemilikan diganti Satuan Permukiman 2026-08-22.
                    Kepemilikan kini selalu kelompok tani, sehingga kolomnya
                    akan menampilkan nilai yang sama pada setiap baris. SP
                    sebelumnya hanya muncul sebagai subteks kecil di bawah
                    nama pemilik.
                --}}
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    <a href="{{ route('dashboard.sp', $a['satuan_permukiman_id']) }}"
                        class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                        {{ $a['satuan_permukiman'] }}
                    </a>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    <a href="{{ route('poktan.detail', $a['poktan_id']) }}"
                        class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                        {{ $a['pemilik'] }}
                    </a>
                </td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">{{ $a['jumlah'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $a['tahun_pengadaan'] }}</td>
                <td class="px-5 py-3">
                    <x-sim.status-badge :status="\App\Enums\Kondisi::from($a['kondisi'])" />
                </td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris :rincian-url="route('alsintan.detail', $a['id_alsintan'])"
                        modal-ubah="formUbahAlsintanBaris"
                        :data-baris="$a + ['id' => $a['id_alsintan']]"
                        :hapus-url="'/alsintan/' . $a['id_alsintan']"
                        konfirmasi-hapus="hapusAlsintan" :label="$a['nama_alat']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $a)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $a['nama_alat'] }}</p>
                        <x-sim.status-badge :status="\App\Enums\Kondisi::from($a['kondisi'])" ukuran="sm" />
                    </div>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $a['jumlah'] }} unit &middot; {{ $a['pemilik'] }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahAlsintan" judul="Tambah Alsintan"
        keterangan="Alat baru tercatat pada inventaris kawasan."
        :aksi="route('alsintan.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.alsintan.form', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahAlsintanBaris" judul="Ubah Data Alsintan"
        keterangan="Satuan permukiman mengikuti kelompok tani yang dipilih."
        pola-aksi="/alsintan/:id" metode="PUT" ukuran="lg"
        label-simpan="Simpan Perubahan">
        @include('pages.alsintan.form', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusAlsintan" judul="Hapus data ini?"
        pesan="Data yang dihapus masih tercatat pada audit log dan dapat dipulihkan admin." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporAlsintan" judul="Impor Data Alsintan"
        entitas="alsintan"
        :kolom-wajib="['satuan_permukiman', 'jenis_alsintan', 'jumlah', 'kondisi']" />
@endsection
