{{--
    Halaman profil pengguna.

    Menampilkan identitas akun, role beserta cakupan datanya, dan penugasan SP.
    Pengguna hanya dapat mengubah data kontak dirinya sendiri. Nama, username,
    role, dan penugasan SP ditetapkan Admin lewat Manajemen Pengguna
    (agents/rules.md bagian 14b poin 1 dan 2), sehingga di sini ditampilkan
    sebagai teks baca-saja, bukan isian yang tampak dapat diubah.

    Komposisi dua kolom asimetris mengikuti pola halaman detail
    (agents/ui-spec.md bagian 2.2): ringkasan entitas menetap di kiri, konci
    bertab di kanan.

    Data masih berupa contoh. Penyambungan ke akun sungguhan dikerjakan pada
    Tahap 3 bersama autentikasi.
--}}
@extends('layouts.app')

@section('content')
    @php
        // `$pengguna` dan `$inisialPengguna` datang dari rute `profil`.
        $role = $pengguna['role'];
        $cakupan = \App\Enums\CakupanData::dari($role['cakupan_data']);
    @endphp

    <x-sim.page-header judul="Profil Saya"
        keterangan="Data akun Anda beserta kewenangan yang diberikan admin."
        {{-- Profil tidak ada di sidebar, sehingga labelnya disusun dari
             alamatnya sendiri oleh RemahHelper. --}}
        :remah="\App\Helpers\RemahHelper::untuk('/profil')">
        <x-slot:aksi>
            <a href="{{ route('profil.kata-sandi') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                Ubah Kata Sandi
            </a>
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
        {{-- Kolom kiri: kartu identitas yang menetap --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center dark:border-gray-800 dark:bg-white/[0.03]">
                {{--
                    Avatar berbasis inisial, bukan foto orang. Sistem tidak
                    membuat gambar wajah karangan (ANTISLOP-ID R-18 dan R-23).
                --}}
                <span
                    class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-brand-500 text-title-sm font-bold text-white"
                    aria-hidden="true">
                    {{ $inisialPengguna }}
                </span>

                <h2 class="mt-4 text-lg font-semibold text-gray-800 dark:text-white/90">
                    {{ $pengguna['nama'] }}
                </h2>
                <p class="mt-0.5 text-theme-sm text-gray-500 dark:text-gray-400">
                    {{ $pengguna['jabatan'] ?? '-' }}
                </p>

                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-teal-50 px-2.5 py-1 text-theme-xs font-medium text-teal-700 dark:bg-teal-500/15 dark:text-teal-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                        {{ $role['nama'] }}
                    </span>
                    <x-sim.status-badge :teks="$pengguna['is_aktif'] ? 'Akun Aktif' : 'Akun Nonaktif'"
                        :warna="$pengguna['is_aktif'] ? 'success' : 'gray'" ukuran="sm" />
                </div>

                <dl class="mt-6 space-y-3 border-t border-gray-200 pt-5 text-left dark:border-gray-800">
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Masuk terakhir</dt>
                        <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                            {{ \Illuminate\Support\Carbon::parse($pengguna['last_login_at'])->translatedFormat('d F Y, H:i') }} WITA
                        </dd>
                    </div>
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Akun dibuat</dt>
                        <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                            {{ \Illuminate\Support\Carbon::parse($pengguna['created_at'])->translatedFormat('d F Y') }}
                        </dd>
                    </div>
                </dl>
            </div>
        </aside>

        {{-- Kolom kanan: tab data akun dan kewenangan --}}
        <div x-data="hashTabs('akun')" class="min-w-0">
            <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                {{-- Kepala tab --}}
                <div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Bagian profil">
                    <button type="button" role="tab" @click="setTab('akun')" :aria-selected="tab === 'akun'"
                        :class="tab === 'akun'
                            ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="shrink-0 border-b-2 px-4 py-2.5 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Data Akun
                    </button>
                    <button type="button" role="tab" @click="setTab('kewenangan')" :aria-selected="tab === 'kewenangan'"
                        :class="tab === 'kewenangan'
                            ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="shrink-0 border-b-2 px-4 py-2.5 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Kewenangan
                    </button>
                </div>

                {{-- Tab data akun --}}
                <div x-show="tab === 'akun'" role="tabpanel" class="p-5 sm:p-6">
                    <form method="POST" action="{{ route('profil.simpan') }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="grid gap-5 sm:grid-cols-2">
                            {{-- Nama dan username ditetapkan admin, ditampilkan baca-saja --}}
                            <div>
                                <span class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                    Nama Lengkap
                                </span>
                                <p class="flex h-11 items-center rounded-lg bg-gray-50 px-4 text-theme-sm text-gray-600 dark:bg-white/5 dark:text-gray-400">
                                    {{ $pengguna['nama'] }}
                                </p>
                            </div>

                            <div>
                                <span class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                    Username
                                </span>
                                <p class="flex h-11 items-center rounded-lg bg-gray-50 px-4 text-theme-sm text-gray-600 dark:bg-white/5 dark:text-gray-400">
                                    {{ $pengguna['username'] }}
                                </p>
                            </div>

                            <div>
                                <label for="email"
                                    class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                    Email<span class="text-error-500">*</span>
                                </label>
                                <input type="email" id="email" name="email" value="{{ old('email', $pengguna['email']) }}"
                                    autocomplete="email" required
                                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90" />
                                @error('email')
                                    <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="telepon"
                                    class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                    Nomor Telepon
                                </label>
                                <input type="tel" id="telepon" name="telepon"
                                    value="{{ old('telepon', $pengguna['telepon']) }}" autocomplete="tel"
                                    inputmode="numeric" placeholder="08xxxxxxxxxx"
                                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm tabular-nums text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90" />
                                @error('telepon')
                                    <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <span class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                    Jabatan
                                </span>
                                <p class="flex h-11 items-center rounded-lg bg-gray-50 px-4 text-theme-sm text-gray-600 dark:bg-white/5 dark:text-gray-400">
                                    {{ $pengguna['jabatan'] ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <p class="rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                            Nama, username, jabatan, dan role hanya dapat diubah admin lewat menu Manajemen Pengguna.
                            Hubungi admin bila ada yang perlu diperbaiki.
                        </p>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                Simpan Data Kontak
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Tab kewenangan --}}
                <div x-show="tab === 'kewenangan'" x-cloak role="tabpanel" class="p-5 sm:p-6">
                    <dl class="space-y-5">
                        <div>
                            <dt class="text-theme-sm font-medium text-gray-700 dark:text-gray-400">Role</dt>
                            <dd class="mt-1 text-theme-sm text-gray-800 dark:text-white/90">{{ $role['nama'] }}</dd>
                            <dd class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                {{ $role['deskripsi'] }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-theme-sm font-medium text-gray-700 dark:text-gray-400">Cakupan Data</dt>
                            <dd class="mt-1 text-theme-sm text-gray-800 dark:text-white/90">
                                {{ $cakupan?->label() ?? '-' }}
                            </dd>
                            <dd class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                @switch($cakupan)
                                    @case(\App\Enums\CakupanData::Semua)
                                        Anda dapat melihat data seluruh satuan permukiman di kawasan ini.
                                        @break
                                    @case(\App\Enums\CakupanData::PerSp)
                                        Anda hanya dapat melihat data pada satuan permukiman yang ditugaskan kepada Anda.
                                        @break
                                    @default
                                        Anda hanya dapat melihat data yang terkait dengan akun Anda sendiri.
                                @endswitch
                            </dd>
                        </div>

                        <div>
                            <dt class="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                Satuan Permukiman yang Ditugaskan
                            </dt>
                            <dd class="mt-1.5">
                                @if ($cakupan !== \App\Enums\CakupanData::PerSp)
                                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                                        Tidak diperlukan, karena cakupan data Anda mencakup seluruh satuan permukiman.
                                    </p>
                                @elseif (empty($pengguna['satuan_permukiman']))
                                    {{--
                                        Akun Per SP tanpa penugasan tidak melihat data apa pun, bukan
                                        melihat seluruhnya (agents/rules.md bagian 5.0b poin 7).
                                        Karena itu keadaan ini diberitahukan secara tegas.
                                    --}}
                                    <p class="rounded-lg border border-yellow-300 bg-yellow-50 p-3 text-theme-xs text-yellow-800 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-200">
                                        Belum ada satuan permukiman yang ditugaskan, sehingga daftar data masih kosong.
                                        Hubungi admin untuk meminta penugasan.
                                    </p>
                                @else
                                    <ul class="flex flex-wrap gap-2">
                                        @foreach ($pengguna['satuan_permukiman'] as $sp)
                                            <li
                                                class="rounded-full bg-gray-100 px-2.5 py-1 text-theme-xs text-gray-700 dark:bg-white/5 dark:text-gray-300">
                                                {{ $sp }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    <p class="mt-6 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                        Rincian izin per modul diatur admin pada menu Role dan Hak Akses.
                        Bila Anda memerlukan akses tambahan, sampaikan kepada admin.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
