{{--
    Daftar lahan pekarangan dan lahan usaha.

    Mengikuti pola CRUD modul transmigran. Tabel dilengkapi baris total luas
    memakai motif identitas, karena rekap luas wajib memakai penjumlahan
    seluruh lahan, bukan mengambil satu baris (agents/rules.md bagian 7.10).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::lahan();

        $cari = trim((string) request('cari', ''));
        $filterSp = request('sp');
        $filterJenis = request('jenis_lahan');
        $filterKategori = request('kategori_lahan');

        $baris = array_values(array_filter($semua, function ($l) use ($cari, $filterSp, $filterJenis, $filterKategori) {
            if ($cari !== '') {
                $cocok = str_contains(mb_strtolower((string) $l['kode_lahan']), mb_strtolower($cari))
                    || str_contains(mb_strtolower($l['pemilik']), mb_strtolower($cari));

                if (! $cocok) {
                    return false;
                }
            }

            if ($filterSp && (string) $l['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }

            if ($filterJenis && $l['jenis_lahan'] !== $filterJenis) {
                return false;
            }

            if ($filterKategori && ($l['kategori_lahan'] ?? null) !== $filterKategori) {
                return false;
            }

            return true;
        }));

        $adaFilter = $cari !== '' || $filterSp || $filterJenis || $filterKategori;

        $totalLuasTampil = array_sum(array_column($baris, 'luas'));
        $luasPekarangan = array_sum(array_column(array_filter($semua, fn ($l) => $l['jenis_lahan'] === 'Lahan Pekarangan'), 'luas'));
        $luasUsaha = array_sum(array_column(array_filter($semua, fn ($l) => $l['jenis_lahan'] === 'Lahan Usaha'), 'luas'));

        $bolehTambah = true;
        $bolehUbah = true;
        $bolehHapus = true;
    @endphp

    <x-sim.page-header judul="Data Lahan"
        keterangan="Lahan pekarangan dan lahan usaha milik keluarga transmigran."
        :remah="[['label' => 'Lahan'], ['label' => 'Daftar Lahan']]">
        <x-slot:aksi>
            {{--
                Impor massal diletakkan mendahului tombol tambah namun bergaya
                sekunder, sebab menambah satu data tetap tindakan yang paling
                sering dipakai (PRD 8.1).
            --}}
            <button type="button" @click="$dispatch('buka-modal', 'imporLahan')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Impor Data
            </button>
            @if ($bolehTambah)
                <button type="button" @click="$dispatch('buka-modal', 'formTambahLahan')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Data Lahan
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-sim.stat-card label="Total Bidang Lahan" :nilai="number_format(count($semua), 0, ',', '.')" satuan="bidang" />
        <x-sim.stat-card label="Total Luas"
            :nilai="number_format(array_sum(array_column($semua, 'luas')), 2, ',', '.')" satuan="ha" />
        <x-sim.stat-card label="Luas Lahan Usaha" :nilai="number_format($luasUsaha, 2, ',', '.')" satuan="ha" />
        <x-sim.stat-card label="Luas Lahan Pekarangan" :nilai="number_format($luasPekarangan, 2, ',', '.')"
            satuan="ha" />
    </div>

    <form method="GET" action="{{ route('lahan.index') }}">
        <x-sim.data-table :jumlah="count($baris)" :kata-kunci="$cari"
            placeholder-cari="Cari kode lahan atau pemilik" judul-kosong="Belum ada data lahan"
            pesan-kosong="Data lahan akan tampil di sini setelah ditambahkan.">

            <x-slot:filter>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="filter_sp"
                            class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                            Satuan Permukiman
                        </label>
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
                            class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                            Jenis Lahan
                        </label>
                        <select id="filter_jenis" name="jenis_lahan"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                            <option value="">Semua jenis</option>
                            @foreach (\App\Enums\JenisLahan::opsi() as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($filterJenis === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter_kategori"
                            class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                            Kategori Lahan
                        </label>
                        <select id="filter_kategori" name="kategori_lahan"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                            <option value="">Semua kategori</option>
                            @foreach (\App\Enums\KategoriLahan::opsi() as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($filterKategori === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                            class="h-10 flex-1 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                            Terapkan Filter
                        </button>
                        @if ($adaFilter)
                            <a href="{{ route('lahan.index') }}"
                                class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                Bersihkan
                            </a>
                        @endif
                    </div>
                </div>
            </x-slot:filter>

            <x-slot:aksiKanan>
                <button type="submit"
                    class="h-10 shrink-0 rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    Cari
                </button>

                    <x-sim.tombol-ekspor />
            </x-slot:aksiKanan>

            <x-slot:aksiKosong>
                @if ($adaFilter)
                    <a href="{{ route('lahan.index') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Bersihkan Filter
                    </a>
                @elseif ($bolehTambah)
                    <button type="button" @click="$dispatch('buka-modal', 'formTambahLahan')"
                        class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Tambah Data Lahan
                    </button>
                @endif
            </x-slot:aksiKosong>

            <x-slot:kepala>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kode</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Pemilik</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jenis</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kategori</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Luas (ha)</th>
                <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Aksi
                </th>
            </x-slot:kepala>

            @foreach ($baris as $l)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                    <td class="px-5 py-3">
                        <a href="{{ route('lahan.detail', $l['id_lahan']) }}"
                            class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                            {{ $l['kode_lahan'] }}
                        </a>
                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $l['satuan_permukiman'] }}
                        </p>
                    </td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $l['pemilik'] }}</td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $l['jenis_lahan'] }}</td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                        {{ $l['kategori_lahan'] ?? '-' }}
                    </td>
                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                        {{ number_format($l['luas'], 2, ',', '.') }}
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('lahan.detail', $l['id_lahan']) }}"
                                aria-label="Lihat rincian lahan {{ $l['kode_lahan'] }}"
                                class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>

                            @if ($bolehUbah)
                                {{-- Ubah sejajar dengan Hapus, sebab menghapus lebih berisiko daripada menyunting --}}
                                <button type="button"
                                    @click.prevent="$dispatch('buka-modal-baris', {
                                        nama: 'formUbahLahanBaris',
                                        data: @js($l + ['id' => $l['id_lahan']])
                                    })"
                                    aria-label="Ubah data {{ $l['kode_lahan'] }}"
                                    class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                    </svg>
                                </button>
                            @endif

                            @if ($bolehHapus)
                                <button type="button"
                                    @click.prevent="$dispatch('buka-konfirmasi', {
                                        nama: 'hapusLahan',
                                        aksi: '{{ route('lahan.hapus', $l['id_lahan']) }}'
                                    })"
                                    aria-label="Hapus data lahan {{ $l['kode_lahan'] }}"
                                    class="rounded-lg p-2 text-gray-500 transition hover:bg-red-50 hover:text-red-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-red-500/10">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach

            {{-- Baris total memakai motif identitas garis atas navy --}}
            <x-slot:kaki>
                <tr class="motif-baris-total">
                    <td colspan="4" class="px-5 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                        Total luas lahan yang ditampilkan
                    </td>
                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                        {{ number_format($totalLuasTampil, 2, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </x-slot:kaki>

            <x-slot:kartu>
                @foreach ($baris as $l)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <a href="{{ route('lahan.detail', $l['id_lahan']) }}"
                                class="min-w-0 rounded focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                    {{ $l['kode_lahan'] }}
                                </p>
                                <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $l['pemilik'] }}</p>
                            </a>
                            <span class="shrink-0 text-theme-sm tabular-nums text-gray-700 dark:text-gray-300">
                                {{ number_format($l['luas'], 2, ',', '.') }} ha
                            </span>
                        </div>
                        <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $l['jenis_lahan'] }}{{ $l['kategori_lahan'] ? ' , ' . $l['kategori_lahan'] : '' }}
                        </p>
                    </div>
                @endforeach
            </x-slot:kartu>
        </x-sim.data-table>
    </form>

    @if ($bolehTambah)
        <x-sim.modal-form nama="formTambahLahan" judul="Tambah Data Lahan"
            keterangan="Isian bertanda bintang wajib diisi." :aksi="route('lahan.simpan')" ukuran="xl"
            label-simpan="Simpan Data Lahan">
            @include('pages.lahan.form', ['awalan' => 'tambah'])
        </x-sim.modal-form>
    @endif

    @if ($bolehHapus)
        <x-sim.confirm-dialog nama="hapusLahan" judul="Hapus data lahan ini?"
            pesan="Dokumen HPL atau SHM yang tertaut ikut tidak dapat diakses sampai data dipulihkan."
            label-setuju="Hapus Data Lahan" />
    @endif

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahLahanBaris" judul="Ubah Data Lahan"
            keterangan="Perubahan tercatat pada audit log."
            pola-aksi="/lahan/:id" metode="PUT" ukuran="xl"
            label-simpan="Simpan Perubahan">
            @include('pages.lahan.form', ['awalan' => 'ubahBaris'])
        </x-sim.modal-form>
    @endif

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporLahan" judul="Impor Data Lahan"
        entitas="lahan"
        :kolom-wajib="['kode_lahan', 'satuan_permukiman', 'luas_ha', 'jenis_lahan', 'status_kepemilikan']" />
@endsection
