{{--
    Modal pemutakhiran status penanganan pengaduan, dipakai dari halaman daftar.

    Dibuat terpisah dari modal ubah data, bukan digabung, karena dua tindakan
    ini berbeda sifat dan tercatat berbeda pada audit log: mengubah data
    memperbaiki isi laporan, sedangkan menangani laporan memajukan prosesnya.
    Menggabungkannya juga berisiko status ikut berubah tanpa disengaja ketika
    petugas sebenarnya hanya membetulkan salah ketik.

    Alur status WAJIB maju satu langkah dan tidak boleh melompat
    (agents/rules.md bagian 10b). Karena itu hanya satu status tujuan yang
    ditawarkan, dihitung Alpine dari status baris yang sedang dibuka. Status
    Selesai tidak memiliki lanjutan, sehingga modalnya menampilkan keterangan
    dan tombol simpannya tidak dirender.
--}}
@props(['nama' => 'tanganiPengaduanBaris'])

@php
    use App\Enums\StatusPengaduan;

    // Peta status berikutnya dibaca Alpine agar tidak perlu memanggil server
    // hanya untuk mengetahui satu langkah lanjutan.
    $petaLanjut = collect(StatusPengaduan::cases())
        ->mapWithKeys(fn ($s) => [$s->value => $s->berikutnya()?->value])
        ->all();
@endphp

<div x-data="{
        terbuka: false,
        mengirim: false,
        baris: null,
        peta: @js($petaLanjut),

        buka(detail) {
            this.baris = detail.data ?? null;
            this.terbuka = true;
            document.body.classList.add('overflow-hidden');
            this.$nextTick(() => this.$refs.panel?.querySelector('textarea')?.focus());
        },

        tutup() {
            this.terbuka = false;
            this.mengirim = false;
            this.baris = null;
            document.body.classList.remove('overflow-hidden');
        },

        get statusSekarang() {
            return this.baris ? this.baris.status : '';
        },

        get statusLanjut() {
            return this.baris ? (this.peta[this.baris.status] ?? null) : null;
        },

        get aksi() {
            return this.baris ? '/pengaduan/' + this.baris.id + '/tangani' : '#';
        },
    }"
    x-on:buka-tangani-pengaduan.window="if ($event.detail.nama === '{{ $nama }}') buka($event.detail)"
    x-on:keydown.escape.window="terbuka && tutup()">

    <div x-show="terbuka" x-cloak class="fixed inset-0 z-99999 overflow-y-auto" role="dialog" aria-modal="true"
        aria-labelledby="judul-{{ $nama }}">

        <div x-show="terbuka" x-transition.opacity @click="tutup()" class="fixed inset-0 bg-gray-900/50"
            aria-hidden="true"></div>

        <div class="flex min-h-full items-end justify-center sm:items-center sm:p-4">
            <div x-ref="panel" x-show="terbuka" x-transition
                class="relative w-full sm:max-w-lg bg-white shadow-xl sm:rounded-2xl dark:bg-gray-900">

                {{--
                    enctype WAJIB, sebab modal ini kini memuat unggahan dokumen
                    tindak lanjut. Tanpa itu berkasnya tidak pernah ikut terkirim
                    dan kegagalannya berlangsung diam-diam.
                --}}
                <form :action="aksi" method="POST" enctype="multipart/form-data"
                    @submit="mengirim = true">
                    @csrf

                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <h2 id="judul-{{ $nama }}" class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                Perbarui Status Penanganan
                            </h2>
                            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400"
                                x-text="baris ? baris.nomor_pengaduan : ''"></p>
                        </div>
                        <button type="button" @click="tutup()" aria-label="Tutup"
                            class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4 px-5 py-4">
                        {{-- Perpindahan status, ditampilkan sebagai satu langkah --}}
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/[0.03]">
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">Status saat ini</p>
                            <p class="mt-0.5 text-theme-sm font-medium text-gray-800 dark:text-white/90"
                                x-text="statusSekarang"></p>

                            <template x-if="statusLanjut">
                                <div class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-800">
                                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">Akan diubah menjadi</p>
                                    <p class="mt-0.5 text-theme-sm font-semibold text-brand-600 dark:text-brand-400"
                                        x-text="statusLanjut"></p>
                                    <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                                        Status hanya dapat maju satu langkah, agar riwayat penanganan mencerminkan
                                        proses yang benar-benar terjadi.
                                    </p>
                                </div>
                            </template>

                            <template x-if="! statusLanjut">
                                <p class="mt-3 border-t border-gray-200 pt-3 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                                    Pengaduan ini sudah selesai ditangani, sehingga tidak ada status lanjutan.
                                </p>
                            </template>
                        </div>

                        {{--
                            Nama isian disamakan dengan modal pada halaman
                            rincian (status_sesudah, bukan status_tujuan), agar
                            satu penangan di sisi server melayani keduanya.
                        --}}
                        <input type="hidden" name="status_sesudah" :value="statusLanjut ?? ''" />

                        <div x-show="statusLanjut" class="space-y-4">
                            <div>
                                <label for="{{ $nama }}_tanggal"
                                    class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                    Tanggal Penanganan<span class="text-error-500">*</span>
                                </label>
                                {{--
                                    Tanggal penanganan tidak selalu sama dengan
                                    tanggal pencatatan: petugas kerap meninjau
                                    lapangan lebih dulu, baru mencatatnya setelah
                                    kembali ke tempat yang berjaringan.
                                --}}
                                <input type="date" id="{{ $nama }}_tanggal" name="tanggal_penanganan" required
                                    value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}"
                                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90" />
                            </div>

                            <div>
                                <label for="{{ $nama }}_catatan"
                                    class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                    Catatan Penanganan<span class="text-error-500">*</span>
                                </label>
                                <textarea id="{{ $nama }}_catatan" name="catatan" rows="3" required maxlength="500"
                                    placeholder="Jelaskan tindakan yang sudah dilakukan atau alasan perubahan status."
                                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30"></textarea>
                                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                    Catatan tampil pada halaman lacak yang dibuka warga, sehingga tulis dengan bahasa
                                    yang dapat dipahami pelapor.
                                </p>
                            </div>

                            <x-sim.file-upload nama="dokumen_tindak_lanjut" label="Dokumen Tindak Lanjut"
                                nama-dokumen="Tindak Lanjut"
                                keterangan="Foto perbaikan, berita acara, atau surat tindak lanjut bila ada." />
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 sm:flex-row sm:justify-end dark:border-gray-800">
                        <button type="button" @click="tutup()"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Tutup
                        </button>

                        {{-- Tombol simpan tidak dirender bila tidak ada status lanjutan (R-26) --}}
                        <button type="submit" x-show="statusLanjut" :disabled="mengirim"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-60 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                            <span x-show="mengirim" x-cloak
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                aria-hidden="true"></span>
                            Simpan Penanganan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>