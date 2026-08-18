{{--
    Modal impor data massal, tiga langkah.

    Menjawab kebutuhan PRD 8.1: sinyal di lokus tidak selalu stabil, sehingga
    petugas perlu mengunduh template, mengisinya luring di lapangan, lalu
    mengunggahnya kembali saat sambungan tersedia. Tanpa jalur ini, pendataan
    ratusan kepala keluarga harus dilakukan satu per satu lewat form sambil
    daring, dan itu tidak mungkin dikerjakan di lapangan.

    Alurnya dipecah tiga langkah agar tiap tahap punya satu pekerjaan saja:

        1. Unduh template   - petugas tahu persis kolom apa yang harus diisi
        2. Unggah berkas    - diperiksa tipe dan ukurannya di sisi klien
        3. Pratinjau hasil  - berapa baris masuk, berapa gagal, apa sebabnya

    Langkah ketiga adalah yang terpenting dan justru paling sering diabaikan.
    Impor yang hanya berkata "gagal" memaksa petugas menebak-nebak barisnya,
    sedangkan berkas berisi ratusan baris tidak mungkin diperiksa manual.
    Karena itu kegagalan selalu disertai nomor baris dan alasannya.

    BELUM TERSAMBUNG BACKEND. Sesuai strategi Tahap 2, antarmukanya dibangun
    lebih dulu dengan data contoh. Spanduk peringatan di dalam modal WAJIB
    ada agar petugas tidak mengira datanya sudah benar-benar masuk.

    Pemakaian:
        <x-sim.modal-impor nama="imporTransmigran" judul="Impor Data Transmigran"
            entitas="transmigran"
            :kolom-wajib="['nik', 'nama_lengkap', 'no_kk', 'satuan_permukiman']" />
--}}
@props([
    'nama',
    'judul',
    'entitas',
    'keterangan' => null,
    'kolomWajib' => [],
])

