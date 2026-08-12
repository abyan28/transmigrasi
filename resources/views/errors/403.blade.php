{{--
    Halaman 403, tanpa izin.

    Salah satu dari lima keadaan yang wajib ditangani setiap halaman
    (agents/ui-spec.md bagian 7). Muncul ketika pengguna membuka alamat yang
    memang ada, tetapi berada di luar kewenangan rolenya.

    Menyembunyikan menu tidak menggantikan pemeriksaan izin di controller dan
    query; halaman inilah yang menyambut pengguna yang mengetik alamat
    langsung (agents/ui-spec.md bagian 5.2 poin 3).

    Pesannya sengaja menyebut jalan keluar yang nyata, yaitu menghubungi admin,
    karena role dan izin hanya dapat diubah admin (agents/rules.md bagian 5).
--}}
@extends('layouts.fullscreen-layout')

@section('content')
    <x-sim.halaman-galat kode="403" judul="Anda tidak memiliki akses ke halaman ini"
        pesan="Halaman ini hanya dapat dibuka petugas dengan kewenangan tertentu. Bila Anda memerlukan aksesnya, hubungi admin untuk menyesuaikan hak akses akun Anda.">
        <x-slot:aksi>
            <a href="{{ route('beranda') }}"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-3 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                Kembali ke Dashboard
            </a>
        </x-slot:aksi>
    </x-sim.halaman-galat>

    {{-- Keterangan tambahan: apa yang perlu disampaikan saat menghubungi admin --}}
    <div class="fixed inset-x-0 bottom-20 mx-auto max-w-md px-6">
        <p class="rounded-lg bg-gray-50 p-3.5 text-center text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
            Saat menghubungi admin, sebutkan halaman yang ingin Anda buka
            agar izin yang diberikan tepat sasaran.
        </p>
    </div>
@endsection
