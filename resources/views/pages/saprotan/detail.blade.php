{{--
    Rincian satu catatan sarana produksi pertanian.

    PENERIMA SELALU KELOMPOK TANI (agents/rules.md bagian 7c). Penyaluran
    kepada perorangan dicabut 2026-08-22, sehingga tautan penerima selalu
    menuju halaman poktan.
--}}
@extends('layouts.app')

@section('content')
    @php

        $bolehUbah = true;
    @endphp

    <x-sim.page-header :judul="$data['nama']"
        :keterangan="$data['jenis'] . ' di ' . $data['satuan_permukiman'] . '.'"
        :remah="\App\Helpers\RemahHelper::untuk('/saprotan', $data['nama'])">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahSaprotan')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                    Ubah Data Saprotan
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Status</h2>

                <div class="mt-3">
                </div>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Jenis</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['jenis'] }}</dd>
                    </div>
                    @if (! empty($data['komoditas']))
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Komoditas</dt>
                            <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                                <a href="{{ route('komoditas.detail', $data['komoditas_id']) }}"
                                    class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                    {{ $data['komoditas'] }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Jumlah diterima</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($data['jumlah'], 0, ',', '.') }} {{ $data['satuan'] }}</dd>
                    </div>

                    {{--
                        Sisa dan terpakai hanya untuk benih.

                        Keduanya DIHITUNG, bukan disimpan: sisa selalu sama
                        dengan jumlah dikurangi seluruh pemakaian pada
                        penanaman. Menyimpannya sebagai kolom berarti angka itu
                        harus dikoreksi setiap kali satu baris penanaman
                        disunting, dan koreksi yang terlewat tidak akan pernah
                        ketahuan.
                    --}}
                    @if ($data['jenis'] === \App\Enums\JenisSaprotan::Benih->value)
                        @php($sisaBenih = \App\Support\DummyData::sisaBenih($data['id_saprotan']))
                        @php($terpakai = $data['jumlah'] - $sisaBenih)

                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Terpakai</dt>
                            <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                                {{ rtrim(rtrim(number_format($terpakai, 2, ',', '.'), '0'), ',') }} {{ $data['satuan'] }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Sisa</dt>
                            <dd class="text-right font-medium tabular-nums {{ $sisaBenih > 0 ? 'text-gray-800 dark:text-white/90' : 'text-error-500' }}">
                                @if ($sisaBenih > 0)
                                    {{ rtrim(rtrim(number_format($sisaBenih, 2, ',', '.'), '0'), ',') }} {{ $data['satuan'] }}
                                @else
                                    Habis
                                @endif
                            </dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Tanggal perolehan</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            {{ \Illuminate\Support\Carbon::parse($data['tanggal_perolehan'])->translatedFormat('d F Y') }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Sumber</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['sumber'] }}</dd>
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
        <div x-data="hashTabs('penerima')" class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian saprotan">
                    @foreach ([
                        'penerima' => 'Penerima',
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

                {{-- Penerima --}}
                <div x-show="tab === 'penerima'" role="tabpanel" class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <span
                            class="rounded-full bg-teal-50 px-2.5 py-1 text-theme-xs font-medium text-teal-700 dark:bg-teal-500/15 dark:text-teal-300">
                            Kelompok Tani
                        </span>

                        <span class="text-theme-sm text-gray-800 dark:text-white/90">
                            @if ($data['poktan_id'])
                                <a href="{{ route('poktan.detail', $data['poktan_id']) }}"
                                    class="rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                    {{ $data['penerima'] }}
                                </a>
                            @else
                                {{ $data['penerima'] }}
                            @endif
                        </span>
                    </div>

                    <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                        Seluruh penyaluran tercatat atas nama kelompok tani, tidak pernah perorangan. Pembagian
                        kepada anggota diatur poktan sendiri di luar sistem.
                    </p>

                    {{--
                        Catatan dan berkas, ditambahkan 2026-08-20. Kolom
                        `keterangan` dan `dokumen_pendukung` sudah lama ada pada
                        kamus data 8.4 tetapi tidak pernah ditampilkan kembali.

                        Justru di modul inilah berita acara penyaluran paling
                        sering diminta saat pemeriksaan, sehingga tidak dapat
                        membukanya berarti unggahannya tidak berguna.
                    --}}
                    <dl class="mt-6 space-y-4 border-t border-gray-200 pt-6 dark:border-gray-800">
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Catatan</dt>
                            <dd class="mt-0.5 text-theme-sm leading-relaxed text-gray-800 dark:text-white/90">
                                {{ $data['keterangan'] ?? 'Tidak ada catatan tambahan.' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Berita acara penyaluran</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                <x-sim.tautan-dokumen modul="saprotan" :id="$data['id_saprotan']"
                                    :berkas="$data['dokumen_pendukung'] ?? null" />
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="saprotan" :record-id="$data['id_saprotan']" />
                </div>
            </div>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahSaprotan" judul="Ubah Data Saprotan"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('saprotan.perbarui', $data['id_saprotan'])" metode="PUT" ukuran="lg"
            label-simpan="Simpan Perubahan">
            @include('pages.saprotan.form', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif
@endsection
