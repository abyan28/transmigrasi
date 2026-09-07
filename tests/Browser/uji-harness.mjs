import { buatPenjagaBrowser } from './browser-harness.mjs';

const penjaga = buatPenjagaBrowser();

penjaga.amati({
    method: 'Runtime.exceptionThrown',
    params: { exceptionDetails: { text: 'uji exception' } },
});
penjaga.amati({
    method: 'Runtime.consoleAPICalled',
    params: { type: 'error', args: [{ value: 'uji console' }] },
});
penjaga.loginSelesai();
penjaga.amati({
    method: 'Page.frameNavigated',
    params: { frame: { url: 'http://127.0.0.1:8099/login' } },
});

let tertangkap = false;
try {
    penjaga.pastikanBersih();
} catch (galat) {
    tertangkap = galat.message.includes('uji exception')
        && galat.message.includes('uji console')
        && galat.message.includes('/login');
}

if (! tertangkap) throw new Error('Penjaga browser tidak menangkap seluruh kegagalan wajib.');
console.log('browser harness guard: PASS');
