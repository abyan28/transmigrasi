{{--
    Rincian satu musim tanam.

    Halaman ini dibuat 2026-08-20 agar musim tanam memiliki tab Catatan Log
    seperti modul lain. Sebelumnya ia hanya punya halaman daftar, sehingga
    perubahan tanggal mulai atau selesai tidak dapat ditelusuri dari tempat
    datanya sendiri.

    MUSIM TANAM ADALAH PERIODE, bukan aset. Karena itu rincian yang ditampilkan
    berpusat pada rentang waktunya beserta penanaman yang jatuh di dalamnya,
    bukan pada kondisi atau lokasi.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        // Penanaman yang jatuh pada musim ini. Dicocokkan lewat label, sebab
        // DummyData::riwayatTanam() menyimpan musimnya sebagai teks tampilan;
        // pada Tahap 7 penyaringannya berpindah ke relasi musim_tanam_id.
        $penanaman = array_values(array_filter(
            DummyData::riwayatTanam(),
            fn ($r) => $r['musim_tanam'] === $data['label'],
        ));

        $luasTanam = array_sum(array_column($penanaman, 'luas_tanam'));

        // Panen yang tercatat pada musim ini, dibaca dari sisi hasil panen.
        $panen = array_values(array_filter(
            DummyData::hasilPanen(),
            fn ($p) => $p['musim_tanam'] === $data['label'],
        ));

        $bolehUbah = true;
    @endphp

    <x-sim.page-header :judul="$data['label']"
        :keterangan="'Musim tanam ' . $data['nama'] . ' tahun ' . $data['tahun'] . '.'"
        :remah="\App\Helpers\RemahHelper::untuk('/musim-tanam', $data['label'])">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahMusim')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                    </svg>
                    Ubah Musim Tanam
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        {{-- Kolom kiri: rentang waktu dan rekap singkat --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                    {{ $data['label'] }}
                </h2>
                <p class="mt-1 text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                    {{ count($penanaman) }}
                    <span class="text-theme-sm font-normal text-gray-500 dark:text-gray-400">penanaman</span>
                </p>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Tanggal mulai</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ \Illuminate\Support\Carbon::parse($data['tanggal_mulai'])->translatedFormat('d F Y') }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Tanggal selesai</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ \Illuminate\Support\Carbon::parse($data['tanggal_selesai'])->translatedFormat('d F Y') }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Luas tanam</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($luasTanam, 2, ',', '.') }} ha
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Panen tercatat</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ count($panen) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </aside>

        {{-- Kolom kanan: tab rincian --}}
        <div x-data="hashTabs('rincian')" class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian musim tanam">
                    @foreach ([
                        'rincian' => 'Rincian',
                        'penanaman' => 'Penanaman (' . count($penanaman) . ')',
                        // Catatan Log wajib tetap paling kanan (ui-spec.md 5.1c).
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

                <div x-show="tab === 'rincian'" role="tabpanel" class="p-5 sm:p-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Nama musim</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">{{ $data['nama'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Tahun</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ $data['tahun'] }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Catatan</dt>
                            <dd class="mt-0.5 text-theme-sm leading-relaxed text-gray-800 dark:text-white/90">
                                {{ $data['keterangan'] ?? 'Tidak ada catatan tambahan.' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div x-show="tab === 'penanaman'" x-cloak role="tabpanel">
                    @if (empty($penanaman))
                        <x-sim.empty-state judul="Belum ada penanaman"
                            pesan="Catatan tanam pada musim ini akan tampil di sini setelah didata." />
                    @else
                        <x-sim.tabel-ringkas :kolom="['Lahan', 'Petani', 'Komoditas', 'Luas (ha)', 'Tanggal Tanam']">
                            @foreach ($penanaman as $r)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                                        {{ $r['kode_lahan'] }}
                                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                            {{ $r['satuan_permukiman'] }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $r['petani'] }}</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $r['komoditas'] }}</td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($r['luas_tanam'], 2, ',', '.') }}</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ \Illuminate\Support\Carbon::parse($r['tanggal_tanam'])->translatedFormat('d M Y') }}
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Baris total memakai motif identitas garis atas navy --}}
                            <tr class="motif-baris-total">
                                <td colspan="3" class="px-5 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    Total luas tanam
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($luasTanam, 2, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="musim_tanam" :record-id="$data['id_musim_tanam']" />
                </div>
            </div>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahMusim" judul="Ubah Musim Tanam"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('musim-tanam.perbarui', $data['id_musim_tanam'])" metode="PUT" ukuran="lg"
            label-simpan="Simpan Perubahan">
            @include('pages.komoditas.form-musim-tanam', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif
@endsection
