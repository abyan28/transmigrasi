{{--
    Penyaluran sarana produksi pertanian.

    Penerima dapat berupa kelompok tani maupun individu transmigran
    (agents/rules.md bagian 7c poin 3). Penyaluran kepada anggota poktan hanya
    untuk anggota berstatus aktif (poin 4), aturan yang dijaga saat pemilihan
    penerima pada Tahap 6.
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `saprotan.index`, termasuk
        penyaringan, angka ringkasan, dan peta `$sisaBenih` yang dahulu
        dihitung ulang di dalam perulangan baris. Lihat routes/web.php.
    --}}
    <x-sim.halaman-daftar judul="Saprotan"
        keterangan="Penyaluran benih, pupuk, pestisida, dan mulsa kepada petani."
        :remah="\App\Helpers\RemahHelper::untuk('/saprotan')"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('saprotan.index')"
        placeholder-cari="Cari nama saprotan atau penerima" judul-kosong="Belum ada penyaluran saprotan"
        pesan-kosong="Penyaluran sarana produksi akan tampil di sini setelah dicatat.">

        <x-slot:aksi>
            <x-sim.aksi-daftar modal-impor="imporSaprotan"
                modal-tambah="formTambahSaprotan" label-tambah="Tambah Saprotan" />
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Catatan Penyaluran" :nilai="count($semua)" />
            <x-sim.stat-card label="Jenis Saprotan" :nilai="count($jenisUnik)" />
            <x-sim.stat-card label="Poktan Penerima" :nilai="$poktanPenerima" />
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
                    <label for="filter_jenis"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Jenis Saprotan</label>
                    <select id="filter_jenis" name="jenis"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua jenis</option>
                        @foreach (\App\Enums\JenisSaprotan::opsi() as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($filterJenis === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <x-sim.tombol-filter :ada-filter="$adaFilter" :url-bersih="route('saprotan.index')" />
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jenis</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama Saprotan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Penerima</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tahun Pengadaan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Sumber Dana</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $s)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $s['jenis'] }}</td>
                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                    {{ $s['nama'] }}
                    {{-- Komoditas hanya ada pada benih, sehingga barisnya
                         tidak selalu tampil. --}}
                    @if (! empty($s['komoditas']))
                        <p class="mt-0.5 text-theme-xs font-normal text-gray-500 dark:text-gray-400">
                            {{ $s['komoditas'] }}
                        </p>
                    @endif
                </td>
                {{--
                    Sisa stok ditampilkan HANYA untuk benih, sebab hanya benih
                    yang dikurangi pemakaiannya oleh penanaman. Menampilkannya
                    pada pupuk berarti menjanjikan penghitungan yang tidak
                    pernah dilakukan.
                --}}
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ number_format($s['jumlah'], 0, ',', '.') }} {{ $s['satuan'] }}
                    @if ($s['jenis'] === \App\Enums\JenisSaprotan::Benih->value)
                        @php($sisa = $sisaBenih[$s['id_saprotan']])
                        <p class="mt-0.5 text-theme-xs {{ $sisa > 0 ? 'text-gray-500 dark:text-gray-400' : 'text-error-500' }}">
                            {{ $sisa > 0 ? 'sisa ' . rtrim(rtrim(number_format($sisa, 2, ',', '.'), '0'), ',') . ' ' . $s['satuan'] : 'habis' }}
                        </p>
                    @endif
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    @if ($s['poktan_id'])
                        <a href="{{ route('poktan.detail', $s['poktan_id']) }}"
                            class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                            {{ $s['penerima'] }}
                        </a>
                    @else
                        {{ $s['penerima'] }}
                    @endif
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $s['satuan_permukiman'] }}</p>
                </td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $s['tahun_pengadaan'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $s['sumber_dana'] ?? '-' }}</td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris :rincian-url="route('saprotan.detail', $s['id_saprotan'])"
                        modal-ubah="formUbahSaprotanBaris"
                        :data-baris="$s + ['id' => $s['id_saprotan']]"
                        :hapus-url="'/saprotan/' . $s['id_saprotan']"
                        konfirmasi-hapus="hapusSaprotan" :label="$s['nama']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $s)
                <div class="p-4">
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $s['nama'] }}</p>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ number_format($s['jumlah'], 0, ',', '.') }} {{ $s['satuan'] }} &middot; {{ $s['penerima'] }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahSaprotan" judul="Tambah Saprotan"
        keterangan="Penyaluran hanya dapat ditujukan kepada anggota berstatus aktif."
        :aksi="route('saprotan.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.saprotan.form', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahSaprotanBaris" judul="Ubah Data Saprotan"
        keterangan="Penerima individu hanya dapat dipilih dari anggota aktif."
        pola-aksi="/saprotan/:id" metode="PUT" ukuran="lg"
        label-simpan="Simpan Perubahan">
        @include('pages.saprotan.form', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusSaprotan" judul="Hapus data ini?"
        pesan="Data yang dihapus masih tercatat pada audit log dan dapat dipulihkan admin." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporSaprotan" judul="Impor Data Saprotan"
        entitas="saprotan"
        :kolom-wajib="['satuan_permukiman', 'jenis_saprotan', 'jumlah', 'satuan']" />
@endsection
