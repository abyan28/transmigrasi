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

    {{-- Bagian 1: identitas --}}
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

    {{-- Bagian 2: penempatan pada dua hierarki --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Penempatan Wilayah</h3>

        <p class="mt-2 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
            Satu SP menempel pada dua induk sekaligus. <span class="font-medium">Desa</span> adalah
            kedudukan administratifnya, sedangkan <span class="font-medium">kawasan transmigrasi</span>
            adalah kedudukan programnya. Satu kawasan dapat mencakup beberapa kecamatan, sehingga keduanya
            tidak selalu sejalan dan harus diisi terpisah.
        </p>

        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_desa_id" class="{{ $kelasLabel }}">Desa<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_desa_id" name="desa_id" required class="{{ $kelasKontrol }}">
                    <option value="">Pilih desa</option>
                    @foreach ($daftarDesa as $d)
                        <option value="{{ $d['id_desa'] }}"
                            @selected(old('desa', $data['desa'] ?? '') === $d['nama'])>
                            {{ $d['nama'] }} &mdash; Kec. {{ $d['kecamatan'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_kawasan_id" class="{{ $kelasLabel }}">Kawasan Transmigrasi<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_kawasan_id" name="kawasan_id" required class="{{ $kelasKontrol }}">
                    <option value="">Pilih kawasan</option>
                    @foreach ($daftarKawasan as $k)
                        <option value="{{ $k['id_kawasan_transmigrasi'] }}"
                            @selected(old('kawasan', $data['kawasan'] ?? '') === $k['nama'])>
                            {{ $k['nama'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    {{-- Bagian 3: titik lokasi --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Titik Lokasi</h3>

        <div class="mt-3">
            <x-sim.koordinat-input :lintang="old('lintang', $data['lintang'] ?? null)"
                :bujur="old('bujur', $data['bujur'] ?? null)" />
        </div>
    </section>

    {{--
        Bagian 4: Keadaan Wilayah (Bab II Monografi).

        Semua isian di bawah opsional dan dokumenter. Angka rentang disimpan
        sebagai pasangan min/maks (keputusan pemilik proyek 2026-08-28), bukan
        teks, agar dapat dianalisis kelak.
    --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Keadaan Wilayah</h3>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
            Data untuk Laporan Monografi SP. Seluruhnya opsional; isi menurut berkas penetapan dan survei wilayah.
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

    <section>
        <h3 class="{{ $kelasBagian }}">Catatan</h3>
        <div class="mt-3">
            {{-- Label ditambahkan 2026-08-20. Sebelumnya isian ini satu-satunya
                 yang sama sekali tanpa `<label>`, sehingga pembaca layar hanya
                 mengumumkan sebuah kotak teks tanpa memberi tahu isinya apa. --}}
            <label for="{{ $awalan }}_keterangan_sp" class="{{ $kelasLabel }}">Catatan</label>
            <textarea id="{{ $awalan }}_keterangan_sp" name="keterangan" rows="2" maxlength="255"
                placeholder="Catatan tambahan mengenai satuan permukiman ini."
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
        </div>
    </section>

    {{-- Bagian 5: catatan --}}
    {{--
        Dokumen pendukung. Kolomnya sudah lama ada pada data-dictionary.md 3.6,
        tetapi belum pernah punya isian, sehingga SK penetapan SP tidak dapat
        diunggah ke mana pun.
    --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Dokumen Pendukung</h3>
        <div class="mt-3">
            <x-sim.file-upload nama="dokumen_pendukung" label="Dokumen Penetapan SP"
                nama-dokumen="Dokumen SP" :nama-pemilik="$data['nama'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                keterangan="SK penetapan, peta wilayah, atau berita acara penyerahan." />
        </div>
    </section>
</div>
