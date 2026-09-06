@extends('emails.layout', ['judul' => 'Pemberitahuan Perubahan Akun'])

@section('content')
    <p style="margin:0 0 16px;">{{ App\Support\KontenSistem::teks('surel.sapaan') }} {{ $name }},</p>
    <p style="margin:0 0 16px;">{{ $noticeText }}</p>
    <p style="margin:0 0 22px;">Jika Anda tidak mengenali tindakan ini, segera hubungi admin.</p>
    <p style="margin:0;">
        {{ App\Support\KontenSistem::teks('surel.penutup') }}<br>
        <strong>{{ App\Support\KontenSistem::teks('surel.nama_pengirim') }}</strong>
    </p>
@endsection
