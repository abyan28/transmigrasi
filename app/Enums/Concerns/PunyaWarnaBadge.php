<?php

namespace App\Enums\Concerns;

/**
 * Perilaku bersama untuk enum yang tampil sebagai badge berwarna.
 *
 * Warna mengikuti token semantik TailAdmin agar komponen badge bawaan langsung
 * cocok tanpa modifikasi. Daftar pemetaan warna ada pada agents/ui-spec.md
 * bagian 6.6.
 *
 * Enum yang memakai trait ini WAJIB menyediakan metode warna().
 */
trait PunyaWarnaBadge
{
    /**
     * Kelas warna badge untuk nilai ini.
     *
     * Nilai yang dikenali komponen badge: `gray`, `teal`, `success`,
     * `warning`, `error`.
     *
     * @return string Nama warna badge
     */
    abstract public function warna(): string;

    /**
     * Daftar pilihan lengkap beserta warnanya.
     *
     * Dipakai komponen yang perlu menampilkan seluruh kemungkinan status
     * sekaligus, misalnya legenda grafik atau laci filter.
     *
     * @return array<int, array<string, string>> Daftar nilai, label, dan warna
     */
    public static function opsiBerwarna(): array
    {
        $hasil = [];

        foreach (self::cases() as $kasus) {
            $hasil[] = [
                'nilai' => $kasus->value,
                'label' => $kasus->label(),
                'warna' => $kasus->warna(),
            ];
        }

        return $hasil;
    }
}
