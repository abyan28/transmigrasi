{{--
    Rincian satu catatan hasil panen.

    Menampilkan volume dalam satuan aslinya beserta setara tonnya, agar
    operator melihat keduanya sekaligus: angka yang ia catat di lapangan, dan
    angka yang dipakai sistem saat merekap.
--}}
@extends('layouts.app')

@section('content')
    @php
        // `$setaraTon` dan `$tanam` datang dari rute `panen.detail`.
        $nilaiJual = ($data['harga_jual'] ?? 0) * $data['produksi'];

        // Gagal total: seluruh luas puso, tidak ada yang dipanen. Keadaan sah,
        // dan pada keadaan itu produktivitas memang nol.
        $gagalTotal = (float) $data['realisasi_panen'] === 0.0 && (float) ($data['puso'] ?? 0) > 0;

        $bolehUbah = true;
    @endphp

    <x-sim.page-header :judul="'Panen ' . $data['komoditas']"
        :keterangan="'Dipanen ' . \Illuminate\Support\Carbon::parse($data['periode_panen'] . '-01')->translatedFormat('F Y') . ' oleh ' . $data['poktan'] . '.'"
        :remah="\App\Helpers\RemahHelper::untuk('/panen', $data['komoditas'])">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahPanen')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                    </svg>
                    Ubah Catatan Panen
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <p class="motif-judul-kartu text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Produksi
                </p>
                <p class="mt-1 text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                    {{ number_format($data['produksi'], 3, ',', '.') }}
                    <span class="text-theme-sm font-normal text-gray-500 dark:text-gray-400">{{ $data['satuan'] }}</span>
                </p>
                @if ($data['satuan'] !== 'Ton')
                    <p class="mt-1 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                        Setara {{ number_format($setaraTon, 3, ',', '.') }} ton saat direkap
                    </p>
                @endif

                {{--
                    Diisi 2026-08-24. Sebelumnya div ini KOSONG, sisa kualitas
                    panen yang dicabut 2026-08-22 dan tidak ikut dibersihkan.

                    Status yang ditandai milik PENANAMAN induknya, karena itu
                    diberi keterangan; tanpa itu ia terbaca sebagai "catatan
                    panen ini belum lengkap".
                --}}
                <div class="mt-4">
                    @if ($gagalTotal)
                        <x-sim.status-badge teks="Gagal Total" warna="error" />
                        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            Seluruh {{ number_format($data['puso'], 2, ',', '.') }} ha puso, tidak ada yang dipanen
                        </p>
                    @elseif (($data['puso'] ?? 0) > 0)
                        <x-sim.status-badge teks="Panen Sebagian Gagal" warna="warning" />
                        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ number_format($data['puso'], 2, ',', '.') }} ha puso dari
                            {{ $tanam ? number_format($tanam['realisasi_tanam'], 2, ',', '.') : '-' }} ha yang ditanam
                        </p>
                    @else
                        <x-sim.status-badge teks="Panen Penuh" warna="success" />
                        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            Seluruh luas yang ditanam berhasil dipanen
                        </p>
                    @endif
                </div>
                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Kelompok tani</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            <a href="{{ route('poktan.detail', $data['poktan_id']) }}"
                                class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                {{ $data['poktan'] }}
                            </a>
                        </dd>
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
                    {{--
                        Tautan balik ke penanamannya. Kualitas panen dicabut
                        2026-08-22 dan digantikan rincian ini: penanaman asal
                        jauh lebih berguna ditelusuri daripada satu label mutu.
                    --}}
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Penanaman</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            @if ($tanam)
                                <a href="{{ route('penanaman.detail', $tanam['id_penanaman']) }}"
                                    class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                    {{ \Illuminate\Support\Carbon::parse($tanam['periode_tanam'] . '-01')->translatedFormat('M Y') }}
                                </a>
                            @else
                                &mdash;
                            @endif
                        </dd>
                    </div>

                </dl>
            </div>
        </aside>

        {{-- Kolom kanan: tab rincian --}}
        <div x-data="hashTabs('rincian')" class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian hasil panen">
                    @foreach ([
                        'rincian' => 'Rincian Panen',
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

                {{-- Rincian panen --}}
                <div x-show="tab === 'rincian'" role="tabpanel" class="p-5 sm:p-6">
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Periode panen</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                {{ \Illuminate\Support\Carbon::parse($data['periode_panen'] . '-01')->translatedFormat('F Y') }}
                            </dd>
                        </div>
                        {{--
                            DUA angka luas yang saling menentukan:
                            realisasi panen + puso = realisasi tanam.

                            Suku "belum dipanen" dicabut 2026-08-24 bersama
                            panen bertahap: satu panen selalu menutup seluruh
                            luas yang ditanam.

                            Realisasi tanam ikut ditampilkan sebagai PENYEBUTNYA.
                            Tanpa angka itu keduanya melayang tanpa acuan, dan
                            pembaca tidak dapat memeriksa identitasnya sendiri.
                        --}}
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Realisasi tanam</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ $tanam ? number_format($tanam['realisasi_tanam'], 2, ',', '.') . ' ha' : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Realisasi panen</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($data['realisasi_panen'], 2, ',', '.') }} ha
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Puso</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums {{ ($data['puso'] ?? 0) > 0 ? 'font-medium text-error-500' : 'text-gray-800 dark:text-white/90' }}">
                                {{ number_format($data['puso'] ?? 0, 2, ',', '.') }} ha
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Produktivitas</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ rtrim(rtrim(number_format($data['produktivitas'], 3, ',', '.'), '0'), ',') }}
                                {{ $data['satuan'] }}/ha
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Harga jual per satuan</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                @if (! empty($data['harga_jual']))
                                    Rp {{ number_format($data['harga_jual'], 0, ',', '.') }} per {{ $data['satuan'] }}
                                @else
                                    -
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Perkiraan nilai jual</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                @if ($nilaiJual > 0)
                                    Rp {{ number_format($nilaiJual, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                        {{--
                            "Setara ton" DIHAPUS dari sini 2026-08-24, sebab
                            kartu ringkasan di kiri sudah mencetaknya. Dua
                            tempat untuk satu angka membuat pembaca mengira
                            keduanya berbeda dan mencari-cari bedanya.
                        --}}
                        <div class="sm:col-span-2">
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Catatan</dt>
                            <dd class="mt-0.5 text-theme-sm leading-relaxed text-gray-800 dark:text-white/90">
                                {{ $data['keterangan'] ?? 'Tidak ada catatan tambahan.' }}
                            </dd>
                        </div>
                        {{--
                            Isian unggahan sudah ada di form sejak 2026-08-22,
                            tetapi kolomnya tidak pernah ada di data dan
                            tautannya tidak pernah dibuat. Petugas mengunggah
                            berita acara lalu tidak menemukan cara membacanya.
                        --}}
                        <div class="sm:col-span-2">
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Dokumen atau foto panen</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                <x-sim.tautan-dokumen modul="panen" :id="$data['id_hasil_panen']"
                                    :berkas="$data['dokumen_pendukung'] ?? null" />
                            </dd>
                        </div>
                    </dl>

                    <p class="mt-6 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                        Volume disimpan apa adanya sesuai satuan baku komoditas.
                        Konversi ke ton hanya dilakukan saat rekap, sehingga angka asli lapangan tetap terjaga.
                    </p>
                </div>


                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="hasil_panen" :record-id="$data['id_hasil_panen']" />
                </div>
            </div>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahPanen" judul="Ubah Catatan Panen"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('panen.perbarui', $data['id_hasil_panen'])" metode="PUT" ukuran="xl"
            label-simpan="Simpan Perubahan">
            @include('pages.panen.form', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif
@endsection
