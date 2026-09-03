<?php

namespace Tests;

use Database\Seeders\DataMasterSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base class suite Feature.
 *
 * Sejak Task 4.1 tampilan beralih dari `DummyData` ke Eloquent, sehingga
 * `RefreshDatabase` dinyalakan di `tests/Pest.php` -- tetap SQLite
 * `:memory:` supaya cepat, hanya kini bertabel.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Seeder yang dijalankan `RefreshDatabase` SEKALI per kelas uji, bukan
     * per uji.
     *
     * Data master (wilayah, satuan) diandaikan ADA oleh banyak halaman
     * (dropdown provinsi/kabupaten pada form SP, form transmigran, penyaring
     * laporan). Menanamnya lewat `$this->seed()` di `beforeEach` menulis 552
     * baris provinsi+kabupaten sebanyak 732 kali dan terukur menaikkan suite
     * Feature dari ~60 detik menjadi 516 detik; lewat properti ini hasilnya
     * dipakai ulang antar-uji melalui transaksi.
     *
     * @var class-string
     */
    protected $seeder = DataMasterSeeder::class;
}
