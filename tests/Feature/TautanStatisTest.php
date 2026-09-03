<?php

use Illuminate\Support\Facades\Artisan;

it('menghasilkan URL statis yang seluruhnya merespons HTTP 200', function () {
    // deploy.yml meng-crawl tanpa login; cerminkan itu. Suite Feature
    // diautentikasi global (Task 3.2b), jadi keluar dulu supaya halaman
    // ber-`guest` (/login dsb.) dirender alih-alih dialihkan.
    auth()->logout();

    Artisan::call('sim:tautan-statis');
    $output = trim(Artisan::output());

    $urls = array_filter(explode(PHP_EOL, $output), fn ($u) => trim($u) !== '');

    expect($urls)->not->toBeEmpty();

    foreach ($urls as $url) {
        $cleanUrl = trim($url);
        $response = $this->get($cleanUrl);

        expect($response->status())
            ->toBe(200, "URL {$cleanUrl} gagal mengembalikan HTTP 200 (status: {$response->status()})");
    }
});
