{{--
    Daftar transmigran.

    Halaman daftar memakai komposisi lebar penuh yang didominasi tabel, dengan
    filter di dalam laci yang dapat dilipat (agents/ui-spec.md bagian 2.2).
    Ini sengaja berbeda dari dashboard yang memakai grid grafik, dan dari
    halaman detail yang memakai dua kolom asimetris.

    Halaman ini menjadi acuan pola CRUD untuk Task 2.8 sampai 2.11.

    Penyaringan masih dikerjakan di sisi PHP atas data contoh. Ketika backend
    siap, penyaringan berpindah ke query tanpa mengubah tampilan.
--}}
@extends('layouts.app')

@section('content')
    @php
        // Data, penyaringan, dan pencarian datang dari rute
        // `transmigran.index`. Lihat routes/web.php.

        // Sementara seluruh tombol dirender. Penyaringan menurut izin
        // dipasang pada Tahap 3 lewat MenuHelper::bolehLihat().
        $bolehTambah = true;
        $bolehUbah = true;
        $bolehHapus = true;
    @endphp

    <x-sim.page-header judul="Data Transmigran"
        keterangan="Daftar kepala keluarga di seluruh satuan permukiman Kawasan Kobalima Timur."
        :remah="\App\Helpers\RemahHelper::untuk('/transmigran')">
        <x-slot:aksi>
            <x-sim.aksi-daftar modal-impor="imporTransmigran"
                :modal-tambah="$bolehTambah ? 'formTambahTransmigran' : null" label-tambah="Tambah Data Transmigran" />
        </x-slot:aksi>
    </x-sim.page-header>

    @php
        $persenAktif = $totalKk > 0 ? round(($totalAktif / $totalKk) * 100, 1) : 0;
        $rasioJiwa = $totalKk > 0 ? number_format($totalJiwa / $totalKk, 1, ',', '.') : '0';
    @endphp

    {{-- Wadah Ringkasan dengan Pilihan Tampilan (Bilah Ramping vs Kartu Lama) --}}
    <div x-data="{
            modeRingkas: localStorage.getItem('pref_ringkasan_transmigran') !== 'kartu',
            toggleMode() {
                this.modeRingkas = !this.modeRingkas;
                localStorage.setItem('pref_ringkasan_transmigran', this.modeRingkas ? 'strip' : 'kartu');
            }
        }"
        class="mb-6">

        {{-- Baris kontrol alih tampilan kecil --}}
        <div class="mb-2 flex items-center justify-between gap-2 px-1">
            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                Ringkasan Kependudukan
            </p>
            <button type="button" @click="toggleMode()"
                :title="modeRingkas ? 'Beralih ke tampilan kartu' : 'Beralih ke tampilan bilah ramping'"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-theme-xs font-medium text-gray-600 shadow-2xs hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/5">
                <template x-if="modeRingkas">
                    <span class="inline-flex items-center gap-1">
                        <svg class="h-3.5 w-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                        Tampilan Kartu
                    </span>
                </template>
                <template x-if="!modeRingkas">
                    <span class="inline-flex items-center gap-1">
                        <svg class="h-3.5 w-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                        </svg>
                        Tampilan Ramping
                    </span>
                </template>
            </button>
        </div>

        {{-- Desain 1: Compact Metric Strip (Default Ramping & Visual) --}}
        <div x-show="modeRingkas" x-transition.opacity>
            <x-sim.metric-strip>
                <x-sim.metric-item label="Kepala Keluarga" :nilai="number_format($totalKk, 0, ',', '.')"
                    satuan="KK" ikon="keluarga" warna="teal" keterangan="Kawasan Kobalima Timur" />
                <x-sim.metric-item label="Masih Tinggal" :nilai="number_format($totalAktif, 0, ',', '.')"
                    satuan="KK" ikon="hunian" warna="emerald" :prosentase="$persenAktif" />
                <x-sim.metric-item label="Total Penduduk" :nilai="number_format($totalJiwa, 0, ',', '.')"
                    satuan="Jiwa" ikon="penduduk" warna="navy" :keterangan="'~' . $rasioJiwa . ' jiwa / KK'" />
                <x-sim.metric-item label="Satuan Permukiman" :nilai="number_format($totalSp, 0, ',', '.')"
                    satuan="SP" ikon="lokasi" warna="gold" keterangan="SP 1 sampai SP 4" />
            </x-sim.metric-strip>
        </div>

        {{-- Desain 2: 4 Kartu Kotak Lama (Opsi Revert / Fallback Penuh) --}}
        <div x-show="!modeRingkas" x-cloak x-transition.opacity class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-sim.stat-card label="Total Kepala Keluarga" :nilai="number_format($totalKk, 0, ',', '.')"
                satuan="KK" />
            <x-sim.stat-card label="Masih Tinggal di Kawasan"
                :nilai="number_format($totalAktif, 0, ',', '.')"
                satuan="KK" />
            <x-sim.stat-card label="Total Jiwa"
                :nilai="number_format($totalJiwa, 0, ',', '.')"
                keterangan="Seluruh anggota keluarga terdata" />
            <x-sim.stat-card label="Satuan Permukiman"
                :nilai="number_format($totalSp, 0, ',', '.')"
                keterangan="Tempat data tersebar" />
        </div>
    </div>

    {{-- Pencarian dan filter dibungkus satu form agar keduanya terkirim bersama --}}
    <form method="GET" action="{{ route('transmigran.index') }}">
        <x-sim.data-table :jumlah="$baris->total()" :paginator="$baris" :kata-kunci="$cari"
            placeholder-cari="Cari nama, NIK, atau nomor KK"
            judul-kosong="Belum ada data transmigran"
            pesan-kosong="Data kepala keluarga akan tampil di sini setelah ditambahkan.">

            <x-slot:filter>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="filter_sp"
                            class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                            Satuan Permukiman
                        </label>
                        <select id="filter_sp" name="sp"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                            <option value="">Semua SP</option>
                            @foreach ($daftarSp as $sp)
                                <option value="{{ $sp['id_satuan_permukiman'] }}"
                                    @selected($filterSp == $sp['id_satuan_permukiman'])>
                                    {{ $sp['nama'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter_tinggal"
                            class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                            Status Tinggal
                        </label>
                        <select id="filter_tinggal" name="status_tinggal"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                            <option value="">Semua status</option>
                            @foreach (\App\Enums\StatusTinggal::opsi() as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($filterTinggal === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-sim.tombol-filter :ada-filter="$adaFilter" :url-bersih="route('transmigran.index')" />
                </div>
            </x-slot:filter>

            <x-slot:aksiKanan>
                <button type="submit"
                    class="h-10 shrink-0 rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    Cari
                </button>
            </x-slot:aksiKanan>

            <x-slot:aksiKosong>
                @if ($adaFilter)
                    <a href="{{ route('transmigran.index') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Bersihkan Filter
                    </a>
                @elseif ($bolehTambah)
                    <button type="button" @click="$dispatch('buka-modal', 'formTambahTransmigran')"
                        class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Tambah Data Transmigran
                    </button>
                @endif
            </x-slot:aksiKosong>

            <x-slot:kepala>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Nama Kepala Keluarga
                </th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">NIK</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Satuan Permukiman
                </th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Anggota
                </th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Status Tinggal
                </th>
                <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Aksi
                </th>
            </x-slot:kepala>

            @foreach ($baris as $t)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                    <td class="px-5 py-3">
                        <a href="{{ route('transmigran.detail', $t['id_transmigran']) }}"
                            class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                            {{ $t['nama_kepala_keluarga'] }}
                        </a>
                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $t['pekerjaan_kepala_keluarga'] }}
                        </p>
                    </td>
                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                        {{ $t['nik'] }}
                    </td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                        {{ $t['satuan_permukiman'] }}
                    </td>
                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                        {{ $t['jumlah_anggota_keluarga'] }} orang
                    </td>
                    <td class="px-5 py-3">
                        <x-sim.status-badge :status="\App\Enums\StatusTinggal::from($t['status_tinggal'])" />
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('transmigran.detail', $t['id_transmigran']) }}"
                                aria-label="Lihat rincian {{ $t['nama_kepala_keluarga'] }}"
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
                                {{--
                                    Ubah tersedia langsung di baris, sejajar dengan Hapus.
                                    Sebelumnya hanya Hapus yang ada di sini sementara Ubah harus
                                    lewat halaman rincian, padahal menghapus lebih berisiko
                                    daripada menyunting.
                                --}}
                                <button type="button"
                                    @click.prevent="$dispatch('buka-modal-baris', {
                                        nama: 'formUbahTransmigranBaris',
                                        data: @js($t + ['id' => $t['id_transmigran']])
                                    })"
                                    aria-label="Ubah data {{ $t['nama_kepala_keluarga'] }}"
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
                                        nama: 'hapusTransmigran',
                                        aksi: '{{ route('transmigran.hapus', $t['id_transmigran']) }}'
                                    })"
                                    aria-label="Hapus data {{ $t['nama_kepala_keluarga'] }}"
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

            {{-- Tata letak kartu untuk layar sempit, mencegah gulir mendatar --}}
            <x-slot:kartu>
                @foreach ($baris as $t)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <a href="{{ route('transmigran.detail', $t['id_transmigran']) }}"
                                class="min-w-0 rounded focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                    {{ $t['nama_kepala_keluarga'] }}
                                </p>
                                <p class="mt-0.5 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                                    {{ $t['nik'] }}
                                </p>
                            </a>
                            <x-sim.status-badge :status="\App\Enums\StatusTinggal::from($t['status_tinggal'])" ukuran="sm" />
                        </div>
                        <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $t['satuan_permukiman'] }} &middot; {{ $t['jumlah_anggota_keluarga'] }} anggota
                        </p>
                    </div>
                @endforeach
            </x-slot:kartu>
        </x-sim.data-table>
    </form>

    {{-- Modal tambah data --}}
    @if ($bolehTambah)
        <x-sim.modal-form nama="formTambahTransmigran" judul="Tambah Data Transmigran"
            keterangan="Isian bertanda bintang wajib diisi."
            :aksi="route('transmigran.simpan')" ukuran="xl"
            :langkah="['Identitas', 'Penempatan', 'Anggota Keluarga', 'Berkas']"
            label-simpan="Simpan Data Transmigran">
            @include('pages.transmigran.form', ['awalan' => 'tambah'])
        </x-sim.modal-form>
    @endif

    {{-- Dialog konfirmasi hapus, satu untuk seluruh baris --}}
    @if ($bolehHapus)
        <x-sim.confirm-dialog nama="hapusTransmigran" judul="Hapus data transmigran ini?"
            pesan="Data yang dihapus masih tercatat pada audit log dan dapat dipulihkan admin."
            label-setuju="Hapus Data Transmigran" />
    @endif

    {{--
        Modal ubah berbaris. Satu modal melayani seluruh baris: data baris yang
        diklik dikirim lewat peristiwa, lalu isian diisi Alpine. Merender satu
        modal per baris akan menggandakan form sebanyak baris pada satu halaman.
    --}}
    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahTransmigranBaris" judul="Ubah Data Transmigran"
            keterangan="Perubahan tercatat pada audit log."
            pola-aksi="/transmigran/:id" metode="PUT" ukuran="xl"
            :langkah="['Identitas', 'Penempatan', 'Anggota Keluarga', 'Berkas']"
            label-simpan="Simpan Perubahan">
            @include('pages.transmigran.form', ['awalan' => 'ubahBaris'])
        </x-sim.modal-form>
    @endif

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporTransmigran" judul="Impor Data Transmigran"
        entitas="transmigran" />
@endsection
