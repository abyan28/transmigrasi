<?php

namespace App\Http\Controllers;

use App\Support\KontenSistem;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pengelolaan Konten Sistem / CMS (Task 9.6).
 *
 * Enam tab pada satu halaman; tiap tab formnya sendiri dan menyimpan lewat
 * aksi yang sama dengan penanda `tab`. Seluruh nilai lewat `App\Support\
 * KontenSistem` -- bawaan (teks mockup Task 2.31) berlaku sampai dinas
 * menyuntingnya, sehingga tampilan tidak berubah sebelum disentuh.
 *
 * Berkas (logo/favicon) belum dikelola: aset publik butuh jalur serba-boleh
 * tersendiri sedangkan berkas unggahan wajib di cakram privat (`rules.md` 14a).
 */
class CmsController extends Controller
{
    public function index(): View
    {
        return view('pages.cms.index', [
            'title' => 'Pengelolaan Konten',
            'konten' => KontenSistem::semua(),
            'faq' => KontenSistem::faq(),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $tab = $request->input('tab', 'identitas');

        $data = match ($tab) {
            'laporan' => $this->simpanLaporan($request),
            'informasi' => $this->simpanInformasi($request),
            'portal' => $this->simpanPortal($request),
            'pengumuman' => $this->simpanPengumuman($request),
            'surel' => $this->simpanSurel($request),
            default => $this->simpanIdentitas($request),
        };

        KontenSistem::simpan($data);

        return redirect()->route('cms', ['tab' => $tab])->with('sukses', 'Pengaturan konten berhasil disimpan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function simpanIdentitas(Request $request): array
    {
        $v = $request->validate([
            'nama_app' => ['required', 'string', 'max:100'],
            'subjudul' => ['required', 'string', 'max:255'],
            'instansi_pusat' => ['nullable', 'string', 'max:255'],
            'instansi_daerah' => ['nullable', 'string', 'max:255'],
            'email_bantuan' => ['nullable', 'email:rfc', 'max:150'],
            'telepon_bantuan' => ['nullable', 'string', 'max:40'],
            'wa_bantuan' => ['nullable', 'string', 'max:40'],
            'footer' => ['nullable', 'string', 'max:500'],
        ], [
            'nama_app.required' => 'Nama resmi aplikasi wajib diisi.',
            'subjudul.required' => 'Subjudul kawasan wajib diisi.',
        ] + ValidationRules::pesan());

        return [
            'identitas.nama_app' => $v['nama_app'],
            'identitas.subjudul' => $v['subjudul'],
            'identitas.instansi_pusat' => $v['instansi_pusat'] ?? '',
            'identitas.instansi_daerah' => $v['instansi_daerah'] ?? '',
            'identitas.email_bantuan' => $v['email_bantuan'] ?? '',
            'identitas.telepon_bantuan' => $v['telepon_bantuan'] ?? '',
            'identitas.wa_bantuan' => $v['wa_bantuan'] ?? '',
            'identitas.footer' => $v['footer'] ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function simpanLaporan(Request $request): array
    {
        $v = $request->validate([
            'kop_kementerian' => ['required', 'string', 'max:255'],
            'kop_pemerintah' => ['required', 'string', 'max:255'],
            'kop_dinas' => ['required', 'string', 'max:255'],
            'kop_alamat' => ['required', 'string', 'max:500'],
            'kop_kontak' => ['required', 'string', 'max:255'],
            'tampilkan_ttd' => ['nullable', 'boolean'],
            'titimangsa_tempat' => ['required', 'string', 'max:100'],
            'ttd_jabatan' => ['required', 'string', 'max:255'],
            'ttd_nama' => ['required', 'string', 'max:255'],
            'ttd_pangkat' => ['nullable', 'string', 'max:100'],
            'ttd_nip' => ['required', 'string', 'max:40'],
        ], [
            'kop_kementerian.required' => 'Nama kementerian pembina wajib diisi.',
            'kop_dinas.required' => 'Nama dinas pelaksana wajib diisi.',
            'titimangsa_tempat.required' => 'Kota titimangsa wajib diisi.',
            'ttd_jabatan.required' => 'Jabatan penandatangan wajib diisi.',
            'ttd_nama.required' => 'Nama pejabat wajib diisi.',
            'ttd_nip.required' => 'NIP pejabat wajib diisi.',
        ] + ValidationRules::pesan());

        return [
            'kop.kementerian' => $v['kop_kementerian'],
            'kop.pemerintah' => $v['kop_pemerintah'],
            'kop.dinas' => $v['kop_dinas'],
            'kop.alamat' => $v['kop_alamat'],
            'kop.kontak' => $v['kop_kontak'],
            'kop.tampilkan_ttd' => $request->boolean('tampilkan_ttd'),
            'kop.titimangsa_tempat' => $v['titimangsa_tempat'],
            'kop.ttd_jabatan' => $v['ttd_jabatan'],
            'kop.ttd_nama' => $v['ttd_nama'],
            'kop.ttd_pangkat' => $v['ttd_pangkat'] ?? '',
            'kop.ttd_nip' => $v['ttd_nip'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function simpanInformasi(Request $request): array
    {
        $v = $request->validate([
            'latar_belakang' => ['nullable', 'string', 'max:5000'],
            'faq' => ['nullable', 'array', 'max:50'],
            'faq.*.tanya' => ['nullable', 'string', 'max:255'],
            'faq.*.jawab' => ['nullable', 'string', 'max:2000'],
        ] + ValidationRules::pesan());

        $faq = collect($v['faq'] ?? [])
            ->map(fn ($f) => ['tanya' => trim((string) ($f['tanya'] ?? '')), 'jawab' => trim((string) ($f['jawab'] ?? ''))])
            ->filter(fn ($f) => $f['tanya'] !== '' && $f['jawab'] !== '')
            ->values()
            ->all();

        return [
            'profil.latar_belakang' => $v['latar_belakang'] ?? '',
            'profil.faq' => $faq,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function simpanPortal(Request $request): array
    {
        $v = $request->validate([
            'sambutan' => ['nullable', 'string', 'max:1000'],
            'disclaimer' => ['nullable', 'string', 'max:1000'],
            'awalan_nomor' => ['required', 'string', 'regex:/^[A-Za-z]{2,6}$/'],
            'hotline' => ['nullable', 'string', 'max:40'],
        ], [
            'awalan_nomor.required' => 'Awalan nomor tiket wajib diisi.',
            'awalan_nomor.regex' => 'Awalan nomor tiket 2-6 huruf, tanpa angka atau spasi.',
        ] + ValidationRules::pesan());

        return [
            'portal.sambutan' => $v['sambutan'] ?? '',
            'portal.disclaimer' => $v['disclaimer'] ?? '',
            'portal.awalan_nomor' => strtoupper($v['awalan_nomor']),
            'portal.hotline' => $v['hotline'] ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function simpanSurel(Request $request): array
    {
        $v = $request->validate([
            'sapaan' => ['required', 'string', 'max:100'],
            'penutup' => ['required', 'string', 'max:100'],
            'nama_pengirim' => ['required', 'string', 'max:255'],
            'catatan_kaki' => ['nullable', 'string', 'max:500'],
        ], ValidationRules::pesan());

        return [
            'surel.sapaan' => $v['sapaan'],
            'surel.penutup' => $v['penutup'],
            'surel.nama_pengirim' => $v['nama_pengirim'],
            'surel.catatan_kaki' => $v['catatan_kaki'] ?? '',
        ];
    }

    private function simpanPengumuman(Request $request): array
    {
        $aktif = $request->boolean('aktif');

        $v = $request->validate([
            'aktif' => ['nullable', 'boolean'],
            'judul' => [Rule::requiredIf($aktif), 'nullable', 'string', 'max:255'],
            'tipe' => ['required', Rule::in(['info', 'success', 'warning', 'error'])],
            'isi' => [Rule::requiredIf($aktif), 'nullable', 'string', 'max:2000'],
        ], [
            'judul.required' => 'Judul pengumuman wajib diisi saat pengumuman aktif.',
            'isi.required' => 'Isi pengumuman wajib diisi saat pengumuman aktif.',
        ] + ValidationRules::pesan());

        return [
            'pengumuman.aktif' => $aktif,
            'pengumuman.judul' => $v['judul'] ?? '',
            'pengumuman.tipe' => $v['tipe'],
            'pengumuman.isi' => $v['isi'] ?? '',
        ];
    }
}
