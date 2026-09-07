import { setTimeout as tidur } from 'node:timers/promises';

export function wajibWebSocket() {
    if (typeof WebSocket === 'undefined') {
        throw new Error('WebSocket bawaan Node wajib tersedia untuk uji browser.');
    }
}

export function buatPenjagaBrowser() {
    const galat = [];
    let sudahLogin = false;

    return {
        amati(pesan) {
            if (pesan.method === 'Runtime.exceptionThrown') {
                const rincian = pesan.params?.exceptionDetails;
                const pesanGalat = rincian?.exception?.description ?? rincian?.text ?? 'Exception JavaScript tanpa pesan.';
                galat.push(`${pesanGalat} @ ${rincian?.url ?? 'halaman aktif'}:${rincian?.lineNumber ?? '?'}`);
            }
            if (pesan.method === 'Runtime.consoleAPICalled' && pesan.params?.type === 'error') {
                galat.push(pesan.params.args?.map((arg) => arg.value ?? arg.description).join(' ') || 'console.error');
            }
            if (sudahLogin && pesan.method === 'Page.frameNavigated' && ! pesan.params?.frame?.parentId) {
                const path = new URL(pesan.params.frame.url).pathname;
                if (path === '/login') galat.push('Navigasi internal dialihkan kembali ke /login.');
            }
        },
        loginSelesai() {
            sudahLogin = true;
        },
        pastikanBersih() {
            if (galat.length > 0) {
                throw new Error(`Galat browser terdeteksi:\n- ${galat.join('\n- ')}`);
            }
        },
    };
}

export async function masukAdmin({ kirim, nilai, asal, penjaga }) {
    await kirim('Page.enable');
    await kirim('Runtime.enable');
    await kirim('Page.navigate', { url: `${asal}/login` });

    for (let i = 0; i < 40; i += 1) {
        if (await nilai("!!document.querySelector('#kredensial')")) break;
        await tidur(250);
    }

    const username = process.env.SIM_BROWSER_USERNAME;
    const password = process.env.SIM_BROWSER_PASSWORD;
    if (! username || ! password) {
        throw new Error('SIM_BROWSER_USERNAME dan SIM_BROWSER_PASSWORD wajib diatur untuk uji browser.');
    }
    const dikirim = await nilai(`
        (() => {
            const kredensial = document.querySelector('#kredensial');
            const sandi = document.querySelector('#password');
            const form = kredensial?.closest('form');
            if (! kredensial || ! sandi || ! form) return false;
            kredensial.value = ${JSON.stringify(username)};
            sandi.value = ${JSON.stringify(password)};
            form.submit();
            return true;
        })()
    `);
    if (! dikirim) throw new Error('Form login tidak ditemukan. Pastikan server dan seed akun browser tersedia.');

    for (let i = 0; i < 40; i += 1) {
        if (await nilai("window.location.pathname !== '/login'")) break;
        await tidur(250);
    }
    const path = await nilai('window.location.pathname');
    if (path === '/login') throw new Error('Login browser gagal; periksa SIM_BROWSER_USERNAME/PASSWORD.');
    penjaga.loginSelesai();
}
