{{--
    Isian data komoditas.

    Satuan panen dipilih di sini dan menjadi SATUAN BAKU komoditas tersebut
    (agents/rules.md bagian 8a). Form panen membacanya dari sini dan tidak
    mengizinkan penggantian, agar rekap lintas komoditas dapat dijumlahkan.

    Faktor konversi ke ton ditampilkan sebagai keterangan agar petugas melihat
    dampak pilihannya sebelum menyimpan, bukan setelah rekap tampak janggal.

    Nama kolom mengikuti agents/data-dictionary.md bagian 5.2.
--}}
@php
    use App\Enums\TipeKomoditas;
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    $daftarSatuan = DummyData::satuan();

    // Faktor konversi dibaca Alpine agar keterangannya ikut berubah saat
    // satuan diganti, tanpa perlu memuat ulang halaman.
    $faktorSatuan = collect($daftarSatuan)
        ->mapWithKeys(fn ($s) => [(string) $s['id_satuan'] => ['nama' => $s['nama'], 'faktor' => $s['faktor_ke_ton']]])
        ->all();
@endphp

<div class="space-y-6"
    x-data="{
        satuanId: @js((string) old('satuan_id', $data['satuan_id'] ?? '')),
        faktor: @js($faktorSatuan),
        get terpilih() {
            return this.faktor[this.satuanId] ?? null;
        },
    }">

    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Komoditas</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_nama_komoditas" class="{{ $kelasLabel }}">Nama Komoditas</label>
                <input type="text" id="{{ $awalan }}_nama_komoditas" name="nama"
                    value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: JAGUNG" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_tipe" class="{{ $kelasLabel }}">Tipe</label>
                <select id="{{ $awalan }}_tipe" name="tipe" class="{{ $kelasKontrol }}">
                    <option value="">Pilih tipe</option>
                    @foreach (TipeKomoditas::cases() as $t)
                        <option value="{{ $t->value }}" @selected(old('tipe', $data['tipe'] ?? '') === $t->value)>
                            {{ $t->value }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_deskripsi_komoditas" class="{{ $kelasLabel }}">Keterangan</label>
                <textarea id="{{ $awalan }}_deskripsi_komoditas" name="deskripsi" rows="2" maxlength="255"
                    placeholder="Penjelasan singkat, misalnya sebaran penanaman atau kekhasan komoditas."
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('deskripsi', $data['deskripsi'] ?? '') }}</textarea>
            </div>
        </div>
    </section>

    <section>
        <h3 class="{{ $kelasBagian }}">Satuan Panen Baku</h3>
        <div class="mt-3 space-y-4">
            <div>
                <label for="{{ $awalan }}_satuan_id_komoditas" class="{{ $kelasLabel }}">Satuan</label>
                <select id="{{ $awalan }}_satuan_id_komoditas" name="satuan_id" x-model="satuanId"
                    class="{{ $kelasKontrol }}">
                    <option value="">Pilih satuan</option>
                    @foreach ($daftarSatuan as $s)
                        <option value="{{ $s['id_satuan'] }}">
                            {{ $s['nama'] }} ({{ $s['simbol'] }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Seluruh pencatatan panen komoditas ini akan memakai satuan tersebut dan tidak dapat diganti
                    saat mengisi form panen.
                </p>
            </div>

            {{--
                Pratinjau konversi. Menampilkan dampak pilihan sebelum disimpan,
                sebab kekeliruan satuan baru terlihat berbulan kemudian ketika
                rekap lintas komoditas tampak janggal.
            --}}
            <div x-show="terpilih" x-cloak x-transition
                class="rounded-lg bg-gray-50 p-4 dark:bg-white/[0.03]">
                <p class="text-theme-xs text-gray-600 dark:text-gray-400">
                    Panen sebesar <span class="font-medium tabular-nums text-gray-800 dark:text-white/90">100</span>
                    <span class="font-medium text-gray-800 dark:text-white/90" x-text="terpilih?.nama"></span>
                    akan tercatat setara
                    <span class="font-medium tabular-nums text-gray-800 dark:text-white/90"
                        x-text="terpilih ? (100 * terpilih.faktor).toLocaleString('id-ID') : ''"></span>
                    ton pada rekap gabungan.
                </p>
            </div>

            <div>
                <label class="flex items-start gap-2.5">
                    <input type="checkbox" name="is_unggulan" value="1"
                        @checked(old('is_unggulan', $data['is_unggulan'] ?? false))
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700" />
                    <span class="text-theme-sm text-gray-700 dark:text-gray-300">
                        Komoditas unggulan
                        <span class="block text-theme-xs text-gray-500 dark:text-gray-400">
                            Ditandai khusus pada dashboard dan daftar komoditas.
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </section>
</div>