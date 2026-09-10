import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';

const expected = new Map([
    ['/', 'Dashboard Kawasan Kobalima Timur'],
    ['/transmigran', 'Data Transmigran'],
    ['/pengaduan', 'Pengaduan'],
    ['/penanaman', 'Penanaman'],
    ['/panen', 'Hasil Panen'],
    ['/wilayah', 'Data Master Wilayah'],
    ['/master/daftar-pilihan', 'Data Master Daftar Pilihan'],
    ['/laporan/monografi-sp', 'Laporan Monografi SP'],
]);
const viewports = new Map([
    ['desktop', { width: 1440, height: 900 }],
    ['mobile', { width: 390, height: 844 }],
]);
const manifest = JSON.parse(readFileSync('artifacts/system-audit/visual-head/manifest.json', 'utf8'));
const head = execFileSync('git', ['rev-parse', 'HEAD'], { encoding: 'utf8' }).trim();
if (manifest.audit_sha !== head) throw new Error(`Manifest ${manifest.audit_sha} tidak cocok HEAD ${head}.`);
if (manifest.theme !== 'dark') throw new Error('Tema audit wajib dark.');
if (manifest.count !== expected.size * viewports.size || manifest.count !== manifest.captures.length) throw new Error('Matriks capture tidak lengkap.');

const hashes = new Map();
const matrix = new Set();
for (const capture of manifest.captures) {
    const viewport = viewports.get(capture.mode);
    const key = `${capture.mode}:${capture.jalur}`;
    if (! viewport || matrix.has(key)) throw new Error(`Capture mode/route tidak sah atau duplikat: ${key}`);
    matrix.add(key);
    if (capture.role !== 'Admin' || capture.url_akhir !== capture.jalur || capture.heading !== expected.get(capture.jalur)) throw new Error(`Identitas halaman tidak sah: ${capture.file}`);
    if (capture.viewport.width !== viewport.width || capture.viewport.height !== viewport.height || capture.viewport.width !== capture.png.width || capture.viewport.height !== capture.png.height) throw new Error(`Dimensi tidak cocok: ${capture.file}`);
    if (capture.overflow_horizontal) throw new Error(`Overflow horizontal: ${capture.file}`);
    const hash = createHash('sha256').update(readFileSync(capture.file)).digest('hex');
    if (hash !== capture.sha256) throw new Error(`Hash tidak cocok: ${capture.file}`);
    if (hashes.has(hash)) throw new Error(`Screenshot duplikat: ${hashes.get(hash)} dan ${capture.file}`);
    hashes.set(hash, capture.file);
}
for (const mode of viewports.keys()) for (const route of expected.keys()) if (! matrix.has(`${mode}:${route}`)) throw new Error(`Capture hilang: ${mode}:${route}`);

console.log(`visual evidence validator: PASS (${manifest.count} capture)`);
