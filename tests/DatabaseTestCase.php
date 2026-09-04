<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PDO;
use Throwable;

/**
 * Base class untuk `tests/Feature/Database/` -- uji migration & model Eloquent
 * (Task 3.1) yang WAJIB berjalan di MySQL/MariaDB nyata.
 *
 * Suite utama tetap SQLite `:memory:` (`phpunit.xml`, `tests/TestCase`) supaya
 * cepat dan tak menyentuh basis data. SQLite tidak menegakkan ENUM, UNSIGNED,
 * maupun sebagian aturan FK, sehingga penjaga skema di sana menjadi fiksi.
 *
 * Basis data uji `digitrans_test` (env `DB_TEST_DATABASE`) DIPISAH dari
 * DB dev `digitrans`. Bila MySQL tak tersedia, uji di-SKIP -- bukan gagal
 * -- supaya kontributor tanpa MySQL tetap dapat menjalankan `pest`.
 */
abstract class DatabaseTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Grup ini SENGAJA tidak mewarisi seeder data master milik `Tests\TestCase`.
     *
     * Uji di sini menyusun barisnya sendiri untuk menguji constraint (UNIQUE,
     * FK, ENUM), sehingga tabel yang sudah terisi justru membuatnya bertabrakan:
     * `Domain2WilayahSpTest` membuat `bidang_pengaduan` sendiri dan langsung
     * kena `uq_daftar_pilihan_jenis_nilai` begitu `DaftarPilihanSeeder` menanamnya lebih
     * dulu. Uji yang memerlukan data master memanggil seedernya sendiri lewat
     * `$this->seed(...)` di `beforeEach` masing-masing.
     *
     * @var class-string|null
     */
    protected $seeder = null;

    protected static bool $dbDisiapkan = false;

    protected ?string $lewatiAlasan = null;

    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->app['config']->set('database.default', 'mysql_testing');

        $cfg = $this->app['config']['database.connections.mysql_testing'];

        try {
            $pdo = new PDO(
                "mysql:host={$cfg['host']};port={$cfg['port']}",
                $cfg['username'],
                (string) $cfg['password'],
                [PDO::ATTR_TIMEOUT => 3],
            );

            if (! static::$dbDisiapkan) {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['database']}`
                    DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci");
                static::$dbDisiapkan = true;
            }
        } catch (Throwable $e) {
            $this->lewatiAlasan = 'MySQL/MariaDB tidak tersedia untuk grup uji Database ('.$e->getMessage().')';
        }
    }

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (Throwable $e) {
            if ($this->lewatiAlasan !== null) {
                $this->markTestSkipped($this->lewatiAlasan);
            }

            throw $e;
        }

        if ($this->lewatiAlasan !== null) {
            $this->markTestSkipped($this->lewatiAlasan);
        }
    }
}
