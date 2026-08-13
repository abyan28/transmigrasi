{{--
    Isian data satuan permukiman.

    SP menempel pada DUA induk sekaligus: desa (hierarki administratif) dan
    kawasan transmigrasi (hierarki program). Percabangan ini tidak lazim dan
    mudah disalahpahami, sehingga keduanya diminta terpisah beserta
    penjelasannya (agents/erd.md bagian 7.0).

    Batas wilayah disimpan sebagai empat teks bebas, bukan koordinat poligon,
    karena yang tersedia di lapangan adalah sebutan batas menurut warga,
    misalnya "berbatasan dengan Sungai Benanain".

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
                <label for="{{ $awalan }}_nama_sp" class="{{ $kelasLabel }}">Nama SP</label>
                <input type="text" id="{{ $awalan }}_nama_sp" name="nama"
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
                <label for="{{ $awalan }}_desa_id" class="{{ $kelasLabel }}">Desa</label>
                <select id="{{ $awalan }}_desa_id" name="desa_id" class="{{ $kelasKontrol }}">
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
                <label for="{{ $awalan }}_kawasan_id" class="{{ $kelasLabel }}">Kawasan Transmigrasi</label>
                <select id="{{ $awalan }}_kawasan_id" name="kawasan_id" class="{{ $kelasKontrol }}">
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

    {{-- Bagian 3: koordinat dan batas --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Letak dan Batas Wilayah</h3>

        <div class="mt-3">
            <x-sim.koordinat-input :lintang="old('lintang', $data['lintang'] ?? null)"
                :bujur="old('bujur', $data['bujur'] ?? null)" />
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            @foreach (['utara' => 'Batas Utara', 'timur' => 'Batas Timur', 'selatan' => 'Batas Selatan', 'barat' => 'Batas Barat'] as $arah => $label)
                <div>
                    <label for="{{ $awalan }}_batas_{{ $arah }}" class="{{ $kelasLabel }}">{{ $label }}</label>
                    <input type="text" id="{{ $awalan }}_batas_{{ $arah }}" name="batas_{{ $arah }}"
                        value="{{ old('batas_' . $arah, $data['batas_' . $arah] ?? '') }}" maxlength="100"
                        placeholder="Contoh: Sungai Benanain" class="{{ $kelasKontrol }}" />
                </div>
            @endforeach
        </div>

        <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
            Batas ditulis sebagai sebutan yang dikenal warga, bukan koordinat, sebab itulah bentuk yang
            tersedia pada berkas penetapan dan yang dipahami di lapangan.
        </p>
    </section>

    {{-- Bagian 4: keterangan --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Keterangan</h3>
        <div class="mt-3">
            <textarea id="{{ $awalan }}_keterangan_sp" name="keterangan" rows="2" maxlength="255"
                placeholder="Catatan tambahan mengenai satuan permukiman ini."
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
        </div>
    </section>
</div>