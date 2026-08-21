{{--
    Isian data satuan permukiman.

    SP menempel pada DUA induk sekaligus: desa (hierarki administratif) dan
    kawasan transmigrasi (hierarki program). Percabangan ini tidak lazim dan
    mudah disalahpahami, sehingga keduanya diminta terpisah beserta
    penjelasannya (agents/erd.md bagian 7.0).

    Keempat isian batas wilayah dicabut 2026-08-18. Isinya berupa sebutan
    naratif seperti "Hutan lindung", bukan koordinat, sehingga tidak pernah
    dipakai perhitungan, indikator, maupun peta mana pun; satu-satunya
    kegunaannya adalah menyalin isi berkas penetapan. Rinciannya pada
    `notes.md` bagian 6.

    Nama kolom mengikuti agents/data-dictionary.md bagian 3.6.
--}}
@php
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    $wilayah = DummyData::wilayah();
    $daftarDesa = $wilayah['desa'];
    $daftarKawasan = DummyData::kawasan();
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

    {{-- Bagian 5: catatan --}}
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
</div>
