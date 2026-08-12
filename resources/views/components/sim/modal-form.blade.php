{{--
    Modal floating untuk formulir isian panjang.

    Ketentuan pada agents/ui-spec.md bagian 6.2:
    - ukuran sm, md, lg, xl,
    - tutup dengan Esc dan klik latar,
    - fokus terkunci di dalam modal,
    - tombol simpan nonaktif beserta pemintal selama proses kirim,
    - layar penuh pada perangkat mobile.

    Untuk modul yang memerlukan verifikasi, tersedia tombol kedua
    "Simpan dan Verifikasi" yang hanya dirender bila pengguna berizin
    (agents/rules.md bagian 5.2 poin 5).

    Pemakaian:
        <x-sim.modal-form nama="formTransmigran" judul="Tambah Transmigran"
            aksi="/transmigran" :boleh-verifikasi="true">
            ... isian ...
        </x-sim.modal-form>

    Membuka dari luar:
        <button @click="$dispatch('buka-modal', 'formTransmigran')">Tambah</button>
--}}
@props([
    'nama',
    'judul',
    'keterangan' => null,
    'aksi' => '#',
    'metode' => 'POST',
    'ukuran' => 'lg',
    'labelSimpan' => 'Simpan',
    'bolehVerifikasi' => false,
])

@php
    $lebar = [
        'sm' => 'sm:max-w-md',
        'md' => 'sm:max-w-lg',
        'lg' => 'sm:max-w-2xl',
        'xl' => 'sm:max-w-4xl',
    ][$ukuran] ?? 'sm:max-w-2xl';
@endphp

<div x-data="{
        terbuka: false,
        mengirim: false,
        buka() {
            this.terbuka = true;
            document.body.classList.add('overflow-hidden');
            this.$nextTick(() => this.$refs.panel?.querySelector('input, select, textarea')?.focus());
        },
        tutup() {
            this.terbuka = false;
            this.mengirim = false;
            document.body.classList.remove('overflow-hidden');
        },
    }"
    x-on:buka-modal.window="if ($event.detail === '{{ $nama }}') buka()"
    x-on:tutup-modal.window="tutup()"
    x-on:keydown.escape.window="terbuka && tutup()">

    <div x-show="terbuka" x-cloak class="fixed inset-0 z-99999 overflow-y-auto" role="dialog" aria-modal="true"
        aria-labelledby="judul-{{ $nama }}">

        {{-- Latar gelap, klik untuk menutup --}}
        <div x-show="terbuka" x-transition.opacity @click="tutup()"
            class="fixed inset-0 bg-gray-900/50" aria-hidden="true"></div>

        <div class="flex min-h-full items-end justify-center sm:items-center sm:p-4">
            {{-- Panel modal. Layar penuh pada mobile, melayang pada layar lebar. --}}
            <div x-ref="panel" x-show="terbuka" x-transition
                @keydown.tab="
                    const fokusable = $refs.panel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])');
                    if (fokusable.length === 0) return;
                    const pertama = fokusable[0];
                    const terakhir = fokusable[fokusable.length - 1];
                    if ($event.shiftKey && document.activeElement === pertama) { $event.preventDefault(); terakhir.focus(); }
                    else if (!$event.shiftKey && document.activeElement === terakhir) { $event.preventDefault(); pertama.focus(); }
                "
                class="relative w-full {{ $lebar }} bg-white shadow-xl sm:rounded-2xl dark:bg-gray-900">

                <form action="{{ $aksi }}" method="POST" enctype="multipart/form-data"
                    @submit="mengirim = true">
                    @csrf
                    @if (! in_array($metode, ['GET', 'POST']))
                        @method($metode)
                    @endif

                    {{-- Kepala modal --}}
                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <h2 id="judul-{{ $nama }}"
                                class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                {{ $judul }}
                            </h2>
                            @if ($keterangan)
                                <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                    {{ $keterangan }}
                                </p>
                            @endif
                        </div>
                        <button type="button" @click="tutup()" aria-label="Tutup"
                            class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-gray-400 dark:hover:bg-white/5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Isi formulir, digulir bila terlalu panjang --}}
                    <div class="max-h-[calc(100vh-16rem)] overflow-y-auto px-5 py-4">
                        {{ $slot }}
                    </div>

                    {{-- Kaki modal, tombol rata kanan --}}
                    <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 sm:flex-row sm:justify-end dark:border-gray-800">
                        <button type="button" @click="tutup()"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Batal
                        </button>

                        @if ($bolehVerifikasi)
                            {{--
                                Tombol simpan biasa menjadi sekunder ketika verifikasi tersedia,
                                agar tindakan yang lebih lengkap menjadi pilihan utama.
                            --}}
                            <button type="submit" name="tindakan" value="simpan" :disabled="mengirim"
                                class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                {{ $labelSimpan }}
                            </button>
                            <button type="submit" name="tindakan" value="simpan_verifikasi" :disabled="mengirim"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-60 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                <span x-show="mengirim" x-cloak
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                    aria-hidden="true"></span>
                                Simpan dan Verifikasi
                            </button>
                        @else
                            <button type="submit" :disabled="mengirim"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-60 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                                <span x-show="mengirim" x-cloak
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                    aria-hidden="true"></span>
                                {{ $labelSimpan }}
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
