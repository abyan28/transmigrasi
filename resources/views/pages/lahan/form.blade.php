{{--
    Isian satu bidang lahan, dipakai bersama modal tambah dan modal ubah.

    Satu baris di sini adalah satu BIDANG, bukan seluruh lahan milik satu
    keluarga. Seorang transmigran umumnya menerima satu lahan pekarangan
    beserta satu lahan usaha, dan keduanya dicatat sebagai baris tersendiri
    agar luas, koordinat, dan dokumennya tidak tercampur.

    Aturan khusus modul ini: kategori lahan, pola tanam, peralatan, dan kendala
    HANYA relevan bila peruntukannya lahan usaha. Untuk lahan pekarangan
    keempatnya dibiarkan kosong (agents/data-dictionary.md bagian 7.1).

    Karena itu keempat isian disembunyikan lewat Alpine ketika peruntukannya
    lahan pekarangan, agar operator tidak mengisi kolom yang tidak berlaku.

    Nama kolom mengikuti agents/data-dictionary.md bagian 7.1.
--}}
@php
    use App\Enums\PeruntukanLahan;
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasArea = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    // Dipusatkan pada enum agar penambahan tahap lahan usaha berikutnya tidak
    // perlu menyunting perbandingan teks yang tersebar di beberapa halaman.
    $nilaiLahanUsaha = PeruntukanLahan::nilaiLahanUsaha();
@endphp

