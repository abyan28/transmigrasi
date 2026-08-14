{{--
    Daftar pengaduan.

    Modul ini memakai ALUR STATUS berurutan, sehingga kolom badge menampilkan
    status penanganan beserta prioritasnya.

    Pengaduan yang belum selesai diurutkan lebih dulu, dan yang berprioritas
    Mendesak ditandai jelas, karena inilah yang perlu segera ditindaklanjuti.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;
        use App\Enums\StatusPengaduan;
        use App\Enums\PrioritasPengaduan;

        $semua = DummyData::pengaduan();

        $cari = trim((string) request('cari', ''));
        $filterSp = request('sp');
        $filterStatus = request('status');
        $filterKategori = request('kategori');
        $filterPrioritas = request('prioritas');

        $baris = array_values(array_filter($semua, function ($p) use ($cari, $filterSp, $filterStatus, $filterKategori, $filterPrioritas) {
            if ($cari !== '') {
                $cocok = str_contains(mb_strtolower($p['judul']), mb_strtolower($cari))
                    || str_contains(mb_strtolower($p['nomor_pengaduan']), mb_strtolower($cari))
                    || str_contains(mb_strtolower($p['nama_pelapor']), mb_strtolower($cari));

                if (! $cocok) {
                    return false;
                }
            }

            if ($filterSp && (string) $p['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }

            if ($filterStatus && $p['status'] !== $filterStatus) {
                return false;
            }

            if ($filterKategori && $p['kategori'] !== $filterKategori) {
                return false;
            }

            if ($filterPrioritas && $p['prioritas'] !== $filterPrioritas) {
                return false;
            }

            return true;
        }));

        // Yang belum selesai didahulukan, lalu diurutkan menurut kemendesakan.
        $urutanPrioritas = ['Mendesak' => 0, 'Tinggi' => 1, 'Sedang' => 2, 'Rendah' => 3];
        usort($baris, function ($a, $b) use ($urutanPrioritas) {
            $selesaiA = $a['status'] === StatusPengaduan::Selesai->value ? 1 : 0;
            $selesaiB = $b['status'] === StatusPengaduan::Selesai->value ? 1 : 0;

            if ($selesaiA !== $selesaiB) {
                return $selesaiA <=> $selesaiB;
            }

            return $urutanPrioritas[$a['prioritas']] <=> $urutanPrioritas[$b['prioritas']];
        });

        $adaFilter = $cari !== '' || $filterSp || $filterStatus || $filterKategori || $filterPrioritas;

        $belumSelesai = count(array_filter($semua, fn ($p) => $p['status'] !== StatusPengaduan::Selesai->value));
        $menungguDiterima = count(array_filter($semua, fn ($p) => $p['status'] === StatusPengaduan::MenungguDiterima->value));
        $mendesak = count(array_filter($semua, fn ($p) => $p['prioritas'] === PrioritasPengaduan::Mendesak->value
            && $p['status'] !== StatusPengaduan::Selesai->value));

        $bolehCatat = true;
        $bolehUbah = true;
        $bolehHapus = true;
    @endphp

    <x-sim.page-header judul="Pengaduan"
        keterangan="Laporan warga dan petugas beserta perkembangan penanganannya."
        :remah="[['label' => 'Pengaduan'], ['label' => 'Daftar Pengaduan']]">
        <x-slot:aksi>
            <a href="{{ route('pengaduan.rekap') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Lihat Rekap Pengaduan
            </a>
            @if ($bolehCatat)
                <button type="button" @click="$dispatch('buka-modal', 'formCatatPengaduan')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Catat Pengaduan Warga
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-sim.stat-card label="Total Pengaduan" :nilai="number_format(count($semua), 0, ',', '.')" satuan="laporan" />
        <x-sim.stat-card label="Belum Selesai" :nilai="number_format($belumSelesai, 0, ',', '.')"
            keterangan="Masih dalam penanganan" />
        <x-sim.stat-card label="Menunggu Diterima" :nilai="number_format($menungguDiterima, 0, ',', '.')"
            keterangan="Perlu disaring petugas" />
        <x-sim.stat-card label="Berprioritas Mendesak" :nilai="number_format($mendesak, 0, ',', '.')"
            keterangan="Perlu segera ditindaklanjuti" />
    </div>

    <form method="GET" action="{{ route('pengaduan.index') }}">
        <x-sim.data-table :jumlah="count($baris)" :kata-kunci="$cari"
            placeholder-cari="Cari nomor, perihal, atau pelapor" judul-kosong="Belum ada pengaduan"
            pesan-kosong="Laporan warga akan tampil di sini setelah masuk.">

            <x-slot:filter>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label for="filter_sp"
                            class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                            Satuan Permukiman
                        </label>
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
                        <label for="filter_status"
                            class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                            Status Penanganan
                        </label>
                        <select id="filter_status" name="status"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                            <option value="">Semua status</option>
                            @foreach (StatusPengaduan::opsi() as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($filterStatus === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter_kategori"
                            class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                            Kategori
                        </label>
                        <select id="filter_kategori" name="kategori"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                            <option value="">Semua kategori</option>
                            @foreach (\App\Enums\KategoriPengaduan::opsi() as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($filterKategori === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter_prioritas"
                            class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                            Prioritas
                        </label>
                        <select id="filter_prioritas" name="prioritas"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                            <option value="">Semua prioritas</option>
                            @foreach (PrioritasPengaduan::opsi() as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($filterPrioritas === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                            class="h-10 flex-1 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                            Terapkan
                        </button>
                        @if ($adaFilter)
                            <a href="{{ route('pengaduan.index') }}"
                                class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                Bersihkan
                            </a>
                        @endif
                    </div>
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
                    <a href="{{ route('pengaduan.index') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Bersihkan Filter
                    </a>
                @elseif ($bolehCatat)
                    <button type="button" @click="$dispatch('buka-modal', 'formCatatPengaduan')"
                        class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Catat Pengaduan Warga
                    </button>
                @endif
            </x-slot:aksiKosong>

            <x-slot:kepala>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nomor</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Perihal</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Satuan Permukiman
                </th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Bidang</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Prioritas</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Aksi
                </th>
            </x-slot:kepala>

            @foreach ($baris as $p)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                        {{ $p['nomor_pengaduan'] }}
                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ \Illuminate\Support\Carbon::parse($p['tanggal_pengaduan'])->translatedFormat('d M Y') }}
                        </p>
                    </td>
                    <td class="px-5 py-3">
                        <a href="{{ route('pengaduan.detail', $p['id_pengaduan']) }}"
                            class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                            {{ $p['judul'] }}
                        </a>
                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $p['kategori'] }} &middot; {{ $p['nama_pelapor'] }}
                        </p>
                    </td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                        {{ $p['satuan_permukiman'] }}
                    </td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $p['bidang'] }}</td>
                    <td class="px-5 py-3">
                        <x-sim.status-badge :status="PrioritasPengaduan::from($p['prioritas'])" />
                    </td>
                    <td class="px-5 py-3">
                        <x-sim.status-badge :status="StatusPengaduan::from($p['status'])" />
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('pengaduan.detail', $p['id_pengaduan']) }}"
                                aria-label="Lihat rincian pengaduan {{ $p['nomor_pengaduan'] }}"
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
                                {{-- Ubah sejajar dengan Hapus, sebab menghapus lebih berisiko daripada menyunting --}}
                                <button type="button"
                                    @click.prevent="$dispatch('buka-modal-baris', {
                                        nama: 'formUbahPengaduanBaris',
                                        data: @js($p + ['id' => $p['id_pengaduan']])
                                    })"
                                    aria-label="Ubah data {{ $p['judul'] }}"
                                    class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                    </svg>
                                </button>
                            @endif

                            {{--
                                Penanganan dipisahkan dari tombol Ubah, sebab memajukan status
                                berbeda sifat dari menyunting isi laporan dan tercatat berbeda
                                pada audit log.
                            --}}
                            <button type="button"
                                @click.prevent="$dispatch('buka-tangani-pengaduan', {
                                    nama: 'tanganiPengaduanBaris',
                                    data: @js($p + ['id' => $p['id_pengaduan']])
                                })"
                                aria-label="Perbarui status penanganan {{ $p['judul'] }}"
                                class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-teal-700 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>

                            @if ($bolehHapus)
                                <button type="button"
                                    @click.prevent="$dispatch('buka-konfirmasi', {
                                        nama: 'hapusPengaduan',
                                        aksi: '{{ route('pengaduan.hapus', $p['id_pengaduan']) }}'
                                    })"
                                    aria-label="Hapus pengaduan {{ $p['nomor_pengaduan'] }}"
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

            <x-slot:kartu>
                @foreach ($baris as $p)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <a href="{{ route('pengaduan.detail', $p['id_pengaduan']) }}"
                                class="min-w-0 rounded focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                    {{ $p['judul'] }}
                                </p>
                                <p class="mt-0.5 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                                    {{ $p['nomor_pengaduan'] }}
                                </p>
                            </a>
                            <x-sim.status-badge :status="PrioritasPengaduan::from($p['prioritas'])" ukuran="sm" />
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <x-sim.status-badge :status="StatusPengaduan::from($p['status'])" ukuran="sm" />
                            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
                                {{ $p['satuan_permukiman'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </x-slot:kartu>
        </x-sim.data-table>
    </form>

    @if ($bolehCatat)
        <x-sim.modal-form nama="formCatatPengaduan" judul="Catat Pengaduan Warga"
            keterangan="Dipakai saat warga menyampaikan laporan secara lisan kepada petugas."
            :aksi="route('pengaduan.simpan')" ukuran="xl" label-simpan="Simpan Pengaduan">
            @include('pages.pengaduan.form', ['awalan' => 'tambah'])
        </x-sim.modal-form>
    @endif

    @if ($bolehHapus)
        <x-sim.confirm-dialog nama="hapusPengaduan" judul="Hapus pengaduan ini?"
            pesan="Riwayat penanganan yang sudah tercatat ikut tidak dapat diakses sampai data dipulihkan."
            label-setuju="Hapus Pengaduan" />
    @endif

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahPengaduanBaris" judul="Ubah Data Pengaduan"
            keterangan="Perubahan tercatat pada riwayat penanganan."
            pola-aksi="/pengaduan/:id" metode="PUT" ukuran="xl"
            label-simpan="Simpan Perubahan">
            @include('pages.pengaduan.form', ['awalan' => 'ubahBaris'])
        </x-sim.modal-form>
    @endif

    {{-- Modal penanganan, satu untuk seluruh baris --}}
    @include('pages.pengaduan.tangani-baris')
@endsection
