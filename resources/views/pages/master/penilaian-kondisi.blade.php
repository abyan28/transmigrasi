@extends('layouts.app')

{{--
    Pengaturan penilaian kondisi SP: bobot parameter dan ambang predikat.

    Keduanya keputusan KEBIJAKAN yang wajib divalidasi dinas, bukan angka
    teknis (rules.md 10c poin 13). Sebelumnya bobot terkunci pada
    TingkatKebutuhan::bobotBawaan() dan ambang pada StatusKondisiSp, padahal
    nilai kondisi aset sudah lebih dulu dapat disunting. Separuh perhitungan
    dapat diatur, separuhnya terkunci.

    DUA TAB BOLEH DI SINI, berbeda dari data master daftar pilihan yang tabnya
    dibongkar menjadi kartu. Yang membatasi bukan cacah tab melainkan lebar
    judulnya terhadap wadahnya (ui-spec.md 5.1d); dua judul pendek jelas muat.
--}}

@section('content')
    @php
        use App\Enums\StatusKondisiSp;
        use App\Enums\TingkatKebutuhan;

        // `$parameter`, `$status`, `$dinilai`, `$totalBobot`, dan `$perSumber`
        // datang dari rute `master.penilaian-kondisi`.
        $bolehUbah = true;
    @endphp

    <x-sim.page-header judul="Penilaian Kondisi SP"
        keterangan="Bobot parameter dan ambang predikat yang menentukan status setiap satuan permukiman."
        :remah="\App\Helpers\RemahHelper::untuk('/master/penilaian-kondisi')" />

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Cara Skor Dihitung</h2>
        <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
            Tiap parameter dicari asetnya pada satuan permukiman, lalu kondisinya diterjemahkan menjadi nilai:
            Baik 1, Rusak Ringan 0,5, Rusak Berat 0,2, dan tidak ada 0. Nilai itu dikalikan bobotnya,
            dijumlahkan, lalu dibagi total bobot.
        </p>
        <p class="mt-3 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
            <span class="font-medium text-gray-800 dark:text-white/90">Perubahan berlaku pada penilaian berikutnya.</span>
            Penilaian yang sudah tersimpan tidak dihitung ulang, sebab masing-masing menyalin bobot yang
            berlaku saat penilaian dibuat. Tanpa salinan itu, laporan yang sudah dicetak akan berbeda dari
            yang tampil di layar setiap kali bobot disunting.
        </p>
    </div>
    <div x-data="hashTabs('parameter')"
        class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
            role="tablist" aria-label="Pengaturan penilaian">
            <button type="button" role="tab" @click="setTab('parameter')"
                :aria-selected="tab === 'parameter'"
                :class="tab === 'parameter'
                    ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                class="shrink-0 border-b-2 px-4 py-2.5 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                Parameter &amp; Bobot ({{ count($dinilai) }})
            </button>
            <button type="button" role="tab" @click="setTab('status')"
                :aria-selected="tab === 'status'"
                :class="tab === 'status'
                    ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                class="shrink-0 border-b-2 px-4 py-2.5 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                Status &amp; Ambang ({{ count($status) }})
            </button>
        </div>

        {{-- TAB 1: parameter dan bobotnya --}}
        <div x-show="tab === 'parameter'" role="tabpanel">
            <div class="border-b border-gray-200 p-5 dark:border-gray-800">
                <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                    Baris di bawah dihasilkan dari <span class="font-medium">jenis infrastruktur dan fasilitas</span>
                    pada data master, sehingga jenis yang baru ditambahkan muncul di sini dengan sendirinya.
                    Jenis baru lahir dalam keadaan <span class="font-medium">belum dinilai</span>: menambah jenis
                    adalah pendataan, sedangkan memasukkannya ke penilaian adalah keputusan kebijakan.
                </p>
                <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
                    Total bobot yang berlaku sekarang
                    <span class="font-semibold tabular-nums text-gray-800 dark:text-white/90">{{ $totalBobot }}</span>
                    dari {{ count($dinilai) }} parameter. Menaikkan satu bobot menurunkan andil parameter lain,
                    sebab pembaginya ikut bertambah.
                </p>
            </div>

            @foreach ($perSumber as $namaSumber => $daftar)
                <div class="border-b border-gray-200 last:border-b-0 dark:border-gray-800">
                    <h3 class="px-5 pt-5 text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                        {{ $namaSumber }}
                    </h3>
                    <p class="px-5 pb-3 pt-0.5 text-theme-xs text-gray-500 dark:text-gray-400">
                        Dibaca dari tabel {{ $namaSumber === 'Fasilitas' ? 'fasilitas SP' : 'infrastruktur' }}.
                    </p>

                    <x-sim.tabel-ringkas judul="Parameter penilaian kondisi satuan permukiman" :kolom="['Jenis', 'Nama Parameter', 'Tingkat', 'Bobot', 'Dinilai', 'Aksi']"
                        :kolom-kanan="['Aksi']">
                        @foreach ($daftar as $p)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3 text-theme-sm text-gray-800 dark:text-white/90">
                                    {{ $p['nilai_jenis'] }}
                                </td>
                                <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                                    {{ $p['is_dinilai'] ? $p['nama'] : '-' }}
                                </td>
                                <td class="px-5 py-3">
                                    @if ($p['is_dinilai'])
                                        <x-sim.status-badge :teks="$p['tingkat']->value"
                                            :warna="$p['tingkat'] === TingkatKebutuhan::Primer ? 'error' : ($p['tingkat'] === TingkatKebutuhan::Sekunder ? 'warning' : 'gray')"
                                            ukuran="sm" />
                                    @else
                                        <span class="text-theme-sm text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $p['is_dinilai'] ? $p['bobot'] : '-' }}
                                </td>
                                <td class="px-5 py-3">
                                    <x-sim.status-badge :teks="$p['is_dinilai'] ? 'Dinilai' : 'Tidak dinilai'"
                                        :warna="$p['is_dinilai'] ? 'success' : 'gray'" ukuran="sm" />
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <x-sim.aksi-baris modal-ubah="formUbahParameter"
                                        :data-baris="$p + ['id' => $p['id_parameter_penilaian_sp'], 'tingkat' => $p['tingkat']->value]"
                                        :label="$p['nilai_jenis']" />
                                </td>
                            </tr>
                        @endforeach
                    </x-sim.tabel-ringkas>
                </div>
            @endforeach

            <p class="border-t border-gray-200 p-5 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                Jenis <span class="font-medium">Lainnya</span> sengaja tidak dinilai. Ia keranjang penampung bagi
                aset di luar daftar, bukan satu jenis barang, sehingga menilai ketersediaannya berarti memberi
                nilai penuh kepada SP yang memiliki satu benda tak jelas.
            </p>
        </div>
        {{-- TAB 2: status, wording, dan ambangnya --}}
        <div x-show="tab === 'status'" x-cloak role="tabpanel">
            <div class="border-b border-gray-200 p-5 dark:border-gray-800">
                <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                    Nama dan keterangan status dapat disesuaikan dengan istilah yang dipakai dinas.
                    Jumlahnya tetap <span class="font-medium">tiga</span> dan tidak dapat ditambah maupun
                    dihapus: perhitungan skor hanya mengenal tiga keluaran, sehingga status keempat tidak
                    akan pernah tercapai oleh satuan permukiman mana pun.
                </p>
                <p class="mt-3 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                    <span class="font-medium text-gray-800 dark:text-white/90">Ambang wajib menurun.</span>
                    Status teratas harus berambang lebih besar daripada status di bawahnya. Bila terbalik,
                    status tengah tidak akan pernah tercapai sebab pembacaannya berhenti pada ambang
                    tertinggi yang cocok lebih dulu.
                </p>
            </div>

            <x-sim.tabel-ringkas judul="Ambang skor status kondisi" :kolom="['Status', 'Keterangan', 'Ambang Skor', 'Aksi']" :kolom-kanan="['Aksi']">
                @foreach ($status as $indeks => $s)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3">
                            <x-sim.status-badge :teks="$s['nama']" :warna="$s['warna']" />
                        </td>
                        <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">
                            {{ $s['keterangan'] }}
                        </td>
                        <td class="px-5 py-3 text-theme-sm tabular-nums text-gray-600 dark:text-gray-400">
                            @if ($indeks === 0)
                                {{ $s['ambang_bawah'] }} ke atas
                            @elseif ($s['ambang_bawah'] > 0)
                                {{ $s['ambang_bawah'] }} sampai {{ $status[$indeks - 1]['ambang_bawah'] - 1 }}
                            @else
                                {{--
                                    Status terendah adalah penampung sisa, sehingga
                                    ambangnya tetap 0 dan tidak disunting. Tanpa itu
                                    ada skor yang tidak mendapat status sama sekali.
                                --}}
                                di bawah {{ $status[$indeks - 1]['ambang_bawah'] }}
                                <span class="block text-theme-xs text-gray-400 dark:text-gray-500">penampung sisa</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <x-sim.aksi-baris modal-ubah="formUbahStatus"
                                :data-baris="$s + ['id' => $s['kode']]" :label="$s['nama']" />
                        </td>
                    </tr>
                @endforeach
            </x-sim.tabel-ringkas>

            <p class="border-t border-gray-200 p-5 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                Warna tidak dapat diubah. Hijau, kuning, dan merah menyatakan urutan keparahan, bukan selera;
                menukarnya membuat rekap pada dasbor terbaca terbalik. Status juga tetap berlaku sebagai
                <span class="font-medium">kesimpulan</span>, bukan pilihan: tidak ada satu pun form yang
                meminta petugas menetapkannya sendiri.
            </p>
        </div>
    </div>

    @if ($bolehUbah)
        <x-sim.modal-form nama="formUbahParameter" judul="Ubah Parameter Penilaian"
            keterangan="Perubahan berlaku pada penilaian berikutnya, bukan pada yang sudah tersimpan."
            pola-aksi="/master/penilaian-kondisi/parameter/:id" metode="PUT" ukuran="lg"
            label-simpan="Simpan Parameter">
            @include('pages.master.form-parameter-penilaian')
        </x-sim.modal-form>

        <x-sim.modal-form nama="formUbahStatus" judul="Ubah Status Kondisi"
            keterangan="Nama dan ambang dapat disesuaikan; jumlah status tetap tiga."
            pola-aksi="/master/penilaian-kondisi/status/:id" metode="PUT" ukuran="lg"
            label-simpan="Simpan Status">
            @include('pages.master.form-status-kondisi')
        </x-sim.modal-form>
    @endif
@endsection