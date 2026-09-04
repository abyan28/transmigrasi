@extends('layouts.app')

{{--
    Pengelolaan Konten Sistem (CMS) -- Task 9.6.

    Lima tab, tiap tab satu <form> yang menyimpan lewat `cms.simpan` (PUT)
    dengan penanda `tab`. Nilai awal dari `App\Support\KontenSistem` (bawaan =
    teks mockup lama), sehingga tampilan tidak berubah sebelum disunting.

    Pengunggahan logo/favicon DITUNDA: berkas publik butuh jalur serba-boleh
    tersendiri sedangkan unggahan wajib di cakram privat (rules.md 14a). Aset
    bundel tetap dipakai; blok logo hanya menampilkannya sebagai info.
--}}

@php
    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasArea = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
    $kelasBagian = 'text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';

    $simpanBtn = 'inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500';
@endphp

@section('content')
    <x-sim.page-header judul="Pengelolaan Konten"
        keterangan="Kelola identitas aplikasi, kop dokumen laporan, narasi profil, portal warga, dan pengumuman dinas tanpa mengubah kode."
        :remah="\App\Helpers\RemahHelper::untuk('/cms')">
        <x-slot:aksi>
            <a href="{{ route('tentang') }}" target="_blank"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Lihat Halaman Publik
            </a>
        </x-slot:aksi>
    </x-sim.page-header>

    <div x-data="hashTabs('identitas')" class="min-w-0 space-y-6">
        <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-gray-200 px-2 pt-2 dark:border-gray-800"
                role="tablist" aria-label="Pengelolaan Konten Sistem">
                @foreach ([
                    'identitas' => 'Identitas & Visual',
                    'laporan' => 'Kop & Dokumen Laporan',
                    'informasi' => 'Konten Profil & FAQ',
                    'portal' => 'Portal Pengaduan Warga',
                    'pengumuman' => 'Pengumuman Dinas',
                ] as $kunci => $label)
                    <button type="button" role="tab" @click="setTab('{{ $kunci }}')"
                        :aria-selected="tab === '{{ $kunci }}'"
                        :class="tab === '{{ $kunci }}'
                            ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="shrink-0 border-b-2 px-5 py-3 text-theme-sm font-medium transition focus:outline-2 focus:outline-offset-2 focus:outline-brand-500">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- ================================================================= --}}
            {{-- TAB 1: IDENTITAS & VISUAL --}}
            {{-- ================================================================= --}}
            <div x-show="tab === 'identitas'" role="tabpanel" class="p-5 sm:p-6">
                <form method="POST" action="{{ route('cms.simpan') }}" class="max-w-3xl space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tab" value="identitas" />

                    <section>
                        <h3 class="{{ $kelasBagian }}">Penamaan Aplikasi</h3>
                        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                            Ditampilkan pada bilah navigasi, header aplikasi, dan judul tab peramban.
                            Bila dikosongkan, sistem memakai nilai bawaan dari konfigurasi.
                        </p>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="cms_nama_app" class="{{ $kelasLabel }}">Nama Resmi Aplikasi<span class="text-error-500">*</span></label>
                                <input type="text" id="cms_nama_app" name="nama_app" required maxlength="100"
                                    value="{{ old('nama_app', $konten['identitas.nama_app']) }}" class="{{ $kelasKontrol }}" />
                                @error('nama_app') <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="cms_subjudul" class="{{ $kelasLabel }}">Subjudul & Lokus Kawasan<span class="text-error-500">*</span></label>
                                <input type="text" id="cms_subjudul" name="subjudul" required maxlength="255"
                                    value="{{ old('subjudul', $konten['identitas.subjudul']) }}" class="{{ $kelasKontrol }}" />
                                @error('subjudul') <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                        <h3 class="{{ $kelasBagian }}">Instansi Pembina & Pengelola</h3>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="cms_instansi_pusat" class="{{ $kelasLabel }}">Instansi Tingkat Pusat</label>
                                <input type="text" id="cms_instansi_pusat" name="instansi_pusat" maxlength="255"
                                    value="{{ old('instansi_pusat', $konten['identitas.instansi_pusat']) }}" class="{{ $kelasKontrol }}" />
                            </div>
                            <div>
                                <label for="cms_instansi_daerah" class="{{ $kelasLabel }}">Instansi Tingkat Daerah</label>
                                <input type="text" id="cms_instansi_daerah" name="instansi_daerah" maxlength="255"
                                    value="{{ old('instansi_daerah', $konten['identitas.instansi_daerah']) }}" class="{{ $kelasKontrol }}" />
                            </div>
                        </div>
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                        <h3 class="{{ $kelasBagian }}">Kontak Layanan Bantuan</h3>
                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="cms_email_bantuan" class="{{ $kelasLabel }}">Email Layanan</label>
                                <input type="email" id="cms_email_bantuan" name="email_bantuan" maxlength="150"
                                    value="{{ old('email_bantuan', $konten['identitas.email_bantuan']) }}" class="{{ $kelasKontrol }}" />
                                @error('email_bantuan') <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="cms_telp_bantuan" class="{{ $kelasLabel }}">Telepon Kantor</label>
                                <input type="text" id="cms_telp_bantuan" name="telepon_bantuan" maxlength="40"
                                    value="{{ old('telepon_bantuan', $konten['identitas.telepon_bantuan']) }}" class="{{ $kelasKontrol }} tabular-nums" />
                            </div>
                            <div>
                                <label for="cms_wa_bantuan" class="{{ $kelasLabel }}">WhatsApp Bantuan</label>
                                <input type="text" id="cms_wa_bantuan" name="wa_bantuan" maxlength="40"
                                    value="{{ old('wa_bantuan', $konten['identitas.wa_bantuan']) }}" class="{{ $kelasKontrol }} tabular-nums" />
                            </div>
                        </div>
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                        <h3 class="{{ $kelasBagian }}">Catatan Kaki & Hak Cipta</h3>
                        <div class="mt-4">
                            <label for="cms_footer" class="{{ $kelasLabel }}">Teks Catatan Kaki</label>
                            <textarea id="cms_footer" name="footer" rows="2" maxlength="500" class="{{ $kelasArea }}">{{ old('footer', $konten['identitas.footer']) }}</textarea>
                        </div>
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                        <h3 class="{{ $kelasBagian }}">Logo & Favicon</h3>
                        <p class="mt-2 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                            Penggantian logo dan favicon lewat unggahan menyusul: berkas publik memerlukan
                            jalur akses tersendiri, sedangkan seluruh unggahan sistem tersimpan di cakram privat.
                            Untuk saat ini aplikasi memakai lambang Kementerian Transmigrasi dan Kabupaten Malaka bawaan.
                        </p>
                    </section>

                    <div class="pt-2">
                        <button type="submit" class="{{ $simpanBtn }}">Simpan Identitas</button>
                    </div>
                </form>
            </div>

            {{-- ================================================================= --}}
            {{-- TAB 2: KOP & DOKUMEN LAPORAN --}}
            {{-- ================================================================= --}}
            <div x-show="tab === 'laporan'" x-cloak role="tabpanel" class="p-5 sm:p-6">
                <div class="grid gap-8 lg:grid-cols-12"
                    x-data="{
                        kop: {
                            kementerian: @js(old('kop_kementerian', $konten['kop.kementerian'])),
                            pemerintah: @js(old('kop_pemerintah', $konten['kop.pemerintah'])),
                            dinas: @js(old('kop_dinas', $konten['kop.dinas'])),
                            alamat: @js(old('kop_alamat', $konten['kop.alamat'])),
                            kontak: @js(old('kop_kontak', $konten['kop.kontak'])),
                        },
                        ttdNama: @js(old('ttd_nama', $konten['kop.ttd_nama'])),
                        ttdJabatan: @js(old('ttd_jabatan', $konten['kop.ttd_jabatan'])),
                        ttdNip: @js(old('ttd_nip', $konten['kop.ttd_nip'])),
                        tampilkanTtd: {{ old('tampilkan_ttd', $konten['kop.tampilkan_ttd']) ? 'true' : 'false' }},
                    }">
                    <form method="POST" action="{{ route('cms.simpan') }}" class="space-y-6 lg:col-span-6">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="tab" value="laporan" />

                        <section>
                            <h3 class="{{ $kelasBagian }}">Kop Surat Dokumen Laporan</h3>
                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                Teks yang dicetak pada bagian atas seluruh laporan resmi
                                (<a href="{{ route('laporan.transmigran') }}" target="_blank" class="text-brand-600 hover:underline">/laporan</a>).
                            </p>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="cms_kop_kementerian" class="{{ $kelasLabel }}">Nama Kementerian Pembina<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_kop_kementerian" name="kop_kementerian" required maxlength="255"
                                        x-model="kop.kementerian" class="{{ $kelasKontrol }}" />
                                    @error('kop_kementerian') <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="cms_kop_pemda" class="{{ $kelasLabel }}">Pemerintah Daerah<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_kop_pemda" name="kop_pemerintah" required maxlength="255"
                                        x-model="kop.pemerintah" class="{{ $kelasKontrol }}" />
                                </div>
                                <div>
                                    <label for="cms_kop_dinas" class="{{ $kelasLabel }}">Nama Dinas Pelaksana<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_kop_dinas" name="kop_dinas" required maxlength="255"
                                        x-model="kop.dinas" class="{{ $kelasKontrol }}" />
                                    @error('kop_dinas') <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="cms_kop_alamat" class="{{ $kelasLabel }}">Alamat Kantor Dinas<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_kop_alamat" name="kop_alamat" required maxlength="500"
                                        x-model="kop.alamat" class="{{ $kelasKontrol }}" />
                                </div>
                                <div>
                                    <label for="cms_kop_kontak" class="{{ $kelasLabel }}">Kontak & Layanan Dinas<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_kop_kontak" name="kop_kontak" required maxlength="255"
                                        x-model="kop.kontak" class="{{ $kelasKontrol }}" />
                                </div>
                            </div>
                        </section>

                        <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                            <div class="flex items-center justify-between">
                                <h3 class="{{ $kelasBagian }}">Pejabat Penandatangan Dokumen</h3>
                                <label class="inline-flex cursor-pointer items-center gap-2">
                                    <input type="checkbox" name="tampilkan_ttd" value="1" x-model="tampilkanTtd"
                                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                                    <span class="text-theme-sm font-medium text-gray-700 dark:text-gray-300"
                                        x-text="tampilkanTtd ? 'Ttd ditampilkan' : 'Ttd disembunyikan'"></span>
                                </label>
                            </div>
                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                Dicetak pada bagian akhir laporan sebagai lembar pengesahan resmi.
                            </p>
                            <div class="mt-4 space-y-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="cms_titimangsa" class="{{ $kelasLabel }}">Kota / Tempat Titimangsa<span class="text-error-500">*</span></label>
                                        <input type="text" id="cms_titimangsa" name="titimangsa_tempat" required maxlength="100"
                                            value="{{ old('titimangsa_tempat', $konten['kop.titimangsa_tempat']) }}" class="{{ $kelasKontrol }}" />
                                    </div>
                                    <div>
                                        <label for="cms_ttd_pangkat" class="{{ $kelasLabel }}">Pangkat / Golongan</label>
                                        <input type="text" id="cms_ttd_pangkat" name="ttd_pangkat" maxlength="100"
                                            value="{{ old('ttd_pangkat', $konten['kop.ttd_pangkat']) }}" class="{{ $kelasKontrol }}" />
                                    </div>
                                </div>
                                <div>
                                    <label for="cms_ttd_jabatan" class="{{ $kelasLabel }}">Jabatan Penandatangan<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_ttd_jabatan" name="ttd_jabatan" required maxlength="255"
                                        x-model="ttdJabatan" class="{{ $kelasKontrol }}" />
                                </div>
                                <div>
                                    <label for="cms_ttd_nama" class="{{ $kelasLabel }}">Nama Lengkap & Gelar Pejabat<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_ttd_nama" name="ttd_nama" required maxlength="255"
                                        x-model="ttdNama" class="{{ $kelasKontrol }}" />
                                </div>
                                <div>
                                    <label for="cms_ttd_nip" class="{{ $kelasLabel }}">Nomor Induk Pegawai (NIP)<span class="text-error-500">*</span></label>
                                    <input type="text" id="cms_ttd_nip" name="ttd_nip" required maxlength="40"
                                        x-model="ttdNip" class="{{ $kelasKontrol }} tabular-nums" />
                                </div>
                            </div>
                        </section>

                        <div class="pt-2">
                            <button type="submit" class="{{ $simpanBtn }}">Simpan Format Laporan</button>
                        </div>
                    </form>

                    {{-- Pratinjau kop A4 --}}
                    <div class="lg:col-span-6">
                        <div class="sticky top-24 rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                            <h4 class="border-b border-gray-200 pb-3 text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                Pratinjau Kop Dokumen
                            </h4>
                            <div class="mt-4 rounded-xl border border-gray-300 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                                <div class="border-b-[2.5px] border-gray-900 pb-3 text-center dark:border-gray-100">
                                    <p class="text-[11px] font-semibold uppercase text-gray-700 dark:text-gray-300" x-text="kop.kementerian"></p>
                                    <p class="text-[11px] font-semibold uppercase text-gray-700 dark:text-gray-300" x-text="kop.pemerintah"></p>
                                    <p class="text-theme-xs font-bold uppercase text-gray-900 dark:text-white" x-text="kop.dinas"></p>
                                    <p class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400" x-text="kop.alamat"></p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400" x-text="kop.kontak"></p>
                                </div>
                                <p class="py-6 text-center text-theme-xs text-gray-400">... isi laporan ...</p>
                                <div x-show="tampilkanTtd" x-cloak class="ml-auto max-w-[16rem] text-center text-theme-xs text-gray-700 dark:text-gray-300">
                                    <p x-text="ttdJabatan"></p>
                                    <p class="mt-12 font-semibold underline" x-text="ttdNama"></p>
                                    <p class="tabular-nums">NIP <span x-text="ttdNip"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================================================================= --}}
            {{-- TAB 3: KONTEN PROFIL & FAQ --}}
            {{-- ================================================================= --}}
            <div x-show="tab === 'informasi'" x-cloak role="tabpanel" class="p-5 sm:p-6">
                <form method="POST" action="{{ route('cms.simpan') }}" class="max-w-3xl space-y-6"
                    x-data="{
                        faqs: @js(count(old('faq', $faq)) ? array_values(old('faq', $faq)) : [['tanya' => '', 'jawab' => '']]),
                        tambah() { this.faqs.push({ tanya: '', jawab: '' }); },
                        hapus(i) { this.faqs.splice(i, 1); if (! this.faqs.length) this.tambah(); },
                    }">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tab" value="informasi" />

                    <section>
                        <h3 class="{{ $kelasBagian }}">Narasi Halaman Tentang Sistem</h3>
                        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                            Tampil pada halaman <a href="{{ route('tentang') }}" target="_blank" class="text-brand-600 hover:underline">/tentang</a>.
                        </p>
                        <div class="mt-4">
                            <label for="cms_latar" class="{{ $kelasLabel }}">Latar Belakang & Tujuan Kawasan</label>
                            <textarea id="cms_latar" name="latar_belakang" rows="5" maxlength="5000" class="{{ $kelasArea }}">{{ old('latar_belakang', $konten['profil.latar_belakang']) }}</textarea>
                        </div>
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="{{ $kelasBagian }}">Tanya Jawab / FAQ</h3>
                                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                    Tampil pada modul <a href="{{ route('panduan') }}" target="_blank" class="text-brand-600 hover:underline">/panduan</a>.
                                    Baris kosong diabaikan saat menyimpan.
                                </p>
                            </div>
                            <button type="button" @click="tambah()"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                + Tambah FAQ
                            </button>
                        </div>
                        <div class="mt-4 space-y-3">
                            <template x-for="(faq, i) in faqs" :key="i">
                                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 space-y-2">
                                            <input type="text" :name="`faq[${i}][tanya]`" x-model="faq.tanya" maxlength="255"
                                                placeholder="Pertanyaan" class="{{ $kelasKontrol }} font-medium" />
                                            <textarea :name="`faq[${i}][jawab]`" x-model="faq.jawab" rows="2" maxlength="2000"
                                                placeholder="Jawaban" class="{{ $kelasArea }}"></textarea>
                                        </div>
                                        <button type="button" @click="hapus(i)" aria-label="Hapus FAQ"
                                            class="rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </section>

                    <div class="pt-2">
                        <button type="submit" class="{{ $simpanBtn }}">Simpan Konten Informasi</button>
                    </div>
                </form>
            </div>

            {{-- ================================================================= --}}
            {{-- TAB 4: PORTAL PENGADUAN WARGA --}}
            {{-- ================================================================= --}}
            <div x-show="tab === 'portal'" x-cloak role="tabpanel" class="p-5 sm:p-6">
                <form method="POST" action="{{ route('cms.simpan') }}" class="max-w-3xl space-y-6"
                    x-data="{ awalan: @js(old('awalan_nomor', $konten['portal.awalan_nomor'])) }">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tab" value="portal" />

                    <section>
                        <h3 class="{{ $kelasBagian }}">Teks Sambutan & Jaminan Kerahasiaan</h3>
                        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                            Tampil pada form pengaduan warga tanpa login
                            (<a href="{{ route('pengaduan-warga') }}" target="_blank" class="text-brand-600 hover:underline">/pengaduan-warga</a>).
                        </p>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="cms_sambutan" class="{{ $kelasLabel }}">Teks Sambutan Warga</label>
                                <textarea id="cms_sambutan" name="sambutan" rows="3" maxlength="1000" class="{{ $kelasArea }}">{{ old('sambutan', $konten['portal.sambutan']) }}</textarea>
                            </div>
                            <div>
                                <label for="cms_disclaimer" class="{{ $kelasLabel }}">Teks Jaminan Kerahasiaan</label>
                                <textarea id="cms_disclaimer" name="disclaimer" rows="2" maxlength="1000" class="{{ $kelasArea }}">{{ old('disclaimer', $konten['portal.disclaimer']) }}</textarea>
                            </div>
                        </div>
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-gray-800">
                        <h3 class="{{ $kelasBagian }}">Format Nomor Tiket & Kontak Darurat</h3>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="cms_awalan" class="{{ $kelasLabel }}">Awalan Nomor Tiket<span class="text-error-500">*</span></label>
                                <input type="text" id="cms_awalan" name="awalan_nomor" required maxlength="6"
                                    x-model="awalan" class="{{ $kelasKontrol }} font-mono uppercase" />
                                @error('awalan_nomor') <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p> @enderror
                                <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                                    Contoh hasil:
                                    <code class="font-mono text-brand-600" x-text="(awalan || 'PGD').toUpperCase() + '-{{ date('Y') }}-0042-K7F2M9'"></code>
                                </p>
                                <p class="mt-2 rounded-lg bg-gray-50 p-2.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                                    Bagian acak (<code class="font-mono">K7F2M9</code>) SELALU ditambahkan sistem dan tidak dapat
                                    dihilangkan. Halaman lacak terbuka tanpa login, sehingga nomor yang dapat ditebak
                                    membuat laporan warga lain ikut terbaca.
                                </p>
                            </div>
                            <div>
                                <label for="cms_hotline" class="{{ $kelasLabel }}">Hotline / WhatsApp Cepat</label>
                                <input type="text" id="cms_hotline" name="hotline" maxlength="40"
                                    value="{{ old('hotline', $konten['portal.hotline']) }}" class="{{ $kelasKontrol }} tabular-nums" />
                            </div>
                        </div>
                    </section>

                    <div class="pt-2">
                        <button type="submit" class="{{ $simpanBtn }}">Simpan Pengaturan Portal</button>
                    </div>
                </form>
            </div>

            {{-- ================================================================= --}}
            {{-- TAB 5: PENGUMUMAN DINAS --}}
            {{-- ================================================================= --}}
            <div x-show="tab === 'pengumuman'" x-cloak role="tabpanel" class="p-5 sm:p-6">
                <div class="grid gap-8 lg:grid-cols-12"
                    x-data="{
                        aktif: {{ old('aktif', $konten['pengumuman.aktif']) ? 'true' : 'false' }},
                        judul: @js(old('judul', $konten['pengumuman.judul'])),
                        isi: @js(old('isi', $konten['pengumuman.isi'])),
                        tipe: @js(old('tipe', $konten['pengumuman.tipe'])),
                    }">
                    <form method="POST" action="{{ route('cms.simpan') }}" class="space-y-6 lg:col-span-7">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="tab" value="pengumuman" />

                        <section>
                            <div class="flex items-center justify-between">
                                <h3 class="{{ $kelasBagian }}">Pengumuman Broadcast Dasbor</h3>
                                <label class="inline-flex cursor-pointer items-center gap-2">
                                    <input type="checkbox" name="aktif" value="1" x-model="aktif"
                                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                                    <span class="text-theme-sm font-medium text-gray-700 dark:text-gray-300"
                                        x-text="aktif ? 'Aktif' : 'Dinonaktifkan'"></span>
                                </label>
                            </div>
                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                Banner yang tampil di bagian atas dashboard seluruh petugas.
                            </p>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="cms_judul_peng" class="{{ $kelasLabel }}">Judul Pengumuman<span class="text-error-500" x-show="aktif">*</span></label>
                                    <input type="text" id="cms_judul_peng" name="judul" maxlength="255"
                                        x-model="judul" :required="aktif" class="{{ $kelasKontrol }}" />
                                    @error('judul') <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="cms_tipe_peng" class="{{ $kelasLabel }}">Tingkat Kegentingan</label>
                                    <select id="cms_tipe_peng" name="tipe" x-model="tipe" class="{{ $kelasKontrol }}">
                                        <option value="info">Informasi Umum</option>
                                        <option value="success">Kabar Baik</option>
                                        <option value="warning">Peringatan</option>
                                        <option value="error">Mendesak / Darurat</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="cms_isi_peng" class="{{ $kelasLabel }}">Isi Pesan Pengumuman<span class="text-error-500" x-show="aktif">*</span></label>
                                    <textarea id="cms_isi_peng" name="isi" rows="4" maxlength="2000"
                                        x-model="isi" :required="aktif" class="{{ $kelasArea }}"></textarea>
                                    @error('isi') <p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <div class="pt-2">
                            <button type="submit" class="{{ $simpanBtn }}">Simpan Pengumuman</button>
                        </div>
                    </form>

                    <div class="lg:col-span-5">
                        <div class="sticky top-24 rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                            <h4 class="border-b border-gray-200 pb-3 text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                Pratinjau Banner Dasbor
                            </h4>
                            <div class="mt-4">
                                <template x-if="aktif && (judul || isi)">
                                    <div class="rounded-xl border p-4"
                                        :class="{
                                            'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200': tipe === 'info',
                                            'border-green-200 bg-green-50 text-green-900 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-200': tipe === 'success',
                                            'border-yellow-300 bg-yellow-50 text-yellow-900 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-200': tipe === 'warning',
                                            'border-red-200 bg-red-50 text-red-900 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200': tipe === 'error',
                                        }">
                                        <h5 class="text-theme-sm font-bold" x-text="judul"></h5>
                                        <p class="mt-1 text-theme-xs leading-relaxed opacity-90" x-text="isi"></p>
                                    </div>
                                </template>
                                <template x-if="! aktif || (! judul && ! isi)">
                                    <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-theme-sm text-gray-400 dark:border-gray-700">
                                        Banner pengumuman tidak tampil pada dasbor.
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
