<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Membandingkan hasil migration Laravel terhadap `database/data/schema.sql`.
 *
 * Uji SQLite `:memory:` tidak menyentuh basis data (rules.md 20a, session-notes
 * Putaran 13), sehingga kebenaran migration TIDAK terjaga oleh 732 uji yang ada.
 * Inilah penjaganya: impor `schema.sql` ke satu basis data sekali-pakai,
 * `migrate:fresh` ke basis data sekali-pakai lain, lalu bandingkan
 * `information_schema` keduanya -- kolom (nama + tipe + null), indeks, dan
 * foreign key (kolom + rujukan + aksi hapus/ubah).
 *
 * Nama constraint/indeks IKUT dibandingkan: migration ditulis dengan nama yang
 * sama seperti `schema.sql` supaya jejaknya dapat ditelusuri.
 *
 * Basis data uji dibuat dari variabel .env (`DB_HOST`/`DB_USERNAME`/`DB_PASSWORD`).
 * DB dev `digitrans` TIDAK disentuh.
 */
class BandingSkema extends Command
{
    protected $signature = 'sim:banding-skema
        {--skema-db=digitrans_skema_ref : Nama DB sekali-pakai untuk impor schema.sql}
        {--migrasi-db=digitrans_test : Nama DB sekali-pakai untuk migrate:fresh}
        {--hanya= : Batasi perbandingan ke tabel yang namanya diberikan (koma)}
        {--lengkap : Gagal bila ada tabel schema.sql yang belum dimigrasikan (verifikasi akhir Task 3.1)}';

    protected $description = 'Bandingkan hasil migration terhadap database/data/schema.sql';

