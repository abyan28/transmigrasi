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
    {{--
        Seluruh isian halaman ini datang dari rute `penanaman`, termasuk peta
        $statusPanen dan $kekuatanPoktan yang keduanya DIHITUNG dan tidak
        pernah disimpan sebagai kolom (rules.md 7d poin 3 dan 11).
        Lihat routes/web.php.
    --}}

    <x-sim.halaman-daftar judul="Penanaman"
        keterangan="Catatan penanaman per kelompok tani, komoditas, dan waktu tanam."
        :remah="\App\Helpers\RemahHelper::untuk('/penanaman')"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('penanaman')"
        placeholder-cari="Cari kelompok tani atau komoditas" judul-kosong="Belum ada penanaman"
        pesan-kosong="Catatan penanaman akan tampil di sini setelah dicatat petugas.">

        <x-slot:aksi>
            <x-sim.aksi-daftar modal-impor="imporPenanaman"
                modal-tambah="formTambahPenanaman" label-tambah="Catat Penanaman" />
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Catatan Penanaman" :nilai="count($semua)" />
            <x-sim.stat-card label="Realisasi Tanam"
                :nilai="number_format(array_sum(array_column($semua, 'realisasi_tanam')), 2, ',', '.')" satuan="ha" />
            {{--
                Menggantikan kartu "Tahun Tercatat" 2026-08-24. Cacah tahun
                hanya menyatakan seberapa lama sistem dipakai, sedangkan sisa
                tanam menyatakan berapa hektare yang masih berdiri tanaman dan
                karena itu masih menunggu panen.
            --}}
            <x-sim.stat-card label="Belum Dipanen" :nilai="number_format($totalBelumDipanen, 2, ',', '.')" satuan="ha"
                keterangan="Luas yang masih berdiri tanaman" />
            <x-sim.stat-card label="Komoditas Ditanam" :nilai="count($daftarKomoditas)" />
        </x-slot:ringkasan>

        <x-slot:filter>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
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
                {{--
                    KETIGA status ditawarkan di sini, berbeda dari halaman
                    Hasil Panen yang hanya menawarkan dua. Menemukan penanaman
                    yang belum dipanen sama sekali adalah tugas halaman inilah,
                    sebab di sinilah barisnya ada.
                --}}
                <div>
                    <label for="filter_status"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Status Panen</label>
                    <select id="filter_status" name="status"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua status</option>
                        @foreach (\App\Enums\StatusPanen::cases() as $s)
                            <option value="{{ $s->value }}" @selected($filterStatus === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <x-sim.tombol-filter :ada-filter="$adaFilter" :url-bersih="route('penanaman')" />
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kelompok Tani</th>
            {{--
                Jumlah Anggota dan Luas Lahan DIHITUNG, tidak disimpan
                (rules.md 7d.3). Keduanya turunan dari keanggotaan aktif dan
                data lahan, sehingga angka yang disimpan akan basi begitu satu
                anggota keluar atau satu bidang dibetulkan - dan kebasian itu
                tidak pernah memerahkan apa pun.
            --}}
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah Anggota</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Komoditas</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Volume Benih</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Luas Lahan (ha)</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Realisasi Tanam (ha)</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Periode Tanam</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Status Panen</th>
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
                {{-- Kekuatan poktan saat halaman dibuka, bukan saat penanaman
                     dicatat: keduanya dihitung ulang setiap kali. --}}
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $kekuatanPoktan[$r['poktan_id']]['jumlah_anggota'] }}
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $r['komoditas'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ rtrim(rtrim(number_format($r['volume_benih'], 2, ',', '.'), '0'), ',') }} kg
                </td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ number_format($kekuatanPoktan[$r['poktan_id']]['luas_total'], 2, ',', '.') }}
                </td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ number_format($r['realisasi_tanam'], 2, ',', '.') }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    {{ \Illuminate\Support\Carbon::parse($r['periode_tanam'] . '-01')->translatedFormat('F Y') }}</td>
                {{--
                    Dua nilai saja sejak 2026-08-24. Keterangan "sisa sekian
                    hektare" ikut dicabut: penanaman yang belum dipanen
                    menyisakan SELURUH luasnya, dan angka itu sudah tertulis
                    pada kolom Realisasi Tanam di sebelahnya.
                --}}
                <td class="px-5 py-3">
                    <x-sim.status-badge :status="$statusPanen[$r['id_penanaman']]" />
                </td>
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
                {{--
                    LIMA kolom pertama: Kelompok Tani, Jumlah Anggota,
                    Komoditas, Volume Benih, dan Luas Lahan. Naik dari tiga
                    sejak Jumlah Anggota dan Luas Lahan ditambahkan 2026-08-25.

                    Luas Lahan sengaja TIDAK dijumlahkan pada baris total:
                    satu poktan muncul pada beberapa baris penanaman, sehingga
                    menjumlahkannya per baris menghitung lahan yang sama
                    berkali-kali. Alasan yang sama dengan rekap panen.
                --}}
                <td colspan="5" class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                    Total realisasi tanam</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                    {{ number_format($totalLuas, 2, ',', '.') }}</td>
                {{--
                    Tiga sel kosong: Periode Tanam, Status Panen, dan Aksi.
                    Luput menyesuaikannya membuat baris total bergeser satu
                    kolom tanpa memerahkan uji mana pun.
                --}}
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </x-slot:kaki>

        <x-slot:kartu>
            @foreach ($baris as $r)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="min-w-0 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                            {{ $r['komoditas'] }} oleh {{ $r['poktan'] }}
                        </p>
                        <x-sim.status-badge :status="$statusPanen[$r['id_penanaman']]" ukuran="sm" class="shrink-0" />
                    </div>
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
        @include('pages.penanaman.form', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahPenanamanBaris" judul="Ubah Penanaman"
        keterangan="Perubahan tercatat pada audit log."
        pola-aksi="/penanaman/:id" metode="PUT" ukuran="lg" label-simpan="Simpan Perubahan">
        @include('pages.penanaman.form', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusPenanaman" judul="Hapus catatan penanaman ini?"
        pesan="Hasil panen yang menaut catatan ini akan kehilangan lokasi produksinya." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporPenanaman" judul="Impor Penanaman"
        entitas="penanaman"
            :kolom-wajib="['kelompok_tani', 'komoditas', 'periode_tanam', 'realisasi_tanam_ha']" />
@endsection
