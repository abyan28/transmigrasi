<?php

/*
 * Task 3.1 -- DOMAIN 3 (Aset SP) + DOMAIN 4 (Master Referensi & Penilaian).
 *
 * Tabel: satuan, komoditas, status_kondisi_sp, parameter_penilaian_sp,
 * penilaian_sp, inventaris_sp, fasilitas_sp, fasilitas_sp_cakupan (pivot).
 *
 * Berjalan di MySQL/MariaDB nyata. Kecocokan kolom/indeks/FK dijaga terpisah
 * oleh `php artisan sim:banding-skema`. Uji ini menjaga model & relasinya.
 * `buatSp()` di-share dari Domain2WilayahSpTest.
 */

use App\Enums\JenisFasilitas;
use App\Enums\JenisReferensi;
use App\Enums\StatusKondisiSp as StatusKondisiSpEnum;
use App\Models\FasilitasSp;
use App\Models\InventarisSp;
use App\Models\Komoditas;
use App\Models\ParameterPenilaianSp;
use App\Models\PenilaianSp;
use App\Models\Referensi;
use App\Models\Satuan;
use App\Models\StatusKondisiSp;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require_once __DIR__.'/DatabaseHelpers.php';

it('membuat kedelapan tabel batch ini', function () {
    foreach ([
        'satuan', 'komoditas', 'status_kondisi_sp', 'parameter_penilaian_sp',
        'penilaian_sp', 'inventaris_sp', 'fasilitas_sp', 'fasilitas_sp_cakupan',
    ] as $tabel) {
        expect(Schema::hasTable($tabel))->toBeTrue("tabel {$tabel} tidak dibuat");
    }
});

it('memakai PK dan nama tabel bentuk tunggal', function () {
    expect((new Satuan)->getKeyName())->toBe('id_satuan')
        ->and((new Komoditas)->getKeyName())->toBe('id_komoditas')
        ->and((new Komoditas)->getTable())->toBe('komoditas')
        ->and((new StatusKondisiSp)->getKeyName())->toBe('id_status_kondisi_sp')
        ->and((new ParameterPenilaianSp)->getKeyName())->toBe('id_parameter_penilaian_sp')
        ->and((new PenilaianSp)->getKeyName())->toBe('id_penilaian_sp')
        ->and((new InventarisSp)->getKeyName())->toBe('id_inventaris_sp')
        ->and((new FasilitasSp)->getKeyName())->toBe('id_fasilitas_sp');
});

it('menautkan komoditas ke satuan lewat kunci eksplisit dan slug route key', function () {
    $satuan = buatSatuanTon();
    $komoditas = Komoditas::create([
        'satuan_id' => $satuan->id_satuan, 'nama' => 'JAGUNG '.Str::random(4),
        'slug' => 'jagung-'.Str::lower(Str::random(6)), 'tipe' => 'Pangan', 'is_unggulan' => true,
    ]);

    expect($komoditas->satuan->id_satuan)->toBe($satuan->id_satuan)
        ->and($satuan->komoditas->pluck('id_komoditas'))->toContain($komoditas->id_komoditas)
        ->and($komoditas->is_unggulan)->toBeTrue()
        ->and($komoditas->getRouteKeyName())->toBe('slug');
});

it('menyimpan tipe komoditas sebagai teks, bukan PHP Enum', function () {
    $komoditas = Komoditas::create([
        'satuan_id' => buatSatuanTon()->id_satuan, 'nama' => 'SORGUM '.Str::random(4),
        'slug' => 'sorgum-'.Str::lower(Str::random(6)), 'tipe' => 'Serealia Baru',
    ]);

    // Nilai di luar daftar baku tetap tersimpan -- Admin boleh menambah lewat master.
    expect($komoditas->fresh()->tipe)->toBe('Serealia Baru');
});

