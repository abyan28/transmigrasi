{{--
    Sepasang penyaring tahun: dari dan sampai.

    Ditambahkan 2026-08-28 (rules.md 12 poin 12). Menggantikan penyaring tahun
    tunggal pada halaman daftar bersumbu waktu, sehingga petugas dapat memilih
    rentang, misalnya panen 2024 sampai 2026.

    HANYA untuk halaman DAFTAR TRANSAKSI (`/panen`, `/penanaman`, `/audit-log`),
    tempat tiap baris berdiri sendiri dan menyaring rentang hanya menyempitkan
    daftar. DILARANG dipakai pada halaman rekap agregat: rekap panen yang
    dijumlah lintas tahun membuat luas 2 ha yang ditanami tiga tahun terbaca
    6 ha (rules.md 9 poin 8b).

    Rute pemanggil bertanggung jawab menyaring: bila `tahun_dari` melampaui
    `tahun_sampai`, perlakukan sebagai rentang kosong atau tukar keduanya,
    jangan biarkan menyaring diam-diam.

    Pemakaian:
        <x-sim.filter-rentang-tahun :daftar-tahun="$daftarTahun"
            :dari="$filterTahunDari" :sampai="$filterTahunSampai" label="Tahun Panen" />
--}}
@props([
    'daftarTahun' => [],
    'dari' => null,
    'sampai' => null,
    'label' => 'Tahun',
    'namaDari' => 'tahun_dari',
    'namaSampai' => 'tahun_sampai',
])

@php
    $kelasSelect = 'h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90';
    $kelasLabel = 'mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400';
@endphp

<div>
    <label for="filter_{{ $namaDari }}" class="{{ $kelasLabel }}">{{ $label }} Awal</label>
    <select id="filter_{{ $namaDari }}" name="{{ $namaDari }}" class="{{ $kelasSelect }}">
        <option value="">Tahun paling awal</option>
        @foreach ($daftarTahun as $t)
            <option value="{{ $t }}" @selected((string) $dari === (string) $t)>{{ $t }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="filter_{{ $namaSampai }}" class="{{ $kelasLabel }}">{{ $label }} Akhir</label>
    <select id="filter_{{ $namaSampai }}" name="{{ $namaSampai }}" class="{{ $kelasSelect }}">
        <option value="">Tahun paling akhir</option>
        @foreach ($daftarTahun as $t)
            <option value="{{ $t }}" @selected((string) $sampai === (string) $t)>{{ $t }}</option>
        @endforeach
    </select>
</div>
