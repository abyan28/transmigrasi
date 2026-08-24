{{--
    Isian penanaman.

    BERPUSAT PADA POKTAN, bukan lahan perorangan (dirombak 2026-08-22).
    Lokasi produksi terbaca lewat rantai `penanaman -> poktan ->
    satuan_permukiman`, sebab poktan sudah menyimpan SP-nya sendiri.

    Alur pengisiannya menurun, tiap langkah menyempitkan langkah berikutnya:

        Kelompok Tani  -> Jumlah Anggota & Luas Lahan terisi sendiri
        Komoditas      -> menyaring benih yang boleh dipakai
        Benih          -> Volume Benih terbatas sisanya
        Realisasi Tanam-> Belum Ditanam terhitung sendiri

    Tiga isian TERKUNCI dan tidak pernah diketik: Jumlah Anggota, Luas Lahan,
    dan Belum Ditanam. Ketiganya turunan dari data yang sudah ada, dan
    membiarkan petugas mengetiknya berarti mengundang angka yang berbeda dari
    sumbernya tanpa ada yang menegur.

    Nama kolom mengikuti agents/data-dictionary.md bagian 9.2.
--}}
@php
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasTerkunci = 'flex h-11 items-center rounded-lg bg-gray-50 px-4 text-theme-sm text-gray-600 dark:bg-white/5 dark:text-gray-400';

    $daftarPoktan = DummyData::poktan();
    $daftarKomoditas = DummyData::komoditas();

    // Peta poktan ke kekuatannya: cacah anggota aktif, luas lahan, dan sisa
    // lahan yang belum ditanami. Disusun sekali di sini, bukan dihitung ulang
    // setiap kali pilihan berubah.
    $petaPoktan = [];
    foreach ($daftarPoktan as $p) {
        $rekap = DummyData::rekapLahanPoktan($p['id_poktan']);

        $petaPoktan[(string) $p['id_poktan']] = [
            'sp_id' => (string) $p['satuan_permukiman_id'],
            'sp_nama' => $p['satuan_permukiman'],
            'anggota' => $rekap['jumlah_anggota'],
            'luas' => $rekap['luas_total'],
            'tersedia' => DummyData::lahanTersedia($p['id_poktan']),
        ];
    }

    // Seluruh benih yang masih bersisa, dikelompokkan agar Alpine dapat
    // menyaringnya tanpa permintaan tambahan ke peladen. Benih yang stoknya
    // habis TIDAK ada di sini sama sekali (kamus data 8.4).
    $petaBenih = [];
    foreach (DummyData::benihTersedia() as $b) {
        $petaBenih[] = [
            'id' => (string) $b['id_saprotan'],
            'poktan_id' => (string) $b['poktan_id'],
            'komoditas_id' => (string) $b['komoditas_id'],
            'label' => $b['label_benih'],
            'sisa' => $b['sisa_benih'],
            'satuan' => $b['satuan'],
        ];
    }
@endphp