it('menegakkan FK RESTRICT satuan saat masih dipakai komoditas', function () {
    $satuan = buatSatuanTon();
    Komoditas::create([
        'satuan_id' => $satuan->id_satuan, 'nama' => 'PADI '.Str::random(4),
        'slug' => 'padi-'.Str::lower(Str::random(6)), 'tipe' => 'Pangan',
    ]);

    expect(fn () => $satuan->delete())->toThrow(QueryException::class);
});

it('meng-cast ENUM jenis_fasilitas dan status penilaian ke PHP Enum', function () {
    $sp = buatSp();
    $fasilitas = FasilitasSp::create([
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'jenis_fasilitas' => JenisFasilitas::PendidikanDasar->value,
        'nama_fasilitas' => 'SD Inpres Kapitan Meo', 'status_penyerahan' => 'Sudah',
        'rincian_kondisi' => ['Baik' => 1],
    ]);
    $penilaian = PenilaianSp::create([
        'satuan_permukiman_id' => $sp->id_satuan_permukiman, 'tanggal_penilaian' => '2026-08-01',
        'skor' => '72.50', 'status' => StatusKondisiSpEnum::Berkembang->value,
        'ada_primer_nol' => false, 'rincian' => ['air_bersih' => 3],
    ]);

    expect($fasilitas->jenis_fasilitas)->toBe(JenisFasilitas::PendidikanDasar)
        ->and($fasilitas->rincian_kondisi)->toBe(['Baik' => 1])
        ->and($penilaian->status)->toBe(StatusKondisiSpEnum::Berkembang)
        ->and($penilaian->rincian)->toBe(['air_bersih' => 3])
        ->and($penilaian->tanggal_penilaian->format('Y-m-d'))->toBe('2026-08-01');
});

it('menolak nilai di luar ENUM jenis_fasilitas pada lapisan basis data', function () {
    $sp = buatSp();

    expect(fn () => DB::table('fasilitas_sp')->insert([
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'jenis_fasilitas' => 'Bioskop', 'nama_fasilitas' => 'X', 'status_penyerahan' => 'Sudah',
    ]))->toThrow(QueryException::class);
});

it('merujuk parameter penilaian ke baris referensi lewat id', function () {
    $ref = Referensi::create(['jenis' => JenisReferensi::JenisFasilitas->value, 'nilai' => 'Pendidikan Dasar']);
    $parameter = ParameterPenilaianSp::create([
        'kode' => 'pendidikan_dasar', 'nama' => 'Pendidikan Dasar', 'tingkat' => 'Primer',
        'bobot' => 10, 'sumber' => 'Fasilitas', 'referensi_id' => $ref->id_referensi, 'is_dinilai' => true,
    ]);

    expect($parameter->referensi->id_referensi)->toBe($ref->id_referensi)
        ->and($parameter->tingkat)->toBe('Primer')
        ->and($parameter->bobot)->toBe(10)
        ->and($parameter->is_dinilai)->toBeTrue();

    // referensi yang dirujuk parameter tidak boleh dihapus (RESTRICT).
    expect(fn () => $ref->delete())->toThrow(QueryException::class);
});

it('mewajibkan kode parameter penilaian unik', function () {
    $ref = Referensi::create(['jenis' => JenisReferensi::JenisInfrastruktur->value, 'nilai' => 'Air']);
    $atribut = [
        'kode' => 'air_bersih', 'nama' => 'Air Bersih', 'tingkat' => 'Primer',
        'bobot' => 15, 'sumber' => 'Infrastruktur', 'referensi_id' => $ref->id_referensi,
    ];
    ParameterPenilaianSp::create($atribut);

    expect(fn () => ParameterPenilaianSp::create($atribut))->toThrow(QueryException::class);
});

