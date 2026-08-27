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
        use App\Enums\Kondisi;
    use App\Enums\SumberDana;
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    // `$daftarSp`, `$opsiJenisInfrastruktur`, `$opsiSumberDana`, dan
    // `$opsiKondisi` disuplai ViewServiceProvider, sebab berkas ini
    // disisipkan dari halaman daftar maupun halaman rincian.
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
                    @foreach ($opsiJenisInfrastruktur as $nilaiJenis => $labelJenis)
                        <option value="{{ $nilaiJenis }}" @selected(old('jenis', $data['jenis'] ?? '') === $nilaiJenis)>
                            {{ $labelJenis }}
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
                    @foreach ($opsiSumberDana as $nilaiRef => $labelRef)
                        <option value="{{ $nilaiRef }}"
                            @selected(old('sumber_dana', $data['sumber_dana'] ?? '') === $nilaiRef)>
                            {{ $nilaiRef }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_kondisi_infrastruktur" class="{{ $kelasLabel }}">Kondisi Saat Didata</label>
                <select id="{{ $awalan }}_kondisi_infrastruktur" name="kondisi" class="{{ $kelasKontrol }}">
                    @foreach ($opsiKondisi as $nilaiRef => $labelRef)
                        <option value="{{ $nilaiRef }}" @selected(old('kondisi', $data['kondisi'] ?? '') === $nilaiRef)>
                            {{ $nilaiRef }}
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

    {{--
        Catatan. Kolom `keterangan` sudah lama ada pada kamus data 10.1 tetapi
        belum pernah punya isian, sehingga hal-hal yang tidak tertampung kolom
        baku tidak dapat dicatat ke mana pun.

        Labelnya "Catatan", diseragamkan 2026-08-20 dari empat penamaan berbeda
        yang sempat dipakai bergantian.
    --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Catatan</h3>
        <div class="mt-3">
            <label for="{{ $awalan }}_keterangan" class="{{ $kelasLabel }}">Catatan</label>
            <textarea id="{{ $awalan }}_keterangan" name="keterangan" rows="3" maxlength="1000"
                placeholder="Contoh: bagian hilir saluran tertimbun longsor sejak Januari."
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
        </div>
    </section>


    {{--
        Unggahan diletakkan PALING BAWAH, setelah catatan (ui-spec.md 6.4a
        poin 5). Sebelumnya ia menumpang seksi Pendanaan dan Kondisi, dan
        posisinya di tengah memutus alur pengisian: isian berkas menuntut
        perhatian lebih lama daripada isian teks.
    --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Dokumentasi</h3>
        {{--
            Dua kolom terpisah pada data-dictionary.md 10.1, dan keduanya
            menjawab hal berbeda: `foto` merekam kondisi lapangan saat
            pendataan, sedangkan `dokumen_pendukung` menyimpan berkas
            administratifnya. Menggabungkan keduanya membuat foto kondisi
            tertimpa dokumen pengadaan, atau sebaliknya.
        --}}
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <x-sim.file-upload nama="foto" label="Foto Kondisi" :hanya-gambar="true"
                nama-dokumen="Foto Infrastruktur" :nama-pemilik="$data['nama'] ?? null"
                :berkas-saat-ini="$data['foto'] ?? null"
                keterangan="Dokumentasi kondisi aset saat pendataan." />

            <x-sim.file-upload nama="dokumen_pendukung" label="Dokumen Pendukung"
                nama-dokumen="Dokumen Infrastruktur" :nama-pemilik="$data['nama'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                keterangan="Dokumen pembangunan, serah terima, atau pemeliharaan." />
        </div>
    </section>
</div>