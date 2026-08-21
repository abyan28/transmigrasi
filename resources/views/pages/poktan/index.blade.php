{{--
    Daftar kelompok tani.

    Poktan menjadi penerima bantuan alsintan dan saprotan, sehingga jumlah
    anggota aktifnya berpengaruh langsung pada penyaluran
    (agents/rules.md bagian 7a dan 7c).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::poktan();
        $anggota = DummyData::anggotaPoktan();

        $cari = trim((string) request('cari', ''));
        $filterSp = request('sp');

        $baris = array_values(array_filter($semua, function ($p) use ($cari, $filterSp) {
            if ($cari !== '' && ! str_contains(mb_strtolower($p['nama']), mb_strtolower($cari))
                && ! str_contains(mb_strtolower($p['nama_ketua']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterSp && (string) $p['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }

            return true;
        }));

        $adaFilter = $cari !== '' || $filterSp;
        $totalAnggota = array_sum(array_column($semua, 'jumlah_anggota'));
        $anggotaAktif = count(array_filter($anggota, fn ($a) => $a['status'] === 'Aktif'));
    @endphp

    <x-sim.halaman-daftar judul="Kelompok Tani"
        keterangan="Poktan di kawasan beserta ketua dan jumlah anggotanya."
        :remah="\App\Helpers\RemahHelper::untuk('/poktan')"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('poktan.index')"
        placeholder-cari="Cari nama poktan atau ketua" judul-kosong="Belum ada kelompok tani"
        pesan-kosong="Kelompok tani akan tampil di sini setelah didata.">

        <x-slot:aksi>
            {{--
                Impor massal diletakkan mendahului tombol tambah namun bergaya
                sekunder, sebab menambah satu data tetap tindakan yang paling
                sering dipakai (PRD 8.1).
            --}}
            <button type="button" @click="$dispatch('buka-modal', 'imporPoktan')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Impor Data
            </button>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahPoktan')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Poktan
            </button>
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Jumlah Poktan" :nilai="count($semua)" satuan="kelompok" />
            <x-sim.stat-card label="Total Anggota" :nilai="number_format($totalAnggota, 0, ',', '.')" satuan="orang" />
            <x-sim.stat-card label="Anggota Terdata" :nilai="count($anggota)"
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
                        @foreach (DummyData::satuanPermukiman() as $sp)
                            <option value="{{ $sp['id_satuan_permukiman'] }}"
                                @selected($filterSp == $sp['id_satuan_permukiman'])>{{ $sp['nama'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="h-10 flex-1 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Terapkan Filter
                    </button>
                    @if ($adaFilter)
                        <a href="{{ route('poktan.index') }}"
                            class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Bersihkan
                        </a>
                    @endif
                </div>
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
        :aksi="route('poktan.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.poktan.form', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahPoktanBaris" judul="Ubah Profil Poktan"
        keterangan="Perubahan tercatat pada audit log."
        pola-aksi="/poktan/:id" metode="PUT" ukuran="lg" label-simpan="Simpan Perubahan">
        @include('pages.poktan.form', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusPoktan" judul="Hapus kelompok tani ini?"
        pesan="Riwayat keanggotaan dan penyaluran saprotan ikut kehilangan induknya." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporPoktan" judul="Impor Kelompok Tani"
        entitas="poktan"
        :kolom-wajib="['nama_poktan', 'satuan_permukiman', 'tanggal_berdiri', 'nik_ketua']" />
@endsection
