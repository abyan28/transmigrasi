@extends('layouts.app')

{{--
    Indeks data master referensi: empat belas daftar sebagai kartu.

    SEMULA BERUPA TAB, dan itu berhenti bekerja karena jumlahnya. Dengan
    sembilan daftar keempatnya masih muat dalam satu baris; setelah menjadi
    empat belas, bar tab mencapai 2309px pada ruang 705px, sehingga hanya
    empat tab yang terlihat dan sepuluh sisanya tersembunyi di balik gulir
    mendatar yang paling sering tidak disadari orang.

    Kartu dipilih, bukan empat belas butir menu di bilah sisi: menu Pengaturan
    Sistem sudah berisi enam butir, dan menambah empat belas lagi hanya
    memindahkan baris panjang yang sama dari bar tab ke bilah sisi.

    Dikelompokkan per MODUL YANG MEMAKAINYA, sebab petugas mencari daftar
    lewat tempat ia melihat dropdownnya, bukan lewat nama daftarnya.
--}}

@section('content')
    @php
        use App\Enums\KelompokReferensi;
        use App\Support\DummyData;

        $semua = DummyData::referensi();

        // Dihitung sekali, dipakai seluruh kartu.
        $jumlah = [];
        $nonaktif = [];

        foreach ($semua as $b) {
            $jumlah[$b['jenis']] = ($jumlah[$b['jenis']] ?? 0) + 1;

            if (! $b['is_aktif']) {
                $nonaktif[$b['jenis']] = ($nonaktif[$b['jenis']] ?? 0) + 1;
            }
        }
    @endphp

    <x-sim.page-header judul="Data Master Referensi"
        keterangan="Pilihan pada form yang dapat ditambah dan disunting tanpa mengubah kode."
        :remah="\App\Helpers\RemahHelper::untuk('/master/referensi')" />

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Cara Kerja Daftar Ini</h2>
        <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
            Setiap daftar di bawah muncul sebagai pilihan pada form. Menambah satu nilai membuatnya langsung
            tersedia, tanpa perlu mengubah kode. Pilih satu daftar untuk melihat dan menyunting isinya.
        </p>
        <p class="mt-3 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
            <span class="font-medium text-gray-800 dark:text-white/90">Nilai dinonaktifkan, bukan dihapus.</span>
            Nilai nonaktif berhenti ditawarkan pada data baru, tetapi tetap terbaca pada data lama yang sudah
            memakainya. Menghapusnya akan membuat data lama menunjuk pilihan yang tidak ada, dan rekap
            kehilangan baris itu tanpa pemberitahuan apa pun.
        </p>
    </div>
    <div class="space-y-6">
        @foreach (KelompokReferensi::cases() as $kelompok)
            <section>
                <div class="mb-3">
                    <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                        {{ $kelompok->label() }}
                    </h2>
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ $kelompok->keterangan() }}
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($kelompok->jenis() as $j)
                        <a href="{{ route('referensi.jenis', ['jenis' => $j->value]) }}"
                            class="group flex flex-col rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-brand-300 hover:shadow-theme-sm focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/50">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-theme-sm font-medium text-gray-800 group-hover:text-brand-600 dark:text-white/90 dark:group-hover:text-brand-400">
                                    {{ $j->label() }}
                                </h3>
                                <span class="shrink-0 rounded-full bg-gray-100 px-2.5 py-0.5 text-theme-xs font-medium tabular-nums text-gray-700 dark:bg-white/[0.06] dark:text-gray-300">
                                    {{ $jumlah[$j->value] ?? 0 }}
                                </span>
                            </div>

                            {{--
                                Penanda perilaku khusus. Ditampilkan di indeks
                                agar petugas tahu daftar mana yang berdampak
                                lebih jauh dari sekadar teks pada dropdown,
                                sebelum ia membukanya.
                            --}}
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @if ($j->berskor())
                                    <span class="rounded-md bg-warning-50 px-2 py-0.5 text-theme-xs font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-300">
                                        Menentukan skor SP
                                    </span>
                                @endif

                                @if ($j->berjenjang())
                                    <span class="rounded-md bg-warning-50 px-2 py-0.5 text-theme-xs font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-300">
                                        Urutan bermakna
                                    </span>
                                @endif

                                @if ($j->dirujukParameter())
                                    <span class="rounded-md bg-blue-light-50 px-2 py-0.5 text-theme-xs font-medium text-blue-light-700 dark:bg-blue-light-500/10 dark:text-blue-light-300">
                                        Dirujuk penilaian SP
                                    </span>
                                @endif

                                @if ($j->berbidang())
                                    <span class="rounded-md bg-blue-light-50 px-2 py-0.5 text-theme-xs font-medium text-blue-light-700 dark:bg-blue-light-500/10 dark:text-blue-light-300">
                                        Menentukan bidang
                                    </span>
                                @endif

                                @if (($nonaktif[$j->value] ?? 0) > 0)
                                    <span class="rounded-md bg-gray-100 px-2 py-0.5 text-theme-xs font-medium text-gray-600 dark:bg-white/[0.06] dark:text-gray-400">
                                        {{ $nonaktif[$j->value] }} nonaktif
                                    </span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endsection