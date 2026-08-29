{{--
    Satu tabel dokumen untuk bagian perluasan Laporan Monografi SP (Putaran 6).

    Menerima deskriptor dari LaporanData::tabelDok():
      $t = ['judul', 'kolom' => [...], 'baris' => [[...]], 'total' => [...]|null,
            'catatan' => string|null, 'kosong' => bool]

    Tiap tabel WAJIB: kelas .tabel-dokumen, <caption> sebagai anak pertama,
    dan pembungkus overflow-x-auto (penjaga uji Halaman).
--}}
<div class="mt-3 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
    <table class="tabel-dokumen min-w-full text-theme-sm">
        <caption class="px-4 py-2.5 text-left text-theme-xs font-medium text-gray-600 dark:text-gray-400">
            {{ $t['judul'] }}
        </caption>
        <thead class="border-y border-gray-200 bg-gray-50 text-theme-xs text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
            <tr>
                @foreach ($t['kolom'] as $k)
                    <th scope="col" class="px-4 py-2 {{ $loop->first ? 'text-left' : 'text-right' }}">{{ $k }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($t['baris'] as $baris)
                <tr class="text-gray-700 dark:text-gray-300">
                    @foreach ($baris as $sel)
                        <td class="px-4 py-2 {{ $loop->first ? 'font-medium text-gray-800 dark:text-white/90' : 'text-right tabular-nums' }}">
                            {{ $sel === null || $sel === '' ? '-' : $sel }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($t['kolom']) }}" class="px-4 py-4 text-center text-theme-xs text-gray-500 dark:text-gray-400">
                        belum ada data
                    </td>
                </tr>
            @endforelse
            @if (! empty($t['total']))
                <tr class="motif-baris-total bg-gray-100 text-gray-900 dark:bg-white/[0.06] dark:text-white">
                    @foreach ($t['total'] as $sel)
                        <td class="px-4 py-2 {{ $loop->first ? 'font-semibold' : 'text-right font-semibold tabular-nums' }}">
                            {{ $sel === null || $sel === '' ? '' : $sel }}
                        </td>
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>
</div>
@if (! empty($t['catatan']))
    <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $t['catatan'] }}</p>
@endif
