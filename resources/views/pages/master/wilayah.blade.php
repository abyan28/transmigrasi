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
        :remah="\App\Helpers\RemahHelper::untuk('/wilayah')">
        <x-slot:aksi>
            {{--
                Impor massal diletakkan mendahului tombol tambah namun bergaya
                sekunder, sebab menambah satu data tetap tindakan yang paling
                sering dipakai (PRD 8.1).
            --}}
            <button type="button" @click="$dispatch('buka-modal', 'imporWilayah')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Impor Data
            </button>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahWilayah')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Wilayah
            </button>
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
        Tab bawaan PROVINSI, bukan kecamatan.

        Semula bawaannya `kecamatan`, dan itu keliru pada dua hal sekaligus:
        pembacaannya melompati dua tingkat pertama sehingga susunan hierarki
        yang baru saja dijelaskan di atas tidak terlihat, dan pengunjung yang
        mengklik menu langsung mendapat alamat `?tab=kecamatan` seolah ia
        pernah memilihnya sendiri.

        Halaman bertingkat dibuka dari tingkat teratas; penelusuran ke bawah
        adalah tindakan yang dipilih pengguna, bukan keadaan awal.
    --}}
    <div x-data="hashTabs('provinsi')"
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

        {{--
            Panel ini SENGAJA tanpa `x-cloak`, dan itu bukan kelalaian: ia
            panel bawaan, sehingga menyembunyikannya sampai Alpine memulai
            justru membuat halaman kosong sesaat. Ketiga panel lain memakai
            `x-cloak` agar tidak berkedip terlihat.

            Sebelum bawaannya diubah menjadi provinsi, keduanya tidak sejalan:
            yang tanpa `x-cloak` adalah provinsi sementara bawaannya kecamatan,
            sehingga panel provinsi berkedip lalu tergantikan.
        --}}
        <div x-show="tab === 'provinsi'" role="tabpanel">
            <x-sim.tabel-ringkas :kolom="['Nama Provinsi', 'Kode', 'Aksi']" :kolom-kanan="['Aksi']">
                @foreach ($wilayah['provinsi'] as $b)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $b['nama'] }}</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                            {{ $b['kode'] }}</td>
                        <td class="px-5 py-3 text-right">
                            <x-sim.aksi-baris modal-ubah="formUbahWilayahBaris"
                                :data-baris="$b + ['id' => $b['id_provinsi']]"
                                :hapus-url="'/wilayah/' . $b['id_provinsi']"
                                konfirmasi-hapus="hapusWilayah" :label="$b['nama']" />
                        </td>
                    </tr>
                @endforeach
            </x-sim.tabel-ringkas>
        </div>

                <div x-show="tab === 'kabupaten'" x-cloak role="tabpanel">
            <x-sim.tabel-ringkas :kolom="['Nama Kabupaten', 'Provinsi', 'Kode', 'Aksi']" :kolom-kanan="['Aksi']">
                @foreach ($wilayah['kabupaten'] as $b)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $b['nama'] }}</td>
                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $b['provinsi'] }}</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                            {{ $b['kode'] }}</td>
                        <td class="px-5 py-3 text-right">
                            <x-sim.aksi-baris modal-ubah="formUbahWilayahBaris"
                                :data-baris="$b + ['id' => $b['id_kabupaten']]"
                                :hapus-url="'/wilayah/' . $b['id_kabupaten']"
                                konfirmasi-hapus="hapusWilayah" :label="$b['nama']" />
                        </td>
                    </tr>
                @endforeach
            </x-sim.tabel-ringkas>
        </div>

                <div x-show="tab === 'kecamatan'" x-cloak role="tabpanel">

            <x-sim.tabel-ringkas :kolom="['Nama Kecamatan', 'Kabupaten', 'Jumlah Desa', 'Aksi']" :kolom-kanan="['Aksi']">
                @foreach ($wilayah['kecamatan'] as $b)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $b['nama'] }}</td>
                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $b['kabupaten'] }}</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                            {{ $b['jumlah_desa'] }}</td>
                        <td class="px-5 py-3 text-right">
                            <x-sim.aksi-baris modal-ubah="formUbahWilayahBaris"
                                :data-baris="$b + ['id' => $b['id_kecamatan']]"
                                :hapus-url="'/wilayah/' . $b['id_kecamatan']"
                                konfirmasi-hapus="hapusWilayah" :label="$b['nama']" />
                        </td>
                    </tr>
                @endforeach
            </x-sim.tabel-ringkas>
        </div>

        <div x-show="tab === 'desa'" x-cloak role="tabpanel">
            <x-sim.tabel-ringkas :kolom="['Nama Desa', 'Kecamatan', 'Jumlah SP', 'Aksi']" :kolom-kanan="['Aksi']">
                @foreach ($wilayah['desa'] as $b)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $b['nama'] }}</td>
                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $b['kecamatan'] }}</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                            {{ $b['jumlah_sp'] }}</td>
                        <td class="px-5 py-3 text-right">
                            <x-sim.aksi-baris modal-ubah="formUbahWilayahBaris"
                                :data-baris="$b + ['id' => $b['id_desa']]"
                                :hapus-url="'/wilayah/' . $b['id_desa']"
                                konfirmasi-hapus="hapusWilayah" :label="$b['nama']" />
                        </td>
                    </tr>
                @endforeach
            </x-sim.tabel-ringkas>
        </div>
    </div>

    <x-sim.modal-form nama="formTambahWilayah" judul="Tambah Wilayah Administratif"
        keterangan="Satu form untuk empat tingkat wilayah."
        :aksi="route('wilayah.simpan')" ukuran="md" label-simpan="Simpan Data">
        @include('pages.master.form-wilayah', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahWilayahBaris" judul="Ubah Wilayah Administratif"
        keterangan="Tingkat wilayah menentukan induk yang diminta."
        pola-aksi="/wilayah/:id" metode="PUT" ukuran="md" label-simpan="Simpan Perubahan">
        @include('pages.master.form-wilayah', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusWilayah" judul="Hapus wilayah ini?"
        pesan="Wilayah yang masih memiliki turunan atau menaungi SP tidak dapat dihapus." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporWilayah" judul="Impor Wilayah Administratif"
        entitas="wilayah"
        :kolom-wajib="['tingkat', 'nama', 'induk']" />
@endsection
