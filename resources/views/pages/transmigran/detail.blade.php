{{--
    Rincian satu transmigran.

    Memakai pola dua kolom asimetris seperti halaman rincian SP: ringkasan
    entitas menetap di kiri, tab konten di kanan (agents/ui-spec.md bagian 2.2).

    Badge verifikasi tampil di kolom kiri, dan alasan penolakan ditulis lengkap,
    bukan hanya sebagai tooltip, agar operator tahu persis bagian mana yang
    perlu diperbaiki (agents/ui-spec.md bagian 6.6).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $statusVerifikasi = \App\Enums\StatusVerifikasi::from($data['status_verifikasi']);
        $statusTinggal = \App\Enums\StatusTinggal::from($data['status_tinggal']);

        // Data terkait, disaring dari penyedia data contoh menurut nama pemilik.
        // Saat backend siap, penyaringan berpindah ke relasi Eloquent.
        $rumah = collect(DummyData::rumah())
            ->firstWhere('penghuni', $data['nama_kepala_keluarga']);

        $lahan = array_values(array_filter(
            DummyData::lahan(),
            fn ($l) => $l['pemilik'] === $data['nama_kepala_keluarga']
        ));

        $panen = array_values(array_filter(
            DummyData::hasilPanen(),
            fn ($p) => $p['petani'] === $data['nama_kepala_keluarga']
        ));

        $totalLuas = array_sum(array_column($lahan, 'luas'));

        $bolehUbah = true;
        $bolehVerifikasi = true;
    @endphp

    <x-sim.page-header :judul="$data['nama_kepala_keluarga']"
        :keterangan="'Kepala keluarga di ' . $data['satuan_permukiman'] . ', datang tahun ' . $data['tahun_kedatangan'] . '.'"
        :remah="[
            ['label' => 'Kependudukan'],
            ['label' => 'Transmigran', 'url' => route('transmigran.index')],
            ['label' => $data['nama_kepala_keluarga']],
        ]">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahTransmigran')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                    </svg>
                    Ubah Data Transmigran
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    {{--
        Alasan penolakan ditulis penuh di bagian paling atas, karena inilah
        satu-satunya petunjuk perbaikan bagi operator (rules.md bagian 5.2 poin 7).
    --}}
    @if ($statusVerifikasi === \App\Enums\StatusVerifikasi::Ditolak && ! empty($data['catatan_verifikasi']))
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-300 bg-red-50 p-4 dark:border-red-500/30 dark:bg-red-500/10"
            role="alert">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <div>
                <p class="text-theme-sm font-semibold text-red-800 dark:text-red-200">Data ditolak saat diperiksa</p>
                <p class="mt-1 text-theme-sm text-red-700 dark:text-red-300">{{ $data['catatan_verifikasi'] }}</p>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        {{-- Kolom kiri: ringkasan yang menetap --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-500 text-theme-lg font-bold text-white"
                        aria-hidden="true">
                        {{ DummyData::inisial($data['nama_kepala_keluarga']) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                            {{ $data['nama_kepala_keluarga'] }}
                        </p>
                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $data['pekerjaan_kepala_keluarga'] }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <x-sim.status-badge :status="$statusVerifikasi" :catatan="$data['catatan_verifikasi'] ?? null" />
                    <x-sim.status-badge :status="$statusTinggal" />
                </div>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">NIK</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $data['nik'] }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Nomor KK</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $data['no_kk'] }}
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
                        <dt class="text-gray-500 dark:text-gray-400">Anggota keluarga</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $data['jumlah_anggota_keluarga'] }} orang
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Telepon</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $data['telepon'] ?? '-' }}
                        </dd>
                    </div>
                </dl>

                {{-- Tindakan verifikasi, hanya dirender bila pengguna berizin --}}
                @if ($bolehVerifikasi)
                    <div class="mt-5 space-y-2 border-t border-gray-200 pt-5 dark:border-gray-800">
                        <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                            Tindakan pemeriksaan data
                        </p>
                        <form method="POST" action="{{ route('transmigran.verifikasi', $data['id_transmigran']) }}">
                            @csrf
                            <button type="submit"
                                class="w-full rounded-lg border border-green-300 px-4 py-2.5 text-theme-sm font-medium text-green-700 transition hover:bg-green-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-green-500/40 dark:text-green-400 dark:hover:bg-green-500/10">
                                Tandai Terverifikasi
                            </button>
                        </form>
                        <button type="button"
                            @click="$dispatch('buka-konfirmasi', {
                                nama: 'tolakTransmigran',
                                aksi: '{{ route('transmigran.tolak', $data['id_transmigran']) }}'
                            })"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Tolak dengan Alasan
                        </button>
                    </div>
                @endif
            </div>
        </aside>

        {{-- Kolom kanan: tab rincian --}}
        <div x-data="hashTabs('biodata')" class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian data transmigran">
                    @foreach ([
                        'biodata' => 'Biodata',
                        'rumah' => 'Rumah',
                        'lahan' => 'Lahan (' . count($lahan) . ')',
                        'panen' => 'Hasil Panen (' . count($panen) . ')',
                        'dokumen' => 'Dokumen',
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

                {{-- Biodata --}}
                <div x-show="tab === 'biodata'" role="tabpanel" class="p-5 sm:p-6">
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        @foreach ([
                            'Jenis kelamin' => $data['jenis_kelamin'] ?? null,
                            'Tempat lahir' => $data['tempat_lahir'] ?? null,
                            'Tanggal lahir' => isset($data['tanggal_lahir'])
                                ? \Illuminate\Support\Carbon::parse($data['tanggal_lahir'])->translatedFormat('d F Y')
                                : null,
                            'Pendidikan terakhir' => $data['pendidikan_terakhir'] ?? null,
                            'Pekerjaan' => $data['pekerjaan_kepala_keluarga'] ?? null,
                            'Daerah asal' => $data['daerah_asal'] ?? null,
                            'Tahun kedatangan' => $data['tahun_kedatangan'] ?? null,
                            'Anggota kelompok tani' => $data['status_anggota_poktan'] ?? null,
                        ] as $label => $nilai)
                            <div>
                                <dt class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                                    {{ $nilai !== null && $nilai !== '' ? $nilai : '-' }}
                                </dd>
                            </div>
                        @endforeach

                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Pendapatan per bulan</dt>
                            <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                @if (! empty($data['pendapatan_per_bulan']))
                                    Rp {{ number_format($data['pendapatan_per_bulan'], 0, ',', '.') }}
                                @else
                                    -
                                @endif
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

                {{-- Rumah --}}
                <div x-show="tab === 'rumah'" x-cloak role="tabpanel">
                    @if ($rumah === null)
                        <x-sim.empty-state judul="Belum menempati rumah"
                            pesan="Keluarga ini belum ditautkan ke rumah mana pun. Penautan dilakukan dari halaman Rumah dan Hunian." />
                    @else
                        <div class="p-5 sm:p-6">
                            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Nomor rumah</dt>
                                    <dd class="mt-0.5">
                                        <a href="{{ route('rumah.detail', $rumah['id_rumah']) }}"
                                            class="rounded text-theme-sm text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                            {{ $rumah['no_rumah'] }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Tahun pembangunan</dt>
                                    <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                        {{ $rumah['tahun_pembangunan'] }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Luas bangunan</dt>
                                    <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                        {{ number_format($rumah['luas_bangunan'], 2, ',', '.') }} m<sup>2</sup>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Koordinat</dt>
                                    <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                        {{ number_format($rumah['lintang'], 6, '.', '') }},
                                        {{ number_format($rumah['bujur'], 6, '.', '') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Kondisi rumah</dt>
                                    <dd class="mt-1">
                                        <x-sim.status-badge
                                            :status="\App\Enums\KondisiRumah::from($rumah['kondisi'])" />
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Status hunian</dt>
                                    <dd class="mt-1">
                                        <x-sim.status-badge
                                            :status="\App\Enums\StatusHunian::from($rumah['status_hunian'])" />
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    @endif
                </div>

                {{-- Lahan --}}
                <div x-show="tab === 'lahan'" x-cloak role="tabpanel">
                    @if (empty($lahan))
                        <x-sim.empty-state judul="Belum ada data lahan"
                            pesan="Lahan pekarangan dan lahan usaha keluarga ini akan tampil di sini setelah didata." />
                    @else
                        <x-sim.tabel-ringkas :kolom="['Kode', 'Jenis', 'Kategori', 'Luas (ha)', 'Status']">
                            @foreach ($lahan as $l)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('lahan.detail', $l['id_lahan']) }}"
                                            class="rounded text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                            {{ $l['kode_lahan'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $l['jenis_lahan'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $l['kategori_lahan'] ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($l['luas'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-sim.status-badge
                                            :status="\App\Enums\StatusVerifikasi::from($l['status_verifikasi'])" />
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Baris total memakai motif identitas garis atas navy --}}
                            <tr class="motif-baris-total">
                                <td colspan="3" class="px-5 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    Total luas lahan
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($totalLuas, 2, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{-- Hasil panen --}}
                <div x-show="tab === 'panen'" x-cloak role="tabpanel">
                    @if (empty($panen))
                        <x-sim.empty-state judul="Belum ada catatan panen"
                            pesan="Hasil panen keluarga ini akan tampil di sini setelah dicatat petugas." />
                    @else
                        <x-sim.tabel-ringkas :kolom="['Komoditas', 'Musim Tanam', 'Tanggal Panen', 'Volume', 'Kualitas']">
                            @foreach ($panen as $p)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('panen.detail', $p['id_hasil_panen']) }}"
                                            class="rounded text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                            {{ $p['komoditas'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $p['musim_tanam'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ \Illuminate\Support\Carbon::parse($p['tanggal_panen'])->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($p['volume'], 3, ',', '.') }} {{ $p['satuan'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $p['kualitas'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{-- Dokumen --}}
                <div x-show="tab === 'dokumen'" x-cloak role="tabpanel">
                    @if (empty($data['dokumen_pendukung']))
                        <x-sim.empty-state judul="Belum ada dokumen"
                            pesan="Kartu keluarga, KTP, atau SK penempatan dapat diunggah lewat tombol Ubah Data Transmigran." />
                    @else
                        <div class="p-5 sm:p-6">
                                {{--
                                    Menaut lewat rute dokumen, bukan path penyimpanan. Berkas berada
                                    di luar folder public, sehingga path mentah tidak dapat dibuka
                                    peramban sekaligus melewati pemeriksaan izin.
                                --}}
                                <a href="{{ route('dokumen.tampilkan', ['modul' => 'transmigran', 'id' => $data['id_transmigran'], 'namaBerkas' => basename($data['dokumen_pendukung'])]) }}"
                                    target="_blank" rel="noopener"
                                class="flex items-center gap-3 rounded-xl border border-gray-200 p-3 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-800 dark:hover:bg-white/5">
                                <span
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-red-50 dark:bg-red-500/15">
                                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                                        aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ basename($data['dokumen_pendukung']) }}
                                    </span>
                                    <span class="block text-theme-xs text-gray-500 dark:text-gray-400">
                                        Klik untuk membuka dokumen
                                    </span>
                                </span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal ubah data, terisi nilai yang sedang berlaku --}}
    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahTransmigran" judul="Ubah Data Transmigran"
            keterangan="Data yang sudah terverifikasi akan kembali menunggu pemeriksaan setelah diubah."
            :aksi="route('transmigran.perbarui', $data['id_transmigran'])" metode="PUT" ukuran="xl"
            label-simpan="Simpan Perubahan" :boleh-verifikasi="$bolehVerifikasi">
            @include('pages.transmigran.form', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif

    {{-- Penolakan verifikasi wajib menyertakan alasan --}}
    @if ($bolehVerifikasi)
        <x-sim.confirm-dialog nama="tolakTransmigran" judul="Tolak verifikasi data ini?"
            pesan="Operator akan melihat alasan penolakan agar dapat memperbaiki datanya."
            label-setuju="Tolak Data Transmigran" ragam="peringatan" metode="POST" :perlu-alasan="true"
            label-alasan="Alasan penolakan" />
    @endif
@endsection
