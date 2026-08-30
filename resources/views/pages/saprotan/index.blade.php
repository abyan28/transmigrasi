{{--
    Daftar pengadaan sarana produksi pertanian, SATU BARIS PER PENGADAAN
    (Putaran 7).

    Satu batch bantuan (mis. 250 kg benih jagung anggaran Dinas 2025) lazim
    dibagikan ke beberapa poktan. Model lama membawa satu poktan_id pada tiap
    baris, sehingga satu batch diketik ulang per poktan dan sisa benih tak
    terdefinisi bila jatah satu poktan tergerus penanaman poktan lain. Kini
    poktan penerima tampil sebagai lencana; rincian per poktan (termasuk sisa
    benih) ada di halaman detail (rules.md §7c).
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `saprotan.index`, termasuk
        penyaringan dan angka ringkasan. Lihat routes/web.php.
    --}}
    <x-sim.halaman-daftar judul="Saprotan"
        keterangan="Pengadaan benih, pupuk, pestisida, dan mulsa, beserta pembagiannya ke kelompok tani."
        :remah="\App\Helpers\RemahHelper::untuk('/saprotan')"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('saprotan.index')"
        placeholder-cari="Cari nama saprotan atau poktan" judul-kosong="Belum ada pengadaan saprotan"
        pesan-kosong="Pengadaan sarana produksi akan tampil di sini setelah dicatat.">

        <x-slot:aksi>
            <x-sim.aksi-daftar modal-impor="imporSaprotan"
                modal-tambah="formTambahSaprotan" label-tambah="Tambah Saprotan" />
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Pengadaan" :nilai="count($semua)" />
            <x-sim.stat-card label="Jenis Saprotan" :nilai="count($jenisUnik)" />
            <x-sim.stat-card label="Poktan Penerima" :nilai="$poktanPenerima"
                keterangan="Kelompok yang menerima bagian" />
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
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Saprotan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Poktan Penerima</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tahun Pengadaan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Sumber Dana</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $s)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3">
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $s['nama'] }}</p>
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $s['jenis'] }}@if (! empty($s['komoditas'])) &middot; {{ $s['komoditas'] }} @endif
                    </p>
                </td>
                <td class="px-5 py-3">
                    @forelse ($s['distribusi'] as $d)
                        <a href="{{ route('poktan.detail', $d['poktan_id']) }}"
                            class="mr-1 mb-1 inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-theme-xs text-gray-700 hover:bg-gray-200 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                            {{ $d['poktan'] }}
                            <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ rtrim(rtrim(number_format($d['jumlah'], 2, ',', '.'), '0'), ',') }}</span>
                        </a>
                    @empty
                        <span class="text-theme-xs text-gray-400 dark:text-white/30">Belum tersalurkan</span>
                    @endforelse
                </td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ number_format($s['jumlah_total'], 0, ',', '.') }} {{ $s['satuan'] }}
                    @if ($belumTersalur[$s['id_saprotan']] > 0)
                        <span class="block text-theme-xs text-yellow-700 dark:text-yellow-400">
                            {{ rtrim(rtrim(number_format($belumTersalur[$s['id_saprotan']], 2, ',', '.'), '0'), ',') }} belum tersalur
                        </span>
                    @endif
                </td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $s['tahun_pengadaan'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $s['sumber_dana'] ?? '-' }}</td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris :rincian-url="route('saprotan.detail', $s['id_saprotan'])"
                        modal-ubah="formUbahSaprotanBaris"
                        :data-baris="['id' => $s['id_saprotan'], 'id_saprotan' => $s['id_saprotan'], 'jenis' => $s['jenis'], 'nama' => $s['nama'], 'komoditas_id' => $s['komoditas_id'], 'varietas' => $s['varietas'], 'jadwal_tanam' => $s['jadwal_tanam'], 'jumlah_total' => $s['jumlah_total'], 'satuan_id' => $s['satuan_id'], 'tahun_pengadaan' => $s['tahun_pengadaan'], 'sumber_dana' => $s['sumber_dana'], 'keterangan' => $s['keterangan']]"
                        :hapus-url="'/saprotan/' . $s['id_saprotan']"
                        konfirmasi-hapus="hapusSaprotan" :label="$s['nama']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $s)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $s['nama'] }}</p>
                        <span class="text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                            {{ number_format($s['jumlah_total'], 0, ',', '.') }} {{ $s['satuan'] }}
                        </span>
                    </div>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $s['jenis'] }} &middot; {{ $s['tahun_pengadaan'] }} &middot;
                        {{ count($s['distribusi']) > 0 ? implode(', ', $s['poktan_penerima']) : 'Belum tersalurkan' }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahSaprotan" judul="Tambah Saprotan"
        keterangan="Satu pengadaan beserta pembagiannya ke satu atau beberapa kelompok tani."
        :aksi="route('saprotan.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.saprotan.form', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahSaprotanBaris" judul="Ubah Data Saprotan"
        keterangan="Satuan permukiman mengikuti kelompok tani penerima."
        pola-aksi="/saprotan/:id" metode="PUT" ukuran="lg"
        label-simpan="Simpan Perubahan">
        @include('pages.saprotan.form', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusSaprotan" judul="Hapus data ini?"
        pesan="Data yang dihapus masih tercatat pada audit log dan dapat dipulihkan admin." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporSaprotan" judul="Impor Data Saprotan"
        entitas="saprotan"
        :kolom-wajib="['jenis_saprotan', 'nama', 'jumlah_total', 'satuan', 'tahun_pengadaan']" />
@endsection
