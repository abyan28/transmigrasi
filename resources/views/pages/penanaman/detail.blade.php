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
        // $panen, $produksiTon, $luasDipanen, $luasPuso, $status,
        // $belumDitanam, $rekapPoktan, dan $benih datang dari rute
        // `penanaman.detail`. Lihat routes/web.php.
        $judul = $data['komoditas'] . ' - ' . $data['poktan'];

        $bolehUbah = true;
    @endphp

    {{--
        Bulan saja, TANPA tanggal. Sebelumnya tercetak "01 November 2025", dan
        angka 01 itu presisi palsu: ia berasal dari imbuhan '-01' yang dipakai
        Carbon membaca CHAR(7), bukan dari pendataan. Justru itulah yang hendak
        dihindari keputusan bulan-saja (rules.md 7d.9).
    --}}
    <x-sim.page-header :judul="$judul"
        :keterangan="'Ditanam ' . \Illuminate\Support\Carbon::parse($data['periode_tanam'] . '-01')->translatedFormat('F Y') . ' di ' . $data['satuan_permukiman'] . '.'"
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

                {{-- Status, agar terbaca tanpa membuka tab panen --}}
                <div class="mt-4">
                    <x-sim.status-badge :status="$status" />
                    @if ($status === \App\Enums\StatusPanen::BelumDipanen)
                        <p class="mt-1.5 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                            Seluruh {{ number_format($data['realisasi_tanam'], 2, ',', '.') }} ha masih berdiri tanaman
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
                        {{-- Label "Tanggal tanam" diganti 2026-08-24: kolomnya
                             CHAR(7) berisi bulan, dan halaman daftar sudah
                             memakai istilah "Periode Tanam". --}}
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Periode tanam</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ \Illuminate\Support\Carbon::parse($data['periode_tanam'] . '-01')->translatedFormat('F Y') }}
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
                            Belum Ditanam ada di form tetapi tidak pernah tampil
                            di halaman rincian, padahal angka inilah yang
                            menentukan apakah kelompok masih dapat menanam lagi.

                            Milik POKTAN, bukan milik penanaman ini: ia sisa
                            lahan kelompok setelah dikurangi seluruh penanaman
                            yang belum dipanen.
                        --}}
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Belum ditanam</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($belumDitanam, 2, ',', '.') }} ha
                                <span class="text-theme-xs text-gray-500 dark:text-gray-400">
                                    sisa lahan kelompok yang masih dapat ditanami
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
                        {{--
                            Unggahan yang tidak punya jalan dibuka adalah
                            kontrol mati (ui-spec.md R-26 dan 780): petugas
                            mengunggah berita acara tanam lalu tidak menemukan
                            cara membacanya. Berkasnya sudah ada di data sejak
                            2026-08-22, hanya tautannya yang belum pernah dibuat.
                        --}}
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Dokumen atau foto penanaman</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                <x-sim.tautan-dokumen modul="penanaman" :id="$data['id_penanaman']"
                                    :berkas="$data['dokumen_pendukung'] ?? null" />
                            </dd>
                        </div>
                    </dl>
                </div>

                <div x-show="tab === 'panen'" x-cloak role="tabpanel">
                    @if (empty($panen))
                        <x-sim.empty-state judul="Belum ada panen tercatat"
                            pesan="Hasil panen dari penanaman ini akan tampil di sini setelah dicatat." />
                    @else
                        {{--
                            URUTAN KOLOM DIPERBAIKI 2026-08-24. Sebelumnya
                            header berbunyi "Realisasi Panen" lalu "Produksi",
                            sedangkan selnya mencetak produksi lalu luas panen:
                            angka ton tampil di bawah judul hektare. Header dan
                            sel kini sejajar, dan satuannya ikut dicetak agar
                            ketidakcocokan semacam itu terlihat mata.
                        --}}
                        <x-sim.tabel-ringkas :kolom="['Periode Panen', 'Realisasi Panen (ha)', 'Puso (ha)', 'Produksi', 'Harga Jual']">
                            @foreach ($panen as $p)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                                        <a href="{{ route('panen.detail', $p['id_hasil_panen']) }}"
                                            class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                            {{ \Illuminate\Support\Carbon::parse($p['periode_panen'] . '-01')->translatedFormat('F Y') }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($p['realisasi_panen'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($p['puso'] ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($p['produksi'], 2, ',', '.') }} {{ $p['satuan'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $p['harga_jual'] ? 'Rp ' . number_format($p['harga_jual'], 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                            @endforeach

                            <tr class="motif-baris-total">
                                <td class="px-5 py-3 text-theme-sm text-gray-700 dark:text-gray-300">Total</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($luasDipanen, 2, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($luasPuso, 2, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($produksiTon, 3, ',', '.') }} ton
                                </td>
                                <td></td>
                            </tr>
                        </x-sim.tabel-ringkas>

                        {{--
                            Identitas luas ditampilkan terang-terangan agar
                            petugas dapat memeriksanya sendiri tanpa menghitung.

                            DUA SUKU sejak 2026-08-24. Suku "belum dipanen"
                            dicabut bersama panen bertahap: satu panen selalu
                            menutup seluruh luas yang ditanam, entah sebagai
                            realisasi panen, puso, atau campuran keduanya.
                        --}}
                        <p class="px-5 py-4 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ number_format($luasDipanen, 2, ',', '.') }} ha dipanen
                            + {{ number_format($luasPuso, 2, ',', '.') }} ha puso
                            = {{ number_format($data['realisasi_tanam'], 2, ',', '.') }} ha realisasi tanam.
                        </p>
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
            @include('pages.penanaman.form', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif
@endsection
