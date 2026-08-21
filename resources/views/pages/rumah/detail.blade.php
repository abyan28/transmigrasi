{{--
    Rincian satu rumah beserta riwayat penghuniannya.

    Riwayat penghunian ditampilkan sebagai garis waktu, bukan tabel biasa,
    karena yang perlu terbaca adalah urutan kejadiannya: siapa masuk, kapan
    keluar, dan mengapa. Pergantian penghuni tidak pernah menimpa data lama
    (agents/rules.md bagian 6a poin 9).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $riwayat = DummyData::riwayatPenghunian($data['id_rumah']);

        // Penghuni sekarang dibaca dari riwayat yang belum punya tanggal keluar,
        // bukan dari kolom penghuni, agar keduanya selalu sepadan.
        $penghuniSekarang = collect($riwayat)->firstWhere('tanggal_keluar', null);

        $bolehUbah = true;
    @endphp

    <x-sim.page-header :judul="'Rumah ' . $data['no_rumah']"
        :keterangan="$data['satuan_permukiman'] . ', dibangun tahun ' . $data['tahun_pembangunan'] . '.'"
        :remah="\App\Helpers\RemahHelper::untuk('/rumah', 'Rumah ' . $data['no_rumah'])">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahRumah')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                    </svg>
                    Ubah Data Rumah
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    {{-- Rumah kosong diberi keterangan tegas beserta alasannya --}}
    @if ($data['status_hunian'] === 'Tidak Dihuni')
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-yellow-300 bg-yellow-50 p-4 dark:border-yellow-500/30 dark:bg-yellow-500/10"
            role="status">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <div>
                <p class="text-theme-sm font-semibold text-yellow-800 dark:text-yellow-200">Rumah tidak dihuni</p>
                <p class="mt-1 text-theme-sm text-yellow-700 dark:text-yellow-300">
                    {{ $data['alasan_tidak_dihuni'] ?? 'Alasan belum dicatat.' }}
                </p>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        {{-- Kolom kiri: ringkasan rumah --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                    Rumah {{ $data['no_rumah'] }}
                </h2>

                <div class="mt-3 flex flex-wrap gap-2">
                    <x-sim.status-badge :status="\App\Enums\KondisiRumah::from($data['kondisi'])" />
                    <x-sim.status-badge :status="\App\Enums\StatusHunian::from($data['status_hunian'])" />
                </div>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Penghuni sekarang</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            @if ($penghuniSekarang)
                                <a href="{{ route('transmigran.detail', $penghuniSekarang['transmigran_id']) }}"
                                    class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                    {{ $penghuniSekarang['transmigran'] }}
                                </a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Satuan permukiman</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            <a href="{{ route('dashboard.sp', $data['satuan_permukiman_id']) }}"
                                class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                {{ $data['satuan_permukiman'] }}
                            </a>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Tahun pembangunan</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $data['tahun_pembangunan'] }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Luas bangunan</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($data['luas_bangunan'], 2, ',', '.') }} m<sup>2</sup>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Koordinat</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($data['lintang'], 6, '.', '') }},
                            {{ number_format($data['bujur'], 6, '.', '') }}
                        </dd>
                    </div>
                </dl>

                    <x-sim.tautan-peta class="mt-3" :lintang="$data['lintang']" :bujur="$data['bujur']"
                        :label="'Rumah ' . $data['no_rumah']" />
            </div>
        </aside>

        {{-- Kolom kanan: tab rincian dan riwayat --}}
        <div x-data="hashTabs('rincian')" class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian rumah">
                    @foreach ([
                        'rincian' => 'Rincian',
                        'riwayat' => 'Riwayat Penghunian (' . count($riwayat) . ')',
                        'dokumentasi' => 'Dokumentasi',
                        'log' => 'Catatan Log',
                    ] as $kunci => $label)
                        <button type="button" role="tab" @click="setTab('{{ $kunci }}')"
                            :aria-selected="tab === '{{ $kunci }}'"
                            :class="tab === '{{ $kunci }}'
                                ? 'border-brand-500 text-brand-600 dark:text-brand-400'
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
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Nomor rumah</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">{{ $data['no_rumah'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Kondisi</dt>
                            <dd class="mt-1">
                                <x-sim.status-badge :status="\App\Enums\KondisiRumah::from($data['kondisi'])" />
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Alasan tidak dihuni</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                {{ $data['alasan_tidak_dihuni'] ?? '-' }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Catatan hunian</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                {{ $data['catatan_hunian'] ?? '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Riwayat penghunian sebagai garis waktu --}}
                <div x-show="tab === 'riwayat'" x-cloak role="tabpanel">
                    @if (empty($riwayat))
                        <x-sim.empty-state judul="Belum ada riwayat penghunian"
                            pesan="Penempatan keluarga pada rumah ini akan tercatat di sini." />
                    @else
                        <div class="p-5 sm:p-6">
                            <ol class="relative space-y-6 border-l border-gray-200 pl-6 dark:border-gray-700">
                                @foreach ($riwayat as $jejak)
                                    @php $masihMenghuni = $jejak['tanggal_keluar'] === null; @endphp
                                    <li class="relative">
                                        {{-- Titik penanda; hijau berarti masih menghuni --}}
                                        <span
                                            class="absolute -left-[1.9rem] mt-1 flex h-3 w-3 rounded-full ring-4 ring-white dark:ring-gray-900 {{ $masihMenghuni ? 'bg-green-500' : 'bg-gray-400' }}"
                                            aria-hidden="true"></span>

                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('transmigran.detail', $jejak['transmigran_id']) }}"
                                                class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                                {{ $jejak['transmigran'] }}
                                            </a>
                                            <x-sim.status-badge :teks="$masihMenghuni ? 'Masih menghuni' : 'Sudah keluar'"
                                                :warna="$masihMenghuni ? 'success' : 'gray'" ukuran="sm" />
                                        </div>

                                        <p class="mt-1 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                                            Masuk
                                            {{ \Illuminate\Support\Carbon::parse($jejak['tanggal_masuk'])->translatedFormat('d F Y') }}
                                            @if (! $masihMenghuni)
                                                , keluar
                                                {{ \Illuminate\Support\Carbon::parse($jejak['tanggal_keluar'])->translatedFormat('d F Y') }}
                                            @endif
                                        </p>

                                        @if ($jejak['alasan_keluar'])
                                            <p class="mt-1.5 text-theme-sm text-gray-700 dark:text-gray-300">
                                                Alasan keluar: {{ $jejak['alasan_keluar'] }}
                                            </p>
                                        @endif

                                        @if ($jejak['keterangan'])
                                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                                {{ $jejak['keterangan'] }}
                                            </p>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>

                            <p class="mt-6 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                                Pergantian penghuni dicatat sebagai jejak baru dan tidak menimpa data sebelumnya,
                                sehingga sejarah penghunian rumah ini tetap utuh.
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Dokumentasi --}}
                <div x-show="tab === 'dokumentasi'" x-cloak role="tabpanel">
                    @if (empty($data['foto_rumah']) && empty($data['dokumen_pendukung']))
                        <x-sim.empty-state judul="Belum ada dokumentasi"
                            pesan="Foto kondisi rumah dan dokumen pendukung dapat diunggah lewat tombol Ubah Data Rumah." />
                    @else
                            <div class="space-y-3 p-5 sm:p-6">
                                @if (! empty($data['foto_rumah']))
                                    <div>
                                        <p class="mb-1.5 text-theme-xs text-gray-500 dark:text-gray-400">Foto Rumah</p>
                                        <x-sim.tautan-dokumen modul="rumah" :id="$data['id_rumah']"
                                            :berkas="$data['foto_rumah']" />
                                    </div>
                                @endif

                                @if (! empty($data['dokumen_pendukung']))
                                    <div>
                                        <p class="mb-1.5 text-theme-xs text-gray-500 dark:text-gray-400">Dokumen Pendukung</p>
                                        <x-sim.tautan-dokumen modul="rumah" :id="$data['id_rumah']"
                                            :berkas="$data['dokumen_pendukung']" />
                                    </div>
                                @endif

                                <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                                    Berkas tersimpan pada penyimpanan privat dan hanya dapat dibuka petugas berwenang.
                                </p>
                            </div>
                    @endif
                </div>

                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="rumah" :record-id="$data['id_rumah']" />
                </div>
            </div>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahRumah" judul="Ubah Data Rumah"
            keterangan="Pergantian penghuni akan tercatat sebagai riwayat baru."
            :aksi="route('rumah.perbarui', $data['id_rumah'])" metode="PUT" ukuran="xl"
            label-simpan="Simpan Perubahan">
            @include('pages.rumah.form', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif
@endsection
