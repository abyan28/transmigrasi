{{--
    Modal rincian satu akun petugas.

    Hanya menampilkan, tanpa isian, sehingga tidak memakai x-sim.modal-form
    yang selalu membawa tombol Simpan. Tombol yang muncul di sini adalah
    tindakan lanjutan, bukan penyimpanan.

    Riwayat tindakan diambil dari audit log yang menyasar akun ini, sesuai
    agents/rules.md bagian 14b poin 15: setiap penyetelan ulang kata sandi
    wajib tercatat beserta petugas pelakunya.

    Struktur data mengikuti agents/data-dictionary.md bagian 2.1.
--}}
@props(['nama' => 'detailPengguna'])

@php
    use App\Support\DummyData;

    $daftarPengguna = DummyData::pengguna();

    /*
        Riwayat tindakan pada akun.

        Modal ini melayani seluruh baris secara bergantian, sehingga akun yang
        sedang dibuka baru diketahui Alpine saat modal dipanggil. Penyaringan
        per akun karena itu dilakukan di sisi klien memakai `record_id`, bukan
        di sini.

        Sebelumnya penyaringan hanya memakai `nama_tabel`, sehingga setiap akun
        menampilkan riwayat akun orang lain. Komentar lamanya bahkan mengaku
        mencocokkan nomor baris, padahal kodenya tidak melakukannya.
    */
    $riwayatAkun = array_values(array_filter(
        DummyData::auditLog(),
        fn ($baris) => $baris['nama_tabel'] === 'user',
    ));

    $kelasLabel = 'text-theme-xs text-gray-500 dark:text-gray-400';
    $kelasNilai = 'mt-0.5 text-theme-sm text-gray-800 dark:text-white/90';
@endphp

