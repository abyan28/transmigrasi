{{--
    Halaman lacak pengaduan, tanpa login.

    Warga memasukkan nomor pengaduan untuk melihat perkembangan laporannya
    (agents/rules.md bagian 10b poin 1b).

    ATURAN PRIVASI YANG WAJIB DIJAGA: halaman ini hanya menampilkan status,
    tanggal, dan catatan penanganan. Data pribadi pelapor tidak pernah
    ditampilkan (poin 1c dan agents/ui-spec.md bagian 4.1a poin 4).

    Konsekuensinya, nama pelapor, nomor telepon, dan alamat IP sengaja TIDAK
    dirender di sini, sekalipun tersedia pada data. Tanpa aturan ini, siapa pun
    yang menebak nomor pengaduan dapat memanen data pribadi warga lain.
--}}
@extends('layouts.publik')

@section('content')
    @php
        use App\Support\DummyData;
        use App\Enums\StatusPengaduan;

        // Nomor dapat datang dari dua arah: kueri `?nomor=` milik formulir, dan
        // segmen rute `/lacak-pengaduan/{nomor}` yang menjadi tautan tetap.
        // Keduanya sah, dan yang kedua membuat halaman ini tetap berfungsi pada
        // build statis yang tidak dapat melayani kueri.
        $nomor = trim((string) ($nomorRute ?? request('nomor', '')));
        $pengaduan = null;
        $riwayat = [];

        if ($nomor !== '') {
            $pengaduan = collect(DummyData::pengaduan())
                ->firstWhere('nomor_pengaduan', mb_strtoupper($nomor));

            if ($pengaduan) {
                $riwayat = DummyData::penangananPengaduan($pengaduan['nomor_pengaduan']);
            }
        }
    @endphp

    <div class="mb-6">
        <h1 class="text-title-sm font-semibold text-navy-500 dark:text-white">Lacak Pengaduan</h1>
        <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
            Masukkan nomor pengaduan yang Anda terima saat mengirim laporan.
        </p>
    </div>

    {{--
        ponytail: pengalihan ke tautan tetap, khusus agar halaman ini tetap
        bekerja pada build statis GitHub Pages yang tidak dapat melayani kueri
        `?nomor=`. HAPUS SELURUH ATRIBUT `x-on:submit` di bawah pada Tahap 8,
        ketika controller pengaduan mengambil alih pencarian. Blok PHP di atas
        sudah benar dan tidak perlu diubah.

        Bila JavaScript mati, atribut ini terabaikan dan formulir kembali
        mengirim GET seperti biasa, sehingga versi ber-PHP tetap berfungsi.
    --}}
    <form method="GET" action="{{ route('lacak-pengaduan') }}"
        x-data
        x-on:submit.prevent="
            const nomor = $el.nomor.value.trim().toUpperCase();
            if (nomor) window.location.href = '{{ rtrim(route('lacak-pengaduan'), '/') }}/' + encodeURIComponent(nomor);
        "
        class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-navy-700 dark:bg-navy-800">
        <label for="nomor" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
            Nomor Pengaduan
        </label>
        <div class="flex flex-col gap-3 sm:flex-row">
            <input type="text" id="nomor" name="nomor" value="{{ $nomor }}" required
                placeholder="Contoh: PGD-2026-0001" autocomplete="off"
                class="h-12 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm tabular-nums text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-navy-700 dark:text-white/90" />
            <button type="submit"
                class="shrink-0 rounded-lg bg-brand-500 px-6 py-3 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                Cari
            </button>
        </div>
        <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
            Nomor tertera pada halaman setelah Anda mengirim pengaduan.
        </p>
    </form>

    {{-- Hasil pencarian --}}
    @if ($nomor !== '')
        @if ($pengaduan === null)
            {{-- Nomor tidak ditemukan, disertai jalan keluarnya --}}
            <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-navy-700 dark:bg-navy-800">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-white/5"
                    aria-hidden="true">
                    <svg class="h-7 w-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </span>
                <h2 class="mt-4 text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                    Nomor pengaduan tidak ditemukan
                </h2>
                <p class="mx-auto mt-2 max-w-md text-theme-sm text-gray-600 dark:text-gray-400">
                    Periksa kembali penulisan nomornya. Bila Anda belum pernah mengirim pengaduan,
                    silakan kirim terlebih dahulu.
                </p>
                <a href="{{ route('pengaduan-warga') }}"
                    class="mt-5 inline-flex items-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-navy-700 dark:text-gray-300 dark:hover:bg-white/5">
                    Kirim Pengaduan Baru
                </a>
            </div>
        @else
            @php
                $status = StatusPengaduan::from($pengaduan['status']);
                $tahapan = StatusPengaduan::cases();
                $indeksSekarang = array_search($status, $tahapan, true);
            @endphp

            <div class="mt-6 rounded-2xl border border-gray-200 bg-white dark:border-navy-700 dark:bg-navy-800">
                <div class="border-b border-gray-200 p-5 sm:p-6 dark:border-navy-700">
                    <p class="text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                        {{ $pengaduan['nomor_pengaduan'] }}
                    </p>
                    <h2 class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">
                        {{ $pengaduan['judul'] }}
                    </h2>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <x-sim.status-badge :status="$status" />
                        <span class="text-theme-xs text-gray-500 dark:text-gray-400">
                            Dilaporkan
                            {{ \Illuminate\Support\Carbon::parse($pengaduan['tanggal_pengaduan'])->translatedFormat('d F Y') }}
                        </span>
                    </div>
                </div>

                {{-- Penanda tahap, memakai bahasa yang menjelaskan artinya bagi warga --}}
                <div class="border-b border-gray-200 p-5 sm:p-6 dark:border-navy-700">
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">Perkembangan Laporan</p>
                    <ol class="mt-4 space-y-4">
                        @php
                            $penjelasan = [
                                'Menunggu Diterima' => 'Laporan Anda sudah masuk dan menunggu diperiksa petugas.',
                                'Diterima' => 'Petugas sudah menerima laporan Anda dan menjadwalkan peninjauan.',
                                'Diproses' => 'Petugas sedang menangani masalah yang Anda laporkan.',
                                'Selesai' => 'Penanganan sudah selesai.',
                            ];
                        @endphp
                        @foreach ($tahapan as $i => $tahap)
                            @php
                                $sudahLewat = $i < $indeksSekarang;
                                $sedangBerjalan = $i === $indeksSekarang;
                            @endphp
                            <li class="flex gap-3">
                                <span
                                    class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-theme-xs font-semibold
                                        {{ $sudahLewat ? 'bg-green-500 text-white' : ($sedangBerjalan ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-500 dark:bg-white/10 dark:text-gray-400') }}"
                                    aria-hidden="true">
                                    @if ($sudahLewat)
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="text-theme-sm {{ $sedangBerjalan ? 'font-semibold text-gray-800 dark:text-white/90' : 'text-gray-600 dark:text-gray-400' }}">
                                        {{ $tahap->label() }}
                                        @if ($sedangBerjalan)
                                            <span class="text-theme-xs font-normal text-brand-600 dark:text-brand-400">
                                                (tahap saat ini)
                                            </span>
                                        @endif
                                    </p>
                                    @if ($sedangBerjalan)
                                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                            {{ $penjelasan[$tahap->value] }}
                                        </p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{--
                    Catatan penanganan. HANYA status, tanggal, dan catatan yang
                    ditampilkan. Nama petugas pun tidak disertakan agar tidak
                    memancing warga menghubungi orang tertentu secara langsung.
                --}}
                <div class="p-5 sm:p-6">
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">Catatan Petugas</p>

                    @if (empty($riwayat))
                        <p class="mt-3 rounded-lg bg-gray-50 p-4 text-theme-sm text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                            Belum ada catatan penanganan. Laporan Anda masih menunggu diperiksa petugas.
                        </p>
                    @else
                        <ol class="mt-4 space-y-4">
                            @foreach ($riwayat as $jejak)
                                <li class="rounded-lg border border-gray-200 p-4 dark:border-navy-700">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-sim.status-badge
                                            :status="StatusPengaduan::from($jejak['status_sesudah'])" ukuran="sm" />
                                        <span class="text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                                            {{ \Illuminate\Support\Carbon::parse($jejak['tanggal_penanganan'])->translatedFormat('d F Y') }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-theme-sm text-gray-700 dark:text-gray-300">
                                        {{ $jejak['catatan'] }}
                                    </p>

                                    {{--
                                        Keberadaan dokumen diberitahukan, tetapi berkasnya TIDAK
                                        dapat diunduh dari sini. Halaman ini terbuka tanpa login
                                        dan hanya berbekal nomor pengaduan, sehingga siapa pun
                                        yang mengetahui nomornya akan ikut memperoleh berkasnya.
                                        Dokumen tindak lanjut kerap memuat nama petugas, hasil
                                        peninjauan, dan kadang data warga lain.
                                    --}}
                                    @if (! empty($jejak['dokumen_tindak_lanjut']))
                                        <p class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-gray-50 px-2.5 py-1.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                            Petugas melampirkan dokumen tindak lanjut. Mintakan salinannya
                                            kepada petugas desa atau SP bila diperlukan.
                                        </p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    <p class="mt-5 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                        Bila laporan Anda belum ditangani dalam waktu lama, hubungi petugas desa atau
                        satuan permukiman Anda dengan menyebutkan nomor pengaduan di atas.
                    </p>
                </div>
            </div>
        @endif
    @endif
@endsection
