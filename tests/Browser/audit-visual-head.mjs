import { createHash } from 'node:crypto';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { spawn } from 'node:child_process';
import { setTimeout as tidur } from 'node:timers/promises';
import { buatPenjagaBrowser, masukAdmin, wajibWebSocket } from './browser-harness.mjs';

const ASAL = 'http://127.0.0.1:8099';
const PORT = 9377;
const KELUARAN = 'artifacts/system-audit/visual-head';
const RUTE = [
    ['dashboard', '/', 'h1', 'Dashboard Kawasan Kobalima Timur'],
    ['transmigran', '/transmigran', 'h1', 'Data Transmigran'],
    ['pengaduan', '/pengaduan', 'h1', 'Pengaduan'],
    ['penanaman', '/penanaman', 'h1', 'Penanaman'],
    ['panen', '/panen', 'h1', 'Hasil Panen'],
    ['master-wilayah', '/wilayah', 'h1', 'Data Master Wilayah'],
    ['master-daftar-pilihan', '/master/daftar-pilihan', 'h1', 'Data Master Daftar Pilihan'],
    ['monografi', '/laporan/monografi-sp', 'article h1', 'Laporan Monografi SP'],
];
const VIEWPORT = [
    ['desktop', 1440, 900],
    ['mobile', 390, 844],
];
const EDGE = [
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
].find(existsSync);

function PNGDimensi(buffer) {
    if (buffer.toString('ascii', 1, 4) !== 'PNG') throw new Error('Bukti bukan PNG.');
    return [buffer.readUInt32BE(16), buffer.readUInt32BE(20)];
}

async function main() {
    wajibWebSocket();
    if (! EDGE) throw new Error('Edge tidak ditemukan.');
    mkdirSync(KELUARAN, { recursive: true });
    const penjaga = buatPenjagaBrowser();
    const proses = spawn(EDGE, ['--headless=new', `--remote-debugging-port=${PORT}`, '--no-first-run', '--disable-gpu', 'about:blank']);

    try {
        let daftar;
        for (let i = 0; i < 40; i += 1) {
            try {
                daftar = await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json();
                break;
            } catch { await tidur(250); }
        }
        if (! daftar) throw new Error('DevTools tidak merespons.');
        const soket = new WebSocket(daftar.find((t) => t.type === 'page').webSocketDebuggerUrl);
        let id = 0;
        const pending = new Map();
        soket.addEventListener('message', (event) => {
            const pesan = JSON.parse(event.data);
            penjaga.amati(pesan);
            if (pesan.id && pending.has(pesan.id)) {
                pending.get(pesan.id)(pesan.result);
                pending.delete(pesan.id);
            }
        });
        await new Promise((resolve, reject) => {
            soket.addEventListener('open', resolve);
            soket.addEventListener('error', reject);
        });
        const kirim = (method, params = {}) => new Promise((resolve) => {
            id += 1;
            pending.set(id, resolve);
            soket.send(JSON.stringify({ id, method, params }));
        });
        const nilai = async (expression) => (await kirim('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true }))?.result?.value;
        await kirim('Page.enable');
        await kirim('Runtime.enable');
        await masukAdmin({ kirim, nilai, asal: ASAL, penjaga });

        const bukti = [];
        for (const [mode, width, height] of VIEWPORT) {
            await kirim('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: mode === 'mobile' });
            for (const [nama, jalur, selector, heading] of RUTE) {
                await kirim('Page.navigate', { url: `${ASAL}${jalur}` });
                for (let i = 0; i < 60; i += 1) {
                    if (await nilai(`document.readyState === 'complete' && !!window.Alpine && document.querySelectorAll(${JSON.stringify(selector)}).length === 1`)) break;
                    await tidur(250);
                }
                await tidur(400);
                const identitas = await nilai(`(() => ({url: location.pathname + location.search, judul: document.querySelector(${JSON.stringify(selector)})?.textContent.trim(), selector: document.querySelectorAll(${JSON.stringify(selector)}).length, overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth}))()`);
                if (identitas.url !== jalur || identitas.selector !== 1 || identitas.judul !== heading) throw new Error(`Identitas halaman gagal untuk ${jalur}: ${JSON.stringify(identitas)}`);
                penjaga.pastikanBersih();
                const tangkap = await kirim('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
                const file = `${KELUARAN}/${mode}-${nama}.png`;
                writeFileSync(file, Buffer.from(tangkap.data, 'base64'));
                const buffer = readFileSync(file);
                const [pngWidth, pngHeight] = PNGDimensi(buffer);
                bukti.push({ nama, jalur, url_akhir: identitas.url, heading: identitas.judul, role: 'Admin', mode, viewport: { width, height }, png: { width: pngWidth, height: pngHeight }, overflow_horizontal: identitas.overflow, file, sha256: createHash('sha256').update(buffer).digest('hex') });
            }
        }
        penjaga.pastikanBersih();
        const sha = process.env.AUDIT_SHA;
        if (! /^[0-9a-f]{40}$/.test(sha ?? '')) throw new Error('AUDIT_SHA wajib berupa SHA Git 40 karakter.');
        writeFileSync(`${KELUARAN}/manifest.json`, JSON.stringify({ audit_sha: sha, generated_at: new Date().toISOString(), theme: 'dark', count: bukti.length, captures: bukti }, null, 2) + '\n');
        soket.close();
    } finally {
        proses.kill();
    }
}

main().catch((error) => { console.error(error); process.exit(1); });
