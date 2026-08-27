{{--
    Isian data rumah, dipakai bersama modal tambah dan modal ubah.

    Dua aturan yang tidak ada pada modul transmigran dan wajib dijaga di sini:

    1. Dropdown penghuni HANYA menampilkan KK yang belum menempati rumah lain,
       karena satu KK hanya boleh menempati satu rumah (rules.md bagian 6a
       poin 5 dan 8). Pada modal ubah, penghuni yang sedang menempati rumah ini
       tetap disertakan agar tidak hilang dari pilihannya sendiri.
    2. Alasan wajib diisi bila status hunian bernilai Tidak Dihuni
       (rules.md bagian 6a poin 4). Dijaga di sisi klien lewat Alpine, dan
       diulang di sisi server pada Tahap 5.

    Nama kolom mengikuti agents/data-dictionary.md bagian 6.2.
--}}
@php
    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    /*
        `$transmigranTanpaRumah`, `$daftarTransmigran`, `$daftarSp`,
        `$opsiKondisiRumah`, dan `$opsiStatusHunian` disuplai
        ViewServiceProvider.

        Penyusunan `$calonPenghuni` tetap di sini, sebab hasilnya bergantung
        pada `$data` yang sedang diubah, dan `$data` itu datang dari induk yang
        menyisipkan berkas ini. Yang dipindahkan ke composer adalah
        pengambilan datanya, bukan penalaran yang memakainya.
    */
    $calonPenghuni = $transmigranTanpaRumah;

    if (! empty($data['penghuni'])) {
        $penghuniSaatIni = collect($daftarTransmigran)
            ->firstWhere('nama_kepala_keluarga', $data['penghuni']);

        if ($penghuniSaatIni) {
            array_unshift($calonPenghuni, $penghuniSaatIni);
        }
    }
@endphp

<div class="space-y-6"
    x-data="{ statusHunian: @js($data['status_hunian'] ?? 'Dihuni') }">

    {{-- Bagian 1: identitas rumah --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Rumah</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_no_rumah" class="{{ $kelasLabel }}">Nomor atau Blok Rumah</label>
                <input type="text" id="{{ $awalan }}_no_rumah" name="no_rumah"
                    value="{{ old('no_rumah', $data['no_rumah'] ?? '') }}" maxlength="50" placeholder="Contoh: A-01"
                    class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_tahun_pembangunan" class="{{ $kelasLabel }}">Tahun Pembangunan</label>
                <input type="number" id="{{ $awalan }}_tahun_pembangunan" name="tahun_pembangunan"
                    value="{{ old('tahun_pembangunan', $data['tahun_pembangunan'] ?? '') }}" min="1900"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_luas_bangunan" class="{{ $kelasLabel }}">Luas Bangunan</label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_luas_bangunan" name="luas_bangunan"
                        value="{{ old('luas_bangunan', $data['luas_bangunan'] ?? '') }}" min="0" step="0.01"
                        placeholder="36.00" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span
                        class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                        m<sup>2</sup>
                    </span>
                </div>
            </div>

            <div>
                <label for="{{ $awalan }}_kondisi" class="{{ $kelasLabel }}">
                    Kondisi Rumah<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_kondisi" name="kondisi" required class="{{ $kelasKontrol }}">
                    @foreach ($opsiKondisiRumah as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('kondisi', $data['kondisi'] ?? '') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <x-sim.wilayah-picker
                    :daftar-kawasan="[['id' => 1, 'nama' => 'Kobalima Timur']]"
                    :daftar-sp="collect($daftarSp)
                        ->map(fn ($s) => ['id' => $s['id_satuan_permukiman'], 'nama' => $s['nama'], 'kawasan_id' => 1])
                        ->all()"
                    :sp-terpilih="old('satuan_permukiman_id', $data['satuan_permukiman_id'] ?? null)" />
            </div>
        </div>
    </section>

    {{-- Bagian 2: penghunian --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Penghunian</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_status_hunian" class="{{ $kelasLabel }}">
                    Status Hunian<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_status_hunian" name="status_hunian" x-model="statusHunian" required
                    class="{{ $kelasKontrol }}">
                    @foreach ($opsiStatusHunian as $nilai => $label)
                        <option value="{{ $nilai }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                {{--
                    Keterangan di bawah penting: operator sering bingung mengapa
                    nama yang dicarinya tidak muncul di daftar.
                --}}
                <x-sim.pilih-cari nama="transmigran_id" label="Kepala Keluarga Penghuni"
                    :awalan="$awalan" :opsi="$calonPenghuni" kunci="id_transmigran"
                    teks="nama_kepala_keluarga" keterangan-opsi="nik" gaya="kurung"
                    :terpilih="old('transmigran_id', $data['transmigran_id'] ?? null)"
                    placeholder="Belum ada penghuni"
                    keterangan="Hanya keluarga yang belum menempati rumah lain yang dapat dipilih."
                    :disabled="'statusHunian === \'Tidak Dihuni\''" />
            </div>

            {{-- Alasan wajib diisi saat rumah tidak dihuni --}}
            <div class="sm:col-span-2" x-show="statusHunian === 'Tidak Dihuni'" x-cloak>
                <label for="{{ $awalan }}_alasan_tidak_dihuni" class="{{ $kelasLabel }}">
                    Alasan Tidak Dihuni<span class="text-error-500">*</span>
                </label>
                <textarea id="{{ $awalan }}_alasan_tidak_dihuni" name="alasan_tidak_dihuni" rows="2"
                    :required="statusHunian === 'Tidak Dihuni'"
                    placeholder="Contoh: atap rusak berat, sedang menunggu perbaikan"
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">{{ old('alasan_tidak_dihuni', $data['alasan_tidak_dihuni'] ?? '') }}</textarea>
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_catatan_hunian" class="{{ $kelasLabel }}">Catatan Hunian</label>
                <textarea id="{{ $awalan }}_catatan_hunian" name="catatan_hunian" rows="2" maxlength="1000"
                    placeholder="Termasuk catatan bila rumah ditinggal sementara"
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">{{ old('catatan_hunian', $data['catatan_hunian'] ?? '') }}</textarea>
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

    {{-- Bagian 4: dokumentasi --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Dokumentasi</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <x-sim.file-upload nama="foto_rumah" label="Foto Rumah" :hanya-gambar="true"
                nama-dokumen="Foto Rumah" :nama-pemilik="$data['no_rumah'] ?? null"
                :berkas-saat-ini="$data['foto_rumah'] ?? null"
                keterangan="Foto tampak depan yang memperlihatkan kondisi bangunan." />

            <x-sim.file-upload nama="dokumen_pendukung" label="Dokumen Pendukung"
                nama-dokumen="Dokumen Rumah" :nama-pemilik="$data['no_rumah'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null" />
        </div>
    </section>
</div>
