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
    @endphp

    <x-sim.halaman-daftar judul="Manajemen Pengguna"
        keterangan="Akun petugas beserta role dan penugasannya."
        :remah="[['label' => 'Pengaturan'], ['label' => 'Pengguna']]"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('pengguna.index')"
        placeholder-cari="Cari nama atau username" judul-kosong="Belum ada pengguna"
        pesan-kosong="Akun petugas akan tampil di sini setelah dibuat admin.">

        <x-slot:aksi>
            <a href="{{ route('pengaturan.role') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Atur Role dan Hak Akses
            </a>
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Total Akun" :nilai="count($semua)" />
            <x-sim.stat-card label="Akun Aktif" :nilai="$aktif" />
            <x-sim.stat-card label="Akun Nonaktif" :nilai="count($semua) - $aktif"
                keterangan="Dinonaktifkan, bukan dihapus" />
            <x-sim.stat-card label="Wajib Ganti Sandi" :nilai="$perluGanti"
                keterangan="Kata sandi baru disetel admin" />
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
        Sistem juga menolak penonaktifan akun Admin terakhir yang masih aktif.
    </p>
@endsection
