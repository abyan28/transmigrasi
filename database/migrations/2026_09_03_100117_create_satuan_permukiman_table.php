<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 2, tabel `satuan_permukiman`.
 *
 * Lokus utama sistem; titik temu cabang administratif (`desa_id`) & program
 * (`kawasan_id`), keduanya WAJIB. `kecamatan_id` sengaja TIDAK ada (dibaca via
 * desa). Blok "Keadaan Wilayah" (Bab II Monografi, Rombongan C) seluruhnya
 * NULL-able & dokumenter; rentang disimpan pasangan min/maks.
 * `jumlah_kk_terisi` TIDAK disimpan (dihitung dari transmigran).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satuan_permukiman', function (Blueprint $table) {
            $table->id('id_satuan_permukiman');
            $table->unsignedBigInteger('kawasan_id');
            $table->unsignedBigInteger('desa_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nama', 150);
            $table->string('slug', 120);
            $table->string('kode_sp', 20)->nullable();
            $table->year('tahun_penempatan')->nullable();
            $table->decimal('luas_lahan', 12, 2)->nullable();
            $table->unsignedInteger('jumlah_kk_rencana')->nullable();
            $table->decimal('lintang', 10, 7)->nullable();
            $table->decimal('bujur', 10, 7)->nullable();
            $table->unsignedBigInteger('berkas_id')->nullable();
            $table->text('keterangan')->nullable();

            // Keadaan Wilayah -- letak astronomis
            $table->decimal('lintang_utara', 10, 7)->nullable();
            $table->decimal('lintang_selatan', 10, 7)->nullable();
            $table->decimal('bujur_barat', 10, 7)->nullable();
            $table->decimal('bujur_timur', 10, 7)->nullable();

            // Keadaan Wilayah -- letak ekonomis
            $table->decimal('jarak_ke_kecamatan_km', 6, 1)->nullable();
            $table->decimal('jarak_ke_kabupaten_km', 6, 1)->nullable();
            $table->decimal('jarak_ke_provinsi_km', 6, 1)->nullable();

            // Keadaan Wilayah -- batas alam
            $table->string('batas_utara', 150)->nullable();
            $table->string('batas_timur', 150)->nullable();
            $table->string('batas_selatan', 150)->nullable();
            $table->string('batas_barat', 150)->nullable();

            // Keadaan Wilayah -- SK pencadangan
            $table->string('nomor_sk_pencadangan', 100)->nullable();
            $table->date('tanggal_sk_pencadangan')->nullable();

            // Keadaan Wilayah -- pola permukiman, tanah, topografi
            $table->enum('pola_permukiman', ['Konsentris', 'Papan Catur', 'Linear', 'Menyebar'])->nullable();
            $table->enum('tingkat_kesuburan_tanah', ['Subur', 'Sedang', 'Kurang Subur'])->nullable();
            $table->decimal('ph_tanah_min', 4, 2)->nullable();
            $table->decimal('ph_tanah_maks', 4, 2)->nullable();
            $table->enum('bentuk_wilayah', ['Datar', 'Bergelombang', 'Berbukit', 'Bergunung'])->nullable();
            $table->decimal('kemiringan_min_persen', 5, 2)->nullable();
            $table->decimal('kemiringan_maks_persen', 5, 2)->nullable();

            // Keadaan Wilayah -- iklim
            $table->decimal('curah_hujan_tahunan_mm', 8, 2)->nullable();
            $table->decimal('curah_hujan_bulan_min_mm', 7, 2)->nullable();
            $table->decimal('curah_hujan_bulan_maks_mm', 7, 2)->nullable();
            $table->decimal('suhu_min_c', 4, 1)->nullable();
            $table->decimal('suhu_maks_c', 4, 1)->nullable();
            $table->decimal('suhu_rata_c', 4, 1)->nullable();
            $table->decimal('angin_min_knot', 4, 1)->nullable();
            $table->decimal('angin_maks_knot', 4, 1)->nullable();
            $table->decimal('angin_rata_knot', 4, 1)->nullable();
            $table->decimal('penyinaran_min_persen', 5, 2)->nullable();
            $table->decimal('penyinaran_maks_persen', 5, 2)->nullable();
            $table->decimal('penyinaran_rata_persen', 5, 2)->nullable();

            // Keadaan Wilayah -- sumberdaya air
            $table->string('sumber_air_bersih', 255)->nullable();
            $table->string('sumber_air_pertanian', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('nama', 'uq_sp_nama');
            $table->unique('slug', 'uq_sp_slug');
            $table->unique('kode_sp', 'uq_sp_kode');
            $table->index('kawasan_id', 'idx_sp_kawasan');
            $table->index('desa_id', 'idx_sp_desa');
            $table->index('user_id', 'idx_sp_user');
            $table->index('berkas_id', 'idx_sp_berkas');

            $table->foreign('kawasan_id', 'fk_sp_kawasan')
                ->references('id_kawasan_transmigrasi')->on('kawasan_transmigrasi')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('desa_id', 'fk_sp_desa')
                ->references('id_desa')->on('desa')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id', 'fk_sp_user')
                ->references('id_user')->on('user')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_sp_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satuan_permukiman');
    }
};
