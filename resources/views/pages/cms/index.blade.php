@extends('layouts.app')

{{--
    Halaman Pengelolaan Konten Sistem (CMS).

    Menyediakan antarmuka pengelolaan identitas visual, kop surat dokumen laporan,
    pejabat penandatangan, narasi profil kawasan, panduan operasional, portal pengaduan
    warga, serta banner pengumuman dinas tanpa memerlukan pengubahan kode.
--}}

@section('content')
    <x-sim.page-header judul="Pengelolaan Konten"
        keterangan="Kelola identitas visual, format kop dokumen laporan, profil kawasan, panduan, portal warga, dan pengumuman dinas."
        :remah="\App\Helpers\RemahHelper::untuk('/cms')">
        <x-slot:aksi>
            <div class="flex items-center gap-2">
                <a href="{{ route('tentang') }}" target="_blank"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                    Lihat Halaman Publik
                </a>
            </div>
        </x-slot:aksi>
    </x-sim.page-header>

    @php
        $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
        $kelasArea = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
        $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
        $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
    @endphp

    <div x-data="hashTabs('identitas')" class="min-w-0 space-y-6">
        {{-- Navigation Tabs --}}
        <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                role="tablist" aria-label="Pengelolaan Konten Sistem">
                @foreach ([
                    'identitas' => 'Identitas & Visual',
                    'laporan' => 'Kop & Dokumen Laporan',
                    'informasi' => 'Konten Profil & FAQ',
                    'portal' => 'Portal Pengaduan Warga',
                    'pengumuman' => 'Pengumuman Dinas',
                ] as $kunci => $label)
                    <button type="button" role="tab" @click="setTab('{{ $kunci }}')"
                        :aria-selected="tab === '{{ $kunci }}'"
                        :class="tab === '{{ $kunci }}'
                            ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="shrink-0 border-b-2 px-5 py-3 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- ====================================================================== --}}
            {{-- TAB 1: IDENTITAS & VISUAL BRANDING --}}
            {{-- ====================================================================== --}}
            <div x-show="tab === 'identitas'" role="tabpanel" class="p-5 sm:p-6"
                x-data="{
                    namaApp: 'Sistem Informasi Monitoring Pertanian & Tata Kelola Kawasan',
                    subjudul: 'Kawasan Transmigrasi Kobalima Timur • Kabupaten Malaka, Provinsi Nusa Tenggara Timur',
                    instansiPusat: 'Kementerian Transmigrasi Republik Indonesia',
                    instansiDaerah: 'Dinas Transmigrasi dan Tenaga Kerja Kabupaten Malaka',
                    emailBantuan: 'helpdesk@transmigrasi.malakakab.go.id',
                    teleponBantuan: '(0389) 21004',
                    waBantuan: '0812-3456-7890',
                    footerTeks: '© 2026 Kementerian Transmigrasi Republik Indonesia. Dikembangkan bersama Institut Teknologi Sepuluh Nopember (ITS).',
                    logoUtama: '{{ asset('images/logo/logo-kementrans-128.png') }}',
                    logoDaerah: '{{ asset('images/logo/lambang-malaka.png') }}',
                    favicon: '{{ asset('images/logo/favicon-32.png') }}',
                    heroBanner: '',
                    tersimpan: false,
                    unggahBerkas(kunci, event) {
                        const file = event.target.files[0];
                        if (file) {
                            this[kunci] = URL.createObjectURL(file);
                        }
                    },
                    resetBerkas(kunci, bawaan) {
                        this[kunci] = bawaan;
                    },
                    simpan() {
                        this.tersimpan = true;
                        setTimeout(() => this.tersimpan = false, 3000);
                    }
                }">

                <div class="grid gap-8 lg:grid-cols-12">
                    {{-- Form Kiri --}}
                    <div class="space-y-6 lg:col-span-7">
                        <section>
                            <h3 class="{{ $kelasBagian }}">Branding &amp; Penamaan Aplikasi</h3>
                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                Ditampilkan pada bilah navigasi, header aplikasi, serta judul tab peramban.
                            </p>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="cms_nama_app" class="{{ $kelasLabel }}">Nama Resmi Aplikasi<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_nama_app" x-model="namaApp" class="{{ $kelasKontrol }}" />
                                </div>

                                <div>
                                    <label for="cms_subjudul" class="{{ $kelasLabel }}">Subjudul &amp; Lokus Kawasan<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_subjudul" x-model="subjudul" class="{{ $kelasKontrol }}" />
                                </div>
                            </div>
                        </section>

                        {{-- Seksi Upload Logo & Aset Visual --}}
                        <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                            <h3 class="{{ $kelasBagian }}">Logo &amp; Aset Visual Branding</h3>
                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                Sesuaikan logo instansi, lambang daerah, dan ikon peramban aplikasi.
                            </p>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                {{-- Logo Utama --}}
                                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                    <label class="{{ $kelasLabel }}">Logo Utama Aplikasi / Kementerian</label>
                                    <div class="mt-2 flex items-center gap-3">
                                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800">
                                            <img :src="logoUtama" alt="Logo Utama" class="h-10 w-10 object-contain" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                                <span>Ganti Logo</span>
                                                <input type="file" accept="image/png,image/svg+xml,image/webp" @change="unggahBerkas('logoUtama', $event)" class="sr-only" />
                                            </label>
                                            <p class="text-[11px] text-gray-400">PNG/SVG transparan, maks 2 MB</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Logo Daerah --}}
                                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                    <label class="{{ $kelasLabel }}">Logo Pemerintah Daerah / Pelaksana</label>
                                    <div class="mt-2 flex items-center gap-3">
                                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800">
                                            <img :src="logoDaerah" alt="Logo Daerah" class="h-10 w-10 object-contain" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                                <span>Ganti Logo</span>
                                                <input type="file" accept="image/png,image/svg+xml,image/webp" @change="unggahBerkas('logoDaerah', $event)" class="sr-only" />
                                            </label>
                                            <p class="text-[11px] text-gray-400">Lambang Pemkab, PNG transparan</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Favicon Browser --}}
                                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                    <label class="{{ $kelasLabel }}">Favicon Tab Peramban</label>
                                    <div class="mt-2 flex items-center gap-3">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800">
                                            <img :src="favicon" alt="Favicon" class="h-6 w-6 object-contain" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                                <span>Pilih Favicon</span>
                                                <input type="file" accept="image/png,image/x-icon,image/svg+xml" @change="unggahBerkas('favicon', $event)" class="sr-only" />
                                            </label>
                                            <p class="text-[11px] text-gray-400">ICO atau PNG 32x32 / 64x64 px</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Hero Banner / Wallpaper Login --}}
                                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                    <label class="{{ $kelasLabel }}">Gambar Latar / Banner Publik</label>
                                    <div class="mt-2 flex items-center gap-3">
                                        <div class="flex h-12 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 text-gray-400 dark:border-gray-700 dark:bg-gray-800">
                                            <template x-if="heroBanner">
                                                <img :src="heroBanner" alt="Banner" class="h-full w-full object-cover" />
                                            </template>
                                            <template x-if="!heroBanner">
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                </svg>
                                            </template>
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                                <span>Pilih Banner</span>
                                                <input type="file" accept="image/jpeg,image/png,image/webp" @change="unggahBerkas('heroBanner', $event)" class="sr-only" />
                                            </label>
                                            <p class="text-[11px] text-gray-400">Rasio 16:9, JPG/WebP maks 5 MB</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                            <h3 class="{{ $kelasBagian }}">Instansi Pembina &amp; Pengelola</h3>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="cms_instansi_pusat" class="{{ $kelasLabel }}">Instansi Tingkat Pusat</label>
                                    <input type="text" id="cms_instansi_pusat" x-model="instansiPusat" class="{{ $kelasKontrol }}" />
                                </div>

                                <div>
                                    <label for="cms_instansi_daerah" class="{{ $kelasLabel }}">Instansi Tingkat Daerah</label>
                                    <input type="text" id="cms_instansi_daerah" x-model="instansiDaerah" class="{{ $kelasKontrol }}" />
                                </div>
                            </div>
                        </section>

                        <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                            <h3 class="{{ $kelasBagian }}">Kontak Layanan Bantuan &amp; Helpdesk</h3>
                            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label for="cms_email_bantuan" class="{{ $kelasLabel }}">Email Layanan</label>
                                    <input type="email" id="cms_email_bantuan" x-model="emailBantuan" class="{{ $kelasKontrol }}" />
                                </div>

                                <div>
                                    <label for="cms_telp_bantuan" class="{{ $kelasLabel }}">Telepon Kantor</label>
                                    <input type="text" id="cms_telp_bantuan" x-model="teleponBantuan" class="{{ $kelasKontrol }} tabular-nums" />
                                </div>

                                <div>
                                    <label for="cms_wa_bantuan" class="{{ $kelasLabel }}">WhatsApp Bantuan</label>
                                    <input type="text" id="cms_wa_bantuan" x-model="waBantuan" class="{{ $kelasKontrol }} tabular-nums" />
                                </div>
                            </div>
                        </section>

                        <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                            <h3 class="{{ $kelasBagian }}">Catatan Kaki &amp; Hak Cipta</h3>
                            <div class="mt-4">
                                <label for="cms_footer" class="{{ $kelasLabel }}">Teks Hak Cipta</label>
                                <textarea id="cms_footer" x-model="footerTeks" rows="2" class="{{ $kelasArea }}"></textarea>
                            </div>
                        </section>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="button" @click="simpan()"
                                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Simpan Identitas &amp; Visual
                            </button>

                            <span x-show="tersimpan" x-cloak x-transition
                                class="text-theme-xs font-medium text-success-600 dark:text-success-400">
                                Perubahan identitas dan aset visual berhasil disimpan.
                            </span>
                        </div>
                    </div>

                    {{-- Pratinjau Kanan (Live Preview) --}}
                    <div class="lg:col-span-5">
                        <div class="sticky top-24 rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-700">
                                <h4 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Pratinjau Langsung
                                </h4>
                                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-theme-xs font-medium text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                    Live Preview
                                </span>
                            </div>

                            <div class="mt-4 space-y-4">
                                {{-- Mock Browser Tab Preview --}}
                                <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-100 text-theme-xs dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-200/70 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/60">
                                        <div class="flex items-center gap-1.5 rounded-t-lg bg-white px-2.5 py-1 text-gray-700 shadow-xs dark:bg-gray-800 dark:text-gray-200">
                                            <img :src="favicon" alt="Favicon" class="h-3.5 w-3.5 object-contain" />
                                            <span class="max-w-[140px] truncate text-[11px] font-medium" x-text="namaApp"></span>
                                        </div>
                                    </div>
                                    <div class="p-3 text-center text-gray-400">
                                        <span class="text-[11px]">Simulasi Tampilan Tab Peramban</span>
                                    </div>
                                </div>

                                {{-- Card Branding Preview --}}
                                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-500/10 p-2 dark:bg-brand-500/20">
                                                <img :src="logoUtama" alt="Logo Utama" class="h-8 w-8 object-contain" />
                                            </div>
                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-800">
                                                <img :src="logoDaerah" alt="Logo Daerah" class="h-8 w-8 object-contain" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-theme-sm font-bold text-gray-800 dark:text-white/90" x-text="namaApp"></p>
                                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400" x-text="subjudul"></p>
                                    </div>

                                    <div class="mt-4 border-t border-gray-100 pt-3 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400 space-y-1">
                                        <p><span class="font-medium text-gray-700 dark:text-gray-300">Pusat:</span> <span x-text="instansiPusat"></span></p>
                                        <p><span class="font-medium text-gray-700 dark:text-gray-300">Daerah:</span> <span x-text="instansiDaerah"></span></p>
                                    </div>
                                </div>

                                {{-- Helpdesk Box Preview --}}
                                <div class="rounded-xl border border-gray-200 bg-white p-4 text-theme-xs dark:border-gray-700 dark:bg-gray-900">
                                    <p class="font-semibold text-gray-800 dark:text-white/90">Pusat Bantuan &amp; Kontak</p>
                                    <div class="mt-2 space-y-1.5 text-gray-600 dark:text-gray-400">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-400">✉</span>
                                            <span class="tabular-nums" x-text="emailBantuan"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-400">☎</span>
                                            <span class="tabular-nums" x-text="teleponBantuan"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-400">💬</span>
                                            <span class="tabular-nums" x-text="waBantuan"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Footer Preview --}}
                                <div class="rounded-xl border border-gray-200 bg-white p-3 text-center text-theme-xs text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400"
                                    x-text="footerTeks">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ====================================================================== --}}
            {{-- TAB 2: FORMAT DOKUMEN LAPORAN & KOP SURAT (BARU) --}}
            {{-- ====================================================================== --}}
            <div x-show="tab === 'laporan'" x-cloak role="tabpanel" class="p-5 sm:p-6"
                x-data="{
                    kopKementerian: 'Kementerian Transmigrasi Republik Indonesia',
                    kopPemerintah: 'Pemerintah Kabupaten Malaka',
                    kopDinas: 'Dinas Transmigrasi dan Tenaga Kerja Kabupaten Malaka',
                    kopAlamat: 'Jalan Raya Betun, Kompleks Perkantoran Pemerintah Daerah Kab. Malaka, Nusa Tenggara Timur',
                    kopKontak: 'Telepon (0389) 123456  |  Email distrans@malakakab.go.id',
                    logoKiri: '{{ asset('images/logo/logo-kementrans-128.png') }}',
                    logoKanan: '{{ asset('images/logo/lambang-malaka.png') }}',
                    tampilkanTtd: true,
                    titimangsaTempat: 'Betun',
                    ttdJabatan: 'Kepala Dinas Transmigrasi dan Tenaga Kerja Kabupaten Malaka',
                    ttdNama: 'Drs. Agustinus Nahak, M.Si.',
                    ttdPangkat: 'Pembina Utama Muda (IV/c)',
                    ttdNip: '19750812 199903 1 004',
                    tersimpan: false,
                    unggahKopLogo(kunci, event) {
                        const file = event.target.files[0];
                        if (file) {
                            this[kunci] = URL.createObjectURL(file);
                        }
                    },
                    simpan() {
                        this.tersimpan = true;
                        setTimeout(() => this.tersimpan = false, 3000);
                    }
                }">

                <div class="grid gap-8 lg:grid-cols-12">
                    {{-- Form Kiri --}}
                    <div class="space-y-6 lg:col-span-6">
                        <section>
                            <h3 class="{{ $kelasBagian }}">Kop Surat Dokumen Laporan Resmi</h3>
                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                Teks dan logo yang dicetak pada bagian atas seluruh 7 laporan resmi (<a href="{{ route('laporan.transmigran') }}" target="_blank" class="text-brand-600 hover:underline">/laporan</a>).
                            </p>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="cms_kop_kementerian" class="{{ $kelasLabel }}">Nama Kementerian Pembina<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_kop_kementerian" x-model="kopKementerian" class="{{ $kelasKontrol }}" />
                                </div>

                                <div>
                                    <label for="cms_kop_pemda" class="{{ $kelasLabel }}">Pemerintah Daerah<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_kop_pemda" x-model="kopPemerintah" class="{{ $kelasKontrol }}" />
                                </div>

                                <div>
                                    <label for="cms_kop_dinas" class="{{ $kelasLabel }}">Nama Dinas Pelaksana<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_kop_dinas" x-model="kopDinas" class="{{ $kelasKontrol }}" />
                                </div>

                                <div>
                                    <label for="cms_kop_alamat" class="{{ $kelasLabel }}">Alamat Kantor Dinas<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_kop_alamat" x-model="kopAlamat" class="{{ $kelasKontrol }}" />
                                </div>

                                <div>
                                    <label for="cms_kop_kontak" class="{{ $kelasLabel }}">Kontak &amp; Layanan Dinas<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_kop_kontak" x-model="kopKontak" class="{{ $kelasKontrol }}" />
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2 pt-2">
                                    <div class="rounded-xl border border-gray-200 p-3.5 dark:border-gray-800">
                                        <label class="{{ $kelasLabel }}">Logo Kiri (Kementerian)</label>
                                        <div class="mt-2 flex items-center gap-2.5">
                                            <img :src="logoKiri" alt="Logo Kiri" class="h-10 w-10 shrink-0 object-contain" />
                                            <label class="cursor-pointer rounded-lg border border-gray-300 px-2.5 py-1 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">
                                                <span>Ganti</span>
                                                <input type="file" accept="image/*" @change="unggahKopLogo('logoKiri', $event)" class="sr-only" />
                                            </label>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-gray-200 p-3.5 dark:border-gray-800">
                                        <label class="{{ $kelasLabel }}">Logo Kanan (Daerah)</label>
                                        <div class="mt-2 flex items-center gap-2.5">
                                            <img :src="logoKanan" alt="Logo Kanan" class="h-10 w-10 shrink-0 object-contain" />
                                            <label class="cursor-pointer rounded-lg border border-gray-300 px-2.5 py-1 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">
                                                <span>Ganti</span>
                                                <input type="file" accept="image/*" @change="unggahKopLogo('logoKanan', $event)" class="sr-only" />
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- Seksi Lembar Pengesahan / Tanda Tangan --}}
                        <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                            <div class="flex items-center justify-between">
                                <h3 class="{{ $kelasBagian }}">Pejabat Penandatangan Dokumen</h3>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="tampilkanTtd" class="sr-only peer" />
                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-500"></div>
                                    <span class="text-theme-sm font-medium text-gray-700 dark:text-gray-300"
                                        x-text="tampilkanTtd ? 'Ttd: Ditampilkan' : 'Ttd: Disembunyikan'"></span>
                                </label>
                            </div>
                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                Dicetak pada bagian akhir laporan sebagai lembar pengesahan resmi.
                            </p>

                            <div class="mt-4 space-y-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="cms_titimangsa_tempat" class="{{ $kelasLabel }}">Kota / Tempat Titimangsa<span class="text-error-500">*</span></label>
                                        <input type="text" id="cms_titimangsa_tempat" x-model="titimangsaTempat" class="{{ $kelasKontrol }}" />
                                    </div>
                                    <div>
                                        <label for="cms_ttd_pangkat" class="{{ $kelasLabel }}">Pangkat / Golongan</label>
                                        <input type="text" id="cms_ttd_pangkat" x-model="ttdPangkat" class="{{ $kelasKontrol }}" />
                                    </div>
                                </div>

                                <div>
                                    <label for="cms_ttd_jabatan" class="{{ $kelasLabel }}">Jabatan Penandatangan<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_ttd_jabatan" x-model="ttdJabatan" class="{{ $kelasKontrol }}" />
                                </div>

                                <div>
                                    <label for="cms_ttd_nama" class="{{ $kelasLabel }}">Nama Lengkap &amp; Gelar Pejabat<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_ttd_nama" x-model="ttdNama" class="{{ $kelasKontrol }}" />
                                </div>

                                <div>
                                    <label for="cms_ttd_nip" class="{{ $kelasLabel }}">Nomor Induk Pegawai (NIP)<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_ttd_nip" x-model="ttdNip" class="{{ $kelasKontrol }} tabular-nums font-mono" />
                                </div>
                            </div>
                        </section>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="button" @click="simpan()"
                                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Simpan Format Laporan
                            </button>

                            <span x-show="tersimpan" x-cloak x-transition
                                class="text-theme-xs font-medium text-success-600 dark:text-success-400">
                                Format kop dan tanda tangan laporan berhasil disimpan.
                            </span>
                        </div>
                    </div>

                    {{-- Pratinjau Kertas Laporan Kanan (Live Paper Preview) --}}
                    <div class="lg:col-span-6">
                        <div class="sticky top-24 rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-700">
                                <h4 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Pratinjau Kertas Laporan
                                </h4>
                                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-theme-xs font-medium text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                    Simulasi Cetak A4
                                </span>
                            </div>

                            {{-- Simulasi Lembar Kertas A4 --}}
                            <div class="mt-4 rounded-xl border border-gray-300 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                {{-- Kop Dokumen Live Preview --}}
                                <div class="border-b-[2.5px] border-gray-900 pb-3 text-center dark:border-gray-100">
                                    <div class="flex items-center justify-between gap-2">
                                        <img :src="logoKiri" alt="Logo Kiri" class="h-12 w-12 shrink-0 object-contain" />
                                        <div class="min-w-0 flex-1 leading-tight text-center">
                                            <p class="text-[11px] font-semibold uppercase text-gray-700 dark:text-gray-300" x-text="kopKementerian"></p>
                                            <p class="text-[11px] font-semibold uppercase text-gray-700 dark:text-gray-300" x-text="kopPemerintah"></p>
                                            <p class="text-theme-xs font-bold uppercase text-gray-900 dark:text-white" x-text="kopDinas"></p>
                                            <p class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400" x-text="kopAlamat"></p>
                                            <p class="text-[10px] text-gray-500 dark:text-gray-400" x-text="kopKontak"></p>
                                        </div>
                                        <img :src="logoKanan" alt="Logo Kanan" class="h-12 w-12 shrink-0 object-contain" />
                                    </div>
                                </div>

                                {{-- Judul Laporan Dummy --}}
                                <div class="py-4 text-center">
                                    <h5 class="text-theme-sm font-bold uppercase tracking-wide text-gray-900 dark:text-white">
                                        LAPORAN HASIL PANEN KOMODITAS PERTANIAN
                                    </h5>
                                    <p class="mt-0.5 text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                                        TAHUN ANGGARAN {{ date('Y') }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-gray-400">
                                        Kawasan Transmigrasi Kobalima Timur • 6 Satuan Permukiman
                                    </p>
                                </div>

                                {{-- Mock Garis Tabel Laporan --}}
                                <div class="my-2 space-y-1.5 rounded border border-gray-100 bg-gray-50 p-2.5 dark:border-gray-800 dark:bg-gray-800/40">
                                    <div class="h-2 w-full rounded bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="h-2 w-5/6 rounded bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="h-2 w-4/6 rounded bg-gray-200 dark:bg-gray-700"></div>
                                </div>

                                {{-- Lembar Pengesahan / Tanda Tangan Live Preview --}}
                                <template x-if="tampilkanTtd">
                                    <div class="mt-6 flex justify-end">
                                        <div class="w-60 text-center text-theme-xs text-gray-800 dark:text-gray-200">
                                            <p><span x-text="titimangsaTempat"></span>, {{ date('d F Y') }}</p>
                                            <p class="mt-0.5 font-medium leading-tight" x-text="ttdJabatan"></p>
                                            
                                            {{-- Ruang Tanda Tangan --}}
                                            <div class="my-10 text-[10px] italic text-gray-300 dark:text-gray-600">
                                                (Tanda Tangan &amp; Cap Basah)
                                            </div>

                                            <p class="font-bold underline" x-text="ttdNama"></p>
                                            <p class="text-[11px] text-gray-600 dark:text-gray-400" x-text="ttdPangkat"></p>
                                            <p class="text-[11px] font-mono text-gray-600 dark:text-gray-400">NIP. <span x-text="ttdNip"></span></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ====================================================================== --}}
            {{-- TAB 3: KONTEN INFORMASI (TENTANG & PANDUAN) --}}
            {{-- ====================================================================== --}}
            <div x-show="tab === 'informasi'" x-cloak role="tabpanel" class="p-5 sm:p-6"
                x-data="{
                    latarBelakang: 'Kawasan Transmigrasi Kobalima Timur memiliki potensi agroekologis yang strategis dengan komoditas unggulan utama tanaman jagung, padi, palawija, dan hortikultura. Sistem informasi ini dikembangkan sebagai basis data terpadu untuk mendigitalisasi pemantauan kondisi kependudukan, penguasaan lahan usaha dan pekarangan, sarana produksi, bantuan alat mesin pertanian, realisasi penanaman, serta hasil panen secara transparan dan akuntabel.',
                    faqs: [
                        { tanya: 'Bagaimana cara menambahkan data transmigran baru?', jawab: 'Buka menu Transmigrasi > Penduduk & Lahan > Transmigran, lalu klik tombol Tambah Transmigran di sudut kanan atas.' },
                        { tanya: 'Kapan laporan panen perlu diperbarui?', jawab: 'Pencatatan dilakukan secara berkala setiap kali kelompok tani binaan menyelesaikan siklus panen komoditas di wilayahnya.' },
                        { tanya: 'Bagaimana alur penanganan pengaduan warga?', jawab: 'Pengaduan masuk berstatus Menunggu Diterima, diverifikasi oleh petugas menjadi Diterima, diproses tindak lanjut lapangannya, lalu ditandai Selesai setelah masalah terselesaikan.' }
                    ],
                    tambahFaq() {
                        this.faqs.push({ tanya: 'Pertanyaan baru', jawab: 'Tuliskan jawaban panduan di sini.' });
                    },
                    hapusFaq(idx) {
                        this.faqs.splice(idx, 1);
                    },
                    tersimpan: false,
                    simpan() {
                        this.tersimpan = true;
                        setTimeout(() => this.tersimpan = false, 3000);
                    }
                }">

                <div class="space-y-6">
                    <section>
                        <h3 class="{{ $kelasBagian }}">Narasi Halaman Tentang Sistem</h3>
                        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                            Teks profil dan tujuan strategis yang tampil pada halaman rujukan informasi publik (<a href="{{ route('tentang') }}" class="text-brand-600 hover:underline">/tentang</a>).
                        </p>

                        <div class="mt-4">
                            <label for="cms_latar_belakang" class="{{ $kelasLabel }}">Latar Belakang &amp; Tujuan Kawasan</label>
                            <textarea id="cms_latar_belakang" x-model="latarBelakang" rows="5" class="{{ $kelasArea }}"></textarea>
                        </div>
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="{{ $kelasBagian }}">Daftar Tanya Jawab / FAQ Panduan</h3>
                                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                    Pertanyaan populer yang tampil pada modul Panduan Penggunaan (<a href="{{ route('panduan') }}" class="text-brand-600 hover:underline">/panduan</a>).
                                </p>
                            </div>
                            <button type="button" @click="tambahFaq()"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                + Tambah FAQ
                            </button>
                        </div>

                        <div class="mt-4 space-y-3">
                            <template x-for="(faq, idx) in faqs" :key="idx">
                                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 space-y-2">
                                            <input type="text" x-model="faq.tanya" placeholder="Pertanyaan..."
                                                class="{{ $kelasKontrol }} font-medium" />
                                            <textarea x-model="faq.jawab" rows="2" placeholder="Jawaban penjelasan..."
                                                class="{{ $kelasArea }}"></textarea>
                                        </div>
                                        <button type="button" @click="hapusFaq(idx)" aria-label="Hapus FAQ"
                                            class="rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </section>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="button" @click="simpan()"
                            class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Simpan Konten Informasi
                        </button>

                        <span x-show="tersimpan" x-cloak x-transition
                            class="text-theme-xs font-medium text-success-600 dark:text-success-400">
                            Konten informasi berhasil disimpan.
                        </span>
                    </div>
                </div>
            </div>

            {{-- ====================================================================== --}}
            {{-- TAB 4: PORTAL PENGADUAN WARGA --}}
            {{-- ====================================================================== --}}
            <div x-show="tab === 'portal'" x-cloak role="tabpanel" class="p-5 sm:p-6"
                x-data="{
                    sambutan: 'Sampaikan laporan, kendala pertanian, atau keluhan fasilitas di lingkungan satuan permukiman Anda. Laporan akan ditindaklanjuti langsung oleh dinas terkait.',
                    formatNomor: 'PGD-{TAHUN}-{NOMOR}',
                    disclaimer: 'Identitas pelapor dilindungi dan hanya digunakan untuk keperluan verifikasi lapangan oleh petugas resmi kementerian dan dinas.',
                    hotlineDarurat: '0811-2345-6789',
                    tersimpan: false,
                    simpan() {
                        this.tersimpan = true;
                        setTimeout(() => this.tersimpan = false, 3000);
                    }
                }">

                <div class="max-w-3xl space-y-6">
                    <section>
                        <h3 class="{{ $kelasBagian }}">Petunjuk &amp; Teks Sambutan Warga</h3>
                        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                            Teks pembuka pada form pengaduan mandiri tanpa login (<a href="{{ route('pengaduan.index') }}" class="text-brand-600 hover:underline">/pengaduan</a>).
                        </p>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="cms_sambutan" class="{{ $kelasLabel }}">Teks Sambutan Warga</label>
                                <textarea id="cms_sambutan" x-model="sambutan" rows="3" class="{{ $kelasArea }}"></textarea>
                            </div>

                            <div>
                                <label for="cms_disclaimer" class="{{ $kelasLabel }}">Teks Jaminan Kerahasiaan &amp; Disclaimer</label>
                                <textarea id="cms_disclaimer" x-model="disclaimer" rows="2" class="{{ $kelasArea }}"></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                        <h3 class="{{ $kelasBagian }}">Format Tiket &amp; Kontak Darurat</h3>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="cms_format_tiket" class="{{ $kelasLabel }}">Format Nomor Registrasi Tiket</label>
                                <input type="text" id="cms_format_tiket" x-model="formatNomor" class="{{ $kelasKontrol }} tabular-nums font-mono" />
                                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                    Contoh hasil: <code class="font-mono text-brand-600">PGD-2026-0042</code>
                                </p>
                            </div>

                            <div>
                                <label for="cms_hotline" class="{{ $kelasLabel }}">Saluran Cepat / Hotline WhatsApp</label>
                                <input type="text" id="cms_hotline" x-model="hotlineDarurat" class="{{ $kelasKontrol }} tabular-nums" />
                            </div>
                        </div>
                    </section>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="button" @click="simpan()"
                            class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Simpan Pengaturan Portal
                        </button>

                        <span x-show="tersimpan" x-cloak x-transition
                            class="text-theme-xs font-medium text-success-600 dark:text-success-400">
                            Pengaturan portal publik tersimpan.
                        </span>
                    </div>
                </div>
            </div>

            {{-- ====================================================================== --}}
            {{-- TAB 5: PENGUMUMAN DINAS (DASHBOARD BROADCAST) --}}
            {{-- ====================================================================== --}}
            <div x-show="tab === 'pengumuman'" x-cloak role="tabpanel" class="p-5 sm:p-6"
                x-data="{
                    isAktif: true,
                    judulPengumuman: 'Penyaluran Bantuan Benih Jagung Musim Tanam 2026',
                    isiPengumuman: 'Diberitahukan kepada seluruh koordinator SP dan ketua kelompok tani bahwa distribusi bantuan benih jagung varietas Bisi-18 akan dimulai pada pekan depan melalui kantor UPT masing-masing.',
                    tipe: 'info',
                    tersimpan: false,
                    simpan() {
                        this.tersimpan = true;
                        setTimeout(() => this.tersimpan = false, 3000);
                    }
                }">

                <div class="grid gap-8 lg:grid-cols-12">
                    <div class="space-y-6 lg:col-span-7">
                        <section>
                            <div class="flex items-center justify-between">
                                <h3 class="{{ $kelasBagian }}">Pengumuman Broadcast Dasbor</h3>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="isAktif" class="sr-only peer" />
                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-500"></div>
                                    <span class="text-theme-sm font-medium text-gray-700 dark:text-gray-300"
                                        x-text="isAktif ? 'Status: Aktif' : 'Status: Dinonaktifkan'"></span>
                                </label>
                            </div>

                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                Banner pengumuman penting yang tampil di bagian atas dashboard seluruh petugas dan pimpinan.
                            </p>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="cms_judul_pengumuman" class="{{ $kelasLabel }}">Judul Pengumuman<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_judul_pengumuman" x-model="judulPengumuman" class="{{ $kelasKontrol }}" />
                                </div>

                                <div>
                                    <label for="cms_tipe_pengumuman" class="{{ $kelasLabel }}">Tingkat Kegentingan / Jenis Pesan</label>
                                    <select id="cms_tipe_pengumuman" x-model="tipe" class="{{ $kelasKontrol }}">
                                        <option value="info">Informasi Umum (Biru / Brand)</option>
                                        <option value="success">Kabar Baik / Sukses (Hijau)</option>
                                        <option value="warning">Peringatan / Perhatian (Kuning)</option>
                                        <option value="error">Mendesak / Darurat (Merah)</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="cms_isi_pengumuman" class="{{ $kelasLabel }}">Isi Pesan Pengumuman<span class="text-error-500">*</span></label>
                                    <textarea id="cms_isi_pengumuman" x-model="isiPengumuman" rows="4" class="{{ $kelasArea }}"></textarea>
                                </div>
                            </div>
                        </section>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="button" @click="simpan()"
                                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Simpan Pengumuman
                            </button>

                            <span x-show="tersimpan" x-cloak x-transition
                                class="text-theme-xs font-medium text-success-600 dark:text-success-400">
                                Pengumuman dasbor berhasil diperbarui.
                            </span>
                        </div>
                    </div>

                    {{-- Pratinjau Banner Kanan --}}
                    <div class="lg:col-span-5">
                        <div class="sticky top-24 rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                            <h4 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-200 pb-3 dark:border-gray-700">
                                Pratinjau Tampilan Banner Dasbor
                            </h4>

                            <div class="mt-4">
                                <template x-if="isAktif">
                                    <div class="rounded-xl border p-4 transition-all"
                                        :class="{
                                            'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200': tipe === 'info',
                                            'border-green-200 bg-green-50 text-green-900 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-200': tipe === 'success',
                                            'border-yellow-300 bg-yellow-50 text-yellow-900 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-200': tipe === 'warning',
                                            'border-red-200 bg-red-50 text-red-900 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200': tipe === 'error'
                                        }">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 text-lg"
                                                x-text="tipe === 'info' ? 'ℹ️' : (tipe === 'success' ? '✅' : (tipe === 'warning' ? '⚠️' : '🚨'))"></span>
                                            <div>
                                                <h5 class="text-theme-sm font-bold" x-text="judulPengumuman"></h5>
                                                <p class="mt-1 text-theme-xs leading-relaxed opacity-90" x-text="isiPengumuman"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="!isAktif">
                                    <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-theme-sm text-gray-400 dark:border-gray-700">
                                        Banner pengumuman saat ini dinonaktifkan dan tidak tampil pada dasbor.
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
