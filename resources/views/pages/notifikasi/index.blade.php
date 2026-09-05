@extends('layouts.app')

@section('content')
    <x-sim.page-header judul="Notifikasi" keterangan="Pemberitahuan yang memerlukan perhatian Anda."
        :remah="\App\Helpers\RemahHelper::untuk('/notifikasi')">
        @if ($baris->whereNull('dibaca_at')->isNotEmpty())
            <x-slot:aksi>
                <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                    @csrf
                    @method('PUT')
                    <button class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Tandai semua dibaca
                    </button>
                </form>
            </x-slot:aksi>
        @endif
    </x-sim.page-header>

    <form method="GET" action="{{ route('notifikasi.index') }}" class="mb-4 flex justify-end">
        <x-sim.pilih-per-halaman :per-halaman="$baris->perPage()" />
    </form>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        @forelse ($baris as $item)
            <form method="POST" action="{{ route('notifikasi.baca', $item->id_notifikasi) }}"
                class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                @csrf
                @method('PUT')
                <button class="flex w-full gap-4 px-5 py-4 text-left hover:bg-gray-50 dark:hover:bg-white/5">
                    <span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full {{ $item->dibaca_at ? 'bg-gray-300 dark:bg-gray-700' : 'bg-brand-500' }}"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $item->jenis->label() }}</span>
                        <span class="mt-1 block text-theme-sm text-gray-600 dark:text-gray-400">{{ $item->pesan }}</span>
                        <span class="mt-1.5 block text-theme-xs text-gray-400">{{ $item->created_at->translatedFormat('d M Y, H:i') }}</span>
                    </span>
                </button>
            </form>
        @empty
            <x-sim.empty-state judul="Belum ada notifikasi"
                pesan="Pemberitahuan pengaduan, kondisi SP, dan aktivitas akun akan tampil di sini." />
        @endforelse
    </div>

    @if ($baris->hasPages())
        <div class="mt-4">{{ $baris->onEachSide(1)->links() }}</div>
    @endif
@endsection
