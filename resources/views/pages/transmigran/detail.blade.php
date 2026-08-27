{{--
    Rincian satu transmigran.

    Memakai pola dua kolom asimetris seperti halaman rincian SP: ringkasan
    entitas menetap di kiri, tab konten di kanan (agents/ui-spec.md bagian 2.2).
--}}
@extends('layouts.app')

@section('content')
    @php
        // Seluruh data terkait halaman ini datang dari rute
        // `transmigran.detail`: $rumah, $lahan, $totalLuas, $poktanBernaung,
        // $spPoktan, $riwayatKk, $poktanDiketuai, $keanggotaanIkut, dan
        // $inisial. Lihat routes/web.php.
        $statusTinggal = \App\Enums\StatusTinggal::from($data['status_tinggal']);

        $bolehUbah = true;

        // Suksesi hanya untuk Admin dan Dinas Transmigrasi (rules.md 6 poin 5f).
        // Tahap 3 menggantinya dengan pemeriksaan kewenangan sungguhan.
        $bolehSuksesi = true;
    @endphp

    <x-sim.page-header :judul="$data['nama_kepala_keluarga']"
        :keterangan="'Kepala keluarga di ' . $data['satuan_permukiman'] . ', datang tahun ' . $data['tahun_kedatangan'] . '.'"
        :remah="\App\Helpers\RemahHelper::untuk('/transmigran', $data['nama_kepala_keluarga'])">
        <x-slot:aksi>
            {{--
                Suksesi adalah TINDAKAN TERSENDIRI, bukan efek samping form
                ubah (rules.md 6 poin 5b). Bila ia lahir dari penyuntingan nama
                pada form biasa, setiap perbaikan ejaan akan mengotori riwayat
                suksesi, yaitu kekaburan yang justru hendak ditutup.

                Bergaya sekunder sebab jauh lebih jarang dipakai daripada
                menyunting data biasa.
            --}}
            @if ($bolehSuksesi)
                <button type="button" @click="$dispatch('buka-modal', 'formGantiKepalaKeluarga')"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992V4.356m-4.993 4.992l3.181-3.183a8.25 8.25 0 00-13.803 3.7M4.031 9.865v4.992h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7" />
                    </svg>
                    Ganti Kepala Keluarga
                </button>
            @endif
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

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        {{-- Kolom kiri: ringkasan yang menetap --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-500 text-theme-lg font-bold text-white"
                        aria-hidden="true">
                        {{ $inisial }}
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
                        <dt class="text-gray-500 dark:text-gray-400">Agama</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            {{ $data['agama'] ?? '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Telepon</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $data['telepon'] ?? '-' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </aside>

        {{-- Kolom kanan: tab rincian --}}
        <div x-data="hashTabs('biodata')" class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian data transmigran">
                    @foreach ([
                        'biodata' => 'Biodata',
                        'keluarga' => 'Anggota Keluarga (' . count($anggotaKeluarga) . ')',
                        'rumah' => 'Rumah',
                        'lahan' => 'Lahan (' . count($lahan) . ')',
                        // Tab Hasil Panen dicabut 2026-08-22: panen kini
                        // dicatat per poktan, bukan per orang. Diganti tautan
                        // ke kelompok tempat keluarga ini bernaung.
                        'poktan' => 'Kelompok Tani (' . count($poktanBernaung) . ')',
                        'dokumen' => 'Dokumen',
                        // Catatan Log wajib tetap paling kanan (ui-spec.md 5.1c),
                        // sehingga riwayat suksesi disisipkan sebelum itu.
                        'riwayat-kk' => 'Riwayat Kepala Keluarga (' . count($riwayatKk) . ')',
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

                {{-- Biodata --}}
                <div x-show="tab === 'biodata'" role="tabpanel" class="p-5 sm:p-6">
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        @foreach ([
                            'Jenis kelamin' => $data['jenis_kelamin'] ?? null,
                            'Agama' => $data['agama'] ?? null,
                            'Tempat lahir' => $data['tempat_lahir'] ?? null,
                            'Tanggal lahir' => isset($data['tanggal_lahir'])
                                ? \Illuminate\Support\Carbon::parse($data['tanggal_lahir'])->translatedFormat('d F Y')
                                : null,
                            // Usia DIHITUNG dari tanggal lahir (Rombongan B),
                            // tidak disimpan, sehingga selalu terkini.
                            'Usia' => isset($data['tanggal_lahir'])
                                ? \Illuminate\Support\Carbon::parse($data['tanggal_lahir'])->age . ' tahun'
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

                {{-- Anggota keluarga (Rombongan B, 2026-08-28) --}}
                <div x-show="tab === 'keluarga'" x-cloak role="tabpanel">
                    @if (count($anggotaKeluarga) === 0)
                        <x-sim.empty-state judul="Belum ada anggota keluarga terdata"
                            pesan="Selain kepala keluarga, belum ada istri, suami, anak, atau anggota lain yang dicatat. Tambahkan lewat tombol Ubah Data." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-theme-sm">
                                <caption class="px-5 py-3 text-left text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                                    Anggota keluarga {{ $data['nama_kepala_keluarga'] }} selain kepala keluarga
                                </caption>
                                <thead class="border-y border-gray-200 bg-gray-50 text-theme-xs text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-5 py-3 text-left">Nama</th>
                                        <th scope="col" class="px-5 py-3 text-left">Hubungan</th>
                                        <th scope="col" class="px-5 py-3 text-left">Jenis Kelamin</th>
                                        <th scope="col" class="px-5 py-3 text-left">NIK</th>
                                        <th scope="col" class="px-5 py-3 text-right">Usia</th>
                                        <th scope="col" class="px-5 py-3 text-left">Agama</th>
                                        <th scope="col" class="px-5 py-3 text-left">Kegiatan</th>
                                        <th scope="col" class="px-5 py-3 text-left">Pendidikan</th>
                                        <th scope="col" class="px-5 py-3 text-left">Pekerjaan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($anggotaKeluarga as $a)
                                        <tr class="text-gray-700 dark:text-gray-300">
                                            <td class="px-5 py-3 font-medium text-gray-800 dark:text-white/90">
                                                {{ $a['nama_lengkap'] }}
                                                @if (! empty($a['keterangan']))
                                                    <span class="mt-0.5 block text-theme-xs font-normal text-gray-500 dark:text-gray-400">{{ $a['keterangan'] }}</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3">{{ $a['hubungan'] }}</td>
                                            <td class="px-5 py-3">{{ $a['jenis_kelamin'] ?? '-' }}</td>
                                            <td class="px-5 py-3 tabular-nums">{{ $a['nik'] ?: 'Belum ada' }}</td>
                                            <td class="px-5 py-3 text-right tabular-nums">
                                                {{ ! empty($a['tanggal_lahir']) ? \Illuminate\Support\Carbon::parse($a['tanggal_lahir'])->age . ' th' : '-' }}
                                            </td>
                                            <td class="px-5 py-3">{{ $a['agama'] ?? '-' }}</td>
                                            <td class="px-5 py-3">{{ $a['kegiatan'] ?? '-' }}</td>
                                            <td class="px-5 py-3">{{ $a['pendidikan_terakhir'] ?? '-' }}</td>
                                            <td class="px-5 py-3">
                                                {{ $a['pekerjaan'] ?? '-' }}
                                                @if (! empty($a['pendapatan_per_bulan']))
                                                    <span class="mt-0.5 block text-theme-xs font-normal text-gray-500 dark:text-gray-400">Rp {{ number_format($a['pendapatan_per_bulan'], 0, ',', '.') }} per bulan</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="px-5 py-3 text-theme-xs text-gray-500 dark:text-gray-400">
                            Usia dihitung dari tanggal lahir dan bertambah sendiri tiap tahun.
                        </p>
                    @endif
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
                        {{--
                            Kolom Status dihapus bersama pencabutan status hak
                            atas tanah (2026-08-20). Sebelum itu isinya memang
                            sudah kosong, sehingga tabel ini menjanjikan satu
                            keterangan yang tidak pernah ada.
                        --}}
                        <x-sim.tabel-ringkas judul="Lahan milik keluarga ini" :kolom="['Kode', 'Peruntukan', 'Kering (ha)', 'Basah (ha)', 'Luas (ha)']">
                            @foreach ($lahan as $l)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('lahan.detail', $l['id_lahan']) }}"
                                            class="rounded text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                            {{ $l['kode_lahan'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $l['peruntukan_lahan'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $l['luas_kering'] === null ? '-' : number_format($l['luas_kering'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $l['luas_basah'] === null ? '-' : number_format($l['luas_basah'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($l['luas'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Baris total memakai motif identitas garis atas navy --}}
                            <tr class="motif-baris-total">
                                <td colspan="2" class="px-5 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    Total luas lahan
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format(array_sum(array_column($lahan, 'luas_kering')), 2, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format(array_sum(array_column($lahan, 'luas_basah')), 2, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($totalLuas, 2, ',', '.') }}
                                </td>
                            </tr>
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                {{--
                    Kelompok tani tempat keluarga ini bernaung.

                    MENGGANTIKAN TAB HASIL PANEN yang dicabut 2026-08-22.
                    Panen kini dicatat per poktan, bukan per orang, sehingga
                    tidak ada lagi cara yang sahih menyaringnya bagi satu
                    keluarga: satu poktan berisi banyak keluarga, dan hasil
                    panennya milik kelompok.

                    Menautkan ke poktan lebih jujur daripada mengarang
                    pembagian per orang yang tidak pernah didata.
                --}}
                <div x-show="tab === 'poktan'" x-cloak role="tabpanel">
                    @if (empty($poktanBernaung))
                        <x-sim.empty-state judul="Belum tergabung kelompok tani"
                            pesan="Keanggotaan poktan keluarga ini akan tampil di sini setelah didata petugas." />
                    @else
                        <x-sim.tabel-ringkas judul="Kelompok tani tempat keluarga ini bernaung" :kolom="['Kelompok Tani', 'Jabatan', 'Tanggal Masuk', 'Satuan Permukiman']">
                            @foreach ($poktanBernaung as $a)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('poktan.detail', $a['poktan_id']) }}"
                                            class="rounded text-theme-sm font-medium text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                            {{ $a['poktan'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $a['jabatan'] }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ \Illuminate\Support\Carbon::parse($a['tanggal_masuk'])->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $spPoktan[$a['poktan_id']] ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>

                        <p class="border-t border-gray-200 px-5 py-4 text-theme-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            Penanaman dan hasil panen dicatat atas nama kelompok, bukan perorangan. Angkanya dapat
                            dibaca pada halaman kelompok tani di atas.
                        </p>
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

                {{--
                    Riwayat suksesi kepala keluarga, disajikan sebagai garis
                    waktu mengikuti riwayat penghunian: yang perlu terbaca
                    adalah urutan kejadian, siapa digantikan siapa, kapan, dan
                    mengapa.
                --}}
                <div x-show="tab === 'riwayat-kk'" x-cloak role="tabpanel">
                    @if (empty($riwayatKk))
                        <x-sim.empty-state judul="Belum pernah berganti kepala keluarga"
                            pesan="Pergantian kedudukan kepala keluarga akan tercatat di sini beserta sebab dan tanggalnya." />
                    @else
                        <ol class="relative m-5 space-y-6 border-l border-gray-200 pl-6 sm:m-6 dark:border-gray-700">
                            @foreach ($riwayatKk as $jejak)
                                @php
                                    $alasan = \App\Enums\AlasanPergantianKK::from($jejak['alasan']);
                                    $kkBerubah = $jejak['no_kk_lama'] !== $jejak['no_kk_baru'];
                                @endphp
                                <li class="relative">
                                    <span
                                        class="absolute -left-[1.9rem] mt-1 flex h-3 w-3 rounded-full bg-brand-500 ring-4 ring-white dark:ring-gray-900"
                                        aria-hidden="true"></span>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-sim.status-badge :teks="$alasan->label()"
                                            :warna="$alasan === \App\Enums\AlasanPergantianKK::Meninggal ? 'gray' : 'warning'"
                                            ukuran="sm" />
                                        <span class="text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                                            {{ \Illuminate\Support\Carbon::parse($jejak['tanggal_pergantian'])->translatedFormat('d F Y') }}
                                        </span>
                                    </div>

                                    {{--
                                        Kedua sisi ditampilkan berdampingan agar riwayat
                                        terbaca berdiri sendiri, tanpa perlu merangkainya
                                        dari baris berikutnya.
                                    --}}
                                    <p class="mt-1.5 text-theme-sm text-gray-800 dark:text-white/90">
                                        <span class="text-gray-500 line-through dark:text-gray-400">{{ $jejak['nama_lama'] }}</span>
                                        <span class="mx-1 text-gray-400" aria-hidden="true">&rarr;</span>
                                        <span class="font-medium">{{ $jejak['nama_baru'] }}</span>
                                        <span class="text-gray-500 dark:text-gray-400">({{ $jejak['hubungan_pengganti'] }})</span>
                                    </p>

                                    <p class="mt-1 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                                        NIK {{ $jejak['nik_lama'] }} &rarr; {{ $jejak['nik_baru'] }}
                                    </p>

                                    {{--
                                        Nomor KK hanya ditulis bila benar-benar berubah.
                                        Menampilkan dua nomor yang sama justru membuat
                                        pembaca menduga ada perubahan yang tidak ada.
                                    --}}
                                    <p class="mt-0.5 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                                        @if ($kkBerubah)
                                            No. KK {{ $jejak['no_kk_lama'] }} &rarr; {{ $jejak['no_kk_baru'] }}
                                        @else
                                            No. KK {{ $jejak['no_kk_baru'] }}, tidak berubah
                                        @endif
                                    </p>

                                    @if ($jejak['keterangan'])
                                        <p class="mt-1.5 text-theme-xs text-gray-600 dark:text-gray-400">
                                            {{ $jejak['keterangan'] }}
                                        </p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>

                        <p class="border-t border-gray-200 p-5 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                            Rumah tangganya berlanjut, yang berganti kepalanya, sehingga rumah dan lahan tetap
                            melekat pada keluarga ini. Riwayat suksesi tidak dapat dihapus, sebab ia menyatakan
                            siapa pemegang jatah pada rentang waktu tertentu.
                        </p>
                    @endif
                </div>

                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="transmigran" :record-id="$data['id_transmigran']" />
                </div>
            </div>
        </div>
    </div>

    {{--
        Modal suksesi kepala keluarga.

        Sengaja TERPISAH dari modal ubah, dan itu bukan soal tata letak: bila
        suksesi lahir dari penyuntingan nama pada form biasa, setiap perbaikan
        ejaan akan mengotori riwayat suksesi. Audit log pun tidak dapat
        membedakan keduanya, sebab keduanya berbentuk aksi Ubah pada kolom yang
        sama (rules.md 6 poin 5a dan 5b).
    --}}
    @if ($bolehSuksesi)
        <x-sim.modal-form nama="formGantiKepalaKeluarga" judul="Ganti Kepala Keluarga"
            :keterangan="'Kedudukan kepala keluarga berpindah dari ' . $data['nama_kepala_keluarga'] . ' kepada penggantinya. Rumah, lahan, dan keanggotaan poktan tetap melekat pada keluarga ini.'"
            :aksi="route('transmigran.ganti-kepala-keluarga', $data['id_transmigran'])" ukuran="lg"
            label-simpan="Simpan Pergantian">

            @php
                $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
                $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
            @endphp

            <div class="space-y-6"
                x-data="{
                    calon: @js($calonPengganti),
                    penggantiId: '',
                    get pengganti() {
                        return this.calon.find(c => String(c.id) === String(this.penggantiId)) ?? null;
                    },
                }">
                {{--
                    Kepala keluarga yang digantikan, ditampilkan sebagai bacaan
                    dan dikirim sebagai isian tersembunyi. Petugas tidak perlu
                    mengetiknya ulang, dan riwayat tetap menyimpan kedua sisi.
                --}}
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">Kepala keluarga saat ini</p>
                    <p class="mt-0.5 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                        {{ $data['nama_kepala_keluarga'] }}
                    </p>
                    <p class="mt-0.5 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                        NIK {{ $data['nik'] }} &middot; No. KK {{ $data['no_kk'] }}
                    </p>
                </div>

                <input type="hidden" name="nik_lama" value="{{ $data['nik'] }}" />
                <input type="hidden" name="nama_lama" value="{{ $data['nama_kepala_keluarga'] }}" />
                <input type="hidden" name="no_kk_lama" value="{{ $data['no_kk'] }}" />

                {{--
                    Pengganti DIPILIH dari daftar anggota keluarga (Stage B3,
                    2026-08-28; erd.md 7.4 dibalik). Nama, NIK, dan hubungannya
                    "naik" menimpa baris transmigran ini, lalu baris
                    anggota_keluarga pengganti dihapus. Urutan Dukcapil
                    (pasangan lalu anak tertua) tidak ditegakkan; daftar
                    diurutkan sebagai penunjuk, bukan aturan (rules.md 6.5d).
                --}}
                <div>
                    <label for="suksesi_pengganti" class="{{ $kelasLabel }}">
                        Pengganti<span class="text-error-500">*</span>
                    </label>
                    @if (empty($calonPengganti))
                        <p class="rounded-lg border border-warning-200 bg-warning-50 p-3.5 text-theme-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300">
                            Keluarga ini belum memiliki anggota keluarga terdata. Tambahkan lebih dulu lewat
                            tombol Ubah Data agar dapat dipilih sebagai pengganti.
                        </p>
                    @else
                        <select id="suksesi_pengganti" name="pengganti_anggota_keluarga_id" required
                            x-model="penggantiId" class="{{ $kelasKontrol }}">
                            <option value="">Pilih anggota keluarga</option>
                            @foreach ($calonPengganti as $c)
                                <option value="{{ $c['id'] }}">
                                    {{ $c['nama'] }} ({{ $c['hubungan'] }}{{ $c['usia'] !== null ? ', ' . $c['usia'] . ' tahun' : '' }})
                                </option>
                            @endforeach
                        </select>
                    @endif

                    {{-- Identitas pengganti, dibaca dari pilihan dan dikirim
                         sebagai isian tersembunyi dengan nama yang sama seperti
                         sebelumnya, agar rute Tahap 5 tidak berubah. --}}
                    <input type="hidden" name="nama_baru" :value="pengganti?.nama ?? ''" />
                    <input type="hidden" name="nik_baru" :value="pengganti?.nik ?? ''" />
                    <input type="hidden" name="hubungan_pengganti" :value="pengganti?.hubungan ?? ''" />

                    <div x-show="pengganti" x-cloak
                        class="mt-2 rounded-lg border border-gray-200 bg-gray-50 p-3.5 text-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
                        <p class="font-medium text-gray-800 dark:text-white/90" x-text="pengganti?.nama"></p>
                        <p class="mt-0.5 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                            <span x-text="pengganti?.hubungan"></span> dari kepala keluarga lama
                            &middot; NIK <span x-text="pengganti?.nik ?? 'belum ada'"></span>
                        </p>
                        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                            Data ini akan menjadi data kepala keluarga, dan barisnya sebagai anggota keluarga dihapus.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    {{--
                        Nomor KK terisi nilai lama dan dapat disunting. Dukcapil
                        menerbitkan KK baru ketika kepala keluarganya berganti,
                        tetapi tidak selalu sudah terbit saat pendataan
                        (rules.md 6.5c).
                    --}}
                    <div>
                        <label for="suksesi_no_kk_baru" class="{{ $kelasLabel }}">
                            Nomor KK Baru<span class="text-error-500">*</span>
                        </label>
                        <input type="text" inputmode="numeric" id="suksesi_no_kk_baru" name="no_kk_baru" required
                            value="{{ $data['no_kk'] }}" minlength="16" maxlength="16" pattern="[0-9]{16}"
                            class="{{ $kelasKontrol }} tabular-nums" />
                        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            Biarkan seperti semula bila KK baru belum terbit.
                        </p>
                    </div>

                    <div>
                        <label for="suksesi_tanggal" class="{{ $kelasLabel }}">
                            Tanggal Pergantian<span class="text-error-500">*</span>
                        </label>
                        <input type="date" id="suksesi_tanggal" name="tanggal_pergantian" required
                            value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" class="{{ $kelasKontrol }}" />
                    </div>

                    <div>
                        <label for="suksesi_alasan" class="{{ $kelasLabel }}">
                            Sebab Pergantian<span class="text-error-500">*</span>
                        </label>
                        <select id="suksesi_alasan" name="alasan" required class="{{ $kelasKontrol }}">
                            <option value="">Pilih sebab</option>
                            @foreach (\App\Enums\AlasanPergantianKK::opsi() as $nilai => $label)
                                <option value="{{ $nilai }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{--
                    JABATAN KETUA POKTAN TIDAK DIWARISKAN.

                    Tanpa blok ini, menyunting baris transmigran akan membuat
                    kepala keluarga baru menjadi ketua poktan tanpa seorang pun
                    memutuskan, padahal ketua dipilih anggota. Karena itu
                    petugas WAJIB memilih, bukan sekadar diberi tahu
                    (rules.md 6 poin 5e).
                --}}
                @if (! empty($poktanDiketuai))
                    <div class="rounded-lg border border-warning-200 bg-warning-50 p-4 dark:border-warning-500/30 dark:bg-warning-500/10">
                        <p class="text-theme-sm font-medium text-warning-800 dark:text-warning-300">
                            Keluarga ini menjabat ketua kelompok tani
                        </p>
                        <p class="mt-1 text-theme-xs text-warning-700 dark:text-warning-200">
                            {{ implode(', ', array_column($poktanDiketuai, 'nama')) }}.
                            Jabatan ketua dipilih anggota dan tidak berpindah dengan sendirinya, sehingga
                            nasibnya perlu ditetapkan sekarang.
                        </p>

                        <fieldset class="mt-3 space-y-2">
                            <legend class="sr-only">Nasib jabatan ketua poktan</legend>
                            <label class="flex items-start gap-2 text-theme-sm text-warning-800 dark:text-warning-200">
                                <input type="radio" name="nasib_ketua_poktan" value="kosongkan" required
                                    class="mt-0.5 h-4 w-4 border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500" />
                                <span>
                                    Kosongkan jabatan ketua
                                    <span class="block text-theme-xs opacity-80">
                                        Kelompok memilih ketua baru, lalu petugas menetapkannya di halaman poktan.
                                    </span>
                                </span>
                            </label>
                            <label class="flex items-start gap-2 text-theme-sm text-warning-800 dark:text-warning-200">
                                <input type="radio" name="nasib_ketua_poktan" value="teruskan" required
                                    class="mt-0.5 h-4 w-4 border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500" />
                                <span>
                                    Teruskan kepada kepala keluarga baru
                                    <span class="block text-theme-xs opacity-80">
                                        Dipakai bila kelompok memang sudah menyepakatinya.
                                    </span>
                                </span>
                            </label>
                        </fieldset>
                    </div>
                @endif

                {{--
                    KEANGGOTAAN POKTAN JUSTRU MENGIKUTI, dan itu benar: sejak
                    2026-08-20 keanggotaan melekat pada keluarga, bukan pada
                    kepala keluarganya (rules.md 7a poin 3a). Petugas cukup
                    diberi tahu, tidak diminta memutuskan.
                --}}
                @if (! empty($keanggotaanIkut))
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                        <p class="text-theme-xs text-gray-600 dark:text-gray-400">
                            Keanggotaan pada
                            <span class="font-medium text-gray-800 dark:text-white/90">{{ implode(', ', array_column($keanggotaanIkut, 'poktan')) }}</span>
                            mengikuti kepala keluarga baru, sebab keanggotaan melekat pada keluarga.
                            Bila yang mewakili keluarga ternyata orang lain, ubah wakilnya di halaman poktan.
                        </p>
                    </div>
                @endif

                <div>
                    <label for="suksesi_keterangan" class="{{ $kelasLabel }}">Catatan</label>
                    <textarea id="suksesi_keterangan" name="keterangan" rows="2" maxlength="500"
                        placeholder="Contoh: akta kematian dan KK baru sudah diserahkan ke kantor SP."
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30"></textarea>
                </div>

                <p class="rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                    Rumah, lahan, dan dokumen tetap melekat pada keluarga ini, sebab jatah transmigrasi
                    diberikan kepada keluarga bukan kepada orangnya. Pergantian ini tercatat sebagai riwayat
                    tersendiri dan tidak dapat dihapus.
                </p>
            </div>
        </x-sim.modal-form>
    @endif

    {{-- Modal ubah data, terisi nilai yang sedang berlaku --}}
    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahTransmigran" judul="Ubah Data Transmigran"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('transmigran.perbarui', $data['id_transmigran'])" metode="PUT" ukuran="xl"
            label-simpan="Simpan Perubahan">
            @include('pages.transmigran.form', [
                'data' => $data,
                'awalan' => 'ubah',
                'anggotaKeluargaData' => $anggotaKeluarga,
            ])
        </x-sim.modal-form>
    @endif
@endsection