<div x-data="{
        terbuka: false,
        akun: null,
        semuaRiwayat: @js($riwayatAkun),

        /*
            Riwayat milik akun yang sedang dibuka saja. Dicocokkan lewat
            record_id, sebab satu modal melayani seluruh baris secara
            bergantian sehingga akunnya baru diketahui saat modal dipanggil.
        */
        get riwayat() {
            if (! this.akun) {
                return [];
            }

            return this.semuaRiwayat.filter(
                (baris) => Number(baris.record_id) === Number(this.akun.id_user),
            );
        },

        buka(detail) {
            this.akun = detail.akun ?? null;
            this.terbuka = true;
            window.kunciGulir?.kunci();
            this.$nextTick(() => this.$refs.tombolTutup?.focus());
        },
        tutup() {
            if (! this.terbuka) {
                return;
            }

            this.terbuka = false;
            window.kunciGulir?.lepas();
        },
    }"
    x-on:buka-detail-pengguna.window="if ($event.detail.nama === '{{ $nama }}') buka($event.detail)"
    x-on:keydown.escape.window="terbuka && tutup()">

    <div x-show="terbuka" x-cloak class="fixed inset-0 z-99999 overflow-y-auto" role="dialog" aria-modal="true"
        aria-labelledby="judul-{{ $nama }}">

        <div x-show="terbuka" x-transition.opacity @click="tutup()"
            class="fixed inset-0 bg-gray-900/50" aria-hidden="true"></div>

        <div class="flex min-h-full items-end justify-center sm:items-center sm:p-4">
            <div x-ref="panel" x-show="terbuka" x-transition
                class="relative w-full sm:max-w-2xl bg-white shadow-xl sm:rounded-2xl dark:bg-gray-900">

                {{-- Kepala --}}
                <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h2 id="judul-{{ $nama }}" class="text-lg font-semibold text-gray-800 dark:text-white/90">
                            Rincian Akun
                        </h2>
                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400"
                            x-text="akun ? akun.nama : ''"></p>
                    </div>
                    <button type="button" x-ref="tombolTutup" @click="tutup()" aria-label="Tutup"
                        class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Isi --}}
                <div class="max-h-[calc(100vh-16rem)] space-y-5 overflow-y-auto px-5 py-4">

                    <section>
                        <h3 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Identitas
                        </h3>
                        <dl class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="{{ $kelasLabel }}">Nama Lengkap</dt>
                                <dd class="{{ $kelasNilai }}" x-text="akun ? akun.nama : '-'"></dd>
                            </div>
                            <div>
                                <dt class="{{ $kelasLabel }}">Jabatan</dt>
                                <dd class="{{ $kelasNilai }}" x-text="akun && akun.jabatan ? akun.jabatan : '-'"></dd>
                            </div>
                            <div>
                                <dt class="{{ $kelasLabel }}">Username</dt>
                                <dd class="{{ $kelasNilai }}" x-text="akun ? akun.username : '-'"></dd>
                            </div>
                            <div>
                                <dt class="{{ $kelasLabel }}">Email</dt>
                                <dd class="{{ $kelasNilai }} break-all" x-text="akun ? akun.email : '-'"></dd>
                            </div>
                            <div>
                                <dt class="{{ $kelasLabel }}">Telepon</dt>
                                <dd class="{{ $kelasNilai }} tabular-nums"
                                    x-text="akun && akun.telepon ? akun.telepon : '-'"></dd>
                            </div>
                            <div>
                                <dt class="{{ $kelasLabel }}">Masuk Terakhir</dt>
                                <dd class="{{ $kelasNilai }}"
                                    x-text="akun && akun.last_login_at ? akun.last_login_at : 'Belum pernah masuk'"></dd>
                            </div>
                        </dl>
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                        <h3 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Kewenangan
                        </h3>
                        <dl class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="{{ $kelasLabel }}">Role</dt>
                                <dd class="{{ $kelasNilai }}" x-text="akun ? akun.role : '-'"></dd>
                            </div>
                            <div>
                                <dt class="{{ $kelasLabel }}">Status Akun</dt>
                                <dd class="mt-0.5">
                                    <span x-show="akun && akun.is_aktif"
                                        class="inline-flex rounded-full bg-success-50 px-2.5 py-0.5 text-theme-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-400">
                                        Aktif
                                    </span>
                                    <span x-show="akun && ! akun.is_aktif" x-cloak
                                        class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-theme-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                        Nonaktif
                                    </span>
                                </dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="{{ $kelasLabel }}">Penugasan Satuan Permukiman</dt>
                                <dd class="{{ $kelasNilai }}">
                                    <template x-if="akun && akun.satuan_permukiman && akun.satuan_permukiman.length">
                                        <span x-text="akun.satuan_permukiman.join(', ')"></span>
                                    </template>
                                    <template x-if="akun && (! akun.satuan_permukiman || ! akun.satuan_permukiman.length)">
                                        <span class="text-gray-500 dark:text-gray-400">
                                            Tanpa batas SP, sebab rolenya bercakupan seluruh data.
                                        </span>
                                    </template>
                                </dd>
                            </div>
                        </dl>

                        {{--
                            Penanda kata sandi sementara. Muncul hanya bila akun
                            wajib mengganti sandi (rules.md 14b poin 13), sehingga
                            Admin tahu petugas belum menyelesaikan penggantian.
                        --}}
                        <div x-show="akun && akun.password_harus_diganti" x-cloak
                            class="mt-4 rounded-lg border border-yellow-300 bg-yellow-50 p-3 dark:border-yellow-500/30 dark:bg-yellow-500/10">
                            <p class="text-theme-xs text-yellow-800 dark:text-yellow-200">
                                <span class="font-semibold">Masih memakai kata sandi sementara.</span>
                                Petugas akan diminta menggantinya saat masuk berikutnya, dan belum dapat membuka
                                halaman lain sebelum penggantian selesai.
                            </p>
                        </div>
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                        <h3 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Riwayat Tindakan pada Akun
                        </h3>

                        <p x-show="riwayat.length === 0" class="mt-3 text-theme-sm text-gray-500 dark:text-gray-400">
                            Belum ada tindakan admin yang tercatat pada akun ini.
                        </p>

                        <ul x-show="riwayat.length > 0" x-cloak class="mt-3 space-y-3">
                            <template x-for="baris in riwayat" :key="baris.id_audit_log">
                                <li class="flex gap-3">
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-300 dark:bg-gray-600"
                                        aria-hidden="true"></span>
                                    <div>
                                        <p class="text-theme-sm text-gray-800 dark:text-white/90" x-text="baris.ringkasan"></p>
                                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                            <span x-text="baris.aksi"></span> oleh <span x-text="baris.pengguna"></span>
                                            &middot; <span x-text="baris.waktu"></span> WITA
                                        </p>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </section>
                </div>

                {{-- Kaki --}}
                <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 sm:flex-row sm:justify-end dark:border-gray-800">
                    <button type="button" @click="tutup()"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Tutup
                    </button>
                    <button type="button"
                        @click="const a = akun; tutup(); $dispatch('buka-setel-sandi', { akun: a });"
                        class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Setel Ulang Kata Sandi
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
