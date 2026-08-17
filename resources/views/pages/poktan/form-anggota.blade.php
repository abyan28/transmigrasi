{{--
    Isian keanggotaan kelompok tani.

    Aturan yang dijaga di sini: anggota yang berhenti DITANDAI Sudah Keluar,
    bukan dihapus (agents/rules.md bagian 5.1 catatan 5). Riwayat keanggotaan
    harus tetap utuh, sebab penyaluran saprotan di masa lalu menaut pada nama
    yang bersangkutan. Menghapusnya membuat catatan penyaluran kehilangan
    penerima.

    Karena itu form ini tidak menyediakan opsi hapus. Yang tersedia hanya
    perubahan status beserta tanggal keluar dan alasannya.

    Nama kolom mengikuti agents/data-dictionary.md bagian 8.2.
--}}
@php
    use App\Enums\JabatanAnggotaPoktan;
    use App\Enums\StatusKeaktifanAnggota;
    use App\Support\DummyData;

    $awalan = $awalan ?? 'tambah';
    $data = $data ?? [];
    $poktanId = $poktanId ?? null;

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';

    $daftarTransmigran = DummyData::transmigran();
    $keluar = StatusKeaktifanAnggota::SudahKeluar->value;
@endphp

<div class="space-y-6"
    x-data="{ status: @js(old('status', $data['status'] ?? StatusKeaktifanAnggota::Aktif->value)) }">

    @if ($poktanId)
        <input type="hidden" name="poktan_id" value="{{ $poktanId }}" />
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="{{ $awalan }}_transmigran_anggota" class="{{ $kelasLabel }}">Transmigran<span class="text-error-500">*</span></label>
            <select id="{{ $awalan }}_transmigran_anggota" name="transmigran_id" required class="{{ $kelasKontrol }}">
                <option value="">Pilih transmigran</option>
                @foreach ($daftarTransmigran as $t)
                    <option value="{{ $t['id_transmigran'] }}"
                        @selected((string) old('transmigran_id', $data['transmigran_id'] ?? '') === (string) $t['id_transmigran'])>
                        {{ $t['nama_kepala_keluarga'] }} &mdash; {{ $t['satuan_permukiman'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="{{ $awalan }}_jabatan_anggota" class="{{ $kelasLabel }}">Jabatan</label>
            <select id="{{ $awalan }}_jabatan_anggota" name="jabatan" class="{{ $kelasKontrol }}">
                @foreach (JabatanAnggotaPoktan::cases() as $j)
                    <option value="{{ $j->value }}" @selected(old('jabatan', $data['jabatan'] ?? '') === $j->value)>
                        {{ $j->value }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="{{ $awalan }}_tanggal_masuk_anggota" class="{{ $kelasLabel }}">Tanggal Masuk<span class="text-error-500">*</span></label>
            <input type="date" id="{{ $awalan }}_tanggal_masuk_anggota" name="tanggal_masuk" required
                value="{{ old('tanggal_masuk', $data['tanggal_masuk'] ?? '') }}" max="{{ date('Y-m-d') }}"
                class="{{ $kelasKontrol }}" />
        </div>

        <div class="sm:col-span-2">
            <label for="{{ $awalan }}_status_anggota" class="{{ $kelasLabel }}">Status Keaktifan</label>
            {{--
                Memakai @change, bukan x-model. Modal ubah mengisi isian ini
                dengan menyetel `.value` secara langsung, dan x-model akan
                menimpanya kembali dengan nilai awal Alpine sehingga bagian
                tanggal keluar tidak pernah muncul untuk anggota yang memang
                sudah berstatus Sudah Keluar.

                `x-init` menyelaraskan keadaan awal untuk kasus yang sama.
            --}}
            <select id="{{ $awalan }}_status_anggota" name="status"
                x-init="status = $el.value" @change="status = $event.target.value"
                class="{{ $kelasKontrol }}">
                @foreach (StatusKeaktifanAnggota::cases() as $s)
                    <option value="{{ $s->value }}"
                        @selected(old('status', $data['status'] ?? StatusKeaktifanAnggota::Aktif->value) === $s->value)>
                        {{ $s->value }}</option>
                @endforeach
            </select>
            <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                Hanya anggota berstatus Aktif yang dapat menerima penyaluran saprotan.
            </p>
        </div>
    </div>

    {{--
        Tanggal dan alasan keluar. Muncul hanya bila status Sudah Keluar,
        sebab mengisinya untuk anggota aktif akan bertentangan sendiri.
    --}}
    <div x-show="status === @js($keluar)" x-cloak x-transition
        class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
        <div>
            {{--
                Wajib bila status Sudah Keluar: tanpa tanggalnya, riwayat
                keanggotaan kehilangan batas akhir dan lama keanggotaan tidak
                dapat dihitung. Bintang statis, `required` mengikuti status.
            --}}
            <label for="{{ $awalan }}_tanggal_keluar_anggota" class="{{ $kelasLabel }}">
                Tanggal Keluar<span class="text-error-500">*</span>
            </label>
            <input type="date" id="{{ $awalan }}_tanggal_keluar_anggota" name="tanggal_keluar"
                value="{{ old('tanggal_keluar', $data['tanggal_keluar'] ?? '') }}" max="{{ date('Y-m-d') }}"
                :required="status === @js($keluar)" class="{{ $kelasKontrol }}" />
        </div>

        <div>
            <label for="{{ $awalan }}_keterangan_anggota" class="{{ $kelasLabel }}">Alasan Keluar</label>
            <textarea id="{{ $awalan }}_keterangan_anggota" name="keterangan" rows="2" maxlength="255"
                placeholder="Contoh: pindah ke luar kawasan."
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
        </div>
    </div>

    {{-- Penegasan aturan, terbaca saat mengisi bukan hanya di dokumen --}}
    <p class="rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
        Anggota yang berhenti ditandai <span class="font-medium">Sudah Keluar</span>, tidak dihapus dari
        daftar. Riwayat keanggotaan diperlukan agar catatan penyaluran saprotan di masa lalu tetap memiliki
        penerima yang jelas.
    </p>
</div>
