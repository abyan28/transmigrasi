{{--
    Bilah penyaring di kepala satu halaman laporan.

    Ditambahkan Putaran 3 D3 (2026-08-29, rules.md 12 poin 5). BUKAN laci: di
    halaman laporan penyaring adalah kontrol utama, bukan pelengkap, sehingga
    ia selalu tampak. Penyaringan dijalankan Alpine di sisi peramban
    (resources/js/filter-laporan.js) -- query string tidak dilayani GitHub
    Pages (notes.md 1b.5).

    Komponen ini TIDAK membuat cakupan Alpine sendiri. `x-sim.kerangka-laporan`
    yang memasang `x-data="filterLaporan(konfig)"` pada <article>, sehingga
    kepala dokumen (kalimat cakupan) dan isi tabel berada di cakupan yang sama.

    Kelasnya memakai `.cetak-sembunyi`: dokumen yang dicetak hanya memuat
    kertasnya, tanpa kontrol.

    Prop:
    - sp          : list<array{id,nama}>. Kosong menyembunyikan pemilih SP.
    - tahun       : bool. Bila true, tampilkan sepasang pemilih rentang tahun.
    - labelTahun  : label untuk pasangan tahun, mis. "Tahun Kedatangan".
    - daftarTahun : list<int> opsi tahun.
    - dimensi     : list<array{kunci,label,opsi:list<string>}> pemilih khas
                    laporan (status, komoditas, jenis, dst).
--}}
@props([
    'sp' => [],
    'tahun' => false,
    'tahunTunggal' => false,
    'tahunBawaan' => null,
    'labelTahun' => 'Tahun',
    'daftarTahun' => [],
    'dimensi' => [],
])

@php
    $kelasSelect = 'h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900';
    $kelasLabel = 'mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400';
@endphp

<section aria-label="Penyaring laporan"
    class="cetak-sembunyi border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
    <div class="flex flex-wrap items-end gap-3">
        @if (! empty($sp))
            <div class="min-w-[12rem] flex-1">
                <label for="filter-laporan-sp" class="{{ $kelasLabel }}">Satuan Permukiman</label>
                <select id="filter-laporan-sp" x-model="sp" class="{{ $kelasSelect }}">
                    <option value="">Seluruh satuan permukiman</option>
                    @foreach ($sp as $opsi)
                        <option value="{{ $opsi['id'] }}">{{ $opsi['nama'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($tahunTunggal)
            <div class="min-w-[9rem] flex-1">
                <label for="filter-laporan-tahun" class="{{ $kelasLabel }}">{{ $labelTahun }}</label>
                <select id="filter-laporan-tahun" x-model="tahun" class="{{ $kelasSelect }}">
                    @foreach ($daftarTahun as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($tahun)
            <div class="min-w-[9rem] flex-1">
                <label for="filter-laporan-tahun-dari" class="{{ $kelasLabel }}">{{ $labelTahun }} dari</label>
                <select id="filter-laporan-tahun-dari" x-model="tahunDari" class="{{ $kelasSelect }}">
                    <option value="">Tahun paling awal</option>
                    @foreach ($daftarTahun as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[9rem] flex-1">
                <label for="filter-laporan-tahun-sampai" class="{{ $kelasLabel }}">{{ $labelTahun }} sampai</label>
                <select id="filter-laporan-tahun-sampai" x-model="tahunSampai" class="{{ $kelasSelect }}">
                    <option value="">Tahun paling akhir</option>
                    @foreach ($daftarTahun as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @foreach ($dimensi as $d)
            <div class="min-w-[10rem] flex-1">
                <label for="filter-laporan-{{ $d['kunci'] }}" class="{{ $kelasLabel }}">{{ $d['label'] }}</label>
                <select id="filter-laporan-{{ $d['kunci'] }}" x-model="dimensi.{{ $d['kunci'] }}"
                    class="{{ $kelasSelect }}">
                    <option value="">Semua</option>
                    @foreach ($d['opsi'] as $nilai)
                        <option value="{{ $nilai }}">{{ $nilai }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach

        {{-- Bersihkan hanya muncul saat ada yang perlu dibersihkan (R-26). --}}
        <button type="button" x-show="adaFilter" x-cloak @click="bersihkan()"
            class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
            Bersihkan
        </button>
    </div>

    {{-- Ringkas keadaan filter untuk pembaca layar dan petugas. --}}
    <p x-show="adaFilter" x-cloak class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400"
        aria-live="polite">
        Menampilkan: <span x-text="kalimatCakupan"></span>
    </p>
</section>