<div class="space-y-6"
    x-data="{
        poktanId: @js((string) old('poktan_id', $data['poktan_id'] ?? '')),
        komoditasId: @js((string) old('komoditas_id', $data['komoditas_id'] ?? '')),
        saprotanId: @js((string) old('saprotan_id', $data['saprotan_id'] ?? '')),
        realisasi: @js((string) old('realisasi_tanam', $data['realisasi_tanam'] ?? '')),
        petaPoktan: @js($petaPoktan),
        semuaBenih: @js($petaBenih),

        get poktan() { return this.petaPoktan[this.poktanId] ?? null; },

        /*
            Benih yang boleh dipakai penanaman ini.

            Disaring TIGA lapis sekaligus: milik poktan yang dipilih, untuk
            komoditas yang dipilih, dan stoknya masih ada. Lapis ketiga sudah
            terjadi di PHP, sehingga daftar ini tidak pernah memuat benih
            habis sejak awal.
        */
        get benihTersedia() {
            if (! this.poktanId || ! this.komoditasId) {
                return [];
            }

            return this.semuaBenih.filter(
                (b) => b.poktan_id === this.poktanId && b.komoditas_id === this.komoditasId
            );
        },

        get benihTerpilih() {
            return this.benihTersedia.find((b) => b.id === this.saprotanId) ?? null;
        },

        /*
            Sisa lahan yang belum ditanami setelah penanaman ini dicatat.

            Dihitung dari lahan yang TERSEDIA, bukan dari luas total: lahan
            yang sedang ditanami penanaman lain dan belum dipanen memang tidak
            dapat ditanami lagi.
        */
        get belumDitanam() {
            if (! this.poktan) {
                return null;
            }

            const dipakai = parseFloat(this.realisasi) || 0;

            return Math.max(0, Math.round((this.poktan.tersedia - dipakai) * 100) / 100);
        },

        get melebihiLahan() {
            if (! this.poktan) {
                return false;
            }

            return (parseFloat(this.realisasi) || 0) > this.poktan.tersedia;
        },

        angka(nilai) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(nilai);
        },
    }"
    x-effect="
        /* Benih dilepas ketika poktan atau komoditasnya berganti, sebab
           pilihan lama hampir pasti bukan milik poktan yang baru. Tanpa ini
           id lama tetap terkirim meski tidak lagi ada di daftar. */
        if (saprotanId && ! benihTersedia.some((b) => b.id === saprotanId)) {
            saprotanId = '';
        }
    ">

    <div class="grid gap-4 sm:grid-cols-2">
        {{-- Langkah 1: kelompok tani, penentu segalanya --}}
        <div class="sm:col-span-2">
            <x-sim.pilih-cari nama="poktan_id" label="Kelompok Tani" :wajib="true"
                :awalan="$awalan" :opsi="$daftarPoktan" kunci="id_poktan"
                teks="nama" keterangan-opsi="satuan_permukiman"
                :terpilih="old('poktan_id', $data['poktan_id'] ?? null)"
                placeholder="Pilih kelompok tani"
                keterangan="Menentukan lokasi, jumlah anggota, luas lahan, dan benih yang boleh dipakai."
                @change="poktanId = $event.target.value" />
        </div>

        {{--
            Dua isian TERKUNCI, terbaca dari poktan.

            Keduanya dikirim lewat isian tersembunyi agar peladen tetap
            menerimanya, tetapi TIDAK dapat diketik. Kolomnya sendiri tidak
            disimpan pada tabel penanaman: nilainya selalu dihitung ulang dari
            keanggotaan dan lahan terbaru (kamus data 9.2).
        --}}
        <div>
            <span class="{{ $kelasLabel }}">Jumlah Anggota</span>
            <p class="{{ $kelasTerkunci }}">
                <span x-show="poktan" x-cloak>
                    <span x-text="poktan?.anggota"></span> orang
                </span>
                <span x-show="! poktan" class="text-gray-400 dark:text-white/30">
                    Terisi setelah kelompok tani dipilih
                </span>
            </p>
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Dihitung dari anggota berstatus aktif beserta ketuanya.
            </p>
        </div>

        <div>
            <span class="{{ $kelasLabel }}">Luas Lahan</span>
            <p class="{{ $kelasTerkunci }}">
                <span x-show="poktan" x-cloak>
                    <span x-text="angka(poktan?.luas)"></span> ha
                </span>
                <span x-show="! poktan" class="text-gray-400 dark:text-white/30">
                    Terisi setelah kelompok tani dipilih
                </span>
            </p>
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Akumulasi lahan ketua dan seluruh anggota aktif.
            </p>
        </div>

        {{-- Langkah 2: komoditas, penyaring benih --}}
        <div>
            <label for="{{ $awalan }}_komoditas_id" class="{{ $kelasLabel }}">
                Komoditas<span class="text-error-500">*</span>
            </label>
            <select id="{{ $awalan }}_komoditas_id" name="komoditas_id" required
                x-model="komoditasId" class="{{ $kelasKontrol }}">
                <option value="">Pilih komoditas</option>
                @foreach ($daftarKomoditas as $k)
                    <option value="{{ $k['id_komoditas'] }}"
                        @selected((string) old('komoditas_id', $data['komoditas_id'] ?? '') === (string) $k['id_komoditas'])>
                        {{ $k['nama'] }} (satuan panen {{ $k['satuan'] }})
                    </option>
                @endforeach
            </select>
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Satuan panen mengikuti komoditas dan tidak dapat diubah saat mencatat hasil.
            </p>
        </div>

        {{--
            Langkah 3: benih, TERSARING dan WAJIB.

            DIWAJIBKAN 2026-08-24. Sebelumnya boleh kosong dengan alasan
            "bibit swadaya tidak melalui modul saprotan", dan alasan itu
            KELIRU: enum sumber perolehan sudah memuat `Swadaya` sejak awal,
            dan satu baris data contoh sudah memakainya. Yang kurang hanyalah
            keseragaman pemakaian.

            Mewajibkannya membuat benih swadaya ikut punya STOK. Tanpa itu ia
            seolah tak terbatas: poktan dapat mencatat penanaman sebanyak apa
            pun tanpa ada yang menegur.

            Ketika belum ada benih yang dapat dipakai, yang tampil adalah
            KETERANGAN BESERTA TAUTAN ke form saprotan - bukan dropdown kosong
            yang membingungkan, dan bukan pula jalan buntu. Dropdown yang tidak
            dapat dipilih apa pun adalah kontrol mati (ui-spec.md R-26).
        --}}
        <div>
            <label for="{{ $awalan }}_saprotan_id" class="{{ $kelasLabel }}">
                Benih Dipakai<span class="text-error-500">*</span>
            </label>

            <select id="{{ $awalan }}_saprotan_id" name="saprotan_id" x-model="saprotanId"
                x-show="benihTersedia.length > 0" x-cloak
                :required="benihTersedia.length > 0" class="{{ $kelasKontrol }}">
                <option value="">Pilih benih</option>
                <template x-for="b in benihTersedia" :key="b.id">
                    <option :value="b.id" x-text="b.label"></option>
                </template>
            </select>

            {{-- Belum memilih poktan atau komoditas: belum ada yang dapat disaring --}}
            <p x-show="benihTersedia.length === 0 && (! poktanId || ! komoditasId)" x-cloak
                class="{{ $kelasTerkunci }} text-gray-400 dark:text-white/30">
                Pilih kelompok tani dan komoditas lebih dulu
            </p>

            {{--
                Sudah memilih keduanya tetapi benihnya tidak ada. Inilah
                keadaan yang dahulu diselesaikan dengan "Tanpa benih tercatat".
                Kini diselesaikan dengan menuntun petugas mendaftarkannya,
                sebab benih apa pun - bantuan maupun swadaya - memang milik
                poktan dan layak tercatat.
            --}}
            <div x-show="benihTersedia.length === 0 && poktanId && komoditasId" x-cloak
                class="rounded-lg border border-gray-300 bg-gray-50 p-3.5 dark:border-gray-700 dark:bg-white/[0.03]">
                <p class="text-theme-sm text-gray-700 dark:text-gray-300">
                    Belum ada benih terdaftar untuk komoditas ini pada kelompok tersebut.
                </p>
                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                    Benih swadaya pun didaftarkan lebih dulu sebagai penyaluran bersumber
                    <strong>Swadaya</strong>, agar stoknya ikut terhitung.
                </p>
                <a href="{{ route('saprotan.index') }}"
                    class="mt-2.5 inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium text-gray-700 transition hover:bg-white focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                    Daftarkan Benih di Saprotan
                </a>
            </div>

            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Hanya benih milik kelompok ini yang stoknya masih ada. Benih yang habis perlu didata ulang
                sebagai penyaluran baru.
            </p>
        </div>

        {{--
            Volume benih, dibatasi sisa stok benih yang dipilih.

            `:max` bukan hiasan: tanpa itu 150 kg benih dapat dipakai untuk
            penanaman senilai 400 kg dan tidak ada yang menegur.
        --}}
        <div x-show="benihTerpilih" x-cloak x-transition>
            <label for="{{ $awalan }}_volume_benih" class="{{ $kelasLabel }}">
                Volume Benih<span class="text-error-500">*</span>
            </label>
            <div class="relative">
                <input type="number" id="{{ $awalan }}_volume_benih" name="volume_benih"
                    value="{{ old('volume_benih', $data['volume_benih'] ?? '') }}"
                    min="0.01" step="0.01" :max="benihTerpilih?.sisa"
                    :required="!! benihTerpilih" :disabled="! benihTerpilih"
                    placeholder="45" class="{{ $kelasKontrol }} tabular-nums pr-16" />
                <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400"
                    x-text="benihTerpilih?.satuan"></span>
            </div>
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Tersisa <span x-text="angka(benihTerpilih?.sisa)"></span>
                <span x-text="benihTerpilih?.satuan"></span>.
            </p>
        </div>
        {{-- Langkah 4: realisasi tanam --}}
        <div>
            <label for="{{ $awalan }}_realisasi_tanam" class="{{ $kelasLabel }}">
                Realisasi Tanam<span class="text-error-500">*</span>
            </label>
            <div class="relative">
                <input type="number" id="{{ $awalan }}_realisasi_tanam" name="realisasi_tanam" required
                    x-model="realisasi" min="0.01" step="0.01" :max="poktan?.tersedia"
                    placeholder="3.00" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                <span
                    class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
            </div>
            <p x-show="melebihiLahan" x-cloak class="mt-1.5 text-theme-xs text-error-500">
                Melebihi lahan yang belum ditanami, yaitu <span x-text="angka(poktan?.tersedia)"></span> ha.
            </p>
            <p x-show="! melebihiLahan" class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Luas yang benar-benar ditanami, boleh lebih kecil dari luas lahan.
            </p>
        </div>

        {{--
            Belum Ditanam: TERKUNCI, sisa lahan setelah penanaman ini.

            Dihitung dari lahan yang TERSEDIA, bukan luas total, sebab lahan
            yang sedang ditanami penanaman lain dan belum dipanen memang tidak
            dapat ditanami lagi. Lahan kembali tersedia setelah panennya
            tercatat, berbeda dari benih yang habis selamanya.
        --}}
        <div>
            <span class="{{ $kelasLabel }}">Belum Ditanam</span>
            <p class="{{ $kelasTerkunci }}">
                <span x-show="poktan" x-cloak>
                    <span x-text="angka(belumDitanam)"></span> ha
                </span>
                <span x-show="! poktan" class="text-gray-400 dark:text-white/30">
                    Terisi setelah kelompok tani dipilih
                </span>
            </p>
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Lahan kembali tersedia setelah panennya dicatat.
            </p>
        </div>

        <div>
            {{--
                PERIODE TANAM, bukan tanggal penuh (diubah 2026-08-22).

                Penanaman satu hamparan berlangsung berhari-hari, sehingga
                menuntut satu tanggal pasti membuat petugas menebak - dan
                tebakan itu lalu dipakai sebagai dasar rekap seolah-olah
                data terukur. Bulan sudah cukup halus untuk seluruh rekap
                yang ada.

                WAJIB sejak pencabutan musim tanam: ia satu-satunya sumbu
                waktu sekaligus pembeda antara dua penanaman komoditas yang
                sama oleh kelompok yang sama (kamus data 9.2).
            --}}
            <label for="{{ $awalan }}_periode_tanam" class="{{ $kelasLabel }}">
                Periode Tanam<span class="text-error-500">*</span>
            </label>
            <input type="month" id="{{ $awalan }}_periode_tanam" name="periode_tanam" required
                value="{{ old('periode_tanam', $data['periode_tanam'] ?? '') }}" max="{{ date('Y-m') }}"
                class="{{ $kelasKontrol }}" />
        </div>

        <div class="sm:col-span-2">
            <label for="{{ $awalan }}_keterangan_penanaman" class="{{ $kelasLabel }}">Catatan</label>
            <textarea id="{{ $awalan }}_keterangan_penanaman" name="keterangan" rows="2" maxlength="255"
                placeholder="Contoh: penanaman bertahap, sisa lahan menyusul bulan depan."
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
        </div>

        {{--
            Dokumen pendukung, ditambahkan 2026-08-22 atas permintaan pemilik
            proyek. Berita acara tanam dan foto hamparan adalah bukti yang
            paling sering diminta saat pemeriksaan program bantuan, dan
            sebelumnya tidak dapat diunggah ke mana pun.

            TIDAK dibatasi gambar saja: berita acara lazimnya PDF hasil
            pindaian, sedangkan foto hamparan berupa gambar. Membatasinya
            pada salah satu memaksa petugas menyimpan yang lain di luar
            sistem.
        --}}
        <div class="sm:col-span-2">
            <x-sim.file-upload nama="dokumen_pendukung" label="Dokumen atau Foto Penanaman"
                nama-dokumen="Dokumen Penanaman" :nama-pemilik="$data['poktan'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                keterangan="Berita acara tanam, foto hamparan, atau bukti penyaluran benih." />
        </div>
    </div>
</div>
