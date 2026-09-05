<div class="relative" x-data="{ dropdownOpen: false }" @click.away="dropdownOpen = false"
    @keydown.escape.window="dropdownOpen = false">
    <button type="button" @click="dropdownOpen = !dropdownOpen"
        class="relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        aria-label="Notifikasi{{ $belumDibaca ? ', '.$belumDibaca.' belum dibaca' : '' }}"
        :aria-expanded="dropdownOpen">
        @if ($belumDibaca)
            <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-error-500 px-1 text-[10px] font-semibold leading-5 text-white"
                aria-hidden="true">{{ min($belumDibaca, 99) }}</span>
        @endif
        <svg class="h-5 w-5 fill-current" viewBox="0 0 20 20" aria-hidden="true">
            <path fill-rule="evenodd" d="M10.75 2.292A.75.75 0 009.25 2.292v.544a6.375 6.375 0 00-5.625 6.331v5.292h-.292a.75.75 0 000 1.5h13.334a.75.75 0 000-1.5h-.292V9.167a6.375 6.375 0 00-5.625-6.331v-.544zm4.125 12.167V9.167a4.875 4.875 0 00-9.75 0v5.292h9.75zM8 17.708a.75.75 0 00.75.75h2.5a.75.75 0 000-1.5h-2.5a.75.75 0 00-.75.75z" clip-rule="evenodd" />
        </svg>
    </button>

    <div x-show="dropdownOpen" x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="absolute -right-[240px] mt-4 flex max-h-[480px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark sm:w-[361px] lg:right-0">
        <div class="mb-3 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
            <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">Notifikasi</h5>
            @if ($belumDibaca)
                <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                    @csrf
                    @method('PUT')
                    <button class="text-theme-xs font-medium text-brand-600 hover:underline dark:text-brand-400">
                        Tandai semua dibaca
                    </button>
                </form>
            @endif
        </div>

        <ul class="overflow-y-auto">
            @forelse ($notifikasi as $item)
                <li class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                    <form method="POST" action="{{ route('notifikasi.baca', $item->id_notifikasi) }}">
                        @csrf
                        @method('PUT')
                        <button class="flex w-full gap-3 rounded-lg px-3 py-3 text-left hover:bg-gray-50 dark:hover:bg-white/5">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $item->dibaca_at ? 'bg-gray-300 dark:bg-gray-700' : 'bg-brand-500' }}"></span>
                            <span class="min-w-0">
                                <span class="block text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $item->jenis->label() }}</span>
                                <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">{{ $item->pesan }}</span>
                                <span class="mt-1 block text-[11px] text-gray-400">{{ $item->created_at->diffForHumans() }}</span>
                            </span>
                        </button>
                    </form>
                </li>
            @empty
                <li class="px-4 py-10 text-center text-theme-sm text-gray-500 dark:text-gray-400">
                    Belum ada notifikasi
                </li>
            @endforelse
        </ul>

        <a href="{{ route('notifikasi.index') }}"
            class="mt-3 rounded-lg border border-gray-200 px-3 py-2 text-center text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
            Lihat semua notifikasi
        </a>
    </div>
</div>
