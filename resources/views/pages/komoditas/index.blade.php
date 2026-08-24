{{--
    Data master komoditas.

    Setiap komoditas wajib punya satuan panen baku (agents/rules.md bagian 8
    poin 4). Satuan itulah yang dipakai form hasil panen, sehingga jagung
    selalu tercatat dalam ton dan cabai dalam kilogram.

    Komoditas unggulan ditandai memakai aksen gold, salah satu dari empat
    pemakaian sah aksen tunggal (agents/ui-spec.md bagian 2.4).

    DUA ISTILAH YANG BERBEDA dan sengaja tidak disatukan:

    - **Komoditas utama** pada dashboard adalah yang volumenya terbesar saat
      ini. Dihitung dari data panen, berubah mengikuti musim.
    - **Komoditas unggulan** di halaman ini ditetapkan menurut proposal atau
      kebijakan dinas (`rules.md` 8.1). Ditandai petugas, dan tidak berubah
      hanya karena panen satu musim naik atau turun.

    Keduanya kebetulan sama-sama menunjuk jagung, tetapi tidak menggantikan
    satu sama lain. Komoditas prioritas program yang volumenya masih kecil
    tetap sah ditandai unggulan.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::komoditas();
        $sebaran = DummyData::sebaranKomoditas();

        $cari = trim((string) request('cari', ''));
        $filterTipe = request('tipe');

        $baris = array_values(array_filter($semua, function ($k) use ($cari, $filterTipe) {
            if ($cari !== '' && ! str_contains(mb_strtolower($k['nama']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterTipe && $k['tipe'] !== $filterTipe) {
                return false;
            }

            return true;
        }));

        $adaFilter = $cari !== '' || $filterTipe;
        $unggulan = count(array_filter($semua, fn ($k) => $k['is_unggulan']));
    @endphp

    <x-sim.halaman-daftar judul="Data Komoditas"
        keterangan="Komoditas kawasan beserta satuan panen bakunya."
        :remah="\App\Helpers\RemahHelper::untuk('/komoditas')"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('komoditas.index')"
        placeholder-cari="Cari nama komoditas" judul-kosong="Belum ada data komoditas"
        pesan-kosong="Komoditas kawasan akan tampil di sini setelah didata.">

        <x-slot:aksi>
            {{--
                Impor massal diletakkan mendahului tombol tambah namun bergaya
                sekunder, sebab menambah satu data tetap tindakan yang paling
                sering dipakai (PRD 8.1).
            --}}
            <button type="button" @click="$dispatch('buka-modal', 'imporKomoditas')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Impor Data
            </button>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahKomoditas')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Komoditas
            </button>
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Jenis Komoditas" :nilai="count($semua)" />
            {{--
                Dua kartu berikut bersumber dari hal yang berbeda: jumlah
                unggulan berasal dari penandaan petugas, sedangkan total panen
                dari data produksi. Keterangannya ditulis agar keduanya tidak
                terbaca sebagai dua sisi angka yang sama.
            --}}
            <x-sim.stat-card label="Komoditas Unggulan" :nilai="$unggulan"
                keterangan="Ditandai menurut proposal atau kebijakan dinas" />
            <x-sim.stat-card label="Satuan Dipakai" :nilai="count(array_unique(array_column($semua, 'satuan')))" />
            <x-sim.stat-card label="Total Panen Tercatat"
                :nilai="number_format(array_sum($sebaran), 1, ',', '.')" satuan="ton"
                keterangan="Agregat kawasan seluruh komoditas" />
        </x-slot:ringkasan>

        <x-slot:filter>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="filter_tipe"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Tipe Komoditas</label>
                    <select id="filter_tipe" name="tipe"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua tipe</option>
                        @foreach (\App\Support\DummyData::opsiFilterReferensi(\App\Enums\JenisReferensi::TipeKomoditas) as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($filterTipe === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="h-10 flex-1 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Terapkan Filter
                    </button>
                    @if ($adaFilter)
                        <a href="{{ route('komoditas.index') }}"
                            class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Bersihkan
                        </a>
                    @endif
                </div>
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama Komoditas</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tipe</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Satuan Panen Baku</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Volume Tercatat</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Keterangan</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $k)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                        <span class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $k['nama'] }}</span>
                        @if ($k['is_unggulan'])
                            {{-- Aksen gold, salah satu dari empat pemakaian sah --}}
                            <span class="rounded-full bg-gold-100 px-2 py-0.5 text-theme-xs font-medium text-gold-800 dark:bg-gold-500/20 dark:text-gold-300">
                                Unggulan
                            </span>
                        @endif
                    </div>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $k['tipe'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $k['satuan'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{--
                        Dicocokkan LANGSUNG sejak 2026-08-24, sebab kunci
                        sebaran kini sama persis dengan nama pada data master.

                        Sebelumnya memakai `ucfirst(mb_strtolower(...))` yang
                        hanya mengapitalkan huruf PERTAMA, sehingga KACANG
                        TANAH dan UBI KAYU tidak pernah ketemu dan tampil
                        sebagai tanda hubung - terbaca "belum ada panen"
                        padahal artinya "kodenya tidak menemukan datanya".
                        Hanya nama satu kata yang kebetulan berhasil.
                    --}}
                    @php $vol = $sebaran[$k['nama']] ?? null; @endphp
                    {{ $vol !== null ? number_format($vol, 1, ',', '.') . ' ton' : '-' }}
                </td>
                <td class="px-5 py-3 text-theme-xs text-gray-500 dark:text-gray-400">{{ $k['deskripsi'] ?? '-' }}</td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris :rincian-url="route('komoditas.detail', $k['id_komoditas'])"
                        modal-ubah="formUbahKomoditasBaris"
                        :data-baris="$k + ['id' => $k['id_komoditas']]"
                        :hapus-url="'/komoditas/' . $k['id_komoditas']"
                        konfirmasi-hapus="hapusKomoditas" :label="$k['nama']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $k)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $k['nama'] }}</p>
                        @if ($k['is_unggulan'])
                            <span class="shrink-0 rounded-full bg-gold-100 px-2 py-0.5 text-theme-xs font-medium text-gold-800 dark:bg-gold-500/20 dark:text-gold-300">
                                Unggulan
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $k['tipe'] }} &middot; satuan {{ $k['satuan'] }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahKomoditas" judul="Tambah Komoditas"
        keterangan="Satuan panen yang dipilih menjadi satuan baku komoditas ini."
        :aksi="route('komoditas.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.komoditas.form', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
        Satuan panen ditetapkan di sini dan tidak dapat diubah operator saat mencatat panen.
        Aturan ini menjaga rekap lintas komoditas tetap sepadan setelah dikonversi ke ton.
    </p>

    <x-sim.modal-form nama="formUbahKomoditasBaris" judul="Ubah Data Komoditas"
        keterangan="Perubahan satuan baku berlaku bagi pencatatan panen berikutnya."
        pola-aksi="/komoditas/:id" metode="PUT" ukuran="lg"
        label-simpan="Simpan Perubahan">
        @include('pages.komoditas.form', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusKomoditas" judul="Hapus data ini?"
        pesan="Data yang dihapus masih tercatat pada audit log dan dapat dipulihkan admin." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporKomoditas" judul="Impor Data Komoditas"
        entitas="komoditas"
        :kolom-wajib="['nama_komoditas', 'jenis', 'satuan_baku']" />
@endsection
