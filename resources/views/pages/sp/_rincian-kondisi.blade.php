{{--
    Rincian kondisi per unit (Putaran 7), untuk fasilitas_sp dan inventaris_sp.

    Muncul HANYA bila jumlah > 1. "Dua dari tiga pos lapuk" jadi angka, bukan
    kalimat di keterangan. Tetap per jenis, bukan per unit: pos ke-2 tak dapat
    dibedakan dari pos ke-3.

    Kolom `kondisi` yang di atas tetap ada sebagai penilaian umum petugas
    (lencana daftar, cacah "perlu perbaikan"). Rincian ini melengkapinya.

    Parameter:
      $awalan          — awalan id isian (tambah / ubah / ubahBaris)
      $opsiKondisi      — peta nilai kondisi (data master)
      $rincianAwal      — [kondisi => jumlah] dari data lama, atau []
      $jumlahFieldId    — id lengkap isian jumlah pada form induk
      $satuanLabel      — "unit" / "buah" / dsb, untuk teks
--}}
@php
    $rincianAwal = $rincianAwal ?? [];
    $satuanLabel = $satuanLabel ?? 'unit';
@endphp

<div class="sm:col-span-2"
    x-data="{
        opsi: @js(array_keys($opsiKondisi)),
        rincian: @js((object) $rincianAwal),
        jumlah: 0,

        init() {
            const el = document.getElementById(@js($jumlahFieldId));
            if (! el) return;
            this.jumlah = Number(el.value) || 0;
            el.addEventListener('input', () => { this.jumlah = Number(el.value) || 0; });
            this.opsi.forEach((k) => { if (this.rincian[k] === undefined) this.rincian[k] = 0; });
        },

        get total() {
            return this.opsi.reduce((s, k) => s + (Number(this.rincian[k]) || 0), 0);
        },
        get cocok() { return this.total === this.jumlah; },
    }"
    x-show="jumlah > 1" x-cloak>

    <span class="{{ $kelasLabel ?? 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400' }}">Rincian Kondisi per Unit</span>

    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($opsiKondisi as $nilaiKondisi => $labelKondisi)
                <div class="flex items-center justify-between gap-3">
                    <label :for="@js($awalan . '_rincian_' . \Illuminate\Support\Str::slug($nilaiKondisi))"
                        class="text-theme-sm text-gray-600 dark:text-gray-400">{{ $nilaiKondisi }}</label>
                    <input type="number"
                        :id="@js($awalan . '_rincian_' . \Illuminate\Support\Str::slug($nilaiKondisi))"
                        name="rincian_kondisi[{{ $nilaiKondisi }}]"
                        x-model.number="rincian[@js($nilaiKondisi)]"
                        min="0" step="1"
                        class="h-10 w-24 rounded-lg border border-gray-300 bg-transparent px-3 text-right text-theme-sm tabular-nums text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90" />
                </div>
            @endforeach
        </div>

        <p class="mt-3 text-theme-xs" :class="cocok ? 'text-gray-500 dark:text-gray-400' : 'text-error-500'">
            Jumlah rincian: <span class="tabular-nums font-medium" x-text="total"></span>
            dari <span class="tabular-nums font-medium" x-text="jumlah"></span> {{ $satuanLabel }}.
            <span x-show="! cocok">Belum sama dengan jumlah total.</span>
        </p>
    </div>
</div>
