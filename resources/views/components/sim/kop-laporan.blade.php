{{--
    Kop surat dokumen laporan (Putaran 5). Dipakai HANYA pada rute dokumen
    polos /laporan/{slug}/dokumen ($dokumen === true di x-sim.kerangka-laporan),
    menggantikan blok "Cakupan laporan". Rute berbingkai tetap memakai masthead
    ringkas lamanya.

    WAJIB memakai flex saja, TANPA elemen tabel: penjaga Pest
    `kolomTerlebarDariHtml` mengurai kepala tiap elemen tabel untuk menghitung
    kolom, dan salah menghitung bila kop dirakit sebagai tabel. Markup di sini
    seluruhnya flex.

    Dua lambang (keputusan pemilik proyek): logo Kementerian Transmigrasi kiri,
    lambang Kabupaten Malaka kanan. Identitas dari LaporanData::instansi().

    Pemisah teks: `|` (bukan em dash) -- penjaga R-02 pada rute dokumen menolak
    U+2014.

    Baris "TAHUN ...": disusun Alpine (`tahunDokumen`) -- rentang bila filter
    rentang tahun aktif, satu tahun bila pemilih tahun tunggal, selain itu
    tahun terakhir deret data (LaporanData::tahunDokumenBawaan(), bukan date()).

    Kalimat cakupan WAJIB tetap tercetak (rules.md 12 poin 8): dokumen yang
    dicetak atau difoto kehilangan kontrol filternya. Disusun ulang Alpine
    (`kalimatCakupan`), dengan bawaan Blade sebagai jaring bila JS mati.
--}}
@props(['slug'])

@php
    $meta = \App\Support\LaporanData::meta($slug);
    $instansi = \App\Support\LaporanData::instansi();
    // Judul dari meta() sudah lengkap ("Laporan Hasil Panen", "Rekap Indikator
    // Kawasan"); tinggal dibesarkan hurufnya untuk kop dokumen.
    $judulDokumen = \Illuminate\Support\Str::upper($meta['judul'] ?? 'Laporan');
    $subjudul = $meta['subjudulDokumen'] ?? null;
    $cakupan = $meta['cakupan'] ?? '';
    $dasarPeriode = $meta['dasarPeriode'] ?? '';
    $catatan = $meta['catatan'] ?? null;
    $tahunBawaan = \App\Support\LaporanData::tahunDokumenBawaan();
@endphp

<header class="border-b-[3px] border-gray-900 px-6 pb-4 pt-6 dark:border-gray-100">
    <div class="flex items-center gap-4">
        <img src="{{ asset($instansi['logoKementerian']) }}" alt="Logo Kementerian Transmigrasi"
            class="h-16 w-16 shrink-0 object-contain" />
        <div class="min-w-0 flex-1 text-center leading-tight">
            <p class="text-theme-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ \Illuminate\Support\Str::upper($instansi['kementerian']) }}
            </p>
            <p class="text-theme-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ \Illuminate\Support\Str::upper($instansi['pemerintah']) }}
            </p>
            <p class="text-theme-md font-bold uppercase text-gray-900 dark:text-white">
                {{ \Illuminate\Support\Str::upper($instansi['dinas']) }}
            </p>
            <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $instansi['alamat'] }}</p>
            <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $instansi['kontak'] }}</p>
        </div>
        <img src="{{ asset($instansi['lambangKabupaten']) }}" alt="Lambang Kabupaten Malaka"
            class="h-16 w-16 shrink-0 object-contain" />
    </div>
</header>

<div class="border-b border-gray-200 px-6 py-5 text-center dark:border-gray-800">
    <h1 class="text-theme-lg font-bold uppercase tracking-wide text-gray-900 dark:text-white">
        {{ $judulDokumen }}
    </h1>
    @if ($subjudul)
        <p class="mt-1 text-theme-sm font-semibold uppercase text-gray-700 dark:text-gray-300">{{ $subjudul }}</p>
    @endif
    <p class="mt-1 text-theme-sm font-semibold text-gray-700 dark:text-gray-300"
        x-text="tahunDokumen">TAHUN {{ $tahunBawaan }}</p>
    <p class="mx-auto mt-3 max-w-2xl text-theme-xs text-gray-500 dark:text-gray-400"
        x-text="kalimatCakupan">{{ $cakupan }}</p>
    @if ($dasarPeriode)
        <p class="mx-auto mt-1 max-w-2xl text-theme-xs italic text-gray-400 dark:text-gray-500">{{ $dasarPeriode }}</p>
    @endif
    @if ($catatan)
        <p class="mx-auto mt-1 max-w-2xl text-theme-xs text-gray-400 dark:text-gray-500">{{ $catatan }}</p>
    @endif
</div>
