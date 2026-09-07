<?php

use App\Support\ImporEngine;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = (string) config('database.connections.sqlite.database');
if (config('database.default') !== 'sqlite' || basename($database) !== 'demo-verifikasi.sqlite') {
    throw new RuntimeException('Verifikasi hanya boleh dijalankan pada database SQLite demo-verifikasi.sqlite.');
}

$urutan = [
    'satuan', 'wilayah', 'komoditas', 'transmigran', 'rumah', 'lahan', 'poktan',
    'saprotan', 'penanaman', 'hasil-panen', 'infrastruktur', 'inventaris-sp',
    'fasilitas-sp', 'alsintan',
];

foreach ($urutan as $nomor => $entitas) {
    $path = dirname(__DIR__).DIRECTORY_SEPARATOR.sprintf('%02d-%s-demo.xlsx', $nomor + 1, $entitas);
    $hasil = ImporEngine::proses($entitas, $path, 'xlsx');
    printf(
        "%02d %-16s diproses=%d dibuat=%d dilewati=%d gagal=%d\n",
        $nomor + 1,
        $entitas,
        $hasil['diproses'],
        $hasil['dibuat'],
        $hasil['dilewati'],
        $hasil['jumlah_gagal'],
    );
    if ($hasil['jumlah_gagal'] > 0) {
        foreach ($hasil['gagal'] as $galat) {
            printf("   baris %s: %s\n", $galat['baris'], $galat['pesan']);
        }
        exit(1);
    }
}
