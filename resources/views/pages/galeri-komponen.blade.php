{{--
    Galeri komponen bersama.

    Halaman internal untuk memeriksa seluruh komponen merender dengan benar di
    kedua mode tema. Bukan bagian aplikasi yang dilihat pengguna akhir, tetapi
    dipertahankan sebagai acuan pemakaian saat membangun halaman berikutnya.
--}}
@extends('layouts.app')

@section('content')
    <x-sim.page-header judul="Galeri Komponen"
        keterangan="Acuan pemakaian komponen bersama. Halaman ini hanya untuk pengembangan."
        :remah="[['label' => 'Pengembangan'], ['label' => 'Galeri Komponen']]">
        <x-slot:aksi>
            <button type="button" @click="$dispatch('toast', { pesan: 'Contoh pemberitahuan berhasil.', ragam: 'sukses' })"
                class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">
                Coba Toast
            </button>
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="space-y-8">
        {{-- Kartu statistik --}}
        <section>
            <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Kartu Statistik</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @php $ringkasan = \App\Support\DummyData::ringkasanDashboard(); @endphp
                <x-sim.stat-card label="Jumlah Kepala Keluarga"
                    :nilai="number_format($ringkasan['jumlah_kk'], 0, ',', '.')" satuan="KK"
                    keterangan="Naik 12 KK dari tahun lalu" tren="naik" url="/kependudukan/rekap" />
                <x-sim.stat-card label="Rumah Terhuni"
                    :nilai="number_format($ringkasan['rumah_terhuni'], 0, ',', '.')"
                    :keterangan="'dari ' . number_format($ringkasan['rumah_total'], 0, ',', '.') . ' rumah'" />
                <x-sim.stat-card label="Luas Lahan"
                    :nilai="number_format($ringkasan['luas_lahan_total'], 2, ',', '.')" satuan="ha" />
                <x-sim.stat-card label="Mutu Data"
                    :nilai="round($ringkasan['data_terverifikasi'] / $ringkasan['data_total'] * 100) . '%'"
                    :keterangan="number_format($ringkasan['data_terverifikasi'], 0, ',', '.') . ' data terverifikasi'" />
            </div>
        </section>

        {{-- Badge status --}}
        <section>
            <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Badge Status</h2>
            <div class="flex flex-wrap gap-2 rounded-2xl border border-gray-200 p-5 dark:border-gray-800">
                @foreach (\App\Enums\StatusVerifikasi::cases() as $status)
                    <x-sim.status-badge :status="$status" />
                @endforeach
                @foreach (\App\Enums\StatusPengaduan::cases() as $status)
                    <x-sim.status-badge :status="$status" />
                @endforeach
                @foreach (\App\Enums\PrioritasPengaduan::cases() as $status)
                    <x-sim.status-badge :status="$status" />
                @endforeach
                @foreach (\App\Enums\KondisiRumah::cases() as $status)
                    <x-sim.status-badge :status="$status" />
                @endforeach
            </div>
        </section>

        {{-- Tabel data --}}
        <section>
            <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Tabel Data</h2>
            @php $transmigran = \App\Support\DummyData::transmigran(); @endphp
            <x-sim.data-table :jumlah="count($transmigran)" placeholder-cari="Cari nama atau NIK"
                judul-kosong="Belum ada data transmigran">
                <x-slot:filter>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                                Satuan Permukiman
                            </label>
                            <select class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm dark:border-gray-700 dark:text-white/90">
                                <option>Semua SP</option>
                                @foreach (\App\Support\DummyData::satuanPermukiman() as $sp)
                                    <option>{{ $sp['nama'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                                Status Verifikasi
                            </label>
                            <select class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm dark:border-gray-700 dark:text-white/90">
                                <option>Semua status</option>
                                @foreach (\App\Enums\StatusVerifikasi::cases() as $s)
                                    <option>{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-slot:filter>

                <x-slot:kepala>
                    <th scope="col" class="px-4 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Nama</th>
                    <th scope="col" class="px-4 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">NIK</th>
                    <th scope="col" class="px-4 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">SP</th>
                    <th scope="col" class="px-4 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Verifikasi</th>
                </x-slot:kepala>

                @foreach ($transmigran as $baris)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-4 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                            {{ $baris['nama_kepala_keluarga'] }}
                        </td>
                        <td class="px-4 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                            {{ $baris['nik'] }}
                        </td>
                        <td class="px-4 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                            {{ $baris['satuan_permukiman'] }}
                        </td>
                        <td class="px-4 py-3">
                            <x-sim.status-badge :status="\App\Enums\StatusVerifikasi::from($baris['status_verifikasi'])"
                                :catatan="$baris['catatan_verifikasi'] ?? null" />
                        </td>
                    </tr>
                @endforeach

                <x-slot:kartu>
                    @foreach ($transmigran as $baris)
                        <div class="p-4">
                            <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                {{ $baris['nama_kepala_keluarga'] }}
                            </p>
                            <p class="mt-0.5 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                                {{ $baris['nik'] }} &middot; {{ $baris['satuan_permukiman'] }}
                            </p>
                            <div class="mt-2">
                                <x-sim.status-badge
                                    :status="\App\Enums\StatusVerifikasi::from($baris['status_verifikasi'])" ukuran="sm" />
                            </div>
                        </div>
                    @endforeach
                </x-slot:kartu>
            </x-sim.data-table>
        </section>

        {{--
            Lima keadaan wajib menurut agents/ui-spec.md bagian 7.
            Ditampilkan berdampingan agar dapat ditinjau sekaligus saat validasi.
        --}}
        <section>
            <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Lima Keadaan Halaman</h2>
            <p class="mb-4 text-theme-sm text-gray-500 dark:text-gray-400">
                Setiap halaman daftar dan rincian wajib menangani kelimanya.
                Keadaan tanpa izin memakai halaman 403 tersendiri.
            </p>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 dark:border-gray-800">
                    <p class="border-b border-gray-200 px-4 py-2 text-theme-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        1. Kosong
                    </p>
                    <x-sim.empty-state judul="Belum ada data lahan"
                        pesan="Data lahan akan tampil di sini setelah ditambahkan." />
                </div>

                <div class="rounded-2xl border border-gray-200 dark:border-gray-800">
                    <p class="border-b border-gray-200 px-4 py-2 text-theme-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        2. Pencarian nihil
                    </p>
                    <x-sim.empty-state ragam="pencarian" kata-kunci="yohanes bere"
                        pesan="Coba kata kunci lain, atau bersihkan filter yang aktif." />
                </div>

                <div class="rounded-2xl border border-gray-200 dark:border-gray-800">
                    <p class="border-b border-gray-200 px-4 py-2 text-theme-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        3. Memuat
                    </p>
                    <x-sim.skeleton ragam="tabel" :baris="4" />
                </div>

                <div class="rounded-2xl border border-gray-200 dark:border-gray-800">
                    <p class="border-b border-gray-200 px-4 py-2 text-theme-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        4. Galat
                    </p>
                    <x-sim.error-state />
                </div>

                <div class="rounded-2xl border border-gray-200 p-4 lg:col-span-2 dark:border-gray-800">
                    <p class="mb-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                        5. Tanpa izin, memakai halaman 403 tersendiri
                    </p>
                    <a href="/uji-403"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Buka Halaman 403
                    </a>
                </div>
            </div>
        </section>

        {{-- Ragam skeleton lain --}}
        <section>
            <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Ragam Keadaan Memuat</h2>
            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <p class="mb-2 text-theme-xs text-gray-500 dark:text-gray-400">Kartu statistik</p>
                    <x-sim.skeleton ragam="kartu" :baris="2" />
                </div>
                <div>
                    <p class="mb-2 text-theme-xs text-gray-500 dark:text-gray-400">Grafik</p>
                    <x-sim.skeleton ragam="grafik" />
                </div>
            </div>
        </section>

        {{-- Isian --}}
        <section>
            <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Isian Khusus</h2>
            <div class="grid gap-6 rounded-2xl border border-gray-200 p-5 lg:grid-cols-2 dark:border-gray-800">
                <div>
                    <h3 class="mb-3 text-theme-sm font-medium text-gray-700 dark:text-gray-300">Pemilih Wilayah</h3>
                    <x-sim.wilayah-picker :daftar-kawasan="[['id' => 1, 'nama' => 'Kobalima Timur']]"
                        :daftar-sp="collect(\App\Support\DummyData::satuanPermukiman())
                            ->map(fn($s) => ['id' => $s['id_satuan_permukiman'], 'nama' => $s['nama'], 'kawasan_id' => 1])
                            ->all()" />
                </div>

                <div>
                    <h3 class="mb-3 text-theme-sm font-medium text-gray-700 dark:text-gray-300">Titik Koordinat</h3>
                    <x-sim.koordinat-input :lintang="-9.512345" :bujur="124.912345" />
                </div>

                <div class="lg:col-span-2">
                    <h3 class="mb-3 text-theme-sm font-medium text-gray-700 dark:text-gray-300">Unggah Dokumen</h3>
                    <x-sim.file-upload nama="dokumen_pendukung" label="Kartu Keluarga"
                        nama-dokumen="Kartu Keluarga" nama-pemilik="Yohanes Bere"
                        keterangan="Unggah hasil pindaian atau foto kartu keluarga yang terbaca jelas." />
                </div>
            </div>
        </section>

        {{-- Modal dan dialog --}}
        <section>
            <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Modal dan Dialog</h2>
            <div class="flex flex-wrap gap-3 rounded-2xl border border-gray-200 p-5 dark:border-gray-800">
                <button type="button" @click="$dispatch('buka-modal', 'contohForm')"
                    class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">
                    Buka Modal Form
                </button>
                <button type="button"
                    @click="$dispatch('buka-konfirmasi', { nama: 'contohHapus', aksi: '/contoh' })"
                    class="rounded-lg border border-red-300 px-4 py-2.5 text-theme-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/40 dark:hover:bg-red-500/10">
                    Buka Konfirmasi Hapus
                </button>
                <button type="button"
                    @click="$dispatch('buka-konfirmasi', { nama: 'contohTolak', aksi: '/contoh' })"
                    class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    Tolak Verifikasi
                </button>
            </div>
        </section>
    </div>

    {{-- Modal contoh dengan tombol Simpan dan Verifikasi --}}
    <x-sim.modal-form nama="contohForm" judul="Tambah Data Transmigran"
        keterangan="Isian bertanda bintang wajib diisi." aksi="#" :boleh-verifikasi="true">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="contoh_nama" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                    Nama Kepala Keluarga<span class="text-error-500">*</span>
                </label>
                <input type="text" id="contoh_nama" name="nama_kepala_keluarga"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm dark:border-gray-700 dark:text-white/90" />
            </div>
            <div>
                <label for="contoh_nik" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                    NIK<span class="text-error-500">*</span>
                </label>
                <input type="text" id="contoh_nik" name="nik" inputmode="numeric" maxlength="16"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm tabular-nums dark:border-gray-700 dark:text-white/90" />
            </div>
        </div>
    </x-sim.modal-form>

    <x-sim.confirm-dialog nama="contohHapus" judul="Hapus data transmigran?"
        pesan="Data yang dihapus masih dapat dipulihkan oleh admin melalui audit log."
        label-setuju="Hapus Data" />

    <x-sim.confirm-dialog nama="contohTolak" judul="Tolak verifikasi data ini?"
        pesan="Operator akan melihat alasan penolakan agar dapat memperbaiki datanya."
        label-setuju="Tolak Verifikasi" ragam="peringatan" metode="POST" :perlu-alasan="true"
        label-alasan="Alasan penolakan" />
@endsection
