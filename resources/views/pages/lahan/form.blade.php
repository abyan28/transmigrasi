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
    }"
    @change="if ($event.target.name === 'transmigran_id') gantiPemilik($event.target.value)">

    {{-- Langkah 1: Identitas & Pemilik --}}
    <div data-langkah="1" x-show="! bertahap || langkah === 1" x-cloak>
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas & Pemilik Bidang Lahan</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-sim.pilih-cari nama="transmigran_id" label="Pemilik" :wajib="true"
                    :awalan="$awalan" :opsi="$daftarTransmigran" kunci="id_transmigran"
                    teks="nama_kepala_keluarga" keterangan-opsi="nik" gaya="kurung"
                    :terpilih="old('transmigran_id', $data['transmigran_id'] ?? null)"
                    placeholder="Pilih kepala keluarga"
                    keterangan="Tiap keluarga memiliki bidang pekarangan dan bidang usahanya sendiri."
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
                {{--
                    Contoh pada placeholder sengaja memakai nomor yang tidak
                    dipakai data mana pun, agar tidak tertukar dengan kode asli.
                --}}
                <input type="text" id="{{ $awalan }}_kode_lahan" name="kode_lahan"
                    value="{{ old('kode_lahan', $data['kode_lahan'] ?? '') }}" maxlength="50"
                    placeholder="Contoh: LU-025" class="{{ $kelasKontrol }}" />
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
            <div x-show="! lahanUsaha" class="sm:col-span-2">
                <label for="{{ $awalan }}_luas" class="{{ $kelasLabel }}">
                    Luas Lahan Pekarangan<span class="text-error-500">*</span>
                </label>
                <div class="relative max-w-sm">
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
                <label for="{{ $awalan }}_tujuan_pemanfaatan" class="{{ $kelasLabel }}">Tujuan Pemanfaatan</label>
                <textarea id="{{ $awalan }}_tujuan_pemanfaatan" name="tujuan_pemanfaatan" rows="2"
                    placeholder="Contoh: budidaya jagung dan kacang tanah"
                    class="{{ $kelasArea }}">{{ old('tujuan_pemanfaatan', $data['tujuan_pemanfaatan'] ?? '') }}</textarea>
            </div>
        </div>
    </section>
    </div>

    {{-- Langkah 2: Penggunaan & Lokasi --}}
    <div data-langkah="2" x-show="! bertahap || langkah === 2" x-cloak>
    <section>
        <div x-show="lahanUsaha">
            <h3 class="{{ $kelasBagian }}">Pengelolaan Lahan Usaha</h3>
            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                Rincian komposisi luas dan komoditas garapan untuk lahan usaha.
            </p>

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
        </div>

        <div x-show="! lahanUsaha" class="rounded-lg bg-gray-50 p-4 text-theme-sm text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
            Bidang ini merupakan lahan pekarangan. Rincian pertanian lahan usaha tidak diperlukan.
        </div>

        <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-800">
            <h3 class="{{ $kelasBagian }}">Titik Lokasi</h3>
            <div class="mt-3">
                <x-sim.koordinat-input :lintang="$data['lintang'] ?? null" :bujur="$data['bujur'] ?? null" />
            </div>
        </div>
    </section>
    </div>

    {{-- Langkah 3: Legalitas & Catatan --}}
    <div data-langkah="3" x-show="! bertahap || langkah === 3" x-cloak>
    <section>
        <h3 class="{{ $kelasBagian }}">Dokumen Status Lahan</h3>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
            Dokumen status hak atas tanah untuk bidang ini. Dokumen tambahan dapat diunggah lewat tab Dokumen di halaman rincian.
        </p>

        <div class="mt-3 grid gap-4 sm:grid-cols-3">
            <div>
                <label for="{{ $awalan }}_jenis_dokumen" class="{{ $kelasLabel }}">Jenis Dokumen</label>
                <select id="{{ $awalan }}_jenis_dokumen" name="jenis_dokumen" class="{{ $kelasKontrol }}">
                    <option value="">Belum ada dokumen</option>
                    @foreach ($opsiJenisDokumenLahan as $nilai => $label)
                        <option value="{{ $nilai }}"
                            @selected(old('jenis_dokumen', $data['jenis_dokumen'] ?? '') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-800">
            <h3 class="{{ $kelasBagian }}">Catatan Tambahan</h3>
            <div class="mt-3">
                <label for="{{ $awalan }}_keterangan" class="{{ $kelasLabel }}">Catatan</label>
                <textarea id="{{ $awalan }}_keterangan" name="keterangan" rows="3" maxlength="1000"
                    placeholder="Catatan tambahan bila ada"
                    class="{{ $kelasArea }}">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
            </div>
        </div>

        <div class="mt-4">
            <x-sim.file-upload nama="file_dokumen" label="Berkas Dokumen"
                nama-dokumen="Dokumen Lahan" :nama-pemilik="$data['kode_lahan'] ?? null"
                :berkas-saat-ini="$data['file_dokumen'] ?? null"
                keterangan="Pindaian sertifikat atau surat keterangan." />
        </div>
    </section>
    </div>
</div>
