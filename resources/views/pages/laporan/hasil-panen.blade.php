{{--
    Laporan Hasil Panen.

    Kerangka saja per 2026-08-28. Format kolom menyusul dari dinas; berkas
    rujukan "Lap. Akhir Panen Jagung Polri MT. I 2025.pdf" di refs/ belum
    terbaca (mesin tak punya perender PDF).

    Dasar periode laporan ini adalah TAHUN PENGADAAN BANTUAN, bukan tahun
    panen (rules.md 9 poin 16). Bantuan APBN/APBD 2025 yang ditanam dan
    dipanen 2026 tetap dilaporkan sebagai capaian 2025. Rantai penelusurannya
    hasil_panen -> penanaman.saprotan_id -> saprotan.tahun_pengadaan.
--}}
@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="hasil-panen"
        cakupan="Seluruh satuan permukiman di kawasan transmigrasi Kobalima Timur."
        dasar-periode="Dikelompokkan menurut tahun pengadaan bantuan (tahun anggaran), bukan tahun panen."
        sumber-label="Data Hasil Panen" :sumber-url="route('panen.index')">
        <x-slot:catatan>
            Laporan ini dua bagian terpisah. Bagian benih menampilkan rantai
            penuh sampai hasil panennya. Bagian pupuk hanya menampilkan
            penyalurannya, sebab pupuk memang tidak tertaut ke satu penanaman
            tertentu.
        </x-slot:catatan>
    </x-sim.kerangka-laporan>
@endsection
