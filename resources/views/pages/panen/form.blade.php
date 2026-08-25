{{--
    Isian hasil panen, dipakai bersama modal tambah dan modal ubah.

    SATU PILIHAN MENGISI DELAPAN ISIAN (dirombak 2026-08-22). Petugas memilih
    penanaman, lalu kelompok tani, jumlah anggota, luas lahan, volume benih,
    realisasi tanam, komoditas, satuan, dan bulan tanam terbaca dengan
    sendirinya.

    SATU PENANAMAN HANYA BOLEH SATU PANEN (ditetapkan 2026-08-24). Luas yang
    ditanam wajib tertutup habis pada satu pencatatan:

        Realisasi Panen + Puso = Realisasi Tanam
        Produksi               = Realisasi Panen x Produktivitas

    Identitas pertama dahulu bersuku tiga, dengan Belum Dipanen sebagai
    selisihnya. Suku itu DICABUT bersama seluruh konsep panen bertahap, atas
    keterangan pemilik proyek bahwa satu penanaman tidak dapat dipanen
    1,5 ha lalu menyusul 0,5 ha. Bentuk dua suku ini pula yang dipakai laporan
    lapangan, yang kolomnya hanya Realisasi Tanam, Realisasi Panen, dan Puso.

    Akibatnya REALISASI PANEN DAN PUSO SALING MENGISI: mengetik salah satunya
    menghitung yang lain, sebab jumlah keduanya sudah tertentu. Petugas yang
    tahu 0,25 ha gagal tidak perlu menghitung sendiri sisanya, dan angka yang
    tidak menutup luas menjadi mustahil terjadi.

    GAGAL TOTAL adalah keadaan yang sah: realisasi panen 0 ha dengan puso menutup
    seluruh luas. Pada keadaan itu produktivitas TIDAK diwajibkan, sebab tidak
    ada yang ditimbang dan memaksa angka berarti mengarang hasil yang tidak
    pernah ada.

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

    /*
     * Simbol satuan, dibaca dari data master. Dipakai pada sufiks isian yang
     * ruangnya sempit: nama penuh "Kilogram/ha" menabrak tombol naik-turun
     * bawaan input number.
     *
     * Tidak disingkat sendiri lewat `substr` atau daftar tulis tangan, sebab
     * satuan baru yang didata Admin tidak akan pernah punya singkatan.
     */
    $simbolSatuan = collect(DummyData::satuan())
        ->mapWithKeys(fn ($s) => [$s['nama'] => $s['simbol']])
        ->all();

    // Penanaman sebagai sumber pilihan tunggal. Seluruh isian terkunci di
    // bawah dibaca dari baris yang dipilih di sini.
    //
    // Bulan tanam menggantikan label musim yang dicabut 2026-08-22. Ia yang
    // membedakan dua penanaman komoditas yang sama oleh kelompok yang sama,
    // sehingga tanpa itu keduanya tampil sebagai pilihan yang bunyinya
    // identik.
    // HANYA PENANAMAN YANG BELUM DIPANEN yang ditawarkan (sejak 2026-08-24).
    // Satu penanaman hanya boleh satu panen, sehingga menawarkan yang sudah
    // dipanen berarti mengundang baris kedua yang tidak sah - dan luasnya
    // akan terhitung dua kali pada rekap.
    //
    // Baris yang sedang DISUNTING tetap ditawarkan; tanpa itu, membuka modal
    // ubah akan menemukan pilihannya sendiri lenyap dari daftar.
    $penanamanTerpilih = $data['penanaman_id'] ?? null;

    $daftarPenanaman = [];
    $petaPenanaman = [];

    foreach (DummyData::penanaman() as $r) {
        $belumDipanen = DummyData::statusPanen($r['id_penanaman']) === \App\Enums\StatusPanen::BelumDipanen;

        if (! $belumDipanen && (string) $r['id_penanaman'] !== (string) $penanamanTerpilih) {
            continue;
        }

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
            'simbol' => $simbolSatuan[$satuanKomoditas[$r['komoditas_id']] ?? ''] ?? '',
            'bulan_tanam' => \Illuminate\Support\Carbon::parse($r['periode_tanam'] . '-01')->translatedFormat('F Y'),
            'sp' => $r['satuan_permukiman'],
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

        /* Luas yang wajib tertutup habis oleh panen dan puso. */
        get luasTanam() {
            return this.tanam ? this.tanam.realisasi_tanam : 0;
        },

        /*
            SALING MENGISI. Jumlah panen dan puso sudah tertentu, yaitu seluruh
            luas yang ditanam, sehingga mengetik salah satunya menentukan yang
            lain. Petugas yang tahu 0,25 ha gagal tidak perlu menghitung
            sisanya sendiri, dan angka yang tidak menutup luas menjadi mustahil.

            Dibulatkan dua desimal karena pengurangan pecahan biner kerap
            menyisakan ekor panjang: 2 - 1.2 menghasilkan 0.7999999999999998,
            dan angka itu akan tampil apa adanya pada isian.
        */
        bulat(nilai) {
            return Math.max(0, Math.round(nilai * 100) / 100);
        },

        isiDariPanen() {
            if (this.tanam) {
                this.puso = String(this.bulat(this.luasTanam - this.angkaPanen));
            }
        },

        isiDariPuso() {
            if (this.tanam) {
                this.panen = String(this.bulat(this.luasTanam - this.angkaPuso));
            }
        },

        /*
            Begitu penanaman dipilih, seluruh luasnya langsung dianggap
            dipanen. Puso menjadi nol, dan petugas tinggal mengubahnya bila
            memang ada yang gagal - keadaan yang lebih jarang daripada panen
            yang mulus.
        */
        pilihPenanaman(nilai) {
            this.penanamanId = nilai;

            if (this.tanam) {
                this.panen = String(this.luasTanam);
                this.puso = '0';
            }
        },

        /*
            GAGAL TOTAL: seluruh luas puso, tidak ada yang dipanen.

            Produktivitas tidak diwajibkan pada keadaan ini, sebab tidak ada
            yang ditimbang. Memaksanya berarti menuntut petugas mengarang
            hasil yang tidak pernah ada.
        */
        get gagalTotal() {
            return this.tanam !== null && this.angkaPanen === 0 && this.angkaPuso > 0;
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
                    keterangan="Hanya penanaman yang belum dipanen. Menentukan kelompok tani, komoditas, satuan, dan luas sekaligus."
                    @change="pilihPenanaman($event.target.value)" />
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

            {{--
                REALISASI PANEN dan PUSO SALING MENGISI. Jumlah keduanya sudah
                tertentu, yaitu seluruh luas yang ditanam, sehingga mengetik
                salah satunya menentukan yang lain.

                Tanpa ini petugas harus menghitung sendiri, dan angka yang
                tidak menutup luas dapat tersimpan tanpa ada yang menegur -
                persis yang dahulu melahirkan sisa menggantung berbulan-bulan.
            --}}
            {{--
                "Realisasi Panen", bukan "Hasil Panen" (diganti 2026-08-24).

                Sejajar dengan REALISASI TANAM tepat di atasnya, dan sama
                persis dengan kolom laporan lapangan yang berbunyi Realisasi
                Tanam, Realisasi Panen, Puso. Nama isiannya memang sudah
                `realisasi_panen` sejak awal; hanya labelnya yang tertinggal.
            --}}
            <div>
                <label for="{{ $awalan }}_realisasi_panen" class="{{ $kelasLabel }}">
                    Realisasi Panen<span class="text-error-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_realisasi_panen" name="realisasi_panen" required
                        x-model="panen" @input="isiDariPanen()" min="0" step="0.01" :max="luasTanam"
                        placeholder="3.00" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
                </div>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Luas yang benar-benar dipanen, bukan volume hasilnya.
                    Isi <strong>0</strong> bila seluruh hamparan gagal.
                </p>
            </div>

            {{--
                PUSO kini WAJIB, bukan lagi opsional. Dialah yang menerangkan
                mengapa luas panen kurang dari luas tanam; tanpa isian ini,
                selisihnya menggantung tanpa penjelasan.

                Boleh bernilai nol, dan nol itu bermakna: tidak ada yang gagal.
            --}}
            <div>
                <label for="{{ $awalan }}_puso" class="{{ $kelasLabel }}">
                    Puso<span class="text-error-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_puso" name="puso" required
                        x-model="puso" @input="isiDariPuso()" min="0" step="0.01" :max="luasTanam"
                        placeholder="0.00" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
                </div>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Luas yang gagal panen. Terisi sendiri dari sisa luas yang ditanam.
                </p>
            </div>

            {{--
                Penegasan bahwa luasnya sudah tertutup habis. Bukan isian,
                hanya pengingat bahwa kedua angka di atas menutup seluruh luas
                yang ditanam - identitas yang dahulu memerlukan suku ketiga.
            --}}
            <div class="sm:col-span-2">
                <p x-show="tanam" x-cloak
                    class="rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                    <span x-text="angka(angkaPanen)"></span> ha dipanen
                    + <span x-text="angka(angkaPuso)"></span> ha puso
                    = <span x-text="angka(luasTanam)"></span> ha yang ditanam.
                    <span x-show="gagalTotal" class="font-medium text-error-500">
                        Seluruh hamparan gagal panen.
                    </span>
                </p>
            </div>

            {{--
                PRODUKTIVITAS tidak diwajibkan pada gagal total, sebab tidak
                ada yang ditimbang. Memaksanya berarti menuntut petugas
                mengarang hasil yang tidak pernah ada.

                `:required` DAN `:disabled` dipakai bersama: isian wajib yang
                sedang tidak berlaku akan menahan pengiriman sambil menunjuk
                elemen yang tampak sehat, sehingga form seolah menolak diam-diam.
                Jebakan yang sama sudah tercatat pada isian komoditas saprotan.
            --}}
            <div>
                <label for="{{ $awalan }}_produktivitas" class="{{ $kelasLabel }}">
                    Produktivitas<span x-show="! gagalTotal" class="text-error-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_produktivitas" name="produktivitas"
                        :required="! gagalTotal" :disabled="gagalTotal"
                        x-model="produktivitas" min="0" step="0.001"
                        placeholder="2.800" class="{{ $kelasKontrol }} tabular-nums pr-20 disabled:opacity-50" />
                    {{--
                        Sufiks memakai SIMBOL satuan, bukan nama penuh.

                        "Kilogram/ha" menabrak tombol naik-turun bawaan input
                        number, sebab keduanya menempati sudut kanan yang sama.
                        `right-10` menyisakan ruang bagi tombol itu.

                        Jangan memakai `right-8`: kelas itu tidak pernah
                        dibangkitkan Tailwind pada proyek ini, dan sufiksnya
                        akan terdorong ke LUAR kotak isian tanpa terlihat pada
                        markup.
                    --}}
                    <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                        <span x-text="tanam?.simbol ?? ''"></span>/ha
                    </span>
                </div>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    <span x-show="! gagalTotal">Satuannya mengikuti komoditas, bukan selalu ton.</span>
                    <span x-show="gagalTotal" x-cloak>Tidak diisi: tidak ada hasil yang ditimbang.</span>
                </p>
            </div>

            {{--
                PRODUKSI: terkunci, hasil Realisasi Panen x Produktivitas.

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
                    Realisasi panen dikali produktivitas.
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
