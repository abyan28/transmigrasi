{{--
    Riwayat penanaman.

    Mencatat lahan mana ditanami komoditas apa pada musim apa. Menjadi jembatan
    antara lahan dan hasil panen: hasil panen menaut ke riwayat tanam, bukan
    langsung ke lahan, sehingga lokasi produksi terbaca lewat rantai
    riwayat tanam, lahan, satuan permukiman
    (agents/data-dictionary.md bagian 9.3).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::riwayatTanam();

        $cari = trim((string) request('cari', ''));
        $filterSp = request('sp');
        $filterMusim = request('musim_tanam');
        $filterKomoditas = request('komoditas');

        $baris = array_values(array_filter($semua, function ($r) use ($cari, $filterSp, $filterMusim, $filterKomoditas) {
            if ($cari !== '' && ! str_contains(mb_strtolower($r['petani']), mb_strtolower($cari))
                && ! str_contains(mb_strtolower($r['kode_lahan']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterSp && (string) $r['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }
            if ($filterMusim && $r['musim_tanam'] !== $filterMusim) {
                return false;
            }
            if ($filterKomoditas && $r['komoditas'] !== $filterKomoditas) {
                return false;
            }

            return true;
        }));

        $adaFilter = $cari !== '' || $filterSp || $filterMusim || $filterKomoditas;
        $totalLuas = array_sum(array_column($baris, 'luas_tanam'));
        $daftarMusim = array_values(array_unique(array_column($semua, 'musim_tanam')));
        $daftarKomoditas = array_values(array_unique(array_column($semua, 'komoditas')));
    @endphp

    <x-sim.halaman-daftar judul="Riwayat Tanam"
        keterangan="Catatan penanaman per lahan, musim, dan komoditas."
        :remah="[['label' => 'Pertanian'], ['label' => 'Riwayat Tanam']]"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('riwayat-tanam')"
        placeholder-cari="Cari petani atau kode lahan" judul-kosong="Belum ada riwayat tanam"
        pesan-kosong="Catatan penanaman akan tampil di sini setelah dicatat petugas.">

        <x-slot:aksi>
            {{--
                Impor massal diletakkan mendahului tombol tambah namun bergaya
                sekunder, sebab menambah satu data tetap tindakan yang paling
                sering dipakai (PRD 8.1).
            --}}
            <button type="button" @click="$dispatch('buka-modal', 'imporRiwayatTanam')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Impor Data
            </button>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahRiwayatTanam')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Catat Penanaman
            </button>
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Catatan Tanam" :nilai="count($semua)" />
            <x-sim.stat-card label="Luas Ditanami"
                :nilai="number_format(array_sum(array_column($semua, 'luas_tanam')), 2, ',', '.')" satuan="ha" />
            <x-sim.stat-card label="Musim Tercatat" :nilai="count($daftarMusim)" />
            <x-sim.stat-card label="Komoditas Ditanam" :nilai="count($daftarKomoditas)" />
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
                    <label for="filter_musim"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Musim Tanam</label>
                    <select id="filter_musim" name="musim_tanam"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua musim</option>
                        @foreach ($daftarMusim as $m)
                            <option value="{{ $m }}" @selected($filterMusim === $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_komoditas"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Komoditas</label>
                    <select id="filter_komoditas" name="komoditas"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua komoditas</option>
                        @foreach ($daftarKomoditas as $k)
                            <option value="{{ $k }}" @selected($filterKomoditas === $k)>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="h-10 flex-1 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Terapkan Filter
                    </button>
                    @if ($adaFilter)
                        <a href="{{ route('riwayat-tanam') }}"
                            class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Bersihkan
                        </a>
                    @endif
                </div>
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Lahan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Petani</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Musim Tanam</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Komoditas</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Luas Tanam (ha)</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tanggal Tanam</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $r)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3">
                    <a href="{{ route('lahan.detail', $r['lahan_id']) }}"
                        class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                        {{ $r['kode_lahan'] }}
                    </a>
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $r['satuan_permukiman'] }}</p>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $r['petani'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $r['musim_tanam'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $r['komoditas'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ number_format($r['luas_tanam'], 2, ',', '.') }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    {{ \Illuminate\Support\Carbon::parse($r['tanggal_tanam'])->translatedFormat('d M Y') }}</td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris modal-ubah="formUbahRiwayatBaris"
                        :data-baris="$r + ['id' => $r['id_riwayat_tanam']]"
                        :hapus-url="'/riwayat-tanam/' . $r['id_riwayat_tanam']"
                        konfirmasi-hapus="hapusRiwayat"
                        :label="$r['komoditas']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kaki>
            <tr class="motif-baris-total">
                <td colspan="4" class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                    Total luas ditanami</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                    {{ number_format($totalLuas, 2, ',', '.') }}</td>
                {{-- Dua sel kosong: kolom Tanggal Tanam dan kolom Aksi tidak punya total --}}
                <td></td>
                <td></td>
            </tr>
        </x-slot:kaki>

        <x-slot:kartu>
            @foreach ($baris as $r)
                <div class="p-4">
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">
                        {{ $r['komoditas'] }} di {{ $r['kode_lahan'] }}
                    </p>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $r['musim_tanam'] }} &middot; {{ number_format($r['luas_tanam'], 2, ',', '.') }} ha
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahRiwayatTanam" judul="Catat Riwayat Tanam"
        keterangan="Lahan menentukan lokasi produksi yang dibaca hasil panen."
        :aksi="route('riwayat-tanam.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.komoditas.form-riwayat-tanam', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahRiwayatBaris" judul="Ubah Riwayat Tanam"
        keterangan="Perubahan tercatat pada audit log."
        pola-aksi="/riwayat-tanam/:id" metode="PUT" ukuran="lg" label-simpan="Simpan Perubahan">
        @include('pages.komoditas.form-riwayat-tanam', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusRiwayat" judul="Hapus catatan penanaman ini?"
        pesan="Hasil panen yang menaut catatan ini akan kehilangan lokasi produksinya." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporRiwayatTanam" judul="Impor Riwayat Tanam"
        entitas="riwayat-tanam"
        :kolom-wajib="['lahan', 'komoditas', 'musim_tanam', 'tanggal_tanam', 'luas_tanam_ha']" />
@endsection
