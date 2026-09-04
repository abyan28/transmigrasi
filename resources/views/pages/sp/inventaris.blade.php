{{--
    Inventaris SP, yaitu barang bergerak milik satuan permukiman.

    Dipisah dari fasilitas SP agar rekap aset kawasan dapat dibedakan antara
    barang bergerak dan bangunan tetap (agents/rules.md bagian 4b poin 1).
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `sp.inventaris`.
        Lihat routes/web.php.
    --}}

    <x-sim.halaman-daftar judul="Inventaris SP"
        keterangan="Barang bergerak milik satuan permukiman beserta status penyerahannya."
        :remah="\App\Helpers\RemahHelper::untuk('/sp/inventaris')"
        :jumlah="$baris->total()" :paginator="$baris" :kata-kunci="$cari" :aksi-url="route('sp.inventaris')"
        placeholder-cari="Cari nama barang" judul-kosong="Belum ada data inventaris"
        pesan-kosong="Barang milik satuan permukiman akan tampil di sini setelah didata.">

        <x-slot:aksi>
            <x-sim.aksi-daftar modal-impor="imporInventaris"
                modal-tambah="formTambahInventaris" label-tambah="Tambah Inventaris" />
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Jenis Barang" :nilai="$jenisBarang" />
            <x-sim.stat-card label="Total Unit" :nilai="number_format($totalUnit, 0, ',', '.')" />
            <x-sim.stat-card label="Sudah Diserahkan" :nilai="$sudahDiserahkan"
                :keterangan="'dari ' . $jenisBarang . ' jenis barang'" />
            <x-sim.stat-card label="Perlu Perhatian" :nilai="$perluPerhatian"
                keterangan="Kondisi rusak ringan atau berat" />
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
                <div>
                    <label for="filter_status"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Status Penyerahan</label>
                    <select id="filter_status" name="status_penyerahan"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua status</option>
                        @foreach ($opsiFilterStatusPenyerahan as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($filterStatus === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <x-sim.tombol-filter :ada-filter="$adaFilter" :url-bersih="route('sp.inventaris')" />
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama Barang</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Satuan Permukiman</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tahun</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Sumber Dana</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kondisi</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Penyerahan</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $b)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                    {{ $b['nama_barang'] }}
                    @if ($b['keterangan'])
                        <p class="mt-0.5 text-theme-xs font-normal text-gray-500 dark:text-gray-400">
                            {{ $b['keterangan'] }}</p>
                    @endif
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $b['satuan_permukiman'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $b['jumlah'] }} {{ $b['satuan_barang'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $b['tahun_perolehan'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $b['sumber_dana'] }}</td>
                <td class="px-5 py-3">
                    <x-sim.status-badge :status="\App\Enums\Kondisi::from($b['kondisi'])" />
                    @if (count($b['rincian_kondisi']) > 1)
                        <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ collect($b['rincian_kondisi'])->filter()->map(fn ($n, $k) => $n . ' ' . \Illuminate\Support\Str::lower($k))->implode(', ') }}
                        </span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    <x-sim.status-badge :status="\App\Enums\StatusPenyerahan::from($b['status_penyerahan'])" />
                </td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris :rincian-url="route('sp.inventaris.detail', $b['id_inventaris_sp'])"
                        modal-ubah="formUbahInventarisBaris"
                        :data-baris="$b + ['id' => $b['id_inventaris_sp']]"
                        :hapus-url="'/sp/inventaris/' . $b['id_inventaris_sp']"
                        konfirmasi-hapus="hapusInventaris"
                        :label="$b['nama_barang']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $b)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $b['nama_barang'] }}</p>
                        <x-sim.status-badge :status="\App\Enums\Kondisi::from($b['kondisi'])" ukuran="sm" />
                    </div>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $b['jumlah'] }} {{ $b['satuan_barang'] }} &middot; {{ $b['satuan_permukiman'] }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahInventaris" judul="Tambah Inventaris SP"
        keterangan="Barang bergerak milik satuan permukiman."
        :aksi="route('inventaris.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.sp.form-inventaris', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahInventarisBaris" judul="Ubah Inventaris SP"
        keterangan="Perubahan tercatat pada audit log."
        pola-aksi="/sp/inventaris/:id" metode="PUT" ukuran="lg" label-simpan="Simpan Perubahan">
        @include('pages.sp.form-inventaris', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusInventaris" judul="Hapus data inventaris ini?"
        pesan="Data yang dihapus masih tercatat pada audit log." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporInventaris" judul="Impor Inventaris SP"
        entitas="inventaris-sp"
        :kolom-wajib="['satuan_permukiman', 'nama_barang', 'jumlah', 'satuan', 'kondisi']" />
@endsection
