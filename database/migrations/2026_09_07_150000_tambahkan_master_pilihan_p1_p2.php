<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const JENIS = [
        'sumber_dana', 'status_penyerahan', 'kondisi', 'kondisi_rumah',
        'status_hunian', 'tipe_komoditas', 'prioritas_pengaduan',
        'jabatan_anggota_poktan', 'jenis_infrastruktur', 'jenis_fasilitas',
        'bidang_pengaduan', 'kategori_pengaduan', 'jenis_alsintan', 'jenis_inventaris',
        'jenis_saprotan', 'status_sertifikat', 'pola_permukiman',
        'tingkat_kesuburan_tanah', 'bentuk_wilayah',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('daftar_pilihan')) {
            return;
        }

        $hasExistingRows = DB::table('daftar_pilihan')->exists();

        if (! Schema::hasColumn('daftar_pilihan', 'label')) {
            Schema::table('daftar_pilihan', fn (Blueprint $table) => $table
                ->string('label', 100)->nullable()->after('nilai'));
        }
        if (! Schema::hasColumn('daftar_pilihan', 'kode_perilaku')) {
            Schema::table('daftar_pilihan', fn (Blueprint $table) => $table
                ->string('kode_perilaku', 50)->nullable()->after('label'));
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE daftar_pilihan MODIFY jenis ENUM('".implode("','", self::JENIS)."') NOT NULL");
        }

        DB::table('daftar_pilihan')->whereNull('label')->update(['label' => DB::raw('nilai')]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE daftar_pilihan MODIFY label VARCHAR(100) NOT NULL');
            DB::statement('ALTER TABLE saprotan MODIFY jenis VARCHAR(100) NOT NULL');
            DB::statement("ALTER TABLE transmigran MODIFY status_sertifikat VARCHAR(100) NOT NULL DEFAULT 'Belum Didata'");
            DB::statement('ALTER TABLE satuan_permukiman MODIFY pola_permukiman VARCHAR(100) NULL');
            DB::statement('ALTER TABLE satuan_permukiman MODIFY tingkat_kesuburan_tanah VARCHAR(100) NULL');
            DB::statement('ALTER TABLE satuan_permukiman MODIFY bentuk_wilayah VARCHAR(100) NULL');
        }

        // Fresh migration belum memiliki data; seeder akan menanam seluruh
        // pilihan dengan id stabil setelah semua tabel selesai dibuat.
        if (! $hasExistingRows) {
            return;
        }

        $sekarang = now();
        $urutan = DB::table('daftar_pilihan')->selectRaw('jenis, MAX(urutan) AS urutan')
            ->groupBy('jenis')->pluck('urutan', 'jenis');
        foreach ([
            'jenis_saprotan' => ['Benih', 'Pupuk', 'Pestisida', 'Mulsa', 'Lainnya'],
            'status_sertifikat' => ['Sudah', 'Belum', 'Belum Didata'],
            'pola_permukiman' => ['Konsentris', 'Papan Catur', 'Linear', 'Menyebar'],
            'tingkat_kesuburan_tanah' => ['Subur', 'Sedang', 'Kurang Subur'],
            'bentuk_wilayah' => ['Datar', 'Bergelombang', 'Berbukit', 'Bergunung'],
        ] as $jenis => $nilai) {
            foreach ($nilai as $isi) {
                $nomorUrut = ((int) ($urutan[$jenis] ?? 0)) + 1;
                DB::table('daftar_pilihan')->insertOrIgnore([
                    'jenis' => $jenis, 'nilai' => $isi, 'label' => $isi,
                    'urutan' => $nomorUrut, 'is_aktif' => true,
                    'updated_at' => $sekarang, 'created_at' => $sekarang,
                ]);
                $urutan[$jenis] = $nomorUrut;
            }
        }

        foreach ([
            ['jenis_saprotan', 'Benih', 'benih'],
            ['status_sertifikat', 'Sudah', 'sudah'],
            ['status_sertifikat', 'Belum', 'belum'],
            ['status_sertifikat', 'Belum Didata', 'tidak_diketahui'],
        ] as [$jenis, $nilai, $kode]) {
            DB::table('daftar_pilihan')->where(compact('jenis', 'nilai'))
                ->whereNull('kode_perilaku')->update(['kode_perilaku' => $kode]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Master pilihan P1/P2 tidak dapat dikembalikan ke ENUM tanpa kehilangan nilai baru.');
    }
};
