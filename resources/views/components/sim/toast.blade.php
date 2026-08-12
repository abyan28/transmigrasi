{{--
    Pemberitahuan singkat hasil sebuah tindakan.

    Diletakkan sekali saja di layout, lalu dipanggil dari mana pun lewat
    peristiwa Alpine. Pesan sesi dari controller juga ditampilkan otomatis.

    Memanggil dari Blade atau JavaScript:
        $dispatch('toast', { pesan: 'Data tersimpan', ragam: 'sukses' })

    Dari controller cukup:
        return back()->with('sukses', 'Data transmigran tersimpan.');
--}}
<div x-data="{
        daftar: [],
        urutan: 0,
        tambah(detail) {
            const id = ++this.urutan;
            this.daftar.push({
                id,
                pesan: detail.pesan ?? '',
                ragam: detail.ragam ?? 'info',
            });
            // Pemberitahuan hilang sendiri agar tidak menumpuk di layar.
            setTimeout(() => this.buang(id), detail.durasi ?? 5000);
        },
        buang(id) {
            this.daftar = this.daftar.filter((t) => t.id !== id);
        },
    }"
    x-on:toast.window="tambah($event.detail)"
    x-init="
        @if (session('sukses')) tambah({ pesan: @js(session('sukses')), ragam: 'sukses' }); @endif
        @if (session('galat')) tambah({ pesan: @js(session('galat')), ragam: 'galat' }); @endif
        @if (session('peringatan')) tambah({ pesan: @js(session('peringatan')), ragam: 'peringatan' }); @endif
    "
    class="pointer-events-none fixed top-4 right-4 z-999999 flex w-full max-w-sm flex-col gap-2"
    role="status" aria-live="polite">

    <template x-for="item in daftar" :key="item.id">
        <div x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-end="opacity-0 translate-x-4"
            class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-4 shadow-lg dark:bg-gray-900"
            :class="{
                'border-green-200 dark:border-green-500/30': item.ragam === 'sukses',
                'border-red-200 dark:border-red-500/30': item.ragam === 'galat',
                'border-yellow-200 dark:border-yellow-500/30': item.ragam === 'peringatan',
                'border-gray-200 dark:border-gray-800': item.ragam === 'info',
            }">

            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                :class="{
                    'bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400': item.ragam === 'sukses',
                    'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400': item.ragam === 'galat',
                    'bg-yellow-100 text-yellow-600 dark:bg-yellow-500/20 dark:text-yellow-400': item.ragam === 'peringatan',
                    'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300': item.ragam === 'info',
                }"
                aria-hidden="true">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path x-show="item.ragam === 'sukses'" stroke-linecap="round" stroke-linejoin="round"
                        d="M4.5 12.75l6 6 9-13.5" />
                    <path x-show="item.ragam === 'galat'" stroke-linecap="round" stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12" />
                    <path x-show="item.ragam === 'peringatan' || item.ragam === 'info'" stroke-linecap="round"
                        stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007M12 3l9 16H3L12 3z" />
                </svg>
            </span>

            <p class="min-w-0 flex-1 text-theme-sm text-gray-700 dark:text-gray-300" x-text="item.pesan"></p>

            <button type="button" @click="buang(item.id)" aria-label="Tutup pemberitahuan"
                class="shrink-0 rounded text-gray-400 hover:text-gray-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:hover:text-gray-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
