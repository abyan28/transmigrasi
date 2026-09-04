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

            <dl class="mt-6 grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Tahun penetapan</dt>
                    <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                        {{ $k['tahun_penetapan'] }}</dd>
                </div>
                <div>
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Nomor SK</dt>
                    <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">{{ $k['nomor_sk'] }}</dd>
                </div>
                <div>
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Luas total</dt>
                    <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                        {{ number_format($k['luas_total'], 2, ',', '.') }} ha</dd>
                </div>
                <div>
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Satuan permukiman</dt>
                    <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                        {{ $k['jumlah_sp'] }} SP di {{ count($kecamatan) }} kecamatan</dd>
                </div>
                {{--
                    Berkas kawasan, ditambahkan 2026-08-25 lalu dijamakkan
                    Putaran 14. Kawasan tidak punya halaman rincian sendiri,
                    sehingga inilah satu-satunya tempat berkasnya dapat dibuka.

                    Dulu hanya SK yang tampil, padahal registry juga memegang
                    HPL (alas hak kawasan) dan peta cakupan: keduanya terunggah
                    tetapi tidak dapat dibuka dari mana pun (R-26).
                --}}
                <div class="sm:col-span-2">
                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Berkas kawasan</dt>
                    <dd class="mt-0.5 space-y-1 text-theme-sm text-gray-800 dark:text-white/90">
                        @forelse ($berkasKawasan[$k['id_kawasan_transmigrasi']] ?? [] as $b)
                            <div>
                                <x-sim.tautan-dokumen modul="kawasan"
                                    :id="$k['id_kawasan_transmigrasi']" :berkas="$b['nama_file']" />
                                <span class="text-theme-xs text-gray-500 dark:text-gray-400">
                                    {{ $b['keterangan'] ?? strtoupper($b['peran']) }}
                                </span>
                            </div>
                        @empty
                            <x-sim.tautan-dokumen modul="kawasan"
                                :id="$k['id_kawasan_transmigrasi']" :berkas="null" />
                        @endforelse
                    </dd>
                </div>
            </dl>

            <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
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
            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Enam SP tersebar di empat kecamatan berbeda. Sebaran inilah alasan kawasan
                dicatat terpisah dari hierarki administratif.
            </p>
        </div>

        <x-sim.tabel-ringkas judul="Satuan permukiman dalam kawasan ini" :kolom="['Satuan Permukiman', 'Desa', 'Kecamatan', 'Kepala Keluarga', 'Rincian']">
            @foreach ($daftarSp as $i => $sp)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                    <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                        {{ $sp['nama'] }}</td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $sp['desa'] }}</td>
                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $sp['kecamatan'] }}</td>
                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                        {{ number_format($rekap[$i]['jumlah_kk'], 0, ',', '.') }} KK</td>
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
