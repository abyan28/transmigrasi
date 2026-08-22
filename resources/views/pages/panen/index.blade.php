{{--
    Daftar hasil panen.

    Volume ditampilkan apa adanya sesuai satuan baku komoditasnya, beserta
    setara tonnya sebagai keterangan. Penjumlahan lintas komoditas WAJIB
    memakai hasil konversi, karena menjumlahkan ton dan kilogram begitu saja
    menghasilkan angka yang keliru (agents/rules.md bagian 9 poin 5).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::hasilPanen();

        $cari = trim((string) request('cari', ''));
        $filterSp = request('sp');
        $filterKomoditas = request('komoditas');
        $filterTahun = request('tahun');

        // Tahun panen diturunkan dari tanggalnya, menggantikan penyaringan per
        // musim tanam yang dicabut 2026-08-22 bersama fiturnya.
        $tahunPanen = fn ($p) => $p['periode_panen']
            ? (int) substr($p['periode_panen'], 0, 4)
            : null;

        $baris = array_values(array_filter($semua, function ($p) use ($cari, $filterSp, $filterKomoditas, $filterTahun, $tahunPanen) {
            if ($cari !== '') {
                $cocok = str_contains(mb_strtolower($p['poktan']), mb_strtolower($cari))
                    || str_contains(mb_strtolower($p['komoditas']), mb_strtolower($cari));

                if (! $cocok) {
                    return false;
                }
            }

            if ($filterSp && (string) $p['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }

            if ($filterKomoditas && $p['komoditas'] !== $filterKomoditas) {
                return false;
            }

            if ($filterTahun && (string) $tahunPanen($p) !== (string) $filterTahun) {
                return false;
            }

            return true;
        }));

        $adaFilter = $cari !== '' || $filterSp || $filterKomoditas || $filterTahun;

        // Total dihitung setelah konversi ke ton, bukan menjumlahkan volume mentah.
        $totalTonTampil = array_sum(array_map(
            fn ($p) => DummyData::keTon($p['produksi'], $p['satuan']),
            $baris
        ));

        $totalTonSemua = array_sum(array_map(
            fn ($p) => DummyData::keTon($p['produksi'], $p['satuan']),
            $semua
        ));

        $daftarKomoditas = array_values(array_unique(array_column($semua, 'komoditas')));

        $daftarTahun = array_values(array_filter(array_unique(array_map($tahunPanen, $semua))));
        rsort($daftarTahun);

        $bolehTambah = true;
        $bolehUbah = true;
        $bolehHapus = true;
    @endphp

    <x-sim.page-header judul="Hasil Panen"
        keterangan="Catatan panen per periode beserta produksi dan harga jualnya."
        :remah="\App\Helpers\RemahHelper::untuk('/panen')">
        <x-slot:aksi>
            {{--
                Impor massal diletakkan mendahului tombol tambah namun bergaya
                sekunder, sebab menambah satu data tetap tindakan yang paling
                sering dipakai (PRD 8.1).
            --}}
            <button type="button" @click="$dispatch('buka-modal', 'imporPanen')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Impor Data
            </button>
            <a href="{{ route('panen.rekap') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Lihat Rekap Panen
            </a>
            @if ($bolehTambah)
                <button type="button" @click="$dispatch('buka-modal', 'formTambahPanen')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Catat Hasil Panen
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-sim.stat-card label="Catatan Panen" :nilai="number_format(count($semua), 0, ',', '.')" satuan="catatan" />
        <x-sim.stat-card label="Total Volume" :nilai="number_format($totalTonSemua, 3, ',', '.')" satuan="ton"
            keterangan="Hasil konversi seluruh komoditas" />
        <x-sim.stat-card label="Jenis Komoditas" :nilai="number_format(count($daftarKomoditas), 0, ',', '.')" />
                <x-sim.stat-card label="Tahun Panen Tercatat" :nilai="number_format(count($daftarTahun), 0, ',', '.')" />
    </div>

    <form method="GET" action="{{ route('panen.index') }}">
        <x-sim.data-table :jumlah="count($baris)" :kata-kunci="$cari"
            placeholder-cari="Cari kelompok tani atau komoditas" judul-kosong="Belum ada catatan panen"
            pesan-kosong="Hasil panen akan tampil di sini setelah dicatat petugas.">

            <x-slot:filter>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
                        <label for="filter_komoditas"
                            class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                            Komoditas
                        </label>
                        <select id="filter_komoditas" name="komoditas"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                            <option value="">Semua komoditas</option>
                            @foreach ($daftarKomoditas as $k)
                                <option value="{{ $k }}" @selected($filterKomoditas === $k)>{{ $k }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter_tahun"
                            class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                            Tahun Panen
                        </label>
                        <select id="filter_tahun" name="tahun"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                            <option value="">Semua tahun</option>
                            @foreach ($daftarTahun as $t)
                                <option value="{{ $t }}" @selected((string) $filterTahun === (string) $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                            class="h-10 flex-1 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                            Terapkan Filter
                        </button>
                        @if ($adaFilter)
                            <a href="{{ route('panen.index') }}"
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

                    <x-sim.tombol-ekspor />
            </x-slot:aksiKanan>

            <x-slot:aksiKosong>
                @if ($adaFilter)
                    <a href="{{ route('panen.index') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Bersihkan Filter
                    </a>
                @elseif ($bolehTambah)
                    <button type="button" @click="$dispatch('buka-modal', 'formTambahPanen')"
                        class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Catat Hasil Panen
                    </button>
                @endif
            </x-slot:aksiKosong>

            <x-slot:kepala>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Komoditas</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kelompok Tani</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tanggal</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Volume</th>
                <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Produktivitas</th>
                <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Aksi
                </th>
            </x-slot:kepala>

            @foreach ($baris as $p)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                    <td class="px-5 py-3">
                        <a href="{{ route('panen.detail', $p['id_hasil_panen']) }}"
                            class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                            {{ $p['komoditas'] }}
                        </a>
                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $p['satuan_permukiman'] }}
                        </p>
                    </td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $p['poktan'] }}</td>
                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                        {{ \Illuminate\Support\Carbon::parse($p['periode_panen'] . '-01')->translatedFormat('F Y') }}
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($p['produksi'], 3, ',', '.') }} {{ $p['satuan'] }}
                        </span>
                        {{-- Setara ton ditampilkan bila satuannya bukan ton --}}
                        @if ($p['satuan'] !== 'Ton')
                            <p class="mt-0.5 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                                setara {{ number_format(DummyData::keTon($p['produksi'], $p['satuan']), 3, ',', '.') }} ton
                            </p>
                        @endif
                    </td>
                    {{-- Kualitas dicabut 2026-08-22, digantikan produktivitas:
                         angka terukur lebih berguna daripada label mutu. --}}
                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                        {{ rtrim(rtrim(number_format($p['produktivitas'], 3, ',', '.'), '0'), ',') }}
                        {{ $p['satuan'] }}/ha
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('panen.detail', $p['id_hasil_panen']) }}"
                                aria-label="Lihat rincian panen {{ $p['komoditas'] }} milik {{ $p['poktan'] }}"
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
                                        nama: 'formUbahPanenBaris',
                                        data: @js($p + ['id' => $p['id_hasil_panen']])
                                    })"
                                    aria-label="Ubah data {{ $p['komoditas'] }}"
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
                                        nama: 'hapusPanen',
                                        aksi: '{{ route('panen.hapus', $p['id_hasil_panen']) }}'
                                    })"
                                    aria-label="Hapus catatan panen {{ $p['komoditas'] }}"
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

            <x-slot:kaki>
                <tr class="motif-baris-total">
                    {{-- Tiga kolom pertama: Komoditas, Kelompok Tani, Periode. Kolom
                         Musim dicabut 2026-08-22, sehingga colspan turun dari 4. --}}
                    <td colspan="3" class="px-5 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                        Total volume yang ditampilkan, dikonversi ke ton
                    </td>
                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                        {{ number_format($totalTonTampil, 3, ',', '.') }} ton
                    </td>
                    <td colspan="2"></td>
                </tr>
            </x-slot:kaki>

            <x-slot:kartu>
                @foreach ($baris as $p)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <a href="{{ route('panen.detail', $p['id_hasil_panen']) }}"
                                class="min-w-0 rounded focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                    {{ $p['komoditas'] }}
                                </p>
                                <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $p['poktan'] }}</p>
                            </a>
                            <span class="shrink-0 text-theme-sm tabular-nums text-gray-700 dark:text-gray-300">
                                {{ number_format($p['produksi'], 3, ',', '.') }} {{ $p['satuan'] }}
                            </span>
                        </div>
                        <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ \Illuminate\Support\Carbon::parse($p['periode_panen'] . '-01')->translatedFormat('F Y') }}
                            &middot; {{ $p['satuan_permukiman'] }}
                        </p>
                    </div>
                @endforeach
            </x-slot:kartu>
        </x-sim.data-table>
    </form>

    @if ($bolehTambah)
        <x-sim.modal-form nama="formTambahPanen" judul="Catat Hasil Panen"
            keterangan="Satuan volume mengikuti komoditas yang dipilih." :aksi="route('panen.simpan')" ukuran="xl"
            label-simpan="Simpan Hasil Panen">
            @include('pages.panen.form', ['awalan' => 'tambah'])
        </x-sim.modal-form>
    @endif

    @if ($bolehHapus)
        <x-sim.confirm-dialog nama="hapusPanen" judul="Hapus catatan panen ini?"
            pesan="Catatan yang dihapus tidak lagi dihitung pada rekap dan dashboard."
            label-setuju="Hapus Catatan Panen" />
    @endif

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahPanenBaris" judul="Ubah Data Panen"
            keterangan="Satuan mengikuti komoditas dan tidak dapat diubah di sini."
            pola-aksi="/panen/:id" metode="PUT" ukuran="xl"
            label-simpan="Simpan Perubahan">
            @include('pages.panen.form', ['awalan' => 'ubahBaris'])
        </x-sim.modal-form>
    @endif

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporPanen" judul="Impor Hasil Panen"
        entitas="hasil-panen"
        :kolom-wajib="['periode_panen', 'komoditas', 'kelompok_tani', 'produksi', 'satuan']" />
@endsection
