{{--
    Rincian satu catatan tanam.

    Halaman ini dibuat 2026-08-20 agar riwayat tanam memiliki tab Catatan Log
    seperti modul lain.

    SATU BARIS ADALAH SATU PENANAMAN pada satu lahan, satu musim, dan satu
    komoditas. Hasil panen menaut ke baris inilah, bukan ke lahan maupun musim
    secara terpisah (kamus data 9.3), sehingga panen yang tercatat ditampilkan
    di sini sebagai kelanjutan penanamannya.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        // Panen dari penanaman ini. Dicocokkan lewat pasangan komoditas, musim,
        // dan petani, sebab DummyData::hasilPanen() belum menyimpan
        // riwayat_tanam_id; pada Tahap 7 penyaringannya berpindah ke relasi.
        $panen = array_values(array_filter(
            DummyData::hasilPanen(),
            fn ($p) => $p['musim_tanam'] === $data['musim_tanam']
                && $p['komoditas'] === $data['komoditas']
                && $p['petani'] === $data['petani'],
        ));

        $volume = array_sum(array_column($panen, 'volume'));

        $lahan = collect(DummyData::lahan())->firstWhere('id_lahan', $data['lahan_id']);

        $bolehUbah = true;
    @endphp

    <x-sim.page-header :judul="$data['kode_lahan'] . ' - ' . $data['musim_tanam']"
        :keterangan="'Penanaman ' . $data['komoditas'] . ' oleh ' . $data['petani'] . '.'"
        :remah="\App\Helpers\RemahHelper::untuk('/riwayat-tanam', $data['kode_lahan'] . ' - ' . $data['musim_tanam'])">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahRiwayat')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                    </svg>
                    Ubah Catatan Tanam
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                    {{ $data['komoditas'] }}
                </h2>
                <p class="mt-1 text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                    {{ number_format($data['luas_tanam'], 2, ',', '.') }}
                    <span class="text-theme-sm font-normal text-gray-500 dark:text-gray-400">ha ditanam</span>
                </p>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Lahan</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            @if ($lahan)
                                <a href="{{ route('lahan.detail', $lahan['id_lahan']) }}"
                                    class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                    {{ $data['kode_lahan'] }}
                                </a>
                            @else
                                {{ $data['kode_lahan'] }}
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Petani</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['petani'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Musim tanam</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            {{ $data['musim_tanam'] }}</dd>
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
                </dl>
            </div>
        </aside>

        <div x-data="hashTabs('rincian')" class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian catatan tanam">
                    @foreach ([
                        'rincian' => 'Rincian',
                        'panen' => 'Hasil Panen (' . count($panen) . ')',
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
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Tanggal tanam</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ \Illuminate\Support\Carbon::parse($data['tanggal_tanam'])->translatedFormat('d F Y') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Luas tanam</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($data['luas_tanam'], 2, ',', '.') }} ha
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

                <div x-show="tab === 'panen'" x-cloak role="tabpanel">
                    @if (empty($panen))
                        <x-sim.empty-state judul="Belum ada panen tercatat"
                            pesan="Hasil panen dari penanaman ini akan tampil di sini setelah dicatat." />
                    @else
                        <x-sim.tabel-ringkas :kolom="['Tanggal Panen', 'Volume', 'Kualitas', 'Harga Jual']">
                            @foreach ($panen as $p)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                                        {{ \Illuminate\Support\Carbon::parse($p['tanggal_panen'])->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($p['volume'], 2, ',', '.') }} {{ $p['satuan'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $p['kualitas'] ?? '-' }}</td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $p['harga_jual'] ? 'Rp ' . number_format($p['harga_jual'], 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                            @endforeach

                            <tr class="motif-baris-total">
                                <td class="px-5 py-3 text-theme-sm text-gray-700 dark:text-gray-300">Total volume</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($volume, 2, ',', '.') }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="riwayat_tanam" :record-id="$data['id_riwayat_tanam']" />
                </div>
            </div>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahRiwayat" judul="Ubah Catatan Tanam"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('riwayat-tanam.perbarui', $data['id_riwayat_tanam'])" metode="PUT" ukuran="lg"
            label-simpan="Simpan Perubahan">
            @include('pages.komoditas.form-riwayat-tanam', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif
@endsection
