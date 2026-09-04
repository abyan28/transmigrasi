{{--
    Halaman Panduan Penggunaan Sistem.

    Menyajikan panduan operasional komprehensif bagi seluruh peran pengguna
    (Admin, Dinas Transmigrasi, Dinas Pertanian, Operator SP).

    Dilengkapi Table of Contents (Daftar Isi) interaktif dengan navigasi anchor,
    petunjuk bertahap per modul, serta penjelasan alur bisnis utama sistem.
--}}
@extends('layouts.app')

@section('content')
    <div x-data="{ modalPdf: false }">
        <x-sim.page-header judul="Panduan Penggunaan"
            keterangan="Buku petunjuk operasional tata kelola data dan monitoring kawasan transmigrasi Kobalima Timur."
            :remah="\App\Helpers\RemahHelper::untuk('/panduan')">
            <x-slot:aksi>
                <button type="button" @click="modalPdf = true"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 shadow-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Unduh Panduan (PDF)
                </button>
            </x-slot:aksi>
        </x-sim.page-header>

        <div class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
            {{-- Kolom Kiri: Daftar Isi (Sticky Anchor Navigation) --}}
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <nav class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"
                    aria-label="Daftar isi panduan">
                    <div class="flex items-center gap-2 border-b border-gray-200 pb-3 dark:border-gray-800">
                        <svg class="h-5 w-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        <h2 class="text-theme-sm font-bold text-gray-800 dark:text-white/90">
                            Daftar Isi Panduan
                        </h2>
                    </div>

                    <ul class="mt-3 space-y-1 text-theme-xs">
                        <li>
                            <a href="#peran-akses"
                                class="block rounded-lg px-2.5 py-1.5 text-gray-600 transition hover:bg-gray-100 hover:text-brand-600 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-brand-400">
                                1. Peran &amp; Hak Akses Pengguna
                            </a>
                        </li>
                        <li>
                            <a href="#dashboard-indikator"
                                class="block rounded-lg px-2.5 py-1.5 text-gray-600 transition hover:bg-gray-100 hover:text-brand-600 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-brand-400">
                                2. Dashboard Monitoring
                            </a>
                        </li>
                        <li>
                            <a href="#master-wilayah"
                                class="block rounded-lg px-2.5 py-1.5 text-gray-600 transition hover:bg-gray-100 hover:text-brand-600 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-brand-400">
                                3. Wilayah &amp; Satuan Permukiman
                            </a>
                        </li>
                        <li>
                            <a href="#kependudukan-lahan"
                                class="block rounded-lg px-2.5 py-1.5 text-gray-600 transition hover:bg-gray-100 hover:text-brand-600 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-brand-400">
                                4. Kependudukan, Rumah, &amp; Lahan
                            </a>
                        </li>
                        <li>
                            <a href="#kelembagaan-pertanian"
                                class="block rounded-lg px-2.5 py-1.5 text-gray-600 transition hover:bg-gray-100 hover:text-brand-600 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-brand-400">
                                5. Pertanian, Alsintan, &amp; Panen
                            </a>
                        </li>
                        <li>
                            <a href="#pengaduan-warga"
                                class="block rounded-lg px-2.5 py-1.5 text-gray-600 transition hover:bg-gray-100 hover:text-brand-600 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-brand-400">
                                6. Layanan Pengaduan Warga
                            </a>
                        </li>
                        <li>
                            <a href="#laporan-ekspor"
                                class="block rounded-lg px-2.5 py-1.5 text-gray-600 transition hover:bg-gray-100 hover:text-brand-600 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-brand-400">
                                7. Laporan Kawasan &amp; Cetak
                            </a>
                        </li>
                        <li>
                            <a href="#faq-bantuan"
                                class="block rounded-lg px-2.5 py-1.5 text-gray-600 transition hover:bg-gray-100 hover:text-brand-600 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-brand-400">
                                8. Tanya Jawab &amp; Bantuan
                            </a>
                        </li>
                    </ul>

                    <div class="mt-5 rounded-xl border border-yellow-200 bg-yellow-50 p-3 dark:border-yellow-500/30 dark:bg-yellow-500/10">
                        <p class="text-[11px] text-yellow-800 dark:text-yellow-200">
                            <strong>Tip Navigasi:</strong> Klik judul bab di atas untuk melompat langsung ke penjelasan modul terkait.
                        </p>
                    </div>
                </nav>
            </aside>

            {{-- Kolom Kanan: Isi Panduan Operasional Lengkap --}}
            <main class="min-w-0 space-y-8">
                {{-- Bab 1: Peran & Hak Akses --}}
                <section id="peran-akses" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-theme-sm font-bold text-white">
                            1
                        </span>
                        <div>
                            <h3 class="text-theme-base font-bold text-gray-800 dark:text-white/90">
                                Peran &amp; Hak Akses Pengguna (Role-Based Access Control)
                            </h3>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                                Pengaturan kewenangan dan batas akses data petugas sistem
                            </p>
                        </div>
                    </div>

                    <p class="mt-4 text-theme-sm leading-relaxed text-gray-600 dark:text-gray-400">
                        Sistem Informasi Transmigrasi mengimplementasikan pembatasan akses berbasis peran (RBAC) dinamis. Terdapat 4 profil peran utama:
                    </p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 p-3.5 dark:border-gray-800">
                            <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-theme-xs font-semibold text-purple-700 dark:bg-purple-500/15 dark:text-purple-300">
                                Administrator Sistem
                            </span>
                            <p class="mt-2 text-theme-xs text-gray-600 dark:text-gray-400">
                                Akses penuh ke seluruh menu, konfigurasi hak akses, manajemen akun petugas, data master wilayah, dan pemantauan jejak audit log.
                            </p>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-3.5 dark:border-gray-800">
                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-theme-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">
                                Dinas Transmigrasi
                            </span>
                            <p class="mt-2 text-theme-xs text-gray-600 dark:text-gray-400">
                                Pengawasan kawasan, pengelolaan kependudukan transmigran, data rumah dan hunian, status lahan, fasilitas SP, serta penanganan pengaduan bidang permukiman.
                            </p>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-3.5 dark:border-gray-800">
                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-theme-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                Dinas Pertanian
                            </span>
                            <p class="mt-2 text-theme-xs text-gray-600 dark:text-gray-400">
                                Pemantauan data kelompok tani, distribusi bantuan alsintan, penyaluran benih/pupuk saprotan, musim tanam, dan rekonsiliasi hasil panen kawasan.
                            </p>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-3.5 dark:border-gray-800">
                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-theme-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                Operator Satuan Permukiman
                            </span>
                            <p class="mt-2 text-theme-xs text-gray-600 dark:text-gray-400">
                                Penginput data lapangan di tingkat tapak (terbatas pada wilayah SP yang ditugaskan). Berwenang menambah dan memperbarui data tanpa hak menghapus.
                            </p>
                        </div>
                    </div>
                </section>

                {{-- Bab 2: Dashboard Monitoring --}}
                <section id="dashboard-indikator" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-theme-sm font-bold text-white">
                            2
                        </span>
                        <div>
                            <h3 class="text-theme-base font-bold text-gray-800 dark:text-white/90">
                                Dashboard &amp; Analisis Indikator Kawasan
                            </h3>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                                Membaca visualisasi data, tren demografi, komoditas, dan status pengaduan
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3 text-theme-sm text-gray-600 dark:text-gray-400">
                        <p>Dashboard utama menyajikan gambaran menyeluruh kondisi kawasan transmigrasi yang terbagi dalam 4 bagian analitis:</p>
                        <ul class="list-disc space-y-1 pl-5 text-theme-xs">
                            <li><strong>Ringkasan Indikator Kunci:</strong> Kartu statistik jumlah KK, total Jiwa, jumlah Petani, total Luas Lahan Usaha &amp; Pekarangan, dan total Volume Panen tahun berjalan.</li>
                            <li><strong>Dinamika Kependudukan:</strong> Grafik garis multi-series tren penduduk (Jiwa, KK, Petani) 10 tahun terakhir dan perbandingan demografi antar-SP.</li>
                            <li><strong>Produksi &amp; Komoditas Pertanian:</strong> Donut chart sebaran volume komoditas unggulan (Jagung, Padi, Kedelai, Ubi Kayu, Kacang Tanah, Hortikultura).</li>
                            <li><strong>Kondisi Infrastruktur &amp; Status Pengaduan:</strong> Evaluasi fasilitas dasar SP (air, jalan, listrik, irigasi) serta donut chart status pengaduan warga.</li>
                        </ul>
                    </div>
                </section>

                {{-- Bab 3: Master Wilayah & SP --}}
                <section id="master-wilayah" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-theme-sm font-bold text-white">
                            3
                        </span>
                        <div>
                            <h3 class="text-theme-base font-bold text-gray-800 dark:text-white/90">
                                Tata Kelola Wilayah &amp; Satuan Permukiman
                            </h3>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                                Hirarki administratif, satuan permukiman, inventaris, dan fasilitas
                            </p>
                        </div>
                    </div>

                    <p class="mt-4 text-theme-sm leading-relaxed text-gray-600 dark:text-gray-400">
                        Struktur data wilayah memiliki hierarki ganda: jalur administratif (Kabupaten &rarr; Kecamatan &rarr; Desa) dan jalur program ketransmigrasian (Kawasan &rarr; Satuan Permukiman).
                    </p>

                    <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-theme-xs dark:border-gray-800 dark:bg-white/[0.02]">
                        <h4 class="font-semibold text-gray-800 dark:text-white/90">Submenu Wilayah &amp; SP:</h4>
                        <ol class="mt-2 list-decimal space-y-1 pl-4 text-gray-600 dark:text-gray-400">
                            <li><strong>Kawasan Transmigrasi:</strong> Data profil kawasan, koordinat batas, dan SK penetapan wilayah.</li>
                            <li><strong>Satuan Permukiman:</strong> Profil 6 SP, luas wilayah tapak, daya tampung, dan status perkembangan SP.</li>
                            <li><strong>Inventaris SP:</strong> Aset bergerak milik permukiman (kendaraan operasional, genset, pompa air komunal).</li>
                            <li><strong>Fasilitas SP:</strong> Sarana ibadah, balai warga, puskesmas pembantu, dan posyandu per SP.</li>
                            <li><strong>Infrastruktur SP:</strong> Jaringan irigasi, jalan poros, ketersediaan air bersih, dan pasokan listrik.</li>
                        </ol>
                    </div>
                </section>

                {{-- Bab 4: Kependudukan, Rumah, & Lahan --}}
                <section id="kependudukan-lahan" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-theme-sm font-bold text-white">
                            4
                        </span>
                        <div>
                            <h3 class="text-theme-base font-bold text-gray-800 dark:text-white/90">
                                Kependudukan, Rumah, &amp; Penguasaan Lahan
                            </h3>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                                Manajemen data transmigran, kondisi hunian, dan sertifikasi hak milik lahan
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3 text-theme-sm text-gray-600 dark:text-gray-400">
                        <div class="rounded-xl border border-teal-200 bg-teal-50/50 p-3.5 dark:border-teal-500/20 dark:bg-teal-500/5">
                            <h4 class="font-semibold text-teal-800 dark:text-teal-200 text-theme-xs">
                                Aturan Kardinalitas Utama (rules.md §10a):
                            </h4>
                            <p class="mt-1 text-theme-xs text-teal-700 dark:text-teal-300">
                                Satu Kepala Keluarga (KK) menempati <strong>tepat satu rumah</strong> (one-to-one). Satu KK dapat menguasai 1 persil lahan pekarangan dan beberapa persil lahan usaha (Lahan Usaha I dan Lahan Usaha II).
                            </p>
                        </div>

                        <ul class="list-disc space-y-1.5 pl-5 text-theme-xs">
                            <li><strong>Perekaman Transmigran:</strong> Input NIK 16-digit, Nomor KK, data anggota keluarga, daerah asal (Transmigran Penduduk Asal / TPA vs Penduduk Setempat / TPS), dan tingkat pendidikan.</li>
                            <li><strong>Suksesi Kepala Keluarga:</strong> Bila KK lama meninggal atau pindah, suksesi dilakukan dengan mengalihkan data KK ke ahli waris yang sah dalam kartu keluarga.</li>
                            <li><strong>Pencatatan Lahan:</strong> Status kepemilikan (SHM / Hak Pakai / Belum Bersertifikat), luas dalam hektare, koordinat polygon, dan riwayat komoditas tanam.</li>
                            <li><strong>Rekap Kependudukan:</strong> Filter tahun konteks memungkinkan eksplorasi demografi tahun 2016 s.d. 2026 secara konsisten.</li>
                        </ul>
                    </div>
                </section>

                {{-- Bab 5: Pertanian, Alsintan, & Panen --}}
                <section id="kelembagaan-pertanian" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-theme-sm font-bold text-white">
                            5
                        </span>
                        <div>
                            <h3 class="text-theme-base font-bold text-gray-800 dark:text-white/90">
                                Kelembagaan Poktan, Alsintan, &amp; Hasil Panen
                            </h3>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                                Pengelolaan kelompok tani, alokasi mesin pertanian, bantuan benih/pupuk, dan data panen
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3 text-theme-sm text-gray-600 dark:text-gray-400">
                        <p>Modul Pertanian menghubungkan kelembagaan tani dengan produktivitas lahan tapak transmigrasi:</p>
                        <div class="grid gap-3 sm:grid-cols-2 text-theme-xs">
                            <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                                <span class="font-semibold text-gray-800 dark:text-white/90">Kelompok Tani (Poktan):</span>
                                <p class="mt-1 text-gray-600 dark:text-gray-400">Pencatatan kelembagaan poktan per SP, struktur pengurus, dan daftar petani anggota.</p>
                            </div>
                            <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                                <span class="font-semibold text-gray-800 dark:text-white/90">Bantuan Alsintan:</span>
                                <p class="mt-1 text-gray-600 dark:text-gray-400">Traktor roda 2/4, pompa air irigasi, combine harvester, dan status kondisi alat.</p>
                            </div>
                            <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                                <span class="font-semibold text-gray-800 dark:text-white/90">Saprotan (Benih &amp; Pupuk):</span>
                                <p class="mt-1 text-gray-600 dark:text-gray-400">Pencatatan volume distribusi benih jagung hibrida, padi, pupuk NPK, dan pestisida.</p>
                            </div>
                            <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                                <span class="font-semibold text-gray-800 dark:text-white/90">Siklus Tanam &amp; Panen:</span>
                                <p class="mt-1 text-gray-600 dark:text-gray-400">Realisasi tanam wajib habis saat panen: Realisasi Panen + Puso = Luas Tanam (rules.md §9.9).</p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Bab 6: Layanan Pengaduan Warga --}}
                <section id="pengaduan-warga" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-theme-sm font-bold text-white">
                            6
                        </span>
                        <div>
                            <h3 class="text-theme-base font-bold text-gray-800 dark:text-white/90">
                                Layanan Pengaduan Warga &amp; Tindak Lanjut
                            </h3>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                                Kanal publik tanpa login, pelacakan nomor tiket, dan alur status penanganan
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-4 text-theme-sm text-gray-600 dark:text-gray-400">
                        <p>
                            Warga dapat menyampaikan aduan terkait sengketa lahan, kerusakan rumah, pasokan air, jalan rusak, maupun kebutuhan alsintan melalui halaman publik <strong>/pengaduan-warga</strong> tanpa harus memiliki akun.
                        </p>

                        <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                            <h4 class="text-theme-xs font-semibold text-gray-800 dark:text-white/90">
                                Tahapan Alur Status Pengaduan:
                            </h4>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-theme-xs">
                                <span class="rounded-md bg-gray-200 px-2.5 py-1 font-semibold text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                    1. Menunggu Diterima
                                </span>
                                <span class="text-gray-400">&rarr;</span>
                                <span class="rounded-md bg-sky-100 px-2.5 py-1 font-semibold text-sky-800 dark:bg-sky-500/20 dark:text-sky-300">
                                    2. Diterima
                                </span>
                                <span class="text-gray-400">&rarr;</span>
                                <span class="rounded-md bg-amber-100 px-2.5 py-1 font-semibold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                                    3. Diproses
                                </span>
                                <span class="text-gray-400">&rarr;</span>
                                <span class="rounded-md bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">
                                    4. Selesai
                                </span>
                            </div>
                            <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                                Petugas hanya dapat memajukan status secara berurutan sesuai alur sah. Setiap tindak lanjut dilengkapi catatan resmi dan dokumentasi foto penanganan.
                            </p>
                        </div>
                    </div>
                </section>

                {{-- Bab 7: Laporan Kawasan & Cetak Dokumen --}}
                <section id="laporan-ekspor" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-theme-sm font-bold text-white">
                            7
                        </span>
                        <div>
                            <h3 class="text-theme-base font-bold text-gray-800 dark:text-white/90">
                                Laporan Resmi Kawasan &amp; Cetak Dokumen Ber-Kop
                            </h3>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                                7 Format laporan standar kementerian dengan kop dinas ganda dan tanda tangan pejabat
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3 text-theme-sm text-gray-600 dark:text-gray-400">
                        <p>Sistem menyediakan 7 laporan resmi siap cetak yang dilengkapi kop surat ganda (Lambang Pemkab Malaka dan Logo Kementerian Transmigrasi):</p>
                        <div class="grid gap-2 sm:grid-cols-2 text-theme-xs">
                            <div class="rounded-lg border border-gray-200 p-2.5 dark:border-gray-800">
                                <span class="font-medium text-gray-800 dark:text-white/90">1. Laporan Perkembangan SP</span>
                            </div>
                            <div class="rounded-lg border border-gray-200 p-2.5 dark:border-gray-800">
                                <span class="font-medium text-gray-800 dark:text-white/90">2. Laporan Kependudukan Kawasan</span>
                            </div>
                            <div class="rounded-lg border border-gray-200 p-2.5 dark:border-gray-800">
                                <span class="font-medium text-gray-800 dark:text-white/90">3. Laporan Lahan &amp; Sertifikasi</span>
                            </div>
                            <div class="rounded-lg border border-gray-200 p-2.5 dark:border-gray-800">
                                <span class="font-medium text-gray-800 dark:text-white/90">4. Laporan Kelembagaan Poktan</span>
                            </div>
                            <div class="rounded-lg border border-gray-200 p-2.5 dark:border-gray-800">
                                <span class="font-medium text-gray-800 dark:text-white/90">5. Laporan Alsintan &amp; Saprotan</span>
                            </div>
                            <div class="rounded-lg border border-gray-200 p-2.5 dark:border-gray-800">
                                <span class="font-medium text-gray-800 dark:text-white/90">6. Laporan Produksi &amp; Panen</span>
                            </div>
                            <div class="rounded-lg border border-gray-200 p-2.5 dark:border-gray-800 sm:col-span-2">
                                <span class="font-medium text-gray-800 dark:text-white/90">7. Laporan Penanganan Pengaduan Kawasan</span>
                            </div>
                        </div>
                        <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                            Fitur cetak dokumen dilengkapi filter di tempat (SP dan rentang tahun) serta tombol "Buka Tampilan Dokumen" untuk pratinjau format surat dinas sebelum dicetak atau diekspor ke PDF peramban.
                        </p>
                    </div>
                </section>

                {{-- Bab 8: Tanya Jawab Umum & Bantuan --}}
                <section id="faq-bantuan" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-theme-sm font-bold text-white">
                            8
                        </span>
                        <div>
                            <h3 class="text-theme-base font-bold text-gray-800 dark:text-white/90">
                                Tanya Jawab &amp; Panduan Kendala (FAQ)
                            </h3>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                                Solusi atas pertanyaan dan kendala operasional yang sering dihadapi petugas
                            </p>
                        </div>
                    </div>

                    {{-- Isi FAQ diatur dari Pengelolaan Konten (Task 9.6). --}}
                    <div class="mt-5 space-y-4">
                        @forelse (\App\Support\KontenSistem::faq() as $faq)
                            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                <h4 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $faq['tanya'] }}</h4>
                                <p class="mt-1.5 text-theme-xs leading-relaxed text-gray-600 dark:text-gray-400">{{ $faq['jawab'] }}</p>
                            </div>
                        @empty
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                                Belum ada tanya jawab yang didaftarkan. Admin dapat menambahkannya lewat Pengelolaan Konten.
                            </p>
                        @endforelse
                    </div>
                </section>
            </main>
        </div>

        {{-- Modal Informasi Unduh PDF --}}
        <div x-show="modalPdf" x-cloak
            class="fixed inset-0 z-99999 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-xs"
            @click.self="modalPdf = false"
            @keydown.escape.window="modalPdf = false">
            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-theme-base font-bold text-gray-800 dark:text-white/90">
                            Buku Panduan Format PDF
                        </h3>
                        <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                            Dokumen Cetak Resmi Serah Terima Sistem
                        </p>
                    </div>
                </div>

                <p class="mt-4 text-theme-xs leading-relaxed text-gray-600 dark:text-gray-400">
                    Buku Panduan Operasional (*Manual Book*) resmi dalam format berkas PDF ber-SK sedang dalam tahap finalisasi penyusunan dokumen serah terima (BAST Task 11.6). Seluruh materi dan petunjuk teknis dapat Anda baca secara lengkap dan interaktif pada halaman web ini.
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="modalPdf = false"
                        class="rounded-lg bg-brand-500 px-4 py-2 text-theme-xs font-semibold text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Mengerti &amp; Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
