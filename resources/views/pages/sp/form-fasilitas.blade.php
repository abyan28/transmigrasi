{{--
    Isian fasilitas SP: bangunan dan fasilitas tetap milik satuan permukiman.

    Berbeda dari inventaris yang berupa barang bergerak, fasilitas menempel
    pada lokasi sehingga memiliki koordinat (agents/rules.md bagian 4b poin 1).

    Kolom jenis_fasilitas berupa enum, terpisah dari nama_fasilitas yang tetap
    teks bebas. Enum diperlukan agar penilaian kondisi SP dapat menghitung
    otomatis, sebab teks bebas membuat "SEKOLAH DASAR" dan "SD Negeri 1" tidak
    terbaca sebagai hal yang sama. Nama bebas dipertahankan agar petugas tetap
    dapat menulis sebutan yang dikenal warga.

    Nama kolom mengikuti agents/data-dictionary.md bagian 4.2.
--}}
@php
    use App\Enums\JenisFasilitas;
    use App\Enums\Kondisi;
    use App\Enums\StatusPenyerahan;
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
        <h3 class="{{ $kelasBagian }}">Identitas Fasilitas</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_jenis_fasilitas" class="{{ $kelasLabel }}">Jenis Fasilitas<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_jenis_fasilitas" name="jenis_fasilitas" required class="{{ $kelasKontrol }}">
                    <option value="">Pilih jenis</option>
                    @foreach (JenisFasilitas::cases() as $j)
                        <option value="{{ $j->value }}"
                            @selected(old('jenis_fasilitas', $data['jenis_fasilitas'] ?? '') === $j->value)>
                            {{ $j->value }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Jenis dibaca penilaian kondisi SP, sehingga wajib dipilih dari daftar baku.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_nama_fasilitas" class="{{ $kelasLabel }}">Nama Fasilitas<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_fasilitas" name="nama_fasilitas" required
                    value="{{ old('nama_fasilitas', $data['nama_fasilitas'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: SD NEGERI KAPITAN MEO" class="{{ $kelasKontrol }}" />
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Boleh memakai sebutan yang dikenal warga setempat.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_jumlah_fasilitas" class="{{ $kelasLabel }}">Jumlah<span class="text-error-500">*</span></label>
                <input type="number" id="{{ $awalan }}_jumlah_fasilitas" name="jumlah" required
                    value="{{ old('jumlah', $data['jumlah'] ?? '') }}" min="0" step="1"
                    class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_sp_fasilitas" class="{{ $kelasLabel }}">Satuan Permukiman<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_sp_fasilitas" name="satuan_permukiman_id" required class="{{ $kelasKontrol }}">
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
                <label for="{{ $awalan }}_tahun_fasilitas" class="{{ $kelasLabel }}">Tahun Dibangun</label>
                <input type="number" id="{{ $awalan }}_tahun_fasilitas" name="tahun_perolehan"
                    value="{{ old('tahun_perolehan', $data['tahun_perolehan'] ?? '') }}" min="1950"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
            </div>
        </div>
    </section>

    <section>
        <h3 class="{{ $kelasBagian }}">Pendanaan dan Status</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_sumber_dana_fasilitas" class="{{ $kelasLabel }}">Sumber Dana</label>
                <select id="{{ $awalan }}_sumber_dana_fasilitas" name="sumber_dana" class="{{ $kelasKontrol }}">
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
                <label for="{{ $awalan }}_status_penyerahan_fasilitas" class="{{ $kelasLabel }}">Status Penyerahan</label>
                <select id="{{ $awalan }}_status_penyerahan_fasilitas" name="status_penyerahan"
                    class="{{ $kelasKontrol }}">
                    @foreach (StatusPenyerahan::cases() as $s)
                        <option value="{{ $s->value }}"
                            @selected(old('status_penyerahan', $data['status_penyerahan'] ?? '') === $s->value)>
                            {{ $s->value }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_kondisi_fasilitas" class="{{ $kelasLabel }}">Kondisi</label>
                <select id="{{ $awalan }}_kondisi_fasilitas" name="kondisi" class="{{ $kelasKontrol }}">
                    @foreach (Kondisi::cases() as $k)
                        <option value="{{ $k->value }}" @selected(old('kondisi', $data['kondisi'] ?? '') === $k->value)>
                            {{ $k->value }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Kondisi menjadi salah satu parameter penilaian kondisi SP.
                </p>
            </div>
        </div>
    </section>

    <section>
        <h3 class="{{ $kelasBagian }}">Letak</h3>
        <div class="mt-3">
            <x-sim.koordinat-input :lintang="old('lintang', $data['lintang'] ?? null)"
                :bujur="old('bujur', $data['bujur'] ?? null)" />
        </div>

        <div class="mt-4">
            <label for="{{ $awalan }}_keterangan_fasilitas" class="{{ $kelasLabel }}">Keterangan</label>
            <textarea id="{{ $awalan }}_keterangan_fasilitas" name="keterangan" rows="2" maxlength="255"
                placeholder="Catatan tambahan mengenai fasilitas ini."
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
        </div>
    </section>
</div>
