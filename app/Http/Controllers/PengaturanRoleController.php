<?php

namespace App\Http\Controllers;

use App\Enums\AksiAuditLog;
use App\Enums\CakupanData;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Paginasi;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Pengelolaan role dan susunan kewenangannya (Task 3.3 / 3.3b backend).
 *
 * Kewenangan (`permission`) TIDAK dapat ditambah/dihapus di sini -- hanya
 * dipasangkan ke role. Role Admin terkunci; role bawaan tak dapat dihapus;
 * role yang masih dipakai akun tak dapat dihapus (`rules.md` 5.0c).
 *
 * `simpan`/`perbarui`/`hapus` menulis ke tabel `role`/`role_permission` nyata.
 * `index()` membaca Eloquent langsung juga (Fase 1, 2026-09-05) -- sebelumnya
 * masih membaca `DummyData` walau tulisannya sudah nyata sejak Task 3.3,
 * sehingga role baru tak pernah muncul pada daftarnya sendiri. Celah yang
 * sama dengan `PengaturanPenggunaController`.
 */
class PengaturanRoleController extends Controller
{
    public function index(Request $request): View
    {
        $perHalaman = Paginasi::perHalaman($request);

        $role = Role::withCount(['permissions', 'users'])
            ->orderBy('id_role')
            ->paginate($perHalaman)
            ->withQueryString();

        $role->through(fn (Role $r) => [
            'id_role' => $r->id_role,
            'nama' => $r->nama,
            'deskripsi' => $r->deskripsi,
            'cakupan_data' => $r->cakupan_data->value,
            'is_bawaan' => $r->is_bawaan,
            'is_terkunci' => $r->is_terkunci,
            'is_aktif' => $r->is_aktif,
            'jumlah_izin' => $r->permissions_count,
            'jumlah_pengguna' => $r->users_count,
        ]);

        return view('pages.pengguna.role', [
            'title' => 'Role dan Hak Akses',
            'role' => $role,
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $role = new Role;
        $role->forceFill([
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'cakupan_data' => $data['cakupan_data'],
            'is_bawaan' => false,
            'is_terkunci' => false,
            'is_aktif' => true,
        ])->save();

        $role->permissions()->sync($this->idIzin($data['izin'] ?? []));
        $this->catat($request, $role, AksiAuditLog::UbahIzinRole, 'Role baru dibuat');

        return redirect()->route('pengaturan.role')->with('sukses', 'Role baru tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        // Admin: kewenangannya tidak dapat diubah (`rules.md` 5.0a/5.0c poin 8).
        abort_if($role->is_terkunci, 403, 'Role ini terkunci dan tidak dapat disunting.');

        $data = $this->validasi($request, $role);

        $role->forceFill([
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'cakupan_data' => $data['cakupan_data'],
        ])->save();

        $role->permissions()->sync($this->idIzin($data['izin'] ?? []));
        $this->catat($request, $role, AksiAuditLog::UbahIzinRole, 'Susunan kewenangan diperbarui');

        return redirect()->route('pengaturan.role')->with('sukses', 'Susunan izin role tersimpan.');
    }

    public function hapus(Request $request, int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        abort_if($role->is_bawaan, 403, 'Role bawaan sistem tidak dapat dihapus.');
        abort_if($role->users()->exists(), 422, 'Role masih dipakai minimal satu akun.');

        $alasan = $request->validate(
            ['alasan' => ['required', 'string', 'max:500']],
            ['alasan.required' => 'Alasan penghapusan wajib diisi.'],
        )['alasan'];

        $this->catat($request, $role, AksiAuditLog::Hapus, $alasan);
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('pengaturan.role')
            ->with('sukses', 'Role dihapus. Susunan kewenangan akun lain tidak terpengaruh.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Role $role = null): array
    {
        $data = $request->validate([
            'nama' => [
                'required', 'string', 'min:3', 'max:50',
                Rule::unique('role', 'nama')->ignore($role?->id_role, 'id_role'),
            ],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'cakupan_data' => ['required', Rule::enum(CakupanData::class)],
            'izin' => ['nullable', 'array'],
            'izin.*' => ['array'],
            'izin.*.*' => ['string', Rule::in(['lihat', 'tambah', 'ubah', 'hapus'])],
        ], [
            'nama.unique' => 'Nama role ini sudah dipakai.',
            'cakupan_data.enum' => 'Cakupan data tidak sah.',
        ] + ValidationRules::pesan());

        // `lihat` prasyarat seluruh aksi lain pada modul yang sama
        // (`data-dictionary.md` 13.3 poin 4 / `rules.md` 5.0c poin 10).
        foreach ($data['izin'] ?? [] as $modul => $aksiList) {
            $lain = array_diff((array) $aksiList, ['lihat']);

            if ($lain !== [] && ! in_array('lihat', (array) $aksiList, true)) {
                throw ValidationException::withMessages([
                    'izin' => "Modul '{$modul}': kewenangan lain menuntut 'lihat' lebih dulu.",
                ]);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, array<int, string>>  $izin
     * @return Collection<int, int>
     */
    private function idIzin(array $izin): Collection
    {
        $nama = [];

        foreach ($izin as $modul => $aksiList) {
            foreach ((array) $aksiList as $aksi) {
                $nama[] = $modul.'.'.$aksi;
            }
        }

        return Permission::whereIn('nama', $nama)->pluck('id_permission');
    }

    private function catat(Request $request, Role $role, AksiAuditLog $aksi, string $catatan): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id_user,
            'aksi' => $aksi,
            'nama_tabel' => 'role',
            'record_id' => $role->id_role,
            'data_baru' => ['catatan' => $catatan, 'jumlah_izin' => $role->permissions()->count()],
            'ip_address' => $request->ip(),
        ]);
    }
}
