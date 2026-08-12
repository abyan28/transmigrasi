{{--
    Pusat laporan dan template isian luring.

    Dua kebutuhan berbeda disatukan di sini:

    1. **Export laporan** untuk kebutuhan desa, dinas, pendamping, dan
       kementerian (agents/rules.md bagian 12).
    2. **Template isian luring** yang dapat diunduh lalu diunggah kembali,
       karena sinyal di lokus tidak selalu stabil (bagian 4.1 poin 10).

    Tombol export belum menghasilkan berkas sungguhan; pembangkitan Excel dan
    PDF dikerjakan pada Tahap 10. Karena itu setiap tombol diberi keterangan
    jujur, bukan dibiarkan tampak berfungsi (ANTISLOP-ID R-26).
--}}
@extends('layouts.app')

@section('content')
    @php
        $laporan = [
            ['kunci' => 'transmigran', 'nama' => 'Data Transmigran', 'keterangan' => 'Kepala keluarga beserta anggota, pekerjaan, dan pendapatan.', 'modul' => 'Kependudukan'],
            ['kunci' => 'rumah', 'nama' => 'Rumah dan Hunian', 'keterangan' => 'Kondisi rumah, status hunian, dan riwayat penghunian.', 'modul' => 'Kependudukan'],
            ['kunci' => 'lahan', 'nama' => 'Data Lahan', 'keterangan' => 'Lahan pekarangan dan lahan usaha beserta dokumennya.', 'modul' => 'Lahan'],
            ['kunci' => 'panen', 'nama' => 'Hasil Panen', 'keterangan' => 'Volume panen per komoditas, sudah dikonversi ke ton.', 'modul' => 'Pertanian'],
            ['kunci' => 'poktan', 'nama' => 'Kelompok Tani', 'keterangan' => 'Profil poktan beserta daftar anggotanya.', 'modul' => 'Kelembagaan'],
            ['kunci' => 'aset', 'nama' => 'Inventaris dan Fasilitas SP', 'keterangan' => 'Aset satuan permukiman beserta status penyerahan.', 'modul' => 'Wilayah dan SP'],
            ['kunci' => 'infrastruktur', 'nama' => 'Infrastruktur Pertanian', 'keterangan' => 'Kondisi aset irigasi, air, jalan produksi, dan gudang.', 'modul' => 'Infrastruktur'],
            ['kunci' => 'pengaduan', 'nama' => 'Pengaduan dan Penanganan', 'keterangan' => 'Laporan warga beserta perkembangan penanganannya.', 'modul' => 'Pengaduan'],
            ['kunci' => 'indikator', 'nama' => 'Indikator Kawasan', 'keterangan' => 'Rekap seluruh indikator utama untuk laporan kementerian.', 'modul' => 'Dashboard'],
        ];

        $template = [
            ['nama' => 'Template Pendataan Transmigran', 'keterangan' => 'Diisi saat pendataan rumah ke rumah, lalu diunggah kembali.'],
            ['nama' => 'Template Pencatatan Panen', 'keterangan' => 'Diisi ketua poktan setiap akhir musim tanam.'],
            ['nama' => 'Template Pendataan Aset SP', 'keterangan' => 'Diisi saat pemeriksaan inventaris dan fasilitas.'],
        ];
    @endphp

    <x-sim.page-header judul="Pusat Laporan"
        keterangan="Unduh rekap data kawasan dan template isian untuk pendataan luring."
        :remah="[['label' => 'Laporan'], ['label' => 'Pusat Laporan']]" />

    <div x-data="hashTabs('laporan')">
        <div class="mb-6 flex gap-1 overflow-x-auto rounded-2xl border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-white/[0.03]"
            role="tablist" aria-label="Jenis unduhan">
            @foreach (['laporan' => 'Laporan Data', 'template' => 'Template Isian Luring'] as $kunci => $label)
                <button type="button" role="tab" @click="setTab('{{ $kunci }}')"
                    :aria-selected="tab === '{{ $kunci }}'"
                    :class="tab === '{{ $kunci }}'
                        ? 'bg-brand-500 text-white'
                        : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5'"
                    class="shrink-0 rounded-lg px-4 py-2 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Laporan data --}}
        <div x-show="tab === 'laporan'" role="tabpanel">
            <div class="mb-6 rounded-xl border border-yellow-300 bg-yellow-50 p-4 dark:border-yellow-500/30 dark:bg-yellow-500/10"
                role="status">
                <p class="text-theme-sm text-yellow-800 dark:text-yellow-200">
                    <span class="font-semibold">Belum menghasilkan berkas.</span>
                    Pembangkitan Excel dan PDF dikerjakan pada tahap berikutnya. Tombol di bawah
                    memperlihatkan susunan laporan yang akan tersedia, beserta filter yang dapat dipakai.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($laporan as $l)
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                        <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $l['modul'] }}</p>
                        <h3 class="mt-1 text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $l['nama'] }}</h3>
                        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $l['keterangan'] }}</p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            {{-- Label menyebut keadaan sebenarnya, bukan menjanjikan unduhan --}}
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                Excel, segera hadir
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                PDF, segera hadir
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Template luring --}}
        <div x-show="tab === 'template'" x-cloak role="tabpanel">
            <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Mengapa Ada Template Luring</h2>
                <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
                    Sinyal di lokus tidak selalu stabil, sehingga pendataan kerap dilakukan tanpa
                    sambungan. Template ini diunduh lebih dulu, diisi di lapangan, lalu diunggah
                    kembali saat petugas kembali ke tempat bersinyal.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($template as $t)
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                        <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $t['nama'] }}</h3>
                        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">{{ $t['keterangan'] }}</p>
                        <span class="mt-4 inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            Segera hadir
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
