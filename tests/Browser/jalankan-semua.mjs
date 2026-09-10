import { fileURLToPath } from 'node:url';
import { readdirSync } from 'node:fs';
import { spawnSync } from 'node:child_process';

const berkas = readdirSync(new URL('.', import.meta.url))
    .filter((nama) => nama.startsWith('uji-') && nama.endsWith('.mjs') && nama !== 'uji-harness.mjs')
    .sort();

let gagal = 0;

for (const nama of berkas) {
    console.log(`\n=== ${nama} ===`);
    const hasil = spawnSync(process.execPath, [fileURLToPath(new URL(nama, import.meta.url))], { stdio: 'inherit' });
    if (hasil.status !== 0) gagal += 1;
}

if (gagal > 0) {
    console.error(`\n${gagal} skrip browser gagal dari ${berkas.length}.`);
    process.exit(1);
}
