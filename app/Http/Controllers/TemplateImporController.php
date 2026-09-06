<?php

namespace App\Http\Controllers;

use App\Enums\JenisDaftarPilihan;
use App\Models\DaftarPilihan;
use App\Support\SkemaImpor;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TemplateImporController extends Controller
{
    public function unduh(Request $request, string $entitas): StreamedResponse
    {
        abort_unless(SkemaImpor::ada($entitas), 404);

        $format = strtolower((string) $request->query('format', 'csv'));
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        return $format === 'xlsx'
            ? $this->xlsx($entitas)
            : $this->csv($entitas);
    }

    public function unduhXlsx(string $entitas): StreamedResponse
    {
        abort_unless(SkemaImpor::ada($entitas), 404);

        return $this->xlsx($entitas);
    }

    private function xlsx(string $entitas): StreamedResponse
    {
        $kolom = SkemaImpor::kolom($entitas);
        $opsiDaftarPilihan = $this->opsiDaftarPilihan($entitas);
        $buku = new Spreadsheet;
        $data = $buku->getActiveSheet()->setTitle('Data');
        $petunjuk = $buku->createSheet()->setTitle('Petunjuk');
        $contoh = $buku->createSheet()->setTitle('Contoh');
        $referensi = $buku->createSheet()->setTitle('Referensi');
        $judul = array_column($kolom, 'kolom');
        $kolomTerakhir = Coordinate::stringFromColumnIndex(count($judul));

        $data->fromArray([$judul], null, 'A1');
        $data->freezePane('A2');
        $data->setAutoFilter("A1:{$kolomTerakhir}1");
        $data->getStyle("A1:{$kolomTerakhir}1")->getFont()->setBold(true);

        foreach ($kolom as $indeks => $definisi) {
            $huruf = Coordinate::stringFromColumnIndex($indeks + 1);
            $data->getColumnDimension($huruf)->setWidth(min(40, max(14, mb_strlen($definisi['kolom']) + 2)));

            if (in_array($definisi['kolom'], SkemaImpor::kolomTeks($entitas), true)) {
                $data->getStyle("{$huruf}2:{$huruf}1001")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            }
            if (in_array($definisi['kolom'], SkemaImpor::kolomTanggal($entitas), true)) {
                $data->getStyle("{$huruf}2:{$huruf}1001")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            }

            $opsi = $this->nilaiBaku($definisi, $opsiDaftarPilihan);
            if ($opsi === []) {
                continue;
            }

            $kolomReferensi = Coordinate::stringFromColumnIndex($indeks + 1);
            $referensi->setCellValueExplicit("{$kolomReferensi}1", $definisi['kolom'], DataType::TYPE_STRING);
            foreach ($opsi as $baris => $nilai) {
                $referensi->setCellValueExplicit("{$kolomReferensi}".($baris + 2), $nilai, DataType::TYPE_STRING);
            }

            $namaRentang = 'opsi_'.($indeks + 1);
            $buku->addNamedRange(new NamedRange($namaRentang, $referensi, "\${$kolomReferensi}\$2:\${$kolomReferensi}\$".(count($opsi) + 1)));
            $validasi = (new DataValidation)
                ->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(! $definisi['wajib'])
                ->setShowDropDown(true)
                ->setShowErrorMessage(true)
                ->setErrorTitle('Nilai tidak sah')
                ->setError('Pilih nilai dari daftar.')
                ->setFormula1($namaRentang);
            $data->setDataValidation("{$huruf}2:{$huruf}1001", $validasi);
        }

        $petunjuk->fromArray([
            ['TEMPLATE IMPOR '.mb_strtoupper(SkemaImpor::judul($entitas))],
            ['Isi data hanya pada sheet Data mulai baris 2.'],
            ['Jangan mengubah nama kolom. Urutan kolom boleh diubah.'],
            ['Maksimal 1000 baris data. Formula tidak diizinkan.'],
            ['Sheet Contoh hanya petunjuk dan tidak ikut diimpor.'],
            [],
            ['Kolom', 'Wajib', 'Keterangan'],
        ], null, 'A1');
        foreach ($kolom as $indeks => $definisi) {
            $opsi = $this->nilaiBaku($definisi, $opsiDaftarPilihan);
            $keterangan = $definisi['keterangan'].($opsi === [] ? '' : '. Nilai: '.implode(' | ', $opsi));
            $petunjuk->fromArray([[$definisi['kolom'], $definisi['wajib'] ? 'Ya' : 'Tidak', $keterangan]], null, 'A'.($indeks + 8));
        }
        $petunjuk->getColumnDimension('A')->setWidth(28);
        $petunjuk->getColumnDimension('B')->setWidth(10);
        $petunjuk->getColumnDimension('C')->setWidth(90);
        $petunjuk->getStyle('A1')->getFont()->setBold(true);
        $petunjuk->getStyle('A7:C7')->getFont()->setBold(true);

        $contoh->fromArray([$judul], null, 'A1');
        foreach ($kolom as $indeks => $definisi) {
            $huruf = Coordinate::stringFromColumnIndex($indeks + 1);
            $contoh->setCellValueExplicit("{$huruf}2", $definisi['contoh'], DataType::TYPE_STRING);
            $contoh->getColumnDimension($huruf)->setWidth(min(40, max(14, mb_strlen($definisi['kolom']) + 2)));
        }
        $contoh->getStyle("A1:{$kolomTerakhir}1")->getFont()->setBold(true);
        $referensi->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $buku->setActiveSheetIndexByName('Data');

        return response()->streamDownload(function () use ($buku): void {
            (new Xlsx($buku))->save('php://output');
            $buku->disconnectWorksheets();
        }, 'template-impor-'.$entitas.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function csv(string $entitas): StreamedResponse
    {
        $judul = array_column(SkemaImpor::kolom($entitas), 'kolom');

        return response()->streamDownload(function () use ($judul): void {
            $keluar = fopen('php://output', 'wb');
            fwrite($keluar, "\xEF\xBB\xBF");
            fputcsv($keluar, $judul, ',', '"', '');
            fclose($keluar);
        }, 'template-impor-'.$entitas.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
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
     * @return array<string, list<string>>
     */
    private function opsiDaftarPilihan(string $entitas): array
    {
        $hasil = [];

        foreach (SkemaImpor::kolomDaftarPilihan($entitas) as $kolom => $namaCase) {
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
