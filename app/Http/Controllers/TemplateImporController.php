<?php

namespace App\Http\Controllers;

use App\Enums\JenisDaftarPilihan;
use App\Models\DaftarPilihan;
use App\Support\SkemaImpor;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Membangkitkan berkas template impor luring (Task 10.6).
 *
 * Satu rute melayani seluruh entitas; yang membedakan hanya susunan kolomnya,
 * dan itu dibaca dari `App\Support\SkemaImpor` -- satu sumber, sehingga
 * template dan (kelak) pembaca unggahannya tidak dapat berbeda diam-diam
 * (`rules.md` 12.13).
 *
 * Formatnya CSV: dibuka Excel maupun aplikasi lembar kerja apa pun, dapat
 * diisi tanpa sambungan, dan diunggah kembali. Baris berawalan `#` adalah
 * petunjuk dan daftar nilai baku -- pembaca impor melewatinya, baris data
 * pertama adalah baris judul kolom.
 */
class TemplateImporController extends Controller
{
    public function unduh(string $entitas): StreamedResponse
    {
        abort_unless(SkemaImpor::ada($entitas), 404);

        $kolom = SkemaImpor::kolom($entitas);
        $opsiDaftarPilihan = $this->opsiDaftarPilihan($entitas);

        $namaBerkas = 'template-impor-'.$entitas.'.csv';

        return response()->streamDownload(function () use ($entitas, $kolom, $opsiDaftarPilihan) {
            $keluar = fopen('php://output', 'wb');

            // BOM UTF-8 supaya Excel membaca aksara Indonesia dengan benar.
            fwrite($keluar, "\xEF\xBB\xBF");

            // `escape: ''` mematikan perilaku escape non-standar PHP (usang sejak
            // 8.4) supaya keluarannya CSV RFC-4180 murni.
            $tulisBaris = static fn (array $sel) => fputcsv($keluar, $sel, ',', '"', '');

            $tulisKomentar = static function (string $teks) use ($tulisBaris): void {
                $tulisBaris(['# '.$teks]);
            };

            $tulisKomentar('TEMPLATE IMPOR '.mb_strtoupper(SkemaImpor::judul($entitas)));
            $tulisKomentar('Isi mulai baris di bawah judul kolom. Jangan mengubah nama atau urutan kolom.');
            $tulisKomentar('Baris contoh boleh dihapus atau ditimpa. Baris berawalan # diabaikan saat impor.');
            $tulisKomentar('Kolom bertanda (wajib) tidak boleh kosong.');
            $tulisKomentar('');

            foreach ($kolom as $k) {
                $tanda = $k['wajib'] ? ' (wajib)' : '';
                $baris = $k['kolom'].$tanda.' -- '.$k['keterangan'];

                $daftarNilai = $this->nilaiBaku($k, $opsiDaftarPilihan);
                if ($daftarNilai !== []) {
                    $baris .= '. Nilai baku: '.implode(' | ', $daftarNilai);
                }

                $tulisKomentar($baris);
            }
            $tulisKomentar('');

            // Baris judul kolom -- inilah yang dibaca pembaca impor.
            $tulisBaris(array_column($kolom, 'kolom'));

            // Dua baris contoh: satu terisi lengkap, satu hanya kolom wajib.
            $tulisBaris(array_column($kolom, 'contoh'));
            $tulisBaris(array_map(
                fn (array $k): string => $k['wajib'] ? $k['contoh'] : '',
                $kolom,
            ));

            fclose($keluar);
        }, $namaBerkas, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Nilai baku satu kolom, dari enum (`SkemaImpor::opsiEnum`) atau daftar
     * pilihan (sudah dimuat di `$opsiDaftarPilihan`).
     *
     * @param  array{kolom: string, opsi: list<string>|null}  $kolom
     * @param  array<string, list<string>>  $opsiDaftarPilihan
     * @return list<string>
     */
    private function nilaiBaku(array $kolom, array $opsiDaftarPilihan): array
    {
        $opsi = $kolom['opsi'];

        if ($opsi === null) {
            return [];
        }

        if ($opsi === ['dp']) {
            return $opsiDaftarPilihan[$kolom['kolom']] ?? [];
        }

        if (count($opsi) === 1 && str_starts_with($opsi[0], 'enum:')) {
            return SkemaImpor::opsiEnum(substr($opsi[0], 5));
        }

        return $opsi;
    }

    /**
     * Nilai daftar pilihan aktif untuk tiap kolom bertanda `dp` pada entitas ini.
     *
     * @return array<string, list<string>>
     */
    private function opsiDaftarPilihan(string $entitas): array
    {
        $hasil = [];

        foreach (SkemaImpor::kolomDaftarPilihan($entitas) as $kolom => $namaCase) {
            /** @var JenisDaftarPilihan $jenis */
            $jenis = constant(JenisDaftarPilihan::class.'::'.$namaCase);

            $hasil[$kolom] = DaftarPilihan::query()
                ->where('jenis', $jenis->value)
                ->where('is_aktif', true)
                ->orderBy('urutan')
                ->pluck('nilai')
                ->all();
        }

        return $hasil;
    }
}
