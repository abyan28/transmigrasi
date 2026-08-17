{{--
    Isian data aset infrastruktur SP.

    Modul pendataan aset, bukan pelaporan masalah. Form ini karena itu TIDAK
    memuat kolom keluhan maupun tombol lapor kerusakan: kondisi diisi petugas
    berdasarkan pengamatan saat pendataan, sedangkan keluhan warga masuk lewat
    fitur pengaduan (agents/tasklist.md Task 2.18).

    Jenis infrastruktur memakai sepuluh nilai enum yang sama dengan penilaian
    kondisi SP, sehingga aset yang didata di sini langsung terbaca oleh
    penilaian tanpa pemetaan tambahan.

    Nama kolom mengikuti agents/data-dictionary.md bagian 10.1.
--}}
@php
    use App\Enums\JenisInfrastruktur;
    use App\Enums\Kondisi;
    use App\Enums\SumberDana;
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    $daftarSp = DummyData::satuanPermukiman();
@endphp

<div class="space-y-6">

    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Aset</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_nama_infrastruktur" class="{{ $kelasLabel }}">Nama Aset<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_infrastruktur" name="nama" required
                    value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: SALURAN IRIGASI BLOK A" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_jenis_infrastruktur" class="{{ $kelasLabel }}">Jenis<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_jenis_infrastruktur" name="jenis" required class="{{ $kelasKontrol }}">
                    <option value="">Pilih jenis</option>
                    @foreach (JenisInfrastruktur::cases() as $j)
                        <option value="{{ $j->value }}" @selected(old('jenis', $data['jenis'] ?? '') === $j->value)>
                            {{ $j->value }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Jenis menentukan bobot aset pada penilaian kondisi satuan permukiman.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_kapasitas" class="{{ $kelasLabel }}">Kapasitas atau Ukuran</label>
                <input type="text" id="{{ $awalan }}_kapasitas" name="kapasitas"
                    value="{{ old('kapasitas', $data['kapasitas'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: Panjang 1,2 km" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_satuan_permukiman_infrastruktur" class="{{ $kelasLabel }}">Satuan Permukiman<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_satuan_permukiman_infrastruktur" name="satuan_permukiman_id" required
                    class="{{ $kelasKontrol }}">
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
                <label for="{{ $awalan }}_tahun_perolehan_infrastruktur" class="{{ $kelasLabel }}">Tahun Dibangun</label>
                <input type="number" id="{{ $awalan }}_tahun_perolehan_infrastruktur" name="tahun_perolehan"
                    value="{{ old('tahun_perolehan', $data['tahun_perolehan'] ?? '') }}" min="1900"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
            </div>
        </div>
    </section>

    <section>
        <h3 class="{{ $kelasBagian }}">Pendanaan dan Kondisi</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_sumber_dana_infrastruktur" class="{{ $kelasLabel }}">Sumber Dana</label>
                <select id="{{ $awalan }}_sumber_dana_infrastruktur" name="sumber_dana" class="{{ $kelasKontrol }}">
                    <option value="">Pilih sumber dana</option>
                    @foreach (SumberDana::cases() as $s)
                        <option value="{{ $s->value }}"
                            @selected(old('sumber_dana', $data['sumber_dana'] ?? '') === $s->value)>
                            {{ $s->value }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_kondisi_infrastruktur" class="{{ $kelasLabel }}">Kondisi Saat Didata</label>
                <select id="{{ $awalan }}_kondisi_infrastruktur" name="kondisi" class="{{ $kelasKontrol }}">
                    @foreach (Kondisi::cases() as $k)
                        <option value="{{ $k->value }}" @selected(old('kondisi', $data['kondisi'] ?? '') === $k->value)>
                            {{ $k->value }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{--
            Penegasan batas modul, diletakkan di dalam form agar terbaca saat
            petugas mengisi, bukan hanya di halaman rincian.
        --}}
        <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
            Kondisi diisi berdasarkan pengamatan petugas saat pendataan. Keluhan warga mengenai kerusakan
            disampaikan lewat fitur pengaduan, bukan lewat form ini, agar penanganannya terlacak beserta
            riwayat tindak lanjutnya.
        </p>
    </section>
</div>
