<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\DummyData;
use Illuminate\Database\Seeder;

/**
 * Menanam 95 kewenangan (`data-dictionary.md` 13.1) + 4 role bawaan + 1 role
 * contoh non-bawaan (`rules.md` 5.0a/5.1), beserta pasangan `role_permission`.
 *
 * Sumber kebenaran = `DummyData::daftarIzin()` / `izinRole()` / `role()` (sudah
 * direkonsiliasi Task 2.27). Idempoten: `updateOrCreate` + `sync`, aman
 * dijalankan ulang -- id role dipaksa 1-5 agar `izinRole($id)` cocok dan
 * seeder akun (Task 3.5) dapat merujuk id yang tetap.
 *
 * Kewenangan TIDAK dapat ditambah/dihapus Admin (tiap izin wajib punya
 * pemeriksa di kode); role BOLEH disunting kecuali baris Admin yang terkunci.
 */
class PermissionRoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->tanamPermission();
        $this->tanamRole();
    }

    private function tanamPermission(): void
    {
        $urutan = 0;

        foreach (DummyData::daftarIzin() as $kelompok) {
            foreach ($kelompok['modul'] as $modul) {
                foreach ($modul['aksi'] as $aksi) {
                    Permission::updateOrCreate(
                        ['nama' => $modul['kunci'].'.'.$aksi],
                        [
                            'modul' => $modul['kunci'],
                            'aksi' => $aksi,
                            'label' => $modul['nama'].': '.ucfirst($aksi),
                            'urutan' => ++$urutan,
                        ],
                    );
                }
            }
        }
    }

    private function tanamRole(): void
    {
        foreach (DummyData::role() as $baris) {
            // `is_bawaan`/`is_terkunci` sengaja di luar $fillable Role (Admin
            // tak boleh menyuntingnya lewat antarmuka) -> forceFill di seeder.
            $role = Role::withTrashed()->firstOrNew(['id_role' => $baris['id_role']]);
            $role->forceFill([
                'nama' => $baris['nama'],
                'deskripsi' => $baris['deskripsi'],
                'cakupan_data' => $baris['cakupan_data'],
                'is_bawaan' => $baris['is_bawaan'],
                'is_terkunci' => $baris['is_terkunci'],
                'is_aktif' => $baris['is_aktif'],
                'deleted_at' => null,
            ])->save();

            $namaIzin = [];
            foreach (DummyData::izinRole($baris['id_role']) as $modul => $daftarAksi) {
                foreach ($daftarAksi as $aksi) {
                    $namaIzin[] = $modul.'.'.$aksi;
                }
            }

            $role->permissions()->sync(
                Permission::whereIn('nama', $namaIzin)->pluck('id_permission'),
            );
        }
    }
}
