<?php

use Illuminate\Support\Facades\Artisan;

it('menghasilkan URL statis yang seluruhnya merespons HTTP 200', function () {
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
