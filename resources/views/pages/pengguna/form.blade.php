{{--
    Isian akun petugas, dipakai bersama modal tambah dan modal ubah.

    Tiga aturan agents/rules.md bagian 14b yang wajib dijaga di sini:

    1. Akun berrole bercakupan `Per SP` wajib memiliki minimal satu penugasan
       SP (poin 2). Pilihan SP karena itu hanya muncul ketika role yang dipilih
       bercakupan `Per SP`, dan ikut berubah saat role diganti. Menampilkannya
       untuk role bercakupan `Semua` membuat petugas mengira penugasan itu
       membatasi aksesnya, padahal tidak berpengaruh apa pun.
    2. Kata sandi TIDAK PERNAH muncul pada modal ubah (poin 10). Admin hanya
       dapat menimpanya lewat modal setel ulang, tidak pernah melihatnya.
       Menyediakan kolom kata sandi di sini akan menyiratkan bahwa nilai lama
       dapat dibaca atau disunting sebagian.
    3. Username hanya huruf kecil, angka, titik, dan garis bawah, sepanjang
       3 sampai 50 karakter (poin 5).

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

    $daftarRole = DummyData::role();
    $daftarSp = DummyData::satuanPermukiman();

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
        get perluSp() {
            return this.cakupan[this.roleId] === @js(CakupanData::PerSp->value);
        },
    }">

    {{-- Bagian 1: identitas petugas --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Petugas</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_nama" class="{{ $kelasLabel }}">Nama Lengkap</label>
                <input type="text" id="{{ $awalan }}_nama" name="nama"
                    value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: BUDI SANTOSO" class="{{ $kelasKontrol }}" />
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
                <label for="{{ $awalan }}_username" class="{{ $kelasLabel }}">Username</label>
                <input type="text" id="{{ $awalan }}_username" name="username"
                    value="{{ old('username', $data['username'] ?? '') }}"
                    minlength="3" maxlength="50" pattern="[a-z0-9._]{3,50}"
                    placeholder="budi.santoso" class="{{ $kelasKontrol }}"
                    aria-describedby="{{ $awalan }}_username_bantuan" />
                <p id="{{ $awalan }}_username_bantuan" class="{{ $kelasBantuan }}">
                    Huruf kecil, angka, titik, dan garis bawah. Panjang 3 sampai 50 karakter.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_email" class="{{ $kelasLabel }}">Email Dinas</label>
                <input type="email" id="{{ $awalan }}_email" name="email"
                    value="{{ old('email', $data['email'] ?? '') }}" maxlength="100"
                    placeholder="nama@malakakab.go.id" class="{{ $kelasKontrol }}"
                    aria-describedby="{{ $awalan }}_email_bantuan" />
                <p id="{{ $awalan }}_email_bantuan" class="{{ $kelasBantuan }}">
                    Dipakai untuk masuk dan menerima kode verifikasi saat lupa kata sandi.
                </p>
            </div>
        </div>

        @if ($mode === 'tambah')
            {{--
                Kata sandi awal hanya ada pada modal tambah. Pada modal ubah,
                kolom ini sengaja tidak dirender sama sekali (rules.md 14b
                poin 10), bukan sekadar dikosongkan.
            --}}
            <div class="mt-4">
                <x-sim.input-kata-sandi nama="password_awal" label="Kata Sandi Awal"
                    autocomplete="new-password" :wajib="true"
                    keterangan="Serahkan langsung kepada petugas yang bersangkutan. Sistem akan meminta penggantian saat ia pertama kali masuk." />
            </div>
        @else
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                    <span class="font-medium text-gray-800 dark:text-white/90">Kata sandi tidak dapat disunting di sini.</span>
                    Sistem hanya menyimpan sidik kata sandi, sehingga nilai lamanya tidak dapat dibaca siapa pun,
                    termasuk Admin. Gunakan tombol Setel Ulang Kata Sandi bila petugas kehilangan akses.
                </p>
            </div>
        @endif
    </section>

    {{-- Bagian 3: kewenangan --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Kewenangan</h3>
        <div class="mt-3 space-y-4">
            <div>
                <label for="{{ $awalan }}_role_id" class="{{ $kelasLabel }}">Role</label>
                <select id="{{ $awalan }}_role_id" name="role_id" x-model="roleId"
                    class="{{ $kelasKontrol }}">
                    <option value="">Pilih role</option>
                    @foreach ($daftarRole as $role)
                        <option value="{{ $role['id_role'] }}" @selected((string) $roleTerpilih === (string) $role['id_role'])>
                            {{ $role['nama'] }} ({{ $role['cakupan_data'] }})
                        </option>
                    @endforeach
                </select>
                <p class="{{ $kelasBantuan }}">
                    Setiap akun memegang tepat satu role. Cakupan data menentukan data siapa saja yang boleh dilihat.
                </p>
            </div>

            {{--
                Penugasan SP. Muncul hanya untuk role bercakupan Per SP
                (rules.md 14b poin 2), dan ikut menyesuaikan begitu role diganti.
            --}}
            <div x-show="perluSp" x-cloak x-transition>
                <span class="{{ $kelasLabel }}">Penugasan Satuan Permukiman</span>

                <div class="rounded-lg border border-gray-300 p-3 dark:border-gray-700">
                    <p class="mb-3 text-theme-xs text-gray-500 dark:text-gray-400">
                        Wajib dipilih minimal satu. Petugas hanya dapat melihat dan memasukkan data
                        pada SP yang ditugaskan padanya.
                    </p>

                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($daftarSp as $sp)
                            <label class="flex items-start gap-2.5 rounded-lg p-2 hover:bg-gray-50 dark:hover:bg-white/5">
                                <input type="checkbox" name="satuan_permukiman[]"
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
                </div>
            </div>

            <div>
                <label class="flex items-start gap-2.5">
                    <input type="checkbox" name="is_aktif" value="1"
                        @checked(old('is_aktif', $data['is_aktif'] ?? true))
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700" />
                    <span class="text-theme-sm text-gray-700 dark:text-gray-300">
                        Akun aktif
                        <span class="block text-theme-xs text-gray-500 dark:text-gray-400">
                            Akun nonaktif tidak dapat masuk, tetapi seluruh riwayat tindakannya tetap tersimpan.
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </section>
</div>
