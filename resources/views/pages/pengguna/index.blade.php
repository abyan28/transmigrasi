{{--
    Manajemen pengguna.

    Seluruh akun dibuat Admin; tidak ada pendaftaran mandiri
    (agents/rules.md bagian 14b poin 1). Penonaktifan memakai is_aktif,
    bukan penghapusan, agar jejak audit tetap utuh.

    Akun bercakupan Per SP wajib punya minimal satu penugasan SP. Bila belum,
    pengguna tidak melihat data apa pun, bukan melihat seluruhnya (bagian 5.0b
    poin 7). Keadaan itu ditandai jelas pada daftar.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::pengguna();

        $cari = trim((string) request('cari', ''));
        $filterRole = request('role');
        $filterAktif = request('aktif');

        $baris = array_values(array_filter($semua, function ($u) use ($cari, $filterRole, $filterAktif) {
            if ($cari !== '' && ! str_contains(mb_strtolower($u['nama']), mb_strtolower($cari))
                && ! str_contains(mb_strtolower($u['username']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterRole && $u['role'] !== $filterRole) {
                return false;
            }
            if ($filterAktif !== null && $filterAktif !== '' && (string) (int) $u['is_aktif'] !== $filterAktif) {
                return false;
            }

            return true;
        }));

        $adaFilter = $cari !== '' || $filterRole || ($filterAktif !== null && $filterAktif !== '');
        $aktif = count(array_filter($semua, fn ($u) => $u['is_aktif']));
        $perluGanti = count(array_filter($semua, fn ($u) => $u['password_harus_diganti']));
        $daftarRole = array_values(array_unique(array_column($semua, 'role')));

        // Admin aktif terakhir tidak boleh dinonaktifkan (rules.md 14b poin 16),
        // agar sistem tidak pernah kehilangan seluruh jalur administrasinya.
        $jumlahAdminAktif = count(array_filter(
            $semua,
            fn ($u) => $u['role'] === 'Admin' && $u['is_aktif'],
        ));

        $adminTerakhir = fn ($u) => $u['role'] === 'Admin'
            && $u['is_aktif']
            && $jumlahAdminAktif === 1;
    @endphp

    {{--
        Panel kredensial akun baru. Kata sandi sementara ditampilkan di layar
        SEKALIGUS dikirim ke surel petugas, dan keduanya memang diperlukan:
        surel menolong petugas yang berjaringan memadai, sedangkan tampilan
        layar menolong petugas di lokus bersinyal lemah yang sedang berdiri di
        depan Admin. Tanpa tampilan layar, jalur Admin yang justru dibuat untuk
        lokus bersinyal lemah kehilangan gunanya (rules.md 14b).

        Tampil sekali saja, sebab nilainya tidak pernah disimpan dalam bentuk
        yang dapat dibaca ulang.
    --}}
    @if (session('kredensial_baru'))
        @php $kredensial = session('kredensial_baru'); @endphp

        <div class="mb-6 rounded-2xl border border-green-300 bg-green-50 p-5 dark:border-green-500/30 dark:bg-green-500/10"
            role="status"
            x-data="{
                tersalin: false,
                salin() {
                    const teks = @js('Email: ' . ($kredensial['email'] ?? '') . ' | Kata sandi sementara: ' . ($kredensial['password'] ?? ''));
                    navigator.clipboard?.writeText(teks).then(() => {
                        this.tersalin = true;
                        setTimeout(() => { this.tersalin = false; }, 2500);
                    });
                },
            }">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-theme-sm font-semibold text-green-800 dark:text-green-200">
                        Akun {{ $kredensial['nama'] ?? 'petugas' }} berhasil dibuat
                    </p>
                    <p class="mt-1 text-theme-xs text-green-700 dark:text-green-300">
                        Catat atau salin kredensial di bawah ini. Kata sandi sementara hanya
                        ditampilkan sekali dan tidak dapat dibaca ulang.
                    </p>
                </div>

                <button type="button" @click="salin()"
                    class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-green-400 px-3 py-2 text-theme-xs font-medium text-green-800 transition hover:bg-green-100 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-green-500/40 dark:text-green-200 dark:hover:bg-green-500/10">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                    </svg>
                    <span x-text="tersalin ? 'Tersalin' : 'Salin Kredensial'"></span>
                </button>
            </div>

            <dl class="mt-4 grid gap-3 border-t border-green-300 pt-4 sm:grid-cols-2 dark:border-green-500/30">
                <div>
                    <dt class="text-theme-xs text-green-700 dark:text-green-300">Email untuk masuk</dt>
                    <dd class="mt-0.5 font-mono text-theme-sm text-green-900 dark:text-green-100">
                        {{ $kredensial['email'] ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-theme-xs text-green-700 dark:text-green-300">Kata sandi sementara</dt>
                    <dd class="mt-0.5 font-mono text-theme-sm font-semibold text-green-900 dark:text-green-100">
                        {{ $kredensial['password'] ?? '-' }}
                    </dd>
                </div>
            </dl>

            {{--
                Spanduk kejujuran. Tampilannya sudah lengkap, tetapi pengiriman
                surel menunggu backend. Tanpa keterangan ini Admin dapat mengira
                petugas sudah menerima surelnya, lalu tidak menyerahkan kata
                sandi secara langsung.
            --}}
            <p class="mt-4 rounded-lg border border-yellow-300 bg-yellow-50 p-3 text-theme-xs text-yellow-800 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-200">
                <span class="font-medium">Pengiriman email belum aktif.</span>
                Kredensial di atas belum benar-benar terkirim ke petugas. Sampai backend selesai,
                serahkan kata sandi ini secara langsung.
            </p>
        </div>
    @endif

    <x-sim.halaman-daftar judul="Manajemen Pengguna"
        keterangan="Akun petugas beserta role dan penugasannya."
        :remah="\App\Helpers\RemahHelper::untuk('/pengguna')"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('pengguna.index')"
        placeholder-cari="Cari nama atau username" judul-kosong="Belum ada pengguna"
        pesan-kosong="Akun petugas akan tampil di sini setelah dibuat admin.">

        <x-slot:aksi>
            <a href="{{ route('pengaturan.role') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Atur Role dan Hak Akses
            </a>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahPengguna')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Akun Petugas
            </button>
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Total Akun" :nilai="count($semua)" />
            <x-sim.stat-card label="Akun Aktif" :nilai="$aktif" />
            <x-sim.stat-card label="Akun Nonaktif" :nilai="count($semua) - $aktif"
                keterangan="Dinonaktifkan, bukan dihapus" />
            <x-sim.stat-card label="Wajib Ganti Sandi" :nilai="$perluGanti"
                keterangan="Pengguna yang wajib mengubah kata sandi" />
        </x-slot:ringkasan>

        <x-slot:filter>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="filter_role"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Role</label>
                    <select id="filter_role" name="role"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua role</option>
                        @foreach ($daftarRole as $r)
                            <option value="{{ $r }}" @selected($filterRole === $r)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_aktif"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Status Akun</label>
                    <select id="filter_aktif" name="aktif"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua status</option>
                        <option value="1" @selected($filterAktif === '1')>Aktif</option>
                        <option value="0" @selected($filterAktif === '0')>Nonaktif</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="h-10 flex-1 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Terapkan Filter
                    </button>
                    @if ($adaFilter)
                        <a href="{{ route('pengguna.index') }}"
                            class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Bersihkan
                        </a>
                    @endif
                </div>
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Username</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Role</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Penugasan SP</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Masuk Terakhir</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $u)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-500 text-theme-xs font-semibold text-white"
                            aria-hidden="true">
                            {{ DummyData::inisial($u['nama']) }}
                        </span>
                        <div class="min-w-0">
                            <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $u['nama'] }}</p>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $u['jabatan'] }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    {{ $u['username'] }}
                    <p class="mt-0.5 truncate text-theme-xs text-gray-500 dark:text-gray-400">{{ $u['email'] }}</p>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $u['role'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    @if ($u['role'] === 'Operator SP')
                        @if (empty($u['satuan_permukiman']))
                            {{-- Keadaan berbahaya: operator tanpa penugasan tidak melihat data apa pun --}}
                            <span class="text-theme-xs font-medium text-yellow-700 dark:text-yellow-400">
                                Belum ditugaskan
                            </span>
                        @else
                            {{ implode(', ', $u['satuan_permukiman']) }}
                        @endif
                    @else
                        <span class="text-theme-xs text-gray-500 dark:text-gray-400">Seluruh kawasan</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    @if ($u['last_login_at'])
                        {{ \Illuminate\Support\Carbon::parse($u['last_login_at'])->translatedFormat('d M Y, H:i') }}
                    @else
                        <span class="text-theme-xs text-gray-500 dark:text-gray-400">Belum pernah masuk</span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    <div class="flex flex-wrap gap-1.5">
                        <x-sim.status-badge :teks="$u['is_aktif'] ? 'Aktif' : 'Nonaktif'"
                            :warna="$u['is_aktif'] ? 'success' : 'gray'" ukuran="sm" />
                        @if ($u['password_harus_diganti'])
                            <x-sim.status-badge teks="Wajib ganti sandi" warna="warning" ukuran="sm" />
                        @endif
                    </div>
                </td>
                <td class="px-5 py-3">
                    <div class="flex items-center justify-end gap-1">
                        {{-- Rincian akun --}}
                        <button type="button"
                            @click="$dispatch('buka-detail-pengguna', { nama: 'detailPengguna', akun: @js($u) })"
                            aria-label="Lihat rincian akun {{ $u['nama'] }}"
                            class="rounded-lg p-2 text-gray-500 transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>

                        {{-- Ubah akun --}}
                        <button type="button"
                            @click="$dispatch('buka-modal-baris', { nama: 'formUbahPenggunaBaris', data: @js($u + ['id' => $u['id_user']]) })"
                            aria-label="Ubah data akun {{ $u['nama'] }}"
                            class="rounded-lg p-2 text-gray-500 transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 hover:bg-gray-100 hover:text-brand-600 dark:hover:bg-white/5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                            </svg>
                        </button>

                        {{-- Setel ulang kata sandi, ikon kunci --}}
                        <button type="button" @click="$dispatch('buka-setel-sandi', { akun: @js($u) })"
                            aria-label="Setel ulang kata sandi {{ $u['nama'] }}"
                            class="rounded-lg p-2 text-gray-500 transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 hover:bg-gray-100 hover:text-brand-600 dark:hover:bg-white/5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                            </svg>
                        </button>

                        {{--
                            Tombol nonaktifkan sengaja TIDAK dirender untuk Admin
                            aktif terakhir (rules.md 14b poin 16). Merender tombol
                            lalu menolaknya di server berarti memasang kontrol yang
                            tidak berfungsi, yang dilarang R-26.

                            Sejak 2026-08-17 tidak ada pula penanda pengganti pada
                            barisnya. Alasannya cukup dinyatakan sekali lewat
                            keterangan di bawah tabel, bukan diulang pada setiap
                            baris Admin.
                        --}}
                        @if ($u['is_aktif'] && ! $adminTerakhir($u))
                            <button type="button"
                                @click="$dispatch('buka-konfirmasi', { nama: 'nonaktifkanPengguna', aksi: '/pengguna/{{ $u['id_user'] }}/nonaktifkan' })"
                                aria-label="Nonaktifkan akun {{ $u['nama'] }}"
                                class="rounded-lg p-2 text-gray-500 transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </button>
                        @elseif ($u['is_aktif'])
                            {{-- Admin aktif terakhir: tanpa tombol, tanpa penanda. --}}
                        @else
                            {{--
                                Jalur mengaktifkan kembali. Tanpa tombol ini akun yang
                                sudah dinonaktifkan terkunci selamanya, sebab akun memang
                                tidak pernah dihapus dan tidak ada jalur lain menyalakannya.
                            --}}
                            <button type="button"
                                @click="$dispatch('buka-konfirmasi', { nama: 'aktifkanPengguna', aksi: '/pengguna/{{ $u['id_user'] }}/aktifkan' })"
                                aria-label="Aktifkan kembali akun {{ $u['nama'] }}"
                                class="rounded-lg p-2 text-gray-500 transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 hover:bg-green-50 hover:text-green-600 dark:hover:bg-green-500/10">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $u)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $u['nama'] }}</p>
                        <x-sim.status-badge :teks="$u['is_aktif'] ? 'Aktif' : 'Nonaktif'"
                            :warna="$u['is_aktif'] ? 'success' : 'gray'" ukuran="sm" />
                    </div>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $u['username'] }} &middot; {{ $u['role'] }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
        Akun tidak pernah dihapus, hanya dinonaktifkan, agar jejak audit tetap utuh.
        Akun Admin terakhir yang masih aktif tidak memiliki tombol nonaktifkan, sebab
        sistem menolak penonaktifannya agar jalur administrasi tidak pernah hilang.
    </p>

    {{-- Modal tambah akun --}}
    <x-sim.modal-form nama="formTambahPengguna" judul="Tambah Akun Petugas"
        keterangan="Akun hanya dapat dibuat admin. Tidak ada pendaftaran mandiri."
        :aksi="route('pengguna.simpan')" ukuran="xl" label-simpan="Simpan Akun">
        @include('pages.pengguna.form', ['awalan' => 'tambah', 'mode' => 'tambah'])
    </x-sim.modal-form>

    {{-- Modal rincian akun --}}
    @include('pages.pengguna.detail')

    {{-- Modal setel ulang kata sandi --}}
    @include('pages.pengguna.setel-sandi')

    {{-- Konfirmasi penonaktifan --}}
    <x-sim.confirm-dialog nama="nonaktifkanPengguna" judul="Nonaktifkan akun ini?"
        pesan="Petugas tidak akan dapat masuk, tetapi seluruh riwayat tindakannya tetap tersimpan. Akun dapat diaktifkan kembali sewaktu-waktu."
        label-setuju="Nonaktifkan" metode="POST" ragam="bahaya" />

    {{--
        Konfirmasi pengaktifan. Memakai ragam peringatan, bukan bahaya, sebab
        tindakannya memulihkan akses dan bukan tindakan merusak.
    --}}
    <x-sim.confirm-dialog nama="aktifkanPengguna" judul="Aktifkan kembali akun ini?"
        pesan="Petugas dapat kembali masuk memakai kredensial yang sama seperti sebelum dinonaktifkan."
        label-setuju="Aktifkan" metode="POST" ragam="peringatan" />

    {{-- Modal ubah akun, satu untuk seluruh baris --}}
    <x-sim.modal-form nama="formUbahPenggunaBaris" judul="Ubah Akun Petugas"
        keterangan="Kata sandi tidak dapat disunting di sini; gunakan Setel Ulang Kata Sandi."
        pola-aksi="/pengguna/:id" metode="PUT" ukuran="xl" label-simpan="Simpan Perubahan">
        @include('pages.pengguna.form', ['awalan' => 'ubahBaris', 'mode' => 'ubah'])
    </x-sim.modal-form>
@endsection
