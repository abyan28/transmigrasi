{{--
    Daftar kelompok tani.

    Poktan menjadi penerima bantuan alsintan dan saprotan, sehingga jumlah
    anggota aktifnya berpengaruh langsung pada penyaluran
    (agents/rules.md bagian 7a dan 7c).
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `poktan.index`.
        Lihat routes/web.php.
    --}}
    <x-sim.halaman-daftar judul="Kelompok Tani"
        keterangan="Poktan di kawasan beserta ketua dan jumlah anggota transmigrannya."
        :remah="\App\Helpers\RemahHelper::untuk('/poktan')"
        :jumlah="$baris->total()" :paginator="$baris" :kata-kunci="$cari" :aksi-url="route('poktan.index')"
        placeholder-cari="Cari nama poktan atau ketua" judul-kosong="Belum ada kelompok tani"
        pesan-kosong="Kelompok tani akan tampil di sini setelah didata.">

        <x-slot:aksi>
            <x-sim.aksi-daftar modal-impor="imporPoktan"
                modal-tambah="formTambahPoktan" label-tambah="Tambah Poktan" />
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Jumlah Poktan" :nilai="$totalPoktan" satuan="kelompok" />
            <x-sim.stat-card label="Total Anggota" :nilai="number_format($totalAnggota, 0, ',', '.')" satuan="orang" />
            <x-sim.stat-card label="Anggota Terdata" :nilai="$anggotaTerdata"
                keterangan="Tercatat rinci pada sistem" />
            <x-sim.stat-card label="Anggota Aktif" :nilai="$anggotaAktif"
                keterangan="Berhak menerima penyaluran saprotan" />
        </x-slot:ringkasan>

        <x-slot:filter>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
                <x-sim.tombol-filter :ada-filter="$adaFilter" :url-bersih="route('poktan.index')" />
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama Poktan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Ketua</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Satuan Permukiman</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Berdiri</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Anggota</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $p)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3">
                    <a href="{{ route('poktan.detail', $p['id_poktan']) }}"
                        class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                        {{ $p['nama'] }}
                    </a>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    {{ $p['nama_ketua'] }}
                    <p class="mt-0.5 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                        {{ $p['telepon_ketua'] }}</p>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $p['satuan_permukiman'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $p['tahun_berdiri'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $p['jumlah_anggota'] }} orang</td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris :rincian-url="route('poktan.detail', $p['id_poktan'])"
                        modal-ubah="formUbahPoktanBaris"
                        :data-baris="$p + ['id' => $p['id_poktan']]"
                        :hapus-url="'/poktan/' . $p['id_poktan']"
                        konfirmasi-hapus="hapusPoktan"
                        :label="$p['nama']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $p)
                <div class="p-4">
                    <a href="{{ route('poktan.detail', $p['id_poktan']) }}"
                        class="rounded focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $p['nama'] }}</p>
                    </a>
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        Ketua {{ $p['nama_ketua'] }} &middot; {{ $p['jumlah_anggota'] }} anggota
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahPoktan" judul="Tambah Kelompok Tani"
        keterangan="Ketua dipilih dari data transmigran agar tautannya tetap sahih."
        :aksi="route('poktan.simpan')" ukuran="lg"
        :langkah="['Identitas Kelompok', 'Pengurus & Legalitas', 'Anggota Kelompok']"
        label-simpan="Simpan Data">
        @include('pages.poktan.form', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahPoktanBaris" judul="Ubah Profil Poktan"
        keterangan="Perubahan tercatat pada audit log."
        pola-aksi="/poktan/:id" metode="PUT" ukuran="lg"
        :langkah="['Identitas Kelompok', 'Pengurus & Legalitas', 'Anggota Kelompok']"
        label-simpan="Simpan Perubahan">
        @include('pages.poktan.form', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusPoktan" judul="Hapus kelompok tani ini?"
        pesan="Riwayat keanggotaan dan penyaluran saprotan ikut kehilangan induknya." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporPoktan" judul="Impor Kelompok Tani"
        entitas="poktan"
        :kolom-wajib="['nama_poktan', 'satuan_permukiman', 'tanggal_berdiri', 'nik_ketua']" />
@endsection
