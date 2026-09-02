{{--
    Rincian satu baris inventaris SP, yaitu barang bergerak milik satuan
    permukiman (agents/rules.md bagian 4b).

    Halaman ini dibuat 2026-08-19 bersama tautan objek pengaduan. Sebelumnya
    inventaris hanya memiliki halaman daftar, sehingga keluhan warga atas
    sebuah barang tidak punya tempat untuk ditampilkan kembali.

    SATU BARIS MEWAKILI BANYAK UNIT. Sejak Putaran 7 `rincian_kondisi` mencatat
    berapa unit berkondisi apa (histogram), sehingga "sebagian retak" jadi
    angka. Tetap per jenis, bukan per unit: meja ke-3 masih tak dapat
    dibedakan dari meja ke-7. Kolom `kondisi` di atas tetap penilaian umum
    petugas untuk lencana dan cacah "perlu perbaikan".
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Enums\Kondisi;

        $bolehUbah = true;

        $kondisi = Kondisi::dari($data['kondisi']);
    @endphp

    <x-sim.page-header :judul="$data['nama_barang']"
        :keterangan="'Inventaris ' . $data['satuan_permukiman'] . ', diperoleh tahun ' . $data['tahun_perolehan'] . '.'"
        :remah="\App\Helpers\RemahHelper::untuk('/sp/inventaris', $data['nama_barang'])">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button"
                    @click="$dispatch('buka-modal', 'formUbahInventaris')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                    Ubah Data Inventaris
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Kondisi</h2>

                <div class="mt-3 flex flex-wrap gap-2">
                    @if ($kondisi)
                        <x-sim.status-badge :status="$kondisi" />
                    @endif
                    <x-sim.status-badge :teks="$data['status_penyerahan']"
                        :warna="$data['status_penyerahan'] === 'Sudah Diserahkan' ? 'success' : 'warning'" />
                </div>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Jumlah</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($data['jumlah'], 0, ',', '.') }} {{ $data['satuan_barang'] }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Tahun perolehan</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $data['tahun_perolehan'] }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Sumber dana</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            {{ $data['sumber_dana'] ?? '-' }}
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
                </dl>
            </div>
        </aside>

        {{-- Kolom kanan: tab rincian --}}
        <div x-data="hashTabs('rincian')" class="min-w-0">
            <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian inventaris SP">
                    @foreach ([
                        'rincian' => 'Rincian Barang',
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

                {{-- Rincian barang --}}
                <div x-show="tab === 'rincian'" role="tabpanel" class="p-5 sm:p-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Nama barang</dt>
                            <dd class="mt-0.5 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                {{ $data['nama_barang'] }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Jenis inventaris</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                {{ $data['jenis_inventaris'] ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Status penyerahan</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                {{ $data['status_penyerahan'] }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Catatan</dt>
                            <dd class="mt-0.5 text-theme-sm leading-relaxed text-gray-800 dark:text-white/90">
                                {{ $data['keterangan'] ?? 'Tidak ada catatan tambahan.' }}
                            </dd>
                        </div>
                        {{--
                            Foto dan dokumen, ditambahkan 2026-08-20 bersama
                            pemisahan kolom `foto` pada kamus data 4.1.
                            Sebelumnya keduanya berbagi satu slot unggah dan
                            tidak pernah ditampilkan kembali, sehingga berkas
                            yang diunggah petugas tidak punya cara dibuka.
                        --}}
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Foto kondisi</dt>
                                <dd class="mt-0.5 space-y-1 text-theme-sm text-gray-800 dark:text-white/90">
                                    {{-- Jamak sejak Putaran 14; satu barang beberapa sudut. --}}
                                    @forelse ($berkasFoto as $b)
                                        <x-sim.tautan-dokumen modul="inventaris_sp"
                                            :id="$data['id_inventaris_sp']" :berkas="$b['nama_file']" />
                                    @empty
                                        <x-sim.tautan-dokumen modul="inventaris_sp"
                                            :id="$data['id_inventaris_sp']" :berkas="null" />
                                    @endforelse
                                </dd>
                            </div>
                            <div>
                                <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Dokumen pendukung</dt>
                                <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                    <x-sim.tautan-dokumen modul="inventaris_sp" :id="$data['id_inventaris_sp']"
                                        :berkas="$data['dokumen_pendukung'] ?? null" />
                                </dd>
                            </div>
                        </div>
                    </dl>

                    @if ($data['jumlah'] > 1)
                        @if (count($data['rincian_kondisi']) > 1)
                            <div class="mt-6">
                                <p class="text-theme-xs font-medium text-gray-600 dark:text-gray-400">Rincian kondisi per unit</p>
                                <dl class="mt-2 space-y-1.5">
                                    @foreach ($data['rincian_kondisi'] as $namaKondisi => $jumlahKondisi)
                                        @continue($jumlahKondisi <= 0)
                                        <div class="flex items-center justify-between gap-3 text-theme-sm">
                                            <dt><x-sim.status-badge :status="\App\Enums\Kondisi::from($namaKondisi)" ukuran="sm" /></dt>
                                            <dd class="tabular-nums text-gray-700 dark:text-gray-300">{{ number_format($jumlahKondisi, 0, ',', '.') }} {{ $data['satuan_barang'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endif
                        <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                            Barang didata per jenis, bukan per unit: unit ke-3 tidak dapat dibedakan dari unit ke-7.
                            Kerusakan pada unit tertentu dicatat lewat pengaduan beserta keterangannya.
                        </p>
                    @endif
                </div>

                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="inventaris_sp" :record-id="$data['id_inventaris_sp']" />
                </div>
            </div>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahInventaris" judul="Ubah Data Inventaris"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('inventaris.perbarui', $data['id_inventaris_sp'])" metode="PUT" ukuran="lg"
            label-simpan="Simpan Perubahan">
            @include('pages.sp.form-inventaris', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif
@endsection