<div class="space-y-6"
    x-data="{
        peruntukan: @js($data['peruntukan_lahan'] ?? PeruntukanLahan::LahanPekarangan->value),
        nilaiLahanUsaha: @js($nilaiLahanUsaha),
        get lahanUsaha() {
            return this.nilaiLahanUsaha.includes(this.peruntukan);
        },
    }">
    {{-- Bagian 1: identitas bidang --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Bidang Lahan</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_kode_lahan" class="{{ $kelasLabel }}">Kode Lahan</label>
                {{--
                    Contoh pada placeholder sengaja memakai nomor yang tidak
                    dipakai data mana pun, agar tidak tertukar dengan kode asli.
                --}}
                <input type="text" id="{{ $awalan }}_kode_lahan" name="kode_lahan"
                    value="{{ old('kode_lahan', $data['kode_lahan'] ?? '') }}" maxlength="50"
                    placeholder="Contoh: LU-025" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <x-sim.pilih-cari nama="transmigran_id" label="Pemilik" :wajib="true"
                    :awalan="$awalan" :opsi="DummyData::transmigran()" kunci="id_transmigran"
                    teks="nama_kepala_keluarga" keterangan-opsi="nik" gaya="kurung"
                    :terpilih="old('transmigran_id', $data['transmigran_id'] ?? null)"
                    placeholder="Pilih kepala keluarga"
                    keterangan="Tiap keluarga memiliki bidang pekarangan dan bidang usahanya sendiri." />
            </div>

            <div>
                <label for="{{ $awalan }}_peruntukan_lahan" class="{{ $kelasLabel }}">
                    Peruntukan Lahan<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_peruntukan_lahan" name="peruntukan_lahan" x-model="peruntukan" required
                    class="{{ $kelasKontrol }}">
                    @foreach (PeruntukanLahan::opsi() as $nilai => $label)
                        <option value="{{ $nilai }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Satu keluarga umumnya menerima satu lahan pekarangan dan satu lahan usaha.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_luas" class="{{ $kelasLabel }}">
                    Luas Lahan<span class="text-error-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_luas" name="luas"
                        value="{{ old('luas', $data['luas'] ?? '') }}" required min="0.01" step="0.01"
                        placeholder="1.50" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span
                        class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                        ha
                    </span>
                </div>
            </div>

            {{--
                Status HAK, bukan status kepemilikan.

                Isian ini sempat memuat nilai HPL dan SHM, dan keduanya keliru:
                HPL adalah Hak Pengelolaan milik instansi atas tanah kawasan,
                sehingga tidak pernah menjadi hak seorang transmigran; SHM
                adalah nama sertifikatnya, bukan nama haknya. Keduanya kini
                menjadi jenis dokumen, bukan status hak.
            --}}
            <div>
                <label for="{{ $awalan }}_status_hak" class="{{ $kelasLabel }}">Status Hak Atas Tanah</label>
                <select id="{{ $awalan }}_status_hak" name="status_hak" class="{{ $kelasKontrol }}">
                    <option value="">Pilih status hak</option>
                    @foreach (\App\Enums\StatusHakLahan::opsi() as $nilai => $label)
                        <option value="{{ $nilai }}"
                            @selected(old('status_hak', $data['status_hak'] ?? '') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Bidang yang sertifikatnya belum terbit diisi Belum Bersertifikat.
                </p>
            </div>

            {{--
                Kategori hanya berlaku untuk lahan usaha, dan wajib bila
                berlaku. Bintang statis, `required` mengikuti peruntukan
                agar isian yang tersembunyi tidak menghalangi pengiriman.
            --}}
            <div x-show="lahanUsaha" x-cloak>
                <label for="{{ $awalan }}_kategori_lahan" class="{{ $kelasLabel }}">
                    Kategori Lahan<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_kategori_lahan" name="kategori_lahan" class="{{ $kelasKontrol }}"
                    :required="lahanUsaha">
                    <option value="">Pilih kategori</option>
                    @foreach (\App\Enums\KategoriLahan::opsi() as $nilai => $label)
                        <option value="{{ $nilai }}"
                            @selected(old('kategori_lahan', $data['kategori_lahan'] ?? '') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <x-sim.wilayah-picker
                    :daftar-kawasan="[['id' => 1, 'nama' => 'Kobalima Timur']]"
                    :daftar-sp="collect(DummyData::satuanPermukiman())
                        ->map(fn ($s) => ['id' => $s['id_satuan_permukiman'], 'nama' => $s['nama'], 'kawasan_id' => 1])
                        ->all()"
                    :sp-terpilih="old('satuan_permukiman_id', $data['satuan_permukiman_id'] ?? null)" />
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_tujuan_pemanfaatan" class="{{ $kelasLabel }}">Tujuan Pemanfaatan</label>
                <textarea id="{{ $awalan }}_tujuan_pemanfaatan" name="tujuan_pemanfaatan" rows="2"
                    placeholder="Contoh: budidaya jagung dan kacang tanah"
                    class="{{ $kelasArea }}">{{ old('tujuan_pemanfaatan', $data['tujuan_pemanfaatan'] ?? '') }}</textarea>
            </div>
        </div>
    </section>

    {{-- Bagian 2: pengelolaan, khusus lahan usaha --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800" x-show="lahanUsaha"
        x-cloak>
        <h3 class="{{ $kelasBagian }}">Pengelolaan Lahan Usaha</h3>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
            Bagian ini hanya berlaku untuk lahan usaha, tidak untuk lahan pekarangan.
        </p>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_pola_tanam" class="{{ $kelasLabel }}">Pola Tanam</label>
                <input type="text" id="{{ $awalan }}_pola_tanam" name="pola_tanam"
                    value="{{ old('pola_tanam', $data['pola_tanam'] ?? '') }}" maxlength="255"
                    placeholder="Contoh: monokultur jagung, tumpang sari" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_peralatan" class="{{ $kelasLabel }}">Peralatan Pertanian</label>
                <textarea id="{{ $awalan }}_peralatan" name="peralatan_pertanian" rows="3"
                    placeholder="Peralatan yang dipakai menggarap lahan"
                    class="{{ $kelasArea }}">{{ old('peralatan_pertanian', $data['peralatan_pertanian'] ?? '') }}</textarea>
            </div>

            <div>
                <label for="{{ $awalan }}_kendala" class="{{ $kelasLabel }}">Kendala yang Dihadapi</label>
                <textarea id="{{ $awalan }}_kendala" name="kendala" rows="3"
                    placeholder="Contoh: kekurangan air pada musim kemarau"
                    class="{{ $kelasArea }}">{{ old('kendala', $data['kendala'] ?? '') }}</textarea>
            </div>
        </div>
    </section>

    {{-- Bagian 3: lokasi --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Titik Lokasi</h3>
        <div class="mt-3">
            <x-sim.koordinat-input :lintang="$data['lintang'] ?? null" :bujur="$data['bujur'] ?? null" />
        </div>
    </section>

    {{--
        Bagian 4: dokumen pertama.

        Sebelumnya seluruh dokumen hanya dapat diunggah lewat tab tersendiri di
        halaman rincian, dengan alasan satu bidang dapat memiliki lebih dari
        satu dokumen. Alasan itu benar secara teori, tetapi memaksa dua langkah
        untuk keadaan yang paling lazim: pada data yang ada, tidak satu pun
        bidang memiliki lebih dari satu dokumen.

        Dokumen pertama karena itu dipindah ke sini, sedangkan tab pada halaman
        rincian tetap ada untuk dokumen kedua dan seterusnya. Ketiga isian
        keterangan dokumen dipertahankan sebab nomor sertifikat adalah data
        legal yang harus dapat dicari, bukan sekadar lampiran.
    --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Dokumen Status Lahan</h3>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
            Dokumen tambahan untuk bidang yang sama diunggah lewat tab Dokumen pada halaman rincian.
        </p>
        {{--
            Tiga keterangan dokumen berjajar, sedangkan area unggah berdiri
            sendiri di baris penuh.

            Menaruh area unggah di dalam grid berpasangan membuat kolom
            sebelahnya menyisakan ruang kosong besar, sebab tingginya jauh
            melebihi isian teks biasa. Tiga belas form lain di sistem ini sudah
            menempatkannya di baris penuh dengan alasan yang sama.
        --}}
        <div class="mt-3 grid gap-4 sm:grid-cols-3">
            <div>
                <label for="{{ $awalan }}_jenis_dokumen" class="{{ $kelasLabel }}">Jenis Dokumen</label>
                <select id="{{ $awalan }}_jenis_dokumen" name="jenis_dokumen" class="{{ $kelasKontrol }}">
                    <option value="">Belum ada dokumen</option>
                    @foreach (\App\Enums\JenisDokumenLahan::opsi() as $nilai => $label)
                        <option value="{{ $nilai }}"
                            @selected(old('jenis_dokumen', $data['jenis_dokumen'] ?? '') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_nomor_dokumen" class="{{ $kelasLabel }}">Nomor Dokumen</label>
                <input type="text" id="{{ $awalan }}_nomor_dokumen" name="nomor_dokumen"
                    value="{{ old('nomor_dokumen', $data['nomor_dokumen'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: SHM/MLK/2021/0871" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_tanggal_terbit" class="{{ $kelasLabel }}">Tanggal Terbit</label>
                <input type="date" id="{{ $awalan }}_tanggal_terbit" name="tanggal_terbit"
                    value="{{ old('tanggal_terbit', $data['tanggal_terbit'] ?? '') }}" max="{{ date('Y-m-d') }}"
                    class="{{ $kelasKontrol }}" />
            </div>
        </div>

        <div class="mt-4">
            <x-sim.file-upload nama="file_dokumen" label="Berkas Dokumen"
                nama-dokumen="Dokumen Lahan" :nama-pemilik="$data['kode_lahan'] ?? null"
                :berkas-saat-ini="$data['file_dokumen'] ?? null"
                keterangan="Pindaian sertifikat atau surat keterangan." />
        </div>
    </section>

    {{-- Bagian 5: keterangan --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Keterangan</h3>
        <div class="mt-3">
            <label for="{{ $awalan }}_keterangan" class="sr-only">Keterangan</label>
            <textarea id="{{ $awalan }}_keterangan" name="keterangan" rows="3" maxlength="1000"
                placeholder="Catatan tambahan bila ada"
                class="{{ $kelasArea }}">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
        </div>
    </section>
</div>
