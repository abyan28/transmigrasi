@extends('emails.layout', ['judul' => 'Kode Pemulihan Kata Sandi'])

@section('content')
    <p style="margin:0 0 16px;">{{ App\Support\KontenSistem::teks('surel.sapaan') }}</p>
    <p style="margin:0 0 16px;">
        Kami menerima permintaan pemulihan kata sandi untuk akun Anda pada
        {{ App\Support\KontenSistem::namaAplikasi() }}. Masukkan kode berikut pada halaman verifikasi:
    </p>
    <div style="margin:20px 0;padding:16px;border:1px solid #d7b76d;border-radius:8px;background:#fffbeb;text-align:center;font-family:monospace;font-size:28px;font-weight:700;letter-spacing:8px;color:#0b2748;">
        {{ $kode }}
    </div>
    <p style="margin:0 0 16px;">
        Kode ini berlaku {{ $menitBerlaku }} menit dan hanya dapat dipakai satu kali.
    </p>
    <p style="margin:0 0 22px;"><strong>Bila Anda tidak meminta pemulihan ini</strong>, abaikan surel ini; kata sandi Anda tidak berubah.</p>
    <p style="margin:0;">
        {{ App\Support\KontenSistem::teks('surel.penutup') }}<br>
        <strong>{{ App\Support\KontenSistem::teks('surel.nama_pengirim') }}</strong>
    </p>
@endsection
