{{--
    Isian data alsintan, dipakai bersama modal tambah dan modal ubah.

    Aturan yang dijaga di sini: kolom pemilik BERUBAH mengikuti jenis
    kepemilikan (agents/rules.md bagian 7c). Alat bantuan tercatat atas nama
    kelompok tani, alat pribadi atas nama transmigran. Menampilkan kedua
    pilihan sekaligus membuat petugas dapat mengisi keduanya, sehingga
    pemiliknya menjadi ganda dan pertanggungjawabannya kabur.

    Nama kolom mengikuti agents/data-dictionary.md bagian 8.3.
--}}
@php
    use App\Enums\KepemilikanAlsintan;
    use App\Enums\Kondisi;
    use App\Enums\SumberDana;
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    $daftarSp = DummyData::satuanPermukiman();
    $daftarPoktan = DummyData::poktan();
    $daftarTransmigran = DummyData::transmigran();
@endphp

<div class="space-y-6"
    x-data="{ kepemilikan: @js(old('kepemilikan', $data['kepemilikan'] ?? KepemilikanAlsintan::BantuanPoktan->value)) }">

    {{-- Bagian 1: identitas alat --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Alat</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="{{ $awalan }}_nama_alat" class="{{ $kelasLabel }}">Nama Alat<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_alat" name="nama_alat" required
                    value="{{ old('nama_alat', $data['nama_alat'] ?? '') }}" maxlength="100"
                    placeholder="Contoh: TRAKTOR RODA DUA" class="{{ $kelasKontrol }}" />
            </div>

            <div>
                <label for="{{ $awalan }}_jumlah" class="{{ $kelasLabel }}">Jumlah Unit<span class="text-error-500">*</span></label>
                <input type="number" id="{{ $awalan }}_jumlah" name="jumlah" required
                    value="{{ old('jumlah', $data['jumlah'] ?? '') }}" min="1" step="1"
                    class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_tahun_perolehan" class="{{ $kelasLabel }}">Tahun Perolehan</label>
                <input type="number" id="{{ $awalan }}_tahun_perolehan" name="tahun_perolehan"
                    value="{{ old('tahun_perolehan', $data['tahun_perolehan'] ?? '') }}" min="1900"
                    max="{{ date('Y') }}" class="{{ $kelasKontrol }} tabular-nums" />
            </div>

            <div>
                <label for="{{ $awalan }}_kondisi" class="{{ $kelasLabel }}">Kondisi</label>
                <select id="{{ $awalan }}_kondisi" name="kondisi" class="{{ $kelasKontrol }}">
                    @foreach (Kondisi::cases() as $k)
                        <option value="{{ $k->value }}" @selected(old('kondisi', $data['kondisi'] ?? '') === $k->value)>
                            {{ $k->value }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $awalan }}_sumber_perolehan" class="{{ $kelasLabel }}">Sumber Perolehan</label>
                <select id="{{ $awalan }}_sumber_perolehan" name="sumber_perolehan" class="{{ $kelasKontrol }}">
                    <option value="">Pilih sumber</option>
                    @foreach (SumberDana::cases() as $s)
                        <option value="{{ $s->value }}"
                            @selected(old('sumber_perolehan', $data['sumber_perolehan'] ?? '') === $s->value)>
                            {{ $s->value }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    {{-- Bagian 2: kepemilikan --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Kepemilikan</h3>
        <div class="mt-3 space-y-4">
            <div>
                <span class="{{ $kelasLabel }}">Jenis Kepemilikan</span>
                <div class="flex flex-wrap gap-4">
                    @foreach (KepemilikanAlsintan::cases() as $kp)
                        <label class="flex items-center gap-2.5">
                            <input type="radio" name="kepemilikan" value="{{ $kp->value }}"
                                x-model="kepemilikan"
                                class="h-4 w-4 border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700" />
                            <span class="text-theme-sm text-gray-700 dark:text-gray-300">{{ $kp->value }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{--
                Pemilik ditampilkan bergantian, tidak pernah keduanya sekaligus,
                agar satu alat tidak berakhir memiliki dua pemilik.
            --}}
            {{--
                Wajib bersyarat. Bintang dipasang statis sebab isian ini hanya
                muncul ketika syaratnya berlaku, sedangkan `required` menyala
                mengikuti pilihan kepemilikan agar isian yang tersembunyi tidak
                menghalangi pengiriman form (pola sama dengan rumah/form).
            --}}
            <div x-show="kepemilikan === @js(KepemilikanAlsintan::BantuanPoktan->value)" x-cloak x-transition>
                <label for="{{ $awalan }}_poktan_id" class="{{ $kelasLabel }}">
                    Kelompok Tani Pemilik<span class="text-error-500">*</span>
                </label>
                <select id="{{ $awalan }}_poktan_id" name="poktan_id" class="{{ $kelasKontrol }}"
                    :required="kepemilikan === @js(KepemilikanAlsintan::BantuanPoktan->value)">
                    <option value="">Pilih kelompok tani</option>
                    @foreach ($daftarPoktan as $p)
                        <option value="{{ $p['id_poktan'] }}"
                            @selected((string) old('poktan_id', $data['poktan_id'] ?? '') === (string) $p['id_poktan'])>
                            {{ $p['nama'] }} &mdash; {{ $p['satuan_permukiman'] }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Alat bantuan dipakai bergilir antar-anggota, sehingga tercatat atas nama kelompok.
                </p>
            </div>

            <div x-show="kepemilikan === @js(KepemilikanAlsintan::Pribadi->value)" x-cloak x-transition>
                {{-- Wajib hanya saat kepemilikan Pribadi, sebab bagian ini
                     ikut tersembunyi untuk alsintan bantuan poktan. --}}
                <x-sim.pilih-cari nama="transmigran_id" label="Transmigran Pemilik" :wajib="true"
                    :awalan="$awalan" :opsi="$daftarTransmigran" kunci="id_transmigran"
                    teks="nama_kepala_keluarga" keterangan-opsi="satuan_permukiman"
                    :terpilih="old('transmigran_id', $data['transmigran_id'] ?? null)"
                    placeholder="Pilih transmigran"
                    :required="'kepemilikan === ' . json_encode(KepemilikanAlsintan::Pribadi->value)" />
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

    {{--
        Dokumen pendukung. Kolomnya sudah ada pada data-dictionary.md 8.3
        tetapi belum pernah punya isian, sehingga bukti penyerahan alsintan
        bantuan tidak dapat diunggah ke mana pun.
    --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Dokumen Pendukung</h3>
        <div class="mt-3">
            <x-sim.file-upload nama="dokumen_pendukung" label="Dokumen atau Foto Alat"
                nama-dokumen="Dokumen Alsintan" :nama-pemilik="$data['nama_alat'] ?? null"
                :berkas-saat-ini="$data['dokumen_pendukung'] ?? null"
                keterangan="Berita acara penyerahan, bukti pembelian, atau foto alat." />
        </div>
    </section>
</div>
