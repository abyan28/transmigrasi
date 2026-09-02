{{--
    Isian data alsintan, dipakai bersama modal tambah dan modal ubah.

    SATU PENGADAAN, BANYAK POKTAN (Putaran 7). Satu batch bantuan (mis. 4
    traktor anggaran APBN 2018) lazim dibagikan ke beberapa poktan, bahkan
    lintas SP. Model lama membawa satu poktan_id pada baris pengadaan, sehingga
    satu batch harus diketik ulang per poktan.

    Bagian atas mendeskripsikan BENDAnya: jenis (data master), nama, jumlah
    total, tahun pengadaan, sumber dana. Bagian bawah adalah DISTRIBUSI: satu
    baris per poktan penerima, dengan jumlah (dibagi rata otomatis, dapat
    disunting), kondisi (diamati per unit di lapangan), penanda tangan serah
    terima, dan tanggal serah. Kelompok tani boleh kosong: barang yang sudah di
    gudang UPT dan belum dibagikan tetap tercatat.

    Nama kolom mengikuti agents/data-dictionary.md bagian 8.3.
--}}
@php
    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    // Peta poktan ke SP-nya dan ke anggota aktifnya, dibaca Alpine untuk tiap
    // baris distribusi (SP terbaca, penanda tangan menyempit).
    $petaPoktan = [];
    foreach ($daftarPoktan as $p) {
        $petaPoktan[(string) $p['id_poktan']] = [
            'nama' => $p['nama'],
            'sp' => $p['satuan_permukiman'],
        ];
    }

    // Nilai awal distribusi bila form ubah membawanya (Tahap 5).
    $distribusiAwal = [];
    foreach ($data['distribusi'] ?? [] as $d) {
        $distribusiAwal[(string) $d['poktan_id']] = [
            'jumlah' => $d['jumlah'],
            'kondisi' => $d['kondisi'] ?? 'Baik',
            'penanda_terima_id' => (string) ($d['penanda_terima_id'] ?? ''),
            'tanggal_serah' => $d['tanggal_serah'] ?? '',
        ];
    }
    $poktanAwal = array_keys($distribusiAwal);
@endphp

