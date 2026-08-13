{{--
    Isian profil kelompok tani.

    Ketua poktan dipilih dari daftar transmigran, bukan diketik bebas, agar
    tautan ke halaman transmigran tetap sahih dan NIK tidak tertulis ganda
    dengan ejaan berbeda.

    Nama kolom mengikuti agents/data-dictionary.md bagian 8.1.
--}}
@php
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    $daftarSp = DummyData::satuanPermukiman();
    $daftarTransmigran = DummyData::transmigran();
@endphp

<div class="space-y-6">
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Kelompok</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_nama_poktan" class="{{ $kelasLabel }}">Nama Kelompok Tani</label>
                <input type="text" id="{{ $awalan }}_nama_poktan" name="nama"
                    value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: POKTAN MEKAR JAYA" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_sp_poktan" class="{{ $kelasLabel }}">Satuan Permukiman</label>
                <select id="{{ $awalan }}_sp_poktan" name="satuan_permukiman_id" class="{{ $kelasKontrol }}">
                    <option value="">Pilih satuan permukiman</option>
                    @foreach ($daftarSp as $sp)
                        <option value="{{ $sp['id_satuan_permukiman'] }}"
                            @selected((string) old('satuan_permukiman_id', $data['satuan_permukiman_id'] ?? '') === (string) $sp['id_satuan_permukiman'])>
                            {{ $sp['nama'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_tahun_berdiri" class="{{ $kelasLabel }}">Tahun Berdiri</label>
                <input type="number" id="{{ $awalan }}_tahun_berdiri" name="tahun_berdiri"
                    value="{{ old('tahun_berdiri', $data['tahun_berdiri'] ?? '') }}" min="1950"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
            </div>
        </div>
    </section>

    <section>
        <h3 class="{{ $kelasBagian }}">Ketua Kelompok</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_ketua_transmigran_id" class="{{ $kelasLabel }}">Ketua</label>
                <select id="{{ $awalan }}_ketua_transmigran_id" name="ketua_transmigran_id"
                    class="{{ $kelasKontrol }}">
                    <option value="">Pilih dari daftar transmigran</option>
                    @foreach ($daftarTransmigran as $t)
                        <option value="{{ $t['id_transmigran'] }}"
                            @selected(old('nama_ketua', $data['nama_ketua'] ?? '') === $t['nama_kepala_keluarga'])>
                            {{ $t['nama_kepala_keluarga'] }} &mdash; {{ $t['satuan_permukiman'] }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Dipilih dari data transmigran agar NIK dan tautan profilnya tetap sahih, bukan diketik ulang.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_telepon_ketua" class="{{ $kelasLabel }}">Telepon Kelompok</label>
                <input type="tel" id="{{ $awalan }}_telepon_ketua" name="telepon"
                    value="{{ old('telepon', $data['telepon_ketua'] ?? '') }}" maxlength="20"
                    placeholder="0812xxxxxxx" class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_email_ketua" class="{{ $kelasLabel }}">Email Kelompok</label>
                <input type="email" id="{{ $awalan }}_email_ketua" name="email"
                    value="{{ old('email', $data['email_ketua'] ?? '') }}" maxlength="100"
                    placeholder="poktan@example.id" class="{{ $kelasKontrol }}" />
            </div>
        </div>
    </section>

    <section>
        <h3 class="{{ $kelasBagian }}">Sekretariat</h3>
        <div class="mt-3 space-y-4">
            <div>
                <label for="{{ $awalan }}_alamat_sekretariat" class="{{ $kelasLabel }}">Alamat Sekretariat</label>
                <textarea id="{{ $awalan }}_alamat_sekretariat" name="alamat_sekretariat" rows="2" maxlength="255"
                    placeholder="Alamat tempat pertemuan kelompok."
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('alamat_sekretariat', $data['alamat_sekretariat'] ?? '') }}</textarea>
            </div>

            <x-sim.koordinat-input :lintang="old('lintang', $data['lintang'] ?? null)"
                :bujur="old('bujur', $data['bujur'] ?? null)" />
        </div>
    </section>
</div>