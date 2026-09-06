@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative z-1 flex min-h-screen items-center justify-center bg-gray-50 p-6 dark:bg-gray-900">
        <div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03] sm:p-8">
            <h1 class="text-title-sm font-semibold text-gray-800 dark:text-white/90">Konfirmasi Email Baru</h1>

            @if (! $valid)
                <p class="mt-4 rounded-lg border border-error-200 bg-error-50 p-4 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-300">
                    {{ $invalidMessage }}
                </p>
                <a href="{{ route('login') }}" class="mt-6 inline-flex font-medium text-brand-500 hover:text-brand-600">Kembali ke halaman masuk</a>
            @else
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                    Konfirmasikan <strong>{{ $newEmail }}</strong> sebagai email login baru. Membuka halaman ini belum mengubah email akun Anda.
                </p>

                @error('token')
                    <p class="mt-4 rounded-lg border border-error-200 bg-error-50 p-4 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-300">{{ $message }}</p>
                @enderror

                <form method="POST" action="{{ route('email-change.confirm', ['token' => $token]) }}" class="mt-6 space-y-5">
                    @csrf

                    @if ($mustSetPassword)
                        @if ($mustSetUsername)
                            <div>
                                <label for="username" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Username Permanen<span class="text-error-500">*</span></label>
                                <input id="username" name="username" type="text" value="{{ old('username') }}" required autocomplete="username" pattern="[a-z0-9._]{3,50}" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                                @error('username')<p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                            </div>
                        @endif

                        <x-sim.input-kata-sandi nama="password" label="Kata Sandi Permanen" autocomplete="new-password" :wajib="true" keterangan="Minimal 8 karakter, memuat huruf dan angka." />
                        <x-sim.input-kata-sandi nama="password_confirmation" label="Ulangi Kata Sandi Permanen" autocomplete="new-password" :wajib="true" />
                    @endif

                    <button type="submit" class="flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        {{ $mustSetPassword ? 'Simpan dan Konfirmasi Email' : 'Konfirmasi Email Baru' }}
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
