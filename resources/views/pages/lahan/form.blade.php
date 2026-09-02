{{--
    Isian lahan satu KELUARGA, dipakai bersama modal tambah dan modal ubah.

    SATU BARIS = SATU KELUARGA (ditetapkan 2026-09-02, Putaran 15). Sebelumnya
    satu baris adalah satu BIDANG, sehingga keluarga dengan pekarangan dan lahan
    usaha menempati dua baris. Keduanya kini disatukan sebab jumlahnya memang
    tetap: tepat satu pekarangan dan satu lahan usaha per keluarga (rules.md 7.8).

    Koordinat tetap DUA PASANG, sebab pekarangan dan lahan usaha berada di
    tempat yang berbeda. Menyatukannya menjadi satu titik berarti membuang
    lokasi yang sudah terdata.

    Pekarangan boleh KOSONG: sebagian keluarga baru menerima lahan usaha.
    Kosong berarti BELUM MENERIMA, bukan menerima seluas nol hektare.

    LUAS LAHAN USAHA DIHITUNG, BUKAN DIKETIK. Aturan `luas_kering + luas_basah
    = luas_usaha` (rules.md 7.5) ditegakkan dengan menurunkan totalnya dari
    kedua bagian, sehingga petugas tidak dapat memasukkan jumlah yang keliru.

    Nama kolom mengikuti agents/data-dictionary.md bagian 7.1.
--}}
@php
    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    // `transmigran_id` UNIQUE (Putaran 15): satu keluarga tepat satu baris
    // lahan. Alur TAMBAH karena itu hanya menawarkan KK yang belum punya baris;
    // menawarkan KK yang sudah terdata membuat simpan selalu ditolak UNIQUE
    // tanpa menjelaskan apa pun. Alur UBAH memakai daftar penuh sebab pemiliknya
    // sudah terpilih.
    $modeTambahLahan = $awalan === 'tambah';
    $opsiPemilikLahan = $modeTambahLahan
        ? ($transmigranTanpaLahan ?? $daftarTransmigran)
        : $daftarTransmigran;

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasArea = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
@endphp

