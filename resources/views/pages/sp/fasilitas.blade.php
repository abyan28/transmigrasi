{{--
    Fasilitas SP, yaitu bangunan dan sarana tetap milik satuan permukiman.

    Berbeda dari inventaris yang mencatat barang bergerak, fasilitas menyimpan
    titik koordinat karena letaknya tetap dan perlu dipetakan
    (agents/data-dictionary.md bagian 4.2).
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `sp.fasilitas`.
        Lihat routes/web.php.
    --}}

    <x-sim.halaman-daftar judul="Fasilitas SP"
        keterangan="Bangunan dan sarana tetap milik satuan permukiman."
        :remah="\App\Helpers\RemahHelper::untuk('/sp/fasilitas')"
        :jumlah="$baris->total()" :paginator="$baris" :kata-kunci="$cari" :aksi-url="route('sp.fasilitas')"
        placeholder-cari="Cari nama fasilitas" judul-kosong="Belum ada data fasilitas"
        pesan-kosong="Bangunan dan sarana milik satuan permukiman akan tampil di sini setelah didata.">

        <x-slot:aksi>
            <x-sim.aksi-daftar modal-impor="imporFasilitas"
                modal-tambah="formTambahFasilitas" label-tambah="Tambah Fasilitas" />
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Jenis Fasilitas" :nilai="$jenisFasilitas" />
            <x-sim.stat-card label="Total Unit" :nilai="number_format($totalUnit, 0, ',', '.')" />
            <x-sim.stat-card label="Kondisi Baik" :nilai="$kondisiBaik" />
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
                        @foreach ($daftarSp as $sp)
                            <option value="{{ $sp['id_satuan_permukiman'] }}"
                                @selected($filterSp == $sp['id_satuan_permukiman'])>{{ $sp['nama'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_kondisi"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Kondisi</label>
                    <select id="filter_kondisi" name="kondisi"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua kondisi</option>
                        @foreach ($opsiFilterKondisi as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($filterKondisi === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <x-sim.tombol-filter :ada-filter="$adaFilter" :url-bersih="route('sp.fasilitas')" />
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama Fasilitas</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Satuan Permukiman</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tahun</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Koordinat</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kondisi</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Penyerahan</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $b)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                    {{ $b['nama_fasilitas'] }}
                    @if ($b['keterangan'])
                        <p class="mt-0.5 text-theme-xs font-normal text-gray-500 dark:text-gray-400">
                            {{ $b['keterangan'] }}</p>
                    @endif
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $b['satuan_permukiman'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">{{ $b['jumlah'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $b['tahun_perolehan'] }}</td>
                <td class="px-5 py-3 text-theme-xs tabular-nums text-gray-600 dark:text-gray-400">
                    {{ number_format($b['lintang'], 6, '.', '') }},<br>{{ number_format($b['bujur'], 6, '.', '') }}
                    <x-sim.tautan-peta class="mt-1" :lintang="$b['lintang']" :bujur="$b['bujur']"
                        :label="$b['nama_fasilitas']" />
                </td>
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
                    <x-sim.aksi-baris :rincian-url="route('sp.fasilitas.detail', $b['id_fasilitas_sp'])"
                        modal-ubah="formUbahFasilitasBaris"
                        :data-baris="$b + ['id' => $b['id_fasilitas_sp']]"
                        :hapus-url="'/sp/fasilitas/' . $b['id_fasilitas_sp']"
                        konfirmasi-hapus="hapusFasilitas"
                        :label="$b['nama_fasilitas']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $b)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $b['nama_fasilitas'] }}</p>
                        <x-sim.status-badge :status="\App\Enums\Kondisi::from($b['kondisi'])" ukuran="sm" />
                    </div>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $b['jumlah'] }} unit &middot; {{ $b['satuan_permukiman'] }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahFasilitas" judul="Tambah Fasilitas SP"
        keterangan="Bangunan dan fasilitas tetap yang menempel pada lokasi."
        :aksi="route('fasilitas.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.sp.form-fasilitas', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahFasilitasBaris" judul="Ubah Fasilitas SP"
        keterangan="Perubahan tercatat pada audit log."
        pola-aksi="/sp/fasilitas/:id" metode="PUT" ukuran="lg" label-simpan="Simpan Perubahan">
        @include('pages.sp.form-fasilitas', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusFasilitas" judul="Hapus data fasilitas ini?"
        pesan="Penilaian kondisi SP berikutnya tidak lagi menghitung fasilitas ini." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporFasilitas" judul="Impor Fasilitas SP"
        entitas="fasilitas-sp"
        :kolom-wajib="['satuan_permukiman', 'jenis_fasilitas', 'nama', 'kondisi']" />
@endsection
