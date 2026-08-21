@extends('layouts.app')

{{--
    Satu daftar referensi, satu halaman.

    Menggantikan tab pada halaman indeks. Ketika daftarnya masih sembilan,
    keempatnya muat sebagai tab dalam satu baris; setelah menjadi empat belas,
    bar tab mencapai 2309px pada ruang 705px sehingga hanya empat tab yang
    terlihat dan sepuluh sisanya tersembunyi di balik gulir mendatar.

    Keterangan perilaku khusus ditaruh DI SINI, bukan di indeks, sebab di
    halaman inilah nilainya disunting. Peringatan tentang akibat mengubah skor
    tidak berguna bagi orang yang sedang memilih daftar mana yang mau dibuka.
--}}

@section('content')
    @php
        use App\Support\DummyData;

        $baris = DummyData::referensi($jenis);
        $jumlahNonaktif = count(array_filter($baris, fn ($b) => ! $b['is_aktif']));

        $bolehUbah = true;
    @endphp

    <x-sim.page-header :judul="$jenis->label()"
        keterangan="Pilihan pada form yang dapat ditambah dan disunting tanpa mengubah kode."
        :remah="\App\Helpers\RemahHelper::untuk('/master/referensi', $jenis->label())">
        <x-slot:aksi>
            <a href="{{ route('master.referensi') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Semua Daftar
            </a>

            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formTambahReferensi')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Pilihan
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    {{-- Aturan pokoknya, sebab penonaktifan tidak lazim --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-sm text-gray-600 dark:text-gray-400">
            <span class="font-medium text-gray-800 dark:text-white/90">Nilai dinonaktifkan, bukan dihapus.</span>
            Nilai nonaktif berhenti ditawarkan pada data baru, tetapi tetap terbaca pada data lama yang sudah
            memakainya. Menghapusnya akan membuat data lama menunjuk pilihan yang tidak ada, dan rekap
            kehilangan baris itu tanpa pemberitahuan apa pun.
        </p>

        @if ($jumlahNonaktif > 0)
            <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
                Daftar ini memuat {{ $jumlahNonaktif }} nilai nonaktif. Nilai tersebut tetap tampil di sini
                agar keadaannya terlihat, dan tetap dapat dicari pada halaman yang memakainya.
            </p>
        @endif
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <x-sim.tabel-ringkas
            :kolom="match (true) {
                $jenis->berskor() => ['Nilai', 'Skor', 'Urutan', 'Status', 'Aksi'],
                $jenis->berbidang() => ['Nilai', 'Bidang Bawaan', 'Urutan', 'Status', 'Aksi'],
                default => ['Nilai', 'Urutan', 'Status', 'Aksi'],
            }"
            :kolom-kanan="['Aksi']">
            @foreach ($baris as $b)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                    <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $b['nilai'] }}</td>

                    @if ($jenis->berskor())
                        {{--
                            Skor hanya untuk jenis yang benar-benar dipakai
                            menghitung kondisi SP. Menampilkan kolom ini pada
                            jenis lain akan menyiratkan perhitungan yang tidak ada.
                        --}}
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                            {{ $b['nilai_skor'] !== null ? number_format($b['nilai_skor'], 2, ',', '.') : '-' }}
                        </td>
                    @endif

                    @if ($jenis->berbidang())
                        {{--
                            Kosong DIBERI LABEL, bukan tanda hubung. Bidang
                            kosong menyatakan kategori yang perlu ditimbang
                            petugas, dan tanda hubung membuatnya tampak seperti
                            data yang lupa diisi.
                        --}}
                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                            @if ($b['bidang_id'] === null)
                                <span class="text-gray-400 dark:text-gray-500">Ditetapkan petugas</span>
                            @else
                                {{ DummyData::referensiNilai($b['bidang_id']) }}
                            @endif
                        </td>
                    @endif

                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                        {{ $b['urutan'] }}
                    </td>
                    <td class="px-5 py-3">
                        <x-sim.status-badge :teks="$b['is_aktif'] ? 'Aktif' : 'Nonaktif'"
                            :warna="$b['is_aktif'] ? 'success' : 'gray'" ukuran="sm" />
                    </td>
                    <td class="px-5 py-3 text-right">
                        {{--
                            Tanpa tombol hapus. Nilai yang tidak lagi dipakai
                            dinonaktifkan lewat tombol ubah, sehingga data lama
                            tetap terbaca.
                        --}}
                        <x-sim.aksi-baris modal-ubah="formUbahReferensi"
                            :data-baris="$b + ['id' => $b['id_referensi']]"
                            :label="$b['nilai']" />
                    </td>
                </tr>
            @endforeach
        </x-sim.tabel-ringkas>

        @if ($jenis->berjenjang())
            <p class="border-t border-gray-200 p-5 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                Urutan pada daftar ini <span class="font-medium">bermakna</span>: daftar pengaduan
                menyortir memakainya, sehingga menukar urutan berarti menukar antrean petugas.
            </p>
        @elseif ($jenis->berskor())
            <p class="border-t border-gray-200 p-5 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                Skor pada daftar ini dipakai menghitung kondisi satuan permukiman. Mengubahnya
                mempengaruhi penilaian <span class="font-medium">berikutnya</span>; penilaian yang sudah
                tersimpan tidak berubah, sebab masing-masing menyalin skor yang berlaku saat itu.
            </p>
        @elseif ($jenis->berbidang())
            <p class="border-t border-gray-200 p-5 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                Bidang bawaan hanya mengisi nilai <span class="font-medium">awal</span> saat kategori
                dipilih; petugas selalu dapat menimpanya. Kategori tanpa bidang bawaan memang perlu
                ditimbang dari isi laporan, sebab dapat jatuh ke dua dinas sekaligus.
            </p>
        @elseif ($jenis->dirujukParameter())
            <p class="border-t border-gray-200 p-5 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                Nilai pada daftar ini <span class="font-medium">dirujuk parameter penilaian</span> satuan
                permukiman. Mengganti ejaannya aman, sebab rujukannya memakai id; menonaktifkannya juga
                aman, sebab parameter yang sudah menunjuknya tetap membacanya.
            </p>
        @endif
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formTambahReferensi" judul="Tambah Pilihan"
            :keterangan="'Nilai baru langsung tersedia pada form yang memakai ' . $jenis->label() . '.'"
            :aksi="route('referensi.simpan')" ukuran="lg" label-simpan="Simpan Pilihan">
            @include('pages.master.form-referensi', ['awalan' => 'tambah', 'jenis' => $jenis])
        </x-sim.modal-form>

        <x-sim.modal-form nama="formUbahReferensi" judul="Ubah Pilihan"
            keterangan="Nilai yang tidak lagi dipakai dinonaktifkan, bukan dihapus."
            pola-aksi="/master/referensi/:id" metode="PUT" ukuran="lg" label-simpan="Simpan Perubahan">
            @include('pages.master.form-referensi', ['awalan' => 'ubahBaris', 'jenis' => $jenis])
        </x-sim.modal-form>
    @endif
@endsection