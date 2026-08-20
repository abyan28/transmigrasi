{{--
    Isian kawasan transmigrasi.

    Kawasan adalah hierarki PROGRAM, terpisah dari hierarki administratif
    provinsi sampai desa. Satu kawasan dapat mencakup beberapa kecamatan,
    sehingga tidak dapat diturunkan dari struktur wilayah biasa
    (agents/erd.md bagian 7.0).

    KABUPATEN DIPILIH LEWAT DUA TINGKAT, provinsi lebih dulu. Cabang program
    memang memotong batas kecamatan, tetapi pangkalnya tetap sama: kabupaten
    berada di bawah provinsi. Menyodorkan daftar kabupaten se-Indonesia tanpa
    menanyakan provinsinya membuat petugas mencari di antara lima ratusan nama
    yang sebagian besar tidak pernah relevan, dan nama kabupaten pun tidak
    selalu unik antar-provinsi.

    Penyaringannya di sisi klien: seluruh kabupaten dirender lalu disembunyikan
    mengikuti provinsi terpilih. Cara ini dipilih agar tidak ada permintaan
    tambahan ke peladen, sejalan dengan pola autofill pada form poktan. Bila
    kelak daftarnya mencapai ribuan baris, barulah pemuatan bertahap sepadan.

    Nama kolom mengikuti agents/data-dictionary.md bagian 3.5.
--}}
@php
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    $daftarProvinsi = DummyData::wilayah()['provinsi'];
    $daftarKabupaten = DummyData::wilayah()['kabupaten'];

    $kabupatenTerpilih = old('kabupaten_id', $data['kabupaten_id'] ?? '');

    // Provinsi awal diturunkan dari kabupaten yang sedang terpilih, bukan
    // disimpan tersendiri: `kawasan` hanya menyimpan `kabupaten_id`, dan
    // provinsinya terbaca lewat rantai itu. Menyimpannya terpisah membuka
    // peluang data tidak sinkron, kekeliruan yang sama sudah dihindari saat
    // memutuskan SP tidak menyimpan `kecamatan_id` (erd.md 1a.8).
    $provinsiAwal = '';
    foreach ($daftarKabupaten as $kab) {
        if ((string) $kab['id_kabupaten'] === (string) $kabupatenTerpilih) {
            $provinsiAwal = (string) $kab['provinsi_id'];
            break;
        }
    }
@endphp

