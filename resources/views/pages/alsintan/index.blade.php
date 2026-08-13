{{--
    Daftar alat dan mesin pertanian.

    Sistem membedakan alsintan milik pribadi transmigran dan bantuan
    pemerintah yang disalurkan lewat poktan (agents/rules.md bagian 7b poin 1),
    karena keduanya berbeda pemilik dan berbeda jalur pertanggungjawabannya.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::alsintan();

        $cari = trim((string) request('cari', ''));
        $filterSp = request('sp');
        $filterKepemilikan = request('kepemilikan');
        $filterKondisi = request('kondisi');

        $baris = array_values(array_filter($semua, function ($a) use ($cari, $filterSp, $filterKepemilikan, $filterKondisi) {
            if ($cari !== '' && ! str_contains(mb_strtolower($a['nama_alat']), mb_strtolower($cari))
                && ! str_contains(mb_strtolower($a['pemilik']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterSp && (string) $a['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }
            if ($filterKepemilikan && $a['kepemilikan'] !== $filterKepemilikan) {
                return false;
            }
            if ($filterKondisi && $a['kondisi'] !== $filterKondisi) {
                return false;
            }

            return true;
        }));

        $adaFilter = $cari !== '' || $filterSp || $filterKepemilikan || $filterKondisi;
        $totalUnit = array_sum(array_column($semua, 'jumlah'));
        $bantuan = count(array_filter($semua, fn ($a) => $a['kepemilikan'] === \App\Enums\KepemilikanAlsintan::BantuanPoktan->value));
        $rusak = count(array_filter($semua, fn ($a) => $a['kondisi'] !== 'Baik'));
    @endphp

    <x-sim.halaman-daftar judul="Alsintan"
        keterangan="Alat dan mesin pertanian milik pribadi maupun bantuan lewat kelompok tani."
        :remah="[['label' => 'Kelembagaan'], ['label' => 'Alsintan']]"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('alsintan.index')"
        placeholder-cari="Cari nama alat atau pemilik" judul-kosong="Belum ada data alsintan"
        pesan-kosong="Alat dan mesin pertanian akan tampil di sini setelah didata.">

        <x-slot:aksi>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahAlsintan')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Alsintan
            </button>
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Jenis Alat" :nilai="count($semua)" />
            <x-sim.stat-card label="Total Unit" :nilai="number_format($totalUnit, 0, ',', '.')" />
            <x-sim.stat-card label="Bantuan Pemerintah" :nilai="$bantuan"
                keterangan="Disalurkan lewat kelompok tani" />
            <x-sim.stat-card label="Perlu Perbaikan" :nilai="$rusak"
                keterangan="Rusak ringan atau rusak berat" />
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
                    <label for="filter_kepemilikan"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Kepemilikan</label>
                    <select id="filter_kepemilikan" name="kepemilikan"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua kepemilikan</option>
                        @foreach (\App\Enums\KepemilikanAlsintan::opsi() as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($filterKepemilikan === $nilai)>{{ $label }}</option>
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
                        <a href="{{ route('alsintan.index') }}"
                            class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Bersihkan
                        </a>
                    @endif
                </div>
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama Alat</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kepemilikan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Pemilik</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tahun</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kondisi</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Verifikasi</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $a)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $a['nama_alat'] }}</td>
                <td class="px-5 py-3">
                    <span class="rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $a['kepemilikan'] === \App\Enums\KepemilikanAlsintan::BantuanPoktan->value ? 'bg-teal-50 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300' : 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300' }}">
                        {{ $a['kepemilikan'] }}
                    </span>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    @if ($a['poktan_id'])
                        <a href="{{ route('poktan.detail', $a['poktan_id']) }}"
                            class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                            {{ $a['pemilik'] }}
                        </a>
                    @elseif ($a['transmigran_id'])
                        <a href="{{ route('transmigran.detail', $a['transmigran_id']) }}"
                            class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                            {{ $a['pemilik'] }}
                        </a>
                    @else
                        {{ $a['pemilik'] }}
                    @endif
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $a['satuan_permukiman'] }}</p>
                </td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">{{ $a['jumlah'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $a['tahun_perolehan'] }}</td>
                <td class="px-5 py-3">
                    <x-sim.status-badge :status="\App\Enums\Kondisi::from($a['kondisi'])" />
                </td>
                <td class="px-5 py-3">
                    <x-sim.status-badge :status="\App\Enums\StatusVerifikasi::from($a['status_verifikasi'])" />
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
        keterangan="Alat baru tercatat menunggu verifikasi dinas."
        :aksi="route('alsintan.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.alsintan.form', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahAlsintanBaris" judul="Ubah Data Alsintan"
        keterangan="Pemilik menyesuaikan jenis kepemilikan yang dipilih."
        pola-aksi="/alsintan/:id" metode="PUT" ukuran="lg"
        label-simpan="Simpan Perubahan">
        @include('pages.alsintan.form', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusAlsintan" judul="Hapus data ini?"
        pesan="Data yang dihapus masih tercatat pada audit log dan dapat dipulihkan admin." label-setuju="Hapus" />
@endsection
