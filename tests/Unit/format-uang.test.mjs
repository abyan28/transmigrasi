import assert from 'node:assert';
import { bersihkanUang, formatUang, hitungPosisiKursor } from '../../resources/js/format-uang.js';

let passed = 0;
let failed = 0;

function test(nama, fn) {
    try {
        fn();
        passed++;
        console.log(`✓ ${nama}`);
    } catch (err) {
        failed++;
        console.error(`✗ ${nama}: ${err.message}`);
    }
}

console.log('--- Uji Unit format-uang.js ---');

// 1. Uji bersihkanUang
test('bersihkanUang: nilai kosong atau null menghasilkan string kosong', () => {
    assert.strictEqual(bersihkanUang(''), '');
    assert.strictEqual(bersihkanUang(null), '');
    assert.strictEqual(bersihkanUang(undefined), '');
});

test('bersihkanUang: angka mentah', () => {
    assert.strictEqual(bersihkanUang('1000'), '1000');
    assert.strictEqual(bersihkanUang(1000), '1000');
    assert.strictEqual(bersihkanUang('1000000'), '1000000');
    assert.strictEqual(bersihkanUang(0), '0');
    assert.strictEqual(bersihkanUang('0'), '0');
});

test('bersihkanUang: membersihkan titik ribuan dan pemisah', () => {
    assert.strictEqual(bersihkanUang('1.000'), '1000');
    assert.strictEqual(bersihkanUang('1.000.000'), '1000000');
    assert.strictEqual(bersihkanUang('10.000.000'), '10000000');
});

test('bersihkanUang: membersihkan prefix Rp dan spasi', () => {
    assert.strictEqual(bersihkanUang('Rp 1.000.000'), '1000000');
    assert.strictEqual(bersihkanUang('Rp. 1.000.000'), '1000000');
    assert.strictEqual(bersihkanUang('  Rp 50.000  '), '50000');
});

test('bersihkanUang: membersihkan pecahan sen akuntansi (,00 atau .00)', () => {
    assert.strictEqual(bersihkanUang('Rp 1.500.000,00'), '1500000');
    assert.strictEqual(bersihkanUang('1500000.00'), '1500000');
    assert.strictEqual(bersihkanUang('1.500.000,00'), '1500000');
});

test('bersihkanUang: membersihkan karakter huruf dan simbol negatif', () => {
    assert.strictEqual(bersihkanUang('-1000000'), '1000000');
    assert.strictEqual(bersihkanUang('abc123def'), '123');
    assert.strictEqual(bersihkanUang('Rp -500.000'), '500000');
});

test('bersihkanUang: membersihkan leading zeros berlebih tapi menjaga 0', () => {
    assert.strictEqual(bersihkanUang('007'), '7');
    assert.strictEqual(bersihkanUang('0001000'), '1000');
    assert.strictEqual(bersihkanUang('0'), '0');
    assert.strictEqual(bersihkanUang('00'), '0');
});

// 2. Uji formatUang
test('formatUang: nilai kosong', () => {
    assert.strictEqual(formatUang(''), '');
    assert.strictEqual(formatUang(null), '');
    assert.strictEqual(formatUang(undefined), '');
});

test('formatUang: nilai nol', () => {
    assert.strictEqual(formatUang('0'), '0');
    assert.strictEqual(formatUang(0), '0');
});

test('formatUang: angka ratusan, ribuan, jutaan, miliaran', () => {
    assert.strictEqual(formatUang('500'), '500');
    assert.strictEqual(formatUang('1000'), '1.000');
    assert.strictEqual(formatUang('10000'), '10.000');
    assert.strictEqual(formatUang('100000'), '100.000');
    assert.strictEqual(formatUang('1000000'), '1.000.000');
    assert.strictEqual(formatUang('10000000'), '10.000.000');
    assert.strictEqual(formatUang('1000000000'), '1.000.000.000');
});

test('formatUang: idempoten (formatting teks yang sudah terformat tidak merusak nilai)', () => {
    assert.strictEqual(formatUang('1.000.000'), '1.000.000');
    assert.strictEqual(formatUang(formatUang('1.000.000')), '1.000.000');
    assert.strictEqual(formatUang('Rp 1.000.000'), '1.000.000');
});

// 3. Uji hitungPosisiKursor
test('hitungPosisiKursor: kursor di ujung saat mengetik', () => {
    // 1000 -> ketik 0 di ujung jadi 10000 -> format 10.000
    const kursor = hitungPosisiKursor('10000', 5, '10.000');
    assert.strictEqual(kursor, 6);
});

test('hitungPosisiKursor: kursor di tengah angka', () => {
    // 1.000.000 -> kursor di setelah '1.' (digit 1, kursor pos 2) -> ketik 5 -> '1.5000.000' (2 digit sebelum kursor) -> format '15.000.000'
    const kursor = hitungPosisiKursor('1.5000.000', 3, '15.000.000');
    // Digit sebelum kursor adalah '1' dan '5' = 2 digit. Di '15.000.000', 2 digit pertama ada di index 0 ('1') dan index 1 ('5'), posisi kursor = 2.
    assert.strictEqual(kursor, 2);
});

console.log(`\nHasil: ${passed} lulus, ${failed} gagal.`);
if (failed > 0) process.exit(1);
