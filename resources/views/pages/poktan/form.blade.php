{{--
    Isian profil kelompok tani.

    KETUA PUNYA TIGA ASAL-USUL, bukan dua (rules.md 7a poin 2a):

      1. Kepala keluarga  - dipilih dari daftar; nama, NIK, dan telepon dibaca
                            lewat relasi agar tidak ada dua versi data.
      2. Anggota keluarga - keluarganya dipilih dari daftar, lalu ORANGNYA
                            dipilih dari daftar anggota keluarga itu
                            (`ketua_anggota_keluarga_id`). Nama, NIK, dan
                            hubungan dibaca dari baris anggota_keluarga
                            (Stage B2, 2026-08-28; erd.md 7.4 dibalik).
      3. Bukan transmigran - penduduk setempat. Nama, NIK, dan luas lahannya
                            diketik seluruhnya.

    Membatasi pilihan pada daftar transmigran membuat poktan berketua penduduk
    setempat tidak dapat didata sama sekali, sedangkan membatasinya pada kepala
    keluarga membuat poktan berketua istri atau anak tidak dapat didata dengan
    benar. Sebelum 2026-08-20 percabangannya boolean, dan hanya sanggup
    membedakan dua keadaan pertama dari yang ketiga.

    Multi-step form 3 langkah:
    1: Identitas Kelompok
    2: Pengurus & Legalitas
    3: Anggota Kelompok Tani

    Nama kolom mengikuti agents/data-dictionary.md bagian 8.1.
--}}
@php
    use App\Enums\AsalWakilPoktan;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];
    $anggotaPoktanData = $anggotaPoktanData ?? [];

    if (empty($anggotaPoktanData) && ! empty($data['id_poktan'])) {
        $anggotaPoktanData = $anggotaPoktanPerPoktan[(int) $data['id_poktan']] ?? [];
    }

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    $asalKetua = old('asal_ketua', $data['asal_ketua'] ?? AsalWakilPoktan::KepalaKeluarga->value);
@endphp

