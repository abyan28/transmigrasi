<?php

use App\Support\SkemaImpor;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require dirname(__DIR__, 4).'/vendor/autoload.php';

$tujuan = dirname(__DIR__);
if (! is_dir($tujuan)) {
    mkdir($tujuan, 0777, true);
}

$nik = static fn (int $n): string => '5371999900'.str_pad((string) $n, 6, '0', STR_PAD_LEFT);
$kk = static fn (int $n): string => '5371888800'.str_pad((string) $n, 6, '0', STR_PAD_LEFT);
$satuanDemo = ['Kilogram Demo 01', 'Kilogram Demo 02', 'Kilogram Demo 03', 'Kilogram Demo 04', 'Kilogram Demo 05'];

$data = [
    'satuan' => [
        ['nama' => $satuanDemo[0], 'simbol' => 'kgd01', 'faktor_ke_ton' => 0.001],
        ['nama' => $satuanDemo[1], 'simbol' => 'kgd02', 'faktor_ke_ton' => 0.001],
        ['nama' => $satuanDemo[2], 'simbol' => 'kgd03', 'faktor_ke_ton' => 0.001],
        ['nama' => $satuanDemo[3], 'simbol' => 'kgd04', 'faktor_ke_ton' => 0.001],
        ['nama' => $satuanDemo[4], 'simbol' => 'kgd05', 'faktor_ke_ton' => 0.001],
    ],
    'wilayah' => [
        ['tingkat' => 'provinsi', 'nama' => 'PROVINSI DEMO IMPOR', 'induk' => '', 'kode' => '99'],
        ['tingkat' => 'kabupaten', 'nama' => 'KABUPATEN DEMO IMPOR', 'induk' => 'PROVINSI DEMO IMPOR', 'kode' => '9901'],
        ['tingkat' => 'kecamatan', 'nama' => 'KECAMATAN DEMO IMPOR', 'induk' => 'KABUPATEN DEMO IMPOR', 'kode' => '990101'],
        ['tingkat' => 'desa', 'nama' => 'DESA DEMO IMPOR 01', 'induk' => 'KECAMATAN DEMO IMPOR', 'kode' => '9901010001'],
        ['tingkat' => 'desa', 'nama' => 'DESA DEMO IMPOR 02', 'induk' => 'KECAMATAN DEMO IMPOR', 'kode' => '9901010002'],
    ],
    'komoditas' => [
        ['nama_komoditas' => 'SORGHUM DEMO', 'jenis' => 'Pangan', 'satuan_baku' => $satuanDemo[0], 'unggulan' => 'ya', 'deskripsi' => 'Komoditas demo tahan kering.'],
        ['nama_komoditas' => 'KACANG HIJAU DEMO', 'jenis' => 'Palawija', 'satuan_baku' => $satuanDemo[1], 'unggulan' => 'tidak', 'deskripsi' => 'Komoditas demo rotasi lahan.'],
        ['nama_komoditas' => 'TOMAT DEMO', 'jenis' => 'Hortikultura', 'satuan_baku' => $satuanDemo[2], 'unggulan' => 'tidak', 'deskripsi' => 'Komoditas demo hortikultura.'],
        ['nama_komoditas' => 'BAWANG MERAH DEMO', 'jenis' => 'Hortikultura', 'satuan_baku' => $satuanDemo[3], 'unggulan' => 'tidak', 'deskripsi' => 'Komoditas demo kebun pekarangan.'],
        ['nama_komoditas' => 'KEDELAI DEMO', 'jenis' => 'Palawija', 'satuan_baku' => $satuanDemo[4], 'unggulan' => 'tidak', 'deskripsi' => 'Komoditas demo lahan kering.'],
    ],
    'transmigran' => [],
    'rumah' => [],
    'lahan' => [],
    'poktan' => [],
    'saprotan' => [],
    'penanaman' => [],
    'hasil-panen' => [],
    'infrastruktur' => [],
    'inventaris-sp' => [],
    'fasilitas-sp' => [],
    'alsintan' => [],
];

