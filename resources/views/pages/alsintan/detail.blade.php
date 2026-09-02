{{--
    Rincian satu PENGADAAN alsintan (Putaran 7).

    Satu batch bantuan dapat dibagikan ke beberapa poktan lintas SP. Halaman
    ini menampilkan bendanya (jenis, nama, jumlah total, tahun, sumber dana)
    dan tabel distribusi per poktan penerima. Kondisi melekat pada tiap baris
    distribusi, sebab diamati per unit di lapangan.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Enums\Kondisi;

        $bolehUbah = true;
    @endphp

    <x-sim.page-header :judul="$data['nama_alat']"
        :keterangan="$data['jenis_alsintan'] . ', diadakan tahun ' . $data['tahun_pengadaan'] . '.'"
        :remah="\App\Helpers\RemahHelper::untuk('/alsintan', $data['nama_alat'])">
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

    <div class="grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Pengadaan</h2>

                <dl class="mt-4 space-y-3 text-theme-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Jenis alat</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['jenis_alsintan'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Jumlah total</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">{{ $data['jumlah_total'] }} unit</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Tersalur</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">{{ $data['jumlah_tersalur'] }} unit</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Belum tersalur</dt>
                        <dd class="text-right font-medium tabular-nums {{ $data['jumlah_belum_tersalur'] > 0 ? 'text-yellow-700 dark:text-yellow-400' : 'text-gray-800 dark:text-white/90' }}">
                            {{ $data['jumlah_belum_tersalur'] }} unit</dd>
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
                    role="tablist" aria-label="Rincian alsintan">
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
                            pesan="Seluruh {{ $data['jumlah_total'] }} unit masih di gudang UPT. Bagikan ke kelompok tani lewat tombol Ubah Data Alsintan." />
                    @else
                        <div class="relative overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                            <table class="min-w-full text-theme-sm">
                                <caption class="px-4 py-2.5 text-left text-theme-xs text-gray-500 dark:text-gray-400">
                                    Pembagian {{ $data['nama_alat'] }} ke kelompok tani penerima
                                </caption>
                                <thead class="border-y border-gray-200 bg-gray-50 text-theme-xs text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-4 py-2 text-left">Kelompok Tani</th>
                                        <th scope="col" class="px-4 py-2 text-left">Satuan Permukiman</th>
                                        <th scope="col" class="px-4 py-2 text-right">Jumlah</th>
                                        <th scope="col" class="px-4 py-2 text-left">Kondisi</th>
                                        <th scope="col" class="px-4 py-2 text-left">Penanda Tangan</th>
                                        <th scope="col" class="px-4 py-2 text-left">Tanggal Serah</th>
                                        <th scope="col" class="px-4 py-2 text-right"><span class="sr-only">Aksi</span></th>
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
                                                @if (! empty($d['foto']))
                                                    <span class="mt-0.5 block text-theme-xs font-normal">
                                                        <x-sim.tautan-dokumen modul="alsintan" :id="$data['id_alsintan']" :berkas="$d['foto']" />
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2">
                                                <a href="{{ route('sp.detail', $d['satuan_permukiman_id']) }}"
                                                    class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                                    {{ $d['satuan_permukiman'] }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-2 text-right tabular-nums">{{ $d['jumlah'] }}</td>
                                            <td class="px-4 py-2">
                                                <x-sim.status-badge :status="Kondisi::from($d['kondisi'])" ukuran="sm" />
                                            </td>
                                            <td class="px-4 py-2">{{ $d['penanda_terima'] ?? 'Belum dicatat' }}</td>
                                            <td class="px-4 py-2 tabular-nums">
                                                {{ $d['tanggal_serah'] ? \Illuminate\Support\Carbon::parse($d['tanggal_serah'])->translatedFormat('d M Y') : '-' }}
                                            </td>
                                            <td class="px-4 py-2 text-right">
                                                @if ($bolehUbah)
                                                    <button type="button"
                                                        @click.prevent="$dispatch('buka-modal-baris', {
                                                            nama: 'formKondisiDistribusi',
                                                            data: @js(['id' => $d['id_alsintan_distribusi'], 'poktan' => $d['poktan'], 'kondisi' => $d['kondisi']]),
                                                        })"
                                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                                        Perbarui Kondisi
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
                            Kondisi diperbarui per baris distribusi: unit di satu poktan dapat berkondisi berbeda
                            dari unit yang sama di poktan lain.
                        </p>
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
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Foto barang</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                <x-sim.tautan-dokumen modul="alsintan" :id="$data['id_alsintan']"
                                    :berkas="$data['foto'] ?? null" />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Dokumen pendukung</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                <x-sim.tautan-dokumen modul="alsintan" :id="$data['id_alsintan']"
                                    :berkas="$data['dokumen_pendukung'] ?? null" />
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Catatan log --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="alsintan" :record-id="$data['id_alsintan']" />
                </div>
            </div>
        </div>
    </div>

    {{-- Modal ubah pengadaan --}}
    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahAlsintan" judul="Ubah Data Alsintan"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('alsintan.perbarui', $data['id_alsintan'])" metode="PUT" ukuran="lg"
            label-simpan="Simpan Perubahan">
            @include('pages.alsintan.form', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>

        {{-- Modal perbarui kondisi satu baris distribusi, beserta foto unitnya. --}}
        <x-sim.modal-form nama="formKondisiDistribusi" judul="Perbarui Kondisi Alat"
            keterangan="Kondisi diamati per unit di lapangan dan berubah setelah pengadaan."
            :pola-aksi="'/alsintan/' . $data['id_alsintan'] . '/distribusi/:id/kondisi'"
            ukuran="md" label-simpan="Simpan Kondisi">

            <div class="space-y-5" x-data="{ namaPoktan: '' }"
                x-on:buka-modal-baris.window="if ($event.detail.nama === 'formKondisiDistribusi') namaPoktan = $event.detail.data.poktan">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">Kelompok tani</p>
                    <p class="mt-0.5 text-theme-sm font-medium text-gray-800 dark:text-white/90" x-text="namaPoktan">&nbsp;</p>
                </div>
                <div>
                    <label for="dist_kondisi_baru" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Kondisi</label>
                    <select id="dist_kondisi_baru" name="kondisi" required
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        @foreach ($opsiKondisi as $nilaiRef => $labelRef)
                            <option value="{{ $nilaiRef }}">{{ $nilaiRef }}</option>
                        @endforeach
                    </select>
                </div>

                {{--
                    Foto merekam WUJUD unit ini saat pendataan, bukan berkas
                    administratif pengadaan (yang ada di tab Catatan dan Berkas).
                    Terpisah dengan alasan yang sama seperti infrastruktur.
                --}}
                <x-sim.file-upload nama="foto" label="Foto Kondisi Unit" :hanya-gambar="true"
                    nama-dokumen="Foto Alsintan" :nama-pemilik="$data['nama_alat']"
                    keterangan="Dokumentasi kondisi unit di kelompok ini saat pendataan." />
            </div>
        </x-sim.modal-form>
    @endif
@endsection
