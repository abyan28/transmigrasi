{{--
    Isian akun petugas, dipakai bersama modal tambah dan modal ubah.

    Aturan agents/rules.md bagian 14b yang wajib dijaga di sini:

    1. Akun berrole bercakupan `Per SP` wajib memiliki minimal satu penugasan
       SP (poin 2). Pilihan SP karena itu hanya muncul ketika role yang dipilih
       bercakupan `Per SP`, dan ikut berubah saat role diganti. Menampilkannya
       untuk role bercakupan `Semua` membuat petugas mengira penugasan itu
       membatasi aksesnya, padahal tidak berpengaruh apa pun.
    2. Kata sandi TIDAK PERNAH muncul pada modal ubah (poin 10). Admin hanya
       dapat menimpanya lewat modal setel ulang, tidak pernah melihatnya.

    Dua hal berubah pada 2026-08-14:

    - **Username tidak lagi diisi Admin.** Petugaslah yang membuatnya sendiri
      saat pertama kali masuk, sebab dialah yang akan mengetiknya setiap hari.
      Akibatnya surel menjadi WAJIB, karena itulah satu-satunya kredensial
      yang dimilikinya pada saat itu.
    - **Kata sandi awal dibuatkan sistem**, bukan diketik Admin. Kata sandi
      karangan manusia cenderung berpola dan berulang untuk banyak akun.

    Nama kolom mengikuti agents/data-dictionary.md bagian 2.1.
--}}
@php
    use App\Enums\CakupanData;
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];
    $mode = $mode ?? 'tambah';

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
    $kelasBantuan = 'mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400';

    // `$daftarRole` dan `$daftarSp` disuplai ViewServiceProvider.

    // Peta cakupan tiap role dibaca Alpine untuk menentukan kapan pilihan SP
    // perlu ditampilkan, tanpa perlu memanggil server.
    $cakupanPerRole = collect($daftarRole)
        ->mapWithKeys(fn ($r) => [$r['id_role'] => $r['cakupan_data']])
        ->all();

    $roleTerpilih = old('role_id', $data['role_id'] ?? '');
    $spTerpilih = old('satuan_permukiman', $data['satuan_permukiman_id'] ?? []);
@endphp

