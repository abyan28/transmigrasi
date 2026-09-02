{{--
    Rincian satu bidang lahan.

    Bidang TIDAK memegang dokumennya sendiri (Putaran 12, 2026-09-02). SHM
    meliputi seluruh lahan satu keluarga sehingga melekat pada transmigran;
    HPL adalah alas hak kawasan milik instansi sehingga melekat pada kawasan
    (rules.md 7.4a). Tab Legalitas menampilkan keduanya sebagai bacaan beserta
    tautan ke tempat penyuntingannya.

    Pola tanam, peralatan, dan kendala DICABUT pada tanggal yang sama atas
    keputusan pemilik proyek, beserta tab Pengelolaan yang hanya menampung
    ketiganya.
--}}
@extends('layouts.app')

@section('content')
    @php
        // `$pemilik`, `$shm`, dan `$hpl` datang dari
        // rute `lahan.detail`.

        // Lahan usaha terbagi beberapa tahap, sehingga tidak boleh dicocokkan
        // dengan satu nilai teks. Pemeriksaannya dipusatkan pada enum.
        $lahanUsaha = \App\Enums\PeruntukanLahan::from($data['peruntukan_lahan'])->lahanUsaha();

        $bolehUbah = true;
    @endphp

    <x-sim.page-header :judul="'Lahan ' . $data['kode_lahan']"
        :keterangan="$data['peruntukan_lahan'] . ' seluas ' . number_format($data['luas'], 2, ',', '.') . ' hektare di ' . $data['satuan_permukiman'] . '.'"
        :remah="\App\Helpers\RemahHelper::untuk('/lahan', $data['kode_lahan'])">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahLahan')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                    </svg>
                    Ubah Data Lahan
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
        {{-- Kolom kiri: ringkasan lahan --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                    {{ $data['kode_lahan'] }}
                </h2>
                <p class="mt-1 text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                    {{ number_format($data['luas'], 2, ',', '.') }}
                    <span class="text-theme-sm font-normal text-gray-500 dark:text-gray-400">ha</span>
                </p>

                <div class="mt-3 flex flex-wrap gap-2">
                    <x-sim.status-badge :teks="$data['peruntukan_lahan']"
                        :warna="$lahanUsaha ? 'teal' : 'gray'" />
                </div>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Pemilik</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            @if ($pemilik)
                                <a href="{{ route('transmigran.detail', $pemilik['id_transmigran']) }}"
                                    class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                    {{ $data['pemilik'] }}
                                </a>
                            @else
                                {{ $data['pemilik'] }}
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Satuan permukiman</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            <a href="{{ route('sp.detail', $data['satuan_permukiman_id']) }}"
                                class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                {{ $data['satuan_permukiman'] }}
                            </a>
                        </dd>
                    </div>
                    {{--
                        Komposisi luas hanya berlaku bagi lahan usaha. Nol
                        ditampilkan apa adanya, bukan disembunyikan: bidang
                        yang seluruhnya kering memang bernilai 0 ha basah, dan
                        menyembunyikannya membuat pembaca menduga datanya belum
                        terisi.
                    --}}
                    @if ($lahanUsaha)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Lahan kering</dt>
                            <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($data['luas_kering'] ?? 0, 2, ',', '.') }} ha
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Lahan basah</dt>
                            <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($data['luas_basah'] ?? 0, 2, ',', '.') }} ha
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </aside>

        {{-- Kolom kanan: tab rincian --}}
        <div x-data="hashTabs('rincian')" class="min-w-0">
            <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian lahan">
                    @php
                        $tab = ['rincian' => 'Rincian'];
                        $tab['dokumen'] = 'Legalitas';
                        $tab['log'] = 'Catatan Log';
                    @endphp
                    @foreach ($tab as $kunci => $label)
                        <button type="button" role="tab" @click="setTab('{{ $kunci }}')"
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                            class="shrink-0 border-b-2 px-4 py-2.5 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Rincian --}}
                <div x-show="tab === 'rincian'" role="tabpanel" class="p-5 sm:p-6">
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Luas lahan</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($data['luas'], 2, ',', '.') }} ha
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Koordinat</dt>
                        <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            @if (! empty($data['lintang']))
                                {{ number_format($data['lintang'], 6, '.', '') }},
                                {{ number_format($data['bujur'], 6, '.', '') }}
                                <x-sim.tautan-peta class="mt-1.5" :lintang="$data['lintang']"
                                    :bujur="$data['bujur']" :label="$data['kode_lahan']" />
                            @else
                                -
                            @endif
                        </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Tujuan pemanfaatan</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                {{ $data['tujuan_pemanfaatan'] ?? '-' }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Keterangan</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                {{ $data['keterangan'] ?? '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Legalitas: SHM milik keluarga, HPL milik kawasan --}}
                <div x-show="tab === 'dokumen'" x-cloak role="tabpanel" class="p-5 sm:p-6">
                    {{--
                        Bidang lahan TIDAK memegang dokumennya sendiri (Putaran 12).
                        SHM meliputi seluruh lahan satu keluarga, pekarangan maupun
                        usaha, sehingga ia melekat pada transmigran dan diunggah
                        sekali. HPL adalah alas hak KAWASAN milik instansi, bukan hak
                        seorang transmigran (rules.md 7.4a), sehingga ia melekat pada
                        kawasan dan cukup satu untuk seluruh bidang di dalamnya.

                        Keduanya ditampilkan di sini sebagai bacaan beserta tautan ke
                        tempat penyuntingannya. Menyediakan unggahan di halaman ini
                        akan melahirkan salinan sertifikat yang sama pada tiap bidang,
                        lalu satu digit salah hanya terbetulkan di sebagian.
                    --}}
                    <div class="space-y-4">
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">Sertifikat keluarga (SHM)</p>
                                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                        Berlaku untuk seluruh bidang milik {{ $pemilik['nama_kepala_keluarga'] ?? 'keluarga ini' }},
                                        pekarangan maupun lahan usaha.
                                    </p>
                                    <p class="mt-2 text-theme-xs">
                                        Status sertifikat:
                                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $pemilik['status_sertifikat'] ?? 'Belum Didata' }}</span>
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    @if ($shm)
                                        <x-sim.tautan-dokumen modul="transmigran" :id="$data['transmigran_id']"
                                            :berkas="$shm['nama_file']" />
                                    @else
                                        <span class="text-theme-xs text-gray-500 dark:text-gray-400">Belum diunggah</span>
                                    @endif
                                    <a href="{{ route('transmigran.detail', $data['transmigran_id']) }}"
                                        class="mt-1.5 block text-theme-xs font-medium text-brand-600 hover:underline dark:text-brand-400">
                                        Buka data keluarga
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">Alas hak kawasan (HPL)</p>
                                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                        Hak Pengelolaan atas tanah kawasan, dipegang instansi. Satu HPL menaungi
                                        seluruh bidang di dalam kawasan, sehingga tidak diunggah per bidang.
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    @if ($hpl)
                                        <x-sim.tautan-dokumen modul="kawasan" :id="1" :berkas="$hpl['nama_file']" />
                                    @else
                                        <span class="text-theme-xs text-gray-500 dark:text-gray-400">Belum diunggah</span>
                                    @endif
                                    <a href="{{ route('kawasan') }}"
                                        class="mt-1.5 block text-theme-xs font-medium text-brand-600 hover:underline dark:text-brand-400">
                                        Buka data kawasan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="lahan" :record-id="$data['id_lahan']" />
                </div>
            </div>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahLahan" judul="Ubah Data Lahan"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('lahan.perbarui', $data['id_lahan'])" metode="PUT" ukuran="xl"
            :langkah="['Identitas & Pemilik', 'Penggunaan & Lokasi', 'Legalitas & Berkas']"
            label-simpan="Simpan Perubahan">
            @include('pages.lahan.form', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>

    @endif
@endsection
