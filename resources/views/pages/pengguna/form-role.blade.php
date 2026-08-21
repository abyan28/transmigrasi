{{--
    Isian role beserta matriks kewenangan per fitur.

    Empat ketentuan yang dijaga di sini:

    1. Role bawaan yang terkunci (Admin) ditampilkan HANYA BACA beserta
       alasannya, bukan dengan kontrol yang tampak dapat diklik lalu ditolak
       diam-diam (agents/rules.md bagian 5.0a, ANTISLOP-ID R-26).
    2. Kotak centang hanya dirender untuk aksi yang benar-benar berlaku pada
       fitur tersebut. Dashboard tidak mengenal tambah maupun hapus, sehingga
       selnya dibiarkan kosong, bukan diisi kotak yang mustahil bermakna.
    3. Cakupan data adalah pilihan terpisah dari kewenangan. Kewenangan menjawab boleh
       melakukan apa, cakupan menjawab boleh melihat data siapa
       (rules.md bagian 5.0b).
    4. Menonaktifkan role tidak menghapusnya, agar riwayat audit log yang
       menyebut role tersebut tetap terbaca.

    Sumber daftar fitur adalah DummyData::daftarIzin(), yang menyalin tabel
    agents/rules.md bagian 5.1.
--}}
@php
    use App\Enums\CakupanData;
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    $kelompokIzin = DummyData::daftarIzin();
    $terkunci = (bool) ($data['is_terkunci'] ?? false);

    $izinDimiliki = ! empty($data['id_role'])
        ? DummyData::izinRole((int) $data['id_role'])
        : [];

    $semuaAksi = [
        'lihat' => 'Lihat',
        'tambah' => 'Tambah',
        'ubah' => 'Ubah',
        'hapus' => 'Hapus',
        'export' => 'Export',
    ];
@endphp

