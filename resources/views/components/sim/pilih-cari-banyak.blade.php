{{--
    Pilihan BERGANDA berdaftar panjang: satu tombol yang membuka panel berisi
    kotak pencarian dan daftar opsi yang dapat dicentang lebih dari satu.

    Saudara dari `x-sim.pilih-cari` (Putaran 7). Kriteria pemakaian sama:
    sumbernya TABEL DATA OPERASIONAL yang bertambah ketika petugas menambah
    data. Bedanya hanya jumlah nilai yang boleh dipilih.

    Dipakai ketika satu induk dibagikan ke banyak pihak sekaligus: satu batch
    alsintan/saprotan ke beberapa poktan, satu infrastruktur melayani beberapa
    SP, satu dokumen lahan mencakup beberapa bidang.

    ## Nilai yang terkirim

    Satu `<input class="sr-only" name="poktan_id[]">` per pilihan, dirender
    lewat `x-for`. Form Request Tahap 5 membacanya sebagai larik. TIDAK memakai
    `type="hidden"` sebab peramban mengabaikan `required` pada isian tersembunyi.

    ## Menyalurkan pilihan ke luar komponen

    `sinkron-ke` menerima ekspresi Alpine milik leluhur (mis. sebuah larik pada
    `x-data` form). Setiap kali pilihan berubah, larik itu ditimpa salinan
    `nilai`, sehingga repeater `x-for` di form dapat menampilkan satu baris per
    pihak terpilih. Kosongkan bila tidak diperlukan.

    ## Bila JavaScript gagal dimuat

    `<select multiple>` asli tetap dirender di dalam `<noscript>`.

    Pemakaian:
        <x-sim.pilih-cari-banyak nama="satuan_permukiman_id" label="SP yang dilayani"
            :opsi="$daftarSp" kunci="id" teks="nama"
            :terpilih="$data['satuan_permukiman_ids'] ?? []"
            sinkron-ke="spDilayani" placeholder="Pilih satu atau lebih SP" />
--}}
@props([
    'nama',
    'label',
    'opsi' => [],
    'kunci' => 'id',
    'teks' => 'nama',
    'keteranganOpsi' => null,
    'terpilih' => [],
    'wajib' => false,
    'placeholder' => 'Pilih satu atau lebih',
    'keterangan' => null,
    'awalan' => null,
    'gaya' => 'pisah',
    'sinkronKe' => null,
])

@php
    $id = ($awalan ? $awalan . '_' : '') . $nama;

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
            'label' => $gabung === ''
                ? $utama
                : ($gaya === 'kurung' ? "{$utama} ({$gabung})" : "{$utama} - {$gabung}"),
        ];
    }

    // Nilai awal sebagai larik string, sejalan dengan `daftar[].id`.
    $terpilihAwal = array_values(array_map('strval', (array) $terpilih));

    $kelasKontrol = 'min-h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-1.5 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';

    $ekspresiDisabled = $attributes->get('disabled');
@endphp

