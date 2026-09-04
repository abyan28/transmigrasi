{{--
    Data master wilayah administratif.

    Hierarki bercabang dua di tingkat kabupaten (agents/rules.md bagian 4a):
    cabang administratif provinsi, kabupaten, kecamatan, desa; dan cabang
    program berupa kawasan transmigrasi. Keduanya bertemu di SP.

    Keempat tingkat cabang administratif disajikan dalam SATU tabel, dengan
    tingkat sebagai kolom sekaligus penyaring.

    Sebelum 2026-09-02 halaman ini memakai empat tab, dan itu tidak lagi
    memadai. Sejak provinsi dan kabupaten dibaca dari data rujukan nasional,
    tab Kabupaten memuat 514 baris tanpa pencarian maupun paginasi. Mencari
    satu nama juga menuntut petugas menebak lebih dulu ia berada di tab mana,
    padahal yang ia ketahui hanya namanya.

    Tab juga sudah dua kali melahirkan cacat: tab bawaan yang keliru, dan
    tingkat form yang tidak mengikuti tab yang sedang terbuka. Menyatukannya
    menghapus kelas cacat itu, bukan memperbaikinya untuk ketiga kali.
--}}
@extends('layouts.app')

@section('content')
    {{-- `$wilayah` datang dari rute `wilayah`. --}}

    <x-sim.page-header judul="Data Master Wilayah"
        keterangan="Wilayah administratif tempat kawasan transmigrasi berada."
        :remah="\App\Helpers\RemahHelper::untuk('/wilayah')">
        <x-slot:aksi>
            <x-sim.aksi-daftar modal-impor="imporWilayah"
                modal-tambah="formTambahWilayah" label-tambah="Tambah Wilayah" />
        </x-slot:aksi>
    </x-sim.page-header>

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
    {{--
        Satu tabel untuk keempat tingkat, menggantikan empat tab.

        Filter Tingkat WAJIB mencantumkan jumlahnya, sebab judul tab lama
        menampilkan angka itu. Menghapus tab tanpa memindahkan angkanya
        berarti pembaca kehilangan keterangan yang sebelumnya ada, dan
        perombakan ini berubah menjadi kemunduran.
    --}}
    <form method="GET" action="{{ route('wilayah') }}">
        <x-sim.data-table :jumlah="$jumlahBaris" :per-halaman="$perHalaman" :kata-kunci="$cari"
            judul="Daftar wilayah administratif"
            placeholder-cari="Cari nama wilayah, induk, atau kode"
            judul-kosong="Wilayah tidak ditemukan"
            pesan-kosong="Ubah kata kunci atau lepas penyaring tingkatnya.">

            <x-slot:filter>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="filter_tingkat"
                            class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                            Tingkat Wilayah
                        </label>
                        <select id="filter_tingkat" name="tingkat"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                            <option value="">Semua tingkat ({{ array_sum($cacahTingkat) }})</option>
                            @foreach (['provinsi' => 'Provinsi', 'kabupaten' => 'Kabupaten/Kota', 'kecamatan' => 'Kecamatan', 'desa' => 'Desa'] as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($filterTingkat === $nilai)>
                                    {{ $label }} ({{ $cacahTingkat[$nilai] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-slot:filter>

            <x-slot:aksiKanan>
                <button type="submit"
                    class="h-10 shrink-0 rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    Cari
                </button>
            </x-slot:aksiKanan>

            <x-slot:aksiKosong>
                @if ($adaFilter)
                    <a href="{{ route('wilayah') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Bersihkan Filter
                    </a>
                @endif
            </x-slot:aksiKosong>

            <x-slot:kepala>
                <th scope="col" class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama Wilayah</th>
                <th scope="col" class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tingkat</th>
                <th scope="col" class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">Induk</th>
                <th scope="col" class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kode</th>
                <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
            </x-slot:kepala>

            @foreach ($baris as $b)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                    <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $b['nama'] }}</td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 capitalize dark:text-gray-400">{{ $b['tingkat'] }}</td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $b['induk'] ?? '-' }}</td>
                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">{{ $b['kode'] ?? '-' }}</td>
                    <td class="px-5 py-3 text-right">
                        <x-sim.aksi-baris modal-ubah="formUbahWilayahBaris"
                            :data-baris="$b['asli'] + ['id' => $b['id'], 'tingkat' => $b['tingkat']]"
                            :hapus-url="'/wilayah/' . $b['tingkat'] . '/' . $b['id']"
                            konfirmasi-hapus="hapusWilayah" :label="$b['nama']" />
                    </td>
                </tr>
            @endforeach

            <x-slot:kartu>
                @foreach ($baris as $b)
                    <div class="border-b border-gray-100 p-4 last:border-0 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $b['nama'] }}</p>
                                <p class="mt-0.5 text-theme-xs text-gray-500 capitalize dark:text-gray-400">
                                    {{ $b['tingkat'] }}@if ($b['induk']) &middot; {{ $b['induk'] }} @endif
                                </p>
                            </div>
                            <x-sim.aksi-baris modal-ubah="formUbahWilayahBaris"
                                :data-baris="$b['asli'] + ['id' => $b['id'], 'tingkat' => $b['tingkat']]"
                                :hapus-url="'/wilayah/' . $b['tingkat'] . '/' . $b['id']"
                                konfirmasi-hapus="hapusWilayah" :label="$b['nama']" />
                        </div>
                    </div>
                @endforeach
            </x-slot:kartu>
        </x-sim.data-table>
    </form>


    <x-sim.modal-form nama="formTambahWilayah" judul="Tambah Wilayah Administratif"
        keterangan="Satu form untuk empat tingkat wilayah."
        :aksi="route('wilayah.simpan')" ukuran="md" label-simpan="Simpan Data">
        @include('pages.master.form-wilayah', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahWilayahBaris" judul="Ubah Wilayah Administratif"
        keterangan="Tingkat wilayah menentukan induk yang diminta."
        pola-aksi="/wilayah/:tingkat/:id" metode="PUT" ukuran="md" label-simpan="Simpan Perubahan">
        @include('pages.master.form-wilayah', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusWilayah" judul="Hapus wilayah ini?"
        pesan="Wilayah yang masih memiliki turunan atau menaungi SP tidak dapat dihapus." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporWilayah" judul="Impor Wilayah Administratif"
        entitas="wilayah"
        :kolom-wajib="['tingkat', 'nama', 'induk']" />
@endsection
