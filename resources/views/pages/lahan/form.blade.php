{{--
    Isian satu bidang lahan, dipakai bersama modal tambah dan modal ubah.

    Satu baris di sini adalah satu BIDANG, bukan seluruh lahan milik satu
    keluarga. Seorang transmigran umumnya menerima satu lahan pekarangan
    beserta satu lahan usaha, dan keduanya dicatat sebagai baris tersendiri
    agar luas, koordinat, dan dokumennya tidak tercampur.

    Aturan khusus modul ini: komposisi luas kering/basah, pola tanam,
    peralatan, dan kendala HANYA relevan bila peruntukannya lahan usaha. Untuk
    lahan pekarangan keempatnya dibiarkan kosong (agents/data-dictionary.md
    bagian 7.1).

    Karena itu keempat isian disembunyikan lewat Alpine ketika peruntukannya
    lahan pekarangan, agar operator tidak mengisi kolom yang tidak berlaku.

    LUAS LAHAN USAHA DIHITUNG, BUKAN DIKETIK. Aturan `luas_kering + luas_basah
    = luas` (rules.md 7.5) ditegakkan dengan cara menurunkan totalnya dari
    kedua bagian, bukan dengan memvalidasi tiga angka yang diketik terpisah.
    Petugas karena itu tidak dapat memasukkan angka yang jumlahnya keliru, dan
    tidak ada pesan galat yang perlu ditulis. Lahan pekarangan tidak memiliki
    komposisi, sehingga luasnya diketik langsung.

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
        kering: @js($data['luas_kering'] ?? ''),
        basah: @js($data['luas_basah'] ?? ''),
        get lahanUsaha() {
            return this.nilaiLahanUsaha.includes(this.peruntukan);
        },
        {{--
            Total lahan usaha selalu jumlah kedua bagiannya. Dibulatkan dua
            angka di belakang koma mengikuti DECIMAL(12,2) pada kamus data,
            sebab penjumlahan pecahan biner dapat menghasilkan ekor panjang
            seperti 1.9500000000000002 dan angka itu akan ikut terkirim.
        --}}
        get totalUsaha() {
            return Math.round(((Number(this.kering) || 0) + (Number(this.basah) || 0)) * 100) / 100;
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

            {{--
                Luas lahan pekarangan: diketik langsung, sebab pekarangan
                tidak memiliki komposisi kering dan basah.
            --}}
            <div x-show="! lahanUsaha">
                <label for="{{ $awalan }}_luas" class="{{ $kelasLabel }}">
                    Luas Lahan<span class="text-error-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_luas" name="luas"
                        value="{{ old('luas', $data['luas'] ?? '') }}" min="0.01" step="0.01"
                        :required="! lahanUsaha" :disabled="lahanUsaha"
                        placeholder="0.25" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span
                        class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                        ha
                    </span>
                </div>
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

        {{--
            Komposisi luas. Satu bidang boleh digarap sebagian kering dan
            sebagian basah sekaligus, sehingga keduanya isian angka, bukan
            pilihan salah satu. Bidang yang seluruhnya kering diisi 0 pada
            bagian basah, bukan dikosongkan (rules.md 7.5b).

            Total tidak dapat disunting: ia bacaan, bukan isian. Nilainya
            dikirim lewat isian ber-`name` berkelas `sr-only`, BUKAN
            `type="hidden"`, mengikuti keputusan yang sama pada komponen
            pilih-cari (ui-spec.md 6.0a): peramban mengabaikan `required`
            pada isian tersembunyi, sehingga form akan terkirim tanpa
            peringatan meski isiannya kosong.
        --}}
        <div class="mt-3 grid gap-4 sm:grid-cols-3">
            <div>
                <label for="{{ $awalan }}_luas_kering" class="{{ $kelasLabel }}">
                    Luas Lahan Kering<span class="text-error-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_luas_kering" name="luas_kering"
                        x-model="kering" min="0" step="0.01"
                        :required="lahanUsaha" :disabled="! lahanUsaha"
                        placeholder="1.25" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span
                        class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                        ha
                    </span>
                </div>
            </div>

            <div>
                <label for="{{ $awalan }}_luas_basah" class="{{ $kelasLabel }}">
                    Luas Lahan Basah<span class="text-error-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_luas_basah" name="luas_basah"
                        x-model="basah" min="0" step="0.01"
                        :required="lahanUsaha" :disabled="! lahanUsaha"
                        placeholder="0.75" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span
                        class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                        ha
                    </span>
                </div>
            </div>

            <div>
                <span class="{{ $kelasLabel }}">Total Luas Bidang</span>
                <div
                    class="flex h-11 items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 dark:border-gray-800 dark:bg-white/[0.03]">
                    <span class="text-theme-sm font-medium tabular-nums text-gray-800 dark:text-white/90"
                        x-text="totalUsaha.toFixed(2)">0.00</span>
                    <span class="text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
                </div>
                <input type="number" name="luas" class="sr-only" tabindex="-1" aria-hidden="true"
                    :value="totalUsaha" :disabled="! lahanUsaha" />
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Dihitung dari kedua bagian di atas.
                </p>
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
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
        rincian tetap ada untuk dokumen kedua dan seterusnya.

        NOMOR DOKUMEN DAN TANGGAL TERBIT DICABUT 2026-08-20 atas keputusan
        pemilik proyek, bersama isian status hak atas tanah. Keduanya sempat
        dipertahankan dengan alasan nomor sertifikat adalah data legal yang
        harus dapat dicari; alasan itu tidak salah, tetapi pendataan di
        lapangan tidak sampai ke sana. Kolomnya tetap ada pada tabel
        `dokumen_lahan` sehingga isian ini dapat dikembalikan tanpa mengubah
        skema bila dinas kelak memerlukannya.
    --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Dokumen Status Lahan</h3>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
            Dokumen tambahan untuk bidang yang sama diunggah lewat tab Dokumen pada halaman rincian.
        </p>
        {{--
            Jenis dokumen berdiri sendiri di kolom sempit, area unggah di
            baris penuh. Menaruh area unggah berpasangan dalam grid membuat
            kolom sebelahnya menyisakan ruang kosong besar, sebab tingginya
            jauh melebihi isian teks biasa. Tiga belas form lain di sistem ini
            sudah menempatkannya di baris penuh dengan alasan yang sama.
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
