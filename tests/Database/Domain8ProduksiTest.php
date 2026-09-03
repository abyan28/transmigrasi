<?php

/*
 * Task 3.1 -- DOMAIN 8 (Produksi Pertanian).
 *
 * Tabel: komoditas_poktan (pivot), penanaman, hasil_panen (+ pivot
 * penanaman_berkas, hasil_panen_berkas). `sim:banding-skema` menjaga kolom/FK.
 */

use App\Models\Berkas;
use App\Models\HasilPanen;
use App\Models\Komoditas;
use App\Models\Penanaman;
use App\Models\Poktan;
use App\Models\Saprotan;
use App\Models\SaprotanDistribusi;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require_once __DIR__.'/DatabaseHelpers.php';

/**
 * Rantai lengkap sampai satu baris penanaman: poktan + komoditas + saprotan
 * benih + distribusi ke poktan itu.
 */
function buatPenanaman(array $atribut = []): Penanaman
{
    $poktan = buatPoktan();
    $satuan = buatSatuanTon();
    $komoditas = Komoditas::create([
        'satuan_id' => $satuan->id_satuan, 'nama' => 'JAGUNG '.Str::random(4),
        'slug' => 'jagung-'.Str::lower(Str::random(6)), 'tipe' => 'Pangan',
    ]);
    $saprotan = Saprotan::create([
        'satuan_id' => $satuan->id_satuan, 'komoditas_id' => $komoditas->id_komoditas,
        'jenis' => 'Benih', 'nama' => 'Benih Jagung', 'jumlah_total' => '100.000',
        'tahun_pengadaan' => 2026,
    ]);
    $distribusi = SaprotanDistribusi::create([
        'saprotan_id' => $saprotan->id_saprotan, 'poktan_id' => $poktan->id_poktan, 'jumlah' => '40.000',
    ]);

    return Penanaman::create(array_merge([
        'poktan_id' => $poktan->id_poktan,
        'komoditas_id' => $komoditas->id_komoditas,
        'saprotan_distribusi_id' => $distribusi->id_saprotan_distribusi,
        'volume_benih' => '25.000',
        'realisasi_tanam' => '5.00',
        'periode_tanam' => '2026-11',
    ], $atribut));
}

it('membuat ketiga tabel + dua pivot batch ini', function () {
    foreach ([
        'komoditas_poktan', 'penanaman', 'hasil_panen', 'penanaman_berkas', 'hasil_panen_berkas',
    ] as $tabel) {
        expect(Schema::hasTable($tabel))->toBeTrue("tabel {$tabel} tidak dibuat");
    }
});

it('memakai PK tunggal dan uuid route key untuk hasil_panen', function () {
    expect((new Penanaman)->getKeyName())->toBe('id_penanaman')
        ->and((new HasilPanen)->getKeyName())->toBe('id_hasil_panen')
        ->and((new HasilPanen)->getRouteKeyName())->toBe('uuid');
});

it('menautkan poktan <-> komoditas lewat pivot M:N komoditas_poktan', function () {
    $poktan = buatPoktan();
    $satuan = buatSatuanTon();
    $jagung = Komoditas::create(['satuan_id' => $satuan->id_satuan, 'nama' => 'JAGUNG '.Str::random(4), 'slug' => 'j-'.Str::lower(Str::random(6)), 'tipe' => 'Pangan']);
    $padi = Komoditas::create(['satuan_id' => $satuan->id_satuan, 'nama' => 'PADI '.Str::random(4), 'slug' => 'p-'.Str::lower(Str::random(6)), 'tipe' => 'Pangan']);

    $poktan->komoditas()->attach([$jagung->id_komoditas, $padi->id_komoditas]);

    expect($poktan->komoditas()->count())->toBe(2)
        ->and($jagung->poktan->pluck('id_poktan'))->toContain($poktan->id_poktan);

    // pasangan sama tidak boleh dobel.
    expect(fn () => $poktan->komoditas()->attach($jagung->id_komoditas))->toThrow(QueryException::class);
});