it('mencakup SP lewat pivot fasilitas_sp_cakupan dua arah', function () {
    $spPangkal = buatSp();
    $spTetangga = buatSp();
    $fasilitas = FasilitasSp::create([
        'satuan_permukiman_id' => $spPangkal->id_satuan_permukiman,
        'jenis_fasilitas' => JenisFasilitas::Kesehatan->value,
        'nama_fasilitas' => 'Puskesmas Pembantu', 'status_penyerahan' => 'Sudah',
    ]);

    $fasilitas->cakupan()->attach([$spPangkal->id_satuan_permukiman, $spTetangga->id_satuan_permukiman]);

    expect($fasilitas->cakupan()->count())->toBe(2)
        ->and($spPangkal->fasilitas->pluck('id_fasilitas_sp'))->toContain($fasilitas->id_fasilitas_sp);

    // pasangan sama tidak boleh dobel.
    expect(fn () => DB::table('fasilitas_sp_cakupan')->insert([
        'fasilitas_sp_id' => $fasilitas->id_fasilitas_sp,
        'satuan_permukiman_id' => $spPangkal->id_satuan_permukiman,
    ]))->toThrow(QueryException::class);
});

it('menghapus aset SP saat SP dihapus permanen (CASCADE)', function () {
    $sp = buatSp();
    InventarisSp::create([
        'satuan_permukiman_id' => $sp->id_satuan_permukiman, 'jenis_inventaris' => 'Kendaraan',
        'nama_barang' => 'Traktor Roda Empat', 'jumlah' => 2, 'status_penyerahan' => 'Belum',
        'rincian_kondisi' => ['Baik' => 1, 'Rusak Ringan' => 1],
    ]);
    FasilitasSp::create([
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'jenis_fasilitas' => JenisFasilitas::Ibadah->value, 'nama_fasilitas' => 'Masjid',
        'status_penyerahan' => 'Sudah',
    ]);

    expect($sp->inventaris()->count())->toBe(1)
        ->and($sp->fasilitas()->count())->toBe(1);

    $sp->forceDelete();

    expect(InventarisSp::withTrashed()->where('satuan_permukiman_id', $sp->id_satuan_permukiman)->count())->toBe(0)
        ->and(FasilitasSp::withTrashed()->where('satuan_permukiman_id', $sp->id_satuan_permukiman)->count())->toBe(0);
});

it('menahan SP dengan riwayat penilaian dari penghapusan (RESTRICT)', function () {
    $sp = buatSp();
    PenilaianSp::create([
        'satuan_permukiman_id' => $sp->id_satuan_permukiman, 'tanggal_penilaian' => '2026-07-01',
        'skor' => '80.00', 'status' => StatusKondisiSpEnum::Mandiri->value,
        'ada_primer_nol' => false, 'rincian' => [],
    ]);

    expect(fn () => $sp->forceDelete())->toThrow(QueryException::class);
});

it('mengaktifkan soft delete hanya pada komoditas, inventaris_sp, fasilitas_sp', function () {
    expect(in_array(SoftDeletes::class, class_uses_recursive(Komoditas::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(InventarisSp::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(FasilitasSp::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(Satuan::class), true))->toBeFalse()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(ParameterPenilaianSp::class), true))->toBeFalse()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(PenilaianSp::class), true))->toBeFalse()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(StatusKondisiSp::class), true))->toBeFalse();
});

it('menyimpan tiga baris ambang status kondisi SP dengan kode unik', function () {
    foreach ([
        ['kode' => 'Mandiri', 'nama' => 'Mandiri', 'ambang_bawah' => '75.00', 'warna' => 'success', 'urutan' => 1],
        ['kode' => 'Berkembang', 'nama' => 'Berkembang', 'ambang_bawah' => '50.00', 'warna' => 'warning', 'urutan' => 2],
        ['kode' => 'Perlu Penanganan', 'nama' => 'Perlu Penanganan', 'ambang_bawah' => '0.00', 'warna' => 'error', 'urutan' => 3],
    ] as $baris) {
        StatusKondisiSp::create($baris);
    }

    expect(StatusKondisiSp::count())->toBe(3)
        ->and(StatusKondisiSp::where('kode', 'Mandiri')->value('ambang_bawah'))->toBe('75.00');

    expect(fn () => StatusKondisiSp::create([
        'kode' => 'Mandiri', 'nama' => 'Duplikat', 'ambang_bawah' => '90.00', 'warna' => 'success',
    ]))->toThrow(QueryException::class);
});
