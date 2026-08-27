/**
 * Konfigurasi bersama seluruh grafik ApexCharts.
 *
 * Ditulis satu kali di sini agar warna, font, locale, dan format angka tidak
 * diulang di tiap grafik (agents/ui-spec.md bagian 9 poin 8).
 *
 * Dua hal yang ditangani berkas ini dan sering terlewat bila konfigurasi
 * disalin per grafik:
 *
 * 1. Mode gelap. ApexCharts menggambar sumbu, legenda, dan tooltip memakai
 *    warna yang dihitung saat grafik dibuat, sehingga tidak ikut berubah saat
 *    kelas `dark` dipasang. Karena itu setiap grafik didaftarkan lalu digambar
 *    ulang ketika tema berganti (agents/ui-spec.md bagian 3.2.3, R-34).
 *
 * 2. Format angka Indonesia. Koma sebagai pemisah desimal dan titik sebagai
 *    pemisah ribuan (agents/rules.md bagian 13.3 poin 3).
 */

/**
 * Urutan warna seri grafik, diambil dari palet logo Kementerian.
 *
 * ApexCharts memerlukan nilai heksadesimal, bukan nama kelas Tailwind
 * (agents/ui-spec.md bagian 9 poin 6).
 */
export const warnaSeri = [
    '#163B54', // navy-500
    '#33809C', // teal-500
    '#C09546', // gold-500
    '#DFB87E', // sand-500
    '#265F73', // teal-700
];

/** Warna kondisi aset, dipakai grafik status infrastruktur. */
export const warnaKondisi = {
    baik: '#12b76a',
    rusakRingan: '#f79009',
    rusakBerat: '#f04438',
};

/** Memeriksa apakah antarmuka sedang memakai mode gelap. */
export function modeGelap() {
    return document.documentElement.classList.contains('dark');
}

/**
 * Membulatkan dan memformat angka mengikuti kaidah penulisan Indonesia.
 *
 * @param {number} nilai Angka yang diformat
 * @param {number} desimal Banyak angka di belakang koma
 * @returns {string} Contoh: 1.234,567
 */
export function angka(nilai, desimal = 0) {
    if (nilai === null || nilai === undefined || Number.isNaN(nilai)) {
        return '-';
    }

    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: desimal,
        maximumFractionDigits: desimal,
    }).format(nilai);
}

/**
 * Memformat nilai uang rupiah tanpa angka desimal.
 *
 * @param {number} nilai Nominal dalam rupiah
 * @returns {string} Contoh: Rp 2.500.000
 */
export function rupiah(nilai) {
    return 'Rp ' + angka(nilai, 0);
}

/**
 * Menyingkat angka besar agar label sumbu tidak saling menimpa.
 *
 * @param {number} nilai Angka yang disingkat
 * @returns {string} Contoh: 4,5 jt
 */
export function angkaSingkat(nilai) {
    if (nilai >= 1_000_000_000) return angka(nilai / 1_000_000_000, 1) + ' M';
    if (nilai >= 1_000_000) return angka(nilai / 1_000_000, 1) + ' jt';
    if (nilai >= 1_000) return angka(nilai / 1_000, 1) + ' rb';
    return angka(nilai, 0);
}

/**
 * Konfigurasi dasar yang diwarisi seluruh grafik.
 *
 * Toolbar dan animasi dimatikan mengikuti dial GERAK 1: perangkat lapangan
 * terbatas dan animasi tidak membantu tugas pendataan (agents/ui-spec.md 2.2).
 *
 * @returns {object} Potongan opsi ApexCharts
 */
