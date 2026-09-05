<?php

use App\Enums\AksiPermission;
use App\Enums\CakupanData;
use App\Enums\JenisNotifikasi;
use App\Models\Notifikasi;
use App\Models\Pengaduan;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SatuanPermukiman;
use App\Models\User;
use App\Support\LayananNotifikasi;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\PenilaianKondisiSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Support\Str;

function penggunaNotifikasi(string $izin, CakupanData $cakupan = CakupanData::Semua): User
{
    [$modul, $aksi] = explode('.', $izin);
    $role = Role::factory()->create(['cakupan_data' => $cakupan]);
    $permission = Permission::firstOrCreate(
        ['nama' => $izin],
        [
            'modul' => $modul,
            'aksi' => AksiPermission::from($aksi),
            'label' => ucfirst($aksi).' '.$modul,
        ],
    );
    $role->permissions()->attach($permission);

    return User::factory()->create(['role_id' => $role->id_role]);
}

beforeEach(function () {
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);
    $this->seed(PenilaianKondisiSeeder::class);
});

it('mengirim pengaduan baru hanya kepada pengguna yang berizin dan bercakupan', function () {
    $sp = SatuanPermukiman::first();
    $semua = penggunaNotifikasi('pengaduan.lihat');
    $perSp = penggunaNotifikasi('pengaduan.lihat', CakupanData::PerSp);
    $tanpaIzin = User::factory()->create();
    $perSp->satuanPermukiman()->attach($sp);

    $pengaduan = Pengaduan::create([
        'uuid' => (string) Str::uuid(), 'nama_pelapor' => 'WARGA UJI',
        'kontak_pelapor' => '081200000000', 'sumber_laporan' => 'Publik',
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'nomor_pengaduan' => 'PGD-UJI-001', 'tanggal_pengaduan' => today(),
        'kategori' => 'Rumah', 'bidang' => 'Ketransmigrasian', 'judul' => 'ATAP BOCOR',
        'deskripsi' => 'Atap bocor.', 'status' => 'Menunggu Diterima', 'prioritas' => 'Sedang',
    ]);

    LayananNotifikasi::pengaduanBaru($pengaduan);

    expect(Notifikasi::where('jenis', JenisNotifikasi::PengaduanBaru)->pluck('user_id')->all())
        ->toEqualCanonicalizing([$semua->id_user, $perSp->id_user])
        ->not->toContain($tanpaIzin->id_user);
});

it('menahan pengaduan tanpa bidang dari penerima Per Bidang', function () {
    $sp = SatuanPermukiman::first();
    $pertanian = penggunaNotifikasi('pengaduan.lihat', CakupanData::PerBidang);
    $pengaduan = Pengaduan::create([
        'uuid' => (string) Str::uuid(), 'nama_pelapor' => 'WARGA UJI',
        'kontak_pelapor' => '081200000009', 'sumber_laporan' => 'Publik',
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'nomor_pengaduan' => 'PGD-UJI-009', 'tanggal_pengaduan' => today(),
        'kategori' => 'Bencana', 'bidang' => null, 'judul' => 'LAPORAN NETRAL',
        'deskripsi' => 'Belum ditetapkan bidangnya.', 'status' => 'Menunggu Diterima', 'prioritas' => 'Sedang',
    ]);

    LayananNotifikasi::pengaduanBaru($pengaduan);

    expect(Notifikasi::where('user_id', $pertanian->id_user)->exists())->toBeFalse();
});

it('mendeduplikasi notifikasi per penerima bukan secara global', function () {
    $sp = SatuanPermukiman::first();
    $a = penggunaNotifikasi('pengaduan.lihat');
    $b = penggunaNotifikasi('pengaduan.lihat');
    $pengaduan = Pengaduan::create([
        'uuid' => (string) Str::uuid(), 'nama_pelapor' => 'WARGA UJI',
        'kontak_pelapor' => '081200000001', 'sumber_laporan' => 'Publik',
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'nomor_pengaduan' => 'PGD-UJI-002', 'tanggal_pengaduan' => today(),
        'kategori' => 'Rumah', 'judul' => 'JALAN RUSAK', 'deskripsi' => 'Rusak.',
        'status' => 'Menunggu Diterima', 'prioritas' => 'Sedang',
    ]);

    LayananNotifikasi::pengaduanBaru($pengaduan);
    LayananNotifikasi::pengaduanBaru($pengaduan);

    expect(Notifikasi::where('jenis', JenisNotifikasi::PengaduanBaru)->count())->toBe(2)
        ->and(Notifikasi::pluck('user_id')->all())->toEqualCanonicalizing([$a->id_user, $b->id_user]);
});

