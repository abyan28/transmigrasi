{{--
    Role dan hak akses.

    Role bersifat dinamis: disimpan sebagai data, dibuat dan diatur Admin lewat
    antarmuka tanpa mengubah struktur database (agents/rules.md bagian 5.0).

    Hak akses ditentukan DUA hal yang terpisah:
    - izin menjawab boleh melakukan apa,
    - cakupan data menjawab boleh melihat data siapa.

    Pemisahan ini penting: dua orang dengan izin sama persis dapat melihat
    kumpulan data yang berbeda, bergantung cakupannya.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $role = DummyData::role();
        $pengguna = DummyData::pengguna();
    @endphp

    <x-sim.page-header judul="Role dan Hak Akses"
        keterangan="Susunan kewenangan yang dapat diberikan kepada akun petugas."
        :remah="[['label' => 'Pengaturan'], ['label' => 'Role dan Hak Akses']]">
        <x-slot:aksi>
            <a href="{{ route('pengguna.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Kembali ke Manajemen Pengguna
            </a>
        </x-slot:aksi>
    </x-sim.page-header>

    {{-- Penjelasan dua dimensi hak akses --}}
    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Izin</h2>
            <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
                Menjawab <span class="font-medium">boleh melakukan apa</span>, contohnya melihat,
                menambah, mengubah, menghapus, memverifikasi, atau mengekspor data pada satu modul.
            </p>
            <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                Daftar izin ditanam sistem dan tidak dapat ditambah admin, karena setiap izin harus
                punya pasangan pemeriksa di dalam kode.
            </p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Cakupan Data</h2>
            <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
                Menjawab <span class="font-medium">boleh melihat data siapa</span>, bernilai Semua,
                Per SP, atau Milik Sendiri.
            </p>
            <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                Diterapkan sebagai penyaring query, bukan sekadar menyembunyikan menu, sehingga tidak
                dapat ditembus dengan mengetik alamat langsung.
            </p>
        </div>
    </div>

    <div class="space-y-4">
        @foreach ($role as $r)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $r['nama'] }}</h3>
                            @if ($r['is_terkunci'])
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-theme-xs font-medium text-gray-700 dark:bg-white/5 dark:text-gray-300">
                                    Terkunci
                                </span>
                            @endif
                            @if ($r['is_bawaan'])
                                <span class="rounded-full bg-teal-50 px-2.5 py-1 text-theme-xs font-medium text-teal-700 dark:bg-teal-500/15 dark:text-teal-300">
                                    Bawaan sistem
                                </span>
                            @endif
                        </div>
                        <p class="mt-1.5 text-theme-sm text-gray-600 dark:text-gray-400">{{ $r['deskripsi'] }}</p>
                    </div>

                    <div class="text-right">
                        <p class="text-theme-xs text-gray-500 dark:text-gray-400">Cakupan data</p>
                        <p class="mt-0.5 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                            {{ $r['cakupan_data'] }}</p>
                    </div>
                </div>

                <dl class="mt-5 grid gap-4 border-t border-gray-200 pt-4 sm:grid-cols-3 dark:border-gray-800">
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Jumlah izin</dt>
                        <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            {{ $r['jumlah_izin'] }} izin</dd>
                    </div>
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Dipakai akun</dt>
                        <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            {{ $r['jumlah_pengguna'] }} akun</dd>
                    </div>
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Dapat dihapus</dt>
                        <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                            {{ $r['is_bawaan'] || $r['jumlah_pengguna'] > 0 ? 'Tidak' : 'Ya' }}
                        </dd>
                    </div>
                </dl>

                @if ($r['is_terkunci'])
                    <p class="mt-4 rounded-lg bg-gray-50 p-3 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                        Role Admin tidak dapat dihapus maupun dikurangi izinnya, agar sistem tidak pernah
                        kehilangan jalur administrasinya.
                    </p>
                @endif
            </div>
        @endforeach
    </div>

    <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
        Penyuntingan susunan izin per modul dikerjakan pada tahap autentikasi, bersama pembuatan
        tabel role dan permission. Halaman ini memperlihatkan susunan awal yang ditanam seeder.
    </p>
@endsection
