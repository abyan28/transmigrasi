/**
 * Penguncian gulir halaman selama lapisan menutup layar sedang terbuka.
 *
 * Yang dikunci adalah `<html>`, BUKAN `<body>`.
 *
 * `layouts/app.blade.php` menetapkan `<html class="h-full">` sedangkan `<body>`
 * tidak diberi tinggi apa pun, sehingga elemen yang benar-benar menggulir
 * adalah `<html>`. Pemasangan `overflow-hidden` pada `<body>` karena itu tidak
 * mengunci apa pun: halaman di belakang tetap bergeser dan panel modal ikut
 * terbawa naik sampai tenggelam ke luar layar.
 *
 * Penguncian memakai penghitung, bukan penanda tunggal, sebab lapisan dapat
 * bertumpuk. Dialog konfirmasi yang dibuka dari dalam modal formulir akan
 * membuka kunci lebih awal bila setiap penutupan langsung melepas kuncinya,
 * padahal modal di bawahnya masih terbuka.
 *
 * Lebar bilah gulir diganti dengan padding agar tata letak tidak melompat
 * mendatar ketika bilah gulir menghilang.
 */

let tumpukan = 0;

/** Menahan nilai padding asli agar dapat dipulihkan persis seperti semula. */
let paddingAsli = '';

/**
 * Menambah satu lapisan dan mengunci gulir halaman.
 *
 * @returns {void}
 */
export function kunci() {
    const akar = document.documentElement;

    tumpukan++;

    if (tumpukan > 1) {
        return;
    }

    const lebarBilah = window.innerWidth - akar.clientWidth;

    paddingAsli = akar.style.paddingRight;

    if (lebarBilah > 0) {
        akar.style.paddingRight = `${lebarBilah}px`;
    }

    akar.classList.add('overflow-hidden');
}

/**
 * Melepas satu lapisan, dan membuka kunci bila tidak ada lapisan tersisa.
 *
 * @returns {void}
 */
export function lepas() {
    const akar = document.documentElement;

    tumpukan = Math.max(0, tumpukan - 1);

    if (tumpukan > 0) {
        return;
    }

    akar.classList.remove('overflow-hidden');
    akar.style.paddingRight = paddingAsli;
}

/**
 * Melepas seluruh lapisan sekaligus.
 *
 * Dipakai sebagai jaring pengaman, misalnya ketika halaman berpindah selagi
 * modal terbuka dan penutupannya tidak pernah terpanggil.
 *
 * @returns {void}
 */
export function lepasSemua() {
    tumpukan = 1;
    lepas();
}

export default { kunci, lepas, lepasSemua };
