{{--
    Rincian kelompok tani beserta anggotanya.

    Anggota yang berhenti ditandai berstatus Sudah Keluar, bukan dihapus,
    agar riwayat keanggotaan tetap utuh (agents/rules.md bagian 5.1 catatan 5).

    Status keaktifan bukan sekadar penanda: penanaman dan rekap kelompok hanya
    menghitung anggota aktif beserta lahannya (bagian 7d poin 3 dan 4).
    Penyaluran saprotan kini ke KELOMPOK, bukan perorangan (dicabut 2026-08-22).
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari rute `poktan.detail`, termasuk
        `$namaKkWakil` yang dahulu ditelusuri ulang di dalam perulangan
        anggota. Lihat routes/web.php.
    --}}

    <x-sim.page-header :judul="$data['nama']"
        :keterangan="'Kelompok tani di ' . $data['satuan_permukiman'] . ', berdiri sejak ' . $data['tahun_berdiri'] . '. Pencatatan anggota khusus warga transmigran.'"
        :remah="\App\Helpers\RemahHelper::untuk('/poktan', $data['nama'])">
        <x-slot:aksi>
            <button type="button" @click="$dispatch('buka-modal', 'formTambahAnggota')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Tambah Anggota
            </button>
            <button type="button" @click="$dispatch('buka-modal', 'formUbahPoktan')"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                </svg>
                Ubah Profil
            </button>
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Profil Poktan</h2>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Ketua</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            {{ $ketua['nama'] }}
                            {{-- Asal-usul ketua ditulis apa adanya. Tanpa keterangan ini, ketua
                                 yang bukan kepala keluarga terbaca seolah kepala keluarga. --}}
                            @if (! $ketua['asal']->identitasDariRelasi())
                                <span class="mt-0.5 block text-theme-xs font-normal text-gray-500 dark:text-gray-400">
                                    {{ $ketua['asal']->label() }}@if ($data['hubungan_ketua']), {{ $data['hubungan_ketua'] }} dari {{ $keluargaKetua['nama_kepala_keluarga'] ?? '-' }}@endif
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">NIK ketua</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $ketua['nik'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Telepon</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $ketua['telepon'] ?? '-' }}</dd>
                    </div>
                    {{--
                        Luas lahan ketua. Dua jalur pertama membacanya dari bidang milik
                        keluarga; ketua non-transmigran memakai nilai yang diketik, sebab
                        lahannya memang tidak terdata pada modul Lahan.
                    --}}
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Lahan usaha ketua</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($lahanKetua['kering'], 2, ',', '.') }} ha kering,
                            {{ number_format($lahanKetua['basah'], 2, ',', '.') }} ha basah
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                        <dd class="text-right font-medium break-all text-gray-800 dark:text-white/90">
                            {{ $data['email_ketua'] ?? '-' }}</dd>
                    </div>
                    {{--
                        Alamat sekretariat, ditambahkan 2026-08-25. Form sudah
                        lama memintanya sebagai titik temu penyuluh, tetapi
                        nilainya tidak pernah ditampilkan kembali sehingga
                        petugas lapangan tetap harus bertanya lewat telepon.
                    --}}
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Alamat sekretariat</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            {{ $data['alamat_ketua'] ?? '-' }}</dd>
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
                        <dt class="text-gray-500 dark:text-gray-400">Anggota transmigran aktif</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $aktif }} dari {{ count($anggota) }} terdata
                            <span class="mt-0.5 block text-theme-xs font-normal text-gray-500 dark:text-gray-400">
                                Khusus warga transmigran
                            </span>
                        </dd>
                    </div>
                    {{--
                        SK pembentukan dan catatan, ditambahkan 2026-08-20.
                        Keduanya sudah lama ada pada kamus data 8.1 tetapi tidak
                        pernah ditampilkan kembali, sehingga SK yang diunggah
                        petugas tidak punya cara dibuka.
                    --}}
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">SK pembentukan</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            <x-sim.tautan-dokumen modul="poktan" :id="$data['id_poktan']"
                                :berkas="$data['dokumen_pendukung'] ?? null" />
                        </dd>
                    </div>
                </dl>

                <div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-800">
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">Catatan</p>
                    <p class="mt-0.5 text-theme-sm leading-relaxed text-gray-800 dark:text-white/90">
                        {{ $data['keterangan'] ?? 'Tidak ada catatan tambahan.' }}
                    </p>
                </div>

                {{-- Titik sekretariat poktan, agar petugas dapat menemukan lokasinya --}}
                @if (! empty($data['lintang']))
                    <div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-800">
                        <p class="text-theme-xs text-gray-500 dark:text-gray-400">Titik sekretariat</p>
                        <p class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($data['lintang'], 6, '.', '') }},
                            {{ number_format($data['bujur'], 6, '.', '') }}
                        </p>
                        <x-sim.tautan-peta class="mt-1.5" :lintang="$data['lintang']"
                            :bujur="$data['bujur']" :label="$data['nama']" />
                    </div>
                @endif
            </div>
        </aside>

        <div x-data="hashTabs('anggota')" class="min-w-0">
            <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian kelompok tani">
                    @foreach ([
                        'anggota' => 'Anggota Transmigran (' . count($anggota) . ')',
                        'alsintan' => 'Alsintan (' . count($alsintan) . ')',
                        'saprotan' => 'Saprotan (' . count($saprotan) . ')',
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

                <div x-show="tab === 'anggota'" role="tabpanel">
                    <div class="border-b border-gray-200 bg-gray-50/60 px-5 py-3 dark:border-gray-800 dark:bg-white/[0.02]">
                        <p class="text-theme-xs text-gray-600 dark:text-gray-400">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Catatan Ruang Lingkup:</span>
                            Sistem ini hanya mendata anggota kelompok tani yang merupakan warga transmigran. Anggota non-transmigran (penduduk lokal) yang tergabung di kelompok yang sama tidak dicatat dalam sistem ini.
                        </p>
                    </div>

                    @if (empty($anggota))
                        <x-sim.empty-state judul="Belum ada anggota terdata"
                            pesan="Daftar anggota transmigran kelompok tani ini akan tampil setelah didata." />
                    @else
                        <x-sim.tabel-ringkas judul="Anggota Kelompok Tani (Khusus Warga Transmigran)"
                            :kolom="['Wakil Keluarga', 'NIK', 'Telepon', 'Jabatan', 'Lahan Usaha (ha)', 'Tanggal Masuk', 'Status', 'Aksi']">
                            @foreach ($anggota as $a)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        {{--
                                            Tautan menuju KELUARGA yang diwakili, bukan orangnya:
                                            anggota keluarga tidak punya halaman rincian sendiri,
                                            datanya tampil di dalam halaman keluarganya (tab
                                            Anggota Keluarga).
                                        --}}
                                        <a href="{{ route('transmigran.detail', $a['transmigran_id']) }}"
                                            class="rounded text-theme-sm text-gray-800 hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                            {{ $a['nama'] }}
                                        </a>
                                        @if ($a['asal_wakil'] !== \App\Enums\AsalWakilPoktan::KepalaKeluarga->value)
                                            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                                {{ $a['hubungan_wakil'] ?? 'Anggota keluarga' }} dari
                                                {{ $namaKkWakil[$a['transmigran_id']] ?? '-' }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $a['nik'] }}</td>
                                    {{--
                                        Telepon wakil, ditambahkan 2026-08-25.
                                        Sudah lama disiapkan `anggotaPoktan()`
                                        hasil percabangan tiga jalur, tetapi
                                        tidak pernah dipasang di kolom mana pun.
                                    --}}
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $a['telepon'] ?? '-' }}</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $a['jabatan'] }}</td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ number_format($a['luas_kering'], 2, ',', '.') }} K /
                                        {{ number_format($a['luas_basah'], 2, ',', '.') }} B
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ \Illuminate\Support\Carbon::parse($a['tanggal_masuk'])->translatedFormat('d M Y') }}
                                        @if ($a['tanggal_keluar'])
                                            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                                keluar {{ \Illuminate\Support\Carbon::parse($a['tanggal_keluar'])->translatedFormat('d M Y') }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-sim.status-badge
                                            :status="\App\Enums\StatusKeaktifanAnggota::from($a['status'])" />
                                        {{--
                                            Alasan keluar dan catatan, keduanya
                                            ditambahkan 2026-08-25. Form anggota
                                            sudah lama memintanya, tetapi tabel
                                            ini hanya menampilkan badge status
                                            sehingga SEBAB seseorang berhenti
                                            tidak pernah terbaca kembali.
                                        --}}
                                        @if ($a['alasan_keluar'])
                                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                                {{ $a['alasan_keluar'] }}
                                            </p>
                                        @endif
                                        @if ($a['keterangan'])
                                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                                {{ $a['keterangan'] }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        {{--
                                            Tanpa tombol ini status keaktifan dan tanggal keluar tidak
                                            pernah dapat diisi setelah anggota tersimpan, padahal justru
                                            keduanya yang berubah belakangan (rules.md 7a.4).

                                            Hapus sengaja tidak disediakan: anggota yang berhenti ditandai
                                            Sudah Keluar agar riwayat keanggotaan tetap utuh.
                                        --}}
                                        <x-sim.aksi-baris modal-ubah="formUbahAnggotaPoktan"
                                            :data-baris="$a + ['id' => $a['id_anggota_poktan']]"
                                            :label="$a['nama']" />
                                    </td>
                                </tr>
                            @endforeach

                            {{--
                                Luas lahan kelompok dijumlahkan dari anggotanya, bukan disimpan
                                sebagai kolom. Kolom `luas_lahan_kelompok` dicabut 2026-08-20
                                sebab nilainya basi begitu luas dibetulkan di modul Lahan.
                            --}}
                            <tr class="motif-baris-total">
                                <td colspan="4" class="px-5 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    Total lahan usaha kelompok
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($luasKelompokKering, 2, ',', '.') }} K /
                                    {{ number_format($luasKelompokBasah, 2, ',', '.') }} B
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </x-sim.tabel-ringkas>

                        <p class="border-t border-gray-200 p-5 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                            Keanggotaan melekat pada keluarga, bukan pada kepala keluarganya: yang terdaftar
                            adalah orang yang benar-benar menggarap. Luas lahan dijumlahkan dari bidang milik
                            keluarga tersebut, sehingga selalu mengikuti data lahan terbaru. Anggota yang
                            berhenti ditandai Sudah Keluar lewat tombol ubah, bukan dihapus, agar riwayat
                            keanggotaan tetap utuh.
                        </p>
                    @endif
                </div>

                <div x-show="tab === 'alsintan'" x-cloak role="tabpanel">
                    @if (empty($alsintan))
                        <x-sim.empty-state judul="Belum ada alsintan"
                            pesan="Bantuan alat dan mesin pertanian untuk kelompok ini akan tampil di sini." />
                    @else
                        <x-sim.tabel-ringkas judul="Alsintan yang bagiannya diterima kelompok tani ini" :kolom="['Jenis', 'Nama Alat', 'Jumlah', 'Tahun', 'Kondisi']">
                            @foreach ($alsintan as $a)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $a['jenis_alsintan'] }}</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                                        <a href="{{ route('alsintan.detail', $a['id_alsintan']) }}"
                                            class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                            {{ $a['nama_alat'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $a['jumlah'] }}</td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $a['tahun_pengadaan'] }}</td>
                                    <td class="px-5 py-3">
                                        <x-sim.status-badge :status="\App\Enums\Kondisi::from($a['kondisi'])" />
                                    </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>
                    @endif
                </div>

                <div x-show="tab === 'saprotan'" x-cloak role="tabpanel">
                    @if (empty($saprotan))
                        <x-sim.empty-state judul="Belum ada penyaluran saprotan"
                            pesan="Penyaluran benih, pupuk, atau pestisida akan tampil di sini." />
                    @else
                        <x-sim.tabel-ringkas judul="Saprotan yang bagiannya diterima kelompok tani ini" :kolom="['Jenis', 'Nama', 'Jumlah Diterima', 'Sisa Benih', 'Tahun Pengadaan']">
                            @foreach ($saprotan as $s)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                        {{ $s['jenis'] }}</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                                        <a href="{{ route('saprotan.detail', $s['saprotan_id']) }}"
                                            class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                            {{ $s['nama'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ rtrim(rtrim(number_format($s['jumlah'], 2, ',', '.'), '0'), ',') }} {{ $s['satuan'] }}</td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $s['sisa_benih'] === null ? '-' : (rtrim(rtrim(number_format($s['sisa_benih'], 2, ',', '.'), '0'), ',') . ' ' . $s['satuan']) }}</td>
                                    <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                        {{ $s['tahun_pengadaan'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </x-sim.tabel-ringkas>
                    @endif
                </div>


                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="poktan" :record-id="$data['id_poktan']" />
                </div>
            </div>
        </div>
    </div>

    {{-- Modal ubah profil poktan --}}
    <x-sim.modal-form nama="formUbahPoktan" judul="Ubah Profil Poktan"
        keterangan="Perubahan tercatat pada audit log."
        :aksi="route('poktan.perbarui', $data['id_poktan'])" metode="PUT" ukuran="lg"
        :langkah="['Identitas Kelompok', 'Pengurus & Legalitas', 'Anggota Kelompok']"
        label-simpan="Simpan Perubahan">
        @include('pages.poktan.form', ['data' => $data, 'awalan' => 'ubah'])
    </x-sim.modal-form>

    {{-- Modal tambah anggota --}}
    <x-sim.modal-form nama="formTambahAnggota" judul="Tambah Anggota Poktan"
        keterangan="Anggota yang berhenti ditandai Sudah Keluar, tidak dihapus dari daftar."
        :aksi="route('anggota-poktan.simpan')" ukuran="lg" label-simpan="Simpan Data">
        @include('pages.poktan.form-anggota', ['awalan' => 'tambah', 'poktanId' => $data['id_poktan']])
    </x-sim.modal-form>

    {{--
        Modal ubah anggota, dipakai bergantian oleh seluruh baris lewat
        `pola-aksi` agar tabel berisi puluhan anggota tidak memuat puluhan
        salinan form yang sama.

        Inilah satu-satunya jalur mengubah status keaktifan dan mengisi
        tanggal keluar. Sebelum modal ini ada, keduanya hanya dapat diisi pada
        saat anggota pertama kali ditambahkan, padahal justru keduanya yang
        berubah belakangan.
    --}}
    <x-sim.modal-form nama="formUbahAnggotaPoktan" judul="Ubah Data Anggota"
        keterangan="Anggota yang berhenti atau pindah kelompok ditandai Sudah Keluar, bukan dihapus."
        pola-aksi="/anggota-poktan/:id" metode="PUT" ukuran="lg"
        label-simpan="Simpan Perubahan">
        @include('pages.poktan.form-anggota', ['awalan' => 'ubahAnggota', 'poktanId' => $data['id_poktan']])
    </x-sim.modal-form>
@endsection
