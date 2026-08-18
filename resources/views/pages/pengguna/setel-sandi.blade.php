{{--
    Modal setel ulang kata sandi oleh Admin.

    Jalur ini adalah pemulihan utama yang selalu tersedia, termasuk ketika
    petugas berada di lokus bersinyal lemah sehingga tidak dapat menerima
    kode verifikasi lewat surel (agents/rules.md bagian 14b poin 12).

    Empat ketentuan yang wajib terlihat di sini:

    1. Admin menimpa kata sandi, tidak pernah membacanya. Sistem hanya
       menyimpan sidik, sehingga tidak ada nilai lama yang dapat ditampilkan
       (poin 10). Karena itu tidak ada kolom "kata sandi lama".
    2. Setelah disetel ulang, akun ditandai wajib ganti kata sandi (poin 9).
       Ditegaskan lewat teks, bukan lewat kotak centang, sebab ini bukan
       pilihan yang boleh dimatikan Admin.
    3. Sejak 2026-08-14 kata sandi sementara **dikirim juga lewat surel**,
       tetapi penyerahan langsung tetap dianjurkan. Keduanya diperlukan sebab
       jalur ini justru dibuat untuk petugas di lokus bersinyal lemah, yang
       belum tentu dapat membuka surelnya saat itu juga.
    4. Tindakan ini tercatat pada audit log beserta pelakunya (poin 11).
--}}
@props(['nama' => 'setelSandi'])

<div x-data="{
        terbuka: false,
        mengirim: false,
        akun: null,
        buka(detail) {
            this.akun = detail.akun ?? null;
            this.terbuka = true;
            window.kunciGulir?.kunci();
            this.$nextTick(() => this.$refs.panel?.querySelector('input')?.focus());
        },
        tutup() {
            if (! this.terbuka) {
                return;
            }

            this.terbuka = false;
            this.mengirim = false;
            window.kunciGulir?.lepas();
        },
        get aksi() {
            // Alamat dirakit dari pola milik Laravel, bukan ditulis mentah,
            // agar tetap benar ketika sistem disajikan pada sub-path.
            return this.akun
                ? @js(route('pengguna.setel-sandi', ['id' => '__ID__'])).replace('__ID__', this.akun.id_user)
                : '#';
        },
    }"
    x-on:buka-setel-sandi.window="buka($event.detail)"
    x-on:keydown.escape.window="terbuka && tutup()">

    <div x-show="terbuka" x-cloak class="fixed inset-0 z-99999 overflow-y-auto" role="dialog" aria-modal="true"
        aria-labelledby="judul-{{ $nama }}">

        <div x-show="terbuka" x-transition.opacity @click="tutup()"
            class="fixed inset-0 bg-gray-900/50" aria-hidden="true"></div>

        <div class="flex min-h-full items-end justify-center sm:items-center sm:p-4">
            <div x-ref="panel" x-show="terbuka" x-transition
                class="relative w-full sm:max-w-lg bg-white shadow-xl sm:rounded-2xl dark:bg-gray-900">

                <form :action="aksi" method="POST" @submit="mengirim = true">
                    @csrf

                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <h2 id="judul-{{ $nama }}" class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                Setel Ulang Kata Sandi
                            </h2>
                            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                Akun <span class="font-medium" x-text="akun ? akun.nama : ''"></span>
                            </p>
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
                        {{--
                            Keterangan cara penyerahan. Diletakkan sebelum isian
                            agar terbaca lebih dulu, bukan setelah Admin telanjur
                            mengetik.

                            Surel adalah SALINAN, bukan pengganti penyerahan
                            langsung. Petugas di lokus bersinyal lemah belum
                            tentu dapat membukanya saat itu juga, padahal jalur
                            inilah yang justru dibuat untuk keadaan tersebut.
                        --}}
                        <div class="rounded-lg border border-yellow-300 bg-yellow-50 p-4 dark:border-yellow-500/30 dark:bg-yellow-500/10">
                            <p class="text-theme-sm text-yellow-800 dark:text-yellow-200">
                                <span class="font-semibold">Sampaikan kepada pengguna terkait.</span>
                                Kata sandi baru akan dikirimkan ke email. Pastikan kata sandi tersebut juga disampaikan 
                                secara langsung kepada pengguna dan tidak melalui pihak lain.
                            </p>
                            <p class="mt-2 text-theme-xs text-yellow-800 dark:text-yellow-200">
                                <span class="font-medium">Pengiriman email belum aktif.</span>
                                Sampai backend selesai, penyerahan langsung adalah satu-satunya cara yang bekerja.
                            </p>
                        </div>

                        <x-sim.input-kata-sandi nama="password_baru" label="Kata Sandi Sementara"
                            autocomplete="new-password" :wajib="true"
                            keterangan="Minimal 8 karakter. Petugas wajib menggantinya saat masuk berikutnya." />

                        <x-sim.input-kata-sandi nama="password_baru_konfirmasi" label="Ulangi Kata Sandi Sementara"
                            autocomplete="new-password" :wajib="true" />

                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/[0.03]">
                            <p class="text-theme-xs text-gray-600 dark:text-gray-400">
                                Setelah disimpan, akun ditandai wajib mengganti kata sandi. Petugas akan diarahkan
                                ke halaman penggantian saat masuk dan belum dapat membuka halaman lain sebelum
                                menyelesaikannya. Tindakan ini tercatat pada audit log beserta nama Anda dan
                                waktu kejadian.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 sm:flex-row sm:justify-end dark:border-gray-800">
                        <button type="button" @click="tutup()"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Batal
                        </button>
                        <button type="submit" :disabled="mengirim"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-60 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                            <span x-show="mengirim" x-cloak
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                aria-hidden="true"></span>
                            Setel Ulang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
