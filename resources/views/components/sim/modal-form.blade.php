{{--
    Modal floating untuk formulir isian panjang.

    Ketentuan pada agents/ui-spec.md bagian 6.2:
    - ukuran sm, md, lg, xl,
    - tutup dengan Esc dan klik latar,
    - fokus terkunci di dalam modal,
    - tombol simpan nonaktif beserta pemintal selama proses kirim,
    - layar penuh pada perangkat mobile.

    Pemakaian:
        <x-sim.modal-form nama="formTransmigran" judul="Tambah Transmigran"
            aksi="/transmigran">
            ... isian ...
        </x-sim.modal-form>

    Membuka dari luar:
        <button @click="$dispatch('buka-modal', 'formTransmigran')">Tambah</button>
--}}
@props([
    'nama',
    'judul',
    'keterangan' => null,
    'aksi' => '#',
    'metode' => 'POST',
    'ukuran' => 'lg',
    'labelSimpan' => 'Simpan',

    /*
        Pola aksi untuk modal yang dipakai bergantian oleh banyak baris tabel,
        contoh `/transmigran/:id`. Penanda :id diganti nilai sebenarnya saat
        modal dibuka, sehingga satu modal cukup melayani seluruh baris.

        Tanpa ini, tombol Ubah di setiap baris memerlukan modalnya sendiri,
        dan halaman berisi dua puluh baris akan memuat dua puluh salinan form
        yang sama.

        Dibiarkan null secara bawaan agar dua puluh satu pemakaian yang sudah
        ada tetap memakai `aksi` statis tanpa perubahan.
    */
    'polaAksi' => null,
])

@php
    /*
        Pola aksi dilewatkan `url()` agar tetap benar pada sub-path, mengikuti
        pola `stat-card`.

        Penanda `:id` DIBIARKAN UTUH, sebab yang menggantinya Alpine di sisi
        klien sesudah alamat ini terbentuk. `url()` hanya menambahkan akar di
        depannya dan tidak menyentuh ruas belakang.

        Dua puluh dua pemanggil mengoper pola mentah semacam '/alsintan/:id';
        tanpa pembungkus ini seluruhnya mengirim ke akar domain.
    */
    $polaAksiPenuh = $polaAksi === null || str_contains($polaAksi, '://')
        ? $polaAksi
        : url($polaAksi);

    $lebar = [
        'sm' => 'sm:max-w-md',
        'md' => 'sm:max-w-lg',
        'lg' => 'sm:max-w-2xl',
        'xl' => 'sm:max-w-4xl',
    ][$ukuran] ?? 'sm:max-w-2xl';
@endphp

