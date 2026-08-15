{{--
    Rincian satu unit alsintan.

    Alsintan membedakan milik pribadi dan bantuan lewat poktan, karena berbeda
    pemilik dan berbeda jalur pertanggungjawabannya (agents/rules.md bagian 7c).
    Tautan pemilik karena itu menuju halaman poktan atau halaman transmigran,
    mengikuti jenis kepemilikannya.

    Tombol Ubah diletakkan di sini, bukan di halaman daftar, mengikuti pola
    yang sudah baku sejak Task 2.7.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Enums\Kondisi;

        $bolehUbah = true;

        $kondisi = Kondisi::from($data['kondisi']);
    @endphp

    <x-sim.page-header :judul="$data['nama_alat']"
        :keterangan="'Alsintan di ' . $data['satuan_permukiman'] . ', diperoleh tahun ' . $data['tahun_perolehan'] . '.'"
        :remah="[
            ['label' => 'Kelembagaan'],
            ['label' => 'Alsintan', 'url' => route('alsintan.index')],
            ['label' => $data['nama_alat']],
        ]">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahAlsintan')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                    Ubah Data Alsintan
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Status</h2>

                <div class="mt-3 flex flex-wrap gap-2">
                    <x-sim.status-badge :status="$kondisi" />
                </div>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Jumlah</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $data['jumlah'] }} unit</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Tahun perolehan</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $data['tahun_perolehan'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Sumber perolehan</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            {{ $data['sumber_perolehan'] }}</dd>
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

        {{-- Kolom kanan: tab rincian --}}
        <div x-data="hashTabs('kepemilikan')" class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian alsintan">
                    @foreach ([
                        'kepemilikan' => 'Kepemilikan',
                        'kondisi' => 'Kondisi Alat',
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

                {{-- Kepemilikan, bagian yang membedakan modul ini dari pendataan aset biasa --}}
                <div x-show="tab === 'kepemilikan'" role="tabpanel" class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <span
                            class="rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $data['kepemilikan'] === \App\Enums\KepemilikanAlsintan::BantuanPoktan->value ? 'bg-teal-50 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300' : 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300' }}">
                            {{ $data['kepemilikan'] }}
                        </span>

                        <span class="text-theme-sm text-gray-800 dark:text-white/90">
                            @if ($data['poktan_id'])
                                <a href="{{ route('poktan.detail', $data['poktan_id']) }}"
                                    class="rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                    {{ $data['pemilik'] }}
                                </a>
                            @elseif ($data['transmigran_id'])
                                <a href="{{ route('transmigran.detail', $data['transmigran_id']) }}"
                                    class="rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                    {{ $data['pemilik'] }}
                                </a>
                            @else
                                {{ $data['pemilik'] }}
                            @endif
                        </span>
                    </div>

                    <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                        @if ($data['kepemilikan'] === \App\Enums\KepemilikanAlsintan::BantuanPoktan->value)
                            Alat bantuan tercatat atas nama kelompok tani, sehingga pemakaiannya bergilir antar-anggota
                            dan pertanggungjawabannya melekat pada pengurus poktan.
                        @else
                            Alat milik pribadi tercatat atas nama transmigran yang bersangkutan, terpisah dari inventaris
                            kelompok tani.
                        @endif
                    </p>
                </div>

                {{-- Kondisi --}}
                <div x-show="tab === 'kondisi'" x-cloak role="tabpanel" class="p-5 sm:p-6">
                    <div class="flex items-start gap-3">
                        <x-sim.status-badge :status="$kondisi" />
                        <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                            Kondisi diperbarui petugas saat pendataan berkala. Alat berkondisi Rusak Berat tetap
                            didata, tidak dihapus, agar kebutuhan penggantian terbaca pada rekap aset.
                        </p>
                    </div>
                </div>

                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="alsintan" :record-id="$data['id_alsintan']" />
                </div>
            </div>
        </div>
    </div>

    {{-- Modal ubah, terisi nilai yang sedang berlaku --}}
    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahAlsintan" judul="Ubah Data Alsintan"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('alsintan.perbarui', $data['id_alsintan'])" metode="PUT" ukuran="lg"
            label-simpan="Simpan Perubahan">
            @include('pages.alsintan.form', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif
@endsection
