<?php

use App\Models\Pengaduan;
use Illuminate\Support\Facades\Artisan;

it('menghasilkan URL statis yang seluruhnya merespons HTTP 200', function () {
    auth()->logout();
    $pengaduan = Pengaduan::query()->firstOrFail();
    $nomorLama = $pengaduan->nomor_pengaduan;
    $pengaduan->update(['nomor_pengaduan' => 'PGD-DATABASE-ONLY']);

    Artisan::call('sim:tautan-statis');
    $urls = array_filter(explode(PHP_EOL, trim(Artisan::output())), fn ($u) => trim($u) !== '');

    expect($urls)->not->toBeEmpty()
        ->and($urls)->toContain(route('lacak-pengaduan.nomor', ['nomor' => $pengaduan->nomor_pengaduan], false))
        ->and($urls)->not->toContain(route('lacak-pengaduan.nomor', ['nomor' => $nomorLama], false));

    foreach ($urls as $url) {
        $cleanUrl = trim($url);
        $response = $this->get($cleanUrl);

        expect($response->status())
            ->toBe(200, "URL {$cleanUrl} gagal mengembalikan HTTP 200 (status: {$response->status()})");
    }
});

it('tidak mengarang URL pengaduan saat basis data kosong', function () {
    Pengaduan::query()->delete();

    Artisan::call('sim:tautan-statis');

    expect(Artisan::output())->not->toContain('/lacak-pengaduan/');
});
