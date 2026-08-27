{{--
    Isian data sarana produksi pertanian.

    Dua aturan yang dijaga di sini:

    1. PENERIMA SELALU KELOMPOK TANI. Pilihan penerima individu dicabut
       2026-08-22: seluruh pencatatan Produksi Pertanian berpusat pada poktan,
       bukan perorangan. Menyediakan dua jalur membuat sebagian bantuan
       tercatat atas nama orang dan sebagian atas nama kelompok, sehingga
       rekap per poktan tidak pernah utuh.
    2. SATUAN PERMUKIMAN MENGIKUTI POKTAN, tidak dipilih sendiri. Poktan sudah
       menyimpan SP-nya, dan membiarkan petugas memilih SP secara terpisah
       memungkinkan bantuan tercatat di SP yang berbeda dari poktannya.

    Nama kolom mengikuti agents/data-dictionary.md bagian 8.4.
--}}
@php
    use App\Enums\JenisSaprotan;
    use App\Enums\SumberDana;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    // `$daftarPoktan`, `$daftarSatuan`, `$daftarKomoditas`, dan
    // `$opsiSumberDana` disuplai ViewServiceProvider, sebab berkas ini
    // disisipkan dari halaman daftar maupun halaman rincian.

    // Peta poktan ke satuan permukimannya, dibaca Alpine untuk mengisi kolom
    // SP begitu poktan dipilih. Disusun di sini, bukan di dalam markup, agar
    // pencariannya tidak diulang setiap kali pilihan berubah.
    $petaSpPoktan = [];
    foreach ($daftarPoktan as $p) {
        $petaSpPoktan[(string) $p['id_poktan']] = [
            'id' => (string) $p['satuan_permukiman_id'],
            'nama' => $p['satuan_permukiman'],
        ];
    }
@endphp

