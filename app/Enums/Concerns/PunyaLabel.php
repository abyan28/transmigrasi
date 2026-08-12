<?php

namespace App\Enums\Concerns;

/**
 * Perilaku bersama untuk seluruh enum pilihan baku sistem.
 *
 * Dipakai agar setiap enum tidak perlu menulis ulang cara menyusun daftar
 * pilihan untuk dropdown maupun cara membaca nilai dari database.
 *
 * Label sengaja dibuat sama dengan nilai yang tersimpan, karena nilai enum
 * pada proyek ini memang sudah berbahasa Indonesia dan layak tampil apa adanya
 * (agents/data-dictionary.md bagian 11). Enum yang butuh label berbeda cukup
 * menimpa metode label().
 */
trait PunyaLabel
{
    /**
     * Teks yang tampil di antarmuka untuk nilai ini.
     *
     * @return string Label berbahasa Indonesia
     */
    public function label(): string
    {
        return $this->value;
    }

    /**
     * Daftar seluruh nilai enum sebagai array biasa.
     *
     * @return array<int, string> Nilai enum
     */
    public static function nilai(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Daftar pilihan untuk dropdown, berupa peta nilai ke label.
     *
     * @return array<string, string> Peta nilai ke label
     */
    public static function opsi(): array
    {
        $hasil = [];

        foreach (self::cases() as $kasus) {
            $hasil[$kasus->value] = $kasus->label();
        }

        return $hasil;
    }

    /**
     * Mengubah teks menjadi enum, mengembalikan null bila tidak dikenali.
     *
     * Berguna saat membaca data lama yang mungkin memuat nilai di luar daftar.
     *
     * @param  string|null  $nilai  Teks yang akan diubah
     * @return static|null Enum yang cocok, atau null
     */
    public static function dari(?string $nilai): ?static
    {
        if ($nilai === null) {
            return null;
        }

        return self::tryFrom($nilai);
    }

    /**
     * Memeriksa apakah nilai ini sama dengan salah satu yang diberikan.
     *
     * @param  self  ...$pembanding  Nilai enum pembanding
     * @return bool True bila cocok dengan salah satunya
     */
    public function salahSatuDari(self ...$pembanding): bool
    {
        return in_array($this, $pembanding, true);
    }
}
