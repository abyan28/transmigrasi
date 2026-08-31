<?php

use App\Support\DummyData;

describe('Format Nominal Uang', function () {
    it('menerapkan direktif x-uang pada seluruh field nominal uang', function () {
        // 1. Form Transmigran: Pendapatan Kepala Keluarga & Anggota Keluarga
        $isiTransmigran = $this->get(route('transmigran.index'))->getContent();

        // Pendapatan Kepala Keluarga
        expect($isiTransmigran)
            ->toContain('name="pendapatan_per_bulan"')
            ->toContain('x-uang')
            ->toContain('inputmode="numeric"');

        // Pendapatan Anggota Keluarga (repeater)
        expect($isiTransmigran)
            ->toContain('anggota_keluarga[${i}][pendapatan_per_bulan]');

        // 2. Form Hasil Panen: Harga Jual
        $isiPanen = $this->get(route('panen.index'))->getContent();
        expect($isiPanen)
            ->toContain('name="harga_jual"')
            ->toContain('x-uang')
            ->toContain('inputmode="numeric"');

        // 3. Form Satuan Permukiman: Ongkos Rute Aksesibilitas
        $isiSp = $this->get(route('sp.index'))->getContent();
        expect($isiSp)
            ->toContain('rute_aksesibilitas[${i}][ongkos_rp]');
    });

    it('mempertahankan prefix Rp sebagai adornment pada seluruh field uang', function () {
        $isiTransmigran = $this->get(route('transmigran.index'))->getContent();
        $isiPanen = $this->get(route('panen.index'))->getContent();

        // Cek keberadaan span prefix Rp dan padding pl-10 pada form
        expect($isiTransmigran)->toContain('Rp');
        expect($isiTransmigran)->toContain('pl-10');
        expect($isiPanen)->toContain('Rp');
        expect($isiPanen)->toContain('pl-10');
    });

    it('tidak mengubah input angka non-uang menjadi x-uang', function () {
        // Form Lahan: luas ha
        $isiLahan = $this->get(route('lahan.index'))->getContent();
        expect($isiLahan)
            ->toContain('type="number" id="tambah_luas"')
            ->not->toContain('id="tambah_luas" name="luas" x-uang');

        // Form Alsintan: jumlah unit & tahun
        $isiAlsintan = $this->get(route('alsintan.index'))->getContent();
        expect($isiAlsintan)
            ->toContain('type="number" id="tambah_jumlah_total"')
            ->toContain('type="number" id="tambah_tahun_pengadaan"');

        // Form Saprotan: jumlah & tahun
        $isiSaprotan = $this->get(route('saprotan.index'))->getContent();
        expect($isiSaprotan)
            ->toContain('type="number" id="tambah_jumlah_total"')
            ->toContain('type="number" id="tambah_tahun_pengadaan"');

        // Form Panen: realisasi_panen (ha), puso (ha), produktivitas
        $isiPanen = $this->get(route('panen.index'))->getContent();
        expect($isiPanen)
            ->toContain('type="number" id="tambah_realisasi_panen"')
            ->toContain('type="number" id="tambah_puso"')
            ->toContain('type="number" id="tambah_produktivitas"');
    });
});
