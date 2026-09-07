{{--
    Kawasan transmigrasi.

    Cabang program dari hierarki wilayah. Satu kawasan dapat menaungi SP yang
    tersebar di beberapa kecamatan, dan itulah alasan kawasan dipisahkan dari
    hierarki administratif (agents/rules.md bagian 4a poin 5).
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `kawasan`.
        Lihat routes/web.php.
    --}}

    <x-sim.page-header judul="Kawasan Transmigrasi"
        keterangan="Wilayah perencanaan program yang menaungi satuan permukiman."
        :remah="\App\Helpers\RemahHelper::untuk('/kawasan')">
        <x-slot:aksi>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahKawasan')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Kawasan
            </button>
        </x-slot:aksi>
    </x-sim.page-header>

    {{-- Tanpa `cari`/filter (kawasan tak sebanyak itu). --}}
    <form method="GET" action="{{ route('kawasan') }}" class="mb-4 flex justify-end">
        <x-sim.pilih-per-halaman :per-halaman="$kawasan->perPage()" />
    </form>

    @foreach ($kawasan as $k)
        <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                        Kawasan {{ $k['nama'] }}
                    </h2>
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                        Kabupaten {{ $k['kabupaten'] }}, {{ $k['provinsi'] }}
                    </p>
                </div>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-theme-xs font-medium text-teal-700 dark:bg-teal-500/15 dark:text-teal-300">
                            {{ $k['kode_kawasan'] }}
                        </span>

                        {{-- Aksi kawasan. Tanpa Rincian, sebab seluruh isinya sudah tampil di kartu ini. --}}
                        <x-sim.aksi-baris modal-ubah="formUbahKawasanBaris"
                            :data-baris="$k + ['id' => $k['id_kawasan_transmigrasi']]"
                            :hapus-url="'/kawasan/' . $k['id_kawasan_transmigrasi']"
                            konfirmasi-hapus="hapusKawasan" :label="$k['nama']" />
                    </div>
            </div>

            {{-- Metrik Utama Kawasan (4 Kolom Simetris) --}}
            <dl class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Tahun penetapan</dt>
                    <dd class="mt-0.5 text-theme-sm font-semibold tabular-nums text-gray-800 dark:text-white/90">
                        {{ $k['tahun_penetapan'] }}
                    </dd>
                </div>
                <div>
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Nomor SK</dt>
                    <dd class="mt-0.5 text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                        {{ $k['nomor_sk'] ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Luas total</dt>
                    <dd class="mt-0.5 text-theme-sm font-semibold tabular-nums text-gray-800 dark:text-white/90">
                        {{ number_format($k['luas_total'], 2, ',', '.') }} ha
                    </dd>
                </div>
                <div>
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Satuan permukiman</dt>
                    <dd class="mt-0.5 text-theme-sm font-semibold tabular-nums text-gray-800 dark:text-white/90">
                        {{ $k['jumlah_sp'] }} SP
                    </dd>
                </div>
            </dl>

            {{-- Dokumen & Alas Hak Kawasan (Panel Kartu Lampiran Mandiri) --}}
            <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-800/80">
                <div class="flex items-center justify-between">
                    <h3 class="text-theme-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Dokumen &amp; Alas Hak Kawasan
                    </h3>
                    <span class="text-[11px] text-gray-400 dark:text-gray-500">
                        {{ count($berkasKawasan[$k['id_kawasan_transmigrasi']] ?? []) }} berkas tersimpan
                    </span>
                </div>

                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($berkasKawasan[$k['id_kawasan_transmigrasi']] ?? [] as $b)
                        @php
                            $labelPeran = match (strtolower($b['peran'] ?? '')) {
                                'hpl' => 'Alas Hak (HPL)',
                                'sk' => 'SK Penetapan',
                                'peta' => 'Peta Kawasan',
                                default => strtoupper($b['peran'] ?? 'Dokumen'),
                            };
                            $warnaPeran = match (strtolower($b['peran'] ?? '')) {
                                'hpl' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-500/15 dark:text-teal-300 dark:border-teal-500/30',
                                'sk' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500/30',
                                'peta' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/30',
                                default => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-white/10 dark:text-gray-300 dark:border-gray-700',
                            };
                        @endphp
                        <div class="flex flex-col justify-between rounded-xl border border-gray-200/80 bg-gray-50/60 p-3 transition hover:border-brand-300 hover:bg-white dark:border-gray-800 dark:bg-white/[0.02] dark:hover:border-brand-700 dark:hover:bg-white/[0.04]">
                            <div class="flex items-start gap-2.5 min-w-0">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white shadow-2xs border border-gray-200 text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <span class="inline-flex rounded-md border px-2 py-0.5 text-[10px] font-semibold {{ $warnaPeran }}">
                                        {{ $labelPeran }}
                                    </span>
                                    <div class="mt-1.5 truncate">
                                        <x-sim.tautan-dokumen modul="kawasan"
                                            :id="$k['id_kawasan_transmigrasi']" :berkas="$b['nama_file']" />
                                    </div>
                                    @if (!empty($b['keterangan']) && $b['keterangan'] !== $b['nama_file'])
                                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2">
                                            {{ $b['keterangan'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="sm:col-span-2 lg:col-span-3 rounded-xl border border-dashed border-gray-200 bg-gray-50/40 p-4 text-center dark:border-gray-800 dark:bg-white/[0.01]">
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                                Belum ada berkas dokumen (SK, HPL, atau peta) yang diunggah untuk kawasan ini.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800/80">
                <p class="text-theme-xs text-gray-500 dark:text-gray-400">Catatan</p>
                <p class="mt-0.5 text-theme-sm leading-relaxed text-gray-800 dark:text-white/90">
                    {{ $k['keterangan'] ?? 'Tidak ada catatan tambahan.' }}
                </p>
            </div>
        </div>
    @endforeach

    @if ($kawasan->hasPages())
        <div class="mb-6">
            {{ $kawasan->onEachSide(1)->links() }}
        </div>
    @endif

    {{-- Sebaran SP, memperlihatkan mengapa kawasan tidak dapat diwakili struktur administratif --}}
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Sebaran Satuan Permukiman</h2>
            @if ($jumlahSpSebaran > 0)
                <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    {{ $jumlahSpSebaran }} SP tersebar di {{ $jumlahKecamatan }} kecamatan berbeda. Sebaran inilah alasan kawasan
                    dicatat terpisah dari hierarki administratif.
                </p>
            @else
                <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Kawasan transmigrasi menaungi satuan permukiman lintas kecamatan administratif.
                </p>
            @endif
        </div>

        @if ($jumlahSpSebaran > 0)
            <x-sim.tabel-ringkas judul="Satuan permukiman dalam kawasan ini" :kolom="['Satuan Permukiman', 'Desa', 'Kecamatan', 'Kepala Keluarga', 'Rincian']">
                @foreach ($daftarSp as $sp)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                            {{ $sp['nama'] }}</td>
                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $sp['desa'] }}</td>
                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $sp['kecamatan'] }}</td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                            {{ number_format($sp['jumlah_kk'], 0, ',', '.') }} KK</td>
                        <td class="px-5 py-3">
                            <a href="{{ route('sp.detail', $sp['id_satuan_permukiman']) }}"
                                class="rounded text-theme-sm font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                Buka rincian
                            </a>
                        </td>
                    </tr>
                @endforeach

                <tr class="motif-baris-total">
                    <td colspan="3" class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">Total</td>
                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                        {{ number_format($totalKk, 0, ',', '.') }} KK</td>
                    <td></td>
                </tr>
            </x-sim.tabel-ringkas>
        @else
            <div class="p-8 text-center sm:p-12">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.75c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M15 21v-3.75c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
                <h3 class="mt-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                    Belum ada Satuan Permukiman
                </h3>
                <p class="mx-auto mt-1 max-w-md text-theme-xs text-gray-500 dark:text-gray-400">
                    Satuan Permukiman yang didaftarkan dan dihubungkan ke kawasan ini akan otomatis muncul pada tabel sebaran ini.
                </p>
                <div class="mt-4">
                    <a href="{{ route('sp.index') }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-theme-xs font-medium text-gray-700 shadow-2xs hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                        Buka Manajemen SP &rarr;
                    </a>
                </div>
            </div>
        @endif
    </div>

    <x-sim.modal-form nama="formTambahKawasan" judul="Tambah Kawasan Transmigrasi"
        keterangan="Kawasan ditetapkan lewat SK dan dapat mencakup beberapa kecamatan."
        :aksi="route('kawasan.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.sp.form-kawasan', ['awalan' => 'tambah'])
    </x-sim.modal-form>

    <x-sim.modal-form nama="formUbahKawasanBaris" judul="Ubah Kawasan Transmigrasi"
        keterangan="Perubahan tercatat pada audit log."
        pola-aksi="/kawasan/:id" metode="PUT" ukuran="lg" label-simpan="Simpan Perubahan">
        @include('pages.sp.form-kawasan', ['awalan' => 'ubahBaris'])
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="hapusKawasan" judul="Hapus kawasan transmigrasi ini?"
        pesan="Seluruh satuan permukiman di dalamnya ikut kehilangan induknya." label-setuju="Hapus" />
@endsection
