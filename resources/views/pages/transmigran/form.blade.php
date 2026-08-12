{{--
    Isian data transmigran, dipakai bersama oleh modal tambah dan modal ubah.

    Ditulis sekali di sini agar kedua modal tidak menyimpan salinan isian yang
    dapat berbeda diam-diam (agents/rules.md bagian 19 poin 4).

    Nama kolom mengikuti agents/data-dictionary.md bagian 6.1, sehingga saat
    backend siap, Form Request tinggal membaca nama yang sama.

    Isian dikelompokkan menjadi tiga bagian agar form tidak terasa padat
    (agents/rules.md bagian 13.1 poin 2).

    Pemakaian:
        @include('pages.transmigran.form', ['data' => $baris, 'awalan' => 'ubah'])
--}}
@php
    // Awalan dipakai agar id isian tetap unik ketika dua modal hadir di satu
    // halaman. Tanpa ini label tidak lagi menunjuk isian yang benar.
    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
@endphp

<div class="space-y-6">
    {{-- Bagian 1: identitas --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Kepala Keluarga</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_nama" class="{{ $kelasLabel }}">
                    Nama Kepala Keluarga<span class="text-error-500">*</span>
                </label>
                <input type="text" id="{{ $awalan }}_nama" name="nama_kepala_keluarga"
                    value="{{ old('nama_kepala_keluarga', $data['nama_kepala_keluarga'] ?? '') }}"
                    required maxlength="255" placeholder="Nama sesuai kartu keluarga"
                    class="{{ $kelasKontrol }}" />
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Ditulis otomatis dalam huruf kapital saat disimpan.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_nik" class="{{ $kelasLabel }}">
                    NIK<span class="text-error-500">*</span>
                </label>
                <input type="text" id="{{ $awalan }}_nik" name="nik" value="{{ old('nik', $data['nik'] ?? '') }}"
                    required inputmode="numeric" pattern="[0-9]{16}" maxlength="16" minlength="16"
                    placeholder="16 digit angka" class="{{ $kelasKontrol }} tabular-nums" />
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">Tepat 16 digit, tanpa spasi.</p>
            </div>

            <div>
                <label for="{{ $awalan }}_no_kk" class="{{ $kelasLabel }}">
                    Nomor Kartu Keluarga<span class="text-error-500">*</span>
                </label>
                <input type="text" id="{{ $awalan }}_no_kk" name="no_kk"
                    value="{{ old('no_kk', $data['no_kk'] ?? '') }}" required inputmode="numeric"
                    pattern="[0-9]{16}" maxlength="16" minlength="16" placeholder="16 digit angka"
                    class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_jenis_kelamin" class="{{ $kelasLabel }}">Jenis Kelamin</label>
                <select id="{{ $awalan }}_jenis_kelamin" name="jenis_kelamin" class="{{ $kelasKontrol }}">
                    <option value="">Pilih jenis kelamin</option>
                    @foreach (\App\Enums\JenisKelamin::opsi() as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('jenis_kelamin', $data['jenis_kelamin'] ?? '') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_pendidikan" class="{{ $kelasLabel }}">Pendidikan Terakhir</label>
                <select id="{{ $awalan }}_pendidikan" name="pendidikan_terakhir" class="{{ $kelasKontrol }}">
                    <option value="">Pilih pendidikan</option>
                    @foreach (\App\Enums\PendidikanTerakhir::opsi() as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('pendidikan_terakhir', $data['pendidikan_terakhir'] ?? '') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_tempat_lahir" class="{{ $kelasLabel }}">Tempat Lahir</label>
                <input type="text" id="{{ $awalan }}_tempat_lahir" name="tempat_lahir"
                    value="{{ old('tempat_lahir', $data['tempat_lahir'] ?? '') }}" maxlength="100"
                    class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_tanggal_lahir" class="{{ $kelasLabel }}">Tanggal Lahir</label>
                <input type="date" id="{{ $awalan }}_tanggal_lahir" name="tanggal_lahir"
                    value="{{ old('tanggal_lahir', $data['tanggal_lahir'] ?? '') }}" max="{{ date('Y-m-d') }}"
                    class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_telepon" class="{{ $kelasLabel }}">Nomor Telepon</label>
                <input type="tel" id="{{ $awalan }}_telepon" name="telepon"
                    value="{{ old('telepon', $data['telepon'] ?? '') }}" inputmode="numeric" maxlength="20"
                    placeholder="08xxxxxxxxxx" class="{{ $kelasKontrol }} tabular-nums" />
            </div>
        </div>
    </section>

    {{-- Bagian 2: penempatan --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Penempatan di Kawasan</h3>
        <div class="mt-3 space-y-4">
            <x-sim.wilayah-picker
                :daftar-kawasan="[['id' => 1, 'nama' => 'Kobalima Timur']]"
                :daftar-sp="collect(\App\Support\DummyData::satuanPermukiman())
                    ->map(fn ($s) => ['id' => $s['id_satuan_permukiman'], 'nama' => $s['nama'], 'kawasan_id' => 1])
                    ->all()"
                :sp-terpilih="old('satuan_permukiman_id', $data['satuan_permukiman_id'] ?? null)" />

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="{{ $awalan }}_tahun_kedatangan" class="{{ $kelasLabel }}">
                        Tahun Kedatangan<span class="text-error-500">*</span>
                    </label>
                    <input type="number" id="{{ $awalan }}_tahun_kedatangan" name="tahun_kedatangan"
                        value="{{ old('tahun_kedatangan', $data['tahun_kedatangan'] ?? '') }}" required
                        min="1900" max="{{ date('Y') }}" placeholder="{{ date('Y') }}"
                        class="{{ $kelasKontrol }} tabular-nums" />
                    <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        Menjadi dasar grafik pertumbuhan penduduk kawasan.
                    </p>
                </div>

                <div>
                    <label for="{{ $awalan }}_daerah_asal" class="{{ $kelasLabel }}">Daerah Asal</label>
                    <input type="text" id="{{ $awalan }}_daerah_asal" name="daerah_asal"
                        value="{{ old('daerah_asal', $data['daerah_asal'] ?? '') }}" maxlength="255"
                        placeholder="Kabupaten atau provinsi asal" class="{{ $kelasKontrol }}" />
                </div>

                <div>
                    <label for="{{ $awalan }}_status_tinggal" class="{{ $kelasLabel }}">
                        Status Tinggal<span class="text-error-500">*</span>
                    </label>
                    <select id="{{ $awalan }}_status_tinggal" name="status_tinggal" required
                        class="{{ $kelasKontrol }}">
                        @foreach (\App\Enums\StatusTinggal::opsi() as $nilai => $label)
                            <option value="{{ $nilai }}" @selected(old('status_tinggal', $data['status_tinggal'] ?? 'Aktif') === $nilai)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="{{ $awalan }}_status_poktan" class="{{ $kelasLabel }}">
                        Anggota Kelompok Tani<span class="text-error-500">*</span>
                    </label>
                    <select id="{{ $awalan }}_status_poktan" name="status_anggota_poktan" required
                        class="{{ $kelasKontrol }}">
                        @foreach (\App\Enums\StatusAnggotaPoktan::opsi() as $nilai => $label)
                            <option value="{{ $nilai }}" @selected(old('status_anggota_poktan', $data['status_anggota_poktan'] ?? 'Tidak') === $nilai)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </section>

    {{-- Bagian 3: keluarga dan ekonomi --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Keluarga dan Penghidupan</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_pekerjaan" class="{{ $kelasLabel }}">
                    Pekerjaan Kepala Keluarga<span class="text-error-500">*</span>
                </label>
                {{--
                    Pekerjaan berupa teks bebas karena ragamnya di lapangan sulit
                    dibatasi di muka. Daftar saran menjaga konsistensi penulisan
                    tanpa menutup kemungkinan pekerjaan baru
                    (agents/data-dictionary.md bagian 6.1).
                --}}
                <input type="text" id="{{ $awalan }}_pekerjaan" name="pekerjaan_kepala_keluarga"
                    value="{{ old('pekerjaan_kepala_keluarga', $data['pekerjaan_kepala_keluarga'] ?? '') }}"
                    required maxlength="100" list="saran-pekerjaan" class="{{ $kelasKontrol }}" />
                <datalist id="saran-pekerjaan">
                    @foreach (array_keys(\App\Support\DummyData::sebaranPekerjaan()) as $pekerjaan)
                        <option value="{{ mb_strtoupper($pekerjaan) }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div>
                <label for="{{ $awalan }}_jumlah_anggota" class="{{ $kelasLabel }}">
                    Jumlah Anggota Keluarga<span class="text-error-500">*</span>
                </label>
                <input type="number" id="{{ $awalan }}_jumlah_anggota" name="jumlah_anggota_keluarga"
                    value="{{ old('jumlah_anggota_keluarga', $data['jumlah_anggota_keluarga'] ?? '') }}" required
                    min="1" max="30" class="{{ $kelasKontrol }} tabular-nums" />
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Termasuk kepala keluarga.
                </p>
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_pendapatan" class="{{ $kelasLabel }}">Pendapatan per Bulan</label>
                <div class="relative">
                    <span
                        class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                        Rp
                    </span>
                    <input type="number" id="{{ $awalan }}_pendapatan" name="pendapatan_per_bulan"
                        value="{{ old('pendapatan_per_bulan', $data['pendapatan_per_bulan'] ?? '') }}" min="0"
                        step="1000" placeholder="0"
                        class="{{ $kelasKontrol }} tabular-nums pl-10" />
                </div>
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_keterangan" class="{{ $kelasLabel }}">Keterangan</label>
                <textarea id="{{ $awalan }}_keterangan" name="keterangan" rows="3" maxlength="1000"
                    placeholder="Catatan tambahan bila ada"
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
            </div>
        </div>
    </section>

    {{-- Bagian 4: dokumen --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Dokumen Pendukung</h3>
        <div class="mt-3">
            <x-sim.file-upload nama="dokumen_pendukung" label="Kartu Keluarga atau KTP"
                nama-dokumen="Kartu Keluarga"
                :nama-pemilik="$data['nama_kepala_keluarga'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                keterangan="Unggah hasil pindaian atau foto yang terbaca jelas." />
        </div>
    </section>
</div>
