@extends('emails.layout', ['judul' => $akunBaru ? 'Akun Petugas Baru' : 'Kata Sandi Disetel Ulang'])

@section('content')
    <p style="margin:0 0 16px;">{{ App\Support\KontenSistem::teks('surel.sapaan') }} {{ $nama }},</p>
    <p style="margin:0 0 16px;">
        @if ($akunBaru)
            Akun Anda pada {{ App\Support\KontenSistem::namaAplikasi() }} telah dibuat oleh Admin.
        @else
            Kata sandi akun {{ App\Support\KontenSistem::namaAplikasi() }} Anda telah disetel ulang oleh Admin.
        @endif
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;border:1px solid #e4e7ec;border-radius:8px;background:#f9fafb;">
        <tr><td style="padding:10px 14px;color:#667085;">Email</td><td style="padding:10px 14px;font-weight:700;">{{ $email }}</td></tr>
        <tr><td style="padding:10px 14px;color:#667085;">Kata sandi sementara</td><td style="padding:10px 14px;font-family:monospace;font-size:16px;font-weight:700;">{{ $sandiSementara }}</td></tr>
    </table>
    <p style="margin:0 0 22px;">
        Saat masuk pertama kali, Anda akan diminta membuat kata sandi baru
        @if ($akunBaru) beserta username @endif. Kata sandi sementara hanya berlaku untuk sekali masuk.
    </p>
    <p style="margin:0;">
        {{ App\Support\KontenSistem::teks('surel.penutup') }}<br>
        <strong>{{ App\Support\KontenSistem::teks('surel.nama_pengirim') }}</strong>
    </p>
@endsection
