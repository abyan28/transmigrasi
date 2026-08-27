{{--
    Laporan Saprotan.

    Kerangka saja per 2026-08-28. Mengikuti berkas rujukan
    "laporan saprotan.jpeg" di refs/. Format kolomnya menyusul dari dinas.

    Laporan terpisah dari Laporan Alsintan, mengikuti dua berkas rujukan
    terpisah, bukan digabung.
--}}
@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="saprotan"
        cakupan="Penyaluran benih, pupuk, pestisida, dan mulsa kepada petani di kawasan Kobalima Timur."
        dasar-periode="Dikelompokkan menurut tahun pengadaan bantuan (tahun anggaran), sesuai kolom tahun_pengadaan pada data saprotan."
        sumber-label="Data Saprotan" :sumber-url="route('saprotan.index')" />
@endsection
