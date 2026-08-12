{{--
    Halaman masuk sistem.

    Catatan penting:
    - Sistem TIDAK menyediakan pendaftaran mandiri. Akun hanya dibuat oleh Admin
      lewat menu Manajemen Pengguna (agents/prd.md bagian 7.1).
    - Sistem TIDAK menyediakan pemulihan kata sandi lewat email. Pengguna yang
      lupa kata sandi menghubungi Admin untuk disetel ulang.
    - Seluruh pengguna sistem adalah petugas. Warga tidak memiliki akun dan
      mengajukan pengaduan lewat halaman publik tanpa login.

    Saat ini masih berupa tampilan. Proses masuk yang sebenarnya beserta
    pembatasan percobaan berulang dikerjakan pada Tahap 3.
--}}
@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative z-1 bg-white p-6 sm:p-0 dark:bg-gray-900">
        <div class="relative flex h-screen w-full flex-col justify-center sm:p-0 lg:flex-row dark:bg-gray-900">
            {{-- Kolom kiri: formulir masuk --}}
            <div class="flex w-full flex-1 flex-col lg:w-1/2">
                <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
                    <div>
                        <div class="mb-5 sm:mb-8">
                            <h1 class="text-title-sm sm:text-title-md mb-2 font-semibold text-gray-800 dark:text-white/90">
                                Masuk ke Sistem
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Gunakan akun yang diberikan oleh admin.
                            </p>
                        </div>

                        <form>
                            <div class="space-y-5">
                                {{-- Satu kolom kredensial, menerima email maupun username --}}
                                <div>
                                    <label for="kredensial"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Email atau Username<span class="text-error-500">*</span>
                                    </label>
                                    <input type="text" id="kredensial" name="kredensial" autocomplete="username"
                                        placeholder="Masukkan email atau username"
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                                </div>

                                {{-- Kata sandi, dengan tombol perlihatkan atau sembunyikan --}}
                                <div>
                                    <label for="password"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Kata Sandi<span class="text-error-500">*</span>
                                    </label>
                                    <div x-data="{ tampilkanSandi: false }" class="relative">
                                        <input :type="tampilkanSandi ? 'text' : 'password'" id="password" name="password"
                                            autocomplete="current-password" placeholder="Masukkan kata sandi"
                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                                        <button type="button" @click="tampilkanSandi = !tampilkanSandi"
                                            :aria-label="tampilkanSandi ? 'Sembunyikan kata sandi' : 'Perlihatkan kata sandi'"
                                            class="absolute top-1/2 right-4 z-30 -translate-y-1/2 cursor-pointer rounded focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                            <svg x-show="!tampilkanSandi" class="fill-gray-500 dark:fill-gray-400"
                                                width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z"
                                                    fill="" />
                                            </svg>
                                            <svg x-show="tampilkanSandi" class="fill-gray-500 dark:fill-gray-400"
                                                width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4849 11.4718C10.3033 11.5223 10.1552 11.5504 10.0006 11.5504C8.97434 11.5504 8.1424 10.7185 8.1424 9.69223C8.1424 9.53763 8.17049 9.38959 8.22094 9.24184L6.05027 7.07117C5.19547 7.78356 4.53877 8.71472 4.16542 9.70164C5.11017 12.1364 7.47575 13.8611 10.2423 13.8611C11.0031 13.8611 11.7215 13.7016 12.3608 13.4212Z"
                                                    fill="" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Opsi tetap masuk --}}
                                <div x-data="{ tetapMasuk: false }">
                                    <label for="ingat_saya"
                                        class="flex cursor-pointer items-center text-sm font-normal text-gray-700 select-none dark:text-gray-400">
                                        <div class="relative">
                                            <input type="checkbox" id="ingat_saya" name="ingat_saya" class="sr-only"
                                                @change="tetapMasuk = !tetapMasuk" />
                                            <div :class="tetapMasuk ? 'border-brand-500 bg-brand-500' :
                                                'bg-transparent border-gray-300 dark:border-gray-700'"
                                                class="mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]">
                                                <span :class="tetapMasuk ? '' : 'opacity-0'">
                                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                        <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7"
                                                            stroke="white" stroke-width="1.94437"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                        Tetap masuk di perangkat ini
                                    </label>
                                </div>

                                {{-- Tombol masuk --}}
                                <div>
                                    <button type="submit"
                                        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex w-full items-center justify-center rounded-lg px-4 py-3 text-sm font-medium text-white transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                        Masuk
                                    </button>
                                </div>
                            </div>
                        </form>

                        {{--
                            Keterangan pemulihan akses. Sengaja berupa teks biasa, bukan tautan,
                            karena sistem tidak menyediakan halaman pemulihan mandiri.
                        --}}
                        <div class="mt-5 rounded-lg bg-gray-50 p-4 dark:bg-white/[0.03]">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium text-gray-800 dark:text-white/90">Lupa kata sandi?</span>
                                Hubungi admin desa atau SP Anda untuk menyetel ulang kata sandi.
                                Akun baru juga hanya dapat dibuat oleh admin.
                            </p>
                        </div>

                        {{--
                            Jalur warga. Halaman masuk ini khusus petugas, sedangkan warga
                            mengajukan pengaduan lewat halaman publik tanpa perlu akun.
                            Tautan sudah menunjuk halaman yang benar-benar ada (R-24, R-26).
                        --}}
                        <div class="border-t border-gray-200 pt-5 mt-5 dark:border-gray-800">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium text-gray-800 dark:text-white/90">Warga transmigrasi?</span>
                                Anda tidak memerlukan akun untuk menyampaikan pengaduan.
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('pengaduan-warga') }}"
                                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                    Kirim Pengaduan
                                </a>
                                <a href="{{ route('lacak-pengaduan') }}"
                                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                    Lacak Pengaduan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom kanan: identitas sistem, hanya tampil pada layar lebar --}}
            <div class="bg-brand-950 relative hidden h-full w-full items-center lg:grid lg:w-1/2 dark:bg-white/5">
                <div class="z-1 flex items-center justify-center">
                    <x-common.common-grid-shape />
                    {{--
                        Panel ini selalu berlatar pekat (bg-brand-950 pada mode
                        terang, putih transparan pada mode gelap), sehingga teks
                        putih benar untuk keduanya. Kelas dark:text-white
                        ditulis eksplisit agar niat itu terbaca dari kelasnya
                        sendiri, tidak perlu ditelusuri ke elemen induk.
                    --}}
                    <div class="flex max-w-xs flex-col items-center">
                        <p class="text-center text-lg font-semibold text-white dark:text-white">
                            {{ config('app.name') }}
                        </p>
                        <p class="mt-2 text-center text-sm text-gray-400 dark:text-white/60">
                            Monitoring Pertanian dan Tata Kelola Data
                            Kawasan Transmigrasi Kobalima Timur
                        </p>
                    </div>
                </div>
            </div>

            {{-- Tombol ganti mode terang atau gelap --}}
            <div class="fixed right-6 bottom-6 z-50">
                <button type="button" aria-label="Ganti mode terang atau gelap"
                    class="bg-brand-500 hover:bg-brand-600 inline-flex size-14 items-center justify-center rounded-full text-white transition-colors focus:outline-2 focus:outline-offset-2 focus:outline-brand-500"
                    @click.prevent="$store.theme.toggle()">
                    <svg class="hidden fill-current dark:block" width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M9.99998 1.5415C10.4142 1.5415 10.75 1.87729 10.75 2.2915V3.5415C10.75 3.95572 10.4142 4.2915 9.99998 4.2915C9.58577 4.2915 9.24998 3.95572 9.24998 3.5415V2.2915C9.24998 1.87729 9.58577 1.5415 9.99998 1.5415ZM10.0009 6.79327C8.22978 6.79327 6.79402 8.22904 6.79402 10.0001C6.79402 11.7712 8.22978 13.207 10.0009 13.207C11.772 13.207 13.2078 11.7712 13.2078 10.0001C13.2078 8.22904 11.772 6.79327 10.0009 6.79327ZM5.29402 10.0001C5.29402 7.40061 7.40135 5.29327 10.0009 5.29327C12.6004 5.29327 14.7078 7.40061 14.7078 10.0001C14.7078 12.5997 12.6004 14.707 10.0009 14.707C7.40135 14.707 5.29402 12.5997 5.29402 10.0001ZM15.9813 5.08035C16.2742 4.78746 16.2742 4.31258 15.9813 4.01969C15.6884 3.7268 15.2135 3.7268 14.9207 4.01969L14.0368 4.90357C13.7439 5.19647 13.7439 5.67134 14.0368 5.96423C14.3297 6.25713 14.8045 6.25713 15.0974 5.96423L15.9813 5.08035ZM18.4577 10.0001C18.4577 10.4143 18.1219 10.7501 17.7077 10.7501H16.4577C16.0435 10.7501 15.7077 10.4143 15.7077 10.0001C15.7077 9.58592 16.0435 9.25013 16.4577 9.25013H17.7077C18.1219 9.25013 18.4577 9.58592 18.4577 10.0001ZM14.9207 15.9806C15.2135 16.2735 15.6884 16.2735 15.9813 15.9806C16.2742 15.6877 16.2742 15.2128 15.9813 14.9199L15.0974 14.036C14.8045 13.7431 14.3297 13.7431 14.0368 14.036C13.7439 14.3289 13.7439 14.8038 14.0368 15.0967L14.9207 15.9806ZM9.99998 15.7088C10.4142 15.7088 10.75 16.0445 10.75 16.4588V17.7088C10.75 18.123 10.4142 18.4588 9.99998 18.4588C9.58577 18.4588 9.24998 18.123 9.24998 17.7088V16.4588C9.24998 16.0445 9.58577 15.7088 9.99998 15.7088ZM5.96356 15.0972C6.25646 14.8043 6.25646 14.3295 5.96356 14.0366C5.67067 13.7437 5.1958 13.7437 4.9029 14.0366L4.01902 14.9204C3.72613 15.2133 3.72613 15.6882 4.01902 15.9811C4.31191 16.274 4.78679 16.274 5.07968 15.9811L5.96356 15.0972ZM4.29224 10.0001C4.29224 10.4143 3.95645 10.7501 3.54224 10.7501H2.29224C1.87802 10.7501 1.54224 10.4143 1.54224 10.0001C1.54224 9.58592 1.87802 9.25013 2.29224 9.25013H3.54224C3.95645 9.25013 4.29224 9.58592 4.29224 10.0001ZM4.9029 5.9637C5.1958 6.25659 5.67067 6.25659 5.96356 5.9637C6.25646 5.6708 6.25646 5.19593 5.96356 4.90303L5.07968 4.01915C4.78679 3.72626 4.31191 3.72626 4.01902 4.01915C3.72613 4.31204 3.72613 4.78692 4.01902 5.07981L4.9029 5.9637Z"
                            fill="" />
                    </svg>
                    <svg class="fill-current dark:hidden" width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path
                            d="M17.4547 11.97L18.1799 12.1611C18.265 11.8383 18.1265 11.4982 17.8401 11.3266C17.5538 11.1551 17.1885 11.1934 16.944 11.4207L17.4547 11.97ZM8.0306 2.5459L8.57989 3.05657C8.80718 2.81209 8.84554 2.44682 8.67398 2.16046C8.50243 1.8741 8.16227 1.73559 7.83948 1.82066L8.0306 2.5459ZM12.9154 13.0035C9.64678 13.0035 6.99707 10.3538 6.99707 7.08524H5.49707C5.49707 11.1823 8.81835 14.5035 12.9154 14.5035V13.0035ZM16.944 11.4207C15.8869 12.4035 14.4721 13.0035 12.9154 13.0035V14.5035C14.8657 14.5035 16.6418 13.7499 17.9654 12.5193L16.944 11.4207ZM16.7295 11.7789C15.9437 14.7607 13.2277 16.9586 10.0003 16.9586V18.4586C13.9257 18.4586 17.2249 15.7853 18.1799 12.1611L16.7295 11.7789ZM10.0003 16.9586C6.15734 16.9586 3.04199 13.8433 3.04199 10.0003H1.54199C1.54199 14.6717 5.32892 18.4586 10.0003 18.4586V16.9586ZM3.04199 10.0003C3.04199 6.77289 5.23988 4.05695 8.22173 3.27114L7.83948 1.82066C4.21532 2.77574 1.54199 6.07486 1.54199 10.0003H3.04199ZM6.99707 7.08524C6.99707 5.52854 7.5971 4.11366 8.57989 3.05657L7.48132 2.03522C6.25073 3.35885 5.49707 5.13487 5.49707 7.08524H6.99707Z"
                            fill="" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
@endsection
