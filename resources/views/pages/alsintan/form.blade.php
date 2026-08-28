{{--
    Isian data alsintan, dipakai bersama modal tambah dan modal ubah.

    PEMILIK SELALU KELOMPOK TANI (agents/rules.md bagian 7b). Kepemilikan
    pribadi dicabut 2026-08-22 mengikuti keputusan pemilik proyek bahwa
    seluruh menu Pertanian mencatat KELOMPOK, bukan individu.

    Sebelumnya form ini menyodorkan dua jalur pemilik yang tampil bergantian,
    dan akibatnya terlihat pada data: alat pribadi tidak dapat dijangkau dari
    halaman mana pun kecuali daftar alsintan itu sendiri. Ia tidak muncul pada
    rincian poktan sebab tidak berpoktan, dan halaman transmigran tidak pernah
    punya tab alsintan.

    Alat yang dibeli dari iuran anggota tetap tercatat atas nama kelompok,
    dengan sumber perolehan bernilai Swadaya.

    Nama kolom mengikuti agents/data-dictionary.md bagian 8.3.
--}}
{{--
    `$daftarPoktan`, `$opsiKondisi`, dan `$opsiSumberDana` disuplai
    ViewServiceProvider, bukan oleh induk yang menyisipkan berkas ini.
    Berkas form dipakai tiga modal sekaligus — tambah dan ubah pada halaman
    daftar, serta ubah pada halaman rincian — sehingga menyalurkannya lewat
    rute menuntut tiga rute mengoper isian yang sama persis.
--}}
@php
    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    // Peta poktan ke satuan permukimannya, dibaca Alpine untuk mengisi kolom
    // SP begitu poktan dipilih. Dahulu ada dua peta terpisah sebab kepemilikan
    // pribadi menunjuk transmigran; peta transmigran ikut lepas bersama
    // pencabutan kepemilikan itu.
    $petaSpPoktan = [];
    foreach ($daftarPoktan as $p) {
        $petaSpPoktan[(string) $p['id_poktan']] = [
            'id' => (string) $p['satuan_permukiman_id'],
            'nama' => $p['satuan_permukiman'],
        ];
    }
@endphp

