{{--
    Global Footer aplikasi SIM Transmigrasi (Tata Letak CMS / Admin).

    Dirancang ramping (slim bar) 1 baris agar tidak membebani tampilan kerja
    petugas internal, menyajikan hak cipta resmi, status versi prototipe,
    dan atribusi lisensi template TailAdmin (MIT).
--}}
<footer class="cetak-sembunyi mt-8 border-t border-gray-200 py-4 dark:border-gray-800">
    <div class="mx-auto flex max-w-(--breakpoint-2xl) flex-col items-center justify-between gap-2 px-4 text-theme-xs text-gray-500 sm:flex-row sm:px-6 lg:px-8 dark:text-gray-400">
        <p>
            &copy; {{ date('Y') }} Kementerian Transmigrasi RI &amp; Pemerintah Kabupaten Malaka. Dikembangkan bersama ITS Surabaya.
        </p>
        <div class="flex items-center gap-3 text-[11px] text-gray-400 dark:text-gray-500">
            <span>v0.8.4-alpha (Tahap 2)</span>
            <span>&bull;</span>
            <span>Fondasi TailAdmin (MIT)</span>
        </div>
    </div>
</footer>
