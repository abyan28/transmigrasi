{{--
    Role dan hak akses.

    Role bersifat dinamis: disimpan sebagai data, dibuat dan diatur Admin lewat
    antarmuka tanpa mengubah struktur database (agents/rules.md bagian 5.0).

    Hak akses ditentukan DUA hal yang terpisah:
    - kewenangan menjawab boleh melakukan apa,
    - cakupan data menjawab boleh melihat data siapa.

    Pemisahan ini penting: dua orang dengan kewenangan sama persis dapat melihat
    kumpulan data yang berbeda, bergantung cakupannya.
--}}
@extends('layouts.app')

@section('content')
    {{-- `$role` dan `$pengguna` datang dari rute `pengaturan.role`. --}}

    <x-sim.page-header judul="Role dan Hak Akses"
        keterangan="Susunan kewenangan yang dapat diberikan kepada akun petugas."
        :remah="\App\Helpers\RemahHelper::untuk('/pengaturan/role')">
        <x-slot:aksi>
            <a href="{{ route('pengguna.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Kembali ke Manajemen Pengguna
            </a>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahRole')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Role
            </button>
        </x-slot:aksi>
    </x-sim.page-header>

    {{-- Penjelasan dua dimensi hak akses --}}
    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Kewenangan</h2>
            <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
                {{-- Kata "mengekspor" dicabut 2026-08-20. Kewenangan `export`
                     sudah dihapus 2026-08-17 sebab ekspor mengikuti `lihat`,
                     tetapi kalimat ini masih menjanjikannya. --}}
                Menentukan <span class="font-medium">tindakan yang dapat dilakukan pengguna</span>,
                yaitu melihat, menambah, mengubah, dan menghapus data dalam suatu fitur.
            </p>
            <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                Daftar kewenangan ditetapkan oleh sistem dan tidak dapat ditambahkan oleh Admin. 
                Setiap kewenangan memiliki pemeriksaan akses yang sesuai di dalam sistem.
            </p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Cakupan Data</h2>
            <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
                        Menentukan <span class="font-medium">data yang dapat diakses oleh pengguna</span>, 
                        baik seluruh data maupun data pada SP tertentu.
            </p>
            <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                Diterapkan sebagai penyaring pada data yang ditampilkan, bukan sekadar menyembunyikan menu. 
                Pembatasan tetap berlaku meskipun pengguna mengakses halaman melalui alamat secara langsung.
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
                    {{--
                        Ketiga nilai diberi warna agar terbaca sekilas tanpa
                        membaca labelnya lebih dulu. Warnanya menyatakan makna,
                        bukan sekadar hiasan: hijau berarti dapat dihapus,
                        abu-abu berarti tidak.
                    --}}
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Jumlah Kewenangan</dt>
                        <dd class="mt-0.5 text-theme-sm font-semibold tabular-nums text-brand-600 dark:text-brand-400">
                            {{ $r['jumlah_izin'] }} kewenangan</dd>
                    </div>
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Jumlah Akun</dt>
                        <dd class="mt-0.5 text-theme-sm font-semibold tabular-nums text-teal-700 dark:text-teal-300">
                            {{ $r['jumlah_pengguna'] }} akun</dd>
                    </div>
                    <div>
                        @php $dapatDihapus = ! $r['is_bawaan'] && $r['jumlah_pengguna'] === 0; @endphp
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Dapat Dihapus</dt>
                        <dd class="mt-0.5 text-theme-sm font-semibold {{ $dapatDihapus ? 'text-success-600 dark:text-success-400' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $dapatDihapus ? 'Ya' : 'Tidak' }}
                        </dd>
                    </div>
                </dl>

                @if ($r['is_terkunci'])
                    <p class="mt-4 rounded-lg bg-gray-50 p-3 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                        Role Admin tidak dapat dihapus maupun dikurangi kewenangannya, agar sistem tidak pernah
                        kehilangan jalur administrasinya.
                    </p>
                @endif

                <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <button type="button" @click="$dispatch('buka-modal', 'formRole{{ $r['id_role'] }}')"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        {{ $r['is_terkunci'] ? 'Lihat Susunan Kewenangan' : 'Ubah Role dan Kewenangan' }}
                    </button>

                    {{--
                        Tombol hapus dirender HANYA bila kedua syarat terpenuhi:
                        bukan role bawaan, dan tidak dipakai akun mana pun
                        (rules.md 5.0c poin 8 dan 9). Merender tombol lalu
                        menolaknya di server berarti memasang kontrol mati.
                    --}}
                    @if (! $r['is_bawaan'] && $r['jumlah_pengguna'] === 0)
                        <button type="button"
                            @click="$dispatch('buka-konfirmasi', { nama: 'hapusRole', aksi: '{{ url('/pengaturan/role/' . $r['id_role']) }}' })"
                            class="rounded-lg border border-red-300 px-3 py-2 text-theme-xs font-medium text-red-600 transition hover:bg-red-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-red-500/40 dark:text-red-400 dark:hover:bg-red-500/10">
                            Hapus Role
                        </button>
                    @endif
                </div>
            </div>

            {{--
                Modal per role. Role terkunci tetap dapat dibuka, tetapi
                matriksnya dirender hanya baca beserta alasannya.
            --}}
            <x-sim.modal-form :nama="'formRole' . $r['id_role']"
                :judul="$r['is_terkunci'] ? 'Susunan Kewenangan ' . $r['nama'] : 'Ubah Role ' . $r['nama']"
                :keterangan="$r['is_terkunci'] ? 'Hanya dapat dilihat, tidak dapat disunting.' : 'Perubahan berlaku bagi seluruh akun yang memakai role ini.'"
                :aksi="route('role.perbarui', $r['id_role'])" metode="PUT" ukuran="xl"
                label-simpan="Simpan Perubahan">
                @include('pages.pengguna.form-role', [
                    'awalan' => 'role' . $r['id_role'],
                    'data' => $r,
                ])
            </x-sim.modal-form>
        @endforeach
    </div>

    {{-- Modal tambah role --}}
    <x-sim.modal-form nama="formTambahRole" judul="Tambah Role"
        keterangan="Role baru dimulai tanpa kewenangan apa pun. Centang hanya yang benar-benar diperlukan."
        :aksi="route('role.simpan')" ukuran="xl" label-simpan="Simpan Role">
        @include('pages.pengguna.form-role', ['awalan' => 'roleBaru', 'data' => []])
    </x-sim.modal-form>

    {{--
        Konfirmasi penghapusan role. Alasan diminta agar tercatat pada audit
        log, sebab penghapusan role mengubah susunan kewenangan sistem.
    --}}
    <x-sim.confirm-dialog nama="hapusRole" judul="Hapus role ini?"
        pesan="Role yang dihapus tidak dapat dipulihkan. Hanya role tanpa pengguna yang dapat dihapus, sehingga tidak ada akun yang kehilangan kewenangannya."
        label-setuju="Hapus Role" metode="DELETE" ragam="bahaya" :perlu-alasan="true"
        label-alasan="Alasan penghapusan" />
@endsection
