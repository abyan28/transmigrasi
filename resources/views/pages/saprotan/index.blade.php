{{--
    Penyaluran sarana produksi pertanian.

    Penerima dapat berupa kelompok tani maupun individu transmigran
    (agents/rules.md bagian 7c poin 3). Penyaluran kepada anggota poktan hanya
    untuk anggota berstatus aktif (poin 4), aturan yang dijaga saat pemilihan
    penerima pada Tahap 6.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::saprotan();

        $cari = trim((string) request('cari', ''));
        $filterSp = request('sp');
        $filterJenis = request('jenis');

        $baris = array_values(array_filter($semua, function ($s) use ($cari, $filterSp, $filterJenis) {
            if ($cari !== '' && ! str_contains(mb_strtolower($s['nama']), mb_strtolower($cari))
                && ! str_contains(mb_strtolower($s['penerima']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterSp && (string) $s['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }
            if ($filterJenis && $s['jenis'] !== $filterJenis) {
                return false;
            }

            return true;
        }));

        $adaFilter = $cari !== '' || $filterSp || $filterJenis;
        $jenisUnik = array_values(array_unique(array_column($semua, 'jenis')));

        // Banyaknya poktan yang pernah menerima, menggantikan pasangan kartu
        // "Kepada Poktan" dan "Kepada Individu". Penerima kini selalu poktan,
        // sehingga kartu lama hanya menampilkan seluruh data dan angka nol.
        $poktanPenerima = count(array_unique(array_column($semua, 'poktan_id')));
    @endphp

    <x-sim.halaman-daftar judul="Saprotan"
        keterangan="Penyaluran benih, pupuk, pestisida, dan mulsa kepada petani."
        :remah="\App\Helpers\RemahHelper::untuk('/saprotan')"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('saprotan.index')"
        placeholder-cari="Cari nama saprotan atau penerima" judul-kosong="Belum ada penyaluran saprotan"
        pesan-kosong="Penyaluran sarana produksi akan tampil di sini setelah dicatat.">

        <x-slot:aksi>
            {{--
                Impor massal diletakkan mendahului tombol tambah namun bergaya
                sekunder, sebab menambah satu data tetap tindakan yang paling
                sering dipakai (PRD 8.1).
            --}}
            <button type="button" @click="$dispatch('buka-modal', 'imporSaprotan')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Impor Data
            </button>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahSaprotan')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Saprotan
            </button>
        </x-slot:aksi>

        <x-slot:ringkasan>
            <x-sim.stat-card label="Catatan Penyaluran" :nilai="count($semua)" />
            <x-sim.stat-card label="Jenis Saprotan" :nilai="count($jenisUnik)" />
            <x-sim.stat-card label="Poktan Penerima" :nilai="$poktanPenerima" />
        </x-slot:ringkasan>

        <x-slot:filter>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="filter_sp"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Satuan Permukiman</label>
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
                    <label for="filter_jenis"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Jenis Saprotan</label>
                    <select id="filter_jenis" name="jenis"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua jenis</option>
                        @foreach (\App\Enums\JenisSaprotan::opsi() as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($filterJenis === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="h-10 flex-1 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Terapkan Filter
                    </button>
                    @if ($adaFilter)
                        <a href="{{ route('saprotan.index') }}"
                            class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Bersihkan
                        </a>
                    @endif
                </div>
            </div>
        </x-slot:filter>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jenis</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama Saprotan</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Penerima</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tanggal</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Sumber</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $s)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $s['jenis'] }}</td>
                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                    {{ $s['nama'] }}
                    {{-- Komoditas hanya ada pada benih, sehingga barisnya
                         tidak selalu tampil. --}}
                    @if (! empty($s['komoditas']))
                        <p class="mt-0.5 text-theme-xs font-normal text-gray-500 dark:text-gray-400">
                            {{ $s['komoditas'] }}
                        </p>
                    @endif
                </td>
                {{--
                    Sisa stok ditampilkan HANYA untuk benih, sebab hanya benih
                    yang dikurangi pemakaiannya oleh penanaman. Menampilkannya
                    pada pupuk berarti menjanjikan penghitungan yang tidak
                    pernah dilakukan.
                --}}
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ number_format($s['jumlah'], 0, ',', '.') }} {{ $s['satuan'] }}
                    @if ($s['jenis'] === \App\Enums\JenisSaprotan::Benih->value)
                        @php($sisa = \App\Support\DummyData::sisaBenih($s['id_saprotan']))
                        <p class="mt-0.5 text-theme-xs {{ $sisa > 0 ? 'text-gray-500 dark:text-gray-400' : 'text-error-500' }}">
                            {{ $sisa > 0 ? 'sisa ' . rtrim(rtrim(number_format($sisa, 2, ',', '.'), '0'), ',') . ' ' . $s['satuan'] : 'habis' }}
                        </p>
                    @endif
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    @if ($s['poktan_id'])
                        <a href="{{ route('poktan.detail', $s['poktan_id']) }}"
                            class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                            {{ $s['penerima'] }}
                        </a>
                    @else
                        {{ $s['penerima'] }}
                    @endif
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $s['satuan_permukiman'] }}</p>
                </td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    {{ \Illuminate\Support\Carbon::parse($s['tanggal_perolehan'])->translatedFormat('d M Y') }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $s['sumber'] }}</td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris :rincian-url="route('saprotan.detail', $s['id_saprotan'])"
                        modal-ubah="formUbahSaprotanBaris"
                        :data-baris="$s + ['id' => $s['id_saprotan']]"
                        :hapus-url="'/saprotan/' . $s['id_saprotan']"
                        konfirmasi-hapus="hapusSaprotan" :label="$s['nama']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $s)
                <div class="p-4">
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $s['nama'] }}</p>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ number_format($s['jumlah'], 0, ',', '.') }} {{ $s['satuan'] }} &middot; {{ $s['penerima'] }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahSaprotan" judul="Tambah Saprotan"
        keterangan="Penyaluran hanya dapat ditujukan kepada anggota berstatus aktif."
        :aksi="route('saprotan.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.saprotan.form', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahSaprotanBaris" judul="Ubah Data Saprotan"
        keterangan="Penerima individu hanya dapat dipilih dari anggota aktif."
        pola-aksi="/saprotan/:id" metode="PUT" ukuran="lg"
        label-simpan="Simpan Perubahan">
        @include('pages.saprotan.form', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusSaprotan" judul="Hapus data ini?"
        pesan="Data yang dihapus masih tercatat pada audit log dan dapat dipulihkan admin." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporSaprotan" judul="Impor Data Saprotan"
        entitas="saprotan"
        :kolom-wajib="['satuan_permukiman', 'jenis_saprotan', 'jumlah', 'satuan']" />
@endsection