it('membuat notifikasi infrastruktur rusak berat dan kondisi SP setelah cakupan tersimpan', function () {
    $penerima = penggunaNotifikasi('infrastruktur.lihat');
    $penerimaPenilaian = penggunaNotifikasi('penilaian_kondisi.lihat');
    $pelaku = User::factory()->create();
    $pelaku->semuaIzin = true;
    $this->actingAs($pelaku);
    $sp = SatuanPermukiman::first();

    $this->post(route('infrastruktur.simpan'), [
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'nama' => 'JALAN UJI RUSAK',
        'jenis' => 'Jalan Penghubung',
        'kondisi' => 'Rusak Berat',
    ])->assertRedirect();

    expect(Notifikasi::where('jenis', JenisNotifikasi::InfrastrukturRusakBerat)
        ->where('user_id', $penerima->id_user)->exists())->toBeTrue()
        ->and(Notifikasi::where('jenis', JenisNotifikasi::SpPerluPenanganan)
            ->where('user_id', $penerimaPenilaian->id_user)->exists())->toBeTrue();
});

it('memberi tahu admin lain tentang aktivitas akun tanpa memberi tahu pelakunya', function () {
    $role = Role::factory()->terkunci()->create();
    $pelaku = User::factory()->create(['role_id' => $role->id_role]);
    $adminLain = User::factory()->create(['role_id' => $role->id_role]);
    $subjek = User::factory()->create();

    LayananNotifikasi::akun($subjek, 'Akun baru dibuat.', $pelaku->id_user);
    LayananNotifikasi::akun($subjek, 'Kata sandi disetel ulang.', $pelaku->id_user);

    expect(Notifikasi::where('user_id', $adminLain->id_user)->count())->toBe(2)
        ->and(Notifikasi::where('user_id', $adminLain->id_user)->whereNull('dibaca_at')->count())->toBe(1)
        ->and(Notifikasi::where('user_id', $pelaku->id_user)->exists())->toBeFalse();
});

it('mencegah pengguna membaca notifikasi milik pengguna lain', function () {
    $pemilik = penggunaNotifikasi('pengaduan.lihat');
    $lain = penggunaNotifikasi('pengaduan.lihat');
    $notifikasi = Notifikasi::create([
        'user_id' => $pemilik->id_user,
        'jenis' => JenisNotifikasi::AkunPengguna,
        'subjek_user_id' => $pemilik->id_user,
        'pesan' => 'Uji.',
    ]);

    $this->actingAs($lain)->put(route('notifikasi.baca', $notifikasi))->assertNotFound();
    expect($notifikasi->fresh()->dibaca_at)->toBeNull();
});

it('menandai seluruh notifikasi milik pengguna sendiri sebagai dibaca', function () {
    $user = penggunaNotifikasi('pengaduan.lihat');
    $lain = penggunaNotifikasi('pengaduan.lihat');
    foreach ([$user, $user, $lain] as $penerima) {
        Notifikasi::create([
            'user_id' => $penerima->id_user,
            'jenis' => JenisNotifikasi::AkunPengguna,
            'subjek_user_id' => $penerima->id_user,
            'pesan' => 'Uji.',
        ]);
    }

    $this->actingAs($user)->get(route('notifikasi.index'))
        ->assertOk()->assertSee('Tandai semua dibaca');

    $this->actingAs($user)->put(route('notifikasi.baca-semua'))->assertRedirect();

    expect($user->notifikasi()->whereNull('dibaca_at')->count())->toBe(0)
        ->and($lain->notifikasi()->whereNull('dibaca_at')->count())->toBe(1);
});
