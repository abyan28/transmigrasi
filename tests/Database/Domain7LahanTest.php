<?php

/*
 * Task 3.1 -- DOMAIN 7 (Lahan).
 *
 * Satu tabel: `lahan`. SATU BARIS = SATU KELUARGA (Putaran 15), dua pasang
 * koordinat (`*_pekarangan`, `*_usaha`). `sim:banding-skema` menjaga kolom/FK.
 * `buatSp()`/`buatTransmigran()`/`buatPoktan()` di-share dari DatabaseHelpers.
 */

use App\Models\Lahan;
use App\Models\Poktan;
use App\Models\Transmigran;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require_once __DIR__.'/DatabaseHelpers.php';

function buatLahan(Transmigran $kk, array $atribut = []): Lahan
{
    return Lahan::create(array_merge([
        'uuid' => (string) Str::uuid(),
        'transmigran_id' => $kk->id_transmigran,
        'satuan_permukiman_id' => $kk->satuan_permukiman_id,
    ], $atribut));
}

it('membuat tabel lahan dengan PK tunggal + uuid route key', function () {
    expect(Schema::hasTable('lahan'))->toBeTrue()
        ->and((new Lahan)->getKeyName())->toBe('id_lahan')
        ->and((new Lahan)->getTable())->toBe('lahan')
        ->and((new Lahan)->getRouteKeyName())->toBe('uuid');
});

it('mengikat lahan ke KK satu-baris-per-keluarga (UNIQUE transmigran_id)', function () {
    $sp = buatSp();
    $kk = buatTransmigran($sp);
    $lahan = buatLahan($kk, ['luas_pekarangan' => '0.25', 'luas_usaha' => '1.75']);

    expect($lahan->transmigran->id_transmigran)->toBe($kk->id_transmigran)
        ->and($kk->lahan->id_lahan)->toBe($lahan->id_lahan)
        ->and($sp->lahan->pluck('id_lahan'))->toContain($lahan->id_lahan);

    // baris lahan kedua untuk KK yang sama ditolak.
    expect(fn () => buatLahan($kk))->toThrow(QueryException::class);
});

it('menyimpan dua pasang koordinat terpisah untuk pekarangan dan lahan usaha', function () {
    $kk = buatTransmigran();
    $lahan = buatLahan($kk, [
        'luas_pekarangan' => '0.25', 'lintang_pekarangan' => '-9.4501234', 'bujur_pekarangan' => '124.9012345',
        'luas_usaha' => '2.00', 'luas_kering' => '1.20', 'luas_basah' => '0.80',
        'lintang_usaha' => '-9.4601234', 'bujur_usaha' => '124.9112345',
    ]);

    $lahan->refresh();
    expect($lahan->lintang_pekarangan)->toBe('-9.4501234')
        ->and($lahan->bujur_usaha)->toBe('124.9112345')
        ->and($lahan->lintang_pekarangan)->not->toBe($lahan->lintang_usaha)
        ->and((float) $lahan->luas_kering + (float) $lahan->luas_basah)->toBe((float) $lahan->luas_usaha);
});

it('membedakan bidang yang belum diterima (NULL) dari seluas nol', function () {
    $kk = buatTransmigran();
    // KK yang baru menerima lahan usaha saja: pekarangan tetap NULL.
    $lahan = buatLahan($kk, ['luas_usaha' => '1.50', 'luas_kering' => '1.50', 'luas_basah' => '0.00']);

    expect($lahan->fresh()->luas_pekarangan)->toBeNull()
        ->and($lahan->fresh()->luas_usaha)->toBe('1.50');
});

it('mewajibkan kode_lahan unik bila diisi', function () {
    $kk1 = buatTransmigran();
    $kk2 = buatTransmigran();
    buatLahan($kk1, ['kode_lahan' => 'KM-SP1-001']);

    expect(fn () => buatLahan($kk2, ['kode_lahan' => 'KM-SP1-001']))->toThrow(QueryException::class);
});

it('menyapu lahan saat KK dihapus permanen (CASCADE), menahan SP (RESTRICT), melepas poktan (SET NULL)', function () {
    $sp = buatSp();
    $poktan = buatPoktan($sp);
    $kk = buatTransmigran($sp);
    $lahan = buatLahan($kk, ['poktan_id' => $poktan->id_poktan]);

    // SP yang memiliki lahan tidak dapat dihapus.
    expect(fn () => $sp->forceDelete())->toThrow(QueryException::class);

    // poktan pengelola dihapus -> lahan lepas, tidak ikut terhapus.
    $poktan->forceDelete();
    expect($lahan->fresh()->poktan_id)->toBeNull();

    // KK dihapus permanen -> lahannya ikut tersapu.
    $kk->forceDelete();
    expect(Lahan::withTrashed()->where('transmigran_id', $kk->id_transmigran)->count())->toBe(0);
});

it('mengaktifkan soft delete pada lahan', function () {
    expect(in_array(SoftDeletes::class, class_uses_recursive(Lahan::class), true))->toBeTrue();

    $kk = buatTransmigran();
    $lahan = buatLahan($kk);
    $id = $lahan->id_lahan;
    $lahan->delete();

    expect(Lahan::find($id))->toBeNull()
        ->and(Lahan::withTrashed()->find($id))->not->toBeNull();

    // baris masih ada -> KK belum bisa dapat lahan baru tanpa forceDelete/restore
    // (UNIQUE transmigran_id tidak melihat deleted_at).
    expect(fn () => buatLahan($kk))->toThrow(QueryException::class);
});
