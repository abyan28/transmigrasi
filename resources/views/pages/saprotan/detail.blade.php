{{--
    Rincian satu PENGADAAN sarana produksi pertanian (Putaran 7).

    Satu batch bantuan dapat dibagikan ke beberapa poktan. Halaman ini
    menampilkan bendanya (jenis, komoditas, varietas, jumlah total, tahun,
    sumber dana) dan tabel distribusi per poktan penerima. Untuk benih, sisa
    stok dihitung PER BARIS distribusi (jatah poktan itu dikurangi
    penanamannya sendiri), tidak disimpan (rules.md §7c poin 8).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Enums\JenisSaprotan;

        $bolehUbah = true;
        $benih = $data['jenis'] === JenisSaprotan::Benih->value;
    @endphp

    <x-sim.page-header :judul="$data['nama']"
        :keterangan="$data['jenis'] . ($data['komoditas'] ? ' ' . $data['komoditas'] : '') . ', diadakan tahun ' . $data['tahun_pengadaan'] . '.'"
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

    <div class="grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Pengadaan</h2>

                <dl class="mt-4 space-y-3 text-theme-sm">
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
                    @if (! empty($data['varietas']))
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Varietas</dt>
                            <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['varietas'] }}</dd>
                        </div>
                    @endif
                    @if (! empty($data['jadwal_tanam']))
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Jadwal tanam</dt>
                            <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                                {{ \Illuminate\Support\Carbon::parse($data['jadwal_tanam'] . '-01')->translatedFormat('F Y') }}
                            </dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Jumlah total</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($data['jumlah_total'], 0, ',', '.') }} {{ $data['satuan'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Tersalur</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ rtrim(rtrim(number_format($data['jumlah_tersalur'], 2, ',', '.'), '0'), ',') }} {{ $data['satuan'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Belum tersalur</dt>
                        <dd class="text-right font-medium tabular-nums {{ $data['jumlah_belum_tersalur'] > 0 ? 'text-yellow-700 dark:text-yellow-400' : 'text-gray-800 dark:text-white/90' }}">
                            {{ rtrim(rtrim(number_format($data['jumlah_belum_tersalur'], 2, ',', '.'), '0'), ',') }} {{ $data['satuan'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Tahun pengadaan</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">{{ $data['tahun_pengadaan'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Sumber dana</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['sumber_dana'] ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </aside>

        <div x-data="hashTabs('distribusi')" class="min-w-0">
            <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian saprotan">
                    @foreach ([
                        'distribusi' => 'Distribusi',
                        'dokumen' => 'Catatan dan Berkas',
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

                {{-- Distribusi ke poktan --}}
                <div x-show="tab === 'distribusi'" role="tabpanel" class="p-5 sm:p-6">
                    @if (count($data['distribusi']) === 0)
                        <x-sim.empty-state judul="Belum tersalurkan"
                            pesan="Seluruh pengadaan masih di gudang UPT. Bagikan ke kelompok tani lewat tombol Ubah Data Saprotan." />
                    @else
                        <div class="relative overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                            <table class="min-w-full text-theme-sm">
                                <caption class="px-4 py-2.5 text-left text-theme-xs text-gray-500 dark:text-gray-400">
                                    Pembagian {{ $data['nama'] }} ke kelompok tani penerima
                                </caption>
                                <thead class="border-y border-gray-200 bg-gray-50 text-theme-xs text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-4 py-2 text-left">Kelompok Tani</th>
                                        <th scope="col" class="px-4 py-2 text-left">Satuan Permukiman</th>
                                        <th scope="col" class="px-4 py-2 text-right">Jumlah</th>
                                        @if ($benih)
                                            <th scope="col" class="px-4 py-2 text-right">Terpakai</th>
                                            <th scope="col" class="px-4 py-2 text-right">Sisa</th>
                                        @endif
                                        <th scope="col" class="px-4 py-2 text-left">Tanggal Serah</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($data['distribusi'] as $d)
                                        <tr class="text-gray-700 dark:text-gray-300">
                                            <td class="px-4 py-2 font-medium text-gray-800 dark:text-white/90">
                                                <a href="{{ route('poktan.detail', $d['poktan_id']) }}"
                                                    class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                                    {{ $d['poktan'] }}
                                                </a>
                                                @if (! empty($d['keterangan']))
                                                    <span class="mt-0.5 block text-theme-xs font-normal text-gray-500 dark:text-gray-400">{{ $d['keterangan'] }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2">
                                                <a href="{{ route('sp.detail', $d['satuan_permukiman_id']) }}"
                                                    class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                                    {{ $d['satuan_permukiman'] }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-2 text-right tabular-nums">
                                                {{ rtrim(rtrim(number_format($d['jumlah'], 2, ',', '.'), '0'), ',') }} {{ $data['satuan'] }}
                                            </td>
                                            @if ($benih)
                                                @php($terpakai = $d['jumlah'] - $d['sisa_benih'])
                                                <td class="px-4 py-2 text-right tabular-nums">
                                                    {{ rtrim(rtrim(number_format($terpakai, 2, ',', '.'), '0'), ',') }}
                                                </td>
                                                <td class="px-4 py-2 text-right tabular-nums {{ $d['sisa_benih'] > 0 ? '' : 'text-error-500' }}">
                                                    {{ $d['sisa_benih'] > 0 ? rtrim(rtrim(number_format($d['sisa_benih'], 2, ',', '.'), '0'), ',') : 'Habis' }}
                                                </td>
                                            @endif
                                            <td class="px-4 py-2 tabular-nums">
                                                {{ $d['tanggal_serah'] ? \Illuminate\Support\Carbon::parse($d['tanggal_serah'])->translatedFormat('d M Y') : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($benih)
                            <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
                                Sisa benih dihitung per kelompok: jatah satu poktan dikurangi penanaman poktan itu
                                sendiri, bukan penanaman poktan lain. Nilainya mengoreksi diri sendiri saat baris
                                penanaman disunting; tidak ada mekanisme "pengembalian stok".
                            </p>
                        @endif
                    @endif
                </div>

                {{-- Catatan dan berkas --}}
                <div x-show="tab === 'dokumen'" x-cloak role="tabpanel" class="p-5 sm:p-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Catatan</dt>
                            <dd class="mt-0.5 text-theme-sm leading-relaxed text-gray-800 dark:text-white/90">
                                {{ $data['keterangan'] ?? 'Tidak ada catatan tambahan.' }}
                            </dd>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Foto barang</dt>
                                <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                    <x-sim.tautan-dokumen modul="saprotan" :id="$data['id_saprotan']"
                                        :berkas="$data['foto'] ?? null" />
                                </dd>
                            </div>
                            <div>
                                <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Berita acara penyaluran</dt>
                                <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                    <x-sim.tautan-dokumen modul="saprotan" :id="$data['id_saprotan']"
                                        :berkas="$data['dokumen_pendukung'] ?? null" />
                                </dd>
                            </div>
                        </div>
                    </dl>
                </div>

                {{-- Catatan log --}}
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
