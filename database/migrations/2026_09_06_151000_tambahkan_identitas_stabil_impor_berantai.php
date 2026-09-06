<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->tambahkanKode('saprotan', 'kode_saprotan', 'SAP', 'id_saprotan', 'tahun_pengadaan', 'uq_saprotan_kode');
        $this->tambahkanKode('penanaman', 'kode_penanaman', 'TAN', 'id_penanaman', null, 'uq_penanaman_kode');
        $this->wajibkanKodeLahan();
        $this->selaraskanRumah();
    }

    public function down(): void
    {
        throw new RuntimeException('Identitas stabil dipakai sebagai rujukan data. Pulihkan cadangan basis data untuk rollback.');
    }

    private function tambahkanKode(
        string $tabel,
        string $kolom,
        string $awalan,
        string $primaryKey,
        ?string $kolomTahun,
        string $indeks,
    ): void {
        if (! Schema::hasColumn($tabel, $kolom)) {
            Schema::table($tabel, function (Blueprint $table) use ($kolom, $primaryKey): void {
                $table->string($kolom, 50)->nullable()->after($primaryKey);
            });

            DB::table($tabel)->orderBy($primaryKey)->get()->each(
                function ($baris) use ($tabel, $kolom, $awalan, $primaryKey, $kolomTahun): void {
                    $tahun = $kolomTahun === null
                        ? substr((string) $baris->periode_tanam, 0, 4)
                        : (string) $baris->{$kolomTahun};

                    DB::table($tabel)->where($primaryKey, $baris->{$primaryKey})->update([
                        $kolom => sprintf('%s-%s-%04d', $awalan, $tahun, $baris->{$primaryKey}),
                    ]);
                },
            );

            Schema::table($tabel, function (Blueprint $table) use ($kolom, $indeks): void {
                $table->string($kolom, 50)->nullable(false)->change();
                $table->unique($kolom, $indeks);
            });
        }
    }

    private function wajibkanKodeLahan(): void
    {
        if (! Schema::hasColumn('lahan', 'kode_lahan')) {
            return;
        }

        DB::table('lahan')->whereNull('kode_lahan')->orderBy('id_lahan')->get()
            ->each(fn ($baris) => DB::table('lahan')->where('id_lahan', $baris->id_lahan)->update([
                'kode_lahan' => sprintf('LH-%04d', $baris->id_lahan),
            ]));

        if (DB::getDriverName() === 'mysql') {
            Schema::table('lahan', fn (Blueprint $table) => $table->string('kode_lahan', 50)->nullable(false)->change());
        }
    }

    private function selaraskanRumah(): void
    {
        DB::table('rumah')->whereNull('no_rumah')->orderBy('id_rumah')->get()
            ->each(fn ($baris) => DB::table('rumah')->where('id_rumah', $baris->id_rumah)->update([
                'no_rumah' => sprintf('R-%04d', $baris->id_rumah),
            ]));

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $duplikat = DB::table('rumah')
            ->select('satuan_permukiman_id', 'no_rumah')
            ->groupBy('satuan_permukiman_id', 'no_rumah')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        if ($duplikat) {
            throw new RuntimeException('Nomor rumah ganda dalam satu SP harus diperbaiki sebelum migrasi dilanjutkan.');
        }

        $mismatch = DB::table('rumah')
            ->join('transmigran', 'transmigran.id_transmigran', '=', 'rumah.transmigran_id')
            ->whereNotNull('rumah.transmigran_id')
            ->whereColumn('rumah.satuan_permukiman_id', '!=', 'transmigran.satuan_permukiman_id')
            ->exists();
        if ($mismatch) {
            throw new RuntimeException('Ada rumah yang SP-nya berbeda dari SP penghuni. Perbaiki data sebelum migrasi dilanjutkan.');
        }

        Schema::table('rumah', fn (Blueprint $table) => $table->string('no_rumah', 50)->nullable(false)->change());

        if (! $this->indeksAda('rumah', 'uq_rumah_sp_nomor')) {
            Schema::table('rumah', fn (Blueprint $table) => $table->unique(
                ['satuan_permukiman_id', 'no_rumah'],
                'uq_rumah_sp_nomor',
            ));
        }

        if (! $this->indeksAda('rumah', 'idx_rumah_transmigran_sp')) {
            Schema::table('rumah', fn (Blueprint $table) => $table->index(
                ['transmigran_id', 'satuan_permukiman_id'],
                'idx_rumah_transmigran_sp',
            ));
        }

        DB::unprepared('DROP TRIGGER IF EXISTS trg_rumah_sp_penghuni_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_rumah_sp_penghuni_update');
        DB::unprepared("CREATE TRIGGER trg_rumah_sp_penghuni_insert BEFORE INSERT ON rumah
            FOR EACH ROW BEGIN
                IF NEW.transmigran_id IS NOT NULL AND NOT EXISTS (
                    SELECT 1 FROM transmigran
                    WHERE id_transmigran = NEW.transmigran_id
                      AND satuan_permukiman_id = NEW.satuan_permukiman_id
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'SP rumah harus sama dengan SP penghuni';
                END IF;
            END");
        DB::unprepared("CREATE TRIGGER trg_rumah_sp_penghuni_update BEFORE UPDATE ON rumah
            FOR EACH ROW BEGIN
                IF NEW.transmigran_id IS NOT NULL AND NOT EXISTS (
                    SELECT 1 FROM transmigran
                    WHERE id_transmigran = NEW.transmigran_id
                      AND satuan_permukiman_id = NEW.satuan_permukiman_id
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'SP rumah harus sama dengan SP penghuni';
                END IF;
            END");
    }

    private function indeksAda(string $tabel, string $nama): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [DB::getDatabaseName(), $tabel, $nama],
        ) !== null;
    }
};
