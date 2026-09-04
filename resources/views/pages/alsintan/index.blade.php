{{--
    Daftar alat dan mesin pertanian, SATU BARIS PER PENGADAAN (Putaran 7).

    Satu batch pengadaan lazim dibagikan ke beberapa poktan, bahkan lintas SP.
    Model lama membawa satu poktan_id pada tiap baris, sehingga satu batch
    diketik ulang per poktan. Kini poktan penerima tampil sebagai lencana di
    dalam baris; rinciannya per poktan ada di halaman detail (rules.md §7b).
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `alsintan.index`, termasuk
        penyaringan dan angka ringkasannya. Lihat routes/web.php.
    --}}
    <x-sim.halaman-daftar judul="Alsintan"
        keterangan="Alat dan mesin pertanian milik kelompok tani."
        :remah="\App\Helpers\RemahHelper::untuk('/alsintan')"
        :jumlah="$baris->total()" :paginator="$baris" :kata-kunci="$cari" :aksi-url="route('alsintan.index')"
        placeholder-cari="Cari nama alat, jenis, atau poktan" judul-kosong="Belum ada data alsintan"
        pesan-kosong="Alat dan mesin pertanian akan tampil di sini setelah didata.">

        <x-slot:aksi>
            <x-sim.aksi-daftar modal-impor="imporAlsintan"
                modal-tambah="formTambahAlsintan" label-tambah="Tambah Alsintan" />
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Pengadaan" :nilai="$pengadaan" />
            <x-sim.stat-card label="Total Unit" :nilai="number_format($totalUnit, 0, ',', '.')" />
            <x-sim.stat-card label="Belum Tersalur" :nilai="number_format($belumTersalur, 0, ',', '.')"
                keterangan="Unit di gudang UPT, belum dibagikan" />
            <x-sim.stat-card label="Poktan Penerima" :nilai="$poktanPenerima"
                keterangan="Kelompok yang menerima bagian" />
        </x-slot:ringkasan>

        <x-slot:filter>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
                <x-sim.tombol-filter :ada-filter="$adaFilter" :url-bersih="route('alsintan.index')" />
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Alat</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Poktan Penerima</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tahun Pengadaan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Sumber Dana</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $a)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3">
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $a['nama_alat'] }}</p>
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $a['jenis_alsintan'] }}</p>
                </td>
                <td class="px-5 py-3">
                    @forelse ($a['distribusi'] as $d)
                        <a href="{{ route('poktan.detail', $d['poktan_id']) }}"
                            class="mr-1 mb-1 inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-theme-xs text-gray-700 hover:bg-gray-200 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                            {{ $d['poktan'] }} <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ $d['jumlah'] }}</span>
                        </a>
                    @empty
                        <span class="text-theme-xs text-gray-400 dark:text-white/30">Belum tersalurkan</span>
                    @endforelse
                </td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $a['jumlah_total'] }}
                    @if ($a['jumlah_belum_tersalur'] > 0)
                        <span class="block text-theme-xs text-yellow-700 dark:text-yellow-400">{{ $a['jumlah_belum_tersalur'] }} belum tersalur</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $a['tahun_pengadaan'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $a['sumber_dana'] ?? '-' }}</td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris :rincian-url="route('alsintan.detail', $a['id_alsintan'])"
                        modal-ubah="formUbahAlsintanBaris"
                        :data-baris="['id' => $a['id_alsintan'], 'id_alsintan' => $a['id_alsintan'], 'jenis_alsintan' => $a['jenis_alsintan'], 'nama_alat' => $a['nama_alat'], 'jumlah_total' => $a['jumlah_total'], 'tahun_pengadaan' => $a['tahun_pengadaan'], 'sumber_dana' => $a['sumber_dana'], 'keterangan' => $a['keterangan']]"
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
                        <span class="text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">{{ $a['jumlah_total'] }} unit</span>
                    </div>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $a['jenis_alsintan'] }} &middot; {{ $a['tahun_pengadaan'] }} &middot;
                        {{ count($a['distribusi']) > 0 ? implode(', ', $a['poktan_penerima']) : 'Belum tersalurkan' }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahAlsintan" judul="Tambah Alsintan"
        keterangan="Alat baru tercatat pada inventaris kawasan."
        :aksi="route('alsintan.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.alsintan.form', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahAlsintanBaris" judul="Ubah Data Alsintan"
        keterangan="Satuan permukiman mengikuti kelompok tani yang dipilih."
        pola-aksi="/alsintan/:id" metode="PUT" ukuran="lg"
        label-simpan="Simpan Perubahan">
        @include('pages.alsintan.form', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusAlsintan" judul="Hapus data ini?"
        pesan="Data yang dihapus masih tercatat pada audit log dan dapat dipulihkan admin." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporAlsintan" judul="Impor Data Alsintan"
        entitas="alsintan"
        :kolom-wajib="['jenis_alsintan', 'nama_alat', 'jumlah_total', 'tahun_pengadaan']" />
@endsection
