<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul ?? App\Support\KontenSistem::namaAplikasi() }}</title>
</head>
<body style="margin:0;background:#f4f6f8;color:#344054;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 12px;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #e4e7ec;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="background:#0b2748;padding:22px 28px;color:#ffffff;">
                        <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#d7b76d;">{{ App\Support\KontenSistem::teks('kop.kementerian') }}</div>
                        <div style="margin-top:5px;font-size:19px;font-weight:700;">{{ App\Support\KontenSistem::teks('kop.dinas') }}</div>
                        <div style="margin-top:5px;font-size:12px;color:#d0d5dd;">{{ App\Support\KontenSistem::teks('kop.alamat') }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;line-height:1.6;font-size:14px;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td style="border-top:1px solid #e4e7ec;padding:18px 28px;background:#f9fafb;font-size:12px;color:#667085;line-height:1.5;">
                        {{ App\Support\KontenSistem::teks('surel.catatan_kaki') }}<br>
                        {{ App\Support\KontenSistem::teks('kop.kontak') }}
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