<div class="space-y-6">

    @if ($terkunci)
        {{--
            Role terkunci. Seluruh kontrol di bawah dirender tanpa kotak
            centang, sehingga tidak ada tombol yang menjanjikan perubahan
            yang sebenarnya akan ditolak.
        --}}
        <div class="rounded-lg border border-yellow-300 bg-yellow-50 p-4 dark:border-yellow-500/30 dark:bg-yellow-500/10"
            role="status">
            <p class="text-theme-sm text-yellow-800 dark:text-yellow-200">
                <span class="font-semibold">Role ini terkunci dan hanya dapat dilihat.</span>
                Kewenangan Admin dipertahankan utuh agar sistem tidak pernah kehilangan seluruh jalur
                administrasinya. Untuk membatasi kewenangan seseorang, buat role baru lalu pindahkan
                akunnya ke role tersebut.
            </p>
        </div>
    @endif

    {{-- Bagian 1: identitas role --}}
    <section>
        <h3 class="{{ $kelasBagian }}">Identitas Role</h3>
        <div class="mt-3 grid gap-4">
            <div>
                <label for="{{ $awalan }}_nama_role" class="{{ $kelasLabel }}">Nama Role<span class="text-error-500">*</span></label>
                <input type="text" id="{{ $awalan }}_nama_role" name="nama" required
                    value="{{ old('nama', $data['nama'] ?? '') }}" maxlength="50"
                    placeholder="Contoh: Penyuluh Lapangan" class="{{ $kelasKontrol }}"
                    @disabled($terkunci) />
            </div>

            <div>
                <label for="{{ $awalan }}_deskripsi_role" class="{{ $kelasLabel }}">Catatan</label>
                <textarea id="{{ $awalan }}_deskripsi_role" name="deskripsi" rows="2" maxlength="255"
                    placeholder="Jelaskan tugas pemegang role ini dalam satu kalimat."
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30"
                    @disabled($terkunci)>{{ old('deskripsi', $data['deskripsi'] ?? '') }}</textarea>
            </div>

            <div>
                <label for="{{ $awalan }}_cakupan_data" class="{{ $kelasLabel }}">Cakupan Data</label>
                <select id="{{ $awalan }}_cakupan_data" name="cakupan_data" class="{{ $kelasKontrol }}"
                    @disabled($terkunci)>
                    @foreach (CakupanData::cases() as $cakupan)
                        <option value="{{ $cakupan->value }}"
                            @selected(old('cakupan_data', $data['cakupan_data'] ?? CakupanData::Semua->value) === $cakupan->value)>
                            {{ $cakupan->value }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    Menentukan data siapa yang boleh dilihat, terpisah dari daftar kewenangan di bawah.
                    Role bercakupan Per SP membuat seluruh kewenangannya otomatis terbatas pada SP yang ditugaskan.
                </p>
            </div>
        </div>
    </section>

    {{-- Bagian 2: matriks kewenangan --}}
    <section>
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h3 class="{{ $kelasBagian }}">Kewenangan per Fitur</h3>
            <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                Sel kosong berarti aksi tersebut tidak berlaku pada fitur itu.
            </p>
        </div>

        <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
            <table class="min-w-full text-left text-theme-sm">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Fitur</th>
                        @foreach ($semuaAksi as $label)
                            <th scope="col" class="px-3 py-3 text-center font-medium text-gray-500 dark:text-gray-400">
                                {{ $label }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                @foreach ($kelompokIzin as $kelompok)
                    <tbody class="border-t border-gray-200 dark:border-gray-800">
                        <tr class="bg-gray-50/60 dark:bg-white/[0.02]">
                            <th scope="colgroup" colspan="6"
                                class="px-4 py-2 text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ $kelompok['kelompok'] }}
                            </th>
                        </tr>

                        @foreach ($kelompok['modul'] as $modul)
                            <tr class="border-t border-gray-100 dark:border-gray-800/60">
                                <th scope="row"
                                    class="px-4 py-2.5 text-left font-normal text-gray-700 dark:text-gray-300">
                                    {{ $modul['nama'] }}
                                </th>

                                @foreach (array_keys($semuaAksi) as $aksi)
                                    <td class="px-3 py-2.5 text-center">
                                        @if (in_array($aksi, $modul['aksi'], true))
                                            @php
                                                $dimiliki = in_array($aksi, $izinDimiliki[$modul['kunci']] ?? [], true);
                                            @endphp

                                            @if ($terkunci)
                                                {{-- Hanya baca: tanda centang atau tanda hubung, tanpa kontrol --}}
                                                @if ($dimiliki)
                                                    <span class="text-success-600 dark:text-success-400"
                                                        title="Diizinkan">&#10003;</span>
                                                    <span class="sr-only">{{ $semuaAksi[$aksi] }} diizinkan</span>
                                                @else
                                                    <span class="text-gray-300 dark:text-gray-700"
                                                        aria-hidden="true">&ndash;</span>
                                                    <span class="sr-only">{{ $semuaAksi[$aksi] }} tidak diizinkan</span>
                                                @endif
                                            @else
                                                <input type="checkbox"
                                                    name="izin[{{ $modul['kunci'] }}][]" value="{{ $aksi }}"
                                                    @checked($dimiliki)
                                                    aria-label="{{ $semuaAksi[$aksi] }} pada {{ $modul['nama'] }}"
                                                    class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700" />
                                            @endif
                                        @else
                                            <span class="text-gray-200 dark:text-gray-800" aria-hidden="true">&middot;</span>
                                            <span class="sr-only">{{ $semuaAksi[$aksi] }} tidak berlaku pada fitur ini</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                @endforeach
            </table>
        </div>
    </section>

    {{--
        Toggle "Role aktif" sengaja tidak disediakan, sejalan dengan formulir
        akun. Role baru selalu langsung aktif, sedangkan role yang tidak lagi
        dipakai dihapus lewat tombol pada halaman daftar. Menyediakan dua
        keadaan yang mirip, yaitu nonaktif dan terhapus, hanya menyulitkan
        admin menebak yang mana yang seharusnya dipakai.
    --}}
</div>
