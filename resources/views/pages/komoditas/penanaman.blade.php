{{--
    Penanaman.

    Mencatat lahan mana ditanami komoditas apa dan kapan. Menjadi jembatan
    antara lahan dan hasil panen: hasil panen menaut ke penanaman, bukan
    langsung ke lahan, sehingga lokasi produksi terbaca lewat rantai
    penanaman, lahan, satuan permukiman
    (agents/data-dictionary.md bagian 9.2).

    DAHULU BERNAMA "RIWAYAT TANAM", diubah 2026-08-22. Kata "riwayat"
    menyiratkan catatan masa lalu, padahal barisnya justru dibuat ketika
    penanaman baru dimulai dan panennya belum ada.

    TANPA MUSIM TANAM sejak tanggal yang sama. Penyaringan periode kini
    memakai TAHUN TANAM yang dihitung dari `periode_tanam`, bukan label musim
    yang harus ditetapkan lebih dulu di tabel tersendiri.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::penanaman();

        $cari = trim((string) request('cari', ''));
        $filterSp = request('sp');
        $filterTahun = request('tahun');
        $filterKomoditas = request('komoditas');

        // Tahun tanam diturunkan dari tanggalnya, bukan disimpan terpisah.
        // Menyimpannya sebagai kolom sendiri membuat nilainya dapat berbeda
        // dari tanggal yang menjadi sumbernya.
        $tahunTanam = fn ($r) => $r['periode_tanam']
            ? \Illuminate\Support\Carbon::parse($r['periode_tanam'] . '-01')->year
            : null;

        $baris = array_values(array_filter($semua, function ($r) use ($cari, $filterSp, $filterTahun, $filterKomoditas, $tahunTanam) {
            if ($cari !== '' && ! str_contains(mb_strtolower($r['poktan']), mb_strtolower($cari))
                && ! str_contains(mb_strtolower($r['komoditas']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterSp && (string) $r['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }
            if ($filterTahun && (string) $tahunTanam($r) !== (string) $filterTahun) {
                return false;
            }
            if ($filterKomoditas && $r['komoditas'] !== $filterKomoditas) {
                return false;
            }

            return true;
        }));

        $adaFilter = $cari !== '' || $filterSp || $filterTahun || $filterKomoditas;
        $totalLuas = array_sum(array_column($baris, 'realisasi_tanam'));

        $daftarTahun = array_values(array_filter(array_unique(array_map($tahunTanam, $semua))));
        rsort($daftarTahun);

        $daftarKomoditas = array_values(array_unique(array_column($semua, 'komoditas')));
    @endphp

    <x-sim.halaman-daftar judul="Penanaman"
        keterangan="Catatan penanaman per kelompok tani, komoditas, dan waktu tanam."
        :remah="\App\Helpers\RemahHelper::untuk('/penanaman')"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('penanaman')"
        placeholder-cari="Cari kelompok tani atau komoditas" judul-kosong="Belum ada penanaman"
        pesan-kosong="Catatan penanaman akan tampil di sini setelah dicatat petugas.">

        <x-slot:aksi>
            {{--
                Impor massal diletakkan mendahului tombol tambah namun bergaya
                sekunder, sebab menambah satu data tetap tindakan yang paling
                sering dipakai (PRD 8.1).
            --}}
            <button type="button" @click="$dispatch('buka-modal', 'imporPenanaman')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Impor Data
            </button>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahPenanaman')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Catat Penanaman
            </button>
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Catatan Penanaman" :nilai="count($semua)" />
            <x-sim.stat-card label="Realisasi Tanam"
                :nilai="number_format(array_sum(array_column($semua, 'realisasi_tanam')), 2, ',', '.')" satuan="ha" />
            <x-sim.stat-card label="Tahun Tercatat" :nilai="count($daftarTahun)" />
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
                    <label for="filter_tahun"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Tahun Tanam</label>
                    <select id="filter_tahun" name="tahun"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua tahun</option>
                        @foreach ($daftarTahun as $t)
                            <option value="{{ $t }}" @selected((string) $filterTahun === (string) $t)>{{ $t }}</option>
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
                        <a href="{{ route('penanaman') }}"
                            class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Bersihkan
                        </a>
                    @endif
                </div>
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kelompok Tani</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Komoditas</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Volume Benih</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Realisasi Tanam (ha)</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Periode Tanam</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $r)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3">
                    <a href="{{ route('poktan.detail', $r['poktan_id']) }}"
                        class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                        {{ $r['poktan'] }}
                    </a>
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $r['satuan_permukiman'] }}</p>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $r['komoditas'] }}</td>
                {{-- Boleh kosong: bibit swadaya tidak melalui modul saprotan. --}}
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    @if ($r['volume_benih'])
                        {{ rtrim(rtrim(number_format($r['volume_benih'], 2, ',', '.'), '0'), ',') }} kg
                    @else
                        <span class="text-gray-400 dark:text-white/30">&mdash;</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ number_format($r['realisasi_tanam'], 2, ',', '.') }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    {{ \Illuminate\Support\Carbon::parse($r['periode_tanam'] . '-01')->translatedFormat('F Y') }}</td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris :rincian-url="route('penanaman.detail', $r['id_penanaman'])"
                        modal-ubah="formUbahPenanamanBaris"
                        :data-baris="$r + ['id' => $r['id_penanaman']]"
                        :hapus-url="'/penanaman/' . $r['id_penanaman']"
                        konfirmasi-hapus="hapusPenanaman"
                        :label="$r['komoditas'] . ' oleh ' . $r['poktan']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kaki>
            <tr class="motif-baris-total">
                {{-- Tiga kolom pertama: Kelompok Tani, Komoditas, Volume Benih. --}}
                <td colspan="3" class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                    Total realisasi tanam</td>
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
                        {{ $r['komoditas'] }} oleh {{ $r['poktan'] }}
                    </p>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ \Illuminate\Support\Carbon::parse($r['periode_tanam'] . '-01')->translatedFormat('F Y') }}
                        &middot; {{ number_format($r['realisasi_tanam'], 2, ',', '.') }} ha
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahPenanaman" judul="Catat Penanaman"
        keterangan="Kelompok tani menentukan lokasi, luas lahan, dan benih yang boleh dipakai."
        :aksi="route('penanaman.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.komoditas.form-penanaman', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahPenanamanBaris" judul="Ubah Penanaman"
        keterangan="Perubahan tercatat pada audit log."
        pola-aksi="/penanaman/:id" metode="PUT" ukuran="lg" label-simpan="Simpan Perubahan">
        @include('pages.komoditas.form-penanaman', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusPenanaman" judul="Hapus catatan penanaman ini?"
        pesan="Hasil panen yang menaut catatan ini akan kehilangan lokasi produksinya." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporPenanaman" judul="Impor Penanaman"
        entitas="penanaman"
            :kolom-wajib="['kelompok_tani', 'komoditas', 'periode_tanam', 'realisasi_tanam_ha']" />
@endsection
