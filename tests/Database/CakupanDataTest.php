<?php

/*
 * Task 3.4 -- CAKUPAN DATA (global scope Eloquent).
 *
 * `rules.md` 5.0b + rancangan mengikat 5.0b-1. Titik penegakan tunggal:
 * `App\Models\Scopes\CakupanDataSp` pada model pemilik SP, `DisaringLewatInduk`
 * pada model turunan. Diuji lewat query Eloquent nyata (MySQL/MariaDB).
 */

use App\Enums\CakupanData;
use App\Enums\HubunganAnggotaKeluarga;
use App\Enums\JenisKelamin;
use App\Enums\StatusPengaduan;
use App\Enums\SumberLaporan;
use App\Models\AnggotaKeluarga;
use App\Models\Pengaduan;
use App\Models\Poktan;
use App\Models\Role;
use App\Models\SatuanPermukiman;
use App\Models\Scopes\CakupanDataSp;
use App\Models\Transmigran;
use App\Models\User;
use Illuminate\Support\Str;

require_once __DIR__.'/DatabaseHelpers.php';

/**
 * Pengguna dengan role bercakupan tertentu + daftar SP yang ditugaskan.
 *
 * @param  array<int, SatuanPermukiman>  $spDitugaskan
 */
function penggunaCakupan(CakupanData $cakupan, array $spDitugaskan = []): User
{
    $role = Role::factory()->create(['cakupan_data' => $cakupan->value]);
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);

    if ($spDitugaskan !== []) {
        $pengguna->satuanPermukiman()->attach(
            collect($spDitugaskan)->pluck('id_satuan_permukiman')->all()
        );
    }

    return $pengguna;
}

function buatPengaduanBidang(SatuanPermukiman $sp, ?string $bidang): Pengaduan
{
    return Pengaduan::create([
        'uuid' => (string) Str::uuid(),
        'nama_pelapor' => 'Warga '.Str::random(4),
        'kontak_pelapor' => '0812'.random_int(1000000, 9999999),
        'sumber_laporan' => SumberLaporan::Publik->value,
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'nomor_pengaduan' => 'PGD-'.random_int(1000, 9999).'-'.Str::upper(Str::random(6)),
        'tanggal_pengaduan' => '2026-08-15',
        'kategori' => 'Jalan Rusak',
        'bidang' => $bidang,
        'judul' => 'Uji cakupan '.Str::random(4),
        'deskripsi' => 'Deskripsi uji cakupan data.',
        'status' => StatusPengaduan::MenungguDiterima->value,
        'prioritas' => 'Tinggi',
    ]);
}

it('tanpa pengguna terautentikasi (artisan/seeder/job) tidak menyaring apa pun', function () {
    buatTransmigran(buatSp());
    buatTransmigran(buatSp());

    // Grup Database TIDAK memakai beforeEach actingAs -- di sini benar-benar tamu.
    expect(Transmigran::count())->toBe(2);
});

it('role bercakupan Semua melihat seluruh baris', function () {
    buatTransmigran(buatSp());
    buatTransmigran(buatSp());

    $this->actingAs(penggunaCakupan(CakupanData::Semua));

    expect(Transmigran::count())->toBe(2);
});

it('role Per SP hanya melihat baris SP yang ditugaskan', function () {
    $spA = buatSp();
    $spB = buatSp();
    $milikA = buatTransmigran($spA);
    buatTransmigran($spB);

    $this->actingAs(penggunaCakupan(CakupanData::PerSp, [$spA]));

    $terlihat = Transmigran::get();
    expect($terlihat)->toHaveCount(1)
        ->and($terlihat->first()->id_transmigran)->toBe($milikA->id_transmigran);
});

it('role Per SP tanpa penugasan melihat NOL baris, bukan seluruhnya', function () {
    buatTransmigran(buatSp());
    buatTransmigran(buatSp());

    $this->actingAs(penggunaCakupan(CakupanData::PerSp, []));

    expect(Transmigran::count())->toBe(0)
        ->and(Poktan::count())->toBe(0);
});

it('menyaring model turunan lewat induknya (anggota keluarga ikut transmigran)', function () {
    $spA = buatSp();
    $spB = buatSp();

    $kkA = buatTransmigran($spA);
    AnggotaKeluarga::create([
        'transmigran_id' => $kkA->id_transmigran,
        'hubungan' => HubunganAnggotaKeluarga::Anak->value,
        'nama_lengkap' => 'Anak SP A', 'jenis_kelamin' => JenisKelamin::LakiLaki->value,
    ]);

    $kkB = buatTransmigran($spB);
    AnggotaKeluarga::create([
        'transmigran_id' => $kkB->id_transmigran,
        'hubungan' => HubunganAnggotaKeluarga::Anak->value,
        'nama_lengkap' => 'Anak SP B', 'jenis_kelamin' => JenisKelamin::Perempuan->value,
    ]);

    $this->actingAs(penggunaCakupan(CakupanData::PerSp, [$spA]));

    $terlihat = AnggotaKeluarga::get();
    expect($terlihat)->toHaveCount(1)
        ->and($terlihat->first()->nama_lengkap)->toBe('Anak SP A');
});

it('tidak menyaring data referensi (SatuanPermukiman sendiri tetap utuh)', function () {
    buatSp();
    buatSp();
    buatSp();

    $this->actingAs(penggunaCakupan(CakupanData::PerSp, []));

    expect(SatuanPermukiman::count())->toBe(3);
});

it('dapat dilewati eksplisit dengan withoutGlobalScope', function () {
    buatTransmigran(buatSp());
    buatTransmigran(buatSp());

    $this->actingAs(penggunaCakupan(CakupanData::PerSp, []));

    expect(Transmigran::count())->toBe(0)
        ->and(Transmigran::withoutGlobalScope(CakupanDataSp::class)->count())->toBe(2);
});

it('menghitung agregat/paginasi setelah penyaringan', function () {
    $spA = buatSp();
    buatTransmigran($spA);
    buatTransmigran($spA);
    buatTransmigran(buatSp());

    $this->actingAs(penggunaCakupan(CakupanData::PerSp, [$spA]));

    expect(Transmigran::count())->toBe(2)
        ->and(Transmigran::paginate(10)->total())->toBe(2);
});

it('role Per Bidang (Dinas Pertanian) hanya melihat pengaduan bidang Pertanian', function () {
    $sp = buatSp();
    $pertanian = buatPengaduanBidang($sp, 'Pertanian');
    buatPengaduanBidang($sp, 'Ketransmigrasian');
    buatPengaduanBidang($sp, null);

    $this->actingAs(penggunaCakupan(CakupanData::PerBidang));

    $terlihat = Pengaduan::get();
    expect($terlihat)->toHaveCount(1)
        ->and($terlihat->first()->id_pengaduan)->toBe($pertanian->id_pengaduan);
});

it('role Per Bidang berjangkauan penuh pada model non-pengaduan', function () {
    buatTransmigran(buatSp());
    buatTransmigran(buatSp());

    $this->actingAs(penggunaCakupan(CakupanData::PerBidang));

    expect(Transmigran::count())->toBe(2);
});
