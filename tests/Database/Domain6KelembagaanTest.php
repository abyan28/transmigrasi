<?php

/*
 * Task 3.1 -- DOMAIN 6 (Kelembagaan & Sarana).
 *
 * Tabel: poktan, anggota_poktan, alsintan, alsintan_distribusi, saprotan,
 * saprotan_distribusi (+ pivot alsintan_berkas).
 *
 * Berjalan di MySQL/MariaDB nyata. `buatSp()` & `buatTransmigran()` di-share
 * dari batch sebelumnya. `sim:banding-skema` menjaga kecocokan kolom/indeks/FK.
 */

use App\Enums\AsalWakilPoktan;
use App\Enums\JenisSaprotan;
use App\Enums\StatusKeaktifanAnggota;
use App\Models\Alsintan;
use App\Models\AlsintanDistribusi;
use App\Models\AnggotaPoktan;
use App\Models\Komoditas;
use App\Models\Poktan;
use App\Models\Saprotan;
use App\Models\SaprotanDistribusi;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require_once __DIR__.'/DatabaseHelpers.php';

it('membuat keenam tabel + pivot alsintan_berkas', function () {
    foreach ([
        'poktan', 'anggota_poktan', 'alsintan', 'alsintan_distribusi',
        'saprotan', 'saprotan_distribusi', 'alsintan_berkas',
    ] as $tabel) {
        expect(Schema::hasTable($tabel))->toBeTrue("tabel {$tabel} tidak dibuat");
    }
});

it('memakai PK tunggal dan slug route key untuk poktan', function () {
    expect((new Poktan)->getKeyName())->toBe('id_poktan')
        ->and((new Poktan)->getRouteKeyName())->toBe('slug')
        ->and((new AnggotaPoktan)->getKeyName())->toBe('id_anggota_poktan')
        ->and((new Alsintan)->getKeyName())->toBe('id_alsintan')
        ->and((new AlsintanDistribusi)->getKeyName())->toBe('id_alsintan_distribusi')
        ->and((new Saprotan)->getKeyName())->toBe('id_saprotan')
        ->and((new SaprotanDistribusi)->getKeyName())->toBe('id_saprotan_distribusi');
});

it('merangkai poktan -> anggota -> KELUARGA yang diwakili lewat kunci eksplisit', function () {
    $sp = buatSp();
    $poktan = buatPoktan($sp);
    $kk = buatTransmigran($sp);
    $anggota = AnggotaPoktan::create([
        'poktan_id' => $poktan->id_poktan, 'transmigran_id' => $kk->id_transmigran,
        'jabatan' => 'Bendahara', 'tanggal_masuk' => '2016-01-10',
        'status' => StatusKeaktifanAnggota::Aktif->value,
    ]);

    expect($anggota->poktan->id_poktan)->toBe($poktan->id_poktan)
        ->and($anggota->transmigran->id_transmigran)->toBe($kk->id_transmigran)
        ->and($anggota->status)->toBe(StatusKeaktifanAnggota::Aktif)
        ->and($anggota->fresh()->asal_wakil)->toBe(AsalWakilPoktan::KepalaKeluarga)
        ->and($poktan->anggota->pluck('id_anggota_poktan'))->toContain($anggota->id_anggota_poktan)
        ->and($sp->poktan->pluck('id_poktan'))->toContain($poktan->id_poktan);
});

it('mewajibkan satu KELUARGA hanya sekali per poktan (UNIQUE komposit)', function () {
    $sp = buatSp();
    $poktan = buatPoktan($sp);
    $kk = buatTransmigran($sp);
    $baris = [
        'poktan_id' => $poktan->id_poktan, 'transmigran_id' => $kk->id_transmigran,
        'jabatan' => 'Anggota', 'tanggal_masuk' => '2016-01-10',
        'status' => StatusKeaktifanAnggota::Aktif->value,
    ];
    AnggotaPoktan::create($baris);

    expect(fn () => AnggotaPoktan::create($baris))->toThrow(QueryException::class);
});

