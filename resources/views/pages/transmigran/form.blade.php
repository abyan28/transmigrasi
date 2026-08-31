{{--
    Isian data transmigran, dipakai bersama oleh modal tambah dan modal ubah.

    Ditulis sekali di sini agar kedua modal tidak menyimpan salinan isian yang
    dapat berbeda diam-diam (agents/rules.md bagian 19 poin 4).

    Nama kolom mengikuti agents/data-dictionary.md bagian 6.1, sehingga saat
    backend siap, Form Request tinggal membaca nama yang sama.

    FORM BERTAHAP (Putaran 4, 2026-08-29). Empat langkah, dibungkus
    <div data-langkah="n">: 1 Identitas, 2 Penempatan, 3 Anggota Keluarga,
    4 Berkas. Modal pemanggil WAJIB mengoper :langkah -- penunjuk langkah dan
    tombol Lanjut/Simpan hidup di x-sim.modal-form (ui-spec.md 6.2).

    Isian wajib TETAP memakai `required` biasa: tombol Lanjut memvalidasi
    langkah aktif sebelum maju, dan tombol Simpan melompat ke langkah
    bermasalah alih-alih menolak diam-diam. Cabang bersyarat di dalam repeater
    anggota keluarga tetap :required/:disabled seperti biasa.

    USIA tidak diisi: dihitung dari tanggal lahir dan bertambah sendiri tiap
    tahun (Rombongan B). JUMLAH ANGGOTA KELUARGA juga tidak diisi: dihitung
    1 (kepala) + cacah baris pada langkah Anggota Keluarga.

    Pemakaian:
        @include('pages.transmigran.form', ['data' => $baris, 'awalan' => 'ubah'])
--}}
@php
    // Awalan dipakai agar id isian tetap unik ketika dua modal hadir di satu
    // halaman. Tanpa ini label tidak lagi menunjuk isian yang benar.
    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];
    $anggotaKeluargaData = $anggotaKeluargaData ?? [];

    // Repeater hanya menyunting anggota yang masih Aktif (Putaran 6). Anggota
    // yang sudah meninggal atau pindah dicatat lewat modal "Catat Peristiwa"
    // di halaman rincian, bukan lewat form ini; menampilkannya di sini akan
    // membuat petugas menghidupkannya kembali tanpa sengaja saat menyimpan.
    $anggotaNonAktif = collect($anggotaKeluargaData)
        ->filter(fn ($a) => ($a['status'] ?? 'Aktif') !== 'Aktif')
        ->values();
    $anggotaKeluargaData = collect($anggotaKeluargaData)
        ->filter(fn ($a) => ($a['status'] ?? 'Aktif') === 'Aktif')
        ->values()
        ->all();

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
@endphp

