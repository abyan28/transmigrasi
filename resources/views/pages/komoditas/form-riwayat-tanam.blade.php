{{--
    Isian riwayat tanam.

    Riwayat tanam adalah JEMBATAN dari lahan menuju hasil panen. Lokasi
    produksi tidak disimpan pada tabel panen, melainkan terbaca lewat rantai
    riwayat tanam ke lahan ke satuan permukiman (agents/tasklist.md Task 2.17).
    Karena itu lahan wajib dipilih di sini, bukan diisi belakangan.

    Nama kolom mengikuti agents/data-dictionary.md bagian 9.1.
--}}
@php
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';

    $daftarLahan = DummyData::lahan();
    $daftarMusim = DummyData::musimTanam();
    $daftarKomoditas = DummyData::komoditas();
@endphp

<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-sim.pilih-cari nama="lahan_id" label="Lahan" :wajib="true"
                :awalan="$awalan" :opsi="$daftarLahan" kunci="id_lahan"
                teks="kode_lahan" keterangan-opsi="pemilik, satuan_permukiman"
                :terpilih="old('lahan_id', $data['lahan_id'] ?? null)"
                placeholder="Pilih lahan"
                keterangan="Lahan menentukan lokasi produksi. Hasil panen membaca satuan permukimannya lewat catatan ini, bukan menyimpannya sendiri." />
        </div>

        <div>
            <label for="{{ $awalan }}_musim_riwayat" class="{{ $kelasLabel }}">Musim Tanam<span class="text-error-500">*</span></label>
            <select id="{{ $awalan }}_musim_riwayat" name="musim_tanam_id" required class="{{ $kelasKontrol }}">
                <option value="">Pilih musim tanam</option>
                @foreach ($daftarMusim as $m)
                    <option value="{{ $m['id_musim_tanam'] }}"
                        @selected(old('musim_tanam', $data['musim_tanam'] ?? '') === $m['label'])>
                        {{ $m['label'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="{{ $awalan }}_komoditas_riwayat" class="{{ $kelasLabel }}">Komoditas<span class="text-error-500">*</span></label>
            <select id="{{ $awalan }}_komoditas_riwayat" name="komoditas_id" required class="{{ $kelasKontrol }}">
                <option value="">Pilih komoditas</option>
                @foreach ($daftarKomoditas as $k)
                    <option value="{{ $k['id_komoditas'] }}"
                        @selected(old('komoditas', $data['komoditas'] ?? '') === $k['nama'])>
                        {{ $k['nama'] }} (satuan panen {{ $k['satuan'] }})
                    </option>
                @endforeach
            </select>
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Satuan panen mengikuti komoditas dan tidak dapat diubah saat mencatat hasil.
            </p>
        </div>

        <div>
            <label for="{{ $awalan }}_luas_tanam" class="{{ $kelasLabel }}">Luas Tanam</label>
            <div class="relative">
                <input type="number" id="{{ $awalan }}_luas_tanam" name="luas_tanam"
                    value="{{ old('luas_tanam', $data['luas_tanam'] ?? '') }}" min="0" step="0.01"
                    placeholder="1.50" class="{{ $kelasKontrol }} tabular-nums pr-12" />
                <span
                    class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-theme-sm text-gray-500 dark:text-gray-400">ha</span>
            </div>
        </div>

        <div>
            <label for="{{ $awalan }}_tanggal_tanam" class="{{ $kelasLabel }}">Tanggal Tanam</label>
            <input type="date" id="{{ $awalan }}_tanggal_tanam" name="tanggal_tanam"
                value="{{ old('tanggal_tanam', $data['tanggal_tanam'] ?? '') }}" max="{{ date('Y-m-d') }}"
                class="{{ $kelasKontrol }}" />
        </div>

        <div class="sm:col-span-2">
            <label for="{{ $awalan }}_keterangan_riwayat" class="{{ $kelasLabel }}">Keterangan</label>
            <textarea id="{{ $awalan }}_keterangan_riwayat" name="keterangan" rows="2" maxlength="255"
                placeholder="Catatan tambahan mengenai penanaman ini."
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
        </div>
    </div>
</div>
