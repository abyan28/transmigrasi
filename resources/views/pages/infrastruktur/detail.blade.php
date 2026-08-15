{{--
    Rincian satu aset infrastruktur SP.

    Modul ini adalah PENDATAAN ASET, bukan pelaporan masalah. Karena itu
    halaman sengaja tidak menyediakan tombol lapor kerusakan: keluhan warga
    masuk lewat modul pengaduan, sedangkan di sini kondisi diperbarui petugas
    saat pendataan berkala (agents/tasklist.md Task 2.18).

    Kondisi aset menjadi sumber indikator ke-12 dashboard sekaligus salah satu
    parameter penilaian kondisi SP.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Enums\JenisInfrastruktur;
        use App\Enums\Kondisi;
        use App\Support\DummyData;

        $bolehUbah = true;

        $kondisi = Kondisi::from($data['kondisi']);
        $jenis = JenisInfrastruktur::from($data['jenis']);

        // Pengaduan pada SP yang sama, sebagai konteks. Bukan daftar keluhan
        // atas aset ini, sebab pengaduan tidak menaut ke id infrastruktur.
        $pengaduanSp = array_values(array_filter(
            DummyData::pengaduan(),
            fn ($p) => $p['satuan_permukiman_id'] === $data['satuan_permukiman_id'],
        ));
    @endphp

    <x-sim.page-header :judul="$data['nama']"
        :keterangan="$jenis->value . ' di ' . $data['satuan_permukiman'] . ', dibangun tahun ' . $data['tahun_perolehan'] . '.'"
        :remah="[
            ['label' => 'Infrastruktur'],
            ['label' => 'Infrastruktur SP', 'url' => route('infrastruktur.index')],
            ['label' => $data['nama']],
        ]">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahInfrastruktur')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                    Ubah Data Aset
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Status Aset</h2>

                <div class="mt-3 flex flex-wrap gap-2">
                    <x-sim.status-badge :status="$kondisi" />
                </div>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Jenis</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $jenis->value }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Kapasitas</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            {{ $data['kapasitas'] ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Tahun dibangun</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $data['tahun_perolehan'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Sumber dana</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            {{ $data['sumber_dana'] }}</dd>
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
        <div x-data="hashTabs('kondisi')" class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian aset infrastruktur">
                    @foreach ([
                        'kondisi' => 'Kondisi Aset',
                        'pengaduan' => 'Pengaduan (' . count($pengaduanSp) . ')',
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

                {{-- Kondisi aset --}}
                <div x-show="tab === 'kondisi'" role="tabpanel" class="p-5 sm:p-6">
                    <div class="flex items-start gap-3">
                        <x-sim.status-badge :status="$kondisi" />
                        <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                            Kondisi diperbarui petugas saat pendataan berkala, bukan lewat laporan warga.
                            Nilai ini menjadi salah satu parameter penilaian kondisi satuan permukiman.
                        </p>
                    </div>

                    {{--
                        Penegasan batas modul. Tanpa ini, petugas mudah mengira
                        halaman aset adalah tempat melaporkan kerusakan.
                    --}}
                    <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                        Halaman ini mendata aset, bukan menerima laporan kerusakan. Keluhan warga mengenai
                        infrastruktur disampaikan lewat
                        <a href="{{ route('pengaduan.index') }}"
                            class="rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">modul
                            pengaduan</a>, agar penanganannya terlacak beserta riwayat tindak lanjutnya.
                    </p>
                </div>

                {{-- Pengaduan pada SP yang sama, sebagai konteks --}}
                <div x-show="tab === 'pengaduan'" x-cloak role="tabpanel">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                            Seluruh pengaduan pada SP ini, tidak khusus mengenai aset yang sedang dibuka.
                        </p>
                    </div>

                    @if ($pengaduanSp === [])
                        <x-sim.empty-state judul="Belum ada pengaduan"
                            pesan="Pengaduan warga pada satuan permukiman ini akan tampil di sini." />
                    @else
                        <x-sim.tabel-ringkas :kolom="['Nomor', 'Judul', 'Kategori', 'Status']">
                            @foreach ($pengaduanSp as $p)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('pengaduan.detail', $p['id_pengaduan']) }}"
                                            class="rounded text-theme-sm tabular-nums text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                            {{ $p['nomor_pengaduan'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                                        {{ $p['judul'] }}</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $p['kategori'] }}</td>
                                    <td class="px-5 py-3">
                                        <x-sim.status-badge :status="\App\Enums\StatusPengaduan::from($p['status'])" />
                                    </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="infrastruktur" :record-id="$data['id_infrastruktur']" />
                </div>
            </div>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahInfrastruktur" judul="Ubah Data Aset"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('infrastruktur.perbarui', $data['id_infrastruktur'])" metode="PUT" ukuran="lg"
            label-simpan="Simpan Perubahan">
            @include('pages.infrastruktur.form', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif
@endsection