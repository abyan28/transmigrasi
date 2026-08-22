{{--
    Isian hasil panen, dipakai bersama modal tambah dan modal ubah.

    SATU PILIHAN MENGISI DELAPAN ISIAN (dirombak 2026-08-22). Petugas memilih
    penanaman, lalu kelompok tani, jumlah anggota, luas lahan, volume benih,
    realisasi tanam, komoditas, satuan, dan bulan tanam terbaca dengan
    sendirinya. Yang benar-benar diketik hanya lima: Hasil Panen, Puso,
    Produktivitas, Harga Jual, dan Catatan.

    Dua identitas aritmetika yang dijaga, keduanya terbukti pada 96 baris
    laporan Polri MT.II 2025:

        Hasil Panen + Puso + Belum Dipanen = Realisasi Tanam
        Produksi                           = Hasil Panen x Produktivitas

    Belum Dipanen dan Produksi karena itu TIDAK diketik; keduanya terhitung
    dan ditampilkan terkunci.

    Satuan MENGIKUTI komoditas penanamannya, tidak dipilih bebas operator
    (agents/rules.md bagian 9 poin 3). Produktivitas ikut satuan itu pula,
    sehingga jagung memakai ton/ha dan cabai kg/ha. Satuan tidak dipaksa ton:
    cabai memang ditimbang kilogram, dan memaksanya membuat harga jual per ton
    menjadi angka yang tidak pernah dipakai siapa pun di lapangan.

    Produksi disimpan apa adanya tanpa konversi; konversi ke ton hanya terjadi
    saat rekap agar data asli lapangan tetap terjaga (bagian 8a poin 4 dan 5).

    Nama kolom mengikuti agents/data-dictionary.md bagian 9.3.
--}}
@php
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
    $kelasTerkunci = 'flex h-11 items-center rounded-lg bg-gray-50 px-4 text-theme-sm text-gray-600 dark:bg-white/5 dark:text-gray-400';

    // Peta satuan baku per komoditas, dibaca dari data master. Sebelumnya
    // ditulis tangan sebagai larik harfiah di berkas ini, sehingga komoditas
    // baru yang didata Admin tidak pernah punya satuan dan panennya tercatat
    // tanpa satuan sama sekali.
    $satuanKomoditas = collect(DummyData::komoditas())
        ->pluck('satuan', 'id_komoditas')
        ->all();

    // Penanaman sebagai sumber pilihan tunggal. Seluruh isian terkunci di
    // bawah dibaca dari baris yang dipilih di sini.
    //
    // Bulan tanam menggantikan label musim yang dicabut 2026-08-22. Ia yang
    // membedakan dua penanaman komoditas yang sama oleh kelompok yang sama,
    // sehingga tanpa itu keduanya tampil sebagai pilihan yang bunyinya
    // identik.
    $daftarPenanaman = [];
    $petaPenanaman = [];

    foreach (DummyData::penanaman() as $r) {
        $label = $r['komoditas'].' - '.$r['poktan']
            .' - '.\Illuminate\Support\Carbon::parse($r['periode_tanam'] . '-01')->translatedFormat('M Y');

        $daftarPenanaman[] = $r + ['label_tanam' => $label];

        $rekap = DummyData::rekapLahanPoktan($r['poktan_id']);

        $petaPenanaman[(string) $r['id_penanaman']] = [
            'poktan' => $r['poktan'],
            'poktan_id' => (string) $r['poktan_id'],
            'anggota' => $rekap['jumlah_anggota'],
            'luas_lahan' => $rekap['luas_total'],
            'volume_benih' => $r['volume_benih'],
            'realisasi_tanam' => (float) $r['realisasi_tanam'],
            'komoditas' => $r['komoditas'],
            'satuan' => $satuanKomoditas[$r['komoditas_id']] ?? '',
            'bulan_tanam' => \Illuminate\Support\Carbon::parse($r['periode_tanam'] . '-01')->translatedFormat('F Y'),
            'sp' => $r['satuan_permukiman'],
            // Sisa yang belum dipanen, sudah memperhitungkan panen sebelumnya
            // pada penanaman yang sama. Inilah batas atas isian di bawah.
            'belum_dipanen' => DummyData::belumDipanen($r['id_penanaman']),
        ];
    }