<div x-data="{
        terbuka: false,
        mengirim: false,
        polaAksi: @js($polaAksiPenuh),
        aksiStatis: @js($aksi),
        baris: null,

        buka(detail) {
            // Modal berbaris menerima data baris yang diklik, lalu mengisi
            // sendiri isian di dalamnya. Modal biasa memanggil tanpa argumen.
            this.baris = (detail && typeof detail === 'object') ? (detail.data ?? null) : null;

            this.terbuka = true;
            window.kunciGulir?.kunci();

            this.$nextTick(() => {
                if (this.baris) {
                    this.isiFormulir();
                }

                this.$refs.panel?.querySelector('input, select, textarea')?.focus();
            });
        },

        /*
            Mengisi setiap isian yang namanya cocok dengan kunci data baris.
            Dicocokkan lewat atribut name, bukan id, sebab id diberi awalan
            berbeda untuk membedakan modal tambah dan modal ubah.
        */
        isiFormulir() {
            const panel = this.$refs.panel;

            if (! panel) {
                return;
            }

            Object.entries(this.baris).forEach(([kunci, nilai]) => {
                const isian = panel.querySelector('[name=&quot;' + kunci + '&quot;]');

                if (! isian || nilai === null) {
                    return;
                }

                if (isian.type === 'checkbox') {
                    isian.checked = Boolean(nilai);
                } else {
                    isian.value = nilai;
                }

                // Memberi tahu Alpine pada isian yang punya pengendali sendiri,
                // misalnya pilihan yang menampilkan bagian lain saat berubah.
                isian.dispatchEvent(new Event('input', { bubbles: true }));
                isian.dispatchEvent(new Event('change', { bubbles: true }));
            });
        },

        get aksi() {
            if (! this.polaAksi) {
                return this.aksiStatis;
            }

            const id = this.baris ? (this.baris.id ?? '') : '';

            return this.polaAksi.replace(':id', id);
        },

        tutup() {
            if (! this.terbuka) {
                return;
            }

            this.terbuka = false;
            this.mengirim = false;
            this.baris = null;
            window.kunciGulir?.lepas();
        },
    }"
    x-on:buka-modal.window="if ($event.detail === '{{ $nama }}') buka()"
    x-on:buka-modal-baris.window="if ($event.detail.nama === '{{ $nama }}') buka($event.detail)"
    x-on:tutup-modal.window="tutup()"
    x-on:keydown.escape.window="terbuka && tutup()">

    {{--
        Wadah TANPA `overflow-y-auto`.

        Sebelumnya wadah ini dan badan formulir sama-sama bergulir, sehingga
        ada dua wilayah gulir bertumpuk dan yang bergerak bergantung posisi
        kursor. Kini hanya badan formulir yang menggulir.
    --}}
    <div x-show="terbuka" x-cloak class="fixed inset-0 z-99999" role="dialog" aria-modal="true"
        aria-labelledby="judul-{{ $nama }}">

        {{-- Latar gelap, klik untuk menutup --}}
        <div x-show="terbuka" x-transition.opacity @click="tutup()"
            class="fixed inset-0 bg-gray-900/50" aria-hidden="true"></div>

        {{--
            `sm:items-start` DENGAN `my-auto` pada panelnya, bukan
            `sm:items-center`.

            Keduanya tampak sama selama modal lebih pendek daripada layar:
            `my-auto` memusatkan panel persis di tengah. Bedanya baru terasa
            ketika modal lebih tinggi daripada layar. Dengan `items-center`,
            panel meluber ke ATAS dan ke bawah sekaligus, dan luberan atasnya
            TIDAK PERNAH dapat dijangkau sebab `scrollTop` tidak bisa bernilai
            negatif. Itulah sebabnya modal terasa tenggelam dan hanya dapat
            dipulihkan dengan memuat ulang halaman.

            Dengan `items-start`, `my-auto` berhenti memusatkan begitu ruangnya
            habis, sehingga panel menempel di atas dan seluruh isinya tetap
            terjangkau.
        --}}
        <div class="flex h-full items-end justify-center overflow-hidden sm:items-start sm:p-4">
            {{-- Panel modal. Layar penuh pada mobile, melayang pada layar lebar. --}}
            <div x-ref="panel" x-show="terbuka" x-transition
                @keydown.tab="
                    const fokusable = $refs.panel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])');
                    if (fokusable.length === 0) return;
                    const pertama = fokusable[0];
                    const terakhir = fokusable[fokusable.length - 1];
                    if ($event.shiftKey && document.activeElement === pertama) { $event.preventDefault(); terakhir.focus(); }
                    else if (!$event.shiftKey && document.activeElement === terakhir) { $event.preventDefault(); pertama.focus(); }
                "
                class="relative flex max-h-full w-full {{ $lebar }} flex-col bg-white shadow-xl sm:my-auto sm:max-h-[calc(100vh-2rem)] sm:rounded-2xl dark:bg-gray-900">

                {{--
                    `flex flex-col` beserta `max-h-full` membuat kepala dan kaki
                    tetap menempel, sedangkan badan formulir yang menyusut dan
                    menggulir. Tanpa ini tinggi badan harus ditebak lewat
                    `calc()`, dan tebakan itu meleset begitu kepala atau kaki
                    lebih tinggi daripada perkiraan.

                    `min-h-0` di sini WAJIB, sedangkan pada badan di bawah tidak.
                    Form adalah item flex milik panel, dan item flex bernilai
                    bawaan `min-height: auto` sehingga menolak menyusut di bawah
                    tinggi isinya. Tanpa ini `max-h-full` pada panel tidak pernah
                    berlaku dan modal tumbuh melampaui layar.
                --}}
                <form :action="aksi" method="POST" enctype="multipart/form-data"
                    @submit="mengirim = true" class="flex min-h-0 flex-col">
                    @csrf
                    @if (! in_array($metode, ['GET', 'POST']))
                        @method($metode)
                    @endif

                    {{-- Kepala modal, tidak ikut menggulir --}}
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <h2 id="judul-{{ $nama }}"
                                class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                {{ $judul }}
                            </h2>
                            @if ($keterangan)
                                <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                    {{ $keterangan }}
                                </p>
                            @endif
                        </div>
                        <button type="button" @click="tutup()" aria-label="Tutup"
                            class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{--
                        Isi formulir, satu-satunya wilayah yang menggulir.

                        `min-h-0` wajib ada: item flex bernilai bawaan
                        `min-height: auto`, sehingga ia menolak menyusut di
                        bawah tinggi isinya dan `overflow-y-auto` tidak pernah
                        aktif. Tanpa itu panel tumbuh melampaui layar dan
                        gejalanya kembali seperti semula.
                    --}}
                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        {{ $slot }}
                    </div>

                    {{-- Kaki modal, tombol rata kanan, tidak ikut menggulir --}}
                    <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 sm:flex-row sm:justify-end dark:border-gray-800">
                        <button type="button" @click="tutup()"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Batal
                        </button>

                        <button type="submit" :disabled="mengirim"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-60 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                            <span x-show="mengirim" x-cloak
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                aria-hidden="true"></span>
                            {{ $labelSimpan }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
