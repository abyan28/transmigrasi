<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Masuk otomatis (lingkungan lokal)
    |--------------------------------------------------------------------------
    |
    | Bila true DAN APP_ENV=local, middleware MasukOtomatisLokal mengautentikasi
    | pengguna pengembang di setiap permintaan sehingga rute internal ber-`auth`
    | dapat ditelusuri tanpa login manual. Default: menyala di `local`, mati di
    | mana pun selain itu. Set SIM_MASUK_OTOMATIS=false untuk menguji alur
    | login/logout sungguhan. Tidak pernah aktif di production apa pun nilainya.
    |
    */

    'masuk_otomatis' => (bool) env('SIM_MASUK_OTOMATIS', env('APP_ENV') === 'local'),

];
