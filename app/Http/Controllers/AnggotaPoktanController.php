<?php

namespace App\Http\Controllers;

use App\Enums\AsalWakilPoktan;
use App\Enums\JenisDaftarPilihan;
use App\Enums\StatusKeaktifanAnggota;
use App\Models\AnggotaPoktan;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Keanggotaan kelompok tani (Task 6.5).
 *
 * Anggota yang berhenti DITANDAI `Sudah Keluar`, TIDAK PERNAH dihapus
 * (`rules.md` 5.1 catatan 7) -- tak ada aksi hapus. `anggota-poktan.perbarui`
 * adalah satu-satunya jalur mengubah status keaktifan + tanggal keluar.
 *
 * Keanggotaan melekat pada KELUARGA (`transmigran_id`), `asal_wakil` menyatakan
 * siapa wakilnya (hanya 2 nilai -- Bukan Transmigran tak berlaku). Satu keluarga
 * hanya boleh `Aktif` di SATU poktan (`rules.md` 6.4).
 */
class AnggotaPoktanController extends Controller
{
    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $this->pastikanTakAktifDiPoktanLain($request, (int) $data['transmigran_id'], (int) $data['poktan_id']);

        AnggotaPoktan::create($this->bersihkan($data));

        return back()->with('sukses', 'Data anggota kelompok tani tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $anggota = AnggotaPoktan::findOrFail($id);

        $data = $this->validasi($request, $anggota);

        $this->pastikanTakAktifDiPoktanLain($request, (int) $data['transmigran_id'], $anggota->poktan_id, $anggota->id_anggota_poktan);

        $anggota->update($this->bersihkan($data));

        return back()->with('sukses', 'Perubahan data anggota tersimpan.');
    }

    /**
     * Satu keluarga hanya boleh berstatus Aktif di SATU poktan (`rules.md` 6.4).
     * `UNIQUE (poktan_id, transmigran_id)` tak menangkapnya -- poktannya beda.
     */
    private function pastikanTakAktifDiPoktanLain(Request $request, int $transmigranId, int $poktanId, ?int $abaikanId = null): void
    {
        if ($request->input('status') !== StatusKeaktifanAnggota::Aktif->value) {
            return;
        }

        $bentrok = AnggotaPoktan::query()
            ->where('transmigran_id', $transmigranId)
            ->where('poktan_id', '!=', $poktanId)
            ->where('status', StatusKeaktifanAnggota::Aktif->value)
            ->when($abaikanId !== null, fn ($q) => $q->where('id_anggota_poktan', '!=', $abaikanId))
            ->exists();

        if ($bentrok) {
            throw ValidationException::withMessages([
                'transmigran_id' => 'Keluarga ini masih berstatus Aktif pada kelompok tani lain. Tandai keluar dari sana lebih dulu.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function bersihkan(array $data): array
    {
        if (($data['asal_wakil'] ?? null) !== AsalWakilPoktan::AnggotaKeluarga->value) {
            $data['anggota_keluarga_id'] = null;
        }

        if (($data['status'] ?? null) !== StatusKeaktifanAnggota::SudahKeluar->value) {
            $data['tanggal_keluar'] = null;
            $data['alasan_keluar'] = null;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?AnggotaPoktan $anggota = null): array
    {
        $poktanId = $anggota?->poktan_id ?? $request->input('poktan_id');

        return $request->validate([
            'poktan_id' => ['required', 'integer', Rule::exists('poktan', 'id_poktan')],
            'transmigran_id' => [
                'required', 'integer', Rule::exists('transmigran', 'id_transmigran'),
                // Satu keluarga hanya satu baris per poktan (schema UNIQUE).
                Rule::unique('anggota_poktan', 'transmigran_id')
                    ->where('poktan_id', $poktanId)
                    ->ignore($anggota?->id_anggota_poktan, 'id_anggota_poktan'),
            ],
            'asal_wakil' => ['required', Rule::in(AsalWakilPoktan::nilaiAnggota())],
            'anggota_keluarga_id' => [
                'nullable', 'integer', Rule::exists('anggota_keluarga', 'id_anggota_keluarga'),
                Rule::requiredIf(fn () => $request->input('asal_wakil') === AsalWakilPoktan::AnggotaKeluarga->value),
            ],
            'telepon_wakil' => ['nullable', 'string', 'max:20'],
            'jabatan' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JabatanAnggotaPoktan, wajib: true),
            'tanggal_masuk' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::enum(StatusKeaktifanAnggota::class)],
            'tanggal_keluar' => [
                'nullable', 'date', 'before_or_equal:today', 'after_or_equal:tanggal_masuk',
                Rule::requiredIf(fn () => $request->input('status') === StatusKeaktifanAnggota::SudahKeluar->value),
            ],
            'alasan_keluar' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'transmigran_id.required' => 'Keluarga transmigran wajib dipilih.',
            'transmigran_id.unique' => 'Keluarga ini sudah terdaftar pada kelompok tani ini.',
            'asal_wakil.required' => 'Pilih siapa yang mewakili keluarga.',
            'anggota_keluarga_id.required' => 'Anggota keluarga yang mewakili wajib dipilih.',
            'tanggal_masuk.required' => 'Tanggal masuk wajib diisi.',
            'tanggal_keluar.required' => 'Tanggal keluar wajib diisi bila status Sudah Keluar.',
            'jabatan.required' => 'Jabatan wajib dipilih.',
        ] + ValidationRules::pesan());
    }
}
