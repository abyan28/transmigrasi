@extends('emails.layout', ['judul' => 'Verifikasi Email Baru'])

@section('content')
    <p style="margin:0 0 16px;">{{ App\Support\KontenSistem::teks('surel.sapaan') }} {{ $name }},</p>
    <p style="margin:0 0 16px;">
        Konfirmasikan <strong>{{ $newEmail }}</strong> sebagai email login baru akun {{ App\Support\KontenSistem::namaAplikasi() }} Anda.
        Email login tidak berubah sampai konfirmasi selesai.
    </p>
    <p style="margin:22px 0;text-align:center;">
        <a href="{{ route('email-change.show', ['token' => $token]) }}" style="display:inline-block;border-radius:8px;background:#0b6bcb;color:#ffffff;padding:12px 20px;text-decoration:none;font-weight:700;">Konfirmasi Email Baru</a>
    </p>
    <p style="margin:0 0 16px;">Tautan ini berlaku {{ $expiresInMinutes }} menit dan hanya dapat digunakan satu kali.</p>
    <p style="margin:0 0 22px;">Jika Anda tidak meminta perubahan ini, abaikan pesan ini dan hubungi admin.</p>
    <p style="margin:0;">
        {{ App\Support\KontenSistem::teks('surel.penutup') }}<br>
        <strong>{{ App\Support\KontenSistem::teks('surel.nama_pengirim') }}</strong>
    </p>
@endsection
