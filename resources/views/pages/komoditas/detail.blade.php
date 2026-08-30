{{--
    Rincian satu komoditas.

    Halaman ini menegaskan SATUAN PANEN BAKU. Satuan melekat pada komoditas,
    bukan dipilih bebas saat mencatat panen, agar rekap lintas komoditas dapat
    dijumlahkan (agents/rules.md bagian 8a). Form panen membaca satuan dari
    sini dan tidak mengizinkan penggantian.

    Komoditas unggulan ditandai aksen gold, salah satu dari empat pemakaian
    sah warna tersebut (agents/ui-spec.md bagian 3.1).
--}}
@extends('layouts.app')

@section('content')
    @php
        // `$riwayat` datang dari rute `komoditas.detail`.
        $bolehUbah = true;
    @endphp

    <x-sim.page-header :judul="$data['nama']"
        :keterangan="'Komoditas ' . strtolower($data['tipe']) . ', satuan panen baku ' . $data['satuan'] . '.'"
        :remah="\App\Helpers\RemahHelper::untuk('/komoditas', $data['nama'])">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahKomoditas')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                    Ubah Data Komoditas
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Profil Komoditas</h2>

                @if ($data['is_unggulan'])
                    <div class="mt-3">
                        <span class="inline-flex items-center rounded-full bg-gold-100 px-2.5 py-1 text-theme-xs font-medium text-gold-800 dark:bg-gold-500/15 dark:text-gold-300">
                            Komoditas Unggulan
                        </span>
                    </div>
                @endif

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Tipe</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['tipe'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Satuan panen baku</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['satuan'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Penanaman</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ count($riwayat) }} catatan</dd>
                    </div>
                </dl>
            </div>
        </aside>

        {{-- Kolom kanan: tab rincian --}}
        <div x-data="hashTabs('satuan')" class="min-w-0">
            <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian komoditas">
                    @php
                        // Tab keterangan hanya dirender bila deskripsinya terisi,
                        // agar tidak ada tab yang membuka panel kosong.
                        $tab = ['satuan' => 'Satuan Panen Baku'];
                        if ($data['deskripsi']) {
                            $tab['keterangan'] = 'Keterangan';
                        }
                        $tab['riwayat'] = 'Penanaman (' . count($riwayat) . ')';
                        $tab['log'] = 'Catatan Log';
                    @endphp
                    @foreach ($tab as $kunci => $label)
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

                {{-- Satuan baku, alasan modul ini ada --}}
                <div x-show="tab === 'satuan'" role="tabpanel" class="p-5 sm:p-6">
                    <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                        Setiap pencatatan panen komoditas ini memakai satuan
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $data['satuan'] }}</span>,
                        dan tidak dapat diganti saat mengisi form panen.
                    </p>
                    <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                        Tanpa satuan baku, satu komoditas dapat tercatat dalam ton di satu SP dan kilogram di SP lain,
                        sehingga penjumlahan lintas wilayah menghasilkan angka yang tidak sepadan.
                    </p>
                </div>

                @if ($data['deskripsi'])
                    <div x-show="tab === 'keterangan'" x-cloak role="tabpanel" class="p-5 sm:p-6">
                        <p class="text-theme-sm text-gray-600 dark:text-gray-400">{{ $data['deskripsi'] }}</p>
                    </div>
                @endif

                {{-- Penanaman --}}
                <div x-show="tab === 'riwayat'" x-cloak role="tabpanel">
                    @if ($riwayat === [])
                        <x-sim.empty-state judul="Belum ada penanaman"
                            pesan="Catatan penanaman komoditas ini akan tampil setelah petugas mendatanya." />
                    @else
                        <x-sim.tabel-ringkas judul="Riwayat penanaman komoditas ini" :kolom="['Kelompok Tani', 'Satuan Permukiman', 'Realisasi Tanam', 'Tanggal Tanam']">
                            @foreach ($riwayat as $r)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                                        <a href="{{ route('penanaman.detail', $r['id_penanaman']) }}"
                                            class="rounded hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:hover:text-brand-400">
                                            {{ $r['poktan'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $r['satuan_permukiman'] }}</td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($r['realisasi_tanam'], 2, ',', '.') }} ha</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ \Illuminate\Support\Carbon::parse($r['periode_tanam'] . '-01')->translatedFormat('d M Y') }}
                                    </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="komoditas" :record-id="$data['id_komoditas']" />
                </div>
            </div>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahKomoditas" judul="Ubah Data Komoditas"
            keterangan="Perubahan satuan baku berlaku bagi pencatatan panen berikutnya, bukan yang sudah tersimpan."
            :aksi="route('komoditas.perbarui', $data['id_komoditas'])" metode="PUT" ukuran="lg"
            label-simpan="Simpan Perubahan">
            @include('pages.komoditas.form', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif
@endsection