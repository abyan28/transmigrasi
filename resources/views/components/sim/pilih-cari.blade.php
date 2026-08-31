{{--
    Pilihan berdaftar panjang: satu tombol yang membuka panel berisi kotak
    pencarian beserta daftar opsinya.

    Dipakai pada isian yang sumbernya TABEL DATA OPERASIONAL: daftar
    transmigran, lahan, poktan, musim tanam, dan sejenisnya. Kriterianya satu
    pertanyaan: apakah daftarnya bertambah ketika petugas menambah data? Bila
    ya, pencariannya diperlukan, berapa pun jumlahnya hari ini.

    Dua hal yang TIDAK memakainya:
    - Enum seperti kondisi atau jenis fasilitas, sebab nilainya tidak bertambah
      dari tambah data.
    - Tabel referensi kecil seperti `satuan`, yang memang dapat ditambah Admin
      tetapi jumlahnya tidak akan pernah menuntut pencarian.

    Kotak pencarian SELALU ada, tanpa ambang jumlah opsi. Alasan pencabutan
    ambang itu ditulis pada blok penyusunan daftar di bawah.

    ## Mengapa bukan `<select>` beserta kotak cari terpisah

    Rancangan pertama komponen ini menaruh kotak pencarian DI ATAS `<select>`
    sebagai dua kontrol berjajar. Itu keliru: pengguna melihat dua kotak dan
    harus menebak sendiri bahwa yang satu menyaring yang lain, sementara
    keduanya tampak sama-sama dapat diisi. Satu pekerjaan tidak boleh
    memerlukan dua kontrol yang hubungannya tidak terlihat.

    Bentuk sekarang mengikuti pola combobox yang lazim: tombol menampilkan
    pilihan yang sedang aktif, dan pencarian berada di dalam panel yang sama
    dengan daftarnya, sehingga hubungan keduanya tidak perlu ditebak.

    ## Nilai yang terkirim

    Nilai disimpan pada `<input type="hidden">` bernama sama seperti kolomnya.
    Elemen itu menerima `.value` dan memancarkan event `change` sungguhan,
    sehingga tetap dilayani `isiFormulir()` milik `x-sim.modal-form` yang
    mengisi form saat modal ubah dibuka, dan tetap terbaca oleh pemanggil yang
    memasang `@change` sendiri.

    ## Bila JavaScript gagal dimuat

    `<select>` asli tetap dirender di dalam `<noscript>`. Sinyal di lokus tidak
    selalu stabil, dan form yang mustahil diisi karena satu berkas gagal
    diunduh adalah kegagalan yang tidak perlu.

    Pemakaian:
        <x-sim.pilih-cari nama="transmigran_id" label="Transmigran" :wajib="true"
            :opsi="$daftarTransmigran" kunci="id_transmigran"
            teks="nama_kepala_keluarga" keterangan-opsi="satuan_permukiman"
            :terpilih="$data['transmigran_id'] ?? null"
            placeholder="Pilih transmigran" />
--}}
@props([
    'nama',
    'label',
    'opsi' => [],
    'kunci' => 'id',
    'teks' => 'nama',
    'keteranganOpsi' => null,
    'terpilih' => null,
    'wajib' => false,
    'placeholder' => 'Pilih data',
    'keterangan' => null,
    'awalan' => null,
    // Bentuk penulisan keterangan di samping teks utama. `kurung`
    // menghasilkan "NAMA (NIK)", `pisah` menghasilkan "NAMA - SP". Yang
    // pertama dipakai bila keterangannya berupa pengenal tunggal seperti NIK,
    // sebab tanda kurung membacanya sebagai atribut, bukan bagian nama.
    'gaya' => 'pisah',
])

