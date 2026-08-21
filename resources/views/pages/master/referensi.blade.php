@extends('layouts.app')

@section('content')
    @php
        use App\Enums\JenisReferensi;
        use App\Support\DummyData;

        $semua = DummyData::referensi();

        // Dikelompokkan per jenis agar tiap tab hanya membaca bagiannya.
        $perJenis = [];
        foreach (JenisReferensi::cases() as $j) {
            $perJenis[$j->value] = array_values(array_filter($semua, fn ($b) => $b['jenis'] === $j->value));
        }

        $bolehUbah = true;
    @endphp

    <x-sim.page-header judul="Data Master Referensi"
        keterangan="Pilihan pada form yang dapat ditambah dan disunting tanpa mengubah kode."
        :remah="\App\Helpers\RemahHelper::untuk('/master/referensi')">
        <x-slot:aksi>
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

    {{-- Penjelasan aturan pokoknya, sebab penonaktifan tidak lazim --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Cara Kerja Daftar Ini</h2>
        <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
            Setiap daftar di bawah muncul sebagai pilihan pada form. Menambah satu nilai membuatnya langsung
            tersedia, tanpa perlu mengubah kode.
        </p>
        <p class="mt-3 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
            <span class="font-medium text-gray-800 dark:text-white/90">Nilai dinonaktifkan, bukan dihapus.</span>
            Nilai nonaktif berhenti ditawarkan pada data baru, tetapi tetap terbaca pada data lama yang sudah
            memakainya. Menghapusnya akan membuat data lama menunjuk pilihan yang tidak ada, dan rekap
            kehilangan baris itu tanpa pemberitahuan apa pun.
        </p>
    </div>

    <div x-data="hashTabs('{{ JenisReferensi::SumberDana->value }}')"
        class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
            role="tablist" aria-label="Jenis referensi">
            @foreach (JenisReferensi::cases() as $j)
                <button type="button" role="tab" @click="setTab('{{ $j->value }}')"
                    :aria-selected="tab === '{{ $j->value }}'"
                    :class="tab === '{{ $j->value }}'
                        ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="shrink-0 border-b-2 px-4 py-2.5 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    {{ $j->label() }} ({{ count($perJenis[$j->value]) }})
                </button>
            @endforeach
        </div>

        @foreach (JenisReferensi::cases() as $indeks => $j)
            {{--
                Panel pertama sengaja tanpa `x-cloak`: ia panel bawaan,
                sehingga menyembunyikannya sampai Alpine memulai justru
                membuat halaman kosong sesaat.
            --}}
            <div x-show="tab === '{{ $j->value }}'" @if ($indeks > 0) x-cloak @endif role="tabpanel">
                <x-sim.tabel-ringkas
                    :kolom="match (true) {
                        $j->berskor() => ['Nilai', 'Skor', 'Urutan', 'Status', 'Aksi'],
                        $j->berbidang() => ['Nilai', 'Bidang Bawaan', 'Urutan', 'Status', 'Aksi'],
                        default => ['Nilai', 'Urutan', 'Status', 'Aksi'],
                    }"
                    :kolom-kanan="['Aksi']">
                    @foreach ($perJenis[$j->value] as $b)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">{{ $b['nilai'] }}</td>

                            @if ($j->berskor())
                                {{--
                                    Skor hanya untuk jenis yang benar-benar
                                    dipakai menghitung kondisi SP. Menampilkan
                                    kolom ini pada jenis lain akan menyiratkan
                                    perhitungan yang tidak ada.
                                --}}
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $b['nilai_skor'] !== null ? number_format($b['nilai_skor'], 2, ',', '.') : '-' }}
                                </td>
                            @endif

                            @if ($j->berbidang())
                                {{--
                                    Kosong DIBERI LABEL, bukan tanda hubung.
                                    Bidang kosong menyatakan kategori yang perlu
                                    ditimbang petugas, dan tanda hubung membuatnya
                                    tampak seperti data yang lupa diisi.
                                --}}
                                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                    @if ($b['bidang_id'] === null)
                                        <span class="text-gray-400 dark:text-gray-500">Ditetapkan petugas</span>
                                    @else
                                        {{ \App\Support\DummyData::referensiNilai($b['bidang_id']) }}
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
                                    Tanpa tombol hapus. Nilai yang tidak lagi
                                    dipakai dinonaktifkan lewat tombol ubah,
                                    sehingga data lama tetap terbaca.
                                --}}
                                <x-sim.aksi-baris modal-ubah="formUbahReferensi"
                                    :data-baris="$b + ['id' => $b['id_referensi']]"
                                    :label="$b['nilai']" />
                            </td>
                        </tr>
                    @endforeach
                </x-sim.tabel-ringkas>

                @if ($j->berjenjang())
                    <p class="border-t border-gray-200 p-5 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                        Urutan pada daftar ini <span class="font-medium">bermakna</span>: daftar pengaduan
                        menyortir memakainya, sehingga menukar urutan berarti menukar antrean petugas.
                    </p>
                @elseif ($j->berskor())
                    <p class="border-t border-gray-200 p-5 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                        Skor pada daftar ini dipakai menghitung kondisi satuan permukiman. Mengubahnya
                        mempengaruhi penilaian <span class="font-medium">berikutnya</span>; penilaian yang sudah
                        tersimpan tidak berubah, sebab masing-masing menyalin skor yang berlaku saat itu.
                    </p>
                @elseif ($j->berbidang())
                    <p class="border-t border-gray-200 p-5 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                        Bidang bawaan hanya mengisi nilai <span class="font-medium">awal</span> saat kategori
                        dipilih; petugas selalu dapat menimpanya. Kategori tanpa bidang bawaan memang perlu
                        ditimbang dari isi laporan, sebab dapat jatuh ke dua dinas sekaligus.
                    </p>
                @elseif ($j->dirujukParameter())
                    <p class="border-t border-gray-200 p-5 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                        Nilai pada daftar ini <span class="font-medium">dirujuk parameter penilaian</span> satuan
                        permukiman. Mengganti ejaannya aman, sebab rujukannya memakai id; menonaktifkannya juga
                        aman, sebab parameter yang sudah menunjuknya tetap membacanya.
                    </p>
                @endif
            </div>
        @endforeach
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formTambahReferensi" judul="Tambah Pilihan"
            keterangan="Pilihan langsung tersedia pada form yang memakainya."
            :aksi="route('referensi.simpan')" ukuran="lg" label-simpan="Simpan Pilihan">
            @include('pages.master.form-referensi', ['awalan' => 'tambah'])
        </x-sim.modal-form>

        <x-sim.modal-form nama="formUbahReferensi" judul="Ubah Pilihan"
            keterangan="Nilai yang tidak lagi dipakai dinonaktifkan, bukan dihapus."
            pola-aksi="/master/referensi/:id" metode="PUT" ukuran="lg" label-simpan="Simpan Perubahan">
            @include('pages.master.form-referensi', ['awalan' => 'ubahBaris'])
        </x-sim.modal-form>
    @endif
@endsection
