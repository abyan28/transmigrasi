{{--
    Form pengaduan warga, tanpa login.

    Warga transmigran tidak memiliki akun sistem, sehingga pengaduan dibuka
    lewat halaman publik (agents/rules.md bagian 10b poin 1).

    Bahasa dibuat sesederhana mungkin karena penggunanya warga desa, bukan
    petugas (agents/ui-spec.md bagian 4.1a poin 2). Beberapa penyesuaian yang
    sengaja dilakukan dibanding form petugas:

    - istilah "satuan permukiman" tetap dipakai karena itulah sebutan resmi
      yang dikenal warga, tetapi diberi keterangan "tempat tinggal Anda",
    - kolom bidang penanganan tidak ditampilkan sama sekali; warga tidak perlu
      tahu pembagian tugas antar-dinas, sistem yang menyimpulkannya,
    - kolom prioritas juga tidak ditampilkan, karena penilaian kegentingan
      adalah tugas petugas, bukan pelapor,
    - koordinat tidak diminta, agar isian tetap pendek.
--}}
@extends('layouts.publik')

@section('content')
    @php
        use App\Support\DummyData;
        use App\Enums\KategoriPengaduan;
    @endphp

    {{-- Nomor pengaduan setelah berhasil kirim, ditampilkan besar dan jelas --}}
    @if (session('nomor_pengaduan'))
        <div class="mb-8 rounded-2xl border border-green-300 bg-green-50 p-6 text-center dark:border-green-500/40 dark:bg-green-500/10"
            role="status"
            x-data="{
                tersalin: false,
                salin() {
                    navigator.clipboard?.writeText(@js(session('nomor_pengaduan'))).then(() => {
                        this.tersalin = true;
                        setTimeout(() => { this.tersalin = false; }, 2500);
                    });
                },
            }">
            <span
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-500 text-white"
                aria-hidden="true">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </span>

            <h2 class="mt-4 text-lg font-semibold text-green-900 dark:text-green-200">
                Pengaduan Anda sudah kami terima
            </h2>

            <p class="mt-2 text-theme-sm text-green-800 dark:text-green-300">
                Simpan nomor di bawah ini. Nomor ini dipakai untuk melihat perkembangan laporan Anda.
            </p>

            {{--
                Nomor dibuat sangat menonjol agar mudah dicatat atau difoto,
                dan dapat disalin sekali ketuk. Tombol salin adalah PELENGKAP,
                bukan pengganti: menyalin hanya menaruh nomor di papan klip,
                yang mudah tertimpa salinan berikutnya. Karena itu nomornya
                tetap ditampilkan besar dan ajakan mencatat tetap ada.
            --}}
            <button type="button" @click="salin()"
                class="mt-4 inline-flex items-center gap-3 rounded-xl border border-green-400 bg-white px-4 py-3 transition hover:bg-green-100 focus:outline-2 focus:outline-offset-2 focus:outline-green-600 dark:border-green-500/40 dark:bg-transparent dark:hover:bg-green-500/10"
                :aria-label="tersalin
                    ? 'Nomor pengaduan sudah disalin'
                    : 'Salin nomor pengaduan {{ session('nomor_pengaduan') }}'">
                <span class="text-title-md font-bold tracking-wide tabular-nums text-green-900 dark:text-green-100">
                    {{ session('nomor_pengaduan') }}
                </span>

                <svg x-show="!tersalin" class="h-5 w-5 shrink-0 text-green-700 dark:text-green-300" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                </svg>

                <svg x-show="tersalin" x-cloak class="h-5 w-5 shrink-0 text-green-700 dark:text-green-300"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </button>

            <p class="mt-2 text-theme-xs text-green-800 dark:text-green-300"
                x-text="tersalin ? 'Nomor sudah disalin. Tetap catat atau foto sebagai cadangan.' : 'Ketuk nomor untuk menyalin.'"></p>

            <a href="{{ route('lacak-pengaduan', ['nomor' => session('nomor_pengaduan')]) }}"
                class="mt-5 inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-green-700 focus:outline-2 focus:outline-offset-2 focus:outline-green-600">
                Lihat Perkembangan Laporan
            </a>

            {{--
                Spanduk kejujuran. Surel disebut pada isian sebagai "nomor
                dikirim juga ke surel Anda", padahal pengirimannya menunggu
                backend. Tanpa keterangan ini warga dapat menunggu surel yang
                tidak akan pernah datang, lalu kehilangan nomornya.
            --}}
            @if (session('email_pelapor'))
                <p class="mt-5 rounded-lg border border-yellow-300 bg-yellow-50 p-3 text-theme-xs text-yellow-800 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-200">
                    <span class="font-medium">Pengiriman surel belum aktif.</span>
                    Nomor ini belum dikirim ke {{ session('email_pelapor') }}. Sampai layanan surel
                    berjalan, catat nomornya dari layar ini.
                </p>
            @endif
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-title-sm font-semibold text-navy-500 dark:text-white">
            Sampaikan Pengaduan Anda
        </h1>
        <p class="mt-2 text-theme-sm text-gray-600 dark:text-gray-400">
            Anda tidak perlu membuat akun. Isi keterangan di bawah ini, lalu kirim.
            Petugas akan menindaklanjuti laporan Anda.
        </p>
    </div>

    {{-- Keterangan singkat cara kerjanya, menjawab kekhawatiran umum warga --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-navy-700 dark:bg-navy-800">
        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">Yang terjadi setelah Anda mengirim</p>
        <ol class="mt-2 space-y-1.5 text-theme-sm text-gray-600 dark:text-gray-400">
            <li>1. Anda menerima nomor pengaduan. Catat atau foto nomor itu.</li>
            <li>2. Petugas memeriksa laporan Anda lalu menindaklanjutinya.</li>
            <li>3. Anda dapat melihat perkembangannya kapan saja lewat menu Lacak Pengaduan.</li>
        </ol>
    </div>

    <form method="POST" action="{{ route('pengaduan-warga.kirim') }}" enctype="multipart/form-data"
        class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-navy-700 dark:bg-navy-800">
        @csrf

        <div class="space-y-6">
            {{-- Bagian 1: siapa yang melapor --}}
            <section>
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Keterangan Anda</h2>
                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                    Diperlukan agar petugas dapat menghubungi Anda bila keterangannya kurang jelas.
                </p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nama_pelapor"
                            class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Nama Anda<span class="text-error-500">*</span>
                        </label>
                        <input type="text" id="nama_pelapor" name="nama_pelapor" value="{{ old('nama_pelapor') }}"
                            required maxlength="255" autocomplete="name"
                            class="h-12 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-navy-700 dark:text-white/90" />
                        @error('nama_pelapor')
                            <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="kontak_pelapor"
                            class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Nomor HP yang Bisa Dihubungi<span class="text-error-500">*</span>
                        </label>
                        <input type="tel" id="kontak_pelapor" name="kontak_pelapor"
                            value="{{ old('kontak_pelapor') }}" required maxlength="20" inputmode="numeric"
                            autocomplete="tel" placeholder="08xxxxxxxxxx"
                            class="h-12 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm tabular-nums text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-navy-700 dark:text-white/90" />
                        @error('kontak_pelapor')
                            <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{--
                        Surel bersifat OPSIONAL dan sengaja diletakkan setelah nomor HP.
                        Sistem tidak boleh bergantung pada surel: jaringan di lokus tidak
                        selalu memadai, dan sebagian warga tidak memilikinya. Nomor
                        pengaduan tetap ditampilkan besar di layar setelah kirim, sehingga
                        surel hanyalah salinan, bukan satu-satunya cara menerima nomor.
                    --}}
                    <div class="sm:col-span-2">
                        <label for="email_pelapor"
                            class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Alamat Surel <span class="font-normal text-gray-500 dark:text-gray-400">(boleh dikosongkan)</span>
                        </label>
                        <input type="email" id="email_pelapor" name="email_pelapor"
                            value="{{ old('email_pelapor') }}" maxlength="100" autocomplete="email"
                            placeholder="nama@contoh.com" aria-describedby="email_pelapor_bantuan"
                            class="h-12 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-navy-700 dark:text-white/90" />
                        <p id="email_pelapor_bantuan" class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            Bila diisi, nomor pelacakan dikirim juga ke surel Anda. Tanpa surel pun pengaduan tetap
                            dapat dikirim, dan nomornya langsung tampil di layar setelah berhasil.
                        </p>
                        @error('email_pelapor')
                            <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- Bagian 2: apa yang dilaporkan --}}
            <section class="border-t border-gray-200 pt-5 dark:border-navy-700">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Masalah yang Dilaporkan</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="satuan_permukiman_id"
                            class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Tempat Tinggal Anda<span class="text-error-500">*</span>
                        </label>
                        {{--
                            Warga cukup memilih SP-nya. Pemilih wilayah bertingkat
                            sengaja tidak dipakai di sini agar isian tetap pendek.
                        --}}
                        <select id="satuan_permukiman_id" name="satuan_permukiman_id" required
                            class="h-12 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-navy-700 dark:text-white/90">
                            <option value="">Pilih tempat tinggal Anda</option>
                            @foreach (DummyData::satuanPermukiman() as $sp)
                                <option value="{{ $sp['id_satuan_permukiman'] }}"
                                    @selected(old('satuan_permukiman_id') == $sp['id_satuan_permukiman'])>
                                    {{ $sp['nama'] }} ({{ $sp['desa'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="kategori"
                            class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Masalahnya Tentang Apa<span class="text-error-500">*</span>
                        </label>
                        <select id="kategori" name="kategori" required
                            class="h-12 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-navy-700 dark:text-white/90">
                            <option value="">Pilih salah satu</option>
                            @foreach (KategoriPengaduan::opsi() as $nilai => $label)
                                <option value="{{ $nilai }}" @selected(old('kategori') === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            Pilih yang paling mendekati. Petugas akan meneruskannya ke bagian yang tepat sekaligus menilai kesegeraannya.
                        </p>
                    </div>

                    <div>
                        <label for="tanggal_pengaduan"
                            class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Kapan Kejadiannya<span class="text-error-500">*</span>
                        </label>
                        <input type="date" id="tanggal_pengaduan" name="tanggal_pengaduan"
                            value="{{ old('tanggal_pengaduan', date('Y-m-d')) }}" required max="{{ date('Y-m-d') }}"
                            class="h-12 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-navy-700 dark:text-white/90" />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="judul"
                            class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Judul Singkat<span class="text-error-500">*</span>
                        </label>
                        <input type="text" id="judul" name="judul" value="{{ old('judul') }}" required
                            maxlength="255" placeholder="Contoh: saluran air tersumbat"
                            class="h-12 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-navy-700 dark:text-white/90" />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="deskripsi"
                            class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Ceritakan Masalahnya<span class="text-error-500">*</span>
                        </label>
                        <textarea id="deskripsi" name="deskripsi" rows="5" required
                            placeholder="Tuliskan apa yang terjadi, sejak kapan, dan bagian mana yang terkena"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-navy-700 dark:text-white/90">{{ old('deskripsi') }}</textarea>
                        @error('deskripsi')
                            <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- Bagian 3: foto, opsional --}}
            <section class="border-t border-gray-200 pt-5 dark:border-navy-700">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                    Foto Keadaan
                    <span class="font-normal text-gray-500 dark:text-gray-400">(boleh dikosongkan)</span>
                </h2>
                <div class="mt-4">
                    <x-sim.file-upload nama="dokumen_pendukung" label="Foto dari Lokasi" :hanya-gambar="true"
                        keterangan="Foto membantu petugas melihat keadaan sebenarnya. Bila tidak ada, lewati saja." />
                </div>
            </section>

            {{--
                Bagian 4: titik lokasi, opsional.

                Warga melapor lewat ponsel dengan jaringan yang tidak selalu memadai,
                sehingga isian ini TIDAK diwajibkan: pengaduan tetap dapat dikirim
                tanpa mengisinya. Bila diisi, petugas terbantu menemukan titik masalah
                tanpa perlu bertanya ulang.

                Peta pemilih titik disertakan sebab GPS ponsel kerap meleset puluhan
                meter, dan warga paling tahu letak sebenarnya (rules.md 10b poin 6c).
            --}}
            <section class="border-t border-gray-200 pt-5 dark:border-navy-700">
                <h2 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                    Titik Lokasi
                    <span class="font-normal text-gray-500 dark:text-gray-400">(boleh dikosongkan)</span>
                </h2>
                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                    Membantu petugas menemukan lokasi masalah. Pengaduan tetap dapat dikirim tanpa mengisi
                    bagian ini.
                </p>

                <div class="mt-4">
                    <x-sim.koordinat-input />
                </div>
            </section>
        </div>

        <div class="mt-6 border-t border-gray-200 pt-5 dark:border-navy-700">
            <button type="submit"
                class="w-full rounded-lg bg-brand-500 px-4 py-3.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                Kirim Pengaduan
            </button>
            <p class="mt-3 text-center text-theme-xs text-gray-500 dark:text-gray-400">
                Dengan mengirim, Anda menyatakan keterangan di atas benar adanya.
            </p>
        </div>
    </form>
@endsection
