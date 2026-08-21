{{--
    Rincian pengaduan beserta penanganannya.

    Inilah yang membedakan modul ini dari empat modul sebelumnya: alur status
    WAJIB berurutan Menunggu Diterima, Diterima, Diproses, Selesai, dan tidak
    boleh melompat maupun mundur (agents/rules.md bagian 10b poin 4).

    Aturan itu diwujudkan di antarmuka sebagai berikut:
    1. Hanya SATU tombol lanjut yang dirender, yaitu menuju status berikutnya
       yang sah menurut StatusPengaduan::berikutnya(). Status lain tidak
       ditawarkan sama sekali, sehingga lompatan mustahil dilakukan lewat UI.
    2. Bila sudah Selesai, tidak ada tombol lanjut, hanya keterangan.
    3. Setiap perpindahan wajib menyertakan catatan tindakan, karena riwayat
       tanpa catatan tidak menjelaskan apa pun kepada pembacanya (poin 5).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;
        use App\Enums\StatusPengaduan;
        use App\Enums\PrioritasPengaduan;

        $status = StatusPengaduan::from($data['status']);
        $prioritas = PrioritasPengaduan::from($data['prioritas']);
        $riwayat = DummyData::penangananPengaduan($data['nomor_pengaduan']);

        // Satu-satunya status yang boleh dituju berikutnya. Bernilai null bila
        // pengaduan sudah selesai.
        $statusBerikutnya = $status->berikutnya();

        $bolehTangani = true;

        // Seluruh tahap alur, dipakai menggambar penanda kemajuan.
        $tahapan = StatusPengaduan::cases();
        $indeksSekarang = array_search($status, $tahapan, true);
    @endphp

    <x-sim.page-header :judul="$data['judul']"
        :keterangan="'Nomor ' . $data['nomor_pengaduan'] . ', dilaporkan ' . \Illuminate\Support\Carbon::parse($data['tanggal_pengaduan'])->translatedFormat('d F Y') . '.'"
        :remah="\App\Helpers\RemahHelper::untuk('/pengaduan', $data['nomor_pengaduan'])">
        {{--
            Tombol penanganan sengaja TIDAK diletakkan di sini, melainkan hanya
            pada kolom kiri di bawah alur penanganan. Di sana ia bersebelahan
            dengan stepper yang menunjukkan tahap laporan saat ini, sehingga
            petugas melihat konteksnya sebelum menekan. Tombol kedua di kepala
            halaman hanya menggandakan tindakan yang sama tanpa konteks apa pun.
        --}}
    </x-sim.page-header>

    {{-- Prioritas Mendesak ditegaskan memakai aksen gold, satu dari empat pemakaian sah --}}
    @if ($prioritas === PrioritasPengaduan::Mendesak && $status !== StatusPengaduan::Selesai)
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-gold-500 bg-gold-50 p-4 dark:border-gold-500/40 dark:bg-gold-500/10"
            role="alert">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-gold-700 dark:text-gold-400" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <div>
                <p class="text-theme-sm font-semibold text-gold-800 dark:text-gold-300">Pengaduan berprioritas mendesak</p>
                <p class="mt-1 text-theme-sm text-gold-800 dark:text-gold-200">
                    Laporan ini perlu segera ditindaklanjuti petugas {{ $data['bidang'] }}.
                </p>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        {{-- Kolom kiri: ringkasan dan alur --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <p class="text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                    {{ $data['nomor_pengaduan'] }}
                </p>

                <div class="mt-3 flex flex-wrap gap-2">
                    <x-sim.status-badge :status="$status" />
                    <x-sim.status-badge :status="$prioritas" />
                </div>

                {{--
                    Penanda kemajuan alur. Menampilkan keempat tahap sekaligus
                    agar petugas tahu posisi laporan ini dan apa tahap sesudahnya.
                --}}
                <div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-800">
                    <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-300">Alur Penanganan</p>
                    <ol class="mt-3 space-y-3">
                        @foreach ($tahapan as $i => $tahap)
                            @php
                                $sudahLewat = $i < $indeksSekarang;
                                $sedangBerjalan = $i === $indeksSekarang;
                            @endphp
                            <li class="flex items-center gap-3">
                                <span
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-theme-xs font-semibold
                                        {{ $sudahLewat ? 'bg-green-500 text-white' : ($sedangBerjalan ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-500 dark:bg-white/10 dark:text-gray-400') }}"
                                    aria-hidden="true">
                                    @if ($sudahLewat)
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </span>
                                <span
                                    class="text-theme-sm {{ $sedangBerjalan ? 'font-semibold text-gray-800 dark:text-white/90' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $tahap->label() }}
                                    @if ($sedangBerjalan)
                                        <span class="sr-only">, tahap saat ini</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Pelapor</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['nama_pelapor'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Kontak</dt>
                        <dd class="text-right font-medium tabular-nums text-gray-800 dark:text-white/90">
                            {{ $data['kontak_pelapor'] }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Sumber laporan</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['sumber_laporan'] }}</dd>
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
                        <dt class="text-gray-500 dark:text-gray-400">Bidang penanganan</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['bidang'] }}</dd>
                    </div>
                </dl>

                @if ($bolehTangani)
                    <div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-800">
                        @if ($statusBerikutnya !== null)
                            <button type="button" @click="$dispatch('buka-modal', 'formPenanganan')"
                                class="w-full rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                Tandai {{ $statusBerikutnya->label() }}
                            </button>
                            <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                                Status hanya dapat maju satu tahap, sesuai alur penanganan.
                            </p>
                        @else
                            {{--
                                Tidak ada tombol sama sekali ketika sudah selesai.
                                Menampilkan tombol mati akan menyesatkan (R-26).
                            --}}
                            <p class="rounded-lg bg-green-50 p-3.5 text-theme-xs text-green-800 dark:bg-green-500/10 dark:text-green-300">
                                Pengaduan sudah selesai ditangani. Tidak ada tahap lanjutan.
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </aside>

        {{-- Kolom kanan: uraian dan riwayat --}}
        <div x-data="hashTabs('uraian')" class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                    role="tablist" aria-label="Rincian pengaduan">
                    @foreach ([
                        'uraian' => 'Uraian Masalah',
                        'riwayat' => 'Riwayat Penanganan (' . count($riwayat) . ')',
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

                {{-- Uraian --}}
                <div x-show="tab === 'uraian'" role="tabpanel" class="p-5 sm:p-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Kategori</dt>
                            <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">{{ $data['kategori'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Uraian masalah</dt>
                            <dd class="mt-0.5 text-theme-sm leading-relaxed text-gray-800 dark:text-white/90">
                                {{ $data['deskripsi'] }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Titik kejadian</dt>
                        <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            @if (! empty($data['lintang']))
                                {{ number_format($data['lintang'], 6, '.', '') }},
                                {{ number_format($data['bujur'], 6, '.', '') }}
                                <x-sim.tautan-peta class="mt-1.5" :lintang="$data['lintang']"
                                    :bujur="$data['bujur']" :label="$data['nomor_pengaduan']" />
                            @else
                                -
                            @endif
                        </dd>
                        </div>
                    </dl>
                </div>

                {{-- Riwayat penanganan --}}
                <div x-show="tab === 'riwayat'" x-cloak role="tabpanel">
                    @if (empty($riwayat))
                        <x-sim.empty-state judul="Belum ada penanganan"
                            pesan="Laporan ini masih menunggu diterima petugas. Riwayat akan terisi setelah petugas menindaklanjuti." />
                    @else
                        <div class="p-5 sm:p-6">
                            <ol class="relative space-y-6 border-l border-gray-200 pl-6 dark:border-gray-700">
                                @foreach ($riwayat as $jejak)
                                    <li class="relative">
                                        <span
                                            class="absolute -left-[1.9rem] mt-1 flex h-3 w-3 rounded-full bg-brand-500 ring-4 ring-white dark:ring-gray-900"
                                            aria-hidden="true"></span>

                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-sim.status-badge
                                                :status="StatusPengaduan::from($jejak['status_sesudah'])" ukuran="sm" />
                                            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
                                                dari {{ $jejak['status_sebelum'] }}
                                            </span>
                                        </div>

                                        <p class="mt-1.5 text-theme-sm text-gray-800 dark:text-white/90">
                                            {{ $jejak['catatan'] }}
                                        </p>

                                        <p class="mt-1 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                                            {{ $jejak['petugas'] }} &middot;
                                            {{ \Illuminate\Support\Carbon::parse($jejak['tanggal_penanganan'])->translatedFormat('d F Y') }}
                                        </p>

                                        {{--
                                            Dokumen tindak lanjut. Modal penanganan sudah lama
                                            menyediakan isian unggahnya, tetapi hasilnya tidak
                                            pernah ditampilkan kembali, sehingga berkas yang
                                            sudah diunggah petugas tidak dapat dibuka siapa pun.
                                        --}}
                                        @if (! empty($jejak['dokumen_tindak_lanjut']))
                                            <div class="mt-2">
                                                <x-sim.tautan-dokumen modul="pengaduan"
                                                    :id="$data['id_pengaduan']"
                                                    :berkas="$jejak['dokumen_tindak_lanjut']" />
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>

                            <p class="mt-6 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                                Setiap perubahan status menambah jejak baru beserta petugas penangan dan catatannya,
                                sehingga perkembangan laporan dapat ditelusuri dari awal. Dokumen tindak lanjut yang
                                diunggah petugas ikut tersimpan pada jejak yang bersangkutan.
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Catatan log: riwayat perubahan data ini saja --}}
                <div x-show="tab === 'log'" x-cloak role="tabpanel">
                    <x-sim.catatan-log nama-tabel="pengaduan" :record-id="$data['id_pengaduan']" />
                </div>
            </div>
        </div>
    </div>

    {{--
        Modal penanganan. Hanya dirender bila masih ada tahap berikutnya,
        dan status tujuan DITETAPKAN SISTEM, bukan dipilih petugas, sehingga
        lompatan status mustahil dilakukan lewat antarmuka.
    --}}
    @if ($bolehTangani && $statusBerikutnya !== null)
        <x-sim.modal-form nama="formPenanganan" :judul="'Tandai ' . $statusBerikutnya->label()"
            :keterangan="'Status berpindah dari ' . $status->label() . ' menjadi ' . $statusBerikutnya->label() . '.'"
            :aksi="route('pengaduan.tangani', $data['id_pengaduan'])" ukuran="lg"
            label-simpan="Simpan Penanganan">
            <div class="space-y-4">
                {{-- Status tujuan dikirim sebagai nilai tetap, tidak dapat diubah pengguna --}}
                <input type="hidden" name="status_sesudah" value="{{ $statusBerikutnya->value }}" />

                <div class="flex items-center gap-3 rounded-lg bg-gray-50 p-3.5 dark:bg-white/[0.03]">
                    <x-sim.status-badge :status="$status" ukuran="sm" />
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                    <x-sim.status-badge :status="$statusBerikutnya" ukuran="sm" />
                </div>

                <div>
                    <label for="tanggal_penanganan"
                        class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                        Tanggal Penanganan<span class="text-error-500">*</span>
                    </label>
                    <input type="date" id="tanggal_penanganan" name="tanggal_penanganan" required
                        value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90" />
                </div>

                <div>
                    <label for="catatan_penanganan"
                        class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                        Catatan Tindakan<span class="text-error-500">*</span>
                    </label>
                    {{--
                        Catatan wajib: riwayat tanpa catatan tidak menjelaskan
                        apa pun kepada pembacanya (rules.md bagian 10b poin 5).
                    --}}
                    <textarea id="catatan_penanganan" name="catatan" rows="4" required
                        placeholder="Jelaskan tindakan yang sudah dilakukan"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90"></textarea>
                </div>

                <x-sim.file-upload nama="dokumen_tindak_lanjut" label="Dokumen Tindak Lanjut"
                    nama-dokumen="Tindak Lanjut" :nama-pemilik="$data['nomor_pengaduan']"
                    keterangan="Foto perbaikan, berita acara, atau surat tindak lanjut bila ada." />

                {{--
                    Bidang dapat ditetapkan saat meninjau, sebab laporan yang
                    masuk lewat kanal publik berkategori netral tiba tanpa
                    bidang. Menyediakannya hanya pada form ubah akan memaksa
                    petugas membuka dua modal untuk satu alur kerja.
                --}}
                <div>
                    <label for="penanganan_bidang"
                        class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                        Bidang Penanganan
                    </label>
                    <select id="penanganan_bidang" name="bidang"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Belum ditentukan</option>
                        @foreach (\App\Support\DummyData::opsiReferensi(\App\Enums\JenisReferensi::BidangPengaduan) as $nilai => $label)
                            <option value="{{ $nilai }}" @selected(($data['bidang'] ?? null) === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        Wajib terisi sebelum laporan berstatus Diproses, agar tampil pada
                        daftar dinas yang menanganinya.
                    </p>
                </div>
            </div>
        </x-sim.modal-form>
    @endif
@endsection
