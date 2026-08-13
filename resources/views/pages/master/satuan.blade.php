{{--
    Data master satuan beserta faktor konversi ke ton.

    Inilah tabel yang membuat rekap lintas komoditas menjadi sepadan: volume
    disimpan apa adanya sesuai satuan baku komoditas, lalu dikonversi ke ton
    hanya saat rekap (agents/rules.md bagian 8a poin 4 dan 5).

    Satuan lokal seperti karung dan ikat sengaja TIDAK dimasukkan ke sini
    karena beratnya tidak baku; keduanya dicatat pada kolom keterangan di
    hasil panen (poin 6).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $satuan = DummyData::satuan();
    @endphp

    <x-sim.page-header judul="Data Master Satuan"
        keterangan="Satuan panen beserta faktor konversinya ke ton."
        :remah="[['label' => 'Pengaturan'], ['label' => 'Data Master Satuan']]">
        <x-slot:aksi>
            {{--
                Impor massal diletakkan mendahului tombol tambah namun bergaya
                sekunder, sebab menambah satu data tetap tindakan yang paling
                sering dipakai (PRD 8.1).
            --}}
            <button type="button" @click="$dispatch('buka-modal', 'imporSatuan')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Impor Data
            </button>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahSatuan')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Satuan
            </button>
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Mengapa Faktor Konversi Diperlukan</h2>
        <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
            Tiap komoditas dicatat memakai satuan bakunya sendiri: jagung dalam ton, cabai dalam
            kilogram. Menjumlahkan keduanya begitu saja menghasilkan angka yang keliru. Karena itu
            setiap rekap dan dashboard mengalikan volume dengan faktor di bawah ini lebih dulu.
        </p>
        <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
            Contoh: panen cabai 320,500 kilogram dihitung sebagai 0,321 ton saat direkap,
            bukan 320,500.
        </p>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                            Nama Satuan</th>
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                            Simbol</th>
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                            Faktor ke Ton</th>
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                            Dipakai Komoditas</th>
                        <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                            Contoh Perhitungan</th>
                            <th scope="col" class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($satuan as $s)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                {{ $s['nama'] }}
                            </td>
                            <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $s['simbol'] }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ rtrim(rtrim(number_format($s['faktor_ke_ton'], 3, ',', '.'), '0'), ',') }}
                            </td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                {{ $s['dipakai_komoditas'] }} komoditas
                            </td>
                            <td class="px-5 py-3 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                                100 {{ $s['simbol'] }} =
                                {{ rtrim(rtrim(number_format(100 * $s['faktor_ke_ton'], 3, ',', '.'), '0'), ',') }} ton
                            </td>
                            <td class="px-5 py-3">
                                <x-sim.aksi-baris modal-ubah="formUbahSatuanBaris"
                                    :data-baris="$s + ['id' => $s['id_satuan']]"
                                    :hapus-url="'/master/satuan/' . $s['id_satuan']"
                                    konfirmasi-hapus="hapusSatuan" :label="$s['nama']" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
        Satuan lokal seperti karung dan ikat tidak dimasukkan ke daftar ini karena beratnya
        berbeda-beda antar-tempat. Keduanya dicatat sebagai keterangan pada catatan panen,
        sehingga rekap tetap dapat dijumlahkan.
    </p>

    <x-sim.modal-form nama="formTambahSatuan" judul="Tambah Data Master Satuan"
        keterangan="Faktor konversi menentukan kesepadanan seluruh rekap panen."
        :aksi="route('satuan.simpan')" ukuran="md" label-simpan="Simpan Data">
        @include('pages.master.form-satuan', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahSatuanBaris" judul="Ubah Data Master Satuan"
        keterangan="Perubahan faktor konversi tidak mengubah panen yang sudah tersimpan."
        pola-aksi="/master/satuan/:id" metode="PUT" ukuran="md" label-simpan="Simpan Perubahan">
        @include('pages.master.form-satuan', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusSatuan" judul="Hapus satuan ini?"
        pesan="Satuan yang masih dipakai komoditas tidak dapat dihapus." label-setuju="Hapus" />

    {{-- Impor massal, lihat komponennya untuk alur tiga langkah --}}
    <x-sim.modal-impor nama="imporSatuan" judul="Impor Data Master Satuan"
        entitas="satuan"
        :kolom-wajib="['nama', 'simbol', 'faktor_ke_ton']" />
@endsection
