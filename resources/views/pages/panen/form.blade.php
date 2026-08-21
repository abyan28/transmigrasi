{{--
    Isian hasil panen, dipakai bersama modal tambah dan modal ubah.

    Aturan khusus modul ini: satuan volume MENGIKUTI komoditas terpilih, tidak
    dipilih bebas oleh operator (agents/rules.md bagian 9 poin 3). Satuan
    ditampilkan sebagai teks yang berubah otomatis, sehingga jagung selalu
    tercatat dalam ton dan cabai dalam kilogram.

    Volume disimpan apa adanya tanpa konversi; konversi ke ton hanya terjadi
    saat rekap agar data asli lapangan tetap terjaga (bagian 8a poin 4 dan 5).

    Nama kolom mengikuti agents/data-dictionary.md bagian 9.3.
--}}
@php
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    // Satuan baku tiap komoditas. Pada Tahap 7 dibaca dari data master
    // komoditas, bukan ditulis di sini.
    $satuanKomoditas = [
        'JAGUNG' => 'Ton',
        'PADI' => 'Ton',
        'KACANG TANAH' => 'Kuintal',
        'UBI KAYU' => 'Kuintal',
        'CABAI' => 'Kilogram',
    ];

    // Catatan tanam sebagai sumber pilihan, beserta label rakitan yang menyebut
    // lahan, musim, dan komoditasnya sekaligus. Dirakit di sini, bukan pada
    // `DummyData`, sebab bentuknya kebutuhan tampilan form ini saja.
    $daftarRiwayatTanam = collect(DummyData::riwayatTanam())
        ->map(fn ($r) => $r + [
            'label_tanam' => $r['kode_lahan'] . ' - ' . $r['musim_tanam'] . ' - ' . $r['komoditas'],
        ])
        ->all();
@endphp

