/**
 * Modul pemformat dan sanitasi input nominal uang (Rupiah).
 *
 * Mengubah input angka mentah (contoh 1000000) menjadi berpemisah ribuan
 * Indonesia (1.000.000) secara otomatis, sembari menjaga agar nilai yang dikirim
 * saat formulir disubmit tetap berupa string numerik murni (1000000).
 */

const protoValueDescriptor = typeof HTMLInputElement !== 'undefined'
    ? Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')
    : null;

/**
 * Membersihkan nilai input dari segala karakter non-digit.
 * Menangani prefix Rp, spasi, titik ribuan, tanda minus, pecahan sen (,00 / .00),
 * dan leading zeros berlebih.
 *
 * @param {string|number|null|undefined} nilai
 * @returns {string} String digit murni (misal "1000000", "0", atau "")
 */
export function bersihkanUang(nilai) {
    if (nilai === null || nilai === undefined || nilai === '') {
        return '';
    }

    let teks = String(nilai).trim();

    // Jika user mem-paste format akuntansi/sen di belakang (misal "1.500.000,00" atau "1500000.00"),
    // bersihkan sen tersebut terlebih dahulu sebelum membuang karakter non-digit.
    teks = teks.replace(/[,.]00$/, '');

    // Hapus semua karakter selain angka
    const digit = teks.replace(/\D/g, '');

    if (digit === '') {
        return '';
    }

    // Hapus leading zero berlebih (misal "00500" -> "500"), namun pertahankan "0" tunggal
    return digit.replace(/^0+(?=\d)/, '');
}

/**
 * Memformat string digit murni menjadi format ribuan dengan titik (1.000.000).
 *
 * @param {string|number|null|undefined} nilai
 * @returns {string} String terformat (misal "1.000.000", "0", atau "")
 */
