{{--
    Rincian satu penanaman.

    Halaman ini dibuat 2026-08-20 agar penanaman memiliki tab Catatan Log
    seperti modul lain.

    SATU BARIS ADALAH SATU PENANAMAN pada satu lahan dan satu komoditas. Hasil
    panen menaut ke baris inilah, bukan ke lahan secara terpisah (kamus data
    9.2), sehingga panen yang tercatat ditampilkan di sini sebagai kelanjutan
    penanamannya.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        // Panen dari penanaman ini, dibaca lewat relasi `penanaman_id`.
        //
        // Sebelumnya dicocokkan lewat pasangan komoditas dan petani, sebab
        // hasil panen belum menyimpan tautannya. Pencocokan teks semacam itu
        // menyatukan dua penanaman berbeda yang kebetulan sama komoditas dan
        // penggarapnya, sehingga volumenya terhitung dua kali.
        $panen = array_values(array_filter(
            DummyData::hasilPanen(),
            fn ($p) => ($p['penanaman_id'] ?? null) === $data['id_penanaman'],
        ));

        $volume = array_sum(array_column($panen, 'volume'));

        // Kekuatan poktan pada saat halaman dibuka. Dihitung, bukan disimpan,
        // sehingga selalu mengikuti keanggotaan dan lahan terbaru.
        $rekapPoktan = DummyData::rekapLahanPoktan($data['poktan_id']);

        $benih = $data['saprotan_id']
            ? collect(DummyData::saprotan())->firstWhere('id_saprotan', $data['saprotan_id'])
            : null;

        $judul = $data['komoditas'] . ' - ' . $data['poktan'];

        $bolehUbah = true;
    @endphp

    <x-sim.page-header :judul="$judul"
        :keterangan="'Ditanam ' . \Illuminate\Support\Carbon::parse($data['periode_tanam'] . '-01')->translatedFormat('d F Y') . ' di ' . $data['satuan_permukiman'] . '.'"
        :remah="\App\Helpers\RemahHelper::untuk('/penanaman', $judul)">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahPenanaman')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                    </svg>
                    Ubah Penanaman
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
                    {{ number_format($data['realisasi_tanam'], 2, ',', '.') }}
                    <span class="text-theme-sm font-normal text-gray-500 dark:text-gray-400">ha ditanam</span>
                </p>

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
                    {{--
                        Dua angka TERHITUNG, bukan tersimpan. Keduanya selalu
                        mengikuti keanggotaan dan lahan terbaru, sehingga
                        halaman ini tidak pernah menampilkan angka basi.
                    --}}
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Jumlah anggota</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $rekapPoktan['jumlah_anggota'] }} orang
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Luas lahan kelompok</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($rekapPoktan['luas_total'], 2, ',', '.') }} ha
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
                </dl>
            </div>
        </aside>

        <div x-data="hashTabs('rincian')" class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian penanaman">
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
                                {{ \Illuminate\Support\Carbon::parse($data['periode_tanam'] . '-01')->translatedFormat('d F Y') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Realisasi tanam</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($data['realisasi_tanam'], 2, ',', '.') }} ha
                                <span class="text-theme-xs text-gray-500 dark:text-gray-400">
                                    dari {{ number_format($rekapPoktan['luas_total'], 2, ',', '.') }} ha lahan kelompok
                                </span>
                            </dd>
                        </div>
                        {{--
                            Benih yang dipakai. Boleh kosong, dan kekosongan itu
                            keadaan yang sah: bibit swadaya anggota memang tidak
                            pernah masuk modul saprotan.
                        --}}
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Benih dipakai</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                @if ($benih)
                                    <a href="{{ route('saprotan.detail', $benih['id_saprotan']) }}"
                                        class="rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                        {{ $benih['nama'] }}
                                    </a>
                                    <span class="tabular-nums text-gray-500 dark:text-gray-400">
                                        &middot; {{ rtrim(rtrim(number_format($data['volume_benih'], 2, ',', '.'), '0'), ',') }}
                                        {{ $benih['satuan'] }}
                                    </span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">
                                        Tanpa benih tercatat, ditanam dari bibit swadaya.
                                    </span>
                                @endif
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
                        <x-sim.tabel-ringkas :kolom="['Periode Panen', 'Hasil Panen', 'Produksi', 'Harga Jual']">
                            @foreach ($panen as $p)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                                        {{ \Illuminate\Support\Carbon::parse($p['periode_panen'] . '-01')->translatedFormat('F Y') }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($p['produksi'], 2, ',', '.') }} {{ $p['satuan'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ number_format($p['realisasi_panen'], 2, ',', '.') }} ha</td>
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
                    <x-sim.catatan-log nama-tabel="penanaman" :record-id="$data['id_penanaman']" />
                </div>
            </div>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahPenanaman" judul="Ubah Penanaman"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('penanaman.perbarui', $data['id_penanaman'])" metode="PUT" ukuran="lg"
            label-simpan="Simpan Perubahan">
            @include('pages.komoditas.form-penanaman', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif
@endsection