<div class="space-y-6"
    x-data="{
        komoditas: @js($data['komoditas'] ?? ''),
        satuanKomoditas: @js($satuanKomoditas),
        get satuan() {
            return this.satuanKomoditas[this.komoditas] ?? '';
        },
    }">

    {{-- Bagian 1: apa yang dipanen --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Komoditas dan Lokasi</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_komoditas" class="{{ $kelasLabel }}">
                    Komoditas<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_komoditas" name="komoditas_id" x-model="komoditas" required
                    class="{{ $kelasKontrol }}">
                    <option value="">Pilih komoditas</option>
                    @foreach ($satuanKomoditas as $nama => $satuan)
                        <option value="{{ $nama }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <span class="{{ $kelasLabel }}">Satuan Panen</span>
                {{--
                    Satuan sengaja baca-saja: ia ditetapkan pada data master
                    komoditas, bukan dipilih operator, agar rekap lintas
                    komoditas tetap sepadan.
                --}}
                <p class="flex h-11 items-center rounded-lg bg-gray-50 px-4 text-theme-sm text-gray-600 dark:bg-white/5 dark:text-gray-400">
                    <span x-text="satuan || 'Mengikuti komoditas terpilih'"></span>
                </p>
                <input type="hidden" name="satuan_id" :value="satuan" />
            </div>

            <div>
                <x-sim.pilih-cari nama="transmigran_id" label="Petani" :wajib="true"
                    :awalan="$awalan" :opsi="DummyData::transmigran()" kunci="id_transmigran"
                    teks="nama_kepala_keluarga" keterangan-opsi="satuan_permukiman"
                    :terpilih="old('transmigran_id', $data['transmigran_id'] ?? null)"
                    placeholder="Pilih petani" />
            </div>

            <div>
                {{--
                    DIBACA DARI `riwayatTanam()`, bukan daftar musim yang
                    ditulis tangan.

                    Isian ini sempat memuat tiga label musim yang ditulis
                    langsung sebagai larik harfiah, sementara namanya
                    `riwayat_tanam_id`. Dua hal keliru sekaligus: nilai yang
                    terkirim berupa teks label, bukan id; dan daftarnya tidak
                    pernah bertambah ketika musim tanam baru didata, sehingga
                    panen musim berikutnya tidak dapat dicatat sama sekali.

                    Yang dipilih adalah CATATAN TANAM, bukan musimnya, sebab
                    `hasil_panen.riwayat_tanam_id` menentukan lahan, musim, dan
                    komoditas sekaligus (kamus data 9.3). Labelnya menyebut
                    ketiganya agar petugas tahu persis penanaman mana yang
                    sedang dipanen.
                --}}
                <x-sim.pilih-cari nama="riwayat_tanam_id" label="Catatan Tanam" :wajib="true"
                    :awalan="$awalan" :opsi="$daftarRiwayatTanam" kunci="id_riwayat_tanam"
                    teks="label_tanam" keterangan-opsi="petani, satuan_permukiman"
                    :terpilih="old('riwayat_tanam_id', $data['riwayat_tanam_id'] ?? null)"
                    placeholder="Pilih catatan tanam"
                    keterangan="Menentukan lahan, musim, dan komoditas sekaligus." />
            </div>

            <div class="sm:col-span-2">
                <x-sim.wilayah-picker
                    :daftar-kawasan="[['id' => 1, 'nama' => 'Kobalima Timur']]"
                    :daftar-sp="collect(DummyData::satuanPermukiman())
                        ->map(fn ($s) => ['id' => $s['id_satuan_permukiman'], 'nama' => $s['nama'], 'kawasan_id' => 1])
                        ->all()"
                    :sp-terpilih="old('satuan_permukiman_id', $data['satuan_permukiman_id'] ?? null)" />
            </div>
        </div>
    </section>

    {{-- Bagian 2: hasil panen --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Hasil Panen</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_tanggal_panen" class="{{ $kelasLabel }}">
                    Tanggal Panen<span class="text-error-500">*</span>
                </label>
                <input type="date" id="{{ $awalan }}_tanggal_panen" name="tanggal_panen"
                    value="{{ old('tanggal_panen', $data['tanggal_panen'] ?? '') }}" required
                    max="{{ date('Y-m-d') }}" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_volume" class="{{ $kelasLabel }}">
                    Volume Panen<span class="text-error-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="{{ $awalan }}_volume" name="volume"
                        value="{{ old('volume', $data['volume'] ?? '') }}" required min="0.001" step="0.001"
                        placeholder="0.000" class="{{ $kelasKontrol }} tabular-nums pr-20" />
                    <span
                        class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400"
                        x-text="satuan"></span>
                </div>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Dicatat sampai 3 angka desimal agar panen berskala kecil tetap terekam.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_kualitas" class="{{ $kelasLabel }}">Kualitas Panen</label>
                <select id="{{ $awalan }}_kualitas" name="kualitas" class="{{ $kelasKontrol }}">
                    <option value="">Pilih kualitas</option>
                    @foreach (\App\Support\DummyData::opsiReferensi(\App\Enums\JenisReferensi::KualitasPanen) as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('kualitas', $data['kualitas'] ?? '') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_harga_jual" class="{{ $kelasLabel }}">Harga Jual per Satuan</label>
                <div class="relative">
                    <span
                        class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">
                        Rp
                    </span>
                    <input type="number" id="{{ $awalan }}_harga_jual" name="harga_jual"
                        value="{{ old('harga_jual', $data['harga_jual'] ?? '') }}" min="0" step="100"
                        placeholder="0" class="{{ $kelasKontrol }} tabular-nums pl-10" />
                </div>
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_satuan_lokal" class="{{ $kelasLabel }}">Keterangan Satuan Lokal</label>
                <input type="text" id="{{ $awalan }}_satuan_lokal" name="keterangan_satuan_lokal"
                    value="{{ old('keterangan_satuan_lokal', $data['keterangan_satuan_lokal'] ?? '') }}"
                    maxlength="255" placeholder="Contoh: setara 13 karung ukuran sedang"
                    class="{{ $kelasKontrol }}" />
                {{--
                    Satuan lokal dicatat sebagai keterangan, bukan sebagai satuan
                    baku, agar rekap tetap konsisten (rules.md bagian 8a poin 6).
                --}}
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Satuan lokal seperti karung atau ikat dicatat di sini, bukan sebagai satuan panen.
                </p>
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_keterangan" class="{{ $kelasLabel }}">Catatan</label>
                <textarea id="{{ $awalan }}_keterangan" name="keterangan" rows="2" maxlength="1000"
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
            </div>
        </div>
    </section>

    {{-- Bagian 3: dokumentasi --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Dokumentasi</h3>
        <div class="mt-3">
            <x-sim.file-upload nama="dokumen_pendukung" label="Foto Hasil Panen" :hanya-gambar="true"
                nama-dokumen="Foto Panen" :nama-pemilik="$data['petani'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null" />
        </div>
    </section>
</div>