    public function handle(): int
    {
        $skemaDb = $this->option('skema-db');
        $migrasiDb = $this->option('migrasi-db');
        $hanya = array_filter(array_map('trim', explode(',', (string) $this->option('hanya'))));

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');

        $this->info("Impor schema.sql -> `{$skemaDb}` ...");
        $this->imporSkema($skemaDb, $host, $port, $user, $pass);
        (new \PDO("mysql:host={$host};port={$port}", $user, $pass))
            ->exec("CREATE DATABASE IF NOT EXISTS `{$migrasiDb}` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci");

        $this->info("migrate:fresh -> `{$migrasiDb}` ...");
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => $migrasiDb,
        ]);
        DB::purge('mysql');
        if ($this->callSilent('migrate:fresh', ['--force' => true]) !== 0) {
            $this->error('migrate:fresh gagal.');

            return self::FAILURE;
        }

        $skema = $this->bacaStruktur($skemaDb, $host, $port, $user, $pass);
        $migrasi = $this->bacaStruktur($migrasiDb, $host, $port, $user, $pass);

        // Tabel bawaan framework yang dipertahankan apa adanya (bukan hasil
        // penerjemahan Task 3.1): nama indeksnya mengikuti bawaan Laravel,
        // strukturnya identik. `sessions` DIBANDINGKAN sebab migrationnya
        // ditulis ulang di sini.
        $lewati = ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];
        foreach ($lewati as $t) {
            unset($skema[$t], $migrasi[$t]);
        }

        return $this->laporkan($skema, $migrasi, $hanya) ? self::SUCCESS : self::FAILURE;
    }

    private function imporSkema(string $db, string $host, string $port, string $user, string $pass): void
    {
        $pdo = new \PDO("mysql:host={$host};port={$port}", $user, $pass);
        $pdo->exec("DROP DATABASE IF EXISTS `{$db}`");
        $pdo->exec("CREATE DATABASE `{$db}` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci");

        $sql = file_get_contents(base_path('database/data/schema.sql'));
        // Buang CREATE DATABASE / USE bawaan file agar impor jatuh ke $db kita.
        $sql = preg_replace('/^\s*(CREATE DATABASE|USE)\b[^;]*;/mi', '', $sql);

        $pdo = new \PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass);
        $pdo->exec($sql);
    }

    /**
     * @return array<string, array{kolom: array<string,string>, indeks: array<string,string>, fk: array<string,string>}>
     */
    private function bacaStruktur(string $db, string $host, string $port, string $user, string $pass): array
    {
        $pdo = new \PDO("mysql:host={$host};port={$port};dbname=information_schema", $user, $pass);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $hasil = [];

        $kolom = $pdo->prepare('SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
            FROM COLUMNS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, ORDINAL_POSITION');
        $kolom->execute([$db]);
        foreach ($kolom->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $default = $r['COLUMN_DEFAULT'];
            // Normalkan default timestamp antar dialek.
            $default = is_string($default) ? strtolower(preg_replace('/\(\)$/', '', $default)) : $default;
            $hasil[$r['TABLE_NAME']]['kolom'][$r['COLUMN_NAME']] = implode('|', [
                strtolower($r['COLUMN_TYPE']),
                $r['IS_NULLABLE'],
                $default ?? 'NULL',
                strtolower(trim(preg_replace('/DEFAULT_GENERATED\s*/i', '', $r['EXTRA']))),
            ]);
        }

        $idx = $pdo->prepare('SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) COLS
            FROM STATISTICS WHERE TABLE_SCHEMA = ? GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE ORDER BY TABLE_NAME, INDEX_NAME');
        $idx->execute([$db]);
        foreach ($idx->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $hasil[$r['TABLE_NAME']]['indeks'][$r['INDEX_NAME']] = ($r['NON_UNIQUE'] ? 'idx' : 'uniq').':'.$r['COLS'];
        }

        $fk = $pdo->prepare('SELECT k.TABLE_NAME, k.CONSTRAINT_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME,
                r.DELETE_RULE, r.UPDATE_RULE
            FROM KEY_COLUMN_USAGE k
            JOIN REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
            WHERE k.TABLE_SCHEMA = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME');
        $fk->execute([$db]);
        foreach ($fk->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $hasil[$r['TABLE_NAME']]['fk'][$r['CONSTRAINT_NAME']] = implode('|', [
                $r['COLUMN_NAME'], $r['REFERENCED_TABLE_NAME'], $r['REFERENCED_COLUMN_NAME'],
                strtoupper($r['DELETE_RULE']), strtoupper($r['UPDATE_RULE']),
            ]);
        }

        foreach ($hasil as &$t) {
            $t += ['kolom' => [], 'indeks' => [], 'fk' => []];
        }

        return $hasil;
    }

    /**
     * @param  array<string, array<string, array<string,string>>>  $skema
     * @param  array<string, array<string, array<string,string>>>  $migrasi
     * @param  list<string>  $hanya
     */
    private function laporkan(array $skema, array $migrasi, array $hanya): bool
    {
        $bersih = true;
        $tabel = $hanya ?: array_values(array_unique([...array_keys($skema), ...array_keys($migrasi)]));
        sort($tabel);

        foreach ($tabel as $t) {
            if (! isset($skema[$t])) {
                $this->error("  TABEL '{$t}' ada di migration tetapi tidak di schema.sql");
                $bersih = false;

                continue;
            }
            if (! isset($migrasi[$t])) {
                if ($this->option('lengkap')) {
                    $this->error("  tabel '{$t}' ada di schema.sql tetapi BELUM dimigrasikan");
                    $bersih = false;
                } elseif (! $hanya) {
                    $this->warn("  tabel '{$t}' belum dimigrasikan (batch berikutnya)");
                }

                continue;
            }

            foreach (['kolom', 'indeks', 'fk'] as $bagian) {
                foreach ($this->selisih($skema[$t][$bagian], $migrasi[$t][$bagian]) as $baris) {
                    $this->error("  [{$t}.{$bagian}] {$baris}");
                    $bersih = false;
                }
            }
        }

        if ($bersih) {
            $this->info('NOL SELISIH -- migration cocok schema.sql.');
        }

        return $bersih;
    }

    /**
     * @param  array<string,string>  $a  dari schema.sql
     * @param  array<string,string>  $b  dari migration
     * @return list<string>
     */
    private function selisih(array $a, array $b): array
    {
        $out = [];
        foreach ($a as $k => $v) {
            if (! array_key_exists($k, $b)) {
                $out[] = "hilang di migration: {$k} ({$v})";
            } elseif ($b[$k] !== $v) {
                $out[] = "beda '{$k}': schema=[{$v}] migration=[{$b[$k]}]";
            }
        }
        foreach ($b as $k => $v) {
            if (! array_key_exists($k, $a)) {
                $out[] = "berlebih di migration: {$k} ({$v})";
            }
        }

        return $out;
    }
}
