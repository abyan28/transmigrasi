{{--
    Rincian satu lahan beserta dokumen status lahannya.

    Dokumen HPL dan SHM dikelola di sini, bukan di dalam form lahan, karena
    satu lahan dapat memiliki lebih dari satu dokumen
    (agents/data-dictionary.md bagian 7.2).

    Bagian pengelolaan (pola tanam, peralatan, kendala) hanya ditampilkan bila
    lahan berjenis Lahan Usaha, mengikuti aturan bahwa keempat kolom itu tidak
    berlaku untuk lahan pekarangan.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $dokumen = DummyData::dokumenLahan($data['id_lahan']);
        $lahanUsaha = $data['jenis_lahan'] === 'Lahan Usaha';

        $pemilik = collect(DummyData::transmigran())
            ->firstWhere('nama_kepala_keluarga', $data['pemilik']);

        $bolehUbah = true;
    @endphp

    <x-sim.page-header :judul="'Lahan ' . $data['kode_lahan']"
        :keterangan="$data['jenis_lahan'] . ' seluas ' . number_format($data['luas'], 2, ',', '.') . ' hektare di ' . $data['satuan_permukiman'] . '.'"
        :remah="[
            ['label' => 'Lahan'],
            ['label' => 'Daftar Lahan', 'url' => route('lahan.index')],
            ['label' => $data['kode_lahan']],
        ]">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahLahan')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                    </svg>
                    Ubah Data Lahan
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        {{-- Kolom kiri: ringkasan lahan --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                    {{ $data['kode_lahan'] }}
                </h2>
                <p class="mt-1 text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                    {{ number_format($data['luas'], 2, ',', '.') }}
                    <span class="text-theme-sm font-normal text-gray-500 dark:text-gray-400">ha</span>
                </p>

                <div class="mt-3 flex flex-wrap gap-2">
                    <x-sim.status-badge :teks="$data['jenis_lahan']"
                        :warna="$lahanUsaha ? 'teal' : 'gray'" />
                </div>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Pemilik</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            @if ($pemilik)
                                <a href="{{ route('transmigran.detail', $pemilik['id_transmigran']) }}"
                                    class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                    {{ $data['pemilik'] }}
                                </a>
                            @else
                                {{ $data['pemilik'] }}
                            @endif
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
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Kategori</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            {{ $data['kategori_lahan'] ?? '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Status kepemilikan</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            {{ $data['status_kepemilikan'] ?? '-' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </aside>

        {{-- Kolom kanan: tab rincian --}}
        <div x-data="hashTabs('rincian')" class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian lahan">
                    @php
                        $tab = ['rincian' => 'Rincian'];
                        if ($lahanUsaha) {
                            $tab['pengelolaan'] = 'Pengelolaan';
                        }
                        $tab['dokumen'] = 'Dokumen (' . count($dokumen) . ')';
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

                {{-- Rincian --}}
                <div x-show="tab === 'rincian'" role="tabpanel" class="p-5 sm:p-6">
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Luas lahan</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($data['luas'], 2, ',', '.') }} ha
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Koordinat</dt>
                        <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            @if (! empty($data['lintang']))
                                {{ number_format($data['lintang'], 6, '.', '') }},
                                {{ number_format($data['bujur'], 6, '.', '') }}
                                <x-sim.tautan-peta class="mt-1.5" :lintang="$data['lintang']"
                                    :bujur="$data['bujur']" :label="$data['kode_lahan']" />
                            @else
                                -
                            @endif
                        </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Tujuan pemanfaatan</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                {{ $data['tujuan_pemanfaatan'] ?? '-' }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Keterangan</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                {{ $data['keterangan'] ?? '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Pengelolaan, hanya untuk lahan usaha --}}
                @if ($lahanUsaha)
                    <div x-show="tab === 'pengelolaan'" x-cloak role="tabpanel" class="p-5 sm:p-6">
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Pola tanam</dt>
                                <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                    {{ $data['pola_tanam'] ?? '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Peralatan pertanian</dt>
                                <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                    {{ $data['peralatan_pertanian'] ?? '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Kendala yang dihadapi</dt>
                                <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                    {{ $data['kendala'] ?? '-' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                @endif

                {{-- Dokumen status lahan --}}
                <div x-show="tab === 'dokumen'" x-cloak role="tabpanel">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-5 dark:border-gray-800">
                        {{--
                            Penjelasan mengapa dokumen lahan diunggah di sini, bukan menyatu
                            pada form lahan seperti modul lain. Tanpa keterangan ini,
                            pemisahannya tampak sebagai ketidakkonsistenan.
                        --}}
                        <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                            Diunggah terpisah dari form lahan, sebab satu lahan dapat memiliki HPL dan SHM
                            sekaligus, masing-masing dengan nomor dan tanggal terbitnya sendiri.
                        </p>
                        @if ($bolehUbah)
                            <button type="button" @click="$dispatch('buka-modal', 'formDokumenLahan')"
                                class="rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                Tambah Dokumen Lahan
                            </button>
                        @endif
                    </div>

                    @if (empty($dokumen))
                        <x-sim.empty-state judul="Belum ada dokumen lahan"
                            pesan="Dokumen HPL, SHM, atau surat keterangan desa dapat diunggah lewat tombol Tambah Dokumen Lahan." />
                    @else
                        <x-sim.tabel-ringkas :kolom="['Jenis', 'Nomor Dokumen', 'Tanggal Terbit', 'Berkas']">
                            @foreach ($dokumen as $d)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ $d['jenis_dokumen'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $d['nomor_dokumen'] ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        @if ($d['tanggal_terbit'])
                                            {{ \Illuminate\Support\Carbon::parse($d['tanggal_terbit'])->translatedFormat('d F Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                        <td class="px-5 py-3">
                                            <x-sim.tautan-dokumen modul="lahan" :id="$data['id_lahan']"
                                                :berkas="$d['file_dokumen']" />
                                        </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="lahan" :record-id="$data['id_lahan']" />
                </div>
            </div>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahLahan" judul="Ubah Data Lahan"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('lahan.perbarui', $data['id_lahan'])" metode="PUT" ukuran="xl"
            label-simpan="Simpan Perubahan">
            @include('pages.lahan.form', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>

        {{-- Modal unggah dokumen lahan, terpisah dari form lahan --}}
        <x-sim.modal-form nama="formDokumenLahan" judul="Tambah Dokumen Lahan"
            :keterangan="'Dokumen untuk lahan ' . $data['kode_lahan'] . '.'"
            :aksi="route('lahan.dokumen.simpan', $data['id_lahan'])" ukuran="lg"
            label-simpan="Simpan Dokumen Lahan">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="dok_jenis" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                        Jenis Dokumen<span class="text-error-500">*</span>
                    </label>
                    <select id="dok_jenis" name="jenis_dokumen" required
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        @foreach (\App\Enums\JenisDokumenLahan::opsi() as $nilai => $label)
                            <option value="{{ $nilai }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="dok_nomor" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                        Nomor Dokumen
                    </label>
                    <input type="text" id="dok_nomor" name="nomor_dokumen" maxlength="100"
                        placeholder="Contoh: HPL/NTT/2016/0142"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90" />
                </div>

                <div>
                    <label for="dok_tanggal" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                        Tanggal Terbit
                    </label>
                    <input type="date" id="dok_tanggal" name="tanggal_terbit" max="{{ date('Y-m-d') }}"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90" />
                </div>

                <div class="sm:col-span-2">
                    <x-sim.file-upload nama="file_dokumen" label="Berkas Dokumen" :wajib="true"
                        nama-dokumen="Dokumen Lahan" :nama-pemilik="$data['pemilik']"
                        keterangan="Unggah hasil pindaian dokumen asli." />
                </div>

                <div class="sm:col-span-2">
                    <label for="dok_keterangan"
                        class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                        Keterangan
                    </label>
                    <textarea id="dok_keterangan" name="keterangan" rows="2"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90"></textarea>
                </div>
            </div>
        </x-sim.modal-form>
    @endif
@endsection
