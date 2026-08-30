{{--
    Isian data pengadaan sarana produksi pertanian.

    SATU PENGADAAN, BANYAK POKTAN (Putaran 7). Satu batch bantuan lazim
    dibagikan ke beberapa poktan. Bagian atas mendeskripsikan bendanya; bagian
    bawah adalah distribusi: satu baris per poktan penerima dengan jumlahnya
    (dibagi rata otomatis, dapat disunting) dan tanggal serah. Kelompok tani
    boleh kosong: barang yang belum dibagikan tetap tercatat.

    SATUAN PERMUKIMAN MENGIKUTI POKTAN, terbaca per baris distribusi, tidak
    dipilih sendiri.

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
    // `$opsiSumberDana` disuplai ViewServiceProvider.

    $petaPoktan = [];
    foreach ($daftarPoktan as $p) {
        $petaPoktan[(string) $p['id_poktan']] = ['nama' => $p['nama'], 'sp' => $p['satuan_permukiman']];
    }

    $distribusiAwal = [];
    foreach ($data['distribusi'] ?? [] as $d) {
        $distribusiAwal[(string) $d['poktan_id']] = [
            'jumlah' => $d['jumlah'],
            'tanggal_serah' => $d['tanggal_serah'] ?? '',
        ];
    }
    $poktanAwal = array_keys($distribusiAwal);
@endphp

<div class="space-y-6"
    x-data="{
        poktanTerpilih: @js($poktanAwal),
        petaPoktan: @js($petaPoktan),
        jenis: @js(old('jenis', $data['jenis'] ?? '')),
        jumlahTotal: Number(@js(old('jumlah_total', $data['jumlah_total'] ?? 0))) || 0,
        distribusi: @js((object) $distribusiAwal),

        init() {
            this.$watch('poktanTerpilih', () => this.selaraskanDistribusi());
            this.$watch('jumlahTotal', () => this.bagiRata());
        },

        selaraskanDistribusi() {
            for (const pid of this.poktanTerpilih) {
                if (! this.distribusi[pid]) {
                    this.distribusi[pid] = { jumlah: 0, tanggal_serah: '' };
                }
            }
            for (const pid of Object.keys(this.distribusi)) {
                if (! this.poktanTerpilih.includes(pid)) {
                    delete this.distribusi[pid];
                }
            }
            this.bagiRata();
        },

        bagiRata() {
            const pids = this.poktanTerpilih;
            if (pids.length === 0) return;

            const dasar = Math.round((this.jumlahTotal / pids.length) * 100) / 100;
            let terpakai = 0;
            pids.forEach((pid, i) => {
                if (! this.distribusi[pid]) return;
                if (i === pids.length - 1) {
                    this.distribusi[pid].jumlah = Math.round((this.jumlahTotal - terpakai) * 100) / 100;
                } else {
                    this.distribusi[pid].jumlah = dasar;
                    terpakai += dasar;
                }
            });
        },

        namaPoktan(pid) { return this.petaPoktan[pid]?.nama ?? pid; },
        spUntuk(pid) { return this.petaPoktan[pid]?.sp ?? '-'; },

        get benih() { return this.jenis === @js(JenisSaprotan::Benih->value); },
        get jumlahTersalur() {
            return this.poktanTerpilih.reduce((t, pid) => t + Number(this.distribusi[pid]?.jumlah || 0), 0);
        },
        get sisaBelum() { return Math.round((this.jumlahTotal - this.jumlahTersalur) * 100) / 100; },
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
                <label for="{{ $awalan }}_jumlah_total" class="{{ $kelasLabel }}">Jumlah Total<span class="text-error-500">*</span></label>
                <input type="number" id="{{ $awalan }}_jumlah_total" name="jumlah_total" required
                    x-model.number="jumlahTotal" min="0" step="0.01"
                    class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_satuan_id" class="{{ $kelasLabel }}">Satuan<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_satuan_id" name="satuan_id" required class="{{ $kelasKontrol }}">
                    <option value="">Pilih satuan</option>
                    @foreach ($daftarSatuan as $s)
                        <option value="{{ $s['id_satuan'] }}"
                            @selected((string) old('satuan_id', $data['satuan_id'] ?? '') === (string) $s['id_satuan'])>
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

    {{-- Bagian 2: distribusi ke poktan --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Distribusi ke Kelompok Tani</h3>
        <div class="mt-3 space-y-4">
            <x-sim.pilih-cari-banyak nama="poktan_id" label="Kelompok Tani Penerima"
                :awalan="$awalan" :opsi="$daftarPoktan" kunci="id_poktan"
                teks="nama" keterangan-opsi="satuan_permukiman"
                :terpilih="$poktanAwal"
                sinkron-ke="poktanTerpilih"
                placeholder="Pilih satu atau lebih kelompok tani, atau biarkan kosong"
                keterangan="Kosongkan bila barang masih di gudang UPT. Satuan permukiman mengikuti poktan penerima." />

            <p class="text-theme-xs" :class="sisaBelum < -0.001 ? 'text-error-500' : 'text-gray-500 dark:text-gray-400'">
                Tersalur <span class="tabular-nums font-medium" x-text="jumlahTersalur"></span>
                dari <span class="tabular-nums font-medium" x-text="jumlahTotal"></span>.
                <span x-show="sisaBelum > 0.001">Sisa <span class="tabular-nums" x-text="sisaBelum"></span> belum tersalurkan.</span>
                <span x-show="sisaBelum < -0.001" class="font-medium">Jumlah distribusi melebihi total.</span>
            </p>

            <template x-for="pid in poktanTerpilih" :key="pid">
                <fieldset class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                    <legend class="px-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                        <span x-text="namaPoktan(pid)"></span>
                        <span class="font-normal text-gray-500 dark:text-gray-400" x-text="'(' + spUntuk(pid) + ')'"></span>
                    </legend>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_dist_jumlah_' + pid">Jumlah</label>
                            <input type="number" :id="'{{ $awalan }}_dist_jumlah_' + pid"
                                :name="`distribusi[${pid}][jumlah]`" x-model.number="distribusi[pid].jumlah"
                                min="0" step="0.01" class="{{ $kelasKontrol }} tabular-nums" />
                        </div>
                        <div>
                            <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_dist_tanggal_' + pid">Tanggal Serah</label>
                            <input type="date" :id="'{{ $awalan }}_dist_tanggal_' + pid"
                                :name="`distribusi[${pid}][tanggal_serah]`" x-model="distribusi[pid].tanggal_serah"
                                max="{{ date('Y-m-d') }}" class="{{ $kelasKontrol }}" />
                        </div>
                    </div>
                </fieldset>
            </template>

            <p x-show="poktanTerpilih.length === 0" class="rounded-lg bg-gray-50 px-4 py-3 text-theme-xs text-gray-500 dark:bg-white/5 dark:text-gray-400">
                Belum ada kelompok tani dipilih. Seluruh jumlah tercatat sebagai belum tersalurkan.
            </p>
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
