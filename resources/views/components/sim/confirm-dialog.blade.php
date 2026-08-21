{{--
    Dialog konfirmasi untuk tindakan yang tidak dapat dibatalkan.

    Dipakai sebelum tindakan yang tidak dapat dibatalkan, terutama penghapusan
    data. Tersedia prop `perluAlasan` bagi tindakan yang menuntut keterangan
    tertulis, agar alasannya ikut tercatat pada audit log.

    Pemakaian:
        <x-sim.confirm-dialog nama="hapusTransmigran"
            judul="Hapus data transmigran?"
            pesan="Data yang dihapus masih dapat dipulihkan admin."
            label-setuju="Hapus" ragam="bahaya" />

        <button @click="$dispatch('buka-konfirmasi', { nama: 'hapusTransmigran', aksi: '/transmigran/5' })">
            Hapus
        </button>
--}}
@props([
    'nama',
    'judul',
    'pesan' => null,
    'labelSetuju' => 'Lanjutkan',
    'metode' => 'DELETE',
    'ragam' => 'bahaya',
    'perluAlasan' => false,
    'labelAlasan' => 'Alasan',
])

@php
    $gayaTombol = $ragam === 'bahaya'
        ? 'bg-red-600 hover:bg-red-700 focus:outline-red-600'
        : 'bg-brand-500 hover:bg-brand-600 focus:outline-brand-500';

    $gayaIkon = $ragam === 'bahaya'
        ? 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400'
        : 'bg-yellow-50 text-yellow-600 dark:bg-yellow-500/15 dark:text-yellow-400';
@endphp

<div x-data="{
        terbuka: false,
        aksi: '',
        mengirim: false,
        buka(detail) {
            this.aksi = detail.aksi ?? '';
            this.terbuka = true;
            window.kunciGulir?.kunci();
            this.$nextTick(() => this.$refs.tombolBatal?.focus());
        },
        tutup() {
            if (! this.terbuka) {
                return;
            }

            this.terbuka = false;
            this.mengirim = false;
            window.kunciGulir?.lepas();
        },
    }"
    x-on:buka-konfirmasi.window="if ($event.detail.nama === '{{ $nama }}') buka($event.detail)"
    x-on:keydown.escape.window="terbuka && tutup()">

    {{--
        Pola gulir sama dengan `modal-form`; lihat komentar rinci di sana.
        Dialog ini isinya pendek, tetapi tetap disamakan agar tidak menjadi
        satu-satunya lapisan yang berperilaku berbeda ketika pesannya panjang.
    --}}
    <div x-show="terbuka" x-cloak class="fixed inset-0 z-99999" role="alertdialog" aria-modal="true"
        aria-labelledby="judul-konfirmasi-{{ $nama }}">

        <div x-show="terbuka" x-transition.opacity @click="tutup()" class="fixed inset-0 bg-gray-900/50"
            aria-hidden="true"></div>

        <div class="flex h-full items-start justify-center overflow-hidden p-4">
            <div x-show="terbuka" x-transition
                class="relative my-auto flex max-h-full w-full max-w-md flex-col overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">

                <form :action="aksi" method="POST" @submit="mengirim = true">
                    @csrf
                    @method($metode)

                    <div class="flex gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $gayaIkon }}">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 id="judul-konfirmasi-{{ $nama }}"
                                class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                                {{ $judul }}
                            </h2>
                            @if ($pesan)
                                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                    {{ $pesan }}
                                </p>
                            @endif

                            @if ($perluAlasan)
                                <div class="mt-3">
                                    <label for="alasan-{{ $nama }}"
                                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                                        {{ $labelAlasan }}<span class="text-error-500">*</span>
                                    </label>
                                    <textarea id="alasan-{{ $nama }}" name="alasan" rows="3" required
                                        placeholder="Tuliskan bagian mana yang perlu diperbaiki"
                                        class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90"></textarea>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" x-ref="tombolBatal" @click="tutup()"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Batal
                        </button>
                        <button type="submit" :disabled="mengirim"
                            class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-theme-sm font-medium text-white disabled:opacity-60 focus:outline-2 focus:outline-offset-2 {{ $gayaTombol }}">
                            <span x-show="mengirim" x-cloak
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                aria-hidden="true"></span>
                            {{ $labelSetuju }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
