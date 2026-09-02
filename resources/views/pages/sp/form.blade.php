{{--
    Isian data satuan permukiman.

    SP menempel pada DUA induk sekaligus: desa (hierarki administratif) dan
    kawasan transmigrasi (hierarki program). Percabangan ini tidak lazim dan
    mudah disalahpahami, sehingga keduanya diminta terpisah beserta
    penjelasannya (agents/erd.md bagian 7.0).

    Section "Keadaan Wilayah" (letak, batas, luas & bentuk, tanah, topografi,
    iklim, sumberdaya air) ditambahkan 2026-08-28 (Rombongan C), mengikuti
    Bab II Laporan Monografi. Seluruhnya dokumenter: dipakai laporan, tidak
    dihitung. Empat isian batas wilayah DIHIDUPKAN KEMBALI di sini setelah
    dicabut 2026-08-18, sebab Monografi memerlukannya (`notes.md` bagian 6).

    Nama kolom mengikuti agents/data-dictionary.md bagian 3.6.
--}}
@php
    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    // `$daftarDesa` dan `$daftarKawasan` disuplai ViewServiceProvider.
@endphp

<div class="space-y-6">

    {{-- Langkah 1: Identitas & Wilayah --}}
    <div data-langkah="1" x-show="! bertahap || langkah === 1" x-cloak>
    <div class="space-y-6">
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Satuan Permukiman</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_nama_sp" class="{{ $kelasLabel }}">Nama SP<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_sp" name="nama" required
                    value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: SP Kapitan Meo" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_kode_sp" class="{{ $kelasLabel }}">Kode SP</label>
                <input type="text" id="{{ $awalan }}_kode_sp" name="kode_sp"
                    value="{{ old('kode_sp', $data['kode_sp'] ?? '') }}" maxlength="20"
                    placeholder="Contoh: SP-01" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_tahun_penempatan" class="{{ $kelasLabel }}">Tahun Penempatan</label>
                <input type="number" id="{{ $awalan }}_tahun_penempatan" name="tahun_penempatan"
                    value="{{ old('tahun_penempatan', $data['tahun_penempatan'] ?? '') }}" min="1950"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_luas_lahan_sp" class="{{ $kelasLabel }}">Luas Lahan</label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_luas_lahan_sp" name="luas_lahan"
                        value="{{ old('luas_lahan', $data['luas_lahan'] ?? '') }}" min="0" step="0.01"
                        placeholder="820.50" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span
                        class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
                </div>
            </div>

            <div>
                <label for="{{ $awalan }}_jumlah_kk_rencana" class="{{ $kelasLabel }}">Rencana Jumlah KK</label>
                <input type="number" id="{{ $awalan }}_jumlah_kk_rencana" name="jumlah_kk_rencana"
                    value="{{ old('jumlah_kk_rencana', $data['jumlah_kk_rencana'] ?? '') }}" min="0" step="1"
                    class="{{ $kelasKontrol }} tabular-nums" />
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Daya tampung rencana. Jumlah terisi dihitung sistem dari data transmigran.
                </p>
            </div>
        </div>
    </section>

    {{-- Penempatan pada dua hierarki --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Penempatan Wilayah</h3>

        <p class="mt-2 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
            Satu SP menempel pada dua induk sekaligus. <span class="font-medium">Kawasan transmigrasi</span>
            adalah kedudukan programnya, sedangkan <span class="font-medium">desa</span> adalah kedudukan
            administratifnya. Satu kawasan dapat mencakup beberapa kecamatan, sehingga keduanya tidak
            selalu sejalan dan tetap diisi terpisah.
        </p>

        {{--
            Kawasan didahulukan (2026-09-02), sepola dengan form Rumah dan
            form Lahan yang menaruh penentu sebelum yang ditentukan
            (rules.md 6a.12 dan 7.12).

            Memilih kawasan MENYARING daftar desa menjadi desa pada
            kabupaten kawasan itu. Penyaringannya menempuh kabupaten,
            BUKAN relasi kawasan-ke-desa: keduanya dua cabang terpisah
            yang baru bertemu di SP (rules.md 4a.2), sehingga menautkan
            desa langsung ke kawasan berarti mengarang relasi yang
            sengaja tidak dimodelkan.

            Desanya tetap dapat dipilih ketika kawasan belum dipilih,
            sebab urutan pengisian adalah anjuran, bukan penghalang.
        --}}
        <div class="mt-3 grid gap-4 sm:grid-cols-2"
            x-data="{
                kawasanId: @js((string) old('kawasan_id', $data['kawasan_id'] ?? '')),
                petaKawasan: @js($petaKawasanKabupaten),

                get kabupatenKawasan() {
                    return this.petaKawasan[this.kawasanId] ?? null;
                },
            }">

            <div>
                <label for="{{ $awalan }}_kawasan_id" class="{{ $kelasLabel }}">Kawasan Transmigrasi<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_kawasan_id" name="kawasan_id" required x-model="kawasanId" class="{{ $kelasKontrol }}">
                    <option value="">Pilih kawasan</option>
                    @foreach ($daftarKawasan as $k)
                        <option value="{{ $k['id_kawasan_transmigrasi'] }}"
                            @selected(old('kawasan', $data['kawasan'] ?? '') === $k['nama'])>
                            {{ $k['nama'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_desa_id" class="{{ $kelasLabel }}">Desa<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_desa_id" name="desa_id" required class="{{ $kelasKontrol }}">
                    <option value="">Pilih desa</option>
                    @foreach ($daftarDesa as $d)
                        <option value="{{ $d['id_desa'] }}"
                            data-kabupaten="{{ $d['kabupaten_id'] ?? '' }}"
                            x-show="! kabupatenKawasan || kabupatenKawasan === {{ (int) ($d['kabupaten_id'] ?? 0) }}"
                            @selected(old('desa', $data['desa'] ?? '') === $d['nama'])>
                            {{ $d['nama'] }} &mdash; Kec. {{ $d['kecamatan'] }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400"
                    x-show="kabupatenKawasan" x-cloak>
                    Hanya desa pada kabupaten kawasan terpilih yang ditampilkan.
                </p>
            </div>
        </div>
    </section>
    </div>
    </div>

    {{-- Langkah 2: Lokasi & Batas --}}
    <div data-langkah="2" x-show="! bertahap || langkah === 2" x-cloak>
    <div class="space-y-6">
    <section>
        <h3 class="{{ $kelasBagian }}">Titik Lokasi</h3>

        <div class="mt-3">
            <x-sim.koordinat-input :lintang="old('lintang', $data['lintang'] ?? null)"
                :bujur="old('bujur', $data['bujur'] ?? null)" />
        </div>
    </section>

    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Batas & Letak Wilayah</h3>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
            Letak astronomis, jarak ke pusat pemerintahan, dan batas administratif SP.
        </p>

        <div class="mt-4 space-y-5">
            {{-- Letak astronomis: kotak lintang/bujur --}}
            <fieldset>
                <legend class="mb-2 text-theme-sm font-medium text-gray-700 dark:text-gray-300">Letak Astronomis</legend>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        'lintang_utara' => 'Lintang Utara',
                        'lintang_selatan' => 'Lintang Selatan',
                        'bujur_barat' => 'Bujur Barat',
                        'bujur_timur' => 'Bujur Timur',
                    ] as $nama => $label)
                        <div>
                            <label for="{{ $awalan }}_{{ $nama }}" class="{{ $kelasLabel }}">{{ $label }}</label>
                            <input type="number" step="0.0000001" id="{{ $awalan }}_{{ $nama }}" name="{{ $nama }}"
                                value="{{ old($nama, $data[$nama] ?? '') }}" class="{{ $kelasKontrol }} tabular-nums" />
                        </div>
                    @endforeach
                </div>
            </fieldset>

            {{-- Letak ekonomis: jarak ke pusat pemerintahan --}}
            <fieldset>
                <legend class="mb-2 text-theme-sm font-medium text-gray-700 dark:text-gray-300">Jarak ke Pusat Pemerintahan</legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        'jarak_ke_kecamatan_km' => 'Ibu Kota Kecamatan',
                        'jarak_ke_kabupaten_km' => 'Ibu Kota Kabupaten',
                        'jarak_ke_provinsi_km' => 'Ibu Kota Provinsi',
                    ] as $nama => $label)
                        <div>
                            <label for="{{ $awalan }}_{{ $nama }}" class="{{ $kelasLabel }}">{{ $label }}</label>
                            <div class="relative">
                                <input type="number" step="0.1" min="0" id="{{ $awalan }}_{{ $nama }}" name="{{ $nama }}"
                                    value="{{ old($nama, $data[$nama] ?? '') }}" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                                <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">km</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </fieldset>

            {{-- Batas wilayah (dihidupkan kembali 2026-08-28) --}}
            <fieldset>
                <legend class="mb-2 text-theme-sm font-medium text-gray-700 dark:text-gray-300">Batas Wilayah</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        'batas_utara' => 'Sebelah Utara',
                        'batas_timur' => 'Sebelah Timur',
                        'batas_selatan' => 'Sebelah Selatan',
                        'batas_barat' => 'Sebelah Barat',
                    ] as $nama => $label)
                        <div>
                            <label for="{{ $awalan }}_{{ $nama }}" class="{{ $kelasLabel }}">{{ $label }}</label>
                            <input type="text" id="{{ $awalan }}_{{ $nama }}" name="{{ $nama }}" maxlength="150"
                                value="{{ old($nama, $data[$nama] ?? '') }}"
                                placeholder="Nama desa, sungai, hutan, atau laut" class="{{ $kelasKontrol }}" />
                        </div>
                    @endforeach
                </div>
            </fieldset>
        </div>
    </section>
    </div>
    </div>

    {{-- Langkah 3: Kondisi Alam & Iklim --}}
    <div data-langkah="3" x-show="! bertahap || langkah === 3" x-cloak>
    <div class="space-y-6">
    <section>
        <h3 class="{{ $kelasBagian }}">Keadaan Alam & Iklim</h3>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
            Data untuk Laporan Monografi SP. Seluruhnya opsional; isi menurut berkas penetapan dan survei wilayah.
        </p>

        <div class="mt-4 space-y-5">
            {{-- Luas dan bentuk lokasi --}}
            <fieldset>
                <legend class="mb-2 text-theme-sm font-medium text-gray-700 dark:text-gray-300">Luas dan Bentuk Lokasi</legend>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="{{ $awalan }}_nomor_sk_pencadangan" class="{{ $kelasLabel }}">Nomor SK Pencadangan Areal</label>
                        <input type="text" id="{{ $awalan }}_nomor_sk_pencadangan" name="nomor_sk_pencadangan" maxlength="100"
                            value="{{ old('nomor_sk_pencadangan', $data['nomor_sk_pencadangan'] ?? '') }}"
                            placeholder="Contoh: 79/HK/2018" class="{{ $kelasKontrol }}" />
                    </div>
                    <div>
                        <label for="{{ $awalan }}_tanggal_sk_pencadangan" class="{{ $kelasLabel }}">Tanggal SK Pencadangan</label>
                        <input type="date" id="{{ $awalan }}_tanggal_sk_pencadangan" name="tanggal_sk_pencadangan"
                            value="{{ old('tanggal_sk_pencadangan', $data['tanggal_sk_pencadangan'] ?? '') }}"
                            max="{{ date('Y-m-d') }}" class="{{ $kelasKontrol }}" />
                    </div>
                    <div>
                        <label for="{{ $awalan }}_pola_permukiman" class="{{ $kelasLabel }}">Pola Permukiman</label>
                        <select id="{{ $awalan }}_pola_permukiman" name="pola_permukiman" class="{{ $kelasKontrol }}">
                            <option value="">Pilih pola</option>
                            @foreach ($opsiPolaPermukiman as $nilai => $label)
                                <option value="{{ $nilai }}" @selected(old('pola_permukiman', $data['pola_permukiman'] ?? '') === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </fieldset>

            {{-- Tanah dan topografi --}}
            <fieldset>
                <legend class="mb-2 text-theme-sm font-medium text-gray-700 dark:text-gray-300">Tanah dan Topografi</legend>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="{{ $awalan }}_tingkat_kesuburan_tanah" class="{{ $kelasLabel }}">Tingkat Kesuburan Tanah</label>
                        <select id="{{ $awalan }}_tingkat_kesuburan_tanah" name="tingkat_kesuburan_tanah" class="{{ $kelasKontrol }}">
                            <option value="">Pilih tingkat</option>
                            @foreach ($opsiKesuburanTanah as $nilai => $label)
                                <option value="{{ $nilai }}" @selected(old('tingkat_kesuburan_tanah', $data['tingkat_kesuburan_tanah'] ?? '') === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="{{ $awalan }}_bentuk_wilayah" class="{{ $kelasLabel }}">Bentuk Wilayah</label>
                        <select id="{{ $awalan }}_bentuk_wilayah" name="bentuk_wilayah" class="{{ $kelasKontrol }}">
                            <option value="">Pilih bentuk</option>
                            @foreach ($opsiBentukWilayah as $nilai => $label)
                                <option value="{{ $nilai }}" @selected(old('bentuk_wilayah', $data['bentuk_wilayah'] ?? '') === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <span class="{{ $kelasLabel }}">pH Tanah</span>
                        <div class="flex items-center gap-2">
                            <input type="number" step="0.01" min="0" max="14" aria-label="pH tanah minimum"
                                name="ph_tanah_min" value="{{ old('ph_tanah_min', $data['ph_tanah_min'] ?? '') }}"
                                placeholder="min" class="{{ $kelasKontrol }} tabular-nums" />
                            <span class="text-theme-sm text-gray-400">sampai</span>
                            <input type="number" step="0.01" min="0" max="14" aria-label="pH tanah maksimum"
                                name="ph_tanah_maks" value="{{ old('ph_tanah_maks', $data['ph_tanah_maks'] ?? '') }}"
                                placeholder="maks" class="{{ $kelasKontrol }} tabular-nums" />
                        </div>
                    </div>
                    <div>
                        <span class="{{ $kelasLabel }}">Kemiringan Lereng (%)</span>
                        <div class="flex items-center gap-2">
                            <input type="number" step="0.01" min="0" aria-label="Kemiringan lereng minimum"
                                name="kemiringan_min_persen" value="{{ old('kemiringan_min_persen', $data['kemiringan_min_persen'] ?? '') }}"
                                placeholder="min" class="{{ $kelasKontrol }} tabular-nums" />
                            <span class="text-theme-sm text-gray-400">sampai</span>
                            <input type="number" step="0.01" min="0" aria-label="Kemiringan lereng maksimum"
                                name="kemiringan_maks_persen" value="{{ old('kemiringan_maks_persen', $data['kemiringan_maks_persen'] ?? '') }}"
                                placeholder="maks" class="{{ $kelasKontrol }} tabular-nums" />
                        </div>
                    </div>
                </div>
            </fieldset>

            {{-- Iklim --}}
            <fieldset>
                <legend class="mb-2 text-theme-sm font-medium text-gray-700 dark:text-gray-300">Iklim</legend>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="{{ $awalan }}_curah_hujan_tahunan_mm" class="{{ $kelasLabel }}">Curah Hujan Rata-rata per Tahun</label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" id="{{ $awalan }}_curah_hujan_tahunan_mm"
                                name="curah_hujan_tahunan_mm" value="{{ old('curah_hujan_tahunan_mm', $data['curah_hujan_tahunan_mm'] ?? '') }}"
                                class="{{ $kelasKontrol }} tabular-nums pr-12" />
                            <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">mm</span>
                        </div>
                    </div>
                    <div>
                        <span class="{{ $kelasLabel }}">Curah Hujan Bulanan (mm)</span>
                        <div class="flex items-center gap-2">
                            <input type="number" step="0.01" min="0" aria-label="Curah hujan bulanan terendah"
                                name="curah_hujan_bulan_min_mm" value="{{ old('curah_hujan_bulan_min_mm', $data['curah_hujan_bulan_min_mm'] ?? '') }}"
                                placeholder="terendah" class="{{ $kelasKontrol }} tabular-nums" />
                            <span class="text-theme-sm text-gray-400">sampai</span>
                            <input type="number" step="0.01" min="0" aria-label="Curah hujan bulanan tertinggi"
                                name="curah_hujan_bulan_maks_mm" value="{{ old('curah_hujan_bulan_maks_mm', $data['curah_hujan_bulan_maks_mm'] ?? '') }}"
                                placeholder="tertinggi" class="{{ $kelasKontrol }} tabular-nums" />
                        </div>
                    </div>
                    @foreach ([
                        ['Suhu Udara (derajat C)', 'suhu_min_c', 'suhu_maks_c', 'suhu_rata_c'],
                        ['Kecepatan Angin (knot)', 'angin_min_knot', 'angin_maks_knot', 'angin_rata_knot'],
                        ['Penyinaran Matahari (%)', 'penyinaran_min_persen', 'penyinaran_maks_persen', 'penyinaran_rata_persen'],
                    ] as [$judul, $min, $maks, $rata])
                        <div class="lg:col-span-3">
                            <span class="{{ $kelasLabel }}">{{ $judul }}</span>
                            <div class="grid gap-2 sm:grid-cols-3">
                                <input type="number" step="0.1" aria-label="{{ $judul }} minimum" name="{{ $min }}"
                                    value="{{ old($min, $data[$min] ?? '') }}" placeholder="minimum" class="{{ $kelasKontrol }} tabular-nums" />
                                <input type="number" step="0.1" aria-label="{{ $judul }} maksimum" name="{{ $maks }}"
                                    value="{{ old($maks, $data[$maks] ?? '') }}" placeholder="maksimum" class="{{ $kelasKontrol }} tabular-nums" />
                                <input type="number" step="0.1" aria-label="{{ $judul }} rata-rata" name="{{ $rata }}"
                                    value="{{ old($rata, $data[$rata] ?? '') }}" placeholder="rata-rata" class="{{ $kelasKontrol }} tabular-nums" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </fieldset>

            {{-- Sumberdaya air --}}
            <fieldset>
                <legend class="mb-2 text-theme-sm font-medium text-gray-700 dark:text-gray-300">Sumberdaya Air</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="{{ $awalan }}_sumber_air_bersih" class="{{ $kelasLabel }}">Sumber Air Bersih</label>
                        <input type="text" id="{{ $awalan }}_sumber_air_bersih" name="sumber_air_bersih" maxlength="255"
                            value="{{ old('sumber_air_bersih', $data['sumber_air_bersih'] ?? '') }}"
                            placeholder="Contoh: perpipaan dan mata air" class="{{ $kelasKontrol }}" />
                    </div>
                    <div>
                        <label for="{{ $awalan }}_sumber_air_pertanian" class="{{ $kelasLabel }}">Sumber Air Pertanian</label>
                        <input type="text" id="{{ $awalan }}_sumber_air_pertanian" name="sumber_air_pertanian" maxlength="255"
                            value="{{ old('sumber_air_pertanian', $data['sumber_air_pertanian'] ?? '') }}"
                            placeholder="Contoh: air hujan dan embung" class="{{ $kelasKontrol }}" />
                    </div>
                </div>
            </fieldset>
        </div>
    </section>
    </div>
    </div>

    {{-- Langkah 4: Aksesibilitas & Berkas --}}
    <div data-langkah="4" x-show="! bertahap || langkah === 4" x-cloak>
    <div class="space-y-6">
    {{--
        Bagian 4b: Rute Aksesibilitas (Tabel 2.1 Monografi), daftar dinamis.
        Ditambahkan 2026-08-28 (Rombongan C, Stage C2).
    --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800"
        x-data="{
            rute: @js(collect($ruteAksesibilitasData ?? [])->map(fn ($r) => [
                'rute' => $r['rute'] ?? '',
                'jarak_km' => $r['jarak_km'] ?? '',
                'sarana_angkutan' => $r['sarana_angkutan'] ?? '',
                'tempat_pemberangkatan' => $r['tempat_pemberangkatan'] ?? '',
                'kondisi_jalan' => $r['kondisi_jalan'] ?? '',
                'waktu_tempuh' => $r['waktu_tempuh'] ?? '',
                'ongkos_rp' => $r['ongkos_rp'] ?? '',
                'keterangan' => $r['keterangan'] ?? '',
            ])->values()->all()),
            tambahRute() {
                this.rute.push({ rute: '', jarak_km: '', sarana_angkutan: '', tempat_pemberangkatan: '', kondisi_jalan: '', waktu_tempuh: '', ongkos_rp: '', keterangan: '' });
            },
            hapusRute(i) { this.rute.splice(i, 1); },
        }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="{{ $kelasBagian }}">Rute Aksesibilitas</h3>
                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                    Cara pencapaian menuju SP ini. Bahan Tabel 2.1 Laporan Monografi.
                </p>
            </div>
            <button type="button" @click="tambahRute()"
                class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                Tambah Rute
            </button>
        </div>

        <p x-show="rute.length === 0"
            class="mt-4 rounded-lg bg-gray-50 px-4 py-6 text-center text-theme-sm text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
            Belum ada rute ditambahkan.
        </p>

        <template x-for="(r, i) in rute" :key="i">
            <fieldset class="mt-4 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <legend class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                        Rute <span x-text="i + 1"></span>
                        <span class="text-gray-400" x-text="r.rute ? ' - ' + r.rute : ''"></span>
                    </legend>
                    <button type="button" @click="hapusRute(i)"
                        class="rounded p-1 text-gray-400 transition hover:text-error-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500"
                        :aria-label="'Hapus rute ' + (i + 1)">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7" />
                        </svg>
                    </button>
                </div>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_rute_' + i">Rute Perjalanan<span class="text-error-500">*</span></label>
                        <input type="text" :id="'{{ $awalan }}_rute_' + i" :name="`rute_aksesibilitas[${i}][rute]`"
                            x-model="r.rute" required maxlength="255" placeholder="Contoh: Kupang ke UPT" class="{{ $kelasKontrol }}" />
                    </div>
                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_rute_jarak_' + i">Jarak Tempuh</label>
                        <div class="relative">
                            <input type="number" step="0.1" min="0" :id="'{{ $awalan }}_rute_jarak_' + i"
                                :name="`rute_aksesibilitas[${i}][jarak_km]`" x-model="r.jarak_km" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                            <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">km</span>
                        </div>
                    </div>
                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_rute_sarana_' + i">Sarana Angkutan</label>
                        <input type="text" :id="'{{ $awalan }}_rute_sarana_' + i" :name="`rute_aksesibilitas[${i}][sarana_angkutan]`"
                            x-model="r.sarana_angkutan" maxlength="150" placeholder="Roda dua, angkutan darat, pesawat" class="{{ $kelasKontrol }}" />
                    </div>
                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_rute_berangkat_' + i">Tempat Pemberangkatan</label>
                        <input type="text" :id="'{{ $awalan }}_rute_berangkat_' + i" :name="`rute_aksesibilitas[${i}][tempat_pemberangkatan]`"
                            x-model="r.tempat_pemberangkatan" maxlength="150" class="{{ $kelasKontrol }}" />
                    </div>
                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_rute_jalan_' + i">Kondisi Jalan</label>
                        <input type="text" :id="'{{ $awalan }}_rute_jalan_' + i" :name="`rute_aksesibilitas[${i}][kondisi_jalan]`"
                            x-model="r.kondisi_jalan" maxlength="150" placeholder="Baik aspal, pengerasan, tanah" class="{{ $kelasKontrol }}" />
                    </div>
                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_rute_waktu_' + i">Waktu Tempuh</label>
                        <input type="text" :id="'{{ $awalan }}_rute_waktu_' + i" :name="`rute_aksesibilitas[${i}][waktu_tempuh]`"
                            x-model="r.waktu_tempuh" maxlength="80" placeholder="6 jam, 45 menit" class="{{ $kelasKontrol }}" />
                    </div>
                    <div>
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_rute_ongkos_' + i">Ongkos</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">Rp</span>
                            <input type="text" inputmode="numeric" x-uang :id="'{{ $awalan }}_rute_ongkos_' + i"
                                :name="`rute_aksesibilitas[${i}][ongkos_rp]`" x-model="r.ongkos_rp"
                                placeholder="0" class="{{ $kelasKontrol }} tabular-nums pl-10" />
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $kelasLabel }}" :for="'{{ $awalan }}_rute_ket_' + i">Catatan</label>
                        <input type="text" :id="'{{ $awalan }}_rute_ket_' + i" :name="`rute_aksesibilitas[${i}][keterangan]`"
                            x-model="r.keterangan" maxlength="255" class="{{ $kelasKontrol }}" />
                    </div>
                </div>
            </fieldset>
        </template>
    </section>

    <section>
        <h3 class="{{ $kelasBagian }}">Dokumen Pendukung & Catatan</h3>
        <div class="mt-3 space-y-4">
            <div>
                <label for="{{ $awalan }}_keterangan_sp" class="{{ $kelasLabel }}">Catatan</label>
                <textarea id="{{ $awalan }}_keterangan_sp" name="keterangan" rows="2" maxlength="255"
                    placeholder="Catatan tambahan mengenai satuan permukiman ini."
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
            </div>

            <x-sim.file-upload nama="dokumen_pendukung" label="Dokumen Penetapan SP"
                nama-dokumen="Dokumen SP" :nama-pemilik="$data['nama'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                keterangan="SK penetapan, peta wilayah, atau berita acara penyerahan." />
        </div>
    </section>
    </div>
    </div>
</div>
