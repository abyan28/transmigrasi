{{--
    Halaman ubah kata sandi atas keinginan sendiri.

    Berbeda dari halaman wajib ganti kata sandi (ganti-kata-sandi.blade.php)
    yang muncul dipaksa sistem, halaman ini dibuka sendiri dari menu profil,
    sehingga kata sandi lama tetap diminta sebagai pemeriksaan pemilik akun.

    Pengguna yang lupa kata sandi memiliki dua jalur pemulihan: kode
    verifikasi lewat email, atau menghubungi admin bila jaringan di
    lokus tidak memadai (agents/rules.md bagian 14b poin 7 sampai 12).

    Aturan kata sandi mengikuti app/Support/ValidationRules::password():
    minimal 8 karakter serta memuat huruf dan angka.
--}}
@extends('layouts.app')

@section('content')
    <x-sim.page-header judul="Ubah Kata Sandi"
        keterangan="Ganti kata sandi akun Anda secara berkala agar data kawasan tetap aman."
        :remah="\App\Helpers\RemahHelper::untuk('/profil', 'Ubah Kata Sandi')" />

    <div class="max-w-2xl">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <form method="POST" action="{{ route('profil.kata-sandi.simpan') }}" class="space-y-5"
                x-data="{ baru: '', konfirmasi: '' }">
                @csrf
                @method('PUT')

                <x-sim.input-kata-sandi nama="password_lama" label="Kata Sandi Saat Ini"
                    autocomplete="current-password" :wajib="true"
                    keterangan="Diperlukan untuk memastikan perubahan dilakukan pemilik akun." />

                <x-sim.input-kata-sandi nama="password" label="Kata Sandi Baru" autocomplete="new-password"
                    :wajib="true" x-model="baru"
                    keterangan="Minimal 8 karakter, memuat huruf dan angka." />

                <x-sim.input-kata-sandi nama="password_confirmation" label="Ulangi Kata Sandi Baru"
                    autocomplete="new-password" :wajib="true" x-model="konfirmasi" />

                {{--
                    Pemeriksaan sisi klien menemani validasi server, bukan
                    menggantikannya (agents/rules.md bagian 14 poin 3).
                --}}
                <p x-show="konfirmasi.length > 0 && baru !== konfirmasi" x-cloak
                    class="text-theme-xs text-error-500">
                    Kedua kata sandi belum sama.
                </p>

                <div class="rounded-lg bg-gray-50 p-3.5 dark:bg-white/[0.03]">
                    <p class="text-theme-xs text-gray-600 dark:text-gray-400">
                        Setelah kata sandi diganti, Anda tetap masuk di perangkat ini.
                        Bila lupa kata sandi di kemudian hari, minta kode verifikasi lewat halaman
                        <a href="{{ route('lupa-kata-sandi') }}"
                            class="font-medium text-brand-500 underline hover:text-brand-600">lupa kata sandi</a>,
                        atau hubungi admin bila jaringan di lokus Anda sedang tidak memadai.
                    </p>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('profil') }}"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-center text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Batal
                    </a>
                    <button type="submit"
                        class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        Simpan Kata Sandi Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