<div class="space-y-6"
    x-data="{
        asal: @js($asalKetua),
        ketuaId: '{{ old('ketua_transmigran_id', $data['ketua_transmigran_id'] ?? '') }}',
        ketuaAnggotaKeluargaId: '{{ old('ketua_anggota_keluarga_id', $data['ketua_anggota_keluarga_id'] ?? '') }}',
        telepon: @js(old('telepon_ketua', $data['telepon_ketua'] ?? '')),
        kontakTransmigran: @js($kontakTransmigran),
        lahanTransmigran: @js($lahanTransmigran),
        anggotaKeluargaKeluarga: @js($anggotaKeluargaPerKeluarga),
        anggota: @js($anggotaPoktanData),

        get dariKeluarga() {
            return this.asal !== @js(AsalWakilPoktan::BukanTransmigran->value);
        },
        get identitasDariRelasi() {
            return this.asal === @js(AsalWakilPoktan::KepalaKeluarga->value);
        },
        get perluHubungan() {
            return this.asal === @js(AsalWakilPoktan::AnggotaKeluarga->value);
        },
        get ketuaBukanTransmigran() {
            return this.asal === @js(AsalWakilPoktan::BukanTransmigran->value);
        },
        get daftarAnggotaKeluarga() {
            return this.anggotaKeluargaKeluarga[this.ketuaId] ?? [];
        },
        gantiKeluarga(id) {
            this.ketuaId = id;
            this.ketuaAnggotaKeluargaId = '';
            this.isiKontak();
        },
        get lahanKeluarga() {
            return this.lahanTransmigran[this.ketuaId] ?? null;
        },
        isiKontak() {
            if (! this.identitasDariRelasi || this.ketuaId === '') {
                return;
            }
            const kontak = this.kontakTransmigran[this.ketuaId];
            if (kontak && this.telepon === '') {
                this.telepon = kontak;
            }
        },
        tambahAnggota() {
            this.anggota.push({
                transmigran_id: '',
                jabatan: 'Anggota',
                keterangan: '',
            });
        },
        hapusAnggota(i) {
            this.anggota.splice(i, 1);
        },
    }">

    {{-- Langkah 1: Identitas Kelompok --}}
    <div data-langkah="1" x-show="! bertahap || langkah === 1" x-cloak>
        <div class="space-y-6">
            <section>
                <h3 class="{{ $kelasBagian }}">Identitas Kelompok Tani</h3>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="{{ $awalan }}_sp_poktan" class="{{ $kelasLabel }}">Satuan Permukiman<span class="text-error-500">*</span></label>
                        <select id="{{ $awalan }}_sp_poktan" name="satuan_permukiman_id" required class="{{ $kelasKontrol }}">
                            <option value="">Pilih satuan permukiman</option>
                            @foreach ($daftarSp as $sp)
                                <option value="{{ $sp['id_satuan_permukiman'] }}"
                                    @selected((string) old('satuan_permukiman_id', $data['satuan_permukiman_id'] ?? '') === (string) $sp['id_satuan_permukiman'])>
                                    {{ $sp['nama'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="{{ $awalan }}_nama_poktan" class="{{ $kelasLabel }}">Nama Kelompok Tani<span class="text-error-500">*</span></label>
                        <input type="text" id="{{ $awalan }}_nama_poktan" name="nama" required
                            value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                            placeholder="Contoh: POKTAN MEKAR JAYA" class="{{ $kelasKontrol }}" />
                    </div>

                    <div>
                        <label for="{{ $awalan }}_tahun_berdiri" class="{{ $kelasLabel }}">Tahun Berdiri</label>
                        <input type="number" id="{{ $awalan }}_tahun_berdiri" name="tahun_berdiri"
                            value="{{ old('tahun_berdiri', $data['tahun_berdiri'] ?? '') }}" min="1950"
                            max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
                    </div>
                </div>
            </section>

            <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                <h3 class="{{ $kelasBagian }}">Alamat dan Titik Lokasi</h3>
                <div class="mt-3 space-y-4">
                    <div>
                        <label for="{{ $awalan }}_alamat_ketua" class="{{ $kelasLabel }}">Alamat Ketua atau Sekretariat</label>
                        <textarea id="{{ $awalan }}_alamat_ketua" name="alamat_ketua" rows="2" maxlength="255"
                            placeholder="Alamat ketua, atau tempat pertemuan kelompok bila ada."
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('alamat_ketua', $data['alamat_ketua'] ?? '') }}</textarea>
                    </div>

                    <x-sim.koordinat-input :lintang="old('lintang', $data['lintang'] ?? null)"
                        :bujur="old('bujur', $data['bujur'] ?? null)" />
                </div>
            </section>
        </div>
    </div>

    {{-- Langkah 2: Pengurus & Legalitas --}}
    <div data-langkah="2" x-show="! bertahap || langkah === 2" x-cloak>
        <div class="space-y-6">
            <section>
                <h3 class="{{ $kelasBagian }}">Ketua Kelompok</h3>

                <fieldset class="mt-3">
                    <legend class="{{ $kelasLabel }}">Ketua berasal dari<span class="text-error-500">*</span></legend>
                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:gap-4">
                        @foreach (AsalWakilPoktan::cases() as $asal)
                            <label class="inline-flex items-center gap-2 text-theme-sm text-gray-700 dark:text-gray-300">
                                <input type="radio" name="asal_ketua" required value="{{ $asal->value }}"
                                    x-model="asal" @change="isiKontak()"
                                    class="h-4 w-4 border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700" />
                                {{ $asal->label() }}
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        Ketua boleh anggota keluarga transmigran yang bukan kepala keluarga, misalnya bila
                        kepala keluarga merantau. Banyak pula poktan diketuai penduduk setempat yang bukan
                        peserta program.
                    </p>
                </fieldset>

                {{-- Keluarga yang diwakili. Terisi pada dua jalur pertama. --}}
                <div class="mt-4" x-show="dariKeluarga">
                    <x-sim.pilih-cari nama="ketua_transmigran_id" label="Keluarga Transmigran" :wajib="true"
                        :awalan="$awalan" :opsi="$daftarTransmigran" kunci="id_transmigran"
                        teks="nama_kepala_keluarga" keterangan-opsi="satuan_permukiman"
                        :terpilih="old('ketua_transmigran_id', $data['ketua_transmigran_id'] ?? null)"
                        placeholder="Pilih keluarga transmigran"
                        keterangan="Keluarga yang diwakili ketua. Luas lahan dan titik koordinatnya dibaca dari bidang milik keluarga ini."
                        @change="gantiKeluarga($event.target.value)"
                        :required="'dariKeluarga'" :disabled="'! dariKeluarga'" />
                </div>

                {{-- Jalur Anggota Keluarga: ketua DIPILIH dari daftar anggota keluarga --}}
                <div class="mt-4" x-show="perluHubungan" x-cloak>
                    <label for="{{ $awalan }}_ketua_anggota_keluarga_id" class="{{ $kelasLabel }}">
                        Anggota yang Menjadi Ketua<span class="text-error-500">*</span>
                    </label>
                    <select id="{{ $awalan }}_ketua_anggota_keluarga_id" name="ketua_anggota_keluarga_id"
                        x-model="ketuaAnggotaKeluargaId"
                        :required="perluHubungan" :disabled="! perluHubungan || ketuaId === ''"
                        class="{{ $kelasKontrol }}">
                        <option value="">Pilih anggota keluarga</option>
                        <template x-for="a in daftarAnggotaKeluarga" :key="a.id">
                            <option :value="a.id" x-text="a.nama + ' (' + a.hubungan + ')'"></option>
                        </template>
                    </select>
                    <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        <span x-show="ketuaId !== '' && daftarAnggotaKeluarga.length === 0" x-cloak>
                            Keluarga ini belum memiliki anggota keluarga terdata. Tambahkan lebih dulu lewat modul Transmigran.
                        </span>
                        <span x-show="daftarAnggotaKeluarga.length > 0">
                            Daftar diambil dari anggota keluarga yang sudah dicatat pada data transmigran.
                        </span>
                    </p>
                </div>

                {{-- Jalur Bukan Transmigran --}}
                <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="ketuaBukanTransmigran" x-cloak>
                    <div>
                        <label for="{{ $awalan }}_nama_ketua" class="{{ $kelasLabel }}">Nama Ketua<span class="text-error-500">*</span></label>
                        <input type="text" id="{{ $awalan }}_nama_ketua" name="nama_ketua"
                            value="{{ old('nama_ketua', $data['nama_ketua'] ?? '') }}" maxlength="255"
                            :required="ketuaBukanTransmigran" :disabled="! ketuaBukanTransmigran"
                            placeholder="Nama lengkap ketua" class="{{ $kelasKontrol }}" />
                    </div>

                    <div>
                        <label for="{{ $awalan }}_nik_ketua" class="{{ $kelasLabel }}">NIK Ketua<span class="text-error-500">*</span></label>
                        <input type="text" inputmode="numeric" id="{{ $awalan }}_nik_ketua" name="nik_ketua"
                            value="{{ old('nik_ketua', $data['nik_ketua'] ?? '') }}"
                            minlength="16" maxlength="16" pattern="[0-9]{16}"
                            :required="ketuaBukanTransmigran" :disabled="! ketuaBukanTransmigran"
                            placeholder="16 digit angka" class="{{ $kelasKontrol }} tabular-nums" />
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="{{ $awalan }}_telepon_ketua" class="{{ $kelasLabel }}">Telepon Ketua</label>
                        <input type="tel" id="{{ $awalan }}_telepon_ketua" name="telepon_ketua"
                            x-model="telepon" maxlength="20"
                            placeholder="0812xxxxxxx" class="{{ $kelasKontrol }} tabular-nums" />
                        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400"
                            x-show="identitasDariRelasi">
                            Terisi sendiri dari data transmigran, dan tetap dapat diperbarui bila nomornya sudah berganti.
                        </p>
                    </div>

                    <div>
                        <label for="{{ $awalan }}_email_ketua" class="{{ $kelasLabel }}">Email Ketua</label>
                        <input type="email" id="{{ $awalan }}_email_ketua" name="email_ketua"
                            value="{{ old('email_ketua', $data['email_ketua'] ?? '') }}" maxlength="255"
                            placeholder="nama@example.id" class="{{ $kelasKontrol }}" />
                    </div>
                </div>

                {{-- Luas lahan ketua --}}
                <div class="mt-4" x-show="dariKeluarga" x-cloak>
                    <span class="{{ $kelasLabel }}">Luas Lahan Usaha Ketua</span>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                        <template x-if="lahanKeluarga && lahanKeluarga.jumlah_bidang > 0">
                            <div class="flex flex-wrap gap-x-8 gap-y-2 text-theme-sm">
                                <span class="text-gray-600 dark:text-gray-400">
                                    Kering
                                    <span class="ml-1 font-medium tabular-nums text-gray-800 dark:text-white/90"
                                        x-text="Number(lahanKeluarga.kering).toFixed(2) + ' ha'"></span>
                                </span>
                                <span class="text-gray-600 dark:text-gray-400">
                                    Basah
                                    <span class="ml-1 font-medium tabular-nums text-gray-800 dark:text-white/90"
                                        x-text="Number(lahanKeluarga.basah).toFixed(2) + ' ha'"></span>
                                </span>
                                <span class="text-gray-600 dark:text-gray-400">
                                    Titik koordinat
                                    <span class="ml-1 font-medium tabular-nums text-gray-800 dark:text-white/90"
                                        x-text="lahanKeluarga.lintang !== null
                                            ? Number(lahanKeluarga.lintang).toFixed(6) + ', ' + Number(lahanKeluarga.bujur).toFixed(6)
                                            : 'belum ada'"></span>
                                </span>
                            </div>
                        </template>
                        <template x-if="! lahanKeluarga || lahanKeluarga.jumlah_bidang === 0">
                            <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                                <span x-show="ketuaId === ''">Pilih keluarga transmigran lebih dulu.</span>
                                <span x-show="ketuaId !== ''" x-cloak>
                                    Keluarga ini belum memiliki lahan usaha terdata.
                                </span>
                            </p>
                        </template>
                    </div>
                    <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        Dijumlahkan dari bidang milik keluarga tersebut, sehingga selalu mengikuti data lahan terbaru.
                    </p>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="! dariKeluarga" x-cloak>
                    <div>
                        <label for="{{ $awalan }}_luas_kering_ketua" class="{{ $kelasLabel }}">Luas Lahan Kering Ketua</label>
                        <div class="relative">
                            <input type="number" id="{{ $awalan }}_luas_kering_ketua" name="luas_kering_ketua"
                                value="{{ old('luas_kering_ketua', $data['luas_kering_ketua'] ?? '') }}"
                                min="0" step="0.01" :disabled="dariKeluarga"
                                placeholder="0.80" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                            <span
                                class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                                ha
                            </span>
                        </div>
                    </div>

                    <div>
                        <label for="{{ $awalan }}_luas_basah_ketua" class="{{ $kelasLabel }}">Luas Lahan Basah Ketua</label>
                        <div class="relative">
                            <input type="number" id="{{ $awalan }}_luas_basah_ketua" name="luas_basah_ketua"
                                value="{{ old('luas_basah_ketua', $data['luas_basah_ketua'] ?? '') }}"
                                min="0" step="0.01" :disabled="dariKeluarga"
                                placeholder="0.20" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                            <span
                                class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                                ha
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                <h3 class="{{ $kelasBagian }}">Dokumen Legalitas & Catatan</h3>
                <div class="mt-3 space-y-4">
                    <div>
                        <label for="{{ $awalan }}_keterangan" class="{{ $kelasLabel }}">Catatan</label>
                        <textarea id="{{ $awalan }}_keterangan" name="keterangan" rows="3" maxlength="1000"
                            placeholder="Contoh: kelompok aktif mengikuti pelatihan penyuluh."
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
                    </div>

                    <x-sim.file-upload nama="dokumen_pendukung" label="SK Pembentukan Kelompok"
                        nama-dokumen="SK Poktan" :nama-pemilik="$data['nama'] ?? null"
                        :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                        keterangan="Surat keputusan pembentukan atau pengukuhan kelompok tani." />
                </div>
            </section>
        </div>
    </div>

    {{-- Langkah 3: Anggota Kelompok Tani --}}
    <div data-langkah="3" x-show="! bertahap || langkah === 3" x-cloak>
        {{--
            Penanda bahwa form ini benar-benar memuat daftar anggota. Modal
            "Ubah" per baris di halaman daftar meng-include form yang sama
            tanpa data anggota, sehingga menyimpannya tidak menambah anggota.
        --}}
        @if ($awalan !== 'ubahBaris')
            <input type="hidden" name="_anggota_disunting" value="1" />
        @endif
        <section>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="{{ $kelasBagian }}">Anggota Kelompok Tani</h3>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        Daftar anggota transmigran yang bernaung di kelompok tani ini.
                    </p>
                </div>
                <button type="button" @click="tambahAnggota()"
                    class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    Tambah Anggota
                </button>
            </div>

            <p x-show="anggota.length === 0"
                class="mt-4 rounded-lg bg-gray-50 px-4 py-6 text-center text-theme-sm text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                Belum ada anggota ditambahkan. Klik tombol di atas untuk menambah anggota kelompok tani.
            </p>

            <template x-for="(a, i) in anggota" :key="i">
                <fieldset class="mt-4 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <legend class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Anggota <span x-text="i + 1"></span>
                        </legend>
                        <button type="button" @click="hapusAnggota(i)"
                            class="rounded p-1 text-gray-400 transition hover:text-error-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500"
                            :aria-label="'Hapus anggota ' + (i + 1)">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ang_transmigran_' + i">Keluarga Transmigran<span class="text-error-500">*</span></label>
                            <select :id="'{{ $awalan }}_ang_transmigran_' + i" :name="`anggota[${i}][transmigran_id]`"
                                x-model="a.transmigran_id" required class="{{ $kelasKontrol }}">
                                <option value="">Pilih keluarga transmigran</option>
                                @foreach ($daftarTransmigran as $t)
                                    <option value="{{ $t['id_transmigran'] }}">
                                        {{ $t['nama_kepala_keluarga'] }} ({{ $t['satuan_permukiman'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ang_jabatan_' + i">Jabatan<span class="text-error-500">*</span></label>
                            <select :id="'{{ $awalan }}_ang_jabatan_' + i" :name="`anggota[${i}][jabatan]`"
                                x-model="a.jabatan" required class="{{ $kelasKontrol }}">
                                @foreach ($opsiJabatanAnggota as $nilaiJabatan => $labelJabatan)
                                    <option value="{{ $nilaiJabatan }}">{{ $labelJabatan }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ang_ket_' + i">Catatan</label>
                            <input type="text" :id="'{{ $awalan }}_ang_ket_' + i" :name="`anggota[${i}][keterangan]`"
                                x-model="a.keterangan" maxlength="255" placeholder="Keterangan peran atau tugas khusus" class="{{ $kelasKontrol }}" />
                        </div>
                    </div>
                </fieldset>
            </template>
        </section>
    </div>
</div>
