{{--
    Audit log perubahan data.

    Setiap perubahan data penting wajib tercatat (agents/rules.md bagian 14
    poin 5), termasuk penyetelan ulang kata sandi dan penonaktifan akun
    (bagian 14b poin 15).

    Catatan audit tidak pernah dapat disunting maupun dihapus dari antarmuka;
    itulah yang membuatnya bernilai sebagai jejak.
--}}
@extends('layouts.app')

@section('content')
    @php
        // Data, penyaringan, dan daftar opsi datang dari rute `audit-log`.
        // Lihat routes/web.php.

        // Warna badge per jenis aksi, agar aksi berisiko langsung terlihat
        $warnaAksi = [
            'Tambah' => 'teal',
            'Ubah' => 'warning',
            'Hapus' => 'error',
            'Pulihkan' => 'success',
            'Reset Kata Sandi' => 'warning',
            'Nonaktifkan Akun' => 'error',
            'Aktifkan Akun' => 'success',
        ];
    @endphp

    <x-sim.halaman-daftar judul="Audit Log"
        keterangan="Jejak perubahan data penting beserta pelaku dan waktunya."
        :remah="\App\Helpers\RemahHelper::untuk('/audit-log')"
        :jumlah="$baris->total()" :paginator="$baris" :kata-kunci="$cari" :aksi-url="route('audit-log')"
        placeholder-cari="Cari keterangan atau nama tabel" judul-kosong="Belum ada catatan audit"
        pesan-kosong="Perubahan data akan tercatat di sini secara otomatis.">

        <x-slot:filter>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="filter_aksi"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Jenis Aksi</label>
                    <select id="filter_aksi" name="aksi"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua aksi</option>
                        @foreach ($daftarAksi as $a)
                            <option value="{{ $a }}" @selected($filterAksi === $a)>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_pengguna"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Pelaku</label>
                    <select id="filter_pengguna" name="pengguna"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua pelaku</option>
                        @foreach ($daftarPengguna as $p)
                            <option value="{{ $p }}" @selected($filterPengguna === $p)>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <x-sim.filter-rentang-tahun :daftar-tahun="$daftarTahun"
                    :dari="$filterTahunDari" :sampai="$filterTahunSampai" label="Tahun" />
                <x-sim.tombol-filter :ada-filter="$adaFilter" :url-bersih="route('audit-log')" />
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Waktu</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Pelaku</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Data</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Keterangan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Alamat IP</th>
        </x-slot:kepala>

        @foreach ($baris as $a)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ \Illuminate\Support\Carbon::parse($a['waktu'])->translatedFormat('d M Y') }}
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ \Illuminate\Support\Carbon::parse($a['waktu'])->format('H:i') }} WITA
                    </p>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $a['pengguna'] }}</td>
                <td class="px-5 py-3">
                    <x-sim.status-badge :teks="$a['aksi']" :warna="$warnaAksi[$a['aksi']] ?? 'gray'" ukuran="sm" />
                </td>
                <td class="px-5 py-3 text-theme-xs text-gray-600 dark:text-gray-400">
                    {{ $a['nama_tabel'] }}
                    <span class="tabular-nums">#{{ $a['record_id'] }}</span>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    {{ $a['ringkasan'] }}
                    @if ($a['perubahan'])
                        <dl class="mt-1.5 space-y-1 text-theme-xs">
                            @foreach ($a['perubahan'] as $p)
                                <div class="flex flex-wrap items-baseline gap-x-1.5">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ $p['kolom'] }}:</dt>
                                    <dd class="text-gray-400 line-through dark:text-gray-500">{{ $p['lama'] }}</dd>
                                    <span aria-hidden="true" class="text-gray-400">&rarr;</span>
                                    <dd class="text-gray-700 dark:text-gray-300">{{ $p['baru'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </td>
                <td class="px-5 py-3 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                    {{ $a['ip_address'] }}</td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $a)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-theme-sm text-gray-800 dark:text-white/90">{{ $a['ringkasan'] }}</p>
                        <x-sim.status-badge :teks="$a['aksi']" :warna="$warnaAksi[$a['aksi']] ?? 'gray'" ukuran="sm" />
                    </div>
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $a['nama_tabel'] }} <span class="tabular-nums">#{{ $a['record_id'] }}</span>
                    </p>
                    <p class="mt-1.5 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                        {{ $a['pengguna'] }} &middot;
                        {{ \Illuminate\Support\Carbon::parse($a['waktu'])->translatedFormat('d M Y, H:i') }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>

    </x-sim.halaman-daftar>

    <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
        Catatan audit tidak dapat disunting maupun dihapus lewat antarmuka. Justru sifat itulah
        yang membuatnya bernilai sebagai jejak pertanggungjawaban.
    </p>
@endsection
