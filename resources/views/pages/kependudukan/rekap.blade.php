{{--
    Rekap kependudukan kawasan.

    Memakai komposisi halaman rekap: tabel agregat dengan baris total
    ditegaskan, tanpa kartu statistik (agents/ui-spec.md bagian 2.2).

    Menyajikan KK masuk dan keluar per tahun sesuai kewajiban pada
    agents/rules.md bagian 10a poin 4.
--}}
@extends('layouts.app')

@section('content')
    {{--
        Seluruh isian halaman ini datang dari closure `susunRekapKependudukan`
        pada routes/web.php, yang dipakai bersama rute `kependudukan.rekap` dan
        `kependudukan.rekap.kelompok`.

        Dasar pengelompokan datang dari dua arah: segmen rute yang menjadi
        tautan tetap, dan kueri `?kelompok=` milik tautan lama. Yang pertama
        membuat seluruh tab tetap dapat dibuka pada build statis.

        Sebelum 2026-08-25 hanya kueri yang ada, sehingga di situs terbit HANYA
        tab Tahun yang terbuka - lima tab lain tidak dapat dicapai sama sekali.
        Cacat yang sama pernah ditemukan pada rekap panen (notes.md 1b.6a) dan
        diperbaiki, tetapi kependudukan terlewat.
    --}}

    <x-sim.page-header judul="Rekap Kependudukan"
        keterangan="Perkembangan jumlah penduduk kawasan beserta perpindahannya."
        :remah="\App\Helpers\RemahHelper::untuk('/kependudukan/rekap')" />

    {{--
        Tombol ekspor dicabut 2026-08-28 (rules.md 12 poin 7). Laporan
        kependudukan kini dokumen bernama di menu "Laporan".
    --}}

    <nav aria-label="Dasar pengelompokan rekap"
        class="mb-6 flex flex-wrap gap-2 rounded-2xl border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-white/[0.03]">
        @foreach ($labelKelompok as $nilai => $label)
            @php
                $aktif = $kelompok === $nilai;
                $queryTab = ($nilai !== 'tahun' && $tahunPilihan !== $tahunTerakhir) ? ['tahun' => $tahunPilihan] : [];
            @endphp
            {{-- Tautan tetap per tab, membawa parameter tahun bila sedang menyaring tahun tertentu --}}
            <a href="{{ route('kependudukan.rekap.kelompok', array_merge(['kelompok' => $nilai], $queryTab)) }}"
                @if ($aktif) aria-current="page" @endif
                class="rounded-lg px-3 py-2 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 {{ $aktif ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}">
                Per {{ $label }}
            </a>
        @endforeach
    </nav>

    {{--
        Penyaring Tahun. Formulir GET biasa yang kompatibel dengan atau tanpa JavaScript.
        Hanya dirender untuk 5 tab demografi (sp, status, pekerjaan, asal, pendidikan)
        karena tab 'tahun' sudah menyajikan seluruh deret waktu historis secara lengkap.
    --}}
    @if ($kelompok !== 'tahun')
        <form method="GET" action="{{ route('kependudukan.rekap.kelompok', ['kelompok' => $kelompok]) }}"
            class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="filter_tahun"
                        class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                        Tahun Data
                    </label>
                    <select id="filter_tahun" name="tahun"
                        class="h-10 w-52 rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90">
                        @foreach ($daftarTahun as $t)
                            <option value="{{ $t }}" @selected($tahunPilihan === $t)>
                                {{ $t }} @if ($t === $tahunTerakhir) (Tahun Terakhir) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit"
                    class="h-10 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    Terapkan Filter
                </button>

                @if ($tahunPilihan !== $tahunTerakhir)
                    <a href="{{ route('kependudukan.rekap.kelompok', ['kelompok' => $kelompok]) }}"
                        class="flex h-10 items-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Reset ke Tahun {{ $tahunTerakhir }}
                    </a>
                @endif
            </div>

            @php
                $totalKkSaatIni = match ($kelompok) {
                    'sp' => array_sum(array_column($perSp, 'jumlah_kk')),
                    'status' => array_sum($penghuni),
                    'pekerjaan' => array_sum($pekerjaan),
                    'asal' => array_sum($daerahAsal),
                    'pendidikan' => array_sum($pendidikan),
                    default => $ringkasan['jumlah_kk'],
                };
            @endphp
            <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
                Menampilkan data kependudukan kawasan pada tahun <strong>{{ $tahunPilihan }}</strong> (Total: <strong>{{ number_format($totalKkSaatIni, 0, ',', '.') }} KK</strong>). Rekap terikat satu tahun data; angka kependudukan tidak dijumlahkan lintas tahun.
            </p>
        </form>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                Rekap per {{ $labelKelompok[$kelompok] ?? 'Tahun' }} @if ($kelompok !== 'tahun') (Tahun {{ $tahunPilihan }}) @endif
            </h2>
        </div>

        <div class="overflow-x-auto">
            @if ($kelompok === 'sp')
                <table class="w-full text-left">
                    <caption class="sr-only">Rekap kependudukan per satuan permukiman</caption>
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Satuan Permukiman</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kepala Keluarga</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Rumah Terhuni</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Luas Lahan (ha)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($perSp as $b)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                    <a href="{{ route('sp.detail', $b['satuan_permukiman_id']) }}"
                                        class="rounded hover:text-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:hover:text-brand-400">
                                        {{ $b['satuan_permukiman'] }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($b['jumlah_kk'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($b['rumah_terhuni'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($b['luas_lahan'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="motif-baris-total">
                            <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">Total</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format(array_sum(array_column($perSp, 'jumlah_kk')), 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format(array_sum(array_column($perSp, 'rumah_terhuni')), 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format(array_sum(array_column($perSp, 'luas_lahan')), 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            @elseif ($kelompok === 'status')
                <table class="w-full text-left">
                    <caption class="sr-only">Rekap kependudukan per status tinggal</caption>
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Status Tinggal</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah KK</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Porsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @php $totalPenghuni = array_sum($penghuni); @endphp
                        @foreach ($penghuni as $status => $jumlah)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3">
                                    <x-sim.status-badge :status="\App\Enums\StatusTinggal::from($status)" />
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($jumlah, 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($jumlah / $totalPenghuni * 100, 1, ',', '.') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="motif-baris-total">
                            <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">Total</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalPenghuni, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">100,0%</td>
                        </tr>
                    </tfoot>
                </table>
            @elseif ($kelompok === 'pekerjaan')
                <table class="w-full text-left">
                    <caption class="sr-only">Rekap kependudukan per pekerjaan</caption>
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Pekerjaan Kepala Keluarga</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah KK</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Porsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @php $totalKerja = array_sum($pekerjaan); @endphp
                        @foreach ($pekerjaan as $nama => $jumlah)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $nama }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($jumlah, 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($jumlah / $totalKerja * 100, 1, ',', '.') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="motif-baris-total">
                            <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">Total</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalKerja, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">100,0%</td>
                        </tr>
                    </tfoot>
                </table>
            {{--
                Dua tab tambahan 2026-08-25 atas permintaan pemilik proyek.

                Keduanya menutup isian form yang selama ini diketik petugas
                lalu tidak pernah terlihat kembali sebagai angka. Daerah asal
                khas program transmigrasi: ia menjawab "dari mana warga
                berasal", pertanyaan yang tidak dijawab tab mana pun.
            --}}
            @elseif ($kelompok === 'asal')
                <table class="w-full text-left">
                    <caption class="sr-only">Rekap kependudukan per daerah asal</caption>
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Daerah Asal</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah KK</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Porsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @php $totalAsal = array_sum($daerahAsal); @endphp
                        @foreach ($daerahAsal as $nama => $jumlah)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $nama }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($jumlah, 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($jumlah / $totalAsal * 100, 1, ',', '.') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="motif-baris-total">
                            <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">Total</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalAsal, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">100,0%</td>
                        </tr>
                    </tfoot>
                </table>
            {{--
                Pendidikan diurutkan menurut JENJANG, bukan menurut jumlah:
                mengurutkannya menurut jumlah membuat SD mendahului Tidak
                Sekolah dan pembaca kehilangan bentuk piramidanya.

                Jenjang tanpa penghuni tetap ditampilkan bernilai nol. Baris
                yang hilang membuat pembaca tidak dapat membedakan "tidak ada"
                dari "belum didata".
            --}}
            @elseif ($kelompok === 'pendidikan')
                <table class="w-full text-left">
                    <caption class="sr-only">Rekap kependudukan per pendidikan</caption>
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Pendidikan Terakhir</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jumlah KK</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Porsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @php $totalDidik = array_sum($pendidikan); @endphp
                        @foreach ($pendidikan as $nama => $jumlah)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $nama }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums {{ $jumlah > 0 ? 'text-gray-800 dark:text-white/90' : 'text-gray-400 dark:text-white/30' }}">
                                    {{ number_format($jumlah, 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($jumlah / $totalDidik * 100, 1, ',', '.') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="motif-baris-total">
                            <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">Total</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($totalDidik, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">100,0%</td>
                        </tr>
                    </tfoot>
                </table>
            @else
                <table class="w-full text-left">
                    <caption class="sr-only">Rekap kependudukan per tahun</caption>
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Kepala Keluarga</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Jiwa</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Petani</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">KK Masuk</th>
                            <th scope="col" class="px-5 py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400">KK Keluar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($perTahun as $b)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3 text-theme-sm font-medium tabular-nums text-gray-800 dark:text-white/90">
                                    {{ $b['tahun'] }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                    {{ number_format($b['jumlah_kk'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($b['jumlah_jiwa'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($b['jumlah_petani'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-green-700 dark:text-green-400">
                                    +{{ number_format($b['kk_masuk'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $b['kk_keluar'] > 0 ? '-' . number_format($b['kk_keluar'], 0, ',', '.') : '0' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="motif-baris-total">
                            <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">Kondisi terkini</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($ringkasan['jumlah_kk'], 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($ringkasan['jumlah_jiwa'], 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format($ringkasan['jumlah_petani'], 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format(array_sum(array_column($perTahun, 'kk_masuk')), 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-800 dark:text-white/90">
                                {{ number_format(array_sum(array_column($perTahun, 'kk_keluar')), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>
    </div>

    <p class="mt-4 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
        Angka KK masuk dan keluar dihitung dari riwayat penghunian rumah, bukan dari data
        transmigran, agar perpindahan antar-rumah di dalam kawasan tetap terekam.
    </p>
@endsection