$sp = ['SP Kapitan Meo', 'SP Tniumanu', 'SP Harekakae', 'SP Tualaran', 'SP Weain'];
$nama = ['MARTINUS BERE DEMO', 'YULIANA NAHAK DEMO', 'DOMINGGUS SERAN DEMO', 'MARIA HOAR DEMO', 'PETRUS BRIA DEMO'];
$komoditas = ['SORGHUM DEMO', 'KACANG HIJAU DEMO', 'TOMAT DEMO', 'BAWANG MERAH DEMO', 'KEDELAI DEMO'];
$slugPoktan = ['poktan-demo-impor-01', 'poktan-demo-impor-02', 'poktan-demo-impor-03', 'poktan-demo-impor-04', 'poktan-demo-impor-05'];

for ($i = 1; $i <= 5; $i++) {
    $indeks = $i - 1;
    $data['transmigran'][] = [
        'nik' => $nik($i), 'nama_lengkap' => $nama[$indeks], 'no_kk' => $kk($i),
        'satuan_permukiman' => $sp[$indeks], 'jenis_kelamin' => $i === 2 || $i === 4 ? 'Perempuan' : 'Laki-laki',
        'agama' => 'Katolik', 'tempat_lahir' => 'MALAKA', 'tanggal_lahir' => sprintf('198%d-0%d-15', $i, min($i, 9)),
        'pendidikan_terakhir' => 'SMA/SMK', 'pekerjaan' => 'PETANI',
        'pendapatan_per_bulan' => 2000000 + ($i * 150000), 'daerah_asal_kabupaten' => 'Kabupaten Malaka',
        'tahun_kedatangan' => 2018 + $i, 'status_tinggal' => 'Aktif', 'tahun_keluar' => '',
        'telepon' => '0813999900'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
        'keterangan' => 'Data demo impor berantai '.$i,
    ];
    $data['rumah'][] = [
        'no_rumah' => 'DEMO-IMP-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'satuan_permukiman' => $sp[$indeks],
        'nik_penghuni' => $nik($i), 'tahun_mulai_menghuni' => 2020 + $i, 'kondisi' => $i === 5 ? 'Rusak Ringan' : 'Tidak Rusak',
        'status_hunian' => 'Dihuni', 'alasan_tidak_dihuni' => '', 'tahun_pembangunan' => 2019 + $i,
        'luas_bangunan' => 36 + ($i * 2), 'lintang' => -9.51 - ($i / 1000), 'bujur' => 124.91 + ($i / 1000),
        'catatan_hunian' => 'Rumah demo impor '.$i,
    ];
    $data['lahan'][] = [
        'kode_lahan' => 'LH-DEMO-IMP-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'nik_pemilik' => $nik($i),
        'satuan_permukiman' => $sp[$indeks], 'luas_pekarangan' => 0.2 + ($i / 100),
        'lintang_pekarangan' => -9.52 - ($i / 1000), 'bujur_pekarangan' => 124.92 + ($i / 1000),
        'luas_kering' => 1 + ($i / 10), 'luas_basah' => $i === 3 ? 0.25 : '',
        'lintang_usaha' => -9.53 - ($i / 1000), 'bujur_usaha' => 124.93 + ($i / 1000),
        'tujuan_pemanfaatan' => $komoditas[$indeks], 'status_sertifikat' => $i <= 3 ? 'Sudah' : 'Belum',
        'keterangan' => 'Lahan demo impor '.$i,
    ];
    $data['poktan'][] = [
        'nama_poktan' => 'POKTAN DEMO IMPOR '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
        'satuan_permukiman' => $sp[$indeks], 'tahun_berdiri' => 2020, 'asal_ketua' => 'Kepala Keluarga',
        'nik_ketua' => $nik($i), 'nama_ketua' => '', 'telepon_ketua' => '0813999900'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
        'email_ketua' => '', 'alamat_ketua' => $sp[$indeks], 'luas_kering_ketua' => '', 'luas_basah_ketua' => '',
        'keterangan' => 'Poktan demo impor '.$i, 'nik_anggota' => $nik($i), 'asal_wakil' => 'Kepala Keluarga',
        'nik_wakil' => '', 'jabatan_anggota' => 'Anggota', 'tanggal_masuk' => '2025-01-10',
        'status_anggota' => 'Aktif', 'tanggal_keluar' => '', 'alasan_keluar' => '',
        'keterangan_anggota' => 'Anggota demo impor '.$i,
    ];
    $data['saprotan'][] = [
        'kode_saprotan' => 'SAP-DEMO-IMP-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'jenis_saprotan' => 'Benih',
        'nama' => 'BENIH '.$komoditas[$indeks], 'jumlah_total' => 100 + ($i * 10), 'satuan' => $satuanDemo[$indeks],
        'tahun_pengadaan' => 2026, 'komoditas' => $komoditas[$indeks], 'varietas' => 'VARIETAS DEMO '.$i,
        'jadwal_tanam' => '2026-01', 'sumber_dana' => 'APBN', 'keterangan' => 'Saprotan demo impor '.$i,
        'poktan_slug' => $slugPoktan[$indeks], 'jumlah_distribusi' => 80 + ($i * 5), 'tanggal_serah' => '2026-01-10',
    ];
    $data['penanaman'][] = [
        'kode_penanaman' => 'TAN-DEMO-IMP-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
        'poktan_slug' => $slugPoktan[$indeks], 'kode_saprotan' => 'SAP-DEMO-IMP-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
        'periode_tanam' => '2026-02', 'volume_benih' => 20 + $i, 'realisasi_tanam_ha' => 1 + ($i / 10),
        'keterangan' => 'Penanaman demo impor '.$i,
    ];
    $data['hasil-panen'][] = [
        'kode_penanaman' => 'TAN-DEMO-IMP-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
        'periode_panen' => '2026-06', 'realisasi_panen_ha' => 1 + ($i / 10), 'puso_ha' => 0,
        'produktivitas' => 4.5 + ($i / 10), 'harga_jual' => 5500 + ($i * 100),
        'keterangan' => 'Hasil panen demo impor '.$i,
    ];
    $data['infrastruktur'][] = [
        'satuan_permukiman' => $sp[$indeks], 'nama_aset' => 'ASET DEMO IMPOR '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
        'jenis' => ['Air', 'Sanitasi', 'Irigasi', 'Jalan Produksi', 'Telekomunikasi'][$indeks],
        'kondisi' => $i === 5 ? 'Rusak Ringan' : 'Baik', 'tahun_perolehan' => 2024,
        'sumber_dana' => 'APBN', 'kapasitas' => (10 + $i).' unit layanan',
        'lintang' => -9.54 - ($i / 1000), 'bujur' => 124.94 + ($i / 1000), 'keterangan' => 'Infrastruktur demo impor '.$i,
    ];
    $data['inventaris-sp'][] = [
        'satuan_permukiman' => $sp[$indeks], 'nama_barang' => 'INVENTARIS DEMO IMPOR '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
        'jumlah' => 2 + $i, 'satuan' => 'unit', 'status_penyerahan' => 'Sudah Diserahkan',
        'kondisi' => $i === 5 ? 'Rusak Ringan' : 'Baik',
        'jenis_inventaris' => ['Peralatan Kantor', 'Elektronik & Mesin', 'Perabotan', 'Kendaraan Operasional', 'Peralatan Lainnya'][$indeks],
        'tahun_perolehan' => 2024, 'sumber_dana' => 'APBN', 'keterangan' => 'Inventaris demo impor '.$i,
    ];
    $data['fasilitas-sp'][] = [
        'satuan_permukiman' => $sp[$indeks],
        'jenis_fasilitas' => ['Kesehatan', 'Pendidikan Dasar', 'Ibadah', 'Balai Pertemuan', 'Olahraga'][$indeks],
        'nama_fasilitas' => 'FASILITAS DEMO IMPOR '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
        'jumlah' => 1, 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => $i === 5 ? 'Rusak Ringan' : 'Baik',
        'tahun_perolehan' => 2024, 'sumber_dana' => 'APBN', 'lintang' => -9.55 - ($i / 1000),
        'bujur' => 124.95 + ($i / 1000), 'keterangan' => 'Fasilitas demo impor '.$i,
    ];
    $data['alsintan'][] = [
        'jenis_alsintan' => ['Traktor Roda Dua', 'Pompa Air', 'Hand Sprayer', 'Mesin Perontok', 'Cultivator'][$indeks],
        'nama_alat' => 'ALSINTAN DEMO IMPOR '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
        'jumlah_total' => 1 + $i, 'tahun_pengadaan' => 2026, 'sumber_dana' => 'APBN',
        'keterangan' => 'Alsintan demo impor '.$i,
    ];
}