it('merangkai penanaman -> poktan/komoditas/jatah benih lewat kunci eksplisit', function () {
    $tanam = buatPenanaman();

    expect($tanam->poktan)->toBeInstanceOf(Poktan::class)
        ->and($tanam->komoditas)->toBeInstanceOf(Komoditas::class)
        ->and($tanam->saprotanDistribusi)->toBeInstanceOf(SaprotanDistribusi::class)
        ->and($tanam->volume_benih)->toBe('25.000')
        ->and($tanam->saprotanDistribusi->penanaman->pluck('id_penanaman'))->toContain($tanam->id_penanaman);
});

it('mengikat hasil panen ke penanaman dan menyalin satuan sebagai snapshot', function () {
    $tanam = buatPenanaman(['realisasi_tanam' => '5.00']);
    $satuanId = $tanam->komoditas->satuan_id;
    $panen = HasilPanen::create([
        'uuid' => (string) Str::uuid(), 'penanaman_id' => $tanam->id_penanaman,
        'satuan_id' => $satuanId, 'periode_panen' => '2027-03',
        'realisasi_panen' => '4.50', 'puso' => '0.50', 'produktivitas' => '6.200',
        'produksi' => '27.900',
    ]);

    expect($panen->penanaman->id_penanaman)->toBe($tanam->id_penanaman)
        ->and($panen->satuan->id_satuan)->toBe($satuanId)
        ->and($tanam->hasilPanen->id_hasil_panen)->toBe($panen->id_hasil_panen)
        ->and($panen->produksi)->toBe('27.900');
});

it('menyapu hasil panen saat penanaman dihapus permanen (CASCADE), menahan satuan (RESTRICT)', function () {
    $tanam = buatPenanaman();
    $satuan = $tanam->komoditas->satuan;
    HasilPanen::create([
        'uuid' => (string) Str::uuid(), 'penanaman_id' => $tanam->id_penanaman,
        'satuan_id' => $satuan->id_satuan, 'periode_panen' => '2027-03',
        'realisasi_panen' => '5.00', 'produktivitas' => '6.000', 'produksi' => '30.000',
    ]);

    // satuan yang dipakai hasil panen tidak dapat dihapus.
    expect(fn () => $satuan->delete())->toThrow(QueryException::class);

    $tanam->forceDelete();
    expect(HasilPanen::withTrashed()->where('penanaman_id', $tanam->id_penanaman)->count())->toBe(0);
});

it('menautkan berkas ke penanaman & hasil panen lewat pivot', function () {
    $tanam = buatPenanaman();
    $panen = HasilPanen::create([
        'uuid' => (string) Str::uuid(), 'penanaman_id' => $tanam->id_penanaman,
        'satuan_id' => $tanam->komoditas->satuan_id, 'periode_panen' => '2027-03',
        'realisasi_panen' => '5.00', 'produktivitas' => '6.000', 'produksi' => '30.000',
    ]);
    $ba = Berkas::create(['uuid' => (string) Str::uuid(), 'nama_file' => 'ba.pdf', 'path' => 'x/ba.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 100]);
    $foto = Berkas::create(['uuid' => (string) Str::uuid(), 'nama_file' => 'f.jpg', 'path' => 'x/f.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 200]);

    $tanam->berkas()->attach($ba->id_berkas, ['peran' => 'berita_acara']);
    $panen->berkas()->attach($foto->id_berkas, ['peran' => 'foto']);

    expect($tanam->berkas()->count())->toBe(1)
        ->and($panen->berkas()->first()->pivot->peran)->toBe('foto');
});

it('mengaktifkan soft delete pada penanaman dan hasil_panen', function () {
    expect(in_array(SoftDeletes::class, class_uses_recursive(Penanaman::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(HasilPanen::class), true))->toBeTrue();
});
