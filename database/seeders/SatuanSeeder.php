<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Seeder;

/**
 * Satuan berat beserta faktor konversinya ke ton (Task 4.5).
 *
 * Tiga satuan awal sesuai `tasklist.md`. `faktor_ke_ton` dipakai sebagai
 * PENGALI pada seluruh rekap panen supaya agregasi lintas komoditas dapat
 * dijumlahkan; karena itu nilainya wajib lebih besar dari nol.
 *
 * Idempoten: `updateOrCreate` pada `nama` yang UNIQUE di skema.
 */
class SatuanSeeder extends Seeder
{
    /** @var list<array{nama: string, simbol: string, faktor: string}> */
    private const SATUAN = [
        ['nama' => 'Ton', 'simbol' => 't', 'faktor' => '1'],
        ['nama' => 'Kuintal', 'simbol' => 'kw', 'faktor' => '0.1'],
        ['nama' => 'Kilogram', 'simbol' => 'kg', 'faktor' => '0.001'],
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