@php
    $id = ($awalan ? $awalan . '_' : '') . $nama;

    // `keteranganOpsi` menerima beberapa kolom sekaligus, dipisah koma, sebab
    // sebagian daftar baru dapat dibedakan lewat dua penanda: kode lahan yang
    // sama dapat muncul di SP berbeda dengan pemilik berbeda.
    $kolomKet = $keteranganOpsi
        ? array_map('trim', explode(',', $keteranganOpsi))
        : [];

    $daftar = [];
    foreach ($opsi as $baris) {
        $ket = [];
        foreach ($kolomKet as $kolom) {
            if (($baris[$kolom] ?? '') !== '') {
                $ket[] = (string) $baris[$kolom];
            }
        }

        $gabung = implode(', ', $ket);
        $utama = (string) ($baris[$teks] ?? '');

        $daftar[] = [
            'id' => (string) ($baris[$kunci] ?? ''),
            'teks' => $utama,
            'ket' => $gabung,
            // Label satu baris untuk tombol dan cadangan tanpa JavaScript.
            'label' => $gabung === ''
                ? $utama
                : ($gaya === 'kurung' ? "{$utama} ({$gabung})" : "{$utama} - {$gabung}"),
        ];
    }

    // KOTAK PENCARIAN SELALU DIRENDER, tanpa ambang jumlah opsi.
    //
    // Sebelumnya ia hanya muncul bila daftarnya mencapai delapan opsi, dengan
    // alasan "kotak pencarian di atas tiga pilihan menambah satu benda yang
    // harus dilewati". Ambang itu DICABUT 2026-08-20 atas keberatan pemilik
    // proyek, dan keberatannya tepat pada dua hal.
    //
    // Pertama, ambangnya dibandingkan terhadap jumlah baris `DummyData`, yaitu
    // data yang dikarang AI sendiri. Menyimpulkan "poktan baru empat baris jadi
    // wajar belum muncul" adalah penalaran melingkar yang dilarang rules.md
    // bagian 19a, dan kekeliruan itu terulang tiga kali pada butir yang sama.
    //
    // Kedua, yang menentukan bukan jumlah hari ini melainkan apakah daftarnya
    // BERTAMBAH ketika petugas menambah data. Bila ya, pencariannya memang
    // diperlukan, dan menyembunyikannya sampai melewati ambang hanya membuat
    // satu komponen berperilaku dua macam tanpa dapat diduga pemakainya.
    //
    // Alasan lama juga sudah tidak berlaku sejak komponen ini dibangun ulang:
    // kotak pencarian kini berada DI DALAM panel yang harus dibuka lebih dulu,
    // bukan berjajar di luar sebagai kontrol kedua. Yang hendak mengklik tetap
    // mengklik tanpa melewati apa pun.
    //
    // Isian yang sumbernya tabel referensi kecil seperti `satuan` sengaja tidak
    // memakai komponen ini sama sekali (ui-spec.md bagian 6.0a).

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';

    // Ekspresi Alpine milik pemanggil, diambil dari atribut agar dapat
    // dipasang pada tombol dan isian nilai sekaligus. Pembungkus terluar tidak
    // boleh menerimanya, sebab `disabled` tidak berlaku pada elemen biasa.
    //
    // DIBACA TANPA TITIK DUA, dan itu bukan kelalaian penulisan. Blade
    // memperlakukan `:nama` sebagai atribut TERIKAT: nilainya dievaluasi
    // sebagai PHP lalu disimpan pada kunci `nama` tanpa titik dua. Membacanya
    // sebagai `:required` karena itu SELALU menghasilkan null.
    //
    // Kekeliruan ini berlangsung diam-diam sejak komponen dibangun ulang
    // 2026-08-17: seluruh pemanggil yang memasang `:required` dan `:disabled`
    // tidak pernah mendapat keduanya, sehingga isian pada cabang form yang
    // sedang tersembunyi tetap aktif dan ikut terkirim. Tidak ada satu pun uji
    // yang memerah, sebab seluruhnya hanya memeriksa keberadaan atribut `name`
    // (agents/notes.md 1d.2).
    $ekspresiRequired = $attributes->get('required');
    $ekspresiDisabled = $attributes->get('disabled');

    // `@change` milik pemanggil WAJIB diambil dan digabung sendiri.
    //
    // Komponen ini tidak pernah memanggil `$attributes->merge()` pada elemen
    // mana pun, sehingga atribut yang dipasang pemanggil hilang begitu saja.
    // Isian nilai sendiri sudah memakai `@change="selaraskan()"`, dan Alpine
    // hanya menghormati satu pengendali per peristiwa pada satu elemen.
    //
    // Akibatnya sama diamnya: autofill telepon ketua poktan tidak pernah
    // bekerja, sebab `@change` yang membawa `isiKontak()` tidak pernah
    // terpasang sama sekali.
    $ekspresiChange = $attributes->get('@change') ?? $attributes->get('x-on:change');
