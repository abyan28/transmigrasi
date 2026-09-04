<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Seeder;

/**
 * Satuan jumlah beserta faktor konversinya ke ton (Task 4.5 / 6.7).
 *
 * Satuan BERAT (Ton/Kuintal/Kilogram) ber-`faktor_ke_ton`: PENGALI seluruh
 * rekap panen supaya agregasi lintas komoditas dapat dijumlahkan; nilainya
 * wajib lebih besar dari nol.
 *
 * Satuan NON-BERAT (Liter, Rol) ditambahkan Task 6.7 untuk saprotan cair /
 * gulungan. `faktor_ke_ton` = NULL: tidak dikonversi ke ton, dan tidak pernah
 * muncul pada rekap panen (yang hanya membaca satuan komoditas).
 *
 * Idempoten: `updateOrCreate` pada `nama` yang UNIQUE di skema.
 */
class SatuanSeeder extends Seeder
{
    /** @var list<array{nama: string, simbol: string, faktor: string|null}> */
    private const SATUAN = [
        ['nama' => 'Ton', 'simbol' => 't', 'faktor' => '1'],
        ['nama' => 'Kuintal', 'simbol' => 'kw', 'faktor' => '0.1'],
        ['nama' => 'Kilogram', 'simbol' => 'kg', 'faktor' => '0.001'],
        ['nama' => 'Liter', 'simbol' => 'L', 'faktor' => null],
        ['nama' => 'Rol', 'simbol' => 'rol', 'faktor' => null],
    ];

    public function run(): void
    {
        foreach (self::SATUAN as $s) {
            Satuan::updateOrCreate(
                ['nama' => $s['nama']],
                ['simbol' => $s['simbol'], 'faktor_ke_ton' => $s['faktor']],
            );
        }
    }
}