{{--
    SATUAN PERMUKIMAN MENGIKUTI PEMILIK, tidak dipilih sendiri.

    Poktan sudah menyimpan SP-nya sendiri. Membiarkan petugas memilih SP
    secara terpisah memungkinkan satu alat tercatat di SP yang berbeda dari
    pemiliknya, dan tidak ada penjaga apa pun yang menangkapnya.
--}}
<div class="space-y-6"
    x-data="{
        poktanId: @js((string) old('poktan_id', $data['poktan_id'] ?? '')),
        petaSpPoktan: @js($petaSpPoktan),
        anggotaPerPoktan: @js($anggotaPerPoktan),

        get spTerpilih() { return this.petaSpPoktan[this.poktanId] ?? null; },
        get daftarPenerima() { return this.anggotaPerPoktan[this.poktanId] ?? []; },
    }">

    {{-- Bagian 1: identitas alat --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Alat</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_nama_alat" class="{{ $kelasLabel }}">Nama Alat<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_alat" name="nama_alat" required
                    value="{{ old('nama_alat', $data['nama_alat'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: TRAKTOR RODA DUA" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_jumlah" class="{{ $kelasLabel }}">Jumlah Unit<span class="text-error-500">*</span></label>
                <input type="number" id="{{ $awalan }}_jumlah" name="jumlah" required
                    value="{{ old('jumlah', $data['jumlah'] ?? '') }}" min="1" step="1"
                    class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_tahun_pengadaan" class="{{ $kelasLabel }}">Tahun Pengadaan</label>
                <input type="number" id="{{ $awalan }}_tahun_pengadaan" name="tahun_pengadaan"
                    value="{{ old('tahun_pengadaan', $data['tahun_pengadaan'] ?? '') }}" min="1900"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_kondisi" class="{{ $kelasLabel }}">Kondisi</label>
                <select id="{{ $awalan }}_kondisi" name="kondisi" class="{{ $kelasKontrol }}">
                    @foreach ($opsiKondisi as $nilaiRef => $labelRef)
                        <option value="{{ $nilaiRef }}" @selected(old('kondisi', $data['kondisi'] ?? '') === $nilaiRef)>
                            {{ $nilaiRef }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_sumber_dana" class="{{ $kelasLabel }}">Sumber Dana</label>
                <select id="{{ $awalan }}_sumber_dana" name="sumber_dana" class="{{ $kelasKontrol }}">
                    <option value="">Pilih sumber</option>
                    @foreach ($opsiSumberDana as $nilaiRef => $labelRef)
                        <option value="{{ $nilaiRef }}"
                            @selected(old('sumber_dana', $data['sumber_dana'] ?? '') === $nilaiRef)>
                            {{ $nilaiRef }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    {{-- Bagian 2: pemilik --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Pemilik</h3>
        <div class="mt-3 space-y-4">
            {{--
                SATU JALUR PEMILIK, tidak lagi bercabang.

                Radio "Jenis Kepemilikan" beserta isian Transmigran Pemilik
                dicabut 2026-08-22: seluruh menu Pertanian mencatat kelompok,
                bukan individu. Alat yang dibeli dari iuran anggota tetap
                tercatat atas nama kelompok, dengan sumber perolehan Swadaya.

                Memakai `pilih-cari` meski data contoh hanya 4 poktan.
                Ambangnya sendiri yang menentukan kapan kotak pencarian
                muncul, sehingga pada data contoh ia tetap tampil sebagai
                dropdown biasa. Yang penting: begitu data nyata masuk dan
                poktan mencapai puluhan, pencariannya sudah ada tanpa perlu
                menyunting halaman ini lagi.
            --}}
            <x-sim.pilih-cari nama="poktan_id" label="Kelompok Tani Pemilik" :wajib="true"
                :awalan="$awalan" :opsi="$daftarPoktan" kunci="id_poktan"
                teks="nama" keterangan-opsi="satuan_permukiman"
                :terpilih="old('poktan_id', $data['poktan_id'] ?? null)"
                placeholder="Pilih kelompok tani"
                keterangan="Alat dipakai bergilir antar-anggota, sehingga tercatat atas nama kelompok."
                @change="poktanId = $event.target.value" />

            {{-- Terbaca dari poktan, bukan dipilih. Alasannya di kepala berkas. --}}
            <div>
                <span class="{{ $kelasLabel }}">Satuan Permukiman</span>
                <p class="flex h-11 items-center rounded-lg bg-gray-50 px-4 text-theme-sm text-gray-600 dark:bg-white/5 dark:text-gray-400">
                    <span x-show="spTerpilih" x-text="spTerpilih?.nama"></span>
                    <span x-show="! spTerpilih" x-cloak class="text-gray-400 dark:text-white/30">
                        Terisi otomatis setelah kelompok tani dipilih
                    </span>
                </p>
                <input type="hidden" name="satuan_permukiman_id" :value="spTerpilih?.id ?? ''" />
            </div>

            {{--
                Penanda tangan serah terima, BUKAN pemilik. Alat tetap milik
                kelompok (rules.md 7b poin 1); yang dicatat hanya siapa yang
                mewakili saat barang diterima, dari berita acara.

                Pilihannya ikut poktan yang dipilih, dan hanya anggota aktif —
                ketua maupun anggota biasa. Kosong dibiarkan sah, sebab data
                lama lahir sebelum kolom ini ada.
            --}}
            <div>
                <label for="{{ $awalan }}_penanda_terima_id" class="{{ $kelasLabel }}">
                    Penerima (Penanda Tangan Serah Terima)
                </label>
                <select id="{{ $awalan }}_penanda_terima_id" name="penanda_terima_id"
                    :disabled="! poktanId" class="{{ $kelasKontrol }}">
                    <option value="">Belum dicatat</option>
                    <template x-for="anggota in daftarPenerima" :key="anggota.id">
                        <option :value="anggota.id"
                            :selected="anggota.id === @js((string) old('penanda_terima_id', $data['penanda_terima_id'] ?? ''))"
                            x-text="anggota.nama + ' - ' + anggota.jabatan"></option>
                    </template>
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400"
                    x-show="! poktanId" x-cloak>
                    Pilih kelompok tani lebih dulu.
                </p>
            </div>
        </div>
    </section>


    {{--
        Catatan diletakkan SEBELUM unggahan, dan unggahan selalu paling
        bawah (ui-spec.md 6.4a poin 5). Isian berkas menuntut perhatian
        lebih lama daripada isian teks, sehingga menaruhnya di tengah
        memutus alur pengisian.
    --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Catatan</h3>
        <div class="mt-3">
            <label for="{{ $awalan }}_keterangan" class="{{ $kelasLabel }}">Catatan</label>
            <textarea id="{{ $awalan }}_keterangan" name="keterangan" rows="3" maxlength="1000"
                placeholder="Contoh: mesin sering panas setelah dua jam pemakaian."
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
        </div>
    </section>

    {{--
        DUA KOLOM TERPISAH: foto dan dokumen.

        Keduanya menjawab hal berbeda. Foto merekam wujud dan kondisi
        barang saat pendataan, dokumen menyimpan berkas administratifnya.
        Satu slot untuk keduanya memaksa petugas memilih salah satu, dan
        yang mengunggah dokumen setelah foto akan kehilangan fotonya tanpa
        peringatan apa pun.

        Pola ini mengikuti inventaris, fasilitas, dan infrastruktur SP
        yang sudah lebih dulu memisahkan keduanya.
    --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Dokumentasi</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <x-sim.file-upload nama="foto" label="Foto Alat" :hanya-gambar="true"
                nama-dokumen="Foto Alsintan" :nama-pemilik="$data['nama_alat'] ?? null"
                :berkas-saat-ini="$data['foto'] ?? null"
                keterangan="Dokumentasi wujud dan kondisi alat saat pendataan." />

            <x-sim.file-upload nama="dokumen_pendukung" label="Dokumen Pendukung"
                nama-dokumen="Dokumen Alsintan" :nama-pemilik="$data['nama_alat'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                keterangan="Berita acara penyerahan atau bukti pengadaan." />
        </div>
    </section>
</div>