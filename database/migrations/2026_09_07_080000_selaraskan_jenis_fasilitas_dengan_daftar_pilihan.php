<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' && $this->masihEnum()) {
            Schema::table('fasilitas_sp', fn (Blueprint $table) => $table
                ->string('jenis_fasilitas', 100)->change());
        }

        $urutan = (int) DB::table('parameter_penilaian_sp')->max('urutan');
        $pilihan = DB::table('daftar_pilihan as dp')
            ->whereIn('dp.jenis', ['jenis_infrastruktur', 'jenis_fasilitas'])
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('parameter_penilaian_sp as p')
                ->whereColumn('p.daftar_pilihan_id', 'dp.id_daftar_pilihan'))
            ->orderBy('dp.id_daftar_pilihan')
            ->get(['dp.id_daftar_pilihan', 'dp.jenis', 'dp.nilai']);

        foreach ($pilihan as $baris) {
            DB::table('parameter_penilaian_sp')->insert([
                'kode' => Str::limit(Str::slug($baris->nilai, '_'), 28, '').'_'.$baris->id_daftar_pilihan,
                'nama' => $baris->nilai,
                'tingkat' => 'Tersier',
                'bobot' => 1,
                'sumber' => $baris->jenis === 'jenis_fasilitas' ? 'Fasilitas' : 'Infrastruktur',
                'daftar_pilihan_id' => $baris->id_daftar_pilihan,
                'is_dinilai' => false,
                'urutan' => ++$urutan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::getDriverName() === 'mysql' && ! $this->parameterSudahUnik()) {
            Schema::table('parameter_penilaian_sp', fn (Blueprint $table) => $table
                ->unique('daftar_pilihan_id', 'uq_parameter_penilaian_daftar_pilihan'));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Jenis fasilitas dari Daftar Pilihan tidak dapat dikembalikan ke ENUM tanpa kehilangan nilai baru.');
    }

    private function masihEnum(): bool
    {
        return DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'fasilitas_sp')
            ->where('COLUMN_NAME', 'jenis_fasilitas')
            ->value('DATA_TYPE') === 'enum';
    }

    private function parameterSudahUnik(): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'parameter_penilaian_sp')
            ->where('INDEX_NAME', 'uq_parameter_penilaian_daftar_pilihan')
            ->exists();
    }
};