@endphp

<div class="space-y-6"
    x-data="{
        penanamanId: @js((string) old('penanaman_id', $data['penanaman_id'] ?? '')),
        panen: @js((string) old('realisasi_panen', $data['realisasi_panen'] ?? '')),
        puso: @js((string) old('puso', $data['puso'] ?? '')),
        produktivitas: @js((string) old('produktivitas', $data['produktivitas'] ?? '')),
        peta: @js($petaPenanaman),

        get tanam() { return this.peta[this.penanamanId] ?? null; },

        get angkaPanen() { return parseFloat(this.panen) || 0; },
        get angkaPuso() { return parseFloat(this.puso) || 0; },

        /*
            Belum Dipanen: sisa dari identitas pertama.

            Dihitung dari `belum_dipanen` milik penanamannya, BUKAN dari
            realisasi tanam mentah. Keduanya berbeda begitu penanaman itu
            sudah pernah dipanen sebagian: memakai realisasi tanam akan
            menawarkan lahan yang sebenarnya sudah habis dipanen.
        */
        get belumDipanen() {
            if (! this.tanam) {
                return null;
            }

            return Math.max(0, Math.round((this.tanam.belum_dipanen - this.angkaPanen - this.angkaPuso) * 100) / 100);
        },

        get melebihiLahan() {
            if (! this.tanam) {
                return false;
            }

            return (this.angkaPanen + this.angkaPuso) > this.tanam.belum_dipanen + 0.001;
        },

        /* Produksi: identitas kedua. Terkunci, tidak pernah diketik. */
        get produksi() {
            const p = parseFloat(this.produktivitas) || 0;

            return Math.round(this.angkaPanen * p * 1000) / 1000;
        },

        angka(nilai, desimal = 2) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: desimal }).format(nilai ?? 0);
        },
    }">

    {{-- Bagian 1: penanaman yang dipanen --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Penanaman yang Dipanen</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                {{--
                    SATU-SATUNYA PILIHAN pada bagian ini. Delapan isian di
                    bawah terbaca dari baris yang dipilih di sini.

                    Isian ini sempat memuat tiga label musim yang ditulis
                    langsung sebagai larik harfiah, sementara namanya
                    `penanaman_id`. Dua hal keliru sekaligus: nilai yang
                    terkirim berupa teks label, bukan id; dan daftarnya tidak
                    pernah bertambah ketika penanaman baru didata, sehingga
                    panen berikutnya tidak dapat dicatat sama sekali.
                --}}
                <x-sim.pilih-cari nama="penanaman_id" label="Penanaman" :wajib="true"
                    :awalan="$awalan" :opsi="$daftarPenanaman" kunci="id_penanaman"
                    teks="label_tanam" keterangan-opsi="satuan_permukiman"
                    :terpilih="old('penanaman_id', $data['penanaman_id'] ?? null)"
                    placeholder="Pilih penanaman"
                    keterangan="Menentukan kelompok tani, komoditas, satuan, dan luas yang ditanam sekaligus."
                    @change="penanamanId = $event.target.value" />
            </div>

            {{--
                DELAPAN ISIAN TERKUNCI, seluruhnya terbaca dari penanamannya.

                Tidak satu pun disimpan pada tabel hasil panen: `poktan_id`
                dan `komoditas` memang disalin agar rekap tidak perlu menyusuri
                relasi, tetapi sisanya dihitung ulang setiap kali. Membiarkan
                petugas mengetiknya berarti mengundang angka yang berbeda dari
                sumbernya tanpa ada yang menegur.
            --}}
            <div>
                <span class="{{ $kelasLabel }}">Kelompok Tani</span>
                <p class="{{ $kelasTerkunci }}">
                    <span x-show="tanam" x-cloak x-text="tanam?.poktan"></span>
                    <span x-show="! tanam" class="text-gray-400 dark:text-white/30">
                        Terisi setelah penanaman dipilih
                    </span>
                </p>
            </div>

            <div>
                <span class="{{ $kelasLabel }}">Jumlah Anggota</span>
                <p class="{{ $kelasTerkunci }}">
                    <span x-show="tanam" x-cloak>
                        <span x-text="tanam?.anggota"></span> orang
                    </span>
                    <span x-show="! tanam" class="text-gray-400 dark:text-white/30">&mdash;</span>
                </p>
            </div>

            <div>
                <span class="{{ $kelasLabel }}">Luas Lahan</span>
                <p class="{{ $kelasTerkunci }}">
                    <span x-show="tanam" x-cloak>
                        <span x-text="angka(tanam?.luas_lahan)"></span> ha
                    </span>
                    <span x-show="! tanam" class="text-gray-400 dark:text-white/30">&mdash;</span>
                </p>
            </div>

            <div>
                <span class="{{ $kelasLabel }}">Volume Benih</span>
                <p class="{{ $kelasTerkunci }}">
                    <span x-show="tanam && tanam.volume_benih" x-cloak>
                        <span x-text="angka(tanam?.volume_benih)"></span> kg
                    </span>
                    {{-- Boleh kosong: bibit swadaya tidak melalui saprotan. --}}
                    <span x-show="tanam && ! tanam.volume_benih" x-cloak class="text-gray-400 dark:text-white/30">
                        Tanpa benih tercatat
                    </span>
                    <span x-show="! tanam" class="text-gray-400 dark:text-white/30">&mdash;</span>
                </p>
            </div>

            <div>
                <span class="{{ $kelasLabel }}">Realisasi Tanam</span>
                <p class="{{ $kelasTerkunci }}">
                    <span x-show="tanam" x-cloak>
                        <span x-text="angka(tanam?.realisasi_tanam)"></span> ha
                    </span>
                    <span x-show="! tanam" class="text-gray-400 dark:text-white/30">&mdash;</span>
                </p>
            </div>

            <div>
                <span class="{{ $kelasLabel }}">Penanaman (Bulan)</span>
                <p class="{{ $kelasTerkunci }}">
                    <span x-show="tanam" x-cloak x-text="tanam?.bulan_tanam"></span>
                    <span x-show="! tanam" class="text-gray-400 dark:text-white/30">&mdash;</span>
                </p>
            </div>

            {{--
                Komoditas dan satuan ikut disalin ke tabel panen, sehingga
                keduanya dikirim lewat isian tersembunyi. Satuan ditetapkan
                data master komoditas, bukan dipilih operator, agar rekap
                lintas komoditas tetap sepadan.
            --}}
            <div class="sm:col-span-2">
                <span class="{{ $kelasLabel }}">Komoditas dan Satuan Panen</span>
                <p class="{{ $kelasTerkunci }}">
                    <span x-show="tanam" x-cloak>
                        <span x-text="tanam?.komoditas"></span>
                        <span class="text-gray-400 dark:text-white/30">&middot;</span>
                        dicatat dalam <span x-text="tanam?.satuan"></span>
                    </span>
                    <span x-show="! tanam" class="text-gray-400 dark:text-white/30">
                        Mengikuti penanaman terpilih
                    </span>
                </p>
                <input type="hidden" name="satuan_id" :value="tanam?.satuan ?? ''" />
                <input type="hidden" name="poktan_id" :value="tanam?.poktan_id ?? ''" />
            </div>
        </div>
    </section>

    {{-- Bagian 2: hasil panen --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Hasil Panen</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                {{--
                    PERIODE PANEN, bukan tanggal penuh.

                    Panen satu hamparan berlangsung berhari-hari, sehingga
                    menuntut satu tanggal pasti membuat petugas menebak.
                    Bulan sudah cukup halus untuk seluruh rekap yang ada.
                --}}
                <label for="{{ $awalan }}_periode_panen" class="{{ $kelasLabel }}">
                    Periode Panen<span class="text-error-500">*</span>
                </label>
                <input type="month" id="{{ $awalan }}_periode_panen" name="periode_panen" required
                    value="{{ old('periode_panen', $data['periode_panen'] ?? '') }}" max="{{ date('Y-m') }}"
                    class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_realisasi_panen" class="{{ $kelasLabel }}">
                    Hasil Panen<span class="text-error-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_realisasi_panen" name="realisasi_panen" required
                        x-model="panen" min="0" step="0.01" :max="tanam?.belum_dipanen"
                        placeholder="3.00" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
                </div>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Luas yang benar-benar dipanen, bukan volume hasilnya.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_puso" class="{{ $kelasLabel }}">Puso</label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_puso" name="puso"
                        x-model="puso" min="0" step="0.01" :max="tanam?.belum_dipanen"
                        placeholder="0.00" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
                </div>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Luas yang gagal panen. Ikut mengurangi sisa yang belum dipanen.
                </p>
            </div>

            {{--
                BELUM DIPANEN: terkunci, sisa dari identitas
                Hasil Panen + Puso + Belum Dipanen = Realisasi Tanam.

                Menyimpannya sebagai isian berarti tiga angka yang saling
                menentukan diketik terpisah, dan ketiganya dapat berbeda
                tanpa ada yang menegur.
            --}}
            <div>
                <span class="{{ $kelasLabel }}">Belum Dipanen</span>
                <p class="{{ $kelasTerkunci }}">
                    <span x-show="tanam" x-cloak>
                        <span x-text="angka(belumDipanen)"></span> ha
                    </span>
                    <span x-show="! tanam" class="text-gray-400 dark:text-white/30">&mdash;</span>
                </p>
                <p x-show="melebihiLahan" x-cloak class="mt-1.5 text-theme-xs text-error-500">
                    Hasil panen dan puso melebihi luas yang belum dipanen, yaitu
                    <span x-text="angka(tanam?.belum_dipanen)"></span> ha.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_produktivitas" class="{{ $kelasLabel }}">
                    Produktivitas<span class="text-error-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_produktivitas" name="produktivitas" required
                        x-model="produktivitas" min="0" step="0.001"
                        placeholder="2.800" class="{{ $kelasKontrol }} tabular-nums pr-20" />
                    <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                        <span x-text="tanam?.satuan ?? ''"></span>/ha
                    </span>
                </div>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Satuannya mengikuti komoditas, bukan selalu ton.
                </p>
            </div>

            {{--
                PRODUKSI: terkunci, hasil Hasil Panen x Produktivitas.

                Tetap dikirim lewat isian tersembunyi sebab kolomnya memang
                disimpan: ia angka yang dilaporkan ke dinas, dan pembulatan
                hasil perkalian dapat berbeda tipis dari angka yang
                benar-benar ditimbang.
            --}}
            <div>
                <span class="{{ $kelasLabel }}">Produksi</span>
                <p class="{{ $kelasTerkunci }} tabular-nums">
                    <span x-show="tanam" x-cloak>
                        <span x-text="angka(produksi, 3)"></span>
                        <span class="ml-1" x-text="tanam?.satuan"></span>
                    </span>
                    <span x-show="! tanam" class="text-gray-400 dark:text-white/30">&mdash;</span>
                </p>
                <input type="hidden" name="produksi" :value="produksi" />
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Hasil panen dikali produktivitas.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_harga_jual" class="{{ $kelasLabel }}">Harga Jual per Satuan</label>
                <div class="relative">
                    <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                        Rp
                    </span>
                    <input type="number" id="{{ $awalan }}_harga_jual" name="harga_jual"
                        value="{{ old('harga_jual', $data['harga_jual'] ?? '') }}" min="0" step="100"
                        placeholder="0" class="{{ $kelasKontrol }} tabular-nums pl-10" />
                </div>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Per satuan baku komoditas, bukan per ton.
                </p>
            </div>



            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_keterangan" class="{{ $kelasLabel }}">Catatan</label>
                <textarea id="{{ $awalan }}_keterangan" name="keterangan" rows="2" maxlength="1000"
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
            </div>
        </div>
    </section>

    {{-- Bagian 3: dokumentasi --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Dokumentasi</h3>
        <div class="mt-3">
            {{--
                TIDAK dibatasi gambar saja sejak 2026-08-22. Berita acara panen
                lazimnya PDF hasil pindaian, sedangkan foto hamparan berupa
                gambar. Membatasinya pada salah satu memaksa petugas menyimpan
                yang lain di luar sistem.
            --}}
            <x-sim.file-upload nama="dokumen_pendukung" label="Dokumen atau Foto Panen"
                nama-dokumen="Dokumen Panen" :nama-pemilik="$data['poktan'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                keterangan="Berita acara panen, foto hamparan, atau bukti timbangan." />
        </div>
    </section>
</div>
