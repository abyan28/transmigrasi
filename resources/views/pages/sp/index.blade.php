{{--
    Daftar satuan permukiman.

    SP adalah simpul tempat kedua cabang hierarki wilayah bertemu, dan seluruh
    data operasional menaut ke sini, tidak pernah langsung ke desa maupun
    kawasan (agents/rules.md bagian 4a poin 6).
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `sp.index`, termasuk peta
        `$kondisi` berisi indikator ke-16. Lihat routes/web.php.
    --}}

    <x-sim.halaman-daftar judul="Satuan Permukiman"
        keterangan="Enam satuan permukiman di Kawasan Transmigrasi Kobalima Timur."
        :remah="\App\Helpers\RemahHelper::untuk('/sp')"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('sp.index')"
        placeholder-cari="Cari nama SP atau desa" judul-kosong="Belum ada data satuan permukiman">

        <x-slot:aksi>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahSp')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah SP
            </button>
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Jumlah SP" :nilai="count($semua)" satuan="SP" />
            <x-sim.stat-card label="Total Luas Lahan" :nilai="number_format($totalLuas, 2, ',', '.')" satuan="ha" />
            <x-sim.stat-card label="Daya Tampung" :nilai="number_format($totalRencana, 0, ',', '.')" satuan="KK" />
            <x-sim.stat-card label="Sudah Terisi" :nilai="number_format($totalTerisi, 0, ',', '.')" satuan="KK"
                :keterangan="round($totalTerisi / $totalRencana * 100) . '% dari daya tampung'" />
        </x-slot:ringkasan>

        <x-slot:filter>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="filter_kecamatan"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Kecamatan</label>
                    <select id="filter_kecamatan" name="kecamatan"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua kecamatan</option>
                        @foreach ($daftarKecamatan as $k)
                            <option value="{{ $k }}" @selected($filterKecamatan === $k)>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <x-sim.tombol-filter :ada-filter="$adaFilter" :url-bersih="route('sp.index')" />
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama SP</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Desa</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kecamatan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Penempatan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Luas (ha)</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Keterisian</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kondisi</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $sp)
            @php $persen = round($sp['jumlah_kk_terisi'] / $sp['jumlah_kk_rencana'] * 100); @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3">
                    <a href="{{ route('sp.detail', $sp['id_satuan_permukiman']) }}"
                        class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                        {{ $sp['nama'] }}
                    </a>
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $sp['kode_sp'] }}</p>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $sp['desa'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $sp['kecamatan'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $sp['tahun_penempatan'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ number_format($sp['luas_lahan'], 2, ',', '.') }}</td>
                <td class="px-5 py-3">
                    <span class="text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                        {{ $sp['jumlah_kk_terisi'] }} / {{ $sp['jumlah_kk_rencana'] }}
                    </span>
                    <div class="mt-1 h-1.5 w-24 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10"
                        role="img" aria-label="Keterisian {{ $persen }} persen">
                        <div class="h-full rounded-full bg-brand-500" style="width: {{ $persen }}%"></div>
                    </div>
                </td>
                <td class="px-5 py-3">
                    @php $k = $kondisi[$sp['id_satuan_permukiman']] ?? null; @endphp
                    @if ($k)
                        <x-sim.status-badge :status="$k['status']" />
                        <p class="mt-1 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                            skor {{ number_format($k['skor'], 0, ',', '.') }}
                        </p>
                    @else
                        <span class="text-theme-xs text-gray-500 dark:text-gray-400">-</span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris :rincian-url="route('sp.detail', $sp['id_satuan_permukiman'])"
                        modal-ubah="formUbahSpBaris"
                        :data-baris="$sp + ['id' => $sp['id_satuan_permukiman']]"
                        :hapus-url="'/sp/' . $sp['id_satuan_permukiman']"
                        konfirmasi-hapus="hapusSp"
                        :label="$sp['nama']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kaki>
            <tr class="motif-baris-total">
                <td colspan="4" class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">Total</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                    {{ number_format(array_sum(array_column($baris, 'luas_lahan')), 2, ',', '.') }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                    {{ array_sum(array_column($baris, 'jumlah_kk_terisi')) }} /
                    {{ array_sum(array_column($baris, 'jumlah_kk_rencana')) }}</td>
                {{-- Dua sel kosong: kolom Kondisi dan kolom Aksi tidak punya total --}}
                <td></td>
                <td></td>
            </tr>
        </x-slot:kaki>

        <x-slot:kartu>
            @foreach ($baris as $sp)
                <div class="p-4">
                    <a href="{{ route('sp.detail', $sp['id_satuan_permukiman']) }}"
                        class="rounded focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $sp['nama'] }}</p>
                    </a>
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $sp['desa'] }}, {{ $sp['kecamatan'] }}
                    </p>
                    <p class="mt-2 text-theme-xs tabular-nums text-gray-600 dark:text-gray-400">
                        {{ $sp['jumlah_kk_terisi'] }} dari {{ $sp['jumlah_kk_rencana'] }} KK
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahSp" judul="Tambah Satuan Permukiman"
        keterangan="Satu SP menempel pada desa sekaligus kawasan transmigrasi."
        :aksi="route('sp.simpan')" ukuran="xl"
        :langkah="['Identitas & Wilayah', 'Lokasi & Batas', 'Keadaan Alam & Iklim', 'Aksesibilitas & Berkas']"
        label-simpan="Simpan Data">
        @include('pages.sp.form', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahSpBaris" judul="Ubah Satuan Permukiman"
        keterangan="Perubahan tercatat pada audit log."
        pola-aksi="/sp/:id" metode="PUT" ukuran="xl"
        :langkah="['Identitas & Wilayah', 'Lokasi & Batas', 'Keadaan Alam & Iklim', 'Aksesibilitas & Berkas']"
        label-simpan="Simpan Perubahan">
        @include('pages.sp.form', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusSp" judul="Hapus satuan permukiman ini?"
        pesan="Seluruh data yang menaut SP ini ikut kehilangan induknya." label-setuju="Hapus" />
@endsection
