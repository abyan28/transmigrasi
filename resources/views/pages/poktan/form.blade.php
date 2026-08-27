{{--
    Isian profil kelompok tani.

    KETUA PUNYA TIGA ASAL-USUL, bukan dua (rules.md 7a poin 2a):

      1. Kepala keluarga  - dipilih dari daftar; nama, NIK, dan telepon dibaca
                            lewat relasi agar tidak ada dua versi data.
      2. Anggota keluarga - keluarganya dipilih dari daftar, tetapi nama, NIK,
                            dan hubungannya WAJIB diketik. Sistem tidak mendata
                            anggota keluarga satu per satu (erd.md 7.4),
                            sehingga tidak ada relasi yang dapat dibaca.
      3. Bukan transmigran - penduduk setempat. Nama, NIK, dan luas lahannya
                            diketik seluruhnya.

    Membatasi pilihan pada daftar transmigran membuat poktan berketua penduduk
    setempat tidak dapat didata sama sekali, sedangkan membatasinya pada kepala
    keluarga membuat poktan berketua istri atau anak tidak dapat didata dengan
    benar. Sebelum 2026-08-20 percabangannya boolean, dan hanya sanggup
    membedakan dua keadaan pertama dari yang ketiga.

    LUAS LAHAN DAN KOORDINAT DITURUNKAN, TIDAK DIKETIK - kecuali jalur ketiga.
    Keduanya dijumlahkan dari bidang milik keluarga yang bersangkutan, sehingga
    tidak pernah basi ketika petugas membetulkan luas di modul lahan.

    Kontak yang disimpan adalah kontak KETUA, bukan kontak kelompok. Penamaan
    kolomnya menyesuaikan data contoh dan halaman rincian yang sejak awal
    memang memperlakukannya demikian.

    Nama kolom mengikuti agents/data-dictionary.md bagian 8.1.
--}}
@php
    use App\Enums\AsalWakilPoktan;
    use App\Enums\HubunganKeluarga;
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    // `$daftarSp`, `$daftarTransmigran`, `$kontakTransmigran`, dan
    // `$lahanTransmigran` disuplai ViewServiceProvider. Kedua peta terakhir
    // dahulu disusun di sini lewat perulangan yang memanggil
    // rekapLahanKeluarga() untuk setiap keluarga, dan perulangan yang sama
    // ditulis ulang di `form-anggota`.

    $asalKetua = old('asal_ketua', $data['asal_ketua'] ?? AsalWakilPoktan::KepalaKeluarga->value);
@endphp