<div class="space-y-6"
    x-data="{
        tanggalLahirKk: @js($data['tanggal_lahir'] ?? ''),
        anggota: @js(collect($anggotaKeluargaData)->map(fn ($a) => [
            'hubungan' => $a['hubungan'] ?? '',
            'nama_lengkap' => $a['nama_lengkap'] ?? '',
            'nik' => $a['nik'] ?? '',
            'jenis_kelamin' => $a['jenis_kelamin'] ?? '',
            'tempat_lahir' => $a['tempat_lahir'] ?? '',
            'tanggal_lahir' => $a['tanggal_lahir'] ?? '',
            'agama' => $a['agama'] ?? '',
            'kegiatan' => $a['kegiatan'] ?? '',
            'pendidikan_terakhir' => $a['pendidikan_terakhir'] ?? '',
            'pekerjaan' => $a['pekerjaan'] ?? '',
            'pendapatan_per_bulan' => $a['pendapatan_per_bulan'] ?? '',
            'telepon' => $a['telepon'] ?? '',
            'keterangan' => $a['keterangan'] ?? '',
        ])->values()->all()),
        usiaDari(tanggal) {
            if (! tanggal) return null;
            const lahir = new Date(tanggal);
            if (isNaN(lahir)) return null;
            const kini = new Date();
            let umur = kini.getFullYear() - lahir.getFullYear();
            const belumUlangTahun = kini.getMonth() < lahir.getMonth()
                || (kini.getMonth() === lahir.getMonth() && kini.getDate() < lahir.getDate());
            if (belumUlangTahun) umur--;
            return umur >= 0 ? umur : null;
        },
        get usiaKk() { return this.usiaDari(this.tanggalLahirKk); },
        get jumlahAnggotaKeluarga() { return 1 + this.anggota.length; },
        tambahAnggota() {
            this.anggota.push({
                hubungan: '', nama_lengkap: '', nik: '', jenis_kelamin: '',
                tempat_lahir: '', tanggal_lahir: '', agama: '', kegiatan: '',
                pendidikan_terakhir: '', pekerjaan: '', pendapatan_per_bulan: '',
                telepon: '', keterangan: '',
            });
        },
        hapusAnggota(i) { this.anggota.splice(i, 1); },
    }">
    {{-- Langkah 1: Identitas --}}
    <div data-langkah="1" x-show="! bertahap || langkah === 1" x-cloak>
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
                    @foreach ($opsiJenisKelamin as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('jenis_kelamin', $data['jenis_kelamin'] ?? '') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_agama" class="{{ $kelasLabel }}">Agama</label>
                <select id="{{ $awalan }}_agama" name="agama" class="{{ $kelasKontrol }}">
                    <option value="">Pilih agama</option>
                    @foreach ($opsiAgama as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('agama', $data['agama'] ?? '') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_pendidikan" class="{{ $kelasLabel }}">Pendidikan Terakhir</label>
                <select id="{{ $awalan }}_pendidikan" name="pendidikan_terakhir" class="{{ $kelasKontrol }}">
                    <option value="">Pilih pendidikan</option>
                    @foreach ($opsiPendidikan as $nilai => $label)
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
                    x-model="tanggalLahirKk"
                    value="{{ old('tanggal_lahir', $data['tanggal_lahir'] ?? '') }}" max="{{ date('Y-m-d') }}"
                    class="{{ $kelasKontrol }}" />
            </div>

            <div>
                {{--
                    Usia DIHITUNG dari tanggal lahir, tidak diisi dan tidak
                    disimpan (Rombongan B). Tanpa `name`, sehingga tidak ikut
                    terkirim; nilainya diturunkan ulang setiap kali data dibaca.
                --}}
                <span class="{{ $kelasLabel }}">Usia</span>
                <p class="flex h-11 items-center rounded-lg bg-gray-50 px-4 text-theme-sm text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                    <span x-text="usiaKk !== null ? usiaKk + ' tahun' : 'Isi tanggal lahir lebih dulu'"></span>
                </p>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Dihitung dari tanggal lahir, bertambah sendiri tiap tahun.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_telepon" class="{{ $kelasLabel }}">Nomor Telepon</label>
                <input type="tel" id="{{ $awalan }}_telepon" name="telepon"
                    value="{{ old('telepon', $data['telepon'] ?? '') }}" inputmode="numeric" maxlength="20"
                    placeholder="08xxxxxxxxxx" class="{{ $kelasKontrol }} tabular-nums" />
            </div>
        </div>
    </section>

    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Penghidupan</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_pekerjaan" class="{{ $kelasLabel }}">
                    Pekerjaan Kepala Keluarga<span class="text-error-500">*</span>
                </label>
                {{--
                    Pekerjaan berupa teks bebas karena ragamnya di lapangan sulit
                    dibatasi di muka. Daftar saran menjaga konsistensi penulisan
                    (agents/data-dictionary.md bagian 6.1).
                --}}
                <input type="text" id="{{ $awalan }}_pekerjaan" name="pekerjaan_kepala_keluarga"
                    value="{{ old('pekerjaan_kepala_keluarga', $data['pekerjaan_kepala_keluarga'] ?? '') }}"
                    required maxlength="100" list="saran-pekerjaan" class="{{ $kelasKontrol }}" />
                <datalist id="saran-pekerjaan">
                    @foreach ($saranPekerjaan as $pekerjaan)
                        <option value="{{ mb_strtoupper($pekerjaan) }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div>
                <label for="{{ $awalan }}_pendapatan" class="{{ $kelasLabel }}">Pendapatan Kepala Keluarga per Bulan</label>
                <div class="relative">
                    <span
                        class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                        Rp
                    </span>
                    <input type="text" inputmode="numeric" x-uang id="{{ $awalan }}_pendapatan" name="pendapatan_per_bulan"
                        value="{{ old('pendapatan_per_bulan', $data['pendapatan_per_bulan'] ?? '') }}"
                        placeholder="0"
                        class="{{ $kelasKontrol }} tabular-nums pl-10" />
                </div>
            </div>
        </div>
    </section>
    </div>

    {{-- Langkah 2: Penempatan --}}
    <div data-langkah="2" x-show="! bertahap || langkah === 2" x-cloak>
    <section>
        <h3 class="{{ $kelasBagian }}">Penempatan di Kawasan</h3>
        <div class="mt-3 space-y-4">
            <x-sim.wilayah-picker
                :daftar-kawasan="[['id' => 1, 'nama' => 'Kobalima Timur']]"
                :daftar-sp="collect($daftarSp)
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
                        Status Tinggal Keluarga<span class="text-error-500">*</span>
                    </label>
                    <select id="{{ $awalan }}_status_tinggal" name="status_tinggal" required
                        class="{{ $kelasKontrol }}">
                        @foreach (\App\Enums\StatusTinggal::opsi() as $nilai => $label)
                            <option value="{{ $nilai }}" @selected(old('status_tinggal', $data['status_tinggal'] ?? 'Aktif') === $nilai)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        Status keberadaan keluarga inti di satuan permukiman.
                    </p>
                </div>

                {{--
                    Keanggotaan poktan TIDAK diisi di sini (rules.md 7a.8).
                    Ditetapkan dari sisi poktan, dan nilai di bawah dibaca
                    sebagai turunan dari keanggotaan berstatus Aktif.
                --}}
                <div>
                    <span class="{{ $kelasLabel }}">Anggota Kelompok Tani</span>
                    <p class="flex h-11 items-center rounded-lg bg-gray-50 px-4 text-theme-sm text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                        {{ ($data['status_anggota_poktan'] ?? 'Tidak') === 'Ya' ? 'Ya, terdaftar pada kelompok tani' : 'Belum terdaftar pada kelompok mana pun' }}
                    </p>
                    <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        Ditetapkan dari halaman Kelompok Tani, bukan dari sini, agar tidak ada dua catatan yang berbeda.
                    </p>
                </div>
            </div>
        </div>
    </section>
    </div>

    {{-- Langkah 3: Anggota Keluarga --}}
    <div data-langkah="3" x-show="! bertahap || langkah === 3" x-cloak>
    <section>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="{{ $kelasBagian }}">Anggota Keluarga</h3>
                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                    Istri atau suami, anak, dan anggota lain selain kepala keluarga.
                    <span class="block">
                        Jumlah anggota keluarga:
                        <span class="font-medium text-gray-700 dark:text-gray-300" x-text="jumlahAnggotaKeluarga + ' orang'"></span>
                        (termasuk kepala keluarga, dihitung dari daftar di bawah).
                    </span>
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
            Belum ada anggota keluarga ditambahkan.
        </p>

        <template x-for="(a, i) in anggota" :key="i">
            <fieldset class="mt-4 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <legend class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                        Anggota <span x-text="i + 1"></span>
                        <span class="text-gray-400" x-text="a.nama_lengkap ? ' - ' + a.nama_lengkap : ''"></span>
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
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_hubungan_' + i">
                            Hubungan<span class="text-error-500">*</span>
                        </label>
                        <select :id="'{{ $awalan }}_ak_hubungan_' + i" :name="`anggota_keluarga[${i}][hubungan]`"
                            x-model="a.hubungan" required class="{{ $kelasKontrol }}">
                            <option value="">Pilih hubungan</option>
                            @foreach ($opsiHubunganAnggota as $nilai => $label)
                                <option value="{{ $nilai }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_nama_' + i">
                            Nama Lengkap<span class="text-error-500">*</span>
                        </label>
                        <input type="text" :id="'{{ $awalan }}_ak_nama_' + i" :name="`anggota_keluarga[${i}][nama_lengkap]`"
                            x-model="a.nama_lengkap" required maxlength="255" class="{{ $kelasKontrol }}" />
                    </div>

                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_nik_' + i">NIK</label>
                        <input type="text" :id="'{{ $awalan }}_ak_nik_' + i" :name="`anggota_keluarga[${i}][nik]`"
                            x-model="a.nik" inputmode="numeric" pattern="[0-9]{16}" maxlength="16"
                            placeholder="Kosongkan bila belum punya" class="{{ $kelasKontrol }} tabular-nums" />
                        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            Boleh kosong bagi balita yang belum memiliki NIK.
                        </p>
                    </div>

                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_jk_' + i">Jenis Kelamin</label>
                        <select :id="'{{ $awalan }}_ak_jk_' + i" :name="`anggota_keluarga[${i}][jenis_kelamin]`"
                            x-model="a.jenis_kelamin" class="{{ $kelasKontrol }}">
                            <option value="">Pilih jenis kelamin</option>
                            @foreach ($opsiJenisKelamin as $nilai => $label)
                                <option value="{{ $nilai }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_tmp_' + i">Tempat Lahir</label>
                        <input type="text" :id="'{{ $awalan }}_ak_tmp_' + i" :name="`anggota_keluarga[${i}][tempat_lahir]`"
                            x-model="a.tempat_lahir" maxlength="100" class="{{ $kelasKontrol }}" />
                    </div>

                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_tgl_' + i">Tanggal Lahir</label>
                        <input type="date" :id="'{{ $awalan }}_ak_tgl_' + i" :name="`anggota_keluarga[${i}][tanggal_lahir]`"
                            x-model="a.tanggal_lahir" max="{{ date('Y-m-d') }}" class="{{ $kelasKontrol }}" />
                        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            <span x-text="usiaDari(a.tanggal_lahir) !== null ? 'Usia ' + usiaDari(a.tanggal_lahir) + ' tahun' : 'Usia dihitung dari tanggal lahir'"></span>
                        </p>
                    </div>

                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_agama_' + i">Agama</label>
                        <select :id="'{{ $awalan }}_ak_agama_' + i" :name="`anggota_keluarga[${i}][agama]`"
                            x-model="a.agama" class="{{ $kelasKontrol }}">
                            <option value="">Pilih agama</option>
                            @foreach ($opsiAgama as $nilai => $label)
                                <option value="{{ $nilai }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_keg_' + i">
                            Pendidikan atau Kerja<span class="text-error-500">*</span>
                        </label>
                        <select :id="'{{ $awalan }}_ak_keg_' + i" :name="`anggota_keluarga[${i}][kegiatan]`"
                            x-model="a.kegiatan" required class="{{ $kelasKontrol }}">
                            <option value="">Pilih kegiatan</option>
                            @foreach ($opsiKegiatanAnggota as $nilai => $label)
                                <option value="{{ $nilai }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{--
                        Cabang isian menurut kegiatan (App\Enums\KegiatanAnggota):
                        Belum Sekolah tidak menampilkan apa-apa; selain itu
                        pendidikan tampil; Bekerja menambah pekerjaan dan
                        pendapatan. `:required` bersyarat mengikuti pola form
                        wilayah dan saprotan.
                    --}}
                    <div x-show="a.kegiatan && a.kegiatan !== 'Belum Sekolah'" x-cloak>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_pend_' + i">
                            <span x-text="a.kegiatan === 'Masih Sekolah' ? 'Jenjang yang Sedang Ditempuh' : 'Pendidikan Terakhir'"></span>
                            <span class="text-error-500">*</span>
                        </label>
                        <select :id="'{{ $awalan }}_ak_pend_' + i" :name="`anggota_keluarga[${i}][pendidikan_terakhir]`"
                            x-model="a.pendidikan_terakhir"
                            :required="a.kegiatan && a.kegiatan !== 'Belum Sekolah'"
                            :disabled="! a.kegiatan || a.kegiatan === 'Belum Sekolah'"
                            class="{{ $kelasKontrol }}">
                            <option value="">Pilih pendidikan</option>
                            @foreach ($opsiPendidikan as $nilai => $label)
                                <option value="{{ $nilai }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="a.kegiatan === 'Bekerja'" x-cloak>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_kerja_' + i">
                            Pekerjaan<span class="text-error-500">*</span>
                        </label>
                        <input type="text" :id="'{{ $awalan }}_ak_kerja_' + i" :name="`anggota_keluarga[${i}][pekerjaan]`"
                            x-model="a.pekerjaan" list="saran-pekerjaan" maxlength="100"
                            :required="a.kegiatan === 'Bekerja'" :disabled="a.kegiatan !== 'Bekerja'"
                            class="{{ $kelasKontrol }}" />
                    </div>

                    <div x-show="a.kegiatan === 'Bekerja'" x-cloak>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_gaji_' + i">Pendapatan per Bulan</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">Rp</span>
                            <input type="text" inputmode="numeric" x-uang :id="'{{ $awalan }}_ak_gaji_' + i" :name="`anggota_keluarga[${i}][pendapatan_per_bulan]`"
                                x-model="a.pendapatan_per_bulan" :disabled="a.kegiatan !== 'Bekerja'"
                                placeholder="0" class="{{ $kelasKontrol }} tabular-nums pl-10" />
                        </div>
                    </div>

                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_telp_' + i">Nomor Telepon</label>
                        <input type="tel" :id="'{{ $awalan }}_ak_telp_' + i" :name="`anggota_keluarga[${i}][telepon]`"
                            x-model="a.telepon" inputmode="numeric" maxlength="20"
                            placeholder="08xxxxxxxxxx" class="{{ $kelasKontrol }} tabular-nums" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_ak_ket_' + i">Catatan</label>
                        <textarea :id="'{{ $awalan }}_ak_ket_' + i" :name="`anggota_keluarga[${i}][keterangan]`"
                            x-model="a.keterangan" rows="2" maxlength="1000"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90"></textarea>
                    </div>
                </div>
            </fieldset>
        </template>

        @if ($anggotaNonAktif->isNotEmpty())
            {{--
                Anggota yang sudah meninggal atau pindah (Putaran 6). Ditampilkan
                sebagai bacaan, tidak ikut terkirim: peristiwanya dicatat lewat
                modal "Catat Peristiwa" di halaman rincian, bukan diedit di sini.
            --}}
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <p class="text-theme-xs font-medium text-gray-600 dark:text-gray-400">
                    Tidak dapat disunting di sini &mdash; sudah dicatat meninggal atau pindah
                </p>
                <ul class="mt-2 space-y-1">
                    @foreach ($anggotaNonAktif as $a)
                        <li class="text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $a['nama_lengkap'] }} ({{ $a['hubungan'] }}) &mdash; {{ $a['status'] }}@if (! empty($a['tanggal_peristiwa'])), {{ \Illuminate\Support\Carbon::parse($a['tanggal_peristiwa'])->translatedFormat('d M Y') }}@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>
    </div>

    {{-- Langkah 4: Catatan dan Berkas --}}
    <div data-langkah="4" x-show="! bertahap || langkah === 4" x-cloak>
    <section>
        <h3 class="{{ $kelasBagian }}">Catatan dan Berkas</h3>
        <div class="mt-3 space-y-4">
            <div>
                <label for="{{ $awalan }}_keterangan" class="{{ $kelasLabel }}">Catatan</label>
                <textarea id="{{ $awalan }}_keterangan" name="keterangan" rows="3" maxlength="1000"
                    placeholder="Catatan tambahan bila ada"
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
            </div>

            <x-sim.file-upload nama="dokumen_pendukung" label="Kartu Keluarga (KK)"
                nama-dokumen="Kartu Keluarga"
                :wajib="true"
                :nama-pemilik="$data['nama_kepala_keluarga'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                keterangan="Unggah hasil pindaian atau foto Kartu Keluarga (KK) yang terbaca jelas." />
        </div>
    </section>
    </div>
</div>