<div class="space-y-6"
    x-data="{
        poktanTerpilih: @js($poktanAwal),
        petaPoktan: @js($petaPoktan),
        anggotaPerPoktan: @js($anggotaPerPoktan),
        kondisiBawaan: @js(array_key_first($opsiKondisi) ?? 'Baik'),
        jumlahTotal: Number(@js(old('jumlah_total', $data['jumlah_total'] ?? 0))) || 0,
        distribusi: @js((object) $distribusiAwal),

        init() {
            this.$watch('poktanTerpilih', () => this.selaraskanDistribusi());
            this.$watch('jumlahTotal', () => this.bagiRata());
        },

        /* Tambah baris untuk poktan baru, buang baris poktan yang dilepas. */
        selaraskanDistribusi() {
            for (const pid of this.poktanTerpilih) {
                if (! this.distribusi[pid]) {
                    this.distribusi[pid] = { jumlah: 0, kondisi: this.kondisiBawaan, penanda_terima_id: '', tanggal_serah: '' };
                }
            }
            for (const pid of Object.keys(this.distribusi)) {
                if (! this.poktanTerpilih.includes(pid)) {
                    delete this.distribusi[pid];
                }
            }
            this.bagiRata();
        },

        /* Bagi rata jumlah total; sisa pembagian jatuh ke poktan pertama. */
        bagiRata() {
            const pids = this.poktanTerpilih;
            if (pids.length === 0) return;

            const dasar = Math.floor(this.jumlahTotal / pids.length);
            const sisa = this.jumlahTotal - dasar * pids.length;
            pids.forEach((pid, i) => {
                if (this.distribusi[pid]) {
                    this.distribusi[pid].jumlah = dasar + (i === 0 ? sisa : 0);
                }
            });
        },

        anggotaUntuk(pid) { return this.anggotaPerPoktan[pid] ?? []; },
        spUntuk(pid) { return this.petaPoktan[pid]?.sp ?? '-'; },
        namaPoktan(pid) { return this.petaPoktan[pid]?.nama ?? pid; },

        get jumlahTersalur() {
            return this.poktanTerpilih.reduce((t, pid) => t + Number(this.distribusi[pid]?.jumlah || 0), 0);
        },
        get sisaBelum() { return this.jumlahTotal - this.jumlahTersalur; },
    }">

    {{-- Bagian 1: identitas pengadaan --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Pengadaan</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_jenis_alsintan" class="{{ $kelasLabel }}">Jenis Alat<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_jenis_alsintan" name="jenis_alsintan" required class="{{ $kelasKontrol }}">
                    <option value="">Pilih jenis</option>
                    @foreach ($opsiJenisAlsintan as $nilaiRef => $labelRef)
                        <option value="{{ $nilaiRef }}" @selected(old('jenis_alsintan', $data['jenis_alsintan'] ?? '') === $nilaiRef)>{{ $nilaiRef }}</option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">Daftar jenis dikelola Admin lewat data master.</p>
            </div>

            <div>
                <label for="{{ $awalan }}_nama_alat" class="{{ $kelasLabel }}">Nama Alat<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_alat" name="nama_alat" required
                    value="{{ old('nama_alat', $data['nama_alat'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: TRAKTOR RODA DUA KUBOTA" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_jumlah_total" class="{{ $kelasLabel }}">Jumlah Unit (Total)<span class="text-error-500">*</span></label>
                <input type="number" id="{{ $awalan }}_jumlah_total" name="jumlah_total" required
                    x-model.number="jumlahTotal" min="1" step="1"
                    class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_tahun_pengadaan" class="{{ $kelasLabel }}">Tahun Pengadaan</label>
                <input type="number" id="{{ $awalan }}_tahun_pengadaan" name="tahun_pengadaan"
                    value="{{ old('tahun_pengadaan', $data['tahun_pengadaan'] ?? '') }}" min="1900"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_sumber_dana" class="{{ $kelasLabel }}">Sumber Dana</label>
                <select id="{{ $awalan }}_sumber_dana" name="sumber_dana" class="{{ $kelasKontrol }}">
                    <option value="">Pilih sumber</option>
                    @foreach ($opsiSumberDana as $nilaiRef => $labelRef)
                        <option value="{{ $nilaiRef }}" @selected(old('sumber_dana', $data['sumber_dana'] ?? '') === $nilaiRef)>{{ $nilaiRef }}</option>
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
                keterangan="Kosongkan bila alat masih di gudang UPT dan belum dibagikan. Satuan permukiman mengikuti poktan penerima." />

            {{-- Sisa yang belum tersalurkan, terhitung hidup. --}}
            <p class="text-theme-xs" :class="sisaBelum < 0 ? 'text-error-500' : 'text-gray-500 dark:text-gray-400'">
                Tersalur <span class="tabular-nums font-medium" x-text="jumlahTersalur"></span>
                dari <span class="tabular-nums font-medium" x-text="jumlahTotal"></span> unit.
                <span x-show="sisaBelum > 0">Sisa <span class="tabular-nums" x-text="sisaBelum"></span> unit belum tersalurkan.</span>
                <span x-show="sisaBelum < 0" class="font-medium">Jumlah distribusi melebihi total.</span>
            </p>

            {{-- Satu baris per poktan terpilih. --}}
            <template x-for="pid in poktanTerpilih" :key="pid">
                <fieldset class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                    <legend class="px-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                        <span x-text="namaPoktan(pid)"></span>
                        <span class="font-normal text-gray-500 dark:text-gray-400" x-text="'(' + spUntuk(pid) + ')'"></span>
                    </legend>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_dist_jumlah_' + pid">Jumlah Unit</label>
                            <input type="number" :id="'{{ $awalan }}_dist_jumlah_' + pid"
                                :name="`distribusi[${pid}][jumlah]`" x-model.number="distribusi[pid].jumlah"
                                min="0" step="1" class="{{ $kelasKontrol }} tabular-nums" />
                        </div>
                        <div>
                            <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_dist_kondisi_' + pid">Kondisi</label>
                            <select :id="'{{ $awalan }}_dist_kondisi_' + pid"
                                :name="`distribusi[${pid}][kondisi]`" x-model="distribusi[pid].kondisi"
                                class="{{ $kelasKontrol }}">
                                @foreach ($opsiKondisi as $nilaiRef => $labelRef)
                                    <option value="{{ $nilaiRef }}">{{ $nilaiRef }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_dist_penanda_' + pid">Penanda Tangan Serah Terima</label>
                            <select :id="'{{ $awalan }}_dist_penanda_' + pid"
                                :name="`distribusi[${pid}][penanda_terima_id]`" x-model="distribusi[pid].penanda_terima_id"
                                class="{{ $kelasKontrol }}">
                                <option value="">Belum dicatat</option>
                                <template x-for="anggota in anggotaUntuk(pid)" :key="anggota.id">
                                    <option :value="anggota.id" x-text="anggota.nama + ' - ' + anggota.jabatan"></option>
                                </template>
                            </select>
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

    {{-- Bagian 3: catatan --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Catatan</h3>
        <div class="mt-3">
            <label for="{{ $awalan }}_keterangan" class="{{ $kelasLabel }}">Catatan</label>
            <textarea id="{{ $awalan }}_keterangan" name="keterangan" rows="3" maxlength="1000"
                placeholder="Contoh: bantuan mekanisasi lahan kering, dibagi rata tiga poktan."
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
        </div>
    </section>

    {{--
        Bagian 4: dokumentasi. Unggahan selalu paling bawah (ui-spec.md 6.4a).

        DUA KOLOM TERPISAH: foto dan dokumen, sejajar saprotan (keputusan 11
        Putaran 12). Foto merekam wujud batch pengadaan saat diterima; dokumen
        menyimpan berita acaranya. Foto KONDISI PER UNIT di tiap poktan tetap
        diunggah dari halaman rincian, sebab kondisinya berbeda per distribusi.
    --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Dokumentasi</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <x-sim.file-upload nama="foto" label="Foto Barang" :hanya-gambar="true"
                nama-dokumen="Foto Alsintan" :nama-pemilik="$data['nama_alat'] ?? null"
                :berkas-saat-ini="$data['foto'] ?? null"
                keterangan="Wujud unit saat batch pengadaan diterima." />

            <x-sim.file-upload nama="dokumen_pendukung" label="Dokumen Pendukung"
                nama-dokumen="Dokumen Alsintan" :nama-pemilik="$data['nama_alat'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                keterangan="Berita acara penyerahan atau bukti pengadaan." />
        </div>
    </section>
</div>
