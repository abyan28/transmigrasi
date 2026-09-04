<?php

namespace Database\Seeders;

use App\Models\Berkas;
use App\Models\PenangananPengaduan;
use App\Models\Pengaduan;
use App\Models\Role;
use App\Models\SatuanPermukiman;
use App\Models\User;
use App\Support\DummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Pengaduan + riwayat penanganan + pivot berkas (Task 8.2).
 *
 * `id_pengaduan` / `nomor_pengaduan` dipaksa sama seperti data contoh (uji
 * HalamanTest menyebut id 1-9 langsung). Petugas penangan (NARA WIJAYA dsb.)
 * dibuat minimal di sini -- `penanganan_pengaduan.user_id` NOT NULL, sedangkan
 * suite Feature tidak menanam akun. `pengaduan.user_id` dibiarkan NULL: data
 * contoh tidak membedakan pencatatnya, dan kolomnya memang nullable.
 *
 * FK SP -> `SpSeeder`; berkas -> `BerkasSeeder` (pivot ditanam di sini sebab
 * BerkasSeeder berjalan lebih dulu).
 */
class PengaduanSeeder extends Seeder
{
    /**
     * Berkas tindak lanjut per (nomor pengaduan, indeks baris penanganan).
     * Diambil dari pemanggilan `self::cariBerkas(N)` di
     * `DummyData::penangananPengaduan()`; dipetakan eksplisit sebab id global
     * baris penanganan tidak stabil terhadap urutan.
     *
     * @var array<string, array<int, int>>
     */
    private const BERKAS_TINDAK_LANJUT = [
        'PGD-2026-0001-PMTUXK' => [1 => 18],
        'PGD-2026-0005-96RY4X' => [1 => 19, 2 => 20],
    ];

    public function run(): void
    {
        $roleId = Role::query()->value('id_role')
            ?? Role::create([
                'nama' => 'Petugas',
                'deskripsi' => 'Peran minimal untuk penautan riwayat penanganan (data contoh).',
                'cakupan_data' => 'Semua',
                'is_bawaan' => false,
                'is_terkunci' => false,
                'is_aktif' => true,
            ])->id_role;

        $petugasId = [];
        foreach (DummyData::pengguna() as $u) {
            $petugasId[$u['nama']] = User::firstOrCreate(
                ['nama' => $u['nama']],
                [
                    'role_id' => $roleId,
                    'username' => $u['username'],
                    'email' => $u['email'],
                    'password' => Hash::make('password'),
                    'is_aktif' => $u['is_aktif'] ?? true,
                ],
            )->id_user;
        }

        $spId = SatuanPermukiman::pluck('id_satuan_permukiman', 'nama');

        foreach (DummyData::pengaduan() as $p) {
            $pengaduan = Pengaduan::withTrashed()->firstOrNew(['id_pengaduan' => $p['id_pengaduan']]);
            $pengaduan->fill([
                'nama_pelapor' => $p['nama_pelapor'],
                'kontak_pelapor' => $p['kontak_pelapor'],
                'email_pelapor' => $p['email_pelapor'] ?? null,
                'sumber_laporan' => $p['sumber_laporan'],
                'ip_pelapor' => $p['sumber_laporan'] === 'Publik' ? '10.14.2.'.random_int(20, 240) : null,
                'satuan_permukiman_id' => $p['satuan_permukiman_id'] ?? $spId[$p['satuan_permukiman']] ?? null,
                'nomor_pengaduan' => $p['nomor_pengaduan'],
                'tanggal_pengaduan' => $p['tanggal_pengaduan'],
                'kategori' => $p['kategori'],
                'bidang' => $p['bidang'] ?? null,
                'judul' => $p['judul'],
                'deskripsi' => $p['deskripsi'],
                'status' => $p['status'],
                'prioritas' => $p['prioritas'],
                'lintang' => $p['lintang'] ?? null,
                'bujur' => $p['bujur'] ?? null,
            ]);
            $pengaduan->uuid ??= (string) Str::uuid();
            $pengaduan->save();
        }

        // Bukti dari pelapor: pivot ber-kunci id_pengaduan yang stabil.
        $buktiPivot = array_values(array_filter(
            DummyData::berkasPemilik()['pengaduan_berkas'] ?? [],
            fn ($b) => Berkas::whereKey($b['berkas_id'])->exists()
                && Pengaduan::withTrashed()->whereKey($b['pengaduan_id'])->exists(),
        ));

        DB::table('pengaduan_berkas')->delete();
        if ($buktiPivot !== []) {
            DB::table('pengaduan_berkas')->insert($buktiPivot);
        }

        // Riwayat penanganan, dibuat berurutan per nomor.
        PenangananPengaduan::query()->delete();
        DB::table('penanganan_pengaduan_berkas')->delete();

        foreach (Pengaduan::withTrashed()->orderBy('id_pengaduan')->get() as $pengaduan) {
            $jejak = DummyData::penangananPengaduan($pengaduan->nomor_pengaduan);
            $berkasNomor = self::BERKAS_TINDAK_LANJUT[$pengaduan->nomor_pengaduan] ?? [];

            foreach ($jejak as $i => $langkah) {
                $baris = $pengaduan->penanganan()->create([
                    'user_id' => $petugasId[$langkah['petugas']] ?? array_values($petugasId)[0],
                    'status_sebelum' => $langkah['status_sebelum'],
                    'status_sesudah' => $langkah['status_sesudah'],
                    'tanggal_penanganan' => $langkah['tanggal_penanganan'],
                    'catatan' => $langkah['catatan'],
                ]);

                $berkasId = $berkasNomor[$i] ?? null;
                if ($berkasId !== null && Berkas::whereKey($berkasId)->exists()) {
                    $baris->berkas()->attach($berkasId, ['peran' => 'tindak_lanjut', 'urutan' => 0]);
                }
            }
        }
    }
}