<div x-data="{
        terbuka: false,
        langkah: 1,
        berkas: null,
        galat: '',
        memproses: false,
        maksByte: {{ 5 * 1024 * 1024 }},

        buka() {
            this.terbuka = true;
            this.langkah = 1;
            this.berkas = null;
            this.galat = '';
            window.kunciGulir?.kunci();

            this.$nextTick(() => {
                this.$refs.panel?.querySelector('a, button')?.focus();
            });
        },

        tutup() {
            if (! this.terbuka) {
                return;
            }

            this.terbuka = false;
            this.memproses = false;
            window.kunciGulir?.lepas();
        },

        pilih(peristiwa) {
            const f = peristiwa.target.files[0];
            this.galat = '';

            if (! f) {
                this.berkas = null;
                return;
            }

            // Diperiksa lebih dulu di sisi klien agar petugas berjaringan
            // lemah tidak menunggu unggahan yang sudah pasti ditolak server.
            if (f.size > this.maksByte) {
                this.galat = 'Ukuran berkas maksimal 5 MB. Berkas Anda ' + this.ukuran(f.size) + '.';
                peristiwa.target.value = '';
                this.berkas = null;
                return;
            }

            const namaKecil = f.name.toLowerCase();
            if (! namaKecil.endsWith('.xlsx') && ! namaKecil.endsWith('.xls') && ! namaKecil.endsWith('.csv')) {
                this.galat = 'Berkas harus berformat Excel (.xlsx atau .xls) atau CSV. Unduh templatenya lebih dulu bila belum punya.';
                peristiwa.target.value = '';
                this.berkas = null;
                return;
            }

            this.berkas = { nama: f.name, ukuran: this.ukuran(f.size) };
        },

        hapusBerkas() {
            this.berkas = null;
            this.galat = '';
            if (this.$refs.masukan) {
                this.$refs.masukan.value = '';
            }
        },

        proses() {
            if (! this.berkas) {
                this.galat = 'Pilih berkas lebih dulu.';
                return;
            }

            // Jeda singkat meniru unggahan sungguhan, sehingga tata letak
            // langkah ketiga sudah teruji dalam keadaan menunggu.
            this.memproses = true;
            setTimeout(() => {
                this.memproses = false;
                this.langkah = 3;
            }, 700);
        },

        ukuran(byte) {
            if (byte < 1024) return byte + ' B';
            if (byte < 1048576) return (byte / 1024).toFixed(1) + ' KB';
            return (byte / 1048576).toFixed(1) + ' MB';
        },
    }"
    x-on:buka-modal.window="if ($event.detail === '{{ $nama }}') buka()"
    x-on:keydown.escape.window="terbuka && tutup()">

    <div x-show="terbuka" x-cloak class="fixed inset-0 z-99999 overflow-y-auto" role="dialog" aria-modal="true"
        aria-labelledby="judul-{{ $nama }}">

        <div x-show="terbuka" x-transition.opacity @click="tutup()" class="fixed inset-0 bg-gray-900/50"
            aria-hidden="true"></div>

        <div class="flex min-h-full items-end justify-center sm:items-center sm:p-4">
            <div x-ref="panel" x-show="terbuka" x-transition
                @keydown.tab="
                    const fokusable = $refs.panel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])');
                    if (fokusable.length === 0) return;
                    const pertama = fokusable[0];
                    const terakhir = fokusable[fokusable.length - 1];
                    if ($event.shiftKey && document.activeElement === pertama) { $event.preventDefault(); terakhir.focus(); }
                    else if (!$event.shiftKey && document.activeElement === terakhir) { $event.preventDefault(); pertama.focus(); }
                "
                class="relative w-full sm:max-w-2xl bg-white shadow-xl sm:rounded-2xl dark:bg-gray-900">

                {{-- Kepala modal beserta penunjuk langkah --}}
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h2 id="judul-{{ $nama }}"
                                class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                {{ $judul }}
                            </h2>
                            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                {{ $keterangan ?? 'Unduh template, isi di lapangan, lalu unggah kembali saat ada sambungan.' }}
                            </p>
                        </div>
                        <button type="button" @click="tutup()" aria-label="Tutup"
                            class="shrink-0 rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{--
                        Penunjuk langkah. Memakai teks berangka, bukan sekadar
                        titik berwarna, agar tahapannya tetap terbaca pembaca
                        layar dan pengguna yang sulit membedakan warna.
                    --}}
                    <ol class="mt-4 flex items-center gap-2 text-theme-xs">
                        @foreach (['Unduh template', 'Unggah berkas', 'Hasil'] as $i => $namaLangkah)
                            <li class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full font-medium"
                                    :class="langkah >= {{ $i + 1 }}
                                        ? 'bg-brand-500 text-white'
                                        : 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400'">
                                    {{ $i + 1 }}
                                </span>
                                <span class="hidden sm:inline"
                                    :class="langkah >= {{ $i + 1 }}
                                        ? 'font-medium text-gray-800 dark:text-white/90'
                                        : 'text-gray-500 dark:text-gray-400'">
                                    {{ $namaLangkah }}
                                </span>
                                @if ($i < 2)
                                    <span class="mx-1 h-px w-4 bg-gray-300 dark:bg-gray-700" aria-hidden="true"></span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="max-h-[calc(100vh-18rem)] overflow-y-auto px-5 py-4">
                    {{--
                        Spanduk kejujuran. Tombol ini terlihat berfungsi penuh
                        padahal penyimpanannya belum ada, sehingga tanpa
                        peringatan petugas dapat mengira datanya sudah masuk.
                    --}}
                    <div class="mb-4 rounded-lg border border-yellow-300 bg-yellow-50 p-3.5 dark:border-yellow-500/30 dark:bg-yellow-500/10">
                        <p class="text-theme-xs text-yellow-800 dark:text-yellow-200">
                            <span class="font-medium">Fitur belum aktif.</span>
                            Tampilan impor sudah disiapkan, tetapi penyimpanan datanya menunggu
                            backend selesai. Berkas yang diunggah di sini belum tersimpan.
                        </p>
                    </div>

                    {{-- ---------------------------------------- Langkah 1 --}}
                    <div x-show="langkah === 1">
                        <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                            Unduh berkas template lebih dulu, lalu isi datanya memakai Excel.
                            Template dapat diisi tanpa sambungan internet, sehingga pendataan
                            tetap berjalan di lokasi yang sinyalnya lemah.
                        </p>

                        @if (! empty($kolomWajib))
                            <div class="mt-4 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-300">
                                    Kolom yang wajib diisi
                                </p>
                                <ul class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($kolomWajib as $kolom)
                                        <li class="rounded-md bg-gray-100 px-2 py-1 font-mono text-theme-xs text-gray-700 dark:bg-white/10 dark:text-gray-300">
                                            {{ $kolom }}
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
                                    Jangan mengubah nama maupun urutan kolom pada template, sebab
                                    pembacaannya bergantung pada judul kolom tersebut.
                                </p>
                            </div>
                        @endif

                        <a href="{{ route('template-impor', $entitas) }}"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Unduh Template Excel
                        </a>
                    </div>

                    {{-- ---------------------------------------- Langkah 2 --}}
                    <div x-show="langkah === 2" x-cloak>
                        <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                            Pilih berkas template yang sudah diisi. Data akan diperiksa lebih
                            dulu dan ditampilkan hasilnya sebelum benar-benar disimpan.
                        </p>

                        <label x-show="!berkas"
                            class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center transition hover:border-brand-400 hover:bg-gray-50 dark:border-gray-700 dark:hover:border-brand-600 dark:hover:bg-white/[0.02]">
                            <svg class="mb-2 h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                            <span class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                                Pilih berkas hasil isian
                            </span>
                            <span class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                Excel atau CSV, maksimal 5 MB
                            </span>
                            <input x-ref="masukan" type="file" accept=".xlsx,.xls,.csv" @change="pilih"
                                class="sr-only" />
                        </label>

                        <div x-show="berkas" x-cloak
                            class="mt-4 flex items-center gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-success-50 dark:bg-success-500/15">
                                <svg class="h-5 w-5 text-success-600 dark:text-success-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-theme-sm font-medium text-gray-800 dark:text-white/90"
                                    x-text="berkas?.nama"></p>
                                <p class="text-theme-xs text-gray-500 dark:text-gray-400" x-text="berkas?.ukuran"></p>
                            </div>
                            <button type="button" @click="hapusBerkas()" aria-label="Hapus berkas terpilih"
                                class="shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:hover:bg-white/5">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <p x-show="galat" x-cloak x-text="galat" class="mt-2 text-theme-xs text-error-500"></p>
                    </div>

                    {{-- ---------------------------------------- Langkah 3 --}}
                    <div x-show="langkah === 3" x-cloak>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                <p class="text-theme-xs text-gray-500 dark:text-gray-400">Baris siap disimpan</p>
                                <p class="mt-1 text-title-sm font-bold tabular-nums text-success-600 dark:text-success-400">
                                    18
                                </p>
                            </div>
                            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                <p class="text-theme-xs text-gray-500 dark:text-gray-400">Baris bermasalah</p>
                                <p class="mt-1 text-title-sm font-bold tabular-nums text-error-500">3</p>
                            </div>
                        </div>

                        {{--
                            Kegagalan selalu disertai nomor baris dan alasannya.
                            Berkas berisi ratusan baris tidak mungkin diperiksa
                            manual, sehingga pesan "impor gagal" tanpa rincian
                            memaksa petugas menebak dan biasanya berakhir dengan
                            mengulang seluruh pekerjaan.
                        --}}
                        <div class="mt-4">
                            <p class="mb-2 text-theme-xs font-medium text-gray-700 dark:text-gray-300">
                                Baris yang perlu diperbaiki
                            </p>
                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                                <table class="w-full text-left text-theme-xs">
                                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                                        <tr class="border-b border-gray-200 dark:border-gray-800">
                                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">
                                                Baris</th>
                                            <th scope="col" class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">
                                                Penyebab</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                        @foreach ([
                                            ['baris' => 4, 'sebab' => 'Kolom wajib masih kosong'],
                                            ['baris' => 9, 'sebab' => 'Data serupa sudah terdaftar sebelumnya'],
                                            ['baris' => 15, 'sebab' => 'Format tanggal tidak dikenali, gunakan format 31/12/2026'],
                                        ] as $galat)
                                            <tr>
                                                <td class="px-3 py-2 tabular-nums text-gray-800 dark:text-white/90">
                                                    {{ $galat['baris'] }}</td>
                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400">
                                                    {{ $galat['sebab'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
                                Baris bermasalah dilewati, sisanya tetap disimpan. Perbaiki baris
                                di atas pada berkas Anda, lalu unggah ulang berkas tersebut.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Kaki modal, tombolnya menyesuaikan langkah --}}
                <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 sm:flex-row sm:justify-end dark:border-gray-800">
                    <button type="button" @click="langkah === 1 ? tutup() : langkah--"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        <span x-text="langkah === 1 ? 'Batal' : 'Kembali'"></span>
                    </button>

                    <button x-show="langkah === 1" type="button" @click="langkah = 2"
                        class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Saya Sudah Punya Berkasnya
                    </button>

                    <button x-show="langkah === 2" x-cloak type="button" @click="proses()"
                        :disabled="!berkas || memproses"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-60 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        <span x-show="memproses" x-cloak
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                            aria-hidden="true"></span>
                        <span x-text="memproses ? 'Memeriksa berkas...' : 'Periksa Data'"></span>
                    </button>

                    <button x-show="langkah === 3" x-cloak type="button" @click="tutup()"
                        class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