$urutan = [
    'satuan', 'wilayah', 'komoditas', 'transmigran', 'rumah', 'lahan', 'poktan',
    'saprotan', 'penanaman', 'hasil-panen', 'infrastruktur', 'inventaris-sp',
    'fasilitas-sp', 'alsintan',
];

foreach ($urutan as $nomor => $entitas) {
    $definisi = SkemaImpor::kolom($entitas);
    $judul = array_column($definisi, 'kolom');
    $buku = new Spreadsheet;
    $lembar = $buku->getActiveSheet()->setTitle('Data');
    $petunjuk = $buku->createSheet()->setTitle('Petunjuk');
    $contoh = $buku->createSheet()->setTitle('Contoh');
    $akhir = Coordinate::stringFromColumnIndex(count($judul));

    $lembar->fromArray([$judul], null, 'A1');
    $lembar->freezePane('A2');
    $lembar->setAutoFilter("A1:{$akhir}1");
    $lembar->getStyle("A1:{$akhir}1")->getFont()->setBold(true);
    $lembar->getStyle("A1:{$akhir}1")->getFill()->setFillType('solid')->getStartColor()->setARGB('FFD9EAD3');

    foreach ($data[$entitas] as $indeksBaris => $baris) {
        foreach ($judul as $indeksKolom => $kolom) {
            $huruf = Coordinate::stringFromColumnIndex($indeksKolom + 1);
            $nilai = $baris[$kolom] ?? '';
            $teks = in_array($kolom, SkemaImpor::kolomTeks($entitas), true);
            if ($teks || $nilai === '') {
                $lembar->setCellValueExplicit($huruf.($indeksBaris + 2), (string) $nilai, DataType::TYPE_STRING);
            } else {
                $lembar->setCellValue($huruf.($indeksBaris + 2), $nilai);
            }
        }
    }

    foreach ($definisi as $indeksKolom => $kolom) {
        $huruf = Coordinate::stringFromColumnIndex($indeksKolom + 1);
        $lembar->getColumnDimension($huruf)->setWidth(min(34, max(14, mb_strlen($kolom['kolom']) + 2)));
        if (in_array($kolom['kolom'], SkemaImpor::kolomTeks($entitas), true)) {
            $barisTerakhir = count($data[$entitas]) + 1;
            $lembar->getStyle("{$huruf}2:{$huruf}{$barisTerakhir}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        }
        if (in_array($kolom['kolom'], SkemaImpor::kolomTanggal($entitas), true)) {
            $barisTerakhir = count($data[$entitas]) + 1;
            $lembar->getStyle("{$huruf}2:{$huruf}{$barisTerakhir}")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        }
    }

    $info = SkemaImpor::petunjuk($entitas);
    $petunjuk->fromArray([
        ['DATA DEMO IMPOR '.mb_strtoupper(SkemaImpor::judul($entitas))],
        ['Urutan demo '.($nomor + 1).' dari '.count($urutan).'.'],
        [$info['prasyarat']],
        ['Impor file sesuai nomor urut nama berkas.'],
        ['Sheet Data berisi '.count($data[$entitas]).' baris yang siap diimpor.'],
        ['Data memakai penanda DEMO IMPOR agar mudah dikenali.'],
    ], null, 'A1');
    $petunjuk->getColumnDimension('A')->setWidth(100);
    $petunjuk->getStyle('A1')->getFont()->setBold(true);

    $contoh->fromArray([$judul], null, 'A1');
    foreach ($definisi as $indeksKolom => $kolom) {
        $contoh->setCellValueExplicit([($indeksKolom + 1), 2], $kolom['contoh'], DataType::TYPE_STRING);
    }

    $namaFile = sprintf('%02d-%s-demo.xlsx', $nomor + 1, $entitas);
    (new Xlsx($buku))->save($tujuan.DIRECTORY_SEPARATOR.$namaFile);
    $buku->disconnectWorksheets();
}

file_put_contents($tujuan.DIRECTORY_SEPARATOR.'README.md', "# Data Excel Demo Impor\n\nImpor berkas sesuai nomor urut 01 sampai 14. Semua workbook memiliki sheet `Data` berisi lima baris, kecuali relasi kelompok tetap direpresentasikan satu baris per kelompok.\n\nPrasyarat: enam SP aplikasi dan master daftar pilihan bawaan sudah tersedia. Data saling terhubung melalui NIK, slug Poktan, kode Saprotan, dan kode Penanaman.\n\nJangan mengimpor ulang file yang sudah berhasil bila isi database telah diedit, karena importer akan menolak identitas yang sama dengan payload berbeda.\n");

printf("Membuat %d workbook di %s\n", count($urutan), $tujuan);
