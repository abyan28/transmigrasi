{{--
    Isian kawasan transmigrasi.

    Kawasan adalah hierarki PROGRAM, terpisah dari hierarki administratif
    provinsi sampai desa. Satu kawasan dapat mencakup beberapa kecamatan,
    sehingga tidak dapat diturunkan dari struktur wilayah biasa
    (agents/erd.md bagian 7.0).

    Nama kolom mengikuti agents/data-dictionary.md bagian 3.5.
--}}
@php
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    $daftarKabupaten = DummyData::wilayah()['kabupaten'];
@endphp

<div class="space-y-6">
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Kawasan</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_nama_kawasan" class="{{ $kelasLabel }}">Nama Kawasan<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_kawasan" name="nama" required
                    value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: Kobalima Timur" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_kode_kawasan" class="{{ $kelasLabel }}">Kode Kawasan</label>
                <input type="text" id="{{ $awalan }}_kode_kawasan" name="kode_kawasan"
                    value="{{ old('kode_kawasan', $data['kode_kawasan'] ?? '') }}" maxlength="20"
                    placeholder="Contoh: KWS-KBT" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_kabupaten_kawasan" class="{{ $kelasLabel }}">Kabupaten<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_kabupaten_kawasan" name="kabupaten_id" required class="{{ $kelasKontrol }}">
                    <option value="">Pilih kabupaten</option>
                    @foreach ($daftarKabupaten as $kab)
                        <option value="{{ $kab['id_kabupaten'] }}"
                            @selected(old('kabupaten', $data['kabupaten'] ?? '') === $kab['nama'])>
                            {{ $kab['nama'] }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Kawasan menempel pada kabupaten, bukan pada kecamatan, sebab satu kawasan dapat
                    mencakup beberapa kecamatan sekaligus.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_luas_total" class="{{ $kelasLabel }}">Luas Total</label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_luas_total" name="luas_total"
                        value="{{ old('luas_total', $data['luas_total'] ?? '') }}" min="0" step="0.01"
                        placeholder="4250.75" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                    <span
                        class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h3 class="{{ $kelasBagian }}">Dasar Penetapan</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_nomor_sk" class="{{ $kelasLabel }}">Nomor SK Penetapan</label>
                <input type="text" id="{{ $awalan }}_nomor_sk" name="nomor_sk"
                    value="{{ old('nomor_sk', $data['nomor_sk'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: SK.123/MEN-TRANS/2015" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_tahun_penetapan" class="{{ $kelasLabel }}">Tahun Penetapan</label>
                <input type="number" id="{{ $awalan }}_tahun_penetapan" name="tahun_penetapan"
                    value="{{ old('tahun_penetapan', $data['tahun_penetapan'] ?? '') }}" min="1950"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div class="sm:col-span-2">
                <x-sim.file-upload nama="dokumen_pendukung" label="Salinan SK Penetapan"
                    keterangan="PDF atau gambar, maksimal 5 MB." />
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_keterangan_kawasan" class="{{ $kelasLabel }}">Keterangan</label>
                <textarea id="{{ $awalan }}_keterangan_kawasan" name="keterangan" rows="2" maxlength="255"
                    placeholder="Catatan tambahan mengenai kawasan ini."
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
            </div>
        </div>
    </section>
</div>