<div x-data="{
        terbuka: false,
        nilai: @js($terpilihAwal),
        cari: '',
        sorot: -1,
        daftar: @js($daftar),

        get tersaring() {
            const kata = this.cari.trim().toLowerCase();

            if (kata === '') {
                return this.daftar;
            }

            return this.daftar.filter(
                (o) => o.teks.toLowerCase().includes(kata) || o.ket.toLowerCase().includes(kata)
            );
        },

        get terpilihItem() {
            return this.daftar.filter((o) => this.nilai.includes(o.id));
        },

        adaTerpilih(id) {
            return this.nilai.includes(id);
        },

        buka() {
            if (this.$refs.penjaga.disabled) {
                return;
            }

            this.terbuka = true;
            this.cari = '';
            this.sorot = -1;
            this.$nextTick(() => this.$refs.kotakCari?.focus());
        },

        tutup(kembalikanFokus = true) {
            this.terbuka = false;
            this.sorot = -1;

            if (kembalikanFokus) {
                this.$nextTick(() => this.$refs.tombol?.focus());
            }
        },

        /* Toggle. TIDAK menutup panel: petugas lazim memilih beberapa sekaligus. */
        toggle(o) {
            if (this.nilai.includes(o.id)) {
                this.nilai = this.nilai.filter((x) => x !== o.id);
            } else {
                this.nilai = [...this.nilai, o.id];
            }
            this.umumkan();
        },

        lepas(id) {
            this.nilai = this.nilai.filter((x) => x !== id);
            this.umumkan();
        },

        umumkan() {
            @if ($sinkronKe)
                {{ $sinkronKe }} = [...this.nilai];
            @endif
            this.$nextTick(() => {
                const el = this.$refs.penjaga;
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            });
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

        gulirKeSorot() {
            this.$nextTick(() => {
                this.$refs.daftar?.querySelector('[data-sorot=&quot;1&quot;]')
                    ?.scrollIntoView({ block: 'nearest' });
            });
        },

        toggleYangTersorot() {
            const o = this.tersaring[this.sorot];

            if (o) {
                this.toggle(o);
            }
        },
    }"
    @click.outside="tutup(false)"
    class="relative">

    <label for="{{ $id }}_tombol" class="{{ $kelasLabel }}">
        {{ $label }}{!! $wajib ? '<span class="text-error-500">*</span>' : '' !!}
    </label>

    {{--
        Isian per pilihan. Larik `name="...[]"` dibaca Form Request sebagai
        larik. Dirender lewat `x-for` supaya jumlahnya mengikuti pilihan.
    --}}
    <template x-for="id in nilai" :key="id">
        <input type="hidden" name="{{ $nama }}[]" :value="id" />
    </template>

    {{--
        Penjaga wajib-isi. TANPA `name` sehingga tidak ikut terkirim, tetapi
        tetap diperiksa validasi form: kosong bila belum ada satu pun pilihan.
    --}}
    <input type="text" x-ref="penjaga" tabindex="-1" aria-hidden="true"
        class="sr-only" :value="nilai.length ? 'terisi' : ''"
        @invalid="$refs.tombol?.focus()"
        @if ($wajib) required @endif
        @if ($ekspresiDisabled) :disabled="{{ $ekspresiDisabled }}" @endif />

    <button type="button" id="{{ $id }}_tombol" x-ref="tombol"
        @click="terbuka ? tutup() : buka()"
        @keydown.arrow-down.prevent="turun()"
        @keydown.enter.prevent="buka()"
        @keydown.space.prevent="buka()"
        :aria-expanded="terbuka" aria-haspopup="listbox"
        :aria-controls="@js($id . '_daftar')"
        role="combobox"
        @if ($ekspresiDisabled) :disabled="{{ $ekspresiDisabled }}" @endif
        class="{{ $kelasKontrol }} flex flex-wrap items-center gap-1.5 text-left disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400 dark:disabled:bg-white/5">

        <span x-show="nilai.length === 0" class="text-gray-400 dark:text-white/30">{{ $placeholder }}</span>

        {{-- Chip per pilihan, dapat dilepas. --}}
        <template x-for="o in terpilihItem" :key="o.id">
            <span class="inline-flex items-center gap-1 rounded-md bg-brand-50 px-2 py-0.5 text-theme-xs font-medium text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                <span x-text="o.teks"></span>
                <button type="button" @click.stop="lepas(o.id)"
                    :aria-label="'Lepas ' + o.teks"
                    class="rounded text-brand-500 hover:text-brand-700 focus:outline-1 focus:outline-brand-500 dark:hover:text-brand-200">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </span>
        </template>

        <svg class="ml-auto h-4 w-4 shrink-0 text-gray-500 transition-transform duration-200 dark:text-gray-400"
            :class="{ 'rotate-180': terbuka }" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

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
                @keydown.enter.prevent="toggleYangTersorot()"
                @keydown.tab="tutup(false)"
                @input="sorot = tersaring.length > 0 ? 0 : -1"
                aria-label="Cari pada daftar {{ \Illuminate\Support\Str::lower($label) }}"
                placeholder="Ketik untuk menyaring daftar"
                class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30" />
        </div>

        <div x-ref="daftar" id="{{ $id }}_daftar" role="listbox" aria-multiselectable="true"
            :aria-label="@js($label)"
            class="custom-scrollbar max-h-64 overflow-y-auto py-1">

            <template x-for="(o, i) in tersaring" :key="o.id">
                <button type="button" role="option"
                    :aria-selected="adaTerpilih(o.id)"
                    :data-sorot="i === sorot ? '1' : '0'"
                    @click="toggle(o)" @mouseenter="sorot = i"
                    :class="{
                        'bg-brand-50 dark:bg-brand-500/10': i === sorot,
                        'font-medium text-brand-600 dark:text-brand-400': adaTerpilih(o.id),
                        'text-gray-700 dark:text-gray-300': ! adaTerpilih(o.id),
                    }"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-theme-sm hover:bg-gray-50 focus:outline-none dark:hover:bg-white/5">

                    {{-- Kotak centang: penanda lebih dari sekadar warna (R-25). --}}
                    <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border"
                        :class="adaTerpilih(o.id)
                            ? 'border-brand-600 bg-brand-600 text-white dark:border-brand-400 dark:bg-brand-500'
                            : 'border-gray-300 dark:border-gray-600'">
                        <svg x-show="adaTerpilih(o.id)" class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="3" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate" x-text="o.teks"></span>
                        <span x-show="o.ket" class="block truncate text-theme-xs text-gray-500 dark:text-gray-400"
                            x-text="o.ket"></span>
                    </span>
                </button>
            </template>

            <p x-show="tersaring.length === 0"
                class="px-3 py-4 text-center text-theme-xs text-gray-500 dark:text-gray-400">
                Tidak ada yang cocok dengan pencarian itu.
            </p>
        </div>
    </div>

    <noscript>
        <style>
            #{{ $id }}_tombol { display: none; }
        </style>

        <select name="{{ $nama }}[]" multiple size="6"
            @if ($wajib) required @endif
            class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-theme-sm text-gray-800 dark:border-gray-700 dark:text-white/90">
            @foreach ($daftar as $o)
                <option value="{{ $o['id'] }}" @selected(in_array($o['id'], $terpilihAwal, true))>
                    {{ $o['label'] }}
                </option>
            @endforeach
        </select>
    </noscript>

    @if ($keterangan)
        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $keterangan }}</p>
    @endif
</div>
