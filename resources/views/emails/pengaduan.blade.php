@extends('emails.layout', ['judul' => $baru ? 'Pengaduan Diterima' : 'Perkembangan Pengaduan'])

@section('content')
    <p style="margin:0 0 16px;">{{ App\Support\KontenSistem::teks('surel.sapaan') }} {{ $pengaduan->nama_pelapor }},</p>
    <p style="margin:0 0 16px;">
        @if ($baru)
            Pengaduan Anda telah diterima dan akan ditinjau petugas.
        @else
            Status pengaduan Anda telah diperbarui menjadi <strong>{{ $pengaduan->status->label() }}</strong>.
        @endif
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;border:1px solid #e4e7ec;border-radius:8px;background:#f9fafb;">
        <tr><td style="padding:10px 14px;color:#667085;">Nomor pengaduan</td><td style="padding:10px 14px;font-family:monospace;font-weight:700;">{{ $pengaduan->nomor_pengaduan }}</td></tr>
        <tr><td style="padding:10px 14px;color:#667085;">Perihal</td><td style="padding:10px 14px;font-weight:700;">{{ $pengaduan->judul }}</td></tr>
        <tr><td style="padding:10px 14px;color:#667085;">Status</td><td style="padding:10px 14px;">{{ $pengaduan->status->label() }}</td></tr>
    </table>
    <p style="margin:0 0 22px;">Simpan nomor pengaduan tersebut untuk memeriksa perkembangan melalui halaman lacak pengaduan.</p>
    <p style="margin:0;">
        {{ App\Support\KontenSistem::teks('surel.penutup') }}<br>
        <strong>{{ App\Support\KontenSistem::teks('surel.nama_pengirim') }}</strong>
    </p>
@endsection