<div class="space-y-6"
    x-data="{
        provinsiId: @js(old('provinsi_id', $provinsiAwal)),
        kabupatenId: @js((string) $kabupatenTerpilih),
        kabupaten: @js(array_map(fn ($k) => ['id' => (string) $k['id_kabupaten'], 'nama' => $k['nama'], 'provinsi_id' => (string) $k['provinsi_id']], $daftarKabupaten)),

        get kabupatenTersaring() {
            return this.kabupaten.filter((k) => k.provinsi_id === this.provinsiId);
        },

        {{--
            Melepas kabupaten yang tidak lagi berada pada provinsi terpilih.
            Tanpa ini, mengganti provinsi setelah memilih kabupaten menyisakan
            nilai lama yang tidak terlihat lagi di daftar, dan form terkirim
            membawa kabupaten dari provinsi yang keliru.
        --}}
        gantiProvinsi() {
            if (! this.kabupatenTersaring.some((k) => k.id === this.kabupatenId)) {
                this.kabupatenId = '';
            }
        },
    }">
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Kawasan</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_nama_kawasan" class="{{ $kelasLabel }}">Nama Kawasan<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_kawasan" name="nama" required
                    value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: Kobalima Timur" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_kode_kawasan" class="{{ $kelasLabel }}">Kode Kawasan</label>
                <input type="text" id="{{ $awalan }}_kode_kawasan" name="kode_kawasan"
                    value="{{ old('kode_kawasan', $data['kode_kawasan'] ?? '') }}" maxlength="20"
                    placeholder="Contoh: KWS-KBT" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_provinsi_kawasan" class="{{ $kelasLabel }}">Provinsi<span class="text-error-500">*</span></label>
                {{--
                    Sengaja TANPA `required`. Isian ini hanya menyaring dan
                    tidak disimpan, sehingga menandainya wajib akan memblokir
                    pengiriman ketika JavaScript mati dan select ini
                    disembunyikan. Yang benar-benar wajib adalah kabupatennya.
                --}}
                <select id="{{ $awalan }}_provinsi_kawasan" name="provinsi_id"
                    x-model="provinsiId" @change="gantiProvinsi()" class="{{ $kelasKontrol }}">
                    <option value="">Pilih provinsi</option>
                    @foreach ($daftarProvinsi as $prov)
                        <option value="{{ $prov['id_provinsi'] }}"
                            @selected((string) old('provinsi_id', $provinsiAwal) === (string) $prov['id_provinsi'])>
                            {{ $prov['nama'] }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Menyaring daftar kabupaten. Tidak disimpan tersendiri, sebab provinsi sudah terbaca
                    lewat kabupatennya.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_kabupaten_kawasan" class="{{ $kelasLabel }}">Kabupaten<span class="text-error-500">*</span></label>
                {{--
                    Opsi dirender Alpine lewat `x-for`, sehingga hanya kabupaten
                    pada provinsi terpilih yang tampil. Dinonaktifkan selama
                    provinsi belum dipilih: dropdown berisi satu baris kosong
                    lebih jujur daripada dropdown yang tampak dapat dibuka
                    tetapi tidak menawarkan apa pun.
                --}}
                {{--
                    `required` dipasang Alpine, bukan ditulis tetap: bila
                    JavaScript mati select ini disembunyikan `<noscript>`, dan
                    `required` tetap pada elemen tersembunyi memblokir
                    pengiriman tanpa pesan yang dapat dilihat petugas.
                --}}
                <select id="{{ $awalan }}_kabupaten_kawasan" name="kabupaten_id"
                    x-model="kabupatenId" :disabled="provinsiId === ''" :required="provinsiId !== ''"
                    class="{{ $kelasKontrol }} disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400 dark:disabled:bg-white/5">
                    <option value="" x-text="provinsiId === '' ? 'Pilih provinsi lebih dulu' : 'Pilih kabupaten'">
                        Pilih kabupaten
                    </option>
                    <template x-for="kab in kabupatenTersaring" :key="kab.id">
                        <option :value="kab.id" x-text="kab.nama"></option>
                    </template>
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Kawasan menempel pada kabupaten, bukan pada kecamatan, sebab satu kawasan dapat
                    mencakup beberapa kecamatan sekaligus.
                </p>

                {{--
                    Jaminan tanpa JavaScript, mengikuti pola `pilih-cari`.

                    Isi `<noscript>` hanya diuraikan peramban ketika JavaScript
                    benar-benar mati; bila hidup, seluruhnya teks biasa dan
                    tidak ikut terkirim, sehingga nama yang sama bukan masalah.

                    Aturan gayanya menyembunyikan select bertingkat yang tanpa
                    Alpine akan tampil kosong dan terkunci, supaya tidak ada dua
                    kontrol berebut satu nama. Daftarnya memuat nama provinsi
                    agar kabupaten senama tetap dapat dibedakan.
                --}}
                <noscript>
                    <style>
                        #{{ $awalan }}_provinsi_kawasan,
                        #{{ $awalan }}_kabupaten_kawasan { display: none; }
                    </style>

                    <select name="kabupaten_id" required class="{{ $kelasKontrol }}">
                        <option value="">Pilih kabupaten</option>
                        @foreach ($daftarKabupaten as $kab)
                            <option value="{{ $kab['id_kabupaten'] }}"
                                @selected((string) $kabupatenTerpilih === (string) $kab['id_kabupaten'])>
                                {{ $kab['nama'] }} &mdash; {{ $kab['provinsi'] }}
                            </option>
                        @endforeach
                    </select>
                </noscript>
            </div>

            <div>
                <label for="{{ $awalan }}_luas_total" class="{{ $kelasLabel }}">Luas Total</label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_luas_total" name="luas_total"
                        value="{{ old('luas_total', $data['luas_total'] ?? '') }}" min="0" step="0.01"
                        placeholder="4250.75" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span
                        class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h3 class="{{ $kelasBagian }}">Dasar Penetapan</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_nomor_sk" class="{{ $kelasLabel }}">Nomor SK Penetapan</label>
                <input type="text" id="{{ $awalan }}_nomor_sk" name="nomor_sk"
                    value="{{ old('nomor_sk', $data['nomor_sk'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: SK.123/MEN-TRANS/2015" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_tahun_penetapan" class="{{ $kelasLabel }}">Tahun Penetapan</label>
                <input type="number" id="{{ $awalan }}_tahun_penetapan" name="tahun_penetapan"
                    value="{{ old('tahun_penetapan', $data['tahun_penetapan'] ?? '') }}" min="1950"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div class="sm:col-span-2">
                <x-sim.file-upload nama="dokumen_pendukung" label="Salinan SK Penetapan"
                    keterangan="PDF atau gambar, maksimal 5 MB." />
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_keterangan_kawasan" class="{{ $kelasLabel }}">Keterangan</label>
                <textarea id="{{ $awalan }}_keterangan_kawasan" name="keterangan" rows="2" maxlength="255"
                    placeholder="Catatan tambahan mengenai kawasan ini."
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
            </div>
        </div>
    </section>
</div>