it('menghapus keanggotaan saat poktan dihapus permanen (CASCADE) tapi menahan KELUARGA-nya (RESTRICT)', function () {
    $sp = buatSp();
    $poktan = buatPoktan($sp);
    $kk = buatTransmigran($sp);
    AnggotaPoktan::create([
        'poktan_id' => $poktan->id_poktan, 'transmigran_id' => $kk->id_transmigran,
        'jabatan' => 'Anggota', 'tanggal_masuk' => '2016-01-10',
        'status' => StatusKeaktifanAnggota::Aktif->value,
    ]);

    // KK yang masih menjadi anggota poktan tidak dapat dihapus permanen.
    expect(fn () => $kk->forceDelete())->toThrow(QueryException::class);

    $poktan->forceDelete();
    expect(AnggotaPoktan::withTrashed()->where('poktan_id', $poktan->id_poktan)->count())->toBe(0);
});

it('mendistribusikan alsintan satu pengadaan ke banyak poktan', function () {
    $sp = buatSp();
    $p1 = buatPoktan($sp);
    $p2 = buatPoktan($sp);
    $alsintan = Alsintan::create([
        'jenis_alsintan' => 'Traktor Roda Dua', 'nama_alat' => 'Quick G1000',
        'jumlah_total' => 5,
    ]);
    $d1 = AlsintanDistribusi::create(['alsintan_id' => $alsintan->id_alsintan, 'poktan_id' => $p1->id_poktan, 'jumlah' => 2, 'kondisi' => 'Baik']);
    AlsintanDistribusi::create(['alsintan_id' => $alsintan->id_alsintan, 'poktan_id' => $p2->id_poktan, 'jumlah' => 1]);

    expect($alsintan->distribusi()->count())->toBe(2)
        ->and((int) $alsintan->distribusi()->sum('jumlah'))->toBe(3)
        ->and($d1->poktan->id_poktan)->toBe($p1->id_poktan);

    // poktan penerima tidak dapat dihapus selagi memegang distribusi (RESTRICT).
    expect(fn () => $p1->forceDelete())->toThrow(QueryException::class);

    // menghapus induk pengadaan menyapu seluruh distribusinya (CASCADE).
    $alsintan->forceDelete();
    expect(AlsintanDistribusi::count())->toBe(0);
});

it('menautkan saprotan benih ke komoditas + satuan, distribusi ke poktan', function () {
    $sp = buatSp();
    $poktan = buatPoktan($sp);
    $satuan = buatSatuanTon();
    $komoditas = Komoditas::create([
        'satuan_id' => $satuan->id_satuan, 'nama' => 'JAGUNG '.Str::random(4),
        'slug' => 'jagung-'.Str::lower(Str::random(6)), 'tipe' => 'Pangan',
    ]);
    $saprotan = Saprotan::create([
        'satuan_id' => $satuan->id_satuan, 'komoditas_id' => $komoditas->id_komoditas,
        'jenis' => JenisSaprotan::Benih->value, 'nama' => 'Benih Jagung Hibrida',
        'jumlah_total' => '150.000', 'varietas' => 'NK212', 'jadwal_tanam' => '2026-11',
        'tahun_pengadaan' => 2026,
    ]);
    SaprotanDistribusi::create(['saprotan_id' => $saprotan->id_saprotan, 'poktan_id' => $poktan->id_poktan, 'jumlah' => '75.500']);

    expect($saprotan->jenis)->toBe(JenisSaprotan::Benih)
        ->and($saprotan->jumlah_total)->toBe('150.000')
        ->and($saprotan->komoditas->id_komoditas)->toBe($komoditas->id_komoditas)
        ->and($saprotan->distribusi->first()->jumlah)->toBe('75.500');

    // satuan yang dipakai saprotan tidak dapat dihapus (RESTRICT).
    expect(fn () => $satuan->delete())->toThrow(QueryException::class);
});

it('mengaktifkan soft delete pada poktan/anggota_poktan/alsintan/saprotan, tidak pada tabel distribusi', function () {
    expect(in_array(SoftDeletes::class, class_uses_recursive(Poktan::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(AnggotaPoktan::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(Alsintan::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(Saprotan::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(AlsintanDistribusi::class), true))->toBeFalse()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(SaprotanDistribusi::class), true))->toBeFalse();
});
