{{--
    Isian data sarana produksi pertanian.

    Dua aturan yang dijaga di sini:

    1. Penerima berubah mengikuti jenis penerima: kelompok tani atau individu
       (agents/rules.md bagian 7c). Tidak pernah keduanya sekaligus.
    2. Pilihan penerima individu HANYA memuat anggota berstatus aktif.
       Anggota yang sudah keluar tetap tersimpan pada riwayat keanggotaan,
       tetapi bantuan tidak boleh disalurkan kepadanya. Menyaringnya di sini
       mencegah kekeliruan penyaluran sejak dari isian, bukan setelah
       tersimpan.

    Nama kolom mengikuti agents/data-dictionary.md bagian 8.4.
--}}
@php
    use App\Enums\JenisSaprotan;
    use App\Enums\SumberDana;
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    $daftarSp = DummyData::satuanPermukiman();
    $daftarPoktan = DummyData::poktan();
    $daftarSatuan = DummyData::satuan();

    // Hanya anggota aktif yang boleh menerima penyaluran (rules.md 7c poin 4).
    $anggotaAktif = array_values(array_filter(
        DummyData::anggotaPoktan(),
        fn ($a) => $a['status'] === 'Aktif',
    ));
@endphp

<div class="space-y-6"
    x-data="{ jenisPenerima: @js(old('jenis_penerima', $data['jenis_penerima'] ?? 'Poktan')) }">

    {{-- Bagian 1: identitas sarana --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Sarana</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="{{ $awalan }}_jenis" class="{{ $kelasLabel }}">Jenis Saprotan<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_jenis" name="jenis" required class="{{ $kelasKontrol }}">
                    <option value="">Pilih jenis</option>
                    @foreach (JenisSaprotan::cases() as $j)
                        <option value="{{ $j->value }}" @selected(old('jenis', $data['jenis'] ?? '') === $j->value)>
                            {{ $j->value }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_nama" class="{{ $kelasLabel }}">Nama Sarana<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama" name="nama" required
                    value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: BENIH JAGUNG HIBRIDA" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_jumlah" class="{{ $kelasLabel }}">Jumlah<span class="text-error-500">*</span></label>
                <input type="number" id="{{ $awalan }}_jumlah" name="jumlah" required
                    value="{{ old('jumlah', $data['jumlah'] ?? '') }}" min="0" step="0.01"
                    class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_satuan_id" class="{{ $kelasLabel }}">Satuan<span class="text-error-500">*</span></label>
                <select id="{{ $awalan }}_satuan_id" name="satuan_id" required class="{{ $kelasKontrol }}">
                    <option value="">Pilih satuan</option>
                    @foreach ($daftarSatuan as $s)
                        <option value="{{ $s['id_satuan'] }}"
                            @selected(old('satuan', $data['satuan'] ?? '') === $s['nama'])>
                            {{ $s['nama'] }} ({{ $s['simbol'] }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_tanggal_perolehan" class="{{ $kelasLabel }}">Tanggal Perolehan</label>
                <input type="date" id="{{ $awalan }}_tanggal_perolehan" name="tanggal_perolehan"
                    value="{{ old('tanggal_perolehan', $data['tanggal_perolehan'] ?? '') }}"
                    max="{{ date('Y-m-d') }}" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_sumber" class="{{ $kelasLabel }}">Sumber Dana</label>
                <select id="{{ $awalan }}_sumber" name="sumber" class="{{ $kelasKontrol }}">
                    <option value="">Pilih sumber</option>
                    @foreach (SumberDana::cases() as $s)
                        <option value="{{ $s->value }}" @selected(old('sumber', $data['sumber'] ?? '') === $s->value)>
                            {{ $s->value }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    {{-- Bagian 2: penerima --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Penerima</h3>
        <div class="mt-3 space-y-4">
            <div>
                <span class="{{ $kelasLabel }}">Jenis Penerima</span>
                <div class="flex flex-wrap gap-4">
                    @foreach (['Poktan', 'Individu'] as $jp)
                        <label class="flex items-center gap-2.5">
                            <input type="radio" name="jenis_penerima" value="{{ $jp }}"
                                x-model="jenisPenerima"
                                class="h-4 w-4 border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700" />
                            <span class="text-theme-sm text-gray-700 dark:text-gray-300">{{ $jp }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{--
                Wajib bersyarat. Bintang dipasang statis sebab isian ini hanya
                muncul ketika syaratnya berlaku, sedangkan `required` menyala
                mengikuti jenis penerima agar isian yang tersembunyi tidak
                menghalangi pengiriman form (pola sama dengan rumah/form).
            --}}
            <div x-show="jenisPenerima === 'Poktan'" x-cloak x-transition>
                <label for="{{ $awalan }}_poktan_id" class="{{ $kelasLabel }}">
                    Kelompok Tani Penerima<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_poktan_id" name="poktan_id" class="{{ $kelasKontrol }}"
                    :required="jenisPenerima === 'Poktan'">
                    <option value="">Pilih kelompok tani</option>
                    @foreach ($daftarPoktan as $p)
                        <option value="{{ $p['id_poktan'] }}"
                            @selected((string) old('poktan_id', $data['poktan_id'] ?? '') === (string) $p['id_poktan'])>
                            {{ $p['nama'] }} &mdash; {{ $p['satuan_permukiman'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div x-show="jenisPenerima === 'Individu'" x-cloak x-transition>
                <label for="{{ $awalan }}_transmigran_id" class="{{ $kelasLabel }}">
                    Anggota Penerima<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_transmigran_id" name="transmigran_id" class="{{ $kelasKontrol }}"
                    :required="jenisPenerima === 'Individu'">
                    <option value="">Pilih anggota</option>
                    @foreach ($anggotaAktif as $a)
                        <option value="{{ $a['transmigran_id'] }}"
                            @selected((string) old('transmigran_id', $data['transmigran_id'] ?? '') === (string) $a['transmigran_id'])>
                            {{ $a['nama'] }} &mdash; {{ $a['poktan'] }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Hanya anggota berstatus aktif yang dapat menerima penyaluran. Anggota yang sudah keluar
                    tetap tersimpan pada riwayat keanggotaan, tetapi tidak muncul di sini.
                </p>
            </div>

            <div>
                <label for="{{ $awalan }}_satuan_permukiman_id" class="{{ $kelasLabel }}">Satuan Permukiman</label>
                <select id="{{ $awalan }}_satuan_permukiman_id" name="satuan_permukiman_id"
                    class="{{ $kelasKontrol }}">
                    <option value="">Pilih satuan permukiman</option>
                    @foreach ($daftarSp as $sp)
                        <option value="{{ $sp['id_satuan_permukiman'] }}"
                            @selected((string) old('satuan_permukiman_id', $data['satuan_permukiman_id'] ?? '') === (string) $sp['id_satuan_permukiman'])>
                            {{ $sp['nama'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>
</div>
