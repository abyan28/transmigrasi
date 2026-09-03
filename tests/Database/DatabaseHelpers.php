<?php

/*
 * Pembangun data bersama untuk grup uji `tests/Database` (MySQL/MariaDB nyata).
 *
 * Di-`require_once` dari tiap berkas uji Database. Tidak memakai factory --
 * Task 3.1 menerjemahkan skema, bukan menyiapkan factory (itu Tahap 4). Tiap
 * pembangun membuat rantai induk seminimal mungkin agar FK NOT NULL terpenuhi.
 */

use App\Models\Berkas;
use App\Models\Desa;
use App\Models\Kabupaten;
use App\Models\KawasanTransmigrasi;
use App\Models\Kecamatan;
use App\Models\Poktan;
use App\Models\Provinsi;
use App\Models\Satuan;
use App\Models\SatuanPermukiman;
use App\Models\Transmigran;
use Illuminate\Support\Str;

if (! function_exists('buatSp')) {
    /**
     * Satu SP lengkap dengan rantai wilayah administratif + kawasan programnya.
     */
    function buatSp(array $atribut = []): SatuanPermukiman
    {
        $prov = Provinsi::create(['nama' => 'Nusa Tenggara Timur '.Str::random(4)]);
        $kab = Kabupaten::create(['provinsi_id' => $prov->id_provinsi, 'nama' => 'Malaka '.Str::random(4)]);
        $kec = Kecamatan::create(['kabupaten_id' => $kab->id_kabupaten, 'nama' => 'Kobalima Timur '.Str::random(4)]);
        $desa = Desa::create(['kecamatan_id' => $kec->id_kecamatan, 'nama' => 'Kapitan Meo '.Str::random(4)]);
        $kawasan = KawasanTransmigrasi::create([
            'kabupaten_id' => $kab->id_kabupaten,
            'nama' => 'Kobalima Timur '.Str::random(4),
            'slug' => 'kobalima-timur-'.Str::lower(Str::random(6)),
        ]);

        return SatuanPermukiman::create(array_merge([
            'kawasan_id' => $kawasan->id_kawasan_transmigrasi,
            'desa_id' => $desa->id_desa,
            'nama' => 'SP Kapitan Meo '.Str::random(4),
            'slug' => 'sp-kapitan-meo-'.Str::lower(Str::random(6)),
        ], $atribut));
    }
}

if (! function_exists('buatSatuanTon')) {
    function buatSatuanTon(): Satuan
    {
        return Satuan::create(['nama' => 'Ton '.Str::random(4), 'simbol' => 't', 'faktor_ke_ton' => '1.000000']);
    }
}

if (! function_exists('buatBerkas')) {
    function buatBerkas(?int $userId = null): Berkas
    {
        return Berkas::create([
            'uuid' => (string) Str::uuid(),
            'nama_file' => 'x-'.Str::random(6).'.pdf',
            'path' => 'uji/'.Str::random(8).'.pdf',
            'mime' => 'application/pdf',
            'ekstensi' => 'pdf',
            'ukuran' => 2048,
            'user_id' => $userId,
        ]);
    }
}

if (! function_exists('buatTransmigran')) {
    function buatTransmigran(?SatuanPermukiman $sp = null, array $atribut = []): Transmigran
    {
        $sp ??= buatSp();

        return Transmigran::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'satuan_permukiman_id' => $sp->id_satuan_permukiman,
            'nik' => (string) random_int(1000000000000000, 9999999999999999),
            'no_kk' => (string) random_int(1000000000000000, 9999999999999999),
            'nama_kepala_keluarga' => 'Mateus Bere '.Str::random(4),
            'pekerjaan_kepala_keluarga' => 'Petani',
            'tahun_kedatangan' => 2015,
            'status_tinggal' => 'Aktif',
            'status_anggota_poktan' => 'Ya',
        ], $atribut));
    }
}

if (! function_exists('buatPoktan')) {
    function buatPoktan(?SatuanPermukiman $sp = null, array $atribut = []): Poktan
    {
        $sp ??= buatSp();

        return Poktan::create(array_merge([
            'satuan_permukiman_id' => $sp->id_satuan_permukiman,
            'slug' => 'poktan-'.Str::lower(Str::random(8)),
            'nama' => 'Poktan Harapan '.Str::random(4),
        ], $atribut));
    }
}
