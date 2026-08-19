{{--
    Musim tanam.

    Nama dan tahun disimpan terpisah, bukan sebagai teks bebas, karena grafik
    volume panen per tahun mustahil dihitung dari teks
    (agents/erd.md bagian 8.2 nomor 22).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::musimTanam();
        $cari = trim((string) request('cari', ''));

        $baris = array_values(array_filter($semua, fn ($m) => $cari === ''
            || str_contains(mb_strtolower($m['label']), mb_strtolower($cari))));
    @endphp

    <x-sim.halaman-daftar judul="Musim Tanam"
        keterangan="Periode tanam yang menjadi dasar pengelompokan hasil panen."
        :remah="[['label' => 'Pertanian'], ['label' => 'Musim Tanam']]"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('musim-tanam')"
        placeholder-cari="Cari musim tanam" judul-kosong="Belum ada musim tanam"
        pesan-kosong="Periode musim tanam akan tampil di sini setelah ditetapkan.">

        <x-slot:aksi>
            {{--
                Impor massal diletakkan mendahului tombol tambah namun bergaya
                sekunder, sebab menambah satu data tetap tindakan yang paling
                sering dipakai (PRD 8.1).

                Ditambahkan 2026-08-19. Modul ini sempat dikecualikan dari impor
                dengan alasan "jumlah barisnya sedikit", padahal musim tanam
                bertambah dua kali setahun tanpa henti sehingga jumlahnya justru
                paling terpengaruh waktu. Alasan itu menghitung baris data
                contoh, dan itu penalaran yang dilarang rules.md 19a.
            --}}
            <button type="button" @click="$dispatch('buka-modal', 'imporMusimTanam')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Impor Data
            </button>

            <button type="button" @click="$dispatch('buka-modal', 'formTambahMusim')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Musim Tanam
            </button>
        </x-slot:aksi>

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Musim Tanam</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tahun</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Mulai</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Selesai</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Penanaman</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Keterangan</th>
            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
        </x-slot:kepala>

        @foreach ($baris as $m)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $m['label'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">{{ $m['tahun'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    {{ \Illuminate\Support\Carbon::parse($m['tanggal_mulai'])->translatedFormat('d M Y') }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    {{ \Illuminate\Support\Carbon::parse($m['tanggal_selesai'])->translatedFormat('d M Y') }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $m['jumlah_tanam'] }} catatan</td>
                <td class="px-5 py-3 text-theme-xs text-gray-500 dark:text-gray-400">{{ $m['keterangan'] ?? '-' }}</td>
                <td class="px-5 py-3">
                    <x-sim.aksi-baris modal-ubah="formUbahMusimBaris"
                        :data-baris="$m + ['id' => $m['id_musim_tanam']]"
                        :hapus-url="'/musim-tanam/' . $m['id_musim_tanam']"
                        konfirmasi-hapus="hapusMusim"
                        :label="$m['label']" />
                </td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $m)
                <div class="p-4">
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $m['label'] }}</p>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ \Illuminate\Support\Carbon::parse($m['tanggal_mulai'])->translatedFormat('d M Y') }}
                        sampai
                        {{ \Illuminate\Support\Carbon::parse($m['tanggal_selesai'])->translatedFormat('d M Y') }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>

    <x-sim.modal-form nama="formTambahMusim" judul="Tambah Musim Tanam"
        keterangan="Nama dan tahun disimpan terpisah agar rekap per tahun dapat dihitung."
        :aksi="route('musim-tanam.simpan')" ukuran="md" label-simpan="Simpan Data">
        @include('pages.komoditas.form-musim-tanam', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahMusimBaris" judul="Ubah Musim Tanam"
        keterangan="Perubahan tercatat pada audit log."
        pola-aksi="/musim-tanam/:id" metode="PUT" ukuran="md" label-simpan="Simpan Perubahan">
        @include('pages.komoditas.form-musim-tanam', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusMusim" judul="Hapus musim tanam ini?"
        pesan="Riwayat tanam yang memakai musim ini akan kehilangan pengelompokannya." label-setuju="Hapus" />

    {{--
        Kolom wajib mengikuti kamus data 9.1: `nama` dan `tahun` tidak boleh
        kosong, sedangkan tanggal mulai dan selesai nullable tetapi diminta
        pada template sebab tanpa keduanya musim tidak dapat dipakai memilah
        panen menurut periode.
    --}}
    <x-sim.modal-impor nama="imporMusimTanam" judul="Impor Musim Tanam"
        entitas="musim-tanam"
        :kolom-wajib="['nama', 'tahun', 'tanggal_mulai', 'tanggal_selesai']" />
@endsection