<div class="space-y-6">
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Kelompok</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_nama_poktan" class="{{ $kelasLabel }}">Nama Kelompok Tani<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_poktan" name="nama" required
                    value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: POKTAN MEKAR JAYA" class="{{ $kelasKontrol }}" />
            </div>

            <div>
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
                <label for="{{ $awalan }}_tahun_berdiri" class="{{ $kelasLabel }}">Tahun Berdiri</label>
                <input type="number" id="{{ $awalan }}_tahun_berdiri" name="tahun_berdiri"
                    value="{{ old('tahun_berdiri', $data['tahun_berdiri'] ?? '') }}" min="1950"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
            </div>
        </div>
    </section>

    <section x-data="{
        asal: @js($asalKetua),
        ketuaId: '{{ old('ketua_transmigran_id', $data['ketua_transmigran_id'] ?? '') }}',
        telepon: @js(old('telepon_ketua', $data['telepon_ketua'] ?? '')),
        kontakTransmigran: @js($kontakTransmigran),
        lahanTransmigran: @js($lahanTransmigran),

        {{--
            Dua penurunan yang perlu dibedakan:

            - `dariKeluarga` menentukan apakah luas lahan dan koordinat dapat
              dibaca dari bidang milik keluarga. Berlaku bagi dua jalur pertama.
            - `identitasDariRelasi` menentukan apakah nama dan NIK dapat dibaca
              lewat relasi. Hanya berlaku bagi kepala keluarga, sebab anggota
              keluarga tidak punya baris yang dapat dibaca.

            Menyatukan keduanya menjadi satu penanda adalah kekeliruan yang
            membuat jalur kedua mustahil dilayani.
        --}}
        get dariKeluarga() {
            return this.asal !== @js(AsalWakilPoktan::BukanTransmigran->value);
        },
        get identitasDariRelasi() {
            return this.asal === @js(AsalWakilPoktan::KepalaKeluarga->value);
        },
        get perluHubungan() {
            return this.asal === @js(AsalWakilPoktan::AnggotaKeluarga->value);
        },
        get lahanKeluarga() {
            return this.lahanTransmigran[this.ketuaId] ?? null;
        },

        {{--
            Telepon terisi sendiri saat keluarga dipilih dari daftar, tetapi
            tetap dapat disunting. Petugas kerap memegang nomor yang lebih baru
            daripada yang tercatat pada data transmigran, dan menguncinya akan
            memaksa mereka memperbaiki data transmigran lebih dulu hanya untuk
            menyimpan satu poktan.

            Hanya diisi pada jalur kepala keluarga: nomor pada tabel transmigran
            adalah nomor kepala keluarganya, bukan nomor anggota keluarga yang
            mewakili.

            Email tidak ikut terisi sebab tabel transmigran memang tidak
            menyimpannya (data-dictionary.md 6.1).
        --}}
        isiKontak() {
            if (! this.identitasDariRelasi || this.ketuaId === '') {
                return;
            }
            const kontak = this.kontakTransmigran[this.ketuaId];
            if (kontak && this.telepon === '') {
                this.telepon = kontak;
            }
        },
    }">
        <h3 class="{{ $kelasBagian }}">Ketua Kelompok</h3>

        <fieldset class="mt-3">
            <legend class="{{ $kelasLabel }}">Ketua berasal dari<span class="text-error-500">*</span></legend>
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:gap-4">
                @foreach (AsalWakilPoktan::cases() as $asal)
                    <label class="inline-flex items-center gap-2 text-theme-sm text-gray-700 dark:text-gray-300">
                        {{-- `required` ditulis sebelum `value`: uji penjaga isian wajib
                             mencocokkan atribut sesudah `name`, dan sintaks panah pada
                             `{{ $asal->value }}` memutus pencocokannya. --}}
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
            {{--
                Memakai @change untuk menyalin nilai ke `ketuaId`, bukan
                `x-model`. Komponen pencarian merender opsinya lewat `x-for`,
                dan `x-model` menyetel ulang nilai select setiap daftar opsi
                berubah, sehingga pilihan petugas hilang begitu ia mengetik di
                kotak pencarian.
            --}}
            <x-sim.pilih-cari nama="ketua_transmigran_id" label="Keluarga Transmigran" :wajib="true"
                :awalan="$awalan" :opsi="$daftarTransmigran" kunci="id_transmigran"
                teks="nama_kepala_keluarga" keterangan-opsi="satuan_permukiman"
                :terpilih="old('ketua_transmigran_id', $data['ketua_transmigran_id'] ?? null)"
                placeholder="Pilih keluarga transmigran"
                keterangan="Keluarga yang diwakili ketua. Luas lahan dan titik koordinatnya dibaca dari bidang milik keluarga ini."
                @change="ketuaId = $event.target.value; isiKontak()"
                {{-- Ekspresi Alpine dikirim sebagai STRING berkutip: Blade
                     mengevaluasi `:atribut` sebagai PHP, sehingga tanpa kutip
                     `dariKeluarga` terbaca sebagai konstanta PHP yang tidak ada. --}}
                :required="'dariKeluarga'" :disabled="'! dariKeluarga'" />
        </div>

        {{--
            Nama dan NIK diketik pada dua jalur terakhir. Kepala keluarga
            dikecualikan sebab keduanya dibaca lewat relasi; menyalinnya akan
            melahirkan dua versi data yang berpotensi tidak sinkron.
        --}}
        <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="! identitasDariRelasi" x-cloak>
            <div>
                <label for="{{ $awalan }}_nama_ketua" class="{{ $kelasLabel }}">Nama Ketua<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_ketua" name="nama_ketua"
                    value="{{ old('nama_ketua', $data['nama_ketua'] ?? '') }}" maxlength="255"
                    :required="! identitasDariRelasi" :disabled="identitasDariRelasi"
                    placeholder="Nama lengkap ketua" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_nik_ketua" class="{{ $kelasLabel }}">NIK Ketua<span class="text-error-500">*</span></label>
                <input type="text" inputmode="numeric" id="{{ $awalan }}_nik_ketua" name="nik_ketua"
                    value="{{ old('nik_ketua', $data['nik_ketua'] ?? '') }}"
                    minlength="16" maxlength="16" pattern="[0-9]{16}"
                    :required="! identitasDariRelasi" :disabled="identitasDariRelasi"
                    placeholder="16 digit angka" class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            {{-- Hubungan hanya berlaku bagi anggota keluarga. --}}
            <div x-show="perluHubungan" x-cloak>
                <label for="{{ $awalan }}_hubungan_ketua" class="{{ $kelasLabel }}">
                    Hubungan dengan Kepala Keluarga<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_hubungan_ketua" name="hubungan_ketua"
                    :required="perluHubungan" :disabled="! perluHubungan" class="{{ $kelasKontrol }}">
                    <option value="">Pilih hubungan</option>
                    @foreach (HubunganKeluarga::opsi() as $nilai => $label)
                        <option value="{{ $nilai }}"
                            @selected(old('hubungan_ketua', $data['hubungan_ketua'] ?? '') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
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

        {{--
            Luas lahan ketua. Dua jalur pertama menampilkannya sebagai BACAAN
            hasil penjumlahan bidang milik keluarga; hanya jalur ketiga yang
            mengetiknya, sebab lahannya memang tidak terdata pada tabel lahan.

            Menyimpannya sebagai kolom pada kedua jalur pertama akan basi begitu
            petugas membetulkan luas di modul lahan, kekeliruan yang sama dengan
            `jumlah_anggota` yang sudah dicabut (erd.md 7.3).
        --}}
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
                Dijumlahkan dari bidang milik keluarga tersebut, sehingga selalu mengikuti data lahan
                terbaru. Perbaikannya dilakukan di modul Lahan, bukan di sini.
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
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Diketik sebab lahan penduduk setempat tidak terdata pada modul Lahan.
                </p>
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

    <section>
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

    {{--
        Catatan. Kolom `keterangan` sudah lama ada pada kamus data 8.1 tetapi
        belum pernah punya isian, sehingga hal-hal yang tidak tertampung kolom
        baku tidak dapat dicatat ke mana pun.

        Labelnya "Catatan", diseragamkan 2026-08-20 dari empat penamaan
        berbeda yang sempat dipakai bergantian.
    --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Catatan</h3>
        <div class="mt-3">
            <label for="{{ $awalan }}_keterangan" class="{{ $kelasLabel }}">Catatan</label>
            <textarea id="{{ $awalan }}_keterangan" name="keterangan" rows="3" maxlength="1000"
                placeholder="Contoh: kelompok aktif mengikuti pelatihan penyuluh."
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
        </div>
    </section>
    {{--
        Dokumen pendukung. Kolomnya sudah lama ada pada data-dictionary.md 8.1
        dengan keterangan "SK pembentukan", tetapi belum pernah punya isian.
    --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Dokumen Pendukung</h3>
        <div class="mt-3">
            <x-sim.file-upload nama="dokumen_pendukung" label="SK Pembentukan Kelompok"
                nama-dokumen="SK Poktan" :nama-pemilik="$data['nama'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                keterangan="Surat keputusan pembentukan atau pengukuhan kelompok tani." />
        </div>
    </section>
</div>