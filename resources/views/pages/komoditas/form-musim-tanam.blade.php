{{--
    Isian musim tanam.

    Nama dan tahun disimpan TERPISAH, bukan sebagai satu teks bebas seperti
    "MT1 2026", agar grafik per tahun dapat dihitung tanpa mengurai teks
    (agents/tasklist.md Task 2.17).

    Nama kolom mengikuti agents/data-dictionary.md bagian 5.3.
--}}
@php
    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
@endphp

<div class="space-y-6"
    x-data="{
        nama: @js(old('nama', $data['nama'] ?? '')),
        tahun: @js((string) old('tahun', $data['tahun'] ?? '')),
    }">

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $awalan }}_nama_musim" class="{{ $kelasLabel }}">Nama Musim<span class="text-error-500">*</span></label>
            <input type="text" id="{{ $awalan }}_nama_musim" name="nama" required x-model="nama"
                maxlength="20" placeholder="Contoh: MT1" class="{{ $kelasKontrol }}" />
        </div>

        <div>
            <label for="{{ $awalan }}_tahun_musim" class="{{ $kelasLabel }}">Tahun<span class="text-error-500">*</span></label>
            <input type="number" id="{{ $awalan }}_tahun_musim" name="tahun" required x-model="tahun"
                min="2000" max="{{ (int) date('Y') + 2 }}" class="{{ $kelasKontrol }} tabular-nums" />
        </div>
    </div>

    {{--
        Pratinjau label gabungan. Nama dan tahun tetap tersimpan terpisah;
        gabungan ini hanya tampilan agar petugas melihat hasil akhirnya.
    --}}
    <div x-show="nama && tahun" x-cloak x-transition class="rounded-lg bg-gray-50 p-4 dark:bg-white/[0.03]">
        <p class="text-theme-xs text-gray-600 dark:text-gray-400">
            Akan tampil sebagai
            <span class="font-medium text-gray-800 dark:text-white/90" x-text="nama + ' ' + tahun"></span>
            pada daftar dan grafik. Nama dan tahun tetap tersimpan sebagai dua kolom terpisah agar rekap
            per tahun dapat dihitung langsung.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $awalan }}_tanggal_mulai" class="{{ $kelasLabel }}">Tanggal Mulai</label>
            <input type="date" id="{{ $awalan }}_tanggal_mulai" name="tanggal_mulai"
                value="{{ old('tanggal_mulai', $data['tanggal_mulai'] ?? '') }}" class="{{ $kelasKontrol }}" />
        </div>

        <div>
            <label for="{{ $awalan }}_tanggal_selesai" class="{{ $kelasLabel }}">Tanggal Selesai</label>
            <input type="date" id="{{ $awalan }}_tanggal_selesai" name="tanggal_selesai"
                value="{{ old('tanggal_selesai', $data['tanggal_selesai'] ?? '') }}" class="{{ $kelasKontrol }}" />
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Musim tanam dapat melintasi pergantian tahun, misalnya November sampai Mei.
            </p>
        </div>
    </div>

    <div>
        <label for="{{ $awalan }}_keterangan_musim" class="{{ $kelasLabel }}">Keterangan</label>
        <textarea id="{{ $awalan }}_keterangan_musim" name="keterangan" rows="2" maxlength="255"
            placeholder="Contoh: Musim hujan, penanaman utama jagung."
            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
    </div>
</div>