<div class="space-y-6"
    x-data="{
        roleId: @js((string) $roleTerpilih),
        cakupan: @js($cakupanPerRole),
        spTerpilih: @js(array_map('strval', (array) $spTerpilih)),

        get perluSp() {
            return this.cakupan[this.roleId] === @js(CakupanData::PerSp->value);
        },

        get adaSpTerpilih() {
            return this.spTerpilih.length > 0;
        },
    }"
    {{--
        Penegakan penugasan SP. Atribut `required` tidak dapat dipakai pada
        larik kotak centang, sebab di sana ia menuntut SETIAP kotak dicentang,
        bukan minimal satu. Pengiriman karena itu dicegah di sini.

        Akun bercakupan Per SP tanpa penugasan tidak melihat data apa pun
        (rules.md 5.0b poin 7), sehingga menyimpannya berarti membuat akun yang
        pasti gagal dipakai sejak hari pertama.
    --}}
    x-on:submit="if (perluSp && ! adaSpTerpilih) { $event.preventDefault(); }">

    {{-- Bagian 1: identitas petugas --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Petugas</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_nama" class="{{ $kelasLabel }}">Nama Lengkap<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama" name="nama" required
                    value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: NARA WIJAYA" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_jabatan" class="{{ $kelasLabel }}">Jabatan</label>
                <input type="text" id="{{ $awalan }}_jabatan" name="jabatan"
                    value="{{ old('jabatan', $data['jabatan'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: Staf Bidang Ketransmigrasian" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_telepon" class="{{ $kelasLabel }}">Nomor Telepon</label>
                <input type="tel" id="{{ $awalan }}_telepon" name="telepon"
                    value="{{ old('telepon', $data['telepon'] ?? '') }}" maxlength="20"
                    placeholder="0812xxxxxxx" class="{{ $kelasKontrol }} tabular-nums" />
            </div>
        </div>
    </section>

    {{-- Bagian 2: kredensial masuk --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Kredensial Masuk</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_email" class="{{ $kelasLabel }}">
                    Email<span class="text-error-500">*</span>
                </label>
                <input type="email" id="{{ $awalan }}_email" name="email" required
                    value="{{ old('email', $data['email'] ?? '') }}" maxlength="100"
                    placeholder="nama@malakakab.go.id" class="{{ $kelasKontrol }}"
                    aria-describedby="{{ $awalan }}_email_bantuan" />
                <p id="{{ $awalan }}_email_bantuan" class="{{ $kelasBantuan }}">
                    Wajib diisi. Dipakai petugas untuk login pertama kali, menerima kata sandi
                    sementara, dan meminta kode verifikasi untuk pemulihan kata sandi.
                </p>
            </div>

            @if ($mode === 'tambah')
                {{--
                    Username sengaja tidak disediakan pada modal tambah.
                    Petugaslah yang membuatnya sendiri saat pertama kali masuk,
                    bersamaan dengan penggantian kata sandi sementara.
                --}}
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">Username dibuat petugas</p>
                    <p class="mt-1 text-theme-xs text-gray-600 dark:text-gray-400">
                        Petugas menentukan usernamenya sendiri saat pertama kali login, sekaligus
                        mengganti kata sandi sementara. Admin tidak perlu meng-input-kannya.
                    </p>
                </div>
            @else
                <div>
                    <span class="{{ $kelasLabel }}">Username</span>
                    <p class="flex h-11 items-center rounded-lg border border-gray-200 bg-gray-50 px-4 font-mono text-theme-sm text-gray-800 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90">
                        {{ $data['username'] ?? '-' }}
                    </p>
                    <p class="{{ $kelasBantuan }}">
                        Dibuat sendiri oleh petugas dan tidak dapat diubah Admin.
                    </p>
                </div>
            @endif
        </div>

        @if ($mode === 'tambah')
            {{--
                Kata sandi awal dibuatkan sistem, bukan diketik Admin. Kata sandi
                karangan manusia cenderung berpola dan dipakai ulang untuk banyak
                akun sekaligus.

                Hasilnya ditampilkan di layar SEKALIGUS dikirim ke surel petugas.
                Keduanya diperlukan: surel menolong petugas yang berjaringan
                memadai, sedangkan tampilan layar menolong petugas di lokus
                bersinyal lemah yang sedang berdiri di depan Admin.
            --}}
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">
                    Kata sandi sementara dibuatkan sistem
                </p>
                <p class="mt-1 text-theme-sm text-gray-600 dark:text-gray-400">
                    Setelah akun tersimpan, kata sandi sementara tampil satu kali di layar dan
                    dikirim ke email di atas. Petugas wajib menggantinya saat pertama kali login.
                </p>
            </div>
        @else
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                    <span class="font-medium text-gray-800 dark:text-white/90">Kata sandi tidak dapat disunting di sini.</span>
                    Kata sandi asli tidak dapat dilihat oleh siapa pun, termasuk Admin. Jika pengguna kehilangan akses,
                    gunakan tombol “Setel Ulang Kata Sandi” untuk membuat kata sandi baru.
                </p>
            </div>
        @endif
    </section>

    {{-- Bagian 3: kewenangan --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Kewenangan</h3>
        <div class="mt-3 space-y-4">
            <div>
                <label for="{{ $awalan }}_role_id" class="{{ $kelasLabel }}">Role<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_role_id" name="role_id" required x-model="roleId"
                    class="{{ $kelasKontrol }}">
                    <option value="">Pilih role</option>
                    @foreach ($daftarRole as $role)
                        <option value="{{ $role['id_role'] }}" @selected((string) $roleTerpilih === (string) $role['id_role'])>
                            {{ $role['nama'] }} ({{ $role['cakupan_data'] }})
                        </option>
                    @endforeach
                </select>
                <p class="{{ $kelasBantuan }}">
                    Setiap akun memegang tepat satu role. Cakupan data menentukan data apa saja yang boleh dilihat.
                </p>
            </div>

            {{--
                Penugasan SP. Muncul hanya untuk role bercakupan Per SP
                (rules.md 14b poin 2), dan ikut menyesuaikan begitu role diganti.
            --}}
            <div x-show="perluSp" x-cloak x-transition>
                <span class="{{ $kelasLabel }}">
                    Penugasan Satuan Permukiman<span class="text-error-500">*</span>
                </span>

                <div class="rounded-lg border border-gray-300 p-3 dark:border-gray-700"
                    :class="perluSp && ! adaSpTerpilih ? 'border-error-500 dark:border-error-500' : ''">
                    <p class="mb-3 text-theme-xs text-gray-500 dark:text-gray-400">
                        Wajib dipilih minimal satu. Petugas hanya dapat melihat dan memasukkan data
                        pada SP yang ditugaskan padanya.
                    </p>

                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($daftarSp as $sp)
                            <label class="flex items-start gap-2.5 rounded-lg p-2 hover:bg-gray-50 dark:hover:bg-white/5">
                                <input type="checkbox" name="satuan_permukiman[]" x-model="spTerpilih"
                                    value="{{ $sp['id_satuan_permukiman'] }}"
                                    @checked(in_array($sp['id_satuan_permukiman'], (array) $spTerpilih))
                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700" />
                                <span class="text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $sp['nama'] }}
                                    <span class="block text-theme-xs text-gray-500 dark:text-gray-400">
                                        {{ $sp['desa'] }}, {{ $sp['kecamatan'] }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    {{--
                        Peringatan menggantikan atribut `required`, yang tidak
                        berlaku pada larik kotak centang: memasangnya di sana
                        justru menuntut SETIAP kotak dicentang. Pesan ini muncul
                        hanya ketika syaratnya berlaku dan belum ada yang dipilih.
                    --}}
                    <p x-show="perluSp && ! adaSpTerpilih" x-cloak
                        class="mt-3 text-theme-xs text-error-500" role="alert">
                        Pilih minimal satu satuan permukiman. Akun bercakupan Per SP tanpa penugasan
                        tidak akan melihat data apa pun.
                    </p>
                </div>
            </div>

            {{--
                Toggle "akun aktif" sengaja tidak disediakan di sini. Akun baru
                selalu langsung aktif, sedangkan penonaktifan dan pengaktifan
                kembali dilakukan lewat tombol pada halaman daftar. Menyediakan
                dua jalur untuk satu keadaan membuat riwayat audit terpecah dan
                membingungkan saat ditelusuri.
            --}}
        </div>
    </section>
</div>