export function formatUang(nilai) {
    const bersih = bersihkanUang(nilai);

    if (bersih === '') {
        return '';
    }

    // Format pemisah ribuan dengan titik
    return bersih.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/**
 * Menghitung posisi kursor baru yang tepat setelah pemformatan ulang teks.
 *
 * @param {string} teksLama Teks sebelum/saat proses edit
 * @param {number} kursorLama Posisi kursor sebelum pemformatan
 * @param {string} teksBaru Teks baru yang sudah diformat
 * @returns {number} Posisi kursor baru pada teksBaru
 */
export function hitungPosisiKursor(teksLama, kursorLama, teksBaru) {
    const digitSebelum = teksLama.slice(0, kursorLama).replace(/\D/g, '').length;

    if (digitSebelum === 0) {
        return 0;
    }

    let hitungDigit = 0;
    for (let i = 0; i < teksBaru.length; i++) {
        if (/\d/.test(teksBaru[i])) {
            hitungDigit++;
            if (hitungDigit === digitSebelum) {
                return i + 1;
            }
        }
    }

    return teksBaru.length;
}

/**
 * Tombol kendali navigasi & pintasan yang diizinkan saat keydown.
 */
const TOMBOL_KENDALI = [
    'Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'Home', 'End',
    'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
];

/**
 * Memasang direktif Alpine `x-uang` dan pendengar form submit global.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export function pasangFormatUang(Alpine) {
    // 1. Direktif Alpine x-uang
    Alpine.directive('uang', (el, { expression }, { effect, cleanup }) => {
        el.setAttribute('data-uang', 'true');
        el.setAttribute('inputmode', 'numeric');
        el.setAttribute('autocomplete', 'off');

        if (el.type === 'number') {
            el.type = 'text';
        }

        let mengabaikanSetter = false;

        // Fungsi pemformat lokal elemen
        const terapFormat = () => {
            const val = protoValueDescriptor.get.call(el);
            const bersih = bersihkanUang(val);
            const format = formatUang(bersih);

            if (val !== format) {
                const kursor = el.selectionStart ?? val.length;
                const kursorBaru = hitungPosisiKursor(val, kursor, format);
                mengabaikanSetter = true;
                protoValueDescriptor.set.call(el, format);
                mengabaikanSetter = false;
                if (document.activeElement === el) {
                    el.setSelectionRange(kursorBaru, kursorBaru);
                }
            }
        };

        // Intersepsi setter property value agar perubahan nilai programmatic
        // (misal via x-model atau isiFormulir) selalu otomatis diformat.
        Object.defineProperty(el, 'value', {
            get() {
                return protoValueDescriptor.get.call(this);
            },
            set(nilaiBaru) {
                if (mengabaikanSetter) {
                    protoValueDescriptor.set.call(this, nilaiBaru);
                    return;
                }
                const bersih = bersihkanUang(nilaiBaru);
                const format = formatUang(bersih);
                protoValueDescriptor.set.call(this, format);
            },
            configurable: true,
        });

        // Format nilai awal dari Blade / HTML
        terapFormat();

        // Handler input
        const padaInput = () => {
            terapFormat();
        };

        // Handler keydown: tolak huruf, tanda minus, simbol, dsb.
        const padaKeydown = (e) => {
            // Izinkan kombinasi Ctrl/Meta (seperti Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+Z)
            if (e.ctrlKey || e.metaKey || TOMBOL_KENDALI.includes(e.key)) {
                // Penanganan khusus Backspace jika kursor tepat setelah titik pemisah ribuan
                if (e.key === 'Backspace' && el.selectionStart === el.selectionEnd) {
                    const pos = el.selectionStart;
                    if (pos > 0 && el.value[pos - 1] === '.') {
                        e.preventDefault();
                        // Hapus digit sebelum titik
                        const val = el.value;
                        const sebelum = val.slice(0, pos - 2);
                        const sesudah = val.slice(pos);
                        const gabung = sebelum + sesudah;
                        const bersih = bersihkanUang(gabung);
                        const format = formatUang(bersih);
                        const kursorBaru = hitungPosisiKursor(gabung, pos - 2, format);
                        mengabaikanSetter = true;
                        protoValueDescriptor.set.call(el, format);
                        mengabaikanSetter = false;
                        el.setSelectionRange(kursorBaru, kursorBaru);
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }

                // Penanganan khusus Delete jika kursor tepat sebelum titik pemisah ribuan
                if (e.key === 'Delete' && el.selectionStart === el.selectionEnd) {
                    const pos = el.selectionStart;
                    if (pos < el.value.length && el.value[pos] === '.') {
                        e.preventDefault();
                        // Hapus digit setelah titik
                        const val = el.value;
                        const sebelum = val.slice(0, pos);
                        const sesudah = val.slice(pos + 2);
                        const gabung = sebelum + sesudah;
                        const bersih = bersihkanUang(gabung);
                        const format = formatUang(bersih);
                        const kursorBaru = hitungPosisiKursor(gabung, pos, format);
                        mengabaikanSetter = true;
                        protoValueDescriptor.set.call(el, format);
                        mengabaikanSetter = false;
                        el.setSelectionRange(kursorBaru, kursorBaru);
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }

                return;
            }

            // Tolak jika bukan digit 0-9
            if (!/^[0-9]$/.test(e.key)) {
                e.preventDefault();
            }
        };

        // Handler paste: bersihkan dan format langsung
        const padaPaste = (e) => {
            const tempel = (e.clipboardData || window.clipboardData)?.getData('text') ?? '';
            const bersih = bersihkanUang(tempel);
            if (bersih === '') {
                e.preventDefault();
                return;
            }

            e.preventDefault();
            const start = el.selectionStart ?? 0;
            const end = el.selectionEnd ?? 0;
            const val = el.value;
            const gabung = val.slice(0, start) + bersih + val.slice(end);
            const format = formatUang(gabung);
            const kursorBaru = hitungPosisiKursor(val.slice(0, start) + bersih, start + bersih.length, format);

            mengabaikanSetter = true;
            protoValueDescriptor.set.call(el, format);
            mengabaikanSetter = false;
            el.setSelectionRange(kursorBaru, kursorBaru);
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        };

        el.addEventListener('input', padaInput);
        el.addEventListener('keydown', padaKeydown);
        el.addEventListener('paste', padaPaste);

        cleanup(() => {
            el.removeEventListener('input', padaInput);
            el.removeEventListener('keydown', padaKeydown);
            el.removeEventListener('paste', padaPaste);
        });
    });

    // 2. Global form submit normalizer: memastikan raw numeric string (1000000) yang terkirim ke backend
    document.addEventListener('submit', (peristiwa) => {
        const form = peristiwa.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const isianUang = form.querySelectorAll('[data-uang]');
        const nilaiAsli = [];

        isianUang.forEach((isian) => {
            if (isian instanceof HTMLInputElement) {
                nilaiAsli.push({ elemen: isian, format: isian.value });
                const bersih = bersihkanUang(isian.value);
                protoValueDescriptor.set.call(isian, bersih);
            }
        });

        // Kembalikan format visual pada tick berikutnya (jika form tidak reload / ajax / submit dibatalkan)
        setTimeout(() => {
            nilaiAsli.forEach(({ elemen, format }) => {
                protoValueDescriptor.set.call(elemen, format);
            });
        }, 0);
    }, { capture: true });
}

export default { bersihkanUang, formatUang, hitungPosisiKursor, pasangFormatUang };