<div class="space-y-6"
    x-data="{
        kering: @js($data['luas_kering'] ?? ''),
        basah: @js($data['luas_basah'] ?? ''),
        petaSpTransmigran: @js(collect($daftarTransmigran)->pluck('satuan_permukiman_id', 'id_transmigran')->all()),
        gantiPemilik(id) {
            const spId = this.petaSpTransmigran ? this.petaSpTransmigran[id] : null;
            if (spId) {
                const sel = this.$el.querySelector('[name=&quot;satuan_permukiman_id&quot;]');
                if (sel) {
                    sel.value = spId;
                    sel.dispatchEvent(new Event('input', { bubbles: true }));
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        },
        {{--
            Total lahan usaha selalu jumlah kedua bagiannya. Dibulatkan dua
            angka mengikuti DECIMAL(12,2), sebab penjumlahan pecahan biner
            dapat menghasilkan ekor panjang seperti 1.9500000000000002.
        --}}
        get totalUsaha() {
            return Math.round(((Number(this.kering) || 0) + (Number(this.basah) || 0)) * 100) / 100;
        },
    }"
    @change="if ($event.target.name === 'transmigran_id') gantiPemilik($event.target.value)">

    {{-- Langkah 1: Identitas & Pemilik --}}
    <div data-langkah="1" x-show="! bertahap || langkah === 1" x-cloak>
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas & Pemilik Lahan</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-sim.pilih-cari nama="transmigran_id" label="Pemilik" :wajib="true"
                    :awalan="$awalan" :opsi="$opsiPemilikLahan" kunci="id_transmigran"
                    teks="nama_kepala_keluarga" keterangan-opsi="nik" gaya="kurung"
                    :terpilih="old('transmigran_id', $data['transmigran_id'] ?? null)"
                    placeholder="Pilih kepala keluarga"
                    keterangan="{{ $modeTambahLahan
                        ? 'Hanya keluarga yang belum punya baris lahan. Keluarga yang sudah terdata disunting lewat tombol Ubah pada daftar.'
                        : 'Satu keluarga tercatat pada satu baris, memuat pekarangan dan lahan usahanya sekaligus.' }}"
                    @change="gantiPemilik($event.target.value)" />
            </div>

            <div class="sm:col-span-2">
                <x-sim.wilayah-picker
                    :daftar-kawasan="[['id' => 1, 'nama' => 'Kobalima Timur']]"
                    :daftar-sp="collect($daftarSp)
                        ->map(fn ($s) => ['id' => $s['id_satuan_permukiman'], 'nama' => $s['nama'], 'kawasan_id' => 1])
                        ->all()"
                    :sp-terpilih="old('satuan_permukiman_id', $data['satuan_permukiman_id'] ?? null)" />
            </div>

            <div>
                <label for="{{ $awalan }}_kode_lahan" class="{{ $kelasLabel }}">Kode Lahan</label>
                <input type="text" id="{{ $awalan }}_kode_lahan" name="kode_lahan"
                    value="{{ old('kode_lahan', $data['kode_lahan'] ?? '') }}" maxlength="50"
                    placeholder="Contoh: LH-025" class="{{ $kelasKontrol }}" />
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_tujuan_pemanfaatan" class="{{ $kelasLabel }}">Tujuan Pemanfaatan</label>
                <textarea id="{{ $awalan }}_tujuan_pemanfaatan" name="tujuan_pemanfaatan" rows="2"
                    placeholder="Contoh: budidaya jagung dan kacang tanah"
                    class="{{ $kelasArea }}">{{ old('tujuan_pemanfaatan', $data['tujuan_pemanfaatan'] ?? '') }}</textarea>
            </div>
        </div>
    </section>
    </div>

    {{-- Langkah 2: Kedua Bidang --}}
    <div data-langkah="2" x-show="! bertahap || langkah === 2" x-cloak>
    <section>
        {{--
            Pekarangan lebih dulu sebab ia yang diterima saat penempatan;
            lahan usaha menyusul. Urutannya mengikuti urutan kejadian di
            lapangan, bukan urutan luasnya.
        --}}
        <h3 class="{{ $kelasBagian }}">Lahan Pekarangan</h3>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
            Kosongkan bila keluarga ini belum menerima lahan pekarangan. Dikosongkan berarti
            <span class="font-medium">belum menerima</span>, bukan menerima seluas nol hektare.
        </p>

        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_luas_pekarangan" class="{{ $kelasLabel }}">Luas Lahan Pekarangan</label>
                <div class="relative max-w-sm">
                    <input type="number" id="{{ $awalan }}_luas_pekarangan" name="luas_pekarangan"
                        value="{{ old('luas_pekarangan', $data['luas_pekarangan'] ?? '') }}" min="0.01" step="0.01"
                        placeholder="0.25" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <x-sim.koordinat-input :lintang="$data['lintang_pekarangan'] ?? null"
                :bujur="$data['bujur_pekarangan'] ?? null"
                nama-lintang="lintang_pekarangan" nama-bujur="bujur_pekarangan" />
        </div>

        <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-800">
            <h3 class="{{ $kelasBagian }}">Lahan Usaha</h3>
            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                Komposisi kering dan basah adalah pembagian luas SATU bidang, bukan dua bidang
                terpisah. Bidang yang seluruhnya kering diisi basah nol.
            </p>

            <div class="mt-3 grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="{{ $awalan }}_luas_kering" class="{{ $kelasLabel }}">Luas Lahan Kering</label>
                    <div class="relative">
                        <input type="number" id="{{ $awalan }}_luas_kering" name="luas_kering"
                            x-model="kering" min="0" step="0.01"
                            placeholder="1.25" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                        <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
                    </div>
                </div>

                <div>
                    <label for="{{ $awalan }}_luas_basah" class="{{ $kelasLabel }}">Luas Lahan Basah</label>
                    <div class="relative">
                        <input type="number" id="{{ $awalan }}_luas_basah" name="luas_basah"
                            x-model="basah" min="0" step="0.01"
                            placeholder="0.75" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                        <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
                    </div>
                </div>

                <div>
                    <span class="{{ $kelasLabel }}">Total Luas Lahan Usaha</span>
                    <div aria-live="polite" aria-atomic="true"
                        class="flex h-11 items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 dark:border-gray-800 dark:bg-white/[0.03]">
                        <span class="text-theme-sm font-medium tabular-nums text-gray-800 dark:text-white/90"
                            x-text="totalUsaha.toFixed(2)">0.00</span>
                        <span class="text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
                    </div>
                    <input type="number" name="luas_usaha" class="sr-only" tabindex="-1" aria-hidden="true"
                        :value="totalUsaha" />
                    <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">Dihitung dari kedua bagian di atas.</p>
                </div>
            </div>

            <div class="mt-3">
                <x-sim.koordinat-input :lintang="$data['lintang_usaha'] ?? null"
                    :bujur="$data['bujur_usaha'] ?? null"
                    nama-lintang="lintang_usaha" nama-bujur="bujur_usaha" />
            </div>
        </div>
    </section>
    </div>

    {{-- Langkah 3: Legalitas & Catatan --}}
    <div data-langkah="3" x-show="! bertahap || langkah === 3" x-cloak>
    <section>
        {{--
            SHM DAN STATUS SERTIFIKAT adalah ISIAN di sini; HPL BACAAN
            (rules.md 7.6a, dibalik 2026-09-03 atas keputusan pemilik proyek).

            Keduanya secara hukum milik KELUARGA, bukan bidang: SHM meliputi
            seluruh lahan satu KK (pekarangan maupun usaha) dan tersimpan pada
            `transmigran_berkas` peran `shm`, statusnya pada
            `transmigran.status_sertifikat`. Alasan lama menjadikannya "bacaan
            saja" - unggahan per-bidang melahirkan salinan sertifikat yang sama
            di banyak baris - GUGUR sejak Putaran 15: satu keluarga kini tepat
            SATU baris lahan, sehingga form ini menjadi tempat kanonis
            mengunggahnya.

            HPL tetap bacaan: ia alas hak KAWASAN milik instansi, satu untuk
            seluruh bidang di dalamnya, dan diunggah dari Data Kawasan.
        --}}
        <h3 class="{{ $kelasBagian }}">Legalitas Lahan Keluarga</h3>

        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_status_sertifikat" class="{{ $kelasLabel }}">
                    Status Sertifikat<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_status_sertifikat" name="status_sertifikat" required
                    class="{{ $kelasKontrol }}">
                    @foreach (\App\Enums\StatusSertifikat::opsi() as $nilai => $label)
                        <option value="{{ $nilai }}"
                            @selected(old('status_sertifikat', $data['status_sertifikat'] ?? 'Belum Didata') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    "Belum Didata" bila petugas belum sempat menanyakannya - berbeda dari "Belum".
                </p>
            </div>
        </div>

        <div class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
            <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">Alas hak kawasan (HPL)</p>
            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                Hak Pengelolaan atas tanah kawasan, dipegang instansi. Satu HPL menaungi
                seluruh bidang di dalam kawasan, sehingga diunggah dari
                <span class="font-medium">Data Kawasan</span>, bukan dari sini.
            </p>
        </div>

        {{-- Catatan sebelum unggahan: unggahan memutus alur pengetikan, dan
             catatan sesudahnya kerap terlewat (ui-spec.md 6.4a poin 5). --}}
        <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-800">
            <h3 class="{{ $kelasBagian }}">Catatan Tambahan</h3>
            <div class="mt-3">
                <label for="{{ $awalan }}_keterangan" class="{{ $kelasLabel }}">Catatan</label>
                <textarea id="{{ $awalan }}_keterangan" name="keterangan" rows="3" maxlength="1000"
                    placeholder="Catatan tambahan bila ada"
                    class="{{ $kelasArea }}">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
            </div>
        </div>

        {{-- Unggahan selalu paling bawah (ui-spec.md 6.4a). --}}
        <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-800">
            <x-sim.file-upload nama="shm" label="Sertifikat keluarga (SHM)"
                nama-dokumen="Sertifikat SHM" :nama-pemilik="$data['pemilik'] ?? null"
                :berkas-saat-ini="$data['shm'] ?? null"
                keterangan="Meliputi seluruh bidang keluarga ini, pekarangan maupun lahan usaha. Cukup diunggah sekali." />
        </div>
    </section>
    </div>
</div>
