{{--
    Rincian pembentuk status kondisi SP.

    Label WAJIB disertai rincian penyebabnya (agents/rules.md bagian 10c.1
    poin 4). Tanpa rincian, label berhenti sebagai stempel: petugas tahu
    sebuah SP bermasalah, tetapi tidak tahu apa yang harus diperbaiki.

    Parameter dikelompokkan menurut tingkat kebutuhan agar terbaca mana yang
    mendesak. Parameter primer yang tidak tersedia ditandai paling tegas,
    sebab satu saja sudah menentukan status SP.

    Pemakaian:
        <x-sim.rincian-kondisi-sp :penilaian="$penilaian" />
--}}
@props([
    'penilaian',
    'ringkas' => false,
])

@php
    use App\Enums\TingkatKebutuhan;
    use App\Support\PenilaianKondisiSp;

    $status = $penilaian['status'];

    // Dikelompokkan per tingkat agar yang mendesak terbaca lebih dulu
    $perTingkat = [];
    foreach ($penilaian['rincian'] as $r) {
        $perTingkat[$r['tingkat']][] = $r;
    }

    $warnaNilai = function (float $nilai): string {
        return match (true) {
            $nilai >= 1.0 => 'text-green-700 dark:text-green-400',
            $nilai >= 0.5 => 'text-yellow-700 dark:text-yellow-400',
            $nilai > 0 => 'text-red-700 dark:text-red-400',
            default => 'text-gray-500 dark:text-gray-400',
        };
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]']) }}>
    {{-- Kepala: status, skor, dan tanggal penilaian --}}
    <div class="border-b border-gray-200 p-5 dark:border-gray-800">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Kondisi Layanan Dasar</h3>
                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                    {{ $status->keterangan() }}
                </p>
            </div>
            <div class="text-right">
                <x-sim.status-badge :status="$status" />
                <p class="mt-1.5 text-title-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                    {{ number_format($penilaian['skor'], 2, ',', '.') }}
                    <span class="text-theme-xs font-normal text-gray-500 dark:text-gray-400">dari 100</span>
                </p>
            </div>
        </div>

        {{--
            Peringatan aturan primer nol. Ditulis tegas karena status SP ini
            ditentukan aturan tersebut, bukan oleh skornya.
        --}}
        @if ($penilaian['ada_primer_nol'])
            <div class="mt-4 flex items-start gap-3 rounded-xl border border-red-300 bg-red-50 p-3.5 dark:border-red-500/30 dark:bg-red-500/10"
                role="alert">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <p class="text-theme-xs text-red-800 dark:text-red-200">
                    <span class="font-semibold">Ada layanan dasar yang belum tersedia.</span>
                    Status ditetapkan Perlu Penanganan tanpa memandang skor, sebab layanan dasar
                    menentukan kelayakan huni.
                </p>
            </div>
        @endif

        <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
            Dinilai
            {{ \Illuminate\Support\Carbon::parse($penilaian['tanggal_penilaian'])->translatedFormat('d F Y') }}
        </p>
    </div>

    {{-- Rincian per tingkat kebutuhan --}}
    <div class="divide-y divide-gray-200 dark:divide-gray-800">
        @foreach (TingkatKebutuhan::cases() as $tingkat)
            @php $daftar = $perTingkat[$tingkat->value] ?? []; @endphp

            @if (! empty($daftar))
                <div class="p-5">
                    <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                        <h4 class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ $tingkat->label() }}
                            <span class="ml-1 font-normal normal-case tracking-normal">
                                (bobot {{ $tingkat->bobotBawaan() }})
                            </span>
                        </h4>
                        <span class="text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $tingkat->keterangan() }}
                        </span>
                    </div>

                    <ul class="space-y-2">
                        @foreach ($daftar as $r)
                            @php $tidakAda = $r['nilai'] == 0; @endphp
                            <li class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 {{ $tidakAda && $tingkat === TingkatKebutuhan::Primer ? 'bg-red-50 dark:bg-red-500/10' : 'bg-gray-50 dark:bg-white/[0.02]' }}">
                                <span class="min-w-0 text-theme-sm text-gray-800 dark:text-white/90">
                                    {{ $r['nama'] }}
                                </span>
                                <span class="shrink-0 text-theme-xs font-medium {{ $warnaNilai($r['nilai']) }}">
                                    {{ $r['kondisi'] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>

    @unless ($ringkas)
        <div class="border-t border-gray-200 p-5 dark:border-gray-800">
            <p class="text-theme-xs text-gray-600 dark:text-gray-400">
                Penilaian ini menilai ketersediaan dan kondisi infrastruktur serta fasilitas,
                bukan penghuninya. Bobot tiap tingkat dapat disesuaikan admin, dan setiap penilaian
                menyimpan salinan bobot yang berlaku saat itu agar laporan lama tetap sahih.
            </p>
        </div>
    @endunless
</div>
