{{--
    Isian profil kelompok tani.

    Ketua poktan TIDAK selalu transmigran. Di lapangan banyak poktan diketuai
    penduduk setempat yang bukan peserta program, sehingga membatasi pilihan
    pada daftar transmigran membuat poktan semacam itu tidak dapat didata
    sama sekali. Karena itu isian ini bercabang lebih dulu: bila ketua berasal
    dari transmigran, ia dipilih dari daftar agar NIK dan tautan profilnya
    tetap sahih; bila bukan, nama dan NIK diketik langsung.

    Kontak yang disimpan adalah kontak KETUA, bukan kontak kelompok. Penamaan
    kolomnya menyesuaikan data contoh dan halaman rincian yang sejak awal
    memang memperlakukannya demikian.

    Nama kolom mengikuti agents/data-dictionary.md bagian 8.1.
--}}
@php
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    $daftarSp = DummyData::satuanPermukiman();
    $daftarTransmigran = DummyData::transmigran();

    // Peta id transmigran ke teleponnya, dipakai mengisi kontak ketua secara
    // otomatis di sisi klien. Disusun di sini agar tidak ada permintaan
    // tambahan ke peladen hanya untuk membaca satu nomor.
    $kontakTransmigran = [];
    foreach ($daftarTransmigran as $t) {
        $kontakTransmigran[(string) $t['id_transmigran']] = $t['telepon'] ?? '';
    }
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
        dariTransmigran: {{ old('is_ketua_transmigran', $data['is_ketua_transmigran'] ?? true) ? 'true' : 'false' }},
        ketuaId: '{{ old('ketua_transmigran_id', $data['ketua_transmigran_id'] ?? '') }}',
        telepon: @js(old('telepon_ketua', $data['telepon_ketua'] ?? '')),
        kontakTransmigran: @js($kontakTransmigran),
        {{--
            Telepon terisi sendiri saat ketua dipilih dari daftar, tetapi tetap
            dapat disunting. Petugas kerap memegang nomor yang lebih baru
            daripada yang tercatat pada data transmigran, dan menguncinya akan
            memaksa mereka memperbaiki data transmigran lebih dulu hanya untuk
            menyimpan satu poktan.

            Email tidak ikut terisi sebab tabel transmigran memang tidak
            menyimpannya (data-dictionary.md 6.1).
        --}}
        isiKontak() {
            if (! this.dariTransmigran || this.ketuaId === '') {
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
            <legend class="{{ $kelasLabel }}">Ketua berasal dari data transmigran?<span class="text-error-500">*</span></legend>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-theme-sm text-gray-700 dark:text-gray-300">
                    <input type="radio" name="is_ketua_transmigran" value="1" required
                        x-model.boolean="dariTransmigran"
                        class="h-4 w-4 border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700" />
                    Ya, sudah terdata sebagai transmigran
                </label>
                <label class="inline-flex items-center gap-2 text-theme-sm text-gray-700 dark:text-gray-300">
                    <input type="radio" name="is_ketua_transmigran" value="0" required
                        x-model.boolean="dariTransmigran"
                        class="h-4 w-4 border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700" />
                    Bukan, penduduk setempat
                </label>
            </div>
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Banyak poktan diketuai penduduk setempat yang bukan peserta program transmigrasi.
            </p>
        </fieldset>

        {{-- Jalur 1: ketua sudah terdata sebagai transmigran --}}
        <div class="mt-4" x-show="dariTransmigran">
            {{--
                Memakai @change untuk menyalin nilai ke `ketuaId`, bukan
                `x-model`. Komponen pencarian merender opsinya lewat `x-for`,
                dan `x-model` menyetel ulang nilai select setiap daftar opsi
                berubah, sehingga pilihan petugas hilang begitu ia mengetik di
                kotak pencarian.
            --}}
            <x-sim.pilih-cari nama="ketua_transmigran_id" label="Ketua" :wajib="true"
                :awalan="$awalan" :opsi="$daftarTransmigran" kunci="id_transmigran"
                teks="nama_kepala_keluarga" keterangan-opsi="satuan_permukiman"
                :terpilih="old('ketua_transmigran_id', $data['ketua_transmigran_id'] ?? null)"
                placeholder="Pilih dari daftar transmigran"
                keterangan="Nama dan NIK dibaca dari data transmigran, tidak diketik ulang, agar tidak ada dua versi yang berbeda ejaan."
                @change="ketuaId = $event.target.value; isiKontak()"
                :required="'dariTransmigran'" :disabled="'! dariTransmigran'" />
        </div>

        {{-- Jalur 2: ketua bukan transmigran, nama dan NIK diketik langsung --}}
        <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="! dariTransmigran" x-cloak>
            <div>
                <label for="{{ $awalan }}_nama_ketua" class="{{ $kelasLabel }}">Nama Ketua<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_ketua" name="nama_ketua"
                    value="{{ old('nama_ketua', $data['nama_ketua'] ?? '') }}" maxlength="255"
                    :required="! dariTransmigran" :disabled="dariTransmigran"
                    placeholder="Nama lengkap ketua" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_nik_ketua" class="{{ $kelasLabel }}">NIK Ketua<span class="text-error-500">*</span></label>
                <input type="text" inputmode="numeric" id="{{ $awalan }}_nik_ketua" name="nik_ketua"
                    value="{{ old('nik_ketua', $data['nik_ketua'] ?? '') }}"
                    minlength="16" maxlength="16" pattern="[0-9]{16}"
                    :required="! dariTransmigran" :disabled="dariTransmigran"
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
                    x-show="dariTransmigran">
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
