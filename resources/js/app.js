import './bootstrap';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';

// flatpickr dipakai untuk seluruh input tanggal
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

// Konfigurasi grafik bersama: warna palet Kementerian, format angka Indonesia,
// dan penggambaran ulang saat mode tema berganti
import grafik from './chart-config';

// Locale flatpickr disetel ke Bahasa Indonesia mengikuti locale aplikasi
flatpickr.localize(Indonesian);

window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
window.flatpickr = flatpickr;

// Diekspos ke window agar dapat dipanggil dari Blade tanpa modul tambahan
window.grafikSim = grafik;

/*
    Peta pemilih titik. Leaflet TIDAK diimpor di sini, melainkan di dalam
    resources/js/peta.js yang memuatnya secara dinamis saat peta pertama kali
    dibuka. Hanya enam form yang memerlukan peta, sehingga menyertakannya pada
    bundel utama akan membebani seluruh halaman lain tanpa alasan.
*/
window.petaSim = {
    async buka(elemen, opsi) {
        const modul = await import('./peta');

        return modul.buatPetaPemilih(elemen, opsi);
    },
};

// Grafik ikut berganti warna ketika pengguna menukar mode terang dan gelap
grafik.pantauTema();

Alpine.start();
