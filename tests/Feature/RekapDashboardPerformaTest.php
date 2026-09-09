<?php

use App\Support\RekapDashboard;
use Illuminate\Support\Facades\DB;

it('membangun deret dashboard dengan budget query konstan terhadap rentang tahun', function () {
    DB::flushQueryLog();
    DB::enableQueryLog();

    $deret = RekapDashboard::deret();
    $jumlahQuery = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect(count($deret['tahun']))->toBeGreaterThan(10)
        ->and($jumlahQuery)->toBeLessThanOrEqual(25);
});
