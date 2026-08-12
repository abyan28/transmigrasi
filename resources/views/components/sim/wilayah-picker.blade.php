{{--
    Pemilih wilayah bertingkat.

    Hierarki wilayah bercabang dua di tingkat kabupaten (agents/rules.md 4a),
    sehingga komponen ini punya dua mode:

    1. mode `operasional` (bawaan) untuk form data operasional:
           Kawasan Transmigrasi -> SP
       Cukup dua tingkat karena seluruh data operasional menaut ke SP.

    2. mode `pendaftaran-sp` khusus form pendaftaran SP baru:
           Kawasan Transmigrasi
           Provinsi -> Kabupaten -> Kecamatan -> Desa

    Kecamatan TIDAK pernah diisi manual. Setelah desa dipilih, kecamatan tampil
    sebagai teks baca-saja hasil pembacaan dari desa tersebut.

    Pemakaian:
        <x-sim.wilayah-picker :daftar-sp="$daftarSp" :daftar-kawasan="$daftarKawasan" />
        <x-sim.wilayah-picker mode="pendaftaran-sp" :daftar-desa="$daftarDesa" />
--}}
@props([
    'mode' => 'operasional',
    'daftarKawasan' => [],
    'daftarSp' => [],
    'daftarDesa' => [],
    'kawasanTerpilih' => null,
    'spTerpilih' => null,
    'desaTerpilih' => null,
    'wajib' => true,
])

@php
    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 disabled:bg-gray-50 disabled:text-gray-400 dark:border-gray-700 dark:text-white/90 dark:disabled:bg-white/5';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';

    // Bila hanya ada satu kawasan, tingkat kawasan terisi otomatis dan
    // disembunyikan agar operator tidak memilih hal yang sama berulang kali.
    $kawasanTunggal = count($daftarKawasan) === 1;
@endphp

@if ($mode === 'pendaftaran-sp')
    {{-- Mode pendaftaran SP: menautkan SP ke kedua cabang hierarki sekaligus --}}
    <div x-data="{
            desa: @js($desaTerpilih),
            daftarDesa: @js($daftarDesa),
            get kecamatan() {
                const d = this.daftarDesa.find((x) => String(x.id) === String(this.desa));
                return d?.kecamatan ?? '';
            },
            get kabupaten() {
                const d = this.daftarDesa.find((x) => String(x.id) === String(this.desa));
                return d?.kabupaten ?? '';
            },
        }"
        {{ $attributes->merge(['class' => 'grid gap-4 sm:grid-cols-2']) }}>

        <div class="sm:col-span-2">
            <label for="kawasan_id" class="{{ $kelasLabel }}">
                Kawasan Transmigrasi{!! $wajib ? '<span class="text-error-500">*</span>' : '' !!}
            </label>
            <select id="kawasan_id" name="kawasan_id" class="{{ $kelasKontrol }}" @if ($wajib) required @endif>
                <option value="">Pilih kawasan</option>
                @foreach ($daftarKawasan as $kawasan)
                    <option value="{{ $kawasan['id'] }}" @selected($kawasanTerpilih == $kawasan['id'])>
                        {{ $kawasan['nama'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="sm:col-span-2">
            <label for="desa_id" class="{{ $kelasLabel }}">
                Desa{!! $wajib ? '<span class="text-error-500">*</span>' : '' !!}
            </label>
            <select id="desa_id" name="desa_id" x-model="desa" class="{{ $kelasKontrol }}"
                @if ($wajib) required @endif>
                <option value="">Pilih desa</option>
                @foreach ($daftarDesa as $desa)
                    <option value="{{ $desa['id'] }}">{{ $desa['nama'] }}</option>
                @endforeach
            </select>
        </div>

        {{-- Kecamatan dan kabupaten hanya ditampilkan, tidak diisi manual --}}
        <div>
            <span class="{{ $kelasLabel }}">Kecamatan</span>
            <p class="flex h-11 items-center rounded-lg bg-gray-50 px-4 text-theme-sm text-gray-600 dark:bg-white/5 dark:text-gray-400">
                <span x-text="kecamatan || 'Terisi otomatis dari desa'"></span>
            </p>
        </div>

        <div>
            <span class="{{ $kelasLabel }}">Kabupaten</span>
            <p class="flex h-11 items-center rounded-lg bg-gray-50 px-4 text-theme-sm text-gray-600 dark:bg-white/5 dark:text-gray-400">
                <span x-text="kabupaten || 'Terisi otomatis dari desa'"></span>
            </p>
        </div>
    </div>
@else
    {{-- Mode operasional: cukup kawasan dan SP --}}
    <div x-data="{
            kawasan: @js($kawasanTerpilih ?? ($kawasanTunggal ? ($daftarKawasan[0]['id'] ?? null) : null)),
            sp: @js($spTerpilih),
            daftarSp: @js($daftarSp),
            get spTersaring() {
                if (!this.kawasan) return this.daftarSp;
                return this.daftarSp.filter((s) => String(s.kawasan_id) === String(this.kawasan));
            },
        }"
        x-effect="if (sp && !spTersaring.some((s) => String(s.id) === String(sp))) sp = null"
        {{ $attributes->merge(['class' => 'grid gap-4 ' . ($kawasanTunggal ? '' : 'sm:grid-cols-2')]) }}>

        @if ($kawasanTunggal)
            {{-- Satu kawasan saja, cukup disimpan tanpa ditampilkan sebagai pilihan --}}
            <input type="hidden" name="kawasan_id" :value="kawasan" />
        @else
            <div>
                <label for="kawasan_id" class="{{ $kelasLabel }}">
                    Kawasan Transmigrasi{!! $wajib ? '<span class="text-error-500">*</span>' : '' !!}
                </label>
                <select id="kawasan_id" name="kawasan_id" x-model="kawasan" class="{{ $kelasKontrol }}"
                    @if ($wajib) required @endif>
                    <option value="">Pilih kawasan</option>
                    @foreach ($daftarKawasan as $kawasan)
                        <option value="{{ $kawasan['id'] }}">{{ $kawasan['nama'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label for="satuan_permukiman_id" class="{{ $kelasLabel }}">
                Satuan Permukiman{!! $wajib ? '<span class="text-error-500">*</span>' : '' !!}
            </label>
            <select id="satuan_permukiman_id" name="satuan_permukiman_id" x-model="sp"
                class="{{ $kelasKontrol }}" @if ($wajib) required @endif>
                <option value="">Pilih satuan permukiman</option>
                <template x-for="item in spTersaring" :key="item.id">
                    <option :value="item.id" x-text="item.nama"></option>
                </template>
            </select>
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Kecamatan dan desa mengikuti satuan permukiman yang dipilih.
            </p>
        </div>
    </div>
@endif
