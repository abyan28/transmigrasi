{{--
    Isian keanggotaan kelompok tani.

    Aturan yang dijaga di sini: anggota yang berhenti DITANDAI Sudah Keluar,
    bukan dihapus (agents/rules.md bagian 5.1 catatan 5). Riwayat keanggotaan
    harus tetap utuh, sebab penyaluran saprotan di masa lalu menaut pada nama
    yang bersangkutan. Menghapusnya membuat catatan penyaluran kehilangan
    penerima.

    Karena itu form ini tidak menyediakan opsi hapus. Yang tersedia hanya
    perubahan status beserta tanggal keluar dan alasannya.

    KEANGGOTAAN MELEKAT PADA KELUARGA, BUKAN PADA KEPALA KELUARGA (rules.md 7a
    poin 3a). Yang terdaftar adalah orang yang benar-benar menggarap dan
    menghadiri pertemuan, dan ia tidak selalu kepala keluarga: bila kepala
    keluarga merantau, istri atau anaknya yang mewakili.

    Karena itu `transmigran_id` menunjuk KELUARGA yang diwakili, sedangkan
    `asal_wakil` menyatakan siapa wakilnya. Berbeda dari ketua, anggota TIDAK
    boleh berasal dari penduduk setempat: seluruh anggota wajib berasal dari
    keluarga transmigran.

    Luas lahan dan koordinat wakil DITURUNKAN dari bidang milik keluarga yang
    bersangkutan, tidak pernah diketik maupun disimpan.

    Nama kolom mengikuti agents/data-dictionary.md bagian 8.2.
--}}
@php
    use App\Enums\AsalWakilPoktan;
    use App\Enums\HubunganKeluarga;
        use App\Enums\StatusKeaktifanAnggota;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];
    $poktanId = $poktanId ?? null;

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasArea = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';

    // `$daftarTransmigran`, `$kontakTransmigran`, `$lahanTransmigran`, dan
    // `$opsiJabatanAnggota` disuplai ViewServiceProvider.
    $keluar = StatusKeaktifanAnggota::SudahKeluar->value;

    $kepalaKeluarga = AsalWakilPoktan::KepalaKeluarga->value;
    $anggotaKeluarga = AsalWakilPoktan::AnggotaKeluarga->value;
@endphp

