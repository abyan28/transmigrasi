{{--
    Musim tanam.

    Nama dan tahun disimpan terpisah, bukan sebagai teks bebas, karena grafik
    volume panen per tahun mustahil dihitung dari teks
    (agents/erd.md bagian 8.2 nomor 22).
--}}
@extends('layouts.app')

@section('content')
    @php
        use App\Support\DummyData;

        $semua = DummyData::musimTanam();
        $cari = trim((string) request('cari', ''));

        $baris = array_values(array_filter($semua, fn ($m) => $cari === ''
            || str_contains(mb_strtolower($m['label']), mb_strtolower($cari))));
    @endphp

    <x-sim.halaman-daftar judul="Musim Tanam"
        keterangan="Periode tanam yang menjadi dasar pengelompokan hasil panen."
        :remah="[['label' => 'Pertanian'], ['label' => 'Musim Tanam']]"
        :jumlah="count($baris)" :kata-kunci="$cari" :aksi-url="route('musim-tanam')"
        placeholder-cari="Cari musim tanam" judul-kosong="Belum ada musim tanam"
        pesan-kosong="Periode musim tanam akan tampil di sini setelah ditetapkan.">

        <x-slot:kepala>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Musim Tanam</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tahun</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Mulai</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Selesai</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Penanaman</th>
            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Keterangan</th>
        </x-slot:kepala>

        @foreach ($baris as $m)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $m['label'] }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">{{ $m['tahun'] }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    {{ \Illuminate\Support\Carbon::parse($m['tanggal_mulai'])->translatedFormat('d M Y') }}</td>
                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                    {{ \Illuminate\Support\Carbon::parse($m['tanggal_selesai'])->translatedFormat('d M Y') }}</td>
                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {{ $m['jumlah_tanam'] }} catatan</td>
                <td class="px-5 py-3 text-theme-xs text-gray-500 dark:text-gray-400">{{ $m['keterangan'] ?? '-' }}</td>
            </tr>
        @endforeach

        <x-slot:kartu>
            @foreach ($baris as $m)
                <div class="p-4">
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $m['label'] }}</p>
                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        {{ \Illuminate\Support\Carbon::parse($m['tanggal_mulai'])->translatedFormat('d M Y') }}
                        sampai
                        {{ \Illuminate\Support\Carbon::parse($m['tanggal_selesai'])->translatedFormat('d M Y') }}
                    </p>
                </div>
            @endforeach
        </x-slot:kartu>
    </x-sim.halaman-daftar>
@endsection
