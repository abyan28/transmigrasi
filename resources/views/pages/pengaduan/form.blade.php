{{--
    Isian pencatatan pengaduan oleh petugas.

    Berbeda dari halaman publik (Task 2.11b) yang diisi warga sendiri, form ini
    dipakai petugas untuk mencatatkan laporan yang disampaikan lisan. Karena
    itu `sumber_laporan` bernilai Petugas (agents/rules.md bagian 10b poin 1a).

    Aturan khusus modul ini: bidang penanganan terisi otomatis dari kategori
    lewat peta `$petaBidang`, tetapi SELALU DAPAT DITIMPA petugas
    (agents/rules.md bagian 10b poin 7c). Empat kategori sengaja tidak dapat
    disimpulkan bidangnya dan wajib dipilih manual.

    Nama kolom mengikuti agents/data-dictionary.md bagian 10.2.
--}}
@php
    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasArea = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    // Peta kategori ke bidang, dipakai Alpine agar bidang terisi seketika saat
    // kategori dipilih. Kategori netral bernilai string kosong.
    // `$petaBidang`, `$opsiKategoriPengaduan`, `$opsiBidang`,
    // `$opsiPrioritasPengaduan`, dan `$daftarSp` disuplai ViewServiceProvider.
@endphp

<div class="space-y-6"
    x-data="{
        kategori: @js($data['kategori'] ?? ''),
        bidang: @js($data['bidang'] ?? ''),
        disentuh: {{ empty($data['bidang']) ? 'false' : 'true' }},
        petaBidang: @js($petaBidang),

        /*
         * Bidang hanya diisi ulang selama petugas belum menyentuhnya sendiri.
         * Tanpa penjaga ini, pilihan manual akan tertimpa setiap kali kategori
         * disunting, dan petugas kehilangan hasil penilaiannya tanpa peringatan.
         */
        gantiKategori(nilai) {
            this.kategori = nilai;

            if (! this.disentuh) {
                this.bidang = this.petaBidang[nilai] ?? '';
            }
        },

        get bidangNetral() {
            return this.kategori !== '' && (this.petaBidang[this.kategori] ?? '') === '';
        },
    }">

    {{-- Bagian 1: pelapor --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Pelapor</h3>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
            Diisi petugas saat mencatatkan laporan yang disampaikan warga secara lisan.
        </p>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_nama_pelapor" class="{{ $kelasLabel }}">
                    Nama Pelapor<span class="text-error-500">*</span>
                </label>
                <input type="text" id="{{ $awalan }}_nama_pelapor" name="nama_pelapor"
                    value="{{ old('nama_pelapor', $data['nama_pelapor'] ?? '') }}" required maxlength="255"
                    class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_kontak_pelapor" class="{{ $kelasLabel }}">
                    Nomor yang Dapat Dihubungi<span class="text-error-500">*</span>
                </label>
                <input type="tel" id="{{ $awalan }}_kontak_pelapor" name="kontak_pelapor"
                    value="{{ old('kontak_pelapor', $data['kontak_pelapor'] ?? '') }}" required maxlength="20"
                    inputmode="numeric" placeholder="08xxxxxxxxxx" class="{{ $kelasKontrol }} tabular-nums" />
            </div>
        </div>
    </section>

    {{-- Bagian 2: isi pengaduan --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Isi Pengaduan</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_judul" class="{{ $kelasLabel }}">
                    Perihal<span class="text-error-500">*</span>
                </label>
                <input type="text" id="{{ $awalan }}_judul" name="judul"
                    value="{{ old('judul', $data['judul'] ?? '') }}" required maxlength="255"
                    placeholder="Ringkasan singkat masalah" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_kategori" class="{{ $kelasLabel }}">
                    Kategori<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_kategori" name="kategori" required
                    :value="kategori" @change="gantiKategori($event.target.value)"
                    class="{{ $kelasKontrol }}">
                    <option value="">Pilih kategori</option>
                    @foreach ($opsiKategoriPengaduan as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(($data['kategori'] ?? '') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_bidang" class="{{ $kelasLabel }}">
                    Bidang Penanganan
                </label>
                {{--
                    Terisi otomatis dari kategori, tetapi tetap dapat ditimpa.
                    Empat kategori sengaja netral sebab pokok masalahnya dapat
                    jatuh ke dua dinas: sengketa lahan usaha bisa menyangkut
                    pembagian lahan maupun produktivitasnya.
                --}}
                <select id="{{ $awalan }}_bidang" name="bidang"
                    :value="bidang" @change="bidang = $event.target.value; disentuh = true"
                    class="{{ $kelasKontrol }}">
                    <option value="">Belum ditentukan</option>
                    @foreach ($opsiBidang as $nilai => $label)
                        <option value="{{ $nilai }}" :selected="bidang === '{{ $nilai }}'">{{ $label }}</option>
                    @endforeach
                </select>

                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400"
                    x-show="! bidangNetral">
                    Terisi otomatis dari kategori. Ubah bila laporan ini sebenarnya
                    ditangani dinas lain.
                </p>
                <p class="mt-1.5 text-theme-xs text-gold-700 dark:text-gold-400" x-show="bidangNetral"
                    x-cloak>
                    Kategori ini dapat ditangani dua dinas, sehingga bidangnya perlu
                    ditetapkan sendiri berdasarkan isi laporan.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_prioritas" class="{{ $kelasLabel }}">
                    Prioritas<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_prioritas" name="prioritas" required class="{{ $kelasKontrol }}">
                    @foreach ($opsiPrioritasPengaduan as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('prioritas', $data['prioritas'] ?? 'Sedang') === $nilai)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_tanggal" class="{{ $kelasLabel }}">
                    Tanggal Kejadian<span class="text-error-500">*</span>
                </label>
                <input type="date" id="{{ $awalan }}_tanggal" name="tanggal_pengaduan"
                    value="{{ old('tanggal_pengaduan', $data['tanggal_pengaduan'] ?? date('Y-m-d')) }}" required
                    max="{{ date('Y-m-d') }}" class="{{ $kelasKontrol }}" />
            </div>

            <div class="sm:col-span-2">
                <x-sim.wilayah-picker
                    :daftar-kawasan="[['id' => 1, 'nama' => 'Kobalima Timur']]"
                    :daftar-sp="collect($daftarSp)
                        ->map(fn ($s) => ['id' => $s['id_satuan_permukiman'], 'nama' => $s['nama'], 'kawasan_id' => 1])
                        ->all()"
                    :sp-terpilih="old('satuan_permukiman_id', $data['satuan_permukiman_id'] ?? null)" />
            </div>

            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_deskripsi" class="{{ $kelasLabel }}">
                    Uraian Masalah<span class="text-error-500">*</span>
                </label>
                <textarea id="{{ $awalan }}_deskripsi" name="deskripsi" rows="4" required
                    placeholder="Jelaskan apa yang terjadi, sejak kapan, dan dampaknya"
                    class="{{ $kelasArea }}">{{ old('deskripsi', $data['deskripsi'] ?? '') }}</textarea>
            </div>
        </div>
    </section>

    {{-- Bagian 3: lokasi kejadian --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Titik Kejadian</h3>
        <div class="mt-3">
            <x-sim.koordinat-input :lintang="$data['lintang'] ?? null" :bujur="$data['bujur'] ?? null" />
        </div>
    </section>

    {{-- Bagian 4: bukti --}}
    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
        <h3 class="{{ $kelasBagian }}">Bukti Pendukung</h3>
        <div class="mt-3">
            <x-sim.berkas-unggah nama="bukti" label="Foto atau Dokumen Bukti"
                :tersimpan="$berkasBukti ?? []"
                keterangan="Beberapa foto dari sudut berbeda membantu petugas menilai penanganan yang diperlukan." />
        </div>
    </section>

    {{-- Sumber laporan ditetapkan sistem, bukan dipilih petugas --}}
    <input type="hidden" name="sumber_laporan" value="{{ \App\Enums\SumberLaporan::Petugas->value }}" />
</div>