@endphp

<div x-data="{
        terbuka: false,
        nilai: @js((string) $terpilih),
        cari: '',
        sorot: -1,
        daftar: @js($daftar),

        get terpilihItem() {
            return this.daftar.find((o) => o.id === this.nilai) ?? null;
        },

        get labelTombol() {
            return this.terpilihItem ? this.terpilihItem.label : @js($placeholder);
        },

        get tersaring() {
            const kata = this.cari.trim().toLowerCase();

            if (kata === '') {
                return this.daftar;
            }

            /*
                Dicocokkan pada teks maupun keterangannya, sebab petugas kerap
                mengingat asal SP lebih dulu daripada nama lengkapnya.
            */
            return this.daftar.filter(
                (o) => o.teks.toLowerCase().includes(kata) || o.ket.toLowerCase().includes(kata)
            );
        },

        buka() {
            if (this.$refs.nilai.disabled) {
                return;
            }

            this.terbuka = true;
            this.cari = '';
            this.sorot = this.tersaring.findIndex((o) => o.id === this.nilai);

            this.$nextTick(() => this.$refs.kotakCari?.focus());
        },

        tutup(kembalikanFokus = true) {
            this.terbuka = false;
            this.sorot = -1;

            if (kembalikanFokus) {
                this.$nextTick(() => this.$refs.tombol?.focus());
            }
        },

        pilih(o) {
            this.nilai = o.id;
            this.tutup();
            this.umumkan();
        },

        kosongkan() {
            this.nilai = '';
            this.umumkan();
        },

        /*
            Memancarkan event pada hidden input, bukan sekadar mengubah state
            Alpine. Tanpa ini pemanggil yang memasang `@change` tidak pernah
            tahu nilainya berubah, dan pengendali di luar komponen ikut diam.
        */
        umumkan() {
            this.$nextTick(() => {
                const el = this.$refs.nilai;
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            });
        },

        /*
            Menyelaraskan tampilan ketika nilai hidden input diubah dari luar,
            misalnya oleh `isiFormulir()` milik modal ubah yang menyetel
            `.value` lalu memancarkan `change`. Tanpa ini tombol tetap
            menampilkan teks placeholder padahal nilainya sudah terisi.
        */
        selaraskan() {
            this.nilai = this.$refs.nilai.value;
        },

        turun() {
            if (! this.terbuka) {
                this.buka();

                return;
            }

            this.sorot = Math.min(this.sorot + 1, this.tersaring.length - 1);
            this.gulirKeSorot();
        },

        naik() {
            this.sorot = Math.max(this.sorot - 1, 0);
            this.gulirKeSorot();
        },

        /* Opsi yang tersorot harus ikut terlihat, bukan hanya tertandai. */
        gulirKeSorot() {
            this.$nextTick(() => {
                this.$refs.daftar?.querySelector('[data-sorot=&quot;1&quot;]')
                    ?.scrollIntoView({ block: 'nearest' });
            });
        },

        pilihYangTersorot() {
            const o = this.tersaring[this.sorot];

            if (o) {
                this.pilih(o);
            }
        },
    }"
    @click.outside="tutup(false)"
    class="relative">

    <label for="{{ $id }}_tombol" class="{{ $kelasLabel }}">
        {{ $label }}{!! $wajib ? '<span class="text-error-500">*</span>' : '' !!}
    </label>

    {{--
        Nilai yang benar-benar terkirim.

        Bukan `type="hidden"` melainkan isian teks yang disembunyikan lewat
        gaya, sebab peramban MENGABAIKAN `required` pada isian tersembunyi:
        form akan terkirim tanpa peringatan apa pun meski isian wajib masih
        kosong. `sr-only` beserta `tabindex="-1"` membuatnya tetap dapat
        divalidasi tanpa ikut terjaring urutan Tab, dan pesan galatnya
        diarahkan ke tombol lewat `@invalid`.

        `x-ref` dipakai agar Alpine dapat membaca keadaan `disabled` yang
        dipasang pemanggil, dan `@change` menangkap perubahan yang datang dari
        luar komponen, misalnya dari `isiFormulir()` milik modal ubah.
    --}}
    <input type="text" id="{{ $id }}" name="{{ $nama }}" x-ref="nilai"
        :value="nilai" @change="selaraskan(); {{ $ekspresiChange ? $ekspresiChange . ';' : '' }}"
        tabindex="-1" aria-hidden="true"
        @invalid="$refs.tombol?.focus()"
        @if ($wajib && ! $ekspresiRequired) required @endif
        @if ($ekspresiRequired) :required="{!! $ekspresiRequired !!}" @endif
        @if ($ekspresiDisabled) :disabled="{!! $ekspresiDisabled !!}" @endif
        class="sr-only" />

    {{--
        Tombol pemicu. Berupa `<button>` agar ikut terjaring focus trap milik
        modal, yang hanya mengumpulkan a/button/input/select/textarea.
    --}}
    <button type="button" id="{{ $id }}_tombol" x-ref="tombol"
        @click="terbuka ? tutup() : buka()"
        @keydown.arrow-down.prevent="turun()"
        @keydown.enter.prevent="buka()"
        @keydown.space.prevent="buka()"
        :aria-expanded="terbuka" aria-haspopup="listbox"
        :aria-controls="@js($id . '_daftar')"
        role="combobox"
        @if ($ekspresiDisabled) :disabled="{!! $ekspresiDisabled !!}" @endif
        class="{{ $kelasKontrol }} flex items-center justify-between gap-2 text-left disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400 dark:disabled:bg-white/5">
        <span class="truncate" :class="terpilihItem ? '' : 'text-gray-400 dark:text-white/30'"
            x-text="labelTombol"></span>
        <svg class="h-4 w-4 shrink-0 text-gray-500 transition-transform duration-200 dark:text-gray-400"
            :class="{ 'rotate-180': terbuka }" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{--
        Panel. Escape dihentikan di sini agar tidak merambat ke modal: tekanan
        pertama menutup panel, tekanan kedua barulah menutup modalnya.

        Tinggi dibatasi dan digulir sendiri, sebab badan modal memakai
        `overflow-y-auto` sehingga panel yang lebih tinggi dari sisa ruang akan
        terpotong, bukan mengambang keluar.
    --}}
    <div x-show="terbuka" x-cloak
        @keydown.escape.stop.prevent="tutup()"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute left-0 right-0 z-50 mt-1 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-lg dark:border-gray-700 dark:bg-gray-900">

        <div class="border-b border-gray-200 p-2 dark:border-gray-700">
            <input type="search" x-ref="kotakCari" x-model="cari"
                @keydown.arrow-down.prevent="turun()"
                @keydown.arrow-up.prevent="naik()"
                @keydown.enter.prevent="pilihYangTersorot()"
                @keydown.tab="tutup(false)"
                @input="sorot = tersaring.length > 0 ? 0 : -1"
                aria-label="Cari pada daftar {{ \Illuminate\Support\Str::lower($label) }}"
                placeholder="Ketik untuk menyaring daftar"
                class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30" />
        </div>

        {{--
            Tinggi maksimal kira-kira lima opsi berketerangan. Dibatasi bukan
            sekadar demi rapi: badan modal memakai `overflow-y-auto`, sehingga
            panel yang lebih tinggi daripada sisa ruang akan terpotong, bukan
            mengambang keluar.
        --}}
        <div x-ref="daftar" id="{{ $id }}_daftar" role="listbox"
            :aria-label="@js($label)"
            class="custom-scrollbar max-h-64 overflow-y-auto py-1">

            {{-- Pilihan kosong, hanya bila isian ini memang boleh dikosongkan --}}
            @unless ($wajib)
                <button type="button" role="option" :aria-selected="nilai === ''"
                    @click="kosongkan(); tutup()"
                    class="block w-full px-3 py-2 text-left text-theme-sm text-gray-500 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none dark:text-gray-400 dark:hover:bg-white/5 dark:focus:bg-white/5">
                    {{ $placeholder }}
                </button>
            @endunless

            <template x-for="(o, i) in tersaring" :key="o.id">
                <button type="button" role="option"
                    :aria-selected="o.id === nilai"
                    :data-sorot="i === sorot ? '1' : '0'"
                    @click="pilih(o)" @mouseenter="sorot = i"
                    :class="{
                        'bg-brand-50 dark:bg-brand-500/10': i === sorot,
                        'font-medium text-brand-600 dark:text-brand-400': o.id === nilai,
                        'text-gray-700 dark:text-gray-300': o.id !== nilai,
                    }"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-theme-sm hover:bg-gray-50 focus:outline-none dark:hover:bg-white/5">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate" x-text="o.teks"></span>
                        <span x-show="o.ket" class="block truncate text-theme-xs text-gray-500 dark:text-gray-400"
                            x-text="o.ket"></span>
                    </span>

                    {{--
                        Centang pada pilihan yang sedang aktif. Warna saja tidak
                        cukup sebagai penanda: pengguna yang tidak membedakan
                        warna akan melihat seluruh baris tampak sama
                        (ANTISLOP-ID R-25).
                    --}}
                    <svg x-show="o.id === nilai" class="h-4 w-4 shrink-0 text-brand-600 dark:text-brand-400"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
            </template>

            {{-- Keadaan nihil perlu dikatakan; daftar yang mendadak kosong
                 tanpa penjelasan terbaca sebagai kerusakan. --}}
            <p x-show="tersaring.length === 0"
                class="px-3 py-4 text-center text-theme-xs text-gray-500 dark:text-gray-400">
                Tidak ada yang cocok dengan pencarian itu.
            </p>
        </div>
    </div>

    {{--
        Cadangan tanpa JavaScript. Sinyal di lokus tidak selalu stabil, dan
        form yang mustahil diisi karena satu berkas gagal diunduh adalah
        kegagalan yang tidak perlu.

        Isi `<noscript>` hanya diuraikan peramban ketika JavaScript benar-benar
        mati; bila hidup, seluruhnya diperlakukan sebagai teks biasa dan tidak
        ikut terkirim. Karena itu memakai nama yang sama dengan isian utama
        bukan masalah nama ganda.

        Aturan gaya di dalamnya menyembunyikan tombol pemicu yang memang tidak
        akan berfungsi tanpa Alpine, sekaligus menampakkan kembali isian
        tersembunyi supaya tidak ada dua kontrol berebut satu nama.
    --}}
    <noscript>
        <style>
            #{{ $id }}_tombol { display: none; }
            #{{ $id }} { display: none; }
        </style>

        <select name="{{ $nama }}"
            @if ($wajib && ! $ekspresiRequired) required @endif
            class="{{ $kelasKontrol }}">
            <option value="">{{ $placeholder }}</option>
            @foreach ($daftar as $o)
                <option value="{{ $o['id'] }}" @selected((string) $terpilih === $o['id'])>
                    {{ $o['label'] }}
                </option>
            @endforeach
        </select>
    </noscript>

    @if ($keterangan)
        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $keterangan }}</p>
    @endif
</div>