<div class="space-y-6"
    x-data="{
        poktanId: @js((string) old('poktan_id', $data['poktan_id'] ?? '')),
        petaSp: @js($petaSpPoktan),
        jenis: @js(old('jenis', $data['jenis'] ?? '')),
        get spTerpilih() { return this.petaSp[this.poktanId] ?? null; },
        get benih() { return this.jenis === @js(JenisSaprotan::Benih->value); },
    }">

    {{-- Bagian 1: identitas sarana --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Sarana</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_jenis" class="{{ $kelasLabel }}">Jenis Saprotan<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_jenis" name="jenis" required x-model="jenis" class="{{ $kelasKontrol }}">
                    <option value="">Pilih jenis</option>
                    @foreach (JenisSaprotan::cases() as $j)
                        <option value="{{ $j->value }}" @selected(old('jenis', $data['jenis'] ?? '') === $j->value)>
                            {{ $j->value }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_nama" class="{{ $kelasLabel }}">Nama Sarana<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama" name="nama" required
                    value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: BENIH JAGUNG HIBRIDA" class="{{ $kelasKontrol }}" />
            </div>

            {{--
                KOMODITAS, MUNCUL HANYA UNTUK BENIH.

                Benih selalu benih SESUATU, dan tanpa kolom ini kaitannya
                hanya tersirat dari teks namanya: sistem tidak tahu "BENIH
                JAGUNG HIBRIDA" itu benih jagung, sehingga form penanaman
                tidak dapat menyaringnya dan petugas bebas memilih benih padi
                untuk penanaman jagung.

                Pupuk, pestisida, dan mulsa TIDAK ditanya: urea dipakai
                tanaman apa pun, dan memaksanya memilih satu komoditas berarti
                mengarang data yang tidak ada di lapangan.

                `:required` mengikuti jenis, bukan dipasang tetap, sebab isian
                yang sedang tersembunyi tetap menghalangi pengiriman form bila
                `required`-nya menyala (pola sama dengan form lahan dan rumah).
            --}}
            <div x-show="benih" x-cloak x-transition class="sm:col-span-2">
                <label for="{{ $awalan }}_komoditas_id" class="{{ $kelasLabel }}">
                    Komoditas<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_komoditas_id" name="komoditas_id"
                    :required="benih" :disabled="! benih" class="{{ $kelasKontrol }}">
                    <option value="">Pilih komoditas</option>
                    @foreach ($daftarKomoditas as $k)
                        <option value="{{ $k['id_komoditas'] }}"
                            @selected((string) old('komoditas_id', $data['komoditas_id'] ?? '') === (string) $k['id_komoditas'])>
                            {{ $k['nama'] }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Menentukan penanaman mana yang boleh memakai benih ini. Hanya ditanyakan untuk jenis Benih.
                </p>
            </div>

            {{--
                Varietas hanya ditanya untuk benih, alasan yang sama dengan
                komoditas (rules.md 7c poin 12). Ia menentukan perlakuan tanam;
                pupuk dan pestisida tidak punya varietas.
            --}}
            <div x-show="benih" x-cloak x-transition>
                <label for="{{ $awalan }}_varietas" class="{{ $kelasLabel }}">
                    Varietas<span class="text-error-500">*</span>
                </label>
                <input type="text" id="{{ $awalan }}_varietas" name="varietas"
                    :required="benih" :disabled="! benih" maxlength="120"
                    value="{{ old('varietas', $data['varietas'] ?? '') }}"
                    placeholder="Contoh: Hibrida Bisi-18, IR64" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_jumlah" class="{{ $kelasLabel }}">Jumlah<span class="text-error-500">*</span></label>
                <input type="number" id="{{ $awalan }}_jumlah" name="jumlah" required
                    value="{{ old('jumlah', $data['jumlah'] ?? '') }}" min="0" step="0.01"
                    class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_satuan_id" class="{{ $kelasLabel }}">Satuan<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_satuan_id" name="satuan_id" required class="{{ $kelasKontrol }}">
                    <option value="">Pilih satuan</option>
                    @foreach ($daftarSatuan as $s)
                        <option value="{{ $s['id_satuan'] }}"
                            @selected(old('satuan', $data['satuan'] ?? '') === $s['nama'])>
                            {{ $s['nama'] }} ({{ $s['simbol'] }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_tahun_pengadaan" class="{{ $kelasLabel }}">
                    Tahun Pengadaan<span class="text-error-500">*</span>
                </label>
                <input type="number" id="{{ $awalan }}_tahun_pengadaan" name="tahun_pengadaan" required
                    value="{{ old('tahun_pengadaan', $data['tahun_pengadaan'] ?? '') }}" min="2000"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Tahun anggaran APBD/APBN yang membiayai bantuan ini, dari berita acara.
                    BUKAN tahun barang diterima: laporan hasil panen mengikuti tahun ini.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_jadwal_tanam" class="{{ $kelasLabel }}">Jadwal Tanam</label>
                <input type="month" id="{{ $awalan }}_jadwal_tanam" name="jadwal_tanam"
                    value="{{ old('jadwal_tanam', $data['jadwal_tanam'] ?? '') }}"
                    class="{{ $kelasKontrol }}" />
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Rencana tanam dari berita acara. Realisasinya dicatat terpisah saat penanaman.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_sumber_dana" class="{{ $kelasLabel }}">Sumber Dana</label>
                <select id="{{ $awalan }}_sumber_dana" name="sumber_dana" class="{{ $kelasKontrol }}">
                    <option value="">Pilih sumber</option>
                    @foreach ($opsiSumberDana as $nilaiRef => $labelRef)
                        <option value="{{ $nilaiRef }}" @selected(old('sumber_dana', $data['sumber_dana'] ?? '') === $nilaiRef)>
                            {{ $nilaiRef }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    {{-- Bagian 2: penerima --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Penerima</h3>
        <div class="mt-3 space-y-4">
            <x-sim.pilih-cari nama="poktan_id" label="Kelompok Tani Penerima" :wajib="true"
                :awalan="$awalan" :opsi="$daftarPoktan" kunci="id_poktan"
                teks="nama" keterangan-opsi="satuan_permukiman"
                :terpilih="old('poktan_id', $data['poktan_id'] ?? null)"
                placeholder="Pilih kelompok tani"
                keterangan="Seluruh penyaluran tercatat atas nama kelompok. Pembagian kepada anggota diatur poktan sendiri."
                @change="poktanId = $event.target.value" />

            {{--
                SATUAN PERMUKIMAN TERBACA, BUKAN DIPILIH.

                Sebelumnya berupa dropdown terpisah, sehingga satu penyaluran
                dapat tercatat pada SP yang berbeda dari SP poktan penerimanya
                tanpa ada yang menegur. Nilainya memang sudah ditentukan begitu
                poktan dipilih, jadi menanyakannya lagi hanya membuka peluang
                salah isi.

                Nilai tetap dikirim lewat `<input type="hidden">`, sebab kolom
                `satuan_permukiman_id` tetap ada pada kamus data 8.4 dan dipakai
                penyaringan daftar.
            --}}
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
                placeholder="Contoh: sebagian pupuk disimpan untuk penanaman berikutnya."
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
            <x-sim.file-upload nama="foto" label="Foto Barang" :hanya-gambar="true"
                nama-dokumen="Foto Saprotan" :nama-pemilik="$data['nama'] ?? null"
                :berkas-saat-ini="$data['foto'] ?? null"
                keterangan="Dokumentasi barang saat diterima kelompok." />

            <x-sim.file-upload nama="dokumen_pendukung" label="Berita Acara Penyaluran"
                nama-dokumen="Dokumen Saprotan" :nama-pemilik="$data['nama'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                keterangan="Berita acara penyaluran atau tanda terima penerima bantuan." />
        </div>
    </section>
</div>
