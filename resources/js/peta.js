/**
 * Pemuat peta pemilih titik koordinat.
 *
 * Leaflet beserta gaya bawaannya dimuat LEWAT IMPOR DINAMIS, bukan disertakan
 * pada bundel utama. Hanya enam form yang memerlukan peta, sedangkan seluruh
 * halaman lain tidak; menyertakannya secara statis akan menambah beban unduhan
 * pada setiap halaman tanpa alasan.
 *
 * Petanya sendiri hanyalah pelengkap. GPS ponsel di lokus kerap meleset puluhan
 * meter, sehingga petugas perlu menggeser penanda agar titiknya tepat. Bila
 * ubin peta gagal dimuat karena jaringan lemah, isian koordinat manual dan
 * tombol pengambilan lokasi tetap berfungsi seperti biasa.
 *
 * Ubin diambil dari OpenStreetMap, sejalan dengan tautan peta yang sudah
 * dipakai pada halaman rincian, dan tidak memerlukan kunci API.
 */

/** Menyimpan modul Leaflet setelah pemuatan pertama agar tidak diunduh ulang. */
let leafletTermuat = null;

/**
 * Memuat Leaflet sekali saja, lalu memakai kembali hasilnya.
 *
 * @returns {Promise<object>} Modul Leaflet siap pakai
 */
async function muatLeaflet() {
    if (leafletTermuat) {
        return leafletTermuat;
    }

    const [modul] = await Promise.all([
        import('leaflet'),
        import('leaflet/dist/leaflet.css'),
    ]);

    const L = modul.default ?? modul;

    /*
        Leaflet mencari berkas ikon penanda lewat path relatif terhadap CSS-nya,
        dan path itu rusak setelah berkas dibundel. Ikon karena itu digambar
        sendiri sebagai penanda SVG agar tidak bergantung pada berkas gambar.
    */
    L.Marker.prototype.options.icon = L.divIcon({
        className: 'sim-penanda-peta',
        html:
            '<svg viewBox="0 0 24 24" width="32" height="32" fill="none" ' +
            'stroke="#163B54" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' +
            '<path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" fill="#ffffff"/>' +
            '<circle cx="12" cy="10.5" r="3" fill="#163B54"/>' +
            '</svg>',
        iconSize: [32, 32],
        iconAnchor: [16, 30],
    });

    leafletTermuat = L;

    return L;
}

/**
 * Membuat peta pemilih titik di dalam sebuah elemen.
 *
 * @param {HTMLElement} elemen Wadah peta
 * @param {object} opsi Pengaturan awal
 * @param {number|null} opsi.lintang Lintang awal
 * @param {number|null} opsi.bujur Bujur awal
 * @param {boolean} opsi.dapatDipilih True bila penanda boleh digeser
 * @param {Function} opsi.saatPindah Dipanggil dengan (lintang, bujur)
 * @returns {Promise<object>} Peta beserta pembantunya
 */
export async function buatPetaPemilih(elemen, opsi = {}) {
    const L = await muatLeaflet();

    // Titik awal jatuh ke pusat kawasan Kobalima Timur bila koordinat belum
    // terisi, agar petugas tidak dilempar ke tengah samudra.
    const lintangAwal = Number(opsi.lintang) || -9.5123450;
    const bujurAwal = Number(opsi.bujur) || 124.9125000;
    const adaTitik = Boolean(Number(opsi.lintang) && Number(opsi.bujur));

    const peta = L.map(elemen, {
        center: [lintangAwal, bujurAwal],
        zoom: adaTitik ? 17 : 13,
        scrollWheelZoom: true,
    });

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; Kontributor OpenStreetMap',
    }).addTo(peta);

    const penanda = L.marker([lintangAwal, bujurAwal], {
        draggable: Boolean(opsi.dapatDipilih),
    }).addTo(peta);

    const laporkan = (posisi) => {
        if (typeof opsi.saatPindah === 'function') {
            opsi.saatPindah(posisi.lat.toFixed(7), posisi.lng.toFixed(7));
        }
    };

    if (opsi.dapatDipilih) {
        penanda.on('dragend', () => laporkan(penanda.getLatLng()));

        // Klik di mana pun memindahkan penanda. Menggeser saja tidak cukup:
        // pada layar sentuh, memindahkan penanda jauh lebih sulit daripada
        // sekadar menyentuh titik yang dituju.
        peta.on('click', (peristiwa) => {
            penanda.setLatLng(peristiwa.latlng);
            laporkan(peristiwa.latlng);
        });
    }

    return {
        peta,
        penanda,

        /** Memindahkan penanda dari luar, misalnya setelah GPS diambil. */
        pindahkan(lintang, bujur) {
            const titik = [Number(lintang), Number(bujur)];
            penanda.setLatLng(titik);
            peta.setView(titik, 17);
        },

        /** Menghitung ulang ukuran setelah wadahnya ditampilkan. */
        segarkan() {
            peta.invalidateSize();
        },

        /** Melepas peta agar tidak menumpuk saat modal dibuka berulang. */
        musnahkan() {
            peta.remove();
        },
    };
}

export default { buatPetaPemilih };
