{{--
    Surel kata sandi sementara akun petugas. Teks polos, tanpa tautan lacak.
--}}
<p>Halo {{ $nama }},</p>

@if ($akunBaru)
    <p>
        Akun Anda pada SIM Transmigrasi Kabupaten Malaka telah dibuat oleh
        admin. Berikut kredensial masuk sementara Anda:
    </p>
@else
    <p>
        Kata sandi akun SIM Transmigrasi Anda telah disetel ulang oleh admin.
        Berikut kata sandi sementara Anda:
    </p>
@endif

<p style="margin: 16px 0; line-height: 1.8;">
    <strong>Email:</strong> {{ $email }}<br>
    <strong>Kata sandi sementara:</strong>
    <span style="font-family: monospace; font-size: 16px;">{{ $sandiSementara }}</span>
</p>

<p>
    Saat masuk pertama kali, Anda akan diminta membuat kata sandi baru
    @if ($akunBaru) beserta username @endif sebelum dapat memakai sistem.
    Kata sandi sementara di atas hanya berlaku untuk sekali masuk.
</p>

<p>
    Bila memungkinkan, admin juga menyerahkan kata sandi ini secara langsung.
    Surel ini adalah salinan cadangan.
</p>

<p>
    Salam,<br>
    Tim SIM Transmigrasi
</p>
