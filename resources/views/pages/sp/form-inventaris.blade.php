{{--
    Isian inventaris SP: barang bergerak milik satuan permukiman.

    Dipisahkan dari fasilitas SP karena berbeda sifat. Inventaris berupa
    barang yang dapat dipindah dan dihitung per unit, sedangkan fasilitas
    berupa bangunan yang menempel pada lokasi (agents/rules.md bagian 4b
    poin 1).

    Status penyerahan penting karena aset yang belum diserahkan masih menjadi
    tanggung jawab pihak pembangun, bukan warga.

    Nama kolom mengikuti agents/data-dictionary.md bagian 4.1.
--}}
@php
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
        <h3 class="{{ $kelasBagian }}">Identitas Barang</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_nama_barang" class="{{ $kelasLabel }}">Nama Barang<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_barang" name="nama_barang" required
                    value="{{ old('nama_barang', $data['nama_barang'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: MEJA KANTOR" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_jumlah_inventaris" class="{{ $kelasLabel }}">Jumlah<span class="text-error-500">*</span></label>
                <input type="number" id="{{ $awalan }}_jumlah_inventaris" name="jumlah" required
                    value="{{ old('jumlah', $data['jumlah'] ?? '') }}" min="0" step="1"
                    class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_satuan_barang" class="{{ $kelasLabel }}">Satuan Barang</label>
                <input type="text" id="{{ $awalan }}_satuan_barang" name="satuan_barang"
                    value="{{ old('satuan_barang', $data['satuan_barang'] ?? '') }}" maxlength="20"
                    placeholder="Contoh: unit, set, buah" class="{{ $kelasKontrol }}" />
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Satuan bebas, terpisah dari satuan panen yang dipakai rekap produksi.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_sp_inventaris" class="{{ $kelasLabel }}">Satuan Permukiman<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_sp_inventaris" name="satuan_permukiman_id" required class="{{ $kelasKontrol }}">
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
                <label for="{{ $awalan }}_tahun_inventaris" class="{{ $kelasLabel }}">Tahun Perolehan</label>
                <input type="number" id="{{ $awalan }}_tahun_inventaris" name="tahun_perolehan"
                    value="{{ old('tahun_perolehan', $data['tahun_perolehan'] ?? '') }}" min="1950"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
            </div>
        </div>
    </section>

    <section>
        <h3 class="{{ $kelasBagian }}">Pendanaan dan Status</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_sumber_dana_inventaris" class="{{ $kelasLabel }}">Sumber Dana</label>
                <select id="{{ $awalan }}_sumber_dana_inventaris" name="sumber_dana" class="{{ $kelasKontrol }}">
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
                <label for="{{ $awalan }}_status_penyerahan_inventaris" class="{{ $kelasLabel }}">Status Penyerahan</label>
                <select id="{{ $awalan }}_status_penyerahan_inventaris" name="status_penyerahan"
                    class="{{ $kelasKontrol }}">
                    @foreach (StatusPenyerahan::cases() as $s)
                        <option value="{{ $s->value }}"
                            @selected(old('status_penyerahan', $data['status_penyerahan'] ?? '') === $s->value)>
                            {{ $s->value }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Aset yang belum diserahkan masih menjadi tanggung jawab pihak pembangun.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_kondisi_inventaris" class="{{ $kelasLabel }}">Kondisi</label>
                <select id="{{ $awalan }}_kondisi_inventaris" name="kondisi" class="{{ $kelasKontrol }}">
                    @foreach (Kondisi::cases() as $k)
                        <option value="{{ $k->value }}" @selected(old('kondisi', $data['kondisi'] ?? '') === $k->value)>
                            {{ $k->value }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_keterangan_inventaris" class="{{ $kelasLabel }}">Catatan</label>
                <textarea id="{{ $awalan }}_keterangan_inventaris" name="keterangan" rows="2" maxlength="255"
                    placeholder="Catatan tambahan, misalnya penempatan barang."
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
            </div>

            {{--
                DUA SLOT TERPISAH, mengikuti pola infrastruktur (kamus data
                10.1). Keduanya menjawab hal berbeda: foto merekam kondisi
                barang saat pendataan, dokumen menyimpan berkas
                administratifnya. Satu slot untuk keduanya membuat foto kondisi
                tertimpa berita acara, dan kehilangannya berlangsung diam-diam
                sebab form tetap tersimpan.

                Kolom `foto` ditambahkan 2026-08-20; `dokumen_pendukung` sudah
                lama ada pada kamus data tetapi baru mendapat isian 2026-08-19.
            --}}
            <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                <x-sim.file-upload nama="foto" label="Foto Kondisi" :hanya-gambar="true"
                    nama-dokumen="Foto Inventaris" :nama-pemilik="$data['nama_barang'] ?? null"
                    :berkas-saat-ini="$data['foto'] ?? null"
                    keterangan="Dokumentasi keadaan barang saat pendataan." />

                <x-sim.file-upload nama="dokumen_pendukung" label="Dokumen Pendukung"
                    nama-dokumen="Dokumen Inventaris" :nama-pemilik="$data['nama_barang'] ?? null"
                    :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                    keterangan="Berita acara penyerahan atau bukti pengadaan barang." />
            </div>
        </div>
    </section>
</div>
