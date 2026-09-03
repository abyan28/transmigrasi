{{--
    Surel kode pemulihan kata sandi. Teks polos, tanpa gambar maupun tautan
    lacak: sebagian petugas membukanya di jaringan lokus yang lemah.
--}}
<p>Halo,</p>

<p>
    Kami menerima permintaan pemulihan kata sandi untuk akun Anda pada
    DIGITRANS Kobalima Timur, Kabupaten Malaka. Masukkan kode berikut pada halaman
    verifikasi:
</p>

<p style="font-size: 24px; font-weight: bold; letter-spacing: 6px; margin: 16px 0;">
    {{ $kode }}
</p>

<p>
    Kode ini berlaku {{ $menitBerlaku }} menit dan hanya dapat dipakai satu
    kali. Bila kedaluwarsa, minta kode baru dari halaman yang sama (paling
    banyak tiga kali dalam satu jam).
</p>

<p>
    <strong>Bila Anda tidak meminta pemulihan ini</strong>, abaikan surel ini;
    kata sandi Anda tidak berubah. Anda juga dapat menghubungi admin untuk
    penyetelan ulang secara langsung bila sinyal di lokus sedang tidak memadai.
</p>

<p>
    Salam,<br>
    Tim DIGITRANS Kobalima Timur
</p>
