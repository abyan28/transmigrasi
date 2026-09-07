<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Berkas;
use App\Support\PenyimpananDokumen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Menyimpan unggahan ke registry `berkas` lalu melekatkannya lewat pivot
 * `*_berkas` milik modulnya (Task 4.1b).
 *
 * Dipakai bersama oleh modul bermulti-berkas: kawasan (HPL/SK/peta),
 * inventaris SP, fasilitas SP, dan infrastruktur SP. Ditulis sekali di sini
 * sebab keempatnya melakukan hal yang sama persis; menyalinnya ke tiap
 * controller berarti empat tempat yang dapat berselisih diam-diam.
 *
 * Berkas fisik selalu masuk cakram PRIVAT lewat `PenyimpananDokumen`, tidak
 * pernah ke `public/` (`rules.md` 14a). Basis data hanya menyimpan path.
 */
trait MenyimpanBerkas
{
    /**
     * @param  array<int, UploadedFile>  $berkasDiunggah
     * @param  string  $relasi  Nama relasi `belongsToMany` pada modelnya
     */
    protected function lekatkanBerkas(
        Model $pemilik,
        array $berkasDiunggah,
        string $modul,
        string $peran,
        string $relasi = 'berkas',
        ?string $labelPemilik = null,
        string $subfolder = '',
        ?int $idFolder = null,
    ): void {
        $urutan = (int) $pemilik->{$relasi}()->wherePivot('peran', $peran)->max('urutan');

        foreach ($berkasDiunggah as $satu) {
            if (! $satu instanceof UploadedFile || ! $satu->isValid()) {
                continue;
            }

            $berkas = $this->rekamBerkas(
                $satu,
                $modul,
                $idFolder ?? (int) $pemilik->getKey(),
                $peran,
                $labelPemilik ?? $this->labelBerkas($pemilik),
                $urutan + 1,
                $subfolder === '' ? $peran : $subfolder,
            );

            $pemilik->{$relasi}()->attach($berkas->id_berkas, [
                'peran' => $peran,
                'urutan' => ++$urutan,
            ]);
        }
    }

    /**
     * Menulis satu baris registry `berkas` beserta berkas fisiknya.
     *
     * `uuid` dibangkitkan di sini sebab model `Berkas` belum punya observer
     * auto-generate; kolomnya UNIQUE dan NOT NULL sehingga wajib terisi.
     */
    protected function rekamBerkas(
        UploadedFile $unggahan,
        string $modul,
        int $idPemilik,
        string $peran,
        string $labelPemilik = '',
        int $urutan = 1,
        string $subfolder = '',
        ?string $uuid = null,
    ): Berkas {
        do {
            $uuid ??= (string) Str::uuid();
            $pendek = substr(str_replace('-', '', $uuid), 0, 8);
            $tabrakan = Berkas::where('uuid', $uuid)->orWhere('nama_file', 'like', '%-'.$pendek.'.%')->exists();
            if ($tabrakan) {
                $uuid = null;
            }
        } while ($tabrakan);

        $path = PenyimpananDokumen::simpan(
            berkas: $unggahan,
            modul: $modul,
            idPemilik: $idPemilik,
            namaDokumen: str_replace('_', ' ', $peran),
            namaPemilik: $labelPemilik,
            urutan: $urutan,
            pengenal: $pendek,
            subfolder: $subfolder,
        );

        return Berkas::create([
            'uuid' => $uuid,
            'nama_file' => basename($path),
            'nama_asli' => $unggahan->getClientOriginalName(),
            'path' => $path,
            'mime' => $unggahan->getClientMimeType(),
            'ekstensi' => Str::lower($unggahan->getClientOriginalExtension()),
            'ukuran' => $unggahan->getSize(),
            'disk' => PenyimpananDokumen::DISK,
            // Boleh NULL: kanal publik mengunggah tanpa akun (Putaran 12
            // keputusan 4), sehingga ketiadaan pengunggah bukan galat.
            'user_id' => Auth::id(),
        ]);
    }

    private function labelBerkas(Model $pemilik): string
    {
        foreach (['nama_kepala_keluarga', 'nomor_pengaduan', 'no_rumah', 'kode_saprotan', 'kode_penanaman', 'kode_sp', 'nama', 'nama_barang', 'nama_fasilitas', 'nama_alat', 'periode_panen', 'judul', 'uuid'] as $kolom) {
            if (filled($pemilik->getAttribute($kolom))) {
                return (string) $pemilik->getAttribute($kolom);
            }
        }

        return '';
    }
}
