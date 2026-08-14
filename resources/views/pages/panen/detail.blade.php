{{--
    Rincian satu catatan hasil panen.

    Menampilkan volume dalam satuan aslinya beserta setara tonnya, agar
    operator melihat keduanya sekaligus: angka yang ia catat di lapangan, dan
    angka yang dipakai sistem saat merekap.
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;
        $setaraTon = DummyData::keTon($data['volume'], $data['satuan']);
        $nilaiJual = ($data['harga_jual'] ?? 0) * $data['volume'];

        $petani = collect(DummyData::transmigran())->firstWhere('nama_kepala_keluarga', $data['petani']);

        $bolehUbah = true;
    @endphp

    <x-sim.page-header :judul="'Panen ' . $data['komoditas']"
        :keterangan="'Dipanen ' . \Illuminate\Support\Carbon::parse($data['tanggal_panen'])->translatedFormat('d F Y') . ' oleh ' . $data['petani'] . '.'"
        :remah="[
            ['label' => 'Pertanian'],
            ['label' => 'Hasil Panen', 'url' => route('panen.index')],
            ['label' => $data['komoditas']],
        ]">
        <x-slot:aksi>
            @if ($bolehUbah)
                <button type="button" @click="$dispatch('buka-modal', 'formUbahPanen')"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                    </svg>
                    Ubah Catatan Panen
                </button>
            @endif
        </x-slot:aksi>
    </x-sim.page-header>

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <p class="motif-judul-kartu text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Volume Panen
                </p>
                <p class="mt-1 text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                    {{ number_format($data['volume'], 3, ',', '.') }}
                    <span class="text-theme-sm font-normal text-gray-500 dark:text-gray-400">{{ $data['satuan'] }}</span>
                </p>
                @if ($data['satuan'] !== 'Ton')
                    <p class="mt-1 text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                        Setara {{ number_format($setaraTon, 3, ',', '.') }} ton saat direkap
                    </p>
                @endif

                <div class="mt-4">
                </div>

                <dl class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-theme-sm dark:border-gray-800">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Petani</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            @if ($petani)
                                <a href="{{ route('transmigran.detail', $petani['id_transmigran']) }}"
                                    class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                    {{ $data['petani'] }}
                                </a>
                            @else
                                {{ $data['petani'] }}
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Satuan permukiman</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">
                            <a href="{{ route('dashboard.sp', $data['satuan_permukiman_id']) }}"
                                class="rounded text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">
                                {{ $data['satuan_permukiman'] }}
                            </a>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Musim tanam</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['musim_tanam'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Kualitas</dt>
                        <dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $data['kualitas'] }}</dd>
                    </div>
                </dl>
            </div>
        </aside>

        <div class="min-w-0">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Rincian Panen</h2>

                <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Tanggal panen</dt>
                        <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                            {{ \Illuminate\Support\Carbon::parse($data['tanggal_panen'])->translatedFormat('d F Y') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Harga jual per satuan</dt>
                        <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            @if (! empty($data['harga_jual']))
                                Rp {{ number_format($data['harga_jual'], 0, ',', '.') }} per {{ $data['satuan'] }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Perkiraan nilai jual</dt>
                        <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            @if ($nilaiJual > 0)
                                Rp {{ number_format($nilaiJual, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Setara ton</dt>
                        <dd class="mt-0.5 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                            {{ number_format($setaraTon, 3, ',', '.') }} ton
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Keterangan satuan lokal</dt>
                        <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                            {{ $data['keterangan_satuan_lokal'] ?? '-' }}
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">Keterangan</dt>
                        <dd class="mt-0.5 text-theme-sm text-gray-800 dark:text-white/90">
                            {{ $data['keterangan'] ?? '-' }}
                        </dd>
                    </div>
                </dl>

                <p class="mt-6 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                    Volume disimpan apa adanya sesuai satuan baku komoditas.
                    Konversi ke ton hanya dilakukan saat rekap, sehingga angka asli lapangan tetap terjaga.
                </p>
            </div>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahPanen" judul="Ubah Catatan Panen"
            keterangan="Perubahan tercatat pada audit log."
            :aksi="route('panen.perbarui', $data['id_hasil_panen'])" metode="PUT" ukuran="xl"
            label-simpan="Simpan Perubahan">
            @include('pages.panen.form', ['data' => $data, 'awalan' => 'ubah'])
        </x-sim.modal-form>
    @endif
@endsection