export function opsiDasar() {
    const gelap = modeGelap();
    const warnaTeks = gelap ? '#98A9B4' : '#667085';
    const warnaGaris = gelap ? '#102C3E' : '#E4E7EC';

    return {
        chart: {
            fontFamily: 'Outfit, system-ui, sans-serif',
            foreColor: warnaTeks,
            toolbar: { show: false },
            animations: { enabled: false },
            background: 'transparent',

            /*
                Grafik ikut menyesuaikan diri setiap kali wadahnya berubah
                lebar, misalnya ketika sidebar dilipat atau jendela diubah
                ukurannya.

                Ini juga menjadi pengaman berkelanjutan bagi tab yang dibuka
                di latar belakang: lebar wadahnya baru terbentuk setelah tab
                ditampilkan, dan tanpa opsi ini kanvasnya tetap memakai ukuran
                keliru yang dihitung saat lebar masih nol.
            */
            redrawOnParentResize: true,
            redrawOnWindowResize: true,
        },
        colors: warnaSeri,
        theme: { mode: gelap ? 'dark' : 'light' },
        grid: {
            borderColor: warnaGaris,
            strokeDashArray: 4,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { left: 8, right: 8 },
        },
        dataLabels: { enabled: false },
        legend: {
            position: 'bottom',
            horizontalAlign: 'left',
            fontSize: '12px',
            markers: { radius: 12 },
            labels: { colors: warnaTeks },
        },
        tooltip: {
            theme: gelap ? 'dark' : 'light',
            style: { fontSize: '12px', fontFamily: 'Outfit, system-ui, sans-serif' },
        },
        xaxis: {
            axisBorder: { color: warnaGaris },
            axisTicks: { color: warnaGaris },
            labels: { style: { colors: warnaTeks, fontSize: '12px' } },
        },
        yaxis: {
            labels: { style: { colors: warnaTeks, fontSize: '12px' } },
        },
        states: {
            hover: { filter: { type: 'lighten', value: 0.06 } },
        },
        noData: {
            text: 'Data belum tersedia',
            style: { color: warnaTeks, fontSize: '13px', fontFamily: 'Outfit, system-ui, sans-serif' },
        },
    };
}

/**
 * Menggabungkan dua objek opsi secara rekursif.
 *
 * Diperlukan karena opsi ApexCharts bersarang, sehingga penggabungan dangkal
 * akan menghapus seluruh isi `chart` atau `xaxis` bawaan.
 *
 * @param {object} dasar Objek dasar
 * @param {object} tambahan Objek penimpa
 * @returns {object} Hasil gabungan
 */
export function gabung(dasar, tambahan) {
    const hasil = { ...dasar };

    for (const [kunci, nilai] of Object.entries(tambahan ?? {})) {
        const bisaDigabung =
            nilai && typeof nilai === 'object' && !Array.isArray(nilai) &&
            hasil[kunci] && typeof hasil[kunci] === 'object' && !Array.isArray(hasil[kunci]);

        hasil[kunci] = bisaDigabung ? gabung(hasil[kunci], nilai) : nilai;
    }

    return hasil;
}

/** Daftar grafik aktif, dipakai untuk menggambar ulang saat tema berganti. */
const grafikTerdaftar = new Map();

/**
 * Membuat grafik ApexCharts beserta penanganan mode gelap.
 *
 * @param {string} idElemen Id elemen wadah grafik
 * @param {object} opsi Opsi khusus grafik, digabung di atas opsiDasar()
 * @returns {object|null} Instance ApexCharts, atau null bila elemen tidak ada
 */
export function buatGrafik(idElemen, opsi) {
    const elemen = document.getElementById(idElemen);

    if (!elemen) {
        return null;
    }

    // Grafik lama dimusnahkan lebih dulu agar tidak menumpuk saat digambar ulang.
    grafikTerdaftar.get(idElemen)?.instance?.destroy();

    const grafik = new ApexCharts(elemen, gabung(opsiDasar(), opsi));
    grafik.render();

    grafikTerdaftar.set(idElemen, { instance: grafik, opsi });

    pantauLebarWadah(idElemen, elemen);

    return grafik;
}

/**
 * Menggambar ulang satu grafik ketika wadahnya BENAR-BENAR terlihat.
 *
 * ApexCharts menghitung lebar kanvasnya sekali saat digambar, dari lebar
 * elemen wadahnya. Pada tab yang dibuka di latar belakang lewat "buka di tab
 * baru", peramban belum melakukan layout sehingga lebar itu terbaca nol, dan
 * ApexCharts jatuh ke lebar bawaannya yang jauh lebih besar.
 *
 * Kanvas keliru itu tidak pernah dihitung ulang dengan sendirinya, sehingga
 * grafik tampak menembus tepi kartunya sampai halaman disegarkan manual.
 * Pengamat di bawah menggambar ulang sekali begitu wadahnya memiliki lebar
 * yang sesungguhnya, lalu melepaskan dirinya agar tidak membebani halaman.
 *
 * @param {string} idElemen Id elemen wadah grafik
 * @param {HTMLElement} elemen Wadah grafik
 */