<div class="space-y-6"
    x-data="{
        status: @js(old('status', $data['status'] ?? StatusKeaktifanAnggota::Aktif->value)),
        asal: @js(old('asal_wakil', $data['asal_wakil'] ?? $kepalaKeluarga)),
        keluargaId: '{{ old('transmigran_id', $data['transmigran_id'] ?? '') }}',
        telepon: @js(old('telepon_wakil', $data['telepon_wakil'] ?? '')),
        kontakTransmigran: @js($kontakTransmigran),
        lahanTransmigran: @js($lahanTransmigran),

        get wakilAnggotaKeluarga() {
            return this.asal === @js($anggotaKeluarga);
        },
        get lahanKeluarga() {
            return this.lahanTransmigran[this.keluargaId] ?? null;
        },

        {{--
            Telepon hanya terisi sendiri pada jalur kepala keluarga: nomor pada
            tabel transmigran adalah nomor kepala keluarganya, bukan nomor
            anggota keluarga yang mewakili.
        --}}
        isiKontak() {
            if (this.wakilAnggotaKeluarga || this.keluargaId === '') {
                return;
            }
            const kontak = this.kontakTransmigran[this.keluargaId];
            if (kontak && this.telepon === '') {
                this.telepon = kontak;
            }
        },
    }">

    @if ($poktanId)
        <input type="hidden" name="poktan_id" value="{{ $poktanId }}" />
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-sim.pilih-cari nama="transmigran_id" label="Keluarga Transmigran" :wajib="true"
                :awalan="$awalan" :opsi="$daftarTransmigran" kunci="id_transmigran"
                teks="nama_kepala_keluarga" keterangan-opsi="satuan_permukiman"
                :terpilih="old('transmigran_id', $data['transmigran_id'] ?? null)"
                placeholder="Pilih keluarga transmigran"
                keterangan="Keanggotaan melekat pada keluarga. Luas lahan dan titik koordinatnya dibaca dari bidang milik keluarga ini."
                @change="keluargaId = $event.target.value; isiKontak()" />
        </div>

        {{--
            Wakil keluarga. Hanya dua nilai: seluruh anggota wajib berasal dari
            keluarga transmigran, sehingga `Bukan Transmigran` tidak ditawarkan
            sama sekali (rules.md 7a poin 3).
        --}}
        <div class="sm:col-span-2">
            <fieldset>
                <legend class="{{ $kelasLabel }}">Yang mewakili keluarga<span class="text-error-500">*</span></legend>
                <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                    @foreach (AsalWakilPoktan::nilaiAnggota() as $nilai)
                        <label class="inline-flex items-center gap-2 text-theme-sm text-gray-700 dark:text-gray-300">
                            <input type="radio" name="asal_wakil" value="{{ $nilai }}" required
                                x-model="asal" @change="isiKontak()"
                                class="h-4 w-4 border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700" />
                            {{ $nilai }}
                        </label>
                    @endforeach
                </div>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Pilih Anggota Keluarga bila yang menggarap dan menghadiri pertemuan bukan kepala
                    keluarganya, misalnya karena kepala keluarga merantau.
                </p>
            </fieldset>
        </div>

        {{--
            Identitas wakil, diketik hanya bila wakilnya bukan kepala keluarga.
            Sistem tidak mendata anggota keluarga satu per satu (erd.md 7.4),
            sehingga tidak ada relasi yang dapat dibaca.
        --}}
        <div class="grid gap-4 sm:col-span-2 sm:grid-cols-3" x-show="wakilAnggotaKeluarga" x-cloak>
            <div>
                <label for="{{ $awalan }}_nama_wakil" class="{{ $kelasLabel }}">Nama Wakil<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_wakil" name="nama_wakil"
                    value="{{ old('nama_wakil', $data['nama_wakil'] ?? '') }}" maxlength="255"
                    :required="wakilAnggotaKeluarga" :disabled="! wakilAnggotaKeluarga"
                    placeholder="Nama lengkap" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_nik_wakil" class="{{ $kelasLabel }}">NIK Wakil<span class="text-error-500">*</span></label>
                <input type="text" inputmode="numeric" id="{{ $awalan }}_nik_wakil" name="nik_wakil"
                    value="{{ old('nik_wakil', $data['nik_wakil'] ?? '') }}"
                    minlength="16" maxlength="16" pattern="[0-9]{16}"
                    :required="wakilAnggotaKeluarga" :disabled="! wakilAnggotaKeluarga"
                    placeholder="16 digit angka" class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_hubungan_dengan_kk" class="{{ $kelasLabel }}">
                    Hubungan dengan KK<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_hubungan_dengan_kk" name="hubungan_dengan_kk"
                    :required="wakilAnggotaKeluarga" :disabled="! wakilAnggotaKeluarga"
                    class="{{ $kelasKontrol }}">
                    <option value="">Pilih hubungan</option>
                    @foreach (HubunganKeluarga::opsi() as $nilai => $label)
                        <option value="{{ $nilai }}"
                            @selected(old('hubungan_dengan_kk', $data['hubungan_dengan_kk'] ?? '') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="sm:col-span-2">
            <label for="{{ $awalan }}_telepon_wakil" class="{{ $kelasLabel }}">Telepon Wakil</label>
            <input type="tel" id="{{ $awalan }}_telepon_wakil" name="telepon_wakil"
                x-model="telepon" maxlength="20"
                placeholder="0812xxxxxxx" class="{{ $kelasKontrol }} tabular-nums" />
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400"
                x-show="! wakilAnggotaKeluarga">
                Terisi sendiri dari data transmigran, dan tetap dapat diperbarui bila nomornya sudah berganti.
            </p>
        </div>

        {{--
            Luas lahan sebagai BACAAN, bukan isian. Menyimpannya akan basi
            begitu petugas membetulkan luas di modul lahan, dan tidak boleh
            berubah hanya karena wakilnya berganti.
        --}}
        <div class="sm:col-span-2">
            <span class="{{ $kelasLabel }}">Luas Lahan Usaha Keluarga</span>
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
                        <span x-show="keluargaId === ''">Pilih keluarga transmigran lebih dulu.</span>
                        <span x-show="keluargaId !== ''" x-cloak>
                            Keluarga ini belum memiliki lahan usaha terdata.
                        </span>
                    </p>
                </template>
            </div>
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Dijumlahkan dari bidang milik keluarga tersebut. Perbaikannya dilakukan di modul Lahan.
            </p>
        </div>

        <div>
            <label for="{{ $awalan }}_jabatan_anggota" class="{{ $kelasLabel }}">Jabatan</label>
            <select id="{{ $awalan }}_jabatan_anggota" name="jabatan" class="{{ $kelasKontrol }}">
                @foreach ($opsiJabatanAnggota as $nilaiJab => $labelJab)
                    <option value="{{ $nilaiJab }}" @selected(old('jabatan', $data['jabatan'] ?? '') === $nilaiJab)>
                        {{ $labelJab }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="{{ $awalan }}_tanggal_masuk_anggota" class="{{ $kelasLabel }}">Tanggal Masuk<span class="text-error-500">*</span></label>
            <input type="date" id="{{ $awalan }}_tanggal_masuk_anggota" name="tanggal_masuk" required
                value="{{ old('tanggal_masuk', $data['tanggal_masuk'] ?? '') }}" max="{{ date('Y-m-d') }}"
                class="{{ $kelasKontrol }}" />
        </div>

        <div class="sm:col-span-2">
            <label for="{{ $awalan }}_status_anggota" class="{{ $kelasLabel }}">Status Keaktifan</label>
            {{--
                Memakai @change, bukan x-model. Modal ubah mengisi isian ini
                dengan menyetel `.value` secara langsung, dan x-model akan
                menimpanya kembali dengan nilai awal Alpine sehingga bagian
                tanggal keluar tidak pernah muncul untuk anggota yang memang
                sudah berstatus Sudah Keluar.

                `x-init` menyelaraskan keadaan awal untuk kasus yang sama.
            --}}
            <select id="{{ $awalan }}_status_anggota" name="status"
                x-init="status = $el.value" @change="status = $event.target.value"
                class="{{ $kelasKontrol }}">
                @foreach (StatusKeaktifanAnggota::cases() as $s)
                    <option value="{{ $s->value }}"
                        @selected(old('status', $data['status'] ?? StatusKeaktifanAnggota::Aktif->value) === $s->value)>
                        {{ $s->value }}</option>
                @endforeach
            </select>
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Hanya anggota berstatus Aktif yang dapat menerima penyaluran saprotan.
            </p>
        </div>
    </div>

    {{--
        Tanggal dan alasan keluar. Muncul hanya bila status Sudah Keluar,
        sebab mengisinya untuk anggota aktif akan bertentangan sendiri.
    --}}
    <div x-show="status === @js($keluar)" x-cloak x-transition
        class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
        <div>
            {{--
                Wajib bila status Sudah Keluar: tanpa tanggalnya, riwayat
                keanggotaan kehilangan batas akhir dan lama keanggotaan tidak
                dapat dihitung. Bintang statis, `required` mengikuti status.
            --}}
            <label for="{{ $awalan }}_tanggal_keluar_anggota" class="{{ $kelasLabel }}">
                Tanggal Keluar<span class="text-error-500">*</span>
            </label>
            <input type="date" id="{{ $awalan }}_tanggal_keluar_anggota" name="tanggal_keluar"
                value="{{ old('tanggal_keluar', $data['tanggal_keluar'] ?? '') }}" max="{{ date('Y-m-d') }}"
                :required="status === @js($keluar)" class="{{ $kelasKontrol }}" />
        </div>

        {{--
            Alasan keluar berdiri sendiri sejak 2026-08-20. Sebelumnya isian ini
            mengirim `keterangan`, padahal kamus data menyebut kolom itu catatan
            umum, sehingga catatan keanggotaan biasa tidak punya tempat sama
            sekali. Pemisahan ini mengikuti `riwayat_penghunian` yang sudah
            membedakan keduanya.
        --}}
        <div>
            <label for="{{ $awalan }}_alasan_keluar" class="{{ $kelasLabel }}">Alasan Keluar</label>
            <textarea id="{{ $awalan }}_alasan_keluar" name="alasan_keluar" rows="2" maxlength="255"
                placeholder="Contoh: pindah ke luar kawasan."
                class="{{ $kelasArea }}">{{ old('alasan_keluar', $data['alasan_keluar'] ?? '') }}</textarea>
        </div>
    </div>

    {{-- Catatan umum keanggotaan, berlaku pada status apa pun. --}}
    <div>
        <label for="{{ $awalan }}_keterangan_anggota" class="{{ $kelasLabel }}">Catatan</label>
        <textarea id="{{ $awalan }}_keterangan_anggota" name="keterangan" rows="2" maxlength="255"
            placeholder="Catatan tambahan bila ada"
            class="{{ $kelasArea }}">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
    </div>

    {{-- Penegasan aturan, terbaca saat mengisi bukan hanya di dokumen --}}
    <p class="rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
        Anggota yang berhenti ditandai <span class="font-medium">Sudah Keluar</span>, tidak dihapus dari
        daftar. Riwayat keanggotaan diperlukan agar catatan penyaluran saprotan di masa lalu tetap memiliki
        penerima yang jelas.
    </p>
</div>