function pantauLebarWadah(idElemen, elemen) {
    if (typeof IntersectionObserver === 'undefined') {
        return;
    }

    // Lebar sudah benar sejak awal, misalnya pada tab yang langsung aktif.
    if (elemen.clientWidth > 0) {
        return;
    }

    const pengamat = new IntersectionObserver((entri) => {
        for (const satu of entri) {
            if (!satu.isIntersecting || satu.target.clientWidth === 0) {
                continue;
            }

            pengamat.disconnect();
            gambarUlangSatu(idElemen);
        }
    });

    pengamat.observe(elemen);
}

/**
 * Menggambar ulang satu grafik memakai opsi yang sudah tersimpan.
 *
 * @param {string} idElemen Id elemen wadah grafik
 */
function gambarUlangSatu(idElemen) {
    const tercatat = grafikTerdaftar.get(idElemen);
    const elemen = document.getElementById(idElemen);

    if (!tercatat || !elemen) {
        return;
    }

    tercatat.instance?.destroy();

    const grafik = new ApexCharts(elemen, gabung(opsiDasar(), tercatat.opsi));
    grafik.render();

    grafikTerdaftar.set(idElemen, { instance: grafik, opsi: tercatat.opsi });
}

/**
 * Menggambar ulang seluruh grafik memakai warna tema yang berlaku.
 *
 * Dipanggil otomatis saat kelas `dark` pada elemen html berubah.
 */
export function segarkanTema() {
    for (const [idElemen, { opsi }] of grafikTerdaftar.entries()) {
        const elemen = document.getElementById(idElemen);

        if (!elemen) {
            grafikTerdaftar.delete(idElemen);
            continue;
        }

        grafikTerdaftar.get(idElemen).instance?.destroy();

        const grafik = new ApexCharts(elemen, gabung(opsiDasar(), opsi));
        grafik.render();
        grafikTerdaftar.set(idElemen, { instance: grafik, opsi });
    }
}

/**
 * Memantau pergantian mode tema pada elemen html.
 *
 * Tema disimpan Alpine store lewat penambahan kelas `dark`, sehingga
 * MutationObserver adalah cara paling langsung mendeteksinya tanpa
 * menambahkan kait khusus di setiap tombol tema.
 */
export function pantauTema() {
    const pengamat = new MutationObserver((perubahan) => {
        for (const ubah of perubahan) {
            if (ubah.attributeName === 'class') {
                segarkanTema();
                return;
            }
        }
    });

    pengamat.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
}

/**
 * Membuka halaman rincian sebuah SP saat titik data grafik diklik.
 *
 * Dipasang sebagai event `dataPointSelection` agar seluruh grafik rekap
 * gabungan dapat ditelusuri ke rincian per SP (agents/rules.md bagian 11
 * poin 5).
 *
 * ALAMAT DASAR WAJIB DIOPER DARI BLADE, tidak boleh ditulis tetap di sini.
 * Sebelum 2026-08-25 modul ini memuat '/dashboard/sp/' secara harfiah,
 * sehingga seluruh penelusuran grafik membalas 404 pada penyajian statis yang
 * berada di sub-path `/transmigrasi/`. Berkas JavaScript tidak mengenal
 * `url()`, jadi satu-satunya sumber alamat yang benar adalah Blade
 * (agents/notes.md bagian 1b.3).
 *
 * @param {Array<number|string>} idSp Daftar id SP berurutan sesuai kategori grafik
 * @param {string} basisUrl Alamat dasar rincian SP, hasil url('/dashboard/sp')
 * @returns {Function} Penangan peristiwa ApexCharts
 */
export function drilldownSp(idSp, basisUrl) {
    return function (event, konteks, konfigurasi) {
        const id = idSp[konfigurasi.dataPointIndex];

        if (id !== undefined && id !== null) {
            window.location.href = basisUrl.replace(/\/$/, '') + '/' + id;
        }
    };
}

export default {
    warnaSeri,
    warnaKondisi,
    modeGelap,
    angka,
    rupiah,
    angkaSingkat,
    opsiDasar,
    gabung,
    buatGrafik,
    segarkanTema,
    pantauTema,
    drilldownSp,
};
